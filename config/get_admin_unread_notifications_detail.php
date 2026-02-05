<?php
// get_admin_unread_notifications_detail.php
// Endpoint untuk mark notifikasi admin sebagai read

session_start();
if(!isset($_SESSION['admin_id'])) {
  header('Content-Type: application/json');
  echo json_encode(['error' => 'Not authenticated']);
  exit;
}

include 'database.php';

// Ensure recipient_type column exists
$check_recipient = mysqli_query($conn, "SHOW COLUMNS FROM notifications LIKE 'recipient_type'");
if(mysqli_num_rows($check_recipient) == 0){
  mysqli_query($conn, "ALTER TABLE notifications ADD COLUMN recipient_type VARCHAR(20) DEFAULT NULL AFTER user_id");
}

$admin_id = intval($_SESSION['admin_id']);

// Mark as read
if(isset($_POST['mark_read'])) {
  $notif_id = intval($_POST['notif_id']);
  $update = mysqli_query($conn, "UPDATE notifications SET is_read = 1 WHERE id = $notif_id AND user_id = $admin_id AND recipient_type = 'admin'");
  header('Content-Type: application/json');
  echo json_encode(['success' => $update ? true : false]);
  exit;
}

// Delete notification
if(isset($_POST['delete'])) {
  $notif_id = intval($_POST['notif_id']);
  $delete = mysqli_query($conn, "DELETE FROM notifications WHERE id = $notif_id AND user_id = $admin_id AND recipient_type = 'admin'");
  header('Content-Type: application/json');
  echo json_encode(['success' => $delete ? true : false]);
  exit;
}

// Get unread notifications count for admin (only is_read = 0 dan recipient_type = 'admin')
$result = mysqli_query($conn, "SELECT COUNT(*) as count FROM notifications WHERE user_id = $admin_id AND recipient_type = 'admin' AND is_read = 0");
$data = mysqli_fetch_assoc($result);

header('Content-Type: application/json');
echo json_encode([
  'count' => (int)$data['count'],
  'success' => true
]);
?>
