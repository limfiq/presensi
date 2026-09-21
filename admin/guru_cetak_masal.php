<?php
include 'auth.php';
include 'config.php';
include 'lib/phpqrcode/qrlib.php';

// Ambil daftar jabatan unik
$jabatanList = mysqli_query($conn, "SELECT DISTINCT jabatan FROM guru WHERE jabatan IS NOT NULL AND jabatan != '' ORDER BY jabatan ASC");

// Filter berdasarkan jabatan
$filter_jabatan = isset($_GET['jabatan']) ? $_GET['jabatan'] : '';
$whereClause = $filter_jabatan ? "WHERE jabatan = '" . mysqli_real_escape_string($conn, $filter_jabatan) . "'" : '';
$guru = mysqli_query($conn, "SELECT * FROM guru $whereClause ORDER BY nama_lengkap ASC");

// $qrPath = "../temp_qr/";
// if (!is_dir($qrPath)) mkdir($qrPath);
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <title>Cetak Massal Kartu Guru Berdasarkan Jabatan</title>
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

    .no-print {
      text-align: center;
      margin: 20px;
    }

    select, button, a {
      padding: 6px 12px;
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
  </style>
</head>
<body>

<div class="no-print">
  <form method="get" style="display: inline;">
    <label for="jabatan">Filter Jabatan:</label>
    <select name="jabatan" onchange="this.form.submit()">
      <option value="">-- Semua Jabatan --</option>
      <?php while ($row = mysqli_fetch_assoc($jabatanList)): ?>
        <option value="<?= htmlspecialchars($row['jabatan']) ?>" <?= $filter_jabatan == $row['jabatan'] ? 'selected' : '' ?>>
          <?= htmlspecialchars($row['jabatan']) ?>
        </option>
      <?php endwhile; ?>
    </select>
  </form>
  <button onclick="window.print()">🖨️ Cetak Sekarang</button>
  <a href="data-guru.php"><i class="fas fa-arrow-left"></i> Kembali</a>
</div>

<div class="grid">
<?php while ($g = mysqli_fetch_assoc($guru)): ?>
  <?php
    // $qrFile = $qrPath . '/' . $g['id'] . '.png';
    // QRcode::png($g['qrcode'], $qrFile, QR_ECLEVEL_H, 6);
  ?>
  <div class="card-wrapper">
    <div class="header">
      <img src="../assets/img/logo.png" class="logo" alt="Logo">
      <div class="school-name">MI DARUL ULUM GROGOL BANYUWANGI</div>
    </div>
    <div class="content">
      <div class="left">
        <img src="uploads/<?= $g['foto'] ?>" class="foto" alt="Foto">
        <div class="data">
          <p><strong>Kode:</strong> <?= $g['qrcode'] ?></p>
          <p><strong>Nama:</strong> <?= $g['nama_lengkap'] ?></p>
          <p><strong>Jabatan:</strong> <?= $g['jabatan'] ?></p>
          <!-- <p><strong>HP:</strong> <?= $g['no_hp'] ?></p> -->
        </div>
      </div>
      <div class="right">
        <!-- <img src="<?= $qrFile ?>" class="qr" alt="QR Code"> -->
         <img src="qrcodes/<?= strtolower($g['qrcode']) ?>.png" class="qr btn-warning" alt="QR Code">
      </div>
    </div>
    <!-- <div class="footer">
      Dicetak: <?= date('d/m/Y') ?>
    </div> -->
  </div>
<?php endwhile; ?>
</div>

</body>
</html>
