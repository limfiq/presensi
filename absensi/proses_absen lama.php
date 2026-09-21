<?php
include '../config/db.php';
date_default_timezone_set('Asia/Jakarta');

$tanggal = date('Y-m-d');
$waktu    = date('H:i:s');
$jamSekarang = strtotime($waktu);

// Ambil QR code dari input
$qrcode = isset($_POST['qrcode']) ? $_POST['qrcode'] : '';
if (!$qrcode) {
    echo json_encode(['status' => 'error', 'message' => 'QR Code tidak terbaca']);
    exit;
}

// Cek apakah data QR milik siswa
$siswa = mysqli_query($conn, "SELECT * FROM siswa WHERE qrcode='$qrcode'");
if (mysqli_num_rows($siswa) > 0) {
    $data = mysqli_fetch_assoc($siswa);
    $id = $data['id'];
    $jenis_pengguna = 'siswa';
} else {
    // Cek apakah data QR milik guru
    $guru = mysqli_query($conn, "SELECT * FROM guru WHERE qrcode='$qrcode'");
    if (mysqli_num_rows($guru) > 0) {
        $data = mysqli_fetch_assoc($guru);
        $id = $data['id'];
        $jenis_pengguna = 'guru';
    } else {
        echo json_encode(['status' => 'notfound']);
        exit;
    }
}

// Tentukan hari dan ambil jadwal
$hariEn = date('l');
$mapHari = ['Monday'=>'Senin','Tuesday'=>'Selasa','Wednesday'=>'Rabu','Thursday'=>'Kamis','Friday'=>'Jumat','Saturday'=>'Sabtu','Sunday'=>'Minggu'];
$hari = $mapHari[$hariEn];

$qjadwal = mysqli_query($conn, "SELECT * FROM jadwal WHERE hari='$hari' AND jenis_pengguna='$jenis_pengguna' LIMIT 1");
$jadwal = mysqli_fetch_assoc($qjadwal);

// Default jika jadwal tidak ditemukan
$jam_masuk = isset($jadwal['jam_masuk']) ? strtotime($jadwal['jam_masuk']) : strtotime('06:00:00');
$jam_pulang = isset($jadwal['jam_pulang']) ? strtotime($jadwal['jam_pulang']) : strtotime('13:00:00');

// Cek presensi hari ini
$kolom_id = $jenis_pengguna === 'siswa' ? "id_siswa" : "id_guru";
$presensi = mysqli_query($conn, "SELECT * FROM presensi WHERE $kolom_id='$id' AND tanggal='$tanggal' LIMIT 1");
$presen = mysqli_fetch_assoc($presensi);

if (!$presen) {
    // Belum absen => absen masuk
    $status_masuk = ($jamSekarang > $jam_masuk) ? 'Terlambat' : 'Tepat Waktu';
    $query = mysqli_query($conn, "
        INSERT INTO presensi ($kolom_id, tanggal, jam_masuk, status_masuk, jenis_pengguna)
        VALUES ('$id', '$tanggal', '$waktu', '$status_masuk', '$jenis_pengguna')
    ");

    echo json_encode([
        'status' => 'success',
        'jenis' => 'masuk',
        'nama' => $data['nama_lengkap'],
        'foto' => $data['foto'],
        'pesan' => "Absen Masuk: $status_masuk"
    ]);

} else if (is_null($presen['jam_pulang'])) {
    // Sudah absen masuk => absen pulang
    $status_pulang = ($jamSekarang < $jam_pulang) ? 'Pulang Cepat' : 'Tepat Waktu';
    $id_presensi = $presen['id'];

    mysqli_query($conn, "
        UPDATE presensi
        SET jam_pulang='$waktu', status_pulang='$status_pulang'
        WHERE id='$id_presensi'
    ");

    echo json_encode([
        'status' => 'success',
        'jenis' => 'pulang',
        'nama' => $data['nama_lengkap'],
        'foto' => $data['foto'],
        'pesan' => "Absen Pulang: $status_pulang"
    ]);

} else {
    // Sudah absen masuk & pulang
    echo json_encode([
        'status' => 'already',
        'nama' => $data['nama_lengkap'],
        'foto' => $data['foto'],
        'pesan' => 'Sudah absen masuk dan pulang hari ini'
    ]);
}
