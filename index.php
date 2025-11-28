<?php
session_start();
include 'koneksi/koneksi.php';

$error    = '';
$username = '';
$role_in  = ''; // 🔹 supaya nggak undefined waktu pertama kali load halaman

// Jika form disubmit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['role'], $_POST['username'], $_POST['password'])) {
    // Normalisasi input
    $role_in  = strtolower(trim($_POST['role']));
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    // Ambil data dari tabel pengguna
    // 🔹 sekarang sekalian ambil id_outlet (kalau dipakai kasir)
    // struktur: id, nama, username, password, role, id_outlet
    $sql  = "SELECT id, nama, username, password, role, id_outlet
             FROM pengguna
             WHERE username = ? AND role = ? 
             LIMIT 1";
    $stmt = mysqli_prepare($conn, $sql);

    if ($stmt === false) {
        // Kalau prepare gagal
        $error = 'Terjadi kesalahan pada server (prepare gagal).';
    } else {
        mysqli_stmt_bind_param($stmt, "ss", $username, $role_in);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);

        if ($res && $u = mysqli_fetch_assoc($res)) {

            // VERSI SAAT INI: password masih plain TEXT
            $password_match = ($password === $u['password']);

            // 🔹 Jika nanti sudah pakai password_hash:
            // $password_match = password_verify($password, $u['password']);

            if ($password_match) {
                // Set sesi umum
                $_SESSION['login']    = true;
                $_SESSION['role']     = $u['role'];        // dari DB
                $_SESSION['id_user']  = (int)$u['id'];
                $_SESSION['nama']     = $u['nama'];
                $_SESSION['username'] = $u['username'];

                // 🔹 simpan id_outlet kalau ada (untuk kasir/outlet)
                $_SESSION['id_outlet'] = isset($u['id_outlet']) ? (int)$u['id_outlet'] : null;

                // Redirect sesuai role
                if ($role_in === 'owner') {
                    header("Location: owner/konfirmasi_restok/konfirmasi_restok.php");
                    exit;
                } elseif ($role_in === 'admin') {
                    header("Location: admin/pengguna/pengguna.php");
                    exit;
                } elseif ($role_in === 'gudang') {
                    header("Location: gudang/stok_barang/stok_barang.php");
                    exit;
                } elseif ($role_in === 'kasir') {
                    // 🔹 kalau nanti mau diarahkan ke stok outlet atau pengajuan restok:
                    // header("Location: kasir/stok_outlet/kasir_stok_outlet.php");
                    header("Location: kasir/stok_barang/stok_barang.php");
                    exit;
                } else {
                    $error = 'Role tidak dikenali dalam sistem.';
                }

            } else {
                $error = 'Username atau password salah!';
            }
        } else {
            $error = 'Username, password, atau role salah!';
        }

        mysqli_stmt_close($stmt);
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Login</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />

  <style>
    * {
      box-sizing: border-box;
      font-family: Inter, system-ui, Arial;
    }

    body {
      margin: 0;
      background-size: cover;
      font-family: Arial, sans-serif;
    }

    .wadah {
      display: flex;
      justify-content: center;
      align-items: center;
      min-height: 100vh;
    }

    .kartu {
      background: #ffffff;
      width: 400px;
      padding: 24px;
      margin: 20px;
      border-radius: 14px;
      color: #000000;
      box-shadow: 0 6px 18px rgba(0, 0, 0, 0.15);
    }

    .gambar {
      width: 50%;
      max-width: 250px;
      height: auto;
      display: block;
      margin: 0 auto 20px;
      object-fit: contain;
    }

    .formulir-masuk label {
      display: block;
      font-size: 13px;
      margin-top: 6px;
    }

    .formulir-masuk input {
      width: 100%;
      outline: none;
      padding: 10px;
      border-radius: 8px;
      border: 1px solid black;
      margin-top: 6px;
    }

    .input-sandi {
      position: relative;
    }

    .input-sandi input {
      padding-right: 40px;
    }

    .tombol-lihat-sandi {
      position: absolute;
      right: 12px;
      top: 50%;
      transform: translateY(-50%);
      cursor: pointer;
      font-size: 18px;
      color: #333;
      opacity: 0.8;
    }

    .pilihan-peran {
      margin-top: 10px;
      font-size: 14px;
    }

    .kelompok-radio {
      display: flex;
      gap: 10px;
      flex-wrap: wrap;
    }

    .kelompok-radio label {
      display: flex;
      align-items: center;
      gap: 5px;
      cursor: pointer;
      font-size: 14px;
    }

    .kelompok-radio input[type="radio"] {
      accent-color: #4c6ef5;
      width: 16px;
      height: 16px;
      cursor: pointer;
    }

    .tombol-masuk {
      width: 100%;
      padding: 10px;
      border-radius: 6px;
      border: none;
      margin-top: 14px;
      background: #fff;
      color: blue;
      font-weight: 700;
      cursor: pointer;
      border: 1px solid black;
    }

    .lupa-sandi {
      font-size: 14px;
      text-align: right;
      margin-top: 8px;
      cursor: pointer;
      text-decoration: none;
      display: block;
    }

    .lapisan-modal {
      position: fixed;
      inset: 0;
      background: rgba(0, 0, 0, 0.5);
      display: none;
      align-items: center;
      justify-content: center;
      z-index: 1000;
    }

    .kotak-modal {
      background: #fff;
      padding: 20px;
      border-radius: 10px;
      width: 280px;
      text-align: center;
      box-shadow: 0 6px 18px rgba(0, 0, 0, 0.3);
    }

    .kotak-modal h2 {
      color: #4c6ef5;
      margin-bottom: 10px;
    }

    .kotak-modal p {
      color: #333;
      font-size: 14px;
    }

    .kotak-modal button {
      margin-top: 15px;
      padding: 8px 16px;
      background: #4c6ef5;
      color: #ffffff;
      border: none;
      border-radius: 6px;
      cursor: pointer;
    }

    .alert-error {
      background: #ffe3e3;
      color: #c92a2a;
      border: 1px solid #faa2a2;
      padding: 10px;
      border-radius: 8px;
      margin-bottom: 12px;
      font-size: 14px;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    @media screen and (max-width: 768px) {
      .lupa-sandi {
        margin-top: 20px;
      }
    }
  </style>
</head>

<body>
  <div class="wadah">
    <div class="kartu">
      <img src="gambar/logo.jpg" alt="login" class="gambar" />

      <?php if (!empty($error)): ?>
        <div class="alert-error">
          <i class="fa-solid fa-circle-exclamation"></i>
          <span><?= htmlspecialchars($error) ?></span>
        </div>
      <?php endif; ?>

      <form action="" method="POST" class="formulir-masuk">
        <label>
          <i style="margin-right: 7px; font-size: 15px;" class="fa-solid fa-user"></i> Username
        </label>
        <input
          type="text"
          name="username"
          placeholder="Masukkan username"
          required
          value="<?= htmlspecialchars($username) ?>" />

        <label>
          <i style="margin-right: 2px; font-size: 15px;" class="fa-solid fa-key"></i> Password
        </label>
        <div class="input-sandi">
          <input type="password" id="sandi" name="password" placeholder="Masukkan password" required />
          <i id="tombolLihatSandi" class="fa-solid fa-eye tombol-lihat-sandi"></i>
        </div>

        <div class="pilihan-peran">
          <label><i class="fa-solid fa-user-gear" style="margin-right: 3px;"></i> Login sebagai:</label>
          <div class="kelompok-radio">
            <label>
              <input type="radio" name="role" value="owner" required
                <?= ($role_in === 'owner') ? 'checked' : ''; ?>> Owner
            </label>
            <label>
              <input type="radio" name="role" value="admin" required
                <?= ($role_in === 'admin') ? 'checked' : ''; ?>> Admin
            </label>
            <label>
              <input type="radio" name="role" value="gudang" required
                <?= ($role_in === 'gudang') ? 'checked' : ''; ?>> Gudang
            </label>
            <label>
              <input type="radio" name="role" value="kasir" required
                <?= ($role_in === 'kasir') ? 'checked' : ''; ?>> Kasir
            </label>
          </div>
        </div>

        <button type="submit" class="tombol-masuk">Masuk</button>
      </form>

      <div class="lupa-sandi" id="lupaSandi">Lupa Sandi?</div>
    </div>
  </div>

  <!-- Modal -->
  <div class="lapisan-modal" id="lapisanModal">
    <div class="kotak-modal">
      <h2>Informasi</h2>
      <p>Silakan hubungi admin untuk konfirmasi terkait masalah Anda.</p>
      <button id="tutupModal">Tutup</button>
    </div>
  </div>

  <script>
    const tombolLihatSandi = document.getElementById("tombolLihatSandi");
    const inputSandi = document.getElementById("sandi");
    const lupaSandi = document.getElementById("lupaSandi");
    const lapisanModal = document.getElementById("lapisanModal");
    const tutupModal = document.getElementById("tutupModal");

    if (tombolLihatSandi && inputSandi) {
      tombolLihatSandi.addEventListener("click", () => {
        inputSandi.type = inputSandi.type === "password" ? "text" : "password";
        tombolLihatSandi.classList.toggle("fa-eye");
        tombolLihatSandi.classList.toggle("fa-eye-slash");
      });
    }

    if (lupaSandi)  lupaSandi.addEventListener("click", () => lapisanModal.style.display = "flex");
    if (tutupModal) tutupModal.addEventListener("click", () => lapisanModal.style.display = "none");
  </script>
</body>
</html>
