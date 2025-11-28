<?php
session_start();
include '../../koneksi/koneksi.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'owner') {
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

if ($id <= 0) {
    echo "ID tidak valid.";
    exit;
}

// Ambil data pengajuan
$sql = "SELECT * FROM ajukan_stok WHERE id = $id";
$q   = mysqli_query($conn, $sql);
if (!$row = mysqli_fetch_assoc($q)) {
    echo "Data pengajuan tidak ditemukan.";
    exit;
}

$status_sekarang = $row['status'];

if ($aksi === 'setujui') {

    // Hanya setujui jika masih Menunggu atau Ditolak
    if (!in_array($status_sekarang, ['Menunggu','Ditolak'])) {
        echo "Pengajuan tidak dapat disetujui (status sekarang: $status_sekarang).";
        exit;
    }

    $u = mysqli_query($conn, "
        UPDATE ajukan_stok
        SET status = 'Disetujui',
            updated_at = NOW()
        WHERE id = $id
    ");

    if ($u && mysqli_affected_rows($conn) > 0) {
        echo "Pengajuan restok disetujui owner.";
    } else {
        echo "Gagal menyetujui pengajuan.";
    }
    exit;

} elseif ($aksi === 'tolak') {

    // Hanya tolak jika masih Menunggu atau Disetujui
    if (!in_array($status_sekarang, ['Menunggu','Disetujui'])) {
        echo "Pengajuan tidak dapat ditolak (status sekarang: $status_sekarang).";
        exit;
    }

    $u = mysqli_query($conn, "
        UPDATE ajukan_stok
        SET status = 'Ditolak',
            updated_at = NOW()
        WHERE id = $id
    ");

    if ($u && mysqli_affected_rows($conn) > 0) {
        echo "Pengajuan restok ditolak owner.";
    } else {
        echo "Gagal menolak pengajuan.";
    }
    exit;

} elseif ($aksi === 'hapus') {

    // Hanya boleh hapus kalau status sudah Selesai
    if ($status_sekarang !== 'Selesai') {
        echo "Hanya pengajuan dengan status 'Selesai' yang boleh dihapus (status sekarang: $status_sekarang).";
        exit;
    }

    mysqli_query($conn, "DELETE FROM ajukan_stok WHERE id = $id");
    if (mysqli_affected_rows($conn) > 0) {
        echo "Pengajuan restok berhasil dihapus.";
    } else {
        echo "Gagal menghapus pengajuan.";
    }
    exit;

} else {
    echo "Aksi tidak dikenali.";
    exit;
}
