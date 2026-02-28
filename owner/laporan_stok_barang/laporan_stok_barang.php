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

/* =========================
   AMBIL DATA OUTLET UNTUK FILTER
========================= */
$outlets = [];
$qOutlet = $koneksi->query("SELECT id_outlet, nama_outlet FROM outlet ORDER BY nama_outlet");
if ($qOutlet) {
    while ($rowO = $qOutlet->fetch_assoc()) $outlets[] = $rowO;
}

/* =========================
   LAPORAN STOK BARANG PER OUTLET
========================= */
$sqlLaporan = "
    SELECT
        o.nama_outlet AS nama_outlet,
        b.nama_barang,
        b.satuan,
        k.nama_kategori AS nama_kategori,
        b.harga,
        b.minimal_stok_outlet,
        COALESCE(sg.Jumlah_stok, 0) AS stok_gudang,
        COALESCE(so.Jumlah_stok, 0) AS stok_outlet
    FROM stok_outlet so
    JOIN outlet o
        ON o.id_outlet = so.id_outlet
    JOIN barang b
        ON b.id_barang = so.Id_barang
    LEFT JOIN kategori k
        ON k.id_kategori = b.id_kategori
    LEFT JOIN stok_gudang sg
        ON sg.Id_barang = b.id_barang
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

/* =========================
   DATA KHUSUS STOK GUDANG
========================= */
$sqlGudang = "
    SELECT 
        b.nama_barang,
        b.minimal_stok_gudang,
        COALESCE(sg.Jumlah_stok, 0) AS stok_gudang
    FROM stok_gudang sg
    JOIN barang b ON b.id_barang = sg.id_barang
    ORDER BY b.nama_barang
";

$resGudang = $koneksi->query($sqlGudang);

$rowsGudang = [];
while ($g = $resGudang->fetch_assoc()) {
    $rowsGudang[] = $g;
}

/* =========================
   (A) MONITORING RESTOK OUTLET -> GUDANG
   sumber: restok_bahan_outlet
========================= */
$sqlRestok = "
  SELECT
    r.Id_restok_bahan,
    r.Id_outlet,
    COALESCE(o.nama_outlet, '(Outlet tidak diketahui)') AS nama_outlet,
    r.Id_stok_outlet,
    COALESCE(so.Nama_barang, r.Nama_barang) AS nama_barang,
    b.satuan,
    r.Jumlah_restok,
    COALESCE(bm.Bahan_masuk, 0) AS barang_masuk,
    r.Status,
    r.Catatan
  FROM restok_bahan_outlet r
  LEFT JOIN outlet o
    ON o.id_outlet = r.Id_outlet
  LEFT JOIN stok_outlet so
    ON so.Id_stok_outlet = r.Id_stok_outlet
  LEFT JOIN barang b
    ON b.nama_barang = COALESCE(so.Nama_barang, r.Nama_barang)
  LEFT JOIN bahan_masuk bm
    ON bm.Id_restok_bahan = r.Id_restok_bahan
  WHERE 1=1
";

$paramsR = [];
$typesR  = "";

if ($id_outlet_filter !== 'all') {
  $sqlRestok .= " AND r.Id_outlet = ? ";
  $paramsR[] = (int)$id_outlet_filter;
  $typesR   .= "i";
}

$sqlRestok .= " ORDER BY r.Id_restok_bahan DESC";

$stmtR = $koneksi->prepare($sqlRestok);
if (!$stmtR) {
  die("Gagal prepare monitoring restok: " . $koneksi->error);
}
if (!empty($paramsR)) {
  $stmtR->bind_param($typesR, ...$paramsR);
}

$stmtR->execute();
$resR = $stmtR->get_result();

$restokRows = [];
while ($rr = $resR->fetch_assoc()) {
  $restokRows[] = $rr;
}
$stmtR->close();
?>

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>
body{
  margin:0;
  font-family:Arial,sans-serif;
  background: radial-gradient(circle at top left,#fff7e0 0%,#ffe3b3 40%,#ffffff 100%);
}

.konten-utama{
  margin-left:250px;
  margin-top:60px;
  padding:30px;
  min-height:calc(100vh - 60px);
}

.konten-utama h2{
  margin-bottom:20px;
  color:#b71c1c;
  font-weight:700;
  letter-spacing:.5px;
}

.tabel-ajukan{
  width:100%;
  border-collapse:collapse;
  background:white;
  border-radius:12px;
  overflow:hidden;
  box-shadow:0 3px 10px rgba(0,0,0,0.12);
  table-layout:fixed;
}

.dataTables_wrapper .dataTables_filter input,
.dataTables_wrapper .dataTables_length select{
  padding:6px 10px;
  border-radius:20px;
  border:1px solid #ffcc80;
  font-size:14px;
  margin-bottom:8px;
  outline:none;
}

.tabel-ajukan thead tr{
  background: linear-gradient(90deg, #d32f2f, #ffb300);
}

.tabel-ajukan th{
  color:#ffffff;
  text-align:left;
  padding:12px 15px;
  font-weight:600;
  font-size:14px;
}

.tabel-ajukan td{
  padding:10px 15px;
  border-bottom:1px solid #ffe0b2;
  border-right:1px solid #fff3e0;
  font-size:13px;
  color:#424242;
  word-wrap:break-word;
}

.tabel-ajukan tr:nth-child(even){
  background:#fffdf7;
}

/* BOX GRAFIK */
.kotak_grafik{
  margin-top:20px;
  margin-bottom:20px;
  background:#fff;
  border-radius:12px;
  padding:18px;
  box-shadow:0 3px 10px rgba(0,0,0,0.12);
}
.judul_grafik{
  font-weight:700;
  color:#b71c1c;
  margin:0 0 10px 0;
  font-size:14px;
}

.tombol{
  border:none;
  border-radius:6px;
  cursor:pointer;
  color:white;
  font-size:11px;
  transition:.25s;
  display:inline-flex;
  align-items:center;
  gap:6px;
  padding:6px 10px;
}
.tombol:hover{
  transform:translateY(-1px);
  box-shadow:0 2px 6px rgba(0,0,0,.18);
}
.tombol-hapus{
  background:#c62828;
}

/* ===== LEGEND WARNA STOK ===== */
.keterangan{
  display:flex;
  gap:20px;
  margin-top:12px;
  justify-content:flex-start;
  align-items:center;
  flex-wrap:wrap;
  font-size:13px;
  color:#444;
}
.keterangan_warnah{
  display:flex;
  align-items:center;
  gap:6px;
}
.warnah{
  width:14px;
  height:14px;
  border-radius:4px;
  display:inline-block;
}
.warnah.merah{ background:#f44336; }
.warnah.kuning{ background:#ffc107; }
.warnah.hijau{ background:#4caf50; }

/* badge status restok */
.badge{
  padding:4px 10px;
  border-radius:999px;
  font-size:12px;
  font-weight:700;
  display:inline-block;
}
.badge.menunggu{ background:#ffebee; color:#b71c1c; }
.badge.dikirim{ background:#fff8e1; color:#8d6e00; }
.badge.selesai{ background:#e8f5e9; color:#1b5e20; }

.tabel-ajukan td small{
  color:#757575;
  font-size:13px;
  margin-left:6px;
  font-weight:600;
  text-transform:lowercase;
}

@media screen and (max-width: 768px) {
  .konten-utama{
    margin-left:0;
    padding:20px;
    width:100%;
    text-align:center;
  }

  .tabel-ajukan, thead, tbody, th, td, tr { display:block; }
  thead tr{ display:none; }

  tr{
    margin-bottom:15px;
    border-bottom:2px solid #d32f2f;
    border-radius:10px;
    overflow:hidden;
    background:#fff;
  }

  td{
    text-align:right;
    padding-left:50%;
    position:relative;
    border-right:none;
    border-bottom:1px solid #ffe0b2;
  }

  td::before{
    content:attr(data-label);
    position:absolute;
    left:15px;
    width:45%;
    font-weight:600;
    text-align:left;
    color:#b71c1c;
  }

  form{
    width:100%;
    display:flex;
    justify-content:center !important;
  }

  form div{
    width:100%;
    text-align:center;
  }

  form select{
    width:70%;
    margin:0 auto;
    display:block;
  }
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
         <td data-label="Stok Gudang">
  <?= (int)$row['stok_gudang']; ?>
  <?php if (!empty($row['satuan'])): ?>
    <small><?= htmlspecialchars($row['satuan']); ?></small>
  <?php endif; ?>
</td>

<td data-label="Stok Outlet">
  <?= (int)$row['stok_outlet']; ?>
  <?php if (!empty($row['satuan'])): ?>
    <small><?= htmlspecialchars($row['satuan']); ?></small>
  <?php endif; ?>
</td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>

  <!-- GRAFIK STOK OUTLET -->
  <div class="kotak_grafik">
    <div class="judul_grafik">Grafik Stok Outlet (per Barang)</div>
    <canvas id="stokChart" height="110"></canvas>

    <div class="keterangan">
      <div class="keterangan_warnah"><span class="warnah merah"></span> Kurang</div>
      <div class="keterangan_warnah"><span class="warnah kuning"></span> Sedang</div>
      <div class="keterangan_warnah"><span class="warnah hijau"></span> Banyak</div>
    </div>
  </div>

  <!-- GRAFIK STOK GUDANG -->
  <div class="kotak_grafik">
    <div class="judul_grafik">Grafik Stok Gudang (per Barang)</div>
    <canvas id="gudangChart" height="110"></canvas>

    <div class="keterangan">
      <div class="keterangan_warnah"><span class="warnah merah"></span> Kurang</div>
      <div class="keterangan_warnah"><span class="warnah kuning"></span> Sedang</div>
      <div class="keterangan_warnah"><span class="warnah hijau"></span> Banyak</div>
    </div>
  </div>

  <div style="margin-top:22px;"></div>

  <!-- =========================
       MONITORING RESTOK (A)
  ========================== -->
  <h2 style="margin-top:10px;">Monitoring Restok Outlet → Gudang</h2>

  <table id="tabel-restok" class="tabel-ajukan">
    <thead>
      <tr>
        <th>ID Restok</th>
        <th>Outlet</th>
        <th>Nama Barang</th>
        <th>Jumlah Restok</th>
        <th>Jml Barang Masuk</th>
        <th>Status</th>
        <th>Alasan Penolakan</th>
        <th>Aksi</th>
      </tr>
    </thead>
  <tbody>
  <?php foreach($restokRows as $r): ?>
    <?php
      $st = strtolower(trim($r['Status'] ?? 'menunggu'));
      $cls = 'menunggu';
      if ($st === 'selesai') $cls = 'selesai';
      else if ($st === 'dikirim') $cls = 'dikirim';

      $statusLower = $st;
      $catatan = trim($r['Catatan'] ?? '');
      $isDitolak = in_array($statusLower, ['ditolak', 'tolak', 'rejected']);
    ?>
    <tr>
      <td data-label="ID Restok"><?= (int)$r['Id_restok_bahan']; ?></td>
      <td data-label="Outlet"><?= htmlspecialchars($r['nama_outlet']); ?></td>
      <td data-label="Nama Barang"><?= htmlspecialchars($r['nama_barang'] ?? '-'); ?></td>

      <td data-label="Jumlah Restok">
        <?= (int)$r['Jumlah_restok']; ?>
        <?php if (!empty($r['satuan'])): ?>
          <small><?= htmlspecialchars($r['satuan']); ?></small>
        <?php endif; ?>
      </td>

      <td data-label="Jml Barang Masuk">
        <?= (int)($r['barang_masuk'] ?? 0); ?>
        <?php if (!empty($r['satuan'])): ?>
          <small><?= htmlspecialchars($r['satuan']); ?></small>
        <?php endif; ?>
      </td>

      <td data-label="Status">
        <span class="badge <?= $cls; ?>"><?= htmlspecialchars($r['Status'] ?? '-'); ?></span>
      </td>

      <td data-label="Alasan Penolakan">
        <?php
          if ($isDitolak) {
            echo $catatan !== '' ? htmlspecialchars($catatan) : '-';
          } else {
            echo '-';
          }
        ?>
      </td>

      <td data-label="Aksi">
        <?php if ($statusLower === 'selesai'): ?>
          <button class="tombol tombol-hapus" onclick="hapusRestokOwner(<?= (int)$r['Id_restok_bahan']; ?>)">
            Hapus
          </button>
        <?php else: ?>
          -
        <?php endif; ?>
      </td>

    </tr>
  <?php endforeach; ?>
</tbody>
  </table>

</div>

<script>
$(document).ready(function () {

  // ===== DATATABLES: STOK =====
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

  // ===== DATATABLES: RESTOK =====

  $('#tabel-restok').DataTable({
  pageLength: 10,
  lengthMenu: [5, 10, 25, 50],
  columnDefs: [{ orderable: false, targets: 6 }],
    language: {
      emptyTable: "Tidak ada data restok",
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

  // ===== DATA DARI PHP (STOK outlet) =====
  const rawRows = <?= json_encode($rows, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
   // ===== DATA DARI PHP (STOK gudang) =====
  const rawGudang = <?= json_encode($rowsGudang, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;

  // ===== WARNA SESUAI LEVEL STOK =====
  function pickColor(stok, min) {
    if (min <= 0) {
      if (stok <= 0) return 'rgba(244, 67, 54, 0.85)';
      if (stok < 10) return 'rgba(255, 193, 7, 0.85)';
      return 'rgba(76, 175, 80, 0.85)';
    }
    if (stok < min) return 'rgba(244, 67, 54, 0.85)';
    if (stok < (min * 2)) return 'rgba(255, 193, 7, 0.85)';
    return 'rgba(76, 175, 80, 0.85)';
  }

  // ===== PREP DATA: STOK OUTLET =====
  const outletSet = new Set();
  const barangSet = new Set();

  rawRows.forEach(r => {
    outletSet.add((r.nama_outlet ?? '(Belum terhubung)').toString());
    barangSet.add((r.nama_barang ?? '').toString());
  });

  const outletLabels = Array.from(outletSet);
  const barangLabels = Array.from(barangSet);

  const dataMap = {};
  outletLabels.forEach(o => dataMap[o] = {});

  rawRows.forEach(r => {
    const outlet = (r.nama_outlet ?? '(Belum terhubung)').toString();
    const barang = (r.nama_barang ?? '').toString();
    const stokO  = parseInt(r.stok_outlet ?? 0, 10) || 0;
    const min    = parseInt(r.minimal_stok_outlet ?? 0, 10) || 0;

    if (!dataMap[outlet][barang]) {
      dataMap[outlet][barang] = { stok: 0, min: min };
    }
    dataMap[outlet][barang].stok += stokO;
    if (min > dataMap[outlet][barang].min) dataMap[outlet][barang].min = min;
  });

  const datasets = barangLabels.map(barang => {
    const data = outletLabels.map(outlet => {
      const cell = dataMap[outlet][barang];
      return cell ? cell.stok : 0;
    });

    const bgColors = outletLabels.map(outlet => {
      const cell = dataMap[outlet][barang];
      const stok = cell ? cell.stok : 0;
      const min  = cell ? cell.min  : 0;
      return pickColor(stok, min);
    });

    return {
      label: barang,
      data: data,
      backgroundColor: bgColors,
      borderWidth: 0
    };
  });

  // ===== CHART STOK OUTLET =====
  const canvas = document.getElementById('stokChart');
  if (canvas) {
    new Chart(canvas, {
      type: 'bar',
      data: {
        labels: outletLabels,
        datasets: datasets
      },
      options: {
        responsive: true,
        plugins: {
          legend: { display: false },
          tooltip: {
            callbacks: {
              label: function(context) {
                const outlet = context.label;
                const barang = context.dataset.label;
                const stok   = context.parsed.y;
                const cell = (dataMap[outlet] && dataMap[outlet][barang]) ? dataMap[outlet][barang] : null;
                const min  = cell ? cell.min : 0;
                return `${barang} | Stok: ${stok} | Minimal: ${min}`;
              }
            }
          }
        },
        scales: {
          y: { beginAtZero: true, ticks: { precision: 0 } }
        }
      }
    });
  }

  // ===== CHART STOK GUDANG (FIXED - AMBIL LANGSUNG DARI TABEL GUDANG) =====

const gudangLabels = rawGudang.map(r => r.nama_barang);
const gudangValues = rawGudang.map(r => parseInt(r.stok_gudang) || 0);

const gudangColors = rawGudang.map(r => {
  const stok = parseInt(r.stok_gudang) || 0;
  const min  = parseInt(r.minimal_stok_gudang) || 0;
  return pickColor(stok, min);
});

const canvasGudang = document.getElementById('gudangChart');
if (canvasGudang) {
  new Chart(canvasGudang, {
    type: 'bar',
    data: {
      labels: gudangLabels,
      datasets: [{
        data: gudangValues,
        backgroundColor: gudangColors,
        borderWidth: 0
      }]
    },
    options: {
      responsive: true,
      plugins: {
        legend: { display: false },
        tooltip: {
          callbacks: {
            label: function(context) {
              const index = context.dataIndex;
              const stok  = context.parsed.y;
              const min   = rawGudang[index].minimal_stok_gudang;
              return `Stok Gudang: ${stok} | Minimal: ${min}`;
            }
          }
        }
      },
      scales: {
        y: { beginAtZero: true, ticks: { precision: 0 } }
      }
    }
  });
}


});
function hapusRestokOwner(id){
  if(!confirm('Hapus data restok ini?')) return;
  $.post('proses_hapus_restok_owner.php', { id:id }, function(res){
    alert(res);
    location.reload();
  });
}

</script>
