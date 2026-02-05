<?php
// get_admin_unread_notifications.php
// Endpoint untuk fetch jumlah unread notifications untuk admin HANYA yang is_read=0

session_start();
if(!isset($_SESSION['admin_id'])) {
  header('Content-Type: application/json');
  echo json_encode(['count' => 0, 'error' => 'Not authenticated']);
  exit;
}

include 'database.php';

// Ensure recipient_type column exists
$check_recipient = mysqli_query($conn, "SHOW COLUMNS FROM notifications LIKE 'recipient_type'");
if(mysqli_num_rows($check_recipient) == 0){
  mysqli_query($conn, "ALTER TABLE notifications ADD COLUMN recipient_type VARCHAR(20) DEFAULT NULL AFTER user_id");
}

$admin_id = intval($_SESSION['admin_id']);

// Get unread notifications count for admin - HANYA yang is_read = 0 dan recipient_type = 'admin'
$result = mysqli_query($conn, "SELECT COUNT(*) as count FROM notifications WHERE user_id = $admin_id AND recipient_type = 'admin' AND is_read = 0");

if(!$result) {
  header('Content-Type: application/json');
  echo json_encode(['count' => 0, 'error' => mysqli_error($conn)]);
  exit;
}

$data = mysqli_fetch_assoc($result);

header('Content-Type: application/json');
echo json_encode([
  'count' => (int)($data['count'] ?? 0),
  'success' => true
]);
?>
