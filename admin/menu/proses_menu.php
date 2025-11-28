<?php
include '../../koneksi/koneksi.php';

// AMBIL DATA SATU MENU (UNTUK EDIT)
if (isset($_POST['aksi']) && $_POST['aksi'] === 'ambil') {
    $id = (int)$_POST['id'];
    $q  = mysqli_query($conn, "SELECT * FROM menu_makanan WHERE id='$id'");
    $data = mysqli_fetch_assoc($q);
    echo json_encode($data);
    exit;
}

// HAPUS MENU
if (isset($_POST['aksi']) && $_POST['aksi'] === 'hapus') {
    $id = (int)$_POST['id'];

    // opsional: hapus file gambar juga
    $q = mysqli_query($conn, "SELECT gambar FROM menu_makanan WHERE id='$id'");
    $d = mysqli_fetch_assoc($q);
    if (!empty($d['gambar'])) {
        $filePath = "../../uploads/menu/" . $d['gambar'];
        if (file_exists($filePath)) {
            unlink($filePath);
        }
    }

    mysqli_query($conn, "DELETE FROM menu_makanan WHERE id='$id'");
    exit;
}

// ---------------- TAMBAH / UPDATE MENU ----------------

$id         = $_POST['id'] ?? '';
$nama_menu  = mysqli_real_escape_string($conn, $_POST['nama_menu']);
$kategori   = mysqli_real_escape_string($conn, $_POST['kategori']);
$harga      = (int)$_POST['harga'];
$gambar_lama = $_POST['gambar_lama'] ?? '';

$nama_file_baru = '';

// CEK JIKA ADA FILE GAMBAR YANG DIUPLOAD
if (isset($_FILES['gambar']) && $_FILES['gambar']['error'] === UPLOAD_ERR_OK) {
    $tmp_name = $_FILES['gambar']['tmp_name'];
    $nama_asli = $_FILES['gambar']['name'];

    $ext = strtolower(pathinfo($nama_asli, PATHINFO_EXTENSION));
    $ext_valid = ['jpg','jpeg','png','gif','webp'];

    if (in_array($ext, $ext_valid)) {
        // bikin nama unik, misal pakai timestamp + random
        $nama_file_baru = time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;

        $folder_tujuan = '../../uploads/menu/';
        if (!is_dir($folder_tujuan)) {
            mkdir($folder_tujuan, 0777, true);
        }

        move_uploaded_file($tmp_name, $folder_tujuan . $nama_file_baru);

        // kalau update dan ada gambar lama, hapus file lama
        if (!empty($gambar_lama)) {
            $file_lama = $folder_tujuan . $gambar_lama;
            if (file_exists($file_lama)) {
                unlink($file_lama);
            }
        }
    }
}

if ($id === '' || $id === null) {
    // ---------------- TAMBAH ----------------
    if ($nama_file_baru !== '') {
        $sql = "INSERT INTO menu_makanan (nama_menu, kategori, harga, gambar)
                VALUES ('$nama_menu', '$kategori', '$harga', '$nama_file_baru')";
    } else {
        $sql = "INSERT INTO menu_makanan (nama_menu, kategori, harga)
                VALUES ('$nama_menu', '$kategori', '$harga')";
    }
} else {
    // ---------------- UPDATE ----------------
    $id  = (int)$id;

    if ($nama_file_baru !== '') {
        // update termasuk gambar
        $sql = "UPDATE menu_makanan SET
                    nama_menu  = '$nama_menu',
                    kategori   = '$kategori',
                    harga      = '$harga',
                    gambar     = '$nama_file_baru'
                WHERE id = '$id'";
    } else {
        // tidak ada gambar baru, gambar tetap
        $sql = "UPDATE menu_makanan SET
                    nama_menu  = '$nama_menu',
                    kategori   = '$kategori',
                    harga      = '$harga'
                WHERE id = '$id'";
    }
}

mysqli_query($conn, $sql) or die(mysqli_error($conn));
echo "sukses";
