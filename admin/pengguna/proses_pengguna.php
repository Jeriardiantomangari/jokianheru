<?php
include '../../koneksi/koneksi.php';

// ==== AMBIL DATA UNTUK EDIT ====
if(isset($_POST['aksi']) && $_POST['aksi'] == 'ambil'){
    $id = $_POST['id'];
    $q  = mysqli_query($conn, "SELECT * FROM pengguna WHERE id='$id'");
    echo json_encode(mysqli_fetch_assoc($q));
    exit;
}

// ==== HAPUS DATA ====
if(isset($_POST['aksi']) && $_POST['aksi'] == 'hapus'){
    $id = $_POST['id'];
    mysqli_query($conn, "DELETE FROM pengguna WHERE id='$id'");
    exit;
}

// ==== TAMBAH / UPDATE ====
// ambil data dari form
$id        = $_POST['id'] ?? '';
$nama      = $_POST['nama'];
$username  = $_POST['username'];
$password  = $_POST['password'];
$role      = $_POST['role'];
// id_outlet boleh kosong (NULL) untuk role selain kasir
$id_outlet = isset($_POST['id_outlet']) && $_POST['id_outlet'] !== '' 
             ? $_POST['id_outlet'] 
             : null;

if($id == ""){
    // TAMBAH
    if($id_outlet === null){
        // tanpa outlet (misal admin/owner/gudang)
        mysqli_query($conn,
            "INSERT INTO pengguna (nama, username, password, role, id_outlet)
             VALUES ('$nama', '$username', '$password', '$role', NULL)");
    } else {
        // dengan outlet
        mysqli_query($conn,
            "INSERT INTO pengguna (nama, username, password, role, id_outlet)
             VALUES ('$nama', '$username', '$password', '$role', '$id_outlet')");
    }
} else {
    // UPDATE
    if($id_outlet === null){
        mysqli_query($conn,
            "UPDATE pengguna SET
               nama      = '$nama',
               username  = '$username',
               password  = '$password',
               role      = '$role',
               id_outlet = NULL
             WHERE id='$id'");
    } else {
        mysqli_query($conn,
            "UPDATE pengguna SET
               nama      = '$nama',
               username  = '$username',
               password  = '$password',
               role      = '$role',
               id_outlet = '$id_outlet'
             WHERE id='$id'");
    }
}

echo "sukses";
?>
