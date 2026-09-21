<?php
include 'auth.php';
require_once '../config/db.php';
require_once '../vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

$id = $_GET['id'] ?? '';
$jenis = $_GET['jenis'] ?? '';
$tgl_awal = $_GET['tgl_awal'] ?? date('Y-m-01');
$tgl_akhir = $_GET['tgl_akhir'] ?? date('Y-m-t');

if (!$id || !$jenis || !in_array($jenis, ['siswa', 'guru'])) {
  die("Parameter tidak valid.");
}

// Ambil data siswa/guru
if ($jenis === 'siswa') {
  $stmt = $conn->prepare("SELECT nama_lengkap, tingkat_rombel AS info FROM siswa WHERE id = ?");
} else {
  $stmt = $conn->prepare("SELECT nama_lengkap, jabatan AS info FROM guru WHERE id = ?");
}
$stmt->bind_param("i", $id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
if (!$user) {
  die("Data tidak ditemukan.");
}

// Ambil data presensi
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

// Ambil data ketidakhadiran
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
  <style>
    body { font-family: DejaVu Sans, sans-serif; font-size: 12px; }
    .header {
      text-align: center;
      border-bottom: 2px solid #000;
      padding-bottom: 10px;
      margin-bottom: 10px;
    }
    .header img {
      float: left;
      height: 60px;
    }
    .title {
      font-size: 16px;
      font-weight: bold;
      margin: 0;
    }
    .subtitle {
      font-size: 13px;
      margin: 0;
    }
    .info {
      margin-top: 15px;
    }
    table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 10px;
    }
    th, td {
      border: 1px solid #444;
      padding: 5px;
      text-align: center;
    }
    th {
      background: #eaeaea;
    }
    tr:nth-child(even) {
      background: #f9f9f9;
    }
    .footer {
      margin-top: 50px;
      text-align: right;
    }
    .ttd {
      margin-top: 60px;
    }
  </style>
</head>
<body>

<div class="header">
  <img src="http://localhost/presensiapp/assets/img/logo.png">
  <div>
    <div class="title">MIDU GROGOL BANYUWANGI</div>
    <div class="subtitle">Pelinggihan, Grogol, Kec. Giri, Kabupaten Banyuwangi, Jawa Timur 68425</div>
    <div class="subtitle">Telp: 0812-3456-7890 | Email: admin@midugrogol.sch.id</div>
  </div>
</div>

<h3 style="text-align:center; margin: 10px 0;">Laporan Presensi <?= ucfirst($jenis) ?></h3>
<p style="text-align:center; margin: 0;">
  Periode: <?= date('d M Y', strtotime($tgl_awal)) ?> s/d <?= date('d M Y', strtotime($tgl_akhir)) ?>
</p>

<div class="info">
  <strong>Nama:</strong> <?= $user['nama_lengkap'] ?><br>
  <strong><?= $jenis === 'siswa' ? 'Kelas' : 'Jabatan' ?>:</strong> <?= $user['info'] ?>
</div>

<table>
  <thead>
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
    <tr><td colspan="8">Tidak ada data presensi atau ketidakhadiran.</td></tr>
    <?php endif; ?>
  </tbody>
</table>

<div class="footer">
  Banyuwangi, <?= date('d M Y') ?><br>
  <div class="ttd">__________________________<br>Admin / Wali Kelas</div>
</div>

</body>
</html>

<?php
$html = ob_get_clean();

$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('defaultFont', 'DejaVu Sans');

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

$namaFile = "laporan_presensi_" . strtolower($jenis) . "_" . date('Ymd') . ".pdf";
$dompdf->stream($namaFile, ["Attachment" => true]);
exit;
