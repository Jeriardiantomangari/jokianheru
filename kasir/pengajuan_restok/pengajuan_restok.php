<?php
session_start();
include '../../koneksi/sidebarkasir.php';
include '../../koneksi/koneksi.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'kasir') {
  header("Location: ../../index.php");
  exit;
}

if (!isset($_SESSION['id_outlet'])) {
  die("Outlet kasir belum di-set. Pastikan kolom id_outlet di tabel akun dan session sudah benar.");
}

$id_outlet = (int)$_SESSION['id_outlet'];
?>

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>

body { 
  margin:0; 
  font-family:Arial,sans-serif; 
  background: radial-gradient(circle at top left, #fff7e0 0%, #ffe3b3 40%, #ffffff 100%); }

.konten-utama { 
  margin-left:250px; 
  margin-top:60px; 
  padding:30px; 
  min-height:calc(100vh - 60px); }

.konten-utama h2 { 
  margin-bottom:10px; 
  color:#b71c1c; 
  font-weight:700; 
  letter-spacing:.5px; }

.konten-utama p.info-outlet { 
  margin-top:0; 
  margin-bottom:20px; 
  color:#555; }

.tombol{ 
  border:none; 
  border-radius:6px; 
  cursor:pointer; 
  color:white; 
  font-size:11px; 
  transition:.25s; 
  display:inline-flex; 
  align-items:center; 
  gap:4px; }

.tombol i{ 
  font-size:12px; }

.tombol:hover{ 
  transform:translateY(-1px); 
  box-shadow:0 2px 6px rgba(0,0,0,.18); }

.tombol-tambah{ 
  background:#1976d2; 
  padding:8px 15px; 
  margin-bottom:15px; }

.tombol-edit{ 
  background:#fb8c00; 
  padding:5px 10px; 
  border-radius:6px; 
  border:none; 
  cursor:pointer; 
  font-size:11px; 
  color:#fff; 
  margin-right:5px; }

.tombol-hapus{ 
  background:#c62828; 
  padding:5px 10px; 
  border-radius:6px; 
  border:none; 
  cursor:pointer; 
  font-size:11px; 
  color:#fff; 
  margin-right:5px; }

.tombol-selesai{ 
  background:#2e7d32; 
  padding:5px 10px; 
  border-radius:6px; 
  border:none; 
  cursor:pointer; 
  font-size:11px; 
  color:#fff; }

.dataTables_wrapper .dataTables_filter input,
.dataTables_wrapper .dataTables_length select{ 
  padding:6px 10px; 
  border-radius:20px; 
  border:1px solid #ffcc80; 
  font-size:14px; 
  margin-bottom:8px; 
  outline:none; }
.dataTables_wrapper .dataTables_filter input:focus,
.dataTables_wrapper .dataTables_length select:focus{ 
  border-color:#fb8c00; 
  box-shadow:0 0 0 2px rgba(251,140,0,.15); }

.tabel-ajukan{ 
  width:100%; 
  border-collapse:collapse; 
  background:white; 
  border-radius:12px; 
  overflow:hidden; 
  box-shadow:0 3px 10px rgba(0,0,0,.12); 
  table-layout:fixed; }

.tabel-ajukan thead tr{ 
  background:linear-gradient(90deg,#d32f2f,#ffb300); }

.tabel-ajukan th{ 
  color:#fff; 
  text-align:left; 
  padding:12px 15px; 
  font-weight:600; 
  font-size:14px; }

.tabel-ajukan td{ 
  padding:10px 15px; 
  border-bottom:1px solid #ffe0b2; 
  border-right:1px solid #fff3e0; 
  font-size:14px; 
  color:#424242; }

.tabel-ajukan tr:nth-child(even){ 
  background:#fffdf7; }

.kotak-modal{ 
  display:none; 
  position:fixed; 
  z-index:999; 
  left:0; 
  top:0; 
  width:100%; 
  height:100vh; 
  background:rgba(0,0,0,.55); 
  justify-content:center; 
  align-items:center; }

.isi-modal{ 
  background:white; 
  padding:25px; 
  border-radius:12px; 
  width:400px;
  max-width:90%; 
  box-shadow:0 6px 18px rgba(0,0,0,.35); 
  text-align:center; 
  position:relative; 
  border-top:4px solid #d32f2f; }

.isi-modal h3{ 
  margin-bottom:16px; 
  color:#b71c1c; 
  font-size:18px; }

  #modalKonfirmasi .isi-modal {
  text-align: left;      
}

.isi-modal input, .isi-modal select{ 
  width:100%; 
  padding:10px; 
  margin:6px 0; 
  border:1px solid #ffcc80; 
  border-radius:8px; 
  font-size:14px; }

.isi-modal input:focus, .isi-modal select:focus{ 
  outline:none; 
  border-color:#fb8c00; 
  box-shadow:0 0 0 2px rgba(251,140,0,.18); }

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
  letter-spacing:.5px; }

.isi-modal button:hover{ 
  background:#b71c1c; }

.tutup-modal{ 
  position:absolute; 
  top:10px; 
  right:12px; 
  cursor:pointer; 
  font-size:20px; 
  color:#999; }

.tutup-modal:hover{ 
  color:#d32f2f; }

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

  .konten-utama .tombol-tambah {
    display: inline-flex;
    margin: 5px auto 15px auto;
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

  .tombol-edit,
  .tombol-hapus,
  .tombol-selesai {
  width: auto;
  padding: 6px 10px;
  display: inline-flex;
  align-items: center;   
  margin: 3px 2px;
  line-height: 1;
  gap:4px;
  }

  .tombol-edit i,
  .tombol-hapus i,
  .tombol-selesai i {
  line-height:1;
  display:inline-block;
  }


  
}

</style>

<div class="konten-utama">
  <h2>Pengajuan Stok ke Gudang</h2>
  <?php
    $qOutlet = mysqli_query($conn, "SELECT nama_outlet FROM outlet WHERE id_outlet = $id_outlet");
    $rowOutlet = mysqli_fetch_assoc($qOutlet);
    $namaOutlet = $rowOutlet ? $rowOutlet['nama_outlet'] : 'Outlet Tidak Dikenal';
  ?>
  <p class="info-outlet">Outlet: <strong><?= htmlspecialchars($namaOutlet); ?></strong></p>

  <button class="tombol tombol-tambah" onclick="bukaModalAjukan()">
    <i class="fa-solid fa-boxes-stacked"></i> Ajukan Restok
  </button>

  <table id="tabel-ajukan" class="tabel-ajukan">
    <thead>
      <tr>
        <th>No.</th>
        <th>Nama Barang</th>
        <th>Jumlah Restok</th>
        <th>Bahan Masuk</th>
        <th>Status</th>
        <th>Aksi</th>
      </tr>
    </thead>
    <tbody>
      <?php
      $no = 1;

      $qAjukan = mysqli_query($conn, "
        SELECT
          r.*,
          COALESCE(SUM(bm.Jumlah_restok),0) AS bahan_masuk_total
        FROM restok_bahan_outlet r
        LEFT JOIN bahan_masuk bm ON bm.Id_restok_bahan = r.Id_restok_bahan
        WHERE r.Id_outlet = $id_outlet
        GROUP BY r.Id_restok_bahan
        ORDER BY r.Id_restok_bahan DESC
      ");

      while ($a = mysqli_fetch_assoc($qAjukan)) {
        $masuk = (int)$a['bahan_masuk_total'];
      ?>
      <tr>
        <td data-label="No"><?= $no++; ?></td>
        <td data-label="Nama Barang"><?= htmlspecialchars($a['Nama_barang'] ?? '-'); ?></td>
        <td data-label="Jumlah Restok"><?= (int)($a['Jumlah_restok'] ?? 0); ?></td>
        <td data-label="Bahan Masuk"><?= $masuk > 0 ? $masuk : '-'; ?></td>
        <td data-label="Status"><?= htmlspecialchars($a['Status'] ?? '-'); ?></td>
        <td data-label="Aksi">
          <?php if (($a['Status'] ?? '') === 'Menunggu' || ($a['Status'] ?? '') === 'Ditolak'): ?>
            <button class="tombol-edit" onclick="editAjukan(<?= (int)$a['Id_restok_bahan']; ?>)">
              <i class="fa-solid fa-pen-to-square"></i> Edit
            </button>
            <button class="tombol-hapus" onclick="hapusAjukan(<?= (int)$a['Id_restok_bahan']; ?>)">
              <i class="fa-solid fa-trash"></i> Hapus
            </button>
          <?php elseif (($a['Status'] ?? '') === 'Disetujui' || ($a['Status'] ?? '') === 'Dikirim'): ?>
            <button class="tombol-selesai" onclick="bukaModalKonfirmasi(<?= (int)$a['Id_restok_bahan']; ?>)">
              <i class="fa-solid fa-circle-check"></i> Konfirmasi
            </button>
          <?php elseif (($a['Status'] ?? '') === 'Selesai'): ?>
            <button class="tombol-hapus" onclick="hapusAjukan(<?= (int)$a['Id_restok_bahan']; ?>)">
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

<!-- Modal Ajukan/Edit -->
<div id="modalAjukan" class="kotak-modal">
  <div class="isi-modal">
    <span class="tutup-modal" onclick="tutupModalAjukan()">&times;</span>
    <h3 id="judulModal">Ajukan Restok Barang ke Gudang</h3>
    <form id="formAjukan">
      <input type="hidden" name="id" id="id_ajukan">

      <label>Pilih Barang</label>
      <select name="id_barang" id="id_barang" required>
        <option value="">-- Pilih Barang --</option>
        <?php
        $qBarang = mysqli_query($conn, "SELECT id_barang, nama_barang, harga FROM barang ORDER BY nama_barang ASC");
        while ($b = mysqli_fetch_assoc($qBarang)) {
          echo '<option value="'.$b['id_barang'].'" data-harga="'.$b['harga'].'">'.$b['nama_barang'].'</option>';
        }
        ?>
      </select>

      <input type="number" id="harga" name="harga" placeholder="Harga" readonly>
      <input type="number" min="1" id="jumlah_restok" name="jumlah_restok" placeholder="Jumlah restok" required>
      <input type="number" id="total_harga" name="total_harga" placeholder="Total harga" readonly>

      <button type="submit">Simpan</button>
    </form>
  </div>
</div>

<!-- Modal Konfirmasi -->
<div id="modalKonfirmasi" class="kotak-modal">
  <div class="isi-modal">
    <span class="tutup-modal" onclick="tutupModalKonfirmasi()">&times;</span>
    <h3>Konfirmasi Barang Masuk (Outlet)</h3>

    <form id="formKonfirmasi">
      <input type="hidden" id="id_konfirmasi" name="id">

      <label>Nama Barang</label>
      <input type="text" id="nama_barang_konfirmasi" readonly>

      <label>Jumlah Restok (Disetujui)</label>
      <input type="number" id="jumlah_restok_konfirmasi" readonly>

      <label>Bahan Masuk</label>
      <input type="number" min="1" id="bahan_masuk" name="bahan_masuk" required>

      <button type="submit">Simpan Konfirmasi</button>
    </form>
  </div>
</div>

<script>
$(document).ready(function () {
  $('#tabel-ajukan').DataTable({
    pageLength: 10,
    lengthMenu: [5, 10, 25, 50],
    columnDefs: [{ orderable: false, targets: 5 }], 
    language: {
      emptyTable: "Belum ada pengajuan restok ke gudang",
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

  $('#id_barang').on('change', function(){
    const selected = $(this).find(':selected');
    const harga = selected.data('harga') || 0;
    $('#harga').val(harga);
    $('#jumlah_restok').val('');
    $('#total_harga').val('');
  });

  $('#jumlah_restok').on('input', function(){
    const jml = parseInt($('#jumlah_restok').val()) || 0;
    const harga = parseInt($('#harga').val()) || 0;
    $('#total_harga').val(jml * harga);
  });

  $('#formAjukan').on('submit', function(e){
    e.preventDefault();
    $.post('proses_ajukan_restok.php', $(this).serialize(), function(res){
      alert(res);
      $('#modalAjukan').hide();
      location.reload();
    });
  });

  $('#formKonfirmasi').on('submit', function(e){
    e.preventDefault();
    const id = $('#id_konfirmasi').val();
    const masuk = parseInt($('#bahan_masuk').val()) || 0;

    $.post('proses_ajukan_restok.php', { aksi:'selesai', id:id, bahan_masuk: masuk }, function(res){
      alert(res);
      $('#modalKonfirmasi').hide();
      location.reload();
    });
  });
});

function bukaModalAjukan() {
  $('#formAjukan')[0].reset();
  $('#id_ajukan').val('');
  $('#judulModal').text('Ajukan Restok Barang ke Gudang');
  $('#modalAjukan').css('display','flex');
}
function tutupModalAjukan(){ $('#modalAjukan').hide(); }

function editAjukan(id) {
  $.post('proses_ajukan_restok.php', { aksi:'ambil', id:id }, function(res){
    let obj;
    try { obj = JSON.parse(res); } catch(e){ alert(res); return; }
    if (obj.error) { alert(obj.error); return; }

    $('#judulModal').text('Edit Pengajuan Restok');
    $('#id_ajukan').val(obj.id);
    $('#id_barang').val(obj.id_barang).change();
    $('#harga').val(obj.harga);
    $('#jumlah_restok').val(obj.jumlah_restok);
    $('#total_harga').val(obj.total_harga);
    $('#modalAjukan').css('display','flex');
  });
}

function hapusAjukan(id) {
  if (!confirm('Yakin ingin menghapus pengajuan ini?')) return;
  $.post('proses_ajukan_restok.php', { aksi:'hapus', id:id }, function(res){
    alert(res);
    location.reload();
  });
}

function bukaModalKonfirmasi(id) {
  $.post('proses_ajukan_restok.php', { aksi:'ambil_konfirmasi', id:id }, function(res){
    let obj;
    try { obj = JSON.parse(res); } catch(e){ alert(res); return; }
    if (obj.error) { alert(obj.error); return; }

    $('#id_konfirmasi').val(obj.id);
    $('#nama_barang_konfirmasi').val(obj.nama_barang);
    $('#jumlah_restok_konfirmasi').val(obj.jumlah_restok);
    $('#bahan_masuk').val(obj.jumlah_restok);

    $('#modalKonfirmasi').css('display','flex');
  });
}
function tutupModalKonfirmasi(){ $('#modalKonfirmasi').hide(); }
</script>
