<?php
session_start();
include '../config/database.php';

if(!isset($_SESSION['admin_id'])){
  header("Location: login.php");
  exit;
}

// Get search and filter parameters
$search_nama = isset($_GET['search_nama']) ? mysqli_real_escape_string($conn, $_GET['search_nama']) : '';
$search_nim = isset($_GET['search_nim']) ? mysqli_real_escape_string($conn, $_GET['search_nim']) : '';
$search_barang = isset($_GET['search_barang']) ? mysqli_real_escape_string($conn, $_GET['search_barang']) : '';
$filter_status = isset($_GET['filter_status']) ? mysqli_real_escape_string($conn, $_GET['filter_status']) : '';
$filter_date = isset($_GET['filter_date']) ? mysqli_real_escape_string($conn, $_GET['filter_date']) : '';

// Build WHERE clause
$where = "1=1";

if(!empty($search_nama)) {
  $where .= " AND u.nama LIKE '%$search_nama%'";
}

if(!empty($search_nim)) {
  $where .= " AND u.nim LIKE '%$search_nim%'";
}

if(!empty($search_barang)) {
  $where .= " AND bl.nama LIKE '%$search_barang%'";
}

if(!empty($filter_status)) {
  $where .= " AND p.status = '$filter_status'";
}

if(!empty($filter_date)) {
  $where .= " AND DATE(p.created_at) = '$filter_date'";
}

// Get total data
$total_q = mysqli_query($conn, "
  SELECT COUNT(*) as total FROM peminjaman p
  JOIN users u ON p.user_id=u.id
  LEFT JOIN labor l ON p.labor_id=l.id
  LEFT JOIN barang_labor bl ON p.barang_labor_id=bl.id
  WHERE $where
");
$total_row = mysqli_fetch_assoc($total_q);
$total_items = $total_row['total'];

// Get all data without pagination
$q = mysqli_query($conn, "
  SELECT p.*, u.nama, u.nim, l.nama AS labor_nama, bl.nama AS barang_nama
  FROM peminjaman p
  JOIN users u ON p.user_id=u.id
  LEFT JOIN labor l ON p.labor_id=l.id
  LEFT JOIN barang_labor bl ON p.barang_labor_id=bl.id
  WHERE $where
  ORDER BY p.created_at DESC
");

// Get stats
$total_returned = mysqli_num_rows(mysqli_query($conn,"SELECT id FROM peminjaman WHERE status='Dikembalikan'"));
$total_approved = mysqli_num_rows(mysqli_query($conn,"SELECT id FROM peminjaman WHERE status='Disetujui' OR status='Dikembalikan'"));
$total_pending = mysqli_num_rows(mysqli_query($conn,"SELECT id FROM peminjaman WHERE status='Menunggu'"));

$id = $_SESSION['admin_id'];
$admin_q = mysqli_query($conn, "SELECT * FROM admin WHERE id=$id");
$a = mysqli_fetch_assoc($admin_q);
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>History Peminjaman</title>
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
  
  body {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
    background-color: var(--bg-secondary);
    color: var(--text-primary);
    transition: background-color 0.3s ease, color 0.3s ease;
  }
  
  .main-content {
    margin-left: 250px;
    padding: 40px 30px;
    min-height: 100vh;
  }
  
  .page-header {
    margin-bottom: 40px;
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
    display: flex;
    align-items: center;
    gap: 10px;
  }

  .welcome-card h2 i {
    color: var(--primary);
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
    transition: all 0.3s cubic-bezier(0.25, 0.46, 0.45, 0.94);
    animation: slideInUp 0.5s ease-out;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
  }

  .stat-card:hover {
    border-color: #2563eb;
    box-shadow: 0 8px 20px rgba(37, 99, 235, 0.2);
    transform: translateY(-4px) scale(1.01);
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
    background-color: #dcfce7;
    color: #16a34a;
  }
  
  .stat-card:nth-child(2) .stat-icon {
    background-color: #fef3c7;
    color: #92400e;
  }
  
  .stat-card:nth-child(3) .stat-icon {
    background-color: #fee2e2;
    color: #dc2626;
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
    border-radius: 12px;
    padding: 24px;
    border: 2px solid var(--border-color);
    margin-bottom: 30px;
    transition: all 0.3s ease;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
  }

  .filter-card:hover {
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
  }

  .filter-card h5 {
    margin: 0 0 18px 0;
    color: var(--text-primary);
    font-size: 16px;
    font-weight: 700;
    display: flex;
    align-items: center;
    gap: 10px;
    padding-bottom: 16px;
    border-bottom: 2px solid var(--border-color);
  }

  .filter-card h5 i {
    color: var(--primary);
  }

  .filter-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 16px;
    align-items: flex-end;
  }

  .filter-group {
    flex: 1;
  }

  .filter-group label {
    font-size: 12px;
    color: var(--text-secondary);
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 8px;
    display: block;
  }

  .filter-buttons {
    display: flex;
    gap: 10px;
  }

  .btn-filter {
    background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
    color: #000;
    border: none;
    font-weight: 600;
    padding: 10px 18px;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.3s ease;
    font-size: 13px;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    text-decoration: none;
  }

  .btn-filter:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow);
  }

  .btn-reset-filter {
    background: var(--bg-secondary);
    color: var(--text-primary);
    border: 2px solid var(--border-color);
    font-weight: 600;
    padding: 10px 18px;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.3s ease;
    font-size: 13px;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    text-decoration: none;
  }

  .btn-reset-filter:hover {
    background: var(--border-color);
    transform: translateY(-2px);
  }
  
  .table-card {
    background: var(--bg-primary);
    border-radius: 10px;
    padding: 30px;
    border: 2px solid var(--border-color);
    margin-bottom: 40px;
  }
  
  .table-card h4 {
    font-size: 20px;
    font-weight: 600;
    color: var(--text-primary);
    margin-bottom: 25px;
  }
  
  .table-responsive {
    border-radius: 8px;
    overflow: hidden;
  }
  
  .table {
    margin-bottom: 0;
    border-collapse: collapse;
  }
  
  .table thead {
    background-color: var(--bg-secondary);
    border-bottom: 2px solid var(--border-color);
  }
  
  .table thead th {
    font-weight: 600;
    color: var(--text-primary);
    padding: 14px;
    font-size: 13px;
    border: none;
    text-align: left;
  }
  
  .table tbody td {
    padding: 14px;
    border-bottom: 1px solid var(--border-color);
    font-size: 13px;
  }
  
  .table tbody tr:hover {
    background-color: var(--bg-secondary);
  }
  
  .badge-status {
    padding: 6px 12px;
    border-radius: 6px;
    font-size: 11px;
    font-weight: 600;
  }
  
  .badge-menunggu {
    background-color: #fef3c7;
    color: #92400e;
  }
  
  .badge-disetujui {
    background-color: #dbeafe;
    color: #1e40af;
  }
  
  .badge-dikembalikan {
    background-color: #dcfce7;
    color: #166534;
  }
  
  .badge-ditolak {
    background-color: #fee2e2;
    color: #991b1b;
  }
  
  .pagination-container {
    display: flex;
    justify-content: center;
    gap: 8px;
    margin-top: 20px;
    flex-wrap: wrap;
  }
  
  .pagination-container a,
  .pagination-container span {
    padding: 8px 12px;
    border: 1px solid var(--border-color);
    border-radius: 6px;
    text-decoration: none;
    color: var(--text-primary);
    transition: all 0.2s;
  }
  
  .pagination-container a:hover {
    background-color: #2563eb;
    color: white;
    border-color: #2563eb;
  }
  
  .pagination-container .active {
    background-color: #2563eb;
    color: white;
    border-color: #2563eb;
  }
  
  .no-data {
    padding: 40px;
    text-align: center;
    color: #999;
  }
  
  .no-data i {
    font-size: 48px;
    margin-bottom: 20px;
    color: #ccc;
  }
  
  .no-data p {
    font-size: 16px;
    font-weight: 600;
  }
  
  @media (max-width: 768px) {
    .main-content { padding: 20px; }
    .welcome-card h2 { font-size: 24px; }
    .stats-grid { grid-template-columns: 1fr; }
    .filter-group { grid-template-columns: 1fr; }
    .table-card { padding: 16px; }
    .table thead th { font-size: 11px; padding: 8px; }
    .table tbody td { font-size: 11px; padding: 8px; }
  }
</style>
</head>

<body>

<?php include '../assets/admin_sidebar.php'; ?>

<div class="main-content">
<div class="welcome-card">
  <h2><i class="fas fa-history"></i> History Peminjaman</h2>
  <p>Riwayat lengkap peminjaman barang labor</p>
</div>

<div class="stats-grid">
  <div class="stat-card">
    <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
    <div class="stat-title">Total Dikembalikan</div>
    <div class="stat-value"><?= $total_returned ?></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon"><i class="fas fa-hourglass-half"></i></div>
    <div class="stat-title">Total Dipinjam</div>
    <div class="stat-value"><?= $total_approved ?></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon"><i class="fas fa-clock"></i></div>
    <div class="stat-title">Menunggu Konfirmasi</div>
    <div class="stat-value"><?= $total_pending ?></div>
  </div>
</div>

<div class="filter-card">
  <h5><i class="fas fa-filter"></i>Filter & Pencarian</h5>
  
  <form method="GET" id="filterForm" class="form-filter">
    <div class="filter-row">
      <div class="filter-group">
        <label>Nama Peminjam</label>
        <input type="text" name="search_nama" id="search_nama" class="form-control" placeholder="Cari nama..." value="<?= htmlspecialchars($search_nama) ?>">
      </div>
      <div class="filter-group">
        <label>NIM</label>
        <input type="text" name="search_nim" id="search_nim" class="form-control" placeholder="Cari NIM..." value="<?= htmlspecialchars($search_nim) ?>">
      </div>
      <div class="filter-group">
        <label>Peralatan</label>
        <input type="text" name="search_barang" id="search_barang" class="form-control" placeholder="Cari peralatan..." value="<?= htmlspecialchars($search_barang) ?>">
      </div>
    </div>
    
    <div class="filter-row">
      <div class="filter-group">
        <label>Status</label>
        <select name="filter_status" id="filter_status" class="form-select">
          <option value="">Semua Status</option>
          <option value="Menunggu" <?= $filter_status == 'Menunggu' ? 'selected' : '' ?>>Menunggu</option>
          <option value="Disetujui" <?= $filter_status == 'Disetujui' ? 'selected' : '' ?>>Disetujui</option>
          <option value="Dikembalikan" <?= $filter_status == 'Dikembalikan' ? 'selected' : '' ?>>Dikembalikan</option>
          <option value="Ditolak" <?= $filter_status == 'Ditolak' ? 'selected' : '' ?>>Ditolak</option>
        </select>
      </div>
      <div class="filter-group">
        <label>Tanggal</label>
        <input type="date" name="filter_date" id="filter_date" class="form-control" value="<?= htmlspecialchars($filter_date) ?>">
      </div>
    </div>
    
    <div style="margin-top: 16px; display: flex; gap: 10px;">
      <a href="history.php" class="btn-reset-filter">
        <i class="fas fa-redo"></i> Reset
      </a>
    </div>
  </form>
</div>

<div class="table-card">
  <h4><i class="fas fa-history"></i> Daftar Peminjaman (<?= $total_items ?> total)</h4>

  <div class="table-responsive" id="tableContainer">
    <table class="table table-bordered table-hover">
      <thead>
        <tr>
          <th>Nama</th>
          <th>NIM</th>
          <th>Peralatan</th>
          <th>Jumlah</th>
          <th>Tanggal Peminjaman</th>
          <th>Tanggal Kembali</th>
          <th>Status</th>
        </tr>
      </thead>
      <tbody id="tableBody">
      <?php 
      $has_data = false;
      while($d=mysqli_fetch_assoc($q)): 
        $has_data = true;
        $badge_class = '';
        if($d['status'] == 'Menunggu') $badge_class = 'badge-menunggu';
        elseif($d['status'] == 'Disetujui') $badge_class = 'badge-disetujui';
        elseif($d['status'] == 'Dikembalikan') $badge_class = 'badge-dikembalikan';
        elseif($d['status'] == 'Ditolak') $badge_class = 'badge-ditolak';
      ?>
        <tr>
          <td><?= htmlspecialchars($d['nama']) ?></td>
          <td><?= htmlspecialchars($d['nim']) ?></td>
          <td><?= htmlspecialchars($d['barang_nama'] ?? '-') ?></td>
          <td><?= intval($d['jumlah']) ?> pcs</td>
          <td><?= date('d-m-Y H:i',strtotime($d['created_at'])) ?></td>
          <td><?= $d['tanggal_kembali'] ? date('d-m-Y H:i',strtotime($d['tanggal_kembali'])) : '-' ?></td>
          <td><span class="badge-status <?= $badge_class ?>"><?= htmlspecialchars($d['status']) ?></span></td>
        </tr>
      <?php endwhile; 
      
      if(!$has_data): ?>
      <tr>
        <td colspan="7">
          <div class="no-data">
            <i class="fas fa-inbox"></i>
            <p>Tidak ada data peminjaman yang sesuai dengan filter</p>
          </div>
        </td>
      </tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

</div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
if(localStorage.getItem('sidebarCollapsed')==='true') {
  document.body.classList.add('sidebar-collapsed');
}
document.addEventListener('click', function(e) {
  if(window.innerWidth<=768 && !document.querySelector('.sidebar').contains(e.target) && !document.querySelector('.sidebar-toggle').contains(e.target) && !document.body.classList.contains('sidebar-collapsed')) {
    document.body.classList.add('sidebar-collapsed');
  }
});

// AJAX Real-time Filtering
const filterInputs = document.querySelectorAll('#search_nama, #search_nim, #search_barang, #filter_status, #filter_date');

filterInputs.forEach(input => {
  input.addEventListener('change', performFilter);
  if(input.type === 'text') {
    input.addEventListener('keyup', performFilter);
  }
});

function performFilter() {
  const search_nama = document.getElementById('search_nama').value;
  const search_nim = document.getElementById('search_nim').value;
  const search_barang = document.getElementById('search_barang').value;
  const filter_status = document.getElementById('filter_status').value;
  const filter_date = document.getElementById('filter_date').value;

  // Build query parameters
  const params = new URLSearchParams();
  if(search_nama) params.append('search_nama', search_nama);
  if(search_nim) params.append('search_nim', search_nim);
  if(search_barang) params.append('search_barang', search_barang);
  if(filter_status) params.append('filter_status', filter_status);
  if(filter_date) params.append('filter_date', filter_date);
  params.append('ajax', '1');

  // Fetch data via AJAX
  fetch('history_ajax.php?' + params.toString())
    .then(response => response.json())
    .then(data => {
      // Update total count
      document.querySelector('.table-card h4').innerHTML = '<i class="fas fa-history"></i> Daftar Peminjaman (' + data.total + ' total)';
      
      // Update table body
      const tableBody = document.getElementById('tableBody');
      tableBody.innerHTML = '';

      if(data.rows.length === 0) {
        tableBody.innerHTML = `
          <tr>
            <td colspan="7">
              <div class="no-data">
                <i class="fas fa-inbox"></i>
                <p>Tidak ada data peminjaman yang sesuai dengan filter</p>
              </div>
            </td>
          </tr>
        `;
        return;
      }

      data.rows.forEach(row => {
        const badgeClass = {
          'Menunggu': 'badge-menunggu',
          'Disetujui': 'badge-disetujui',
          'Dikembalikan': 'badge-dikembalikan',
          'Ditolak': 'badge-ditolak'
        }[row.status] || '';

        const tr = document.createElement('tr');
        tr.innerHTML = `
          <td>${escapeHtml(row.nama)}</td>
          <td>${escapeHtml(row.nim)}</td>
          <td>${escapeHtml(row.barang_nama || '-')}</td>
          <td>${row.jumlah} pcs</td>
          <td>${row.tanggal}</td>
          <td>${row.tanggal_kembali || '-'}</td>
          <td><span class="badge-status ${badgeClass}">${escapeHtml(row.status)}</span></td>
        `;
        tableBody.appendChild(tr);
      });
    })
    .catch(error => {
      console.error('Error:', error);
      alert('Terjadi kesalahan saat memfilter data');
    });
}

// Helper function to escape HTML
function escapeHtml(text) {
  const div = document.createElement('div');
  div.textContent = text;
  return div.innerHTML;
}
</script>
</body>
</html>
