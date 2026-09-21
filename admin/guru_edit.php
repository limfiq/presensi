<?php
session_start();
$activePage = 'data-guru';
include 'auth.php';
include 'config.php';
include 'lib/phpqrcode/qrlib.php';

if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: data-guru.php');
    exit;
}

$id = $_GET['id'];
$query = mysqli_query($conn, "SELECT * FROM guru WHERE id='$id'");
$data = mysqli_fetch_assoc($query);

if (!$data) {
    header('Location: data-guru.php');
    exit;
}

$nip = $data['nip'] ?? '';
$nama_lengkap = $data['nama_lengkap'] ?? '';
$nik = $data['nik'] ?? '';
$tempat_lahir = $data['tempat_lahir'] ?? '';
$tanggal_lahir = $data['tanggal_lahir'] ?? '';
$jabatan = $data['jabatan'] ?? '';
$jenis_kelamin = $data['jenis_kelamin'] ?? '';
$alamat = $data['alamat'] ?? '';
$no_hp = $data['no_hp'] ?? '';
$email = $data['email'] ?? '';
$foto = $data['foto'] ?? 'default.jpg';
$qrcode = $data['qrcode'] ?? '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nip = $_POST['nip'] ?? '';
    $nama_lengkap = $_POST['nama_lengkap'] ?? '';
    $nik = $_POST['nik'] ?? '';
    $tempat_lahir = $_POST['tempat_lahir'] ?? '';
    $tanggal_lahir = $_POST['tanggal_lahir'] ?? '';
    $jabatan = $_POST['jabatan'] ?? '';
    $jenis_kelamin = $_POST['jenis_kelamin'] ?? '';
    $alamat = $_POST['alamat'] ?? '';
    $no_hp = $_POST['no_hp'] ?? '';
    $email = $_POST['email'] ?? '';
    $foto_lama = $data['foto'];

    $cek = mysqli_query($conn, "SELECT * FROM guru WHERE nip='$nip' AND id != '$id'");
    if (mysqli_num_rows($cek) > 0) {
        $error = "NIP sudah digunakan!";
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
            if ($foto_lama != 'default.jpg' && file_exists('uploads/' . $foto_lama)) {
                unlink('uploads/' . $foto_lama);
            }
        }
    } else {
        $foto = $foto_lama;
    }

    if (!isset($error)) {
        $inisial = implode('', array_map(fn($word) => strtoupper($word[0]), explode(' ', $nama_lengkap)));
        $kodeAcak = rand(10000, 99999);
        $qrcode_text = $inisial . $kodeAcak;
        $qrcode_file = $qrcode_text . '.png';

        if ($qrcode_text != $data['qrcode']) {
            if (file_exists('qrcodes/' . $data['qrcode'] . '.png')) {
                unlink('qrcodes/' . $data['qrcode'] . '.png');
            }
            QRcode::png($qrcode_text, 'qrcodes/' . $qrcode_file, QR_ECLEVEL_L, 4, 2);
        } else {
            $qrcode_text = $data['qrcode'];
        }

        $update = mysqli_query($conn, "UPDATE guru SET
            nip='$nip', nama_lengkap='$nama_lengkap', nik='$nik', tempat_lahir='$tempat_lahir',
            tanggal_lahir='$tanggal_lahir', jabatan='$jabatan', jenis_kelamin='$jenis_kelamin',
            alamat='$alamat', no_hp='$no_hp', email='$email', foto='$foto', qrcode='$qrcode_text'
            WHERE id='$id'");

        if ($update) {
            $_SESSION['sukses'] = 'Data guru berhasil diperbarui!';
        } else {
            $error = "Gagal memperbarui data: " . mysqli_error($conn);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Guru - Sistem Presensi MIDU</title>
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
            <span class="navbar-brand ms-3">Edit Guru</span>
        </div>
    </nav>

    <div class="container-fluid">
        <div class="card">
            <div class="card-header"><h5>Form Edit Guru</h5></div>
            <div class="card-body px-5 py-4">
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger"><?= $error ?></div>
                <?php endif; ?>

                <form method="POST" enctype="multipart/form-data">
                    <div class="row">
                        <div class="col-md-6">
                            <label for="nip">NPM</label>
                            <input type="text" name="nip" id="nip" class="form-control mb-3" value="<?php echo htmlspecialchars($nip); ?>" required>

                            <label for="nik">NIK</label>
                            <input type="text" name="nik" id="nik" class="form-control mb-3" value="<?php echo htmlspecialchars($nik); ?>">

                            <label for="tanggal_lahir">Tanggal Lahir</label>
                            <input type="date" name="tanggal_lahir" id="tanggal_lahir" class="form-control mb-3" value="<?php echo htmlspecialchars($tanggal_lahir); ?>">

                            <label for="jabatan">Jabatan</label>
                            <input type="text" name="jabatan" id="jabatan" class="form-control mb-3" value="<?php echo htmlspecialchars($jabatan); ?>">

                            <label for="jenis_kelamin">Jenis Kelamin</label>
                            <select name="jenis_kelamin" id="jenis_kelamin" class="form-select mb-3">
                                <option value="">Pilih Jenis Kelamin</option>
                                <option value="L" <?php echo ($jenis_kelamin == 'L') ? 'selected' : ''; ?>>Laki-laki</option>
                                <option value="P" <?php echo ($jenis_kelamin == 'P') ? 'selected' : ''; ?>>Perempuan</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label for="nama_lengkap">Nama Lengkap</label>
                            <input type="text" name="nama_lengkap" id="nama_lengkap" class="form-control mb-3" value="<?php echo htmlspecialchars($nama_lengkap); ?>" required>

                            <label for="tempat_lahir">Tempat Lahir</label>
                            <input type="text" name="tempat_lahir" id="tempat_lahir" class="form-control mb-3" value="<?php echo htmlspecialchars($tempat_lahir); ?>">

                            <label for="alamat">Alamat</label>
                            <textarea name="alamat" id="alamat" class="form-control mb-3"><?php echo htmlspecialchars($alamat); ?></textarea>

                            <label for="no_hp">No. HP</label>
                            <input type="text" name="no_hp" id="no_hp" class="form-control mb-3" value="<?php echo htmlspecialchars($no_hp); ?>">

                            <label for="email">Email</label>
                            <input type="email" name="email" id="email" class="form-control mb-3" value="<?php echo htmlspecialchars($email); ?>">
                        </div>

                        <div class="col-md-6">
                            <label for="foto">Foto</label>
                            <input type="file" name="foto" id="foto" class="form-control mb-3" accept="image/*" onchange="previewFoto(event)">
                        </div>
                        <div class="col-md-6 d-flex align-items-end">
                            <img id="preview" class="preview-img" src="<?php echo ($foto != 'default.jpg') ? 'uploads/' . htmlspecialchars($foto) : '#'; ?>" style="<?php echo ($foto != 'default.jpg') ? 'display:block;' : 'display:none;'; ?>">
                        </div>

                        <div class="col-md-12">
                            <button type="submit" class="btn btn-success mt-3">Simpan</button>
                            <a href="data-guru.php" class="btn btn-warning mt-3">Kembali</a>
                        </div>
                    </div>
                </form>

                <?php if (isset($_SESSION['sukses'])): ?>
                <script>
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil!',
                        text: <?php echo json_encode($_SESSION['sukses']); ?>,
                        confirmButtonColor: '#28a745',
                        allowOutsideClick: false
                    }).then((result) => {
                        if (result.isConfirmed) {
                            window.location = 'data-guru.php';
                        }
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
