<?php
session_start();
$activePage = 'data-ketidakhadiran';
include 'auth.php';
include 'config.php';

$data = $conn->query("
  SELECT k.*, 
         IF(k.jenis_pengguna='siswa', s.nama_lengkap, g.nama_lengkap) AS nama, 
         IF(k.jenis_pengguna='siswa', s.tingkat_rombel, g.jabatan) AS info
  FROM ketidakhadiran k
  LEFT JOIN siswa s ON k.id_siswa = s.id
  LEFT JOIN guru g ON k.id_guru = g.id
  ORDER BY k.tanggal DESC
");
$no = 1;
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Data jadwal - Sistem Presensi MIDU</title>
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
        text: <?php echo json_encode($_SESSION['success']); ?>,
        confirmButtonColor: '#28a745'
      });
    </script>
    <?php unset($_SESSION['success']); endif; ?>

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
          <h3 class="mb-0">Data Ketidakhadiran</h3>
        </div>
        <div class="card-body">
          <!-- <a href="ketidakhadiran_tambah.php" class="btn btn-primary mb-3">+ Tambah</a> -->
          <a href="ketidakhadiran_tambah.php" class="btn btn-success mb-5"><i class="fas fa-user-plus me-2"></i>Tambah ketidakhadiran</a>
          <div class="table-responsive">
    <table class="table table-bordered" id="tabelabsensi">
      <thead >
        <tr>
          <th>No</th>
          <th>Tanggal</th>
          <th>Nama</th>
          <th>Jenis</th>
          <th>Kelas / Jabatan</th>
          <th>Keterangan</th>
          <th>Alasan</th>
          <th>Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php $no = 1; while ($row = $data->fetch_assoc()): ?>
        <tr>
          <td><?= $no++ ?></td>
          <td><?= $row['tanggal'] ?></td>
          <td><?= $row['nama'] ?></td>
          <td><?= ucfirst($row['jenis_pengguna']) ?></td>
          <td><?= $row['info'] ?></td>
          <td><?= ucfirst($row['keterangan']) ?></td>
          <td><?= $row['alasan'] ?></td>
          <td>
            <a href="ketidakhadiran_edit.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-warning"><i class="fas fa-edit"></i></a>
            <button class="btn btn-sm btn-danger btn-hapus" data-id="<?= $row['id'] ?>" data-nama="<?= $row['nama'] ?>"> <i class="fas fa-trash"></i> </button>

          </td>
        </tr>
        <?php endwhile ?>
      </tbody>
    </table>
  </div>
        </div>
      </div>
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

    $(document).ready(function () {
      $('#tabelabsensi').DataTable();

      $('.btn-hapus').click(function () {
        var id = $(this).data('id');
        Swal.fire({
          title: 'Yakin ingin menghapus data ini?',
          text: "Data yang dihapus tidak dapat dikembalikan!",
          icon: 'warning',
          showCancelButton: true,
          confirmButtonColor: '#d33',
          cancelButtonColor: '#3085d6',
          confirmButtonText: 'Ya, hapus!',
          cancelButtonText: 'Batal'
        }).then((result) => {
          if (result.isConfirmed) {
            window.location.href = 'ketidakhadiran_hapus.php?id=' + id;
          }
        });
      });
    });
  </script>
</body>
</html>
