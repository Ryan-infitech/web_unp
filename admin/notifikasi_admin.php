<?php
// notifikasi_admin.php - Page untuk admin lihat dan manage notifikasi mereka
session_start();
include '../config/database.php';

if(!isset($_SESSION['admin_id'])){
  header("Location: login.php");
  exit;
}

$admin_id = intval($_SESSION['admin_id']);

// Auto-mark all unread notifications as read when page is opened
mysqli_query($conn, "UPDATE notifications SET is_read = 1 WHERE user_id = $admin_id");

// Get admin notifikasi (unread first) - hanya untuk admin ini
$notif_q = mysqli_query($conn, 
  "SELECT * FROM notifications WHERE user_id = $admin_id ORDER BY is_read ASC, created_at DESC LIMIT 20"
);

// Get total dan unread count
$total_notif = mysqli_fetch_assoc(
  mysqli_query($conn, "SELECT COUNT(*) as count FROM notifications WHERE user_id = $admin_id")
)['count'];

$unread_count = mysqli_fetch_assoc(
  mysqli_query($conn, "SELECT COUNT(*) as count FROM notifications WHERE user_id = $admin_id AND is_read = 0")
)['count'];

?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Notifikasi Admin</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    :root {
      --primary: #f59e0b;
      --success: #22c55e;
      --danger: #ef4444;
      --bg-primary: #ffffff;
      --bg-secondary: #f8fafc;
      --text-primary: #0f172a;
      --text-secondary: #64748b;
      --border-color: #e2e8f0;
      --shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    }

    body {
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      background: linear-gradient(135deg, #f59e0b15 0%, #fb923c15 100%);
    }

    .main-content {
      padding: 40px 30px;
      min-height: 100vh;
      margin-left: 250px;
    }

    .page-header h1 {
      font-size: 28px;
      font-weight: 700;
      display: flex;
      align-items: center;
      gap: 12px;
      margin-bottom: 30px;
    }

    .page-header h1 i {
      color: var(--primary);
      font-size: 32px;
    }

    .notif-container {
      background: var(--bg-primary);
      border-radius: 12px;
      padding: 25px;
      box-shadow: var(--shadow);
      max-width: 700px;
    }

    .notif-item {
      padding: 15px;
      border: 2px solid var(--border-color);
      border-radius: 8px;
      margin-bottom: 12px;
      transition: all 0.3s;
      display: flex;
      gap: 15px;
      align-items: flex-start;
    }

    .notif-item:hover {
      border-color: var(--primary);
      background: var(--bg-secondary);
    }

    .notif-item.unread {
      border-left: 4px solid var(--primary);
      background: rgba(245, 158, 11, 0.05);
      border-color: var(--primary);
    }

    .notif-icon {
      font-size: 20px;
      color: var(--primary);
      flex-shrink: 0;
      margin-top: 2px;
    }

    .notif-content {
      flex: 1;
      min-width: 0;
    }

    .notif-title {
      font-weight: 700;
      color: var(--text-primary);
      margin-bottom: 5px;
      font-size: 14px;
    }

    .notif-message {
      font-size: 13px;
      color: var(--text-secondary);
      line-height: 1.5;
      margin-bottom: 8px;
    }

    .notif-time {
      font-size: 12px;
      color: var(--text-secondary);
    }

    .notif-actions {
      display: flex;
      gap: 6px;
      flex-shrink: 0;
    }

    .notif-btn {
      width: 28px;
      height: 28px;
      border: none;
      border-radius: 6px;
      background: transparent;
      color: var(--text-secondary);
      cursor: pointer;
      transition: all 0.2s;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 13px;
    }

    .notif-btn:hover {
      background: rgba(245, 158, 11, 0.1);
      color: var(--primary);
    }

    .notif-btn.delete:hover {
      background: rgba(239, 68, 68, 0.1);
      color: var(--danger);
    }

    .mark-all-btn {
      background: linear-gradient(135deg, var(--primary) 0%, #d97706 100%);
      color: white;
      border: none;
      padding: 10px 20px;
      border-radius: 8px;
      font-weight: 600;
      font-size: 13px;
      cursor: pointer;
      margin-bottom: 20px;
      transition: all 0.3s;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .mark-all-btn:hover {
      transform: translateY(-2px);
      box-shadow: 0 6px 20px rgba(245, 158, 11, 0.3);
    }

    .mark-all-btn:disabled {
      opacity: 0.5;
      cursor: not-allowed;
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

    @media (max-width: 768px) {
      .main-content {
        margin-left: 70px;
        padding: 20px 15px;
      }

      .notif-container {
        max-width: 100%;
      }
    }
  </style>
</head>
<body>

<?php include '../assets/admin_sidebar.php'; ?>

<div class="main-content">
  <div class="page-header">
    <h1>
      <i class="fas fa-bell"></i>
      Notifikasi Saya
    </h1>
  </div>

<div style="margin-bottom: 20px;">
  <a href="notifikasi.php" class="btn btn-secondary btn-sm">
    <i class="fas fa-arrow-left"></i> Kembali
  </a>
</div>


  <div class="notif-container">
    <div id="notificationsContainer">
      <?php if(mysqli_num_rows($notif_q) > 0): ?>
        <?php while($notif = mysqli_fetch_assoc($notif_q)): 
          // Semua notifikasi otomatis dibaca saat page dimuat
          $is_unread = '';
          $created = new DateTime($notif['created_at']);
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
        ?>
          <div class="notif-item <?php echo $is_unread; ?>" data-notif-id="<?php echo $notif['id']; ?>">
            <div class="notif-icon">
              <i class="fas fa-bell"></i>
            </div>
            <div class="notif-content">
              <div class="notif-title"><?php echo htmlspecialchars($notif['title']); ?></div>
              <div class="notif-message"><?php echo htmlspecialchars($notif['message']); ?></div>
              <div class="notif-time"><?php echo $time_text; ?></div>
            </div>
            <div class="notif-actions">
              <button class="notif-btn delete" onclick="deleteNotif(<?php echo $notif['id']; ?>)" title="Hapus">
                <i class="fas fa-trash"></i>
              </button>
            </div>
          </div>
        <?php endwhile; ?>
      <?php else: ?>
        <div class="empty-state">
          <i class="fas fa-inbox"></i>
          <p>Tidak ada notifikasi</p>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<script>
function markAsRead(notifId) {
  fetch('../config/mark_admin_notification_read.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/x-www-form-urlencoded',
    },
    body: 'notif_id=' + notifId
  })
  .then(response => response.json())
  .then(data => {
    if(data.success) {
      const item = document.querySelector(`[data-notif-id="${notifId}"]`);
      if(item) {
        item.classList.remove('unread');
        const btn = item.querySelector('.check-btn');
        if(btn) btn.style.display = 'none';
      }
      updateBadge();
    }
  })
  .catch(error => console.error('Error:', error));
}

function markAllAsRead() {
  const items = document.querySelectorAll('.notif-item.unread');
  if(items.length === 0) {
    alert('Semua notifikasi sudah dibaca');
    return;
  }

  items.forEach(item => {
    const notifId = item.getAttribute('data-notif-id');
    fetch('../config/mark_admin_notification_read.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded',
      },
      body: 'notif_id=' + notifId
    })
    .then(response => response.json())
    .then(data => {
      if(data.success) {
        item.classList.remove('unread');
        const btn = item.querySelector('.check-btn');
        if(btn) btn.style.display = 'none';
      }
    });
  });

  setTimeout(updateBadge, 500);
}

function deleteNotif(notifId) {
  if(confirm('Hapus notifikasi ini?')) {
    fetch('../config/mark_admin_notification_read.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded',
      },
      body: 'delete_id=' + notifId
    })
    .then(response => response.json())
    .then(data => {
      if(data.success) {
        const item = document.querySelector(`[data-notif-id="${notifId}"]`);
        if(item) {
          item.style.animation = 'slideOut 0.3s ease-out';
          setTimeout(() => item.remove(), 300);
        }
        updateBadge();
      }
    })
    .catch(error => console.error('Error:', error));
  }
}

function updateBadge() {
  // Panggil fungsi update badge dari sidebar
  if(window.parent && window.parent.updateAdminNotificationBadge) {
    window.parent.updateAdminNotificationBadge();
  }
}

// Add animation
const style = document.createElement('style');
style.textContent = `
  @keyframes slideOut {
    from {
      opacity: 1;
      transform: translateX(0);
    }
    to {
      opacity: 0;
      transform: translateX(100px);
    }
  }
`;
document.head.appendChild(style);
</script>

</body>
</html>
