<?php
session_start();
include '../config/database.php';

// Check if user is logged in
if(!isset($_SESSION['admin_id'])){
  http_response_code(401);
  echo json_encode(['error' => 'Unauthorized']);
  exit;
}

// Check if it's AJAX request
if(!isset($_GET['ajax'])) {
  http_response_code(400);
  echo json_encode(['error' => 'Invalid request']);
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

// Get total count
$total_q = mysqli_query($conn, "
  SELECT COUNT(*) as total FROM peminjaman p
  JOIN users u ON p.user_id=u.id
  LEFT JOIN labor l ON p.labor_id=l.id
  LEFT JOIN barang_labor bl ON p.barang_labor_id=bl.id
  WHERE $where
");
$total_row = mysqli_fetch_assoc($total_q);
$total_items = $total_row['total'];

// Get data
$q = mysqli_query($conn, "
  SELECT p.*, u.nama, u.nim, l.nama AS labor_nama, bl.nama AS barang_nama
  FROM peminjaman p
  JOIN users u ON p.user_id=u.id
  LEFT JOIN labor l ON p.labor_id=l.id
  LEFT JOIN barang_labor bl ON p.barang_labor_id=bl.id
  WHERE $where
  ORDER BY p.created_at DESC
");

// Prepare response
$rows = [];
while($d = mysqli_fetch_assoc($q)) {
  $rows[] = [
    'nama' => $d['nama'],
    'nim' => $d['nim'],
    'barang_nama' => $d['barang_nama'] ?? '-',
    'jumlah' => intval($d['jumlah']),
    'tanggal' => date('d-m-Y H:i', strtotime($d['created_at'])),
    'tanggal_kembali' => $d['tanggal_kembali'] ? date('d-m-Y H:i', strtotime($d['tanggal_kembali'])) : '-',
    'status' => $d['status']
  ];
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode([
  'total' => $total_items,
  'rows' => $rows
]);
?>
