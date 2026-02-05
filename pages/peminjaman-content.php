<?php
session_start();
if(!isset($_SESSION['user_id'])){
  header("Location: ../auth/login.php");
  exit;
}
include '../config/database.php';

// Create tables if not exist
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS labor (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nama VARCHAR(191) NOT NULL,
  deskripsi TEXT,
  icon VARCHAR(50),
  color VARCHAR(20),
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
)");

mysqli_query($conn, "CREATE TABLE IF NOT EXISTS barang_labor (
  id INT AUTO_INCREMENT PRIMARY KEY,
  labor_id INT NOT NULL,
  nama VARCHAR(191) NOT NULL,
  deskripsi TEXT,
  stok INT DEFAULT 1,
  status VARCHAR(20) DEFAULT 'Tersedia',
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (labor_id) REFERENCES labor(id)
)");

// Add columns if not exist
 $check_cols = mysqli_query($conn, "SHOW COLUMNS FROM peminjaman");
 $existing_cols = array();
while($col = mysqli_fetch_assoc($check_cols)) {
  $existing_cols[] = $col['Field'];
}

if(!in_array('labor_id', $existing_cols)) {
  mysqli_query($conn, "ALTER TABLE peminjaman ADD COLUMN labor_id INT NULL");
}
if(!in_array('barang_labor_id', $existing_cols)) {
  mysqli_query($conn, "ALTER TABLE peminjaman ADD COLUMN barang_labor_id INT NULL");
}
if(!in_array('jumlah', $existing_cols)) {
  mysqli_query($conn, "ALTER TABLE peminjaman ADD COLUMN jumlah INT DEFAULT 1");
}

// Create notifications table if not exist
$create_notif = "CREATE TABLE IF NOT EXISTS notifications (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  title VARCHAR(255),
  message TEXT,
  is_read TINYINT(1) DEFAULT 0,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
)";
mysqli_query($conn, $create_notif);

 $message = '';
 $message_type = '';

// Get user data early (needed for notifications)
$user_q = mysqli_query($conn, "SELECT * FROM users WHERE id='".$_SESSION['user_id']."'");
$user = mysqli_fetch_assoc($user_q);

// Handle form submission
if(isset($_POST['kirim'])){
  $labor_id = isset($_POST['labor_id']) && !empty($_POST['labor_id']) ? intval($_POST['labor_id']) : null;
  $tanggal_mulai = mysqli_real_escape_string($conn, $_POST['tanggal_mulai'] ?? '');
  $tanggal_selesai = mysqli_real_escape_string($conn, $_POST['tanggal_selesai'] ?? '');
  $keperluan = mysqli_real_escape_string($conn, $_POST['tanggung']);
  $user_id = intval($_SESSION['user_id']);
  
  // Get equipment quantities from form
  $barang_quantities = isset($_POST['barang_qty']) ? $_POST['barang_qty'] : [];
  
  if(empty($tanggal_mulai) || empty($tanggal_selesai) || empty($keperluan) || !$labor_id || empty($barang_quantities)){
    $message = "Semua field harus diisi dengan benar!";
    $message_type = "danger";
  } else if(strtotime($tanggal_selesai) <= strtotime($tanggal_mulai)){
    $message = "Tanggal selesai harus setelah tanggal mulai!";
    $message_type = "danger";
  } else {
    $all_success = true;
    $failed_items = [];
    
    foreach($barang_quantities as $barang_id => $jumlah) {
      $jumlah = intval($jumlah);
      if($jumlah < 1) continue;
      
      $barang_id = intval($barang_id);
      $stock_q = mysqli_query($conn, "SELECT stok FROM barang_labor WHERE id = $barang_id");
      $stock = mysqli_fetch_assoc($stock_q);
      
      if(!$stock || $stock['stok'] < $jumlah){
        $all_success = false;
        $failed_items[] = $barang_id;
        continue;
      }
      
      $ins = mysqli_query($conn, "INSERT INTO peminjaman(user_id, tanggung_jawab, labor_id, barang_labor_id, jumlah, status) 
        VALUES('$user_id', '$keperluan', $labor_id, $barang_id, $jumlah, 'Menunggu')");
      
      if(!$ins){
        $all_success = false;
        $failed_items[] = $barang_id;
      }
    }
    
    if($all_success){
      // Create notifications for all admins
      $admin_q = mysqli_query($conn, "SELECT id, nama FROM admin");
      if($admin_q && mysqli_num_rows($admin_q) > 0 && $user && isset($user['nama'])){
        $user_name = htmlspecialchars($user['nama']);
        while($admin = mysqli_fetch_assoc($admin_q)){
          $admin_id = intval($admin['id']);
          $notif_title = "Peminjaman Baru dari " . $user_name;
          $notif_msg = "User " . $user_name . " telah mengajukan peminjaman peralatan. Silakan cek dan berikan persetujuan.";
          mysqli_query($conn, "INSERT INTO notifications (user_id, recipient_type, title, message, is_read, created_at) VALUES ($admin_id, 'admin', '$notif_title', '$notif_msg', 0, NOW())");
        }
      }
      $message = "Peminjaman berhasil diajukan! Tunggu persetujuan dari admin.";
      $message_type = "success";
    } else if(empty($failed_items)){
      // Create notifications for all admins
      $admin_q = mysqli_query($conn, "SELECT id, nama FROM admin");
      if($admin_q && mysqli_num_rows($admin_q) > 0 && $user && isset($user['nama'])){
        $user_name = htmlspecialchars($user['nama']);
        while($admin = mysqli_fetch_assoc($admin_q)){
          $admin_id = intval($admin['id']);
          $notif_title = "Peminjaman Baru dari " . $user_name;
          $notif_msg = "User " . $user_name . " telah mengajukan peminjaman peralatan. Silakan cek dan berikan persetujuan.";
          mysqli_query($conn, "INSERT INTO notifications (user_id, recipient_type, title, message, is_read, created_at) VALUES ($admin_id, 'admin', '$notif_title', '$notif_msg', 0, NOW())");
        }
      }
      $message = "Peminjaman berhasil diajukan! Tunggu persetujuan dari admin.";
      $message_type = "success";
    } else {
      $message = "Beberapa peralatan gagal diproses. Periksa stok dan coba lagi.";
      $message_type = "danger";
    }
  }
}

// Get data
 $labor_q = mysqli_query($conn, "SELECT * FROM labor ORDER BY nama ASC");

// Generate initials
 $nama = isset($user['nama']) ? $user['nama'] : 'User';
 $parts = explode(' ', $nama);
 $initials = '';
foreach($parts as $part) {
  if(!empty($part)) {
    $initials .= strtoupper($part[0]);
  }
}
if(strlen($initials) > 2) {
  $initials = substr($initials, 0, 2);
}

 $foto_filename = $user['foto'] ?? '';
 $base_path = dirname(dirname(__FILE__));
 $foto_path = './assets/img/' . $foto_filename;
 $has_foto = $user && !empty($foto_filename) && file_exists($base_path . '/assets/img/' . $foto_filename);
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Ajukan Peminjaman - Labor</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      min-height: 100vh;
      padding-top: 30px;
    }

    .main-content {
      margin-left: 250px;
      padding: 30px 20px;
      min-height: 100vh;
      background: transparent;
      transition: margin-left 0.3s ease;
    }

    @media (max-width: 992px) {
      .main-content {
        margin-left: 0;
        padding: 60px 20px 20px 20px;
      }
    }

    @media (max-width: 768px) {
      .main-content {
        padding: 60px 15px 20px 15px;
      }
    }

    @media (max-width: 480px) {
      .main-content {
        padding: 55px 12px 20px 12px;
      }
    }

    .container-main {
      max-width: 1200px;
      margin: 0 auto;
    }

    .page-title {
      font-size: 28px;
      font-weight: 700;
      color: #2c3e50;
      margin-bottom: 30px;
      display: flex;
      align-items: center;
      gap: 15px;
    }

    .page-title i {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      font-size: 32px;
    }

    .card-modern {
      background: white;
      border: none;
      border-radius: 15px;
      box-shadow: 0 10px 40px rgba(0,0,0,0.08);
      overflow: hidden;
      margin-bottom: 25px;
      transition: all 0.3s ease;
    }

    .card-modern:hover {
      box-shadow: 0 15px 50px rgba(0,0,0,0.12);
      transform: translateY(-2px);
    }

    .card-header-modern {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      color: white;
      padding: 25px;
      font-size: 20px;
      font-weight: 700;
      display: flex;
      align-items: center;
      gap: 12px;
    }

    .card-body-modern {
      padding: 20px;
    }

    .user-info-box {
      display: grid;
      grid-template-columns: 80px auto auto auto;
      gap: 25px;
      margin-bottom: 15px;
      align-items: center;
    }

    .user-avatar {
      width: 80px;
      height: 80px;
      border-radius: 6px;
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      display: flex;
      align-items: center;
      justify-content: center;
      color: white;
      font-size: 30px;
      font-weight: 700;
      overflow: hidden;
      box-shadow: 0 2px 8px rgba(102, 126, 234, 0.15);
    }

    .user-avatar img {
      width: 100%;
      height: 100%;
      object-fit: cover;
    }

    .user-details {
      display: flex;
      flex-direction: row;
      justify-content: flex-start;
      gap: 30px;
      align-items: center;
    }

    .user-detail-item {
      margin-bottom: 0;
      text-align: center;
    }

    .user-detail-label {
      font-size: 10px;
      color: #999;
      text-transform: uppercase;
      font-weight: 600;
      margin-bottom: 3px;
      display: flex;
      align-items: center;
      gap: 6px;
    }

    .user-detail-label i {
      color: #667eea;
      font-size: 14px;
    }

    .user-detail-value {
      font-size: 13px;
      font-weight: 600;
      color: #2c3e50;
    }

    .form-section-title {
      font-size: 18px;
      font-weight: 700;
      color: #2c3e50;
      margin: 30px 0 20px 0;
      padding-bottom: 12px;
      border-bottom: 3px solid #667eea;
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .form-section-title i {
      color: #667eea;
      font-size: 22px;
    }

    .labor-options {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
      gap: 15px;
      margin-bottom: 25px;
    }

    .labor-option {
      position: relative;
    }

    .labor-option input[type="radio"] {
      display: none;
    }

    .labor-card {
      padding: 20px;
      border: 2px solid #e0e0e0;
      border-radius: 10px;
      cursor: pointer;
      transition: all 0.3s ease;
      text-align: center;
      background: white;
    }

    .labor-image-preview {
      width: 100%;
      height: 100px;
      margin-bottom: 12px;
      border-radius: 8px;
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      display: flex;
      align-items: center;
      justify-content: center;
      overflow: hidden;
      position: relative;
    }

    .labor-image-preview img {
      width: 100%;
      height: 100%;
      object-fit: cover;
    }

    .labor-image-preview i {
      font-size: 32px;
      color: white;
    }

    .labor-option input[type="radio"]:checked + .labor-card {
      border-color: #667eea;
      background: linear-gradient(135deg, rgba(102, 126, 234, 0.05) 0%, rgba(118, 75, 162, 0.05) 100%);
      box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
    }

    .labor-card:hover {
      border-color: #667eea;
      transform: translateY(-5px);
      box-shadow: 0 8px 20px rgba(102, 126, 234, 0.2);
    }

    .labor-icon {
      font-size: 32px;
      margin-bottom: 10px;
      display: block;
      color: #667eea;
    }

    .labor-name {
      font-size: 13px;
      font-weight: 700;
      color: #2c3e50;
      margin-bottom: 5px;
    }

    .labor-status {
      font-size: 11px;
      color: #28a745;
      font-weight: 600;
    }

    .equipment-list {
      display: grid;
      gap: 12px;
      margin-bottom: 25px;
      max-height: 400px;
      overflow-y: auto;
      padding-right: 10px;
    }

    .equipment-list::-webkit-scrollbar {
      width: 6px;
    }

    .equipment-list::-webkit-scrollbar-track {
      background: #f1f1f1;
      border-radius: 10px;
    }

    .equipment-list::-webkit-scrollbar-thumb {
      background: #667eea;
      border-radius: 10px;
    }

    .equipment-item {
      display: grid;
      grid-template-columns: 80px 1fr auto;
      gap: 15px;
      align-items: center;
      padding: 15px;
      border: 2px solid #e0e0e0;
      border-radius: 8px;
      transition: all 0.3s ease;
      background: white;
    }

    .equipment-item:hover {
      border-color: #667eea;
      background: #f8f9ff;
    }

    .equipment-item.disabled-equipment {
      opacity: 0.6;
      cursor: not-allowed;
      background: #f5f5f5;
      border-color: #ddd !important;
    }
    
    .equipment-item.disabled-equipment:hover {
      border-color: #ddd !important;
      background: #f5f5f5;
      transform: none;
    }
    
    .equipment-status-badge {
      display: inline-block;
      font-size: 11px;
      font-weight: 700;
      padding: 5px 10px;
      border-radius: 4px;
      margin-top: 5px;
    }
    
    .equipment-status-badge.not-available {
      background: #ffe5e5;
      color: #d32f2f;
    }
    
    .equipment-status-badge i {
      margin-right: 4px;
    }

    .equipment-stock {
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      color: white;
      padding: 12px 8px;
      border-radius: 6px;
      min-width: 70px;
      text-align: center;
      font-weight: 700;
    }

    .equipment-stock-number {
      font-size: 24px;
      line-height: 1;
      margin-bottom: 4px;
    }

    .equipment-stock-label {
      font-size: 10px;
      text-transform: uppercase;
      opacity: 0.9;
    }

    .equipment-controls {
      display: flex;
      align-items: center;
      gap: 10px;
      background: #f8f9ff;
      padding: 8px 12px;
      border-radius: 6px;
      border: 1px solid #e0e0e0;
    }

    .equipment-qty-btn {
      width: 28px;
      height: 28px;
      border: none;
      border-radius: 4px;
      background: #667eea;
      color: white;
      font-size: 14px;
      cursor: pointer;
      transition: all 0.2s ease;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 0;
    }

    .equipment-qty-btn:hover:not(:disabled) {
      background: #764ba2;
      transform: scale(1.1);
    }

    .equipment-qty-btn:disabled {
      background: #ccc;
      cursor: not-allowed;
      opacity: 0.5;
    }

    .equipment-qty-input {
      width: 45px;
      border: none;
      background: white;
      text-align: center;
      font-weight: 700;
      font-size: 16px;
      color: #2c3e50;
      padding: 6px 4px;
    }

    .equipment-qty-input:focus {
      outline: none;
    }

    .equipment-option {
      display: flex;
      align-items: center;
      gap: 15px;
      padding: 15px;
      border: 2px solid #e0e0e0;
      border-radius: 8px;
      cursor: pointer;
      transition: all 0.3s ease;
      background: white;
    }

    .equipment-option input[type="radio"] {
      width: 18px;
      height: 18px;
      cursor: pointer;
      flex-shrink: 0;
      accent-color: #667eea;
    }

    .equipment-option:hover {
      border-color: #667eea;
      background: #f8f9ff;
    }

    .equipment-option input[type="radio"]:checked + label {
      color: #667eea;
      font-weight: 700;
    }

    .equipment-info {
      flex: 1;
      display: flex;
      flex-direction: column;
      gap: 3px;
    }

    .equipment-name {
      font-weight: 700;
      color: #2c3e50;
      font-size: 14px;
    }

    .equipment-desc {
      font-size: 12px;
      color: #999;
    }

    .equipment-stock-badge {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      color: white;
      padding: 6px 12px;
      border-radius: 6px;
      font-size: 12px;
      font-weight: 700;
      white-space: nowrap;
    }

    .form-group-modern {
      margin-bottom: 20px;
    }

    .form-label-modern {
      display: block;
      font-size: 13px;
      font-weight: 700;
      color: #2c3e50;
      margin-bottom: 8px;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }

    .form-label-modern i {
      color: #667eea;
      margin-right: 6px;
    }

    .form-control-modern {
      width: 100%;
      padding: 12px 15px;
      border: 2px solid #e0e0e0;
      border-radius: 8px;
      font-size: 14px;
      font-family: inherit;
      transition: all 0.3s ease;
    }

    .form-control-modern:focus {
      outline: none;
      border-color: #667eea;
      box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
    }

    .quantity-box {
      display: flex;
      align-items: center;
      gap: 15px;
      background: #f8f9ff;
      padding: 15px 20px;
      border-radius: 8px;
      width: fit-content;
    }

    .qty-btn {
      width: 32px;
      height: 32px;
      border: none;
      border-radius: 6px;
      background: #667eea;
      color: white;
      font-size: 16px;
      cursor: pointer;
      transition: all 0.2s ease;
      display: flex;
      align-items: center;
      justify-content: center;
    }

    .qty-btn:hover {
      background: #764ba2;
      transform: scale(1.1);
    }

    .qty-input {
      width: 50px;
      border: none;
      background: white;
      text-align: center;
      font-weight: 700;
      font-size: 16px;
      color: #2c3e50;
    }

    .qty-input:focus {
      outline: none;
    }

    .btn-submit-modern {
      width: 100%;
      padding: 15px;
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      color: white;
      border: none;
      border-radius: 8px;
      font-size: 16px;
      font-weight: 700;
      cursor: pointer;
      transition: all 0.3s ease;
      margin-top: 30px;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 10px;
    }

    .btn-submit-modern:hover {
      transform: translateY(-2px);
      box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
    }

    .btn-submit-modern:active {
      transform: translateY(0);
    }

    .alert-box {
      padding: 15px 20px;
      border-radius: 8px;
      margin-bottom: 20px;
      display: flex;
      align-items: center;
      gap: 12px;
      animation: slideInDown 0.4s ease-out;
    }

    .alert-box.success {
      background: #d4edda;
      border-left: 4px solid #28a745;
      color: #155724;
    }

    .alert-box.danger {
      background: #f8d7da;
      border-left: 4px solid #dc3545;
      color: #721c24;
    }

    .alert-box i {
      font-size: 18px;
    }

    .placeholder-text {
      color: #999;
      text-align: center;
      padding: 30px;
      font-size: 14px;
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

    .form-row-modern {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 20px;
    }

    @media (max-width: 768px) {
      .form-row-modern {
        grid-template-columns: 1fr;
      }

      .labor-options {
        grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
      }

      .user-info-box {
        grid-template-columns: 80px 1fr;
        gap: 15px;
      }

      .user-details {
        flex-direction: column;
        gap: 10px;
        align-items: flex-start;
      }

      .user-detail-item {
        text-align: left;
      }

      .user-avatar {
        width: 80px;
        height: 80px;
      }
    }
  </style>
</head>
<body>
  <?php include '../assets/sidebar.php'; ?>

  <div class="main-content">
    <div class="container-main">
      <div class="page-title">
        <i class="fas fa-folder-open"></i>
        Ajukan Peminjaman Labor
      </div>

      <?php if($message): ?>
        <div class="alert-box <?php echo $message_type; ?>">
          <i class="fas fa-<?php echo $message_type === 'success' ? 'check-circle' : 'times-circle'; ?>"></i>
          <div><?php echo $message; ?></div>
        </div>
      <?php endif; ?>

      <div class="card-modern">
        <div class="card-header-modern">
          <i class="fas fa-clipboard-list"></i>
          Form Peminjaman
        </div>
        <div class="card-body-modern">
          <form method="post" id="formPeminjaman">
            <!-- Labor Selection -->
            <div class="form-section-title">
              <i class="fas fa-building"></i>
              Pilih Laboratorium
            </div>
            <div class="labor-options" id="laborContainer">
              <?php 
              if($labor_q && mysqli_num_rows($labor_q) > 0):
                while($labor = mysqli_fetch_assoc($labor_q)): 
              ?>
                <label class="labor-option">
                  <input type="radio" name="labor_id" value="<?php echo $labor['id']; ?>" required>
                  <div class="labor-card">
                    <div class="labor-image-preview">
                      <?php if(!empty($labor['image'])): ?>
                        <img src="../assets/uploads/labor/<?php echo htmlspecialchars($labor['image']); ?>" alt="<?php echo htmlspecialchars($labor['nama']); ?>">
                      <?php else: ?>
                        <i class="fas fa-<?php echo htmlspecialchars($labor['icon'] ?? 'flask'); ?>"></i>
                      <?php endif; ?>
                    </div>
                    <div class="labor-name"><?php echo htmlspecialchars($labor['nama']); ?></div>
                    <div class="labor-status">Tersedia</div>
                  </div>
                </label>
              <?php 
                endwhile;
              else:
              ?>
                <div class="placeholder-text">Tidak ada laboratorium tersedia</div>
              <?php 
              endif;
              ?>
            </div>

            <!-- Equipment Selection -->
            <div class="form-section-title">
              <i class="fas fa-tools"></i>
              Pilih Peralatan dan Jumlah
            </div>
            <div class="equipment-list" id="equipmentContainer">
              <div class="placeholder-text">Pilih laboratorium untuk melihat peralatan</div>
            </div>

            <!-- Date Section -->
            <div class="form-section-title" style="margin-top: 30px;">
              <i class="fas fa-calendar"></i>
              Jadwal Peminjaman
            </div>
            <div class="form-row-modern">
              <div class="form-group-modern">
                <label class="form-label-modern"><i class="fas fa-play-circle"></i>Tanggal Mulai</label>
                <input type="datetime-local" name="tanggal_mulai" class="form-control-modern" id="tanggalMulai" required>
              </div>
              <div class="form-group-modern">
                <label class="form-label-modern"><i class="fas fa-stop-circle"></i>Tanggal Selesai</label>
                <input type="datetime-local" name="tanggal_selesai" class="form-control-modern" id="tanggalSelesai" required>
              </div>
            </div>

            <!-- Purpose -->
            <div class="form-section-title">
              <i class="fas fa-pencil-alt"></i>
              Keperluan
            </div>
            <div class="form-group-modern">
              <label class="form-label-modern"><i class="fas fa-comment"></i>Jelaskan Keperluan Meminjam</label>
              <textarea name="tanggung" class="form-control-modern" placeholder="Masukkan keperluan meminjam peralatan..." rows="4" required></textarea>
            </div>

            <button type="submit" name="kirim" class="btn-submit-modern">
              <i class="fas fa-paper-plane"></i>
              Ajukan Peminjaman
            </button>
          </form>
        </div>
      </div>
    </div>
  </div>

  <script>
    // Set default dates
    document.addEventListener('DOMContentLoaded', function() {
      const now = new Date();
      
      // Format tanggal dengan benar untuk datetime-local
      const year = String(now.getFullYear()).padStart(4, '0');
      const month = String(now.getMonth() + 1).padStart(2, '0');
      const date = String(now.getDate()).padStart(2, '0');
      const hours = String(now.getHours()).padStart(2, '0');
      const minutes = String(now.getMinutes()).padStart(2, '0');
      
      const todayStr = `${year}-${month}-${date}T${hours}:${minutes}`;
      
      const tanggalMulai = document.getElementById('tanggalMulai');
      if(tanggalMulai) {
        tanggalMulai.value = todayStr;
        tanggalMulai.min = todayStr;
      }

      // Handle labor selection
      const laborInputs = document.querySelectorAll('input[name="labor_id"]');
      laborInputs.forEach(input => {
        input.addEventListener('change', function() {
          if(this.checked) {
            loadEquipment(this.value);
          }
        });
      });

      // Handle date changes
      tanggalMulai.addEventListener('change', function() {
        const tanggalSelesai = document.getElementById('tanggalSelesai');
        tanggalSelesai.min = this.value;
      });

      // Check URL parameters for auto-select labor
      const urlParams = new URLSearchParams(window.location.search);
      const laborIdFromUrl = urlParams.get('labor_id');

      if(laborIdFromUrl) {
        // Find the corresponding labor option
        const laborInput = document.querySelector(`input[name="labor_id"][value="${laborIdFromUrl}"]`);
        if(laborInput) {
          // Select the labor
          laborInput.checked = true;
          
          // Load equipment for that labor
          loadEquipment(laborIdFromUrl);

        }
      }
    });

    function loadEquipment(laborId) {
      const container = document.getElementById('equipmentContainer');
      
      if(!laborId) {
        container.innerHTML = '<div class="placeholder-text">Pilih laboratorium untuk melihat peralatan</div>';
        return;
      }

      container.innerHTML = '<div class="placeholder-text"><i class="fas fa-spinner fa-spin"></i> Memuat peralatan...</div>';

      // Add timestamp to prevent caching
      const timestamp = new Date().getTime();
      fetch('../config/get_barang.php?labor_id=' + laborId + '&t=' + timestamp)
        .then(response => response.json())
        .then(data => {
          if(data && data.length > 0) {
            let html = '';
            data.forEach((item, idx) => {
              // Check if item is available
              const isAvailable = item.status === 'Tersedia' && item.stok > 0;
              const disabledClass = !isAvailable ? 'disabled-equipment' : '';
              const disabledAttr = !isAvailable ? 'disabled' : '';
              
              // Status badge for unavailable items
              let statusBadge = '';
              if(!isAvailable) {
                if(item.stok === 0 || item.stok == '0') {
                  statusBadge = '<span class="equipment-status-badge not-available"><i class="fas fa-times-circle"></i> Habis</span>';
                } else {
                  statusBadge = '<span class="equipment-status-badge not-available"><i class="fas fa-ban"></i> Tidak Tersedia</span>';
                }
              }
              
              html += `
                <div class="equipment-item ${disabledClass}" data-barang-id="${item.id}" data-stok="${item.stok}" data-available="${isAvailable}">
                  <div class="equipment-stock">
                    <div class="equipment-stock-number" id="stock-${item.id}">${item.stok}</div>
                    <div class="equipment-stock-label">Stok</div>
                  </div>
                  <div class="equipment-info">
                    <div class="equipment-name">${item.nama}</div>
                    <div class="equipment-desc">${item.deskripsi || ''}</div>
                    ${statusBadge}
                  </div>
                  <div class="equipment-controls">
                    <button type="button" class="equipment-qty-btn" onclick="decreaseEquipmentQty(${item.id}, ${item.stok})" id="btn-minus-${item.id}" ${disabledAttr}>
                      <i class="fas fa-minus"></i>
                    </button>
                    <input type="number" name="barang_qty[${item.id}]" id="qty-${item.id}" class="equipment-qty-input" value="0" min="0" max="${item.stok}" onchange="updateEquipmentQty(${item.id}, ${item.stok})" ${disabledAttr}>
                    <button type="button" class="equipment-qty-btn" onclick="increaseEquipmentQty(${item.id}, ${item.stok})" id="btn-plus-${item.id}" ${disabledAttr}>
                      <i class="fas fa-plus"></i>
                    </button>
                  </div>
                </div>
              `;
            });
            container.innerHTML = html;
          } else {
            container.innerHTML = '<div class="placeholder-text">Tidak ada peralatan untuk laboratorium ini</div>';
          }
        })
        .catch(error => {
          console.error('Error:', error);
          container.innerHTML = '<div class="placeholder-text">Gagal memuat peralatan</div>';
        });
    }

    function increaseEquipmentQty(barangId, maxStok) {
      const qtyInput = document.getElementById('qty-' + barangId);
      let currentQty = parseInt(qtyInput.value) || 0;
      if(currentQty < maxStok) {
        qtyInput.value = currentQty + 1;
        updateEquipmentQty(barangId, maxStok);
      }
    }

    function decreaseEquipmentQty(barangId, maxStok) {
      const qtyInput = document.getElementById('qty-' + barangId);
      let currentQty = parseInt(qtyInput.value) || 0;
      if(currentQty > 0) {
        qtyInput.value = currentQty - 1;
        updateEquipmentQty(barangId, maxStok);
      }
    }

    function updateEquipmentQty(barangId, maxStok) {
      const qtyInput = document.getElementById('qty-' + barangId);
      const stockDisplay = document.getElementById('stock-' + barangId);
      const item = document.querySelector(`[data-barang-id="${barangId}"]`);
      const btnMinus = document.getElementById('btn-minus-' + barangId);
      const btnPlus = document.getElementById('btn-plus-' + barangId);
      
      let currentQty = parseInt(qtyInput.value) || 0;
      currentQty = Math.max(0, Math.min(currentQty, maxStok));
      qtyInput.value = currentQty;
      
      const remainingStok = maxStok - currentQty;
      stockDisplay.textContent = remainingStok;
      
      // Disable buttons if stok is 0 or qty is at max
      btnMinus.disabled = currentQty === 0;
      btnPlus.disabled = remainingStok === 0;
      qtyInput.disabled = maxStok === 0;
      
      // Update item appearance
      if(remainingStok === 0 && currentQty === 0) {
        item.classList.add('disabled');
      } else {
        item.classList.remove('disabled');
      }
    }
  </script>
</body>
</html>