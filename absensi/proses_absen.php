<?php
require_once "../config/db.php";
date_default_timezone_set("Asia/Jakarta");

$qrcode  = isset($_POST['qrcode']) ? trim($_POST['qrcode']) : '';
$lokasi  = isset($_POST['lokasi']) ? trim($_POST['lokasi']) : '';
$alamat  = isset($_POST['alamat']) ? trim($_POST['alamat']) : '';

if (empty($qrcode)) {
    echo json_encode(["status" => "error", "pesan" => "QR Code tidak boleh kosong"]);
    exit;
}

// Cek apakah QR milik siswa atau guru
$stmt = $conn->prepare("SELECT * FROM siswa WHERE qrcode = ?");
$stmt->bind_param("s", $qrcode);
$stmt->execute();
$result = $stmt->get_result();
$data = $result->fetch_assoc();

$jenis_pengguna = 'siswa';

if (!$data) {
    $stmt = $conn->prepare("SELECT * FROM guru WHERE qrcode = ?");
    $stmt->bind_param("s", $qrcode);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_assoc();
    $jenis_pengguna = 'guru';
}

if (!$data) {
    echo json_encode([
        "status" => "notfound",
        "pesan" => "QR Code tidak ditemukan",
        "nama" => "",
        "foto" => "default.png"
    ]);
    exit;
}

$id_user = $data['id'];
$nama = strtoupper($data['nama_lengkap']);

$foto = $data['foto'];
$tingkat_rombel = $jenis_pengguna == 'siswa' ? $data['tingkat_rombel'] : null;
$jabatan = $jenis_pengguna == 'guru' ? $data['jabatan'] : null;

$tanggal = date('Y-m-d');
waktu:
$jam_sekarang = date('H:i:s');

// Ambil jadwal hari ini
$hari_ini = date('l');
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

$stmt = $conn->prepare("SELECT jam_masuk, jam_pulang FROM jadwal WHERE jenis_pengguna = ? AND hari = ?");
$stmt->bind_param("ss", $jenis_pengguna, $hari);
$stmt->execute();
$jadwal = $stmt->get_result()->fetch_assoc();

$jam_masuk = $jadwal['jam_masuk'] ?? '07:00:00';
$jam_pulang = $jadwal['jam_pulang'] ?? '14:00:00';

// Cek apakah sudah ada presensi hari ini
$stmt = $conn->prepare("SELECT * FROM presensi WHERE tanggal = ? AND jenis_pengguna = ? AND (id_siswa = ? OR id_guru = ?)");
$stmt->bind_param("ssii", $tanggal, $jenis_pengguna, $id_user, $id_user);
$stmt->execute();
$cek = $stmt->get_result()->fetch_assoc();

$now = date("Y-m-d H:i:s");

if (!$cek) {
    // Belum ada data -> catat jam masuk
    $status_masuk = (strtotime($jam_sekarang) > strtotime($jam_masuk)) ? 'Terlambat' : 'Tepat Waktu';

    $stmt = $conn->prepare("INSERT INTO presensi (id_siswa, id_guru, jenis_pengguna, tanggal, jam_masuk, status_masuk, lokasi, alamat, created_at) VALUES (?,?,?,?,?,?,?,?,NOW())");
    $id_siswa = $jenis_pengguna == 'siswa' ? $id_user : null;
    $id_guru  = $jenis_pengguna == 'guru' ? $id_user : null;
    $stmt->bind_param("iissssss", $id_siswa, $id_guru, $jenis_pengguna, $tanggal, $jam_sekarang, $status_masuk, $lokasi, $alamat);
    $stmt->execute();

    echo json_encode([
        "status" => "success",
        "pesan" => "<span class='badge bg-success mt-1'>Presensi Masuk dicatat</span>",
        "pesanb" => "Presensi Masuk dicatat - $jam_sekarang",
        "nama" => $nama,
        "foto" => $foto,
        "tingkat_rombel" => $tingkat_rombel,
        "jabatan" => $jabatan,
        "lokasi" => $alamat
    ]);
} elseif ($cek && !$cek['jam_pulang']) {
    // Sudah masuk tapi belum pulang
    $status_pulang = (strtotime($jam_sekarang) < strtotime($jam_pulang)) ? 'Pulang Cepat' : 'Tepat Waktu';

    $stmt = $conn->prepare("UPDATE presensi SET jam_pulang = ?, status_pulang = ?, lokasi = ?, alamat = ? WHERE id = ?");
    $stmt->bind_param("ssssi", $jam_sekarang, $status_pulang, $lokasi, $alamat, $cek['id']);
    $stmt->execute();

    echo json_encode([
        "status" => "success",
        "pesan" => "<span class='badge bg-success mt-1'>Presensi Pulang dicatat</span>",
        "pesanb" => "Presensi Pulang dicatat - $jam_sekarang",
        "nama" => $nama,
        "foto" => $foto,
        "tingkat_rombel" => $tingkat_rombel,
        "jabatan" => $jabatan,
        "lokasi" => $alamat
    ]);
} else {
    echo json_encode([
        "status" => "already",
        "pesan" => "<span class='badge bg-warning mt-1'>Sudah Melakukan Presensi Hari Ini</span>",
        "pesanb" => "Sudah Melakukan Presensi Hari Ini - $jam_sekarang",
        "nama" => $nama,
        "foto" => $foto,
        "tingkat_rombel" => $tingkat_rombel,
        "jabatan" => $jabatan,
        "lokasi" => $alamat
    ]);
}
