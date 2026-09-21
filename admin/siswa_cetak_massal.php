<?php
include 'auth.php';
include 'config.php';
include 'lib/phpqrcode/qrlib.php';

// Ambil semua kelas unik
$kelas_result = mysqli_query($conn, "SELECT DISTINCT tingkat_rombel FROM siswa ORDER BY tingkat_rombel ASC");

$kelas = isset($_GET['kelas']) ? $_GET['kelas'] : '';
$where = $kelas ? "WHERE tingkat_rombel = '$kelas'" : '';
$siswa = mysqli_query($conn, "SELECT * FROM siswa $where ORDER BY nama_lengkap ASC");

// $qrPath = "../temp_qr/";
// if (!is_dir($qrPath)) mkdir($qrPath);
?>
<!DOCTYPE html>
<html>
<head>
  <title>Cetak Kartu QR Massal</title>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
  <style>
    @media print {
      .no-print { display: none; }
      body { margin: 0; }
      .card-wrapper {
        page-break-inside: avoid;
      }
    }

    body {
      font-family: 'Segoe UI', sans-serif;
      background: #f8f9fa;
    }

    .grid {
      display: flex;
      flex-wrap: wrap;
      gap: 10px;
      justify-content: center;
      padding: 20px;
    }

    .card-wrapper {
      width: 86mm;
      height: 54mm;
      /* background-image: linear-gradient(to bottom right, #007b8a, #00a7b7); */
      color: black;
      border-radius: 6px;
      border: 1px solid #ccc;
      padding: 10px;
      box-sizing: border-box;
      position: relative;
      display: flex;
      flex-direction: column;
    }

    .header {
      display: flex;
      align-items: center;
      border-bottom: 1px solid rgba(255,255,255,0.3);
      padding-bottom: 4px;
      margin-bottom: 4px;
    }

    .logo {
      width: 34px;
      height: 34px;
      margin-right: 8px;
    }

    .school-name {
      font-size: 13px;
      font-weight: bold;
      text-transform: uppercase;
    }

    .content {
      display: flex;
      justify-content: space-between;
      height: 100%;
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
      margin: 20px;
    }

    button, a, select {
      padding: 6px 14px;
      font-size: 14px;
      border-radius: 4px;
      margin: 5px;
      text-decoration: none;
    }

    button {
      background-color: #007b8a;
      color: white;
      border: none;
      cursor: pointer;
    }

    a {
      background: #aaa;
      color: white;
    }

    select {
      background: white;
      border: 1px solid #ccc;
    }

    form {
      display: inline-block;
    }
  </style>
</head>
<body>

<div class="no-print">
  <form method="get" action="">
    <label for="kelas">Filter Kelas:</label>
    <select name="kelas" onchange="this.form.submit()">
      <option value="">Semua Kelas</option>
      <?php while ($row = mysqli_fetch_assoc($kelas_result)): ?>
        <option value="<?= $row['tingkat_rombel'] ?>" <?= ($row['tingkat_rombel'] == $kelas) ? 'selected' : '' ?>>
          <?= $row['tingkat_rombel'] ?>
        </option>
      <?php endwhile; ?>
    </select>
  </form>
  <button onclick="window.print()">🖨️ Cetak Kartu <?= $kelas ? 'Kelas ' . $kelas : 'Semua' ?></button>
  <a href="data-siswa.php"><i class="fas fa-arrow-left"></i> Kembali</a>
</div>

<div class="grid">
  <?php while($row = mysqli_fetch_assoc($siswa)): 
    // $qrFile = $qrPath . $row['nisn'] . ".png";
    // QRcode::png($row['qrcode'], $qrFile, QR_ECLEVEL_H, 6);
  ?>
  <div class="card-wrapper">
    <div class="header">
      <img src="../assets/img/logo.png" class="logo" alt="Logo">
      <div class="school-name">MI DARUL ULUM GROGOL BANYUWANGI</div>
    </div>
    <div class="content">
      <div class="left">
        <img src="uploads/<?= $row['foto'] ?>" class="foto" alt="Foto">
        <div class="data">
          <p><strong>Kode:</strong> <?= $row['qrcode'] ?></p>
          <p><strong>Nama:</strong> <?= ucwords($row['nama_lengkap']) ?></p>
          <p><strong>Kelas:</strong> <?= $row['tingkat_rombel'] ?></p>
        </div>
      </div>
      <div class="right">
        <img src="qrcodes/<?= strtolower($row['qrcode']) ?>.png" class="qr" alt="QR Code">
      </div>
    </div>
    <!-- <div class="footer">midugrogol.sch.id</div> -->
  </div>
  <?php endwhile; ?>
</div>

</body>
</html>
