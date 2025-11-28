<?php
session_start();
include '../../koneksi/koneksi.php';

// Cek role gudang (kalau login sudah jalan)
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'gudang') {
    // http_response_code(403);
    // echo "Akses ditolak";
    // exit;
}

// ------------------------
// AMBIL DATA SATU PENGAJUAN (UNTUK EDIT)
// ------------------------
if (isset($_POST['aksi']) && $_POST['aksi'] === 'ambil') {
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    if ($id <= 0) {
        echo json_encode(['error' => 'ID tidak valid']);
        exit;
    }

    $sql = "SELECT * FROM ajukan_stok WHERE id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);

    if (!$res || mysqli_num_rows($res) === 0) {
        echo json_encode(['error' => 'Data pengajuan tidak ditemukan']);
        exit;
    }

    $row = mysqli_fetch_assoc($res);

    // Hanya boleh diedit jika status Menunggu / Ditolak
    if (!in_array($row['status'], ['Menunggu','Ditolak'])) {
        echo json_encode(['error' => 'Pengajuan ini tidak dapat diedit (status sudah '.$row['status'].')']);
        exit;
    }

    echo json_encode([
        'id'            => (int)$row['id'],
        'id_barang'     => (int)$row['id_barang'],
        'nama_barang'   => $row['nama_barang'],
        'harga'         => (int)$row['harga'],
        'jumlah_restok' => (int)$row['jumlah_restok'],
        'total_harga'   => (int)$row['total_harga'],
        'status'        => $row['status'],
    ]);
    exit;
}

// ------------------------
// HAPUS PENGAJUAN
// ------------------------
if (isset($_POST['aksi']) && $_POST['aksi'] === 'hapus') {
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    if ($id <= 0) {
        echo "ID tidak valid";
        exit;
    }

    // Cek status dulu
    $cek = mysqli_query($conn, "SELECT status FROM ajukan_stok WHERE id = $id");
    if (!$cek || mysqli_num_rows($cek) === 0) {
        echo "Data pengajuan tidak ditemukan";
        exit;
    }
    $row = mysqli_fetch_assoc($cek);

    // Boleh hapus jika: Menunggu, Ditolak, Selesai
    // Tidak boleh hapus jika: Disetujui (masih proses, stok belum ditambah)
    if (!in_array($row['status'], ['Menunggu','Ditolak','Selesai'])) {
        echo "Pengajuan ini tidak bisa dihapus (status sekarang: ".$row['status'].")";
        exit;
    }

    mysqli_query($conn, "DELETE FROM ajukan_stok WHERE id = $id");
    if (mysqli_affected_rows($conn) > 0) {
        echo "Pengajuan berhasil dihapus.";
    } else {
        echo "Gagal menghapus pengajuan.";
    }
    exit;
}

// ------------------------
// AKSI SELESAI (gudang sudah beli barang)
// ------------------------
if (isset($_POST['aksi']) && $_POST['aksi'] === 'selesai') {
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

    if ($id <= 0) {
        echo "ID pengajuan tidak valid";
        exit;
    }

    // Ambil data pengajuan
    $sqlSel = "SELECT id_barang, jumlah_restok, status 
               FROM ajukan_stok 
               WHERE id = ?";
    $stmtSel = mysqli_prepare($conn, $sqlSel);
    mysqli_stmt_bind_param($stmtSel, "i", $id);
    mysqli_stmt_execute($stmtSel);
    $resSel = mysqli_stmt_get_result($stmtSel);

    if (!$resSel || mysqli_num_rows($resSel) === 0) {
        echo "Pengajuan tidak ditemukan";
        exit;
    }

    $row = mysqli_fetch_assoc($resSel);
    $id_barang      = (int)$row['id_barang'];
    $jumlah_restok  = (int)$row['jumlah_restok'];
    $status_lama    = $row['status'];

    // Hanya boleh selesai jika sudah DISETUJUI oleh owner
    if ($status_lama !== 'Disetujui') {
        echo "Pengajuan ini belum disetujui owner atau sudah selesai/dibatalkan.";
        exit;
    }

    // Tambah stok barang
    $sqlStok = "UPDATE barang 
                SET stok = stok + ? 
                WHERE id = ?";
    $stmtStok = mysqli_prepare($conn, $sqlStok);
    mysqli_stmt_bind_param($stmtStok, "ii", $jumlah_restok, $id_barang);
    mysqli_stmt_execute($stmtStok);

    // Ubah status jadi Selesai
    $statusBaru = 'Selesai';
    $sqlUpd = "UPDATE ajukan_stok SET status = ? WHERE id = ?";
    $stmtUpd = mysqli_prepare($conn, $sqlUpd);
    mysqli_stmt_bind_param($stmtUpd, "si", $statusBaru, $id);
    mysqli_stmt_execute($stmtUpd);

    echo "Pengajuan selesai. Stok barang sudah ditambah.";
    exit;
}

// ------------------------
// INSERT / UPDATE PENGAJUAN BARU
// ------------------------
$id_ajukan      = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$id_barang      = isset($_POST['id_barang']) ? (int)$_POST['id_barang'] : 0;
$jumlah_restok  = isset($_POST['jumlah_restok']) ? (int)$_POST['jumlah_restok'] : 0;

if ($id_barang <= 0 || $jumlah_restok <= 0) {
    echo "Data pengajuan tidak lengkap";
    exit;
}

// Ambil nama & harga dari tabel barang (supaya aman, tidak dari input user)
$sqlB = "SELECT nama_barang, harga FROM barang WHERE id = ?";
$stmtB = mysqli_prepare($conn, $sqlB);
mysqli_stmt_bind_param($stmtB, "i", $id_barang);
mysqli_stmt_execute($stmtB);
$resB = mysqli_stmt_get_result($stmtB);

if (!$resB || mysqli_num_rows($resB) === 0) {
    echo "Barang tidak ditemukan";
    exit;
}

$barang      = mysqli_fetch_assoc($resB);
$nama_barang = $barang['nama_barang'];
$harga       = (int)$barang['harga'];
$total_harga = $harga * $jumlah_restok;

// Jika id_ajukan > 0 → UPDATE (hanya jika status Menunggu/Ditolak)
if ($id_ajukan > 0) {
    $cek = mysqli_query($conn, "SELECT status FROM ajukan_stok WHERE id = $id_ajukan");
    if (!$cek || mysqli_num_rows($cek) === 0) {
        echo "Pengajuan tidak ditemukan";
        exit;
    }
    $row = mysqli_fetch_assoc($cek);
    if (!in_array($row['status'], ['Menunggu','Ditolak'])) {
        echo "Pengajuan ini tidak dapat diubah (status sudah ".$row['status'].")";
        exit;
    }

    $sqlUpd = "UPDATE ajukan_stok 
               SET id_barang = ?, 
                   nama_barang = ?, 
                   harga = ?, 
                   jumlah_restok = ?, 
                   total_harga = ? 
               WHERE id = ?";
    $stmtUpd = mysqli_prepare($conn, $sqlUpd);
    mysqli_stmt_bind_param($stmtUpd, "isiiii", $id_barang, $nama_barang, $harga, $jumlah_restok, $total_harga, $id_ajukan);
    mysqli_stmt_execute($stmtUpd);

    if (mysqli_stmt_affected_rows($stmtUpd) >= 0) {
        echo "Pengajuan restok berhasil diubah.";
    } else {
        echo "Tidak ada perubahan pada pengajuan.";
    }
    exit;
}

// Jika id_ajukan == 0 → INSERT baru (status awal: Menunggu)
$sqlIns = "INSERT INTO ajukan_stok (id_barang, nama_barang, harga, jumlah_restok, total_harga, status)
           VALUES (?,?,?,?,?, 'Menunggu')";
$stmtIns = mysqli_prepare($conn, $sqlIns);
mysqli_stmt_bind_param($stmtIns, "isiii", $id_barang, $nama_barang, $harga, $jumlah_restok, $total_harga);
mysqli_stmt_execute($stmtIns);

if (mysqli_stmt_affected_rows($stmtIns) > 0) {
    echo "Pengajuan restok berhasil dibuat, menunggu persetujuan owner.";
} else {
    echo "Gagal menyimpan pengajuan restok.";
}
