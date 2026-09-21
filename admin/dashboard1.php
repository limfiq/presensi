<?php
session_start();
require_once 'config.php';
if (!isset($_SESSION['admin'])) {
  header("Location: login.php");
  exit;
}

// Get current date (July 15, 2025)
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

// Today's Schedules (fallback to avoid errors if jadwal schema is unknown)
$total_schedules = 0;
try {
  $stmt = $conn->prepare("SELECT COUNT(*) as total FROM jadwal WHERE tanggal = ?");
  $stmt->bind_param("s", $current_date);
  $stmt->execute();
  $result = $stmt->get_result();
  $total_schedules = $result->fetch_assoc()['total'];
} catch (Exception $e) {
  // Fallback: Assume jadwal uses 'hari' (day of the week)
  $day_name = date('l', strtotime($current_date)); // e.g., 'Tuesday'
  $stmt = $conn->prepare("SELECT COUNT(*) as total FROM jadwal WHERE hari = ?");
  $stmt->bind_param("s", $day_name);
  $stmt->execute();
  $result = $stmt->get_result();
  $total_schedules = $result->fetch_assoc()['total'];
}

// Recent Attendance Records (last 5)
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

// Recent Absence Records (last 5)
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

// Attendance Trend Data (last 7 days)
$trend_data = ['present' => [], 'absent' => []];
$trend_labels = [];
$days = 7;
for ($i = 0; $i < $days; $i++) {
  $date = date('Y-m-d', strtotime("-$i days"));
  $trend_labels[] = date('d M', strtotime($date));
  
  // Present count
  $stmt = $conn->prepare("SELECT COUNT(*) as total FROM presensi WHERE tanggal = ?");
  $stmt->bind_param("s", $date);
  $stmt->execute();
  $result = $stmt->get_result();
  $present = $result->fetch_assoc()['total'] ?? 0;
  
  // Absent count
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

// Fallback for empty chart data
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
  <link href="assets/css/dataTables.bootstrap5.min.css" rel="stylesheet" >
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
      <h3 class="mb-4">Dashboard (15 Jul 2025, 18:15 WIB)</h3>

      <!-- Summary Widgets -->
      <div class="row g-3 mb-4">
        <div class="col-md-3">
          <div class="card">
            <div class="card-body d-flex align-items-center">
              <i class="fas fa-users widget-icon text-primary"></i>
              <div>
                <h6 class="card-title">Total Siswa</h6>
                <div class="widget-value"><?= $total_students ?></div>
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
                <div class="widget-value"><?= $total_teachers ?></div>
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
                <div class="widget-value"><?= $total_present ?></div>
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
                <div class="widget-value"><?= $total_absent ?></div>
              </div>
            </div>
          </div>
        </div>
        <!-- <div class="col-md-3">
          <div class="card">
            <div class="card-body d-flex align-items-center">
              <i class="fas fa-clock widget-icon text-warning"></i>
              <div>
                <h6 class="card-title">Terlambat Hari Ini</h6>
                <div class="widget-value"><?= $total_late ?></div>
              </div>
            </div>
          </div>
        </div>
        <div class="col-md-3">
          <div class="card">
            <div class="card-body d-flex align-items-center">
              <i class="fas fa-calendar-alt widget-icon text-secondary"></i>
              <div>
                <h6 class="card-title">Jadwal Hari Ini</h6>
                <div class="widget-value"><?= $total_schedules ?></div>
              </div>
            </div>
          </div>
        </div> -->
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

      
    </div>
</div>

<script src="assets/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/jquery-3.7.1.min.js"></script>
<script src="assets/js/jquery.dataTables.min.js"></script>
<script src="assets/js/dataTables.bootstrap5.min.js"></script>
<script>
  $(document).ready(function() {
    $('#recentAttendance').DataTable({
      "pageLength": 5,
      "lengthChange": false,
      "searching": false,
      "ordering": false
    });
    $('#recentAbsences').DataTable({
      "pageLength": 5,
      "lengthChange": false,
      "searching": false,
      "ordering": false
    });

    // Sidebar toggle
    $('#toggleSidebarBtn').click(function() {
      $('.sidebar').toggleClass('collapsed');
      $('#mainContent').toggleClass('expanded');
    });

    // Debug chart data
    const trendLabels = <?php echo json_encode($trend_labels, JSON_HEX_QUOT | JSON_HEX_APOS); ?>;
    const trendDataPresent = <?php echo json_encode($trend_data['present'], JSON_HEX_QUOT | JSON_HEX_APOS); ?>;
    const trendDataAbsent = <?php echo json_encode($trend_data['absent'], JSON_HEX_QUOT | JSON_HEX_APOS); ?>;
    console.log('Trend Labels:', trendLabels);
    console.log('Trend Data (Present):', trendDataPresent);
    console.log('Trend Data (Absent):', trendDataAbsent);

    // Validate chart data
    if (!Array.isArray(trendLabels) || !Array.isArray(trendDataPresent) || !Array.isArray(trendDataAbsent)) {
      console.error('Invalid chart data: Labels or datasets are not arrays');
      return;
    }

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
          labels: trendLabels,
          datasets: [
            {
              label: 'Hadir',
              data: trendDataPresent,
              borderColor: '#28a745',
              backgroundColor: 'rgba(40, 167, 69, 0.2)',
              fill: true,
              tension: 0.4
            },
            {
              label: 'Tidak Hadir',
              data: trendDataAbsent,
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