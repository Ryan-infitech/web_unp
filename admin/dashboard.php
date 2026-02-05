<?php
session_start();
include '../config/database.php';

if(!isset($_SESSION['admin_id'])){
  header("Location: login.php");
  exit;
}

$id = $_SESSION['admin_id'];
$admin_q = mysqli_query($conn, "SELECT * FROM admin WHERE id=$id");
$a = mysqli_fetch_assoc($admin_q);

if(!$a){
  die("Admin tidak ditemukan");
}

// Get statistics
$total_peralatan = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM barang_labor"))['total'] ?? 0;
$menunggu_approval = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM peminjaman WHERE status='Menunggu Persetujuan'"))['total'] ?? 0;
$notif_masuk_hari_ini = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM notification_logs WHERE DATE(sent_at)=CURDATE()"))['total'] ?? 0;
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard Admin</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
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
    --hover-bg: #f9f9f9;
    --shadow: 0 4px 15px rgba(0,0,0,0.1);
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

  @keyframes slideInUp {
    from {
      opacity: 0;
      transform: translateY(20px);
    }
    to {
      opacity: 1;
      transform: translateY(0);
    }
  }

  @keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
  }

  @keyframes pulse {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.1); }
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

  /* Header Section */
  .page-header {
    margin-bottom: 50px;
    background: linear-gradient(135deg, #f59e0b20 0%, #fb923c20 100%);
    padding: 40px 35px;
    border-radius: 16px;
    border: 2px solid var(--border-color);
    box-shadow: 0 8px 32px rgba(245, 158, 11, 0.1);
    animation: slideInDown 0.5s ease-out;
    position: relative;
    overflow: hidden;
  }

  .page-header::before {
    content: '';
    position: absolute;
    top: -50%;
    right: -50%;
    width: 200%;
    height: 200%;
    background: radial-gradient(circle, rgba(245, 158, 11, 0.05) 0%, transparent 70%);
    animation: pulse 8s ease-in-out infinite;
  }

  .page-header > * {
    position: relative;
    z-index: 1;
  }

  .page-title {
    font-size: 32px;
    font-weight: 700;
    margin-bottom: 8px;
    color: var(--text-primary);
    background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
  }

  .page-subtitle {
    font-size: 15px;
    color: var(--text-secondary);
    font-weight: 500;
  }

  /* Stats Grid - Spacious */
  .stats-section {
    margin-bottom: 60px;
  }

  .stats-section-title {
    font-size: 18px;
    font-weight: 600;
    color: var(--text-primary);
    margin-bottom: 25px;
    display: flex;
    align-items: center;
    gap: 10px;
  }

  .stats-section-title i {
    color: var(--primary);
  }

  .stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 25px;
    margin-bottom: 0;
  }

  .stat-card {
    background: var(--bg-primary);
    border-radius: 12px;
    padding: 32px 28px;
    border: 2px solid var(--border-color);
    transition: all 0.3s cubic-bezier(0.25, 0.46, 0.45, 0.94);
    animation: slideInUp 0.5s ease-out;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
  }

  .stat-card:hover {
    border-color: var(--border-color);
    box-shadow: 0 8px 20px rgba(0,0,0,0.1);
    transform: translateY(-6px) scale(1.02);
  }

  .stat-card-top {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    margin-bottom: 20px;
  }

  .stat-info h3 {
    font-size: 36px;
    font-weight: 700;
    color: var(--text-primary);
    margin-bottom: 6px;
  }

  .stat-info p {
    font-size: 14px;
    color: var(--text-secondary);
    font-weight: 500;
  }

  .stat-icon {
    width: 56px;
    height: 56px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    transition: all 0.3s ease;
  }

  .stat-card:nth-child(2) .stat-icon:hover {
    animation: pulse 0.6s ease-in-out;
  }

  .stat-card-bottom {
    padding-top: 16px;
    border-top: 2px solid var(--border-color);
    font-size: 13px;
    color: var(--text-secondary);
  }

  /* Icon Variations - Orange/Yellow Theme */
  .stat-card:nth-child(1) .stat-icon {
    background-color: #fed7aa;
    color: #ea580c;
  }

  .stat-card:nth-child(2) .stat-icon {
    background-color: #fecdd3;
    color: #dc2626;
  }

  .stat-card:nth-child(3) .stat-icon {
    background-color: #ddd6fe;
    color: #7c3aed;
  }

  /* Quick Actions Section */
  .actions-section {
    margin-bottom: 0;
  }

  .actions-title {
    font-size: 18px;
    font-weight: 600;
    color: var(--text-primary);
    margin-bottom: 25px;
    display: flex;
    align-items: center;
    gap: 10px;
  }

  .actions-title i {
    color: var(--primary);
  }

  .actions-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
  }

  .action-card {
    background: var(--bg-primary);
    border-radius: 12px;
    padding: 28px 24px;
    border: 2px solid var(--border-color);
    text-decoration: none;
    color: inherit;
    transition: all 0.3s cubic-bezier(0.25, 0.46, 0.45, 0.94);
    text-align: center;
    animation: slideInUp 0.6s ease-out;
    animation-fill-mode: both;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
  }

  .action-card:nth-child(1) { animation-delay: 0.1s; }
  .action-card:nth-child(2) { animation-delay: 0.2s; }
  .action-card:nth-child(3) { animation-delay: 0.3s; }
  .action-card:nth-child(4) { animation-delay: 0.4s; }
  .action-card:nth-child(5) { animation-delay: 0.5s; }
  .action-card:nth-child(6) { animation-delay: 0.6s; }

  .action-card:hover {
    border-color: var(--border-color);
    box-shadow: 0 12px 28px rgba(0,0,0,0.12);
    transform: translateY(-8px) scale(1.02);
  }

  .action-icon-wrapper {
    width: 64px;
    height: 64px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 16px;
    font-size: 28px;
    transition: all 0.3s ease;
  }

  .action-card:hover .action-icon-wrapper {
    transform: scale(1.15) rotate(5deg);
  }

  .action-card:nth-child(1) .action-icon-wrapper {
    background-color: #fef3c7;
    color: #f59e0b;
  }

  .action-card:nth-child(2) .action-icon-wrapper {
    background-color: #fecdd3;
    color: #dc2626;
  }

  .action-card:nth-child(3) .action-icon-wrapper {
    background-color: #fed7aa;
    color: #ea580c;
  }

  .action-card:nth-child(4) .action-icon-wrapper {
    background-color: #ddd6fe;
    color: #7c3aed;
  }

  .action-card:nth-child(5) .action-icon-wrapper {
    background-color: #d1fae5;
    color: #059669;
  }

  .action-card:nth-child(6) .action-icon-wrapper {
    background-color: #cffafe;
    color: #0369a1;
  }

  .action-title {
    font-size: 16px;
    font-weight: 600;
    color: var(--text-primary);
    margin-bottom: 8px;
  }

  .action-desc {
    font-size: 13px;
    color: var(--text-secondary);
  }

  /* Responsive */
  @media (max-width: 768px) {
    .main-content {
      padding: 24px 16px;
      margin-left: 0;
    }

    .page-title {
      font-size: 24px;
    }

    .stats-grid {
      grid-template-columns: 1fr;
      gap: 16px;
    }

    .stat-card {
      padding: 24px 20px;
    }

    .stat-info h3 {
      font-size: 28px;
    }

    .actions-grid {
      grid-template-columns: 1fr;
      gap: 16px;
    }
  }
</style>
</head>
<body>

<?php include '../assets/admin_sidebar.php'; ?>

<div class="main-content">
  <!-- Page Header -->
  <div class="page-header">
    <div class="page-title"><i class="fas fa-chart-line" style="margin-right: 10px;"></i>Dashboard</div>
    <div class="page-subtitle">Selamat datang kembali, <?php echo htmlspecialchars($a['nama'] ?? $a['username']); ?>! Kelola sistem peminjaman labor dengan efisien.</div>
  </div>

  <!-- Statistics Section -->
  <div class="stats-section">
    <div class="stats-section-title">
      <i class="fas fa-chart-line"></i> Statistik Utama
    </div>

    <div class="stats-grid">
      <div class="stat-card">
        <div class="stat-card-top">
          <div class="stat-info">
            <h3><?php echo $total_peralatan; ?></h3>
            <p>Total Peralatan</p>
          </div>
          <div class="stat-icon">
            <i class="fas fa-tools"></i>
          </div>
        </div>
        <div class="stat-card-bottom">
          Peralatan tersedia
        </div>
      </div>

      <div class="stat-card">
        <div class="stat-card-top">
          <div class="stat-info">
            <h3><?php echo $menunggu_approval; ?></h3>
            <p>Perlu Persetujuan</p>
          </div>
          <div class="stat-icon">
            <i class="fas fa-clock"></i>
          </div>
        </div>
        <div class="stat-card-bottom">
          Peminjaman menunggu
        </div>
      </div>

      <div class="stat-card">
        <div class="stat-card-top">
          <div class="stat-info">
            <h3><?php echo $notif_masuk_hari_ini; ?></h3>
            <p>Notifikasi Hari Ini</p>
          </div>
          <div class="stat-icon">
            <i class="fas fa-bell"></i>
          </div>
        </div>
        <div class="stat-card-bottom">
          Notifikasi masuk
        </div>
      </div>
    </div>
  </div>

  <!-- Quick Actions Section -->
  <div class="actions-section">
    <div class="actions-title">
      <i class="fas fa-bolt"></i> Aksi Cepat
    </div>

    <div class="actions-grid">
      <a href="peminjaman.php" class="action-card">
        <div class="action-icon-wrapper">
          <i class="fas fa-list"></i>
        </div>
        <div class="action-title">Data Peminjaman</div>
        <div class="action-desc">Lihat semua permintaan peminjaman</div>
      </a>

      <a href="pengembalian.php" class="action-card">
        <div class="action-icon-wrapper">
          <i class="fas fa-arrow-left"></i>
        </div>
        <div class="action-title">Pengembalian</div>
        <div class="action-desc">Proses pengembalian barang</div>
      </a>

      <a href="peralatan.php" class="action-card">
        <div class="action-icon-wrapper">
          <i class="fas fa-tools"></i>
        </div>
        <div class="action-title">Kelola Peralatan</div>
        <div class="action-desc">Tambah, edit, atau hapus peralatan</div>
      </a>

      <a href="notifikasi.php" class="action-card">
        <div class="action-icon-wrapper">
          <i class="fas fa-envelope"></i>
        </div>
        <div class="action-title">Kirim Notifikasi</div>
        <div class="action-desc">Kirim notifikasi ke pengguna</div>
      </a>

      <a href="history.php" class="action-card">
        <div class="action-icon-wrapper">
          <i class="fas fa-history"></i>
        </div>
        <div class="action-title">History</div>
        <div class="action-desc">Lihat riwayat peminjaman</div>
      </a>

      <a href="kelola_labor.php" class="action-card">
        <div class="action-icon-wrapper">
          <i class="fas fa-building"></i>
        </div>
        <div class="action-title">Kelola Labor</div>
        <div class="action-desc">Update informasi labor</div>
      </a>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
if(localStorage.getItem('sidebarCollapsed')==='true') {
  document.body.classList.add('sidebar-collapsed');
}
</script>
</body>
</html>