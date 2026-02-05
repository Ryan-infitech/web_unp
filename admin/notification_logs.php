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

// Pastikan tabel notifikasi logs ada
$create_logs = "CREATE TABLE IF NOT EXISTS notification_logs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  notification_id INT NULL,
  admin_id INT NULL,
  user_id INT NOT NULL,
  email VARCHAR(191) NULL,
  success TINYINT(1) DEFAULT 0,
  error_message TEXT NULL,
  sent_at DATETIME DEFAULT CURRENT_TIMESTAMP
)";
mysqli_query($conn, $create_logs);

// Tambahkan kolom admin_id jika belum ada
$alter_logs = "ALTER TABLE notification_logs ADD COLUMN IF NOT EXISTS admin_id INT NULL";
mysqli_query($conn, $alter_logs);

// Ambil logs dengan info admin pengirim
$logs_q = mysqli_query($conn, "
  SELECT nl.*, u.nama as user_nama, u.nim, u.email as user_email, a.nama as admin_nama
  FROM notification_logs nl
  LEFT JOIN users u ON nl.user_id=u.id
  LEFT JOIN admin a ON nl.admin_id=a.id
  ORDER BY nl.sent_at DESC LIMIT 100
");
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Log Notifikasi Email</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
  * {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
  }

  @keyframes slideInDown {
    from { opacity: 0; transform: translateY(-20px); }
    to { opacity: 1; transform: translateY(0); }
  }

  @keyframes slideInUp {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
  }

  @keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
  }

  body {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
    background-color: #f8fafc;
    color: #1e293b;
    animation: fadeIn 0.3s ease;
  }
  
  /* Main Content */
  .main-content {
    margin-left: 250px;
    padding: 40px 30px;
    min-height: 100vh;
  }
  
  .page-header {
    background: #ffffff;
    color: #0f172a;
    border-radius: 10px;
    padding: 24px;
    margin-bottom: 30px;
    border: 1px solid #e2e8f0;
    transition: all 0.2s;
    animation: slideInDown 0.4s ease;
  }
  
  .page-header h2 {
    font-weight: 700;
    margin-bottom: 8px;
    font-size: 28px;
    color: #0f172a;
  }
  
  .page-header p {
    margin-bottom: 0;
    font-size: 14px;
    color: #64748b;
  }
  
  /* Table Container */
  .table-container {
    background: #ffffff;
    border-radius: 10px;
    padding: 30px;
    box-shadow: none;
    border: 1px solid #e2e8f0;
    animation: slideInUp 0.5s ease 0.2s both;
  }
  
  .table-container h5 {
    color: #0f172a;
    font-weight: 600;
    margin-bottom: 25px;
    font-size: 20px;
  }
  
  .table-responsive {
    background: transparent;
    border-radius: 8px;
    padding: 0;
    box-shadow: none;
    overflow-x: auto;
  }
  
  .table {
    margin-bottom: 0;
    border-collapse: collapse;
  }
  
  .table th {
    background-color: #f1f5f9;
    color: #475569;
    border: none;
    font-weight: 600;
    padding: 14px;
    font-size: 13px;
    text-align: left;
  }
  
  .table th i {
    margin-right: 8px;
    color: #64748b;
  }
  
  .table td {
    padding: 14px;
    border-bottom: 1px solid #e2e8f0;
    font-weight: 500;
    vertical-align: middle;
    font-size: 14px;
    color: #1e293b;
  }
  
  .table tbody tr:hover {
    background-color: #f8fafc;
    animation: fadeIn 0.3s ease;
  }
  
  .badge {
    padding: 6px 12px;
    border-radius: 6px;
    font-weight: 600;
    border: none;
    font-size: 12px;
  }
  
  .badge-success {
    background-color: #dcfce7;
    color: #166534;
  }
  
  .badge-danger {
    background-color: #fee2e2;
    color: #991b1b;
  }
  
  .badge-warning {
    background-color: #fef3c7;
    color: #92400e;
  }
  
  .text-muted {
    color: #64748b !important;
    font-weight: 500;
  }
  
  .text-danger {
    color: #dc2626 !important;
    font-weight: 600;
  }
  
  .text-muted i {
    color: #94a3b8;
  }
  
  /* Empty State */
  .empty-state {
    text-align: center;
    padding: 60px 20px;
    color: #64748b;
    animation: slideInUp 0.5s ease;
  }
  
  .empty-state i {
    font-size: 64px;
    color: #cbd5e1;
    margin-bottom: 20px;
  }
  
  .empty-state p {
    font-size: 16px;
    font-weight: 500;
    margin: 0;
    color: #64748b;
  }
  
  /* Mobile responsive */
  @media (max-width: 768px) {
    .main-content {
      padding: 20px;
    }
    
    .page-header h2 {
      font-size: 24px;
    }
    
    .table-container h5 {
      font-size: 18px;
    }
  }
</style>
</head>
<body>

<?php include '../assets/admin_sidebar.php'; ?>

<!-- Main Content -->
<div class="main-content">
  <!-- Page Header -->
  <div class="page-header">
    <h2><i class="fas fa-paper-plane"></i> Log Email yang Dikirim</h2>
    <p>Riwayat email yang dikirim admin kepada user peminjaman</p>
  </div>

<div style="margin-bottom: 20px;">
  <a href="notifikasi.php" class="btn btn-secondary btn-sm">
    <i class="fas fa-arrow-left"></i> Kembali
  </a>
</div>

  <!-- Data Table -->
  <div class="table-container">
    <h5><i class="fas fa-envelope-open"></i> Email yang Dikirim ke User</h5>
    <?php if(mysqli_num_rows($logs_q) > 0): ?>
      <div class="table-responsive">
        <table class="table table-hover mb-0">
          <thead>
            <tr>
              <th><i class="fas fa-user"></i> Admin Pengirim</th>
              <th><i class="fas fa-user-circle"></i> Penerima</th>
              <th><i class="fas fa-id-card"></i> NIM</th>
              <th><i class="fas fa-envelope"></i> Email Tujuan</th>
              <th><i class="fas fa-check-circle"></i> Status</th>
              <th><i class="fas fa-exclamation-circle"></i> Error</th>
              <th><i class="fas fa-calendar"></i> Waktu Kirim</th>
            </tr>
          </thead>
          <tbody>
            <?php while($l = mysqli_fetch_assoc($logs_q)): ?>
              <tr>
                <td><strong><?php echo htmlspecialchars($l['admin_nama'] ?? 'System'); ?></strong></td>
                <td><?php echo htmlspecialchars($l['user_nama'] ?? '-'); ?></td>
                <td><?php echo htmlspecialchars($l['nim'] ?? '-'); ?></td>
                <td><?php echo htmlspecialchars($l['user_email'] ?? '-'); ?></td>
                <td>
                  <?php if($l['success']): ?>
                    <span class="badge badge-success"><i class="fas fa-check"></i> Terkirim</span>
                  <?php else: ?>
                    <span class="badge badge-danger"><i class="fas fa-times"></i> Gagal</span>
                  <?php endif; ?>
                </td>
                <td>
                  <?php if($l['error_message']): ?>
                    <small class="text-danger"><?php echo htmlspecialchars($l['error_message']); ?></small>
                  <?php else: ?>
                    <span class="text-muted">-</span>
                  <?php endif; ?>
                </td>
                <td><?php echo date('d-m-Y H:i', strtotime($l['sent_at'])); ?></td>
              </tr>
            <?php endwhile; ?>
          </tbody>
        </table>
      </div>
    <?php else: ?>
      <div class="empty-state">
        <i class="fas fa-inbox"></i>
        <p>Tidak ada log notifikasi</p>
      </div>
    <?php endif; ?>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
if(localStorage.getItem('sidebarCollapsed')==='true') document.body.classList.add('sidebar-collapsed');
document.addEventListener('click', function(e) {
  if(window.innerWidth<=768 && !document.querySelector('.sidebar').contains(e.target) && !document.querySelector('.sidebar-toggle').contains(e.target) && !document.body.classList.contains('sidebar-collapsed')) {
    document.body.classList.add('sidebar-collapsed');
  }
});
</script>
</body>
</html>