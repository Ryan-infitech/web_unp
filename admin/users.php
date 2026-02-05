<?php
session_start();
include '../config/database.php';

// Cek apakah admin sudah login
if(!isset($_SESSION['admin_id'])){
  header("Location: login.php");
  exit;
}

$id = $_SESSION['admin_id'];
$q = mysqli_query($conn, "SELECT * FROM admin WHERE id=$id");
$a = mysqli_fetch_assoc($q);

if(!$a){
  die("Admin tidak ditemukan");
}

$message = '';
$message_type = '';

// Handle DELETE
if(isset($_POST['delete'])){
  $user_id = intval($_POST['user_id']);
  $user_type = mysqli_real_escape_string($conn, $_POST['user_type']);
  
  if($user_type === 'mahasiswa'){
    $del = mysqli_query($conn, "DELETE FROM users WHERE id=$user_id");
  } else {
    $del = mysqli_query($conn, "DELETE FROM admin WHERE id=$user_id");
  }
  
  if($del){
    $message = "User berhasil dihapus!";
    $message_type = "success";
  } else {
    $message = "Gagal menghapus user!";
    $message_type = "danger";
  }
}

// Handle ADD/EDIT ADMIN
if(isset($_POST['save_admin'])){
  $nama = mysqli_real_escape_string($conn, $_POST['nama']);
  $username = mysqli_real_escape_string($conn, $_POST['username']);
  $password = $_POST['password'];
  $admin_id = isset($_POST['admin_id']) ? intval($_POST['admin_id']) : 0;
  
  if(empty($username)){
    $message = "Username tidak boleh kosong!";
    $message_type = "danger";
  } else {
    if($admin_id > 0){
      // Edit admin
      if(!empty($password)){
        // Update dengan password baru
        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
        $update = mysqli_query($conn, "UPDATE admin SET nama='$nama', username='$username', password='$hashedPassword' WHERE id=$admin_id");
      } else {
        // Update tanpa password
        $update = mysqli_query($conn, "UPDATE admin SET nama='$nama', username='$username' WHERE id=$admin_id");
      }
      if($update){
        $message = "Admin berhasil diperbarui!";
        $message_type = "success";
      } else {
        $message = "Gagal memperbarui admin!";
        $message_type = "danger";
      }
    } else {
      // Add new admin
      if(empty($password)){
        $message = "Password tidak boleh kosong untuk admin baru!";
        $message_type = "danger";
      } else {
        $check = mysqli_query($conn, "SELECT id FROM admin WHERE username='$username'");
        if(mysqli_num_rows($check) > 0){
          $message = "Username sudah digunakan!";
          $message_type = "danger";
        } else {
          $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
          $insert = mysqli_query($conn, "INSERT INTO admin (nama, username, password) VALUES ('$nama', '$username', '$hashedPassword')");
          if($insert){
            $message = "Admin berhasil ditambahkan!";
            $message_type = "success";
          } else {
            $message = "Gagal menambahkan admin!";
            $message_type = "danger";
          }
        }
      }
    }
  }
}

// Handle ADD/EDIT MAHASISWA
if(isset($_POST['save_mahasiswa'])){
  $nama = mysqli_real_escape_string($conn, $_POST['nama']);
  $email = mysqli_real_escape_string($conn, $_POST['email']);
  $nim = mysqli_real_escape_string($conn, $_POST['nim']);
  $password = $_POST['password'];
  $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
  
  if(empty($nama) || empty($email) || empty($nim)){
    $message = "Nama, Email, dan NIM tidak boleh kosong!";
    $message_type = "danger";
  } else {
    if($user_id > 0){
      // Edit mahasiswa
      if(!empty($password)){
        // Update dengan password baru
        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
        $update = mysqli_query($conn, "UPDATE users SET nama='$nama', email='$email', nim='$nim', password='$hashedPassword' WHERE id=$user_id");
      } else {
        // Update tanpa password
        $update = mysqli_query($conn, "UPDATE users SET nama='$nama', email='$email', nim='$nim' WHERE id=$user_id");
      }
      if($update){
        $message = "Mahasiswa berhasil diperbarui!";
        $message_type = "success";
      } else {
        $message = "Gagal memperbarui mahasiswa!";
        $message_type = "danger";
      }
    } else {
      // Add new mahasiswa
      if(empty($password)){
        $message = "Password tidak boleh kosong untuk mahasiswa baru!";
        $message_type = "danger";
      } else {
        $check = mysqli_query($conn, "SELECT id FROM users WHERE email='$email' OR nim='$nim'");
        if(mysqli_num_rows($check) > 0){
          $message = "Email atau NIM sudah terdaftar!";
          $message_type = "danger";
        } else {
          $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
          $insert = mysqli_query($conn, "INSERT INTO users (nama, email, nim, password, role, is_active) VALUES ('$nama', '$email', '$nim', '$hashedPassword', 'mahasiswa', 1)");
          if($insert){
            $message = "Mahasiswa berhasil ditambahkan!";
            $message_type = "success";
          } else {
            $message = "Gagal menambahkan mahasiswa!";
            $message_type = "danger";
          }
        }
      }
    }
  }
}

// Handle TOGGLE STATUS MAHASISWA
if(isset($_POST['toggle_status'])){
  $user_id = intval($_POST['user_id']);
  $current_status = intval($_POST['current_status']);
  $new_status = $current_status ? 0 : 1;
  
  $update = mysqli_query($conn, "UPDATE users SET is_active=$new_status WHERE id=$user_id");
  if($update){
    echo json_encode(['status' => 'success', 'new_status' => $new_status]);
    exit;
  } else {
    echo json_encode(['status' => 'error']);
    exit;
  }
}

?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Kelola Users</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    :root {
      --primary: #f59e0b;
      --primary-light: #fbbf24;
      --primary-dark: #d97706;
      --secondary: #fb923c;
      --success: #22c55e;
      --danger: #ef4444;
      --warning: #eab308;
      --bg-primary: #ffffff;
      --bg-secondary: #f8fafc;
      --text-primary: #0f172a;
      --text-secondary: #64748b;
      --border-color: #e2e8f0;
      --shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    }

    body.dark-mode {
      --primary: #fbbf24;
      --primary-light: #fcd34d;
      --primary-dark: #f59e0b;
      --secondary: #fb923c;
      --success: #22c55e;
      --danger: #ef4444;
      --warning: #eab308;
      --bg-primary: #1e293b;
      --bg-secondary: #0f172a;
      --text-primary: #f1f5f9;
      --text-secondary: #cbd5e1;
      --border-color: #475569;
      --shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
    }

    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      background: linear-gradient(135deg, #f59e0b15 0%, #fb923c15 100%);
      color: var(--text-primary);
    }

    .main-content {
      padding: 40px 30px;
      min-height: 100vh;
      margin-left: 250px;
      transition: margin-left 0.3s ease;
    }

    /* Header Section */
    .page-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 30px;
      gap: 20px;
      flex-wrap: wrap;
    }

    .page-header h1 {
      font-size: 28px;
      font-weight: 700;
      color: var(--text-primary);
      display: flex;
      align-items: center;
      gap: 12px;
    }

    .page-header h1 i {
      color: var(--primary);
      font-size: 32px;
    }

    .header-actions {
      display: flex;
      gap: 12px;
      flex-wrap: wrap;
    }

    .btn-add {
      background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
      color: white;
      border: none;
      padding: 10px 20px;
      border-radius: 8px;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.3s;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .btn-add:hover {
      transform: translateY(-2px);
      box-shadow: 0 6px 20px rgba(245, 158, 11, 0.3);
    }

    /* Search and Filter */
    .search-filter-section {
      background: var(--bg-primary);
      padding: 20px;
      border-radius: 12px;
      margin-bottom: 30px;
      box-shadow: var(--shadow);
      display: flex;
      gap: 15px;
      flex-wrap: wrap;
      align-items: flex-end;
    }

    .search-box, .filter-box {
      flex: 1;
      min-width: 200px;
    }

    .search-box label, .filter-box label {
      display: block;
      margin-bottom: 8px;
      font-weight: 600;
      color: var(--text-primary);
      font-size: 14px;
    }

    .search-box input, .filter-box select {
      width: 100%;
      padding: 10px 15px;
      border: 2px solid var(--border-color);
      border-radius: 8px;
      font-size: 14px;
      transition: all 0.3s;
      background: var(--bg-secondary);
      color: var(--text-primary);
    }

    .search-box input:focus, .filter-box select:focus {
      outline: none;
      border-color: var(--primary);
      box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.1);
    }

    /* Alert */
    .alert-custom {
      padding: 15px 20px;
      border-radius: 10px;
      margin-bottom: 20px;
      display: flex;
      align-items: center;
      gap: 12px;
      animation: slideInDown 0.3s ease;
    }

    .alert-success {
      background: rgba(34, 197, 94, 0.1);
      border: 2px solid var(--success);
      color: var(--success);
    }

    .alert-danger {
      background: rgba(239, 68, 68, 0.1);
      border: 2px solid var(--danger);
      color: var(--danger);
    }

    .alert-custom i {
      font-size: 18px;
    }

    /* Users List */
    .users-list {
      display: grid;
      gap: 20px;
      grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
    }

    .user-card {
      background: var(--bg-primary);
      border-radius: 12px;
      padding: 20px;
      box-shadow: var(--shadow);
      transition: all 0.3s;
      border: 2px solid transparent;
      display: flex;
      flex-direction: column;
      gap: 15px;
    }

    .user-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
      border-color: var(--primary);
    }

    .user-header {
      display: flex;
      justify-content: space-between;
      align-items: start;
      gap: 10px;
    }

    .user-badges {
      display: flex;
      gap: 8px;
      flex-wrap: wrap;
    }

    .user-badge {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      padding: 6px 12px;
      border-radius: 20px;
      font-size: 12px;
      font-weight: 600;
    }

    .badge-admin {
      background: rgba(251, 146, 60, 0.15);
      color: var(--secondary);
    }

    .badge-mahasiswa {
      background: rgba(100, 116, 139, 0.15);
      color: var(--text-secondary);
    }

    .badge-active {
      background: rgba(34, 197, 94, 0.15);
      color: var(--success);
    }

    .badge-inactive {
      background: rgba(239, 68, 68, 0.15);
      color: var(--danger);
    }

    .user-name {
      font-size: 16px;
      font-weight: 700;
      color: var(--text-primary);
    }

    .user-details {
      display: flex;
      flex-direction: column;
      gap: 8px;
    }

    .user-detail-item {
      display: flex;
      align-items: center;
      gap: 8px;
      font-size: 13px;
      color: var(--text-secondary);
    }

    .user-detail-item i {
      width: 16px;
      color: var(--text-secondary);
    }

    .user-actions {
      display: flex;
      gap: 10px;
      margin-top: auto;
    }

    .btn-action {
      flex: 1;
      padding: 10px 12px;
      border: none;
      border-radius: 8px;
      font-size: 13px;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.3s;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 6px;
    }

    .btn-edit {
      background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
      color: white;
    }

    .btn-edit:hover {
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(245, 158, 11, 0.3);
    }

    .btn-delete {
      background: rgba(239, 68, 68, 0.1);
      color: var(--danger);
      border: 2px solid var(--danger);
    }

    .btn-delete:hover {
      background: var(--danger);
      color: white;
      transform: translateY(-2px);
    }

    .btn-toggle {
      background: rgba(34, 197, 94, 0.1);
      color: var(--success);
      border: 2px solid var(--success);
    }

    .btn-toggle:hover {
      background: var(--success);
      color: white;
    }

    .btn-toggle.inactive {
      background: rgba(239, 68, 68, 0.1);
      color: var(--danger);
      border-color: var(--danger);
    }

    .empty-state {
      grid-column: 1 / -1;
      text-align: center;
      padding: 60px 20px;
      color: var(--text-secondary);
    }

    .empty-state i {
      font-size: 48px;
      margin-bottom: 12px;
      opacity: 0.5;
    }

    .empty-state p {
      font-size: 16px;
      margin: 0;
    }

    /* Modal */
    .modal-overlay {
      display: none;
      position: fixed;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background: rgba(0, 0, 0, 0.5);
      z-index: 1000;
      align-items: center;
      justify-content: center;
    }

    .modal-overlay.active {
      display: flex;
    }

    .modal-content {
      background: var(--bg-primary);
      border-radius: 12px;
      padding: 30px;
      max-width: 500px;
      width: 90%;
      max-height: 90vh;
      overflow-y: auto;
      box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
      animation: slideInUp 0.3s ease;
    }

    .modal-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 20px;
      border-bottom: 2px solid var(--border-color);
      padding-bottom: 15px;
    }

    .modal-header h2 {
      font-size: 20px;
      font-weight: 700;
      color: var(--text-primary);
      display: flex;
      align-items: center;
      gap: 10px;
      margin: 0;
    }

    .modal-header h2 i {
      color: var(--primary);
    }

    .modal-close {
      background: none;
      border: none;
      font-size: 24px;
      cursor: pointer;
      color: var(--text-secondary);
      transition: all 0.3s;
    }

    .modal-close:hover {
      color: var(--primary);
      transform: rotate(90deg);
    }

    .form-group {
      margin-bottom: 18px;
    }

    .form-group label {
      display: block;
      margin-bottom: 8px;
      font-weight: 600;
      color: var(--text-primary);
      font-size: 14px;
    }

    .form-group input, .form-group select {
      width: 100%;
      padding: 12px 15px;
      border: 2px solid var(--border-color);
      border-radius: 8px;
      font-size: 14px;
      transition: all 0.3s;
      background: var(--bg-secondary);
      color: var(--text-primary);
    }

    .form-group input:focus, .form-group select:focus {
      outline: none;
      border-color: var(--primary);
      box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.1);
    }

    .modal-footer {
      display: flex;
      gap: 10px;
      margin-top: 25px;
    }

    .btn-modal {
      flex: 1;
      padding: 12px 20px;
      border: none;
      border-radius: 8px;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.3s;
      font-size: 14px;
    }

    .btn-save {
      background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
      color: white;
    }

    .btn-save:hover {
      transform: translateY(-2px);
      box-shadow: 0 6px 20px rgba(245, 158, 11, 0.3);
    }

    .btn-cancel {
      background: var(--bg-secondary);
      color: var(--text-primary);
      border: 2px solid var(--border-color);
    }

    .btn-cancel:hover {
      background: var(--border-color);
    }

    @keyframes slideInDown {
      from {
        opacity: 0;
        transform: translateY(-20px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    @keyframes slideInUp {
      from {
        opacity: 0;
        transform: translateY(20px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    @media (max-width: 768px) {
      .main-content {
        margin-left: 70px;
        padding: 20px 15px;
      }

      .page-header {
        flex-direction: column;
        align-items: flex-start;
      }

      .search-filter-section {
        flex-direction: column;
      }

      .users-list {
        grid-template-columns: 1fr;
      }

      .modal-content {
        width: 95%;
      }
    }

    body.sidebar-collapsed .main-content {
      margin-left: 70px;
    }
  </style>
</head>
<body>

<?php include '../assets/admin_sidebar.php'; ?>

<div class="main-content">
  <!-- Alert Messages -->
  <?php if(!empty($message)): ?>
    <div class="alert-custom alert-<?php echo $message_type; ?>">
      <i class="fas fa-<?php echo $message_type === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
      <span><?php echo $message; ?></span>
    </div>
  <?php endif; ?>

  <!-- Header -->
  <div class="page-header">
    <h1>
      <i class="fas fa-users-cog"></i>
      Kelola Users
    </h1>
    <div class="header-actions">
      <a href="aktivasi.php" class="btn-add" style="text-decoration: none; color: white;">
        <i class="fas fa-check-circle"></i> Aktivasi Akun
      </a>
      <button class="btn-add" onclick="openModalAddAdmin()">
        <i class="fas fa-user-tie"></i> Tambah Admin
      </button>
      <button class="btn-add" onclick="openModalAddMahasiswa()">
        <i class="fas fa-user-graduate"></i> Tambah Mahasiswa
      </button>
    </div>
  </div>

  <!-- Search & Filter -->
  <div class="search-filter-section">
    <div class="search-box">
      <label><i class="fas fa-search"></i> Cari User</label>
      <input type="text" id="searchInput" placeholder="Nama, Email, NIM, atau Username...">
    </div>
    <div class="filter-box">
      <label><i class="fas fa-filter"></i> Filter</label>
      <select id="filterInput">
        <option value="">Semua User</option>
        <option value="admin">Admin</option>
        <option value="mahasiswa">Mahasiswa</option>
      </select>
    </div>
  </div>

  <!-- Users List -->
  <div class="users-list" id="usersList">
    <div class="empty-state">
      <i class="fas fa-spinner fa-spin"></i>
      <p>Memuat data...</p>
    </div>
  </div>
</div>

<!-- Modal Add Admin -->
<div class="modal-overlay" id="modalAddAdmin">
  <div class="modal-content">
    <div class="modal-header">
      <h2><i class="fas fa-user-tie"></i> Tambah Admin Baru</h2>
      <button class="modal-close" onclick="closeModalAddAdmin()">×</button>
    </div>
    <form method="POST">
      <input type="hidden" name="save_admin">
      <div class="form-group">
        <label><i class="fas fa-user"></i> Nama</label>
        <input type="text" name="nama" placeholder="Masukkan nama..." required>
      </div>
      <div class="form-group">
        <label><i class="fas fa-user-tag"></i> Username</label>
        <input type="text" name="username" placeholder="Masukkan username..." required>
      </div>
      <div class="form-group">
        <label><i class="fas fa-lock"></i> Password</label>
        <input type="password" name="password" placeholder="Masukkan password..." required>
      </div>
      <div class="modal-footer">
        <button type="submit" class="btn-modal btn-save">
          <i class="fas fa-save"></i> Simpan
        </button>
        <button type="button" class="btn-modal btn-cancel" onclick="closeModalAddAdmin()">
          <i class="fas fa-times"></i> Batal
        </button>
      </div>
    </form>
  </div>
</div>

<!-- Modal Edit Admin -->
<div class="modal-overlay" id="modalEditAdmin">
  <div class="modal-content">
    <div class="modal-header">
      <h2><i class="fas fa-user-tie"></i> Edit Admin</h2>
      <button class="modal-close" onclick="closeModalEditAdmin()">×</button>
    </div>
    <form method="POST" onsubmit="return confirmAdminSave()">
      <input type="hidden" name="save_admin">
      <input type="hidden" name="admin_id" id="editAdminId">
      <div class="form-group">
        <label><i class="fas fa-user"></i> Nama</label>
        <input type="text" name="nama" id="editAdminNama" placeholder="Masukkan nama..." required>
      </div>
      <div class="form-group">
        <label><i class="fas fa-user-tag"></i> Username</label>
        <input type="text" name="username" id="editAdminUsername" placeholder="Masukkan username..." required>
      </div>
      <div class="form-group">
        <label><i class="fas fa-lock"></i> Password (Kosongkan jika tidak ingin mengubah)</label>
        <input type="password" name="password" id="editAdminPassword" placeholder="Masukkan password baru...">
      </div>
      <div class="modal-footer">
        <button type="submit" class="btn-modal btn-save">
          <i class="fas fa-save"></i> Simpan
        </button>
        <button type="button" class="btn-modal btn-cancel" onclick="closeModalEditAdmin()">
          <i class="fas fa-times"></i> Batal
        </button>
      </div>
    </form>
  </div>
</div>

<!-- Modal Add Mahasiswa -->
<div class="modal-overlay" id="modalAddMahasiswa">
  <div class="modal-content">
    <div class="modal-header">
      <h2><i class="fas fa-user-graduate"></i> Tambah Mahasiswa Baru</h2>
      <button class="modal-close" onclick="closeModalAddMahasiswa()">×</button>
    </div>
    <form method="POST">
      <input type="hidden" name="save_mahasiswa">
      <div class="form-group">
        <label><i class="fas fa-user"></i> Nama</label>
        <input type="text" name="nama" placeholder="Masukkan nama..." required>
      </div>
      <div class="form-group">
        <label><i class="fas fa-envelope"></i> Email</label>
        <input type="email" name="email" placeholder="Masukkan email..." required>
      </div>
      <div class="form-group">
        <label><i class="fas fa-id-card"></i> NIM</label>
        <input type="text" name="nim" placeholder="Masukkan NIM..." required>
      </div>
      <div class="form-group">
        <label><i class="fas fa-lock"></i> Password</label>
        <input type="password" name="password" placeholder="Masukkan password..." required>
      </div>
      <div class="modal-footer">
        <button type="submit" class="btn-modal btn-save">
          <i class="fas fa-save"></i> Simpan
        </button>
        <button type="button" class="btn-modal btn-cancel" onclick="closeModalAddMahasiswa()">
          <i class="fas fa-times"></i> Batal
        </button>
      </div>
    </form>
  </div>
</div>

<!-- Modal Edit Mahasiswa -->
<div class="modal-overlay" id="modalEditMahasiswa">
  <div class="modal-content">
    <div class="modal-header">
      <h2><i class="fas fa-user-graduate"></i> Edit Mahasiswa</h2>
      <button class="modal-close" onclick="closeModalEditMahasiswa()">×</button>
    </div>
    <form method="POST" onsubmit="return confirmMahasiswaSave()">
      <input type="hidden" name="save_mahasiswa">
      <input type="hidden" name="user_id" id="editMahasiswaId">
      <div class="form-group">
        <label><i class="fas fa-user"></i> Nama</label>
        <input type="text" name="nama" id="editMahasiswaNama" placeholder="Masukkan nama..." required>
      </div>
      <div class="form-group">
        <label><i class="fas fa-envelope"></i> Email</label>
        <input type="email" name="email" id="editMahasiswaEmail" placeholder="Masukkan email..." required>
      </div>
      <div class="form-group">
        <label><i class="fas fa-id-card"></i> NIM</label>
        <input type="text" name="nim" id="editMahasiswaNim" placeholder="Masukkan NIM..." required>
      </div>
      <div class="form-group">
        <label><i class="fas fa-lock"></i> Password (Kosongkan jika tidak ingin mengubah)</label>
        <input type="password" name="password" id="editMahasiswaPassword" placeholder="Masukkan password baru...">
      </div>
      <div class="modal-footer">
        <button type="submit" class="btn-modal btn-save">
          <i class="fas fa-save"></i> Simpan
        </button>
        <button type="button" class="btn-modal btn-cancel" onclick="closeModalEditMahasiswa()">
          <i class="fas fa-times"></i> Batal
        </button>
      </div>
    </form>
  </div>
</div>

<script>
// Load users on page load
document.addEventListener('DOMContentLoaded', function() {
  loadUsers();
  
  // Real-time search
  document.getElementById('searchInput').addEventListener('keyup', loadUsers);
  document.getElementById('filterInput').addEventListener('change', loadUsers);
});

function loadUsers() {
  const search = document.getElementById('searchInput').value;
  const filter = document.getElementById('filterInput').value;
  
  fetch(`users_ajax.php?search=${encodeURIComponent(search)}&filter=${encodeURIComponent(filter)}`)
    .then(response => response.text())
    .then(data => {
      document.getElementById('usersList').innerHTML = data;
    })
    .catch(error => console.error('Error:', error));
}

// Modal functions for Add Admin
function openModalAddAdmin() {
  document.getElementById('modalAddAdmin').classList.add('active');
}

function closeModalAddAdmin() {
  document.getElementById('modalAddAdmin').classList.remove('active');
}

// Modal functions for Edit Admin
function openModalEditAdmin(id, nama, username) {
  document.getElementById('editAdminId').value = id;
  document.getElementById('editAdminNama').value = nama || '';
  document.getElementById('editAdminUsername').value = username || '';
  document.getElementById('editAdminPassword').value = '';
  
  // Store original values
  originalAdminData = {
    nama: nama || '',
    username: username || '',
    password: ''
  };
  
  document.getElementById('modalEditAdmin').classList.add('active');
}

function closeModalEditAdmin() {
  if(hasAdminFormChanged()) {
    if(confirm('Ada perubahan yang belum disimpan. Tutup tanpa menyimpan?')) {
      document.getElementById('modalEditAdmin').classList.remove('active');
      resetAdminForm();
    }
  } else {
    document.getElementById('modalEditAdmin').classList.remove('active');
    resetAdminForm();
  }
}

// Track original form values for Admin
let originalAdminData = {};

function hasAdminFormChanged() {
  const currentData = {
    nama: document.getElementById('editAdminNama').value,
    username: document.getElementById('editAdminUsername').value,
    password: document.getElementById('editAdminPassword').value
  };
  
  return JSON.stringify(originalAdminData) !== JSON.stringify(currentData);
}

function resetAdminForm() {
  document.getElementById('editAdminNama').value = '';
  document.getElementById('editAdminUsername').value = '';
  document.getElementById('editAdminPassword').value = '';
}

// Modal functions for Add Mahasiswa
function openModalAddMahasiswa() {
  document.getElementById('modalAddMahasiswa').classList.add('active');
}

function closeModalAddMahasiswa() {
  document.getElementById('modalAddMahasiswa').classList.remove('active');
}

// Modal functions for Edit Mahasiswa
function openModalEditMahasiswa(id, nama, email, nim) {
  document.getElementById('editMahasiswaId').value = id;
  document.getElementById('editMahasiswaNama').value = nama || '';
  document.getElementById('editMahasiswaEmail').value = email || '';
  document.getElementById('editMahasiswaNim').value = nim || '';
  document.getElementById('editMahasiswaPassword').value = '';
  
  // Store original values
  originalMahasiswaData = {
    nama: nama || '',
    email: email || '',
    nim: nim || '',
    password: ''
  };
  
  document.getElementById('modalEditMahasiswa').classList.add('active');
}

function closeModalEditMahasiswa() {
  if(hasMahasiswaFormChanged()) {
    if(confirm('Ada perubahan yang belum disimpan. Tutup tanpa menyimpan?')) {
      document.getElementById('modalEditMahasiswa').classList.remove('active');
      resetMahasiswaForm();
    }
  } else {
    document.getElementById('modalEditMahasiswa').classList.remove('active');
    resetMahasiswaForm();
  }
}

// Track original form values for Mahasiswa
let originalMahasiswaData = {};

function hasMahasiswaFormChanged() {
  const currentData = {
    nama: document.getElementById('editMahasiswaNama').value,
    email: document.getElementById('editMahasiswaEmail').value,
    nim: document.getElementById('editMahasiswaNim').value,
    password: document.getElementById('editMahasiswaPassword').value
  };
  
  return JSON.stringify(originalMahasiswaData) !== JSON.stringify(currentData);
}

function resetMahasiswaForm() {
  document.getElementById('editMahasiswaNama').value = '';
  document.getElementById('editMahasiswaEmail').value = '';
  document.getElementById('editMahasiswaNim').value = '';
  document.getElementById('editMahasiswaPassword').value = '';
}

// Confirm functions before save
function confirmAdminSave() {
  if(hasAdminFormChanged()) {
    return confirm('Simpan perubahan data admin ini?');
  }
  alert('Tidak ada perubahan data yang disimpan.');
  return false;
}

function confirmMahasiswaSave() {
  if(hasMahasiswaFormChanged()) {
    return confirm('Simpan perubahan data mahasiswa ini?');
  }
  alert('Tidak ada perubahan data yang disimpan.');
  return false;
}

// Delete function
function deleteUser(id, type, nama) {
  if(confirm(`Apakah Anda yakin ingin menghapus ${type === 'admin' ? 'admin' : 'mahasiswa'} "${nama}"?`)) {
    const form = document.createElement('form');
    form.method = 'POST';
    form.innerHTML = `
      <input type="hidden" name="delete" value="1">
      <input type="hidden" name="user_id" value="${id}">
      <input type="hidden" name="user_type" value="${type}">
    `;
    document.body.appendChild(form);
    form.submit();
  }
}

// Toggle status function for mahasiswa
function toggleStatus(id, currentStatus) {
  fetch('users.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/x-www-form-urlencoded',
    },
    body: `toggle_status=1&user_id=${id}&current_status=${currentStatus}`
  })
  .then(response => response.json())
  .then(data => {
    if(data.status === 'success') {
      loadUsers();
    }
  })
  .catch(error => console.error('Error:', error));
}

// Close modal when clicking outside
document.addEventListener('click', function(e) {
  if(e.target.classList.contains('modal-overlay')) {
    if(e.target.id === 'modalEditAdmin') {
      closeModalEditAdmin();
    } else if(e.target.id === 'modalEditMahasiswa') {
      closeModalEditMahasiswa();
    } else {
      e.target.classList.remove('active');
    }
  }
});
</script>

</body>
</html>
