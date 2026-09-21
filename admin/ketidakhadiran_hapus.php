<?php
session_start();
require_once 'config.php';
include 'auth.php';

$id = $_GET['id'] ?? null;
if ($id) {
  $stmt = $conn->prepare("DELETE FROM ketidakhadiran WHERE id = ?");
  $stmt->bind_param("i", $id);
  $stmt->execute();
}

header("Location: data-ketidakhadiran.php");
exit;
