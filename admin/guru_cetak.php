
<?php
session_start();
include 'auth.php';
include 'config.php';
include 'lib/phpqrcode/qrlib.php';

if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['error'] = 'ID guru tidak valid!';
    header('Location: data-guru.php');
    exit;
}

$id = $_GET['id'];
$data = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM guru WHERE id='$id'"));

if (!$data) {
    $_SESSION['error'] = 'Data guru tidak ditemukan!';
    header('Location: data-guru.php');
    exit;
}

// $qrPath = "temp_qr/";
// if (!is_dir($qrPath)) {
//     mkdir($qrPath, 0755, true);
// }
// $qrFile = $qrPath . 'qr_guru_' . $data['id'] . '.png';
// QRcode::png($data['qrcode'], $qrFile, QR_ECLEVEL_H, 6);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cetak Kartu Guru - Sistem Presensi MIDU</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        @media print {
            .no-print { display: none; }
            body { margin: 0; }
        }

        body {
            font-family: 'Segoe UI', sans-serif;
            background: #f1f5f9;
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
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            color: black;
        }

        .header {
            display: flex;
            align-items: center;
            border-bottom: 1px solid rgba(0,0,0,0.3);
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
    <?php if (isset($_SESSION['error'])): ?>
    <script>
        Swal.fire({
            icon: 'error',
            title: 'Gagal!',
            text: <?php echo json_encode($_SESSION['error']); ?>,
            confirmButtonColor: '#dc3545'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location = 'data-guru.php';
            }
        });
    </script>
    <?php unset($_SESSION['error']); endif; ?>

    <div class="card-container">
        <div class="header">
            <img src="../assets/img/logo.png" class="logo" alt="Logo">
            <div class="school-name">MI DARUL ULUM GROGOL BANYUWANGI</div>
        </div>
        <div class="content">
            <div class="left">
                <img src="uploads/<?php echo htmlspecialchars($data['foto'] != 'default.jpg' ? strtolower($data['foto']) : 'default.jpg'); ?>" class="foto" alt="Foto Guru">
                <div class="data">
                    <p><strong>Kode:</strong> <?php echo htmlspecialchars($data['qrcode']); ?></p>
                    <p><strong>Nama:</strong> <?php echo htmlspecialchars($data['nama_lengkap']); ?></p>
                    <p><strong>Jabatan:</strong> <?php echo htmlspecialchars($data['jabatan']); ?></p>
                </div>
            </div>
            <div class="right">
                <img src="qrcodes/<?= strtolower($data['qrcode']) ?>.png" class="qr" alt="QR Code">
            </div>
        </div>
    </div>

    <div class="no-print">
        <button onclick="window.print()"><i class="fas fa-print"></i> Cetak</button>
        <a href="data-guru.php"><i class="fas fa-arrow-left"></i> Kembali</a>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
