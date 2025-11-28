<?php
session_start();
include '../../koneksi/koneksi.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'kasir') {
    echo "Akses ditolak.";
    exit;
}

if (!isset($_SESSION['id_outlet'])) {
    echo "Outlet tidak ditemukan di session.";
    exit;
}

$id_outlet = (int)$_SESSION['id_outlet'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (isset($_POST['aksi']) && $_POST['aksi'] === 'ambil') {
        $id = (int)$_POST['id'];

        $q = mysqli_query($conn,"
          SELECT * FROM ajukan_stok_outlet
          WHERE id = $id AND id_outlet = $id_outlet
        ");

        if (!$row = mysqli_fetch_assoc($q)) {
            echo json_encode(['error' => 'Data tidak ditemukan atau bukan milik outlet ini.']);
            exit;
        }

        echo json_encode($row);
        exit;
    }

    if (isset($_POST['aksi']) && $_POST['aksi'] === 'hapus') {
        $id = (int)$_POST['id'];

        $cek = mysqli_query($conn,"
          SELECT status FROM ajukan_stok_outlet
          WHERE id = $id AND id_outlet = $id_outlet
        ");

        if (!$row = mysqli_fetch_assoc($cek)) {
            echo "Data tidak ditemukan atau bukan milik outlet ini.";
            exit;
        }

        if ($row['status'] === 'Dikirim') {
            echo "Pengajuan tidak bisa dihapus karena status masih Dikirim.";
            exit;
        }

        mysqli_query($conn,"DELETE FROM ajukan_stok_outlet WHERE id = $id AND id_outlet = $id_outlet");
        if (mysqli_affected_rows($conn) > 0) {
            echo "Pengajuan berhasil dihapus.";
        } else {
            echo "Gagal menghapus pengajuan.";
        }
        exit;
    }

    if (isset($_POST['aksi']) && $_POST['aksi'] === 'selesai') {
        $id = (int)$_POST['id'];

        $q = mysqli_query($conn,"
          SELECT *
          FROM ajukan_stok_outlet
          WHERE id = $id AND id_outlet = $id_outlet
          LIMIT 1
        ");
        if (!$row = mysqli_fetch_assoc($q)) {
            echo "Pengajuan tidak ditemukan atau bukan milik outlet ini.";
            exit;
        }

        if ($row['status'] !== 'Dikirim') {
            echo "Pengajuan ini tidak dalam status Dikirim (status sekarang: ".$row['status'].").";
            exit;
        }

        $id_barang = (int)$row['id_barang'];
        $jumlah    = (int)$row['jumlah_restok'];

        mysqli_begin_transaction($conn);
        try {
            $upd1 = mysqli_query($conn,"
              UPDATE ajukan_stok_outlet
              SET status = 'Selesai'
              WHERE id = $id AND id_outlet = $id_outlet
            ");
            if (!$upd1) {
                throw new Exception("Gagal mengupdate status ke Selesai.");
            }

            $qStok = mysqli_query($conn,"
              SELECT id, stok
              FROM stok_outlet
              WHERE id_outlet = $id_outlet AND id_barang = $id_barang
              LIMIT 1
            ");

            if ($so = mysqli_fetch_assoc($qStok)) {
                $id_stok = (int)$so['id'];
                $upd2 = mysqli_query($conn,"
                  UPDATE stok_outlet
                  SET stok = stok + $jumlah
                  WHERE id = $id_stok
                ");
                if (!$upd2) {
                    throw new Exception("Gagal menambah stok outlet (update).");
                }
            } else {
                $ins2 = mysqli_query($conn,"
                  INSERT INTO stok_outlet (id_outlet, id_barang, stok)
                  VALUES ($id_outlet, $id_barang, $jumlah)
                ");
                if (!$ins2) {
                    throw new Exception("Gagal menambah stok outlet (insert).");
                }
            }

            mysqli_commit($conn);
            echo "Stok outlet berhasil ditambah dan status pengajuan menjadi Selesai.";

        } catch (Exception $e) {
            mysqli_rollback($conn);
            echo "Terjadi kesalahan: ".$e->getMessage();
        }
        exit;
    }

    $id           = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $id_barang    = (int)$_POST['id_barang'];
    $nama_barang  = mysqli_real_escape_string($conn, $_POST['nama_barang']);
    $harga        = (int)$_POST['harga'];
    $jumlah       = (int)$_POST['jumlah_restok'];
    $total_harga  = (int)$_POST['total_harga'];

    if ($jumlah <= 0) {
      echo "Jumlah restok harus lebih dari 0.";
      exit;
    }

    if ($id <= 0) {
        $sql = "
          INSERT INTO ajukan_stok_outlet
          (id_outlet, id_barang, nama_barang, harga, jumlah_restok, total_harga, status)
          VALUES
          ($id_outlet, $id_barang, '$nama_barang', $harga, $jumlah, $total_harga, 'Menunggu')
        ";
        mysqli_query($conn, $sql);

        if (mysqli_affected_rows($conn) > 0) {
            echo "Pengajuan restok berhasil dikirim ke gudang.";
        } else {
            echo "Gagal menyimpan pengajuan restok.";
        }
    } else {
        $cek = mysqli_query($conn,"
          SELECT status FROM ajukan_stok_outlet
          WHERE id = $id AND id_outlet = $id_outlet
        ");
        if (!$row = mysqli_fetch_assoc($cek)) {
            echo "Data tidak ditemukan atau bukan milik outlet ini.";
            exit;
        }
        if ($row['status'] !== 'Menunggu' && $row['status'] !== 'Ditolak') {
            echo "Pengajuan tidak bisa diubah karena status sudah ".$row['status'];
            exit;
        }

        $sql = "
          UPDATE ajukan_stok_outlet
          SET 
            id_barang    = $id_barang,
            nama_barang  = '$nama_barang',
            harga        = $harga,
            jumlah_restok= $jumlah,
            total_harga  = $total_harga
          WHERE id = $id AND id_outlet = $id_outlet
        ";
        mysqli_query($conn, $sql);

        if (mysqli_affected_rows($conn) >= 0) {
            echo "Pengajuan restok berhasil diubah.";
        } else {
            echo "Gagal mengubah pengajuan restok.";
        }
    }
}
