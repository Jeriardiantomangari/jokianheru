-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Waktu pembuatan: 28 Nov 2025 pada 12.01
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
-- Basis data: `db_dfc`
--

-- --------------------------------------------------------

--
-- Struktur dari tabel `ajukan_stok`
--

CREATE TABLE `ajukan_stok` (
  `id` int NOT NULL,
  `id_barang` int NOT NULL,
  `nama_barang` varchar(100) NOT NULL,
  `harga` int NOT NULL,
  `jumlah_restok` int NOT NULL,
  `total_harga` int NOT NULL,
  `status` enum('Menunggu','Disetujui','Ditolak','Selesai') NOT NULL DEFAULT 'Menunggu',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data untuk tabel `ajukan_stok`
--

INSERT INTO `ajukan_stok` (`id`, `id_barang`, `nama_barang`, `harga`, `jumlah_restok`, `total_harga`, `status`, `created_at`, `updated_at`) VALUES
(5, 1, 'ayam', 30000, 200, 6000000, 'Selesai', '2025-11-28 00:52:16', '2025-11-28 00:53:51');

-- --------------------------------------------------------

--
-- Struktur dari tabel `ajukan_stok_outlet`
--

CREATE TABLE `ajukan_stok_outlet` (
  `id` int NOT NULL,
  `id_outlet` int NOT NULL,
  `id_barang` int NOT NULL,
  `nama_barang` varchar(100) NOT NULL,
  `harga` int NOT NULL,
  `jumlah_restok` int NOT NULL,
  `total_harga` int NOT NULL,
  `status` enum('Menunggu','Disetujui','Ditolak','Dikirim','Selesai') NOT NULL DEFAULT 'Menunggu',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `barang`
--

CREATE TABLE `barang` (
  `id` int NOT NULL,
  `nama_barang` varchar(100) NOT NULL,
  `jenis` varchar(50) NOT NULL,
  `harga` int NOT NULL,
  `stok` int NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data untuk tabel `barang`
--

INSERT INTO `barang` (`id`, `nama_barang`, `jenis`, `harga`, `stok`) VALUES
(1, 'ayam', 'bahan', 30000, 356),
(2, 'garam', 'bumbu', 5000, 53);

-- --------------------------------------------------------

--
-- Struktur dari tabel `menu_makanan`
--

CREATE TABLE `menu_makanan` (
  `id` int NOT NULL,
  `nama_menu` varchar(100) NOT NULL,
  `kategori` enum('Makanan','Minuman') NOT NULL,
  `harga` int NOT NULL,
  `gambar` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data untuk tabel `menu_makanan`
--

INSERT INTO `menu_makanan` (`id`, `nama_menu`, `kategori`, `harga`, `gambar`) VALUES
(1, 'nasi ayam ', 'Makanan', 15000, '1764160691_180caff7.jpeg'),
(2, 'nas i ikan', 'Makanan', 20000, NULL),
(3, 'nasi kecap', 'Makanan', 25000, NULL),
(4, 'nasi goreng', 'Makanan', 23000, NULL),
(5, 'nasi campur ', 'Makanan', 25000, NULL),
(6, 'es teh', 'Minuman', 5000, NULL),
(7, 'es coklat ', 'Minuman', 5000, NULL),
(8, 'es kopi', 'Minuman', 5000, NULL),
(9, 'es susu ', 'Minuman', 5000, NULL),
(10, 'es campur', 'Minuman', 5000, NULL);

-- --------------------------------------------------------

--
-- Struktur dari tabel `outlet`
--

CREATE TABLE `outlet` (
  `id` int NOT NULL,
  `nama_outlet` varchar(100) NOT NULL,
  `alamat` text
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data untuk tabel `outlet`
--

INSERT INTO `outlet` (`id`, `nama_outlet`, `alamat`) VALUES
(1, 'Outlet 1', 'Jl. Contoh No. 1'),
(2, 'Outlet 2', 'Jl. Contoh No. 2');

-- --------------------------------------------------------

--
-- Struktur dari tabel `pengguna`
--

CREATE TABLE `pengguna` (
  `id` int NOT NULL,
  `nama` varchar(100) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(100) NOT NULL,
  `role` enum('admin','gudang','kasir','owner') NOT NULL DEFAULT 'admin',
  `id_outlet` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data untuk tabel `pengguna`
--

INSERT INTO `pengguna` (`id`, `nama`, `username`, `password`, `role`, `id_outlet`) VALUES
(2, 'Jeri Arianto', 'admin', 'okedang', 'admin', NULL),
(3, 'abdul ', 'gudang', 'okedang', 'gudang', NULL),
(4, 'abdul j', 'kasir', 'okedang', 'kasir', 1),
(6, 'abdul d', 'owner', 'okedang', 'owner', NULL);

-- --------------------------------------------------------

--
-- Struktur dari tabel `penjualan`
--

CREATE TABLE `penjualan` (
  `id` int NOT NULL,
  `tanggal` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `id_kasir` int NOT NULL,
  `id_outlet` int DEFAULT NULL,
  `total_harga` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data untuk tabel `penjualan`
--

INSERT INTO `penjualan` (`id`, `tanggal`, `id_kasir`, `id_outlet`, `total_harga`) VALUES
(14, '2025-11-27 16:41:20', 4, 1, 15000),
(15, '2025-11-27 16:42:06', 4, 1, 45000);

-- --------------------------------------------------------

--
-- Struktur dari tabel `penjualan_detail`
--

CREATE TABLE `penjualan_detail` (
  `id` int NOT NULL,
  `id_penjualan` int NOT NULL,
  `id_menu` int NOT NULL,
  `nama_menu` varchar(100) NOT NULL,
  `harga` int NOT NULL,
  `jumlah` int NOT NULL,
  `subtotal` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data untuk tabel `penjualan_detail`
--

INSERT INTO `penjualan_detail` (`id`, `id_penjualan`, `id_menu`, `nama_menu`, `harga`, `jumlah`, `subtotal`) VALUES
(2, 14, 1, 'nasi ayam ', 15000, 1, 15000),
(3, 15, 1, 'nasi ayam ', 15000, 1, 15000),
(4, 15, 5, 'nasi campur ', 25000, 1, 25000),
(5, 15, 9, 'es susu ', 5000, 1, 5000);

-- --------------------------------------------------------

--
-- Struktur dari tabel `stok_outlet`
--

CREATE TABLE `stok_outlet` (
  `id` int NOT NULL,
  `id_outlet` int NOT NULL,
  `id_barang` int NOT NULL,
  `stok` int NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data untuk tabel `stok_outlet`
--

INSERT INTO `stok_outlet` (`id`, `id_outlet`, `id_barang`, `stok`) VALUES
(5, 1, 1, 45),
(6, 1, 2, 5);

--
-- Indeks untuk tabel yang dibuang
--

--
-- Indeks untuk tabel `ajukan_stok`
--
ALTER TABLE `ajukan_stok`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_ajukan_barang` (`id_barang`);

--
-- Indeks untuk tabel `ajukan_stok_outlet`
--
ALTER TABLE `ajukan_stok_outlet`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_ajukan_outlet_barang` (`id_barang`),
  ADD KEY `fk_ajukan_outlet_outlet` (`id_outlet`);

--
-- Indeks untuk tabel `barang`
--
ALTER TABLE `barang`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `menu_makanan`
--
ALTER TABLE `menu_makanan`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `outlet`
--
ALTER TABLE `outlet`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `pengguna`
--
ALTER TABLE `pengguna`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD KEY `fk_pengguna_outlet` (`id_outlet`);

--
-- Indeks untuk tabel `penjualan`
--
ALTER TABLE `penjualan`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_penjualan_kasir` (`id_kasir`),
  ADD KEY `fk_penjualan_outlet` (`id_outlet`);

--
-- Indeks untuk tabel `penjualan_detail`
--
ALTER TABLE `penjualan_detail`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_detail_penjualan` (`id_penjualan`),
  ADD KEY `fk_detail_menu` (`id_menu`);

--
-- Indeks untuk tabel `stok_outlet`
--
ALTER TABLE `stok_outlet`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_outlet_barang` (`id_outlet`,`id_barang`),
  ADD KEY `fk_stok_outlet_barang` (`id_barang`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `ajukan_stok`
--
ALTER TABLE `ajukan_stok`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT untuk tabel `ajukan_stok_outlet`
--
ALTER TABLE `ajukan_stok_outlet`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT untuk tabel `barang`
--
ALTER TABLE `barang`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT untuk tabel `menu_makanan`
--
ALTER TABLE `menu_makanan`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT untuk tabel `outlet`
--
ALTER TABLE `outlet`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT untuk tabel `pengguna`
--
ALTER TABLE `pengguna`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT untuk tabel `penjualan`
--
ALTER TABLE `penjualan`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT untuk tabel `penjualan_detail`
--
ALTER TABLE `penjualan_detail`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT untuk tabel `stok_outlet`
--
ALTER TABLE `stok_outlet`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Ketidakleluasaan untuk tabel pelimpahan (Dumped Tables)
--

--
-- Ketidakleluasaan untuk tabel `ajukan_stok`
--
ALTER TABLE `ajukan_stok`
  ADD CONSTRAINT `fk_ajukan_barang` FOREIGN KEY (`id_barang`) REFERENCES `barang` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `ajukan_stok_outlet`
--
ALTER TABLE `ajukan_stok_outlet`
  ADD CONSTRAINT `fk_ajukan_outlet_barang` FOREIGN KEY (`id_barang`) REFERENCES `barang` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_ajukan_outlet_outlet` FOREIGN KEY (`id_outlet`) REFERENCES `outlet` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `pengguna`
--
ALTER TABLE `pengguna`
  ADD CONSTRAINT `fk_pengguna_outlet` FOREIGN KEY (`id_outlet`) REFERENCES `outlet` (`id`) ON DELETE SET NULL;

--
-- Ketidakleluasaan untuk tabel `penjualan`
--
ALTER TABLE `penjualan`
  ADD CONSTRAINT `fk_penjualan_kasir` FOREIGN KEY (`id_kasir`) REFERENCES `pengguna` (`id`) ON DELETE RESTRICT,
  ADD CONSTRAINT `fk_penjualan_outlet` FOREIGN KEY (`id_outlet`) REFERENCES `outlet` (`id`) ON DELETE SET NULL;

--
-- Ketidakleluasaan untuk tabel `penjualan_detail`
--
ALTER TABLE `penjualan_detail`
  ADD CONSTRAINT `fk_detail_menu` FOREIGN KEY (`id_menu`) REFERENCES `menu_makanan` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_detail_penjualan` FOREIGN KEY (`id_penjualan`) REFERENCES `penjualan` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `stok_outlet`
--
ALTER TABLE `stok_outlet`
  ADD CONSTRAINT `fk_stok_outlet_barang` FOREIGN KEY (`id_barang`) REFERENCES `barang` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_stok_outlet_outlet` FOREIGN KEY (`id_outlet`) REFERENCES `outlet` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
