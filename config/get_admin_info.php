<?php
session_start();
include 'database.php';

// Check if user is logged in
if(!isset($_SESSION['admin_id'])){
  http_response_code(401);
  echo json_encode(['error' => 'Unauthorized']);
  exit;
}

// Get admin info
$id = $_SESSION['admin_id'];
$admin_q = mysqli_query($conn, "SELECT id, nama, username FROM admin WHERE id=$id");
$admin = mysqli_fetch_assoc($admin_q);

if(!$admin) {
  http_response_code(404);
  echo json_encode(['error' => 'Admin tidak ditemukan']);
  exit;
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode([
  'id' => $admin['id'],
  'nama' => $admin['nama'] ?? $admin['username'],
  'username' => $admin['username']
]);
?>
