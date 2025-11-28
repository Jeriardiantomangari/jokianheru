<?php  
session_start();
include '../../koneksi/sidebargudang.php'; 
include '../../koneksi/koneksi.php'; 

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'gudang') {
    header("Location: ../../index.php");
    exit;
}
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

/* TAMBAHAN: tombol hapus untuk status Selesai */
.tombol-hapus {
  background:#c62828;
  padding:5px 10px;
  border-radius:6px;
  border:none;
  cursor:pointer;
  font-size:11px;
  color:#fff;
}

.tabel-ajukan { 
  width:100%; 
  border-collapse:collapse; 
  background:white; 
  border-radius:12px; 
  overflow:hidden; 
  box-shadow:0 3px 10px rgba(0,0,0,0.12); 
  table-layout:fixed; 
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
  <h2>Pengajuan Stok Outlet ke Gudang</h2>

  <table id="tabel-ajukan-outlet" class="tabel-ajukan">
    <thead>
      <tr>
        <th>No.</th>
        <th>Tanggal</th>
        <th>Outlet</th>
        <th>Nama Barang</th>
        <th>Harga Satuan</th>
        <th>Jumlah</th>
        <th>Total</th>
        <th>Status</th>
        <th>Aksi</th>
      </tr>
    </thead>
    <tbody>
      <?php
      $no = 1;
      $sql = "
        SELECT a.*, o.nama_outlet
        FROM ajukan_stok_outlet a
        JOIN outlet o ON a.id_outlet = o.id
        ORDER BY a.id DESC
      ";
      $q = mysqli_query($conn, $sql);
      while($row = mysqli_fetch_assoc($q)) {
        $tgl = '-';
        if (!empty($row['created_at'])) {
          $tgl = date('d-m-Y H:i', strtotime($row['created_at']));
        }
      ?>
      <tr>
        <td><?= $no++; ?></td>
        <td><?= $tgl; ?></td>
        <td><?= htmlspecialchars($row['nama_outlet']); ?></td>
        <td><?= htmlspecialchars($row['nama_barang']); ?></td>
        <td>Rp <?= number_format($row['harga'],2,',','.'); ?></td>
        <td><?= (int)$row['jumlah_restok']; ?></td>
        <td>Rp <?= number_format($row['total_harga'],2,',','.'); ?></td>
        <td><?= htmlspecialchars($row['status']); ?></td>
        <td>
          <?php if ($row['status'] === 'Menunggu'): ?>
            <button class="tombol tombol-setuju" onclick="setujui(<?= $row['id']; ?>)">
              <i class="fa-solid fa-check"></i> Disetujui
            </button>
            <button class="tombol tombol-tolak" onclick="tolak(<?= $row['id']; ?>)">
              <i class="fa-solid fa-xmark"></i> Tolak
            </button>

          <?php elseif ($row['status'] === 'Selesai'): ?>
            <button class="tombol tombol-hapus" onclick="hapus(<?= $row['id']; ?>)">
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
    "pageLength": 10,
    "lengthMenu": [5, 10, 25, 50],
    "columnDefs": [{
      "orderable": false, "targets": 8
    }],
    "language": {
      "emptyTable": "Belum ada pengajuan stok outlet",
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
  if(!confirm('Setujui pengajuan ini? Stok gudang akan berkurang dan status menjadi Dikirim.')) return;
  $.post('proses_konfirmasi_restok.php', {aksi:'setujui', id:id}, function(res){
    alert(res);
    location.reload();
  });
}

function tolak(id){
  if(!confirm('Tolak pengajuan ini?')) return;
  $.post('proses_konfirmasi_restok.php', {aksi:'tolak', id:id}, function(res){
    alert(res);
    location.reload();
  });
}

// TAMBAHAN: hapus data yang statusnya sudah Selesai
function hapus(id){
  if(!confirm('Hapus pengajuan ini? Data akan hilang dari daftar.')) return;
  $.post('proses_konfirmasi_restok.php', {aksi:'hapus', id:id}, function(res){
    alert(res);
    location.reload();
  });
}
</script>
