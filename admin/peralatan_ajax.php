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
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$filter_labor = isset($_GET['filter_labor']) ? intval($_GET['filter_labor']) : '';
$sort_by = isset($_GET['sort_by']) ? mysqli_real_escape_string($conn, $_GET['sort_by']) : 'name_asc';

// Build WHERE clause
$where = "1=1";
if(!empty($search)) {
  $where .= " AND bl.nama LIKE '%$search%'";
}
if(!empty($filter_labor)) {
  $where .= " AND bl.labor_id = $filter_labor";
}

// Build SORT clause
$order_by = "bl.nama ASC";
if($sort_by === 'name_desc') {
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

// Get total count
$total_q = mysqli_query($conn, "SELECT COUNT(*) as total FROM barang_labor bl LEFT JOIN labor l ON bl.labor_id=l.id WHERE $where");
$total_row = mysqli_fetch_assoc($total_q);
$total_peralatan = $total_row['total'];

// Get data
$barang_q = mysqli_query($conn, "SELECT bl.*, l.nama as labor_nama FROM barang_labor bl LEFT JOIN labor l ON bl.labor_id=l.id WHERE $where ORDER BY $order_by");

// Prepare response
$rows = [];
$no = 1;
while($barang = mysqli_fetch_assoc($barang_q)) {
  $rows[] = [
    'no' => $no++,
    'id' => $barang['id'],
    'labor_nama' => $barang['labor_nama'] ?? '-',
    'nama' => $barang['nama'],
    'stok' => intval($barang['stok']),
    'status' => $barang['status']
  ];
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode([
  'total' => $total_peralatan,
  'rows' => $rows
]);
?>
