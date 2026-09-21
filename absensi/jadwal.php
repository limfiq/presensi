<?php
date_default_timezone_set('Asia/Jakarta');
require_once '../config/db.php'; // atau sesuaikan dengan koneksi Anda

$hari_ini = date('l'); // English format
$hari_map = [
    'Monday' => 'Senin',
    'Tuesday' => 'Selasa',
    'Wednesday' => 'Rabu',
    'Thursday' => 'Kamis',
    'Friday' => 'Jumat',
    'Saturday' => 'Sabtu',
    'Sunday' => 'Minggu'
];

$hari = $hari_map[$hari_ini];

$query = $conn->prepare("SELECT jam_masuk, jam_pulang FROM jadwal WHERE jenis_pengguna = 'siswa' AND hari = ?");
$query->bind_param("s", $hari);
$query->execute();
$result = $query->get_result();

$data = $result->fetch_assoc();
if ($data) {
    echo json_encode($data);
} else {
    echo json_encode(["jam_masuk" => null, "jam_pulang" => null]);
}
