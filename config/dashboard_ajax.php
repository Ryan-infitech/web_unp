<?php
session_start();
include 'database.php';

// Check if user is logged in
if(!isset($_SESSION['user_id'])){
  http_response_code(401);
  echo json_encode(['error' => 'Unauthorized']);
  exit;
}

$user_id = intval($_SESSION['user_id']);

// Get stats for user's peminjaman
$stats_q = mysqli_query($conn, "SELECT 
  COUNT(CASE WHEN status = 'Menunggu' THEN 1 END) as menunggu,
  COUNT(CASE WHEN status = 'Disetujui' AND status_pengembalian = 0 THEN 1 END) as disetujui,
  COUNT(CASE WHEN status = 'Ditolak' THEN 1 END) as ditolak,
  COUNT(CASE WHEN status = 'Dikembalikan' OR status_pengembalian = 1 THEN 1 END) as dikembalikan,
  COUNT(*) as total
FROM peminjaman WHERE user_id = $user_id
");
$stats = mysqli_fetch_assoc($stats_q);

// Get latest notifications
$notifs_q = mysqli_query($conn, "SELECT 
  id, user_id, title, message, is_read, created_at
  FROM notifications 
  WHERE user_id = $user_id 
  ORDER BY created_at DESC 
  LIMIT 5
");

$notifications = [];
while($notif = mysqli_fetch_assoc($notifs_q)) {
  $created = new DateTime($notif['created_at']);
  $now = new DateTime();
  $interval = $now->diff($created);
  
  if($interval->days > 0) {
    $time_text = $interval->days . ' hari lalu';
  } elseif($interval->h > 0) {
    $time_text = $interval->h . ' jam lalu';
  } elseif($interval->i > 0) {
    $time_text = $interval->i . ' menit lalu';
  } else {
    $time_text = 'Baru saja';
  }
  
  $notifications[] = [
    'id' => $notif['id'],
    'title' => $notif['title'],
    'message' => $notif['message'],
    'is_read' => $notif['is_read'],
    'created_at' => $time_text
  ];
}

// Get unread count
$unread_q = mysqli_query($conn, "SELECT COUNT(*) as count FROM notifications WHERE user_id = $user_id AND is_read = 0");
$unread_row = mysqli_fetch_assoc($unread_q);
$unread_count = $unread_row['count'];

// Return JSON response
header('Content-Type: application/json');
echo json_encode([
  'stats' => $stats,
  'notifications' => $notifications,
  'unread_count' => $unread_count
]);
?>
