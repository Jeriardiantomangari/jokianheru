<?php
session_start();
include '../../koneksi/koneksi.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'gudang') {
    echo "Akses ditolak.";
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo "Metode tidak diizinkan.";
    exit;
}

if (!isset($_POST['aksi'], $_POST['id'])) {
    echo "Parameter tidak lengkap.";
    exit;
}

$aksi = $_POST['aksi'];
$id   = (int)$_POST['id'];

// Ambil data pengajuan + stok gudang + stok outlet
$sql = "
    SELECT a.*, 
           b.stok AS stok_gudang,
           so.id AS id_stok_outlet,
           so.stok AS stok_outlet
    FROM ajukan_stok_outlet a
    JOIN barang b ON a.id_barang = b.id
    LEFT JOIN stok_outlet so 
           ON so.id_outlet = a.id_outlet 
          AND so.id_barang = a.id_barang
    WHERE a.id = $id
";

$q = mysqli_query($conn, $sql);
if (!$row = mysqli_fetch_assoc($q)) {
    echo "Data pengajuan tidak ditemukan.";
    exit;
}

$jumlah = (int)$row['jumlah_restok'];
$stok_gudang = (int)$row['stok_gudang'];
$id_outlet = (int)$row['id_outlet'];
$id_barang = (int)$row['id_barang'];
$id_stok_outlet = $row['id_stok_outlet'];
$stok_outlet = $row['stok_outlet'] === null ? 0 : (int)$row['stok_outlet'];

if ($aksi === 'setujui') {

    // cek stok gudang cukup
    if ($stok_gudang < $jumlah) {
        echo "Stok gudang tidak cukup. Stok sekarang: $stok_gudang.";
        exit;
    }

    // pakai transaksi biar aman
    mysqli_begin_transaction($conn);

    try {
        // 1. Kurangi stok gudang
        $u1 = mysqli_query($conn, "
            UPDATE barang 
            SET stok = stok - $jumlah 
            WHERE id = $id_barang
        ");

        // 2. Tambah stok outlet (insert kalau belum ada)
        if ($id_stok_outlet) {
            $u2 = mysqli_query($conn, "
                UPDATE stok_outlet 
                SET stok = stok + $jumlah 
                WHERE id = $id_stok_outlet
            ");
        } else {
            $u2 = mysqli_query($conn, "
                INSERT INTO stok_outlet (id_outlet, id_barang, stok)
                VALUES ($id_outlet, $id_barang, $jumlah)
            ");
        }

        // 3. Update status pengajuan -> Dikirim / Selesai (pilih salah satu, misalnya 'Dikirim')
        $u3 = mysqli_query($conn, "
            UPDATE ajukan_stok_outlet
            SET status = 'Dikirim',
                updated_at = NOW()
            WHERE id = $id
        ");

        if (!$u1 || !$u2 || !$u3) {
            throw new Exception("Query gagal.");
        }

        mysqli_commit($conn);
        echo "Pengajuan disetujui. Stok gudang dan outlet sudah diperbarui.";

    } catch (Exception $e) {
        mysqli_rollback($conn);
        echo "Terjadi kesalahan saat menyetujui pengajuan.";
    }
    exit;

} elseif ($aksi === 'tolak') {

    // cukup update status saja
    $u = mysqli_query($conn, "
        UPDATE ajukan_stok_outlet
        SET status = 'Ditolak',
            updated_at = NOW()
        WHERE id = $id
    ");

    if ($u && mysqli_affected_rows($conn) > 0) {
        echo "Pengajuan ditolak.";
    } else {
        echo "Gagal menolak pengajuan.";
    }
    exit;

} elseif ($aksi === 'hapus') {

    // hanya boleh hapus kalau status Selesai
    if ($row['status'] !== 'Selesai') {
        echo "Hanya pengajuan dengan status 'Selesai' yang boleh dihapus (status sekarang: ".$row['status'].").";
        exit;
    }

    mysqli_query($conn, "DELETE FROM ajukan_stok_outlet WHERE id = $id");
    if (mysqli_affected_rows($conn) > 0) {
        echo "Pengajuan berhasil dihapus.";
    } else {
        echo "Gagal menghapus pengajuan.";
    }
    exit;

} else {
    echo "Aksi tidak dikenali.";
    exit;
}
