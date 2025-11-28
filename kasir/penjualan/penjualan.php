<?php
session_start();
include '../../koneksi/sidebarkasir.php';
include '../../koneksi/koneksi.php';

/**
 * CEK LOGIN & ROLE
 */
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'kasir') {
    header("Location: ../../index.php");
    exit;
}

if (!isset($_SESSION['id_outlet'])) {
    die("Outlet untuk kasir belum di-set. Pastikan kolom id_outlet di tabel pengguna dan session sudah benar.");
}

if (!isset($_SESSION['id_user'])) {
    die("ID kasir belum ada di session. Cek lagi proses login, harus set \$_SESSION['id_user'].");
}

/**
 * AMBIL DATA MENU DARI DATABASE
 */
$menus = [];
$qMenu = mysqli_query(
    $conn,
    "SELECT id, nama_menu, harga, kategori, gambar 
     FROM menu_makanan 
     ORDER BY nama_menu"
);
while ($r = mysqli_fetch_assoc($qMenu)) {
    $menus[] = $r;
}

/**
 * AMBIL PESAN SUKSES / ERROR DARI SESSION (FLASH MESSAGE)
 */
$success_message = $_SESSION['success_message'] ?? '';
$error_message   = $_SESSION['error_message'] ?? '';
unset($_SESSION['success_message'], $_SESSION['error_message']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Input Penjualan - Kasir</title>

  <style>
  * { box-sizing: border-box; }

  body {
    margin: 0;
    font-family: Arial, sans-serif;
    background: radial-gradient(circle at top left, #fff7e0 0%, #ffe3b3 40%, #ffffff 100%);
  }

  /* KONTEN UTAMA: isi di tengah, ada ruang untuk sidebar */
  .konten-utama {
    margin-left:250px;  
    margin-top:60px;            
    padding:25px 30px;
    min-height:100vh;               
    display:flex;                   
    align-items:flex-start;         
    justify-content:center;         
  }

  .wrapper-penjualan {
    width:100%;
    max-width:1200px;
  }

  /* ALERT */
  .alert {
    padding:10px 12px;
    border-radius:8px;
    margin-bottom:12px;
    font-size:13px;
  }

  .alert-success {
    background:#e8f5e9;
    border:1px solid #66bb6a;
    color:#2e7d32;
  }

  .alert-error {
    background:#ffebee;
    border:1px solid #ef5350;
    color:#c62828;
  }

  /* LAYOUT KIRI / KANAN – TIDAK DALAM 1 KARTU */
  .layout-penjualan {
    display:flex;
    gap:18px;
    align-items:flex-start;
    flex-wrap:wrap;
  }

  .col-left {
    flex:0 0 auto;
  }

  .col-right {
    flex:1;
    min-width:350px;
  }

  .panel {
    background:#ffffff;
    border-radius:14px;
    box-shadow:0 6px 18px rgba(0,0,0,0.12);
    padding:16px 18px 18px;
    border:1px solid #ffe0b2;
  }

  .panel h3 {
    margin-top:0;
    margin-bottom:8px;
    font-size:15px;
    color:#bf360c;
    display:flex;
    align-items:center;
    gap:6px;
  }

  .panel h3::before {
    content:"";
    width:5px;
    height:18px;
    border-radius:999px;
    background:linear-gradient(180deg,#ff9800,#d32f2f);
  }

  /* FILTER & SEARCH MENU */
  .menu-filters {
    display:flex;
    gap:5px;
    margin-top:4px;
    margin-bottom:8px;
  }

  .menu-search {
    flex:1;
    padding:7px 9px;
    border-radius:999px;
    border:1px solid #ffcc80;
    font-size:13px;
  }

  .menu-search:focus {
    outline:none;
    border-color:#fb8c00;
    box-shadow:0 0 0 2px rgba(251,140,0,0.18);
  }

  .menu-kategori {
    width:130px;
    padding:7px 9px;
    border-radius:999px;
    border:1px solid #ffcc80;
    font-size:13px;
    background:#fff;
  }

  .menu-kategori:focus {
    outline:none;
    border-color:#fb8c00;
    box-shadow:0 0 0 2px rgba(251,140,0,0.18);
  }

  /* GRID MENU – 5 kolom, tiap kartu 150x200 */
  .menu-grid {
    margin-top:6px;
    display:grid;
    grid-template-columns: repeat(4, 150px);  /* 5 per baris, fixed 150px */
    gap:10px;
    justify-content:flex-start;

    max-height:430px;      /* kalau kebanyakan menu, scroll */
    overflow-y:auto;
    padding-right:4px;
  }

  /* MENU CARD UKURAN FIX */
  .menu-card {
    background:#ffffff;
    border-radius:10px;
    padding:8px 9px;
    border:1px solid #ffe0b2;
    cursor:pointer;
    transition:0.15s;
    box-shadow:0 1px 3px rgba(0,0,0,0.06);
    display:flex;
    flex-direction:column;
    width:150px;
    height:200px;           /* tinggi fix 200px */
  }

  .menu-card:hover {
    transform:translateY(-2px);
    box-shadow:0 3px 8px rgba(0,0,0,0.16);
    border-color:#ffb74d;
  }

  .menu-image-wrap {
    width:100%;
    height:110px;
    border-radius:8px;
    overflow:hidden;
    margin-bottom:6px;
    background:#f5f5f5;
    display:flex;
    align-items:center;
    justify-content:center;
    font-size:11px;
    color:#b0b0b0;
  }

  .menu-image-wrap img {
    width:100%;
    height:100%;
    object-fit:cover;
    display:block;
  }

  .menu-nama {
    font-size:13px;
    font-weight:600;
    color:#424242;
    margin-bottom:4px;
    overflow:hidden;
    text-overflow:ellipsis;
    white-space:nowrap;
  }

  .menu-harga {
    font-size:12px;
    color:#e65100;
  }

  .menu-keterangan {
    font-size:11px;
    color:#999;
    margin-top:2px;
  }

  /* RESPONSIVE */
  @media (max-width: 1200px) {
    .menu-grid {
      grid-template-columns: repeat(4, 150px);
    }
  }

  @media (max-width: 992px) {
    .konten-utama {
      margin-left:0;
      padding:15px;
    }
    .layout-penjualan {
      flex-direction:column;
    }
    .col-left {
      width:100%;
    }
    .menu-grid {
      grid-template-columns: repeat(4, 150px);
    }
  }

  @media (max-width: 800px) {
    .menu-grid {
      grid-template-columns: repeat(3, 150px);
    }
  }

  @media (max-width: 600px) {
    .menu-grid {
      grid-template-columns: repeat(2, 150px);
      justify-content:center;
    }
  }

  /* WRAPPER TABEL DI KANAN – biar bisa scroll sendiri */
  .tabel-wrapper {
    margin-top:6px;
    border-radius:10px;
    border:1px solid #ffe0b2;
    background:#ffffff;
    overflow:hidden;
  }

  .tabel-scroll {
    max-height:260px;
    overflow-y:auto;
  }

  /* TABEL */
  table {
    width:100%;
    border-collapse:collapse;
    font-size:13px;
  }

  table thead {
    position:sticky;
    top:0;
    z-index:1;
    background: linear-gradient(90deg, #d32f2f, #ffb300);
    color:#fff;
  }

  table th {
    padding:8px 8px;
    text-align:left;
    font-weight:600;
    white-space:nowrap;
  }

  table td {
    padding:7px 8px;
    border-bottom:1px solid #ffe0b2;
  }

  table tbody tr:nth-child(even) {
    background:#fffdf7;
  }


  .empty-row {
    text-align:center;
    color:#999;
    font-style:italic;
  }

  /* BUTTONS */
  .btn {
    display:inline-block;
    padding:7px 14px;
    margin-top:12px;
    cursor:pointer;
    border-radius:999px;
    border:none;
    font-size:13px;
    color:#fff;
    background:#d32f2f;
    font-weight:600;
    letter-spacing:.2px;
  }

  .btn:hover { background:#b71c1c; }

  .btn-secondary {
    background:#757575;
  }

  .btn-secondary:hover {
    background:#555;
  }

  .btn-danger {
    background:#e53935;
  }

  .btn-danger:hover {
    background:#c62828;
  }

  .btn-small {
    padding:4px 8px;
    font-size:11px;
    border-radius:999px;
  }

  /* JUMLAH */
  .btn-jumlah {
    padding:3px 7px;
    border-radius:999px;
    border:none;
    cursor:pointer;
    font-size:11px;
    background:#eeeeee;
  }

  .btn-jumlah:hover {
    background:#e0e0e0;
  }

  .jumlah-wrapper {
    display:flex;
    align-items:center;
    justify-content:flex-end;
    gap:3px;
  }

  .jumlah-input {
    width:48px;
    text-align:center;
    border-radius:8px;
    border:1px solid #ffcc80;
    font-size:12px;
    padding:3px 4px;
  }

  .jumlah-input:focus {
    outline:none;
    border-color:#fb8c00;
    box-shadow:0 0 0 1px rgba(251,140,0,0.25);
  }

  /* TOTAL & ACTION */
  .total-box {
    margin-top:10px;
    padding:10px 12px;
    border-radius:10px;
    background:#fff3e0;
    border:1px solid #ffcc80;
    display:flex;
    justify-content:space-between;
    align-items:center;
    font-size:15px;
    font-weight:700;
    color:#bf360c;
  }

  .total-label {
    font-size:14px;
    font-weight:600;
  }

  .total-value {
    font-size:17px;
  }

  .action-row {
    margin-top:10px;
    display:flex;
    gap:8px;
    justify-content:flex-end;
  }
  /* ========== RESPONSIVE KHUSUS LAYAR KECIL (HP) ========== */
@media (max-width: 768px) {
  /* konten full width, tanpa ruang sidebar kiri */
  .konten-utama {
    margin-left: 0;
    margin-top: 60px;
    padding: 10px 12px;
    align-items: stretch;
    justify-content: flex-start;
  }

  .wrapper-penjualan {
    max-width: 100%;
  }

  /* urutan sudah menu -> pesanan, tapi kita pastikan column */
  .layout-penjualan {
    flex-direction: column;
    gap: 12px;
  }

  .col-left,
  .col-right {
    width: 100%;
  }

  .panel {
    padding: 12px 12px 14px;
    border-radius: 12px;
  }

  .panel h3 {
    font-size: 14px;
  }

  /* GRID MENU DI HP: 2 kolom fleksibel */
  .menu-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr)); /* 2 kolom */
    gap: 8px;
    max-height: 260px;         /* lebih pendek supaya tidak terlalu panjang */
    padding-right: 0;
  }

  /* kartu menu jangan fixed width/height */
  .menu-card {
    width: 100%;
    height: auto;
    padding: 8px;
  }

  .menu-image-wrap {
    height: 90px;
  }

  .menu-nama {
    font-size: 12px;
    white-space: normal;       /* boleh 2 baris */
  }

  .menu-harga {
    font-size: 12px;
  }

  .menu-keterangan {
    font-size: 10px;
  }

  /* TABEL PESANAN DI BAWAH: font sedikit diperkecil */
  table {
    font-size: 12px;
  }

  table th,
  table td {
    padding: 6px 6px;
  }

  .tabel-wrapper {
    margin-top: 6px;
  }

  .tabel-scroll {
    max-height: 220px;         /* biar tidak terlalu tinggi di HP */
  }

  .jumlah-wrapper {
    justify-content: center;
  }

  .jumlah-input {
    width: 40px;
    font-size: 11px;
  }

  .btn-jumlah {
    font-size: 11px;
    padding: 3px 6px;
  }

  /* TOTAL & BUTTONS DI HP */
  .total-box {
    flex-direction: row;
    align-items: center;
    gap: 6px;
    padding: 8px 10px;
    font-size: 13px;
  }

  .total-label {
    font-size: 13px;
  }

  .total-value {
    font-size: 15px;
  }

  .action-row {
    flex-direction: row;
    justify-content: flex-end;
    gap: 6px;
  }

  .btn {
    font-size: 12px;
    padding: 6px 12px;
  }
}
  </style>
</head>
<body>
<div class="konten-utama">
  <div class="wrapper-penjualan">

    <?php if ($success_message): ?>
      <div class="alert alert-success"><?= htmlspecialchars($success_message); ?></div>
    <?php endif; ?>

    <?php if ($error_message): ?>
      <div class="alert alert-error"><?= htmlspecialchars($error_message); ?></div>
    <?php endif; ?>

    <!-- FORM KIRIM KE FILE PROSES -->
    <form method="post" id="form-penjualan" action="proses_penjualan.php">
      <div class="layout-penjualan">
        <!-- KIRI: DAFTAR MENU (TERPISAH CARD) -->
        <div class="col-left">
          <div class="panel">
            <h3>Pilih Menu</h3>
            <div style="font-size:12px;color:#777;margin-bottom:4px;">
              Klik menu untuk menambah pesanan. Jumlah bisa diketik atau ditambah dengan klik berulang.
            </div>

            <div class="menu-filters">
              <input type="text" id="menu-search" class="menu-search" placeholder="Cari menu...">
              <select id="menu-kategori" class="menu-kategori">
                <option value="">Semua</option>
                <option value="Makanan">Makanan</option>
                <option value="Minuman">Minuman</option>
              </select>
            </div>

            <div class="menu-grid" id="menu-grid">
              <?php foreach ($menus as $m): ?>
                <div class="menu-card"
                     data-id="<?= $m['id']; ?>"
                     data-nama="<?= htmlspecialchars($m['nama_menu'], ENT_QUOTES); ?>"
                     data-harga="<?= $m['harga']; ?>"
                     data-kategori="<?= htmlspecialchars($m['kategori'], ENT_QUOTES); ?>">
                  <div class="menu-image-wrap">
                    <?php if (!empty($m['gambar'])): ?>
                      <img src="../../uploads/menu/<?= htmlspecialchars($m['gambar']); ?>" alt="gambar menu">
                    <?php else: ?>
                      <span>Tidak ada gambar</span>
                    <?php endif; ?>
                  </div>
                  <div class="menu-nama"><?= htmlspecialchars($m['nama_menu']); ?></div>
                  <div class="menu-harga">Rp <?= number_format($m['harga'], 0, ',', '.'); ?></div>
                  <div class="menu-keterangan"><?= htmlspecialchars($m['kategori']); ?> • Klik untuk tambah</div>
                </div>
              <?php endforeach; ?>
            </div>
          </div>
        </div>

        <!-- KANAN: DAFTAR PESANAN (CARD TERPISAH) -->
        <div class="col-right">
          <div class="panel">
            <h3>Daftar Pesanan</h3>

            <div class="tabel-wrapper">
              <div class="tabel-scroll">
                <table id="tabel-item">
                  <thead>
                    <tr>
                      <th>Menu</th>
                      <th>Harga</th>
                      <th>Jumlah</th>
                      <th>Subtotal</th>
                      <th>Aksi</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr class="empty-row">
                      <td colspan="5">Belum ada item. Klik menu di sebelah kiri untuk menambah pesanan.</td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>

            <div class="total-box">
              <div class="total-label">Total</div>
              <div class="total-value">Rp <span id="total_display">0</span></div>
            </div>
            <input type="hidden" name="total_harga" id="total_harga" value="0">

            <div class="action-row">
              <button type="button" class="btn btn-secondary" onclick="resetForm()">Reset</button>
              <button type="submit" class="btn">Simpan Transaksi</button>
            </div>
          </div>
        </div>
      </div>
    </form>
  </div>
</div>

<script>
/**
 * SCRIPT UNTUK:
 * - klik menu -> tambah ke tabel
 * - atur jumlah +/- 
 * - hitung total
 * - filter menu
 */

let counter = 0;

function findRowByMenuId(idMenu) {
  return document.querySelector('#tabel-item tbody tr[data-menu-id="' + idMenu + '"]');
}

function formatRupiah(angka) {
  return new Intl.NumberFormat('id-ID').format(angka);
}

// ================== KLIK KARTU MENU ==================
document.getElementById('menu-grid').addEventListener('click', function(e) {
  const card = e.target.closest('.menu-card');
  if (!card) return;

  const idMenu   = card.getAttribute('data-id');
  const namaMenu = card.getAttribute('data-nama');
  const harga    = parseInt(card.getAttribute('data-harga') || 0);

  tambahAtauUpdateItem(idMenu, namaMenu, harga);
});

/**
 * Tambah item baru atau update jumlah item yang sudah ada
 */
function tambahAtauUpdateItem(idMenu, namaMenu, harga) {
  const tbody = document.querySelector('#tabel-item tbody');
  const existingRow = findRowByMenuId(idMenu);

  const emptyRow = document.querySelector('#tabel-item tbody .empty-row');
  if (emptyRow) emptyRow.remove();

  if (existingRow) {
    // Jika menu sudah ada di tabel, cukup tambah jumlah
    const inputJumlah = existingRow.querySelector('.jumlah-input');
    let jumlah = parseInt(inputJumlah.value || 0) + 1;
    inputJumlah.value = jumlah;

    const subtotal = harga * jumlah;
    existingRow.querySelector('input[name$="[subtotal]"]').value = subtotal;
    existingRow.querySelector('.subtotal_text').textContent = formatRupiah(subtotal);
  } else {
    // Jika belum ada, buat baris baru
    const tr = document.createElement('tr');
    tr.setAttribute('data-index', counter);
    tr.setAttribute('data-menu-id', idMenu);

    const jumlah = 1;
    const subtotal = harga * jumlah;

    tr.innerHTML = `
      <td>
        ${namaMenu}
        <input type="hidden" name="items[${counter}][id_menu]" value="${idMenu}">
        <input type="hidden" name="items[${counter}][nama_menu]" value="${namaMenu}">
      </td>
      <td>
        Rp ${formatRupiah(harga)}
        <input type="hidden" name="items[${counter}][harga]" value="${harga}">
      </td>
      <td class="text-right">
        <div class="jumlah-wrapper">
          <button type="button" class="btn-jumlah" data-action="minus">-</button>
          <input type="number"
                 min="0"
                 class="jumlah-input"
                 name="items[${counter}][jumlah]"
                 value="${jumlah}">
          <button type="button" class="btn-jumlah" data-action="plus">+</button>
        </div>
      </td>
      <td class="text-right">
        Rp <span class="subtotal_text">${formatRupiah(subtotal)}</span>
        <input type="hidden" name="items[${counter}][subtotal]" value="${subtotal}">
      </td>
      <td class="text-right">
        <button type="button" class="btn btn-danger btn-small" data-action="hapus">
          Hapus
        </button>
      </td>
    `;

    tbody.appendChild(tr);
    counter++;
  }

  hitungTotal();
}

// ================== TOMBOL + / - & HAPUS ==================
document.querySelector('#tabel-item tbody').addEventListener('click', function(e) {
  const btn = e.target;

  // Hapus baris
  if (btn.dataset.action === 'hapus') {
    const row = btn.closest('tr');
    if (row) {
      row.remove();
      if (!document.querySelector('#tabel-item tbody tr')) {
        const tbody = document.querySelector('#tabel-item tbody');
        const emptyTr = document.createElement('tr');
        emptyTr.classList.add('empty-row');
        emptyTr.innerHTML = '<td colspan="5">Belum ada item. Klik menu di sebelah kiri untuk menambah pesanan.</td>';
        tbody.appendChild(emptyTr);
      }
      hitungTotal();
    }
    return;
  }

  // Tombol + / -
  if (btn.classList.contains('btn-jumlah')) {
    const wrapper    = btn.closest('.jumlah-wrapper');
    const row        = btn.closest('tr');
    const harga      = parseInt(row.querySelector('input[name$="[harga]"]').value || 0);
    const inputJumlah= wrapper.querySelector('.jumlah-input');

    let jumlah = parseInt(inputJumlah.value || 0);
    if (btn.dataset.action === 'plus') {
      jumlah++;
    } else if (btn.dataset.action === 'minus') {
      jumlah--;
    }

    // Jika jumlah <= 0 -> hapus baris
    if (jumlah <= 0 || isNaN(jumlah)) {
      row.remove();
      if (!document.querySelector('#tabel-item tbody tr')) {
        const tbody = document.querySelector('#tabel-item tbody');
        const emptyTr = document.createElement('tr');
        emptyTr.classList.add('empty-row');
        emptyTr.innerHTML = '<td colspan="5">Belum ada item. Klik menu di sebelah kiri untuk menambah pesanan.</td>';
        tbody.appendChild(emptyTr);
      }
      hitungTotal();
      return;
    }

    inputJumlah.value = jumlah;
    const subtotal = harga * jumlah;
    row.querySelector('input[name$="[subtotal]"]').value = subtotal;
    row.querySelector('.subtotal_text').textContent = formatRupiah(subtotal);

    hitungTotal();
  }
});

// ================== EDIT JUMLAH MANUAL ==================
document.querySelector('#tabel-item tbody').addEventListener('input', function(e) {
  if (!e.target.classList.contains('jumlah-input')) return;

  const inputJumlah = e.target;
  const row         = inputJumlah.closest('tr');
  const harga       = parseInt(row.querySelector('input[name$="[harga]"]').value || 0);

  let jumlah = parseInt(inputJumlah.value);

  // Saat sedang mengetik dan kosong
  if (inputJumlah.value === '' || isNaN(jumlah)) {
    row.querySelector('.subtotal_text').textContent = '0';
    row.querySelector('input[name$="[subtotal]"]').value = 0;
    hitungTotal();
    return;
  }

  if (jumlah < 0) jumlah = 0;
  inputJumlah.value = jumlah;

  const subtotal = harga * jumlah;
  row.querySelector('input[name$="[subtotal]"]').value = subtotal;
  row.querySelector('.subtotal_text').textContent = formatRupiah(subtotal);

  hitungTotal();
});

// Jika blur dan jumlah <= 0 -> hapus baris
document.querySelector('#tabel-item tbody').addEventListener('blur', function(e) {
  if (!e.target.classList.contains('jumlah-input')) return;

  const inputJumlah = e.target;
  const row         = inputJumlah.closest('tr');
  let jumlah        = parseInt(inputJumlah.value);

  if (isNaN(jumlah) || jumlah <= 0) {
    row.remove();
    if (!document.querySelector('#tabel-item tbody tr')) {
      const tbody = document.querySelector('#tabel-item tbody');
      const emptyTr = document.createElement('tr');
      emptyTr.classList.add('empty-row');
      emptyTr.innerHTML = '<td colspan="5">Belum ada item. Klik menu di sebelah kiri untuk menambah pesanan.</td>';
      tbody.appendChild(emptyTr);
    }
    hitungTotal();
  }
}, true);

// ================== TOTAL ==================
function hitungTotal() {
  let total = 0;
  document.querySelectorAll('#tabel-item tbody input[name$="[subtotal]"]').forEach(function (input) {
    total += parseInt(input.value || 0);
  });

  document.getElementById('total_display').innerText = formatRupiah(total);
  document.getElementById('total_harga').value = total;
}

// ================== RESET ==================
function resetForm() {
  if (confirm('Reset form dan hapus semua item?')) {
    document.getElementById('form-penjualan').reset();
    const tbody = document.querySelector('#tabel-item tbody');
    tbody.innerHTML = `
      <tr class="empty-row">
        <td colspan="5">Belum ada item. Klik menu di sebelah kiri untuk menambah pesanan.</td>
      </tr>
    `;
    document.getElementById('total_display').innerText = '0';
    document.getElementById('total_harga').value = '0';
    counter = 0;
  }
}

// ================== FILTER MENU (SEARCH + KATEGORI) ==================
function filterMenu() {
  const keyword  = document.getElementById('menu-search').value.toLowerCase();
  const kategori = document.getElementById('menu-kategori').value;

  document.querySelectorAll('.menu-card').forEach(function(card) {
    const namaCard = card.getAttribute('data-nama').toLowerCase();
    const katCard  = card.getAttribute('data-kategori');

    const matchNama = namaCard.includes(keyword);
    const matchKat  = kategori === '' || katCard === kategori;

    card.style.display = (matchNama && matchKat) ? 'flex' : 'none';
  });
}

document.getElementById('menu-search').addEventListener('input', filterMenu);
document.getElementById('menu-kategori').addEventListener('change', filterMenu);
</script>
</body>
</html>
