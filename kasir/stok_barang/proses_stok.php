<?php
include '../../koneksi/koneksi.php';

// ==== AMBIL DATA UNTUK MODAL ====
if (isset($_POST['aksi']) && $_POST['aksi'] == 'ambil') {
    $id = (int)$_POST['id'];

    $sql = "
        SELECT so.id,
               so.stok,
               b.nama_barang,
               b.jenis
        FROM stok_outlet so
        JOIN barang b ON so.id_barang = b.id
        WHERE so.id = $id
        LIMIT 1
    ";
    $q = mysqli_query($conn, $sql);

    if (!$q || mysqli_num_rows($q) == 0) {
        echo json_encode(['error' => 'Data stok tidak ditemukan']);
        exit;
    }

    $row = mysqli_fetch_assoc($q);
    echo json_encode($row);
    exit;
}

// ==== SIMPAN PEMAKAIAN (KURANGI STOK) ====
$id               = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$jumlah_digunakan = isset($_POST['jumlah_digunakan']) ? (int)$_POST['jumlah_digunakan'] : 0;

if ($id <= 0 || $jumlah_digunakan <= 0) {
    echo "Data tidak valid.";
    exit;
}

// Ambil stok sekarang
$q = mysqli_query($conn, "SELECT stok FROM stok_outlet WHERE id = $id LIMIT 1");
if (!$q || mysqli_num_rows($q) == 0) {
    echo "Data stok tidak ditemukan.";
    exit;
}

$row         = mysqli_fetch_assoc($q);
$stokSekarang = (int)$row['stok'];

// Cek apakah cukup
if ($jumlah_digunakan > $stokSekarang) {
    echo "Jumlah yang digunakan melebihi stok yang tersedia ($stokSekarang).";
    exit;
}

$stokBaru = $stokSekarang - $jumlah_digunakan;

// Update stok
$update = mysqli_query($conn, "UPDATE stok_outlet SET stok = $stokBaru WHERE id = $id");

if ($update) {
    echo "Stok berhasil dikurangi. Sisa stok: $stokBaru";
} else {
    echo "Gagal mengupdate stok.";
}
?>
