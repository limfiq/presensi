<?php
session_start();
require_once 'config.php';

// Redirect jika sudah login
if (isset($_SESSION['admin'])) {
  header("Location: dashboard.php");
  exit;
}

// Inisialisasi pesan kesalahan
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
  $username = trim($_POST['username']);
  $password = trim($_POST['password']);

  // Validasi input
  if (empty($username) || empty($password)) {
    $error = 'Username dan password harus diisi.';
  } else {
    // Gunakan prepared statement untuk mencegah SQL injection
    $stmt = $conn->prepare("SELECT id, username, password FROM admin WHERE username = ?");
    if (!$stmt) {
      $error = 'Kesalahan database: ' . $conn->error;
    } else {
      $stmt->bind_param("s", $username);
      if (!$stmt->execute()) {
        $error = 'Gagal menjalankan query: ' . $stmt->error;
      } else {
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
          $admin = $result->fetch_assoc();
          // Periksa password dengan password_verify atau fallback MD5
          if (password_verify($password, $admin['password']) || $admin['password'] === md5($password)) {
            $_SESSION['admin'] = [
              'id' => $admin['id'],
              'username' => $admin['username']
            ];
            // Debugging: Periksa sesi
            // var_dump($_SESSION); exit;
            // Jika menggunakan MD5, perbarui ke password_hash
            if ($admin['password'] === md5($password)) {
              $hashed_password = password_hash($password, PASSWORD_DEFAULT);
              $update_stmt = $conn->prepare("UPDATE admin SET password = ? WHERE id = ?");
              $update_stmt->bind_param("si", $hashed_password, $admin['id']);
              $update_stmt->execute();
              $update_stmt->close();
            }
            header("Location: dashboard.php");
            exit;
          } else {
            $error = 'Username atau password salah.';
          }
        } else {
          $error = 'Username tidak ditemukan.';
        }
      }
      $stmt->close();
    }
  }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login Admin - Sistem Presensi MIDU</title>
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
    .login-container {
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
    .input-group-text {
      background-color: #f8f9fa;
      border-radius: 8px 0 0 8px;
      border-right: none;
    }
    .logo {
      max-width: 85px;
      margin-bottom: 20px;
    }
    .alert {
      border-radius: 8px;
    }
    .show-password {
      cursor: pointer;
      user-select: none;
    }
    .register-link {
      color: #2c6e49;
      text-decoration: none;
      font-weight: 500;
    }
    .register-link:hover {
      text-decoration: underline;
    }
    @media (max-width: 576px) {
      .login-container {
        padding: 15px;
      }
      .card-body {
        padding: 20px;
      }
    }
  </style>
</head>
<body>
  <div class="login-container">
    <div class="card">
      <div class="card-header">
        <img src="../assets/img/logo.png" alt="Logo Sekolah" class="logo mx-auto d-block">
        <h4 class="mb-0">Login Admin - Sistem Presensi MIDU</h4>
      </div>
      <div class="card-body">
        <?php if (!empty($error)): ?>
          <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($error) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
          </div>
        <?php endif; ?>
        <form id="loginForm" method="post">
          <div class="mb-3">
            <label for="username" class="form-label">Username</label>
            <div class="input-group">
              <span class="input-group-text"><i class="fas fa-user"></i></span>
              <input type="text" name="username" id="username" class="form-control" required
                     placeholder="Masukkan username" aria-describedby="usernameHelp">
            </div>
          </div>
          <div class="mb-3">
            <label for="password" class="form-label">Password</label>
            <div class="input-group">
              <span class="input-group-text"><i class="fas fa-lock"></i></span>
              <input type="password" name="password" id="password" class="form-control" required
                     placeholder="Masukkan password" aria-describedby="passwordHelp">
              <span class="input-group-text show-password" onclick="togglePassword()">
                <i class="fas fa-eye" id="toggleIcon"></i>
              </span>
            </div>
          </div>
          <button type="submit" name="login" class="btn btn-primary w-100" id="loginBtn">Login</button>
          <div class="text-center mt-3">
            <a href="register.php" class="register-link">Belum punya akun? Daftar di sini</a>
          </div>
        </form>
      </div>
    </div>
    <p class="text-center mt-3 text-muted small">© 2025 MIDU Grogol</p>
  </div>

  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    function togglePassword() {
      const passwordInput = document.getElementById('password');
      const toggleIcon = document.getElementById('toggleIcon');
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