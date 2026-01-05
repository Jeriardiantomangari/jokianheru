<?php
session_start();
include '../../koneksi/sidebarowner.php';
include '../../koneksi/koneksi.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'owner') {
    header("Location: ../../index.php");
    exit;
}
if (!isset($koneksi) && isset($conn)) {
    $koneksi = $conn;
}

$id_outlet_filter = $_GET['id_outlet'] ?? 'all';

// ====== AMBIL DATA OUTLET UNTUK FILTER ======
$outlets = [];
$qOutlet = $koneksi->query("SELECT id_outlet, nama_outlet FROM outlet ORDER BY nama_outlet");
if ($qOutlet) {
    while ($rowO = $qOutlet->fetch_assoc()) $outlets[] = $rowO;
}
$sqlLaporan = "
    SELECT
        COALESCE(o.nama_outlet, '(Belum terhubung)') AS nama_outlet,
        b.nama_barang,
        k.nama_kategori AS nama_kategori,
        b.harga,
        COALESCE(sg.Jumlah_stok, 0) AS stok_gudang,
        COALESCE(so.Jumlah_stok, 0) AS stok_outlet
    FROM stok_outlet so
    JOIN barang b
        ON b.id_barang = so.Id_barang
    LEFT JOIN kategori k
        ON k.id_kategori = b.id_kategori
    LEFT JOIN stok_gudang sg
        ON sg.Id_barang = b.id_barang
    LEFT JOIN (
        SELECT r1.Id_stok_outlet, r1.Id_outlet
        FROM restok_bahan_outlet r1
        JOIN (
            SELECT Id_stok_outlet, MAX(Id_restok_bahan) AS max_id
            FROM restok_bahan_outlet
            GROUP BY Id_stok_outlet
        ) r2
          ON r2.Id_stok_outlet = r1.Id_stok_outlet
         AND r2.max_id = r1.Id_restok_bahan
    ) map
        ON map.Id_stok_outlet = so.Id_stok_outlet
    LEFT JOIN outlet o
        ON o.id_outlet = map.Id_outlet
    WHERE 1=1
";

$params = [];
$types  = "";

if ($id_outlet_filter !== 'all') {
    $sqlLaporan .= " AND o.id_outlet = ? ";
    $params[] = (int)$id_outlet_filter;
    $types   .= "i";
}

$sqlLaporan .= " ORDER BY nama_outlet, b.nama_barang";

$stmtLap = $koneksi->prepare($sqlLaporan);
if (!$stmtLap) {
    die("Gagal prepare laporan stok: " . $koneksi->error);
}
if (!empty($params)) {
    $stmtLap->bind_param($types, ...$params);
}

$stmtLap->execute();
$resultLap = $stmtLap->get_result();

$rows = [];
while ($r = $resultLap->fetch_assoc()) {
    $rows[] = $r;
}
$stmtLap->close();
?>

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>

<style>
body{
margin:0;
font-family:Arial,sans-serif;
background: radial-gradient(circle at top left,#fff7e0 0%,#ffe3b3 40%,#ffffff 100%);}

.konten-utama{
  margin-left:250px;
  margin-top:60px;
  padding:30px;
  min-height:calc(100vh - 60px);}
  
.konten-utama h2{
  margin-bottom:20px;
  color:#b71c1c;
  font-weight:700;
  letter-spacing:.5px;}

.tabel-ajukan{
  width:100%;
  border-collapse:collapse;
  background:white;
  border-radius:12px;
  overflow:hidden;
  box-shadow:0 3px 10px rgba(0,0,0,0.12);
  table-layout:fixed;}

.dataTables_wrapper .dataTables_filter input,
.dataTables_wrapper .dataTables_length select{
  padding:6px 10px;
  border-radius:20px;
  border:1px solid #ffcc80;
  font-size:14px;
  margin-bottom:8px;
  outline:none;}

.tabel-ajukan thead tr{
  background: linear-gradient(90deg, #d32f2f, #ffb300);}

.tabel-ajukan th{
  color:#ffffff;
  text-align:left;
  padding:12px 15px;
  font-weight:600;
  font-size:14px;}

.tabel-ajukan td{
  padding:10px 15px;
  border-bottom:1px solid #ffe0b2;
  border-right:1px solid #fff3e0;
  font-size:13px;
  color:#424242;}

.tabel-ajukan tr:nth-child(even){
  background:#fffdf7;}

@media screen and (max-width: 768px) {
  .konten-utama{
    margin-left:0;
    padding:20px;
    width:100%;
    text-align:center;}

  .tabel-ajukan, thead, tbody, th, td, tr {
    display:block;}

  thead tr{
    display:none;}

  tr{
    margin-bottom:15px;
    border-bottom:2px solid #d32f2f;
    border-radius:10px;
    overflow:hidden;
    background:#fff;}

  td{
    text-align:right;
    padding-left:50%;
    position:relative;
    border-right:none;
    border-bottom:1px solid #ffe0b2;}

  td::before{
    content:attr(data-label);
    position:absolute;
    left:15px;
    width:45%;
    font-weight:600;
    text-align:left;
    color:#b71c1c;}

  form{
    width:100%;
    display:flex;
    justify-content:center !important;}

  form div{
    width:100%;
    text-align:center;}

  form select{
    width:70%;
    margin:0 auto;
    display:block;}
}
</style>

<div class="konten-utama">
  <h2>Laporan Stok Barang per Outlet</h2>

  <form method="get" style="margin-bottom:15px; display:flex; flex-wrap:wrap; gap:10px; align-items:flex-end;">
    <div>
      <label style="font-size:13px; color:#555; display:block; margin-bottom:4px;">Outlet</label>
      <select name="id_outlet"
              style="padding:7px 10px; border-radius:6px; border:1px solid #ffcc80;"
              onchange="this.form.submit()">
        <option value="all" <?= $id_outlet_filter === 'all' ? 'selected' : ''; ?>>Semua Outlet</option>
        <?php foreach($outlets as $o): ?>
          <option value="<?= (int)$o['id_outlet']; ?>" <?= $id_outlet_filter == $o['id_outlet'] ? 'selected' : ''; ?>>
            <?= htmlspecialchars($o['nama_outlet']); ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>
  </form>

  <table id="tabel-stok-outlet" class="tabel-ajukan">
    <thead>
      <tr>
        <th>Outlet</th>
        <th>Nama Barang</th>
        <th>Kategori</th>
        <th>Harga</th>
        <th>Stok Gudang</th>
        <th>Stok Outlet</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach($rows as $row): ?>
        <tr>
          <td data-label="Outlet"><?= htmlspecialchars($row['nama_outlet']); ?></td>
          <td data-label="Nama Barang"><?= htmlspecialchars($row['nama_barang']); ?></td>
          <td data-label="Kategori"><?= htmlspecialchars($row['nama_kategori'] ?? '-'); ?></td>
          <td data-label="Harga">Rp <?= number_format((int)$row['harga'],0,',','.'); ?></td>
          <td data-label="Stok Gudang"><?= (int)$row['stok_gudang']; ?></td>
          <td data-label="Stok Outlet"><?= (int)$row['stok_outlet']; ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<script>
$(document).ready(function () {
  $('#tabel-stok-outlet').DataTable({
    pageLength: 10,
    lengthMenu: [5, 10, 25, 50],
    language: {
      emptyTable: "Tidak ada data stok",
      info: "Menampilkan _START_ sampai _END_ dari _TOTAL_ data",
      infoEmpty: "Menampilkan 0 sampai 0 dari 0 data",
      infoFiltered: "(disaring dari _MAX_ data total)",
      lengthMenu: "Tampilkan _MENU_ data",
      loadingRecords: "Memuat...",
      processing: "Sedang diproses...",
      search: "Cari:",
      zeroRecords: "Tidak ditemukan data yang sesuai",
      paginate: { first: "Pertama", last: "Terakhir", next: "Berikutnya", previous: "Sebelumnya" }
    }
  });
});
</script>
