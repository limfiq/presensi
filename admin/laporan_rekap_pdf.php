<?php
require_once 'config.php';
include 'auth.php';
require_once '../vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

// Ambil filter
$tgl_awal = $_GET['tgl_awal'] ?? date('Y-m-01');
$tgl_akhir = $_GET['tgl_akhir'] ?? date('Y-m-t');
$jenis = $_GET['jenis'] ?? 'semua';
$info = $_GET['info'] ?? '';

$wherePresensi = "WHERE p.tanggal BETWEEN ? AND ?";
$whereKetidakhadiran = "WHERE k.tanggal BETWEEN ? AND ?";
$params = [$tgl_awal, $tgl_akhir];
$types = "ss";

if ($jenis === 'siswa' || $jenis === 'guru') {
  $wherePresensi .= " AND p.jenis_pengguna = ?";
  $whereKetidakhadiran .= " AND k.jenis_pengguna = ?";
  $params[] = $jenis;
  $types .= "s";
}

if ($jenis === 'siswa' && !empty($info)) {
  $wherePresensi .= " AND s.tingkat_rombel = ?";
  $whereKetidakhadiran .= " AND s.tingkat_rombel = ?";
  $params[] = $info;
  $types .= "s";
}

if ($jenis === 'guru' && !empty($info)) {
  $wherePresensi .= " AND g.jabatan = ?";
  $whereKetidakhadiran .= " AND g.jabatan = ?";
  $params[] = $info;
  $types .= "s";
}

// Query utama gabungan presensi & ketidakhadiran
$sql = "
SELECT 
  COALESCE(p.jenis_pengguna, k.jenis_pengguna) AS jenis_pengguna,
  COALESCE(s.nama_lengkap, g.nama_lengkap) AS nama,
  COALESCE(s.tingkat_rombel, g.jabatan) AS info,
  COALESCE(p.id_siswa, p.id_guru, k.id_siswa, k.id_guru) AS pengguna_id,

  COUNT(DISTINCT p.id) AS hadir,
  SUM(CASE WHEN p.status_masuk='Tepat Waktu' THEN 1 ELSE 0 END) AS tepat_waktu,
  SUM(CASE WHEN p.status_masuk='Terlambat' THEN 1 ELSE 0 END) AS terlambat,
  SUM(CASE WHEN p.status_pulang='Pulang Cepat' THEN 1 ELSE 0 END) AS pulang_cepat,

  SUM(CASE WHEN k.keterangan='ijin' THEN 1 ELSE 0 END) AS ijin,
  SUM(CASE WHEN k.keterangan='sakit' THEN 1 ELSE 0 END) AS sakit,
  SUM(CASE WHEN k.keterangan='alpa' THEN 1 ELSE 0 END) AS alpa,
  SUM(CASE WHEN k.keterangan='cuti' THEN 1 ELSE 0 END) AS cuti

FROM 
  (SELECT * FROM presensi $wherePresensi) p
  LEFT JOIN siswa s ON p.id_siswa = s.id
  LEFT JOIN guru g ON p.id_guru = g.id
  FULL OUTER JOIN (SELECT * FROM ketidakhadiran $whereKetidakhadiran) k 
    ON k.id_siswa = p.id_siswa OR k.id_guru = p.id_guru

GROUP BY jenis_pengguna, pengguna_id
ORDER BY nama ASC
";

// Karena MySQL tidak support FULL OUTER JOIN, kita pecah menjadi dua query lalu digabung secara manual (di bawah ini)

// QUERY 1: presensi
$sql1 = "
SELECT 
  p.jenis_pengguna,
  IF(p.jenis_pengguna='siswa', s.nama_lengkap, g.nama_lengkap) AS nama,
  IF(p.jenis_pengguna='siswa', s.tingkat_rombel, g.jabatan) AS info,
  IFNULL(p.id_siswa, p.id_guru) AS pengguna_id,
  COUNT(*) AS hadir,
  SUM(CASE WHEN p.status_masuk='Tepat Waktu' THEN 1 ELSE 0 END) AS tepat_waktu,
  SUM(CASE WHEN p.status_masuk='Terlambat' THEN 1 ELSE 0 END) AS terlambat,
  SUM(CASE WHEN p.status_pulang='Pulang Cepat' THEN 1 ELSE 0 END) AS pulang_cepat
FROM presensi p
LEFT JOIN siswa s ON p.id_siswa = s.id
LEFT JOIN guru g ON p.id_guru = g.id
$wherePresensi
GROUP BY p.jenis_pengguna, pengguna_id
";

$stmt1 = $conn->prepare($sql1);
$stmt1->bind_param($types, ...$params);
$stmt1->execute();
$result1 = $stmt1->get_result();

// Simpan presensi ke array
$data = [];
while ($row = $result1->fetch_assoc()) {
  $key = $row['jenis_pengguna'] . '_' . $row['pengguna_id'];
  $data[$key] = $row + ['ijin' => 0, 'sakit' => 0, 'alpa' => 0, 'cuti' => 0];
}

// QUERY 2: ketidakhadiran
$sql2 = "
SELECT 
  k.jenis_pengguna,
  IF(k.jenis_pengguna='siswa', s.nama_lengkap, g.nama_lengkap) AS nama,
  IF(k.jenis_pengguna='siswa', s.tingkat_rombel, g.jabatan) AS info,
  IFNULL(k.id_siswa, k.id_guru) AS pengguna_id,
  k.keterangan
FROM ketidakhadiran k
LEFT JOIN siswa s ON k.id_siswa = s.id
LEFT JOIN guru g ON k.id_guru = g.id
$whereKetidakhadiran
";

$stmt2 = $conn->prepare($sql2);
$stmt2->bind_param($types, ...$params);
$stmt2->execute();
$result2 = $stmt2->get_result();

// Gabungkan ke array
while ($row = $result2->fetch_assoc()) {
  $key = $row['jenis_pengguna'] . '_' . $row['pengguna_id'];
  if (!isset($data[$key])) {
    $data[$key] = [
      'jenis_pengguna' => $row['jenis_pengguna'],
      'nama' => $row['nama'],
      'info' => $row['info'],
      'pengguna_id' => $row['pengguna_id'],
      'hadir' => 0,
      'tepat_waktu' => 0,
      'terlambat' => 0,
      'pulang_cepat' => 0,
      'ijin' => 0,
      'sakit' => 0,
      'alpa' => 0,
      'cuti' => 0
    ];
  }
  $data[$key][$row['keterangan']]++;
}

// Generate HTML
$html = '<h3 style="text-align:center;">Laporan Rekap Presensi</h3>';
$html .= '<p>Periode: ' . $tgl_awal . ' s/d ' . $tgl_akhir . '</p>';
if ($jenis !== 'semua') $html .= '<p>Jenis Pengguna: ' . ucfirst($jenis) . '</p>';
if (!empty($info)) $html .= '<p>' . ($jenis === 'siswa' ? 'Kelas' : 'Jabatan') . ': ' . $info . '</p>';

$html .= '<table border="1" cellpadding="5" cellspacing="0" width="100%">
<thead>
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
  </tr>
</thead>
<tbody>';

$no = 1;
foreach ($data as $row) {
  $html .= '<tr>
    <td>' . ($no++) . '</td>
    <td>' . $row['nama'] . '</td>
    <td>' . ucfirst($row['jenis_pengguna']) . '</td>
    <td>' . $row['info'] . '</td>
    <td>' . $row['hadir'] . '</td>
    <td>' . $row['tepat_waktu'] . '</td>
    <td>' . $row['terlambat'] . '</td>
    <td>' . $row['pulang_cepat'] . '</td>
    <td>' . $row['ijin'] . '</td>
    <td>' . $row['sakit'] . '</td>
    <td>' . $row['alpa'] . '</td>
    <td>' . $row['cuti'] . '</td>
  </tr>';
}

$html .= '</tbody></table>';

// Dompdf render
$options = new Options();
$options->set('isRemoteEnabled', true);
$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'landscape');
$dompdf->render();
$dompdf->stream("rekap_presensi.pdf", array("Attachment" => false));
