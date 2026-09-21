<?php
$activePage = 'import-siswa';
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
            
            // Skip header row
            for ($i = 1; $i < count($rows); $i++) {
                // Skip empty rows
                if (empty(array_filter($rows[$i], fn($value) => !is_null($value) && $value !== ''))) {
                    continue;
                }
                
                // Safely trim non-null values
                $row = array_map(fn($value) => is_string($value) ? trim($value) : $value, $rows[$i]);
                
                // Map Excel columns to database fields with validation
                $nisn = $row[2] ?? '';
                $nama_lengkap = $row[1] ?? '';
                $nik = $row[3] ?? null;
                $tempat_lahir = $row[4] ?? null;
                $tanggal_lahir = !empty($row[5]) ? date('Y-m-d', strtotime($row[5])) : null;
                $tingkat_rombel = $row[6] ?? null;
                $umur = !empty($row[7]) ? (int) filter_var($row[7], FILTER_SANITIZE_NUMBER_INT) : null;
                $jenis_kelamin = !empty($row[8]) ? ($row[8] == 'Laki-laki' ? 'L' : 'P') : null;
                $alamat = $row[9] ?? null;
                $nama_ayah = $row[10] ?? null;
                $nama_ibu = $row[11] ?? null;
                
                // Validate required fields
                if (empty($nisn) || empty($nama_lengkap)) {
                    $error_messages[] = "Skipping row $i: Missing NISN or Nama Lengkap.";
                    continue;
                }
                
                // Generate QR code with first two initials
                $inisial = '';
                if (!empty($nama_lengkap)) {
                    $words = explode(' ', $nama_lengkap);
                    $inisial = strtoupper(($words[0][0] ?? '') . ($words[1][0] ?? ($words[0][1] ?? '')));
                }
                $kodeAcak = rand(10000, 99999);
                $qrcode_text = $inisial . $kodeAcak;
                $qrcode_file = "qrcodes/" . $qrcode_text . ".png";
                
                // Create qrcodes directory in admin/ if it doesn't exist
                if (!file_exists('qrcodes')) {
                    mkdir('qrcodes', 0777, true);
                }
                
                // Generate QR code
                QRcode::png($qrcode_text, $qrcode_file, QR_ECLEVEL_L, 3);
                
                // Prepare SQL statement
                $sql = "INSERT INTO siswa (nisn, nama_lengkap, nik, tempat_lahir, tanggal_lahir, tingkat_rombel, umur, jenis_kelamin, alamat, nama_ayah, nama_ibu, qrcode)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ssssssisssss", $nisn, $nama_lengkap, $nik, $tempat_lahir, $tanggal_lahir, $tingkat_rombel, $umur, $jenis_kelamin, $alamat, $nama_ayah, $nama_ibu, $qrcode_text);
                
                if ($stmt->execute()) {
                    $success_count++;
                } else {
                    $error_messages[] = "Error importing data for $nama_lengkap: " . $stmt->error;
                }
                $stmt->close();
            }
            
            // Store results for display
            if ($success_count > 0) {
                $display_success = "Successfully imported $success_count student records!";
            }
            if (!empty($error_messages)) {
                $display_errors = implode('<br>', $error_messages);
            }
        } catch (Exception $e) {
            $display_errors = "Error processing Excel file: " . $e->getMessage();
        }
        
        // Clear session to avoid duplicate messages
        unset($_SESSION['import_success']);
        unset($_SESSION['import_errors']);
    } else {
        $display_errors = "No file uploaded or upload error occurred.";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Import Data Siswa - Sistem Presensi MIDU</title>
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
                    <a href="#" class="btn btn-outline-danger btn-sm ms-2">Logout</a>
                </div>
            </div>
        </nav>

        <div class="container-fluid">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Import Data Siswa</h5>
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
                            <li>File harus berformat Excel (.xlsx atau .xls) <a href="uploads/template_siswa.xlsx" class="btn btn-sm btn-success">Unduh Template Excel</a></li>
                            <li>Urutan kolom harus sesuai: No, Nama Lengkap, NISN, NIK, Tempat Lahir, Tanggal Lahir, Tingkat - Rombel, Umur, Jenis Kelamin, Alamat, Nama Ayah, Nama Ibu</li>
                            <li>Pastikan data lengkap dan format tanggal valid</li>
                            <li>Hapus baris kosong di akhir file Excel untuk menghindari error</li>
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
                    window.location.href = 'data-siswa.php';
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