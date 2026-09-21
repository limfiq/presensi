<?php
session_start();
require_once 'config.php';
if (!isset($_SESSION['admin'])) {
  header("Location: login.php");
  exit;
}

// Inisialisasi pesan
$error = '';
$success = '';

// Ambil ID admin dari URL
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
  header("Location: manage_admins.php");
  exit;
}
$admin_id = (int)$_GET['id'];

// Ambil data admin
$stmt = $conn->prepare("SELECT * FROM admin WHERE id = ?");
$stmt->bind_param("i", $admin_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
  header("Location: manage_admins.php");
  exit;
}
$admin = $result->fetch_assoc();
$stmt->close();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_admin'])) {
  $username = trim($_POST['username']);
  $password = trim($_POST['password']);
  $confirm_password = trim($_POST['confirm_password']);

  // Validasi input
  if (empty($username)) {
    $error = 'Username harus diisi.';
  } elseif (strlen($username) < 4 || strlen($username) > 50) {
    $error = 'Username harus antara 4 hingga 50 karakter.';
  } elseif (!empty($password) && strlen($password) < 8) {
    $error = 'Kata sandi baru harus minimal 8 karakter.';
  } elseif (!empty($password) && $password !== $confirm_password) {
    $error = 'Kata sandi dan konfirmasi kata sandi tidak cocok.';
  } else {
    // Periksa apakah username sudah digunakan oleh admin lain
    $stmt = $conn->prepare("SELECT id FROM admin WHERE username = ? AND id != ?");
    $stmt->bind_param("si", $username, $admin_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
      $error = 'Username sudah digunakan.';
    } else {
      // Update admin
      if (!empty($password)) {
        // Jika kata sandi diisi, hash kata sandi baru
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE admin SET username = ?, password = ? WHERE id = ?");
        $stmt->bind_param("ssi", $username, $hashed_password, $admin_id);
      } else {
        // Jika kata sandi kosong, pertahankan kata sandi lama
        $stmt = $conn->prepare("UPDATE admin SET username = ? WHERE id = ?");
        $stmt->bind_param("si", $username, $admin_id);
      }
      if ($stmt->execute()) {
        $success = 'Data admin berhasil diperbarui. <a href="manage_admins.php" class="login-link">Kembali ke daftar admin</a>.';
      } else {
        $error = 'Gagal memperbarui data: ' . $stmt->error;
      }
    }
    $stmt->close();
  }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Edit Admin - Sistem Presensi MIDU</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
  <style>
    body {
      background: linear-gradient(135deg, #f1f5f9 0%, #e2e8f0 100%);
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }
    .edit-container {
      max-width: 400px;
      width: 100%;
      padding: 20px;
    }
    .card {
      border: none;
      border-radius: 15px;
      box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
      overflow: hidden;
    }
    .card-header {
      background-color: #2c6e49;
      color: white;
      text-align: center;
      padding: 20px;
      border-bottom: none;
    }
    .card-body {
      padding: 30px;
    }
    .form-control {
      border-radius: 8px;
      padding: 10px;
      transition: border-color 0.3s ease;
    }
    .form-control:focus {
      border-color: #2c6e49;
      box-shadow: 0 0 5px rgba(44, 110, 73, 0.3);
    }
    .btn-primary {
      background-color: #2c6e49;
      border: none;
      border-radius: 8px;
      padding: 12px;
      font-weight: 500;
      transition: background-color 0.3s ease;
    }
    .btn-primary:hover {
      background-color: #3d8b5e;
    }
    .btn-secondary {
      background-color: #6c757d;
      border: none;
      border-radius: 8px;
      padding: 12px;
      font-weight: 500;
    }
    .btn-secondary:hover {
      background-color: #5a6268;
    }
    .input-group-text {
      background-color: #f8f9fa;
      border-radius: 8px 0 0 8px;
      border-right: none;
    }
    .logo {
      max-width: 100px;
      margin-bottom: 20px;
    }
    .alert {
      border-radius: 8px;
    }
    .show-password {
      cursor: pointer;
      user-select: none;
    }
    .login-link {
      color: #2c6e49;
      text-decoration: none;
      font-weight: 500;
    }
    .login-link:hover {
      text-decoration: underline;
    }
    @media (max-width: 576px) {
      .edit-container {
        padding: 15px;
      }
      .card-body {
        padding: 20px;
      }
    }
  </style>
</head>
<body>
  <div class="edit-container">
    <div class="card">
      <div class="card-header">
        <img src="../assets/img/logo.png" alt="Logo Sekolah" class="logo mx-auto d-block">
        <h4 class="mb-0">Edit Admin - Sistem Presensi MIDU</h4>
      </div>
      <div class="card-body">
        <?php if (!empty($error)): ?>
          <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($error) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
          </div>
        <?php endif; ?>
        <?php if (!empty($success)): ?>
          <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?= $success ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
          </div>
        <?php endif; ?>
        <form id="editAdminForm" method="post">
          <div class="mb-3">
            <label for="name" class="form-label">Nama Lengkap</label>
            <div class="input-group">
              <span class="input-group-text"><i class="fas fa-user"></i></span>
              <input type="text" name="name" id="name" class="form-control"  value="<?= htmlspecialchars($admin['name']) ?>" required>
            </div>
          </div>
          <div class="mb-3">
            <label for="username" class="form-label">Username</label>
            <div class="input-group">
              <span class="input-group-text"><i class="fas fa-user"></i></span>
              <input type="text" name="username" id="username" class="form-control" required
                     value="<?= htmlspecialchars($admin['username']) ?>"
                     placeholder="Masukkan username (4-50 karakter)" aria-describedby="usernameHelp">
            </div>
          </div>
          <div class="mb-3">
            <label for="password" class="form-label"><small>Kata Sandi Baru (kosongkan jika tidak diubah)</small></label>
            <div class="input-group">
              <span class="input-group-text"><i class="fas fa-lock"></i></span>
              <input type="password" name="password" id="password" class="form-control"
                     placeholder="Minimal 8 karakter" aria-describedby="passwordHelp">
              <span class="input-group-text show-password" onclick="togglePassword('password', 'toggleIcon')">
                <i class="fas fa-eye" id="toggleIcon"></i>
              </span>
            </div>
          </div>
          <div class="mb-3">
            <label for="confirm_password" class="form-label">Konfirmasi Kata Sandi</label>
            <div class="input-group">
              <span class="input-group-text"><i class="fas fa-lock"></i></span>
              <input type="password" name="confirm_password" id="confirm_password" class="form-control"
                     placeholder="Ulangi kata sandi" aria-describedby="confirmPasswordHelp">
              <span class="input-group-text show-password" onclick="togglePassword('confirm_password', 'toggleConfirmIcon')">
                <i class="fas fa-eye" id="toggleConfirmIcon"></i>
              </span>
            </div>
          </div>
          <div class="d-flex gap-2">
            <button type="submit" name="edit_admin" class="btn btn-primary w-50">Simpan</button>
            <a href="manage_admins.php" class="btn btn-secondary w-50">Kembali</a>
          </div>
        </form>
      </div>
    </div>
    <p class="text-center mt-3 text-muted small">© 2025 MIDU Grogol</p>
  </div>

  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    function togglePassword(inputId, toggleIconId) {
      const passwordInput = document.getElementById(inputId);
      const toggleIcon = document.getElementById(toggleIconId);
      if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        toggleIcon.classList.remove('fa-eye');
        toggleIcon.classList.add('fa-eye-slash');
      } else {
        passwordInput.type = 'password';
        toggleIcon.classList.remove('fa-eye-slash');
        toggleIcon.classList.add('fa-eye');
      }
    }
  </script>
</body>
</html>