<?php  
session_start();
include '../../koneksi/sidebarowner.php'; 
include '../../koneksi/koneksi.php'; 

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'owner') {
    header("Location: ../../index.php");
    exit;
}

// ====== FILTER ======
// Tanggal selalu otomatis: hari ini (tanpa bisa diubah di UI)
$tanggal = date('Y-m-d');
$id_outlet_filter = $_GET['id_outlet'] ?? 'all';

// ====== SNAPSHOT LAPORAN STOK ======
// menyimpan stok ke tabel laporan_stok_barang untuk tanggal yang dipilih
$sqlSnapshot = "
    INSERT INTO laporan_stok_barang (
        tanggal,
        id_outlet,
        id_barang,
        stok_gudang,
        stok_outlet
    )
    SELECT
        ?            AS tanggal,
        so.id_outlet AS id_outlet,
        b.id         AS id_barang,
        b.stok       AS stok_gudang,
        so.stok      AS stok_outlet
    FROM stok_outlet so
    JOIN barang b ON b.id = so.id_barang
    ON DUPLICATE KEY UPDATE
        stok_gudang = VALUES(stok_gudang),
        stok_outlet = VALUES(stok_outlet),
        updated_at  = NOW()
";
$stmtSnap = $conn->prepare($sqlSnapshot);
if (!$stmtSnap) {
    die("Gagal prepare snapshot: " . $conn->error);
}
$stmtSnap->bind_param("s", $tanggal);
$stmtSnap->execute();
$stmtSnap->close();

// ====== AMBIL DATA OUTLET UNTUK FILTER ======
$outlets = [];
$qOutlet = mysqli_query($conn, "SELECT id, nama_outlet FROM outlet ORDER BY nama_outlet");
while ($rowO = mysqli_fetch_assoc($qOutlet)) {
    $outlets[] = $rowO;
}

// ====== AMBIL DATA LAPORAN STOK UNTUK TABEL ======
$rows = [];

$sqlLaporan = "
    SELECT 
        o.nama_outlet,
        b.nama_barang,
        b.jenis,
        b.harga,
        lsb.stok_gudang,
        lsb.stok_outlet
    FROM laporan_stok_barang lsb
    JOIN outlet o ON o.id = lsb.id_outlet
    JOIN barang b ON b.id = lsb.id_barang
    WHERE lsb.tanggal = ?
";

$params = [$tanggal];
$types  = "s";

if ($id_outlet_filter !== 'all') {
    $sqlLaporan .= " AND lsb.id_outlet = ? ";
    $params[] = $id_outlet_filter;
    $types   .= "i";
}

$sqlLaporan .= " ORDER BY o.nama_outlet, b.nama_barang";

$stmtLap = $conn->prepare($sqlLaporan);
if (!$stmtLap) {
    die("Gagal prepare laporan stok: " . $conn->error);
}
$stmtLap->bind_param($types, ...$params);
$stmtLap->execute();
$resultLap = $stmtLap->get_result();

while ($r = $resultLap->fetch_assoc()) {
    $rows[] = $r;
}

$stmtLap->close();
?>
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>
body {
  margin: 0;
  font-family: Arial, sans-serif;
  background: radial-gradient(circle at top left, #fff7e0 0%, #ffe3b3 40%, #ffffff 100%);
}

.konten-utama { 
  margin-left:250px; 
  margin-top:60px; 
  padding:30px; 
  min-height:calc(100vh - 60px); 
}

.konten-utama h2 { 
  margin-bottom:20px; 
  color:#b71c1c; 
  font-weight:700;
  letter-spacing:.5px;
}

/* TOMBOL */
.tombol { 
  border:none; 
  border-radius:6px; 
  cursor:pointer; 
  color:white; 
  font-size:11px; 
  transition:0.25s; 
  display:inline-flex;
  align-items:center;
  gap:4px;
}

.tombol i {
  font-size:12px;
}

.tombol:hover { 
  transform: translateY(-1px);
  box-shadow:0 2px 6px rgba(0,0,0,0.18);
}

.tombol-setuju {
  background:#2e7d32;
  padding:5px 10px;
  margin-right:5px;
}

.tombol-tolak {
  background:#c62828;
  padding:5px 10px;
}

/* tombol hapus */
.tombol-hapus {
  background:#c62828;
  padding:5px 10px;
  border-radius:6px;
  border:none;
  cursor:pointer;
  font-size:11px;
  color:#fff;
}

/* TABEL */
.tabel-ajukan { 
  width:100%; 
  border-collapse:collapse; 
  background:white; 
  border-radius:12px; 
  overflow:hidden; 
  box-shadow:0 3px 10px rgba(0,0,0,0.12); 
  table-layout:fixed; 
}

/* DataTables controls */
.dataTables_wrapper .dataTables_filter input,
.dataTables_wrapper .dataTables_length select { 
  padding:6px 10px; 
  border-radius:20px; 
  border:1px solid #ffcc80; 
  font-size:14px; 
  margin-bottom:8px; 
  outline:none;
}

.dataTables_wrapper .dataTables_filter input:focus,
.dataTables_wrapper .dataTables_length select:focus {
  border-color:#fb8c00;
  box-shadow:0 0 0 2px rgba(251,140,0,0.15);
}

.tabel-ajukan thead tr {
  background: linear-gradient(90deg, #d32f2f, #ffb300);
}

.tabel-ajukan th { 
  color:#ffffff; 
  text-align:left; 
  padding:12px 15px; 
  font-weight:600;
  font-size:14px;
}

.tabel-ajukan td { 
  padding:10px 15px; 
  border-bottom:1px solid #ffe0b2; 
  border-right:1px solid #fff3e0; 
  font-size:13px;
  color:#424242;
}

.tabel-ajukan tr:nth-child(even){
  background:#fffdf7;
}

@media screen and (max-width: 768px) {
  .konten-utama {
    margin-left: 0;
    padding: 20px;
    width: 100%;
    background: radial-gradient(circle at top, #fff7e0 0%, #ffe3b3 55%, #ffffff 100%);
    text-align: center;
  }

  .konten-utama h2 {
    text-align: center;
  }

  .konten-utama .tombol-cetak,
  .konten-utama .tombol-tambah {
    display: inline-block;
    margin: 5px auto;
  }

  .tabel-barang,
  thead,
  tbody,
  th,
  td,
  tr {
    display: block;
  }

  thead tr {
    display: none;
  }

  tr {
    margin-bottom: 15px;
    border-bottom: 2px solid #d32f2f;
    border-radius:10px;
    overflow:hidden;
    background:#ffffff;
  }

  td {
    text-align: right;
    padding-left: 50%;
    position: relative;
    border-right:none;
    border-bottom:1px solid #ffe0b2;
  }

  td::before {
    content: attr(data-label);
    position: absolute;
    left: 15px;
    width: 45%;
    font-weight: 600;
    text-align: left;
    color:#b71c1c;
  }

  .tombol-edit,
  .tombol-hapus {
    width: auto;
    padding: 6px 10px;
    display: inline-flex;
    margin: 3px 2px;
  }
  form {
    width: 100%;
    display: flex;
    justify-content: center !important;
  }

  form div {
    width: 100%;
    text-align: center;
  }

  form select {
    width: 60%; /* atau 80% jika ingin lebih besar */
    margin: 0 auto;
    display: block;
  }

  form label {
    text-align: center;
    width: 100%;
    display: block;
  }
}


</style>

<div class="konten-utama">
  <h2>Laporan Stok Barang per Outlet</h2>

  <!-- FILTER (HANYA OUTLET, TANPA TANGGAL & TANPA TOMBOL TAMPILKAN) -->
  <form method="get" style="margin-bottom:15px; display:flex; flex-wrap:wrap; gap:10px; align-items:flex-end;">
    <div>
      <label style="font-size:13px; color:#555; display:block; margin-bottom:4px;">Outlet</label>
      <select name="id_outlet" 
              style="padding:7px 10px; border-radius:6px; border:1px solid #ffcc80;"
              onchange="this.form.submit()">
        <option value="all" <?= $id_outlet_filter === 'all' ? 'selected' : ''; ?>>Semua Outlet</option>
        <?php foreach($outlets as $o): ?>
          <option value="<?= $o['id']; ?>" <?= $id_outlet_filter == $o['id'] ? 'selected' : ''; ?>>
            <?= htmlspecialchars($o['nama_outlet']); ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>
  </form>

  <table id="tabel-stok-outlet" class="tabel-ajukan">
    <thead>
      <tr>
        <!-- TIDAK ADA KOLUM NO. -->
        <th>Outlet</th>
        <th>Nama Barang</th>
        <th>Jenis</th>
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
          <td data-label="Jenis"><?= htmlspecialchars($row['jenis']); ?></td>
          <td data-label="Harga">Rp <?= number_format($row['harga'],0,',','.'); ?></td>
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
    "pageLength": 10,
    "lengthMenu": [5, 10, 25, 50],
    "language": {
      "emptyTable": "Tidak ada data stok",
      "info": "Menampilkan _START_ sampai _END_ dari _TOTAL_ data",
      "infoEmpty": "Menampilkan 0 sampai 0 dari 0 data",
      "infoFiltered": "(disaring dari _MAX_ data total)",
      "lengthMenu": "Tampilkan _MENU_ data",
      "loadingRecords": "Memuat...",
      "processing": "Sedang diproses...",
      "search": "Cari:",
      "zeroRecords": "Tidak ditemukan data yang sesuai",
      "paginate": {
        "first": "Pertama",
        "last": "Terakhir",
        "next": "Berikutnya",
        "previous": "Sebelumnya"
      }
    }
    // tidak ada columnDefs: semua kolom tetap align bawaan (CSS sudah text-align:left)
  });
});
</script>
