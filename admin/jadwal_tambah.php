<?php
include 'auth.php';
include 'config.php';

if (isset($_POST['simpan'])) {
    $hari = $_POST['hari'];
    $jam_masuk = $_POST['jam_masuk'];
    $jam_pulang = $_POST['jam_pulang'];

    // Validasi sederhana
    if ($hari && $jam_masuk && $jam_pulang) {
        $insert = mysqli_query($conn, "INSERT INTO jadwal (jenis_pengguna, hari, jam_masuk, jam_pulang) VALUES ('siswa', '$hari', '$jam_masuk', '$jam_pulang')");
        if ($insert) {
            echo "<script>
                alert('Jadwal berhasil ditambahkan.');
                window.location.href = 'jadwal.php';
            </script>";
        } else {
            echo "<script>alert('Gagal menyimpan.');</script>";
        }
    } else {
        echo "<script>alert('Semua kolom wajib diisi.');</script>";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <title>Tambah Jadwal Siswa</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="bg-light">
<div class="container py-4">
  <h4 class="mb-4">➕ Tambah Jadwal Kehadiran Siswa</h4>
  <a href="jadwal.php" class="btn btn-secondary mb-3">🔙 Kembali</a>

  <form method="post" class="card p-4 shadow-sm bg-white">
    <div class="mb-3">
      <label for="hari" class="form-label">Hari</label>
      <select name="hari" class="form-select" required>
        <option value="">-- Pilih Hari --</option>
        <?php
        $hariList = ['Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'];
        foreach ($hariList as $h) {
            echo "<option value=\"$h\">$h</option>";
        }
        ?>
      </select>
    </div>

    <div class="mb-3">
      <label for="jam_masuk" class="form-label">Jam Masuk</label>
      <input type="time" name="jam_masuk" class="form-control" required>
    </div>

    <div class="mb-3">
      <label for="jam_pulang" class="form-label">Jam Pulang</label>
      <input type="time" name="jam_pulang" class="form-control" required>
    </div>

    <button type="submit" name="simpan" class="btn btn-success">💾 Simpan</button>
  </form>
</div>
</body>
</html>
