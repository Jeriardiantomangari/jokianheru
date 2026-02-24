-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Waktu pembuatan: 24 Feb 2026 pada 16.00
-- Versi server: 8.4.3
-- Versi PHP: 8.3.26

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Basis data: `db_dfcbaru`
--

-- --------------------------------------------------------

--
-- Struktur dari tabel `akun`
--

CREATE TABLE `akun` (
  `id_akun` int NOT NULL,
  `id_outlet` int DEFAULT NULL,
  `nama` varchar(15) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `username` varchar(15) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `password` varchar(15) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `role` enum('admin','kasir','owner','gudang') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data untuk tabel `akun`
--

INSERT INTO `akun` (`id_akun`, `id_outlet`, `nama`, `username`, `password`, `role`) VALUES
(4, NULL, 'Jeri Arianto2', 'owner', 'okedang', 'owner'),
(5, NULL, 'Nama Admin', 'admin', 'okedang', 'admin'),
(8, NULL, 'abdul j', 'gudang', 'okedang', 'gudang'),
(9, 1, 'ddfgdsg', 'kasir', 'okedang', 'kasir'),
(12, 5, 'abdul ', 'kasir2', 'okedang', 'kasir');

-- --------------------------------------------------------

--
-- Struktur dari tabel `bahan_masuk`
--

CREATE TABLE `bahan_masuk` (
  `Id_bahan_masuk` int NOT NULL,
  `Id_restok_bahan` int NOT NULL,
  `Nama_barang` varchar(100) DEFAULT NULL,
  `Jumlah_restok` int DEFAULT NULL,
  `Bahan_masuk` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data untuk tabel `bahan_masuk`
--

INSERT INTO `bahan_masuk` (`Id_bahan_masuk`, `Id_restok_bahan`, `Nama_barang`, `Jumlah_restok`, `Bahan_masuk`) VALUES
(7, 8, 'ikan', 120, 120),
(13, 14, 'ayam', 1, 1),
(23, 25, 'ikan', 20, 12),
(24, 27, 'ayam', 2, 2);

-- --------------------------------------------------------

--
-- Struktur dari tabel `barang`
--

CREATE TABLE `barang` (
  `id_barang` int NOT NULL,
  `id_kategori` int NOT NULL,
  `nama_barang` varchar(15) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `kategori` varchar(15) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `harga` int NOT NULL,
  `minimal_stok_gudang` int NOT NULL,
  `maksimal_stok_gudang` int NOT NULL DEFAULT '0',
  `minimal_stok_outlet` int NOT NULL,
  `maksimal_stok_outlet` int NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data untuk tabel `barang`
--

INSERT INTO `barang` (`id_barang`, `id_kategori`, `nama_barang`, `kategori`, `harga`, `minimal_stok_gudang`, `maksimal_stok_gudang`, `minimal_stok_outlet`, `maksimal_stok_outlet`) VALUES
(1, 1, 'ayam', '1', 100000, 60, 100, 60, 100),
(4, 1, 'ikan', '1', 200000, 60, 100, 60, 100),
(5, 1, 'garam', '1', 3000, 60, 100, 60, 100);

-- --------------------------------------------------------

--
-- Struktur dari tabel `barang_masuk`
--

CREATE TABLE `barang_masuk` (
  `Id_barang_masuk` int NOT NULL,
  `Id_restok_barang` int DEFAULT NULL,
  `Nama_barang` varchar(100) DEFAULT NULL,
  `Harga` decimal(10,2) DEFAULT NULL,
  `Jumlah_restok` int DEFAULT NULL,
  `Barang_masuk` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data untuk tabel `barang_masuk`
--

INSERT INTO `barang_masuk` (`Id_barang_masuk`, `Id_restok_barang`, `Nama_barang`, `Harga`, `Jumlah_restok`, `Barang_masuk`) VALUES
(15, 62, 'ayam', NULL, 12, 5),
(16, 63, 'garam', NULL, 10, 10);

-- --------------------------------------------------------

--
-- Struktur dari tabel `detail_penjualan`
--

CREATE TABLE `detail_penjualan` (
  `Id_detail_penjualan` int NOT NULL,
  `id_penjualan` int NOT NULL,
  `id_menu` int NOT NULL,
  `Nama_menu` varchar(100) DEFAULT NULL,
  `Harga` decimal(10,2) DEFAULT NULL,
  `Jumlah` int DEFAULT NULL,
  `Total` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data untuk tabel `detail_penjualan`
--

INSERT INTO `detail_penjualan` (`Id_detail_penjualan`, `id_penjualan`, `id_menu`, `Nama_menu`, `Harga`, `Jumlah`, `Total`) VALUES
(3, 3, 1, 'nasi ayam ', 15000.00, 2, 30000.00),
(4, 4, 1, 'nasi ayam ', 15000.00, 1, 15000.00),
(5, 5, 1, 'nasi ayam ', 15000.00, 1, 15000.00),
(6, 6, 1, 'nasi ayam ', 15000.00, 3, 45000.00),
(7, 7, 1, 'nasi ayam ', 15000.00, 55, 825000.00),
(8, 8, 1, 'nasi ayam ', 15000.00, 4, 60000.00),
(9, 9, 1, 'nasi ayam ', 15000.00, 3, 45000.00);

-- --------------------------------------------------------

--
-- Struktur dari tabel `kategori`
--

CREATE TABLE `kategori` (
  `id_kategori` int NOT NULL,
  `nama_kategori` varchar(15) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data untuk tabel `kategori`
--

INSERT INTO `kategori` (`id_kategori`, `nama_kategori`) VALUES
(1, 'bahankp');

-- --------------------------------------------------------

--
-- Struktur dari tabel `menu`
--

CREATE TABLE `menu` (
  `id_menu` int NOT NULL,
  `nama_menu` varchar(15) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `jenis` enum('Makanan','Minuman') NOT NULL,
  `harga` int NOT NULL,
  `gambar` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data untuk tabel `menu`
--

INSERT INTO `menu` (`id_menu`, `nama_menu`, `jenis`, `harga`, `gambar`) VALUES
(1, 'nasi ayam ', 'Makanan', 15000, '1771946157_fd844d23.jpeg');

-- --------------------------------------------------------

--
-- Struktur dari tabel `outlet`
--

CREATE TABLE `outlet` (
  `id_outlet` int NOT NULL,
  `nama_outlet` varchar(15) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `alamat` text CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data untuk tabel `outlet`
--

INSERT INTO `outlet` (`id_outlet`, `nama_outlet`, `alamat`) VALUES
(1, 'kelapa', 'jl kelapa 2'),
(5, 'sagu', 'ddd');

-- --------------------------------------------------------

--
-- Struktur dari tabel `penjualan`
--

CREATE TABLE `penjualan` (
  `id_penjualan` int NOT NULL,
  `id_outlet` int NOT NULL,
  `id_kasir` int NOT NULL,
  `tanggal` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `total_harga` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data untuk tabel `penjualan`
--

INSERT INTO `penjualan` (`id_penjualan`, `id_outlet`, `id_kasir`, `tanggal`, `total_harga`) VALUES
(3, 1, 9, '2026-01-03 05:12:13', 30000),
(4, 1, 9, '2026-01-03 06:02:19', 15000),
(5, 5, 10, '2026-01-03 13:49:50', 15000),
(6, 5, 10, '2026-01-03 13:58:41', 45000),
(7, 5, 10, '2026-01-04 12:52:15', 825000),
(8, 1, 9, '2026-02-22 17:52:26', 60000),
(9, 1, 9, '2026-02-24 15:13:49', 45000);

-- --------------------------------------------------------

--
-- Struktur dari tabel `restok_bahan_outlet`
--

CREATE TABLE `restok_bahan_outlet` (
  `Id_restok_bahan` int NOT NULL,
  `Id_outlet` int NOT NULL,
  `Id_stok_outlet` int NOT NULL,
  `Nama_barang` varchar(100) DEFAULT NULL,
  `Jumlah_restok` int DEFAULT NULL,
  `Status` varchar(50) DEFAULT NULL,
  `Catatan` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data untuk tabel `restok_bahan_outlet`
--

INSERT INTO `restok_bahan_outlet` (`Id_restok_bahan`, `Id_outlet`, `Id_stok_outlet`, `Nama_barang`, `Jumlah_restok`, `Status`, `Catatan`) VALUES
(8, 1, 4, 'ikan', 120, 'Selesai', NULL),
(14, 1, 3, 'ayam', 1, 'Selesai', NULL),
(25, 1, 4, 'ikan', 20, 'Selesai', NULL),
(27, 5, 6, 'ayam', 2, 'Selesai', 'stok belum tersedia'),
(28, 5, 6, 'ayam', 3, 'Dikirim', 'egeg');

-- --------------------------------------------------------

--
-- Struktur dari tabel `restok_barang`
--

CREATE TABLE `restok_barang` (
  `Id_restok_barang` int NOT NULL,
  `Id_stok_gudang` int NOT NULL,
  `Nama_barang` varchar(15) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `Harga` decimal(10,2) DEFAULT NULL,
  `Jumlah_restok` int DEFAULT NULL,
  `Total_harga` decimal(10,2) DEFAULT NULL,
  `Status` varchar(50) DEFAULT NULL,
  `Catatan` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data untuk tabel `restok_barang`
--

INSERT INTO `restok_barang` (`Id_restok_barang`, `Id_stok_gudang`, `Nama_barang`, `Harga`, `Jumlah_restok`, `Total_harga`, `Status`, `Catatan`) VALUES
(62, 5, 'ayam', 100000.00, 12, 1200000.00, 'Selesai', NULL),
(63, 6, 'garam', 3000.00, 10, 30000.00, 'Selesai', NULL);

-- --------------------------------------------------------

--
-- Struktur dari tabel `stok_gudang`
--

CREATE TABLE `stok_gudang` (
  `Id_stok_gudang` int NOT NULL,
  `id_barang` int NOT NULL,
  `Nama_barang` varchar(15) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `Kategori` varchar(15) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `Jumlah_stok` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data untuk tabel `stok_gudang`
--

INSERT INTO `stok_gudang` (`Id_stok_gudang`, `id_barang`, `Nama_barang`, `Kategori`, `Jumlah_stok`) VALUES
(4, 4, 'ikan', '1', 6),
(5, 1, 'ayam', '1', 752),
(6, 5, 'garam', '1', 10);

-- --------------------------------------------------------

--
-- Struktur dari tabel `stok_outlet`
--

CREATE TABLE `stok_outlet` (
  `Id_stok_outlet` int NOT NULL,
  `id_outlet` int NOT NULL,
  `id_barang` int NOT NULL,
  `Nama_barang` varchar(15) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `Kategori` varchar(15) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `Jumlah_stok` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data untuk tabel `stok_outlet`
--

INSERT INTO `stok_outlet` (`Id_stok_outlet`, `id_outlet`, `id_barang`, `Nama_barang`, `Kategori`, `Jumlah_stok`) VALUES
(3, 1, 1, 'ayam', '1', 66),
(4, 1, 4, 'ikan', '1', 342),
(5, 1, 5, 'garam', '1', 29),
(6, 5, 1, 'ayam', '1', 2),
(7, 5, 5, 'garam', '1', 0),
(8, 5, 4, 'ikan', '1', 0);

--
-- Indeks untuk tabel yang dibuang
--

--
-- Indeks untuk tabel `akun`
--
ALTER TABLE `akun`
  ADD PRIMARY KEY (`id_akun`),
  ADD KEY `id_outlet` (`id_outlet`);

--
-- Indeks untuk tabel `bahan_masuk`
--
ALTER TABLE `bahan_masuk`
  ADD PRIMARY KEY (`Id_bahan_masuk`),
  ADD KEY `bahan_masuk_ibfk_1` (`Id_restok_bahan`);

--
-- Indeks untuk tabel `barang`
--
ALTER TABLE `barang`
  ADD PRIMARY KEY (`id_barang`),
  ADD KEY `id_kategori` (`id_kategori`);

--
-- Indeks untuk tabel `barang_masuk`
--
ALTER TABLE `barang_masuk`
  ADD PRIMARY KEY (`Id_barang_masuk`),
  ADD KEY `barang_masuk_ibfk_1` (`Id_restok_barang`);

--
-- Indeks untuk tabel `detail_penjualan`
--
ALTER TABLE `detail_penjualan`
  ADD PRIMARY KEY (`Id_detail_penjualan`),
  ADD KEY `Id_menu` (`id_menu`),
  ADD KEY `detail_penjualan_ibfk_1` (`id_penjualan`);

--
-- Indeks untuk tabel `kategori`
--
ALTER TABLE `kategori`
  ADD PRIMARY KEY (`id_kategori`);

--
-- Indeks untuk tabel `menu`
--
ALTER TABLE `menu`
  ADD PRIMARY KEY (`id_menu`);

--
-- Indeks untuk tabel `outlet`
--
ALTER TABLE `outlet`
  ADD PRIMARY KEY (`id_outlet`);

--
-- Indeks untuk tabel `penjualan`
--
ALTER TABLE `penjualan`
  ADD PRIMARY KEY (`id_penjualan`),
  ADD KEY `id_outlet` (`id_outlet`),
  ADD KEY `id_kasir` (`id_kasir`);

--
-- Indeks untuk tabel `restok_bahan_outlet`
--
ALTER TABLE `restok_bahan_outlet`
  ADD PRIMARY KEY (`Id_restok_bahan`),
  ADD KEY `Id_stok_outlet` (`Id_stok_outlet`),
  ADD KEY `fk_restok_outlet_outlet` (`Id_outlet`);

--
-- Indeks untuk tabel `restok_barang`
--
ALTER TABLE `restok_barang`
  ADD PRIMARY KEY (`Id_restok_barang`),
  ADD KEY `Id_stok_gudang` (`Id_stok_gudang`);

--
-- Indeks untuk tabel `stok_gudang`
--
ALTER TABLE `stok_gudang`
  ADD PRIMARY KEY (`Id_stok_gudang`),
  ADD KEY `Id_barang` (`id_barang`);

--
-- Indeks untuk tabel `stok_outlet`
--
ALTER TABLE `stok_outlet`
  ADD PRIMARY KEY (`Id_stok_outlet`),
  ADD KEY `Id_barang` (`id_barang`),
  ADD KEY `id_outlet` (`id_outlet`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `akun`
--
ALTER TABLE `akun`
  MODIFY `id_akun` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT untuk tabel `bahan_masuk`
--
ALTER TABLE `bahan_masuk`
  MODIFY `Id_bahan_masuk` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT untuk tabel `barang`
--
ALTER TABLE `barang`
  MODIFY `id_barang` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT untuk tabel `barang_masuk`
--
ALTER TABLE `barang_masuk`
  MODIFY `Id_barang_masuk` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT untuk tabel `detail_penjualan`
--
ALTER TABLE `detail_penjualan`
  MODIFY `Id_detail_penjualan` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT untuk tabel `kategori`
--
ALTER TABLE `kategori`
  MODIFY `id_kategori` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT untuk tabel `menu`
--
ALTER TABLE `menu`
  MODIFY `id_menu` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT untuk tabel `outlet`
--
ALTER TABLE `outlet`
  MODIFY `id_outlet` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT untuk tabel `penjualan`
--
ALTER TABLE `penjualan`
  MODIFY `id_penjualan` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT untuk tabel `restok_bahan_outlet`
--
ALTER TABLE `restok_bahan_outlet`
  MODIFY `Id_restok_bahan` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT untuk tabel `restok_barang`
--
ALTER TABLE `restok_barang`
  MODIFY `Id_restok_barang` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=64;

--
-- AUTO_INCREMENT untuk tabel `stok_gudang`
--
ALTER TABLE `stok_gudang`
  MODIFY `Id_stok_gudang` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT untuk tabel `stok_outlet`
--
ALTER TABLE `stok_outlet`
  MODIFY `Id_stok_outlet` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- Ketidakleluasaan untuk tabel pelimpahan (Dumped Tables)
--

--
-- Ketidakleluasaan untuk tabel `akun`
--
ALTER TABLE `akun`
  ADD CONSTRAINT `akun_ibfk_1` FOREIGN KEY (`id_outlet`) REFERENCES `outlet` (`id_outlet`);

--
-- Ketidakleluasaan untuk tabel `bahan_masuk`
--
ALTER TABLE `bahan_masuk`
  ADD CONSTRAINT `bahan_masuk_ibfk_1` FOREIGN KEY (`Id_restok_bahan`) REFERENCES `restok_bahan_outlet` (`Id_restok_bahan`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_bahan_masuk_restok` FOREIGN KEY (`Id_restok_bahan`) REFERENCES `restok_bahan_outlet` (`Id_restok_bahan`) ON DELETE RESTRICT ON UPDATE CASCADE;

--
-- Ketidakleluasaan untuk tabel `barang`
--
ALTER TABLE `barang`
  ADD CONSTRAINT `barang_ibfk_1` FOREIGN KEY (`id_kategori`) REFERENCES `kategori` (`id_kategori`);

--
-- Ketidakleluasaan untuk tabel `barang_masuk`
--
ALTER TABLE `barang_masuk`
  ADD CONSTRAINT `barang_masuk_ibfk_1` FOREIGN KEY (`Id_restok_barang`) REFERENCES `restok_barang` (`Id_restok_barang`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `detail_penjualan`
--
ALTER TABLE `detail_penjualan`
  ADD CONSTRAINT `detail_penjualan_ibfk_1` FOREIGN KEY (`id_penjualan`) REFERENCES `penjualan` (`id_penjualan`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `detail_penjualan_ibfk_2` FOREIGN KEY (`id_menu`) REFERENCES `menu` (`id_menu`);

--
-- Ketidakleluasaan untuk tabel `restok_bahan_outlet`
--
ALTER TABLE `restok_bahan_outlet`
  ADD CONSTRAINT `fk_restok_outlet_outlet` FOREIGN KEY (`Id_outlet`) REFERENCES `outlet` (`id_outlet`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `restok_bahan_outlet_ibfk_1` FOREIGN KEY (`Id_outlet`) REFERENCES `outlet` (`id_outlet`),
  ADD CONSTRAINT `restok_bahan_outlet_ibfk_2` FOREIGN KEY (`Id_stok_outlet`) REFERENCES `stok_outlet` (`Id_stok_outlet`);

--
-- Ketidakleluasaan untuk tabel `restok_barang`
--
ALTER TABLE `restok_barang`
  ADD CONSTRAINT `restok_barang_ibfk_1` FOREIGN KEY (`Id_stok_gudang`) REFERENCES `stok_gudang` (`Id_stok_gudang`);

--
-- Ketidakleluasaan untuk tabel `stok_gudang`
--
ALTER TABLE `stok_gudang`
  ADD CONSTRAINT `stok_gudang_ibfk_1` FOREIGN KEY (`id_barang`) REFERENCES `barang` (`id_barang`);

--
-- Ketidakleluasaan untuk tabel `stok_outlet`
--
ALTER TABLE `stok_outlet`
  ADD CONSTRAINT `fk_stok_outlet_outlet` FOREIGN KEY (`id_outlet`) REFERENCES `outlet` (`id_outlet`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `stok_outlet_ibfk_1` FOREIGN KEY (`id_barang`) REFERENCES `barang` (`id_barang`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
