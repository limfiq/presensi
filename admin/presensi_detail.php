<?php
include 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
  $id = intval($_POST['id']);
  $stmt = $conn->prepare("
    SELECT p.*, 
           IF(p.jenis_pengguna='siswa', s.nama_lengkap, g.nama_lengkap) AS nama,
           IF(p.jenis_pengguna='siswa', s.tingkat_rombel, g.jabatan) AS info
    FROM presensi p
    LEFT JOIN siswa s ON p.id_siswa = s.id
    LEFT JOIN guru g ON p.id_guru = g.id
    WHERE p.id = ?
  ");
  $stmt->bind_param("i", $id);
  $stmt->execute();
  $result = $stmt->get_result();

  if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();

    // Format tanggal ke DD MMMM YYYY
    $tanggal = $row['tanggal'] ? date('d F Y', strtotime($row['tanggal'])) : '-';
    $tanggal = str_replace(
      ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'],
      ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'],
      $tanggal
    );

    // Format created_at ke DD MMMM YYYY HH:mm:ss
    $created_at = $row['created_at'] ? date('d F Y H:i:s', strtotime($row['created_at'])) : '-';
    $created_at = str_replace(
      ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'],
      ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'],
      $created_at
    );

    // Format jam_masuk dan jam_pulang
    $jam_masuk = $row['jam_masuk'] ? date('H:i', strtotime($row['jam_masuk'])) : '-';
    $jam_pulang = $row['jam_pulang'] ? date('H:i', strtotime($row['jam_pulang'])) : '-';

    // Kembalikan data sebagai JSON
    echo json_encode([
      'id' => $row['id'],
      'nama' => $row['nama'] ?? '-',
      'jenis_pengguna' => ucfirst($row['jenis_pengguna']),
      'info' => $row['info'] ?? '-',
      'tanggal' => $tanggal,
      'jam_masuk' => $jam_masuk,
      'status_masuk' => $row['status_masuk'] ?? '-',
      'jam_pulang' => $jam_pulang,
      'status_pulang' => $row['status_pulang'] ?? '-',
      'lokasi' => $row['lokasi'] ?? '-',
      'alamat' => $row['alamat'] ?? '-',
      'created_at' => $created_at
    ]);
  } else {
    echo json_encode(['error' => 'Data presensi tidak ditemukan.']);
  }

  $stmt->close();
} else {
  echo json_encode(['error' => 'Permintaan tidak valid.']);
}
$conn->close();
?>