<?php
// Admin Sidebar Navigation
// Detect current page untuk set active state
// Gunakan full path untuk better matching
$current_file = $_SERVER['PHP_SELF'];
$current_page = basename($current_file);

// Get current directory to detect if we're in a subfolder
$admin_dir = dirname($current_file);
$is_subfolder = (basename($admin_dir) !== 'admin');
$base_path = $is_subfolder ? '../' : '';

// Define sidebar items with both display link and detection pattern
$sidebar_items = [
    [
        'icon' => 'fas fa-bell',
        'label' => 'Kirim Notifikasi',
        'link' => $base_path . 'notifikasi.php',
        'id' => 'notifikasi',
        'detect' => 'notifikasi.php'
    ],
    [
        'icon' => 'fas fa-home',
        'label' => 'Beranda',
        'link' => $base_path . 'dashboard.php',
        'id' => 'dashboard',
        'detect' => 'dashboard.php'
    ],
    [
        'icon' => 'fa-solid fa-users-viewfinder',
        'label' => 'Face Auth',
        'link' => $base_path . 'face-recognition/index.php',
        'id' => 'face_auth',
        'detect' => ['face-recognition', 'index.php']
    ],
    [
        'icon' => 'fas fa-list',
        'label' => 'Peminjaman',
        'link' => $base_path . 'peminjaman.php',
        'id' => 'peminjaman',
        'detect' => 'peminjaman.php'
    ],
    [
        'icon' => 'fas fa-arrow-left',
        'label' => 'Pengembalian',
        'link' => $base_path . 'pengembalian.php',
        'id' => 'pengembalian',
        'detect' => 'pengembalian.php'
    ],
    [
        'icon' => 'fas fa-users-cog',
        'label' => 'Kelola Users',
        'link' => $base_path . 'users.php',
        'id' => 'users',
        'detect' => 'users.php'
    ],
    [
        'icon' => 'fas fa-tools',
        'label' => 'Kelola Peralatan',
        'link' => $base_path . 'peralatan.php',
        'id' => 'peralatan',
        'detect' => 'peralatan.php'
    ],
    [
        'icon' => 'fas fa-building',
        'label' => 'Kelola Labor',
        'link' => $base_path . 'kelola_labor.php',
        'id' => 'kelola_labor',
        'detect' => 'kelola_labor.php'
    ],
    [
        'icon' => 'fas fa-history',
        'label' => 'History',
        'link' => $base_path . 'history.php',
        'id' => 'history',
        'detect' => 'history.php'
   ]
];

// Helper function to check if current page matches the menu item
function isActiveItem($item, $current_file) {
    $detect = $item['detect'];
    
    // If detect is an array, check if all patterns exist in the path
    if(is_array($detect)) {
        foreach($detect as $pattern) {
            if(strpos($current_file, $pattern) === false) {
                return false;
            }
        }
        return true;
    }
    
    // If detect is a string, check if it's in the path
    return strpos($current_file, $detect) !== false;
}
?>

<!-- SIDEBAR TOGGLE -->
<button class="sidebar-toggle" onclick="document.body.classList.toggle('sidebar-collapsed'); localStorage.setItem('sidebarCollapsed', document.body.classList.contains('sidebar-collapsed'));">
  <i class="fas fa-bars"></i>
</button>

<!-- SIDEBAR -->
<div class="sidebar">
  <div class="sidebar-header">
    <div>
      <h5><i class="fas fa-tasks"></i> Menu Admin</h5>
      <p id="adminName">Loading...</p>
    </div>
  </div>
  
  <div class="sidebar-menu">
    <?php foreach($sidebar_items as $item): ?>
      <?php $is_active = isActiveItem($item, $current_file) ? 'active' : ''; ?>
      <a href="<?php echo $item['link']; ?>" class="sidebar-item <?php echo $is_active; ?>">
        <i class="<?php echo $item['icon']; ?>"></i>
        <span><?php echo $item['label']; ?></span>
        <?php if($item['id'] === 'notifikasi'): ?>
          <span class="notif-badge" id="adminNotifBadge"></span>
        <?php endif; ?>
      </a>
    <?php endforeach; ?>
    
    <div style="margin-top: auto; padding: 15px 0; border-top: 1px solid var(--border-light);"></div>
    
    <a href="<?php echo $base_path; ?>logout.php" class="sidebar-item">
      <i class="fas fa-sign-out-alt"></i>
      <span>Logout</span>
    </a>
  </div>
</div>

<!-- SIDEBAR STYLES - MINIMALIS & MODERN WITH DARK MODE (MAHASISWA THEME) -->
<style>
  :root {
    --primary: #f59e0b;
    --primary-light: #fbbf24;
    --primary-dark: #d97706;
    --secondary: #fb923c;
    --bg-primary: #ffffff;
    --bg-secondary: #f8fafc;
    --bg-tertiary: #fef3c7;
    --text-primary: #0f172a;
    --text-secondary: #64748b;
    --border-color: #e0e0e0;
    --border-light: #e2e8f0;
    --hover-bg: #f9f9f9;
    --success: #22c55e;
    --danger: #ef4444;
    --shadow: 0 4px 15px rgba(0,0,0,0.1);
  }

  body.dark-mode {
    --primary: #fbbf24;
    --primary-light: #fcd34d;
    --primary-dark: #f59e0b;
    --secondary: #fb923c;
    --bg-primary: #1e293b;
    --bg-secondary: #0f172a;
    --bg-tertiary: #334155;
    --text-primary: #f1f5f9;
    --text-secondary: #cbd5e1;
    --border-color: #334155;
    --border-light: #475569;
    --hover-bg: #334155;
    --shadow: 0 4px 15px rgba(0,0,0,0.3);
  }

  * {
    box-sizing: border-box;
  }

  body {
    overflow-x: hidden;
    margin: 0;
    padding: 0;
  }

  /* Main content wrapper */
  .main-content {
    margin-left: 250px;
    transition: margin-left 0.3s ease;
    width: calc(100% - 250px);
  }

  /* Sidebar - Minimalis dengan icon dan text */
  .sidebar {
    position: fixed;
    top: 0;
    left: 0;
    width: 250px;
    height: 100vh;
    background: var(--bg-primary);
    border-right: 2px solid var(--border-light);
    overflow-y: auto;
    transition: width 0.3s ease, background 0.3s ease, border-color 0.3s ease;
    z-index: 999;
    display: flex;
    flex-direction: column;
  }

  .sidebar-header {
    padding: 20px;
    text-align: left;
    border-bottom: 2px solid var(--border-light);
    display: flex;
    align-items: center;
    min-height: auto;
    transition: background 0.3s ease, border-color 0.3s ease;
  }

  .sidebar-header h5,
  .sidebar-header p {
    display: block;
  }

  .sidebar-header::before {
    display: none;
  }

  .sidebar-header h5 {
    margin: 0 0 5px 0;
    font-weight: 700;
    color: var(--text-primary);
    font-size: 16px;
  }

  .sidebar-header p {
    margin: 0;
    font-size: 12px;
    color: var(--text-secondary);
    font-weight: 400;
  }

  .sidebar-menu {
    padding: 15px 0;
    flex: 1;
    display: flex;
    flex-direction: column;
  }

  .sidebar-item {
    padding: 12px 20px;
    text-decoration: none;
    color: var(--text-secondary);
    display: flex;
    align-items: center;
    justify-content: flex-start;
    transition: all 0.2s;
    border-left: 3px solid transparent;
    font-weight: 500;
    position: relative;
    font-size: 14px;
    gap: 12px;
  }

  .sidebar-item:hover {
    background-color: var(--hover-bg);
    color: var(--primary);
  }

  .sidebar-item.active {
    background-color: rgba(245, 158, 11, 0.1);
    border-left-color: var(--primary);
    color: var(--primary);
  }

  body.dark-mode .sidebar-item.active {
    background-color: rgba(251, 191, 36, 0.15);
    border-left-color: var(--primary);
    color: var(--primary);
  }

  .sidebar-item i {
    font-size: 18px;
    width: 20px;
    text-align: center;
  }

  .sidebar-item span {
    display: inline;
    margin-left: 0;
  }

  .notif-badge {
    display: none;
    min-width: 20px;
    height: 20px;
    background: #ff4444;
    color: white;
    border-radius: 50%;
    font-size: 11px;
    font-weight: 700;
    align-items: center;
    justify-content: center;
    margin-left: auto;
    padding: 0 6px;
    animation: pulse 2s infinite;
  }

  @keyframes pulse {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.1); }
  }

  /* Sidebar toggle button - Hidden */
  .sidebar-toggle {
    display: none;
  }

  /* Theme toggle button */
  .theme-toggle {
    color: var(--text-secondary);
  }

  .theme-toggle:hover {
    color: var(--primary);
  }

  body.dark-mode .theme-toggle:hover {
    color: var(--primary);
  }

  /* Collapsed sidebar state */
  body.sidebar-collapsed .sidebar {
    width: 70px;
  }

  body.sidebar-collapsed .main-content {
    margin-left: 70px;
    width: calc(100% - 70px);
  }

  body.sidebar-collapsed .sidebar-header h5,
  body.sidebar-collapsed .sidebar-header p {
    display: none;
  }

  body.sidebar-collapsed .sidebar-header::before {
    display: block;
    content: '⚙️';
    font-size: 28px;
    width: 100%;
    text-align: center;
  }

  body.sidebar-collapsed .sidebar-header {
    padding: 20px 0;
    text-align: center;
    justify-content: center;
    min-height: 70px;
  }

  body.sidebar-collapsed .sidebar-item {
    justify-content: center;
    padding: 16px;
    gap: 0;
  }

  body.sidebar-collapsed .sidebar-item span {
    display: none;
  }

  body.sidebar-collapsed .sidebar-toggle {
    display: flex;
    align-items: center;
    justify-content: center;
    position: fixed;
    top: 16px;
    left: 85px;
    z-index: 1001;
    background: var(--bg-primary);
    border: 2px solid var(--border-light);
    border-radius: 6px;
    padding: 8px 10px;
    cursor: pointer;
    transition: all 0.2s;
  }

  body.sidebar-collapsed .sidebar-toggle:hover {
    background: var(--hover-bg);
    border-color: #2563eb;
    color: #2563eb;
  }

  body.sidebar-collapsed .sidebar-toggle i {
    font-size: 16px;
    color: var(--text-secondary);
  }

  /* Responsive */
  @media (max-width: 768px) {
    .sidebar {
      width: 250px;
    }

    .main-content {
      margin-left: 250px;
      width: calc(100% - 250px);
    }

    body.sidebar-collapsed .sidebar {
      width: 70px;
    }

    body.sidebar-collapsed .main-content {
      margin-left: 70px;
      width: calc(100% - 70px);
    }

    body.sidebar-collapsed .sidebar-header h5,
    body.sidebar-collapsed .sidebar-header p {
      display: none;
    }

    body.sidebar-collapsed .sidebar-header::before {
      display: block;
    }

    body.sidebar-collapsed .sidebar-item span {
      display: none;
    }

    body.sidebar-collapsed .sidebar-toggle {
      display: none;
    }
  }
</style>

<!-- SIDEBAR SCRIPT -->
<script>
  // Load sidebar and theme states from localStorage on page load
  window.addEventListener('DOMContentLoaded', function() {
    if(localStorage.getItem('sidebarCollapsed') === 'true') {
      document.body.classList.add('sidebar-collapsed');
    }
  });

  // Close sidebar when clicking outside on mobile
  document.addEventListener('click', function(e) {
    const sidebar = document.querySelector('.sidebar');
    const toggle = document.querySelector('.sidebar-toggle');
    
    if(window.innerWidth <= 768) {
      if(sidebar && toggle && 
         !sidebar.contains(e.target) && 
         !toggle.contains(e.target) && 
         !document.body.classList.contains('sidebar-collapsed')) {
        document.body.classList.add('sidebar-collapsed');
      }
    }
  });

  // Ensure proper layout on window resize
  window.addEventListener('resize', function() {
    if(window.innerWidth > 768) {
      // On larger screens, remove forced collapse
      if(localStorage.getItem('sidebarCollapsed') !== 'true') {
        document.body.classList.remove('sidebar-collapsed');
      }
    }
  });

  // Load admin name from session
  function loadAdminName() {
    fetch('../config/get_admin_info.php')
      .then(response => response.json())
      .then(data => {
        const adminNameEl = document.getElementById('adminName');
        if(adminNameEl && data.nama) {
          adminNameEl.textContent = data.nama;
        }
      })
      .catch(error => console.error('Error loading admin name:', error));
  }

  // Update admin notification badge real-time
  function updateAdminNotificationBadge() {
    fetch('../config/get_admin_unread_notifications.php')
      .then(response => response.json())
      .then(data => {
        const badge = document.getElementById('adminNotifBadge');
        if(badge) {
          if(data.count > 0) {
            badge.textContent = data.count;
            badge.style.display = 'flex';
          } else {
            badge.style.display = 'none';
          }
        }
      })
      .catch(error => console.error('Error updating admin notification badge:', error));
  }

  // Initial setup - set badge to hidden by default
  function initializeBadge() {
    const badge = document.getElementById('adminNotifBadge');
    if(badge) {
      badge.style.display = 'none';
    }
  }

  // Initial load and periodic update
  document.addEventListener('DOMContentLoaded', function() {
    loadAdminName();
    initializeBadge();
    updateAdminNotificationBadge();
    setInterval(updateAdminNotificationBadge, 5000); // Update every 5 seconds
  });
</script>
