<?php
session_start();
$activePage = 'data-siswa';
include 'auth.php';
include 'config.php';
include 'lib/phpqrcode/qrlib.php';

$nisn = $nama_lengkap = $nik = $tempat_lahir = $tanggal_lahir = $tingkat_rombel = $umur = $status = $jenis_kelamin = $alamat = $nama_ayah = $nama_ibu = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nisn = $_POST['nisn'] ?? '';
    $nama_lengkap = $_POST['nama_lengkap'] ?? '';
    $nik = $_POST['nik'] ?? '';
    $tempat_lahir = $_POST['tempat_lahir'] ?? '';
    $tanggal_lahir = $_POST['tanggal_lahir'] ?? '';
    $tingkat_rombel = $_POST['tingkat_rombel'] ?? '';
    $umur = $_POST['umur'] ?? '';
    $status = $_POST['status'] ?? '';
    $jenis_kelamin = $_POST['jenis_kelamin'] ?? '';
    $alamat = $_POST['alamat'] ?? '';
    $nama_ayah = $_POST['nama_ayah'] ?? '';
    $nama_ibu = $_POST['nama_ibu'] ?? '';
    $foto = 'default.jpg';

    $cek = mysqli_query($conn, "SELECT * FROM siswa WHERE nisn='$nisn'");
    if (mysqli_num_rows($cek) > 0) {
        $error = "NISN sudah digunakan!";
    }

    if (!isset($error) && !empty($_FILES['foto']['name'])) {
        $allowed = ['jpg', 'jpeg', 'png'];
        $fileName = $_FILES['foto']['name'];
        $fileSize = $_FILES['foto']['size'];
        $tmpName  = $_FILES['foto']['tmp_name'];
        $ext      = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        if (!in_array($ext, $allowed)) {
            $error = "Format file tidak didukung!";
        } elseif ($fileSize > 5 * 1024 * 1024) {
            $error = "Ukuran file maksimal 5MB!";
        } else {
            $foto = uniqid() . '.' . $ext;
            move_uploaded_file($tmpName, 'uploads/' . $foto);
        }
    }

    if (!isset($error)) {
        // Generate QR code with first two initials
        $inisial = '';
        if (!empty($nama_lengkap)) {
            $words = explode(' ', $nama_lengkap);
            $inisial = strtoupper(($words[0][0] ?? '') . ($words[1][0] ?? ($words[0][1] ?? '')));
        }
        $kodeAcak = rand(10000, 99999);
        $qrcode_text = $inisial . $kodeAcak;
        $qrcode_file = $qrcode_text . '.png';


        $insert = mysqli_query($conn, "INSERT INTO siswa (
            nisn, nama_lengkap, nik, tempat_lahir, tanggal_lahir, tingkat_rombel, umur, status, jenis_kelamin,
            alamat, nama_ayah, nama_ibu, foto, qrcode
        ) VALUES (
            '$nisn', '$nama_lengkap', '$nik', '$tempat_lahir', '$tanggal_lahir', '$tingkat_rombel', '$umur',
            '$status', '$jenis_kelamin', '$alamat', '$nama_ayah', '$nama_ibu', '$foto', '$qrcode_text'
        )");

        if ($insert) {
            QRcode::png($qrcode_text, 'qrcodes/' . $qrcode_file, QR_ECLEVEL_L, 4, 2);
            $_SESSION['sukses'] = 'Data siswa berhasil ditambahkan!';
            header('Location: siswa_tambah.php');
            exit;
        } else {
            $error = "Gagal menyimpan data: " . mysqli_error($conn);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Siswa - Sistem Presensi MIDU</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
    body {
      background-color: #f1f5f9;
      overflow-x: hidden;
    }
    #preview {
      max-width: 150px;
      border: 1px solid #ccc;
      border-radius: 8px;
    }
    label {
      font-weight: 500;
    }
    .qr {
      width: 50px;
      height: 50px;
    }
    .sidebar {
      position: fixed;
      top: 0;
      left: 0;
      width: 250px;
      height: 100vh;
      background-color: #2c6e49;
      color: white;
      padding-top: 20px;
      transition: transform 0.3s ease;
      z-index: 1000;
      transform: translateX(0);
    }
    .sidebar.collapsed {
      transform: translateX(-250px);
    }
    .sidebar .nav-link {
      color: #d4edda;
      padding: 10px 20px;
      margin: 5px 10px;
      border-radius: 5px;
    }
    .sidebar .nav-link:hover,
    .sidebar .nav-link.active {
      background-color: #3d8b5e;
      color: white;
    }
    .sidebar .nav-link i {
      margin-right: 10px;
    }
    .content {
      transition: margin-left 0.3s ease;
      margin-left: 250px;
      padding: 20px;
    }
    .content.expanded {
      margin-left: 0;
    }
    .navbar-brand {
      color: #2c6e49 !important;
      font-weight: bold;
    }
    .card {
      border: none;
      border-radius: 10px;
      box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }
    .card-header {
      background-color: #2c6e49;
      color: white;
      border-radius: 10px 10px 0 0;
    }
    .logo {
      width: 100px;
      display: block;
      margin: 0 auto 20px;
    }
    @media (max-width: 768px) {
      .sidebar {
        transform: translateX(-250px);
      }
      .sidebar.active {
        transform: translateX(0);
      }
      .content {
        margin-left: 0 !important;
      }
    }
  </style>
</head>
<body>
<?php include 'sidebar.php'; ?>
<div class="content" id="mainContent">
    <nav class="navbar navbar-light bg-white shadow-sm mb-4">
        <div class="container-fluid">
            <button class="btn btn-outline-success" id="toggleSidebarBtn">
                <i class="fas fa-bars"></i>
            </button>
            <span class="navbar-brand ms-3">Tambah Siswa</span>
        </div>
    </nav>

    <div class="container-fluid">
        <div class="card">
            <div class="card-header"><h5>Form Tambah Siswa</h5></div>
            <div class="card-body px-5 py-4">
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger"><?= $error ?></div>
                <?php endif; ?>

                <form method="POST" enctype="multipart/form-data">
                    <div class="row">
                        <div class="col-md-6">
                            <label for="nisn">NISN</label>
                            <input type="text" name="nisn" id="nisn" class="form-control mb-3" value="<?= htmlspecialchars($nisn) ?>" required>

                            <label for="nik">NIK</label>
                            <input type="text" name="nik" id="nik" class="form-control mb-3" value="<?= htmlspecialchars($nik) ?>" >

                            <label for="tanggal_lahir">Tanggal Lahir</label>
                            <input type="date" name="tanggal_lahir" id="tanggal_lahir" class="form-control mb-3" value="<?= htmlspecialchars($tanggal_lahir) ?>" required>

                            <label for="umur">Umur</label>
                            <input type="number" name="umur" id="umur" class="form-control mb-3" value="<?= htmlspecialchars($umur) ?>">

                            <label for="jenis_kelamin">Jenis Kelamin</label>
                            <select name="jenis_kelamin" id="jenis_kelamin" class="form-select mb-3" required>
                                <option value="">Pilih Jenis Kelamin</option>
                                <option value="L" <?= ($jenis_kelamin == 'L') ? 'selected' : '' ?>>Laki-laki</option>
                                <option value="P" <?= ($jenis_kelamin == 'P') ? 'selected' : '' ?>>Perempuan</option>
                            </select>

                            <label for="nama_ayah">Nama Ayah</label>
                            <input type="text" name="nama_ayah" id="nama_ayah" class="form-control mb-3" value="<?= htmlspecialchars($nama_ayah) ?>" >
                        </div>

                        <div class="col-md-6">
                            <label for="nama_lengkap">Nama Lengkap</label>
                            <input type="text" name="nama_lengkap" id="nama_lengkap" class="form-control mb-3" value="<?= htmlspecialchars($nama_lengkap) ?>" required>

                            <label for="tempat_lahir">Tempat Lahir</label>
                            <input type="text" name="tempat_lahir" id="tempat_lahir" class="form-control mb-3" value="<?= htmlspecialchars($tempat_lahir) ?>" required>

                            <label for="tingkat_rombel">Tingkat Rombel</label>
                            <input type="text" name="tingkat_rombel" id="tingkat_rombel" class="form-control mb-3" value="<?= htmlspecialchars($tingkat_rombel) ?>" required>

                            <label for="status">Status</label>
                            <select name="status" id="status" class="form-select mb-3" required>
                                <option value="aktif" <?= ($status == 'aktif') ? 'selected' : '' ?>>Aktif</option>
                                <option value="non aktif" <?= ($status == 'non aktif') ? 'selected' : '' ?>>Non Aktif</option>
                            </select>

                            <label for="alamat">Alamat</label>
                            <textarea name="alamat" id="alamat" class="form-control mb-3"><?= htmlspecialchars($alamat) ?></textarea>

                            <label for="nama_ibu">Nama Ibu</label>
                            <input type="text" name="nama_ibu" id="nama_ibu" class="form-control mb-3" value="<?= htmlspecialchars($nama_ibu) ?>" required>
                        </div>

                        <div class="col-md-6">
                            <label for="foto">Foto</label>
                            <input type="file" name="foto" id="foto" class="form-control mb-3" accept="image/*" onchange="previewFoto(event)">
                        </div>
                        <div class="col-md-6 d-flex align-items-end">
                            <img id="preview" class="preview-img" src="#" style="display:none;">
                        </div>

                        <div class="col-md-12">
                            <button type="submit" class="btn btn-success mt-3">Simpan</button>
                            <a href="data-siswa.php" class="btn btn-warning mt-3">Kembali</a>
                        </div>
                    </div>
                </form>

                <?php if (isset($_SESSION['sukses'])): ?>
                <script>
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil!',
                        text: <?= json_encode($_SESSION['sukses']) ?>,
                        confirmButtonColor: '#28a745'
                    }).then(() => {
                        window.location = 'data-siswa.php';
                    });
                </script>
                <?php unset($_SESSION['sukses']); endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
function previewFoto(event) {
    const img = document.getElementById('preview');
    img.src = URL.createObjectURL(event.target.files[0]);
    img.style.display = 'block';
}
</script>
</body>
</html>
