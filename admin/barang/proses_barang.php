<?php
include '../../koneksi/koneksi.php';

// AMBIL DATA SATU BARANG (UNTUK EDIT)
if (isset($_POST['aksi']) && $_POST['aksi'] === 'ambil') {
    $id = (int)$_POST['id'];
    $q  = mysqli_query($conn, "SELECT * FROM barang WHERE id='$id'");
    $data = mysqli_fetch_assoc($q);
    echo json_encode($data);
    exit;
}

// HAPUS BARANG
if (isset($_POST['aksi']) && $_POST['aksi'] === 'hapus') {
    $id = (int)$_POST['id'];
    mysqli_query($conn, "DELETE FROM barang WHERE id='$id'");
    exit;
}

// TAMBAH / UPDATE BARANG
$id          = $_POST['id'] ?? '';
$nama_barang = mysqli_real_escape_string($conn, $_POST['nama_barang']);
$jenis       = mysqli_real_escape_string($conn, $_POST['jenis']);
$harga       = (int)$_POST['harga'];

if ($id === '' || $id === null) {
    // TAMBAH
    $sql = "INSERT INTO barang (nama_barang, jenis, harga)
            VALUES ('$nama_barang', '$jenis', '$harga')";
} else {
    // UPDATE
    $id  = (int)$id;
    $sql = "UPDATE barang SET
                nama_barang = '$nama_barang',
                jenis       = '$jenis',
                harga       = '$harga'
            WHERE id = '$id'";
}

mysqli_query($conn, $sql);
echo "sukses";
