<?php
session_start();
include '../../koneksi/koneksi.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'gudang') {
    echo "Akses ditolak.";
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo "Metode tidak diizinkan.";
    exit;
}

$aksi = $_POST['aksi'] ?? '';
$id   = isset($_POST['id']) ? (int)$_POST['id'] : 0;

if ($aksi === '' || $id <= 0) {
    echo "Parameter tidak lengkap.";
    exit;
}

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {

    // 1) Ambil data pengajuan
    $q1 = mysqli_prepare($conn, "
        SELECT Id_restok_bahan, Id_outlet, Id_stok_outlet, Nama_barang, Jumlah_restok, Status
        FROM restok_bahan_outlet
        WHERE Id_restok_bahan = ?
        LIMIT 1
    ");
    mysqli_stmt_bind_param($q1, "i", $id);
    mysqli_stmt_execute($q1);
    $r = mysqli_fetch_assoc(mysqli_stmt_get_result($q1));

    if (!$r) {
        echo "Data pengajuan tidak ditemukan.";
        exit;
    }

    $status         = $r['Status'] ?? '';
    $jumlah         = (int)($r['Jumlah_restok'] ?? 0);
    $id_stok_outlet = $r['Id_stok_outlet'] !== null ? (int)$r['Id_stok_outlet'] : 0;
    $nama_barang    = $r['Nama_barang'] ?? '';

    if ($jumlah <= 0) {
        echo "Jumlah restok tidak valid.";
        exit;
    }

    // ---- AKSI SETUJUI ----
    if ($aksi === 'setujui') {

        if ($status !== 'Menunggu') {
            echo "Pengajuan tidak bisa disetujui karena status sekarang: $status.";
            exit;
        }

        mysqli_begin_transaction($conn);

        // 2) Tentukan Id_barang (prioritas dari Id_stok_outlet)
        $id_barang = 0;

        if ($id_stok_outlet > 0) {
            $qB = mysqli_prepare($conn, "SELECT Id_barang FROM stok_outlet WHERE Id_stok_outlet = ? LIMIT 1");
            mysqli_stmt_bind_param($qB, "i", $id_stok_outlet);
            mysqli_stmt_execute($qB);
            $b = mysqli_fetch_assoc(mysqli_stmt_get_result($qB));
            $id_barang = (int)($b['Id_barang'] ?? 0);
        }

        // kalau Id_barang belum ketemu, cari dari Nama_barang (fallback)
        if ($id_barang <= 0 && $nama_barang !== '') {
            $qB2 = mysqli_prepare($conn, "SELECT id_barang FROM barang WHERE nama_barang = ? LIMIT 1");
            mysqli_stmt_bind_param($qB2, "s", $nama_barang);
            mysqli_stmt_execute($qB2);
            $b2 = mysqli_fetch_assoc(mysqli_stmt_get_result($qB2));
            $id_barang = (int)($b2['id_barang'] ?? 0);
        }

        if ($id_barang <= 0) {
            mysqli_rollback($conn);
            echo "Id_barang tidak ditemukan. Pastikan Id_stok_outlet terisi atau Nama_barang cocok dengan tabel barang.";
            exit;
        }

        // 3) Lock stok gudang dulu (stok_gudang)
        $lock = mysqli_prepare($conn, "
            SELECT Id_stok_gudang, Jumlah_stok
            FROM stok_gudang
            WHERE Id_barang = ?
            FOR UPDATE
        ");
        mysqli_stmt_bind_param($lock, "i", $id_barang);
        mysqli_stmt_execute($lock);
        $sg = mysqli_fetch_assoc(mysqli_stmt_get_result($lock));

        if (!$sg) {
            mysqli_rollback($conn);
            echo "Data stok gudang untuk barang ini belum ada (stok_gudang kosong).";
            exit;
        }

        $id_stok_gudang  = (int)$sg['Id_stok_gudang'];
        $stok_gudang_now = (int)$sg['Jumlah_stok'];

        if ($stok_gudang_now < $jumlah) {
            mysqli_rollback($conn);
            echo "Stok gudang tidak cukup. Stok sekarang: $stok_gudang_now.";
            exit;
        }

        // 4) Kurangi stok gudang
        $u1 = mysqli_prepare($conn, "
            UPDATE stok_gudang
            SET Jumlah_stok = Jumlah_stok - ?
            WHERE Id_stok_gudang = ?
        ");
        mysqli_stmt_bind_param($u1, "ii", $jumlah, $id_stok_gudang);
        mysqli_stmt_execute($u1);

        // 5) Update status pengajuan -> Dikirim (tanpa updated_at karena tidak ada kolomnya)
        $u2 = mysqli_prepare($conn, "
            UPDATE restok_bahan_outlet
            SET Status = 'Dikirim'
            WHERE Id_restok_bahan = ?
        ");
        mysqli_stmt_bind_param($u2, "i", $id);
        mysqli_stmt_execute($u2);


        mysqli_commit($conn);
        echo "Pengajuan disetujui. Stok gudang berkurang. Status: Dikirim.";
        exit;
    }

    // ---- AKSI TOLAK ----
    if ($aksi === 'tolak') {

        if ($status !== 'Menunggu') {
            echo "Pengajuan tidak bisa ditolak karena status sekarang: $status.";
            exit;
        }

        $u = mysqli_prepare($conn, "
            UPDATE restok_bahan_outlet
            SET Status = 'Ditolak'
            WHERE Id_restok_bahan = ?
        ");
        mysqli_stmt_bind_param($u, "i", $id);
        mysqli_stmt_execute($u);

        echo "Pengajuan ditolak.";
        exit;
    }

    // ---- AKSI HAPUS ----
    if ($aksi === 'hapus') {

        if ($status !== 'Selesai') {
            echo "Hanya status 'Selesai' yang boleh dihapus (status sekarang: $status).";
            exit;
        }

        mysqli_begin_transaction($conn);

        // hapus bahan_masuk dulu (FK)
        $d1 = mysqli_prepare($conn, "DELETE FROM bahan_masuk WHERE Id_restok_bahan = ?");
        mysqli_stmt_bind_param($d1, "i", $id);
        mysqli_stmt_execute($d1);

        // hapus pengajuan
        $d2 = mysqli_prepare($conn, "DELETE FROM restok_bahan_outlet WHERE Id_restok_bahan = ?");
        mysqli_stmt_bind_param($d2, "i", $id);
        mysqli_stmt_execute($d2);

        mysqli_commit($conn);
        echo "Pengajuan berhasil dihapus.";
        exit;
    }

    echo "Aksi tidak dikenali.";
    exit;

} catch (Throwable $e) {
    @mysqli_rollback($conn);
    echo "Terjadi kesalahan proses: " . $e->getMessage();
    exit;
}
