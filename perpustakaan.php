<?php
// ============================================================
// KONFIGURASI DATABASE
// Sesuaikan dengan pengaturan MySQL kamu
// ============================================================
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');       // isi password MySQL kamu
define('DB_NAME', 'db_perpustakaan');

function koneksi() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        die("<p style='color:red'>Koneksi gagal: " . $conn->connect_error . "</p>");
    }
    $conn->set_charset("utf8");
    return $conn;
}

// ============================================================
// PROSES FORM (tambah / edit / hapus)
// ============================================================
$pesan = "";

// --- PETUGAS ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $aksi  = $_POST['aksi']  ?? '';
    $modul = $_POST['modul'] ?? '';
    $conn  = koneksi();

    if ($modul === 'petugas') {
        if ($aksi === 'tambah') {
            $nama     = trim($_POST['nama']);
            $username = trim($_POST['username']);
            $password = trim($_POST['password']);
            $telepon  = trim($_POST['telepon']);
            $stmt = $conn->prepare("INSERT INTO petugas (nama, username, password, no_telepon) VALUES (?,?,?,?)");
            $stmt->bind_param("ssss", $nama, $username, $password, $telepon);
            $stmt->execute();
            $pesan = "<div class='alert ok'>Petugas berhasil ditambahkan.</div>";

        } elseif ($aksi === 'edit') {
            $id       = (int)$_POST['id'];
            $nama     = trim($_POST['nama']);
            $username = trim($_POST['username']);
            $telepon  = trim($_POST['telepon']);
            $stmt = $conn->prepare("UPDATE petugas SET nama=?, username=?, no_telepon=? WHERE id_petugas=?");
            $stmt->bind_param("sssi", $nama, $username, $telepon, $id);
            $stmt->execute();
            $pesan = "<div class='alert ok'>Data petugas berhasil diperbarui.</div>";

        } elseif ($aksi === 'hapus') {
            $id   = (int)$_POST['id'];
            $stmt = $conn->prepare("DELETE FROM petugas WHERE id_petugas=?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $pesan = "<div class='alert ok'>Petugas berhasil dihapus.</div>";
        }
    }

    if ($modul === 'anggota') {
        if ($aksi === 'tambah') {
            $nama    = trim($_POST['nama']);
            $alamat  = trim($_POST['alamat']);
            $telepon = trim($_POST['telepon']);
            $tgl     = trim($_POST['tanggal_daftar']);
            $id_p    = (int)$_POST['id_petugas'];
            $stmt = $conn->prepare("INSERT INTO anggota (nama, alamat, no_telepon, tanggal_daftar, id_petugas) VALUES (?,?,?,?,?)");
            $stmt->bind_param("ssssi", $nama, $alamat, $telepon, $tgl, $id_p);
            $stmt->execute();
            $pesan = "<div class='alert ok'>Anggota berhasil ditambahkan.</div>";

        } elseif ($aksi === 'edit') {
            $id      = (int)$_POST['id'];
            $nama    = trim($_POST['nama']);
            $alamat  = trim($_POST['alamat']);
            $telepon = trim($_POST['telepon']);
            $stmt = $conn->prepare("UPDATE anggota SET nama=?, alamat=?, no_telepon=? WHERE id_anggota=?");
            $stmt->bind_param("sssi", $nama, $alamat, $telepon, $id);
            $stmt->execute();
            $pesan = "<div class='alert ok'>Data anggota berhasil diperbarui.</div>";

        } elseif ($aksi === 'hapus') {
            $id   = (int)$_POST['id'];
            $stmt = $conn->prepare("DELETE FROM anggota WHERE id_anggota=?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $pesan = "<div class='alert ok'>Anggota berhasil dihapus.</div>";
        }
    }
    $conn->close();
}

// ============================================================
// AMBIL DATA UNTUK DITAMPILKAN
// ============================================================
$conn       = koneksi();
$petugas    = $conn->query("SELECT * FROM petugas ORDER BY id_petugas");
$anggota    = $conn->query("SELECT a.*, p.nama AS nama_petugas FROM anggota a LEFT JOIN petugas p ON a.id_petugas = p.id_petugas ORDER BY a.id_anggota");
$opt_petugas= $conn->query("SELECT id_petugas, nama FROM petugas ORDER BY id_petugas");
$conn->close();

// Halaman aktif (tab)
$tab = $_GET['tab'] ?? 'petugas';
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Sistem Informasi Perpustakaan</title>
<style>
  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
  body { font-family: 'Segoe UI', sans-serif; background: #f0f2f5; color: #333; font-size: 15px; }

  header { background: #2c5f8a; color: #fff; padding: 18px 32px; }
  header h1 { font-size: 20px; font-weight: 600; }
  header p  { font-size: 13px; opacity: .75; margin-top: 2px; }

  .container { max-width: 1000px; margin: 28px auto; padding: 0 16px; }

  .tabs { display: flex; gap: 8px; margin-bottom: 20px; }
  .tabs a {
    padding: 9px 22px; border-radius: 6px; text-decoration: none;
    font-weight: 500; font-size: 14px; background: #fff;
    color: #555; border: 1px solid #ddd; transition: all .15s;
  }
  .tabs a.aktif { background: #2c5f8a; color: #fff; border-color: #2c5f8a; }
  .tabs a:hover:not(.aktif) { background: #e8f0f8; }

  .card { background: #fff; border-radius: 10px; box-shadow: 0 1px 4px rgba(0,0,0,.08); padding: 24px; margin-bottom: 24px; }
  .card h2 { font-size: 16px; font-weight: 600; margin-bottom: 16px; color: #2c5f8a; border-bottom: 2px solid #e8f0f8; padding-bottom: 10px; }

  .form-row { display: flex; gap: 12px; flex-wrap: wrap; margin-bottom: 12px; }
  .form-row label { display: flex; flex-direction: column; gap: 4px; font-size: 13px; color: #555; flex: 1; min-width: 160px; }
  .form-row input, .form-row select {
    padding: 8px 10px; border: 1px solid #ccc; border-radius: 6px;
    font-size: 14px; width: 100%; transition: border .15s;
  }
  .form-row input:focus, .form-row select:focus { border-color: #2c5f8a; outline: none; }

  .btn { padding: 8px 18px; border: none; border-radius: 6px; cursor: pointer; font-size: 14px; font-weight: 500; }
  .btn-biru   { background: #2c5f8a; color: #fff; }
  .btn-biru:hover { background: #245079; }
  .btn-kuning { background: #e8a020; color: #fff; }
  .btn-kuning:hover { background: #c8881a; }
  .btn-merah  { background: #d9534f; color: #fff; }
  .btn-merah:hover { background: #b8403c; }

  table { width: 100%; border-collapse: collapse; font-size: 14px; }
  th { background: #f5f7fa; color: #555; font-weight: 600; text-align: left; padding: 10px 12px; border-bottom: 2px solid #e0e4ea; }
  td { padding: 9px 12px; border-bottom: 1px solid #f0f2f5; vertical-align: middle; }
  tr:hover td { background: #fafbfc; }
  .aksi-grup { display: flex; gap: 6px; }

  .alert { padding: 10px 16px; border-radius: 6px; margin-bottom: 16px; font-size: 14px; }
  .alert.ok { background: #e6f4ea; color: #2d7a3e; border-left: 4px solid #34a853; }

  /* Modal */
  .modal-bg { display: none; position: fixed; inset: 0; background: rgba(0,0,0,.45); z-index: 100; align-items: center; justify-content: center; }
  .modal-bg.buka { display: flex; }
  .modal { background: #fff; border-radius: 10px; padding: 28px; width: 100%; max-width: 480px; box-shadow: 0 8px 32px rgba(0,0,0,.18); }
  .modal h3 { font-size: 16px; font-weight: 600; color: #2c5f8a; margin-bottom: 18px; }
  .modal .form-row { margin-bottom: 10px; }
  .modal-footer { display: flex; justify-content: flex-end; gap: 10px; margin-top: 18px; }
  .btn-abu { background: #ddd; color: #444; }
  .btn-abu:hover { background: #ccc; }
</style>
</head>
<body>

<header>
  <h1>Sistem Informasi Perpustakaan</h1>
  <p>Manajemen data petugas dan anggota perpustakaan</p>
</header>

<div class="container">
  <?= $pesan ?>

  <div class="tabs">
    <a href="?tab=petugas" class="<?= $tab==='petugas' ? 'aktif' : '' ?>">Petugas</a>
    <a href="?tab=anggota" class="<?= $tab==='anggota' ? 'aktif' : '' ?>">Anggota</a>
  </div>

  <!-- ======================================================
       TAB PETUGAS
  ====================================================== -->
  <?php if ($tab === 'petugas'): ?>
  <div class="card">
    <h2>Tambah Petugas</h2>
    <form method="POST">
      <input type="hidden" name="aksi"  value="tambah">
      <input type="hidden" name="modul" value="petugas">
      <div class="form-row">
        <label>Nama Lengkap <input type="text" name="nama" placeholder="contoh: Flo Raras Fathin" required></label>
        <label>Username     <input type="text" name="username" placeholder="contoh: flo" required></label>
      </div>
      <div class="form-row">
        <label>Password     <input type="password" name="password" required></label>
        <label>No. Telepon  <input type="text" name="telepon" placeholder="08xxxxxxxxxx"></label>
      </div>
      <button type="submit" class="btn btn-biru">Simpan Petugas</button>
    </form>
  </div>

  <div class="card">
    <h2>Daftar Petugas</h2>
    <table>
      <thead>
        <tr><th>ID</th><th>Nama</th><th>Username</th><th>No. Telepon</th><th>Aksi</th></tr>
      </thead>
      <tbody>
      <?php while ($row = $petugas->fetch_assoc()): ?>
        <tr>
          <td><?= $row['id_petugas'] ?></td>
          <td><?= htmlspecialchars($row['nama']) ?></td>
          <td><?= htmlspecialchars($row['username']) ?></td>
          <td><?= htmlspecialchars($row['no_telepon'] ?? '-') ?></td>
          <td>
            <div class="aksi-grup">
              <button class="btn btn-kuning"
                onclick="bukaEditPetugas(<?= $row['id_petugas'] ?>,'<?= addslashes($row['nama']) ?>','<?= addslashes($row['username']) ?>','<?= addslashes($row['no_telepon'] ?? '') ?>')">
                Edit
              </button>
              <form method="POST" onsubmit="return confirm('Hapus petugas ini?')">
                <input type="hidden" name="aksi"  value="hapus">
                <input type="hidden" name="modul" value="petugas">
                <input type="hidden" name="id"    value="<?= $row['id_petugas'] ?>">
                <button type="submit" class="btn btn-merah">Hapus</button>
              </form>
            </div>
          </td>
        </tr>
      <?php endwhile; ?>
      </tbody>
    </table>
  </div>

  <!-- Modal Edit Petugas -->
  <div class="modal-bg" id="modalEditPetugas">
    <div class="modal">
      <h3>Edit Data Petugas</h3>
      <form method="POST">
        <input type="hidden" name="aksi"  value="edit">
        <input type="hidden" name="modul" value="petugas">
        <input type="hidden" name="id"    id="edit_p_id">
        <div class="form-row">
          <label>Nama Lengkap <input type="text" name="nama" id="edit_p_nama" required></label>
        </div>
        <div class="form-row">
          <label>Username     <input type="text" name="username" id="edit_p_username" required></label>
          <label>No. Telepon  <input type="text" name="telepon"  id="edit_p_telepon"></label>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-abu" onclick="tutupModal('modalEditPetugas')">Batal</button>
          <button type="submit" class="btn btn-biru">Simpan</button>
        </div>
      </form>
    </div>
  </div>

  <!-- ======================================================
       TAB ANGGOTA
  ====================================================== -->
  <?php elseif ($tab === 'anggota'): ?>
  <div class="card">
    <h2>Tambah Anggota</h2>
    <form method="POST">
      <input type="hidden" name="aksi"  value="tambah">
      <input type="hidden" name="modul" value="anggota">
      <div class="form-row">
        <label>Nama Lengkap <input type="text" name="nama" placeholder="contoh: Putri Olivia" required></label>
        <label>No. Telepon  <input type="text" name="telepon" placeholder="08xxxxxxxxxx"></label>
      </div>
      <div class="form-row">
        <label style="flex:2">Alamat <input type="text" name="alamat" placeholder="contoh: Jl. Raya Darmo No. 12, Surabaya"></label>
      </div>
      <div class="form-row">
        <label>Tanggal Daftar <input type="date" name="tanggal_daftar" value="2026-06-06" required></label>
        <label>Didaftarkan Oleh
          <select name="id_petugas" required>
            <option value="">-- Pilih Petugas --</option>
            <?php while ($p = $opt_petugas->fetch_assoc()): ?>
            <option value="<?= $p['id_petugas'] ?>"><?= htmlspecialchars($p['nama']) ?></option>
            <?php endwhile; ?>
          </select>
        </label>
      </div>
      <button type="submit" class="btn btn-biru">Simpan Anggota</button>
    </form>
  </div>

  <div class="card">
    <h2>Daftar Anggota</h2>
    <table>
      <thead>
        <tr><th>ID</th><th>Nama</th><th>Alamat</th><th>Telepon</th><th>Tgl Daftar</th><th>Petugas</th><th>Aksi</th></tr>
      </thead>
      <tbody>
      <?php while ($row = $anggota->fetch_assoc()): ?>
        <tr>
          <td><?= $row['id_anggota'] ?></td>
          <td><?= htmlspecialchars($row['nama']) ?></td>
          <td><?= htmlspecialchars($row['alamat'] ?? '-') ?></td>
          <td><?= htmlspecialchars($row['no_telepon'] ?? '-') ?></td>
          <td><?= $row['tanggal_daftar'] ?></td>
          <td><?= htmlspecialchars($row['nama_petugas'] ?? '-') ?></td>
          <td>
            <div class="aksi-grup">
              <button class="btn btn-kuning"
                onclick="bukaEditAnggota(<?= $row['id_anggota'] ?>,'<?= addslashes($row['nama']) ?>','<?= addslashes($row['alamat'] ?? '') ?>','<?= addslashes($row['no_telepon'] ?? '') ?>')">
                Edit
              </button>
              <form method="POST" onsubmit="return confirm('Hapus anggota ini?')">
                <input type="hidden" name="aksi"  value="hapus">
                <input type="hidden" name="modul" value="anggota">
                <input type="hidden" name="id"    value="<?= $row['id_anggota'] ?>">
                <button type="submit" class="btn btn-merah">Hapus</button>
              </form>
            </div>
          </td>
        </tr>
      <?php endwhile; ?>
      </tbody>
    </table>
  </div>

  <!-- Modal Edit Anggota -->
  <div class="modal-bg" id="modalEditAnggota">
    <div class="modal">
      <h3>Edit Data Anggota</h3>
      <form method="POST">
        <input type="hidden" name="aksi"  value="edit">
        <input type="hidden" name="modul" value="anggota">
        <input type="hidden" name="id"    id="edit_a_id">
        <div class="form-row">
          <label>Nama Lengkap <input type="text" name="nama" id="edit_a_nama" required></label>
          <label>No. Telepon  <input type="text" name="telepon" id="edit_a_telepon"></label>
        </div>
        <div class="form-row">
          <label style="flex:1">Alamat <input type="text" name="alamat" id="edit_a_alamat"></label>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-abu" onclick="tutupModal('modalEditAnggota')">Batal</button>
          <button type="submit" class="btn btn-biru">Simpan</button>
        </div>
      </form>
    </div>
  </div>
  <?php endif; ?>

</div><!-- /container -->

<script>
function bukaEditPetugas(id, nama, username, telepon) {
  document.getElementById('edit_p_id').value       = id;
  document.getElementById('edit_p_nama').value     = nama;
  document.getElementById('edit_p_username').value = username;
  document.getElementById('edit_p_telepon').value  = telepon;
  document.getElementById('modalEditPetugas').classList.add('buka');
}
function bukaEditAnggota(id, nama, alamat, telepon) {
  document.getElementById('edit_a_id').value      = id;
  document.getElementById('edit_a_nama').value    = nama;
  document.getElementById('edit_a_alamat').value  = alamat;
  document.getElementById('edit_a_telepon').value = telepon;
  document.getElementById('modalEditAnggota').classList.add('buka');
}
function tutupModal(id) {
  document.getElementById(id).classList.remove('buka');
}
// Klik di luar modal = tutup
document.querySelectorAll('.modal-bg').forEach(function(el) {
  el.addEventListener('click', function(e) {
    if (e.target === el) tutupModal(el.id);
  });
});
</script>
</body>
</html>
