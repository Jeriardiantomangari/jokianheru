<?php
include '../../koneksi/koneksi.php';

// AMBIL DATA SATU KATEGORI (UNTUK EDIT)
if (isset($_POST['aksi']) && $_POST['aksi'] === 'ambil') {
    $id = (int)$_POST['id'];
    $q  = mysqli_query($conn, "SELECT * FROM kategori WHERE id_kategori='$id'");
    $data = mysqli_fetch_assoc($q);
    echo json_encode($data);
    exit;
}

// HAPUS KATEGORI
if (isset($_POST['aksi']) && $_POST['aksi'] === 'hapus') {
    $id = (int)$_POST['id'];
    mysqli_query($conn, "DELETE FROM kategori WHERE id_kategori='$id'");
    exit;
}

// TAMBAH / UPDATE KATEGORI
$id            = $_POST['id'] ?? '';  
$nama_kategori = mysqli_real_escape_string($conn, $_POST['nama_kategori']);  
if ($id === '' || $id === null) {
    // TAMBAH
    $sql = "INSERT INTO kategori (nama_kategori)
            VALUES ('$nama_kategori')";
} else {
    // UPDATE
    $id = (int)$id;
    $sql = "UPDATE kategori SET
                nama_kategori = '$nama_kategori'
            WHERE id_kategori = '$id'";
}

mysqli_query($conn, $sql);
echo "sukses";  
