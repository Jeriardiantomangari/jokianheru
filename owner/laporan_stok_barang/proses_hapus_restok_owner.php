<?php
session_start();
include '../../koneksi/koneksi.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'owner') {
    echo "Akses ditolak";
    exit;
}

$id = (int)($_POST['id'] ?? 0);
if ($id <= 0) {
    echo "ID tidak valid";
    exit;
}

mysqli_begin_transaction($conn);

try {
    // kunci data restok
    $sqlCek = "SELECT Status
               FROM restok_bahan_outlet
               WHERE Id_restok_bahan = ?
               FOR UPDATE";
    $stmtCek = mysqli_prepare($conn, $sqlCek);
    mysqli_stmt_bind_param($stmtCek, "i", $id);
    mysqli_stmt_execute($stmtCek);
    $resCek = mysqli_stmt_get_result($stmtCek);

    if (!$resCek || mysqli_num_rows($resCek) === 0) {
        throw new Exception("Data restok tidak ditemukan");
    }

    $row = mysqli_fetch_assoc($resCek);
    $status = strtolower(trim($row['Status'] ?? ''));

    // hanya boleh hapus kalau status selesai
    if ($status !== 'selesai') {
        throw new Exception("Tidak bisa hapus. Status sekarang: " . ($row['Status'] ?? '-'));
    }

    // hapus log bahan_masuk dulu (biar aman kalau ada foreign key)
    $sqlDelBm = "DELETE FROM bahan_masuk WHERE Id_restok_bahan = ?";
    $stmtDelBm = mysqli_prepare($conn, $sqlDelBm);
    mysqli_stmt_bind_param($stmtDelBm, "i", $id);
    mysqli_stmt_execute($stmtDelBm);

    // hapus restok
    $sqlDel = "DELETE FROM restok_bahan_outlet WHERE Id_restok_bahan = ?";
    $stmtDel = mysqli_prepare($conn, $sqlDel);
    mysqli_stmt_bind_param($stmtDel, "i", $id);
    mysqli_stmt_execute($stmtDel);

    if (mysqli_stmt_affected_rows($stmtDel) <= 0) {
        throw new Exception("Gagal menghapus data restok");
    }

    mysqli_commit($conn);
    echo "Data restok (Selesai) berhasil dihapus.";
} catch (Exception $e) {
    mysqli_rollback($conn);
    echo $e->getMessage();
}
