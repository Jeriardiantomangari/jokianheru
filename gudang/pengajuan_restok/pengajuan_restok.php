<?php 
session_start();
include '../../koneksi/sidebargudang.php'; 
include '../../koneksi/koneksi.php'; 

// Cek role (opsional kalau login sudah jalan)
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'gudang') {
    // header("Location: ../../index.php");
    // exit;
}
?>
<!-- CDN jQuery dan DataTables -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>

<!-- FontAwesome -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>
body {
  margin: 0;
  font-family: Arial, sans-serif;
  background: radial-gradient(circle at top left, #fff7e0 0%, #ffe3b3 40%, #ffffff 100%);
}

.konten-utama { 
  margin-left:250px;   /* sesuaikan jika tidak pakai sidebar */
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

.tombol-tambah {
  background:#1976d2;
  padding:8px 15px;
  margin-bottom:15px;
}

/* Tombol di kolom Aksi */
.tombol-edit {
  background:#fb8c00;
  padding:5px 10px;
  border-radius:6px;
  border:none;
  cursor:pointer;
  font-size:11px;
  color:#fff;
  margin-right:5px;
}

.tombol-hapus {
  background:#c62828;
  padding:5px 10px;
  border-radius:6px;
  border:none;
  cursor:pointer;
  font-size:11px;
  color:#fff;
}

.tombol-belum {
  background:#757575;
  padding:5px 10px;
  border-radius:6px;
  border:none;
  font-size:11px;
  color:#fff;
  margin-right:5px;
}

.tombol-selesai {
  background:#2e7d32;
  padding:5px 10px;
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
  font-size:14px;
  color:#424242;
}

.tabel-ajukan tr:nth-child(even){
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

.isi-modal input,
.isi-modal select { 
  width:100%; 
  padding:10px; 
  margin:6px 0; 
  border:1px solid #ffcc80; 
  border-radius:8px; 
  font-size:14px;
}

.isi-modal input:focus,
.isi-modal select:focus {
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
  <h2>Pengajuan Restok Barang</h2>

  <button class="tombol tombol-tambah" onclick="bukaModalAjukan()">
    <i class="fa-solid fa-boxes-stacked"></i> Ajukan Restok
  </button>

  <table id="tabel-ajukan" class="tabel-ajukan">
    <thead>
      <tr>
        <th>No.</th>
        <th>Tanggal Pengajuan</th>
        <th>Nama Barang</th>
        <th>Harga</th>
        <th>Jumlah Restok</th>
        <th>Total Harga</th>
        <th>Status Restok</th>
        <th>Aksi</th>
      </tr>
    </thead>
    <tbody>
      <?php
      $no=1;
      $qAjukan = mysqli_query($conn,"SELECT * FROM ajukan_stok ORDER BY id DESC");
      while($a = mysqli_fetch_assoc($qAjukan)) {

        $tgl = '-';
        if (!empty($a['created_at'])) {
          $tgl = date('d-m-Y H:i', strtotime($a['created_at']));
        }
      ?>
      <tr>
        <td><?= $no++; ?></td>
        <td><?= $tgl; ?></td>
        <td><?= htmlspecialchars($a['nama_barang']); ?></td>
        <td>Rp <?= number_format($a['harga'],2,',','.'); ?></td>
        <td><?= (int)$a['jumlah_restok']; ?></td>
        <td>Rp <?= number_format($a['total_harga'],2,',','.'); ?></td>
        <td><?= htmlspecialchars($a['status']); ?></td>
        <td>
          <?php if ($a['status'] === 'Menunggu' || $a['status'] === 'Ditolak'): ?>
            <!-- Gudang boleh EDIT & HAPUS -->
            <button class="tombol-edit" onclick="editAjukan(<?= $a['id']; ?>)">
              <i class="fa-solid fa-pen-to-square"></i> Edit
            </button>
            <button class="tombol-hapus" onclick="hapusAjukan(<?= $a['id']; ?>)">
              <i class="fa-solid fa-trash"></i> Hapus
            </button>

          <?php elseif ($a['status'] === 'Disetujui'): ?>
            <!-- Owner sudah setuju: tombol Belum + Selesai -->
            <button class="tombol-selesai" onclick="selesaiRestok(<?= $a['id']; ?>)">
              <i class="fa-solid fa-check"></i> Selesai
            </button>

          <?php elseif ($a['status'] === 'Selesai'): ?>
            <!-- STATUS SELESAI: sekarang bisa dihapus -->
            <button class="tombol-hapus" onclick="hapusAjukan(<?= $a['id']; ?>)">
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

<!-- Modal Ajukan / Edit Restok -->
<div id="modalAjukan" class="kotak-modal">
  <div class="isi-modal">
    <span class="tutup-modal" onclick="tutupModalAjukan()">&times;</span>
    <h3 id="judulModal">Ajukan Restok Barang</h3>
    <form id="formAjukan">
      <!-- ID pengajuan untuk EDIT -->
      <input type="hidden" name="id" id="id_ajukan">

      <label>Pilih Barang</label>
      <select name="id_barang" id="id_barang" required>
        <option value="">-- Pilih Barang --</option>
        <?php
        $qBarang = mysqli_query($conn,"SELECT id, nama_barang, harga FROM barang ORDER BY nama_barang ASC");
        while($b = mysqli_fetch_assoc($qBarang)) {
          echo '<option value="'.$b['id'].'" data-harga="'.$b['harga'].'">'.$b['nama_barang'].'</option>';
        }
        ?>
      </select>

      <input type="text" id="nama_barang" name="nama_barang" placeholder="Nama Barang" readonly>
      <input type="number" id="harga" name="harga" placeholder="Harga" readonly>
      <input type="number" min="1" id="jumlah_restok" name="jumlah_restok" placeholder="Jumlah restok" required>
      <input type="number" id="total_harga" name="total_harga" placeholder="Total harga" readonly>

      <button type="submit">Simpan</button>
    </form>
  </div>
</div>

<script>
// DataTables
$(document).ready(function () {
  $('#tabel-ajukan').DataTable({
    "pageLength": 10,
    "lengthMenu": [5, 10, 25, 50],
    "columnDefs": [{
      "orderable": false, "targets": 7   // kolom Aksi
    }],
    "language": {
      "emptyTable": "Belum ada pengajuan restok",
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
        "previous": "Sebbelumnya"
      }
    }
  });
});

// Buka/tutup modal
function bukaModalAjukan() {
  $('#formAjukan')[0].reset();
  $('#id_ajukan').val('');
  $('#judulModal').text('Ajukan Restok Barang');
  $('#modalAjukan').css('display','flex');
}

function tutupModalAjukan(){
  $('#modalAjukan').hide();
}

// Ketika pilih barang → isi nama & harga
$('#id_barang').on('change', function(){
  const selected = $(this).find(':selected');
  const nama  = selected.text();
  const harga = selected.data('harga') || 0;

  $('#nama_barang').val(nama);
  $('#harga').val(harga);
  // reset jumlah & total
  $('#jumlah_restok').val('');
  $('#total_harga').val('');
});

// Hitung total harga otomatis
$('#jumlah_restok').on('input', function(){
  const jml   = parseInt($('#jumlah_restok').val()) || 0;
  const harga = parseInt($('#harga').val()) || 0;
  $('#total_harga').val(jml * harga);
});

// Submit pengajuan (baru / edit)
$('#formAjukan').submit(function(e){
  e.preventDefault();
  $.post('proses_ajukan_restok.php', $(this).serialize(), function(res){
    alert(res);
    $('#modalAjukan').hide();
    location.reload();
  });
});

// EDIT pengajuan (Menunggu/Ditolak)
function editAjukan(id) {
  $.post('proses_ajukan_restok.php', {aksi:'ambil', id:id}, function(res){
    let obj = JSON.parse(res);
    if (obj.error) {
      alert(obj.error);
      return;
    }
    $('#judulModal').text('Edit Pengajuan Restok');
    $('#id_ajukan').val(obj.id);
    $('#id_barang').val(obj.id_barang).change();
    $('#nama_barang').val(obj.nama_barang);
    $('#harga').val(obj.harga);
    $('#jumlah_restok').val(obj.jumlah_restok);
    $('#total_harga').val(obj.total_harga);
    $('#modalAjukan').css('display','flex');
  });
}

// HAPUS pengajuan (Menunggu/Ditolak/Selesai)
function hapusAjukan(id) {
  if (!confirm('Yakin ingin menghapus pengajuan ini?')) return;
  $.post('proses_ajukan_restok.php', {aksi:'hapus', id:id}, function(res){
    alert(res);
    location.reload();
  });
}

// Gudang menandai sudah beli → stok ditambah & status jadi Selesai
function selesaiRestok(id) {
  if(!confirm('Tandai pengajuan ini sebagai SELESAI dan tambahkan stok barang?')) return;
  $.post('proses_ajukan_restok.php', {aksi:'selesai', id:id}, function(res){
    alert(res);
    location.reload();
  });
}
</script>
