<?php
include '../../koneksi/koneksi.php';

// ==== AMBIL DATA UNTUK EDIT ====
if(isset($_POST['aksi']) && $_POST['aksi'] == 'ambil'){
    $id_akun = $_POST['id'];  
    $q  = mysqli_query($conn, "SELECT * FROM akun WHERE id_akun='$id_akun'");  
    echo json_encode(mysqli_fetch_assoc($q));
    exit;
}

// ==== HAPUS DATA ====
if(isset($_POST['aksi']) && $_POST['aksi'] == 'hapus'){
    $id_akun = $_POST['id'];  
    mysqli_query($conn, "DELETE FROM akun WHERE id_akun='$id_akun'");  
    exit;
}

// ==== TAMBAH / UPDATE ====
$id_akun   = $_POST['id'] ?? ''; 
$nama      = $_POST['nama'];
$username  = $_POST['username'];
$password  = $_POST['password'];
$role      = $_POST['role'];

$id_outlet = isset($_POST['id_outlet']) && $_POST['id_outlet'] !== '' 
             ? $_POST['id_outlet'] 
             : null;

if($id_akun == ""){
    // TAMBAH
    if($id_outlet === null){
        // tanpa outlet (misal admin/owner/gudang)
        mysqli_query($conn,
            "INSERT INTO akun (nama, username, password, role, id_outlet)
             VALUES ('$nama', '$username', '$password', '$role', NULL)");
    } else {
        // dengan outlet
        mysqli_query($conn,
            "INSERT INTO akun (nama, username, password, role, id_outlet)
             VALUES ('$nama', '$username', '$password', '$role', '$id_outlet')");
    }
} else {
    // UPDATE
    if($id_outlet === null){
        mysqli_query($conn,
            "UPDATE akun SET
               nama      = '$nama',
               username  = '$username',
               password  = '$password',
               role      = '$role',
               id_outlet = NULL
             WHERE id_akun='$id_akun'");  
    } else {
        mysqli_query($conn,
            "UPDATE akun SET
               nama      = '$nama',
               username  = '$username',
               password  = '$password',
               role      = '$role',
               id_outlet = '$id_outlet'
             WHERE id_akun='$id_akun'");  
    }
}

echo "sukses";
?>
