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
<title>Notifikasi - Aplikasi Labor</title>
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

if(isset($_POST['mark_read']) && isset($_POST['notif_id'])){
  $nid = mysqli_real_escape_string($conn, $_POST['notif_id']);
  mysqli_query($conn, "UPDATE notifications SET is_read=1 WHERE id='$nid' AND user_id='".intval($_SESSION['user_id'])."'");
}

// Ensure recipient_type column exists
$check_recipient = mysqli_query($conn, "SHOW COLUMNS FROM notifications LIKE 'recipient_type'");
if(mysqli_num_rows($check_recipient) == 0){
  mysqli_query($conn, "ALTER TABLE notifications ADD COLUMN recipient_type VARCHAR(20) DEFAULT 'user' AFTER user_id");
}

if(isset($_POST['delete_notif']) && isset($_POST['notif_id'])){
  $nid = mysqli_real_escape_string($conn, $_POST['notif_id']);
  mysqli_query($conn, "DELETE FROM notifications WHERE id='$nid' AND user_id='".intval($_SESSION['user_id'])."'");
}

$notifs_q = mysqli_query($conn, "SELECT * FROM notifications WHERE user_id='".intval($_SESSION['user_id'])."' AND (recipient_type='user' OR recipient_type IS NULL OR recipient_type='') ORDER BY created_at DESC");
?>

<style>
  .notifikasi-container {
    background: white;
    border-radius: 12px;
    padding: 30px;
    box-shadow: 0 2px 15px rgba(0,0,0,0.08);
    animation: slideUp 0.5s ease-out;
  }

  .notifikasi-header {
    display: flex;
    align-items: center;
    margin-bottom: 30px;
    padding-bottom: 20px;
    border-bottom: 2px solid #f0f0f0;
  }

  .notifikasi-header i {
    font-size: 32px;
    color: #667eea;
    margin-right: 15px;
    animation: bounce 2s ease-in-out infinite;
  }

  .notifikasi-header h3 {
    margin: 0;
    color: #333;
    font-weight: 700;
    font-size: 24px;
  }

  .notifikasi-list {
    display: flex;
    flex-direction: column;
    gap: 15px;
  }

  .notif-card {
    border-left: 4px solid #667eea;
    padding: 20px;
    background: #f8f9ff;
    border-radius: 8px;
    transition: all 0.3s ease;
    animation: fadeInUp 0.5s ease-out;
  }

  .notif-card.unread {
    background: linear-gradient(135deg, #fffaf0 0%, #fff5e6 100%);
    border-left-color: #ff9800;
  }

  .notif-card:hover {
    background: #f0f2ff;
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.15);
    transform: translateX(5px);
  }

  @keyframes fadeOut {
    from {
      opacity: 1;
      transform: translateX(0);
    }
    to {
      opacity: 0;
      transform: translateX(-20px);
    }
  }

  .notif-card.removing {
    animation: fadeOut 0.3s ease-out;
  }

  .notif-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 12px;
  }

  .notif-title {
    font-weight: 700;
    color: #333;
    font-size: 16px;
  }

  .notif-time {
    font-size: 12px;
    color: #999;
  }

  .notif-message {
    font-size: 14px;
    color: #666;
    margin-bottom: 15px;
    line-height: 1.6;
  }

  .notif-actions {
    display: flex;
    gap: 8px;
    justify-content: flex-end;
  }

  .notif-actions button {
    padding: 6px 12px;
    border-radius: 6px;
    border: none;
    font-size: 12px;
    font-weight: 600;
    transition: all 0.2s ease;
    cursor: pointer;
  }

  .btn-mark {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
  }

  .btn-mark:hover {
    transform: translateY(-2px);
    box-shadow: 0 3px 10px rgba(102, 126, 234, 0.3);
    color: white;
  }

  .btn-delete {
    background: #f0f0f0;
    color: #dc3545;
  }

  .btn-delete:hover {
    background: #dc3545;
    color: white;
  }

  .empty-message {
    text-align: center;
    padding: 60px 20px;
  }

  .empty-message i {
    font-size: 80px;
    color: #ddd;
    margin-bottom: 20px;
    display: block;
    animation: float 3s ease-in-out infinite;
  }

  .empty-message p {
    color: #999;
    font-size: 18px;
  }

  .btn-back {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border: none;
    padding: 10px 20px;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    margin-top: 20px;
    transition: all 0.3s ease;
  }

  .btn-back:hover {
    color: white;
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
  }
</style>

<div class="notifikasi-container">
  <div class="notifikasi-header">
    <i class="fas fa-bell"></i>
    <h3>Semua Notifikasi</h3>
  </div>

  <?php if(mysqli_num_rows($notifs_q) > 0): ?>
    <div class="notifikasi-list">
      <?php while($n = mysqli_fetch_assoc($notifs_q)): ?>
        <div class="notif-card <?php echo !$n['is_read'] ? 'unread' : ''; ?>" data-notif-id="<?php echo $n['id']; ?>">
          <div class="notif-header">
            <div class="notif-title">
              <i class="fas fa-<?php echo !$n['is_read'] ? 'star' : 'check'; ?>"></i> 
              <?php echo htmlspecialchars($n['title']); ?>
            </div>
            <div class="notif-time">
              <i class="fas fa-clock"></i> <?php echo date('d M Y • H:i', strtotime($n['created_at'])); ?>
            </div>
          </div>
          <div class="notif-message">
            <?php echo nl2br(htmlspecialchars($n['message'])); ?>
          </div>
          <div class="notif-actions">
            <?php if(!$n['is_read']): ?>
            <button type="button" class="btn-mark" onclick="markAsRead(<?php echo $n['id']; ?>)">
              <i class="fas fa-check"></i> Tandai dibaca
            </button>
            <?php endif; ?>
            <button type="button" class="btn-delete" onclick="deleteNotif(<?php echo $n['id']; ?>)">
              <i class="fas fa-trash"></i> Hapus
            </button>
          </div>
        </div>
      <?php endwhile; ?>
    </div>
  <?php else: ?>
    <div class="empty-message">
      <i class="fas fa-inbox"></i>
      <p>Tidak ada notifikasi</p>
    </div>
  <?php endif; ?>
</div>

<script>
  function markAsRead(notifId) {
    const notifCard = document.querySelector(`[data-notif-id="${notifId}"]`);
    
    const formData = new FormData();
    formData.append('mark_read', 1);
    formData.append('notif_id', notifId);
    
    fetch(window.location.href, {
      method: 'POST',
      body: formData
    }).then(response => {
      if(response.ok) {
        // Remove unread class dan update display
        notifCard.classList.remove('unread');
        
        // Update button (hide tandai dibaca button)
        const btn = notifCard.querySelector('.btn-mark');
        if(btn) btn.style.display = 'none';
        
        // Update icon
        const icon = notifCard.querySelector('.notif-title i');
        if(icon) {
          icon.classList.remove('fa-star');
          icon.classList.add('fa-check');
        }
        
        // Update sidebar badge
        updateNotificationBadge();
      }
    }).catch(error => console.error('Error:', error));
  }

  function deleteNotif(notifId) {
    if(confirm('Yakin ingin menghapus notifikasi ini?')) {
      const notifCard = document.querySelector(`[data-notif-id="${notifId}"]`);
      
      const formData = new FormData();
      formData.append('delete_notif', 1);
      formData.append('notif_id', notifId);
      
      fetch(window.location.href, {
        method: 'POST',
        body: formData
      }).then(response => {
        if(response.ok) {
          // Animate remove
          notifCard.classList.add('removing');
          setTimeout(() => {
            notifCard.remove();
            
            // Check if no notifications left
            const listContainer = document.querySelector('.notifikasi-list');
            if(listContainer && listContainer.children.length === 0) {
              location.reload();
            }
            
            // Update sidebar badge
            updateNotificationBadge();
          }, 300);
        }
      }).catch(error => console.error('Error:', error));
    }
  }

  function updateNotificationBadge() {
    // Update badge di sidebar
    const badge = document.querySelector('.notif-badge');
    if(badge) {
      const unreadCount = document.querySelectorAll('.notif-card.unread').length;
      if(unreadCount > 0) {
        badge.textContent = unreadCount;
        badge.style.display = 'flex';
      } else {
        badge.style.display = 'none';
      }
    }
  }

  // Request notification permission
  document.addEventListener('DOMContentLoaded', function() {
    updateNotificationBadge();
    
    if('Notification' in window && Notification.permission === 'default') {
      Notification.requestPermission();
    }
  });
</script>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
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
</script>

</body>
</html>