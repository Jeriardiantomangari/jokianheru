<?php
session_start();
include '../../koneksi/koneksi.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'gudang') {
    echo "Akses ditolak";
    exit;
}

function norm_status($s) {
    return strtolower(trim((string)$s));
}

/**
 * Cari / pastikan Id_stok_gudang berdasarkan id_barang.
 * Kalau belum ada stok_gudang untuk barang tsb, buat otomatis.
 */
function get_or_create_stok_gudang($conn, $id_barang) {
    // 1) cari stok_gudang
    $sql = "SELECT Id_stok_gudang FROM stok_gudang WHERE Id_barang = ? LIMIT 1";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $id_barang);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);

    if ($res && ($row = mysqli_fetch_assoc($res))) {
        return (int)$row['Id_stok_gudang'];
    }

    // 2) kalau belum ada, ambil data barang lalu insert stok_gudang
    $sqlB = "SELECT nama_barang, kategori FROM barang WHERE id_barang = ? LIMIT 1";
    $stmtB = mysqli_prepare($conn, $sqlB);
    mysqli_stmt_bind_param($stmtB, "i", $id_barang);
    mysqli_stmt_execute($stmtB);
    $resB = mysqli_stmt_get_result($stmtB);

    if (!$resB || mysqli_num_rows($resB) === 0) {
        return 0;
    }
    $b = mysqli_fetch_assoc($resB);

    $nama = $b['nama_barang'];
    $kategori = $b['kategori'];

    $sqlIns = "INSERT INTO stok_gudang (Id_barang, Nama_barang, Kategori, Jumlah_stok)
               VALUES (?, ?, ?, 0)";
    $stmtIns = mysqli_prepare($conn, $sqlIns);
    mysqli_stmt_bind_param($stmtIns, "iss", $id_barang, $nama, $kategori);
    mysqli_stmt_execute($stmtIns);

    if (mysqli_stmt_affected_rows($stmtIns) > 0) {
        return (int)mysqli_insert_id($conn);
    }

    return 0;
}

// ======================================================
// AMBIL DATA UNTUK EDIT (AJAX)
// ======================================================
if (isset($_POST['aksi']) && $_POST['aksi'] === 'ambil') {
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    if ($id <= 0) {
        echo json_encode(['error' => 'ID tidak valid']);
        exit;
    }

    // ambil restok + id_barang untuk set dropdown
    $sql = "SELECT r.*, s.Id_barang
            FROM restok_barang r
            JOIN stok_gudang s ON s.Id_stok_gudang = r.Id_stok_gudang
            WHERE r.Id_restok_barang = ?
            LIMIT 1";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);

    if (!$res || mysqli_num_rows($res) === 0) {
        echo json_encode(['error' => 'Data pengajuan tidak ditemukan']);
        exit;
    }

    $row = mysqli_fetch_assoc($res);

    if (!in_array(trim($row['Status']), ['Menunggu','Ditolak'], true)) {
        echo json_encode(['error' => 'Pengajuan ini tidak dapat diedit (status sudah '.$row['Status'].')']);
        exit;
    }

    echo json_encode([
        'id' => (int)$row['Id_restok_barang'],
        'id_barang' => (int)$row['Id_barang'],
        'id_stok_gudang' => (int)$row['Id_stok_gudang'],
        'nama_barang' => $row['Nama_barang'],
        'harga' => (float)$row['Harga'],
        'jumlah_restok' => (int)$row['Jumlah_restok'],
        'total_harga' => (float)$row['Total_harga'],
        'status' => $row['Status']
    ]);
    exit;
}

// ======================================================
// AMBIL DATA UNTUK KONFIRMASI BARANG MASUK (AJAX)
// ======================================================
if (isset($_POST['aksi']) && $_POST['aksi'] === 'ambil_konfirmasi') {
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    if ($id <= 0) {
        echo json_encode(['error' => 'ID tidak valid']);
        exit;
    }

    $sql = "SELECT Id_restok_barang, Nama_barang, Jumlah_restok, Status
            FROM restok_barang
            WHERE Id_restok_barang = ?
            LIMIT 1";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);

    if (!$res || mysqli_num_rows($res) === 0) {
        echo json_encode(['error' => 'Data pengajuan tidak ditemukan']);
        exit;
    }

    $row = mysqli_fetch_assoc($res);

    if (trim($row['Status']) !== 'Disetujui') {
        echo json_encode(['error' => 'Pengajuan ini belum disetujui atau sudah selesai/dibatalkan.']);
        exit;
    }

    echo json_encode([
        'id' => (int)$row['Id_restok_barang'],
        'nama_barang' => $row['Nama_barang'],
        'jumlah_restok' => (int)$row['Jumlah_restok'],
        'status' => $row['Status']
    ]);
    exit;
}

// ======================================================
// HAPUS PENGAJUAN
// ======================================================
if (isset($_POST['aksi']) && $_POST['aksi'] === 'hapus') {
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    if ($id <= 0) {
        echo "ID tidak valid";
        exit;
    }

    // cek status
    $sqlCek = "SELECT Status FROM restok_barang WHERE Id_restok_barang = ? LIMIT 1";
    $stmtCek = mysqli_prepare($conn, $sqlCek);
    mysqli_stmt_bind_param($stmtCek, "i", $id);
    mysqli_stmt_execute($stmtCek);
    $resCek = mysqli_stmt_get_result($stmtCek);

    if (!$resCek || mysqli_num_rows($resCek) === 0) {
        echo "Data pengajuan tidak ditemukan";
        exit;
    }

    $row = mysqli_fetch_assoc($resCek);
    $status = norm_status($row['Status']);

    // boleh hapus jika: menunggu, ditolak, selesai
    if (!in_array($status, ['menunggu','ditolak','selesai'], true)) {
        echo "Pengajuan ini tidak bisa dihapus (status sekarang: ".$row['Status'].")";
        exit;
    }

    $sqlDel = "DELETE FROM restok_barang WHERE Id_restok_barang = ?";
    $stmtDel = mysqli_prepare($conn, $sqlDel);
    mysqli_stmt_bind_param($stmtDel, "i", $id);
    mysqli_stmt_execute($stmtDel);

    if (mysqli_stmt_affected_rows($stmtDel) > 0) {
        echo "Pengajuan berhasil dihapus.";
    } else {
        echo "Gagal menghapus pengajuan.";
    }
    exit;
}

// ======================================================
// AKSI SELESAI (tambah stok + ubah status) + SIMPAN KE barang_masuk
// ======================================================
if (isset($_POST['aksi']) && $_POST['aksi'] === 'selesai') {
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $barang_masuk = isset($_POST['barang_masuk']) ? (int)$_POST['barang_masuk'] : 0;

    if ($id <= 0) {
        echo "ID pengajuan tidak valid";
        exit;
    }
    if ($barang_masuk <= 0) {
        echo "Jumlah barang masuk harus diisi";
        exit;
    }

    mysqli_begin_transaction($conn);

    try {
        // lock row restok (biar aman kalau double klik)
        $sqlSel = "SELECT Id_stok_gudang, Nama_barang, Jumlah_restok, Status
                   FROM restok_barang
                   WHERE Id_restok_barang = ?
                   FOR UPDATE";
        $stmtSel = mysqli_prepare($conn, $sqlSel);
        mysqli_stmt_bind_param($stmtSel, "i", $id);
        mysqli_stmt_execute($stmtSel);
        $resSel = mysqli_stmt_get_result($stmtSel);

        if (!$resSel || mysqli_num_rows($resSel) === 0) {
            throw new Exception("Pengajuan tidak ditemukan");
        }

        $row = mysqli_fetch_assoc($resSel);
        $id_stok_gudang = (int)$row['Id_stok_gudang'];
        $nama_barang = (string)$row['Nama_barang'];
        $jumlah_restok = (int)$row['Jumlah_restok'];
        $status_lama = trim($row['Status']);

        if ($status_lama !== 'Disetujui') {
            throw new Exception("Pengajuan ini belum disetujui owner atau sudah selesai/dibatalkan.");
        }

        // validasi barang masuk (tidak boleh melebihi jumlah restok disetujui)
        if ($barang_masuk > $jumlah_restok) {
            throw new Exception("Barang masuk tidak boleh melebihi jumlah restok yang disetujui.");
        }

        // (opsional tapi disarankan) cegah konfirmasi dobel
        $sqlCekLog = "SELECT 1 FROM barang_masuk WHERE Id_restok_barang = ? LIMIT 1";
        $stmtCekLog = mysqli_prepare($conn, $sqlCekLog);
        mysqli_stmt_bind_param($stmtCekLog, "i", $id);
        mysqli_stmt_execute($stmtCekLog);
        $resCekLog = mysqli_stmt_get_result($stmtCekLog);
        if ($resCekLog && mysqli_num_rows($resCekLog) > 0) {
            throw new Exception("Pengajuan ini sudah pernah dikonfirmasi (data barang masuk sudah ada).");
        }

        // SIMPAN log konfirmasi ke tabel barang_masuk
        // Pastikan kolom: Id_restok_barang, Nama_barang, Jumlah_restok, Barang_masuk
        $sqlLog = "INSERT INTO barang_masuk (Id_restok_barang, Nama_barang, Jumlah_restok, Barang_masuk)
                   VALUES (?, ?, ?, ?)";
        $stmtLog = mysqli_prepare($conn, $sqlLog);
        mysqli_stmt_bind_param($stmtLog, "isii", $id, $nama_barang, $jumlah_restok, $barang_masuk);
        mysqli_stmt_execute($stmtLog);

        if (mysqli_stmt_affected_rows($stmtLog) <= 0) {
            throw new Exception("Gagal menyimpan data barang masuk.");
        }

        // update stok gudang pakai barang_masuk
        $sqlStok = "UPDATE stok_gudang
                    SET Jumlah_stok = COALESCE(Jumlah_stok, 0) + ?
                    WHERE Id_stok_gudang = ?";
        $stmtStok = mysqli_prepare($conn, $sqlStok);
        mysqli_stmt_bind_param($stmtStok, "ii", $barang_masuk, $id_stok_gudang);
        mysqli_stmt_execute($stmtStok);

        if (mysqli_stmt_affected_rows($stmtStok) < 0) {
            throw new Exception("Gagal menambah stok gudang.");
        }

        // ubah status menjadi selesai
        $statusBaru = 'Selesai';
        $sqlUpd = "UPDATE restok_barang SET Status = ? WHERE Id_restok_barang = ?";
        $stmtUpd = mysqli_prepare($conn, $sqlUpd);
        mysqli_stmt_bind_param($stmtUpd, "si", $statusBaru, $id);
        mysqli_stmt_execute($stmtUpd);

        mysqli_commit($conn);

        if ($barang_masuk < $jumlah_restok) {
            echo "Konfirmasi tersimpan. Data masuk tercatat di barang_masuk. Stok bertambah $barang_masuk (lebih kecil dari jumlah disetujui: $jumlah_restok). Status: Selesai.";
        } else {
            echo "Pengajuan selesai. Data masuk tercatat di barang_masuk. Stok barang sudah ditambah.";
        }
    } catch (Exception $e) {
        mysqli_rollback($conn);
        echo $e->getMessage();
    }

    exit;
}

// ======================================================
// INSERT / UPDATE PENGAJUAN
// ======================================================
$id_ajukan = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$id_barang = isset($_POST['id_barang']) ? (int)$_POST['id_barang'] : 0;
$jumlah_restok = isset($_POST['jumlah_restok']) ? (int)$_POST['jumlah_restok'] : 0;

if ($id_barang <= 0 || $jumlah_restok <= 0) {
    echo "Data pengajuan tidak lengkap";
    exit;
}

// ambil data barang (pakai nama kolom yang benar: nama_barang, harga)
$sqlB = "SELECT nama_barang, harga FROM barang WHERE id_barang = ? LIMIT 1";
$stmtB = mysqli_prepare($conn, $sqlB);
mysqli_stmt_bind_param($stmtB, "i", $id_barang);
mysqli_stmt_execute($stmtB);
$resB = mysqli_stmt_get_result($stmtB);

if (!$resB || mysqli_num_rows($resB) === 0) {
    echo "Barang tidak ditemukan";
    exit;
}

$barang = mysqli_fetch_assoc($resB);
$nama_barang = $barang['nama_barang'];
$harga = (float)$barang['harga'];
$total_harga = $harga * $jumlah_restok;

// pastikan punya Id_stok_gudang
$id_stok_gudang = get_or_create_stok_gudang($conn, $id_barang);
if ($id_stok_gudang <= 0) {
    echo "Stok gudang untuk barang ini tidak ditemukan/gagal dibuat.";
    exit;
}

if ($id_ajukan > 0) {
    // cek status boleh edit
    $sqlCek = "SELECT Status FROM restok_barang WHERE Id_restok_barang = ? LIMIT 1";
    $stmtCek = mysqli_prepare($conn, $sqlCek);
    mysqli_stmt_bind_param($stmtCek, "i", $id_ajukan);
    mysqli_stmt_execute($stmtCek);
    $resCek = mysqli_stmt_get_result($stmtCek);

    if (!$resCek || mysqli_num_rows($resCek) === 0) {
        echo "Pengajuan tidak ditemukan";
        exit;
    }

    $row = mysqli_fetch_assoc($resCek);
    if (!in_array(trim($row['Status']), ['Menunggu','Ditolak'], true)) {
        echo "Pengajuan ini tidak dapat diubah (status sudah ".$row['Status'].")";
        exit;
    }

    // UPDATE (sekarang juga update Id_stok_gudang)
    $sqlUpd = "UPDATE restok_barang
               SET Id_stok_gudang = ?,
                   Nama_barang = ?,
                   Harga = ?,
                   Jumlah_restok = ?,
                   Total_harga = ?
               WHERE Id_restok_barang = ?";
    $stmtUpd = mysqli_prepare($conn, $sqlUpd);
    mysqli_stmt_bind_param($stmtUpd, "isdiii", $id_stok_gudang, $nama_barang, $harga, $jumlah_restok, $total_harga, $id_ajukan);
    mysqli_stmt_execute($stmtUpd);

    echo "Pengajuan restok berhasil diubah.";
    exit;
}

// INSERT baru (wajib isi Id_stok_gudang)
$sqlIns = "INSERT INTO restok_barang
           (Id_stok_gudang, Nama_barang, Harga, Jumlah_restok, Total_harga, Status)
           VALUES (?,?,?,?,?, 'Menunggu')";
$stmtIns = mysqli_prepare($conn, $sqlIns);
mysqli_stmt_bind_param($stmtIns, "isdii", $id_stok_gudang, $nama_barang, $harga, $jumlah_restok, $total_harga);
mysqli_stmt_execute($stmtIns);

if (mysqli_stmt_affected_rows($stmtIns) > 0) {
    echo "Pengajuan restok berhasil dibuat, menunggu persetujuan owner.";
} else {
    echo "Gagal menyimpan pengajuan restok.";
}
?>
