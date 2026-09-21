<?php
session_start();
require_once 'config.php';
if (!isset($_SESSION['admin'])) {
  header("Location: login.php");
  exit;
}
date_default_timezone_set('Asia/Jakarta');

// Array nama hari
$nama_hari = array(
  'Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'
);

// Ambil hari dan tanggal
$hari_ini = $nama_hari[date('w')];
$tanggal = date('d') . ' ' . date('F') . ' ' . date('Y');
$tanggal = str_replace(
  ['January','February','March','April','May','June','July','August','September','October','November','December'],
  ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'],
  $tanggal
);
// Get current date
$current_date = date('Y-m-d');

// Total Students
$stmt = $conn->query("SELECT COUNT(*) as total FROM siswa");
$total_students = $stmt->fetch_assoc()['total'];

// Total Teachers
$stmt = $conn->query("SELECT COUNT(*) as total FROM guru");
$total_teachers = $stmt->fetch_assoc()['total'];

// Today's Attendance (Present)
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM presensi WHERE tanggal = ?");
$stmt->bind_param("s", $current_date);
$stmt->execute();
$result = $stmt->get_result();
$total_present = $result->fetch_assoc()['total'];

// Today's Absences
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM ketidakhadiran WHERE tanggal = ?");
$stmt->bind_param("s", $current_date);
$stmt->execute();
$result = $stmt->get_result();
$total_absent = $result->fetch_assoc()['total'];

// Today's Late Arrivals
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM presensi WHERE tanggal = ? AND status_masuk = 'Terlambat'");
$stmt->bind_param("s", $current_date);
$stmt->execute();
$result = $stmt->get_result();
$total_late = $result->fetch_assoc()['total'];

// Today's Schedules
$total_schedules = 0;
try {
  $stmt = $conn->prepare("SELECT COUNT(*) as total FROM jadwal WHERE tanggal = ?");
  $stmt->bind_param("s", $current_date);
  $stmt->execute();
  $result = $stmt->get_result();
  $total_schedules = $result->fetch_assoc()['total'];
} catch (Exception $e) {
  $day_name = date('l', strtotime($current_date));
  $stmt = $conn->prepare("SELECT COUNT(*) as total FROM jadwal WHERE hari = ?");
  $stmt->bind_param("s", $day_name);
  $stmt->execute();
  $result = $stmt->get_result();
  $total_schedules = $result->fetch_assoc()['total'];
}

// Recent Attendance Records
$sql_recent_attendance = "
  SELECT 
    p.tanggal, p.jam_masuk, p.status_masuk,
    IF(p.id_siswa IS NOT NULL, s.nama_lengkap, g.nama_lengkap) AS nama,
    IF(p.id_siswa IS NOT NULL, 'siswa', 'guru') AS jenis_pengguna
  FROM presensi p
  LEFT JOIN siswa s ON p.id_siswa = s.id
  LEFT JOIN guru g ON p.id_guru = g.id
  WHERE p.tanggal = ?
  ORDER BY p.tanggal DESC, p.jam_masuk DESC
  LIMIT 15";
$stmt = $conn->prepare($sql_recent_attendance);
$stmt->bind_param("s", $current_date);
$stmt->execute();
$recent_attendance = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Recent Absence Records
$sql_recent_absences = "
  SELECT 
    k.tanggal, k.keterangan, k.alasan,
    IF(k.id_siswa IS NOT NULL, s.nama_lengkap, g.nama_lengkap) AS nama,
    IF(k.id_siswa IS NOT NULL, 'siswa', 'guru') AS jenis_pengguna
  FROM ketidakhadiran k
  LEFT JOIN siswa s ON k.id_siswa = s.id
  LEFT JOIN guru g ON k.id_guru = g.id
  WHERE k.tanggal = ?
  ORDER BY k.tanggal DESC, k.created_at DESC
  LIMIT 15";
$stmt = $conn->prepare($sql_recent_absences);
$stmt->bind_param("s", $current_date);
$stmt->execute();
$recent_absences = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Siswa dan Guru yang Belum Presensi
$sql_belum_presensi = "
  SELECT 'siswa' AS jenis_pengguna, s.id, s.nama_lengkap, s.tingkat_rombel AS info
  FROM siswa s
  LEFT JOIN presensi p ON s.id = p.id_siswa AND p.tanggal = ?
  LEFT JOIN ketidakhadiran k ON s.id = k.id_siswa AND k.tanggal = ?
  WHERE p.id IS NULL AND k.id IS NULL
  UNION
  SELECT 'guru' AS jenis_pengguna, g.id, g.nama_lengkap, g.jabatan AS info
  FROM guru g
  LEFT JOIN presensi p ON g.id = p.id_guru AND p.tanggal = ?
  LEFT JOIN ketidakhadiran k ON g.id = k.id_guru AND k.tanggal = ?
  WHERE p.id IS NULL AND k.id IS NULL";
$stmt = $conn->prepare($sql_belum_presensi);
$stmt->bind_param("ssss", $current_date, $current_date, $current_date, $current_date);
$stmt->execute();
$belum_presensi = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Attendance Trend Data
$trend_data = ['present' => [], 'absent' => []];
$trend_labels = [];
$days = 7;
for ($i = 0; $i < $days; $i++) {
  $date = date('Y-m-d', strtotime("-$i days"));
  $trend_labels[] = date('d M', strtotime($date));
  $stmt = $conn->prepare("SELECT COUNT(*) as total FROM presensi WHERE tanggal = ?");
  $stmt->bind_param("s", $date);
  $stmt->execute();
  $result = $stmt->get_result();
  $present = $result->fetch_assoc()['total'] ?? 0;
  $stmt = $conn->prepare("SELECT COUNT(*) as total FROM ketidakhadiran WHERE tanggal = ?");
  $stmt->bind_param("s", $date);
  $stmt->execute();
  $result = $stmt->get_result();
  $absent = $result->fetch_assoc()['total'] ?? 0;
  $trend_data['present'][] = (int)$present;
  $trend_data['absent'][] = (int)$absent;
}
$trend_labels = array_reverse($trend_labels);
$trend_data['present'] = array_reverse($trend_data['present']);
$trend_data['absent'] = array_reverse($trend_data['absent']);
if (empty($trend_labels) || empty($trend_data['present']) || empty($trend_data['absent'])) {
  $trend_labels = ['No Data'];
  $trend_data['present'] = [0];
  $trend_data['absent'] = [0];
}


?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard - Sistem Presensi MIDU</title>
  <link href="assets/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
  <link href="assets/css/dataTables.bootstrap5.min.css" rel="stylesheet">
  <script src="assets/js/sweetalert2@11.js"></script>
  <script src="assets/js/chart.umd.min.js"></script>
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
    .widget-icon {
      font-size: 2rem;
      margin-right: 10px;
    }
    .widget-value {
      font-size: 1.5rem;
      font-weight: bold;
    }
    #attendanceTrendChart {
      max-height: 300px;
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
          <a href="logout.php" class="btn btn-outline-danger btn-sm ms-2">Logout</a>
        </div>
      </div>
    </nav>

    <div class="container-fluid">
      <h4 class="mb-4 text-end"><strong><?= $hari_ini . ', ' . $tanggal; ?></strong></h4>

      <!-- Summary Widgets -->
      <div class="row g-3 mb-4">
        <div class="col-md-3">
          <div class="card">
            <div class="card-body d-flex align-items-center">
              <i class="fas fa-users widget-icon text-primary"></i>
              <div>
                <h6 class="card-title">Total Siswa</h6>
                <div class="widget-value"><?php echo $total_students; ?></div>
              </div>
            </div>
          </div>
        </div>
        <div class="col-md-3">
          <div class="card">
            <div class="card-body d-flex align-items-center">
              <i class="fas fa-chalkboard-teacher widget-icon text-success"></i>
              <div>
                <h6 class="card-title">Total Guru</h6>
                <div class="widget-value"><?php echo $total_teachers; ?></div>
              </div>
            </div>
          </div>
        </div>
        <div class="col-md-3">
          <div class="card">
            <div class="card-body d-flex align-items-center">
              <i class="fas fa-check-circle widget-icon text-info"></i>
              <div>
                <h6 class="card-title">Hadir Hari Ini</h6>
                <div class="widget-value"><?php echo $total_present; ?></div>
              </div>
            </div>
          </div>
        </div>
        <div class="col-md-3">
          <div class="card">
            <div class="card-body d-flex align-items-center">
              <i class="fas fa-times-circle widget-icon text-danger"></i>
              <div>
                <h6 class="card-title">Tidak Hadir Hari Ini</h6>
                <div class="widget-value"><?php echo $total_absent; ?></div>
              </div>
            </div>
          </div>
        </div>
      </div>
        <!-- Attendance Trend Chart -->
      <div class="row g-3 mb-4">
        <div class="col-md-12">
          <div class="card">
            <div class="card-header">
              <h5 class="mb-0">Tren Kehadiran (7 Hari Terakhir)</h5>
            </div>
            <div class="card-body">
              <canvas id="attendanceTrendChart"></canvas>
              <?php if (empty($trend_data['present']) && empty($trend_data['absent'])): ?>
                <p class="text-center text-muted mt-3">Tidak ada data kehadiran untuk ditampilkan.</p>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>
      <!-- Recent Attendance and Absences -->
      <div class="row g-3 ">
        <div class="col-md-6">
          <div class="card">
            <div class="card-header">
              <h5 class="mb-0">Presensi Terbaru (Hari Ini)</h5>
            </div>
            <div class="card-body">
              <div class="table-responsive">
                <table class="table table-bordered table-striped table-sm" id="recentAttendance">
                  <thead class="table-dark">
                    <tr>
                      <th>No</th>
                      <th>Nama</th>
                      <th>Jenis</th>
                      <th>Tanggal</th>
                      <th>Jam Masuk</th>
                      <th>Status</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php $no = 1; foreach ($recent_attendance as $row): ?>
                    <tr>
                      <td><?= $no++ ?></td>
                      <td><?= htmlspecialchars($row['nama']) ?></td>
                      <td><?= ucfirst($row['jenis_pengguna']) ?></td>
                      <td><?= date('d-m-Y', strtotime($row['tanggal'])) ?></td>
                      <td><?= $row['jam_masuk'] ?? '-' ?></td>
                      <td><?= $row['status_masuk'] ?? '-' ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($recent_attendance)): ?>
                    <!-- <tr><td colspan="6" class="text-center">Tidak ada data presensi hari ini.</td></tr> -->
                    <?php endif; ?>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>
        <div class="col-md-6">
          <div class="card">
            <div class="card-header">
              <h5 class="mb-0">Ketidakhadiran Terbaru (Hari Ini)</h5>
            </div>
            <div class="card-body">
              <div class="table-responsive">
                <table class="table table-bordered table-striped table-sm" id="recentAbsences">
                  <thead class="table-dark">
                    <tr>
                      <th>No</th>
                      <th>Nama</th>
                      <th>Jenis</th>
                      <th>Tanggal</th>
                      <th>Keterangan</th>
                      <th>Alasan</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php $no = 1; foreach ($recent_absences as $row): ?>
                    <tr>
                      <td><?= $no++ ?></td>
                      <td><?= htmlspecialchars($row['nama']) ?></td>
                      <td><?= ucfirst($row['jenis_pengguna']) ?></td>
                      <td><?= date('d-m-Y', strtotime($row['tanggal'])) ?></td>
                      <td><?= $row['keterangan'] ?? '-' ?></td>
                      <td><?= $row['alasan'] ? htmlspecialchars($row['alasan']) : '-' ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($recent_absences)): ?>
                    <!-- <tr><td colspan="6" class="text-center">Tidak ada data ketidakhadiran hari ini.</td></tr> -->
                    <?php endif; ?>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>
      </div>
    

      <!-- Belum Presensi -->
      <div class="row g-3">
        <div class="col-md-12">
          <div class="card">
            <div class="card-header">
              <h5 class="mb-0">Siswa dan Guru yang Belum Presensi (Hari Ini)</h5>
            </div>
            <div class="card-body">
              <div class="mb-3">
                <button class="btn btn-success" id="catatKetidakhadiran"><i class="fas fa-save me-2"></i>Catat Ketidakhadiran</button>
              </div>
              <div class="table-responsive">
                <table class="table table-bordered table-striped table-sm" id="belumPresensi">
                  <thead class="table-dark">
                    <tr>
                      <th><input type="checkbox" id="selectAllBelum"></th>
                      <th>No</th>
                      <th>Nama</th>
                      <th>Jenis</th>
                      <th>Kelas/Jabatan</th>
                      <th>Keterangan</th>
                      <th>Alasan</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php $no = 1; foreach ($belum_presensi as $row): ?>
                    <tr>
                      <td><input type="checkbox" class="selectBelum" data-id="<?php echo $row['id']; ?>" data-jenis="<?php echo $row['jenis_pengguna']; ?>"></td>
                      <td><?php echo $no++; ?></td>
                      <td><?php echo htmlspecialchars($row['nama_lengkap']); ?></td>
                      <td><?php echo ucfirst($row['jenis_pengguna']); ?></td>
                      <td><?php echo htmlspecialchars($row['info'] ?? '-'); ?></td>
                      <td>
                        <select class="form-select keterangan" data-id="<?php echo $row['id']; ?>">
                          <option value="">Pilih Keterangan</option>
                          <option value="izin">Izin</option>
                          <option value="sakit">Sakit</option>
                          <option value="alpa">Alpa</option>
                          <option value="cuti">Cuti</option>
                        </select>
                      </td>
                      <td><input type="text" class="form-control alasan" data-id="<?php echo $row['id']; ?>"></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($belum_presensi)): ?>
                    <tr>
                      <td></td>
                      <td></td>
                      <td></td>
                      <td>Tidak ada siswa atau guru yang belum presensi hari ini.</td>
                      <td></td>
                      <td></td>
                      <td></td>
                    </tr>
                    <?php endif; ?>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <script src="assets/js/bootstrap.bundle.min.js"></script>
  <script src="assets/js/jquery-3.7.1.min.js"></script>
  <script src="assets/js/jquery.dataTables.min.js"></script>
  <script src="assets/js/dataTables.bootstrap5.min.js"></script>
  <script>
    $(document).ready(function() {
      // Debug table structure
      console.log('recentAttendance rows:', $('#recentAttendance tbody tr').length);
      console.log('recentAbsences rows:', $('#recentAbsences tbody tr').length);
      console.log('belumPresensi rows:', $('#belumPresensi tbody tr').length);

      // Initialize DataTables
      if ($('#recentAttendance').length) {
        $('#recentAttendance').DataTable({
          "pageLength": 5,
          "lengthChange": false,
          "searching": false,
          "ordering": false
        });
      }
      if ($('#recentAbsences').length) {
        $('#recentAbsences').DataTable({
          "pageLength": 5,
          "lengthChange": false,
          "searching": false,
          "ordering": false
        });
      }
      if ($('#belumPresensi').length) {
        $('#belumPresensi').DataTable({
          "pageLength": 10,
          "lengthChange": true,
          "searching": true,
          "ordering": true,
          "drawCallback": function() {
            updateCatatButton();
          },
          "columnDefs": [
            {
              "targets": 0, // Checkbox column
              "orderable": false,
              "searchable": false
            },
            {
              "targets": 5, // Keterangan (select)
              "orderable": false,
              "searchable": false
            },
            {
              "targets": 6, // Alasan (input)
              "orderable": false,
              "searchable": false
            }
          ]
        });
      }

      // Sidebar toggle
      $('#toggleSidebarBtn').click(function() {
        $('.sidebar').toggleClass('collapsed');
        $('#mainContent').toggleClass('expanded');
      });

      // Checkbox Pilih Semua
      $('#selectAllBelum').on('change', function() {
        const isChecked = $(this).prop('checked');
        $('.selectBelum').prop('checked', isChecked);
        updateCatatButton();
      });

      // Checkbox Individual
      $(document).on('change', '.selectBelum', function() {
        updateCatatButton();
      });

      // Update Status Tombol Catat
      function updateCatatButton() {
        const checkedCount = $('.selectBelum:checked').length;
        if (checkedCount > 0) {
          $('#catatKetidakhadiran').show().text(`Catat Ketidakhadiran (${checkedCount})`);
        } else {
          $('#catatKetidakhadiran').hide();
        }
      }

      // Catat Ketidakhadiran
    $('#catatKetidakhadiran').on('click', function() {
    const data = [];
    const invalidRecords = [];
    
    $('.selectBelum:checked').each(function() {
        const id = $(this).data('id');
        const jenis = $(this).data('jenis');
        const keterangan = $(`.keterangan[data-id="${id}"]`).val().trim();
        const alasan = $(`.alasan[data-id="${id}"]`).val().trim();
        
        console.log('Data for ID', id, ':', { jenis_pengguna: jenis, keterangan: keterangan, alasan: alasan }); // Debug log
        
        // Validate keterangan
        if (!keterangan) {
        const nama = $(`.selectBelum[data-id="${id}"]`).closest('tr').find('td:eq(2)').text();
        invalidRecords.push(`ID ${id} (${nama})`);
        return true; // Continue to next record
        }
        
        data.push({
        id: id,
        jenis_pengguna: jenis,
        tanggal: '<?php echo date('Y-m-d'); ?>',
        keterangan: keterangan,
        alasan: alasan || null // Send null if alasan is empty
        });
    });

    // Check for invalid records
    if (invalidRecords.length > 0) {
        Swal.fire({
        icon: 'warning',
        title: 'Peringatan!',
        text: `Pilih keterangan untuk: ${invalidRecords.join(', ')}.`,
        confirmButtonColor: '#dc3545'
        });
        return;
    }

    // Check if any records were selected
    if (data.length === 0) {
        Swal.fire({
        icon: 'warning',
        title: 'Peringatan!',
        text: 'Pilih setidaknya satu data untuk dicatat.',
        confirmButtonColor: '#dc3545'
        });
        return;
    }

    console.log('AJAX Data:', JSON.stringify(data)); // Debug full payload

    Swal.fire({
        title: 'Yakin mencatat ' + data.length + ' ketidakhadiran?',
        text: "Data akan disimpan ke tabel ketidakhadiran.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#28a745',
        cancelButtonColor: '#dc3545',
        confirmButtonText: 'Ya, catat!',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
        $.ajax({
            url: 'ketidakhadiran_save.php',
            method: 'POST',
            data: { data: JSON.stringify(data) },
            dataType: 'json',
            beforeSend: function() {
            $('#catatKetidakhadiran').prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i>Mencatat...');
            },
            success: function(response) {
            $('#catatKetidakhadiran').prop('disabled', false).html('<i class="fas fa-save me-2"></i>Catat Ketidakhadiran');
            if (response.success) {
                Swal.fire({
                icon: 'success',
                title: 'Berhasil!',
                text: response.success,
                confirmButtonColor: '#28a745'
                }).then(() => {
                location.reload(); // Refresh halaman untuk memperbarui tabel
                });
            } else {
                Swal.fire({
                icon: 'error',
                title: 'Gagal!',
                text: response.error || 'Gagal mencatat ketidakhadiran.',
                confirmButtonColor: '#dc3545'
                });
            }
            },
            error: function(xhr, status, error) {
            $('#catatKetidakhadiran').prop('disabled', false).html('<i class="fas fa-save me-2"></i>Catat Ketidakhadiran');
            console.error('AJAX Error:', status, error, xhr.responseText);
            Swal.fire({
                icon: 'error',
                title: 'Gagal!',
                text: 'Gagal mencatat ketidakhadiran: ' + error,
                confirmButtonColor: '#dc3545'
            });
            }
        });
        }
    });
    });
      // Chart.js for Attendance Trend
      try {
        const ctx = document.getElementById('attendanceTrendChart').getContext('2d');
        if (!ctx) {
          console.error('Canvas element not found');
          return;
        }
        new Chart(ctx, {
          type: 'line',
          data: {
            labels: <?php echo json_encode($trend_labels, JSON_HEX_QUOT | JSON_HEX_APOS); ?>,
            datasets: [
              {
                label: 'Hadir',
                data: <?php echo json_encode($trend_data['present'], JSON_HEX_QUOT | JSON_HEX_APOS); ?>,
                borderColor: '#28a745',
                backgroundColor: 'rgba(40, 167, 69, 0.2)',
                fill: true,
                tension: 0.4
              },
              {
                label: 'Tidak Hadir',
                data: <?php echo json_encode($trend_data['absent'], JSON_HEX_QUOT | JSON_HEX_APOS); ?>,
                borderColor: '#dc3545',
                backgroundColor: 'rgba(220, 53, 69, 0.2)',
                fill: true,
                tension: 0.4
              }
            ]
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
              y: {
                beginAtZero: true,
                title: { display: true, text: 'Jumlah' }
              },
              x: {
                title: { display: true, text: 'Tanggal' }
              }
            },
            plugins: {
              legend: { display: true }
            }
          }
        });
      } catch (error) {
        console.error('Chart initialization failed:', error);
      }

      // Check jadwal for hiding belumPresensi table
      fetch('jadwal.php')
        .then(res => res.json())
        .then(data => {
          const jamMasuk = data.jam_masuk; // e.g., "07:30"
          if (jamMasuk) {
            const [hours, minutes] = jamMasuk.split(':').map(Number);
            const now = new Date();
            const jamMasukTime = new Date();
            jamMasukTime.setHours(hours, minutes, 0, 0);
            const bufferTime = new Date(jamMasukTime.getTime() + 30 * 60000); // Add 30 minutes
            if (now < bufferTime) {
              $('#belumPresensi').closest('.card').hide();
              $('#catatKetidakhadiran').hide();
            } else {
              $('#belumPresensi').closest('.card').show();
            }
          }
        })
        .catch(() => {
          console.error('Gagal mengambil jadwal');
        });

        // Scroll to belumPresensi table if URL has #belumPresensi
if (window.location.hash === '#belumPresensi') {
  $('html, body').animate({
    scrollTop: $('#belumPresensi').offset().top - 100
  }, 500);
}
    });
  </script>
</body>
</html>
<?php $conn->close(); ?>