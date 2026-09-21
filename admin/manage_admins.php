<?php

session_start();
$activePage = 'manage_admins';
require_once 'config.php';
if (!isset($_SESSION['admin'])) {
  header("Location: login.php");
  exit;
}

// Ambil daftar admin
$stmt = $conn->query("SELECT * FROM admin");
$admins = $stmt->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Manajemen Admin - Sistem Presensi MIDU</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
  <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
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
    .table-responsive {
      margin-top: 20px;
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
          <span class="navbar-text">Admin: <?= htmlspecialchars($_SESSION['admin']['username']) ?></span>
          <a href="logout.php" class="btn btn-outline-danger btn-sm ms-2">Logout</a>
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
      <h3 class="mb-4">Manajemen Admin</h3>
      <div class="card">
        <div class="card-header">
          <h5 class="mb-0">Daftar Admin</h5>
        </div>
        <div class="card-body">
          <div class="table-responsive">
            <table class="table table-bordered table-striped table-sm" id="adminTable">
              <thead class="table-dark">
                <tr>
                  <th width="50px">No</th>
                  <th>Nama Lengkap</th>
                  <th>Username</th>
                  <th width="250px">Aksi</th>
                </tr>
              </thead>
              <tbody>
                <?php $no = 1; foreach ($admins as $admin): ?>
                <tr>
                  <td><?= $no++ ?></td>
                  <td><?= htmlspecialchars($admin['name']) ?></td>
                  <td><?= htmlspecialchars($admin['username']) ?></td>
                  <td>
                    <a href="edit_admin.php?id=<?= $admin['id'] ?>" class="btn btn-sm btn-primary">
                      <i class="fas fa-edit"></i> Edit
                    </a>
                    <!-- <button class="btn btn-sm btn-danger btn-hapus" data-id="<?php echo $admin['id']; ?>"><i class="fas fa-trash"></i> Hapus</button> -->
                  </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($admins)): ?>
                <tr><td colspan="3" class="text-center">Tidak ada data admin.</td></tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>

  <script>
    $(document).ready(function() {
      // Inisialisasi DataTables
      $('#adminTable').DataTable({
        "pageLength": 10,
        "lengthChange": true,
        "searching": true,
        "ordering": true,
        "language": {
          "search": "Cari:",
          "lengthMenu": "Tampilkan _MENU_ entri",
          "info": "Menampilkan _START_ sampai _END_ dari _TOTAL_ entri",
          "paginate": {
            "first": "Pertama",
            "last": "Terakhir",
            "next": "Selanjutnya",
            "previous": "Sebelumnya"
          }
        }
      });

      // Toggle Sidebar
      $('#toggleSidebarBtn').click(function() {
        $('.sidebar').toggleClass('collapsed');
        $('#mainContent').toggleClass('expanded');
      });
    });
  </script>
  <script>
    $(document).ready(function () {
      
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
            window.location = 'admin_hapus.php?id=' + id;
          }
        });
      });
    });
  </script>
</body>
</html>