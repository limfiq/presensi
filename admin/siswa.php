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
      background: linear-gradient(135deg, #f1f5f9 0%, #e2e8f0 100%);
      min-height: 100vh;
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      overflow-x: hidden;
    }
    .qr, .foto {
      width: 50px;
      height: 50px;
      object-fit: cover;
      border-radius: 4px;
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
    .modal-header {
      background-color: #2c6e49;
      color: white;
    }
    .modal-content {
      border-radius: 10px;
    }
    .modal-body img {
      max-width: 100px;
      border-radius: 8px;
      border: 1px solid #dee2e6;
    }
    .modal-body .row {
      margin-bottom: 10px;
    }
    .modal-body .col-md-4 {
      font-weight: 500;
      color: #2c6e49;
    }
    .btn-view {
      background-color: #17a2b8;
      border-color: #17a2b8;
    }
    .btn-view:hover {
      background-color: #138496;
      border-color: #138496;
    }
    .table-responsive {
      margin-top: 20px;
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
      .modal-body img {
        max-width: 80px;
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
        <span class="navbar-brand ms-3">Data Siswa</span>
        <div>
          <span class="navbar-text">Admin</span>
          <a href="logout.php" class="btn btn-outline-danger btn-sm ms-2">Logout</a>
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
                  <th width="150px">Aksi</th>
                </tr>
              </thead>
              <tbody>
                <?php while ($row = mysqli_fetch_assoc($siswa)): ?>
                <tr>
                  <td><?= $n++ ?></td>
                  <td><?= htmlspecialchars($row['nisn']) ?></td>
                  <td><?= htmlspecialchars($row['nama_lengkap']) ?></td>
                  <td><?= $row['jenis_kelamin'] == 'L' ? 'Laki-laki' : 'Perempuan' ?></td>
                  <td><?= htmlspecialchars($row['tingkat_rombel']) ?></td>
                  <td>
                    <img src="qrcodes/<?= htmlspecialchars(strtolower($row['qrcode'])) ?>.png" alt="QR" class="qr"><span class="ms-2 fw-bold"><?= htmlspecialchars($row['qrcode']) ?></span>
                  </td>
                  <td>
                    <img src="Uploads/<?= htmlspecialchars(strtolower($row['foto'])) ?>" alt="foto" class="foto">
                  </td>
                  <td>
                    <button class="btn btn-sm btn-view me-1" data-id="<?= htmlspecialchars($row['id']) ?>"><i class="fas fa-eye"></i></button>
                    <a href="siswa_edit.php?id=<?= htmlspecialchars($row['id']) ?>" class="btn btn-sm btn-warning me-1"><i class="fas fa-edit"></i></a>
                    <button class="btn btn-sm btn-danger btn-hapus me-1" data-id="<?= htmlspecialchars($row['id']) ?>"><i class="fas fa-trash"></i></button>
                    <a href="siswa_cetak.php?id=<?= htmlspecialchars($row['id']) ?>" class="btn btn-sm btn-success" target="_blank"><i class="fas fa-print"></i></a>
                  </td>
                </tr>
                <?php endwhile; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal Detail Siswa -->
    <div class="modal fade" id="detailSiswaModal" tabindex="-1" aria-labelledby="detailSiswaModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="detailSiswaModalLabel">Detail Siswa</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <div class="row">
              <div class="col-md-4">NISN:</div>
              <div class="col-md-8" id="detail_nisn"></div>
            </div>
            <div class="row">
              <div class="col-md-4">Nama Lengkap:</div>
              <div class="col-md-8" id="detail_nama"></div>
            </div>
            <div class="row">
              <div class="col-md-4">NIK:</div>
              <div class="col-md-8" id="detail_nik"></div>
            </div>
            <div class="row">
              <div class="col-md-4">Tempat Lahir:</div>
              <div class="col-md-8" id="detail_tempat_lahir"></div>
            </div>
            <div class="row">
              <div class="col-md-4">Tanggal Lahir:</div>
              <div class="col-md-8" id="detail_tanggal_lahir"></div>
            </div>
            <div class="row">
              <div class="col-md-4">Tingkat/Rombel:</div>
              <div class="col-md-8" id="detail_tingkat"></div>
            </div>
            <div class="row">
              <div class="col-md-4">Umur:</div>
              <div class="col-md-8" id="detail_umur"></div>
            </div>
            <div class="row">
              <div class="col-md-4">Status:</div>
              <div class="col-md-8" id="detail_status"></div>
            </div>
            <div class="row">
              <div class="col-md-4">Jenis Kelamin:</div>
              <div class="col-md-8" id="detail_jenis_kelamin"></div>
            </div>
            <div class="row">
              <div class="col-md-4">Alamat:</div>
              <div class="col-md-8" id="detail_alamat"></div>
            </div>
            <div class="row">
              <div class="col-md-4">Nama Ayah:</div>
              <div class="col-md-8" id="detail_nama_ayah"></div>
            </div>
            <div class="row">
              <div class="col-md-4">Nama Ibu:</div>
              <div class="col-md-8" id="detail_nama_ibu"></div>
            </div>
            <div class="row">
              <div class="col-md-4">Foto:</div>
              <div class="col-md-8"><img id="detail_foto" src="" alt="Foto Siswa"></div>
            </div>
            <div class="row">
              <div class="col-md-4">QR Code:</div>
              <div class="col-md-8"><img id="detail_qrcode" src="" alt="QR Code"></div>
            </div>
            <div class="row">
              <div class="col-md-4">Dibuat Pada:</div>
              <div class="col-md-8" id="detail_created_at"></div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
          </div>
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
    $(document).ready(function () {
      // Inisialisasi DataTables
      $('#tabelsiswa').DataTable({
        "drawCallback": function () {
          console.log('DataTables redraw complete'); // Debugging redraw
        }
      });

      // Toggle Sidebar
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

      // Manajemen Fokus untuk Modal
      $('#detailSiswaModal').on('hidden.bs.modal', function () {
        // Kembalikan fokus ke tombol View yang diklik
        const lastButton = document.querySelector('.btn-view:focus');
        if (lastButton) {
          lastButton.focus();
        } else {
          // Jika tidak ada tombol View yang fokus, kembalikan ke elemen aman
          document.querySelector('#tabelsiswa').focus();
        }
        console.log('Modal closed, focus returned'); // Debugging
      });

      // Delegasi event tombol hapus
      $(document).on('click', '.btn-hapus', function() {
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
            window.location.href = 'siswa_hapus.php?id=' + id;
          }
        });
      });

      // View Detail Siswa dengan Delegasi Event
      $(document).on('click', '.btn-view', function () {
        var id = $(this).data('id');
        console.log('View ID:', id); // Debugging
        if (!id) {
          Swal.fire({
            icon: 'error',
            title: 'Gagal!',
            text: 'ID siswa tidak valid.',
            confirmButtonColor: '#dc3545'
          });
          return;
        }
        $.ajax({
          url: 'siswa_detail.php',
          method: 'POST',
          data: { id: id },
          dataType: 'json',
          success: function (data) {
            console.log('Data Siswa:', data); // Debugging
            if (data.error) {
              Swal.fire({
                icon: 'error',
                title: 'Gagal!',
                text: data.error,
                confirmButtonColor: '#dc3545'
              });
            } else {
              $('#detail_nisn').text(data.nisn || '-');
              $('#detail_nama').text(data.nama_lengkap || '-');
              $('#detail_nik').text(data.nik || '-');
              $('#detail_tempat_lahir').text(data.tempat_lahir || '-');
              $('#detail_tanggal_lahir').text(data.tanggal_lahir || '-');
              $('#detail_tingkat').text(data.tingkat_rombel || '-');
              $('#detail_umur').text(data.umur || '-');
              $('#detail_status').text(data.status || '-');
              $('#detail_jenis_kelamin').text(data.jenis_kelamin || '-');
              $('#detail_alamat').text(data.alamat || '-');
              $('#detail_nama_ayah').text(data.nama_ayah || '-');
              $('#detail_nama_ibu').text(data.nama_ibu || '-');
              $('#detail_foto').attr('src', data.foto ? 'Uploads/' + data.foto : 'Uploads/default.png');
              $('#detail_qrcode').attr('src', data.qrcode ? 'qrcodes/' + data.qrcode + '.png' : '');
              $('#detail_created_at').text(data.created_at || '-');
              $('#detailSiswaModal').modal('show');
            }
          },
          error: function (xhr, status, error) {
            console.error('AJAX Error:', status, error, xhr.responseText); // Debugging
            Swal.fire({
              icon: 'error',
              title: 'Gagal!',
              text: 'Gagal mengambil data siswa.',
              confirmButtonColor: '#dc3545'
            });
          }
        });
      });
    });
  </script>
</body>
</html>