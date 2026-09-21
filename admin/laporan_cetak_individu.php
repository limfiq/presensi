<?php
require_once 'config.php';
include 'auth.php';

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
  <title>Cetak Laporan Presensi</title>
  <style>
    body { font-family: Arial, sans-serif; font-size: 12px; color: #000; }
    h2, h4, h5 { margin: 0; padding: 0; }
    .header { text-align: center; margin-bottom: 20px; }
    table { width: 100%; border-collapse: collapse; margin-top: 10px; }
    table, th, td { border: 1px solid #000; }
    th, td { padding: 5px; text-align: center; }
    .info { margin-bottom: 10px; }
    .no-print { margin-top: 20px; text-align: center; }
    .alasan-column { max-width: 200px; word-wrap: break-word; }
    @media print {
      .no-print { display: none; }
    }
  </style>
</head>
<body>

<div class="header">
  <h2>LAPORAN PRESENSI <?= strtoupper($jenis) ?></h2>
  <h4>PERIODE: <?= date('d M Y', strtotime($tgl_awal)) ?> s/d <?= date('d M Y', strtotime($tgl_akhir)) ?></h4>
</div>

<div class="info">
  <strong>Nama:</strong> <?= htmlspecialchars($user['nama_lengkap']) ?><br>
  <strong><?= $jenis === 'siswa' ? 'Kelas' : 'Jabatan' ?>:</strong> <?= htmlspecialchars($user['info']) ?>
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

<div class="no-print">
  <button onclick="window.print()">🖨️ Cetak Laporan</button>
  <a href="data-presensi.php?tgl_awal=<?= $tgl_awal ?>&tgl_akhir=<?= $tgl_akhir ?>&jenis=<?= $jenis ?>" style="margin-left: 15px;">← Kembali</a>
</div>

</body>
</html>