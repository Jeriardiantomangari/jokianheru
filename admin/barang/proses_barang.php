<?php
include '../../koneksi/koneksi.php';

// ===============================
// AMBIL DATA SATU BARANG (UNTUK EDIT)
// ===============================
if (isset($_POST['aksi']) && $_POST['aksi'] === 'ambil') {
    $id = (int)($_POST['id'] ?? 0);

    $q = mysqli_query($conn, "SELECT * FROM barang WHERE id_barang = $id");
    if (!$q) {
        echo json_encode(["error" => mysqli_error($conn)]);
        exit;
    }

    echo json_encode(mysqli_fetch_assoc($q));
    exit;
}

// ===============================
// HAPUS BARANG
// ===============================
if (isset($_POST['aksi']) && $_POST['aksi'] === 'hapus') {
    $id = (int)($_POST['id'] ?? 0);

    $q = mysqli_query($conn, "DELETE FROM barang WHERE id_barang = $id");
    echo $q ? "sukses" : ("Gagal menghapus: " . mysqli_error($conn));
    exit;
}

// ===============================
// TAMBAH / UPDATE BARANG
// ===============================
$id = $_POST['id'] ?? '';
$nama_barang = mysqli_real_escape_string($conn, $_POST['nama_barang'] ?? '');

// dari <select name="kategori"> -> isinya ID kategori
$kategori_id = (int)($_POST['kategori'] ?? 0);

$harga_raw = $_POST['harga'] ?? '';
$minimal_stok_gudang = (int)($_POST['minimal_stok_gudang'] ?? 0);
$minimal_stok_outlet = (int)($_POST['minimal_stok_outlet'] ?? 0);

// VALIDASI
if ($nama_barang === '' || $kategori_id === 0 || $harga_raw === '') {
    echo "Nama barang, kategori, dan harga tidak boleh kosong.";
    exit;
}
$harga = (int)$harga_raw;

// ===============================
// IMPORTANT FIX:
// Isi kolom `kategori` (karena wajib / NOT NULL)
// Kalau tabel kamu juga punya `id_kategori`, kita isi juga.
// ===============================
if ($id === '' || $id === null) {
    // TAMBAH
    $sql = "INSERT INTO barang (nama_barang, kategori, id_kategori, harga, minimal_stok_gudang, minimal_stok_outlet)
            VALUES ('$nama_barang', '$kategori_id', '$kategori_id', '$harga', '$minimal_stok_gudang', '$minimal_stok_outlet')";
} else {
    // UPDATE
    $id = (int)$id;
    $sql = "UPDATE barang SET
                nama_barang = '$nama_barang',
                kategori = '$kategori_id',
                id_kategori = '$kategori_id',
                harga = '$harga',
                minimal_stok_gudang = '$minimal_stok_gudang',
                minimal_stok_outlet = '$minimal_stok_outlet'
            WHERE id_barang = $id";
}

$q = mysqli_query($conn, $sql);

if ($q) {
    echo "sukses";
} else {
    echo "Gagal menyimpan data: " . mysqli_error($conn);
}
