<?php
session_start();
require_once 'config.php';
include 'auth.php';

$data = $conn->query("
  SELECT k.*, 
         IF(k.jenis_pengguna='siswa', s.nama_lengkap, g.nama_lengkap) AS nama, 
         IF(k.jenis_pengguna='siswa', s.tingkat_rombel, g.jabatan) AS info
  FROM ketidakhadiran k
  LEFT JOIN siswa s ON k.id_siswa = s.id
  LEFT JOIN guru g ON k.id_guru = g.id
  ORDER BY k.tanggal DESC
");
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Data Ketidakhadiran</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

</head>
<body>
<div class="container py-4">
  <h4 class="mb-4">Data Ketidakhadiran</h4>
  <a href="ketidakhadiran_tambah.php" class="btn btn-primary mb-3">+ Tambah</a>

  <div class="table-responsive">
    <table class="table table-bordered">
      <thead class="table-dark">
        <tr>
          <th>No</th>
          <th>Tanggal</th>
          <th>Nama</th>
          <th>Jenis</th>
          <th>Kelas / Jabatan</th>
          <th>Keterangan</th>
          <th>Alasan</th>
          <th>Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php $no = 1; while ($row = $data->fetch_assoc()): ?>
        <tr>
          <td><?= $no++ ?></td>
          <td><?= $row['tanggal'] ?></td>
          <td><?= $row['nama'] ?></td>
          <td><?= ucfirst($row['jenis_pengguna']) ?></td>
          <td><?= $row['info'] ?></td>
          <td><?= ucfirst($row['keterangan']) ?></td>
          <td><?= $row['alasan'] ?></td>
          <td>
            <a href="ketidakhadiran_edit.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-warning">Edit</a>
            <button class="btn btn-sm btn-danger btn-hapus" data-id="<?= $row['id'] ?>" data-nama="<?= $row['nama'] ?>"> Hapus </button>

          </td>
        </tr>
        <?php endwhile ?>
      </tbody>
    </table>
  </div>
</div>

<script>
  document.querySelectorAll('.btn-hapus').forEach(button => {
    button.addEventListener('click', function () {
      const id = this.dataset.id;
      const nama = this.dataset.nama;

      Swal.fire({
        title: 'Yakin ingin menghapus?',
        text: `Data ketidakhadiran ${nama} akan dihapus!`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Ya, hapus!',
        cancelButtonText: 'Batal'
      }).then((result) => {
        if (result.isConfirmed) {
          window.location.href = `ketidakhadiran_hapus.php?id=${id}`;
        }
      });
    });
  });
</script>

</body>
</html>
