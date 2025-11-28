<?php
session_start();
include '../../koneksi/koneksi.php';

// Hanya GUDANG yang boleh akses file ini
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'gudang') {
    http_response_code(403);
    echo "Akses ditolak (khusus gudang)";
    exit;
}

// Jika ada parameter aksi (ambil detail barang)
if (isset($_POST['aksi']) && $_POST['aksi'] === 'ambil') {

    if (!isset($_POST['id'])) {
        echo json_encode(['error' => 'ID tidak dikirim']);
        exit;
    }

    $id = (int)$_POST['id'];
    $q  = mysqli_query($conn, "SELECT * FROM barang WHERE id = $id");

    if ($row = mysqli_fetch_assoc($q)) {
        echo json_encode([
            'id'          => $row['id'],
            'nama_barang' => $row['nama_barang'],
            'jenis'       => $row['jenis'],
            'harga'       => (int)$row['harga'],
            'stok'        => (int)$row['stok'],
        ]);
    } else {
        echo json_encode(['error' => 'Data tidak ditemukan']);
    }

    exit;
}

// TANPA aksi → GUDANG hanya boleh UPDATE STOK (bukan tambah / hapus)
$id   = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$stok = isset($_POST['stok']) ? (int)$_POST['stok'] : 0;

if ($id <= 0) {
    echo "ID tidak valid";
    exit;
}

// Update hanya kolom STOK
$sql  = "UPDATE barang SET stok = ? WHERE id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "ii", $stok, $id);
mysqli_stmt_execute($stmt);

if (mysqli_stmt_affected_rows($stmt) >= 0) {
    echo "STOK_UPDATED";
} else {
    echo "Gagal mengubah stok";
}
