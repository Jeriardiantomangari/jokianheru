<?php
session_start();
include '../../koneksi/sidebarkasir.php'; 
include '../../koneksi/koneksi.php'; 

// Cek role & id_outlet
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'kasir') {
    header("Location: ../../index.php");
    exit;
}

if (!isset($_SESSION['id_outlet'])) {
    die("Outlet untuk kasir belum di-set. Pastikan kolom id_outlet di tabel pengguna dan session sudah benar.");
}

$id_outlet = (int)$_SESSION['id_outlet'];
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
  margin-bottom:10px; 
  color:#b71c1c; 
  font-weight:700;
  letter-spacing:.5px;
}

.konten-utama p.info-outlet {
  margin-top:0;
  margin-bottom:20px;
  color:#555;
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

.tombol-cetak { 
  background:#43a047; 
  margin-right:10px; 
  padding:8px 15px; 
}

.tombol-edit {
  background:#fb8c00;
  padding:6px 10px;
  border-radius:6px;
  border:none;
  cursor:pointer;
  font-size:11px;
  color:#fff;
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

/* TABEL */
.tabel-stok-outlet { 
  width:100%; 
  border-collapse:collapse; 
  background:white; 
  border-radius:12px; 
  overflow:hidden; 
  box-shadow:0 3px 10px rgba(0,0,0,0.12); 
  table-layout:fixed; 
}

.tabel-stok-outlet thead tr {
  background: linear-gradient(90deg, #d32f2f, #ffb300);
}

.tabel-stok-outlet th { 
  color:#ffffff; 
  text-align:left; 
  padding:12px 15px; 
  font-weight:600;
  font-size:14px;
}

.tabel-stok-outlet td { 
  padding:10px 15px; 
  border-bottom:1px solid #ffe0b2; 
  border-right:1px solid #fff3e0; 
  font-size:14px;
  color:#424242;
}

.tabel-stok-outlet tr:nth-child(even){
  background:#fffdf7;
}

/* Modal */
.kotak-modal { 
  display:none; 
  position:fixed; 
  z-index:300; 
  left:0; 
  top:0; 
  width:100%; 
  height:100vh; 
  background:rgba(0,0,0,0.55); 
  justify-content:center; 
  align-items:center; 
}

.isi-modal { 
  background:white; 
  padding:25px; 
  border-radius:12px; 
  width:400px; 
  max-width:90%; 
  box-shadow:0 6px 18px rgba(0,0,0,.35); 
  text-align:center; 
  position:relative; 
  border-top:4px solid #d32f2f;
}

.isi-modal h3 { 
  margin-bottom:16px; 
  color:#b71c1c;
  font-size:18px;
}

.isi-modal input { 
  width:100%; 
  padding:10px; 
  margin:6px 0; 
  border:1px solid #ffcc80; 
  border-radius:8px; 
  font-size:14px;
}

.isi-modal input:focus {
  outline:none;
  border-color:#fb8c00;
  box-shadow:0 0 0 2px rgba(251,140,0,0.18);
}

.isi-modal button { 
  width:100%; 
  padding:10px; 
  border:none; 
  border-radius:8px; 
  background:#d32f2f; 
  color:white; 
  font-weight:600; 
  cursor:pointer; 
  margin-top:10px; 
  letter-spacing:.5px;
}

.isi-modal button:hover { 
  background:#b71c1c; 
}

.tutup-modal { 
  position:absolute; 
  top:10px; 
  right:12px; 
  cursor:pointer; 
  font-size:20px; 
  color:#999; 
}

.tutup-modal:hover { 
  color:#d32f2f; 
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
}
</style>

<div class="konten-utama">
  <h2>Stok Barang di Outlet</h2>
  <?php
  // Ambil nama outlet
  $qOutlet   = mysqli_query($conn, "SELECT nama_outlet FROM outlet WHERE id = $id_outlet");
  $rowOutlet = mysqli_fetch_assoc($qOutlet);
  $namaOutlet = $rowOutlet ? $rowOutlet['nama_outlet'] : 'Outlet Tidak Dikenal';
  ?>
  <p class="info-outlet">Outlet: <strong><?= htmlspecialchars($namaOutlet); ?></strong></p>

  <button class="tombol tombol-cetak"><i class="fa-solid fa-print"></i> Cetak</button>

  <table id="tabel-stok-outlet" class="tabel-stok-outlet">
    <thead>
      <tr>
        <th>No.</th>
        <th>Nama Barang</th>
        <th>Jenis</th>
        <th>Stok </th>
        <th>Aksi</th>
      </tr>
    </thead>
    <tbody>
      <?php
      $no = 1;
      $sql = "
        SELECT so.id,
               so.stok,
               b.nama_barang,
               b.jenis
        FROM stok_outlet so
        JOIN barang b ON so.id_barang = b.id
        WHERE so.id_outlet = $id_outlet
        ORDER BY b.nama_barang ASC
      ";
      $qStok = mysqli_query($conn, $sql);
      while ($row = mysqli_fetch_assoc($qStok)) {
        $stok_now  = (int)$row['stok'];
      ?>
      <tr>
        <td><?= $no++; ?></td>
        <td><?= htmlspecialchars($row['nama_barang']); ?></td>
        <td><?= htmlspecialchars($row['jenis']); ?></td>
        <td><?= $stok_now; ?></td>
        <td>
          <button class="tombol-edit" onclick="editStok(<?= $row['id']; ?>)">
            <i class="fa-solid fa-pen-to-square"></i> Pakai Stok
          </button>
        </td>
      </tr>
      <?php } ?>
    </tbody>
  </table>
</div>

<!-- Modal Input Pemakaian -->
<div id="modalStok" class="kotak-modal">
  <div class="isi-modal">
    <span class="tutup-modal" onclick="tutupModalStok()">&times;</span>
    <h3>Input Pemakaian Barang</h3>
    <form id="formStok">
      <input type="hidden" name="id" id="id_stok">

      <input type="text" id="nama_barang" placeholder="Nama Barang" readonly>
      <input type="text" id="jenis" placeholder="Jenis" readonly>

      <input type="number" id="stok_sekarang" placeholder="Stok sekarang" readonly>

      <!-- ini yang diisi kasir: berapa stok dipakai hari ini -->
      <input type="number" min="1" name="jumlah_digunakan" id="jumlah_digunakan" placeholder="Jumlah digunakan" required>

      <input type="number" id="stok_setelah" placeholder="Stok setelah pemakaian" readonly>

      <button type="submit">Simpan</button>
    </form>
  </div>
</div>

<script>
// DataTables
$(document).ready(function () {
  $('#tabel-stok-outlet').DataTable({
    "pageLength": 10,
    "lengthMenu": [5, 10, 25, 50],
    "columnDefs": [{
      "orderable": false, "targets": 4   // kolom Aksi
    }],
    "language": {
      "emptyTable": "Tidak ada data stok untuk outlet ini",
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

// Buka modal, ambil data stok_outlet + barang dari server
function editStok(id) {
  $.post('proses_stok.php', {aksi:'ambil', id:id}, function(res){
    let obj = JSON.parse(res);
    if (obj.error) {
      alert(obj.error);
      return;
    }
    $('#id_stok').val(obj.id);
    $('#nama_barang').val(obj.nama_barang);
    $('#jenis').val(obj.jenis);
    $('#stok_sekarang').val(obj.stok);

    $('#jumlah_digunakan').val('');
    $('#stok_setelah').val('');

    $('#modalStok').css('display','flex');
  });
}

function tutupModalStok(){
  $('#modalStok').hide();
}

// Saat kasir mengetik jumlah digunakan → hitung stok setelah
$('#jumlah_digunakan').on('input', function(){
  let stokSekarang = parseInt($('#stok_sekarang').val()) || 0;
  let jml          = parseInt($('#jumlah_digunakan').val()) || 0;
  let sisa         = stokSekarang - jml;
  if (sisa < 0) sisa = 0;
  $('#stok_setelah').val(sisa);
});

// Submit form: kirim id stok_outlet + jumlah_digunakan ke server
$('#formStok').submit(function(e){
  e.preventDefault();
  $.post('proses_stok.php', $(this).serialize(), function(res){
    alert(res);
    $('#modalStok').hide();
    location.reload();
  });
});

// Cetak PDF
$('.tombol-cetak').click(function(){
  const { jsPDF } = window.jspdf;
  const doc = new jsPDF({ orientation:'portrait', unit:'mm', format:'a4' });
  doc.setFontSize(14);
  doc.text("Laporan Stok Barang Outlet", 105, 15, {align:"center"});
  doc.setFontSize(11);
  doc.text("Outlet: <?= addslashes($namaOutlet); ?>", 15, 22);

  let headers = [];
  // skip kolom Aksi (index 4)
  $('#tabel-stok-outlet thead th').each(function(index){ 
    if(index !== 4) headers.push($(this).text()); 
  });

  let data = [];
  $('#tabel-stok-outlet tbody tr').each(function(){
    let rowData=[];
    $(this).find('td').each(function(index){ 
      if(index !== 4) rowData.push($(this).text()); 
    });
    data.push(rowData);
  });

  doc.autoTable({
    head:[headers], 
    body:data, 
    startY:26, 
    theme:'grid', 
    headStyles:{fillColor:[211,47,47], textColor:255}, 
    styles:{fontSize:10}, 
    margin:{top:26} 
  });

  doc.save('Laporan_Stok_Outlet.pdf');
});
</script>
