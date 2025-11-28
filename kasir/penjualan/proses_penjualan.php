<?php
session_start();
include '../../koneksi/koneksi.php';


if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'kasir') {
    header("Location: ../../index.php");
    exit;
}

if (!isset($_SESSION['id_outlet'])) {
    $_SESSION['error_message'] = "Outlet untuk kasir belum di-set. Pastikan kolom id_outlet di tabel pengguna dan session sudah benar.";
    header("Location: penjualan.php"); 
    exit;
}

if (!isset($_SESSION['id_user'])) {
    $_SESSION['error_message'] = "ID kasir belum ada di session. Cek lagi proses login, harus set \$_SESSION['id_user'].";
    header("Location: penjualan.php");
    exit;
}

$id_outlet = (int) $_SESSION['id_outlet'];
$id_kasir  = (int) $_SESSION['id_user'];


if ($_SERVER['REQUEST_METHOD'] === 'POST') {

   
    if (empty($_POST['items'])) {
        $_SESSION['error_message'] = "Tidak ada item yang ditambahkan.";
        header("Location: penjualan.php");
        exit;
    }

    $tanggal     = date('Y-m-d H:i:s');
    $total_harga = (int) ($_POST['total_harga'] ?? 0);

 
    mysqli_begin_transaction($conn);

    try {
        $stmt = mysqli_prepare(
            $conn,
            "INSERT INTO penjualan (tanggal, id_kasir, id_outlet, total_harga)
             VALUES (?, ?, ?, ?)"
        );

        mysqli_stmt_bind_param(
            $stmt,
            'siii',
            $tanggal,
            $id_kasir,
            $id_outlet,
            $total_harga
        );

        mysqli_stmt_execute($stmt);
        $id_penjualan = mysqli_insert_id($conn); 
        mysqli_stmt_close($stmt);

        $stmt_detail = mysqli_prepare(
            $conn,
            "INSERT INTO penjualan_detail (id_penjualan, id_menu, nama_menu, harga, jumlah, subtotal)
             VALUES (?, ?, ?, ?, ?, ?)"
        );

        $ada_detail_valid = false;

        foreach ($_POST['items'] as $item) {
            $id_menu   = (int) ($item['id_menu']   ?? 0);
            $nama_menu =        ($item['nama_menu'] ?? '');
            $harga     = (int) ($item['harga']     ?? 0);
            $jumlah    = (int) ($item['jumlah']    ?? 0);
            $subtotal  = (int) ($item['subtotal']  ?? 0);

            if ($jumlah <= 0) {
                continue;
            }

            $ada_detail_valid = true;

            mysqli_stmt_bind_param(
                $stmt_detail,
                'iisiii',          
                $id_penjualan,
                $id_menu,
                $nama_menu,
                $harga,
                $jumlah,
                $subtotal
            );
            mysqli_stmt_execute($stmt_detail);
        }

        mysqli_stmt_close($stmt_detail);

        if (!$ada_detail_valid) {
            mysqli_rollback($conn);
            $_SESSION['error_message'] = "Tidak ada item dengan jumlah yang valid.";
            header("Location: penjualan.php");
            exit;
        }

        mysqli_commit($conn);

        $_SESSION['success_message'] = "Transaksi berhasil disimpan. ID Penjualan: " . $id_penjualan;
        header("Location: penjualan.php");
        exit;

    } catch (Exception $e) {
        mysqli_rollback($conn);

        $_SESSION['error_message'] = "Terjadi kesalahan saat menyimpan transaksi: " . $e->getMessage();


        header("Location: penjualan.php");
        exit;
    }

} else {
    header("Location: penjualan.php");
    exit;
}
