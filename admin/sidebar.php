<!-- sidebar.php -->
<div class="sidebar" id="sidebar">
    <img src="../assets/img/logo.png" alt="MIDU Logo" class="logo">
    <h4 class="text-center mb-4">MIDU Presensi</h4>
    <ul class="nav flex-column">
        <li class="nav-item">
            <a class="nav-link <?= ($activePage == 'dashboard') ? 'active' : '' ?>" href="dashboard.php">
                <i class="fas fa-tachometer-alt"></i> Dashboard
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= ($activePage == 'data-siswa') ? 'active' : '' ?>" href="data-siswa.php">
                <i class="fas fa-user-graduate"></i> Data Siswa
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= ($activePage == 'data-guru') ? 'active' : '' ?>" href="data-guru.php">
                <i class="fas fa-chalkboard-teacher"></i> Data Guru
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= ($activePage == 'data-jadwal') ? 'active' : '' ?>" href="data-jadwal.php">
                <i class="fas fa-calendar"></i> Data Jadwal
            </a>
        </li>
       
        <li class="nav-item">
            <a class="nav-link <?= ($activePage == 'presensi') ? 'active' : '' ?>" href="presensi.php">
                <i class="fas fa-calendar-check"></i> Data Kehadiran
            </a>
        </li>
        
        <li class="nav-item">
            <a class="nav-link <?= ($activePage == 'data-ketidakhadiran') ? 'active' : '' ?>" href="data-ketidakhadiran.php">
                <i class="fas fa-calendar-check"></i> Data Ketidakhadiran
            </a>
        </li>

        <li class="nav-item">
            <a class="nav-link <?= ($activePage == 'data-presensi') ? 'active' : '' ?>" href="data-presensi.php">
                <i class="fas fa-calendar-check"></i> Rekap Presensi
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= ($activePage == 'manage_admins') ? 'active' : '' ?>" href="manage_admins.php">
                <i class="fas fa-user-cog"></i> Manajemen Admin
            </a>
        </li>
      
        <li class="nav-item">
            <a class="nav-link <?= ($activePage == 'logout') ? 'active' : '' ?>" href="logout.php">
                <i class="fa fa-sign-out"></i> logout
            </a>
        </li>
    </ul>
</div>
