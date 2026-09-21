<?php
$activePage = 'import-guru';
require '../vendor/autoload.php';
include 'auth.php';
include 'config.php';
include 'lib/phpqrcode/qrlib.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$display_success = null;
$display_errors = null;

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES["excel_file"])) {
    $file = $_FILES["excel_file"]["tmp_name"];
    if ($file) {
        try {
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file);
            $worksheet = $spreadsheet->getActiveSheet();
            $rows = $worksheet->toArray();
            
            $success_count = 0;
            $error_messages = [];
            
            // Log untuk debug
            $log_message = date('Y-m-d H:i:s') . " - Mulai impor file Excel\n";
            file_put_contents('error_log.txt', $log_message, FILE_APPEND);
            
            // Skip header row
            for ($i = 1; $i < count($rows); $i++) {
                // Skip empty rows
                if (empty(array_filter($rows[$i], fn($value) => !is_null($value) && $value !== ''))) {
                    $log_message = date('Y-m-d H:i:s') . " - Skipping row $i: Baris kosong\n";
                    file_put_contents('error_log.txt', $log_message, FILE_APPEND);
                    continue;
                }
                
                // Safely trim non-null values
                $row = array_map(fn($value) => is_string($value) ? trim($value) : $value, $rows[$i]);
                
                // Log isi baris
                $log_message = date('Y-m-d H:i:s') . " - Row $i: " . json_encode($row) . "\n";
                file_put_contents('error_log.txt', $log_message, FILE_APPEND);
                
                // Map Excel columns to database fields with validation
                $nip = $row[2] ?? null;
                $nama_lengkap = $row[1] ?? '';
                $nik = $row[3] ?? null;
                $tempat_lahir = $row[4] ?? null;
                $tanggal_lahir = !empty($row[5]) ? date('Y-m-d', strtotime($row[5])) : null;
                $jabatan = $row[6] ?? null;
                $jenis_kelamin = !empty($row[8]) ? ($row[8] == 'Laki-laki' ? 'L' : 'P') : null;
                $alamat = $row[9] ?? null;
                $no_hp = $row[10] ?? null;
                $email = $row[11] ?? null;
                $foto = !empty($row[12]) ? $row[12] : 'default.png';
                
                // Validate required fields
                if (empty($nama_lengkap)) {
                    $error_messages[] = "Skipping row $i: Missing Nama Lengkap.";
                    $log_message = date('Y-m-d H:i:s') . " - Skipping row $i: Missing Nama Lengkap\n";
                    file_put_contents('error_log.txt', $log_message, FILE_APPEND);
                    continue;
                }
                
                // Validate NIP uniqueness if provided
                if (!empty($nip)) {
                    $stmt = $conn->prepare("SELECT COUNT(*) FROM guru WHERE nip = ?");
                    $stmt->bind_param("s", $nip);
                    $stmt->execute();
                    $result = $stmt->get_result()->fetch_row()[0];
                    $stmt->close();
                    if ($result > 0) {
                        $error_messages[] = "Skipping row $i: NIP $nip sudah ada.";
                        $log_message = date('Y-m-d H:i:s') . " - Skipping row $i: NIP $nip sudah ada\n";
                        file_put_contents('error_log.txt', $log_message, FILE_APPEND);
                        continue;
                    }
                }
                
                // Generate QR code with first two initials
                $inisial = '';
                if (!empty($nama_lengkap)) {
                    $words = explode(' ', $nama_lengkap);
                    $inisial = strtoupper(($words[0][0] ?? '') . ($words[1][0] ?? ($words[0][1] ?? '')));
                }
                $kodeAcak = rand(10000, 99999);
                $qrcode_text = $inisial . $kodeAcak;
                
                // Validate QR code uniqueness
                $stmt = $conn->prepare("SELECT COUNT(*) FROM guru WHERE qrcode = ?");
                $stmt->bind_param("s", $qrcode_text);
                $stmt->execute();
                $result = $stmt->get_result()->fetch_row()[0];
                $stmt->close();
                $attempts = 0;
                $max_attempts = 5;
                while ($result > 0 && $attempts < $max_attempts) {
                    $kodeAcak = rand(10000, 99999);
                    $qrcode_text = $inisial . $kodeAcak;
                    $stmt = $conn->prepare("SELECT COUNT(*) FROM guru WHERE qrcode = ?");
                    $stmt->bind_param("s", $qrcode_text);
                    $stmt->execute();
                    $result = $stmt->get_result()->fetch_row()[0];
                    $stmt->close();
                    $attempts++;
                }
                if ($result > 0) {
                    $error_messages[] = "Skipping row $i: Gagal menghasilkan QR code unik untuk $nama_lengkap.";
                    $log_message = date('Y-m-d H:i:s') . " - Skipping row $i: Gagal menghasilkan QR code unik\n";
                    file_put_contents('error_log.txt', $log_message, FILE_APPEND);
                    continue;
                }
                
                // Create qrcodes directory in admin/ if it doesn't exist
                if (!file_exists('qrcodes')) {
                    mkdir('qrcodes', 0777, true);
                }
                
                // Generate QR code
                $qrcode_file = "qrcodes/" . $qrcode_text . ".png";
                QRcode::png($qrcode_text, $qrcode_file, QR_ECLEVEL_L, 3);
                
                // Prepare SQL statement
                $sql = "INSERT INTO guru (nip, nama_lengkap, nik, tempat_lahir, tanggal_lahir, jabatan, jenis_kelamin, alamat, no_hp, email, foto, qrcode)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ssssssssssss", $nip, $nama_lengkap, $nik, $tempat_lahir, $tanggal_lahir, $jabatan, $jenis_kelamin, $alamat, $no_hp, $email, $foto, $qrcode_text);
                
                if ($stmt->execute()) {
                    $success_count++;
                    $log_message = date('Y-m-d H:i:s') . " - Berhasil mengimpor data untuk $nama_lengkap\n";
                    file_put_contents('error_log.txt', $log_message, FILE_APPEND);
                } else {
                    $error_messages[] = "Error importing data for $nama_lengkap: " . $stmt->error;
                    $log_message = date('Y-m-d H:i:s') . " - Error importing data for $nama_lengkap: " . $stmt->error . "\n";
                    file_put_contents('error_log.txt', $log_message, FILE_APPEND);
                }
                $stmt->close();
            }
            
            // Store results for display
            if ($success_count > 0) {
                $display_success = "Successfully imported $success_count teacher records!";
                $log_message = date('Y-m-d H:i:s') . " - Total berhasil: $success_count data guru\n";
                file_put_contents('error_log.txt', $log_message, FILE_APPEND);
            }
            if (!empty($error_messages)) {
                $display_errors = implode('<br>', $error_messages);
            }
        } catch (Exception $e) {
            $display_errors = "Error processing Excel file: " . $e->getMessage();
            $log_message = date('Y-m-d H:i:s') . " - Error processing Excel file: " . $e->getMessage() . "\n";
            file_put_contents('error_log.txt', $log_message, FILE_APPEND);
        }
        
        // Clear session to avoid duplicate messages
        unset($_SESSION['import_success']);
        unset($_SESSION['import_errors']);
    } else {
        $display_errors = "No file uploaded or upload error occurred.";
        $log_message = date('Y-m-d H:i:s') . " - No file uploaded or upload error occurred\n";
        file_put_contents('error_log.txt', $log_message, FILE_APPEND);
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Import Data Guru - Sistem Presensi MIDU</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f1f5f9;
            overflow-x: hidden;
        }
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            width: 250px;
            height: 100vh;
            background-color: #2c6e49;
            color: white;
            padding-top: 20px;
            transition: transform 0.3s ease;
            z-index: 1000;
            transform: translateX(0);
        }
        .sidebar.collapsed {
            transform: translateX(-250px);
        }
        .sidebar .nav-link {
            color: #d4edda;
            padding: 10px 20px;
            margin: 5px 10px;
            border-radius: 5px;
        }
        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            background-color: #3d8b5e;
            color: white;
        }
        .sidebar .nav-link i {
            margin-right: 10px;
        }
        .content {
            transition: margin-left 0.3s ease;
            margin-left: 250px;
            padding: 20px;
        }
        .content.expanded {
            margin-left: 0;
        }
        .navbar-brand {
            color: #2c6e49 !important;
            font-weight: bold;
        }
        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .card-header {
            background-color: #2c6e49;
            color: white;
            border-radius: 10px 10px 0 0;
        }
        .logo {
            width: 100px;
            display: block;
            margin: 0 auto 20px;
        }
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-250px);
            }
            .sidebar.active {
                transform: translateX(0);
            }
            .content {
                margin-left: 0 !important;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <?php include 'sidebar.php'; ?>

    <!-- Main Content -->
    <div class="content" id="mainContent">
        <nav class="navbar navbar-light bg-white shadow-sm mb-4">
            <div class="container-fluid">
                <button class="btn btn-outline-success" id="toggleSidebarBtn">
                    <i class="fas fa-bars"></i>
                </button>
                <span class="navbar-brand ms-3">Sistem Presensi MIDU</span>
                <div>
                    <span class="navbar-text">Admin</span>
                    <a href="logout.php" class="btn btn-outline-danger btn-sm ms-2">Logout</a>
                </div>
            </div>
        </nav>

        <div class="container-fluid">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Import Data Guru</h5>
                </div>
                <div class="card-body">
                    <form action="" method="POST" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label for="excel_file" class="form-label">Pilih File Excel</label>
                            <input type="file" class="form-control" id="excel_file" name="excel_file" accept=".xlsx,.xls" required>
                        </div>
                        <button type="submit" class="btn btn-primary">Import Data</button>
                    </form>
                    <div class="mt-3">
                        <p><strong>Catatan:</strong></p>
                        <ul>
                            <li>File harus berformat Excel (.xlsx atau .xls) <a href="Uploads/template_guru.xlsx" class="btn btn-sm btn-success">Unduh Template Excel</a></li>
                            <li>Urutan kolom harus sesuai: No, Nama Lengkap, NIP, NIK, Tempat Lahir, Tanggal Lahir, Jabatan, Jenis Kelamin, Alamat, No HP, Email, Foto</li>
                            <li>Pastikan data lengkap dan format tanggal valid (contoh: 2023-12-31)</li>
                            <li>NIP bersifat opsional, tetapi harus unik jika diisi</li>
                            <li>Kolom Foto bersifat opsional; jika kosong, akan menggunakan 'default.png'</li>
                            <li>Hapus baris kosong di akhir file untuk menghindari error</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        const sidebar = document.getElementById('sidebar');
        const mainContent = document.getElementById('mainContent');
        const toggleBtn = document.getElementById('toggleSidebarBtn');

        toggleBtn.addEventListener('click', function () {
            if (window.innerWidth <= 768) {
                sidebar.classList.toggle('active');
            } else {
                sidebar.classList.toggle('collapsed');
                mainContent.classList.toggle('expanded');
            }
        });

        window.addEventListener('click', function(e) {
            if (window.innerWidth <= 768) {
                if (!sidebar.contains(e.target) && !toggleBtn.contains(e.target)) {
                    sidebar.classList.remove('active');
                }
            }
        });

        // Display SweetAlert messages
        <?php
        if ($display_success) {
            echo "Swal.fire({
                icon: 'success',
                title: 'Sukses',
                html: '" . addslashes($display_success) . "',
                showConfirmButton: true
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = 'data-guru.php';
                }
            });";
        }
        if ($display_errors) {
            echo "Swal.fire({
                icon: 'error',
                title: 'Error',
                html: '" . addslashes($display_errors) . "',
                showConfirmButton: true
            });";
        }
        ?>
    </script>
</body>
</html>
<?php $conn->close(); ?>