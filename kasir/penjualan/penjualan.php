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

if (!isset($_SESSION['id_akun'])) {
  die("ID kasir belum ada di session. Pastikan saat login set \$_SESSION['id_akun'] dari tabel akun.");
}

$id_outlet = (int)$_SESSION['id_outlet'];
$id_kasir  = (int)$_SESSION['id_akun'];

// ==== Ambil NAMA outlet ====
$nama_outlet = 'Outlet';
$qOutlet = mysqli_query($conn, "SELECT nama_outlet FROM outlet WHERE id_outlet = $id_outlet LIMIT 1");
if ($qOutlet && mysqli_num_rows($qOutlet) > 0) {
  $nama_outlet = mysqli_fetch_assoc($qOutlet)['nama_outlet'];
}

// ==== Ambil NAMA kasir ====
// Sesuaikan kolomnya: nama / username / nama_akun
$nama_kasir = 'Kasir';
$qKasir = mysqli_query($conn, "SELECT nama FROM akun WHERE id_akun = $id_kasir LIMIT 1");
if ($qKasir && mysqli_num_rows($qKasir) > 0) {
  $nama_kasir = mysqli_fetch_assoc($qKasir)['nama'];
}

$menus = [];
$qMenu = mysqli_query(
  $conn,
  "SELECT id_menu, nama_menu, harga, jenis, gambar
   FROM menu
   ORDER BY nama_menu"
);
while ($r = mysqli_fetch_assoc($qMenu)) {
  $menus[] = $r;
}

$pesan_sukses = $_SESSION['success_message'] ?? '';
$pesan_gagal  = $_SESSION['error_message'] ?? '';
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

    .konten-utama {
      margin-left: 250px;
      margin-top: 60px;
      padding: 25px 30px;
      min-height: 100vh;
      display: flex;
      align-items: flex-start;
      justify-content: center;
    }

    .pembungkus-penjualan { width: 100%; max-width: 1200px; }

    .pemberitahuan {
      padding: 10px 12px;
      border-radius: 8px;
      margin-bottom: 12px;
      font-size: 13px;
    }

    .pemberitahuan-sukses {
      background: #e8f5e9;
      border: 1px solid #66bb6a;
      color: #2e7d32;
    }

    .pemberitahuan-gagal {
      background: #ffebee;
      border: 1px solid #ef5350;
      color: #c62828;
    }

    .tata-letak-penjualan {
      display: flex;
      gap: 18px;
      align-items: flex-start;
      flex-wrap: wrap;
    }

    .kolom-kiri { flex: 0 0 auto; }
    .kolom-kanan { flex: 1; min-width: 350px; }

    .kotak {
      background: #ffffff;
      border-radius: 14px;
      box-shadow: 0 6px 18px rgba(0,0,0,0.12);
      padding: 16px 18px 18px;
      border: 1px solid #ffe0b2;
    }

    .kotak h3 {
      margin-top: 0;
      margin-bottom: 8px;
      font-size: 15px;
      color: #bf360c;
      display: flex;
      align-items: center;
      gap: 6px;
    }

    .kotak h3::before {
      content: "";
      width: 5px;
      height: 18px;
      border-radius: 999px;
      background: linear-gradient(180deg,#ff9800,#d32f2f);
    }

    .penyaring-menu {
      display: flex;
      gap: 5px;
      margin-top: 4px;
      margin-bottom: 8px;
    }

    .cari-menu, .kategori-menu {
      padding: 7px 9px;
      border-radius: 999px;
      border: 1px solid #ffcc80;
      font-size: 13px;
      background: #fff;
    }

    .cari-menu { flex: 1; }

    .cari-menu:focus, .kategori-menu:focus {
      outline: none;
      border-color: #fb8c00;
      box-shadow: 0 0 0 2px rgba(251,140,0,0.18);
    }

    .kategori-menu { width: 130px; }

    .kisi-menu {
      margin-top: 6px;
      display: grid;
      grid-template-columns: repeat(4, 150px);
      gap: 10px;
      justify-content: flex-start;
      max-height: 430px;
      overflow-y: auto;
      padding-right: 4px;
    }

    .kartu-menu {
      background: #ffffff;
      border-radius: 10px;
      padding: 8px 9px;
      border: 1px solid #ffe0b2;
      cursor: pointer;
      transition: 0.15s;
      box-shadow: 0 1px 3px rgba(0,0,0,0.06);
      display: flex;
      flex-direction: column;
      width: 150px;
      height: 200px;
    }

    .kartu-menu:hover {
      transform: translateY(-2px);
      box-shadow: 0 3px 8px rgba(0,0,0,0.16);
      border-color: #ffb74d;
    }

    .bungkus-gambar-menu {
      width: 100%;
      height: 110px;
      border-radius: 8px;
      overflow: hidden;
      margin-bottom: 6px;
      background: #f5f5f5;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 11px;
      color: #b0b0b0;
    }

    .bungkus-gambar-menu img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      display: block;
    }

    .nama-menu {
      font-size: 13px;
      font-weight: 600;
      color: #424242;
      margin-bottom: 4px;
      overflow: hidden;
      text-overflow: ellipsis;
      white-space: nowrap;
    }

    .harga-menu { font-size: 12px; color: #e65100; }
    .keterangan-menu { font-size: 11px; color: #999; margin-top: 2px; }

    .bungkus-tabel {
      margin-top: 6px;
      border-radius: 10px;
      border: 1px solid #ffe0b2;
      background: #ffffff;
      overflow: hidden;
    }

    .gulir-tabel { max-height: 260px; overflow-y: auto; }

    table { width: 100%; border-collapse: collapse; font-size: 13px; }

    table thead {
      position: sticky;
      top: 0;
      z-index: 1;
      background: linear-gradient(90deg, #d32f2f, #ffb300);
      color: #fff;
    }

    table th {
      padding: 8px;
      text-align: left;
      font-weight: 600;
      white-space: nowrap;
    }

    table td { padding: 7px 8px; border-bottom: 1px solid #ffe0b2; }
    table tbody tr:nth-child(even) { background: #fffdf7; }

    .baris-kosong { text-align: center; color: #999; font-style: italic; }

    .tombol {
      display: inline-block;
      padding: 7px 14px;
      margin-top: 12px;
      cursor: pointer;
      border-radius: 999px;
      border: none;
      font-size: 13px;
      color: #fff;
      background: #d32f2f;
      font-weight: 600;
      letter-spacing: .2px;
    }

    .tombol:hover { background: #b71c1c; }

    .tombol-sekunder { background: #757575; }
    .tombol-sekunder:hover { background: #555; }

    .tombol-bahaya { background: #e53935; }
    .tombol-bahaya:hover { background: #c62828; }

    .tombol-kecil { padding: 4px 8px; font-size: 11px; border-radius: 999px; }

    .tombol-jumlah {
      padding: 3px 7px;
      border-radius: 999px;
      border: none;
      cursor: pointer;
      font-size: 11px;
      background: #eeeeee;
    }
    .tombol-jumlah:hover { background: #e0e0e0; }

    .bungkus-jumlah {
      display: flex;
      align-items: center;
      justify-content: flex-end;
      gap: 3px;
    }

    .input-jumlah {
      width: 48px;
      text-align: center;
      border-radius: 8px;
      border: 1px solid #ffcc80;
      font-size: 12px;
      padding: 3px 4px;
    }

    .input-jumlah:focus {
      outline: none;
      border-color: #fb8c00;
      box-shadow: 0 0 0 1px rgba(251,140,0,0.25);
    }

    .kotak-total {
      margin-top: 10px;
      padding: 10px 12px;
      border-radius: 10px;
      background: #fff3e0;
      border: 1px solid #ffcc80;
      display: flex;
      justify-content: space-between;
      align-items: center;
      font-size: 15px;
      font-weight: 700;
      color: #bf360c;
    }

    .label-total { font-size: 14px; font-weight: 600; }
    .nilai-total { font-size: 17px; }

    .baris-aksi {
      margin-top: 10px;
      display: flex;
      gap: 8px;
      justify-content: flex-end;
    }

    /* ================= MODAL ================= */
    .modal {
      position: fixed;
      inset: 0;
      background: rgba(0,0,0,0.45);
      display: none;
      align-items: center;
      justify-content: center;
      padding: 16px;
      z-index: 9999;
    }

    .modal.is-open { display: flex; }

    .modal-konten {
      width: 100%;
      max-width: 420px;
      background: #fff;
      border-radius: 14px;
      border: 1px solid #ffe0b2;
      box-shadow: 0 10px 30px rgba(0,0,0,0.25);
      overflow: hidden;
    }

    .modal-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 12px 14px;
      background: linear-gradient(90deg, #d32f2f, #ffb300);
      color: #fff;
    }

    .modal-header h4 { margin: 0; font-size: 14px; font-weight: 700; }

    .btn-tutup {
      background: rgba(255,255,255,0.2);
      border: none;
      color: #fff;
      font-weight: 700;
      border-radius: 10px;
      padding: 6px 10px;
      cursor: pointer;
    }

    .modal-body { padding: 14px; }

    /* ================= STRUK RAPiH ================= */
    #print-struk {
      width: 360px;              /* tetap rapih di modal */
      margin: 0 auto;
    }

    #area-struk {
      font-family: "Courier New", monospace;
      font-size: 12px;
      color: #111;
      line-height: 1.25;
    }

    .struk-center { text-align: center; }
    .struk-bold { font-weight: 700; }

    .struk-title {
      font-size: 14px;
      font-weight: 700;
      letter-spacing: 0.5px;
      margin-bottom: 4px;
    }

    .struk-meta {
      font-size: 11px;
      color: #333;
      margin-bottom: 8px;
    }

    .struk-line {
      border-top: 1px dashed #333;
      margin: 8px 0;
    }

    .struk-items { margin-top: 4px; }

    /* setiap item: nama (1 baris), lalu qty x harga (kiri) dan subtotal (kanan) */
    .struk-item { margin: 6px 0; }

    .struk-item-name {
      font-weight: 700;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }

    .struk-item-row {
      display: flex;
      justify-content: space-between;
      gap: 8px;
      font-size: 11px;
      color: #333;
    }

    .struk-item-left {
      flex: 1;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }

    .struk-item-right {
      min-width: 90px;
      text-align: right;
      white-space: nowrap;
    }

    .struk-total-row {
      display: flex;
      justify-content: space-between;
      align-items: baseline;
      font-weight: 700;
      font-size: 13px;
    }

    .struk-footer {
      text-align: center;
      font-size: 11px;
      color: #333;
      margin-top: 6px;
    }

    /* ===== PRINT ONLY STRUK ===== */
    @media print {
      body * { visibility: hidden !important; }
      #print-struk, #print-struk * { visibility: visible !important; }

      #print-struk {
        position: fixed;
        left: 0;
        top: 0;
        width: 80mm;   /* cocok thermal */
        margin: 0;
        padding: 0;
      }

      .modal, .modal-header, .modal-body, .btn-tutup, .tombol { display: none !important; }
    }

    @media (max-width: 768px) {
      .konten-utama {
        margin-left: 0;
        margin-top: 60px;
        padding: 10px 12px;
        align-items: stretch;
        justify-content: flex-start;
      }

      .pembungkus-penjualan { max-width: 100%; }
      .tata-letak-penjualan { flex-direction: column; gap: 12px; }
      .kolom-kiri, .kolom-kanan { width: 100%; }
      .kotak { padding: 12px 12px 14px; border-radius: 12px; }
      .kotak h3 { font-size: 14px; }

      .kisi-menu {
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 8px;
        max-height: 260px;
        padding-right: 0;
      }

      .kartu-menu { width: 100%; height: auto; padding: 8px; }
      .bungkus-gambar-menu { height: 90px; }
      .nama-menu { font-size: 12px; white-space: normal; }

      table { font-size: 12px; }
      table th, table td { padding: 6px; }

      .gulir-tabel { max-height: 220px; }
      .bungkus-jumlah { justify-content: center; }
      .input-jumlah { width: 40px; font-size: 11px; }

      .kotak-total { padding: 8px 10px; font-size: 13px; }
      .label-total { font-size: 13px; }
      .nilai-total { font-size: 15px; }

      .tombol { font-size: 12px; padding: 6px 12px; }
    }
  </style>
</head>

<body>
  <div class="konten-utama">
    <div class="pembungkus-penjualan">

      <?php if ($pesan_sukses): ?>
        <div class="pemberitahuan pemberitahuan-sukses"><?= htmlspecialchars($pesan_sukses); ?></div>
      <?php endif; ?>

      <?php if ($pesan_gagal): ?>
        <div class="pemberitahuan pemberitahuan-gagal"><?= htmlspecialchars($pesan_gagal); ?></div>
      <?php endif; ?>

      <form method="post" id="form-penjualan" action="proses_penjualan.php">
        <div class="tata-letak-penjualan">
          <div class="kolom-kiri">
            <div class="kotak">
              <h3>Pilih Menu</h3>
              <div style="font-size:12px;color:#777;margin-bottom:4px;">
                Klik menu untuk menambah pesanan. Jumlah bisa diketik atau ditambah dengan klik berulang.
              </div>

              <div class="penyaring-menu">
                <input type="text" id="menu-search" class="cari-menu" placeholder="Cari menu...">
                <select id="menu-kategori" class="kategori-menu">
                  <option value="">Semua</option>
                  <option value="Makanan">Makanan</option>
                  <option value="Minuman">Minuman</option>
                </select>
              </div>

              <div class="kisi-menu" id="menu-grid">
                <?php foreach ($menus as $m): ?>
                  <div class="kartu-menu"
                       data-id="<?= (int)$m['id_menu']; ?>"
                       data-nama="<?= htmlspecialchars($m['nama_menu'], ENT_QUOTES); ?>"
                       data-harga="<?= (int)$m['harga']; ?>"
                       data-kategori="<?= htmlspecialchars($m['jenis'], ENT_QUOTES); ?>">
                    <div class="bungkus-gambar-menu">
                      <?php if (!empty($m['gambar'])): ?>
                        <img src="../../uploads/menu/<?= htmlspecialchars($m['gambar']); ?>" alt="gambar menu">
                      <?php else: ?>
                        <span>Tidak ada gambar</span>
                      <?php endif; ?>
                    </div>
                    <div class="nama-menu"><?= htmlspecialchars($m['nama_menu']); ?></div>
                    <div class="harga-menu">Rp <?= number_format((int)$m['harga'], 0, ',', '.'); ?></div>
                    <div class="keterangan-menu"><?= htmlspecialchars($m['jenis']); ?> • Klik untuk tambah</div>
                  </div>
                <?php endforeach; ?>
              </div>

            </div>
          </div>

          <div class="kolom-kanan">
            <div class="kotak">
              <h3>Daftar Pesanan</h3>

              <div class="bungkus-tabel">
                <div class="gulir-tabel">
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
                      <tr class="baris-kosong">
                        <td colspan="5">Belum ada item. Klik menu di sebelah kiri untuk menambah pesanan.</td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>

              <div class="kotak-total">
                <div class="label-total">Total</div>
                <div class="nilai-total">Rp <span id="total_display">0</span></div>
              </div>
              <input type="hidden" name="total_harga" id="total_harga" value="0">

              <div class="baris-aksi">
                <button type="button" class="tombol tombol-sekunder" onclick="resetForm()">Reset</button>
                <button type="button" class="tombol tombol-sekunder" onclick="bukaStruk()">Struk Pemesanan</button>
                <button type="submit" class="tombol">Simpan Transaksi</button>
              </div>

            </div>
          </div>
        </div>
      </form>

    </div>
  </div>

  <!-- MODAL STRUK -->
  <div class="modal" id="modal-struk" onclick="tutupStrukJikaKlikLuar(event)">
    <div class="modal-konten">
      <div class="modal-header">
        <h4>Struk Pemesanan</h4>
        <button type="button" class="btn-tutup" onclick="tutupStruk()">Tutup</button>
      </div>

      <div class="modal-body">
        <div id="print-struk">
          <div id="area-struk">
            <div class="struk-center struk-title">STRUK PEMESANAN</div>

            <div class="struk-center struk-meta">
             Outlet: <?= htmlspecialchars($nama_outlet); ?> • Kasir: <?= htmlspecialchars($nama_kasir); ?><br>
              <span id="struk-tanggal"></span>
            </div>

            <div class="struk-line"></div>

            <div id="struk-items" class="struk-items"></div>

            <div class="struk-line"></div>

            <div class="struk-total-row">
              <div>TOTAL</div>
              <div>Rp <span id="struk-total">0</span></div>
            </div>

            <div class="struk-line"></div>
            <div class="struk-footer">Terima kasih</div>
          </div>
        </div>

        <div style="display:flex; gap:8px; justify-content:flex-end; margin-top:12px;">
          <button type="button" class="tombol tombol-sekunder" onclick="tutupStruk()">Kembali</button>
          <button type="button" class="tombol tombol-cetak">Cetak</button>
        </div>
      </div>
    </div>
  </div>

  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/jspdf@2.5.1/dist/jspdf.umd.min.js"></script>
 

  <script>
    let counter = 0;

    function formatRupiah(angka) {
      return new Intl.NumberFormat('id-ID').format(angka);
    }

    function findRowByMenuId(idMenu) {
      return document.querySelector('#tabel-item tbody tr[data-menu-id="' + idMenu + '"]');
    }

    document.getElementById('menu-grid').addEventListener('click', function (e) {
      const card = e.target.closest('.kartu-menu');
      if (!card) return;

      const idMenu = card.getAttribute('data-id');
      const namaMenu = card.getAttribute('data-nama');
      const harga = parseInt(card.getAttribute('data-harga') || 0, 10);

      tambahAtauUpdateItem(idMenu, namaMenu, harga);
    });

    function tambahAtauUpdateItem(idMenu, namaMenu, harga) {
      const tbody = document.querySelector('#tabel-item tbody');
      const existingRow = findRowByMenuId(idMenu);

      const emptyRow = tbody.querySelector('.baris-kosong');
      if (emptyRow) emptyRow.remove();

      if (existingRow) {
        const inputJumlah = existingRow.querySelector('.input-jumlah');
        const jumlah = parseInt(inputJumlah.value || 0, 10) + 1;
        inputJumlah.value = jumlah;

        const subtotal = harga * jumlah;
        existingRow.querySelector('input[name$="[subtotal]"]').value = subtotal;
        existingRow.querySelector('.subtotal-teks').textContent = formatRupiah(subtotal);
      } else {
        const tr = document.createElement('tr');
        tr.setAttribute('data-index', counter);
        tr.setAttribute('data-menu-id', idMenu);

        const jumlah = 1;
        const subtotal = harga * jumlah;

        tr.innerHTML = `
          <td>
            ${escapeHtml(namaMenu)}
            <input type="hidden" name="items[${counter}][id_menu]" value="${idMenu}">
            <input type="hidden" name="items[${counter}][nama_menu]" value="${escapeHtml(namaMenu)}">
          </td>
          <td>
            Rp ${formatRupiah(harga)}
            <input type="hidden" name="items[${counter}][harga]" value="${harga}">
          </td>
          <td>
            <div class="bungkus-jumlah">
              <button type="button" class="tombol-jumlah" data-action="minus">-</button>
              <input type="number" min="0" class="input-jumlah" name="items[${counter}][jumlah]" value="${jumlah}">
              <button type="button" class="tombol-jumlah" data-action="plus">+</button>
            </div>
          </td>
          <td>
            Rp <span class="subtotal-teks">${formatRupiah(subtotal)}</span>
            <input type="hidden" name="items[${counter}][subtotal]" value="${subtotal}">
          </td>
          <td>
            <button type="button" class="tombol tombol-bahaya tombol-kecil" data-action="hapus">Hapus</button>
          </td>
        `;

        tbody.appendChild(tr);
        counter++;
      }

      hitungTotal();
    }

    document.querySelector('#tabel-item tbody').addEventListener('click', function (e) {
      const btn = e.target;

      if (btn.dataset.action === 'hapus') {
        const row = btn.closest('tr');
        if (row) row.remove();
        handleEmptyTable();
        hitungTotal();
        return;
      }

      if (btn.classList.contains('tombol-jumlah')) {
        const row = btn.closest('tr');
        const harga = parseInt(row.querySelector('input[name$="[harga]"]').value || 0, 10);
        const inputJumlah = row.querySelector('.input-jumlah');

        let jumlah = parseInt(inputJumlah.value || 0, 10);
        if (btn.dataset.action === 'plus') jumlah++;
        if (btn.dataset.action === 'minus') jumlah--;

        if (jumlah <= 0 || isNaN(jumlah)) {
          row.remove();
          handleEmptyTable();
          hitungTotal();
          return;
        }

        inputJumlah.value = jumlah;
        const subtotal = harga * jumlah;
        row.querySelector('input[name$="[subtotal]"]').value = subtotal;
        row.querySelector('.subtotal-teks').textContent = formatRupiah(subtotal);

        hitungTotal();
      }
    });

    document.querySelector('#tabel-item tbody').addEventListener('input', function (e) {
      if (!e.target.classList.contains('input-jumlah')) return;

      const inputJumlah = e.target;
      const row = inputJumlah.closest('tr');
      const harga = parseInt(row.querySelector('input[name$="[harga]"]').value || 0, 10);

      if (inputJumlah.value === '') {
        row.querySelector('.subtotal-teks').textContent = '0';
        row.querySelector('input[name$="[subtotal]"]').value = 0;
        hitungTotal();
        return;
      }

      let jumlah = parseInt(inputJumlah.value, 10);
      if (isNaN(jumlah) || jumlah < 0) jumlah = 0;
      inputJumlah.value = jumlah;

      const subtotal = harga * jumlah;
      row.querySelector('input[name$="[subtotal]"]').value = subtotal;
      row.querySelector('.subtotal-teks').textContent = formatRupiah(subtotal);

      if (jumlah === 0) {
        row.remove();
        handleEmptyTable();
      }

      hitungTotal();
    });

    document.querySelector('#tabel-item tbody').addEventListener('blur', function (e) {
      if (!e.target.classList.contains('input-jumlah')) return;

      const inputJumlah = e.target;
      const row = inputJumlah.closest('tr');
      const jumlah = parseInt(inputJumlah.value || 0, 10);

      if (isNaN(jumlah) || jumlah <= 0) {
        row.remove();
        handleEmptyTable();
        hitungTotal();
      }
    }, true);

    function handleEmptyTable() {
      const tbody = document.querySelector('#tabel-item tbody');
      const rows = tbody.querySelectorAll('tr[data-menu-id]');
      if (rows.length) return;

      tbody.innerHTML = `
        <tr class="baris-kosong">
          <td colspan="5">Belum ada item. Klik menu di sebelah kiri untuk menambah pesanan.</td>
        </tr>
      `;
      counter = 0;
    }

    function hitungTotal() {
      let total = 0;
      document.querySelectorAll('#tabel-item tbody input[name$="[subtotal]"]').forEach(function (input) {
        total += parseInt(input.value || 0, 10);
      });

      document.getElementById('total_display').innerText = formatRupiah(total);
      document.getElementById('total_harga').value = total;
    }

    function resetForm() {
      if (!confirm('Reset form dan hapus semua item?')) return;

      document.getElementById('form-penjualan').reset();
      document.querySelector('#tabel-item tbody').innerHTML = `
        <tr class="baris-kosong">
          <td colspan="5">Belum ada item. Klik menu di sebelah kiri untuk menambah pesanan.</td>
        </tr>
      `;
      document.getElementById('total_display').innerText = '0';
      document.getElementById('total_harga').value = '0';
      counter = 0;
    }

    function filterMenu() {
      const keyword = document.getElementById('menu-search').value.toLowerCase();
      const kategori = document.getElementById('menu-kategori').value;

      document.querySelectorAll('.kartu-menu').forEach(function (card) {
        const namaCard = (card.getAttribute('data-nama') || '').toLowerCase();
        const katCard = card.getAttribute('data-kategori') || '';

        const matchNama = namaCard.includes(keyword);
        const matchKat = kategori === '' || katCard === kategori;

        card.style.display = (matchNama && matchKat) ? 'flex' : 'none';
      });
    }

    document.getElementById('menu-search').addEventListener('input', filterMenu);
    document.getElementById('menu-kategori').addEventListener('change', filterMenu);

    function updateStrukPreview() {
      const rows = document.querySelectorAll('#tabel-item tbody tr[data-menu-id]');
      if (!rows.length) return false;

      // tanggal/jam
      const now = new Date();
      const tanggal = now.toLocaleString('id-ID', { dateStyle: 'medium', timeStyle: 'short' });
      const elTanggal = document.getElementById('struk-tanggal');
      if (elTanggal) elTanggal.textContent = tanggal;

      // items
      const container = document.getElementById('struk-items');
      if (!container) return false;
      container.innerHTML = '';

      rows.forEach((row) => {
        const nama = row.querySelector('input[name$="[nama_menu]"]')?.value || '-';
        const harga = parseInt(row.querySelector('input[name$="[harga]"]')?.value || 0, 10);
        const jumlah = parseInt(row.querySelector('input[name$="[jumlah]"]')?.value || 0, 10);
        const subtotal = parseInt(row.querySelector('input[name$="[subtotal]"]')?.value || 0, 10);

        const div = document.createElement('div');
        div.className = 'struk-item';
        div.innerHTML = `
          <div class="struk-item-name">${escapeHtml(nama)}</div>
          <div class="struk-item-row">
            <div class="struk-item-left">${jumlah} x Rp ${formatRupiah(harga)}</div>
            <div class="struk-item-right">Rp ${formatRupiah(subtotal)}</div>
          </div>
        `;
        container.appendChild(div);
      });

      // total
      const total = parseInt(document.getElementById('total_harga')?.value || 0, 10);
      const elTotal = document.getElementById('struk-total');
      if (elTotal) elTotal.textContent = formatRupiah(total);

      return true;
    }

    function bukaStruk() {
      const ok = updateStrukPreview();
      if (!ok) {
        alert('Belum ada item untuk dibuat struk.');
        return;
      }
      document.getElementById('modal-struk').classList.add('is-open');
    }

    function tutupStruk() {
      document.getElementById('modal-struk').classList.remove('is-open');
    }

    function tutupStrukJikaKlikLuar(e) {
      if (e.target && e.target.id === 'modal-struk') tutupStruk();
    }

    function escapeHtml(str) {
      return String(str)
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');
    }

    function pxToMm(px) {
      return px * 0.264583; // approx px -> mm
    }

$(document).on('click', '.tombol-cetak', function () {
  const data = buildReceiptData();
  if (!data.items.length) {
    alert('Belum ada item untuk dicetak.');
    return;
  }

  const { jsPDF } = window.jspdf;

  // ====== SETTING STRUK (80mm) ======
  const paperWidth = 80;          // mm
  const margin = 4;               // mm
  const lineH = 4.2;              // mm (tinggi baris)
  const usableW = paperWidth - (margin * 2);

  // hitung tinggi dinamis (judul + meta + garis + (tiap item 2 baris) + total + footer)
  const baseLines = 10; // judul+meta+garis+total+footer
  const itemLines = data.items.length * 2;
  const estHeight = (baseLines + itemLines) * lineH + 20; // +20 buffer
  const paperHeight = Math.max(120, estHeight);

  const doc = new jsPDF({
    orientation: 'p',
    unit: 'mm',
    format: [paperWidth, paperHeight]
  });

  // Font seperti struk
  doc.setFont('courier', 'normal');
  doc.setFontSize(10);

  let y = 6;

  // Helper teks
  const center = (txt) => doc.text(txt, paperWidth / 2, y, { align: 'center' });
  const left = (txt) => doc.text(txt, margin, y);
  const right = (txt) => doc.text(txt, paperWidth - margin, y, { align: 'right' });

 const line = () => {
  doc.setLineWidth(0.2);
  doc.setLineDashPattern([1, 1], 0); // dash 1mm, gap 1mm
  doc.line(margin, y, paperWidth - margin, y);
  doc.setLineDashPattern([], 0);     // reset biar teks lain normal
};

  // ====== HEADER ======
  doc.setFont('courier', 'bold');
  doc.setFontSize(12);
  center('STRUK PEMESANAN');
  y += lineH + 1;

  doc.setFont('courier', 'normal');
  doc.setFontSize(9);
  center(`Outlet: ${data.outlet}  Kasir: ${data.kasir}`);
  y += lineH;
  center(data.tanggal);
  y += lineH;

  y += 1;
  line();
  y += lineH;

  // ====== ITEMS ======
  doc.setFontSize(10);

  data.items.forEach((it) => {
    // Nama item (1 baris)
    doc.setFont('courier', 'bold');
    const name = String(it.nama);

    // potong nama biar gak lewat (monospace)
    const maxChars = 32;
    const nameCut = name.length > maxChars ? name.slice(0, maxChars - 3) + '...' : name;

    left(nameCut);
    y += lineH;

    // Baris qty x harga (kiri) & subtotal (kanan)
    doc.setFont('courier', 'normal');
    const leftText = `${it.jumlah} x Rp ${rupiahPlain(it.harga)}`;
    const rightText = `Rp ${rupiahPlain(it.subtotal)}`;

    // kalau kiri kepanjangan, potong
    const leftCut = leftText.length > 24 ? leftText.slice(0, 24) : leftText;

    left(leftCut);
    right(rightText);
    y += lineH;
  });

  y += 1;
  line();
  y += lineH;

  // ====== TOTAL ======
  doc.setFont('courier', 'bold');
  doc.setFontSize(11);
  left('TOTAL');
  right(`Rp ${rupiahPlain(data.total)}`);
  y += lineH;

  y += 1;
  line();
  y += lineH;

  // ====== FOOTER ======
  doc.setFont('courier', 'normal');
  doc.setFontSize(9);
  center('Terima kasih');

  // ====== PRINT ======
  const blobUrl = doc.output('bloburl');
  const w = window.open(blobUrl, '_blank');
  if (w) {
    w.onload = function () { w.focus(); w.print(); };
  } else {
    doc.save('Struk_Pemesanan.pdf');
  }
});



    function rupiahPlain(n) {
  return new Intl.NumberFormat('id-ID').format(n);
}

function buildReceiptData() {
  const rows = document.querySelectorAll('#tabel-item tbody tr[data-menu-id]');
  const items = [];

  rows.forEach((row) => {
    const nama = row.querySelector('input[name$="[nama_menu]"]')?.value || '-';
    const harga = parseInt(row.querySelector('input[name$="[harga]"]')?.value || 0, 10);
    const jumlah = parseInt(row.querySelector('input[name$="[jumlah]"]')?.value || 0, 10);
    const subtotal = parseInt(row.querySelector('input[name$="[subtotal]"]')?.value || 0, 10);

    items.push({ nama, harga, jumlah, subtotal });
  });

  const total = parseInt(document.getElementById('total_harga')?.value || 0, 10);

  const now = new Date();
  const tanggal = now.toLocaleString('id-ID', { dateStyle: 'medium', timeStyle: 'short' });

 return {
  outlet: <?= json_encode($nama_outlet); ?>,
  kasir: <?= json_encode($nama_kasir); ?>,
  tanggal,
  items,
  total
};
}
  </script>
</body>
</html>