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

// Auto-mark all unread notifications as read when page is opened
mysqli_query($conn, "UPDATE notifications SET is_read = 1 WHERE user_id = $id");

// Get incoming notifications/requests statistics
$pending_requests = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM peminjaman WHERE status = 'Menunggu'"))['total'] ?? 0;
$return_requests = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM peminjaman WHERE status = 'Disetujui' AND status_pengembalian = 0"))['total'] ?? 0;
$total_incoming = $pending_requests + $return_requests;

// Get list of incoming requests
$incoming_q = mysqli_query($conn, "
  SELECT 
    p.id,
    p.status,
    p.status_pengembalian,
    u.nama as user_name,
    u.email as user_email,
    l.nama as labor_name,
    bl.nama as barang_name,
    p.jumlah,
    p.created_at
  FROM peminjaman p
  LEFT JOIN users u ON p.user_id = u.id
  LEFT JOIN labor l ON p.labor_id = l.id
  LEFT JOIN barang_labor bl ON p.barang_labor_id = bl.id
  WHERE (p.status = 'Menunggu' OR (p.status = 'Disetujui' AND p.status_pengembalian = 0))
  ORDER BY p.created_at DESC
  LIMIT 20
");

// Mark as reviewed (if requested via AJAX)
if(isset($_POST['mark_reviewed']) && isset($_POST['peminjaman_id'])){
  $peminjaman_id = intval($_POST['peminjaman_id']);
  // This is for UI purposes - you might want to add a reviewed_by_admin column
  echo json_encode(['success' => true]);
  exit;
}

?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Notifikasi Masuk</title>
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

    /* Page Header */
    .page-header {
      margin-bottom: 30px;
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

    /* Stats */
    .stats-row {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
      gap: 20px;
      margin-bottom: 30px;
    }

    .stat-card {
      background: var(--bg-primary);
      border-radius: 12px;
      padding: 20px;
      box-shadow: var(--shadow);
      border: 2px solid transparent;
      transition: all 0.3s;
    }

    .stat-card:hover {
      transform: translateY(-5px);
      border-color: var(--primary);
    }

    .stat-card-icon {
      font-size: 28px;
      color: var(--primary);
      margin-bottom: 10px;
    }

    .stat-card-value {
      font-size: 24px;
      font-weight: 700;
      color: var(--text-primary);
      margin-bottom: 5px;
    }

    .stat-card-label {
      font-size: 12px;
      color: var(--text-secondary);
      text-transform: uppercase;
      letter-spacing: 0.5px;
      font-weight: 600;
    }

    /* Request List */
    .requests-section {
      background: var(--bg-primary);
      border-radius: 12px;
      padding: 25px;
      box-shadow: var(--shadow);
    }

    .requests-section h3 {
      font-size: 18px;
      font-weight: 700;
      color: var(--text-primary);
      margin-bottom: 20px;
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .requests-section h3 i {
      color: var(--primary);
    }

    .request-item {
      padding: 15px;
      border: 2px solid var(--border-color);
      border-radius: 8px;
      margin-bottom: 12px;
      transition: all 0.3s;
      display: grid;
      grid-template-columns: 1fr auto auto;
      gap: 15px;
      align-items: center;
    }

    .request-item:hover {
      border-color: var(--primary);
      background: var(--bg-secondary);
    }

    .request-item.pending {
      border-left: 4px solid var(--warning);
    }

    .request-item.approved {
      border-left: 4px solid var(--success);
    }

    .request-info {
      display: flex;
      flex-direction: column;
      gap: 8px;
    }

    .request-header {
      display: flex;
      align-items: center;
      gap: 10px;
      font-weight: 700;
      color: var(--text-primary);
    }

    .request-status {
      display: inline-block;
      font-size: 11px;
      font-weight: 700;
      padding: 4px 10px;
      border-radius: 4px;
      text-transform: uppercase;
    }

    .request-status.pending {
      background: rgba(234, 179, 8, 0.2);
      color: var(--warning);
    }

    .request-status.approved {
      background: rgba(34, 197, 94, 0.2);
      color: var(--success);
    }

    .request-details {
      font-size: 13px;
      color: var(--text-secondary);
      display: flex;
      flex-direction: column;
      gap: 4px;
    }

    .request-details span {
      display: flex;
      align-items: center;
      gap: 6px;
    }

    .request-details i {
      color: var(--primary);
      min-width: 14px;
    }

    .request-actions {
      display: flex;
      gap: 8px;
    }

    .btn-action {
      padding: 8px 12px;
      border: none;
      border-radius: 6px;
      font-size: 12px;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.3s;
      display: flex;
      align-items: center;
      gap: 6px;
    }

    .btn-view {
      background: var(--primary);
      color: white;
    }

    .btn-view:hover {
      background: var(--primary-dark);
      transform: translateY(-2px);
    }

    .btn-delete {
      background: var(--danger);
      color: white;
    }

    .btn-delete:hover {
      background: #c93030;
      transform: translateY(-2px);
    }

    .empty-state {
      text-align: center;
      padding: 40px 20px;
      color: var(--text-secondary);
    }

    .empty-state i {
      font-size: 48px;
      margin-bottom: 12px;
      opacity: 0.5;
    }

    .empty-state p {
      font-size: 14px;
      margin: 0;
    }

    /* Responsive */
    @media (max-width: 768px) {
      .main-content {
        margin-left: 70px;
        padding: 20px 15px;
      }

      .request-item {
        grid-template-columns: 1fr;
      }

      .request-actions {
        width: 100%;
      }

      .btn-action {
        flex: 1;
      }
    }
  </style>
</head>
<body>

<?php include '../assets/admin_sidebar.php'; ?>

<div class="main-content">
  <!-- Page Header -->
  <div class="page-header">
    <h1>
      <i class="fas fa-inbox"></i>
      Notifikasi Masuk
    </h1>
  </div>

  <!-- Statistics -->
  <div class="stats-row">
    <div class="stat-card">
      <div class="stat-card-icon"><i class="fas fa-hourglass-half"></i></div>
      <div class="stat-card-value"><?php echo $pending_requests; ?></div>
      <div class="stat-card-label">Peminjaman Menunggu</div>
    </div>
    <div class="stat-card">
      <div class="stat-card-icon"><i class="fas fa-undo"></i></div>
      <div class="stat-card-value"><?php echo $return_requests; ?></div>
      <div class="stat-card-label">Menunggu Pengembalian</div>
    </div>
    <div class="stat-card">
      <div class="stat-card-icon"><i class="fas fa-bell"></i></div>
      <div class="stat-card-value" style="color: var(--primary);"><?php echo $total_incoming; ?></div>
      <div class="stat-card-label">Total Notifikasi</div>
    </div>
  </div>

  <!-- Requests List -->
  <div class="requests-section">
    <h3><i class="fas fa-list-check"></i> Daftar Permintaan Masuk</h3>
    
    <div id="requestsList">
      <?php
      if(mysqli_num_rows($incoming_q) > 0):
        while($request = mysqli_fetch_assoc($incoming_q)):
          $request_class = $request['status'] === 'Menunggu' ? 'pending' : 'approved';
          $status_text = $request['status'] === 'Menunggu' ? 'Menunggu Persetujuan' : 'Menunggu Pengembalian';
          $created = new DateTime($request['created_at']);
          $now = new DateTime();
          $interval = $now->diff($created);
          
          if($interval->days > 0) {
            $time_text = $interval->days . ' hari lalu';
          } elseif($interval->h > 0) {
            $time_text = $interval->h . ' jam lalu';
          } elseif($interval->i > 0) {
            $time_text = $interval->i . ' menit lalu';
          } else {
            $time_text = 'Baru saja';
          }
          
          $action_page = $request['status'] === 'Menunggu' ? 'peminjaman.php' : 'pengembalian.php';
      ?>
        <div class="request-item <?php echo $request_class; ?>">
          <div class="request-info">
            <div class="request-header">
              <span><?php echo htmlspecialchars($request['user_name'] ?? 'User'); ?></span>
              <span class="request-status <?php echo $request_class; ?>"><?php echo $status_text; ?></span>
            </div>
            <div class="request-details">
              <span>
                <i class="fas fa-flask"></i>
                <?php echo htmlspecialchars($request['labor_name'] ?? 'Labor'); ?>
              </span>
              <span>
                <i class="fas fa-tools"></i>
                <?php echo htmlspecialchars($request['barang_name'] ?? 'Peralatan'); ?> (<?php echo $request['jumlah']; ?>x)
              </span>
              <span>
                <i class="fas fa-clock"></i>
                <?php echo $time_text; ?>
              </span>
            </div>
          </div>
          <div style="color: var(--text-secondary); font-size: 13px; text-align: right;">
            <div><?php echo htmlspecialchars($request['user_email'] ?? '-'); ?></div>
          </div>
          <div class="request-actions">
            <a href="<?php echo $action_page; ?>?id=<?php echo $request['id']; ?>" class="btn-action btn-view">
              <i class="fas fa-eye"></i> Lihat
            </a>
          </div>
        </div>
      <?php
        endwhile;
      else:
      ?>
        <div class="empty-state">
          <i class="fas fa-inbox"></i>
          <p>Tidak ada notifikasi masuk</p>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>

</body>
</html>
