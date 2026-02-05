<?php
session_start();
include '../config/database.php';

// Cek apakah user sudah login sebagai admin
if(!isset($_SESSION['admin_id'])){
    header("Location: login.php");
    exit;
}

$error = '';
$success = '';

// Get search and filter parameters
$search_nama = isset($_GET['search_nama']) ? mysqli_real_escape_string($conn, $_GET['search_nama']) : '';
$search_nim = isset($_GET['search_nim']) ? mysqli_real_escape_string($conn, $_GET['search_nim']) : '';
$filter_sort = isset($_GET['filter_sort']) ? mysqli_real_escape_string($conn, $_GET['filter_sort']) : 'terbaru';
$filter_date = isset($_GET['filter_date']) ? mysqli_real_escape_string($conn, $_GET['filter_date']) : '';

// Build WHERE clause
$where = "is_active = 0";

if(!empty($search_nama)) {
  $where .= " AND nama LIKE '%$search_nama%'";
}

if(!empty($search_nim)) {
  $where .= " AND nim LIKE '%$search_nim%'";
}

if(!empty($filter_date)) {
  $where .= " AND DATE(id) = '$filter_date'";
}

// Build ORDER clause
$order = "id DESC";
if($filter_sort === 'terlama') {
  $order = "id ASC";
}

// Ambil data user yang belum diaktifkan
$query = "SELECT id, email, nama, nim, is_active FROM users WHERE $where ORDER BY $order";
$result = mysqli_query($conn, $query);

// Get stats
$total_pending = mysqli_num_rows(mysqli_query($conn,"SELECT id FROM users WHERE is_active = 0"));
$total_active = mysqli_num_rows(mysqli_query($conn,"SELECT id FROM users WHERE is_active = 1"));
$total_users = mysqli_num_rows(mysqli_query($conn,"SELECT id FROM users"));

// Proses aktivasi akun
if(isset($_POST['aktivasi'])){
    $user_id = intval($_POST['user_id']);
    
    // Update status is_active menjadi 1
    $update = mysqli_query($conn, "UPDATE users SET is_active = 1 WHERE id = $user_id");
    
    if($update){
        $success = "Akun berhasil diaktifkan!";
        // Reload halaman
        header("Refresh: 2");
    } else {
        $error = "Terjadi kesalahan saat mengaktifkan akun: " . mysqli_error($conn);
    }
}

// Proses tolak akun (hapus user)
if(isset($_POST['tolak'])){
    $user_id = intval($_POST['user_id']);
    
    // Hapus user dari database
    $delete = mysqli_query($conn, "DELETE FROM users WHERE id = $user_id");
    
    if($delete){
        $success = "Akun berhasil ditolak dan dihapus!";
        // Reload halaman
        header("Refresh: 2");
    } else {
        $error = "Terjadi kesalahan saat menolak akun: " . mysqli_error($conn);
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aktivasi Akun - Admin Labor</title>
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

        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-5px); }
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background-color: var(--bg-secondary);
            color: var(--text-primary);
        }
        
        .main-content {
            margin-left: 250px;
            padding: 40px 30px;
            min-height: 100vh;
            animation: fadeIn 0.3s ease;
        }
        
        .page-header {
            margin-bottom: 40px;
            animation: slideInDown 0.4s ease;
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
        }
        
        .welcome-card p {
            font-size: 14px;
            color: var(--text-secondary);
            margin-bottom: 0;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }
        
        .stat-card {
            background: var(--bg-primary);
            border-radius: 10px;
            padding: 24px;
            border: 2px solid var(--border-color);
            transition: all 0.2s;
            animation: slideInUp 0.5s ease;
        }
        
        .stat-card:hover {
            border-color: var(--primary);
            box-shadow: 0 4px 12px rgba(245, 158, 11, 0.15);
            transform: translateY(-2px);
        }
        
        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 12px;
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
            background-color: #dbeafe;
            color: #1e40af;
        }
        
        .stat-title {
            font-size: 13px;
            color: var(--text-secondary);
            margin-bottom: 6px;
            font-weight: 500;
        }
        
        .stat-value {
            font-size: 28px;
            font-weight: 700;
            color: var(--text-primary);
        }
        
        .filter-card {
            background: var(--bg-primary);
            border-radius: 10px;
            padding: 24px;
            border: 2px solid var(--border-color);
            margin-bottom: 30px;
            animation: slideInUp 0.5s ease 0.1s both;
        }
        
        .filter-title {
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 20px;
            color: var(--text-primary);
        }
        
        .filter-group {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 15px;
        }
        
        .form-control,
        .form-select {
            border: 1px solid var(--border-color);
            border-radius: 8px;
            padding: 10px 12px;
            font-size: 14px;
        }
        
        .form-control:focus,
        .form-select:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.1);
        }
        
        .btn-filter {
            background: var(--primary);
            color: #000;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            font-size: 14px;
        }
        
        .btn-filter:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: var(--shadow);
        }
        
        .btn-reset {
            background: var(--bg-secondary);
            color: var(--text-primary);
            border: 1px solid var(--border-color);
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            font-size: 14px;
        }
        
        .btn-reset:hover {
            background: var(--border-color);
        }
        
        .table-card {
            background: var(--bg-primary);
            border-radius: 10px;
            padding: 30px;
            border: 2px solid var(--border-color);
            margin-bottom: 40px;
            animation: slideInUp 0.5s ease 0.2s both;
        }
        
        .table-card h4 {
            font-size: 20px;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 25px;
        }
        
        .user-card {
            background: var(--bg-secondary);
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 15px;
            border: 1px solid var(--border-color);
            transition: all 0.3s;
            animation: slideInUp 0.4s ease backwards;
        }
        
        .user-card:hover {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            border-color: var(--primary);
        }
        
        .user-card-header {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid var(--border-color);
        }
        
        .user-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
            font-size: 20px;
        }
        
        .user-info {
            flex: 1;
        }
        
        .user-name {
            font-size: 16px;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 4px;
        }
        
        .user-nim {
            font-size: 13px;
            color: var(--text-secondary);
            margin-bottom: 4px;
        }
        
        .user-email {
            font-size: 13px;
            color: var(--text-secondary);
        }
        
        .status-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
            background: #fee2e2;
            color: #991b1b;
        }
        
        .user-actions {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }
        
        .btn-aksi {
            border: none;
            padding: 8px 16px;
            font-weight: 600;
            border-radius: 8px;
            color: white;
            transition: all 0.3s ease;
            font-size: 13px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            flex: 1;
            justify-content: center;
        }

        .btn-aksi:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }
        
        .btn-aksi-aktif {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
        }

        .btn-aksi-aktif:hover {
            background: linear-gradient(135deg, #34b856 0%, #2ed9b7 100%);
        }
        
        .btn-aksi-tolak {
            background: linear-gradient(135deg, #dc3545 0%, #ff6b6b 100%);
        }

        .btn-aksi-tolak:hover {
            background: linear-gradient(135deg, #e74c58 0%, #ff7c7c 100%);
        }

        .empty-state {
            background: var(--bg-secondary);
            padding: 60px 40px;
            border-radius: 10px;
            text-align: center;
            color: var(--text-secondary);
            animation: slideInUp 0.5s ease;
        }

        .empty-state i {
            font-size: 64px;
            color: #cbd5e1;
            margin-bottom: 20px;
            display: block;
        }

        .empty-state p {
            font-size: 16px;
            font-weight: 600;
            margin: 0;
        }

        .alert {
            border: none;
            border-radius: 12px;
            padding: 15px 20px;
            margin-bottom: 20px;
            font-size: 14px;
            font-weight: 600;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            animation: slideInDown 0.4s ease;
        }

        .alert-success {
            background: rgba(40, 167, 69, 0.9);
            color: white;
        }

        .alert-danger {
            background: rgba(220, 53, 69, 0.9);
            color: white;
        }

        @media (max-width: 768px) {
            .main-content {
                padding: 20px;
                margin-left: 0;
            }

            .page-title {
                font-size: 24px;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .filter-group {
                grid-template-columns: 1fr;
            }

            .user-card-header {
                flex-wrap: wrap;
            }

            .user-actions {
                flex-direction: column;
            }

            .btn-aksi {
                width: 100%;
            }
        }
    </style>
</head>
<body>

<?php include '../assets/admin_sidebar.php'; ?>

<div class="main-content">

    <div style="margin-bottom: 20px;">
      <a href="dashboard.php" class="btn btn-secondary btn-sm">
        <i class="fas fa-arrow-left"></i> Kembali
      </a>
    </div>

    <?php if($error): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if($success): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <i class="fas fa-check-circle"></i> <?php echo $success; ?>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="welcome-card">
        <h2><i class="fas fa-user-check"></i> Aktivasi Akun Pengguna</h2>
        <p>Kelola dan aktifkan akun mahasiswa yang baru saja mendaftar</p>
    </div>

    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-hourglass-half"></i></div>
            <div class="stat-title">Menunggu Aktivasi</div>
            <div class="stat-value"><?= $total_pending ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
            <div class="stat-title">Sudah Diaktifkan</div>
            <div class="stat-value"><?= $total_active ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-users"></i></div>
            <div class="stat-title">Total User</div>
            <div class="stat-value"><?= $total_users ?></div>
        </div>
    </div>

    <div class="filter-card">
        <div class="filter-title"><i class="fas fa-filter"></i> Filter & Pencarian</div>
        <form method="get" action="">
            <div class="filter-group">
                <input type="text" class="form-control" name="search_nama" placeholder="Cari nama..." value="<?= htmlspecialchars($search_nama) ?>">
                <input type="text" class="form-control" name="search_nim" placeholder="Cari NIM..." value="<?= htmlspecialchars($search_nim) ?>">
                <select class="form-select" name="filter_sort">
                    <option value="terbaru" <?= $filter_sort === 'terbaru' ? 'selected' : '' ?>>Terbaru</option>
                    <option value="terlama" <?= $filter_sort === 'terlama' ? 'selected' : '' ?>>Terlama</option>
                </select>
            </div>
            <div style="display: flex; gap: 10px;">
                <button type="submit" class="btn-filter"><i class="fas fa-search"></i> Cari</button>
                <a href="aktivasi.php" class="btn-reset"><i class="fas fa-redo"></i> Reset</a>
            </div>
        </form>
    </div>

    <div class="table-card">
        <h4><i class="fas fa-list"></i> Daftar Akun yang Menunggu Aktivasi</h4>
        <?php
        if(mysqli_num_rows($result) > 0){
            $index = 0;
            while($user = mysqli_fetch_assoc($result)){
                $index++;
                echo '
                <div class="user-card" style="animation-delay: ' . ($index * 0.05) . 's;">
                    <div class="user-card-header">
                        <div class="user-avatar">' . strtoupper(substr($user['nama'], 0, 1)) . '</div>
                        <div class="user-info">
                            <div class="user-name"><i class="fas fa-user"></i> ' . htmlspecialchars($user['nama']) . '</div>
                            <div class="user-nim"><i class="fas fa-id-card"></i> ' . htmlspecialchars($user['nim']) . '</div>
                            <div class="user-email"><i class="fas fa-envelope"></i> ' . htmlspecialchars($user['email']) . '</div>
                        </div>
                        <span class="status-badge"><i class="fas fa-info-circle"></i> Belum Diaktifkan</span>
                    </div>
                    
                    <div class="user-actions">
                        <form method="post" style="flex: 1;">
                            <input type="hidden" name="user_id" value="' . $user['id'] . '">
                            <button type="submit" name="aktivasi" class="btn-aksi btn-aksi-aktif" onclick="return confirm(\'Aktifkan akun ' . htmlspecialchars($user['nama']) . '?\')">
                                <i class="fas fa-check"></i> Aktifkan
                            </button>
                        </form>

                        <form method="post" style="flex: 1;">
                            <input type="hidden" name="user_id" value="' . $user['id'] . '">
                            <button type="submit" name="tolak" class="btn-aksi btn-aksi-tolak" onclick="return confirm(\'Tolak akun ' . htmlspecialchars($user['nama']) . '?\')">
                                <i class="fas fa-times"></i> Tolak & Hapus
                            </button>
                        </form>
                    </div>
                </div>
                ';
            }
        } else {
            echo '
            <div class="empty-state">
                <i class="fas fa-check-circle"></i>
                <p>Tidak ada akun yang menunggu aktivasi</p>
            </div>
            ';
        }
        ?>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Auto-dismiss alerts after 5 seconds
    document.querySelectorAll('.alert').forEach(function(alert) {
        setTimeout(function() {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        }, 5000);
    });

    // Load sidebar state from localStorage
    if(localStorage.getItem('sidebarCollapsed')==='true') {
        document.body.classList.add('sidebar-collapsed');
    }
    document.addEventListener('click', function(e) {
        if(window.innerWidth<=768 && !document.querySelector('.sidebar').contains(e.target) && !document.querySelector('.sidebar-toggle').contains(e.target) && !document.body.classList.contains('sidebar-collapsed')) {
            document.body.classList.add('sidebar-collapsed');
        }
    });
</script>
</body>
</html>
