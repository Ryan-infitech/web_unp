<?php
// Ensure session is started (if not already)
if(session_status() === PHP_SESSION_NONE) {
  session_start();
}

// Include database connection
if(!isset($conn)) {
  include dirname(__DIR__) . '/config/database.php';
}

// Get current user info
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
$user_info = null;
if($user_id) {
  $user_query = mysqli_query($conn, "SELECT * FROM users WHERE id = '$user_id'");
  $user_info = mysqli_fetch_assoc($user_query);
}

// Deteksi direktori saat ini untuk menentukan relative path
$current_file = basename($_SERVER['PHP_SELF']);
$script_path = $_SERVER['SCRIPT_FILENAME'];
$document_root = $_SERVER['DOCUMENT_ROOT'];
$relative_path = str_replace($document_root, '', $script_path);

// Tentukan apakah file berada di root, subdirectory, atau lebih dalam
$path_depth = substr_count(str_replace($document_root, '', dirname($script_path)), '/');
$base_path = str_repeat('../', $path_depth - 1); // -1 karena htdocs sudah dihitung

// Determine current page untuk active state
$current_page = '';
if (strpos($relative_path, 'dashboard-content.php') !== false || strpos($relative_path, 'index.php') !== false) {
    $current_page = 'dashboard';
} elseif (strpos($relative_path, 'labor-content.php') !== false) {
    $current_page = 'labor';
} elseif (strpos($relative_path, 'peminjaman-content.php') !== false) {
    $current_page = 'peminjaman';
} elseif (strpos($relative_path, 'riwayat-content.php') !== false) {
    $current_page = 'riwayat';
} elseif (strpos($relative_path, 'profil-content.php') !== false) {
    $current_page = 'profil';
} elseif (strpos($relative_path, 'notifikasi-content.php') !== false) {
    $current_page = 'notifikasi';
}

// Function untuk generate link dengan relative path yang benar
function get_nav_link($target, $current_base) {
    return $current_base . $target;
}

// Function untuk check active state
function is_active($page, $current) {
    return $page === $current ? 'active' : '';
}
?>
<!DOCTYPE html>
<html>
<head>
<title>Aplikasi Labor</title>
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
  }
  
  .sidebar {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    min-height: 100vh;
    padding: 20px 0;
    position: fixed;
    left: 0;
    top: 0;
    width: 250px;
    box-shadow: 2px 0 10px rgba(0,0,0,0.1);
    z-index: 1000;
    overflow-y: auto;
    transition: transform 0.3s ease, width 0.3s ease;
  }

  .sidebar.collapsed {
    transform: translateX(-250px);
  }

  .sidebar-toggle {
    display: none;
    position: fixed;
    top: 15px;
    left: 15px;
    z-index: 1001;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border: none;
    width: 45px;
    height: 45px;
    border-radius: 8px;
    font-size: 20px;
    cursor: pointer;
    transition: all 0.3s ease;
    padding: 0;
    align-items: center;
    justify-content: center;
    box-shadow: 0 2px 10px rgba(102, 126, 234, 0.3);
  }

  .sidebar-toggle:hover {
    background: linear-gradient(135deg, #764ba2 0%, #667eea 100%);
    transform: scale(1.05);
  }

  .sidebar-toggle:active {
    transform: scale(0.95);
  }
  
  .sidebar-brand {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 30px 20px 40px;
    border-bottom: 1px solid rgba(255,255,255,0.1);
  }
  
  .sidebar-brand i {
    font-size: 40px;
    color: white;
    margin-right: 15px;
  }
  
  .user-avatar-brand {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    background: rgba(255,255,255,0.2);
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 0 15px 0;
    border: 2px solid rgba(255,255,255,0.3);
    overflow: hidden;
    font-size: 20px;
    font-weight: 700;
    color: white;
    flex-shrink: 0;
    cursor: pointer;
    transition: all 0.3s ease;
  }

  .user-avatar-brand:hover {
    transform: scale(1.1);
    background: rgba(255,255,255,0.3);
    border-color: rgba(255,255,255,0.5);
  }
  
  .avatar-img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: flex;
  }
  
  .avatar-initials {
    font-size: 20px;
    font-weight: 700;
    color: white;
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(255,255,255,0.2);
  }
  
  .sidebar-brand i {
    font-size: 40px;
    color: white;
    margin-right: 15px;
  }
  
  .sidebar-brand h4 {
    color: white;
    margin: 0;
    font-weight: 700;
    font-size: 20px;
  }
  
  .sidebar-menu {
    list-style: none;
    padding: 20px 0;
    margin-bottom: 0;
  }
  
  .sidebar-menu li {
    margin: 0;
  }
  
  .sidebar-menu a {
    display: flex;
    align-items: center;
    padding: 15px 25px;
    color: rgba(255,255,255,0.75);
    text-decoration: none;
    transition: all 0.3s ease;
    font-size: 14px;
    font-weight: 500;
    border-left: 4px solid transparent;
  }
  
  .sidebar-menu a:hover {
    background-color: rgba(255,255,255,0.1);
    color: white;
    padding-left: 30px;
    border-left-color: rgba(255,255,255,0.5);
  }
  
  .sidebar-menu a.active {
    background: linear-gradient(90deg, rgba(255,255,255,0.2) 0%, transparent 100%);
    color: white;
    padding-left: 30px;
    border-left: 4px solid #fff;
    box-shadow: inset 0 2px 8px rgba(0,0,0,0.1);
    font-weight: 600;
  }
  
  .sidebar-menu i {
    width: 25px;
    margin-right: 15px;
    text-align: center;
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
  
  .sidebar-footer {
    position: absolute;
    bottom: 20px;
    left: 0;
    right: 0;
    width: 100%;
    padding: 0 20px;
  }
  
  .sidebar-footer a {
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 12px;
    background: rgba(255,255,255,0.1);
    color: white;
    text-decoration: none;
    border-radius: 8px;
    transition: all 0.3s ease;
    font-weight: 600;
    border: 1px solid rgba(255,255,255,0.2);
    font-size: 13px;
  }
  
  .sidebar-footer a:hover {
    background: rgba(255,255,255,0.2);
    color: white;
  }
  
  .sidebar-footer i {
    margin-right: 8px;
  }
  
  .main-content {
    margin-left: 275px;
    padding: 30px 20px;
    min-height: 100vh;
    transition: margin-left 0.3s ease;
    position: relative;
    z-index: 1;
  }

  .main-content.sidebar-collapsed {
    margin-left: 0;
  }

  .main-content.mobile-mode {
    margin-left: 0;
    padding: 60px 15px 20px 15px;
  }

  /* Overlay untuk mobile */
  .sidebar-overlay {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.5);
    z-index: 998;
    opacity: 0;
    transition: opacity 0.3s ease;
    pointer-events: none;
  }

  .sidebar-overlay.active {
    opacity: 1;
    pointer-events: auto;
  }
  
  @media (max-width: 992px) {
    .sidebar-toggle {
      display: flex;
    }

    .sidebar {
      transform: translateX(-250px);
    }

    .sidebar.active {
      transform: translateX(0);
    }

    .sidebar-overlay {
      display: block;
    }

    .sidebar-overlay.active {
      display: block;
    }

    .main-content {
      margin-left: 0;
      padding: 60px 20px 20px 20px;
    }
  }

  @media (max-width: 768px) {
    .main-content {
      padding: 60px 15px 20px 15px;
    }

    .sidebar-brand {
      padding: 20px 15px 30px;
    }

    .sidebar-menu a {
      padding: 12px 20px;
      font-size: 13px;
    }

    .sidebar-menu i {
      width: 20px;
      margin-right: 12px;
    }

    .sidebar-footer {
      bottom: 10px;
      padding: 0 15px;
    }

    .sidebar-footer a {
      padding: 10px;
      font-size: 12px;
    }
  }

  @media (max-width: 480px) {
    .sidebar {
      width: 220px;
    }

    .sidebar-toggle {
      width: 40px;
      height: 40px;
      font-size: 18px;
    }

    .main-content {
      padding: 55px 12px 20px 12px;
    }
  }
</style>
</head>
<body>
<!-- Sidebar Toggle Button -->
<button id="sidebarToggle" class="sidebar-toggle">
  <i class="fas fa-bars"></i>
</button>

<!-- Sidebar Overlay -->
<div id="sidebarOverlay" class="sidebar-overlay"></div>

<!-- Sidebar Navigation -->
<div class="sidebar" id="sidebar">
  <div class="sidebar-brand">
    <div class="user-avatar-brand" onclick="navigateToProfile(event)" title="Klik untuk ke profil">
      <?php if($user_info): ?>
        <?php 
          $foto_file = isset($user_info['foto']) ? $user_info['foto'] : '';
          $foto_path = __DIR__ . '/img/' . $foto_file;
          $has_foto = !empty($foto_file) && file_exists($foto_path);
        ?>
        <?php if($has_foto): ?>
          <img src="../assets/img/<?php echo urlencode($foto_file); ?>?v=<?php echo time(); ?>" alt="User" class="avatar-img" loading="lazy">
          <div class="avatar-initials" style="display: none;"><?php echo strtoupper(substr($user_info['nama'], 0, 2)); ?></div>
        <?php else: ?>
          <div class="avatar-initials"><?php echo strtoupper(substr($user_info['nama'], 0, 2)); ?></div>
        <?php endif; ?>
      <?php else: ?>
        <i class="fas fa-user-circle"></i>
      <?php endif; ?>
    </div>
    <h4><?php echo $user_info ? htmlspecialchars($user_info['nama']) : 'Labor'; ?></h4>
  </div>
  
  <ul class="sidebar-menu">
    <li><a href="#" class="nav-link <?php echo is_active('profil', $current_page); ?>" data-page="profil"><i class="fas fa-user-circle"></i> <span>Profil Saya</span></a></li>
    <li><a href="#" class="nav-link <?php echo is_active('dashboard', $current_page); ?>" data-page="dashboard"><i class="fas fa-home"></i> <span>Dashboard</span></a></li>
    <li><a href="#" class="nav-link <?php echo is_active('labor', $current_page); ?>" data-page="labor"><i class="fas fa-flask"></i> <span>Daftar Labor</span></a></li>
    <li><a href="#" class="nav-link <?php echo is_active('peminjaman', $current_page); ?>" data-page="peminjaman"><i class="fas fa-plus-circle"></i> <span>Ajukan Peminjaman</span></a></li>
    <li><a href="#" class="nav-link <?php echo is_active('riwayat', $current_page); ?>" data-page="riwayat"><i class="fas fa-history"></i> <span>Riwayat Peminjaman</span></a></li>
    <li>
      <a href="#" class="nav-link <?php echo is_active('notifikasi', $current_page); ?>" data-page="notifikasi" style="position: relative;">
        <i class="fas fa-bell"></i> 
        <span>Notifikasi</span>
        <?php 
          if($user_id) {
            $unread_count = mysqli_num_rows(mysqli_query($conn, "SELECT id FROM notifications WHERE user_id='$user_id' AND is_read=0"));
            if($unread_count > 0) {
              echo '<span class="notif-badge">' . $unread_count . '</span>';
            }
          }
        ?>
      </a>
    </li>
  </ul>
  
  <div class="sidebar-footer">
    <a href="#" class="nav-link" onclick="logout(event)"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
  // Sidebar Toggle Functionality
  const sidebarToggle = document.getElementById('sidebarToggle');
  const sidebar = document.getElementById('sidebar');
  const sidebarOverlay = document.getElementById('sidebarOverlay');

  // Always reset localStorage on page load (sidebar default collapsed)
  function resetSidebarState() {
    if(window.innerWidth <= 992) {
      localStorage.removeItem('sidebarActive');
      sidebar.classList.remove('active');
      sidebarOverlay.classList.remove('active');
    }
  }

  // Toggle sidebar
  function toggleSidebar() {
    const isActive = sidebar.classList.toggle('active');
    sidebarOverlay.classList.toggle('active', isActive);
    
    // Save preference (mobile only)
    if(window.innerWidth <= 992) {
      localStorage.setItem('sidebarActive', isActive ? 'true' : 'false');
    }
  }

  // Close sidebar on overlay click
  sidebarOverlay.addEventListener('click', (e) => {
    e.stopPropagation();
    sidebar.classList.remove('active');
    sidebarOverlay.classList.remove('active');
    localStorage.setItem('sidebarActive', 'false');
  });

  // Toggle button click
  if(sidebarToggle) {
    sidebarToggle.addEventListener('click', (e) => {
      e.stopPropagation();
      toggleSidebar();
    });
  }

  // Close sidebar when clicking navigation links (mobile)
  const navLinks = document.querySelectorAll('.sidebar-menu a, .sidebar-footer a');
  navLinks.forEach(link => {
    link.addEventListener('click', (e) => {
      if(window.innerWidth <= 992 && sidebar.classList.contains('active')) {
        sidebar.classList.remove('active');
        sidebarOverlay.classList.remove('active');
        localStorage.setItem('sidebarActive', 'false');
      }
    });
  });

  // Close sidebar on Escape key
  document.addEventListener('keydown', (e) => {
    if(e.key === 'Escape' && sidebar.classList.contains('active')) {
      sidebar.classList.remove('active');
      sidebarOverlay.classList.remove('active');
      localStorage.setItem('sidebarActive', 'false');
    }
  });

  // Handle window resize
  window.addEventListener('resize', () => {
    if(window.innerWidth > 992) {
      sidebar.classList.remove('active');
      sidebarOverlay.classList.remove('active');
      localStorage.removeItem('sidebarActive');
    }
  });

  // Initialize on page load
  document.addEventListener('DOMContentLoaded', function() {
    // Reset sidebar state on page load (always collapsed)
    resetSidebarState();

    // Update notification badge
    updateNotificationBadge();
    setInterval(updateNotificationBadge, 5000);

    // Setup navigation links
    const pageNavLinks = document.querySelectorAll('.nav-link[data-page]');
    pageNavLinks.forEach(link => {
      link.addEventListener('click', function(e) {
        e.preventDefault();
        const page = this.getAttribute('data-page');
        navigateToPage(page);
      });
    });

    // Force reload avatar
    const avatarImg = document.querySelector('.user-avatar-brand img');
    if(avatarImg) {
      try {
        const src = avatarImg.src.split('?')[0];
        avatarImg.src = src + '?v=' + new Date().getTime();
      } catch(e) {
        console.log('Avatar refresh error:', e);
      }
    }
  });

  // Helper function untuk mendapatkan base path dari halaman saat ini
  function getBasePath() {
    const pathname = window.location.pathname;
    return pathname.includes('/pages/') ? '' : '';
  }

  // Navigate to profile page
  function navigateToProfile(event) {
    event.preventDefault();
    const pathname = window.location.pathname;
    let url = pathname.includes('/pages/') ? 'profil-content.php' : 'pages/profil-content.php';
    window.location.href = url;
  }

  // Update notification badge
  function updateNotificationBadge() {
    fetch('../config/get_unread_notifications.php')
      .then(response => response.json())
      .then(data => {
        const badge = document.querySelector('.notif-badge');
        if(data.count > 0) {
          if(!badge) {
            const notifLink = document.querySelector('a[data-page="notifikasi"]');
            if(notifLink) {
              const newBadge = document.createElement('span');
              newBadge.className = 'notif-badge';
              newBadge.textContent = data.count;
              newBadge.style.display = 'flex';
              notifLink.appendChild(newBadge);
            }
          } else {
            badge.textContent = data.count;
            badge.style.display = 'flex';
          }
        } else {
          if(badge) {
            badge.style.display = 'none';
          }
        }
      })
      .catch(error => console.error('Error updating notification badge:', error));
  }
  
  // Centralized navigation function
  function navigateToPage(page) {
    const pathname = window.location.pathname;
    const isInPages = pathname.includes('/pages/');
    
    const pageMap = {
      'dashboard': 'dashboard-content.php',
      'labor': 'labor-content.php',
      'peminjaman': 'peminjaman-content.php',
      'riwayat': 'riwayat-content.php',
      'profil': 'profil-content.php',
      'notifikasi': 'notifikasi-content.php'
    };
    
    const filename = pageMap[page] || 'dashboard-content.php';
    const url = isInPages ? filename : 'pages/' + filename;
    
    // Clear sidebar state before navigation
    localStorage.removeItem('sidebarActive');
    window.location.href = url;
  }
  
  // Logout function
  function logout(e) {
    e.preventDefault();
    if(confirm('Apakah Anda yakin ingin logout?')) {
      const pathname = window.location.pathname;
      const logoutUrl = pathname.includes('/pages/') ? '../auth/logout.php' : 'auth/logout.php';
      
      // Clear sidebar state before logout
      localStorage.removeItem('sidebarActive');
      window.location.href = logoutUrl;
    }
  }
</script>
