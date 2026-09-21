<?php
session_start();
include 'auth.php';
include 'config.php';

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $siswa = mysqli_query($conn, "SELECT * FROM siswa WHERE id = $id");
    if (mysqli_num_rows($siswa) > 0) {
        $data = mysqli_fetch_assoc($siswa);

        // Hapus foto jika bukan default
        if ($data['foto'] && $data['foto'] != 'default.jpg') {
            @unlink('uploads/' . $data['foto']);
        }

        // Hapus QR code
        if ($data['qrcode']) {
            @unlink('qrcodes/' . $data['qrcode'] . '.png');
        }

        // Hapus dari database
        $delete = mysqli_query($conn, "DELETE FROM siswa WHERE id = $id");
        if ($delete) {
            $_SESSION['success'] = 'Data siswa berhasil dihapus!';
        } else {
            $_SESSION['error'] = 'Gagal menghapus data siswa!';
        }
    } else {
        $_SESSION['error'] = 'Data siswa tidak ditemukan!';
    }
} else {
    $_SESSION['error'] = 'ID tidak valid!';
}

header('Location: data-siswa.php');
exit;
