<?php
session_start();
include 'config.php';

// Aktifkan error reporting untuk debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Set header JSON
header('Content-Type: application/json');

// Periksa metode dan data
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['ids']) || !is_array($_POST['ids'])) {
  echo json_encode(['error' => 'Data tidak valid atau metode tidak diizinkan']);
  exit;
}

$ids = array_map('intval', $_POST['ids']);
if (empty($ids)) {
  echo json_encode(['error' => 'Tidak ada data yang dipilih']);
  exit;
}

try {
  // Mulai transaksi
  $conn->begin_transaction();

  // Hapus data presensi
  $placeholders = implode(',', array_fill(0, count($ids), '?'));
  $stmt = $conn->prepare("DELETE FROM presensi WHERE id IN ($placeholders)");
  if (!$stmt) {
    throw new Exception('Gagal menyiapkan query: ' . $conn->error);
  }
  $stmt->bind_param(str_repeat('i', count($ids)), ...$ids);
  $stmt->execute();

  // Catat log aktivitas (jika tabel ada)
  if ($conn->query("SHOW TABLES LIKE 'log_aktivitas'")->num_rows > 0) {
    $aktivitas = "Menghapus " . count($ids) . " data presensi: " . implode(',', $ids);
    $id_user = $_SESSION['user_id'] ?? 1; // Ganti dengan ID user dari session
    $stmt_log = $conn->prepare("INSERT INTO log_aktivitas (aktivitas, id_user, created_at) VALUES (?, ?, NOW())");
    if (!$stmt_log) {
      throw new Exception('Gagal menyiapkan log query: ' . $conn->error);
    }
    $stmt_log->bind_param("si", $aktivitas, $id_user);
    $stmt_log->execute();
    $stmt_log->close();
  }

  // Commit transaksi
  $conn->commit();
  echo json_encode(['success' => 'Data berhasil dihapus']);
} catch (Exception $e) {
  // Rollback transaksi jika gagal
  $conn->rollback();
  echo json_encode(['error' => 'Gagal menghapus data: ' . $e->getMessage()]);
} finally {
  if (isset($stmt)) $stmt->close();
  $conn->close();
}
?>