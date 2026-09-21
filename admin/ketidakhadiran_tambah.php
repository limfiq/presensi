<?php
session_start();
require_once 'config.php';
include 'auth.php';

// Ambil data siswa & guru
$siswa = $conn->query("SELECT id, nama_lengkap, tingkat_rombel FROM siswa ORDER BY nama_lengkap");
$guru  = $conn->query("SELECT id, nama_lengkap, jabatan FROM guru ORDER BY nama_lengkap");

$success = $error = '';

function sudahHadir($conn, $jenis, $id, $tanggal) {
  if ($jenis === 'siswa') {
    $stmt = $conn->prepare("SELECT id FROM presensi WHERE tanggal=? AND id_siswa=?");
  } else {
    $stmt = $conn->prepare("SELECT id FROM presensi WHERE tanggal=? AND id_guru=?");
  }
  $stmt->bind_param("si", $tanggal, $id);
  $stmt->execute();
  $stmt->store_result();
  return $stmt->num_rows > 0;
}

function sudahAdaKetidakhadiran($conn, $jenis, $id, $tanggal) {
  if ($jenis === 'siswa') {
    $stmt = $conn->prepare("SELECT id FROM ketidakhadiran WHERE tanggal=? AND id_siswa=? AND jenis_pengguna='siswa'");
  } else {
    $stmt = $conn->prepare("SELECT id FROM ketidakhadiran WHERE tanggal=? AND id_guru=? AND jenis_pengguna='guru'");
  }
  $stmt->bind_param("si", $tanggal, $id);
  $stmt->execute();
  $stmt->store_result();
  return $stmt->num_rows > 0;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $jenis       = $_POST['jenis'] ?? '';
  $id_pengguna = $_POST['id_pengguna'] ?? '';
  $tanggal     = $_POST['tanggal'] ?? '';
  $keterangan  = $_POST['keterangan'] ?? '';
  $alasan      = $_POST['alasan'] ?? null;

  if ($jenis && $id_pengguna && $tanggal && $keterangan) {
    if (sudahHadir($conn, $jenis, $id_pengguna, $tanggal)) {
      $error = "Tidak bisa mencatat ketidakhadiran karena pengguna sudah tercatat hadir.";
    } elseif (sudahAdaKetidakhadiran($conn, $jenis, $id_pengguna, $tanggal)) {
      $error = "Data ketidakhadiran pada tanggal tersebut sudah ada untuk pengguna ini.";
    } else {
      $stmt = $conn->prepare("INSERT INTO ketidakhadiran (tanggal, id_siswa, id_guru, jenis_pengguna, keterangan, alasan) VALUES (?, ?, ?, ?, ?, ?)");
      $id_siswa = $jenis === 'siswa' ? $id_pengguna : null;
      $id_guru  = $jenis === 'guru'  ? $id_pengguna : null;
      $stmt->bind_param("siisss", $tanggal, $id_siswa, $id_guru, $jenis, $keterangan, $alasan);

      if ($stmt->execute()) {
        $success = "Data ketidakhadiran berhasil disimpan.";
      } else {
        $error = "Gagal menyimpan data. " . $stmt->error;
      }
    }
  } else {
    $error = "Semua field wajib diisi.";
  }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Ketidakhadiran - Sistem Presensi MIDU</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body {
            background: linear-gradient(135deg, #f1f5f9 0%, #e2e8f0 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            overflow-x: hidden;
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
        .form-label {
            font-weight: 500;
            color: #2c6e49;
        }
        .form-control, .form-select {
            border-radius: 8px;
            transition: border-color 0.3s ease;
        }
        .form-control:focus, .form-select:focus {
            border-color: #2c6e49;
            box-shadow: 0 0 5px rgba(44, 110, 73, 0.3);
        }
        .btn-primary {
            background-color: #2c6e49;
            border-color: #2c6e49;
        }
        .btn-primary:hover {
            background-color: #3d8b5e;
            border-color: #3d8b5e;
        }
        .select2-container--bootstrap-5 .select2-selection {
            border-radius: 8px;
        }
        .select2-container--bootstrap-5 .select2-selection--single {
            height: calc(2.25rem + 2px);
            padding: 0.375rem 2.25rem 0.375rem 0.75rem;
        }
        .select2-hidden {
            display: none !important;
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
                <span class="navbar-brand ms-3">Tambah Ketidakhadiran</span>
            </div>
        </nav>

        <div class="container-fluid">
            <div class="card">
                <div class="card-header"><h5>Form Tambah Ketidakhadiran</h5></div>
                <div class="card-body px-5 py-4">
                    <?php if ($success): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <?= $success ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php elseif ($error): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <?= $error ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <form method="post" id="formKetidakhadiran" novalidate>
                        <div class="mb-3">
                            <label class="form-label">Jenis Pengguna</label>
                            <select name="jenis" id="jenis" class="form-select" required>
                                <option value="">-- Pilih Jenis --</option>
                                <option value="siswa">Siswa</option>
                                <option value="guru">Guru</option>
                            </select>
                        </div>

                        <div class="mb-3" id="siswaDiv">
                            <label class="form-label">Nama Siswa</label>
                            <select name="id_siswa" class="form-select select2" id="id_siswa">
                                <option value="">-- Pilih Siswa --</option>
                                <?php $siswa->data_seek(0); while ($row = $siswa->fetch_assoc()): ?>
                                    <option value="<?= $row['id'] ?>"><?= $row['nama_lengkap'] ?> - <?= $row['tingkat_rombel'] ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <div class="mb-3 d-none" id="guruDiv">
                            <label class="form-label">Nama Guru</label>
                            <select name="id_guru" class="form-select select2" id="id_guru">
                                <option value="">-- Pilih Guru --</option>
                                <?php $guru->data_seek(0); while ($row = $guru->fetch_assoc()): ?>
                                    <option value="<?= $row['id'] ?>"><?= $row['nama_lengkap'] ?> - <?= $row['jabatan'] ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <input type="hidden" name="id_pengguna" id="id_pengguna">

                        <div class="mb-3">
                            <label class="form-label">Tanggal</label>
                            <input type="date" name="tanggal" class="form-control" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Keterangan</label>
                            <select name="keterangan" class="form-select" required>
                                <option value="">-- Pilih --</option>
                                <option value="izin">Izin</option>
                                <option value="sakit">Sakit</option>
                                <option value="alpa">Alpa</option>
                                <option value="cuti">Cuti</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Alasan (Opsional)</label>
                            <textarea name="alasan" class="form-control" rows="2" placeholder="Tulis alasan jika ada..."></textarea>
                        </div>

                        <button type="submit" class="btn btn-primary">Simpan</button>
                        <a href="data-ketidakhadiran.php" class="btn btn-secondary">Kembali</a>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="footer">
        <p class="text-muted small">© 2025 Sekolah MIDU Grogol</p>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        $(document).ready(function () {
            // Inisialisasi Select2 hanya pada elemen yang terlihat
            function initSelect2() {
                $('.select2').each(function () {
                    if ($(this).closest('.d-none').length === 0) {
                        $(this).select2({
                            theme: 'bootstrap-5',
                            width: '100%',
                            placeholder: $(this).find('option:first').text(),
                            allowClear: true
                        });
                    }
                });
            }

            // Hancurkan Select2 pada elemen yang disembunyikan
            function destroySelect2() {
                $('.select2').each(function () {
                    if ($(this).data('select2')) {
                        $(this).select2('destroy');
                    }
                });
            }

            // Toggle jenis pengguna
            $('#jenis').on('change', function () {
                const jenis = $(this).val();
                destroySelect2(); // Hancurkan semua instance Select2
                if (jenis === 'siswa') {
                    $('#siswaDiv').removeClass('d-none');
                    $('#guruDiv').addClass('d-none');
                    $('#id_pengguna').val($('#id_siswa').val());
                } else if (jenis === 'guru') {
                    $('#guruDiv').removeClass('d-none');
                    $('#siswaDiv').addClass('d-none');
                    $('#id_pengguna').val($('#id_guru').val());
                } else {
                    $('#siswaDiv, #guruDiv').addClass('d-none');
                    $('#id_pengguna').val('');
                }
                initSelect2(); // Inisialisasi ulang Select2 pada elemen yang terlihat
            }).trigger('change');

            // Sinkronkan hidden input
            $('#id_siswa').on('change', function () {
                $('#id_pengguna').val($(this).val());
            });

            $('#id_guru').on('change', function () {
                $('#id_pengguna').val($(this).val());
            });

            // Validasi klien
            $('#formKetidakhadiran').on('submit', function (e) {
                const jenis = $('#jenis').val();
                const id_pengguna = $('#id_pengguna').val();
                const tanggal = $('input[name="tanggal"]').val();
                const keterangan = $('select[name="keterangan"]').val();
                if (!jenis || !id_pengguna || !tanggal || !keterangan) {
                    e.preventDefault();
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal!',
                        text: 'Semua field wajib diisi.',
                        toast: true,
                        position: 'top-end',
                        showConfirmButton: false,
                        timer: 3000
                    });
                }
            });

            // Alert auto-close
            setTimeout(() => {
                const alert = document.querySelector('.alert');
                if (alert) {
                    const bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
                    bsAlert.close();
                }
            }, 3000);

            // Toggle sidebar
            $('#toggleSidebarBtn').on('click', function () {
                $('.sidebar').toggleClass('collapsed active');
                $('#mainContent').toggleClass('expanded');
            });
        });
    </script>
</body>
</html>