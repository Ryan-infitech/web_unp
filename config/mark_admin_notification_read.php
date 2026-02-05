<?php
// mark_admin_notification_read.php
// AJAX endpoint untuk mark admin notification sebagai read

session_start();
if(!isset($_SESSION['admin_id'])) {
  header('Content-Type: application/json');
  echo json_encode(['error' => 'Not authenticated']);
  exit;
}

include 'database.php';

$admin_id = intval($_SESSION['admin_id']);

if(isset($_POST['notif_id'])) {
  $notif_id = intval($_POST['notif_id']);
  
  // Update notification to mark as read
  $update = mysqli_query($conn, 
    "UPDATE notifications SET is_read = 1 WHERE id = $notif_id AND user_id = $admin_id"
  );
  
  header('Content-Type: application/json');
  echo json_encode(['success' => $update ? true : false]);
  exit;
}

if(isset($_POST['delete_id'])) {
  $notif_id = intval($_POST['delete_id']);
  
  // Delete notification
  $delete = mysqli_query($conn, 
    "DELETE FROM notifications WHERE id = $notif_id AND user_id = $admin_id"
  );
  
  header('Content-Type: application/json');
  echo json_encode(['success' => $delete ? true : false]);
  exit;
}

header('Content-Type: application/json');
echo json_encode(['error' => 'Invalid request']);
?>
