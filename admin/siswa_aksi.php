<?php
include 'config.php';

// Fungsi upload
function uploadFoto($file) {
  $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
  $namaBaru = uniqid() . '.' . $ext;
  move_uploaded_file($file['tmp_name'], "../uploads/" . $namaBaru);
  return $namaBaru;
}

// ========== TAMBAH ========== //
if (isset($_POST['tambah'])) {
  $nisn           = $_POST['nisn'];
  $nama_lengkap   = $_POST['nama_lengkap'];
  $nik            = $_POST['nik'];
  $tempat_lahir   = $_POST['tempat_lahir'];
  $tanggal_lahir  = $_POST['tanggal_lahir'];
  $tingkat_rombel = $_POST['tingkat_rombel'];
  $umur           = $_POST['umur'];
  $status         = $_POST['status'];
  $jenis_kelamin  = $_POST['jenis_kelamin'];
  $alamat         = $_POST['alamat'];
  $nama_ayah      = $_POST['nama_ayah'];
  $nama_ibu       = $_POST['nama_ibu'];
  // $qrcode         = $_POST['qrcode'];

  // Buat QR otomatis

$inisial = strtoupper(implode('', array_map(fn($x) => $x[0], explode(' ', $nama_lengkap))));

do {
  $acak = rand(1000, 9999);
  $qrcode = $inisial . $acak;
  $cek = $conn->query("SELECT id FROM siswa WHERE qrcode = '$qrcode'");
} while ($cek->num_rows > 0);



  $foto = 'default.jpg';
  if (!empty($_FILES['foto']['name'])) {
    $foto = uploadFoto($_FILES['foto']);
  }

  $query = "INSERT INTO siswa 
    (nisn, nama_lengkap, nik, tempat_lahir, tanggal_lahir, tingkat_rombel, umur, status, jenis_kelamin, alamat, nama_ayah, nama_ibu, foto, qrcode) 
    VALUES 
    ('$nisn', '$nama_lengkap', '$nik', '$tempat_lahir', '$tanggal_lahir', '$tingkat_rombel', '$umur', '$status', '$jenis_kelamin', '$alamat', '$nama_ayah', '$nama_ibu', '$foto', '$qrcode')";

  mysqli_query($conn, $query);

  $_SESSION['pesan'] = "Data siswa berhasil ditambahkan.";
  header("Location: siswa.php");
  exit;
}

// ========== EDIT ========== //
if (isset($_POST['edit'])) {
  $id             = $_POST['id'];
  $nisn           = $_POST['nisn'];
  $nama_lengkap   = $_POST['nama_lengkap'];
  $nik            = $_POST['nik'];
  $tempat_lahir   = $_POST['tempat_lahir'];
  $tanggal_lahir  = $_POST['tanggal_lahir'];
  $tingkat_rombel = $_POST['tingkat_rombel'];
  $umur           = $_POST['umur'];
  $status         = $_POST['status'];
  $jenis_kelamin  = $_POST['jenis_kelamin'];
  $alamat         = $_POST['alamat'];
  $nama_ayah      = $_POST['nama_ayah'];
  $nama_ibu       = $_POST['nama_ibu'];
  // $qrcode         = $_POST['qrcode'];

  $q = "UPDATE siswa SET 
        nisn='$nisn', 
        nama_lengkap='$nama_lengkap', 
        nik='$nik', 
        tempat_lahir='$tempat_lahir', 
        tanggal_lahir='$tanggal_lahir',
        tingkat_rombel='$tingkat_rombel', 
        umur='$umur', 
        status='$status', 
        jenis_kelamin='$jenis_kelamin',
        alamat='$alamat', 
        nama_ayah='$nama_ayah', 
        nama_ibu='$nama_ibu', 
        ";

  if (!empty($_FILES['foto']['name'])) {
    $foto = uploadFoto($_FILES['foto']);
    $q .= ", foto='$foto'";
  }

  $q .= " WHERE id=$id";
  mysqli_query($conn, $q);

  $_SESSION['pesan'] = "Data siswa berhasil diupdate.";
  header("Location: siswa.php");
  exit;
}

// ========== HAPUS ========== //
if (isset($_GET['hapus']) && isset($_GET['id'])) {
  $id = $_GET['id'];
  mysqli_query($conn, "DELETE FROM siswa WHERE id=$id");
  $_SESSION['pesan'] = "Data siswa berhasil dihapus.";
  header("Location: siswa.php");
  exit;
}
