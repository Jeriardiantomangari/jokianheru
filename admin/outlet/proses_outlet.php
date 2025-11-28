<?php
include '../../koneksi/koneksi.php';

// ==== AMBIL DATA UNTUK EDIT ====
if (isset($_POST['aksi']) && $_POST['aksi'] == 'ambil') {
    $id  = $_POST['id'];
    $q   = mysqli_query($conn, "SELECT * FROM outlet WHERE id='$id'");
    echo json_encode(mysqli_fetch_assoc($q));
    exit;
}

// ==== HAPUS DATA ====
if (isset($_POST['aksi']) && $_POST['aksi'] == 'hapus') {
    $id = $_POST['id'];
    mysqli_query($conn, "DELETE FROM outlet WHERE id='$id'");
    exit;
}

// ==== TAMBAH / UPDATE OUTLET ====

// data dari form
$id          = $_POST['id'] ?? '';
$nama_outlet = $_POST['nama_outlet'];
$alamat      = $_POST['alamat'];

if ($id == "") {
    // TAMBAH
    mysqli_query($conn,
        "INSERT INTO outlet (nama_outlet, alamat)
         VALUES ('$nama_outlet', '$alamat')");
} else {
    // UPDATE
    mysqli_query($conn,
        "UPDATE outlet SET
           nama_outlet = '$nama_outlet',
           alamat      = '$alamat'
         WHERE id = '$id'");
}

echo "sukses";
?>
