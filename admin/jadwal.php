<?php
include 'auth.php';
include 'config.php';

// Ambil semua jadwal siswa
$jadwal = mysqli_query($conn, "SELECT * FROM jadwal WHERE jenis_pengguna='siswa' ORDER BY FIELD(hari, 'Senin','Selasa','Rabu','Kamis','Jumat','Sabtu','Minggu')");
?>

<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <title>Data Jadwal Siswa</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="bg-light">
<div class="container py-4">
  <h4 class="mb-4">📅 Data Jadwal Kehadiran Siswa</h4>

  <a href="jadwal_tambah.php" class="btn btn-success mb-3">➕ Tambah Jadwal</a>

  <div class="table-responsive">
    <table id="jadwalTable" class="table table-bordered table-striped">
      <thead class="table-dark">
        <tr>
          <th>No</th>
          <th>Hari</th>
          <th>Jam Masuk</th>
          <th>Jam Pulang</th>
          <th>Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php $no=1; while($row = mysqli_fetch_assoc($jadwal)): ?>
        <tr>
          <td><?= $no++ ?></td>
          <td><?= $row['hari'] ?></td>
          <td><?= substr($row['jam_masuk'], 0, 5) ?></td>
          <td><?= substr($row['jam_pulang'], 0, 5) ?></td>
          <td>
            <a href="jadwal_edit.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-warning">✏️ Edit</a>
            <button onclick="hapusJadwal(<?= $row['id'] ?>)" class="btn btn-sm btn-danger">🗑️ Hapus</button>
          </td>
        </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  </div>
</div>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script>
  $(document).ready(function() {
    $('#jadwalTable').DataTable();
  });

  function hapusJadwal(id) {
    Swal.fire({
      title: 'Hapus Jadwal?',
      text: "Data tidak dapat dikembalikan!",
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#d33',
      cancelButtonColor: '#6c757d',
      confirmButtonText: 'Ya, hapus!'
    }).then((result) => {
      if (result.isConfirmed) {
        window.location.href = "jadwal_hapus.php?id=" + id;
      }
    });
  }
</script>
</body>
</html>
