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

 $message = '';
 $message_type = '';

// Pastikan tabel labor ada
 $create_labor = "CREATE TABLE IF NOT EXISTS labor (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nama VARCHAR(191) NOT NULL,
  deskripsi TEXT,
  icon VARCHAR(50),
  color VARCHAR(20),
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
)";
mysqli_query($conn, $create_labor);

// Pastikan tabel barang_labor ada
 $create_barang = "CREATE TABLE IF NOT EXISTS barang_labor (
  id INT AUTO_INCREMENT PRIMARY KEY,
  labor_id INT NOT NULL,
  nama VARCHAR(191) NOT NULL,
  deskripsi TEXT,
  stok INT DEFAULT 1,
  stok_awal INT DEFAULT 1,
  status VARCHAR(20) DEFAULT 'Tersedia',
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (labor_id) REFERENCES labor(id)
)";
mysqli_query($conn, $create_barang);

// Tambah kolom stok_awal jika belum ada
$check_column = mysqli_query($conn, "SHOW COLUMNS FROM barang_labor LIKE 'stok_awal'");
if(mysqli_num_rows($check_column) == 0) {
  mysqli_query($conn, "ALTER TABLE barang_labor ADD COLUMN stok_awal INT DEFAULT 1 AFTER stok");
}

// Tambah barang labor (dari tambah_peralatan.php)
if(isset($_POST['tambah'])){
  $labor_id = intval($_POST['labor_id']);
  $nama = mysqli_real_escape_string($conn, $_POST['nama']);
  $deskripsi = mysqli_real_escape_string($conn, $_POST['deskripsi']);
  $stok = intval($_POST['stok']);
  
  if(empty($nama) || !$labor_id){
    $message = "Labor dan nama barang harus diisi!";
    $message_type = "warning";
  } else {
    $ins = mysqli_query($conn, "INSERT INTO barang_labor (labor_id, nama, deskripsi, stok, stok_awal) VALUES ($labor_id, '$nama', '$deskripsi', $stok, $stok)");
    if($ins){
      $message = "Barang berhasil ditambahkan!";
      $message_type = "success";
      // Clear form
      $_POST = array();
    } else {
      $message = "Gagal menambahkan barang: " . mysqli_error($conn);
      $message_type = "danger";
    }
  }
}

// Edit barang labor (dari edit_peralatan.php)
if(isset($_POST['edit'])){
  $id_barang = intval($_POST['barang_id']);
  $labor_id = intval($_POST['labor_id']);
  $nama = mysqli_real_escape_string($conn, $_POST['nama']);
  $deskripsi = mysqli_real_escape_string($conn, $_POST['deskripsi']);
  $stok = intval($_POST['stok']);
  $status = mysqli_real_escape_string($conn, $_POST['status']);
  
  if(empty($nama) || !$labor_id){
    $message = "Labor dan nama barang harus diisi!";
    $message_type = "warning";
  } else {
    if($status === 'Tersedia'){
      $upd = mysqli_query($conn, "UPDATE barang_labor SET labor_id=$labor_id, nama='$nama', deskripsi='$deskripsi', stok=$stok, stok_awal=$stok, status='$status' WHERE id=$id_barang");
    } else {
      $upd = mysqli_query($conn, "UPDATE barang_labor SET labor_id=$labor_id, nama='$nama', deskripsi='$deskripsi', stok=0, stok_awal=$stok, status='$status' WHERE id=$id_barang");
    }
    
    if($upd){
      $message = "Barang berhasil diperbarui!";
      $message_type = "success";
    } else {
      $message = "Gagal memperbarui barang!";
      $message_type = "danger";
    }
  }
}

// Hapus barang labor (single delete)
if(isset($_POST['hapus'])){
  $id_barang = intval($_POST['barang_id']);
  $del = mysqli_query($conn, "DELETE FROM barang_labor WHERE id=$id_barang");
  if($del){
    $message = "Barang berhasil dihapus!";
    $message_type = "success";
  } else {
    $message = "Gagal menghapus barang!";
    $message_type = "danger";
  }
}

// Hapus barang labor secara massal (multiple delete)
if(isset($_POST['hapus_massal']) && isset($_POST['selected_barang'])){
  $selected_ids = array_map('intval', $_POST['selected_barang']);
  $ids_string = implode(',', $selected_ids);
  $del_massal = mysqli_query($conn, "DELETE FROM barang_labor WHERE id IN ($ids_string)");
  if($del_massal){
    $count = count($selected_ids);
    $message = "Total $count barang berhasil dihapus!";
    $message_type = "success";
  } else {
    $message = "Gagal menghapus barang secara massal!";
    $message_type = "danger";
  }
}

// Ambil data labor
 $labor_list = mysqli_query($conn, "SELECT * FROM labor ORDER BY nama ASC");

// Get search and filter parameters
 $search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
 $filter_labor = isset($_GET['filter_labor']) ? intval($_GET['filter_labor']) : '';
 $filter_stock = isset($_GET['filter_stock']) ? mysqli_real_escape_string($conn, $_GET['filter_stock']) : '';
 $sort_by = isset($_GET['sort_by']) ? mysqli_real_escape_string($conn, $_GET['sort_by']) : 'newest';

// Build WHERE clause
 $where = "1=1";
if(!empty($search)) {
  $where .= " AND bl.nama LIKE '%$search%'";
}
if(!empty($filter_labor)) {
  $where .= " AND bl.labor_id = $filter_labor";
}
// PERBAIKAN LOGIKA: Menambahkan kondisi filter stok
if(!empty($filter_stock)) {
  if($filter_stock == 'available') {
    $where .= " AND bl.status = 'Tersedia'";
  } elseif ($filter_stock == 'unavailable') {
    $where .= " AND bl.status = 'Tidak Tersedia'";
  }
}

// Build SORT clause - Default: newest first (created_at DESC)
 $order_by = "bl.created_at DESC";
if($sort_by === 'name_asc') {
  $order_by = "bl.nama ASC";
} elseif($sort_by === 'name_desc') {
  $order_by = "bl.nama DESC";
} elseif($sort_by === 'stock_most') {
  $order_by = "bl.stok DESC";
} elseif($sort_by === 'stock_least') {
  $order_by = "bl.stok ASC";
} elseif($sort_by === 'newest') {
  $order_by = "bl.created_at DESC";
} elseif($sort_by === 'oldest') {
  $order_by = "bl.created_at ASC";
}

// Get total data
 $total_q = mysqli_query($conn, "SELECT COUNT(*) as total FROM barang_labor bl LEFT JOIN labor l ON bl.labor_id=l.id WHERE $where");
 $total_row = mysqli_fetch_assoc($total_q);
 $total_peralatan = $total_row['total'];

// Ambil semua data barang_labor tanpa pagination
 $barang_q = mysqli_query($conn, "SELECT bl.*, l.nama as labor_nama FROM barang_labor bl LEFT JOIN labor l ON bl.labor_id=l.id WHERE $where ORDER BY $order_by");

// Statistics
 $tersedia = mysqli_num_rows(mysqli_query($conn, "SELECT id FROM barang_labor WHERE status='Tersedia'"));
 $tidak_tersedia = mysqli_num_rows(mysqli_query($conn, "SELECT id FROM barang_labor WHERE status='Tidak Tersedia'"));
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Kelola Peralatan</title>
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
      --border-color: #334155;
      --shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
    }

    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    html, body {
      height: 100%;
    }

    body {
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      background: linear-gradient(135deg, rgba(245, 158, 11, 0.08) 0%, rgba(251, 146, 60, 0.08) 100%);
      color: var(--text-primary);
      transition: background 0.3s ease, color 0.3s ease;
    }

    body.dark-mode {
      background: linear-gradient(135deg, rgba(245, 158, 11, 0.12) 0%, rgba(251, 146, 60, 0.12) 100%);
    }

    .main-content {
      padding: 40px 30px;
      min-height: 100vh;
      margin-left: 250px;
      transition: margin-left 0.3s ease;
    }

    /* Page Header */
    .page-header {
      margin-bottom: 40px;
      animation: slideInDown 0.5s ease both;
    }

    .page-title {
      font-size: 32px;
      font-weight: 800;
      margin-bottom: 8px;
      color: var(--text-primary);
      display: flex;
      align-items: center;
      gap: 16px;
    }

    .page-title i {
      color: var(--primary);
      font-size: 36px;
    }

    .page-subtitle {
      font-size: 15px;
      color: var(--text-secondary);
      margin-left: 52px;
    }

    /* Alert */
    .alert {
      border-radius: 10px;
      border: 2px solid;
      margin-bottom: 30px;
      font-size: 14px;
      font-weight: 500;
      padding: 16px 20px;
      animation: slideInDown 0.4s ease;
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

    @keyframes fadeIn {
      from {
        opacity: 0;
      }
      to {
        opacity: 1;
      }
    }

    @keyframes pulse {
      0%, 100% { transform: scale(1); }
      50% { transform: scale(1.05); }
    }

    .alert-success {
      background-color: #dcfce7;
      color: #166534;
      border-color: var(--success);
    }

    body.dark-mode .alert-success {
      background-color: rgba(34, 197, 94, 0.15);
      border-color: var(--success);
      color: #86efac;
    }

    .alert-danger {
      background-color: #fee2e2;
      color: #991b1b;
      border-color: var(--danger);
    }

    body.dark-mode .alert-danger {
      background-color: rgba(239, 68, 68, 0.15);
      border-color: var(--danger);
      color: #fca5a5;
    }

    .alert-warning {
      background-color: #fef3c7;
      color: #78350f;
      border-color: var(--primary);
    }

    body.dark-mode .alert-warning {
      background-color: rgba(245, 158, 11, 0.15);
      border-color: var(--primary);
      color: #fcd34d;
    }

    /* Stats Grid */
    .stats-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
      gap: 16px;
      margin-bottom: 30px;
      animation: fadeIn 0.5s ease 0.1s both;
    }

    .stat-card {
      background: var(--bg-primary);
      border-radius: 10px;
      padding: 18px 16px;
      border: 2px solid var(--border-color);
      transition: all 0.3s ease;
      position: relative;
      overflow: hidden;
      animation: slideInUp 0.5s ease backwards;
    }

    .stat-card:nth-child(1) {
      animation-delay: 0.1s;
    }

    .stat-card:nth-child(2) {
      animation-delay: 0.2s;
    }

    .stat-card:nth-child(3) {
      animation-delay: 0.3s;
    }

    .stat-card::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      height: 4px;
      background: linear-gradient(90deg, var(--primary) 0%, var(--secondary) 100%);
    }

    .stat-card:hover {
      border-color: var(--primary);
      box-shadow: var(--shadow);
      transform: translateY(-4px);
    }

    .stat-icon {
      width: 48px;
      height: 48px;
      border-radius: 8px;
      display: flex;
      align-items: center;
      justify-content: center;
      margin-bottom: 10px;
      font-size: 20px;
      background: linear-gradient(135deg, rgba(245, 158, 11, 0.15) 0%, rgba(251, 146, 60, 0.15) 100%);
      color: var(--primary);
    }

    .stat-label {
      font-size: 11px;
      color: var(--text-secondary);
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      margin-bottom: 6px;
    }

    .stat-value {
      font-size: 28px;
      font-weight: 800;
      color: var(--text-primary);
      line-height: 1;
    }

    .stat-card:nth-child(2) .stat-icon {
      background: linear-gradient(135deg, rgba(34, 197, 94, 0.15) 0%, rgba(34, 197, 94, 0.1) 100%);
      color: var(--success);
    }

    .stat-card:nth-child(3) .stat-icon {
      background: linear-gradient(135deg, rgba(239, 68, 68, 0.15) 0%, rgba(239, 68, 68, 0.1) 100%);
      color: var(--danger);
    }

    /* Container with Grid Layout */
    .container-wrapper {
      display: grid;
      grid-template-columns: 1fr 1.2fr;
      gap: 30px;
    }

    /* Form Card */
    .form-card {
      background: var(--bg-primary);
      border-radius: 12px;
      padding: 32px;
      border: 2px solid var(--border-color);
      transition: all 0.3s ease;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
      animation: slideInUp 0.5s ease 0.2s both;
    }

    .form-card:hover {
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
    }

    .form-header {
      display: flex;
      align-items: center;
      gap: 14px;
      margin-bottom: 28px;
      padding-bottom: 24px;
      border-bottom: 2px solid var(--border-color);
    }

    .form-header i {
      font-size: 28px;
      color: var(--primary);
    }

    .form-header h3 {
      margin: 0;
      color: var(--text-primary);
      font-size: 22px;
      font-weight: 700;
    }

    .form-group {
      margin-bottom: 22px;
    }

    .form-label {
      display: block;
      color: var(--text-primary);
      font-weight: 600;
      margin-bottom: 10px;
      font-size: 14px;
    }

    .form-control,
    .form-select {
      width: 100%;
      padding: 12px 16px;
      border: 2px solid var(--border-color);
      border-radius: 8px;
      font-size: 14px;
      font-family: inherit;
      color: var(--text-primary);
      background: var(--bg-secondary);
      transition: all 0.3s ease;
    }

    .form-control::placeholder {
      color: var(--text-secondary);
    }

    .form-control:focus,
    .form-select:focus {
      outline: none;
      border-color: var(--primary);
      box-shadow: 0 0 0 4px rgba(245, 158, 11, 0.1);
      background: var(--bg-primary);
    }

    body.dark-mode .form-control,
    body.dark-mode .form-select {
      background: #334155;
      color: var(--text-primary);
    }

    textarea.form-control {
      resize: vertical;
      min-height: 100px;
    }

    /* Buttons */
    .btn {
      padding: 12px 24px;
      border-radius: 8px;
      border: none;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.3s ease;
      font-size: 14px;
      display: inline-flex;
      align-items: center;
      gap: 8px;
      text-decoration: none;
    }

    .btn:hover {
      text-decoration: none;
    }

    .btn-primary {
      background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
      color: #000;
      font-weight: 700;
    }

    .btn-primary:hover {
      background: linear-gradient(135deg, var(--primary-dark) 0%, var(--secondary) 100%);
      transform: translateY(-2px);
      box-shadow: var(--shadow);
    }

    .btn-secondary {
      background: var(--bg-secondary);
      color: var(--text-primary);
      border: 2px solid var(--border-color);
    }

    .btn-secondary:hover {
      background: var(--border-color);
      transform: translateY(-2px);
    }

    .btn-success {
      background: var(--success);
      color: white;
    }

    .btn-success:hover {
      background: #16a34a;
      transform: translateY(-2px);
    }

    .btn-danger {
      background: var(--danger);
      color: white;
    }

    .btn-danger:hover {
      background: #dc2626;
      transform: translateY(-2px);
    }

    .btn-sm {
      padding: 8px 14px;
      font-size: 12px;
      gap: 6px;
    }

    /* Table Card */
    .table-card {
      background: var(--bg-primary);
      border-radius: 12px;
      padding: 32px;
      border: 2px solid var(--border-color);
      transition: all 0.3s ease;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
      animation: slideInUp 0.5s ease 0.25s both;
    }

    .table-card:hover {
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
    }

    .table-card h3 {
      margin: 0 0 28px 0;
      color: var(--text-primary);
      font-size: 22px;
      font-weight: 700;
      display: flex;
      align-items: center;
      gap: 14px;
      padding-bottom: 24px;
      border-bottom: 2px solid var(--border-color);
    }

    .table-card h3 i {
      color: var(--primary);
      font-size: 28px;
    }

    .table-responsive {
      border-radius: 8px;
      overflow-x: auto;
    }

    .table {
      color: var(--text-primary);
      margin-bottom: 0;
    }

    .table thead {
      background: var(--bg-secondary);
      border-bottom: 2px solid var(--border-color);
    }

    .table thead th {
      padding: 16px 14px;
      font-weight: 700;
      color: var(--text-primary);
      font-size: 13px;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      border: none;
    }

    .table tbody tr {
      border-bottom: 1px solid var(--border-color);
      transition: background 0.2s ease;
    }

    .table tbody tr:hover {
      background: var(--bg-secondary);
    }

    .table tbody td {
      padding: 14px;
      color: var(--text-secondary);
      font-size: 14px;
      vertical-align: middle;
    }

    .table tbody strong {
      color: var(--text-primary);
    }

    /* Badge */
    .badge {
      padding: 6px 12px;
      border-radius: 6px;
      font-size: 12px;
      font-weight: 600;
      display: inline-block;
    }

    .badge-success {
      background: rgba(34, 197, 94, 0.15);
      color: var(--success);
    }

    body.dark-mode .badge-success {
      background: rgba(34, 197, 94, 0.2);
    }

    .badge-danger {
      background: rgba(239, 68, 68, 0.15);
      color: var(--danger);
    }

    body.dark-mode .badge-danger {
      background: rgba(239, 68, 68, 0.2);
    }

    /* Empty State */
    .empty-state {
      text-align: center;
      padding: 60px 30px;
      color: var(--text-secondary);
    }

    .empty-state i {
      font-size: 56px;
      color: var(--border-color);
      margin-bottom: 20px;
      opacity: 0.6;
    }

    .empty-state p {
      font-size: 16px;
      margin: 0;
    }

    /* Modal Styles */
    .modal-content {
      border: 2px solid var(--border-color);
      border-radius: 12px;
      background: var(--bg-primary);
    }

    .modal-header {
      background: var(--bg-secondary);
      border-bottom: 2px solid var(--border-color);
      padding: 24px;
    }

    .modal-header .modal-title {
      font-size: 20px;
      font-weight: 700;
      color: var(--text-primary);
      display: flex;
      align-items: center;
      gap: 12px;
    }

    .modal-header .modal-title i {
      color: var(--primary);
      font-size: 24px;
    }

    .modal-body {
      padding: 24px;
    }

    .modal-body .form-group {
      margin-bottom: 18px;
    }

    .modal-footer {
      border-top: 2px solid var(--border-color);
      padding: 16px 24px;
      background: var(--bg-secondary);
    }

    /* Filter Card */
    .filter-card {
      background: var(--bg-primary);
      border-radius: 12px;
      padding: 24px;
      border: 2px solid var(--border-color);
      margin-bottom: 30px;
      transition: all 0.3s ease;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
      animation: slideInUp 0.5s ease 0.15s both;
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

    /* Pagination */
    .pagination-container {
      display: flex;
      justify-content: center;
      gap: 6px;
      margin-top: 24px;
      flex-wrap: wrap;
    }

    .pagination-container a,
    .pagination-container span {
      padding: 8px 12px;
      border: 1px solid var(--border-color);
      border-radius: 6px;
      text-decoration: none;
      color: var(--text-primary);
      font-size: 12px;
      font-weight: 600;
      transition: all 0.2s;
    }

    .pagination-container a:hover {
      background-color: var(--primary);
      color: #000;
      border-color: var(--primary);
    }

    .pagination-container .active {
      background-color: var(--primary);
      color: #000;
      border-color: var(--primary);
      font-weight: 700;
    }

    /* Responsive */
    @media (max-width: 1200px) {
      .container-wrapper {
        grid-template-columns: 1fr;
      }
    }

    @media (max-width: 768px) {
      .main-content {
        padding: 24px 16px;
        margin-left: 0;
      }

      .page-title {
        font-size: 24px;
        gap: 12px;
      }

      .page-title i {
        font-size: 28px;
      }

      .page-subtitle {
        margin-left: 40px;
      }

      .stats-grid {
        grid-template-columns: 1fr;
        gap: 16px;
      }

      .form-card,
      .table-card {
        padding: 24px;
      }

      .table {
        font-size: 12px;
      }

      .table thead th,
      .table tbody td {
        padding: 10px;
      }

      .btn {
        padding: 10px 16px;
        font-size: 13px;
      }

      .btn-sm {
        padding: 6px 10px;
        font-size: 11px;
      }
    }

    /* Modal Alert Styles */
    #modalAlert {
      border-radius: 10px;
      border: 2px solid;
      font-size: 14px;
      font-weight: 500;
      padding: 16px 20px;
      animation: slideDown 0.3s ease;
    }

    #modalAlert.alert-success {
      background-color: #dcfce7;
      color: #166534;
      border-color: var(--success);
    }

    body.dark-mode #modalAlert.alert-success {
      background-color: rgba(34, 197, 94, 0.15);
      border-color: var(--success);
      color: #86efac;
    }

    #modalAlert.alert-danger {
      background-color: #fee2e2;
      color: #991b1b;
      border-color: var(--danger);
    }

    body.dark-mode #modalAlert.alert-danger {
      background-color: rgba(239, 68, 68, 0.15);
      border-color: var(--danger);
      color: #fca5a5;
    }

    #modalAlert.alert-warning {
      background-color: #fef3c7;
      color: #78350f;
      border-color: var(--primary);
    }

    body.dark-mode #modalAlert.alert-warning {
      background-color: rgba(245, 158, 11, 0.15);
      border-color: var(--primary);
      color: #fcd34d;
    }

    /* Modal Input Focus Styles */
    #addPeralatanModal input:focus,
    #addPeralatanModal select:focus,
    #addPeralatanModal textarea:focus {
      outline: none;
      border-color: var(--primary) !important;
      box-shadow: 0 0 0 4px rgba(245, 158, 11, 0.1) !important;
      background: var(--bg-primary) !important;
    }

    /* Checkbox Styles */
    input[type="checkbox"] {
      width: 18px;
      height: 18px;
      cursor: pointer;
      accent-color: var(--primary);
      transition: all 0.2s ease;
    }

    input[type="checkbox"]:hover {
      transform: scale(1.1);
    }

    input[type="checkbox"]:checked {
      accent-color: var(--primary-dark);
    }

    /* Selected Count */
    #selectedCount {
      padding: 8px 16px;
      background: rgba(245, 158, 11, 0.1);
      border-radius: 6px;
      border: 1px solid var(--primary);
      font-size: 14px;
    }
  </style>
</head>
<body>

<?php include '../assets/admin_sidebar.php'; ?>

<div class="main-content">
  <!-- Alert Messages -->
  <?php if($message): ?>
    <div class="alert alert-<?= $message_type ?> alert-dismissible fade show" role="alert">
      <i class="fas fa-<?php echo ($message_type == 'success') ? 'check-circle' : (($message_type == 'danger') ? 'exclamation-circle' : 'exclamation-triangle'); ?> me-2"></i>
      <?= htmlspecialchars($message) ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  <?php endif; ?>

  <!-- Page Header -->
  <div class="page-header">
    <div class="page-title">
      <i class="fas fa-tools"></i>
      Kelola Peralatan
    </div>
    <div class="page-subtitle">Tambah, edit, dan kelola daftar peralatan laboratorium</div>
  </div>

  <!-- Statistics Grid -->
  <div class="stats-grid">
    <div class="stat-card">
      <div class="stat-icon"><i class="fas fa-boxes"></i></div>
      <div class="stat-label">Total Peralatan</div>
      <div class="stat-value"><?= $total_peralatan ?></div>
    </div>
    <div class="stat-card">
      <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
      <div class="stat-label">Tersedia</div>
      <div class="stat-value"><?= $tersedia ?></div>
    </div>
    <div class="stat-card">
      <div class="stat-icon"><i class="fas fa-times-circle"></i></div>
      <div class="stat-label">Tidak Tersedia</div>
      <div class="stat-value"><?= $tidak_tersedia ?></div>
    </div>
  </div>

  <!-- Form and Table Grid -->
  <div class="container-wrapper">
    <!-- Table Card -->
    <div class="table-card" style="grid-column: 1 / -1;">
      <!-- Filter Card -->
      <div class="filter-card">
        <h5><i class="fas fa-filter"></i>Filter & Pencarian</h5>
        
        <form method="GET" id="filterForm" class="form-filter">
          <div class="filter-row">
            <div class="filter-group">
              <label>Cari Peralatan</label>
              <input type="text" name="search" id="search" class="form-control" placeholder="Nama peralatan..." 
                     value="<?= htmlspecialchars($search) ?>">
            </div>
            <div class="filter-group">
              <label>Kategori Labor</label>
              <select name="filter_labor" id="filter_labor" class="form-select">
                <option value="">Semua Kategori</option>
                <?php 
                mysqli_data_seek($labor_list, 0);
                while($l = mysqli_fetch_assoc($labor_list)): 
                ?>
                  <option value="<?= $l['id'] ?>" <?= $filter_labor == $l['id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($l['nama']) ?>
                  </option>
                <?php endwhile; ?>
              </select>
            </div>
            <div class="filter-group">
              <label>Urutkan Berdasarkan</label>
              <select name="sort_by" id="sort_by" class="form-select">
                <option value="name_asc" <?= $sort_by === 'name_asc' ? 'selected' : '' ?>>Nama (A-Z)</option>
                <option value="name_desc" <?= $sort_by === 'name_desc' ? 'selected' : '' ?>>Nama (Z-A)</option>
                <option value="stock_most" <?= $sort_by === 'stock_most' ? 'selected' : '' ?>>Stok Paling Banyak</option>
                <option value="stock_least" <?= $sort_by === 'stock_least' ? 'selected' : '' ?>>Stok Paling Sedikit</option>
                <option value="newest" <?= $sort_by === 'newest' ? 'selected' : '' ?>>Paling Baru Ditambah</option>
                <option value="oldest" <?= $sort_by === 'oldest' ? 'selected' : '' ?>>Paling Lama Ditambah</option>
              </select>
            </div>
          </div>
          
          <div style="margin-top: 16px; display: flex; gap: 10px;">
            <a href="peralatan.php" class="btn-reset-filter">
              <i class="fas fa-redo"></i> Reset
            </a>
            <button type="button" class="btn btn-primary" style="margin-left: auto;" onclick="openAddModal()">
              <i class="fas fa-plus"></i> Tambah Peralatan
            </button>
          </div>
        </form>
      </div>

      <h3>
        <i class="fas fa-list"></i>
        Daftar Peralatan (<span id="totalCount"><?= $total_peralatan ?></span> total)
      </h3>
      
      <?php if(mysqli_num_rows($barang_q) > 0): ?>
        <div style="margin-bottom: 16px; display: flex; gap: 10px; align-items: center;">
          <span id="selectedCount" style="color: var(--text-secondary); font-weight: 500; display: none;">
            <strong id="countSelected">0</strong> item dipilih
          </span>
          <button type="button" id="deleteMassalBtn" class="btn btn-danger" style="display: none;" onclick="deleteSelected()">
            <i class="fas fa-trash"></i> Hapus Dipilih
          </button>
        </div>
        <div class="table-responsive">
          <form id="tableForm" method="post">
            <table class="table">
              <thead>
                <tr>
                  <th style="width: 40px; padding-left: 16px;">
                    <input type="checkbox" id="selectAll" onclick="toggleSelectAll(this)">
                  </th>
                  <th>No</th>
                  <th>Labor</th>
                  <th>Nama Peralatan</th>
                  <th>Stok</th>
                  <th>Status</th>
                  <th>Aksi</th>
                </tr>
              </thead>
              <tbody id="tableBody">
                <?php 
                $no = 1;
                while($barang = mysqli_fetch_assoc($barang_q)): 
                ?>
                  <tr>
                    <td style="padding-left: 16px;">
                      <input type="checkbox" name="selected_barang[]" value="<?= $barang['id'] ?>" class="barang-checkbox" onchange="updateDeleteButton()">
                    </td>
                    <td><?= $no++ ?></td>
                    <td><strong><?= htmlspecialchars($barang['labor_nama'] ?? '-') ?></strong></td>
                    <td><?= htmlspecialchars($barang['nama']) ?></td>
                    <td><strong><?= intval($barang['stok']) ?></strong> pcs</td>
                    <td>
                      <span class="badge <?= ($barang['status'] == 'Tersedia') ? 'badge-success' : 'badge-danger' ?>">
                        <i class="fas fa-<?= ($barang['status'] == 'Tersedia') ? 'check' : 'ban' ?> me-1"></i>
                        <?= htmlspecialchars($barang['status']) ?>
                      </span>
                    </td>
                    <td>
                      <div style="display: flex; gap: 8px;">
                        <button type="button" class="btn btn-sm" style="background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); color: white;" onclick="openEditModal(<?= $barang['id'] ?>, '<?= htmlspecialchars(addslashes($barang['labor_id'])) ?>', '<?= htmlspecialchars(addslashes($barang['nama'])) ?>', '<?= intval($barang['stok']) ?>', '<?= htmlspecialchars(addslashes($barang['status'])) ?>', '<?= htmlspecialchars(addslashes($barang['deskripsi'])) ?>')">
                          <i class="fas fa-edit"></i>
                        </button>
                        
                        <form method="post" style="display: inline;">
                          <input type="hidden" name="barang_id" value="<?= $barang['id'] ?>">
                          <button type="submit" name="hapus" class="btn btn-sm btn-danger" onclick="return confirm('Hapus peralatan ini?')">
                            <i class="fas fa-trash"></i>
                          </button>
                        </form>
                      </div>
                    </td>
                  </tr>
                <?php endwhile; ?>
              </tbody>
            </table>
          </form>
        </div>
      <?php else: ?>
        <div class="empty-state" id="emptyState">
          <i class="fas fa-inbox"></i>
          <p>Tidak ada peralatan yang sesuai dengan filter. Coba ubah kriteria pencarian.</p>
        </div>
      <?php endif; ?>
    </div>
  </div>



</div>

<!-- Modal Edit Peralatan -->
<div id="editPeralatanModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1050; align-items: center; justify-content: center;">
  <div style="background: var(--bg-primary); border-radius: 12px; padding: 32px; border: 2px solid var(--border-color); max-width: 500px; width: 90%; max-height: 90vh; overflow-y: auto; animation: slideUp 0.3s ease;">
    <div style="display: flex; align-items: center; gap: 14px; margin-bottom: 28px; padding-bottom: 24px; border-bottom: 2px solid var(--border-color);">
      <i class="fas fa-edit-circle" style="font-size: 28px; color: var(--primary);"></i>
      <h3 style="margin: 0; color: var(--text-primary); font-size: 22px; font-weight: 700;">Edit Peralatan</h3>
      <button type="button" onclick="closeEditModal()" style="margin-left: auto; background: none; border: none; font-size: 24px; color: var(--text-secondary); cursor: pointer;">
        <i class="fas fa-times"></i>
      </button>
    </div>

    <!-- Alert dalam modal -->
    <div id="editModalAlert" style="display: none; border-radius: 10px; border: 2px solid; margin-bottom: 20px; font-size: 14px; font-weight: 500; padding: 16px 20px; animation: slideDown 0.3s ease;" role="alert">
      <span id="editAlertMessage"></span>
    </div>

    <form id="editPeralatanForm" method="post" onsubmit="handleEditSubmitForm(event)">
      <div style="margin-bottom: 22px;">
        <label style="display: block; color: var(--text-primary); font-weight: 600; margin-bottom: 10px; font-size: 14px;">
          Labor <span style="color: var(--danger);">*</span>
        </label>
        <select name="labor_id" id="edit_labor_id" required style="width: 100%; padding: 12px 16px; border: 2px solid var(--border-color); border-radius: 8px; font-size: 14px; font-family: inherit; color: var(--text-primary); background: var(--bg-secondary); transition: all 0.3s ease;">
          <option value="">-- Pilih Labor --</option>
          <?php 
          mysqli_data_seek($labor_list, 0);
          while($l = mysqli_fetch_assoc($labor_list)): 
          ?>
            <option value="<?= $l['id'] ?>">
              <?= htmlspecialchars($l['nama']) ?>
            </option>
          <?php endwhile; ?>
        </select>
      </div>

      <div style="margin-bottom: 22px;">
        <label style="display: block; color: var(--text-primary); font-weight: 600; margin-bottom: 10px; font-size: 14px;">
          Nama Peralatan <span style="color: var(--danger);">*</span>
        </label>
        <input type="text" name="nama" id="edit_nama" placeholder="Contoh: Mikroskop Binokuler" required style="width: 100%; padding: 12px 16px; border: 2px solid var(--border-color); border-radius: 8px; font-size: 14px; font-family: inherit; color: var(--text-primary); background: var(--bg-secondary); transition: all 0.3s ease;">
      </div>

      <div style="margin-bottom: 22px;">
        <label style="display: block; color: var(--text-primary); font-weight: 600; margin-bottom: 10px; font-size: 14px;">
          Stok <span style="color: var(--danger);">*</span>
        </label>
        <input type="number" name="stok" id="edit_stok" min="1" placeholder="Jumlah stok" required value="1" style="width: 100%; padding: 12px 16px; border: 2px solid var(--border-color); border-radius: 8px; font-size: 14px; font-family: inherit; color: var(--text-primary); background: var(--bg-secondary); transition: all 0.3s ease;">
      </div>

      <div style="margin-bottom: 22px;">
        <label style="display: block; color: var(--text-primary); font-weight: 600; margin-bottom: 10px; font-size: 14px;">
          Status
        </label>
        <select name="status" id="edit_status" style="width: 100%; padding: 12px 16px; border: 2px solid var(--border-color); border-radius: 8px; font-size: 14px; font-family: inherit; color: var(--text-primary); background: var(--bg-secondary); transition: all 0.3s ease;">
          <option value="Tersedia">Tersedia</option>
          <option value="Tidak Tersedia">Tidak Tersedia</option>
        </select>
      </div>

      <div style="margin-bottom: 22px;">
        <label style="display: block; color: var(--text-primary); font-weight: 600; margin-bottom: 10px; font-size: 14px;">
          Deskripsi
        </label>
        <textarea name="deskripsi" id="edit_deskripsi" placeholder="Deskripsi singkat peralatan (opsional)" style="width: 100%; padding: 12px 16px; border: 2px solid var(--border-color); border-radius: 8px; font-size: 14px; font-family: inherit; color: var(--text-primary); background: var(--bg-secondary); transition: all 0.3s ease; resize: vertical; min-height: 100px;"></textarea>
      </div>

      <div style="display: flex; gap: 12px; margin-top: 28px; padding-top: 24px; border-top: 2px solid var(--border-color);">
        <button type="button" class="btn btn-secondary" onclick="closeEditModal()" style="flex: 1; justify-content: center;">
          <i class="fas fa-times"></i> Batal
        </button>
        <button type="submit" class="btn btn-primary" name="edit" style="flex: 1; justify-content: center;">
          <i class="fas fa-save"></i> Simpan
        </button>
      </div>
      <input type="hidden" name="barang_id" id="edit_barang_id" value="">
    </form>
  </div>
</div>

<!-- Modal Tambah Peralatan -->
<div id="addPeralatanModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1050; align-items: center; justify-content: center;">
  <div style="background: var(--bg-primary); border-radius: 12px; padding: 32px; border: 2px solid var(--border-color); max-width: 500px; width: 90%; max-height: 90vh; overflow-y: auto; animation: slideUp 0.3s ease;">
    <div style="display: flex; align-items: center; gap: 14px; margin-bottom: 28px; padding-bottom: 24px; border-bottom: 2px solid var(--border-color);">
      <i class="fas fa-plus-circle" style="font-size: 28px; color: var(--primary);"></i>
      <h3 style="margin: 0; color: var(--text-primary); font-size: 22px; font-weight: 700;">Tambah Peralatan</h3>
      <button type="button" onclick="closeAddModal()" style="margin-left: auto; background: none; border: none; font-size: 24px; color: var(--text-secondary); cursor: pointer;">
        <i class="fas fa-times"></i>
      </button>
    </div>

    <!-- Alert dalam modal -->
    <div id="modalAlert" style="display: none; border-radius: 10px; border: 2px solid; margin-bottom: 20px; font-size: 14px; font-weight: 500; padding: 16px 20px; animation: slideDown 0.3s ease;" role="alert">
      <span id="alertMessage"></span>
    </div>

    <form id="addPeralatanForm" method="post" onsubmit="handleSubmitForm(event)">
      <div style="margin-bottom: 22px;">
        <label style="display: block; color: var(--text-primary); font-weight: 600; margin-bottom: 10px; font-size: 14px;">
          Labor <span style="color: var(--danger);">*</span>
        </label>
        <select name="labor_id" id="labor_id" required style="width: 100%; padding: 12px 16px; border: 2px solid var(--border-color); border-radius: 8px; font-size: 14px; font-family: inherit; color: var(--text-primary); background: var(--bg-secondary); transition: all 0.3s ease;">
          <option value="">-- Pilih Labor --</option>
          <?php 
          mysqli_data_seek($labor_list, 0);
          while($l = mysqli_fetch_assoc($labor_list)): 
          ?>
            <option value="<?= $l['id'] ?>">
              <?= htmlspecialchars($l['nama']) ?>
            </option>
          <?php endwhile; ?>
        </select>
      </div>

      <div style="margin-bottom: 22px;">
        <label style="display: block; color: var(--text-primary); font-weight: 600; margin-bottom: 10px; font-size: 14px;">
          Nama Peralatan <span style="color: var(--danger);">*</span>
        </label>
        <input type="text" name="nama" id="modal_nama" placeholder="Contoh: Mikroskop Binokuler" required style="width: 100%; padding: 12px 16px; border: 2px solid var(--border-color); border-radius: 8px; font-size: 14px; font-family: inherit; color: var(--text-primary); background: var(--bg-secondary); transition: all 0.3s ease;">
      </div>

      <div style="margin-bottom: 22px;">
        <label style="display: block; color: var(--text-primary); font-weight: 600; margin-bottom: 10px; font-size: 14px;">
          Stok <span style="color: var(--danger);">*</span>
        </label>
        <input type="number" name="stok" id="modal_stok" min="1" placeholder="Jumlah stok" required value="1" style="width: 100%; padding: 12px 16px; border: 2px solid var(--border-color); border-radius: 8px; font-size: 14px; font-family: inherit; color: var(--text-primary); background: var(--bg-secondary); transition: all 0.3s ease;">
      </div>

      <div style="margin-bottom: 22px;">
        <label style="display: block; color: var(--text-primary); font-weight: 600; margin-bottom: 10px; font-size: 14px;">
          Deskripsi
        </label>
        <textarea name="deskripsi" id="modal_deskripsi" placeholder="Deskripsi singkat peralatan (opsional)" style="width: 100%; padding: 12px 16px; border: 2px solid var(--border-color); border-radius: 8px; font-size: 14px; font-family: inherit; color: var(--text-primary); background: var(--bg-secondary); transition: all 0.3s ease; resize: vertical; min-height: 100px;"></textarea>
      </div>

      <div style="display: flex; gap: 12px; margin-top: 28px; padding-top: 24px; border-top: 2px solid var(--border-color);">
        <button type="button" class="btn btn-secondary" onclick="closeAddModal()" style="flex: 1; justify-content: center;">
          <i class="fas fa-times"></i> Batal
        </button>
        <button type="submit" class="btn btn-primary" name="tambah" style="flex: 1; justify-content: center;">
          <i class="fas fa-save"></i> Simpan
        </button>
      </div>
    </form>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
  // Track modal changes
  let modalHasChanges = false;
  let modalAutoClose = false;  // Flag untuk auto-close tanpa confirmation
  const modalForm = document.getElementById('addPeralatanForm');
  const modalInputs = modalForm.querySelectorAll('input, select, textarea');

  // Monitor form changes
  modalInputs.forEach(input => {
    input.addEventListener('change', () => {
      modalHasChanges = true;
    });
    input.addEventListener('keyup', () => {
      modalHasChanges = true;
    });
  });

  function openAddModal() {
    modalHasChanges = false;
    modalAutoClose = false;
    resetModalForm();
    document.getElementById('addPeralatanModal').style.display = 'flex';
  }

  function closeAddModal() {
    // Jika auto-close, langsung tutup tanpa confirmation
    if(modalAutoClose) {
      document.getElementById('addPeralatanModal').style.display = 'none';
      resetModalForm();
      modalAutoClose = false;  // Reset flag
      return;
    }
    
    // Jika manual close dengan perubahan, tanya konfirmasi
    if(modalHasChanges) {
      if(confirm('Ada perubahan data. Apakah Anda yakin ingin menutup tanpa menyimpan?')) {
        document.getElementById('addPeralatanModal').style.display = 'none';
        resetModalForm();
      }
    } else {
      // Tidak ada perubahan, tutup langsung
      document.getElementById('addPeralatanModal').style.display = 'none';
      resetModalForm();
    }
  }

  function resetModalForm() {
    modalForm.reset();
    document.getElementById('modalAlert').style.display = 'none';
    document.getElementById('alertMessage').textContent = '';
    modalHasChanges = false;
  }

  function handleSubmitForm(event) {
    event.preventDefault();
    
    const formData = new FormData(modalForm);
    formData.append('tambah', '1');

    fetch('<?php echo $_SERVER['PHP_SELF']; ?>', {
      method: 'POST',
      body: formData
    })
    .then(response => response.text())
    .then(html => {
      // Set flag untuk auto-close tanpa confirmation
      modalAutoClose = true;
      closeAddModal();
      setTimeout(() => {
        location.reload();
      }, 500);
    })
    .catch(error => {
      console.error('Error:', error);
      const modalAlert = document.getElementById('modalAlert');
      const alertMessage = document.getElementById('alertMessage');
      modalAlert.className = 'alert alert-danger';
      alertMessage.textContent = 'Terjadi kesalahan saat menyimpan data';
      modalAlert.style.display = 'block';
    });
  }

  // Close modal when clicking outside
  document.getElementById('addPeralatanModal').addEventListener('click', function(e) {
    if(e.target === this) {
      closeAddModal();
    }
  });


  // Load dark mode from localStorage
  if(localStorage.getItem('darkMode') === 'true') {
    document.body.classList.add('dark-mode');
  }

  // Handle sidebar collapse on mobile
  if(localStorage.getItem('sidebarCollapsed') === 'true') {
    document.body.classList.add('sidebar-collapsed');
  }

  // Auto-dismiss alerts after 5 seconds
  document.querySelectorAll('.alert').forEach(function(alert) {
    setTimeout(function() {
      const bsAlert = new bootstrap.Alert(alert);
      bsAlert.close();
    }, 5000);
  });

  // AJAX Real-time Filtering
  const filterInputs = document.querySelectorAll('#search, #filter_labor, #sort_by');

  filterInputs.forEach(input => {
    input.addEventListener('change', performFilter);
    if(input.type === 'text') {
      input.addEventListener('keyup', performFilter);
    }
  });

  function performFilter() {
    const search = document.getElementById('search').value;
    const filter_labor = document.getElementById('filter_labor').value;
    const sort_by = document.getElementById('sort_by').value;

    // Build query parameters
    const params = new URLSearchParams();
    if(search) params.append('search', search);
    if(filter_labor) params.append('filter_labor', filter_labor);
    if(sort_by) params.append('sort_by', sort_by);
    params.append('ajax', '1');

    // Fetch data via AJAX
    fetch('peralatan_ajax.php?' + params.toString())
      .then(response => response.json())
      .then(data => {
        // Update total count
        document.getElementById('totalCount').textContent = data.total;
        
        // Update table body
        const tableBody = document.getElementById('tableBody');
        const tableContainer = document.querySelector('.table-responsive');
        const emptyState = document.getElementById('emptyState');

        tableBody.innerHTML = '';

        if(data.rows.length === 0) {
          if(tableContainer) tableContainer.style.display = 'none';
          if(!emptyState) {
            const newEmptyState = document.createElement('div');
            newEmptyState.className = 'empty-state';
            newEmptyState.id = 'emptyState';
            newEmptyState.innerHTML = `
              <i class="fas fa-inbox"></i>
              <p>Tidak ada peralatan yang sesuai dengan filter. Coba ubah kriteria pencarian.</p>
            `;
            document.querySelector('.table-card').appendChild(newEmptyState);
          } else {
            emptyState.style.display = 'block';
          }
          return;
        }

        if(tableContainer) tableContainer.style.display = 'block';
        if(emptyState) emptyState.style.display = 'none';

        data.rows.forEach(row => {
          const badgeClass = row.status === 'Tersedia' ? 'badge-success' : 'badge-danger';
          const badgeIcon = row.status === 'Tersedia' ? 'check' : 'ban';
          
          const tr = document.createElement('tr');
          tr.innerHTML = `
            <td>${row.no}</td>
            <td><strong>${escapeHtml(row.labor_nama)}</strong></td>
            <td>${escapeHtml(row.nama)}</td>
            <td><strong>${row.stok}</strong> pcs</td>
            <td>
              <span class="badge ${badgeClass}">
                <i class="fas fa-${badgeIcon} me-1"></i>
                ${escapeHtml(row.status)}
              </span>
            </td>
            <td>
              <div style="display: flex; gap: 8px;">
                <a href="edit_peralatan.php?id=${row.id}" class="btn btn-sm" style="background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); color: white;">
                  <i class="fas fa-edit"></i>
                </a>
                
                <form method="post" style="display: inline;">
                  <input type="hidden" name="barang_id" value="${row.id}">
                  <button type="submit" name="hapus" class="btn btn-sm btn-danger" onclick="return confirm('Hapus peralatan ini?')">
                    <i class="fas fa-trash"></i>
                  </button>
                </form>
              </div>
            </td>
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

  // ============= EDIT MODAL FUNCTIONS =============
  // Track edit modal changes
  let editModalHasChanges = false;
  let editModalAutoClose = false;  // Flag untuk auto-close tanpa confirmation
  const editModalForm = document.getElementById('editPeralatanForm');
  const editModalInputs = editModalForm.querySelectorAll('input, select, textarea');

  // Store original values for change detection
  let originalEditValues = {};

  // Monitor edit form changes
  editModalInputs.forEach(input => {
    input.addEventListener('change', () => {
      editModalHasChanges = true;
    });
    input.addEventListener('keyup', () => {
      editModalHasChanges = true;
    });
  });

  function openEditModal(barangId, laborId, nama, stok, status, deskripsi) {
    editModalHasChanges = false;
    editModalAutoClose = false;
    
    // Set form values
    document.getElementById('edit_barang_id').value = barangId;
    document.getElementById('edit_labor_id').value = laborId;
    document.getElementById('edit_nama').value = nama;
    document.getElementById('edit_stok').value = stok;
    document.getElementById('edit_status').value = status;
    document.getElementById('edit_deskripsi').value = deskripsi;
    
    // Store original values
    originalEditValues = {
      laborId: laborId,
      nama: nama,
      stok: stok,
      status: status,
      deskripsi: deskripsi
    };
    
    // Reset alert
    document.getElementById('editModalAlert').style.display = 'none';
    document.getElementById('editAlertMessage').textContent = '';
    
    // Show modal
    document.getElementById('editPeralatanModal').style.display = 'flex';
  }

  function closeEditModal() {
    // Jika auto-close, langsung tutup tanpa confirmation
    if(editModalAutoClose) {
      document.getElementById('editPeralatanModal').style.display = 'none';
      resetEditModalForm();
      editModalAutoClose = false;  // Reset flag
      return;
    }
    
    // Jika manual close dengan perubahan, tanya konfirmasi
    if(editModalHasChanges) {
      if(confirm('Ada perubahan data. Apakah Anda yakin ingin menutup tanpa menyimpan?')) {
        document.getElementById('editPeralatanModal').style.display = 'none';
        resetEditModalForm();
      }
    } else {
      // Tidak ada perubahan, tutup langsung
      document.getElementById('editPeralatanModal').style.display = 'none';
      resetEditModalForm();
    }
  }

  function resetEditModalForm() {
    editModalForm.reset();
    document.getElementById('editModalAlert').style.display = 'none';
    document.getElementById('editAlertMessage').textContent = '';
    editModalHasChanges = false;
  }

  function handleEditSubmitForm(event) {
    event.preventDefault();
    
    const formData = new FormData(editModalForm);
    formData.append('edit', '1');

    fetch('<?php echo $_SERVER['PHP_SELF']; ?>', {
      method: 'POST',
      body: formData
    })
    .then(response => response.text())
    .then(html => {
      // Set flag untuk auto-close tanpa confirmation
      editModalAutoClose = true;
      closeEditModal();
      setTimeout(() => {
        location.reload();
      }, 500);
    })
    .catch(error => {
      console.error('Error:', error);
      const editModalAlert = document.getElementById('editModalAlert');
      const editAlertMessage = document.getElementById('editAlertMessage');
      editModalAlert.className = 'alert alert-danger';
      editAlertMessage.textContent = 'Terjadi kesalahan saat menyimpan data';
      editModalAlert.style.display = 'block';
    });
  }

  // Close edit modal when clicking outside
  document.getElementById('editPeralatanModal').addEventListener('click', function(e) {
    if(e.target === this) {
      closeEditModal();
    }
  });

  // Fungsi untuk checkbox dan delete massal
  function toggleSelectAll(checkbox) {
    const allCheckboxes = document.querySelectorAll('.barang-checkbox');
    allCheckboxes.forEach(cb => {
      cb.checked = checkbox.checked;
    });
    updateDeleteButton();
  }

  function updateDeleteButton() {
    const selectedCheckboxes = document.querySelectorAll('.barang-checkbox:checked');
    const deleteBtn = document.getElementById('deleteMassalBtn');
    const selectedCount = document.getElementById('selectedCount');
    const countSelected = document.getElementById('countSelected');
    const selectAllCheckbox = document.getElementById('selectAll');

    if(selectedCheckboxes.length > 0) {
      deleteBtn.style.display = 'inline-flex';
      selectedCount.style.display = 'inline-block';
      countSelected.textContent = selectedCheckboxes.length;
    } else {
      deleteBtn.style.display = 'none';
      selectedCount.style.display = 'none';
      selectAllCheckbox.checked = false;
    }
  }

  function deleteSelected() {
    const selectedCheckboxes = document.querySelectorAll('.barang-checkbox:checked');
    
    if(selectedCheckboxes.length === 0) {
      alert('Pilih minimal 1 peralatan untuk dihapus');
      return;
    }

    const count = selectedCheckboxes.length;
    if(confirm(`Apakah Anda yakin ingin menghapus ${count} peralatan?`)) {
      // Buat form untuk submit
      const form = document.createElement('form');
      form.method = 'POST';
      form.style.display = 'none';

      // Tambahkan checkbox yang dipilih
      selectedCheckboxes.forEach(cb => {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'selected_barang[]';
        input.value = cb.value;
        form.appendChild(input);
      });

      // Tambahkan hidden input untuk hapus_massal
      const hapusInput = document.createElement('input');
      hapusInput.type = 'hidden';
      hapusInput.name = 'hapus_massal';
      hapusInput.value = '1';
      form.appendChild(hapusInput);

      document.body.appendChild(form);
      form.submit();
    }
  }

  // Listen untuk perubahan checkbox
  document.querySelectorAll('.barang-checkbox').forEach(checkbox => {
    checkbox.addEventListener('change', updateDeleteButton);
  });
</script>
</body>
</html>