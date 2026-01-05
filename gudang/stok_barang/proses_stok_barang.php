<?php
session_start();
include '../../koneksi/koneksi.php';

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
    $q  = mysqli_query($conn, "SELECT * FROM stok_gudang WHERE Id_stok_gudang = $id");

    if ($row = mysqli_fetch_assoc($q)) {
        echo json_encode([
            'id'          => $row['Id_stok_gudang'],       
            'nama_barang' => $row['Nama_barang'],          
            'kategori'    => $row['Kategori'],            
            'jumlah_stok' => (int)$row['Jumlah_stok'],      
        ]);
    } else {
        echo json_encode(['error' => 'Data tidak ditemukan']);
    }

    exit;
}

$id   = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$stok = isset($_POST['stok']) ? (int)$_POST['stok'] : 0;

if ($id <= 0) {
    echo "ID tidak valid";
    exit;
}

// Update hanya kolom STOK
$sql  = "UPDATE stok_gudang SET Jumlah_stok = ? WHERE Id_stok_gudang = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "ii", $stok, $id);
mysqli_stmt_execute($stmt);

if (mysqli_stmt_affected_rows($stmt) >= 0) {
    echo "STOK_UPDATED";
} else {
    echo "Gagal mengubah stok";
}
