<?php
$activePage = 'data-siswa';
include 'auth.php';
include 'config.php';

$siswa = mysqli_query($conn, "SELECT * FROM siswa ORDER BY nama_lengkap ASC");
$n = 1;
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Data Siswa - Sistem Presensi MIDU</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <style>
    body {
      background-color: #f1f5f9;
      overflow-x: hidden;
    }
    .qr, .foto {
      width: 50px;
      height: 50px;
      object-fit: cover;
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
  <?php include 'sidebar.php'; ?>

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
    <?php if (isset($_SESSION['success'])): ?>
      <script>
      Swal.fire({
          icon: 'success',
          title: 'Berhasil!',
          text: <?= json_encode($_SESSION['success']) ?>,
          confirmButtonColor: '#28a745'
      });
      </script>
      <?php unset($_SESSION['success']); endif; ?>

      <?php if (isset($_SESSION['error'])): ?>
      <script>
      Swal.fire({
          icon: 'error',
          title: 'Gagal!',
          text: <?= json_encode($_SESSION['error']) ?>,
          confirmButtonColor: '#dc3545'
      });
      </script>
    <?php unset($_SESSION['error']); endif; ?>


    <div class="container-fluid">
      <div class="card">
        <div class="card-header mb-3">
          <h3 class="mb-0">Data Siswa</h3>
        </div>
        <div class="card-body">
          <a href="siswa_tambah.php" class="btn btn-success mb-5"><i class="fas fa-user-plus me-2"></i>Tambah Siswa</a>
          <a href="import_siswa.php" class="btn btn-info mb-5"><i class="fas fa-file-import me-2"></i>Import Siswa</a>
          <a href="siswa_cetak_massal.php" class="btn btn-warning mb-5"><i class="fas fa-print me-2"></i>Print Kartu Siswa</a>
          <div class="table-responsive">
            <table class="table table-bordered" id="tabelsiswa">
              <thead class="table-light">
                <tr class="text-center">
                  <th width="50px">No.</th>
                  <th>NISN</th>
                  <th>Nama</th>
                  <th>JK</th>
                  <th>Tingkat</th>
                  <th>QR Code</th>
                  <th>Foto</th>
                  <th width="125px">Aksi</th>
                </tr>
              </thead>
              <tbody>
                <?php while ($row = mysqli_fetch_assoc($siswa)): ?>
                <tr>
                  <td><?= $n++ ?></td>
                  <td><?= $row['nisn'] ?></td>
                  <td><?= $row['nama_lengkap'] ?></td>
                  <td><?= $row['jenis_kelamin'] ?></td>
                  <td><?= $row['tingkat_rombel'] ?></td>
                  <td>
                    <img src="qrcodes/<?= strtolower($row['qrcode'])  ?>.png" alt="QR" class="qr"><span class="ms-2 fw-bold"><?= $row['qrcode'] ?></span>
                  </td>
                  <td>
                    <img src="uploads/<?= strtolower($row['foto'])  ?>" alt="foto" class="qr">

                  </td>
                  <td>
                    <a href="siswa_edit.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-warning"><i class="fas fa-edit"></i></a>
                    <button class="btn btn-sm btn-danger btn-hapus" data-id="<?= $row['id'] ?>"><i class="fas fa-trash"></i></button>
                    <a href="siswa_cetak.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-success" target="_blank"><i class="fas fa-print"></i></a>
                  </td>
                </tr>
                <?php endwhile; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- JS CDN -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
  <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
  <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

  <!-- Toggle Sidebar -->
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
  </script>

  <!-- DataTables & SweetAlert -->
  <script>
    $(document).ready(function () {
      $('#tabelsiswa').DataTable();

      $('.btn-hapus').click(function () {
        var id = $(this).data('id');
        Swal.fire({
          title: 'Yakin hapus data ini?',
          icon: 'warning',
          showCancelButton: true,
          confirmButtonText: 'Hapus',
          cancelButtonText: 'Batal'
        }).then((result) => {
          if (result.isConfirmed) {
            window.location = 'siswa_hapus.php?id=' + id;
          }
        });
      });
    });
  </script>
</body>
</html>
