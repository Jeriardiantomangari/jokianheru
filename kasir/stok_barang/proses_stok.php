<?php
session_start();
include '../../koneksi/koneksi.php';

function out_json($arr){
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($arr);
    exit;
}

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'kasir') {
    out_json(['error' => 'Akses ditolak']);
}
// AMBIL DATA (untuk modal)
if (($_POST['aksi'] ?? '') === 'ambil') {
    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) out_json(['error' => 'ID tidak valid']);

   $sql = "
    SELECT
      so.Id_stok_outlet,
      COALESCE(so.Jumlah_stok,0) AS Jumlah_stok,
      b.nama_barang,
      k.nama_kategori
    FROM stok_outlet so
    JOIN barang b ON b.id_barang = so.id_barang
    JOIN kategori k ON k.id_kategori = b.id_kategori
    WHERE so.Id_stok_outlet = ?
    LIMIT 1
";

    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);

    if (!$res || mysqli_num_rows($res) === 0) out_json(['error' => 'Data stok tidak ditemukan']);

    $row = mysqli_fetch_assoc($res);

  out_json([
  'id'         => (int)$row['Id_stok_outlet'],
  'nama_barang'=> (string)$row['nama_barang'],
  'kategori'   => (string)$row['nama_kategori'],
  'stok'       => (int)$row['Jumlah_stok']
]);

}
// SIMPAN PEMAKAIAN
if (($_POST['aksi'] ?? '') === 'simpan') {

    $id = (int)($_POST['id'] ?? 0);
    $jumlah = (int)($_POST['jumlah_digunakan'] ?? 0);

    if ($id <= 0) out_json(['error' => 'ID stok tidak valid']);
    if ($jumlah <= 0) out_json(['error' => 'Jumlah digunakan harus diisi']);

    mysqli_begin_transaction($conn);

    try {
        $lock = mysqli_prepare($conn, "SELECT COALESCE(Jumlah_stok,0) AS Jumlah_stok FROM stok_outlet WHERE Id_stok_outlet = ? FOR UPDATE");
        mysqli_stmt_bind_param($lock, "i", $id);
        mysqli_stmt_execute($lock);
        $res = mysqli_stmt_get_result($lock);
        if (!$res || mysqli_num_rows($res) === 0) throw new Exception("Data stok tidak ditemukan");

        $row = mysqli_fetch_assoc($res);
        $stok_now = (int)$row['Jumlah_stok'];

        if ($jumlah > $stok_now) {
            throw new Exception("Stok tidak cukup. Stok sekarang: $stok_now");
        }

        // kurangi stok
        $upd = mysqli_prepare($conn, "UPDATE stok_outlet SET Jumlah_stok = COALESCE(Jumlah_stok,0) - ? WHERE Id_stok_outlet = ?");
        mysqli_stmt_bind_param($upd, "ii", $jumlah, $id);
        mysqli_stmt_execute($upd);

        mysqli_commit($conn);

        out_json([
            'success' => true,
            'message' => "Berhasil. Stok berkurang $jumlah."
        ]);

    } catch (Throwable $e) {
        mysqli_rollback($conn);
        out_json(['error' => "Gagal: " . $e->getMessage()]);
    }
}

out_json(['error' => 'Aksi tidak valid']);
