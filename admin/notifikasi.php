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

// Ensure recipient_type column exists
$check_recipient = mysqli_query($conn, "SHOW COLUMNS FROM notifications LIKE 'recipient_type'");
if(mysqli_num_rows($check_recipient) == 0){
  mysqli_query($conn, "ALTER TABLE notifications ADD COLUMN recipient_type VARCHAR(20) DEFAULT 'user' AFTER user_id");
}

$message = '';
$message_type = '';

// Handle Send Notification
if(isset($_POST['send_notification'])){
  $title = mysqli_real_escape_string($conn, $_POST['title']);
  $message_content = mysqli_real_escape_string($conn, $_POST['message']);
  $send_to = isset($_POST['send_to']) ? $_POST['send_to'] : [];
  $send_to_all = isset($_POST['send_to_all']) ? $_POST['send_to_all'] : 0;
  
  if(empty($title) || empty($message_content)){
    $message = "Judul dan pesan tidak boleh kosong!";
    $message_type = "danger";
  } else {
    $user_ids = [];
    
    if($send_to_all == 1){
      // Send to all users
      $result = mysqli_query($conn, "SELECT id FROM users WHERE is_active = 1");
      while($row = mysqli_fetch_assoc($result)){
        $user_ids[] = $row['id'];
      }
    } else {
      // Send to selected users
      if(empty($send_to)){
        $message = "Silakan pilih setidaknya satu user atau pilih 'Kirim ke Seluruh User'!";
        $message_type = "danger";
      } else {
        $user_ids = $send_to;
      }
    }
    
    if(!empty($user_ids)){
      $success_count = 0;
      $fail_count = 0;
      
      foreach($user_ids as $user_id){
        $user_id = intval($user_id);
        $insert = mysqli_query($conn, "INSERT INTO notifications (user_id, recipient_type, title, message, is_read, created_at) VALUES ($user_id, 'user', '$title', '$message_content', 0, NOW())");
        
        if($insert){
          $success_count++;
        } else {
          $fail_count++;
        }
      }
      
      if($fail_count == 0){
        $message = "Notifikasi berhasil dikirim ke " . $success_count . " user!";
        $message_type = "success";
      } else {
        $message = "Notifikasi dikirim ke " . $success_count . " user, gagal untuk " . $fail_count . " user.";
        $message_type = "warning";
      }
    }
  }
}

// Get total users
$total_users = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM users WHERE is_active = 1"))['total'] ?? 0;

// Get statistics - hanya untuk admin yang login
$total_notifications_sent = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM notifications WHERE user_id = $id"))['total'] ?? 0;
$unread_notifications = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM notifications WHERE user_id = $id AND is_read = 0"))['total'] ?? 0;

// Get incoming requests count
$incoming_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM peminjaman WHERE status = 'Menunggu' OR (status = 'Disetujui' AND status_pengembalian = 0)"))['total'] ?? 0;

// Get latest notifications
$latest_notifications = mysqli_query($conn, "SELECT n.*, u.nama, u.email FROM notifications n LEFT JOIN users u ON n.user_id = u.id ORDER BY n.created_at DESC LIMIT 10");

?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Kirim Notifikasi</title>
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

    .alert-warning {
      background: rgba(234, 179, 8, 0.1);
      border: 2px solid var(--warning);
      color: var(--warning);
    }

    .alert-custom i {
      font-size: 18px;
    }

    /* Stats Row */
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

    .stat-card a {
      text-decoration: none;
      color: inherit;
      cursor: pointer;
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

    .stat-card-badge {
      position: absolute;
      top: -8px;
      right: -8px;
      min-width: 24px;
      height: 24px;
      background: #ff4444;
      color: white;
      border-radius: 50%;
      font-size: 12px;
      font-weight: 700;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 0 6px;
      box-shadow: 0 2px 8px rgba(255, 68, 68, 0.3);
      animation: badge-pulse 2s infinite;
    }

    @keyframes badge-pulse {
      0%, 100% { transform: scale(1); }
      50% { transform: scale(1.15); }
    }

    /* Main Content Grid */
    .content-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 30px;
      margin-top: 30px;
    }

    /* Form Section */
    .form-section {
      background: var(--bg-primary);
      border-radius: 12px;
      padding: 25px;
      box-shadow: var(--shadow);
    }

    .form-section h3 {
      font-size: 18px;
      font-weight: 700;
      color: var(--text-primary);
      margin-bottom: 20px;
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .form-section h3 i {
      color: var(--primary);
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

    .form-group input,
    .form-group textarea,
    .form-group select {
      width: 100%;
      padding: 12px 15px;
      border: 2px solid var(--border-color);
      border-radius: 8px;
      font-size: 14px;
      transition: all 0.3s;
      background: var(--bg-secondary);
      color: var(--text-primary);
      font-family: inherit;
    }

    .form-group input:focus,
    .form-group textarea:focus,
    .form-group select:focus {
      outline: none;
      border-color: var(--primary);
      box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.1);
    }

    .form-group textarea {
      resize: vertical;
      min-height: 120px;
    }

    /* Selected Users List */
    .selected-users {
      background: var(--bg-secondary);
      border-radius: 8px;
      padding: 12px;
      margin-top: 10px;
      max-height: 200px;
      overflow-y: auto;
      display: flex;
      flex-wrap: wrap;
      gap: 8px;
    }

    .user-chip {
      background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
      color: white;
      padding: 6px 12px;
      border-radius: 20px;
      font-size: 12px;
      display: flex;
      align-items: center;
      gap: 6px;
      font-weight: 600;
    }

    .user-chip .remove {
      cursor: pointer;
      font-weight: bold;
      margin-left: 2px;
    }

    .user-chip .remove:hover {
      opacity: 0.8;
    }

    /* Search Suggestions */
    .search-suggestions {
      position: absolute;
      top: 100%;
      left: 0;
      right: 0;
      background: var(--bg-primary);
      border: 2px solid var(--border-color);
      border-top: none;
      border-radius: 0 0 8px 8px;
      max-height: 250px;
      overflow-y: auto;
      z-index: 1000;
      display: none;
      box-shadow: var(--shadow);
    }

    .search-suggestions.active {
      display: block;
    }

    .suggestion-item {
      padding: 12px 15px;
      border-bottom: 1px solid var(--border-color);
      cursor: pointer;
      transition: all 0.2s;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }

    .suggestion-item:hover {
      background: var(--bg-secondary);
      color: var(--primary);
    }

    .suggestion-item.selected {
      background: rgba(245, 158, 11, 0.1);
      color: var(--primary);
      font-weight: 600;
    }

    .suggestion-item:last-child {
      border-bottom: none;
    }

    /* Buttons */
    .btn-group {
      display: flex;
      gap: 12px;
      margin-top: 25px;
    }

    .btn-send {
      flex: 1;
      background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
      color: white;
      border: none;
      padding: 12px 20px;
      border-radius: 8px;
      font-weight: 600;
      font-size: 14px;
      cursor: pointer;
      transition: all 0.3s;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
    }

    .btn-send:hover {
      transform: translateY(-2px);
      box-shadow: 0 6px 20px rgba(245, 158, 11, 0.3);
    }

    .btn-send:active {
      transform: translateY(0);
    }

    .btn-reset {
      flex: 1;
      background: var(--bg-secondary);
      color: var(--text-primary);
      border: 2px solid var(--border-color);
      padding: 12px 20px;
      border-radius: 8px;
      font-weight: 600;
      font-size: 14px;
      cursor: pointer;
      transition: all 0.3s;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
    }

    .btn-reset:hover {
      border-color: var(--primary);
      color: var(--primary);
    }

    /* Recipient Options */
    .recipient-options {
      display: flex;
      gap: 15px;
      align-items: center;
      margin-bottom: 20px;
      padding-bottom: 20px;
      border-bottom: 2px solid var(--border-color);
    }

    .option-radio {
      display: flex;
      align-items: center;
      gap: 8px;
      cursor: pointer;
      user-select: none;
    }

    .option-radio input[type="radio"] {
      width: 18px;
      height: 18px;
      cursor: pointer;
    }

    .option-radio label {
      margin: 0;
      cursor: pointer;
      font-weight: 500;
    }

    /* Search Wrapper */
    .search-wrapper {
      position: relative;
    }

    .search-wrapper input {
      width: 100%;
    }

    /* Notifications History */
    .notifications-section {
      background: var(--bg-primary);
      border-radius: 12px;
      padding: 25px;
      box-shadow: var(--shadow);
    }

    .notifications-section h3 {
      font-size: 18px;
      font-weight: 700;
      color: var(--text-primary);
      margin-bottom: 20px;
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .notifications-section h3 i {
      color: var(--primary);
    }

    .notification-item {
      padding: 15px;
      border: 2px solid var(--border-color);
      border-radius: 8px;
      margin-bottom: 12px;
      transition: all 0.3s;
    }

    .notification-item:hover {
      border-color: var(--primary);
      background: var(--bg-secondary);
    }

    .notification-item.unread {
      border-left: 4px solid var(--primary);
      background: rgba(245, 158, 11, 0.05);
    }

    .notification-header {
      display: flex;
      justify-content: space-between;
      align-items: start;
      margin-bottom: 8px;
    }

    .notification-title {
      font-weight: 700;
      color: var(--text-primary);
      font-size: 14px;
    }

    .notification-time {
      font-size: 12px;
      color: var(--text-secondary);
    }

    .notification-recipient {
      font-size: 13px;
      color: var(--text-secondary);
      margin-bottom: 8px;
      display: flex;
      align-items: center;
      gap: 6px;
    }

    .notification-message {
      font-size: 13px;
      color: var(--text-primary);
      line-height: 1.5;
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

    /* Animations */
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

    /* Responsive */
    @media (max-width: 768px) {
      .main-content {
        margin-left: 70px;
        padding: 20px 15px;
      }

      .content-grid {
        grid-template-columns: 1fr;
      }

      .stats-row {
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      }

      body.sidebar-collapsed .main-content {
        margin-left: 70px;
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
      <i class="fas fa-bell"></i>
      Kirim Notifikasi
    </h1>
  </div>

  <!-- Alert Messages -->
  <?php if(!empty($message)): ?>
    <div class="alert-custom alert-<?php echo $message_type; ?>">
      <i class="fas fa-<?php echo $message_type === 'success' ? 'check-circle' : ($message_type === 'danger' ? 'exclamation-circle' : 'exclamation-triangle'); ?>"></i>
      <span><?php echo $message; ?></span>
    </div>
  <?php endif; ?>

  <!-- Statistics Cards -->
  <div class="stats-row">
    <a href="notifikasi_admin.php" style="text-decoration: none;">
      <div class="stat-card" style="position: relative;">
        <div class="stat-card-icon"><i class="fas fa-bell"></i></div>
        <div class="stat-card-value"><?php echo $total_notifications_sent; ?></div>
        <div class="stat-card-label">Notifikasi Saya</div>
        <div class="stat-card-badge" id="myNotifBadge" style="display: <?php echo $unread_notifications > 0 ? 'flex' : 'none'; ?>"><?php echo $unread_notifications; ?></div>
      </div>
    </a>
    <a href="notifikasi_masuk.php" style="text-decoration: none;">
      <div class="stat-card" style="position: relative;">
        <div class="stat-card-icon"><i class="fas fa-inbox"></i></div>
        <div class="stat-card-value" id="incomingNotifValue"><?php echo $incoming_count; ?></div>
        <div class="stat-card-label">Notifikasi Masuk</div>
        <div class="stat-card-badge" id="incomingNotifBadge" style="display: <?php echo $incoming_count > 0 ? 'flex' : 'none'; ?>"><?php echo $incoming_count; ?></div>
      </div>
    </a>
    <a href="notification_logs.php" style="text-decoration: none;">
      <div class="stat-card">
        <div class="stat-card-icon"><i class="fas fa-envelope-circle-check"></i></div>
        <div class="stat-card-value" style="color: var(--success);">Log</div>
        <div class="stat-card-label">Log Mailer</div>
      </div>
    </a>
  </div>

  <!-- Main Content Grid -->
  <div class="content-grid">
    <!-- Form Section -->
    <div class="form-section">
      <h3><i class="fas fa-envelope-open"></i> Buat Notifikasi Baru</h3>
      
      <form method="POST" id="notificationForm">
        <input type="hidden" name="send_notification" value="1">

        <!-- Recipient Options -->
        <div class="recipient-options">
          <div class="option-radio">
            <input type="radio" id="sendSelected" name="recipient_type" value="selected" checked>
            <label for="sendSelected">Pilih User</label>
          </div>
          <div class="option-radio">
            <input type="radio" id="sendAll" name="recipient_type" value="all">
            <label for="sendAll">Kirim ke Semua</label>
          </div>
        </div>

        <!-- User Selection -->
        <div id="userSelectionDiv" style="display: block;">
          <div class="form-group">
            <label><i class="fas fa-search"></i> Cari & Pilih User</label>
            <div class="search-wrapper">
              <input 
                type="text" 
                id="userSearch" 
                placeholder="Ketik nama atau email..."
                autocomplete="off"
              >
              <div class="search-suggestions" id="searchSuggestions"></div>
            </div>
          </div>

          <div class="form-group">
            <label><i class="fas fa-users-check"></i> User Terpilih</label>
            <div class="selected-users" id="selectedUsers">
              <p style="color: var(--text-secondary); font-size: 13px; margin: 0;">Belum ada user yang dipilih</p>
            </div>
          </div>
        </div>

        <!-- Message Fields -->
        <div class="form-group">
          <label><i class="fas fa-heading"></i> Judul Notifikasi</label>
          <input 
            type="text" 
            name="title" 
            placeholder="Masukkan judul notifikasi..."
            required
          >
        </div>

        <div class="form-group">
          <label><i class="fas fa-file-alt"></i> Pesan Notifikasi</label>
          <textarea 
            name="message" 
            placeholder="Masukkan pesan yang ingin dikirimkan..."
            required
          ></textarea>
        </div>

        <!-- Buttons -->
        <div class="btn-group">
          <button type="submit" class="btn-send" id="submitBtn">
            <i class="fas fa-paper-plane"></i> Kirim Notifikasi
          </button>
          <button type="reset" class="btn-reset" onclick="resetForm()">
            <i class="fas fa-redo"></i> Reset
          </button>
        </div>
      </form>
    </div>

    <!-- Notifications History -->
    <div class="notifications-section">
      <h3><i class="fas fa-history"></i> Riwayat Notifikasi</h3>
      
      <div id="notificationsHistory">
        <?php
        if(mysqli_num_rows($latest_notifications) > 0):
          while($notif = mysqli_fetch_assoc($latest_notifications)):
            $is_unread = $notif['is_read'] == 0 ? 'unread' : '';
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
          <div class="notification-item <?php echo $is_unread; ?>">
            <div class="notification-header">
              <div class="notification-title"><?php echo htmlspecialchars($notif['title']); ?></div>
              <div class="notification-time"><?php echo $time_text; ?></div>
            </div>
            <div class="notification-recipient">
              <i class="fas fa-user-circle"></i>
              <?php echo htmlspecialchars($notif['nama'] ?? 'User'); ?> (<?php echo htmlspecialchars($notif['email'] ?? '-'); ?>)
            </div>
            <div class="notification-message">
              <?php echo nl2br(htmlspecialchars(substr($notif['message'], 0, 100))); ?>
              <?php if(strlen($notif['message']) > 100): ?>...<?php endif; ?>
            </div>
          </div>
        <?php
          endwhile;
        else:
        ?>
          <div class="empty-state">
            <i class="fas fa-inbox"></i>
            <p>Belum ada notifikasi terkirim</p>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<script>
let selectedUsers = [];

// Form submission
document.getElementById('notificationForm').addEventListener('submit', function(e) {
  const recipientType = document.querySelector('input[name="recipient_type"]:checked').value;
  
  if(recipientType === 'selected' && selectedUsers.length === 0) {
    e.preventDefault();
    alert('Silakan pilih setidaknya satu user!');
    return false;
  }
  
  // Remove old hidden inputs first
  const oldInputs = document.querySelectorAll('input[name="send_to[]"]');
  oldInputs.forEach(input => {
    if(input.id !== 'selectedUsersInput') {
      input.remove();
    }
  });
  
  // Set hidden input with selected users
  if(recipientType === 'selected') {
    // Add hidden inputs for each selected user
    const form = document.getElementById('notificationForm');
    selectedUsers.forEach(userId => {
      const input = document.createElement('input');
      input.type = 'hidden';
      input.name = 'send_to[]';
      input.value = userId;
      form.appendChild(input);
    });
  } else {
    // For send to all, add indicator
    const input = document.createElement('input');
    input.type = 'hidden';
    input.name = 'send_to_all';
    input.value = '1';
    document.getElementById('notificationForm').appendChild(input);
  }
});

// Recipient type radio button
document.getElementById('sendSelected').addEventListener('change', function() {
  document.getElementById('userSelectionDiv').style.display = 'block';
});

document.getElementById('sendAll').addEventListener('change', function() {
  document.getElementById('userSelectionDiv').style.display = 'none';
  selectedUsers = [];
  updateSelectedUsersDisplay();
});

// User search
document.getElementById('userSearch').addEventListener('input', function(e) {
  const search = e.target.value.trim();
  
  if(search.length < 1) {
    document.getElementById('searchSuggestions').classList.remove('active');
    return;
  }
  
  fetch(`notifikasi_ajax.php?search=${encodeURIComponent(search)}`)
    .then(response => response.text())
    .then(data => {
      const suggestions = document.getElementById('searchSuggestions');
      suggestions.innerHTML = data;
      suggestions.classList.add('active');
    })
    .catch(error => console.error('Error:', error));
});

// Close suggestions when clicking outside
document.addEventListener('click', function(e) {
  if(!e.target.closest('.search-wrapper')) {
    document.getElementById('searchSuggestions').classList.remove('active');
  }
});

// Select user from suggestions
window.selectUser = function(userId, userName, userEmail) {
  if(!selectedUsers.includes(userId)) {
    selectedUsers.push(userId);
    updateSelectedUsersDisplay();
  }
  document.getElementById('userSearch').value = '';
  document.getElementById('searchSuggestions').classList.remove('active');
  document.getElementById('userSearch').focus();
};

// Remove user from selection
window.removeUser = function(userId) {
  selectedUsers = selectedUsers.filter(id => id !== userId);
  updateSelectedUsersDisplay();
};

// Update selected users display
function updateSelectedUsersDisplay() {
  const container = document.getElementById('selectedUsers');
  
  if(selectedUsers.length === 0) {
    container.innerHTML = '<p style="color: var(--text-secondary); font-size: 13px; margin: 0;">Belum ada user yang dipilih</p>';
    return;
  }
  
  // Fetch user names for selected IDs
  const userIds = selectedUsers.join(',');
  fetch(`notifikasi_ajax.php?get_users=${userIds}`)
    .then(response => response.text())
    .then(data => {
      container.innerHTML = data;
    })
    .catch(error => console.error('Error:', error));
}

// Reset form
function resetForm() {
  selectedUsers = [];
  updateSelectedUsersDisplay();
  document.getElementById('notificationForm').reset();
  document.getElementById('sendSelected').checked = true;
  document.getElementById('userSelectionDiv').style.display = 'block';
}

// Update incoming notification badge real-time
function updateIncomingNotificationBadge() {
  fetch('notifikasi_ajax.php?get_incoming_count=1')
    .then(response => response.json())
    .then(data => {
      const badge = document.getElementById('incomingNotifBadge');
      const value = document.getElementById('incomingNotifValue');
      
      if(data.count > 0) {
        badge.textContent = data.count;
        badge.style.display = 'flex';
        value.textContent = data.count;
      } else {
        badge.style.display = 'none';
        value.textContent = '0';
      }
    })
    .catch(error => console.error('Error updating incoming badge:', error));
}

// Initial load and periodic update for incoming notifications
document.addEventListener('DOMContentLoaded', function() {
  updateIncomingNotificationBadge();
  setInterval(updateIncomingNotificationBadge, 5000); // Update every 5 seconds
});
</script>

</body>
</html>
