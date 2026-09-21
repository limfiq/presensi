<?php
include 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
  $id = intval($_POST['id']);
  $stmt = $conn->prepare("SELECT * FROM guru WHERE id = ?");
  $stmt->bind_param("i", $id);
  $stmt->execute();
  $result = $stmt->get_result();
  
  if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    
    // Format tanggal_lahir ke DD MMMM YYYY
    $tanggal_lahir = $row['tanggal_lahir'] ? date('d F Y', strtotime($row['tanggal_lahir'])) : '-';
    $tanggal_lahir = str_replace(
      ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'],
      ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'],
      $tanggal_lahir
    );
    
    // Format created_at ke DD MMMM YYYY HH:mm:ss
    $created_at = $row['created_at'] ? date('d F Y H:i:s', strtotime($row['created_at'])) : '-';
    $created_at = str_replace(
      ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'],
      ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'],
      $created_at
    );
    
    // Format jenis_kelamin
    $jenis_kelamin = $row['jenis_kelamin'] == 'L' ? 'Laki-laki' : ($row['jenis_kelamin'] == 'P' ? 'Perempuan' : '-');
    
    // Kembalikan data sebagai JSON
    echo json_encode([
      'nip' => $row['nip'],
      'nama_lengkap' => $row['nama_lengkap'],
      'nik' => $row['nik'],
      'tempat_lahir' => $row['tempat_lahir'],
      'tanggal_lahir' => $tanggal_lahir,
      'jabatan' => $row['jabatan'],
      'jenis_kelamin' => $jenis_kelamin,
      'alamat' => $row['alamat'],
      'no_hp' => $row['no_hp'],
      'email' => $row['email'],
      'foto' => $row['foto'],
      'qrcode' => $row['qrcode'],
      'created_at' => $created_at
    ]);
  } else {
    echo json_encode(['error' => 'Data guru tidak ditemukan.']);
  }
  
  $stmt->close();
} else {
  echo json_encode(['error' => 'Permintaan tidak valid.']);
}
$conn->close();
?>