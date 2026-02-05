<?php
session_start();
if(!isset($_SESSION['user_id'])){
  header("Location: ../auth/login.php");
  exit;
}
include '../config/database.php';
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard - Aplikasi Labor</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
  * {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
  }
  
  body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background-color: #f5f7fb;
    overflow-x: hidden;
  }
  
  .main-content {
    margin-left: 250px;
    padding: 30px 20px;
    min-height: 100vh;
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
</style>
</head>
<body>

<!-- Include Sidebar -->
<?php include '../assets/sidebar.php'; ?>

<!-- Main Content -->
<div class="main-content">

<?php
if(!isset($_SESSION['user_id'])){
  header("HTTP/1.1 401 Unauthorized");
  exit;
}

 $user = mysqli_fetch_assoc(mysqli_query($conn,"SELECT nama FROM users WHERE id='".intval($_SESSION['user_id'])."'"));

// Ambil statistik peminjaman
 $stats_q = mysqli_query($conn, "SELECT 
  COUNT(CASE WHEN status = 'Menunggu' THEN 1 END) as menunggu,
  COUNT(CASE WHEN status = 'Disetujui' AND status_pengembalian = 0 THEN 1 END) as disetujui,
  COUNT(CASE WHEN status = 'Ditolak' THEN 1 END) as ditolak,
  COUNT(CASE WHEN status = 'Dikembalikan' OR status_pengembalian = 1 THEN 1 END) as dikembalikan,
  COUNT(*) as total
FROM peminjaman WHERE user_id='".intval($_SESSION['user_id'])."'
");
 $stats = mysqli_fetch_assoc($stats_q);

// PERBAIKAN LOGIKA: Ambil info stok peralatan dari seluruh labor
// Total peralatan = SUM(stok_awal) = total stok dari semua labor
// Tersedia = SUM(stok) dimana status='Tersedia' = stok yang bisa dipinjam
 $labor_stats = mysqli_query($conn, "SELECT 
  SUM(stok_awal) as total_barang,
  SUM(CASE WHEN status='Tersedia' THEN stok ELSE 0 END) as barang_tersedia
FROM barang_labor
");
 $labor_stat = mysqli_fetch_assoc($labor_stats);

// Ambil daftar labor
 $labor_list = mysqli_query($conn, "SELECT * FROM labor ORDER BY id ASC");

// Ensure recipient_type column exists
$check_recipient = mysqli_query($conn, "SHOW COLUMNS FROM notifications LIKE 'recipient_type'");
if(mysqli_num_rows($check_recipient) == 0){
  mysqli_query($conn, "ALTER TABLE notifications ADD COLUMN recipient_type VARCHAR(20) DEFAULT 'user' AFTER user_id");
}

// Ambil notifikasi
 $notifs_q = mysqli_query($conn, "SELECT * FROM notifications WHERE user_id='".intval($_SESSION['user_id'])."' AND (recipient_type='user' OR recipient_type IS NULL OR recipient_type='') ORDER BY created_at DESC LIMIT 5");
 $unread_count = mysqli_num_rows(mysqli_query($conn, "SELECT id FROM notifications WHERE user_id='".intval($_SESSION['user_id'])."' AND (recipient_type='user' OR recipient_type IS NULL OR recipient_type='') AND is_read=0"));
?>

<style>
  .dashboard-welcome {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 40px;
    border-radius: 12px;
    margin-bottom: 30px;
    box-shadow: 0 5px 20px rgba(102, 126, 234, 0.3);
    animation: slideDown 0.5s ease-out;
  }

  .dashboard-welcome h1 {
    font-size: 28px;
    font-weight: 700;
    margin-bottom: 8px;
  }

  .dashboard-welcome p {
    opacity: 0.95;
    font-size: 15px;
  }

  /* Stats Grid */
  .stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
  }

  .stat-card {
    background: white;
    padding: 25px;
    border-radius: 12px;
    box-shadow: 0 2px 15px rgba(0,0,0,0.08);
    display: flex;
    align-items: center;
    justify-content: space-between;
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
    animation: slideUp 0.5s ease-out;
    animation-fill-mode: both;
  }

  .stat-card:nth-child(1) { animation-delay: 0.1s; }
  .stat-card:nth-child(2) { animation-delay: 0.2s; }
  .stat-card:nth-child(3) { animation-delay: 0.3s; }
  .stat-card:nth-child(4) { animation-delay: 0.4s; }

  .stat-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 4px;
    background: linear-gradient(90deg, #667eea, #764ba2);
    transform: scaleX(0);
    transform-origin: left;
    transition: transform 0.3s ease;
  }

  .stat-card:hover::before {
    transform: scaleX(1);
  }

  .stat-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.12);
  }

  .stat-info h3 {
    font-size: 32px;
    font-weight: 700;
    color: #667eea;
    margin-bottom: 5px;
    transition: all 0.3s ease;
  }

  .stat-card:hover .stat-info h3 {
    transform: scale(1.1);
  }

  .stat-info p {
    color: #666;
    font-size: 13px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
  }

  .stat-icon {
    width: 60px;
    height: 60px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 28px;
    transition: all 0.3s ease;
  }

  .stat-card:hover .stat-icon {
    transform: rotate(10deg) scale(1.15);
  }

  .stat-card.menunggu .stat-icon {
    background: rgba(255, 193, 7, 0.15);
    color: #ffc107;
  }

  .stat-card.disetujui .stat-icon {
    background: rgba(76, 175, 80, 0.15);
    color: #4caf50;
  }

  .stat-card.ditolak .stat-icon {
    background: rgba(244, 67, 54, 0.15);
    color: #f44336;
  }

  .stat-card.total .stat-icon {
    background: rgba(102, 126, 234, 0.15);
    color: #667eea;
  }

  .stat-card.dikembalikan .stat-icon {
    background: rgba(23, 162, 184, 0.15);
    color: #17a2b8;
  }

  /* Labor Section */
  .labor-section {
    background: white;
    padding: 30px;
    border-radius: 12px;
    box-shadow: 0 2px 15px rgba(0,0,0,0.08);
    margin-bottom: 30px;
    animation: slideUp 0.6s ease-out;
  }

  .section-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 25px;
    padding-bottom: 15px;
    border-bottom: 2px solid #f0f0f0;
  }

  .section-header h2 {
    color: #333;
    font-size: 20px;
    font-weight: 700;
    display: flex;
    align-items: center;
  }

  .section-header i {
    color: #667eea;
    margin-right: 12px;
    font-size: 24px;
  }

  .availability-info {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
    font-size: 14px;
  }

  .availability-info span {
    color: #666;
  }

  .availability-info strong {
    color: #333;
  }

  .progress-bar {
    width: 100%;
    height: 10px;
    background: #e0e0e0;
    border-radius: 5px;
    overflow: hidden;
    position: relative;
    margin-top: 10px;
  }

  .progress-fill {
    height: 100%;
    background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
    transition: width 1s ease;
    position: relative;
    overflow: hidden;
  }

  .progress-fill::after {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    bottom: 0;
    right: 0;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
    animation: shimmer 2s infinite;
  }

  @keyframes shimmer {
    0% { transform: translateX(-100%); }
    100% { transform: translateX(100%); }
  }

  /* Labor Grid */
  .labor-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 20px;
    margin-top: 20px;
  }

  .labor-card {
    border: 2px solid #e0e0e0;
    border-radius: 12px;
    overflow: hidden;
    transition: all 0.3s ease;
    cursor: pointer;
    animation: fadeInUp 0.6s ease-out;
    animation-fill-mode: both;
  }

  .labor-card:nth-child(1) { animation-delay: 0.1s; }
  .labor-card:nth-child(2) { animation-delay: 0.2s; }
  .labor-card:nth-child(3) { animation-delay: 0.3s; }

  .labor-card:hover {
    border-color: #667eea;
    transform: translateY(-8px);
    box-shadow: 0 10px 30px rgba(102, 126, 234, 0.2);
  }

  .labor-header {
    width: 100%;
    height: 140px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 56px;
    position: relative;
    overflow: hidden;
  }

  .labor-header::after {
    content: '';
    position: absolute;
    top: -50%;
    right: -50%;
    width: 200%;
    height: 200%;
    background: rgba(255,255,255,0.1);
    border-radius: 50%;
    transition: all 0.5s ease;
  }

  .labor-card:hover .labor-header::after {
    top: -10%;
    right: -10%;
  }

  .labor-header i {
    position: relative;
    z-index: 1;
    transition: transform 0.3s ease;
  }

  .labor-card:hover .labor-header i {
    transform: scale(1.2) rotate(10deg);
  }

  .labor-header img {
    transition: transform 0.3s ease;
  }

  .labor-card:hover .labor-header img {
    transform: scale(1.1);
  }

  .labor-body {
    padding: 20px;
  }

  .labor-title {
    font-size: 16px;
    font-weight: 700;
    color: #333;
    margin-bottom: 10px;
    transition: color 0.3s ease;
  }

  .labor-card:hover .labor-title {
    color: #667eea;
  }

  .labor-desc {
    font-size: 13px;
    color: #666;
    margin-bottom: 15px;
    line-height: 1.5;
  }

  .labor-button {
    width: 100%;
    padding: 10px 15px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
  }

  .labor-button:hover {
    transform: scale(1.02);
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
    color: white;
  }

  /* Notifikasi Widget */
  .notif-widget {
    background: white;
    padding: 30px;
    border-radius: 12px;
    box-shadow: 0 2px 15px rgba(0,0,0,0.08);
    animation: slideUp 0.7s ease-out;
  }

  .notif-widget h5 {
    color: #667eea;
    font-weight: 700;
    margin-bottom: 25px;
    font-size: 18px;
    display: flex;
    align-items: center;
  }

  .notif-widget i {
    margin-right: 10px;
    animation: bounce 2s ease-in-out infinite;
  }

  .notif-item {
    border-left: 4px solid #667eea;
    padding: 15px;
    margin-bottom: 12px;
    background: #f8f9ff;
    border-radius: 8px;
    transition: all 0.2s ease;
    font-size: 13px;
  }

  .notif-item:hover {
    background: #f0f2ff;
    transform: translateX(5px);
  }

  .notif-item.unread {
    background: #fffaf0;
    border-left-color: #ff9800;
  }

  .notif-item-title {
    font-weight: 700;
    color: #333;
    margin-bottom: 5px;
  }

  .notif-item-time {
    font-size: 11px;
    color: #999;
  }

  @media (max-width: 768px) {
    .stats-grid {
      grid-template-columns: 1fr 1fr;
    }

    .labor-grid {
      grid-template-columns: 1fr;
    }
  }
</style>

<div class="dashboard-welcome">
  <h1><i class="fas fa-home"></i> Dashboard</h1>
  <p>Selamat datang kembali, <strong><?php echo htmlspecialchars($user['nama']); ?></strong>! 👋</p>
</div>

<!-- Statistics Grid -->
<div class="stats-grid">
  <div class="stat-card menunggu" id="stat-menunggu">
    <div class="stat-info">
      <h3 id="stat-menunggu-count"><?php echo $stats['menunggu']; ?></h3>
      <p>Menunggu</p>
    </div>
    <div class="stat-icon">
      <i class="fas fa-hourglass-half"></i>
    </div>
  </div>

  <div class="stat-card disetujui" id="stat-disetujui">
    <div class="stat-info">
      <h3 id="stat-disetujui-count"><?php echo $stats['disetujui']; ?></h3>
      <p>Disetujui</p>
    </div>
    <div class="stat-icon">
      <i class="fas fa-check-circle"></i>
    </div>
  </div>

  <div class="stat-card ditolak" id="stat-ditolak">
    <div class="stat-info">
      <h3 id="stat-ditolak-count"><?php echo $stats['ditolak']; ?></h3>
      <p>Ditolak</p>
    </div>
    <div class="stat-icon">
      <i class="fas fa-times-circle"></i>
    </div>
  </div>

  <div class="stat-card total" id="stat-total">
    <div class="stat-info">
      <h3 id="stat-total-count"><?php echo $stats['total']; ?></h3>
      <p>Total</p>
    </div>
    <div class="stat-icon">
      <i class="fas fa-list"></i>
    </div>
  </div>

  <div class="stat-card dikembalikan" id="stat-dikembalikan">
    <div class="stat-info">
      <h3 id="stat-dikembalikan-count"><?php echo $stats['dikembalikan']; ?></h3>
      <p>Dikembalikan</p>
    </div>
    <div class="stat-icon">
      <i class="fas fa-reply"></i>
    </div>
  </div>
</div>

<!-- Ketersediaan Peralatan -->
<div class="labor-section">
  <div class="section-header">
    <h2><i class="fas fa-cube"></i> Ketersediaan Peralatan</h2>
  </div>
  <div class="availability-info">
    <span>Total Peralatan: <strong><?php echo $labor_stat['total_barang']; ?></strong></span>
    <span>Tersedia: <strong style="color: #4caf50;"><?php echo $labor_stat['barang_tersedia']; ?></strong></span>
    <span><?php echo round(($labor_stat['barang_tersedia'] / max($labor_stat['total_barang'], 1)) * 100); ?>% Siap Digunakan</span>
  </div>
  <div class="progress-bar">
    <div class="progress-fill" style="width: <?php echo round(($labor_stat['barang_tersedia'] / max($labor_stat['total_barang'], 1)) * 100); ?>%"></div>
  </div>
</div>

<!-- Daftar Labor -->
<div class="labor-section">
  <div class="section-header">
    <h2><i class="fas fa-flask"></i> Daftar Labor Tersedia</h2>
  </div>

  <div class="labor-grid">
    <?php while($labor = mysqli_fetch_assoc($labor_list)): ?>
      <?php
        $barang_count = mysqli_num_rows(mysqli_query($conn, "SELECT id FROM barang_labor WHERE labor_id=".$labor['id']));
      ?>
      <div class="labor-card" onclick="loadPage('labor')">
        <div class="labor-header">
          <?php if(!empty($labor['image'])): ?>
            <img src="../assets/uploads/labor/<?php echo htmlspecialchars($labor['image']); ?>" alt="<?php echo htmlspecialchars($labor['nama']); ?>" style="width: 100%; height: 100%; object-fit: cover; position: relative; z-index: 1;">
          <?php else: ?>
            <i class="fas fa-<?php echo htmlspecialchars($labor['icon']); ?>"></i>
          <?php endif; ?>
        </div>
        <div class="labor-body">
          <div class="labor-title"><?php echo htmlspecialchars($labor['nama']); ?></div>
          <div class="labor-desc"><?php echo htmlspecialchars($labor['deskripsi']); ?></div>
          <div style="font-size: 12px; color: #667eea; font-weight: 600; margin-bottom: 12px;">
            <i class="fas fa-box"></i> <?php echo $barang_count; ?> Peralatan
          </div>
          <button type="button" class="labor-button" onclick="loadPage('peminjaman')">
            <i class="fas fa-plus-circle"></i> Detail Labor
          </button>
        </div>
      </div>
    <?php endwhile; ?>
  </div>
</div>

<!-- Notifikasi Terbaru -->
<div class="notif-widget" id="notif-widget">
  <h5><i class="fas fa-bell"></i> Notifikasi Terbaru</h5>
  <div id="notif-list">
    <?php if(mysqli_num_rows($notifs_q) > 0): ?>
      <?php while($n = mysqli_fetch_assoc($notifs_q)): ?>
        <div class="notif-item <?php echo !$n['is_read'] ? 'unread' : ''; ?>">
          <div class="notif-item-title">
            <i class="fas fa-<?php echo !$n['is_read'] ? 'star' : 'check'; ?>"></i> 
            <?php echo htmlspecialchars($n['title']); ?>
          </div>
          <div style="color: #666; margin: 5px 0;"><?php echo htmlspecialchars(substr($n['message'], 0, 60)); ?></div>
          <div class="notif-item-time"><i class="fas fa-clock"></i> <?php echo date('d M Y • H:i', strtotime($n['created_at'])); ?></div>
        </div>
      <?php endwhile; ?>
    <?php else: ?>
      <div style="text-align: center; padding: 20px; color: #999;">
        <i class="fas fa-inbox" style="font-size: 24px; margin-bottom: 10px; display: block;"></i>
        Belum ada notifikasi
      </div>
    <?php endif; ?>
  </div>
  <div style="text-align: center; margin-top: 15px;">
    <button type="button" class="btn btn-sm btn-outline-primary" onclick="loadPage('notifikasi')">
      <i class="fas fa-envelope"></i> Lihat Semua <span id="unread-badge"><?php echo $unread_count > 0 ? '('.$unread_count.' baru)' : ''; ?></span>
    </button>
  </div>
</div>

<script>
  function navigateToPage(page) {
    const pageMap = {
      'dashboard': 'dashboard-content.php',
      'labor': 'labor-content.php',
      'peminjaman': 'peminjaman-content.php',
      'riwayat': 'riwayat-content.php',
      'profil': 'profil-content.php',
      'notifikasi': 'notifikasi-content.php'
    };
    
    const filename = pageMap[page] || 'dashboard-content.php';
    window.location.href = filename;
  }

  function markAsRead(notifId) {
    const formData = new FormData();
    formData.append('mark_read', 1);
    formData.append('notif_id', notifId);
    
    fetch(window.location.href, {
      method: 'POST',
      body: formData
    }).then(() => {
      navigateToPage('dashboard');
    });
  }
</script>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
  // AJAX untuk refresh dashboard data setiap 5 detik
  function updateDashboardData() {
    fetch('../config/dashboard_ajax.php', {
      method: 'GET'
    })
    .then(response => response.json())
    .then(data => {
      // Update statistik
      updateStatValue('stat-menunggu-count', data.stats.menunggu);
      updateStatValue('stat-disetujui-count', data.stats.disetujui);
      updateStatValue('stat-ditolak-count', data.stats.ditolak);
      updateStatValue('stat-total-count', data.stats.total);
      updateStatValue('stat-dikembalikan-count', data.stats.dikembalikan);
      
      // Update notifikasi
      updateNotifications(data.notifications, data.unread_count);
    })
    .catch(error => console.error('Error updating dashboard:', error));
  }

  function updateStatValue(elementId, newValue) {
    const element = document.getElementById(elementId);
    if(element) {
      const currentValue = parseInt(element.textContent);
      if(currentValue !== newValue) {
        element.textContent = newValue;
      }
    }
  }

  function updateNotifications(notifications, unreadCount) {
    const notifList = document.getElementById('notif-list');
    const unreadBadge = document.getElementById('unread-badge');
    
    if(notifications.length === 0) {
      notifList.innerHTML = `
        <div style="text-align: center; padding: 20px; color: #999;">
          <i class="fas fa-inbox" style="font-size: 24px; margin-bottom: 10px; display: block;"></i>
          Belum ada notifikasi
        </div>
      `;
    } else {
      let html = '';
      notifications.forEach(notif => {
        html += `
          <div class="notif-item ${!notif.is_read ? 'unread' : ''}">
            <div class="notif-item-title">
              <i class="fas fa-${!notif.is_read ? 'star' : 'check'}"></i> 
              ${escapeHtml(notif.title)}
            </div>
            <div style="color: #666; margin: 5px 0;">${escapeHtml(notif.message.substring(0, 60))}</div>
            <div class="notif-item-time"><i class="fas fa-clock"></i> ${notif.created_at}</div>
          </div>
        `;
      });
      notifList.innerHTML = html;
    }
    
    // Update badge
    if(unreadCount > 0) {
      unreadBadge.textContent = '(' + unreadCount + ' baru)';
    } else {
      unreadBadge.textContent = '';
    }
  }

  function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
  }

  // Add pulse animation
  const style = document.createElement('style');
  style.textContent = `
    @keyframes pulse {
      0% { transform: scale(1); }
      50% { transform: scale(1.05); color: #667eea; }
      100% { transform: scale(1); }
    }
  `;
  document.head.appendChild(style);

  // Initial update and set interval
  document.addEventListener('DOMContentLoaded', function() {
    updateDashboardData();
    setInterval(updateDashboardData, 5000); // Update setiap 5 detik
  });

  function loadPage(page) {
    let url = '';
    switch(page) {
      case 'dashboard':
        url = 'pages/dashboard-content.php';
        break;
      case 'labor':
        url = '../pages/labor-content.php';
        break;
      case 'peminjaman':
        url = 'pages/peminjaman-content.php';
        break;
      case 'riwayat':
        url = 'pages/riwayat-content.php';
        break;
      case 'profil':
        url = 'pages/profil-content.php';
        break;
      case 'notifikasi':
        url = '../pages/notifikasi-content.php';
        break;
      default:
        url = 'pages/dashboard-content.php';
    }
    
    window.location.href = url;
  }
</script>

</body>
</html>