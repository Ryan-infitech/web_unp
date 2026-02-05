<?php
session_start();
include 'database.php';

// Ensure recipient_type column exists
$check_recipient = mysqli_query($conn, "SHOW COLUMNS FROM notifications LIKE 'recipient_type'");
if(mysqli_num_rows($check_recipient) == 0){
  mysqli_query($conn, "ALTER TABLE notifications ADD COLUMN recipient_type VARCHAR(20) DEFAULT NULL AFTER user_id");
}

header('Content-Type: application/json');

$user_id = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : 0;

if($user_id > 0) {
  $result = mysqli_query($conn, "SELECT COUNT(*) as count FROM notifications WHERE user_id=$user_id AND (recipient_type='user' OR recipient_type IS NULL OR recipient_type='') AND is_read=0");
  $data = mysqli_fetch_assoc($result);
  echo json_encode(['count' => intval($data['count'])]);
} else {
  echo json_encode(['count' => 0]);
}
