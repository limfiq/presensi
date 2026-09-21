<?php
session_start();
$activePage = 'presensi';
include 'auth.php';
include 'config.php';

// Query untuk data presensi
$data = $conn->query("
  SELECT p.*, 
         IF(p.jenis_pengguna='siswa', s.nama_lengkap, g.nama_lengkap) AS nama, 
         IF(p.jenis_pengguna='siswa', s.tingkat_rombel, g.jabatan) AS info
  FROM presensi p
  LEFT JOIN siswa s ON p.id_siswa = s.id
  LEFT JOIN guru g ON p.id_guru = g.id
  ORDER BY p.tanggal DESC
");

// Ambil daftar unik tingkat_rombel dari siswa
$kelas = $conn->query("SELECT DISTINCT tingkat_rombel FROM siswa WHERE tingkat_rombel IS NOT NULL ORDER BY tingkat_rombel ASC");
$daftar_kelas = [];
while ($row = $kelas->fetch_assoc()) {
  $daftar_kelas[] = $row['tingkat_rombel'];
}

// Ambil daftar unik jabatan dari guru
$jabatan = $conn->query("SELECT DISTINCT jabatan FROM guru WHERE jabatan IS NOT NULL ORDER BY jabatan ASC");
$daftar_jabatan = [];
while ($row = $jabatan->fetch_assoc()) {
  $daftar_jabatan[] = $row['jabatan'];
}

$no = 1;
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Data Presensi - Sistem Presensi MIDU</title>
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
    .filter-form .form-label {
      color: #2c6e49;
      font-weight: 500;
    }
    #btnHapusTerpilih {
      display: none;
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
        <span class="navbar-brand ms-3">Data Presensi</span>
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
          <h3 class="mb-0">Data Presensi</h3>
        </div>
        <div class="card-body">
          <div class="mb-3">
            <!-- <a href="presensi_tambah.php" class="btn btn-success"><i class="fas fa-user-plus me-2"></i>Tambah Presensi</a> -->
            <button id="btnHapusTerpilih" class="btn btn-danger"><i class="fas fa-trash me-2"></i>Hapus Terpilih</button>
          </div>
          <!-- Form Filter -->
          <div class="filter-form mb-4">
            <!-- <h5>Filter Data</h5> -->
            <form id="filterForm" class="row g-3">
              <div class="col-md-3">
                <label for="filter_tanggal_dari" class="form-label">Tanggal Dari</label>
                <input type="date" class="form-control" id="filter_tanggal_dari">
              </div>
              <div class="col-md-3">
                <label for="filter_tanggal_sampai" class="form-label">Tanggal Sampai</label>
                <input type="date" class="form-control" id="filter_tanggal_sampai">
              </div>
              <div class="col-md-3">
                <label for="filter_jenis" class="form-label">Jenis Pengguna</label>
                <select class="form-control" id="filter_jenis" onchange="toggleFilterFields()">
                  <option value="">Semua</option>
                  <option value="siswa">Siswa</option>
                  <option value="guru">Guru</option>
                </select>
              </div>
              <div class="col-md-3" id="filter_kelas_field" style="display: none;">
                <label for="filter_kelas" class="form-label">Kelas</label>
                <select class="form-control" id="filter_kelas">
                  <option value="">Semua</option>
                  <?php foreach ($daftar_kelas as $k): ?>
                    <option value="<?php echo htmlspecialchars($k); ?>"><?php echo htmlspecialchars($k); ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="col-md-3" id="filter_jabatan_field" style="display: none;">
                <label for="filter_jabatan" class="form-label">Jabatan</label>
                <select class="form-control" id="filter_jabatan">
                  <option value="">Semua</option>
                  <?php foreach ($daftar_jabatan as $j): ?>
                    <option value="<?php echo htmlspecialchars($j); ?>"><?php echo htmlspecialchars($j); ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="col-md-6">
                <button type="button" class="btn btn-success mt-4 me-2" onclick="applyFilter()"><i class="fas fa-filter me-2"></i>Terapkan Filter</button>
                <button type="button" class="btn btn-secondary mt-4" onclick="resetFilter()"><i class="fas fa-undo me-2"></i>Reset Filter</button>
              </div>
            </form>
          </div>
          <div class="table-responsive">
            <table class="table table-bordered" id="tabelpresensi">
              <thead class="table-light">
                <tr class="text-center">
                  <th width="50px"><input type="checkbox" id="selectAll"></th>
                  <th width="50px">No</th>
                  <th>Tanggal</th>
                  <th>Nama</th>
                  <th>Jenis</th>
                  <th>Kelas/Jabatan</th>
                  <th>Jam Masuk</th>
                  <th>Status Masuk</th>
                  <th>Jam Pulang</th>
                  <th>Status Pulang</th>
                  <th>Aksi</th>
                </tr>
              </thead>
              <tbody>
                <?php while ($row = $data->fetch_assoc()): ?>
                <tr>
                  <td><input type="checkbox" class="selectRow" value="<?php echo htmlspecialchars($row['id']); ?>"></td>
                  <td><?php echo $no++; ?></td>
                  <td><?php echo date('d F Y', strtotime($row['tanggal'])); ?></td>
                  <td><?php echo htmlspecialchars($row['nama'] ?? '-'); ?></td>
                  <td><?php echo ucfirst($row['jenis_pengguna']); ?></td>
                  <td><?php echo htmlspecialchars($row['info'] ?? '-'); ?></td>
                  <td><?php echo $row['jam_masuk'] ? date('H:i', strtotime($row['jam_masuk'])) : '-'; ?></td>
                  <td><?php echo htmlspecialchars($row['status_masuk'] ?? '-'); ?></td>
                  <td><?php echo $row['jam_pulang'] ? date('H:i', strtotime($row['jam_pulang'])) : '-'; ?></td>
                  <td><?php echo htmlspecialchars($row['status_pulang'] ?? '-'); ?></td>
                  <td>
                    <button class="btn btn-sm btn-view me-1" data-id="<?php echo htmlspecialchars($row['id']); ?>"><i class="fas fa-eye"></i></button>
                    <a href="presensi_edit.php?id=<?php echo htmlspecialchars($row['id']); ?>" class="btn btn-sm btn-warning me-1"><i class="fas fa-edit"></i></a>
                    <button class="btn btn-sm btn-danger btn-hapus me-1" data-id="<?php echo htmlspecialchars($row['id']); ?>" data-nama="<?php echo htmlspecialchars($row['nama'] ?? '-'); ?>"><i class="fas fa-trash"></i></button>
                  </td>
                </tr>
                <?php endwhile; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal Detail Presensi -->
    <div class="modal fade" id="detailPresensiModal" tabindex="-1" aria-labelledby="detailPresensiModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="detailPresensiModalLabel">Detail Presensi</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <div class="row">
              <div class="col-md-4">ID Presensi:</div>
              <div class="col-md-8" id="detail_id"></div>
            </div>
            <div class="row">
              <div class="col-md-4">Nama:</div>
              <div class="col-md-8" id="detail_nama"></div>
            </div>
            <div class="row">
              <div class="col-md-4">Jenis Pengguna:</div>
              <div class="col-md-8" id="detail_jenis_pengguna"></div>
            </div>
            <div class="row">
              <div class="col-md-4">Kelas/Jabatan:</div>
              <div class="col-md-8" id="detail_info"></div>
            </div>
            <div class="row">
              <div class="col-md-4">Tanggal:</div>
              <div class="col-md-8" id="detail_tanggal"></div>
            </div>
            <div class="row">
              <div class="col-md-4">Jam Masuk:</div>
              <div class="col-md-8" id="detail_jam_masuk"></div>
            </div>
            <div class="row">
              <div class="col-md-4">Status Masuk:</div>
              <div class="col-md-8" id="detail_status_masuk"></div>
            </div>
            <div class="row">
              <div class="col-md-4">Jam Pulang:</div>
              <div class="col-md-8" id="detail_jam_pulang"></div>
            </div>
            <div class="row">
              <div class="col-md-4">Status Pulang:</div>
              <div class="col-md-8" id="detail_status_pulang"></div>
            </div>
            <div class="row">
              <div class="col-md-4">Posisi:</div>
              <div class="col-md-8" id="detail_lokasi"></div>
            </div>
            <div class="row">
              <div class="col-md-4">Lokasi Absen:</div>
              <div class="col-md-8" id="detail_alamat"></div>
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

  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
  <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
  <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    console.log('JavaScript loaded'); // Debugging: Pastikan skrip dimuat

    $(document).ready(function () {
      console.log('Document ready'); // Debugging: Pastikan document.ready dijalankan

      // Inisialisasi DataTables tanpa AJAX
      const table = $('#tabelpresensi').DataTable({
        "drawCallback": function () {
          console.log('DataTables redraw complete'); // Debugging redraw
          updateHapusTerpilihButton();
        }
      });

      // Toggle Sidebar
      const sidebar = document.getElementById('sidebar');
      const mainContent = document.getElementById('mainContent');
      const toggleBtn = document.getElementById('toggleSidebarBtn');

      toggleBtn.addEventListener('click', function () {
        console.log('Sidebar toggle clicked'); // Debugging
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
      $('#detailPresensiModal').on('hidden.bs.modal', function () {
        const lastButton = document.querySelector('.btn-view:focus');
        if (lastButton) {
          lastButton.focus();
        } else {
          document.querySelector('#tabelpresensi').focus();
        }
        console.log('Modal closed, focus returned'); // Debugging
      });

      // Hapus Presensi (Satu Data)
      $(document).on('click', '.btn-hapus', function () {
        var id = $(this).data('id');
        var nama = $(this).data('nama');
        console.log('Hapus ID:', id, 'Nama:', nama); // Debugging
        if (!id) {
          Swal.fire({
            icon: 'error',
            title: 'Gagal!',
            text: 'ID presensi tidak valid.',
            confirmButtonColor: '#dc3545'
          });
          return;
        }
        Swal.fire({
          title: 'Yakin hapus data presensi ' + nama + '?',
          text: "Data yang dihapus tidak dapat dikembalikan!",
          icon: 'warning',
          showCancelButton: true,
          confirmButtonColor: '#dc3545',
          cancelButtonColor: '#3085d6',
          confirmButtonText: 'Ya, hapus!',
          cancelButtonText: 'Batal'
        }).then((result) => {
          if (result.isConfirmed) {
            window.location.href = 'presensi_hapus.php?id=' + id;
          }
        });
      });

      // View Detail Presensi
      $(document).on('click', '.btn-view', function () {
        var id = $(this).data('id');
        console.log('View ID:', id); // Debugging
        if (!id) {
          Swal.fire({
            icon: 'error',
            title: 'Gagal!',
            text: 'ID presensi tidak valid.',
            confirmButtonColor: '#dc3545'
          });
          return;
        }
        $.ajax({
          url: 'presensi_detail.php',
          method: 'POST',
          data: { id: id },
          dataType: 'json',
          success: function (data) {
            console.log('Data Presensi:', data); // Debugging
            if (data.error) {
              Swal.fire({
                icon: 'error',
                title: 'Gagal!',
                text: data.error,
                confirmButtonColor: '#dc3545'
              });
            } else {
              $('#detail_id').text(data.id || '-');
              $('#detail_nama').text(data.nama || '-');
              $('#detail_jenis_pengguna').text(data.jenis_pengguna || '-');
              $('#detail_info').text(data.info || '-');
              $('#detail_tanggal').text(data.tanggal || '-');
              $('#detail_jam_masuk').text(data.jam_masuk || '-');
              $('#detail_status_masuk').text(data.status_masuk || '-');
              $('#detail_jam_pulang').text(data.jam_pulang || '-');
              $('#detail_status_pulang').text(data.status_pulang || '-');
              $('#detail_lokasi').text(data.lokasi || '-');
              $('#detail_alamat').text(data.alamat || '-');
              $('#detail_created_at').text(data.created_at || '-');
              $('#detailPresensiModal').modal('show');
            }
          },
          error: function (xhr, status, error) {
            console.error('AJAX Error:', status, error, xhr.responseText); // Debugging
            Swal.fire({
              icon: 'error',
              title: 'Gagal!',
              text: 'Gagal mengambil data presensi: ' + error,
              confirmButtonColor: '#dc3545'
            });
          }
        });
      });

      // Toggle Filter Fields
      window.toggleFilterFields = function() {
        const jenis = $('#filter_jenis').val();
        console.log('Toggling filter fields, jenis:', jenis); // Debugging
        $('#filter_kelas_field').css('display', jenis === 'siswa' ? 'block' : 'none');
        $('#filter_jabatan_field').css('display', jenis === 'guru' ? 'block' : 'none');
        if (jenis !== 'siswa') $('#filter_kelas').val('');
        if (jenis !== 'guru') $('#filter_jabatan').val('');
      };

      // Fungsi Filter
      window.applyFilter = function() {
        console.log('applyFilter called'); // Debugging
        const tanggalDari = $('#filter_tanggal_dari').val();
        const tanggalSampai = $('#filter_tanggal_sampai').val();
        const jenis = $('#filter_jenis').val();
        const kelas = $('#filter_kelas').val();
        const jabatan = $('#filter_jabatan').val();

        console.log('Applying filter:', { tanggalDari, tanggalSampai, jenis, kelas, jabatan });

        // Validasi Tanggal
        if (tanggalDari && tanggalSampai && new Date(tanggalSampai) < new Date(tanggalDari)) {
          Swal.fire({
            icon: 'error',
            title: 'Gagal!',
            text: 'Tanggal Sampai harus lebih besar atau sama dengan Tanggal Dari.',
            confirmButtonColor: '#dc3545'
          });
          $('#filter_tanggal_sampai').val('');
          return;
        }

        // Filter Jenis Pengguna
        table.column(4).search(jenis, false, false);

        // Filter Kelas/Jabatan
        if (jenis === 'siswa' && kelas) {
          table.column(5).search(kelas, false, false);
        } else if (jenis === 'guru' && jabatan) {
          table.column(5).search(jabatan, false, false);
        } else {
          table.column(5).search('', false, false);
        }

        // Filter rentang tanggal
        $.fn.dataTable.ext.search.push(
          function(settings, data, dataIndex) {
            const tanggal = data[2]; // Kolom Tanggal
            const dateFrom = tanggalDari ? new Date(tanggalDari) : null;
            const dateTo = tanggalSampai ? new Date(tanggalSampai) : null;
            const dateCell = new Date(tanggal.split(' ').reverse().join('-')); // Konversi DD MMMM YYYY ke YYYY-MM-DD

            if (!dateFrom && !dateTo) {
              return true;
            }
            if (dateFrom && !dateTo && dateCell >= dateFrom) {
              return true;
            }
            if (!dateFrom && dateTo && dateCell <= dateTo) {
              return true;
            }
            if (dateFrom && dateTo && dateCell >= dateFrom && dateCell <= dateTo) {
              return true;
            }
            return false;
          }
        );

        // Simpan filter ke sessionStorage
        sessionStorage.setItem('filter_tanggal_dari', tanggalDari);
        sessionStorage.setItem('filter_tanggal_sampai', tanggalSampai);
        sessionStorage.setItem('filter_jenis', jenis);
        sessionStorage.setItem('filter_kelas', kelas);
        sessionStorage.setItem('filter_jabatan', jabatan);

        // Terapkan filter
        table.draw();
        $.fn.dataTable.ext.search.pop(); // Hapus filter tanggal kustom setelah draw
      };

      // Fungsi Reset Filter
      window.resetFilter = function() {
        console.log('resetFilter called'); // Debugging
        $('#filter_tanggal_dari').val('');
        $('#filter_tanggal_sampai').val('');
        $('#filter_jenis').val('');
        $('#filter_kelas').val('');
        $('#filter_jabatan').val('');
        $('#filter_kelas_field').css('display', 'none');
        $('#filter_jabatan_field').css('display', 'none');

        // Reset filter DataTables
        table.search('').columns().search('').draw();

        // Hapus filter dari sessionStorage
        sessionStorage.removeItem('filter_tanggal_dari');
        sessionStorage.removeItem('filter_tanggal_sampai');
        sessionStorage.removeItem('filter_jenis');
        sessionStorage.removeItem('filter_kelas');
        sessionStorage.removeItem('filter_jabatan');

        console.log('Filters reset');
      };

      // Muat filter dari sessionStorage
      $(window).on('load', function() {
        console.log('Window loaded'); // Debugging
        const tanggalDari = sessionStorage.getItem('filter_tanggal_dari') || '';
        const tanggalSampai = sessionStorage.getItem('filter_tanggal_sampai') || '';
        const jenis = sessionStorage.getItem('filter_jenis') || '';
        const kelas = sessionStorage.getItem('filter_kelas') || '';
        const jabatan = sessionStorage.getItem('filter_jabatan') || '';

        $('#filter_tanggal_dari').val(tanggalDari);
        $('#filter_tanggal_sampai').val(tanggalSampai);
        $('#filter_jenis').val(jenis);
        $('#filter_kelas').val(kelas);
        $('#filter_jabatan').val(jabatan);

        toggleFilterFields();

        if (tanggalDari || tanggalSampai || jenis || kelas || jabatan) {
          applyFilter();
        }
      });

      // Validasi Tanggal Sampai
      $('#filter_tanggal_sampai').on('change', function() {
        console.log('Tanggal Sampai changed:', $(this).val()); // Debugging
        const tanggalDari = $('#filter_tanggal_dari').val();
        const tanggalSampai = $(this).val();
        if (tanggalDari && tanggalSampai && new Date(tanggalSampai) < new Date(tanggalDari)) {
          Swal.fire({
            icon: 'error',
            title: 'Gagal!',
            text: 'Tanggal Sampai harus lebih besar atau sama dengan Tanggal Dari.',
            confirmButtonColor: '#dc3545'
          });
          $(this).val('');
        }
      });

      // Checkbox Pilih Semua
      $('#selectAll').on('change', function() {
        const isChecked = $(this).prop('checked');
        console.log('Select All:', isChecked); // Debugging
        $('.selectRow').prop('checked', isChecked);
        updateHapusTerpilihButton();
      });

      // Checkbox Individual
      $(document).on('change', '.selectRow', function() {
        console.log('Row checkbox changed:', $(this).val()); // Debugging
        updateHapusTerpilihButton();
      });

      // Update Status Tombol Hapus Terpilih
      function updateHapusTerpilihButton() {
        const checkedCount = $('.selectRow:checked').length;
        console.log('Checked count:', checkedCount); // Debugging
        if (checkedCount > 0) {
          $('#btnHapusTerpilih').show().text(`Hapus Terpilih (${checkedCount})`);
        } else {
          $('#btnHapusTerpilih').hide();
        }
      }

      // Hapus Data Terpilih
      $('#btnHapusTerpilih').on('click', function() {
        const ids = $('.selectRow:checked').map(function() {
          return $(this).val();
        }).get();
        console.log('Hapus terpilih IDs:', ids); // Debugging
        if (ids.length === 0) {
          Swal.fire({
            icon: 'warning',
            title: 'Peringatan!',
            text: 'Pilih setidaknya satu data untuk dihapus.',
            confirmButtonColor: '#dc3545'
          });
          return;
        }

        Swal.fire({
          title: 'Yakin hapus ' + ids.length + ' data presensi?',
          text: "Data yang dihapus tidak dapat dikembalikan!",
          icon: 'warning',
          showCancelButton: true,
          confirmButtonColor: '#dc3545',
          cancelButtonColor: '#3085d6',
          confirmButtonText: 'Ya, hapus!',
          cancelButtonText: 'Batal'
        }).then((result) => {
          if (result.isConfirmed) {
            $.ajax({
              url: 'presensi_hapus_massal.php',
              method: 'POST',
              data: { ids: ids },
              dataType: 'json',
              success: function(response) {
                console.log('Hapus massal response:', response); // Debugging
                if (response.success) {
                  Swal.fire({
                    icon: 'success',
                    title: 'Berhasil!',
                    text: response.success,
                    confirmButtonColor: '#28a745'
                  }).then(() => {
                    // console.log('Refreshing table after deletion'); // Debugging
                    // table.draw(); // Hanya redraw, tidak menggunakan AJAX
                    // $('#selectAll').prop('checked', false);
                    // updateHapusTerpilihButton();
                    location.reload();

                  });
                } else {
                  Swal.fire({
                    icon: 'error',
                    title: 'Gagal!',
                    text: response.error || 'Gagal menghapus data.',
                    confirmButtonColor: '#dc3545'
                  });
                }
              },
              error: function(xhr, status, error) {
                console.error('Hapus massal AJAX Error:', status, error, xhr.responseText); // Debugging
                Swal.fire({
                  icon: 'error',
                  title: 'Gagal!',
                  text: 'Gagal menghapus data: ' + error,
                  confirmButtonColor: '#dc3545'
                });
              }
            });
          }
        });
      });
    });
  </script>
</body>
</html>
<?php $conn->close(); ?>