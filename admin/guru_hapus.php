
<?php
session_start();
include 'auth.php';
include 'config.php';

if (isset($_GET['id']) && !empty($_GET['id'])) {
    $id = intval($_GET['id']);
    $guru = mysqli_query($conn, "SELECT * FROM guru WHERE id = $id");
    if (mysqli_num_rows($guru) > 0) {
        $data = mysqli_fetch_assoc($guru);

        // Hapus foto jika bukan default
        if ($data['foto'] && $data['foto'] != 'default.jpg' && file_exists('uploads/' . $data['foto'])) {
            unlink('uploads/' . $data['foto']);
        }

        // Hapus QR code
        if ($data['qrcode'] && file_exists('qrcodes/' . $data['qrcode'] . '.png')) {
            unlink('qrcodes/' . $data['qrcode'] . '.png');
        }

        // Hapus dari database
        $delete = mysqli_query($conn, "DELETE FROM guru WHERE id = $id");
        if ($delete) {
            $_SESSION['success'] = 'Data guru berhasil dihapus!';
        } else {
            $_SESSION['error'] = 'Gagal menghapus data guru: ' . mysqli_error($conn);
        }
    } else {
        $_SESSION['error'] = 'Data guru tidak ditemukan!';
    }
} else {
    $_SESSION['error'] = 'ID tidak valid!';
}

header('Location: data-guru.php');
exit;
?>
