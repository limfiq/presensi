<?php
include 'auth.php';
include 'config.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id > 0) {
    $hapus = mysqli_query($conn, "DELETE FROM jadwal WHERE id='$id'");
    if ($hapus) {
        echo "<script>
            alert('Jadwal berhasil dihapus.');
            window.location.href = 'jadwal.php';
        </script>";
    } else {
        echo "<script>
            alert('Gagal menghapus jadwal.');
            window.location.href = 'jadwal.php';
        </script>";
    }
} else {
    echo "<script>
        alert('ID tidak valid.');
        window.location.href = 'jadwal.php';
    </script>";
}
