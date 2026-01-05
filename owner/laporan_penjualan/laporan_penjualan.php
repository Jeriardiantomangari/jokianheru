<?php
session_start();
include '../../koneksi/sidebarowner.php';
include '../../koneksi/koneksi.php';

if (!isset($koneksi) && isset($conn)) {
    $koneksi = $conn;
}

$jenis_laporan = $_GET['jenis_laporan'] ?? 'harian';

$tanggal_hari_ini = date('Y-m-d');
$bulan_ini        = date('m');
$tahun_ini        = date('Y');

$tanggal_harian = $_GET['tanggal_harian'] ?? $tanggal_hari_ini;

$tanggal_mulai_minggu   = $_GET['tanggal_mulai_minggu']   ?? date('Y-m-d', strtotime('monday this week'));
$tanggal_selesai_minggu = $_GET['tanggal_selesai_minggu'] ?? date('Y-m-d', strtotime('sunday this week'));

$tahun_bulanan = $_GET['tahun_bulanan'] ?? $tahun_ini;
$bulan_bulanan = $_GET['bulan_bulanan'] ?? $bulan_ini;

if ($jenis_laporan == 'harian') {
    $periode_mulai   = $tanggal_harian;
    $periode_selesai = $tanggal_harian;
    $label_periode   = "Harian";
    $sub_label       = "Tanggal " . $tanggal_harian;
} elseif ($jenis_laporan == 'mingguan') {
    $periode_mulai   = $tanggal_mulai_minggu;
    $periode_selesai = $tanggal_selesai_minggu;
    $label_periode   = "Mingguan";
    $sub_label       = "Periode $tanggal_mulai_minggu s/d $tanggal_selesai_minggu";
} else { // bulanan
    $bulan_bulanan = str_pad($bulan_bulanan, 2, '0', STR_PAD_LEFT);
    $periode_mulai   = "$tahun_bulanan-$bulan_bulanan-01";
    $periode_selesai = date("Y-m-t", strtotime($periode_mulai));
    $label_periode   = "Bulanan";
    $sub_label       = "Bulan " . $bulan_bulanan . "-" . $tahun_bulanan;
}

$tanggal_detail = $_GET['tanggal_detail'] ?? $periode_mulai;
if ($tanggal_detail < $periode_mulai)   $tanggal_detail = $periode_mulai;
if ($tanggal_detail > $periode_selesai) $tanggal_detail = $periode_selesai;

$sqlSummary = "
    SELECT 
        o.id_outlet,
        o.nama_outlet,
        COUNT(p.id_penjualan) AS total_transaksi,
        COALESCE(SUM(p.total_harga), 0) AS total_penjualan
    FROM outlet o
    LEFT JOIN penjualan p 
        ON p.id_outlet = o.id_outlet
       AND DATE(p.tanggal) BETWEEN ? AND ?
    GROUP BY o.id_outlet, o.nama_outlet
    ORDER BY o.nama_outlet
";

$stmt = $koneksi->prepare($sqlSummary);
if (!$stmt) {
    die("Gagal prepare summary: " . $koneksi->error);
}
$stmt->bind_param("ss", $periode_mulai, $periode_selesai);
$stmt->execute();
$result = $stmt->get_result();

$rows = [];
$grand_total = 0;
$total_transaksi_all = 0;

while ($row = $result->fetch_assoc()) {
    $row['total_transaksi'] = (int)$row['total_transaksi'];
    $row['total_penjualan'] = (int)$row['total_penjualan'];

    $rows[] = $row;
    $grand_total += $row['total_penjualan'];
    $total_transaksi_all += $row['total_transaksi'];
}
$stmt->close();

$chart_labels   = [];
$chart_datasets = [];
$chart_main_title = '';

if ($jenis_laporan == 'harian') {
    $chart_main_title = 'Penjualan Harian per Outlet';

    $sqlH = "
        SELECT 
            o.nama_outlet,
            COALESCE(SUM(p.total_harga), 0) AS total_penjualan
        FROM outlet o
        LEFT JOIN penjualan p 
            ON p.id_outlet = o.id_outlet
           AND DATE(p.tanggal) = ?
        GROUP BY o.id_outlet, o.nama_outlet
        ORDER BY o.nama_outlet
    ";
    $stmtH = $koneksi->prepare($sqlH);
    if (!$stmtH) {
        die("Gagal prepare chart harian: " . $koneksi->error);
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
    }

    $sqlDaily = "
        SELECT 
            p.id_outlet, 
            o.nama_outlet, 
            DATE(p.tanggal) AS tanggal, 
            SUM(p.total_harga) AS total_penjualan
        FROM penjualan p
        JOIN outlet o ON o.id_outlet = p.id_outlet
        WHERE DATE(p.tanggal) BETWEEN ? AND ?
        GROUP BY p.id_outlet, o.nama_outlet, DATE(p.tanggal)
        ORDER BY o.nama_outlet, DATE(p.tanggal)
    ";
    $stmtDaily = $koneksi->prepare($sqlDaily);
    if (!$stmtDaily) {
        die("Gagal prepare chart mingguan: " . $koneksi->error);
    }
    $stmtDaily->bind_param("ss", $tanggal_mulai_minggu, $tanggal_selesai_minggu);
    $stmtDaily->execute();
    $resDaily = $stmtDaily->get_result();

    $outletNames = []; // id_outlet => nama_outlet
    $dataMatrix  = []; // id_outlet => [..nilai per hari..]

    while ($r = $resDaily->fetch_assoc()) {
        $id_outlet = (int)$r['id_outlet'];
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

} else { // bulanan

    $chart_main_title = 'Penjualan per Minggu per Outlet (Bulanan)';
    $chart_labels = ['Minggu 1','Minggu 2','Minggu 3','Minggu 4'];

    $sqlMonthly = "
        SELECT
            p.id_outlet,
            o.nama_outlet,
            CASE
                WHEN DAY(p.tanggal) BETWEEN 1 AND 7  THEN 1
                WHEN DAY(p.tanggal) BETWEEN 8 AND 14 THEN 2
                WHEN DAY(p.tanggal) BETWEEN 15 AND 21 THEN 3
                ELSE 4
            END AS minggu_ke,
            SUM(p.total_harga) AS total_penjualan
        FROM penjualan p
        JOIN outlet o ON o.id_outlet = p.id_outlet
        WHERE DATE(p.tanggal) BETWEEN ? AND ?
        GROUP BY p.id_outlet, o.nama_outlet, minggu_ke
        ORDER BY o.nama_outlet, minggu_ke
    ";

    $stmtM = $koneksi->prepare($sqlMonthly);
    if (!$stmtM) {
        die("Gagal prepare chart bulanan: " . $koneksi->error);
    }
    $stmtM->bind_param("ss", $periode_mulai, $periode_selesai);
    $stmtM->execute();
    $resM = $stmtM->get_result();

    $outletNames = []; // id_outlet => nama_outlet
    $dataMatrix  = []; // id_outlet => [week1..week4]

    while ($r = $resM->fetch_assoc()) {
        $id_outlet = (int)$r['id_outlet'];
        $minggu_ke = (int)$r['minggu_ke']; // 1..4
        $total     = (int)$r['total_penjualan'];

        if (!isset($outletNames[$id_outlet])) {
            $outletNames[$id_outlet] = $r['nama_outlet'];
        }
        if (!isset($dataMatrix[$id_outlet])) {
            $dataMatrix[$id_outlet] = array_fill(0, 4, 0);
        }
        $dataMatrix[$id_outlet][$minggu_ke - 1] += $total;
    }
    $stmtM->close();

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

        .pembungkus-halaman {
            margin-left:250px;
            margin-top:60px;
            padding:24px 28px;
            min-height:100vh;
        }

        .judul-halaman {
            margin:0 0 4px 0;
            font-size:22px;
            font-weight:700;
            color:#b71c1c;
            display:flex;
            align-items:center;
            gap:8px;
        }

        .subjudul-halaman {
            margin:0 0 18px 0;
            font-size:13px;
            color:#666;
        }

        .tata-letak-laporan {
            display: grid;
            grid-template-columns: 1fr 2fr 1fr;
            gap: 18px;
            align-items: flex-start;
        }

        .kartu {
            background:#ffffff;
            border-radius:14px;
            box-shadow:0 6px 18px rgba(0,0,0,0.10);
            padding:16px 18px 18px;
            border:1px solid #ffe0b2;
        }

        .kepala-kartu {
            display:flex;
            align-items:center;
            justify-content:space-between;
            margin-bottom:8px;
        }

        .judul-kartu {
            margin:0;
            font-size:15px;
            font-weight:700;
            color:#bf360c;
            display:flex;
            align-items:center;
            gap:6px;
        }

        .judul-kartu::before {
            content:"";
            width:5px;
            height:18px;
            border-radius:999px;
            background:linear-gradient(180deg,#ff9800,#d32f2f);
        }

        .teks-subkartu {
            font-size:12px;
            color:#888;
            margin-bottom:20px;
        }

        .form-filter label {
            display:block;
            font-size:12px;
            color:#555;
            margin-bottom:4px;
        }

        .form-filter select,
        .form-filter input[type="date"],
        .form-filter input[type="number"] {
            width:100%;
            padding:7px 10px;
            border-radius:10px;
            border:1px solid #ffcc80;
            font-size:13px;
            margin-bottom:10px;
        }

        .form-filter select:focus,
        .form-filter input[type="date"]:focus,
        .form-filter input[type="number"]:focus {
            outline:none;
            border-color:#fb8c00;
            box-shadow:0 0 0 2px rgba(251,140,0,0.18);
        }

        .baris-filter {
            display:flex;
            gap:8px;
            flex-wrap:wrap;
        }

        .baris-filter > div {
            flex:1;
            min-width:130px;
        }

        .tombol-utama {
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
        .tombol-utama:hover{
            filter:brightness(0.95);
        }

        .wadah-grafik {
            width:100%;
            max-width:100%;
            height:400px;
        }

        .pembungkus-tabel {
            margin-top:6px;
            border-radius:12px;
            border:1px solid #ffe0b2;
            overflow:hidden;
            background:#ffffff;
            box-shadow:0 4px 12px rgba(0,0,0,0.08);
        }

        .gulir-tabel {
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

        .teks-kanan { text-align:right; }
        .teks-tengah{ text-align:center; }

        .baris-kosong td {
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
            .pembungkus-halaman {
                margin-left: 0;
                margin-top: 60px;
                padding: 12px 14px;
            }

            .kartu {
                padding: 12px 14px 14px;
                border-radius: 10px;
            }

            .wadah-grafik {
                height: 220px;
            }

            table {
                font-size: 12px;
            }

            th, td {
                padding: 6px 8px;
                white-space: normal;
            }

            .tata-letak-laporan {
                grid-template-columns: 1fr;
                gap: 12px;
            }
        }
    </style>
</head>
<body>
<div class="pembungkus-halaman">
    <h1 class="judul-halaman">Laporan Penjualan</h1>
    <p class="subjudul-halaman">
        Monitor performa penjualan setiap outlet berdasarkan periode <strong><?= htmlspecialchars($label_periode); ?></strong>.
        <span style="color:#999;">(<?= htmlspecialchars($sub_label); ?>)</span>
    </p>

    <div class="tata-letak-laporan">
        <div class="kartu">
            <div class="kepala-kartu">
                <h3 class="judul-kartu">Filter Laporan</h3>
            </div>
            <p class="teks-subkartu">
                Silahkan memilih jenis laporan dan periode yang ingin dilihat.
            </p>

            <form method="get" class="form-filter">
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
                    <div class="baris-filter">
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
                    <div class="baris-filter">
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
                        max="<?= htmlspecialchars($periode_selesai); ?>">
                </div>

                <button type="submit" class="tombol-utama">Tampilkan Laporan</button>
            </form>
        </div>

        <div class="kartu">
            <div class="kepala-kartu">
                <h3 class="judul-kartu">Grafik Penjualan</h3>
            </div>
            <p class="teks-subkartu">
                Perbandingan penjualan antar outlet berdasarkan periode dan jenis laporan yang dipilih (harian/mingguan/bulanan).
            </p>
            <div class="wadah-grafik">
                <canvas id="chartPenjualan"></canvas>
            </div>
        </div>

        <div class="kartu">
            <div class="kepala-kartu">
                <h3 class="judul-kartu">Detail Penjualan</h3>
            </div>
            <p class="teks-subkartu">
                Ringkasan total transaksi dan total penjualan setiap outlet pada periode yang dipilih.
            </p>
            <div class="pembungkus-tabel">
                <div class="gulir-tabel">
                    <table>
                        <thead>
                            <tr>
                                <th>Outlet</th>
                                <th class="teks-tengah">Total Transaksi</th>
                                <th class="teks-kanan">Total Penjualan</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if (!empty($rows)): ?>
                            <?php foreach ($rows as $r): ?>
                                <tr>
                                    <td><?= htmlspecialchars($r['nama_outlet']); ?></td>
                                    <td class="teks-tengah"><?= number_format($r['total_transaksi'], 0, ',', '.'); ?></td>
                                    <td class="teks-kanan">Rp <?= number_format($r['total_penjualan'], 0, ',', '.'); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr class="baris-kosong">
                                <td colspan="3">Tidak ada data laporan untuk periode ini.</td>
                            </tr>
                        <?php endif; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <th>Grand Total</th>
                                <th class="teks-tengah"><?= number_format($total_transaksi_all, 0, ',', '.'); ?> transaksi</th>
                                <th class="teks-kanan">Rp <?= number_format($grand_total, 0, ',', '.'); ?></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

    </div>
</div>

<script>
const chartLabels      = <?= json_encode($chart_labels); ?>;
const chartDatasetsRaw = <?= json_encode($chart_datasets); ?>;
const jenisLaporan     = '<?= $jenis_laporan; ?>';

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
    data: { labels: chartLabels, datasets: datasets },
    options: {
        responsive: true,
        plugins: {
            legend: {
                display: datasets.length > 1,
                position: 'bottom',
                labels: { font: { size: 10 } }
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
                ticks: { autoSkip: false, maxRotation: 0, minRotation: 0 }
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
