-- --------------------------------------------------------
-- Host:                         127.0.0.1
-- Server version:               10.4.34-MariaDB-log - mariadb.org binary distribution
-- Server OS:                    Win64
-- HeidiSQL Version:             12.1.0.6537
-- --------------------------------------------------------

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8 */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;


-- Dumping database structure for absensi_sekolah
CREATE DATABASE IF NOT EXISTS `absensi_sekolah` /*!40100 DEFAULT CHARACTER SET armscii8 COLLATE armscii8_bin */;
USE `absensi_sekolah`;

-- Dumping structure for table absensi_sekolah.admin
CREATE TABLE IF NOT EXISTS `admin` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=armscii8 COLLATE=armscii8_bin;

-- Dumping data for table absensi_sekolah.admin: ~2 rows (approximately)
INSERT INTO `admin` (`id`, `username`, `password`, `name`) VALUES
	(1, 'admin', '$2y$10$f2b3sdduJFk1cuuunnR/r.Nyi7bX5PoBONgprtAhGdUZabfTTPC4C', 'Administrator');

-- Dumping structure for table absensi_sekolah.guru
CREATE TABLE IF NOT EXISTS `guru` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nip` varchar(20) DEFAULT NULL,
  `nama_lengkap` varchar(100) NOT NULL,
  `nik` varchar(20) DEFAULT NULL,
  `tempat_lahir` varchar(50) DEFAULT NULL,
  `tanggal_lahir` date DEFAULT NULL,
  `jabatan` varchar(100) DEFAULT NULL,
  `jenis_kelamin` enum('L','P') DEFAULT NULL,
  `alamat` text DEFAULT NULL,
  `no_hp` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `foto` varchar(255) DEFAULT 'default.jpg',
  `qrcode` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `qrcode` (`qrcode`),
  UNIQUE KEY `nip` (`nip`)
) ENGINE=InnoDB AUTO_INCREMENT=42 DEFAULT CHARSET=armscii8 COLLATE=armscii8_bin;

-- Dumping data for table absensi_sekolah.guru: ~1 rows (approximately)
INSERT INTO `guru` (`id`, `nip`, `nama_lengkap`, `nik`, `tempat_lahir`, `tanggal_lahir`, `jabatan`, `jenis_kelamin`, `alamat`, `no_hp`, `email`, `foto`, `qrcode`, `created_at`) VALUES
	(41, '12345678', 'Ahmad Fauzi', '23456', 'Banyuwangi', '2025-07-17', 'Guru Kelas 3', 'L', 'Jl. MT. Haryono Gang Wirodipuro RT.15 RW.03 Kelurahan Kotakulon', '081234567890', 'fauzygm@gmail.com', '6878d341b48c2.jpeg', 'AF68662', '2025-07-17 10:41:05');

-- Dumping structure for table absensi_sekolah.jadwal
CREATE TABLE IF NOT EXISTS `jadwal` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `jenis_pengguna` enum('siswa','guru') NOT NULL,
  `hari` enum('Senin','Selasa','Rabu','Kamis','Jumat','Sabtu','Minggu') DEFAULT NULL,
  `jam_masuk` time DEFAULT NULL,
  `jam_pulang` time DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=armscii8 COLLATE=armscii8_bin;

-- Dumping data for table absensi_sekolah.jadwal: ~5 rows (approximately)
INSERT INTO `jadwal` (`id`, `jenis_pengguna`, `hari`, `jam_masuk`, `jam_pulang`) VALUES
	(2, 'siswa', 'Senin', '07:00:00', '13:00:00'),
	(3, 'siswa', 'Selasa', '07:00:00', '13:00:00'),
	(5, 'siswa', 'Rabu', '07:00:00', '13:00:00'),
	(6, 'siswa', 'Kamis', '07:00:00', '13:00:00'),
	(7, 'siswa', 'Jumat', '07:00:00', '11:00:00'),
	(8, 'siswa', 'Sabtu', '07:00:00', '13:00:00');

-- Dumping structure for table absensi_sekolah.ketidakhadiran
CREATE TABLE IF NOT EXISTS `ketidakhadiran` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tanggal` date NOT NULL,
  `id_siswa` int(11) DEFAULT NULL,
  `id_guru` int(11) DEFAULT NULL,
  `jenis_pengguna` enum('siswa','guru') NOT NULL,
  `keterangan` enum('izin','sakit','alpa','cuti') NOT NULL,
  `alasan` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=27 DEFAULT CHARSET=armscii8 COLLATE=armscii8_bin;

-- Dumping data for table absensi_sekolah.ketidakhadiran: ~0 rows (approximately)

-- Dumping structure for table absensi_sekolah.presensi
CREATE TABLE IF NOT EXISTS `presensi` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_siswa` int(11) DEFAULT NULL,
  `id_guru` int(11) DEFAULT NULL,
  `tanggal` date NOT NULL,
  `jam_masuk` time DEFAULT NULL,
  `status_masuk` varchar(50) DEFAULT NULL,
  `jam_pulang` time DEFAULT NULL,
  `status_pulang` varchar(50) DEFAULT NULL,
  `lokasi` varchar(100) DEFAULT NULL,
  `alamat` text DEFAULT NULL,
  `jenis_pengguna` enum('siswa','guru') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unik_presensi` (`tanggal`,`id_siswa`,`id_guru`)
) ENGINE=InnoDB AUTO_INCREMENT=33 DEFAULT CHARSET=armscii8 COLLATE=armscii8_bin;

-- Dumping data for table absensi_sekolah.presensi: ~0 rows (approximately)

-- Dumping structure for table absensi_sekolah.siswa
CREATE TABLE IF NOT EXISTS `siswa` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nisn` varchar(20) NOT NULL,
  `nama_lengkap` varchar(100) NOT NULL,
  `nik` varchar(20) DEFAULT NULL,
  `tempat_lahir` varchar(50) DEFAULT NULL,
  `tanggal_lahir` date DEFAULT NULL,
  `tingkat_rombel` varchar(20) DEFAULT NULL,
  `umur` varchar(50) DEFAULT NULL,
  `status` enum('aktif','non aktif') DEFAULT 'aktif',
  `jenis_kelamin` enum('L','P') DEFAULT NULL,
  `alamat` text DEFAULT NULL,
  `nama_ayah` varchar(100) DEFAULT NULL,
  `nama_ibu` varchar(100) DEFAULT NULL,
  `foto` varchar(255) DEFAULT 'default.png',
  `qrcode` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `nisn` (`nisn`),
  UNIQUE KEY `qrcode` (`qrcode`)
) ENGINE=InnoDB AUTO_INCREMENT=102 DEFAULT CHARSET=armscii8 COLLATE=armscii8_bin;

-- Dumping data for table absensi_sekolah.siswa: ~0 rows (approximately)
INSERT INTO `siswa` (`id`, `nisn`, `nama_lengkap`, `nik`, `tempat_lahir`, `tanggal_lahir`, `tingkat_rombel`, `umur`, `status`, `jenis_kelamin`, `alamat`, `nama_ayah`, `nama_ibu`, `foto`, `qrcode`, `created_at`) VALUES
	(1, '3172045378', 'AZALIN YIASHA YALIZHA', '3510175812170000', 'BANYUWANGI', '2017-12-18', 'Kelas 2', '610', 'aktif', 'P', 'Dsn. Jambean', 'Haeroni', 'ISWATI', 'default.png', 'AY15557', '2025-07-17 10:55:18'),
	(2, '3179054255', 'NAURA AZZAHWA JASMIN SALSABILAH', '3510174910170000', 'BANYUWANGI', '2017-10-09', 'Kelas 2', '70', 'aktif', 'P', 'Dsn. Jambean', 'Usman Effendi', 'MUTAMIMAH', 'default.png', 'NA92168', '2025-07-17 10:55:18'),
	(3, '3172872613', 'SUCI AZZAHRA', '3510175905170000', 'BANYUWANGI', '2017-05-19', 'Kelas 2', '75', 'aktif', 'P', 'Dsn. Pelinggihan GROGOL, GIRI, BANYUWANGI, JAWA TIMUR, 68451, 68451', 'Jaini', 'NURUL UMAH', 'default.png', 'SA65837', '2025-07-17 10:55:18'),
	(4, '3174820710', 'SITI UMAIROH', '3510176312170000', 'BANYUWANGI', '2017-12-23', 'Kelas 2', '610', 'aktif', 'P', 'Dsn. Pelinggihan', 'Sugiyanto', 'SITI MARIYAM', 'default.png', 'SU51182', '2025-07-17 10:55:18'),
	(5, '3171628338', 'SHOFI SALSABILA', '3510174202170000', 'BANYUWANGI', '2017-02-02', 'Kelas 2', '78', 'aktif', 'P', 'Dsn. Pelinggihan', 'Akromin', 'MUHAYAH', 'default.png', 'SS55821', '2025-07-17 10:55:18'),
	(6, '3178690226', 'SALSA DILLATUS SHOLIHA', '3510215012170000', 'BANYUWANGI', '2017-12-10', 'Kelas 2', '610', 'aktif', 'P', 'Dsn. Pelinggihan', '-', 'NUR ISTIQLA', 'default.png', 'SD68316', '2025-07-17 10:55:18'),
	(7, '3178074781', 'RATNA ANI LESTARI', '3510174607170000', 'BANYUWANGI', '2017-07-06', 'Kelas 2', '73', 'aktif', 'P', 'Dsn. Pelinggihan', 'Khodiri', 'ARMAWATI', 'default.png', 'RA26264', '2025-07-17 10:55:18'),
	(8, '3173266376', 'NATASYA AMELLIA PUTRI', '3510174604170000', 'BANYUWANGI', '2017-04-06', 'Kelas 2', '76', 'aktif', 'P', 'Dsn. Pelinggihan', 'Sunardi', 'FITRIYAH', 'default.png', 'NA50871', '2025-07-17 10:55:18'),
	(9, '3174903962', 'MUHAMMAD NURIL AL FARIZI', '3510171902170000', 'BANYUWANGI', '2017-02-19', 'Kelas 2', '78', 'aktif', 'L', 'Dsn. Pelinggihan', 'Ilham Noviantoni', 'NURONNIYAH', 'default.png', 'MN72877', '2025-07-17 10:55:18'),
	(10, '3186362078', 'LINTANG PUTRI LATIFAH', '3510175803180000', 'BANYUWANGI', '2018-03-18', 'Kelas 2', '67', 'aktif', 'P', ', , , , ,', 'Saifulloh', 'NISWATIN', 'default.png', 'LP65481', '2025-07-17 10:55:18'),
	(11, '3173201012', 'FAIRUZ SA\'ADA', '3510216001170000', 'BANYUWANGI', '2017-01-20', 'Kelas 2', '79', 'aktif', 'P', 'Dsn. Pelinggihan', 'Hendi Subyanto', 'NUR AINIYAH', 'default.png', 'FS81909', '2025-07-17 10:55:18'),
	(12, '3174965441', 'AVIKA LESTARI', '3510176904170000', 'BANYUWANGI', '2017-04-29', 'Kelas 2', '75', 'aktif', 'P', 'Dsn. Pelinggihan', 'Irwan Wahyudi', 'USWATUN HASANAH', 'default.png', 'AL54983', '2025-07-17 10:55:18'),
	(13, '3175715403', 'ARENDRA RAMDHAN', '3510172105170000', 'BANYUWANGI', '2017-05-21', 'Kelas 2', '75', 'aktif', 'L', 'Dsn. Guwo', 'Moch Yusuf S', 'NURAINIYAH', 'default.png', 'AR39025', '2025-07-17 10:55:18'),
	(14, '3173927640', 'AHMAD MUHAJIR', '3510170710170000', 'BANYUWANGI', '2017-10-07', 'Kelas 2', '70', 'aktif', 'L', 'Dsn. Guwo', 'Khoirul Alimin', 'NUR HAYATI', 'default.png', 'AM50938', '2025-07-17 10:55:18'),
	(15, '3174900758', 'ACH. ALI WAFA', '3510172505170000', 'BANYUWANGI', '2017-05-25', 'Kelas 2', '75', 'aktif', 'L', 'Dsn. Pelinggihan', 'H. Abdul Karim', 'ISNIYAH', 'default.png', 'AA48808', '2025-07-17 10:55:18'),
	(16, '3177401263', 'MH. KEFIN RENDI ALFINANTA', '3510171705170000', 'BANYUWANGI', '2017-05-17', 'Kelas 2', '75', 'aktif', 'L', 'Dsn. Pelinggihan', 'Abdullah', 'SAIFAL AINIYAH', 'default.png', 'MK23472', '2025-07-17 10:55:18'),
	(17, '3179286250', 'SYAKDIATUR RIZKA APRILIA', '3510176404170000', 'BANYUWANGI', '2017-04-24', 'Kelas 2', '76', 'aktif', 'P', 'Dsn. Jambean', 'PAESOLI', 'SANIYAH', 'default.png', 'SR34381', '2025-07-17 10:55:18'),
	(18, '3163561326', 'MAKAYLA YUMNA', '3510175504160000', 'BANYUWANGI', '2016-04-15', 'Kelas 3', '86', 'aktif', 'P', 'Pelinggihan GROGOL, GIRI, BANYUWANGI, JAWA TIMUR, 68425, 68425', 'M. Taufiq', 'HULLATUL WAQOR', 'default.png', 'MY21810', '2025-07-17 10:55:27'),
	(19, '3166053394', 'DIRA AZZAHRAH', '3510174105160000', 'BANYUWANGI', '2016-05-01', 'Kelas 3', '85', 'aktif', 'P', 'Pelinggihan GROGOL, GIRI, BANYUWANGI, JAWA TIMUR, 68425, 68425', 'Hadiri', 'NUR HAYATI', 'default.png', 'DA69924', '2025-07-17 10:55:27'),
	(20, '3161624296', 'NABIL MAHREZ AL.GHIFARY', '3510170106160000', 'BANYUWANGI', '2016-06-01', 'Kelas 3', '84', 'aktif', 'P', 'Lebak GROGOL, GIRI, BANYUWANGI, JAWA TIMUR, 68451, 68451', 'Abdul Mughis', 'MERNI BUDIYANTI', 'default.png', 'NM95105', '2025-07-17 10:55:27'),
	(21, '3163561029', 'MUHAMMAD RAFLI PRATAMA', '3510171707160000', 'BANYUWANGI', '2016-07-17', 'Kelas 3', '83', 'aktif', 'P', 'Guwo GROGOL, GIRI, BANYUWANGI, JAWA TIMUR, 68451, 68451', 'Munir', 'DESI MILTA', 'default.png', 'MR62459', '2025-07-17 10:55:27'),
	(22, '3155966715', 'MOCHAMMAD HAIKAL', '3510171612150000', 'BANYUWANGI', '2015-12-16', 'Kelas 3', '810', 'aktif', 'P', 'Laos GROGOL, GIRI, BANYUWANGI, JAWA TIMUR, 68451, 68451', 'Ilyas', 'PIPIT FITRIANI', 'default.png', 'MH55664', '2025-07-17 10:55:27'),
	(23, '3160745990', 'NURI IFTITATUL FIRSA', '3510174803160000', 'BANYUWANGI', '2016-03-08', 'Kelas 3', '87', 'aktif', 'P', 'Jambean JAMBESARI, GIRI, BANYUWANGI, JAWA TIMUR, 68454, 68454', 'Afandi', 'HERMAYUNITA', 'default.png', 'NI60262', '2025-07-17 10:55:27'),
	(24, '3174279606', 'MOHAMMAD NIZZAM MAULANA', '3510171902170000', 'BANYUWANGI', '2017-02-19', 'Kelas 3', '78', 'aktif', 'P', 'Pelinggihan GROGOL, GIRI, BANYUWANGI, JAWA TIMUR, 68425, 68425', 'Ahmad Dailami', 'EKA INAYATUR ROHMATILLAH', 'default.png', 'MN48528', '2025-07-17 10:55:27'),
	(25, '3168379162', 'MUHAMMAD ALMADANI ALVIANSYAH', '3510162407160000', 'BANYUWANGI', '2016-07-24', 'Kelas 3', '83', 'aktif', 'P', 'Pelinggihan Grogol GROGOL, GIRI, BANYUWANGI, JAWA TIMUR, 68425, 68425', 'Alvi Sahrin', 'SITI AISYAH', 'default.png', 'MA62113', '2025-07-17 10:55:27'),
	(26, '3160232100', 'AHMAD ALI ZAENAL MUTTAKIN', '3510170701160000', 'MAGELANG', '2016-01-07', 'Kelas 3', '89', 'aktif', 'P', 'Dsn. Laos GROGOL, GIRI, BANYUWANGI, JAWA TIMUR, 68425, 68425', 'Abdul Rosyad', 'MUHIMATUL ZAHRO', 'default.png', 'AA47498', '2025-07-17 10:55:27'),
	(27, '3168132005', 'NUR AINI', '3510174503160000', 'BANYUWANGI', '2016-03-05', 'Kelas 3', '87', 'aktif', 'P', 'Laos GROGOL, GIRI, BANYUWANGI, JAWA TIMUR, 68451, 68451', 'Rayis', 'SUMIYATI', 'default.png', 'NA47517', '2025-07-17 10:55:27'),
	(28, '3165422147', 'SAID AHMAD', '3510170203160000', 'BANYUWANGI', '2016-03-02', 'Kelas 3', '87', 'aktif', 'P', 'Krajan GROGOL, GIRI, BANYUWANGI, JAWA TIMUR, 68425, 68425', 'Ah Saihu', 'FARIKAH AGUSTIN MAIMUNAH', 'default.png', 'SA26055', '2025-07-17 10:55:27'),
	(29, '3164579434', 'AISYA KANAYA AZZAHRA', '3510175105160000', 'DENPASAR', '2016-05-11', 'Kelas 3', '85', 'aktif', 'P', 'Dsn Kedawung GROGOL, GIRI, BANYUWANGI, JAWA TIMUR, 68451, 68451', 'Moh. Makmuri', 'YATIM IKE WIJI HARTATIK', 'default.png', 'AK96804', '2025-07-17 10:55:27'),
	(30, '3164400982', 'MIRZA FIRDAUS', '3510171011160000', 'BANYUWANGI', '2016-11-10', 'Kelas 3', '711', 'aktif', 'P', 'Guwo GROGOL, GIRI, BANYUWANGI, JAWA TIMUR, 68451, 68451', 'Moh. Ashari', 'ISTIAROH', 'default.png', 'MF30697', '2025-07-17 10:55:27'),
	(31, '3174804805', 'UBAIDILAH ARROSYID', '3510172101170000', 'BANYUWANGI', '2017-01-21', 'Kelas 3', '79', 'aktif', 'P', 'Krajan GROGOL, GIRI, BANYUWANGI, JAWA TIMUR, 68425, 68425', 'Imam Jama\'sari', 'NURIS MARDHATILA', 'default.png', 'UA84390', '2025-07-17 10:55:27'),
	(32, '3166887526', 'AQILA NAZWA', '3510175707160000', 'BANYUWANGI', '2016-07-17', 'Kelas 3', '83', 'aktif', 'P', 'Dsn. Kedawung GROGOL, GIRI, BANYUWANGI, JAWA TIMUR, 68451, 68451', 'Moh. Makmuri', 'RUSDIANA NURCAHYANTI', 'default.png', 'AN71102', '2025-07-17 10:55:27'),
	(33, '3162875178', 'MOHAMAD DANU ALFIAN', '3510172810160000', 'BANYUWANGI', '2016-10-28', 'Kelas 3', '711', 'aktif', 'P', 'Dsn. Guwo GROGOL, GIRI, BANYUWANGI, JAWA TIMUR, 68451, 68451', 'MOHAMAD SUKRON HADI', 'ANA MARETA', 'default.png', 'MD21490', '2025-07-17 10:55:27'),
	(34, '3161166692', 'PUTRI AULIA RAMADANI', '3510174706160000', 'BANYUWANGI', '2016-06-07', 'Kelas 3', '84', 'aktif', 'P', 'Dsn. Pelinggihan RT.002/Rw.001 GROGOL, GIRI, BANYUWANGI, JAWA TIMUR, 68425, 68425', 'SUTARMAN', 'RULY HANDAYANI', 'default.png', 'PA49359', '2025-07-17 10:55:27'),
	(35, '3158186447', 'NADEA SABRINA HARI RAHMADANI', '3510176406150000', 'BANYUWANGI', '2015-06-24', 'Kelas 4', '94', 'aktif', 'P', 'DSN KEDAWUNG PONDOKNONGKO, KABAT, BANYUWANGI, JAWA TIMUR, 68461, 68461', 'MAHSUN HARIYANTO', 'DWI MARTA FITRIANI', 'default.png', 'NS80279', '2025-07-17 10:55:46'),
	(36, '3154624455', 'FARDAN RAFISKY AZZAM', '3510171908150000', 'BANYUWANGI', '2015-08-19', 'Kelas 4', '92', 'aktif', 'P', 'RT 002/001 Dsn. Pelinggihan GROGOL, GIRI, BANYUWANGI, JAWA TIMUR, 68451, 68451', 'Mulyadi', 'IIS DAMAYANTI', 'default.png', 'FR79651', '2025-07-17 10:55:46'),
	(37, '3153899339', 'DIRA KARTIKA IMRO\'ATUL AZIZA', '3510176709150000', 'BANYUWANGI', '2015-09-27', 'Kelas 4', '90', 'aktif', 'P', 'RT. 001/002 JAMBESARI, GIRI, BANYUWANGI, JAWA TIMUR, 68454, 68454', 'Sahroni', 'PITRIA PATMAWATI', 'default.png', 'DK31411', '2025-07-17 10:55:46'),
	(38, '3155349391', 'AHMAD MUARIFI', '3510170103150000', 'BANYUWANGI', '2015-03-01', 'Kelas 4', '97', 'aktif', 'L', 'RT 0002/001 Dsn. Pelinggihan GROGOL, GIRI, BANYUWANGI, JAWA TIMUR, 68451, 68451', 'Kastolani', 'NURIYAH', 'default.png', 'AM31226', '2025-07-17 10:55:46'),
	(39, '3161392143', 'RIFA NABILA PUTRI', '3510176403160000', 'BANYUWANGI', '2016-03-24', 'Kelas 4', '87', 'aktif', 'P', 'Lebak GROGOL, GIRI, BANYUWANGI, JAWA TIMUR, 68451, 68451', 'Mujiarto', 'JUMA\'ATI', 'default.png', 'RN11185', '2025-07-17 10:55:46'),
	(40, '3154980131', 'NABILA AMALIA PUTRI', '3510174612150000', 'BANYUWANGI', '2015-12-06', 'Kelas 4', '810', 'aktif', 'P', 'Guwo GROGOL, GIRI, BANYUWANGI, JAWA TIMUR, 68451, 68451', 'Abdul Azis', 'NURUL HARTATIK', 'default.png', 'NA55675', '2025-07-17 10:55:46'),
	(41, '0152093723', 'M. ALIEF ZAIDAN THOHIR', '3510170602150000', 'BANYUWANGI', '2015-02-06', 'Kelas 4', '98', 'aktif', 'L', 'Lebak GROGOL, GIRI, BANYUWANGI, JAWA TIMUR, 68451, 68451', 'Mohammad Soni', 'LINTA YUTIM', 'default.png', 'MA46856', '2025-07-17 10:55:46'),
	(42, '3158610805', 'SYAHRU REEZA AZZAM ROHMAD', '3510172301150000', 'BANYUWANGI', '2015-01-23', 'Kelas 4', '99', 'aktif', 'L', 'Lebak GROGOL, GIRI, BANYUWANGI, JAWA TIMUR, 68451, 68451', 'Harohmad', 'WIWIN RATNAWATI', 'default.png', 'SR46709', '2025-07-17 10:55:46'),
	(43, '3150527502', 'TASYA SALSABILLA', '3510175906150000', 'BANYUWANGI', '2015-06-19', 'Kelas 4', '94', 'aktif', 'P', 'Jambean JAMBESARI, GIRI, BANYUWANGI, JAWA TIMUR, 68454, 68454', 'Pipin Arista', 'ANISA UMROH', 'default.png', 'TS64550', '2025-07-17 10:55:46'),
	(44, '3155902397', 'DIAJENG NAILA WULANDARI', '3510176908150000', 'BANYUWANGI', '2015-08-29', 'Kelas 4', '91', 'aktif', 'P', 'Pelinggihan GROGOL, GIRI, BANYUWANGI, JAWA TIMUR, 68451, 68451', 'Sahroni', 'MAHBUBAH', 'default.png', 'DN24866', '2025-07-17 10:55:46'),
	(45, '3155474727', 'ADI PUTRA', '3510170901150000', 'BANYUWANGI', '2015-01-09', 'Kelas 4', '99', 'aktif', 'L', 'RT.003/001 Dsn. Pelinggihan GROGOL, GIRI, BANYUWANGI, JAWA TIMUR, 68425, 68425', 'Misradi', 'WARNI', 'default.png', 'AP48239', '2025-07-17 10:55:46'),
	(46, '3159775820', 'RISKA NAILA RISMA', '3510171403150000', 'BANYUWANGI', '2015-03-14', 'Kelas 4', '97', 'aktif', 'P', 'RT. 001/001 Dsn Pelinggihan GROGOL, GIRI, BANYUWANGI, JAWA TIMUR, 68425, 68425', 'Irsad', 'ISNAH', 'default.png', 'RN60860', '2025-07-17 10:55:46'),
	(47, '3157614562', 'FIRLY KHOIRUNISA HADIYONO', '3510175510150000', 'BANYUWANGI', '2015-10-15', 'Kelas 4', '90', 'aktif', 'P', 'Kedawung GROGOL, GIRI, BANYUWANGI, JAWA TIMUR, 68451, 68451', 'Wawan Hadiyono', 'ROMDANAH', 'default.png', 'FK32026', '2025-07-17 10:55:46'),
	(48, '3168040437', 'MOH. RIZKY AZZAM AL FATIH', '3510171202160000', 'BANYUWANGI', '2016-02-12', 'Kelas 4', '88', 'aktif', 'L', 'Guwo GROGOL, GIRI, BANYUWANGI, JAWA TIMUR, 68451, 68451', 'Moh. Hairun Nasiin', 'SANTI WAHYUNINGTIYAS', 'default.png', 'MR58033', '2025-07-17 10:55:46'),
	(49, '3145581455', 'DINO ALKADAFI', '3510172512140000', 'BANYUWANGI', '2014-12-25', 'Kelas 4', '910', 'aktif', 'L', 'RT. 003/001 Dsn. Pelinggihan GROGOL, GIRI, BANYUWANGI, JAWA TIMUR, 68451, 68451', 'Abdul Azis', 'ENDANG WATI', 'default.png', 'DA57919', '2025-07-17 10:55:46'),
	(50, '0158163933', 'AHMAD KUDUS', '3510172507150000', 'BANYUWANGI', '2015-07-25', 'Kelas 4', '93', 'aktif', 'L', 'Guwo GROGOL, GIRI, BANYUWANGI, JAWA TIMUR, 68425, 68425', 'Ahmad Samawi', 'KHOMSIYAH', 'default.png', 'AK64364', '2025-07-17 10:55:46'),
	(51, '3159983350', 'AMADANI', '3510172806150000', 'BANYUWANGI', '2015-06-28', 'Kelas 4', '93', 'aktif', 'L', 'RT 002/001 Dsn. Guwo GROGOL, GIRI, BANYUWANGI, JAWA TIMUR, 68451, 68451', 'Lukman', 'MAESAROH', 'default.png', 'AM70817', '2025-07-17 10:55:46'),
	(52, '3164817290', 'KAMILATUN NISA', '3510175204160000', 'BANYUWANGI', '2016-04-12', 'Kelas 4', '86', 'aktif', 'P', 'Guwo GROGOL, GIRI, BANYUWANGI, JAWA TIMUR, 68451, 68451', 'Sairoji', 'HUSNUL HOTIMAH', 'default.png', 'KN40174', '2025-07-17 10:55:46'),
	(53, '0158575496', 'AHD RIZKY', '3510171905150000', 'BANYUWANGI', '2015-05-19', 'Kelas 4', '95', 'aktif', 'L', 'Guwo RT 001/002 GROGOL, GIRI, BANYUWANGI, JAWA TIMUR, 68425, 68425', 'Sulaiman', 'SITI NUR ULYAH', 'default.png', 'AR24455', '2025-07-17 10:55:46'),
	(54, '3162413929', 'SITI AISYAH ZAHROH', '3510214903160000', 'BANYUWANGI', '2016-03-09', 'Kelas 4', '87', 'aktif', 'P', 'Dsn. Guwo GROGOL, GIRI, BANYUWANGI, JAWA TIMUR, 46824, 46824', 'Sutikno', 'MASRUROH', 'default.png', 'SA48486', '2025-07-17 10:55:46'),
	(55, '3157309281', 'AVIKA SHOHIFATUS SHOFFA', '3513036407150000', 'BANYUWNAGI', '2015-07-24', 'Kelas 4', '93', 'aktif', 'P', 'Guwo GROGOL, GIRI, BANYUWANGI, JAWA TIMUR, 68451, 68451', 'Samsuri', 'SHOLEHATIK', 'default.png', 'AS25808', '2025-07-17 10:55:46'),
	(56, '3158525522', 'ZASKIA KIRANA', '3510216209160000', 'BANYUWANGI', '2016-09-22', 'Kelas 4', '81', 'aktif', 'P', 'Padang Baru PESUCEN, KALIPURO, BANYUWANGI, JAWA TIMUR, 68423, 68423', 'M. Munir', 'YUNITA SARI', 'default.png', 'ZK66686', '2025-07-17 10:55:46'),
	(57, '3130715891', 'MUHAMMAD ARDI FIRMANSYAH', '3510170305130000', 'BANYUWANGI', '2013-05-03', 'Kelas 5', '113', 'aktif', 'P', 'RT/RW 01/02 Dsn Guwo GROGOL, GIRI, BANYUWANGI, JAWA TIMUR, 68425, 68425', 'ARSIKUM', 'CAMELIYA NUR JANNAH', 'default.png', 'MA23999', '2025-07-17 10:56:10'),
	(58, '3144061176', 'ALFIAN RIZKI', '3510172011140000', 'BANYUWANGI', '2014-11-20', 'Kelas 5', '98', 'aktif', 'P', 'Jl. Intan Permai Gg. Berlian No. 3 KEROBOKAN KELOD, KUTA UTARA, BADUNG, BALI, 80363, 80363', 'SISWANTO', 'TIKA SRI MULYANI', 'default.png', 'AR90382', '2025-07-17 10:56:10'),
	(59, '0142041468', 'ACHMAD RIVALDY', '3510172704140000', 'BANYUWANGI', '2014-04-27', 'Kelas 5', '103', 'aktif', 'P', 'Dsn. Rupi GROGOL, GIRI, BANYUWANGI, JAWA TIMUR, 68425, 68425', 'Heri Utomo', 'RITA MAESARO', 'default.png', 'AR99211', '2025-07-17 10:56:10'),
	(60, '0141165604', 'AISYILA NURMALIKA AZZAHRA', '3510176811140000', 'BANYUWANGI', '2014-11-28', 'Kelas 5', '98', 'aktif', 'P', 'Pelinggihan GROGOL, GIRI, BANYUWANGI, JAWA TIMUR, 46825, 46825', 'MOHLASIN', 'SITI AMALIYAH', 'default.png', 'AN29935', '2025-07-17 10:56:10'),
	(61, '0137026182', 'ANNISA KAMILA TRI NURJANNAH', '3573024412140000', 'MALANG', '2014-12-04', 'Kelas 5', '98', 'aktif', 'P', 'Krajan GROGOL, GIRI, BANYUWANGI, JAWA TIMUR, 68425, 68425', 'Dodik Hariono', 'SARIAH', 'default.png', 'AK92092', '2025-07-17 10:56:10'),
	(62, '0149474559', 'DEWI AGHITSNA PUTRI', '3510174405140000', 'BANYUWANGI', '2014-05-04', 'Kelas 5', '103', 'aktif', 'P', 'Dsn. Laos GROGOL, GIRI, BANYUWANGI, JAWA TIMUR, 68425, 68425', 'Supaat', 'SRI INDRAWATI', 'default.png', 'DA26139', '2025-07-17 10:56:10'),
	(63, '0154440912', 'JIHAN SYAFIRA', '3510176402150000', 'BANYUWANGI', '2015-02-24', 'Kelas 5', '95', 'aktif', 'P', 'Pelinggihan GROGOL, GIRI, BANYUWANGI, JAWA TIMUR, 68425, 68425', 'SAID', 'SITI PATMAH', 'default.png', 'JS36698', '2025-07-17 10:56:10'),
	(64, '0141987317', 'M. RAFA ABDULLOH', '3510170906140000', 'BANYUWANGI', '2014-06-09', 'Kelas 5', '102', 'aktif', 'P', 'Jambean JAMBESARI, GIRI, BANYUWANGI, JAWA TIMUR, 68454, 68454', 'KHOLIL', 'MUSPIROH', 'default.png', 'MR24897', '2025-07-17 10:56:10'),
	(65, '0141335873', 'AHMAD LUTFI', '3510171304140000', 'BANYUWANGI', '2014-04-13', 'Kelas 5', '104', 'aktif', 'P', 'Dusun Guwo GROGOL, GIRI, BANYUWANGI, JAWA TIMUR, 68425, 68425', 'Slamet', 'SITI HILMIYAH', 'default.png', 'AL90515', '2025-07-17 10:56:10'),
	(66, '0149006594', 'FAZA RAHMIKA RAMADANI', '3510174207140000', 'BANYUWANGI', '2014-07-02', 'Kelas 5', '101', 'aktif', 'P', 'Guwo GROGOL, GIRI, BANYUWANGI, JAWA TIMUR, 68451, 68451', 'Muhyarun', 'LISA RAHMAWATI', 'default.png', 'FR50379', '2025-07-17 10:56:10'),
	(67, '0142491737', 'HAFIZ FITRI', '3510176807140000', 'BANYUWANGI', '2014-07-28', 'Kelas 5', '100', 'aktif', 'P', 'Guwo GROGOL, GIRI, BANYUWANGI, JAWA TIMUR, 68425, 68425', 'Wahyudi', 'ROHIMAH', 'default.png', 'HF38095', '2025-07-17 10:56:10'),
	(68, '0147715669', 'FAJRA NADA NADIFA', '3510175608140000', 'BANYUWANGI', '2014-08-16', 'Kelas 5', '911', 'aktif', 'P', 'DSN KEDAWUNG GROGOL, GIRI, BANYUWANGI, JAWA TIMUR, 68451, 68451', 'MOH, SULIK', 'NURUL HIDAYAH', 'default.png', 'FN28791', '2025-07-17 10:56:10'),
	(69, '0142121166', 'JIHAN FAHIRA', '3510176311140000', 'BANYUWANGI', '2014-11-23', 'Kelas 5', '98', 'aktif', 'P', 'LINGK PAYAMAN GIRI, GIRI, BANYUWANGI, JAWA TIMUR, 68423, 68423', 'TAUFIK HIDAYAT', 'NURIS NURIYATI', 'default.png', 'JF38446', '2025-07-17 10:56:10'),
	(70, '0149923487', 'UMAIROH', '3510175206140000', 'BANYUWANGI', '2014-06-12', 'Kelas 5', '102', 'aktif', 'P', 'Guwo GROGOL, GIRI, BANYUWANGI, JAWA TIMUR, 46825, 46825', 'JAINAL', 'SUSIYATI', 'default.png', 'UM99508', '2025-07-17 10:56:10'),
	(71, '0144921285', 'WARDATUN NAUMI', '3510176701140000', 'BANYUWANGI', '2014-01-27', 'Kelas 5', '106', 'aktif', 'P', 'Pelinggihan GROGOL, GIRI, BANYUWANGI, JAWA TIMUR, 68425, 68425', 'SUTOMO', 'AMINAH', 'default.png', 'WN55633', '2025-07-17 10:56:10'),
	(72, '0145241679', 'RIRIN RISTIANI', '3510177010140000', 'BANYUWANGI', '2014-10-30', 'Kelas 5', '99', 'aktif', 'P', 'Desa jambesari dusun jambean JAMBESARI, GIRI, BANYUWANGI, JAWA TIMUR, 68424, 68424', 'Asrofi', 'YULI IKRIMAH', 'default.png', 'RR51906', '2025-07-17 10:56:10'),
	(73, '0154882606', 'NAYLA FEBY ANGGRAYNI', '3510174502150000', 'BANYUWANGI', '2015-02-05', 'Kelas 5', '96', 'aktif', 'P', 'Pelinggihan GROGOL, GIRI, BANYUWANGI, JAWA TIMUR, 68425, 68425', 'Dardiri', 'HERLINA', 'default.png', 'NF87982', '2025-07-17 10:56:10'),
	(74, '0145578483', 'MUHAMMAD IQBAL AL FARISI', '3510171109140000', 'BANYUWANGI', '2014-09-11', 'Kelas 5', '911', 'aktif', 'P', 'Desa jambesari dusun jambean JAMBESARI, GIRI, BANYUWANGI, JAWA TIMUR, 68425, 68425', 'Siswoyo', 'NUZULIA RAMADHANI', 'default.png', 'MI70048', '2025-07-17 10:56:10'),
	(75, '0141080146', 'MUHAMMAD ALI BASTHOMY', '3510171001140000', 'BANYUWANGI', '2014-01-10', 'Kelas 5', '107', 'aktif', 'P', 'Dsn. Guwo GROGOL, GIRI, BANYUWANGI, JAWA TIMUR, 68425, 68425', 'Isnaini', 'INAYAH', 'default.png', 'MA47314', '2025-07-17 10:56:10'),
	(76, '0144977106', 'MUHAMMAD AKBAR', '3510171701140000', 'BANYUWANGI', '2014-01-17', 'Kelas 5', '106', 'aktif', 'P', 'Pelinggihan GROGOL, GIRI, BANYUWANGI, JAWA TIMUR, 68425, 68425', 'Maskuri', 'NURIYATIM', 'default.png', 'MA52266', '2025-07-17 10:56:10'),
	(77, '0149879010', 'MOHAMMAD YUSUF', '3510171603140000', 'BANYUWANGI', '2014-03-16', 'Kelas 5', '104', 'aktif', 'P', 'Pelinggihan GROGOL, GIRI, BANYUWANGI, JAWA TIMUR, 68425, 68425', 'IMAM AHMADI', 'ALIYANAH', 'default.png', 'MY75355', '2025-07-17 10:56:10'),
	(78, '3145429317', 'SINAR MUTIARA TAQWA', '3510176005140000', 'BANYUWANGI', '2014-05-20', 'Kelas 5', '102', 'aktif', 'P', 'Krajan GROGOL, GIRI, BANYUWANGI, JAWA TIMUR, 68451, 68451', 'Ismanto', 'RIANAH', 'default.png', 'SM63465', '2025-07-17 10:56:10'),
	(79, '3141268786', 'BINAR MUTIARA TAQWA', '3510176005140000', 'BANYUWANGI', '2014-05-20', 'Kelas 5', '102', 'aktif', 'P', 'Dsn Krajan GROGOL, GIRI, BANYUWANGI, JAWA TIMUR, 68451, 68451', 'ISMANTO', 'RIANAH', 'default.png', 'BM76629', '2025-07-17 10:56:10'),
	(80, '0148733963', 'MUHAMAD FAREL WIJAYA', '3315042106140000', 'GROBOGAN', '2014-06-21', 'Kelas 5', '101', 'aktif', 'P', 'Grogol GROGOL, GIRI, BANYUWANGI, JAWA TIMUR, 46824, 46824', 'Darmanto', 'ENDANG RAHAYU', 'default.png', 'MF43950', '2025-07-17 10:56:10'),
	(81, '0143474431', 'RAFA FIRJATUL AZZARIAH', '3510175503140000', 'BANYUWANGI', '2014-03-15', 'Kelas 5', '104', 'aktif', 'P', 'Guwo GROGOL, GIRI, BANYUWANGI, JAWA TIMUR, 68425, 68425', 'M. Sahri', 'HUSNUL KHOTIMAH', 'default.png', 'RF93951', '2025-07-17 10:56:10'),
	(82, '0147600757', 'MOH. SYARIF HASAN', '3510160607140000', 'BANYUWANGI', '2014-07-06', 'Kelas 5', '101', 'aktif', 'P', 'Guwo GROGOL, GIRI, BANYUWANGI, JAWA TIMUR, 68425, 68425', 'Nono Irawan', 'MAULIDAH', 'default.png', 'MS26180', '2025-07-17 10:56:10'),
	(83, '0131989848', 'MIKHAIRA AZ ZAHRA RAYA ANGGRAINI', '3510175708130000', 'BANYUWANGI', '2013-08-17', 'Kelas 6', '1011', 'aktif', 'P', 'RT/RW 01/01 Dsn Guwo GROGOL, GIRI, BANYUWANGI, JAWA TIMUR, 68425, 68425', 'USMAN EFENDI', 'MUTAMIMAH', 'default.png', 'MA56792', '2025-07-17 10:56:42'),
	(84, '0136848058', 'MUHAMMAD ARJUNNAJA', '3510172510130000', 'BANYUWANGI', '2013-10-25', 'Kelas 6', '109', 'aktif', 'P', 'RT/RW 02/01 Dsn Pelinggihan GROGOL, GIRI, BANYUWANGI, JAWA TIMUR, 68425, 68425', 'ASLANI', 'DEWI HUMAIROH', 'default.png', 'MA36078', '2025-07-17 10:56:42'),
	(85, '0134304837', 'KUNI CAHYATUN NUFUS', '3510174511130000', 'BANYUWANGI', '2013-11-05', 'Kelas 6', '109', 'aktif', 'P', 'RT/RW 02/01 Dsn Pelinggihan GROGOL, GIRI, BANYUWANGI, JAWA TIMUR, 68425, 68425', 'GOZALI', 'MAULIDAH', 'default.png', 'KC18786', '2025-07-17 10:56:42'),
	(86, '0129585362', 'HILYATUL AULIA', '3518054807130000', 'NGANJUK', '2013-07-08', 'Kelas 6', '111', 'aktif', 'P', 'RT/RW 02/02 Dsn Grogol GROGOL, GIRI, BANYUWANGI, JAWA TIMUR, 68425, 68425', 'AH.SAIHU', 'FARIKAH AGUSTIN MAIMUNAH', 'default.png', 'HA44728', '2025-07-17 10:56:42'),
	(87, '0134642955', 'HOLIFATUL JANNAH', '3510175207130000', 'BANYUWANGI', '2013-07-12', 'Kelas 6', '111', 'aktif', 'P', 'RT/RW 01/01 PELINGGIHAN GROGOL, GIRI, BANYUWANGI, JAWA TIMUR, 68425, 68425', 'AHMAD SYUJA', 'HAMDA ROYANI', 'default.png', 'HJ10036', '2025-07-17 10:56:42'),
	(88, '0133084390', 'IQBAL SYUBBAN HALABI', '3510173005130000', 'BANYUWANGI', '2013-05-30', 'Kelas 6', '112', 'aktif', 'P', 'RT/RW 03/01 Dsn Pelinggihan GROGOL, GIRI, BANYUWANGI, JAWA TIMUR, 68425, 68425', 'ZURKONI', 'EKA HANDAYANI', 'default.png', 'IS65252', '2025-07-17 10:56:42'),
	(89, '0143893080', 'YUDHA SAPUTRA', '3510171210140000', 'BANYUWANGI', '2014-10-12', 'Kelas 6', '910', 'aktif', 'P', 'RT/RW 02/01 Dsn Laos GROGOL, GIRI, BANYUWANGI, JAWA TIMUR, 68425, 68425', 'NURUL HUDA', 'SUCIAT ININGSIH', 'default.png', 'YS29855', '2025-07-17 10:56:42'),
	(90, '0133310095', 'SYAHRIL BAARI', '3510212211130000', 'BANYUWANGI', '2013-11-22', 'Kelas 6', '108', 'aktif', 'P', 'RT/RW 01/01 Dsn Padangbaru PESUCEN, KALIPURO, BANYUWANGI, JAWA TIMUR, 68455, 68455', 'SAIPUL BAHRI', 'SUSIYATI', 'default.png', 'SB57194', '2025-07-17 10:56:42'),
	(91, '0137362691', 'ABDUL MALIK', '3510173112130000', 'BANYUWANGI', '2013-12-31', 'Kelas 6', '107', 'aktif', 'P', 'RT/RW 02/01 Dsn Pelinggihan GROGOL, GIRI, BANYUWANGI, JAWA TIMUR, 68425, 68425', 'LUKMAN', 'HARTILAH', 'default.png', 'AM19682', '2025-07-17 10:56:42'),
	(92, '0132943497', 'MOHAMMAD FIRDAUS', '3510171804130000', 'BANYUWANGI', '2013-04-18', 'Kelas 6', '113', 'aktif', 'P', 'RT/RW 03/01 Dsn Pelinggihan GROGOL, GIRI, BANYUWANGI, JAWA TIMUR, 68425, 68425', 'LUKMANUL HAKIM', 'JAMILA', 'default.png', 'MF76259', '2025-07-17 10:56:42'),
	(93, '0136686409', 'MUHAMMAD HAIKAL KAFILI', '3510172905130000', 'BANYUWANGI', '2013-05-29', 'Kelas 6', '112', 'aktif', 'P', 'RT/RW 03/01 Dsn Guwo GROGOL, GIRI, BANYUWANGI, JAWA TIMUR, 68425, 68425', 'IMAM FAUZI', 'HUSNUT TAQIYAH', 'default.png', 'MH58601', '2025-07-17 10:56:42'),
	(94, '0138189652', 'MUHAMMAD ULUL AZMI', '3510171906130000', 'BANYUWANGI', '2013-06-19', 'Kelas 6', '111', 'aktif', 'P', 'RT/RW 02/01 Dsn Lebak GROGOL, GIRI, BANYUWANGI, JAWA TIMUR, 68425, 68425', 'SLAMET SUYITNO', 'YULIATIN', 'default.png', 'MU66656', '2025-07-17 10:56:42'),
	(95, '0136448779', 'AHMAD QURUNUL BAHRI', '3510173010130000', 'BANYUWANGI', '2013-10-30', 'Kelas 6', '109', 'aktif', 'P', 'RT/RW 01/01 Dsn Laos GROGOL, GIRI, BANYUWANGI, JAWA TIMUR, 68425, 68425', 'NURIYONO', 'SULASTRI', 'default.png', 'AQ32117', '2025-07-17 10:56:42'),
	(96, '0131594703', 'MOHAMAD RENDI ALFARIS', '3510142606130000', 'BANYUWANGI', '2013-06-26', 'Kelas 6', '111', 'aktif', 'P', 'RT/RW 04/02 Dsn Jambean JAMBESARI, GIRI, BANYUWANGI, JAWA TIMUR, 68425, 68425', 'TUHAINI', 'SUCI ILMA WAHYUNI', 'default.png', 'MR28128', '2025-07-17 10:56:42'),
	(97, '0142857989', 'ABDUL FATAH', '3510171103140000', 'BANYUWANGI', '2014-03-11', 'Kelas 6', '105', 'aktif', 'P', 'RT/RW 02/02 Dsn Guwo GROGOL, GIRI, BANYUWANGI, JAWA TIMUR, 68425, 68425', 'YOSI WINARTO', 'HUSNIYAH', 'default.png', 'AF30617', '2025-07-17 10:56:42'),
	(98, '0133213144', 'MUHAMMAD NAUVAL ARUM ASABIL', '3510172708130000', 'BANYUWANGI', '2013-08-27', 'Kelas 6', '1011', 'aktif', 'P', 'RT/RW 03/01 Dsn Pelinggihan GROGOL, GIRI, BANYUWANGI, JAWA TIMUR, 68425, 68425', 'ABDUL MUNIR', 'RETNO WULANDARI', 'default.png', 'MN37174', '2025-07-17 10:56:42'),
	(99, '0133241995', 'SELAMET RIZKI', '3510171610130000', 'BANYUWANGI', '2013-10-16', 'Kelas 6', '109', 'aktif', 'P', 'RT/RW 04/01 Dsn Pelinggihan GROGOL, GIRI, BANYUWANGI, JAWA TIMUR, 68425, 68425', 'SAMSUL HADI', 'SUPI\'AH', 'default.png', 'SR86634', '2025-07-17 10:56:42'),
	(100, '0133063316', 'AHMAD ALWI', '3510172404130000', 'BANYUWANGI', '2013-04-24', 'Kelas 6', '113', 'aktif', 'P', 'GUWO GROGOL, GIRI, BANYUWANGI, JAWA TIMUR, 68451, 68451', 'KHOIRUL ALIMIN', 'NUR HAYATI', 'default.png', 'AA39406', '2025-07-17 10:56:42'),
	(101, '0135015748', 'ADIB RIZALUL BANI', '3510170403130000', 'BANYUWANGI', '2013-03-04', 'Kelas 6', '115', 'aktif', 'P', 'JAMBEAN JAMBESARI, GIRI, BANYUWANGI, JAWA TIMUR, 68425, 68425', 'MUALIP', 'NUR AZIZAH', 'default.png', 'AR89790', '2025-07-17 10:56:42');

/*!40103 SET TIME_ZONE=IFNULL(@OLD_TIME_ZONE, 'system') */;
/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IFNULL(@OLD_FOREIGN_KEY_CHECKS, 1) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40111 SET SQL_NOTES=IFNULL(@OLD_SQL_NOTES, 1) */;
