<?php 
session_start();
include '../../koneksi/sidebargudang.php'; 
include '../../koneksi/koneksi.php'; 

// Cek role
if(!isset($_SESSION['role']) || $_SESSION['role'] != 'gudang'){
    header("Location: ../../index.php"); 
    exit;
}
?>
<!-- CDN jQuery dan DataTables -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>

<!-- FontAwesome -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<!-- jsPDF -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.28/jspdf.plugin.autotable.min.js"></script>

<style>
.konten-utama { 
  margin-left:250px; 
  margin-top:60px; 
  padding:30px; 
  min-height:calc(100vh - 60px); 
  background: radial-gradient(circle at top left, #fff7e0 0%, #ffe3b3 40%, #ffffff 100%);
  font-family:Arial,sans-serif; 
}

.konten-utama h2 { 
  margin-bottom:20px; 
  color:#b71c1c; 
  font-weight:700;
  letter-spacing:.5px;
}

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

.tombol i { font-size:12px; }

.tombol:hover { 
  transform: translateY(-1px);
  box-shadow:0 2px 6px rgba(0,0,0,0.18);
}

.tombol-cetak { 
  background:#43a047; 
  margin-right:10px; 
  padding:8px 15px; 
}

.tabel-barang { 
  width:100%; 
  border-collapse:collapse; 
  background:white; 
  border-radius:12px; 
  overflow:hidden; 
  box-shadow:0 3px 10px rgba(0,0,0,0.12); 
  table-layout:fixed; 
}

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

.tabel-barang thead tr {
  background: linear-gradient(90deg, #d32f2f, #ffb300);
}

.tabel-barang th { 
  color:#ffffff; 
  text-align:left; 
  padding:12px 15px; 
  font-weight:600;
  font-size:14px;
}

.tabel-barang td { 
  padding:10px 15px; 
  border-bottom:1px solid #ffe0b2; 
  border-right:1px solid #fff3e0; 
  font-size:14px;
  color:#424242;
}

.tabel-barang tr:nth-child(even){
  background:#fffdf7;
}

.tr-merah  { background:#ffebee !important; }
.tr-merah, .tr-merah td, .tr-merah th, .tr-merah a, .tr-merah span, .tr-merah strong {
  color:#b71c1c !important;
  font-weight:700;
}

.tr-kuning { background:#fff8e1 !important; }
.tr-kuning, .tr-kuning td, .tr-kuning th, .tr-kuning a, .tr-kuning span, .tr-kuning strong {
  color:#8d6e00 !important;
  font-weight:700;
}

.tr-hijau  { background:#e8f5e9 !important; }
.tr-hijau, .tr-hijau td, .tr-hijau th, .tr-hijau a, .tr-hijau span, .tr-hijau strong {
  color:#1b5e20 !important;
  font-weight:700;
}

.tabel-barang td small{
  font-size:13px;
  margin-left:6px;
  font-weight:600;
  text-transform:lowercase;
}

/* === SATUAN IKUT WARNA STATUS === */
.tr-merah td small{
  color:#b71c1c !important;
  font-weight:700;
}

.tr-kuning td small{
  color:#8d6e00 !important;
  font-weight:700;
}

.tr-hijau td small{
  color:#1b5e20 !important;
  font-weight:700;
}
/* Responsif */
@media screen and (max-width: 768px) {
  .konten-utama {
    margin-left: 0;
    padding: 20px;
    width: 100%;
    background: radial-gradient(circle at top, #fff7e0 0%, #ffe3b3 55%, #ffffff 100%);
    text-align: center;
  }

  .konten-utama h2 { text-align:center; }

  .konten-utama .tombol-cetak {
    display:inline-block;
    margin:5px auto;
  }

  .tabel-barang, thead, tbody, th, td, tr { display:block; }
  thead tr { display:none; }

  tr {
    margin-bottom: 15px;
    border-bottom: 2px solid #d32f2f;
    border-radius:10px;
    overflow:hidden;
    background:#ffffff;
  }

  td {
    text-align:right;
    padding-left:50%;
    position:relative;
    border-right:none;
    border-bottom:1px solid #ffe0b2;
  }

  td::before {
    content: attr(data-label);
    position:absolute;
    left:15px;
    width:45%;
    font-weight:600;
    text-align:left;
    color:#b71c1c;
  }
}
</style>

<div class="konten-utama">
  <h2>Laporan Stok Barang</h2>

  <button class="tombol tombol-cetak"><i class="fa-solid fa-print"></i> Cetak</button>

  <table id="tabel-barang" class="tabel-barang">
    <thead>
      <tr>
        <th>No.</th>
        <th>Nama Barang</th>
        <th>Kategori</th>
        <th>Jumlah Stok</th>
        <th>Status Stok</th>
      </tr>
    </thead>
    <tbody>
      <?php
      $no = 1;

      // JOIN stok_gudang dengan barang untuk ambil minimal_stok_gudang
    $query = mysqli_query($conn, "
  SELECT 
    sg.Id_stok_gudang,
    sg.Id_barang,
    sg.Nama_barang,
    sg.Kategori,
    sg.Jumlah_stok,
    b.minimal_stok_gudang,
    b.satuan,
    COALESCE(k.nama_kategori, sg.Kategori) AS nama_kategori
  FROM stok_gudang sg
  JOIN barang b ON b.id_barang = sg.Id_barang
  LEFT JOIN kategori k ON k.id_kategori = sg.Kategori
  ORDER BY sg.Id_stok_gudang ASC
");

      if(!$query){
        die("Query gagal: " . mysqli_error($conn));
      }

      while($row = mysqli_fetch_assoc($query)) {
        $stok = (int)$row['Jumlah_stok'];
        $min  = (int)$row['minimal_stok_gudang'];

        // 3 kondisi:
        // Kurang  : stok < min          => merah
        // Medium  : stok >= min & <2min => kuning
        // Banyak  : stok >= 2min        => hijau
        if ($stok < $min) {
          $kelas = 'tr-merah';
          $peringatan = 'Kurang';
        } elseif ($stok >= 2 * $min) {
          $kelas = 'tr-hijau';
          $peringatan = 'Banyak';
        } else {
          $kelas = 'tr-kuning';
          $peringatan = 'Sedang';
        }
      ?>
      <tr class="<?= $kelas; ?>">
        <td data-label="No"><?= $no++; ?></td>
        <td data-label="Nama Barang"><?= htmlspecialchars($row['Nama_barang']); ?></td>
        <td data-label="Kategori"><?= htmlspecialchars($row['nama_kategori']); ?></td>
        <td data-label="Jumlah Stok">
  <?= $stok; ?>
  <?php if (!empty($row['satuan'])): ?>
    <small><?= htmlspecialchars($row['satuan']); ?></small>
  <?php endif; ?>
</td>
        <td data-label="Peringatan"><?= $peringatan; ?></td>
      </tr>
      <?php } ?>
    </tbody>
  </table>
</div>

<script>
// DataTables
$(document).ready(function () {
  $('#tabel-barang').DataTable({
    "pageLength": 10,
    "lengthMenu": [5, 10, 25, 50],
    "language": {
      "emptyTable": "Tidak ada data tersedia",
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
  });
});

// Cetak PDF
$('.tombol-cetak').click(function(){
  const { jsPDF } = window.jspdf;
  const doc = new jsPDF({ orientation:'portrait', unit:'mm', format:'a4' });

  doc.setFontSize(14);
  doc.text("Laporan Stok Barang", 105, 15, {align:"center"});

  let headers = [];
  $('#tabel-barang thead th').each(function(){ 
    headers.push($(this).text()); 
  });

  let data = [];
  $('#tabel-barang tbody tr').each(function(){
    let rowData = [];
    $(this).find('td').each(function(){ 
      rowData.push($(this).text()); 
    });
    data.push(rowData);
  });

  doc.autoTable({
    head:[headers],
    body:data,
    startY:20,
    theme:'grid',
    headStyles:{fillColor:[211,47,47], textColor:255},
    styles:{fontSize:10},
    margin:{top:20}
  });

  doc.save('Laporan_Stok_Barang.pdf');
});
</script>
