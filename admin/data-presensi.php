<?php
session_start();
require_once 'config.php';
include 'auth.php';
$activePage = 'data-presensi';

$tgl_awal = $_GET['tgl_awal'] ?? date('Y-m-01');
$tgl_akhir = $_GET['tgl_akhir'] ?? date('Y-m-t');
$jenis = $_GET['jenis'] ?? 'semua';
$kelas = $_GET['kelas'] ?? '';
$jabatan = $_GET['jabatan'] ?? '';

// Ambil daftar kelas
$daftar_kelas = [];
$q1 = $conn->query("SELECT DISTINCT tingkat_rombel FROM siswa ORDER BY tingkat_rombel");
while ($r = $q1->fetch_assoc()) $daftar_kelas[] = $r['tingkat_rombel'];

// Ambil daftar jabatan
$daftar_jabatan = [];
$q2 = $conn->query("SELECT DISTINCT jabatan FROM guru ORDER BY jabatan");
while ($r = $q2->fetch_assoc()) $daftar_jabatan[] = $r['jabatan'];

// Filter presensi
$where_presensi = ["tanggal BETWEEN ? AND ?"];
$params_presensi = [$tgl_awal, $tgl_akhir];
$types_presensi = "ss";

if ($jenis === 'siswa') {
  $where_presensi[] = "jenis_pengguna = 'siswa'";
  if ($kelas) {
    $where_presensi[] = "s.tingkat_rombel = ?";
    $params_presensi[] = $kelas;
    $types_presensi .= "s";
  }
} elseif ($jenis === 'guru') {
  $where_presensi[] = "jenis_pengguna = 'guru'";
  if ($jabatan) {
    $where_presensi[] = "g.jabatan = ?";
    $params_presensi[] = $jabatan;
    $types_presensi .= "s";
  }
}
$where_presensi_str = implode(" AND ", $where_presensi);

// Filter ketidakhadiran
$where_ketidakhadiran = ["tanggal BETWEEN ? AND ?"];
$params_ketidakhadiran = [$tgl_awal, $tgl_akhir];
$types_ketidakhadiran = "ss";

if ($jenis === 'siswa') {
  $where_ketidakhadiran[] = "jenis_pengguna = 'siswa'";
  if ($kelas) {
    $where_ketidakhadiran[] = "s.tingkat_rombel = ?";
    $params_ketidakhadiran[] = $kelas;
    $types_ketidakhadiran .= "s";
  }
} elseif ($jenis === 'guru') {
  $where_ketidakhadiran[] = "jenis_pengguna = 'guru'";
  if ($jabatan) {
    $where_ketidakhadiran[] = "g.jabatan = ?";
    $params_ketidakhadiran[] = $jabatan;
    $types_ketidakhadiran .= "s";
  }
}
$where_ketidakhadiran_str = implode(" AND ", $where_ketidakhadiran);

// Query presensi
$sql1 = "
  SELECT 
    jenis_pengguna,
    IF(jenis_pengguna='siswa', s.nama_lengkap, g.nama_lengkap) AS nama,
    IF(jenis_pengguna='siswa', s.tingkat_rombel, g.jabatan) AS info,
    IFNULL(p.id_siswa, p.id_guru) AS pengguna_id,
    COUNT(*) AS hadir,
    SUM(CASE WHEN status_masuk='Tepat Waktu' THEN 1 ELSE 0 END) AS tepat_waktu,
    SUM(CASE WHEN status_masuk='Terlambat' THEN 1 ELSE 0 END) AS terlambat,
    SUM(CASE WHEN status_pulang='Pulang Cepat' THEN 1 ELSE 0 END) AS pulang_cepat
  FROM presensi p
  LEFT JOIN siswa s ON p.id_siswa = s.id
  LEFT JOIN guru g ON p.id_guru = g.id
  WHERE $where_presensi_str
  GROUP BY jenis_pengguna, pengguna_id
";

// Query ketidakhadiran
$sql2 = "
  SELECT 
    jenis_pengguna,
    IF(jenis_pengguna='siswa', s.nama_lengkap, g.nama_lengkap) AS nama,
    IF(jenis_pengguna='siswa', s.tingkat_rombel, g.jabatan) AS info,
    IFNULL(k.id_siswa, k.id_guru) AS pengguna_id,
    SUM(keterangan = 'ijin') AS ijin,
    SUM(keterangan = 'sakit') AS sakit,
    SUM(keterangan = 'alpa') AS alpa,
    SUM(keterangan = 'cuti') AS cuti
  FROM ketidakhadiran k
  LEFT JOIN siswa s ON k.id_siswa = s.id
  LEFT JOIN guru g ON k.id_guru = g.id
  WHERE $where_ketidakhadiran_str
  GROUP BY jenis_pengguna, pengguna_id
";

// Eksekusi
$stmt1 = $conn->prepare($sql1);
$stmt1->bind_param($types_presensi, ...$params_presensi);
$stmt1->execute();
$res1 = $stmt1->get_result();
$data_presensi = [];
while ($r = $res1->fetch_assoc()) {
  $id = $r['jenis_pengguna'] . '_' . $r['pengguna_id'];
  $data_presensi[$id] = $r;
}

$stmt2 = $conn->prepare($sql2);
$stmt2->bind_param($types_ketidakhadiran, ...$params_ketidakhadiran);
$stmt2->execute();
$res2 = $stmt2->get_result();
$data_ketidakhadiran = [];
while ($r = $res2->fetch_assoc()) {
  $id = $r['jenis_pengguna'] . '_' . $r['pengguna_id'];
  $data_ketidakhadiran[$id] = $r;
}

// Gabungkan data
$rekap = [];

foreach ($data_presensi as $id => $p) {
  $k = $data_ketidakhadiran[$id] ?? [];
  $rekap[] = [
    'nama' => $p['nama'],
    'info' => $p['info'],
    'jenis_pengguna' => $p['jenis_pengguna'],
    'pengguna_id' => $p['pengguna_id'],
    'hadir' => $p['hadir'],
    'tepat_waktu' => $p['tepat_waktu'],
    'terlambat' => $p['terlambat'],
    'pulang_cepat' => $p['pulang_cepat'],
    'ijin' => $k['ijin'] ?? 0,
    'sakit' => $k['sakit'] ?? 0,
    'alpa' => $k['alpa'] ?? 0,
    'cuti' => $k['cuti'] ?? 0,
  ];
}

foreach ($data_ketidakhadiran as $id => $k) {
  if (!isset($data_presensi[$id])) {
    $rekap[] = [
      'nama' => $k['nama'],
      'info' => $k['info'],
      'jenis_pengguna' => $k['jenis_pengguna'],
      'pengguna_id' => $k['pengguna_id'],
      'hadir' => 0,
      'tepat_waktu' => 0,
      'terlambat' => 0,
      'pulang_cepat' => 0,
      'ijin' => $k['ijin'] ?? 0,
      'sakit' => $k['sakit'] ?? 0,
      'alpa' => $k['alpa'] ?? 0,
      'cuti' => $k['cuti'] ?? 0,
    ];
  }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Data Guru - Sistem Presensi MIDU</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <style>
    body {
      background-color: #f1f5f9;
      overflow-x: hidden;
    }

    label {
      font-weight: 500;
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
<div class="container-fluid">
    <div class="card">
        <div class="card-header mb-3">
          <h3 class="mb-0">Laporan Presensi</h3>
        </div>
        <div class="card-body">
  
  <form class="row g-2 mb-3" method="get">

    
    <div class="col-md-2">
      <label class="form-label">Dari Tgl</label>
      <input type="date" name="tgl_awal" value="<?= $tgl_awal ?>" class="form-control">
    </div>
    <div class="col-md-2">
      <label class="form-label">Sampai Tgl</label>
      <input type="date" name="tgl_akhir" value="<?= $tgl_akhir ?>" class="form-control">
    </div>
    <div class="col-md-2">
      <label class="form-label">Jenis</label>
      <select name="jenis" class="form-select" onchange="this.form.submit()">
        <option value="semua" <?= $jenis === 'semua' ? 'selected' : '' ?>>Semua</option>
        <option value="siswa" <?= $jenis === 'siswa' ? 'selected' : '' ?>>Siswa</option>
        <option value="guru" <?= $jenis === 'guru' ? 'selected' : '' ?>>Guru</option>
      </select>
    </div>

    <?php if ($jenis === 'siswa'): ?>
    <div class="col-md-2">
      <label class="form-label">Kelas</label>
      <select name="kelas" class="form-select">
        <option value="">Semua</option>
        <?php foreach ($daftar_kelas as $k): ?>
        <option value="<?= $k ?>" <?= $kelas === $k ? 'selected' : '' ?>><?= $k ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <?php endif; ?>

    <?php if ($jenis === 'guru'): ?>
    <div class="col-md-2">
      <label class="form-label">Jabatan</label>
      <select name="jabatan" class="form-select">
        <option value="">Semua</option>
        <?php foreach ($daftar_jabatan as $j): ?>
        <option value="<?= $j ?>" <?= $jabatan === $j ? 'selected' : '' ?>><?= $j ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <?php endif; ?>

    <div class="col-md-2 align-self-end d-flex gap-2">
        <button type="submit" class="btn btn-success w-100"><i class="fas fa-filter me-2"></i>Terapkan</button>
        <a href="data-presensi.php" class="btn btn-secondary w-100"><i class="fas fa-undo me-2"></i>Reset</a>
    </div>

  </form>
  <?php
  // Ambil nilai info (kelas/jabatan) sesuai jenis
  $info = '';
  if ($jenis === 'siswa') {
    $info = $kelas;
  } elseif ($jenis === 'guru') {
    $info = $jabatan;
  }
?>

<div class="mb-3">
  <a href="laporan_rekap_pdf.php?jenis=<?= $jenis ?>&tgl_awal=<?= $tgl_awal ?>&tgl_akhir=<?= $tgl_akhir ?>&info=<?= urlencode($info) ?>" 
     class="btn btn-warning" target="_blank">
    <i class="fas fa-file-pdf"></i> Cetak Rekap PDF
  </a>
</div>


  <!-- Tabel -->
  <div class="table-responsive">
    <table class="table table-bordered table-striped align-middle" id="tabelpresensi">
      <thead class="text-center">
        <tr>
          <th>No</th>
          <th>Nama</th>
          <th>Jenis</th>
          <th>Kelas / Jabatan</th>
          <th>Hadir</th>
          <th>Tepat Waktu</th>
          <th>Terlambat</th>
          <th>Pulang Cepat</th>
          <th>Ijin</th>
          <th>Sakit</th>
          <th>Alpa</th>
          <th>Cuti</th>
          <th>Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php $no = 1; foreach ($rekap as $row): ?>
        <tr>
          <td><?= $no++ ?></td>
          <td><?= $row['nama'] ?></td>
          <td><?= ucfirst($row['jenis_pengguna']) ?></td>
          <td><?= $row['info'] ?></td>
          <td class="text-center"><?= $row['hadir'] ?></td>
          <td class="text-center"><?= $row['tepat_waktu'] ?></td>
          <td class="text-center"><?= $row['terlambat'] ?></td>
          <td class="text-center"><?= $row['pulang_cepat'] ?></td>
          <td class="text-center"><?= $row['ijin'] ?></td>
          <td class="text-center"><?= $row['sakit'] ?></td>
          <td class="text-center"><?= $row['alpa'] ?></td>
          <td class="text-center"><?= $row['cuti'] ?></td>
          <td class="text-center">
            <a href="laporan_view.php?jenis=<?= $row['jenis_pengguna'] ?>&id=<?= $row['pengguna_id'] ?>&tgl_awal=<?= $tgl_awal ?>&tgl_akhir=<?= $tgl_akhir ?>" class="btn btn-sm btn-info">View</a>
            <a href="laporan_cetak_individu.php?jenis=<?= $row['jenis_pengguna'] ?>&id=<?= $row['pengguna_id'] ?>&tgl_awal=<?= $tgl_awal ?>&tgl_akhir=<?= $tgl_akhir ?>" class="btn btn-sm btn-success" target="_blank">Cetak</a>
            <a href="laporan_pdf_individu.php?jenis=<?= $row['jenis_pengguna'] ?>&id=<?= $row['pengguna_id'] ?>&tgl_awal=<?= $tgl_awal ?>&tgl_akhir=<?= $tgl_akhir ?>" class="btn btn-sm btn-danger">PDF</a>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
        </div>
    </div>
</div>
  
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
  <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
  <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
</body>
<script>$('#tabelpresensi').DataTable();</script>
</html>
