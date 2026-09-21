<?php
session_start();
$activePage = 'data-presensi';
include 'auth.php';
include 'config.php';

$presensi = null;
$siswa = $conn->query("SELECT id, nama_lengkap FROM siswa ORDER BY nama_lengkap ASC");
$guru = $conn->query("SELECT id, nama_lengkap FROM guru ORDER BY nama_lengkap ASC");

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
  $_SESSION['error'] = 'ID presensi tidak valid.';
  header('Location: data-presensi.php');
  exit;
}

$id = intval($_GET['id']);
$stmt = $conn->prepare("
  SELECT p.*, 
         IF(p.jenis_pengguna='siswa', s.nama_lengkap, g.nama_lengkap) AS nama
  FROM presensi p
  LEFT JOIN siswa s ON p.id_siswa = s.id
  LEFT JOIN guru g ON p.id_guru = g.id
  WHERE p.id = ?
");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
  $_SESSION['error'] = 'Data presensi tidak ditemukan.';
  header('Location: data-presensi.php');
  exit;
}

$presensi = $result->fetch_assoc();
$stmt->close();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $id_siswa = !empty($_POST['id_siswa']) ? intval($_POST['id_siswa']) : null;
  $id_guru = !empty($_POST['id_guru']) ? intval($_POST['id_guru']) : null;
  $tanggal = $_POST['tanggal'] ?? '';
  $jam_masuk = !empty($_POST['jam_masuk']) ? $_POST['jam_masuk'] : null;
  $status_masuk = !empty($_POST['status_masuk']) ? $_POST['status_masuk'] : null;
  $jam_pulang = !empty($_POST['jam_pulang']) ? $_POST['jam_pulang'] : null;
  $status_pulang = !empty($_POST['status_pulang']) ? $_POST['status_pulang'] : null;
  $lokasi = !empty($_POST['lokasi']) ? $_POST['lokasi'] : null;
  $alamat = !empty($_POST['alamat']) ? $_POST['alamat'] : null;
  $jenis_pengguna = $_POST['jenis_pengguna'] ?? '';

  // Validasi
  if (empty($tanggal) || empty($jenis_pengguna) || ($jenis_pengguna == 'siswa' && !$id_siswa) || ($jenis_pengguna == 'guru' && !$id_guru)) {
    $_SESSION['error'] = 'Tanggal dan pengguna harus diisi.';
  } else {
    $stmt = $conn->prepare("
      UPDATE presensi 
      SET id_siswa = ?, id_guru = ?, tanggal = ?, jam_masuk = ?, status_masuk = ?, 
          jam_pulang = ?, status_pulang = ?, lokasi = ?, alamat = ?, jenis_pengguna = ?
      WHERE id = ?
    ");
    $stmt->bind_param("iisssssssi", $id_siswa, $id_guru, $tanggal, $jam_masuk, $status_masuk, $jam_pulang, $status_pulang, $lokasi, $alamat, $jenis_pengguna, $id);

    if ($stmt->execute()) {
      $_SESSION['success'] = 'Data presensi berhasil diperbarui.';
      header('Location: data-presensi.php');
      exit;
    } else {
      $_SESSION['error'] = 'Gagal memperbarui data presensi.';
    }
    $stmt->close();
  }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Edit Presensi - Sistem Presensi MIDU</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <style>
    body {
      background: linear-gradient(135deg, #f1f5f9 0%, #e2e8f0 100%);
      min-height: 100vh;
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
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
    .form-label {
      color: #2c6e49;
      font-weight: 500;
    }
    .footer {
      text-align: center;
      margin-top: 20px;
      color: #6c757d;
      font-size: 0.9rem;
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
  <?php include 'sidebar.php'; ?>
  <div class="content" id="mainContent">
    <nav class="navbar navbar-light bg-white shadow-sm mb-4">
      <div class="container-fluid">
        <button class="btn btn-outline-success" id="toggleSidebarBtn">
          <i class="fas fa-bars"></i>
        </button>
        <span class="navbar-brand ms-3">Edit Presensi</span>
        <div>
          <span class="navbar-text">Admin</span>
          <a href="logout.php" class="btn btn-outline-danger btn-sm ms-2">Logout</a>
        </div>
      </div>
    </nav>

    <?php if (isset($_SESSION['error'])): ?>
    <script>
      Swal.fire({
        icon: 'error',
        title: 'Gagal!',
        text: <?php echo json_encode($_SESSION['error']); ?>,
        confirmButtonColor: '#dc3545'
      });
    </script>
    <?php unset($_SESSION['error']); endif; ?>

    <div class="container-fluid">
      <div class="card">
        <div class="card-header mb-3">
          <h3 class="mb-0">Edit Data Presensi</h3>
        </div>
        <div class="card-body">
          <form method="POST" action="">
            <div class="mb-3">
              <label for="jenis_pengguna" class="form-label">Jenis Pengguna</label>
              <select class="form-control" id="jenis_pengguna" name="jenis_pengguna" required onchange="togglePengguna()">
                <option value="siswa" <?php echo $presensi['jenis_pengguna'] == 'siswa' ? 'selected' : ''; ?>>Siswa</option>
                <option value="guru" <?php echo $presensi['jenis_pengguna'] == 'guru' ? 'selected' : ''; ?>>Guru</option>
              </select>
            </div>
            <div class="mb-3" id="siswa_field" style="display: <?php echo $presensi['jenis_pengguna'] == 'siswa' ? 'block' : 'none'; ?>;">
              <label for="id_siswa" class="form-label">Nama Siswa</label>
              <select class="form-control" id="id_siswa" name="id_siswa">
                <option value="">Pilih Siswa</option>
                <?php while ($row = $siswa->fetch_assoc()): ?>
                <option value="<?php echo $row['id']; ?>" <?php echo $presensi['id_siswa'] == $row['id'] ? 'selected' : ''; ?>>
                  <?php echo htmlspecialchars($row['nama_lengkap']); ?>
                </option>
                <?php endwhile; $siswa->data_seek(0); ?>
              </select>
            </div>
            <div class="mb-3" id="guru_field" style="display: <?php echo $presensi['jenis_pengguna'] == 'guru' ? 'block' : 'none'; ?>;">
              <label for="id_guru" class="form-label">Nama Guru</label>
              <select class="form-control" id="id_guru" name="id_guru">
                <option value="">Pilih Guru</option>
                <?php while ($row = $guru->fetch_assoc()): ?>
                <option value="<?php echo $row['id']; ?>" <?php echo $presensi['id_guru'] == $row['id'] ? 'selected' : ''; ?>>
                  <?php echo htmlspecialchars($row['nama_lengkap']); ?>
                </option>
                <?php endwhile; $guru->data_seek(0); ?>
              </select>
            </div>
            <div class="mb-3">
              <label for="tanggal" class="form-label">Tanggal</label>
              <input type="date" class="form-control" id="tanggal" name="tanggal" value="<?php echo $presensi['tanggal']; ?>" required>
            </div>
            <div class="mb-3">
              <label for="jam_masuk" class="form-label">Jam Masuk</label>
              <input type="time" class="form-control" id="jam_masuk" name="jam_masuk" value="<?php echo $presensi['jam_masuk']; ?>">
            </div>
            <div class="mb-3">
              <label for="status_masuk" class="form-label">Status Masuk</label>
              <select class="form-control" id="status_masuk" name="status_masuk">
                <option value="">Pilih Status</option>
                <option value="Hadir" <?php echo $presensi['status_masuk'] == 'Hadir' ? 'selected' : ''; ?>>Hadir</option>
                <option value="Terlambat" <?php echo $presensi['status_masuk'] == 'Terlambat' ? 'selected' : ''; ?>>Terlambat</option>
                <option value="Izin" <?php echo $presensi['status_masuk'] == 'Izin' ? 'selected' : ''; ?>>Izin</option>
                <option value="Sakit" <?php echo $presensi['status_masuk'] == 'Sakit' ? 'selected' : ''; ?>>Sakit</option>
                <option value="Alfa" <?php echo $presensi['status_masuk'] == 'Alfa' ? 'selected' : ''; ?>>Alfa</option>
              </select>
            </div>
            <div class="mb-3">
              <label for="jam_pulang" class="form-label">Jam Pulang</label>
              <input type="time" class="form-control" id="jam_pulang" name="jam_pulang" value="<?php echo $presensi['jam_pulang']; ?>">
            </div>
            <div class="mb-3">
              <label for="status_pulang" class="form-label">Status Pulang</label>
              <select class="form-control" id="status_pulang" name="status_pulang">
                <option value="">Pilih Status</option>
                <option value="Hadir" <?php echo $presensi['status_pulang'] == 'Hadir' ? 'selected' : ''; ?>>Hadir</option>
                <option value="Pulang Cepat" <?php echo $presensi['status_pulang'] == 'Pulang Cepat' ? 'selected' : ''; ?>>Pulang Cepat</option>
                <option value="Izin" <?php echo $presensi['status_pulang'] == 'Izin' ? 'selected' : ''; ?>>Izin</option>
                <option value="Sakit" <?php echo $presensi['status_pulang'] == 'Sakit' ? 'selected' : ''; ?>>Sakit</option>
                <option value="Alfa" <?php echo $presensi['status_pulang'] == 'Alfa' ? 'selected' : ''; ?>>Alfa</option>
              </select>
            </div>
            <div class="mb-3">
              <label for="lokasi" class="form-label">Lokasi</label>
              <input type="text" class="form-control" id="lokasi" name="lokasi" value="<?php echo htmlspecialchars($presensi['lokasi'] ?? ''); ?>">
            </div>
            <div class="mb-3">
              <label for="alamat" class="form-label">Alamat</label>
              <textarea class="form-control" id="alamat" name="alamat"><?php echo htmlspecialchars($presensi['alamat'] ?? ''); ?></textarea>
            </div>
            <button type="submit" class="btn btn-success"><i class="fas fa-save me-2"></i>Simpan</button>
            <a href="presensi.php" class="btn btn-secondary">Kembali</a>
          </form>
        </div>
      </div>
    </div>
    <div class="footer">
      <p class="text-muted small">© 2025 Sekolah MIDU Grogol</p>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
  <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
  <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
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

    function togglePengguna() {
      const jenis = document.getElementById('jenis_pengguna').value;
      document.getElementById('siswa_field').style.display = jenis === 'siswa' ? 'block' : 'none';
      document.getElementById('guru_field').style.display = jenis === 'guru' ? 'block' : 'none';
    }
  </script>
</body>
</html>
<?php $conn->close(); ?>