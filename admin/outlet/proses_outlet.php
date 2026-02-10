<?php
include '../../koneksi/koneksi.php';

// ==== AMBIL DATA UNTUK EDIT ====
if (isset($_POST['aksi']) && $_POST['aksi'] == 'ambil') {
    $id = $_POST['id'];

    // Mengambil data outlet berdasarkan id_outlet
    $q = mysqli_query($conn, "SELECT * FROM outlet WHERE id_outlet='$id'");
    echo json_encode(mysqli_fetch_assoc($q));
    exit;
}

// ==== HAPUS DATA ====
if (isset($_POST['aksi']) && $_POST['aksi'] == 'hapus') {
    $id = $_POST['id'];

    // Menghapus outlet berdasarkan id_outlet
    mysqli_query($conn, "DELETE FROM outlet WHERE id_outlet='$id'");
    exit;
}

// ==== TAMBAH / UPDATE OUTLET ====

// Data dari form
$id          = $_POST['id'] ?? '';  
$nama_outlet = $_POST['nama_outlet'];
$alamat      = $_POST['alamat'];

if ($id == "") {
    // TAMBAH: Insert data outlet baru
    mysqli_query($conn,
        "INSERT INTO outlet (nama_outlet, alamat)
         VALUES ('$nama_outlet', '$alamat')");
} else {
    // UPDATE: Update data outlet berdasarkan id_outlet
    mysqli_query($conn,
        "UPDATE outlet SET
           nama_outlet = '$nama_outlet',
           alamat      = '$alamat'
         WHERE id_outlet = '$id'");
}

echo "sukses";
?>
