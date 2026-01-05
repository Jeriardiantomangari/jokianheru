<?php
session_start();
include '../../koneksi/koneksi.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'kasir') {
    header("Location: ../../index.php");
    exit;
}

if (!isset($_SESSION['id_outlet'])) {
    $_SESSION['error_message'] = "Outlet untuk kasir belum di-set. Pastikan kolom id_outlet di tabel akun dan session sudah benar.";
    header("Location: penjualan.php");
    exit;
}

if (!isset($_SESSION['id_akun'])) {
    $_SESSION['error_message'] = "ID kasir belum ada di session. Pastikan saat login set \$_SESSION['id_akun'] dari tabel akun.";
    header("Location: penjualan.php");
    exit;
}

$id_outlet = (int)$_SESSION['id_outlet'];
$id_kasir  = (int)$_SESSION['id_akun'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (empty($_POST['items'])) {
        $_SESSION['error_message'] = "Tidak ada item yang ditambahkan.";
        header("Location: penjualan.php");
        exit;
    }

    $tanggal     = date('Y-m-d H:i:s');
    $total_harga = (int)($_POST['total_harga'] ?? 0);

    mysqli_begin_transaction($conn);

    try {

        // INSERT MASTER PENJUALAN

        $stmt = mysqli_prepare(
            $conn,
            "INSERT INTO penjualan (tanggal, id_kasir, id_outlet, total_harga)
             VALUES (?, ?, ?, ?)"
        );

        if (!$stmt) {
            throw new Exception("Prepare penjualan gagal: " . mysqli_error($conn));
        }

        mysqli_stmt_bind_param($stmt, 'siii', $tanggal, $id_kasir, $id_outlet, $total_harga);

        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception("Execute penjualan gagal: " . mysqli_stmt_error($stmt));
        }

        $id_penjualan = mysqli_insert_id($conn);
        mysqli_stmt_close($stmt);

        
        // INSERT DETAIL ke tabel `detail_penjualan`
       
        $stmt_detail = mysqli_prepare(
            $conn,
            "INSERT INTO detail_penjualan (Id_penjualan, Id_menu, Nama_menu, Harga, Jumlah, Total)
             VALUES (?, ?, ?, ?, ?, ?)"
        );

        if (!$stmt_detail) {
            throw new Exception("Prepare detail_penjualan gagal: " . mysqli_error($conn));
        }

        $ada_detail_valid = false;

        foreach ($_POST['items'] as $item) {
            $id_menu   = (int)($item['id_menu'] ?? 0);
            $nama_menu = (string)($item['nama_menu'] ?? '');
            $harga     = (int)($item['harga'] ?? 0);
            $jumlah    = (int)($item['jumlah'] ?? 0);

            // subtotal dari form 
            $total_item = (int)($item['subtotal'] ?? 0);

            if ($jumlah <= 0) {
                continue;
            }

            // kalau subtotal kosong/0, hitung ulang biar aman
            if ($total_item <= 0) {
                $total_item = $harga * $jumlah;
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
                $total_item
            );

            if (!mysqli_stmt_execute($stmt_detail)) {
                throw new Exception("Execute detail_penjualan gagal: " . mysqli_stmt_error($stmt_detail));
            }
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

    } catch (Throwable $e) {
        mysqli_rollback($conn);
        $_SESSION['error_message'] = "Terjadi kesalahan saat menyimpan transaksi: " . $e->getMessage();
        header("Location: penjualan.php");
        exit;
    }

} else {
    header("Location: penjualan.php");
    exit;
}
