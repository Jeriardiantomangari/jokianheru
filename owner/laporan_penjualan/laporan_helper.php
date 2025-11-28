<?php
// laporan_helper.php

function generate_laporan_harian($tanggal, $koneksi) {
    $sql = "
    INSERT INTO laporan_penjualan (
        jenis_laporan, periode_mulai, periode_selesai,
        id_outlet, total_transaksi, total_penjualan
    )
    SELECT
        'harian',
        ?, ?,
        p.id_outlet,
        COUNT(*),
        SUM(p.total_harga)
    FROM penjualan p
    WHERE DATE(p.tanggal) = ?
    GROUP BY p.id_outlet
    ON DUPLICATE KEY UPDATE
        total_transaksi = VALUES(total_transaksi),
        total_penjualan = VALUES(total_penjualan),
        updated_at = NOW();
    ";

    $stmt = $koneksi->prepare($sql);
    if (!$stmt) {
        die("Gagal prepare laporan harian: " . $koneksi->error);
    }
    $stmt->bind_param("sss", $tanggal, $tanggal, $tanggal);
    $stmt->execute();
    $stmt->close();
}

function generate_laporan_bulanan($tahun, $bulan, $koneksi) {
    // contoh: tahun=2025, bulan=11
    $periode_mulai   = "$tahun-$bulan-01";
    $periode_selesai = date("Y-m-t", strtotime($periode_mulai)); // hari terakhir bulan tsb
    $ym = "$tahun-$bulan";

    $sql = "
    INSERT INTO laporan_penjualan (
        jenis_laporan, periode_mulai, periode_selesai,
        id_outlet, total_transaksi, total_penjualan
    )
    SELECT
        'bulanan',
        ?, ?,
        p.id_outlet,
        COUNT(*),
        SUM(p.total_harga)
    FROM penjualan p
    WHERE DATE_FORMAT(p.tanggal, '%Y-%m') = ?
    GROUP BY p.id_outlet
    ON DUPLICATE KEY UPDATE
        total_transaksi = VALUES(total_transaksi),
        total_penjualan = VALUES(total_penjualan),
        updated_at = NOW();
    ";

    $stmt = $koneksi->prepare($sql);
    if (!$stmt) {
        die("Gagal prepare laporan bulanan: " . $koneksi->error);
    }
    $stmt->bind_param("sss", $periode_mulai, $periode_selesai, $ym);
    $stmt->execute();
    $stmt->close();
}

// contoh sederhana untuk mingguan: berdasarkan range tanggal
function generate_laporan_mingguan($tanggal_mulai, $tanggal_selesai, $koneksi) {
    $sql = "
    INSERT INTO laporan_penjualan (
        jenis_laporan, periode_mulai, periode_selesai,
        id_outlet, total_transaksi, total_penjualan
    )
    SELECT
        'mingguan',
        ?, ?,
        p.id_outlet,
        COUNT(*),
        SUM(p.total_harga)
    FROM penjualan p
    WHERE DATE(p.tanggal) BETWEEN ? AND ?
    GROUP BY p.id_outlet
    ON DUPLICATE KEY UPDATE
        total_transaksi = VALUES(total_transaksi),
        total_penjualan = VALUES(total_penjualan),
        updated_at = NOW();
    ";

    $stmt = $koneksi->prepare($sql);
    if (!$stmt) {
        die("Gagal prepare laporan mingguan: " . $koneksi->error);
    }
    $stmt->bind_param("ssss", $tanggal_mulai, $tanggal_selesai, $tanggal_mulai, $tanggal_selesai);
    $stmt->execute();
    $stmt->close();
}
