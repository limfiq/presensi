<?php
session_start();
include 'auth.php';
include 'config.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
  $_SESSION['error'] = 'ID presensi tidak valid.';
  header('Location: presensi.php');
  exit;
}

$id = intval($_GET['id']);
$stmt = $conn->prepare("
  SELECT p.*, 
         IF(p.jenis_pengguna='siswa', s.nama_lengkap, g.nama_lengkap) AS nama
  FROM presensi p
  LEFT JOIN siswa s ON p.id_siswa = s.id
  LEFT JOIN guru g ON p.id_guru = g.id
  WHERE p.id = ?
");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
  $_SESSION['error'] = 'Data presensi tidak ditemukan.';
  header('Location: presensi.php');
  exit;
}

$row = $result->fetch_assoc();
$nama = $row['nama'] ?? 'tidak diketahui';
$stmt->close();

$stmt = $conn->prepare("DELETE FROM presensi WHERE id = ?");
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
  $_SESSION['success'] = 'Data presensi untuk ' . htmlspecialchars($nama) . ' berhasil dihapus.';
} else {
  $_SESSION['error'] = 'Gagal menghapus data presensi.';
}

$stmt->close();
$conn->close();
header('Location: presensi.php');
exit;
?>