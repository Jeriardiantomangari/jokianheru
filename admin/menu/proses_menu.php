<?php
include '../../koneksi/koneksi.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $aksi = $_POST['aksi'] ?? '';

    if ($aksi === 'ambil') {
        // Ambil data menu untuk edit
        $id = $_POST['id'];
        $result = mysqli_query($conn, "SELECT * FROM menu WHERE id_menu='$id'");
        $row = mysqli_fetch_assoc($result);
        echo json_encode($row);
    } elseif ($aksi === 'hapus') {
        // Hapus menu
        $id = $_POST['id'];
        $result = mysqli_query($conn, "SELECT gambar FROM menu WHERE id_menu='$id'");
        $row = mysqli_fetch_assoc($result);
        $gambar = $row['gambar'];

        if ($gambar) {
            $path = "../../uploads/menu/$gambar";
            if (file_exists($path)) {
                unlink($path);
            }
        }

        mysqli_query($conn, "DELETE FROM menu WHERE id_menu='$id'");
        echo "Menu berhasil dihapus";
    } else {
        // Tambah/Edit Menu
        $id = $_POST['id'] ?? '';
        $nama_menu = mysqli_real_escape_string($conn, $_POST['nama_menu']);
        $jenis = mysqli_real_escape_string($conn, $_POST['kategori']);  // Sesuaikan dengan kategori
        $harga = (int)$_POST['harga'];
        $gambar_lama = $_POST['gambar_lama'] ?? '';

        $gambar_baru = '';
        if (isset($_FILES['gambar']) && $_FILES['gambar']['error'] === UPLOAD_ERR_OK) {
            $file_temp = $_FILES['gambar']['tmp_name'];
            $file_name = $_FILES['gambar']['name'];
            $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

            $valid_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            if (in_array($file_ext, $valid_extensions)) {
                $gambar_baru = time() . '_' . bin2hex(random_bytes(4)) . '.' . $file_ext;
                $upload_dir = '../../uploads/menu/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                move_uploaded_file($file_temp, $upload_dir . $gambar_baru);

                // Hapus gambar lama jika ada
                if ($gambar_lama && file_exists($upload_dir . $gambar_lama)) {
                    unlink($upload_dir . $gambar_lama);
                }
            }
        }

        if ($id) {
            // Update menu
            $sql = "UPDATE menu SET 
                    nama_menu='$nama_menu', 
                    jenis='$jenis', 
                    harga='$harga' 
                    " . ($gambar_baru ? ", gambar='$gambar_baru'" : "") . " 
                    WHERE id_menu='$id'";
        } else {
            // Tambah menu
            $sql = "INSERT INTO menu (nama_menu, jenis, harga, gambar) 
                    VALUES ('$nama_menu', '$jenis', '$harga', '$gambar_baru')";
        }

        if (mysqli_query($conn, $sql)) {
            echo "Menu berhasil disimpan";
        } else {
            echo "Terjadi kesalahan: " . mysqli_error($conn);
        }
    }
}
?>
