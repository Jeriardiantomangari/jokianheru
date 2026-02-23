<?php
session_start();
include '../../koneksi/koneksi.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'kasir') {
    echo "Akses ditolak";
    exit;
}
if (!isset($_SESSION['id_outlet'])) {
    echo "Outlet belum di-set";
    exit;
}
$id_outlet = (int)$_SESSION['id_outlet'];

function norm_status($s) { return strtolower(trim((string)$s)); }

function get_or_create_stok_outlet($conn, $id_outlet, $id_barang) {

    $sql = "SELECT Id_stok_outlet
            FROM stok_outlet
            WHERE id_outlet = ? AND id_barang = ?
            LIMIT 1";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ii", $id_outlet, $id_barang);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    if ($res && ($row = mysqli_fetch_assoc($res))) {
        return (int)$row['Id_stok_outlet'];
    }

    $sqlB = "SELECT nama_barang, kategori FROM barang WHERE id_barang = ? LIMIT 1";
    $stmtB = mysqli_prepare($conn, $sqlB);
    mysqli_stmt_bind_param($stmtB, "i", $id_barang);
    mysqli_stmt_execute($stmtB);
    $resB = mysqli_stmt_get_result($stmtB);
    if (!$resB || mysqli_num_rows($resB) === 0) return 0;

    $b = mysqli_fetch_assoc($resB);
    $nama = $b['nama_barang'];
    $kategori = $b['kategori'];

    $sqlIns = "INSERT INTO stok_outlet (id_outlet, id_barang, Nama_barang, Kategori, Jumlah_stok)
               VALUES (?, ?, ?, ?, 0)";
    $stmtIns = mysqli_prepare($conn, $sqlIns);
    mysqli_stmt_bind_param($stmtIns, "iiss", $id_outlet, $id_barang, $nama, $kategori);
    mysqli_stmt_execute($stmtIns);

    return (mysqli_stmt_affected_rows($stmtIns) > 0) ? (int)mysqli_insert_id($conn) : 0;
}

if (($_POST['aksi'] ?? '') === 'ambil') {
    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) { echo json_encode(['error'=>'ID tidak valid']); exit; }

    $sql = "SELECT r.*, s.id_barang
            FROM restok_bahan_outlet r
            JOIN stok_outlet s ON s.Id_stok_outlet = r.Id_stok_outlet
            WHERE r.Id_restok_bahan = ? AND r.Id_outlet = ?
            LIMIT 1";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ii", $id, $id_outlet);
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

    $id_barang = (int)$row['id_barang'];
    $jumlah = (int)$row['Jumlah_restok'];

    echo json_encode([
    'id' => (int)$row['Id_restok_bahan'],
    'id_barang' => $id_barang,
    'id_stok_outlet' => (int)$row['Id_stok_outlet'],
    'nama_barang' => $row['Nama_barang'],
    'jumlah_restok' => $jumlah,
    'status' => $row['Status']
]);
exit;
}

/*  AMBIL KONFIRMASI  */

if (($_POST['aksi'] ?? '') === 'ambil_konfirmasi') {
    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) { echo json_encode(['error'=>'ID tidak valid']); exit; }

    $sql = "SELECT Id_restok_bahan, Nama_barang, Jumlah_restok, Status
            FROM restok_bahan_outlet
            WHERE Id_restok_bahan = ? AND Id_outlet = ?
            LIMIT 1";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ii", $id, $id_outlet);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);

    if (!$res || mysqli_num_rows($res) === 0) {
        echo json_encode(['error' => 'Data pengajuan tidak ditemukan']);
        exit;
    }

    $row = mysqli_fetch_assoc($res);
    if (!in_array(trim($row['Status']), ['Disetujui','Dikirim'], true)) {
        echo json_encode(['error' => 'Pengajuan ini belum disetujui/dikirim atau sudah selesai.']);
        exit;
    }

    echo json_encode([
        'id' => (int)$row['Id_restok_bahan'],
        'nama_barang' => $row['Nama_barang'],
        'jumlah_restok' => (int)$row['Jumlah_restok'],
        'status' => $row['Status']
    ]);
    exit;
}

/* HAPUS  */

if (($_POST['aksi'] ?? '') === 'hapus') {
    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) { echo "ID tidak valid"; exit; }

    $sqlCek = "SELECT Status FROM restok_bahan_outlet
               WHERE Id_restok_bahan = ? AND Id_outlet = ?
               LIMIT 1";
    $stmtCek = mysqli_prepare($conn, $sqlCek);
    mysqli_stmt_bind_param($stmtCek, "ii", $id, $id_outlet);
    mysqli_stmt_execute($stmtCek);
    $resCek = mysqli_stmt_get_result($stmtCek);

    if (!$resCek || mysqli_num_rows($resCek) === 0) {
        echo "Data pengajuan tidak ditemukan";
        exit;
    }

    $row = mysqli_fetch_assoc($resCek);
    $status = norm_status($row['Status']);

    if (!in_array($status, ['menunggu','ditolak','selesai'], true)) {
        echo "Pengajuan ini tidak bisa dihapus (status sekarang: ".$row['Status'].")";
        exit;
    }

    $sqlDel = "DELETE FROM restok_bahan_outlet WHERE Id_restok_bahan = ? AND Id_outlet = ?";
    $stmtDel = mysqli_prepare($conn, $sqlDel);
    mysqli_stmt_bind_param($stmtDel, "ii", $id, $id_outlet);
    mysqli_stmt_execute($stmtDel);

    echo (mysqli_stmt_affected_rows($stmtDel) > 0) ? "Pengajuan berhasil dihapus." : "Gagal menghapus pengajuan.";
    exit;
}

/*  SELESAI (KONFIRMASI BARANG MASUK ) */
if (($_POST['aksi'] ?? '') === 'selesai') {
    $id = (int)($_POST['id'] ?? 0);
    $bahan_masuk = (int)($_POST['bahan_masuk'] ?? 0);

    if ($id <= 0) { echo "ID pengajuan tidak valid"; exit; }
    if ($bahan_masuk <= 0) { echo "Jumlah bahan masuk harus diisi"; exit; }

    mysqli_begin_transaction($conn);

    try {
        // lock restok row
        $sqlSel = "SELECT Id_stok_outlet, Nama_barang, Jumlah_restok, Status
                   FROM restok_bahan_outlet
                   WHERE Id_restok_bahan = ? AND Id_outlet = ?
                   FOR UPDATE";
        $stmtSel = mysqli_prepare($conn, $sqlSel);
        mysqli_stmt_bind_param($stmtSel, "ii", $id, $id_outlet);
        mysqli_stmt_execute($stmtSel);
        $resSel = mysqli_stmt_get_result($stmtSel);

        if (!$resSel || mysqli_num_rows($resSel) === 0) {
            throw new Exception("Pengajuan tidak ditemukan");
        }

        $row = mysqli_fetch_assoc($resSel);
        $id_stok_outlet = (int)$row['Id_stok_outlet'];
        $nama_barang = (string)$row['Nama_barang'];
        $jumlah_restok = (int)$row['Jumlah_restok'];
        $status_lama = trim($row['Status']);

        if (!in_array($status_lama, ['Disetujui','Dikirim'], true)) {
            throw new Exception("Pengajuan ini belum disetujui/dikirim atau sudah selesai.");
        }

        if ($bahan_masuk > $jumlah_restok) {
            throw new Exception("Bahan masuk tidak boleh melebihi jumlah restok yang disetujui.");
        }

        // cegah konfirmasi dobel
        $sqlCekLog = "SELECT 1 FROM bahan_masuk WHERE Id_restok_bahan = ? LIMIT 1";
        $stmtCekLog = mysqli_prepare($conn, $sqlCekLog);
        mysqli_stmt_bind_param($stmtCekLog, "i", $id);
        mysqli_stmt_execute($stmtCekLog);
        $resCekLog = mysqli_stmt_get_result($stmtCekLog);
        if ($resCekLog && mysqli_num_rows($resCekLog) > 0) {
            throw new Exception("Pengajuan ini sudah pernah dikonfirmasi (log bahan_masuk sudah ada).");
        }

        // insert log bahan_masuk
        $sqlLog = "INSERT INTO bahan_masuk (Id_restok_bahan, Nama_barang, Jumlah_restok, Bahan_masuk)
                   VALUES (?, ?, ?, ?)";
        $stmtLog = mysqli_prepare($conn, $sqlLog);
        mysqli_stmt_bind_param($stmtLog, "isii", $id, $nama_barang, $jumlah_restok, $bahan_masuk);
        mysqli_stmt_execute($stmtLog);
        if (mysqli_stmt_affected_rows($stmtLog) <= 0) {
            throw new Exception("Gagal menyimpan data bahan masuk.");
        }

        $id_bahan_masuk_baru = (int)mysqli_insert_id($conn);

        // update stok_outlet
        $sqlStok = "UPDATE stok_outlet
                    SET Jumlah_stok = COALESCE(Jumlah_stok, 0) + ?
                    WHERE Id_stok_outlet = ?";
        $stmtStok = mysqli_prepare($conn, $sqlStok);
        mysqli_stmt_bind_param($stmtStok, "ii", $bahan_masuk, $id_stok_outlet);
        mysqli_stmt_execute($stmtStok);


        // update status selesai
        $statusBaru = 'Selesai';
        $sqlUpd = "UPDATE restok_bahan_outlet
                   SET Status = ?
                   WHERE Id_restok_bahan = ? AND Id_outlet = ?";
        $stmtUpd = mysqli_prepare($conn, $sqlUpd);
        mysqli_stmt_bind_param($stmtUpd, "sii", $statusBaru, $id, $id_outlet);
        mysqli_stmt_execute($stmtUpd);

        mysqli_commit($conn);

        if ($bahan_masuk < $jumlah_restok) {
            echo "Konfirmasi tersimpan. Log masuk tercatat. Stok outlet bertambah $bahan_masuk (lebih kecil dari disetujui: $jumlah_restok). Status: Selesai.";
        } else {
            echo "Konfirmasi tersimpan. Log masuk tercatat. Stok outlet bertambah $bahan_masuk. Status: Selesai.";
        }
    } catch (Exception $e) {
        mysqli_rollback($conn);
        echo $e->getMessage();
    }
    exit;
}

/* ===================== INSERT / UPDATE AJUKAN ===================== */
$id_ajukan = (int)($_POST['id'] ?? 0);
$id_barang = (int)($_POST['id_barang'] ?? 0);
$jumlah_restok = (int)($_POST['jumlah_restok'] ?? 0);

if ($id_barang <= 0 || $jumlah_restok <= 0) {
    echo "Data pengajuan tidak lengkap";
    exit;
}

$sqlB = "SELECT nama_barang FROM barang WHERE id_barang = ? LIMIT 1";
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

$id_stok_outlet = get_or_create_stok_outlet($conn, $id_outlet, $id_barang);
if ($id_stok_outlet <= 0) {
    echo "Stok outlet untuk barang ini tidak ditemukan/gagal dibuat.";
    exit;
}

if ($id_ajukan > 0) {
    $sqlCek = "SELECT Status FROM restok_bahan_outlet
               WHERE Id_restok_bahan = ? AND Id_outlet = ?
               LIMIT 1";
    $stmtCek = mysqli_prepare($conn, $sqlCek);
    mysqli_stmt_bind_param($stmtCek, "ii", $id_ajukan, $id_outlet);
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

    $sqlUpd = "UPDATE restok_bahan_outlet
               SET Id_stok_outlet = ?,
                   Nama_barang = ?,
                   Jumlah_restok = ?,
                   Status = 'Menunggu'
               WHERE Id_restok_bahan = ? AND Id_outlet = ?";
    $stmtUpd = mysqli_prepare($conn, $sqlUpd);
    mysqli_stmt_bind_param($stmtUpd, "isiii", $id_stok_outlet, $nama_barang, $jumlah_restok, $id_ajukan, $id_outlet);
    mysqli_stmt_execute($stmtUpd);

    echo "Pengajuan restok berhasil diubah.";
    exit;
}

$sqlIns = "INSERT INTO restok_bahan_outlet
           (Id_outlet, Id_stok_outlet, Nama_barang, Jumlah_restok, Status)
           VALUES (?, ?, ?, ?, 'Menunggu')";
$stmtIns = mysqli_prepare($conn, $sqlIns);
mysqli_stmt_bind_param($stmtIns, "iisi", $id_outlet, $id_stok_outlet, $nama_barang, $jumlah_restok);
mysqli_stmt_execute($stmtIns);

echo (mysqli_stmt_affected_rows($stmtIns) > 0)
    ? "Pengajuan restok berhasil dibuat, menunggu persetujuan."
    : "Gagal menyimpan pengajuan restok.";
?>
