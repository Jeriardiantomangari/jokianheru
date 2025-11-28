
<?php 
// Ambil nama file PHP saat ini untuk menandai menu aktif
$halaman = basename($_SERVER['PHP_SELF']); 
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dasbor Admin Penjualan Ayam Crispy</title>

  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }

    body {
      display: flex;
      min-height: 100vh;
      background: #f3f3f3;
      overflow-x: hidden;
    }

    .menu-samping {
      width: 260px;
      background: linear-gradient(180deg, #d32f2f, #b71c1c);
      display: flex;
      flex-direction: column;
      padding: 30px 0 20px 0;
      color: #fff;
      position: fixed;
      top: 0; 
      bottom: 0; 
      left: 0;
      z-index: 200;
      box-shadow: 2px 0 14px rgba(0,0,0,.25);
      transition: transform 0.3s ease; 
    }

    .bagian-foto {
      width: 100%;
      display: flex;
      flex-direction: column;
      justify-content: center; 
      align-items: center;
      margin-bottom: 30px;
      text-align: center;
      padding: 0 15px;
    }

    .bagian-foto img {
      width: 100px; 
      height: 100px;
      border-radius: 50%;
      object-fit: cover;
      display: block; 
      margin: 0 auto 10px auto;
      transition: .3s;
      box-shadow: 0 6px 18px rgba(0,0,0,.4);
      border: 1px solid rgba(255, 255, 255, 0.9);
      background: #fff;
    }


    .nama-logo {
      margin-top:5px;
      font-size: 20px;
      font-weight: 700;
      color: #ffffff;
      text-shadow: 0 1px 3px rgba(0,0,0,0.35);
    }

    .daftar-menu {
      display: flex; 
      flex-direction: column;
      width: 100%;
      padding: 0 15px;
      gap: 5px;
    }

    .daftar-menu a {
      display: flex; 
      align-items: center; 
      justify-content: flex-start;
      text-decoration: none;
      color: #ffebee;                   
      font-weight: 500;
      font-size: 17px;               
      padding: 10px 12px;
      border-radius: 10px;
      transition: all .25s ease;
      cursor: pointer;
    }

    .daftar-menu a i {
      margin-right: 10px;
      width: 22px; 
      text-align: center;
      font-size: 17px;
    }

    .daftar-menu a:hover {
      background: rgba(255,255,255,0.18);
      transform: translateX(4px);
    }

    .daftar-menu a.active {
      background: #ffeb3b;
      color: #b71c1c;
      font-weight: 700;
      box-shadow: 0 3px 8px rgba(0,0,0,0.3);
    }

    .daftar-menu a.active i {
      color: #b71c1c;
    }

    .tombol-keluar {
      background: transparent;
      color: #ffcccb;
      border: 1.5px solid #ffcccb;
      padding: 8px 20px;
      border-radius: 20px;
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 17px;
      font-weight: 500;
      margin-top: auto;             
      margin-left: 20px;
      margin-right: 20px;
    }

    .tombol-keluar i {
      margin-right: 8px;
    }

    .tombol-keluar:hover {
      background: #ff5252;
      border-color: #ff5252;
      color: #fff;
      transform: scale(1.04);
    }

    .menu-atas {
      position: fixed;
      top: 0;
      left: 260px;
      right: 0;
      height: 60px;
      background: #ffffff;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.25);
      display: flex;
      justify-content: flex-start;
      align-items: center;
      padding: 0 15px;
      z-index: 250;
      transition: left 0.3s ease;
    }

    .tombol-menu {
      display: none !important;
      font-size: 1.6rem;
      color: #333;
      cursor: pointer;
    }
      .welcome-text {
      font-size: 30px;
      font-weight: 600;
      color: #b71c1c;
    }


    @media (max-width: 768px) {
      body {
      
        overflow-x: hidden;
      }

      .menu-samping {
        transform: translateX(-100%);
        width: 250px;
      }

      .bagian-foto {
        margin-top: 80px;
        margin-bottom: 20px;
      }

      .menu-samping.active {
        transform: translateX(0);
      }
      .menu-atas {
        left: 0;
        justify-content: space-between;
      }

      .tombol-menu {
        display: block !important;
      }
       .welcome-text {
      font-size: 18px;
    }
  }
  </style>
</head>
<body>

  
  <!-- MENU SAMPING -->
  <div class="menu-samping" id="menuSamping">
     <div class="bagian-foto">
      <img src="../../gambar/logo.jpg" alt="Logo Gerai" class="gambar" />
      <div class="nama-logo">
        D Fried Chicken
      </div>
    </div>

    <div class="daftar-menu">


      <!-- MENU 1: PENJUALAN -->
      <a href="../penjualan/penjualan.php" class="<?= $halaman == 'penjualan.php' ? 'active' : '' ?>">
        <i class="fa-solid fa-cash-register"></i> Penjualan
      </a>

        <!-- STOK BARANG -->
      <a href="../stok_barang/stok_barang.php" class="<?= $halaman == 'stok_barang.php' ? 'active' : '' ?>">
        <i class="fa-solid fa-boxes-stacked"></i> Stok Barang
      </a>
      
      <!-- MENU 2: PENGAJUAN RESTOK -->
      <a href="../pengajuan_restok/pengajuan_restok.php" class="<?= $halaman == 'pengajuan_restok.php' ? 'active' : '' ?>">
        <i class="fa-solid fa-cart-arrow-down"></i> Pengajuan Restok
      </a>

    </div>

    <button class="tombol-keluar" id="keluar">
      <i class="fa-solid fa-power-off"></i> Keluar
    </button>
  </div>

  <!-- MENU ATAS -->
  <div class="menu-atas">
    <i class="fa-solid fa-bars tombol-menu" id="tombolMenu"></i>
    <div class="welcome-text">Selamat Datang Kasir</div>
  </div>

  <script>
    const tombolMenu = document.getElementById("tombolMenu");
    const menuSamping = document.getElementById("menuSamping");

    if (tombolMenu) {
      tombolMenu.addEventListener("click", () => {
        menuSamping.classList.toggle("active");
      });
    }

    document.getElementById("keluar").addEventListener("click", () => {
      const yakin = confirm("Apakah Anda yakin ingin keluar ?");
      if (yakin) {
        window.location.href = "../../index.php";
      }
    });
  </script>

</body>
</html>
