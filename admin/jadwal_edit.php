<?php
$activePage = 'edit_jadwal';
include 'auth.php';
include 'config.php';


// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $jenis_pengguna = $conn->real_escape_string($_POST['jenis_pengguna']);
    $hari = $conn->real_escape_string($_POST['hari']);
    $jam_masuk = $conn->real_escape_string($_POST['jam_masuk']);
    $jam_pulang = $conn->real_escape_string($_POST['jam_pulang']);

    // Validate input
    if (empty($jenis_pengguna) || empty($hari) || empty($jam_masuk) || empty($jam_pulang)) {
        $error = "Semua kolom harus diisi!";
    } else {
        if ($id > 0) {
            // Update existing record
            $sql = "UPDATE jadwal SET jenis_pengguna='$jenis_pengguna', hari='$hari', jam_masuk='$jam_masuk', jam_pulang='$jam_pulang' WHERE id=$id";
        } else {
            // Insert new record
            $sql = "INSERT INTO jadwal (jenis_pengguna, hari, jam_masuk, jam_pulang) VALUES ('$jenis_pengguna', '$hari', '$jam_masuk', '$jam_pulang')";
        }

        if ($conn->query($sql) === TRUE) {
            echo "<script>
                document.addEventListener('DOMContentLoaded', function() {
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil',
                        text: 'Jadwal berhasil disimpan!',
                        confirmButtonColor: '#2c6e49'
                    }).then(function() {
                        window.location.href = 'data-jadwal.php';
                    });
                });
            </script>";
        } else {
            echo "<script>
                document.addEventListener('DOMContentLoaded', function() {
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal',
                        text: 'Gagal menyimpan jadwal: " . $conn->error . "',
                        confirmButtonColor: '#2c6e49'
                    });
                });
            </script>";
        }
    }
}

// Fetch existing data if editing
$jadwal = [];
if (isset($_GET['id']) && (int)$_GET['id'] > 0) {
    $id = (int)$_GET['id'];
    $result = $conn->query("SELECT * FROM jadwal WHERE id=$id");
    if ($result->num_rows > 0) {
        $jadwal = $result->fetch_assoc();
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Jadwal - Sistem Presensi MIDU</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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
                    <h5 class="mb-0">Edit Jadwal</h5>
                </div>
                <div class="card-body">
                    <?php if (isset($error)): ?>
                        <script>
                            document.addEventListener('DOMContentLoaded', function() {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Gagal',
                                    text: '<?php echo $error; ?>',
                                    confirmButtonColor: '#2c6e49'
                                });
                            });
                        </script>
                    <?php endif; ?>
                    <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="POST">
                        <input type="hidden" name="id" value="<?php echo isset($jadwal['id']) ? $jadwal['id'] : ''; ?>">
                        <div class="mb-3" style="display: none;">
                            <label for="jenis_pengguna" class="form-label">Jenis Pengguna</label>
                            <select class="form-select" id="jenis_pengguna" name="jenis_pengguna" required>
                                <option value="siswa" <?php echo (isset($jadwal['jenis_pengguna']) && $jadwal['jenis_pengguna'] == 'siswa') ? 'selected' : ''; ?>>Siswa</option>
                                <option value="guru" <?php echo (isset($jadwal['jenis_pengguna']) && $jadwal['jenis_pengguna'] == 'guru') ? 'selected' : ''; ?>>Guru</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="hari" class="form-label">Hari</label>
                            <input type="text" class="form-control" value="<?php echo isset($jadwal['hari']) ? $jadwal['hari'] : ''; ?>" disabled>
                            <input type="hidden" class="form-control" id="hari" name="hari" value="<?php echo isset($jadwal['hari']) ? $jadwal['hari'] : ''; ?>" >
                            
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="jam_masuk" class="form-label">Jam Masuk</label>
                            <input type="time" class="form-control" id="jam_masuk" name="jam_masuk" value="<?php echo isset($jadwal['jam_masuk']) ? $jadwal['jam_masuk'] : ''; ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="jam_pulang" class="form-label">Jam Pulang</label>
                            <input type="time" class="form-control" id="jam_pulang" name="jam_pulang" value="<?php echo isset($jadwal['jam_pulang']) ? $jadwal['jam_pulang'] : ''; ?>" required>
                        </div>
                        <button type="submit" class="btn btn-success">Simpan</button>
                        <a href="data-jadwal.php" class="btn btn-warning">Kembali</a>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script>
        const sidebar = document.getElementById('sidebar');
        const mainContent = document.getElementById('mainContent');
        const toggleBtn = document.getElementById('toggleSidebarBtn');

        toggleBtn.addEventListener('click', function () {
            if (window.innerWidth <= 768) {
                sidebar.classList.toggle('active'); // mobile
            } else {
                sidebar.classList.toggle('collapsed'); // desktop
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
    </script>
</body>
</html>