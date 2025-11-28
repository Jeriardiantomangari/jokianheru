<?php
session_start();
include '../../koneksi/sidebarowner.php'; 
include '../../koneksi/koneksi.php'; 
require_once 'laporan_helper.php';

// Samakan variabel koneksi: pakai $koneksi di file ini & helper
if (!isset($koneksi) && isset($conn)) {
    $koneksi = $conn;
}

// Ambil filter
$jenis_laporan = $_GET['jenis_laporan'] ?? 'harian'; // harian/mingguan/bulanan

// default: hari ini & bulan ini
$tanggal_hari_ini = date('Y-m-d');
$bulan_ini        = date('m');
$tahun_ini        = date('Y');

// input untuk harian
$tanggal_harian = $_GET['tanggal_harian'] ?? $tanggal_hari_ini;

// input untuk mingguan (range tanggal)
$tanggal_mulai_minggu   = $_GET['tanggal_mulai_minggu']   ?? date('Y-m-d', strtotime('monday this week'));
$tanggal_selesai_minggu = $_GET['tanggal_selesai_minggu'] ?? date('Y-m-d', strtotime('sunday this week'));

// input untuk bulanan
$tahun_bulanan = $_GET['tahun_bulanan'] ?? $tahun_ini;
$bulan_bulanan = $_GET['bulan_bulanan'] ?? $bulan_ini;

// === AUTO GENERATE LAPORAN BERDASARKAN JENIS ===
if ($jenis_laporan == 'harian') {
    generate_laporan_harian($tanggal_harian, $koneksi);
    $periode_mulai   = $tanggal_harian;
    $periode_selesai = $tanggal_harian;
    $label_periode   = "Harian";
    $sub_label       = "Tanggal " . $tanggal_harian;
} elseif ($jenis_laporan == 'mingguan') {
    generate_laporan_mingguan($tanggal_mulai_minggu, $tanggal_selesai_minggu, $koneksi);
    $periode_mulai   = $tanggal_mulai_minggu;
    $periode_selesai = $tanggal_selesai_minggu;
    $label_periode   = "Mingguan";
    $sub_label       = "Periode $tanggal_mulai_minggu s/d $tanggal_selesai_minggu";
} else { // bulanan
    generate_laporan_bulanan($tahun_bulanan, $bulan_bulanan, $koneksi);
    $periode_mulai   = "$tahun_bulanan-$bulan_bulanan-01";
    $periode_selesai = date("Y-m-t", strtotime($periode_mulai));
    $label_periode   = "Bulanan";
    $sub_label       = "Bulan " . $bulan_bulanan . "-" . $tahun_bulanan;
}

// ====== TANGGAL DETAIL (DIPILIH DI DALAM RANGE PERIODE) ======
$tanggal_detail = $_GET['tanggal_detail'] ?? $periode_mulai;
// pastikan tidak keluar dari range
if ($tanggal_detail < $periode_mulai)   $tanggal_detail = $periode_mulai;
if ($tanggal_detail > $periode_selesai) $tanggal_detail = $periode_selesai;

// Ambil data dari tabel laporan_penjualan (utama: per outlet untuk tabel ringkasan)
$sql = "
SELECT lp.*, o.nama_outlet
FROM laporan_penjualan lp
JOIN outlet o ON o.id = lp.id_outlet
WHERE lp.jenis_laporan   = ?
  AND lp.periode_mulai   = ?
  AND lp.periode_selesai = ?
ORDER BY o.nama_outlet
";

$stmt = $koneksi->prepare($sql);
if (!$stmt) {
    die("Gagal prepare statement laporan: " . $koneksi->error);
}
$stmt->bind_param("sss", $jenis_laporan, $periode_mulai, $periode_selesai);
$stmt->execute();
$result = $stmt->get_result();

$rows = [];         // simpan semua baris laporan (untuk tabel)
$grand_total = 0;
$total_transaksi_all = 0;

while ($row = $result->fetch_assoc()) {
    $rows[] = $row;
    $grand_total += $row['total_penjualan'];
    $total_transaksi_all += $row['total_transaksi'];
}
$stmt->close();

// ============================================================
// DATA UNTUK CHART
// - Harian   -> 1 dataset, X: outlet (satu bar per outlet)
// - Mingguan -> multi dataset (per outlet), X: hari (Senin-Minggu)
// - Bulanan  -> multi dataset (per outlet), X: minggu (Minggu 1-4)
// ============================================================
$chart_labels   = [];
$chart_datasets = [];
$chart_main_title = '';

if ($jenis_laporan == 'harian') {
    // Untuk harian: bar per outlet pada 1 hari
    $chart_main_title = 'Penjualan Harian per Outlet';

    $sqlH = "
        SELECT lp.id_outlet, o.nama_outlet, lp.total_penjualan
        FROM laporan_penjualan lp
        JOIN outlet o ON o.id = lp.id_outlet
        WHERE lp.jenis_laporan = 'harian'
          AND lp.periode_mulai = ?
        ORDER BY o.nama_outlet
    ";
    $stmtH = $koneksi->prepare($sqlH);
    if (!$stmtH) {
        die("Gagal prepare laporan harian untuk chart: " . $koneksi->error);
    }
    $stmtH->bind_param("s", $tanggal_harian);
    $stmtH->execute();
    $resH = $stmtH->get_result();

    $labels = [];
    $data   = [];

    while ($r = $resH->fetch_assoc()) {
        $labels[] = $r['nama_outlet'];
        $data[]   = (int)$r['total_penjualan'];
    }
    $stmtH->close();

    $chart_labels = $labels;
    $chart_datasets[] = [
        'label' => 'Total Penjualan',
        'data'  => $data,
    ];

} elseif ($jenis_laporan == 'mingguan') {

    $chart_main_title = 'Penjualan Harian per Outlet (Mingguan)';

    // list tanggal & nama hari
    $start = new DateTime($tanggal_mulai_minggu);
    $end   = new DateTime($tanggal_selesai_minggu);
    $end->setTime(0,0,0);

    $periode = new DatePeriod($start, new DateInterval('P1D'), (clone $end)->modify('+1 day'));

    $nama_hari = [
        1 => 'Senin',
        2 => 'Selasa',
        3 => 'Rabu',
        4 => 'Kamis',
        5 => 'Jumat',
        6 => 'Sabtu',
        7 => 'Minggu'
    ];

    $chart_labels = [];
    $dateIndex = [];
    $idx = 0;
    foreach ($periode as $dt) {
        $tgl = $dt->format('Y-m-d');
        $hari_ke = (int)$dt->format('N');
        $chart_labels[] = $nama_hari[$hari_ke];
        $dateIndex[$tgl] = $idx++;
        // pastikan laporan harian sudah tergenerate
        generate_laporan_harian($tgl, $koneksi);
    }

    if (!empty($chart_labels)) {
        $sqlDaily = "
            SELECT lp.id_outlet, o.nama_outlet, lp.periode_mulai AS tanggal, lp.total_penjualan
            FROM laporan_penjualan lp
            JOIN outlet o ON o.id = lp.id_outlet
            WHERE lp.jenis_laporan = 'harian'
              AND lp.periode_mulai BETWEEN ? AND ?
            ORDER BY o.nama_outlet, lp.periode_mulai
        ";
        $stmtDaily = $koneksi->prepare($sqlDaily);
        if (!$stmtDaily) {
            die("Gagal prepare untuk detail harian mingguan: " . $koneksi->error);
        }
        $stmtDaily->bind_param("ss", $tanggal_mulai_minggu, $tanggal_selesai_minggu);
        $stmtDaily->execute();
        $resDaily = $stmtDaily->get_result();

        $outletNames = [];          // id_outlet => nama_outlet
        $dataMatrix  = [];          // id_outlet => [..nilai per hari..]

        while ($r = $resDaily->fetch_assoc()) {
            $id_outlet = $r['id_outlet'];
            $tgl       = $r['tanggal'];
            $total     = (int)$r['total_penjualan'];

            if (!isset($dateIndex[$tgl])) continue;
            $i = $dateIndex[$tgl];

            if (!isset($outletNames[$id_outlet])) {
                $outletNames[$id_outlet] = $r['nama_outlet'];
            }
            if (!isset($dataMatrix[$id_outlet])) {
                $dataMatrix[$id_outlet] = array_fill(0, count($chart_labels), 0);
            }
            $dataMatrix[$id_outlet][$i] += $total;
        }
        $stmtDaily->close();

        foreach ($dataMatrix as $id_outlet => $dataArr) {
            $chart_datasets[] = [
                'label' => $outletNames[$id_outlet] ?? ('Outlet '.$id_outlet),
                'data'  => $dataArr,
            ];
        }
    }

} else { // bulanan

    $chart_main_title = 'Penjualan per Minggu per Outlet (Bulanan)';

    // pastikan laporan harian untuk semua hari di bulan tsb sudah ada
    $start = new DateTime($periode_mulai);
    $end   = new DateTime($periode_selesai);
    $end->setTime(0,0,0);

    $periode = new DatePeriod($start, new DateInterval('P1D'), (clone $end)->modify('+1 day'));
    foreach ($periode as $dt) {
        $tgl = $dt->format('Y-m-d');
        generate_laporan_harian($tgl, $koneksi);
    }

    // label Minggu 1-4
    $chart_labels = ['Minggu 1','Minggu 2','Minggu 3','Minggu 4'];

    $sqlDaily = "
        SELECT lp.id_outlet, o.nama_outlet, lp.periode_mulai AS tanggal, lp.total_penjualan
        FROM laporan_penjualan lp
        JOIN outlet o ON o.id = lp.id_outlet
        WHERE lp.jenis_laporan = 'harian'
          AND lp.periode_mulai BETWEEN ? AND ?
        ORDER BY o.nama_outlet, lp.periode_mulai
    ";
    $stmtDaily = $koneksi->prepare($sqlDaily);
    if (!$stmtDaily) {
        die("Gagal prepare untuk detail mingguan bulanan: " . $koneksi->error);
    }
    $stmtDaily->bind_param("ss", $periode_mulai, $periode_selesai);
    $stmtDaily->execute();
    $resDaily = $stmtDaily->get_result();

    $outletNames = [];                 // id_outlet => nama_outlet
    $dataMatrix  = [];                 // id_outlet => [week1, week2, week3, week4]

    while ($r = $resDaily->fetch_assoc()) {
        $id_outlet = $r['id_outlet'];
        $tgl       = $r['tanggal'];
        $total     = (int)$r['total_penjualan'];

        $day_of_month = (int)date('j', strtotime($tgl)); // 1-31
        $week_index   = (int)ceil($day_of_month / 7);    // 1..5+
        if ($week_index > 4) $week_index = 4;
        $idx = $week_index - 1;                         // 0..3

        if (!isset($outletNames[$id_outlet])) {
            $outletNames[$id_outlet] = $r['nama_outlet'];
        }
        if (!isset($dataMatrix[$id_outlet])) {
            $dataMatrix[$id_outlet] = array_fill(0, 4, 0);
        }
        $dataMatrix[$id_outlet][$idx] += $total;
    }
    $stmtDaily->close();

    foreach ($dataMatrix as $id_outlet => $dataArr) {
        $chart_datasets[] = [
            'label' => $outletNames[$id_outlet] ?? ('Outlet '.$id_outlet),
            'data'  => $dataArr,
        ];
    }
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Penjualan - Owner</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        * { box-sizing:border-box; }

        body {
            margin:0;
            font-family: Arial, sans-serif;
            font-size:14px;
            background: radial-gradient(circle at top left, #fff7e0 0%, #ffe3b3 40%, #ffffff 100%);
        }

        .page-wrapper {
            margin-left:250px;
            margin-top:60px;
            padding:24px 28px;
            min-height:100vh;
        }


        .page-title {
            margin:0 0 4px 0;
            font-size:22px;
            font-weight:700;
            color:#b71c1c;
            display:flex;
            align-items:center;
            gap:8px;
        }

        .page-title span.icon {
            width:24px;
            height:24px;
            border-radius:999px;
            display:inline-flex;
            align-items:center;
            justify-content:center;
            background:linear-gradient(135deg,#ff9800,#d32f2f);
            color:#fff;
            font-size:14px;
        }

        .page-subtitle {
            margin:0 0 18px 0;
            font-size:13px;
            color:#666;
        }

        /* Tiga card vertikal */
        .layout-report {
            display: grid;
            grid-template-columns: 1fr 2fr 1fr; /* Filter kecil, Chart besar, Detail kecil */
            gap: 18px;
            align-items: flex-start;
        }

        .card {
            background:#ffffff;
            border-radius:14px;
            box-shadow:0 6px 18px rgba(0,0,0,0.10);
            padding:16px 18px 18px;
            border:1px solid #ffe0b2;
        }

        .card-header {
            display:flex;
            align-items:center;
            justify-content:space-between;
            margin-bottom:8px;
        }

        .card-title {
            margin:0;
            font-size:15px;
            font-weight:700;
            color:#bf360c;
            display:flex;
            align-items:center;
            gap:6px;
        }

        .card-title::before {
            content:"";
            width:5px;
            height:18px;
            border-radius:999px;
            background:linear-gradient(180deg,#ff9800,#d32f2f);
        }

        .card-subtext {
            font-size:12px;
            color:#888;
            margin-bottom:20px;
        }

        .filter-form label {
            display:block;
            font-size:12px;
            color:#555;
            margin-bottom:4px;
        }

        .filter-form select,
        .filter-form input[type="date"],
        .filter-form input[type="number"] {
            width:100%;
            padding:7px 10px;
            border-radius:10px;
            border:1px solid #ffcc80;
            font-size:13px;
            margin-bottom:10px;
        }

        .filter-form select:focus,
        .filter-form input[type="date"]:focus,
        .filter-form input[type="number"]:focus {
            outline:none;
            border-color:#fb8c00;
            box-shadow:0 0 0 2px rgba(251,140,0,0.18);
        }

        .filter-row {
            display:flex;
            gap:8px;
            flex-wrap:wrap;
        }

        .filter-row > div {
            flex:1;
            min-width:130px;
        }

        .btn-primary {
            display:inline-block;
            border:none;
            border-radius:999px;
            padding:7px 16px;
            font-size:13px;
            font-weight:600;
            color:#fff;
            background:linear-gradient(135deg,#ff9800,#d32f2f);
            cursor:pointer;
            margin-top:4px;
        }
        .btn-primary:hover{
            filter:brightness(0.95);
        }

        .badge-period {
            display:inline-flex;
            align-items:center;
            gap:6px;
            font-size:12px;
            padding:5px 10px;
            border-radius:999px;
            background:#fff3e0;
            border:1px solid #ffcc80;
            color:#bf360c;
            margin-top:8px;
        }

        .badge-period span.dot{
            width:7px;
            height:7px;
            border-radius:50%;
            background:linear-gradient(135deg,#ff9800,#d32f2f);
        }

        .card-section-title {
            font-size:13px;
            font-weight:600;
            color:#555;
            margin:0 0 6px 0;
        }

        .chart-container {
            width:100%;
            max-width:100%;
            height:400px;
        }

        .table-wrapper {
            margin-top:6px;
            border-radius:12px;
            border:1px solid #ffe0b2;
            overflow:hidden;
            background:#ffffff;
            box-shadow:0 4px 12px rgba(0,0,0,0.08);
        }

        .table-scroll {
            max-height:320px;
            overflow-y:auto;
            overflow-x:auto;
        }

        table {
            border-collapse:collapse;
            width:100%;
            font-size:13px;
        }

        thead {
            position:sticky;
            top:0;
            z-index:1;
            background:linear-gradient(90deg,#d32f2f,#ffb300);
            color:#fff;
        }

        th, td {
            padding:8px 10px;
            border-bottom:1px solid #ffe0b2;
            text-align:left;
            white-space:nowrap;
        }

        tbody tr:nth-child(even){
            background:#fffdf7;
        }

        tbody tr:hover{
            background:#fff4e0;
        }

        .text-right { text-align:right; }
        .text-center{ text-align:center; }

        .empty-row td {
            text-align:center;
            font-style:italic;
            color:#999;
        }

        tfoot th {
            background:#fff3e0;
            font-weight:700;
            color:#bf360c;
        }

       @media (max-width: 768px) {

    /* Hilangkan efek sidebar di mobile */
    .page-wrapper {
        margin-left: 0;
        margin-top: 60px;
        padding: 12px 14px;
    }

    .card {
        padding: 12px 14px 14px;
        border-radius: 10px;
    }

    .chart-container {
        height: 220px; /* lebih pendek biar tidak kepanjangan */
    }

    table {
        font-size: 12px;
    }

    th,
    td {
        padding: 6px 8px;
        white-space: normal; /* biar teks boleh turun ke bawah */
    }

    /* Pada layar kecil, stack jadi 1 kolom: 
       urutan mengikuti HTML: Filter -> Chart -> Detail */
    .layout-report {
        grid-template-columns: 1fr;
        gap: 12px; /* jarak antar card */
    }
}

        
    </style>
</head>
<body>
<div class="page-wrapper">
    <h1 class="page-title">
        Laporan Penjualan 
    </h1>
    <p class="page-subtitle">
        Monitor performa penjualan setiap outlet berdasarkan periode <strong><?= htmlspecialchars($label_periode); ?></strong>.
    </p>

    <div class="layout-report">
        <!-- CARD 1: FILTER LAPORAN -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Filter Laporan</h3>
            </div>
            <p class="card-subtext">
                Silahkan memilih jenis laporan dan periode yang ingin dilihat. 
            </p>

            <form method="get" class="filter-form">
                <label>Jenis Laporan</label>
                <select name="jenis_laporan" onchange="this.form.submit()">
                    <option value="harian"   <?= $jenis_laporan=='harian'?'selected':'' ?>>Harian</option>
                    <option value="mingguan" <?= $jenis_laporan=='mingguan'?'selected':'' ?>>Mingguan</option>
                    <option value="bulanan"  <?= $jenis_laporan=='bulanan'?'selected':'' ?>>Bulanan</option>
                </select>

                <hr style="border:none;border-top:1px dashed #ffd180;margin:8px 0 10px 0;">

                <?php if ($jenis_laporan == 'harian'): ?>
                    <div>
                        <label>Tanggal</label>
                        <input type="date" name="tanggal_harian" value="<?= htmlspecialchars($tanggal_harian); ?>">
                    </div>
                <?php elseif ($jenis_laporan == 'mingguan'): ?>
                    <div class="filter-row">
                        <div>
                            <label>Tanggal Mulai</label>
                            <input type="date" name="tanggal_mulai_minggu" value="<?= htmlspecialchars($tanggal_mulai_minggu); ?>">
                        </div>
                        <div>
                            <label>Tanggal Selesai</label>
                            <input type="date" name="tanggal_selesai_minggu" value="<?= htmlspecialchars($tanggal_selesai_minggu); ?>">
                        </div>
                    </div>
                <?php else: ?>
                    <div class="filter-row">
                        <div>
                            <label>Bulan</label>
                            <select name="bulan_bulanan">
                                <?php for($b=1; $b<=12; $b++):
                                    $val = str_pad($b,2,'0',STR_PAD_LEFT); ?>
                                    <option value="<?= $val; ?>" <?= $bulan_bulanan==$val?'selected':'' ?>>
                                        <?= $val; ?>
                                    </option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div>
                            <label>Tahun</label>
                            <input type="number" name="tahun_bulanan"
                                   value="<?= htmlspecialchars($tahun_bulanan); ?>"
                                   style="max-width:100%;">
                        </div>
                    </div>
                <?php endif; ?>

                <div style="margin-top:4px;">
                    <label style="font-size:12px; color:#555; display:block; margin-bottom:4px;">
                        Tanggal dalam Periode
                    </label>
                    <input
                        type="date"
                        name="tanggal_detail"
                        value="<?= htmlspecialchars($tanggal_detail); ?>"
                        min="<?= htmlspecialchars($periode_mulai); ?>"
                        max="<?= htmlspecialchars($periode_selesai); ?>"  >
                </div>

                <button type="submit" class="btn-primary">Tampilkan Laporan</button>
            </form>
        </div>

        <!-- CARD 2: CHART -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Grafik Penjualan</h3>
            </div>
            <p class="card-subtext">
                Perbandingan penjualan antar outlet berdasarkan periode dan jenis laporan yang dipilih (harian/mingguan/bulanan).
            </p>
            <div class="chart-container">
                <canvas id="chartPenjualan"></canvas>
            </div>
        </div>

        <!-- CARD 3: DETAIL PENJUALAN PER OUTLET -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Detail Penjualan </h3>
            </div>
            <p class="card-subtext">
                Ringkasan total transaksi dan total penjualan setiap outlet pada periode yang dipilih.
            </p>
            <div class="table-wrapper">
                <div class="table-scroll">
                    <table>
                        <thead>
                            <tr>
                                <th>Outlet</th>
                                <th class="text-center">Total Transaksi</th>
                                <th class="text-right">Total Penjualan</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if (!empty($rows)): ?>
                            <?php foreach ($rows as $r): ?>
                                <tr>
                                    <td><?= htmlspecialchars($r['nama_outlet']); ?></td>
                                    <td class="text-center"><?= number_format($r['total_transaksi'], 0, ',', '.'); ?></td>
                                    <td class="text-right">Rp <?= number_format($r['total_penjualan'], 0, ',', '.'); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr class="empty-row">
                                <td colspan="3">Tidak ada data laporan untuk periode ini.</td>
                            </tr>
                        <?php endif; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <th>Grand Total</th>
                                <th class="text-center"><?= number_format($total_transaksi_all, 0, ',', '.'); ?> transaksi</th>
                                <th class="text-right">Rp <?= number_format($grand_total, 0, ',', '.'); ?></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

    </div> <!-- /.layout-report -->
</div> <!-- /.page-wrapper -->

<script>
const chartLabels      = <?= json_encode($chart_labels); ?>;
const chartDatasetsRaw = <?= json_encode($chart_datasets); ?>;
const jenisLaporan     = '<?= $jenis_laporan; ?>';

// Palet warna dasar
const baseColors = [
    { bg: 'rgba(244, 67, 54, 0.7)',  border: 'rgba(244, 67, 54, 1)' },   // merah
    { bg: 'rgba(33, 150, 243, 0.7)', border: 'rgba(33, 150, 243, 1)' },  // biru
    { bg: 'rgba(76, 175, 80, 0.7)',  border: 'rgba(76, 175, 80, 1)' },   // hijau
    { bg: 'rgba(255, 193, 7, 0.7)',  border: 'rgba(255, 193, 7, 1)' },   // kuning
    { bg: 'rgba(156, 39, 176, 0.7)', border: 'rgba(156, 39, 176, 1)' },  // ungu
    { bg: 'rgba(0, 188, 212, 0.7)',  border: 'rgba(0, 188, 212, 1)' },   // tosca
    { bg: 'rgba(255, 87, 34, 0.7)',  border: 'rgba(255, 87, 34, 1)' },   // oranye
];

let datasets = [];

// HARIAN: 1 dataset, tiap bar (outlet) beda warna
if (jenisLaporan === 'harian') {
    const ds = chartDatasetsRaw[0] || { label: 'Total Penjualan', data: [] };

    const bgColors = ds.data.map((_, idx) => baseColors[idx % baseColors.length].bg);
    const borderColors = ds.data.map((_, idx) => baseColors[idx % baseColors.length].border);

    datasets.push({
        label: ds.label,
        data: ds.data,
        backgroundColor: bgColors,
        borderColor: borderColors,
        borderWidth: 1,
        borderRadius: 0,
    });

// MINGGUAN/BULANAN: 1 dataset per outlet, warna konsisten
} else {
    datasets = chartDatasetsRaw.map((ds, idx) => {
        const color = baseColors[idx % baseColors.length];
        return {
            label: ds.label,
            data: ds.data,
            backgroundColor: color.bg,
            borderColor: color.border,
            borderWidth: 1,
            borderRadius: 0,
        };
    });
}

const ctx = document.getElementById('chartPenjualan').getContext('2d');
new Chart(ctx, {
    type: 'bar',
    data: {
        labels: chartLabels,
        datasets: datasets
    },
    options: {
        responsive: true,
   plugins: {
    legend: {
        display: datasets.length > 1,  // atau true kalau mau selalu tampil
        position: 'bottom',            // ⬅️ ini yang memindahkan ke bawah
        labels: {
            // optional: kecilkan font biar muat banyak outlet
            font: {
                size: 10
            }
        }
    },
    tooltip: {
        callbacks: {
            label: function(ctx) {
                const label = ctx.dataset.label || '';
                const val   = ctx.parsed.y || 0;
                return (label ? label + ': ' : '') + 'Rp ' + val.toLocaleString('id-ID');
            }
        }
    },
    title: {
        display: true,
        text: 'Laporan Penjualan (<?= ucfirst($jenis_laporan); ?>)'
    }

        },
        scales: {
            x: {
                stacked: false,
                ticks: {
                    autoSkip: false,
                    maxRotation: 0,
                    minRotation: 0
                }
            },
            y: {
                beginAtZero: true,
                ticks: {
                    callback: function(value) {
                        return 'Rp ' + value.toLocaleString('id-ID');
                    }
                }
            }
        }
    }
});
</script>

</body>
</html>
