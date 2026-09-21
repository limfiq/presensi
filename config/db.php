<?php
$host = "localhost";
$user = "root"; // sesuaikan dengan database Anda
$pass = "";
$db   = "absensi_sekolah";

$conn = new mysqli($host, $user, $pass, $db);

// Cek koneksi
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}
?>
