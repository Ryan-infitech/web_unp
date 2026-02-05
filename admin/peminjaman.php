<?php
session_start();
include '../config/database.php';

if(!isset($_SESSION['admin_id'])){
  header("Location: login.php");
  exit;
}

/* ================== AUTO COLUMN CHECK ================== */
 $check = mysqli_query($conn,"SHOW COLUMNS FROM peminjaman LIKE 'status'");
if(mysqli_num_rows($check)==0){
  mysqli_query($conn,"ALTER TABLE peminjaman ADD status VARCHAR(30) DEFAULT 'Menunggu'");
}
 $check2 = mysqli_query($conn,"SHOW COLUMNS FROM peminjaman LIKE 'keterangan'");
if(mysqli_num_rows($check2)==0){
  mysqli_query($conn,"ALTER TABLE peminjaman ADD keterangan TEXT NULL");
}
 $check3 = mysqli_query($conn,"SHOW COLUMNS FROM peminjaman LIKE 'tanggal_kembali'");
if(mysqli_num_rows($check3)==0){
  mysqli_query($conn,"ALTER TABLE peminjaman ADD tanggal_kembali DATETIME NULL");
}

// Create notifications table if not exist
$create_notif = "CREATE TABLE IF NOT EXISTS notifications (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  recipient_type VARCHAR(20) DEFAULT 'user' COMMENT 'user atau admin',
  title VARCHAR(255),
  message TEXT,
  is_read TINYINT(1) DEFAULT 0,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
)";
mysqli_query($conn, $create_notif);

// Add recipient_type column if not exist
$check_recipient = mysqli_query($conn,"SHOW COLUMNS FROM notifications LIKE 'recipient_type'");
if(mysqli_num_rows($check_recipient)==0){
  mysqli_query($conn,"ALTER TABLE notifications ADD COLUMN recipient_type VARCHAR(20) DEFAULT 'user' COMMENT 'user atau admin' AFTER user_id");
}

/* ================== ACTION ================== */
 $message=''; $message_type='';

// Handle bulk approve
if(isset($_POST['bulk_approve']) && isset($_POST['selected_peminjaman'])){
  $selected_ids = array_map('intval', $_POST['selected_peminjaman']);
  $count = 0;
  $failed = 0;
  
  foreach($selected_ids as $id){
    // Get peminjaman details
    $pem_q = mysqli_query($conn, "SELECT barang_labor_id, jumlah, user_id FROM peminjaman WHERE id=$id AND status='Menunggu'");
    
    if(mysqli_num_rows($pem_q) > 0){
      $pem = mysqli_fetch_assoc($pem_q);
      $barang_id = intval($pem['barang_labor_id']);
      $jumlah = intval($pem['jumlah']);
      
      // Get current stock
      $stock_q = mysqli_query($conn, "SELECT stok FROM barang_labor WHERE id=$barang_id");
      if(mysqli_num_rows($stock_q) > 0){
        $stock_row = mysqli_fetch_assoc($stock_q);
        $current_stok = intval($stock_row['stok']);
        $new_stok = max(0, $current_stok - $jumlah);
        $new_status = ($new_stok == 0) ? 'Tidak Tersedia' : 'Tersedia';
        
        // Update peminjaman dan barang
        $update_pem = mysqli_query($conn, "UPDATE peminjaman SET status='Disetujui' WHERE id=$id");
        $update_barang = mysqli_query($conn, "UPDATE barang_labor SET stok=$new_stok, status='$new_status' WHERE id=$barang_id");
        
        if($update_pem && $update_barang){
          $count++;
        } else {
          $failed++;
        }
      } else {
        $failed++;
      }
    } else {
      $failed++;
    }
  }
  
  if($count > 0){
    $message = "Total $count peminjaman berhasil disetujui!";
    $message_type = "success";
  }
  if($failed > 0){
    $message .= ($count > 0 ? " " : "") . "Total $failed peminjaman gagal disetujui.";
    $message_type = ($count > 0) ? "warning" : "danger";
  }
}

// Handle bulk reject
if(isset($_POST['bulk_reject']) && isset($_POST['selected_peminjaman'])){
  $selected_ids = array_map('intval', $_POST['selected_peminjaman']);
  $count = 0;
  
  foreach($selected_ids as $id){
    $update = mysqli_query($conn, "UPDATE peminjaman SET status='Ditolak' WHERE id=$id AND status='Menunggu'");
    if($update && mysqli_affected_rows($conn) > 0){
      $count++;
    }
  }
  
  if($count > 0){
    $message = "Total $count peminjaman berhasil ditolak!";
    $message_type = "warning";
  }
}

if(isset($_POST['approve'])){
  $id = intval($_POST['peminjaman_id']);
  
  // Get peminjaman details with proper validation
  $pem_q = mysqli_query($conn, "SELECT barang_labor_id, jumlah, user_id FROM peminjaman WHERE id=$id");
  if(!$pem_q) {
    error_log("ERROR: Failed to query peminjaman: " . mysqli_error($conn));
    $message = "Error: Gagal mengambil data peminjaman";
    $message_type = "danger";
  } else if(mysqli_num_rows($pem_q) === 0) {
    error_log("ERROR: Peminjaman ID $id not found");
    $message = "Error: Peminjaman tidak ditemukan";
    $message_type = "danger";
  } else {
    $pem = mysqli_fetch_assoc($pem_q);
    $barang_id = intval($pem['barang_labor_id']);
    $jumlah = intval($pem['jumlah']);
    $user_id = intval($pem['user_id']);
    
    // Validate data
    if($barang_id <= 0 || $jumlah <= 0) {
      error_log("ERROR: Invalid barang_id($barang_id) or jumlah($jumlah)");
      $message = "Error: Data peminjaman tidak valid";
      $message_type = "danger";
    } else {
      // Get current stock
      $stock_q = mysqli_query($conn, "SELECT stok FROM barang_labor WHERE id=$barang_id");
      if(!$stock_q) {
        error_log("ERROR: Failed to query barang_labor stock: " . mysqli_error($conn));
        $message = "Error: Gagal mengambil data stok";
        $message_type = "danger";
      } else if(mysqli_num_rows($stock_q) === 0) {
        error_log("ERROR: Barang ID $barang_id not found");
        $message = "Error: Peralatan tidak ditemukan";
        $message_type = "danger";
      } else {
        $stock_row = mysqli_fetch_assoc($stock_q);
        $current_stok = intval($stock_row['stok']);
        $new_stok = max(0, $current_stok - $jumlah);
        $new_status = ($new_stok == 0) ? 'Tidak Tersedia' : 'Tersedia';
        
        // Start transaction
        mysqli_begin_transaction($conn);
        
        try {
          // Update peminjaman status
          $update_pem = mysqli_query($conn, "UPDATE peminjaman SET status='Disetujui' WHERE id=$id");
          if(!$update_pem) throw new Exception("Failed to update peminjaman: " . mysqli_error($conn));
          
          // Update barang_labor stok dan status
          $update_barang = mysqli_query($conn, "UPDATE barang_labor SET stok=$new_stok, status='$new_status' WHERE id=$barang_id");
          if(!$update_barang) throw new Exception("Failed to update barang_labor: " . mysqli_error($conn));
          
          mysqli_commit($conn);
          
          // Send email to user
          $user_email_q = mysqli_query($conn, "SELECT email, nama FROM users WHERE id=$user_id");
          if($user_email_q && mysqli_num_rows($user_email_q) > 0) {
            $user_data = mysqli_fetch_assoc($user_email_q);
            $user_email = htmlspecialchars($user_data['email']);
            $user_name = htmlspecialchars($user_data['nama']);
            
            // Get detail barang dan labor
            $detail_q = mysqli_query($conn, "SELECT bl.nama as barang_nama, l.nama as labor_nama FROM peminjaman p LEFT JOIN barang_labor bl ON p.barang_labor_id=bl.id LEFT JOIN labor l ON p.labor_id=l.id WHERE p.id=$id");
            $detail = mysqli_fetch_assoc($detail_q);
            $barang_nama = htmlspecialchars($detail['barang_nama'] ?? 'N/A');
            $labor_nama = htmlspecialchars($detail['labor_nama'] ?? 'N/A');
            
            // Send email with HTML format
            require_once __DIR__ . '/../config/mailer.php';
            
            $email_subject = "Peminjaman Peralatan Disetujui - Sistem Laboratorium";
            $email_body = "
            <html>
            <head>
              <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; background: #f9f9f9; border-radius: 8px; }
                .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; border-radius: 8px 8px 0 0; text-align: center; }
                .content { background: white; padding: 20px; border-radius: 0 0 8px 8px; }
                .info-box { background: #f0f4ff; border-left: 4px solid #667eea; padding: 15px; margin: 15px 0; border-radius: 4px; }
                .success { color: #22c55e; font-weight: bold; }
                .footer { margin-top: 20px; padding-top: 20px; border-top: 1px solid #ddd; color: #666; font-size: 12px; }
              </style>
            </head>
            <body>
              <div class='container'>
                <div class='header'>
                  <h2>Peminjaman Peralatan Disetujui!</h2>
                </div>
                <div class='content'>
                  <p>Halo <strong>$user_name</strong>,</p>
                  <p>Peminjaman peralatan Anda telah <span class='success'>DISETUJUI</span> oleh admin.</p>
                  
                  <div class='info-box'>
                    <h3 style='margin-top: 0;'>Detail Peminjaman:</h3>
                    <p><strong>Labor:</strong> $labor_nama</p>
                    <p><strong>Peralatan:</strong> $barang_nama</p>
                    <p><strong>Jumlah:</strong> $jumlah pcs</p>
                    <p><strong>Status:</strong> <span class='success'>Disetujui</span></p>
                  </div>
                  
                  <p>Anda dapat segera mengambil peralatan tersebut di laboratorium pada jam kerja.</p>
                  <p>Silakan login ke sistem untuk melihat detail lengkap peminjaman Anda.</p>
                  
                  <div class='footer'>
                    <p>Email ini dikirim otomatis. Silakan jangan reply email ini.</p>
                    <p>&copy; Sistem Peminjaman Laboratorium</p>
                  </div>
                </div>
              </div>
            </body>
            </html>
            ";
            
            send_html_mail($user_email, $email_subject, $email_body);
            
            // Create in-app notification for user
            mysqli_query($conn, "INSERT INTO notifications (user_id, recipient_type, title, message, is_read, created_at) VALUES ($user_id, 'user', 'Peminjaman Disetujui', 'Peminjaman peralatan Anda telah disetujui oleh admin. Silakan ambil peralatan di laboratorium.', 0, NOW())");
            
            // Log notification to notification_logs table
            $admin_id = $_SESSION['admin_id'];
            $email_untuk_log = htmlspecialchars($user_email);
            mysqli_query($conn, "INSERT INTO notification_logs (admin_id, user_id, email, success, error_message, sent_at) VALUES ($admin_id, $user_id, '$email_untuk_log', 1, NULL, NOW())");
          }
          
          $message = "Peminjaman berhasil disetujui! Stok telah dikurangi dari $current_stok menjadi $new_stok";
          $message_type = "success";
        } catch(Exception $e) {
          mysqli_rollback($conn);
          error_log("ERROR: " . $e->getMessage());
          $message = "Error: " . $e->getMessage();
          $message_type = "danger";
        }
      }
    }
  }
}

if(isset($_POST['reject'])){
  $id=$_POST['peminjaman_id'];
  $alasan=mysqli_real_escape_string($conn,$_POST['alasan_penolakan']);
  mysqli_query($conn,"UPDATE peminjaman SET status='Ditolak', keterangan='$alasan' WHERE id=$id");
  $message="Peminjaman ditolak";
  $message_type="danger";
}

/* ================== DATA ================== */
 $q = mysqli_query($conn,"
  SELECT p.*, u.nama, u.nim, l.nama AS labor_nama, bl.nama AS barang_nama
  FROM peminjaman p
  JOIN users u ON p.user_id=u.id
  LEFT JOIN labor l ON p.labor_id=l.id
  LEFT JOIN barang_labor bl ON p.barang_labor_id=bl.id
  WHERE p.status='Menunggu'
  ORDER BY p.created_at DESC
");

 $pending = mysqli_num_rows(mysqli_query($conn,"SELECT id FROM peminjaman WHERE status='Menunggu'"));
 $rejected = mysqli_num_rows(mysqli_query($conn,"SELECT id FROM peminjaman WHERE status='Ditolak'"));
 $borrowed = mysqli_num_rows(mysqli_query($conn,"SELECT id FROM peminjaman WHERE status='Disetujui' OR status='Dikembalikan'"));

 $id = $_SESSION['admin_id'];
 $admin_q = mysqli_query($conn, "SELECT * FROM admin WHERE id=$id");
 $a = mysqli_fetch_assoc($admin_q);
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Data Peminjaman</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<style>
  * { margin: 0; padding: 0; box-sizing: border-box; }
  
  :root {
    --primary: #f59e0b;
    --primary-light: #fbbf24;
    --primary-dark: #d97706;
    --secondary: #fb923c;
    --bg-primary: #ffffff;
    --bg-secondary: #f8fafc;
    --text-primary: #0f172a;
    --text-secondary: #64748b;
    --border-color: #e0e0e0;
    --shadow: 0 4px 15px rgba(0,0,0,0.1);
  }

  @keyframes slideInUp {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
  }

  @keyframes slideInDown {
    from { opacity: 0; transform: translateY(-20px); }
    to { opacity: 1; transform: translateY(0); }
  }

  @keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
  }

  @keyframes pulse {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.05); }
  }
  
  body {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
    background-color: var(--bg-secondary);
    color: var(--text-primary);
    transition: background-color 0.3s ease, color 0.3s ease;
  }
  
  .main-content {
    margin-left: 250px;
    padding: 40px 30px;
    min-height: 100vh;
    transition: margin-left 0.3s ease;
  }
  
  .page-header {
    margin-bottom: 40px;
  }
  
  .page-title {
    font-size: 28px;
    font-weight: 700;
    margin-bottom: 8px;
    color: var(--text-primary);
  }
  
  .page-subtitle {
    font-size: 14px;
    color: var(--text-secondary);
  }

  .welcome-card {
    background: var(--bg-primary);
    border-radius: 10px;
    padding: 24px;
    border: 2px solid var(--border-color);
    transition: all 0.2s;
    margin-bottom: 30px;
  }

  .welcome-card h2 {
    font-size: 28px;
    font-weight: 700;
    margin-bottom: 8px;
    color: var(--text-primary);
    display: flex;
    align-items: center;
    gap: 10px;
  }

  .welcome-card h2 i {
    color: var(--primary);
  }

  .welcome-card p {
    font-size: 14px;
    color: var(--text-secondary);
    margin-bottom: 0;
  }
  
  .alert {
    border-radius: 8px;
    border: 2px solid;
    margin-bottom: 30px;
    font-size: 14px;
    font-weight: 500;
    padding: 12px 16px;
  }
  
  .alert-success {
    background-color: #dcfce7;
    color: #166534;
    border-color: #22c55e;
  }
  
  .alert-danger {
    background-color: #fee2e2;
    color: #991b1b;
    border-color: #ef4444;
  }
  
  .stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
    gap: 16px;
    margin-bottom: 30px;
  }
  
  .stat-card {
    background: var(--bg-primary);
    border-radius: 10px;
    padding: 18px 16px;
    border: 2px solid var(--border-color);
    transition: all 0.3s cubic-bezier(0.25, 0.46, 0.45, 0.94);
    animation: slideInUp 0.5s ease-out;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
  }

  .stat-card:hover {
    border-color: #2563eb;
    box-shadow: 0 8px 20px rgba(37, 99, 235, 0.2);
    transform: translateY(-4px) scale(1.01);
  }
  
  .stat-icon {
    width: 48px;
    height: 48px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 10px;
    font-size: 20px;
  }
  
  .stat-card:nth-child(1) .stat-icon {
    background-color: #fef3c7;
    color: #92400e;
  }
  
  .stat-card:nth-child(2) .stat-icon {
    background-color: #dcfce7;
    color: #16a34a;
  }
  
  .stat-card:nth-child(3) .stat-icon {
    background-color: #fee2e2;
    color: #dc2626;
  }
  
  .stat-title {
    font-size: 11px;
    color: var(--text-secondary);
    margin-bottom: 6px;
    font-weight: 500;
  }
  
  .stat-value {
    font-size: 28px;
    font-weight: 700;
    color: var(--text-primary);
  }
  
  .table-card {
    background: var(--bg-primary);
    border-radius: 10px;
    padding: 30px;
    border: 2px solid var(--border-color);
    margin-bottom: 40px;
    animation: slideInUp 0.6s ease-out 0.2s both;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
  }
  
  .table-card h4 {
    font-size: 20px;
    font-weight: 600;
    color: var(--text-primary);
    margin-bottom: 25px;
  }
  
  .table-responsive {
    border-radius: 8px;
    overflow: hidden;
    border: 1px solid var(--border-color);
  }
  
  .table {
    margin-bottom: 0;
    border-collapse: collapse;
  }
  
  .table thead {
    background-color: var(--bg-secondary);
    border-bottom: 2px solid var(--border-color);
  }
  
  .table thead th {
    font-weight: 600;
    color: var(--text-primary);
    padding: 14px;
    font-size: 13px;
    border: none;
    text-align: left;
  }
  
  .table tbody td {
    padding: 14px;
    border-bottom: 1px solid var(--border-color);
    font-size: 14px;
  }
  
  .table tbody tr {
    transition: all 0.3s ease;
  }

  .table tbody tr:hover {
    background-color: var(--bg-secondary);
    transform: scale(1.01);
    box-shadow: inset 0 0 10px rgba(245, 158, 11, 0.05);
  }

  /* Enhanced row padding and spacing */
  .table tbody tr td {
    padding: 16px 14px !important;
    vertical-align: middle;
  }

  .table-card {
    padding: 30px;
  }
  
  .badge {
    padding: 6px 12px;
    border-radius: 6px;
    font-size: 12px;
    font-weight: 600;
  }
  
  .badge.bg-warning {
    background-color: #fef3c7;
    color: #92400e;
  }
  
  .badge.bg-success {
    background-color: #dcfce7;
    color: #166534;
  }
  
  .badge.bg-danger {
    background-color: #fee2e2;
    color: #991b1b;
  }
  
  .btn-success {
    background-color: #16a34a;
    border: none;
    font-weight: 600;
    padding: 8px 16px;
    border-radius: 6px;
    color: white;
    font-size: 12px;
    cursor: pointer;
    transition: all 0.2s;
  }
  
  .btn-success:hover {
    background-color: #15803d;
    transform: translateY(-1px);
    box-shadow: 0 2px 8px rgba(22, 163, 74, 0.3);
  }
  
  .btn-danger {
    background-color: #dc2626;
    border: none;
    font-weight: 600;
    padding: 8px 16px;
    border-radius: 6px;
    color: white;
    font-size: 12px;
    cursor: pointer;
    transition: all 0.2s;
  }
  
  .btn-danger:hover {
    background-color: #b91c1c;
    transform: translateY(-1px);
    box-shadow: 0 2px 8px rgba(220, 38, 38, 0.3);
  }
  
  .modal-dialog {
    border-radius: 12px;
  }
  
  .modal-content {
    border: 2px solid var(--border-color);
    border-radius: 12px;
    background: var(--bg-primary);
  }
  
  .modal-header {
    background-color: var(--bg-secondary);
    border-bottom: 2px solid var(--border-color);
    padding: 20px;
  }
  
  .modal-header .modal-title {
    font-weight: 600;
    color: var(--text-primary);
  }
  
  .modal-body {
    padding: 20px;
  }

  /* Checkbox Styles */
  input[type="checkbox"] {
    width: 18px;
    height: 18px;
    cursor: pointer;
    accent-color: #16a34a;
    transition: all 0.2s ease;
  }

  input[type="checkbox"]:hover {
    transform: scale(1.1);
  }

  /* Selected Count */
  #selectedCountPem {
    padding: 8px 16px;
    background: rgba(22, 163, 74, 0.1);
    border-radius: 6px;
    border: 1px solid #16a34a;
    font-size: 14px;
  }

  .btn-sm {
    display: inline-flex;
    align-items: center;
    gap: 6px;
  }
  
  .modal-footer {
    border-top: 2px solid var(--border-color);
    padding: 16px 20px;
  }
  
  .form-label {
    font-weight: 600;
    color: var(--text-primary);
    margin-bottom: 8px;
    display: block;
    font-size: 14px;
  }
  
  .form-control,
  .form-select,
  textarea {
    border: 2px solid var(--border-color);
    border-radius: 8px;
    padding: 10px 14px;
    font-size: 14px;
    background-color: var(--bg-primary);
    color: var(--text-primary);
    transition: all 0.2s;
    font-family: inherit;
  }
  
  .form-control:focus,
  .form-select:focus,
  textarea:focus {
    border-color: #2563eb;
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
    outline: none;
  }
  
  @media (max-width: 768px) {
    .main-content {
      padding: 24px 16px;
    }
    
    .page-title {
      font-size: 22px;
    }
    
    .stats-grid {
      grid-template-columns: repeat(2, 1fr);
      gap: 16px;
    }
    
    .table-card {
      padding: 20px;
    }
    
    .table thead th {
      font-size: 12px;
      padding: 10px;
    }
    
    .table tbody td {
      padding: 10px;
      font-size: 12px;
    }
  }
</style>
</head>

<body>

<?php include '../assets/admin_sidebar.php'; ?>

<div class="main-content">
<?php if($message): ?>
<div class="alert alert-<?= $message_type ?> alert-dismissible fade show">
  <?= $message ?>
  <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<div class="welcome-card">
  <h2><i class="fas fa-clipboard-list"></i> Data Peminjaman Labor</h2>
  <p>Kelola dan setujui peminjaman mahasiswa</p>
</div>

<div class="stats-grid">
  <div class="stat-card">
    <div class="stat-icon"><i class="fas fa-clock"></i></div>
    <div class="stat-title">Menunggu Konfirmasi</div>
    <div class="stat-value"><?= $pending ?></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
    <div class="stat-title">Total Dipinjam</div>
    <div class="stat-value"><?= $borrowed ?></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon"><i class="fas fa-times-circle"></i></div>
    <div class="stat-title">Ditolak</div>
    <div class="stat-value"><?= $rejected ?></div>
  </div>
</div>

<div class="table-card">
  <h4>Daftar Peminjaman Menunggu Konfirmasi</h4>

  <div class="table-responsive">
    <form id="peminjamanForm" method="post">
      <div style="margin-bottom: 16px; display: flex; gap: 10px; align-items: center;">
        <span id="selectedCountPem" style="color: var(--text-secondary); font-weight: 500; display: none;">
          <strong id="countSelectedPem">0</strong> item dipilih
        </span>
        <button type="button" id="bulkApproveBtnPem" class="btn btn-success btn-sm" style="display: none;" onclick="bulkApproveSelected()">
          <i class="fas fa-check-circle"></i> Setujui Dipilih
        </button>
        <button type="button" id="bulkRejectBtnPem" class="btn btn-danger btn-sm" style="display: none;" onclick="bulkRejectSelected()">
          <i class="fas fa-times-circle"></i> Tolak Dipilih
        </button>
      </div>

      <table class="table table-bordered table-hover">
        <thead>
          <tr>
            <th style="width: 40px; padding-left: 16px;">
              <input type="checkbox" id="selectAllPem" onclick="toggleSelectAllPem(this)">
            </th>
            <th>Nama</th>
            <th>NIM</th>
            <th>Tanggal</th>
            <th>Labor</th>
            <th>Peralatan</th>
            <th>Keperluan</th>
            <th>Status</th>
            <th>Aksi</th>
          </tr>
        </thead>
        <tbody>
        <?php while($d=mysqli_fetch_assoc($q)): ?>
          <tr>
            <td style="padding-left: 16px;">
              <input type="checkbox" name="selected_peminjaman[]" value="<?= $d['id'] ?>" class="peminjaman-checkbox" onchange="updateDeleteButtonPem()">
            </td>
            <td><?= htmlspecialchars($d['nama']) ?></td>
            <td><?= htmlspecialchars($d['nim']) ?></td>
            <td><?= date('d-m-Y H:i',strtotime($d['created_at'])) ?></td>
            <td><?= htmlspecialchars($d['labor_nama'] ?? '-') ?></td>
            <td><?= htmlspecialchars($d['barang_nama'] ?? '-') ?></td>
            <td><?= htmlspecialchars($d['tanggung_jawab']) ?></td>
            <td><b><?= htmlspecialchars($d['status']) ?></b></td>
            <td>
            <?php if($d['status']=='Menunggu'): ?>
              <!-- Tombol Approve dengan Konfirmasi Browser -->
              <form method="post" class="d-inline" onsubmit="return confirm('Apakah Anda yakin ingin menyetujui peminjaman ini?');">
                <input type="hidden" name="peminjaman_id" value="<?= $d['id'] ?>">
                <button name="approve" class="btn btn-success btn-sm">✔</button>
              </form>
              
              <!-- Tombol Reject dengan Prompt Browser -->
              <button onclick="konfirmasiTolak(<?= $d['id'] ?>)" class="btn btn-danger btn-sm">✖</button>
            <?php else: ?>
              <span class="text-muted">-</span>
            <?php endif ?>
            </td>
          </tr>

        <?php endwhile ?>
        </tbody>
      </table>
    </form>
  </div>
</div>

</div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
if(localStorage.getItem('sidebarCollapsed')==='true') {
  document.body.classList.add('sidebar-collapsed');
}
document.addEventListener('click', function(e) {
  if(window.innerWidth<=768 && !document.querySelector('.sidebar').contains(e.target) && !document.querySelector('.sidebar-toggle').contains(e.target) && !document.body.classList.contains('sidebar-collapsed')) {
    document.body.classList.add('sidebar-collapsed');
  }
});

// Fungsi untuk menolak menggunakan Prompt Browser
function konfirmasiTolak(id) {
  // Tampilkan prompt browser
  var alasan = prompt("Masukkan alasan penolakan:");
  
  // Jika user mengisi alasan dan klik OK
  if (alasan != null && alasan.trim() !== "") {
    // Buat elemen form secara dinamis
    var form = document.createElement('form');
    form.method = 'POST';
    form.action = ''; // Submit ke halaman yang sama
    
    // Input ID Peminjaman
    var idInput = document.createElement('input');
    idInput.type = 'hidden';
    idInput.name = 'peminjaman_id';
    idInput.value = id;
    
    // Input Alasan Penolakan
    var alasanInput = document.createElement('input');
    alasanInput.type = 'hidden';
    alasanInput.name = 'alasan_penolakan';
    alasanInput.value = alasan;
    
    // Input flag reject agar PHP masuk ke blok isset($_POST['reject'])
    var rejectInput = document.createElement('input');
    rejectInput.type = 'hidden';
    rejectInput.name = 'reject';
    rejectInput.value = 'reject';

    // Masukkan semua input ke form
    form.appendChild(idInput);
    form.appendChild(alasanInput);
    form.appendChild(rejectInput);
    
    // Tambahkan form ke body dan submit
    document.body.appendChild(form);
    form.submit();
  } else if (alasan !== null) {
    // Jika user klik OK tapi kosong
    alert("Alasan penolakan harus diisi!");
  }
  // Jika user klik Cancel, tidak terjadi apa-apa
}

// Fungsi untuk checkbox pada peminjaman
function toggleSelectAllPem(checkbox) {
  const allCheckboxes = document.querySelectorAll('.peminjaman-checkbox');
  allCheckboxes.forEach(cb => {
    cb.checked = checkbox.checked;
  });
  updateDeleteButtonPem();
}

function updateDeleteButtonPem() {
  const selectedCheckboxes = document.querySelectorAll('.peminjaman-checkbox:checked');
  const approveBtn = document.getElementById('bulkApproveBtnPem');
  const rejectBtn = document.getElementById('bulkRejectBtnPem');
  const selectedCount = document.getElementById('selectedCountPem');
  const countSelected = document.getElementById('countSelectedPem');
  const selectAllCheckbox = document.getElementById('selectAllPem');

  if(selectedCheckboxes.length > 0) {
    approveBtn.style.display = 'inline-flex';
    rejectBtn.style.display = 'inline-flex';
    selectedCount.style.display = 'inline-block';
    countSelected.textContent = selectedCheckboxes.length;
  } else {
    approveBtn.style.display = 'none';
    rejectBtn.style.display = 'none';
    selectedCount.style.display = 'none';
    selectAllCheckbox.checked = false;
  }
}

function bulkApproveSelected() {
  const selectedCheckboxes = document.querySelectorAll('.peminjaman-checkbox:checked');
  
  if(selectedCheckboxes.length === 0) {
    alert('Pilih minimal 1 peminjaman untuk disetujui');
    return;
  }

  const count = selectedCheckboxes.length;
  if(confirm(`Apakah Anda yakin ingin menyetujui ${count} peminjaman?`)) {
    const form = document.getElementById('peminjamanForm');
    const input = document.createElement('input');
    input.type = 'hidden';
    input.name = 'bulk_approve';
    input.value = '1';
    form.appendChild(input);
    form.submit();
  }
}

function bulkRejectSelected() {
  const selectedCheckboxes = document.querySelectorAll('.peminjaman-checkbox:checked');
  
  if(selectedCheckboxes.length === 0) {
    alert('Pilih minimal 1 peminjaman untuk ditolak');
    return;
  }

  const count = selectedCheckboxes.length;
  if(confirm(`Apakah Anda yakin ingin menolak ${count} peminjaman?`)) {
    const form = document.getElementById('peminjamanForm');
    const input = document.createElement('input');
    input.type = 'hidden';
    input.name = 'bulk_reject';
    input.value = '1';
    form.appendChild(input);
    form.submit();
  }
}

// Listen untuk perubahan checkbox
document.querySelectorAll('.peminjaman-checkbox').forEach(checkbox => {
  checkbox.addEventListener('change', updateDeleteButtonPem);
});
</script>
</body>
</html>