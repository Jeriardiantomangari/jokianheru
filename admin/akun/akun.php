<?php 
session_start();
include '../../koneksi/sidebaradmin.php'; 
include '../../koneksi/koneksi.php'; 

// Cek role (kalau mau owner juga bisa akses, nanti bisa jadi: !in_array($_SESSION['role'], ['admin','owner']))
if(!isset($_SESSION['role']) || $_SESSION['role'] != 'admin'){
    header("Location: ../index.php"); 
    exit;
}

// TAMBAHAN: ambil data outlet untuk dropdown
$qo_outlet = mysqli_query($conn, "SELECT id_outlet, nama_outlet FROM outlet ORDER BY nama_outlet ASC");
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
/* AREA KONTEN UTAMA */
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

/* TOMBOL-TOMBOL */
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

/* Tambah = kuning keemasan */
.tombol-tambah { 
  background:#ffb300; 
  margin-bottom:12px; 
  padding:8px 15px; 
}

/* Cetak = hijau tapi sedikit warm */
.tombol-cetak { 
  background:#43a047; 
  margin-right:10px; 
  padding:8px 15px; 
}

/* Edit = oranye */
.tombol-edit { 
  background:#fb8c00; 
  min-width:70px; 
  margin-bottom:4px; 
  padding:6px 10px; 
}

/* Hapus = merah gelap */
.tombol-hapus { 
  background:#c62828; 
  min-width:70px; 
  padding:6px 10px; 
}

/* Control DataTables */
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

/* Tabel Akun */
.tabel-akun { 
  width:100%; 
  border-collapse:collapse; 
  background:white; 
  border-radius:12px; 
  overflow:hidden; 
  box-shadow:0 3px 10px rgba(0,0,0,0.12); 
  table-layout:fixed; 
}

.tabel-akun thead tr {
  background: linear-gradient(90deg, #d32f2f, #ffb300);
}

.tabel-akun th { 
  color:#ffffff; 
  text-align:left; 
  padding:12px 15px; 
  font-weight:600;
  font-size:14px;
}

.tabel-akun td { 
  padding:10px 15px; 
  border-bottom:1px solid #ffe0b2; 
  border-right:1px solid #fff3e0; 
  font-size:14px;
  color:#424242;
}

.tabel-akun tr:nth-child(even){
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

  .tabel-akun,
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
  <h2>Data Akun</h2>

  <button class="tombol tombol-cetak"><i class="fa-solid fa-print"></i> Cetak</button>
  <button class="tombol tombol-tambah" onclick="tambahAkun()"><i class="fa-solid fa-plus"></i> Tambah</button>

  <table id="tabel-akun" class="tabel-akun">
    <thead>
      <tr>
        <th>No.</th>
        <th>Nama</th>
        <th>Username</th>
        <th>Password</th>
        <!-- TAMBAHAN: kolom Outlet -->
        <th>Outlet</th>
        <th>Role</th>
        <th>Aksi</th>
      </tr>
    </thead>
    <tbody>
<?php
$no = 1;
// TAMBAHAN: join dengan tabel outlet untuk ambil nama_outlet
$query = mysqli_query($conn, "
    SELECT akun.*, outlet.nama_outlet 
    FROM akun 
    LEFT JOIN outlet ON akun.id_outlet = outlet.id_outlet
    ORDER BY akun.id_akun ASC
");
while($row = mysqli_fetch_assoc($query)) {
?>


      <tr>
        <td data-label="No"><?php echo $no++; ?></td>
        <td data-label="Nama"><?php echo $row['nama']; ?></td>
        <td data-label="Username"><?php echo $row['username']; ?></td>
        <td data-label="Password"><?php echo $row['password']; ?></td>
        <!-- TAMBAHAN: tampilkan nama outlet / id_outlet -->
        <td data-label="Outlet">
          <?php 
            if($row['nama_outlet']){
              echo $row['nama_outlet'];
            } elseif($row['id_outlet']) {
              echo 'Outlet ID '.$row['id_outlet'];
            } else {
              echo '-';
            }
          ?>
        </td>
        <td data-label="Role"><?php echo $row['role']; ?></td>
        <td data-label="Aksi">
          <button class="tombol tombol-edit" onclick="editAkun(<?php echo $row['id_akun']; ?>)">
            <i class="fa-solid fa-pen-to-square"></i> Edit
          </button>
          <button class="tombol tombol-hapus" onclick="hapusAkun(<?php echo $row['id_akun']; ?>)">
            <i class="fa-solid fa-trash"></i> Hapus
          </button>
        </td>
      </tr>
      <?php } ?>
    </tbody>
  </table>
</div>

<!-- Modal Tambah/Edit Akun -->
<div id="modalAkun" class="kotak-modal">
  <div class="isi-modal">
    <span class="tutup-modal" onclick="tutupModal()">&times;</span>
    <h3 id="judulModal">Tambah Akun</h3>
    <form id="formAkun">
      <input type="hidden" name="id" id="idAkun">
      <input type="text" name="nama" id="nama" placeholder="Nama" required>
      <input type="text" name="username" id="username" placeholder="Username" required>
      <input type="text" name="password" id="password" placeholder="Password" required>

      <!-- ROLE -->
      <select name="role" id="role" required>
        <option value="">-- Pilih Role --</option>
        <option value="owner">Owner</option>
        <option value="admin">Admin</option>
        <option value="gudang">Gudang</option>
        <option value="kasir">Kasir</option>
      </select>

      <!-- TAMBAHAN: PILIH OUTLET -->
      <select name="id_outlet" id="id_outlet">
        <option value="">-- Pilih Outlet (khusus kasir) --</option>
        <?php 
        // reset pointer jika perlu
        mysqli_data_seek($qo_outlet, 0);
        while($o = mysqli_fetch_assoc($qo_outlet)){ ?>
          <option value="<?php echo $o['id_outlet']; ?>">
            <?php echo $o['nama_outlet']; ?>
          </option>
        <?php } ?>
      </select>

      <button type="submit" id="simpanAkun">Simpan</button>
    </form>
  </div>
</div>

<script>
// DataTables
$(document).ready(function () {
  $('#tabel-akun').DataTable({
    "pageLength": 10,
    "lengthMenu": [5, 10, 25, 50],
    "columnDefs": [{
      // TAMBAHAN: kolom Aksi sekarang index ke-6 (0:No,1:Nama,2:User,3:Pass,4:Outlet,5:Role,6:Aksi)
      "orderable": false, "targets": 6
    }],
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

// Modal Tambah
function tambahAkun() {
  $('#formAkun')[0].reset();
  $('#idAkun').val('');
  $('#judulModal').text('Tambah Akun');
  $('#modalAkun').css('display','flex');
}

// Modal Edit
function editAkun(id) {
  $.post('proses_akun.php', {aksi:'ambil', id:id}, function(data){
    let obj = JSON.parse(data);
    $('#judulModal').text('Edit Akun');
    $('#idAkun').val(obj.id_akun);  // Pastikan ini benar sesuai dengan kolom 'id_akun'
    $('#nama').val(obj.nama);
    $('#username').val(obj.username);
    $('#password').val(obj.password);
    $('#role').val(obj.role);
    $('#id_outlet').val(obj.id_outlet);
    $('#modalAkun').css('display','flex');
  });
}


// Hapus
function hapusAkun(id){
  if(confirm('Apakah Anda yakin ingin menghapus data akun ini?')){
    $.post('proses_akun.php', {aksi:'hapus', id:id}, function(){
      alert('Data berhasil dihapus!');
      location.reload();
    });
  }
}

// Tutup Modal
function tutupModal(){ 
  $('#modalAkun').hide(); 
}

// Submit Form (Tambah/Update)
$('#formAkun').submit(function(e){
  e.preventDefault();
  const id        = $('#idAkun').val();
  const role      = $('#role').val();
  const idOutlet  = $('#id_outlet').val();
  const pesan     = id ? 'Data akun berhasil diubah!' : 'Data akun berhasil ditambahkan!';

  // TAMBAHAN: validasi kasir wajib pilih outlet
  if(role === 'kasir' && !idOutlet){
    alert('Kasir wajib memiliki outlet!');
    return;
  }

  $.post('proses_akun.php', $(this).serialize(), function(){
    $('#modalAkun').hide();
    alert(pesan);
    location.reload();
  });
});

// Cetak PDF
$('.tombol-cetak').click(function(){
  const { jsPDF } = window.jspdf;
  const doc = new jsPDF({ orientation:'portrait', unit:'mm', format:'a4' });
  doc.setFontSize(14);
  doc.text("Data Akun", 105, 15, {align:"center"});

  let headers = [];
  // TAMBAHAN: sekarang kolom Aksi index ke-6, jadi skip index 6
  $('#tabel-akun thead th').each(function(index){ 
    if(index !== 6) headers.push($(this).text()); 
  });

  let data = [];
  $('#tabel-akun tbody tr').each(function(){
    let rowData=[];
    $(this).find('td').each(function(index){ 
      if(index !== 6) rowData.push($(this).text()); 
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

  doc.save('Data_Akun.pdf');
});
</script>
