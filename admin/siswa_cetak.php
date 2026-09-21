<?php
include 'auth.php';
include 'config.php';
include 'lib/phpqrcode/qrlib.php';

$id = $_GET['id'];
$data = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM siswa WHERE id=$id"));
$qrcode = $data['qrcode'];
// $qrPath = "../temp_qr/";
// if (!is_dir($qrPath)) mkdir($qrPath);
// $qrFile = $qrPath . $data['nisn'] . ".png";
// QRcode::png($data['qrcode'], $qrFile, QR_ECLEVEL_H, 6);
?>
<!DOCTYPE html>
<html>
<head>
  <title>Cetak Kartu Siswa</title>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
  <style>
    @media print {
      .no-print { display: none; }
      body { margin: 0; }
    }

    body {
      font-family: 'Segoe UI', sans-serif;
      background: #f8f9fa;
    }

    .card-container {
      width: 86mm;
      height: 54mm;
      background: white;
      border: 1px solid #ccc;
      margin: 40px auto;
      padding: 10px;
      box-sizing: border-box;
      position: relative;
      border-radius: 6px;
      /* background-image: linear-gradient(to bottom right, #007b8a, #00a7b7); */
      color: black;
    }

    .header {
      display: flex;
      align-items: center;
      border-bottom: 1px solid rgba(255,255,255,0.3);
      padding-bottom: 4px;
      margin-bottom: 4px;
    }

    .logo {
      width: 36px;
      height: 36px;
      margin-right: 8px;
    }

    .school-name {
      font-size: 13px;
      font-weight: bold;
      text-transform: uppercase;
      line-height: 1.2;
    }

    .content {
      display: flex;
      justify-content: space-between;
      height: 75%;
    }

    .left {
      width: 58%;
      display: flex;
      flex-direction: column;
      justify-content: center;
    }

    .foto {
      width: 58px;
      height: 70px;
      object-fit: cover;
      border: 2px solid #fff;
      border-radius: 4px;
      margin-bottom: 6px;
    }

    .data {
      font-size: 11px;
    }

    .data p {
      margin: 2px 0;
    }

    .right {
      width: 42%;
      display: flex;
      justify-content: center;
      align-items: center;
    }

    .qr {
      width: 145px;
      height: 145px;
    }

    .footer {
      position: absolute;
      bottom: 4px;
      left: 6px;
      font-size: 9px;
      color: rgba(255,255,255,0.8);
    }

    .no-print {
      text-align: center;
      margin-top: 20px;
    }

    button, a {
      padding: 6px 14px;
      font-size: 14px;
      border-radius: 4px;
      margin: 5px;
      text-decoration: none;
    }

    button {
            background-color: #28a745;
            color: white;
            border: none;
        }

        a {
            background: #ffc107;
            color: black;
        }
  </style>
</head>
<body>

<div class="card-container">
  <!-- HEADER SEKOLAH -->
  <div class="header">
    <img src="../assets/img/logo.png" class="logo" alt="Logo">
    <div class="school-name">MI DARUL ULUM GROGOL BANYUWANGI</div>
  </div>

  <!-- ISI KARTU -->
<div class="content">
  <div class="left">
    <img src="uploads/<?= $data['foto'] ?>" class="foto" alt="Foto">
    <div class="data">
      <p><strong>Kode:</strong> <?= $data['qrcode'] ?></p>
      <p><strong>Nama:</strong> <?= ucwords($data['nama_lengkap']) ?></p>
      <p><strong>Kelas:</strong> <?= $data['tingkat_rombel'] ?></p>
    </div>
  </div>
  <div class="right" style="text-align:center;">
    <img src="qrcodes/<?= strtolower($data['qrcode']) ?>.png" class="qr" alt="QR Code"><br>
  </div>
  
</div>


  <!-- FOOTER -->
  <!-- <div class="footer">https://midugrogol.sch.id</div> -->
</div>

<!-- TOMBOL -->
<div class="no-print">
  <button onclick="window.print()">🖨️ Cetak</button>
  <a href="data-siswa.php"><i class="fas fa-arrow-left"></i> Kembali</a>
</div>

</body>
</html>
