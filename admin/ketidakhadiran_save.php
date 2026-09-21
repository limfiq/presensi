<?php
session_start();
include 'config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['data'])) {
  echo json_encode(['error' => 'Data tidak valid atau metode tidak diizinkan']);
  exit;
}

$data = json_decode($_POST['data'], true);
if (!$data || !is_array($data)) {
  echo json_encode(['error' => 'Format data tidak valid']);
  exit;
}

// Valid values for keterangan (must match database ENUM or VARCHAR constraints)
$valid_keterangan = ['izin', 'sakit', 'alpa', 'cuti'];

try {
  $conn->begin_transaction();
  $stmt = $conn->prepare("INSERT INTO ketidakhadiran (id_siswa, id_guru, tanggal, keterangan, alasan, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
  
  foreach ($data as $entry) {
    $id_siswa = $entry['jenis_pengguna'] === 'siswa' ? $entry['id'] : null;
    $id_guru = $entry['jenis_pengguna'] === 'guru' ? $entry['id'] : null;
    $tanggal = $entry['tanggal'];
    $keterangan = trim($entry['keterangan'] ?? '');
    $alasan = isset($entry['alasan']) ? trim($entry['alasan']) : null;

    // Validate keterangan
    if (!in_array($keterangan, $valid_keterangan)) {
      throw new Exception("Keterangan tidak valid untuk ID {$entry['id']}: '$keterangan'. Harus salah satu dari: " . implode(', ', $valid_keterangan));
    }

    // Validate tanggal format
    if (!DateTime::createFromFormat('Y-m-d', $tanggal)) {
      throw new Exception("Format tanggal tidak valid untuk ID {$entry['id']}: '$tanggal'");
    }

    $stmt->bind_param('iisss', $id_siswa, $id_guru, $tanggal, $keterangan, $alasan);
    $stmt->execute();
  }
  
  $conn->commit();
  echo json_encode(['success' => 'Ketidakhadiran berhasil dicatat']);
} catch (Exception $e) {
  $conn->rollback();
  echo json_encode(['error' => 'Gagal mencatat ketidakhadiran: ' . $e->getMessage()]);
} finally {
  if (isset($stmt)) $stmt->close();
  $conn->close();
}
?>