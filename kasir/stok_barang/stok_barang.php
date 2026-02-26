<?php
session_start();
include '../../koneksi/sidebarkasir.php';
include '../../koneksi/koneksi.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'kasir') {
    header("Location: ../../index.php");
    exit;
}

if (!isset($_SESSION['id_outlet'])) {
    die("Outlet untuk kasir belum di-set. Pastikan kolom id_outlet di tabel akun dan session sudah benar.");
}

$id_outlet = (int)$_SESSION['id_outlet'];

// ambil nama outlet
$qOutlet = mysqli_query($conn, "SELECT nama_outlet FROM outlet WHERE id_outlet = $id_outlet");
$rowOutlet = mysqli_fetch_assoc($qOutlet);
$namaOutlet = $rowOutlet ? $rowOutlet['nama_outlet'] : 'Outlet Tidak Dikenal';
// query untuk mengambil data stok barang per outlet
$sql = "
  SELECT
    so.Id_stok_outlet,
    COALESCE(so.Jumlah_stok, 0) AS Jumlah_stok,
    b.nama_barang,
    k.nama_kategori,
    b.minimal_stok_outlet,
    b.satuan
  FROM stok_outlet so
  JOIN barang b ON b.id_barang = so.id_barang
  JOIN kategori k ON k.id_kategori = b.id_kategori
  WHERE so.id_outlet = $id_outlet
  ORDER BY b.nama_barang ASC
";


$qStok = mysqli_query($conn, $sql);
if (!$qStok) {
    die("Query gagal: " . mysqli_error($conn));
}
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">

  <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
  <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>

  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

  <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.28/jspdf.plugin.autotable.min.js"></script>

  <style>
    body{
      margin:0;
      font-family:Arial,sans-serif;
      background:radial-gradient(circle at top left,#fff7e0 0%,#ffe3b3 40%,#ffffff 100%);}

    .konten-utama{
      margin-left:250px;
      margin-top:60px;
      padding:30px;
      min-height:calc(100vh - 60px);}

    .konten-utama h2{
      margin-bottom:10px;
      color:#b71c1c;
      font-weight:700;
      letter-spacing:.5px;}

    .konten-utama p.info-outlet{
      margin-top:0;
      margin-bottom:20px;
      color:#555;}

    .tombol{
      border:none;
      border-radius:6px;
      cursor:pointer;
      color:white;
      font-size:11px;
      transition:.25s;
      display:inline-flex;
      align-items:center;
      gap:4px;}


    .tombol i{
      font-size:12px;}

    .tombol:hover{
      transform:translateY(-1px);
      box-shadow:0 2px 6px rgba(0,0,0,.18);}

    .tombol-cetak{
      background:#43a047;
      margin-right:10px;
      padding:8px 15px;}

    .tombol-edit{
      background:#fb8c00;
      padding:6px 10px;
      border-radius:6px;
      border:none;
      cursor:pointer;
      font-size:11px;
      color:#fff;}

    .dataTables_wrapper .dataTables_filter input,
    .dataTables_wrapper .dataTables_length select{
      padding:6px 10px;
      border-radius:20px;
      border:1px solid #ffcc80;
      font-size:14px;
      margin-bottom:8px;
      outline:none;
    }

    .dataTables_wrapper .dataTables_filter input:focus,
    .dataTables_wrapper .dataTables_length select:focus{
      border-color:#fb8c00;
      box-shadow:0 0 0 2px rgba(251,140,0,.15);
    }

    .tabel-stok-outlet{
      width:100%;
      border-collapse:collapse;
      background:white;
      border-radius:12px;
      overflow:hidden;
      box-shadow:0 3px 10px rgba(0,0,0,.12);
      table-layout:fixed;
    }

    .tabel-stok-outlet thead tr{
      background:linear-gradient(90deg,#d32f2f,#ffb300);}

    .tabel-stok-outlet th{
      color:#fff;
      text-align:left;
      padding:12px 15px;
      font-weight:600;
      font-size:14px;}

    .tabel-stok-outlet td{
      padding:10px 15px;
      border-bottom:1px solid #ffe0b2;
      border-right:1px solid #fff3e0;
      font-size:14px;
      color:#424242;}

    .tabel-stok-outlet tr:nth-child(even){
      background:#fffdf7;}

    .kotak-modal{
      display:none;
      position:fixed;
      z-index:300;
      left:0;
      top:0;
      width:100%;
      height:100vh;
      background:rgba(0,0,0,.55);
      justify-content:center;
      align-items:center;}

    .isi-modal{
      background:white;
      padding:25px;
      border-radius:12px;
      width:400px;
      max-width:90%;
      box-shadow:0 6px 18px rgba(0,0,0,.35);
      text-align:center;
      position:relative;
      border-top:4px solid #d32f2f;}

    .isi-modal h3{
      margin-bottom:16px;
      color:#b71c1c;
      font-size:18px;}

    .isi-modal input{
      width:100%;
      padding:10px;
      margin:6px 0;
      border:1px solid #ffcc80;
      border-radius:8px;
      font-size:14px;}

    .isi-modal input:focus{
      outline:none;
      border-color:#fb8c00;
      box-shadow:0 0 0 2px rgba(251,140,0,.18);}

    .isi-modal button{
      width:100%;
      padding:10px;
      border:none;
      border-radius:8px;
      background:#d32f2f;
      color:white;
      font-weight:600;
      cursor:pointer;
      margin-top:10px;
      letter-spacing:.5px;}

    .isi-modal button:hover{
      background:#b71c1c;}

    .tutup-modal{
      position:absolute;
      top:10px;
      right:12px;
      cursor:pointer;
      font-size:20px;
      color:#999;}

    .tutup-modal:hover{
      color:#d32f2f;}

tr.row-merah  {
  background:#ffebee !important; }

tr.row-merah, tr.row-merah td,
tr.row-merah th,
tr.row-merah a, 
tr.row-merah span, 
tr.row-merah i{
  color:#b71c1c !important;
  font-weight:700 !important;
}

tr.row-kuning { 
  background:#fff8e1 !important; }

tr.row-kuning, 
tr.row-kuning td, 
tr.row-kuning th, 
tr.row-kuning a, 
tr.row-kuning span, 
tr.row-kuning i{
  color:#8d6e00 !important;
  font-weight:700 !important;
}

tr.row-hijau  { 
  background:#e8f5e9 !important; }

tr.row-hijau, 
tr.row-hijau td,
tr.row-hijau th,
tr.row-hijau a,
tr.row-hijau span, 
tr.row-hijau i{
  color:#1b5e20 !important;
  font-weight:700 !important;
}

.tabel-stok-outlet td small{
  font-size:13px;
  margin-left:6px;
  font-weight:600;
  text-transform:lowercase;
}

/* satuan warna  */
tr.row-merah td small{
  color:#b71c1c !important;
  font-weight:700 !important;
}
tr.row-kuning td small{
  color:#8d6e00 !important;
  font-weight:700 !important;
}
tr.row-hijau td small{
  color:#1b5e20 !important;
  font-weight:700 !important;
}

 /* RESPONSIF  */
    @media screen and (max-width: 768px) {
      .konten-utama {
        margin-left: 0;
        padding: 20px;
        width: 100%;
        background: radial-gradient(circle at top, #fff7e0 0%, #ffe3b3 55%, #ffffff 100%);
        text-align: center;
      }

      .konten-utama h2,
      .konten-utama p.info-outlet {
        text-align: center;
      }

      .tombol-cetak {
        display: inline-flex;
        margin: 5px auto 15px auto;
      }
      .tabel-stok-outlet,
      .tabel-stok-outlet thead,
      .tabel-stok-outlet tbody,
      .tabel-stok-outlet th,
      .tabel-stok-outlet td,
      .tabel-stok-outlet tr {
        display: block;
      }

      .tabel-stok-outlet thead tr { display: none; }

      .tabel-stok-outlet tr {
        margin-bottom: 15px;
        border-bottom: 2px solid #d32f2f;
        border-radius: 10px;
        overflow: hidden;
        background: #ffffff;
      }

      .tabel-stok-outlet td {
        text-align: right;
        padding-left: 50%;
        position: relative;
        border-right: none;
        border-bottom: 1px solid #ffe0b2;
      }

      .tabel-stok-outlet td::before {
        content: attr(data-label);
        position: absolute;
        left: 15px;
        width: 45%;
        font-weight: 600;
        text-align: left;
        color: #b71c1c;
      }

    .tombol-edit {
  width: auto;
  padding: 6px 10px;
  display: inline-flex;
  align-items: center;   
  margin: 3px 2px;
  line-height: 1;
  gap:4px;
}

.tombol-edit i{
  line-height:1;
  display:inline-block;
}

    }

  </style>
</head>
<body>

<div class="konten-utama">
  <h2>Stok Barang di Outlet</h2>
  <p class="info-outlet">Outlet: <strong><?= htmlspecialchars($namaOutlet); ?></strong></p>

  <button class="tombol tombol-cetak"><i class="fa-solid fa-print"></i> Cetak</button>

  <table id="tabel-stok-outlet" class="tabel-stok-outlet">
    <thead>
      <tr>
        <th>No.</th>
        <th>Nama Barang</th>
        <th>Kategori</th>
        <th>Jumlah Stok</th>
        <th>Status Stok</th>
        <th>Aksi</th>
      </tr>
    </thead>
    <tbody>
      <?php
      $no = 1;
      while ($row = mysqli_fetch_assoc($qStok)) {

          $stok_now = (int)$row['Jumlah_stok'];
          $min_out  = (int)$row['minimal_stok_outlet'];

          // 3 kondisi peringatan
          if ($stok_now < $min_out) {
              $peringatan = "Kurang";
              $kelasRow   = "row-merah";
          } elseif ($stok_now >= 2 * $min_out) {
              $peringatan = "Banyak";
              $kelasRow   = "row-hijau";
          } else {
              $peringatan = "sedang";
              $kelasRow   = "row-kuning";
          }
      ?>
      <tr class="<?= $kelasRow; ?>">
        <td data-label="No"><?= $no++; ?></td>
        <td data-label="Nama_barang"><?= htmlspecialchars($row['nama_barang']); ?></td>
       <td data-label="Nama_kategori"><?= htmlspecialchars($row['nama_kategori']); ?></td>
     <td data-label="Jumlah Stok">
  <?= $stok_now; ?>
  <?php if (!empty($row['satuan'])): ?>
    <small><?= htmlspecialchars($row['satuan']); ?></small>
  <?php endif; ?>
</td>
        <td data-label="Status Stok"><?= $peringatan; ?></td>
        <td data-label="Aksi">
          <button class="tombol-edit" onclick="editStok(<?= (int)$row['Id_stok_outlet']; ?>)">
            <i class="fa-solid fa-pen-to-square"></i> Pakai Stok
          </button>
        </td>
      </tr>
      <?php } ?>
    </tbody>
  </table>
</div>

<!-- Modal -->
<div id="modalStok" class="kotak-modal">
  <div class="isi-modal">
    <span class="tutup-modal" onclick="tutupModalStok()">&times;</span>
    <h3>Input Pemakaian Barang</h3>

    <form id="formStok">
      <input type="hidden" name="id" id="id_stok">

      <input type="text" id="nama_barang" placeholder="Nama Barang" readonly>
      <input type="text" id="kategori" placeholder="Kategori" readonly>

      <input type="number" id="stok_sekarang" placeholder="Stok sekarang" readonly>

      <input type="number" min="1" name="jumlah_digunakan" id="jumlah_digunakan" placeholder="Jumlah digunakan" required>

      <input type="number" id="stok_setelah" placeholder="Stok setelah pemakaian" readonly>

      <button type="submit">Simpan</button>
    </form>
  </div>
</div>

<script>
$(document).ready(function () {
  $('#tabel-stok-outlet').DataTable({
    pageLength: 10,
    lengthMenu: [5, 10, 25, 50],
    columnDefs: [{ orderable: false, targets: 5 }], 
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

// ambil data stok untuk modal
function editStok(id) {
  $.ajax({
    url: 'proses_stok.php',
    type: 'POST',
    data: { aksi:'ambil', id:id },
    dataType: 'json',
    success: function(obj){
      if (obj.error) { alert(obj.error); return; }

      $('#id_stok').val(obj.id);
      $('#nama_barang').val(obj.nama_barang);
      $('#kategori').val(obj.kategori);
      $('#stok_sekarang').val(obj.stok);

      $('#jumlah_digunakan').val('');
      $('#stok_setelah').val('');

      $('#modalStok').css('display','flex');
    },
    error: function(xhr){
      alert('Server error: ' + xhr.responseText);
    }
  });
}

function tutupModalStok(){
  $('#modalStok').hide();
}

$('#jumlah_digunakan').on('input', function(){
  let stokSekarang = parseInt($('#stok_sekarang').val()) || 0;
  let jml          = parseInt($('#jumlah_digunakan').val()) || 0;
  let sisa         = stokSekarang - jml;
  if (sisa < 0) sisa = 0;
  $('#stok_setelah').val(sisa);
});

// simpan pemakaian stok
$('#formStok').submit(function(e){
  e.preventDefault();

  $.ajax({
    url: 'proses_stok.php',
    type: 'POST',
    data: $(this).serialize() + '&aksi=simpan',
    dataType: 'json',
    success: function(res){
      if(res.error){
        alert(res.error);
        return;
      }
      alert(res.message || 'Berhasil');
      $('#modalStok').hide();
      location.reload();
    },
    error: function(xhr){
      alert('Server error: ' + xhr.responseText);
    }
  });
});

// cetak PDF (tanpa kolom Aksi)
$('.tombol-cetak').click(function(){
  const { jsPDF } = window.jspdf;
  const doc = new jsPDF({ orientation:'portrait', unit:'mm', format:'a4' });
  doc.setFontSize(14);
  doc.text("Laporan Stok Barang Outlet", 105, 15, {align:"center"});
  doc.setFontSize(11);
  doc.text("Outlet: <?= addslashes($namaOutlet); ?>", 15, 22);

  let headers = [];
  $('#tabel-stok-outlet thead th').each(function(index){
    if(index !== 5) headers.push($(this).text());
  });

  let data = [];
  $('#tabel-stok-outlet tbody tr').each(function(){
    let rowData=[];
    $(this).find('td').each(function(index){
      if(index !== 5) rowData.push($(this).text());
    });
    data.push(rowData);
  });

  doc.autoTable({
    head:[headers],
    body:data,
    startY:26,
    theme:'grid',
    margin:{top:26}
  });

  doc.save('Laporan_Stok_Outlet.pdf');
});
</script>

</body>
</html>
