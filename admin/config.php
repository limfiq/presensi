<?php
$host = "localhost";
$user = "root";
$pass = "";
$db   = "absensi_sekolah";

$conn = mysqli_connect($host, $user, $pass, $db) or die("Koneksi gagal.");
if (session_status() == PHP_SESSION_NONE) {
  session_start();
}

?>
