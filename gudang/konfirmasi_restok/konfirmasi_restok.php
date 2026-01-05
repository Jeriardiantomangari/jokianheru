<?php
session_start();
include '../../koneksi/sidebargudang.php';
include '../../koneksi/koneksi.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'gudang') {
    header("Location: ../../index.php");
    exit;
}
?>
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

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
  margin-bottom:20px;
  color:#b71c1c;
  font-weight:700;
  letter-spacing:.5px;}

.tombol{
  border:none;
  border-radius:6px;
  cursor:pointer;
  color:white;
  font-size:11px;
  transition:.25s;
  display:inline-flex;
  align-items:center;gap:4px;}

.tombol i{
  font-size:12px;}

.tombol:hover{
  transform:translateY(-1px);
  box-shadow:0 2px 6px rgba(0,0,0,.18);}

.tombol-setuju{
  background:#2e7d32;
  padding:5px 10px;
  margin-right:5px;}

.tombol-tolak{
  background:#c62828;
  padding:5px 10px;}

.tombol-hapus{
  background:#c62828;
  padding:5px 10px;
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
  outline:none;}

.dataTables_wrapper .dataTables_filter input:focus,
.dataTables_wrapper .dataTables_length select:focus{
  border-color:#fb8c00;
  box-shadow:0 0 0 2px rgba(251,140,0,.15);}

.tabel-ajukan{
  width:100%;
  border-collapse:collapse;
  background:white;
  border-radius:12px;
  overflow:hidden;
  box-shadow:0 3px 10px rgba(0,0,0,.12);
  table-layout:fixed;}

.tabel-ajukan thead tr{
  background:linear-gradient(90deg,#d32f2f,#ffb300);}

.tabel-ajukan th{
  color:#fff;
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

  .tabel-ajukan,
  .tabel-ajukan thead,
  .tabel-ajukan tbody,
  .tabel-ajukan th,
  .tabel-ajukan td,
  .tabel-ajukan tr {
    display: block;
  }

  .tabel-ajukan thead tr {
    display: none;
  }

  .tabel-ajukan tr {
    margin-bottom: 15px;
    border-bottom: 2px solid #d32f2f;
    border-radius: 10px;
    overflow: hidden;
    background: #ffffff;
  }

  .tabel-ajukan td {
    text-align: right;
    padding-left: 50%;
    position: relative;
    border-right: none;
    border-bottom: 1px solid #ffe0b2;
  }

  .tabel-ajukan td::before {
    content: attr(data-label);
    position: absolute;
    left: 15px;
    width: 45%;
    font-weight: 600;
    text-align: left;
    color: #b71c1c;
  }

  .tombol-setuju,
  .tombol-tolak,
  .tombol-hapus {
    width: auto;
    padding: 6px 10px;
    display: inline-flex;
    margin: 3px 2px;
  }
}

</style>

<div class="konten-utama">
  <h2>Pengajuan Stok Outlet ke Gudang</h2>

  <table id="tabel-ajukan-outlet" class="tabel-ajukan">
    <thead>
      <tr>
        <th>No.</th>
        <th>Outlet</th>
        <th>Nama Barang</th>
        <th>Harga Satuan</th>
        <th>Jumlah Restok</th>
        <th>Total Harga</th>
        <th>Bahan Masuk</th>
        <th>Status</th>
        <th>Aksi</th>
      </tr>
    </thead>
    <tbody>
      <?php
      $no = 1;
      $sql = "
        SELECT
          r.Id_restok_bahan,
          r.Id_outlet,
          r.Id_stok_outlet,
          r.Nama_barang,
          r.Jumlah_restok,
          r.Status,
          o.nama_outlet,
          b.harga AS Harga,
          (b.harga * r.Jumlah_restok) AS Total_harga,
          COALESCE(SUM(bm.Bahan_masuk), 0) AS bahan_masuk
        FROM restok_bahan_outlet r
        JOIN outlet o ON o.id_outlet = r.Id_outlet
        LEFT JOIN stok_outlet so ON so.Id_stok_outlet = r.Id_stok_outlet
        LEFT JOIN barang b ON b.id_barang = so.Id_barang
        LEFT JOIN bahan_masuk bm ON bm.Id_restok_bahan = r.Id_restok_bahan
        GROUP BY r.Id_restok_bahan
        ORDER BY r.Id_restok_bahan DESC
      ";

      $q = mysqli_query($conn, $sql);
      while ($row = mysqli_fetch_assoc($q)) {
       
        $harga = (float)($row['Harga'] ?? 0);
        $total = (float)($row['Total_harga'] ?? 0);
        $masuk = (int)($row['bahan_masuk'] ?? 0);
      ?>
      <tr>
        <td data-label="No"><?= $no++; ?></td>
        <td data-label="Outlet"><?= htmlspecialchars($row['nama_outlet'] ?? '-'); ?></td>
        <td data-label="Nama Barang"><?= htmlspecialchars($row['Nama_barang'] ?? '-'); ?></td>
        <td data-label="Harga">Rp <?= number_format($harga, 2, ',', '.'); ?></td>
        <td data-label="Jumlah"><?= (int)($row['Jumlah_restok'] ?? 0); ?></td>
        <td data-label="Total">Rp <?= number_format($total, 2, ',', '.'); ?></td>
        <td data-label="Bahan Masuk"><?= ($masuk > 0) ? $masuk : '-'; ?></td>
        <td data-label="Status"><?= htmlspecialchars($row['Status'] ?? '-'); ?></td>

        <td data-label="Aksi">
          <?php if (($row['Status'] ?? '') === 'Menunggu'): ?>
            <button class="tombol tombol-setuju" onclick="setujui(<?= (int)$row['Id_restok_bahan']; ?>)">
              <i class="fa-solid fa-check"></i> Setujui
            </button>
            <button class="tombol tombol-tolak" onclick="tolak(<?= (int)$row['Id_restok_bahan']; ?>)">
              <i class="fa-solid fa-xmark"></i> Tolak
            </button>

          <?php elseif (($row['Status'] ?? '') === 'Selesai'): ?>
            <button class="tombol tombol-hapus" onclick="hapus(<?= (int)$row['Id_restok_bahan']; ?>)">
              <i class="fa-solid fa-trash"></i> Hapus
            </button>

          <?php else: ?>
            -
          <?php endif; ?>
        </td>
      </tr>
      <?php } ?>
    </tbody>
  </table>
</div>

<script>
$(document).ready(function () {
  $('#tabel-ajukan-outlet').DataTable({
    pageLength: 10,
    lengthMenu: [5, 10, 25, 50],
    columnDefs: [{ orderable: false, targets: 8 }],
       "language": {
      "emptyTable": "Belum ada pengajuan restok gudang",
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

function setujui(id){
  if(!confirm('Setujui pengajuan ini? Status akan menjadi Disetujui / Dikirim sesuai proses.')) return;
  $.post('proses_konfirmasi_restok_outlet.php', {aksi:'setujui', id:id}, function(res){
    alert(res);
    location.reload();
  });
}

function tolak(id){
  if(!confirm('Tolak pengajuan ini?')) return;
  $.post('proses_konfirmasi_restok_outlet.php', {aksi:'tolak', id:id}, function(res){
    alert(res);
    location.reload();
  });
}

function hapus(id){
  if(!confirm('Hapus pengajuan ini? Data akan hilang dari daftar.')) return;
  $.post('proses_konfirmasi_restok_outlet.php', {aksi:'hapus', id:id}, function(res){
    alert(res);
    location.reload();
  });
}
</script>
