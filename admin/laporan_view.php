<?php
session_start();
require_once 'config.php';
include 'auth.php';


$id = $_GET['id'] ?? '';
$jenis = $_GET['jenis'] ?? '';
$tgl_awal = $_GET['tgl_awal'] ?? date('Y-m-01');
$tgl_akhir = $_GET['tgl_akhir'] ?? date('Y-m-t');

if (!$id || !$jenis || !in_array($jenis, ['siswa', 'guru'])) {
  echo "Parameter tidak valid.";
  exit;
}

// Ambil data nama & info siswa/guru
if ($jenis === 'siswa') {
  $stmt = $conn->prepare("SELECT nama_lengkap, tingkat_rombel AS info FROM siswa WHERE id = ?");
} else {
  $stmt = $conn->prepare("SELECT nama_lengkap, jabatan AS info FROM guru WHERE id = ?");
}
$stmt->bind_param("i", $id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
if (!$user) {
  echo "Data tidak ditemukan.";
  exit;
}

// Ambil presensi detail
if ($jenis === 'siswa') {
  $sql_presensi = "SELECT tanggal, jam_masuk, status_masuk, jam_pulang, status_pulang, NULL AS keterangan, NULL AS alasan FROM presensi WHERE id_siswa = ? AND tanggal BETWEEN ? AND ?";
} else {
  $sql_presensi = "SELECT tanggal, jam_masuk, status_masuk, jam_pulang, status_pulang, NULL AS keterangan, NULL AS alasan FROM presensi WHERE id_guru = ? AND tanggal BETWEEN ? AND ?";
}
$stmt_presensi = $conn->prepare($sql_presensi);
$stmt_presensi->bind_param("iss", $id, $tgl_awal, $tgl_akhir);
$stmt_presensi->execute();
$result_presensi = $stmt_presensi->get_result();
$presensi_data = [];
while ($row = $result_presensi->fetch_assoc()) {
  $presensi_data[] = $row;
}

// Ambil ketidakhadiran detail
if ($jenis === 'siswa') {
  $sql_ketidakhadiran = "SELECT tanggal, NULL AS jam_masuk, NULL AS status_masuk, NULL AS jam_pulang, NULL AS status_pulang, keterangan, alasan FROM ketidakhadiran WHERE id_siswa = ? AND tanggal BETWEEN ? AND ?";
} else {
  $sql_ketidakhadiran = "SELECT tanggal, NULL AS jam_masuk, NULL AS status_masuk, NULL AS jam_pulang, NULL AS status_pulang, keterangan, alasan FROM ketidakhadiran WHERE id_guru = ? AND tanggal BETWEEN ? AND ?";
}
$stmt_ketidakhadiran = $conn->prepare($sql_ketidakhadiran);
$stmt_ketidakhadiran->bind_param("iss", $id, $tgl_awal, $tgl_akhir);
$stmt_ketidakhadiran->execute();
$result_ketidakhadiran = $stmt_ketidakhadiran->get_result();
$ketidakhadiran_data = [];
while ($row = $result_ketidakhadiran->fetch_assoc()) {
  $ketidakhadiran_data[] = $row;
}

// Gabungkan data presensi dan ketidakhadiran
$combined_data = array_merge($presensi_data, $ketidakhadiran_data);

// Urutkan berdasarkan tanggal
usort($combined_data, function($a, $b) {
  return strtotime($a['tanggal']) - strtotime($b['tanggal']);
});
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
    .alasan-column {
      max-width: 200px;
      word-wrap: break-word;
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
<div class="container-fluid">
    <div class="card">
        <div class="card-header mb-3">
          <h3 class="mb-0">Detail Presensi dan Ketidakhadiran</h3>
        </div>
        <div class="card-body">
          <div class="col-md-4">
            <table class="table table-striped table-sm">
              <tr>
                <td><strong>Nama Lengkap</strong></td>
                <td width="10px">:</td>
                <td><?= htmlspecialchars($user['nama_lengkap']) ?></td>
              </tr>
              <tr>
                <td><strong><?= $jenis === 'siswa' ? 'Kelas/Rombel' : 'Jabatan' ?></strong></td>
                <td width="10px">:</td>
                <td><?= htmlspecialchars($user['info']) ?></td>
              </tr>
              <tr>
                <td><strong>Periode</strong></td>
                <td width="10px">:</td>
                <td><?= date('d M Y', strtotime($tgl_awal)) ?> s/d <?= date('d M Y', strtotime($tgl_akhir)) ?></td>
              </tr>
            </table>
          </div>

          <div class="table-responsive">
            <table class="table table-bordered table-sm" id="tabelpresensi">
              <thead class="table-dark">
                <tr>
                  <th>No</th>
                  <th>Tanggal</th>
                  <th>Jam Masuk</th>
                  <th>Status Masuk</th>
                  <th>Jam Pulang</th>
                  <th>Status Pulang</th>
                  <th>Keterangan</th>
                  <th class="alasan-column">Alasan</th>
                </tr>
              </thead>
              <tbody>
                <?php $no = 1; foreach ($combined_data as $row): ?>
                <tr>
                  <td><?= $no++ ?></td>
                  <td><?= date('d-m-Y', strtotime($row['tanggal'])) ?></td>
                  <td><?= $row['jam_masuk'] ?? '-' ?></td>
                  <td><?= $row['status_masuk'] ?? '-' ?></td>
                  <td><?= $row['jam_pulang'] ?? '-' ?></td>
                  <td><?= $row['status_pulang'] ?? '-' ?></td>
                  <td><?= $row['keterangan'] ?? '-' ?></td>
                  <td class="alasan-column"><?= $row['alasan'] ? htmlspecialchars($row['alasan']) : '-' ?></td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($combined_data)): ?>
                <tr><td colspan="8" class="text-center">Tidak ada data presensi atau ketidakhadiran.</td></tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>

          <a href="data-presensi.php?tgl_awal=<?= $tgl_awal ?>&tgl_akhir=<?= $tgl_akhir ?>&jenis=<?= $jenis ?>" class="btn btn-secondary mt-3">← Kembali</a>
        </div>
    </div>
</div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script>
  $(document).ready(function() {
    $('#tabelpresensi').DataTable({
      "pageLength": 10,
      "lengthMenu": [10, 25, 50, 100],
      "order": [[1, "asc"]] // Sort by Tanggal column
    });
  });
</script>
</body>
</html>