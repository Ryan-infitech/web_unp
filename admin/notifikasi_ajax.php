<?php
session_start();
include '../config/database.php';

// Cek autentikasi
if(!isset($_SESSION['admin_id'])){
  die("Unauthorized");
}

// Get incoming notification count (pending and in-process returns)
if(isset($_GET['get_incoming_count'])){
  $count = mysqli_fetch_assoc(mysqli_query($conn, 
    "SELECT COUNT(*) as total FROM peminjaman 
     WHERE status = 'Menunggu' OR (status = 'Disetujui' AND status_pengembalian = 0)"
  ))['total'] ?? 0;
  
  header('Content-Type: application/json');
  echo json_encode(['count' => (int)$count, 'success' => true]);
  exit;
}

// Search users
if(isset($_GET['search'])){
  $search = mysqli_real_escape_string($conn, $_GET['search']);
  
  $query = "SELECT id, nama, email, nim FROM users WHERE is_active = 1 AND (nama LIKE '%$search%' OR email LIKE '%$search%' OR nim LIKE '%$search%') ORDER BY nama ASC LIMIT 10";
  
  $result = mysqli_query($conn, $query);
  
  if(mysqli_num_rows($result) > 0){
    while($user = mysqli_fetch_assoc($result)){
      echo '<div class="suggestion-item" onclick="selectUser('.$user['id'].', \''.addslashes($user['nama']).'\', \''.addslashes($user['email']).'\')">';
      echo '<div>';
      echo '<div style="font-weight: 600; color: var(--text-primary);">'.$user['nama'].'</div>';
      echo '<div style="font-size: 12px; color: var(--text-secondary);">'.$user['email'].' • NIM: '.$user['nim'].'</div>';
      echo '</div>';
      echo '<i class="fas fa-plus" style="color: var(--primary);"></i>';
      echo '</div>';
    }
  } else {
    echo '<div class="suggestion-item" style="cursor: default; justify-content: center;">';
    echo '<span style="color: var(--text-secondary);">Tidak ada user yang ditemukan</span>';
    echo '</div>';
  }
  exit;
}

// Get users by IDs
if(isset($_GET['get_users'])){
  $userIds = explode(',', mysqli_real_escape_string($conn, $_GET['get_users']));
  $userIds = array_filter($userIds); // Remove empty values
  
  if(empty($userIds)){
    echo '<p style="color: var(--text-secondary); font-size: 13px; margin: 0;">Belum ada user yang dipilih</p>';
    exit;
  }
  
  $idList = implode(',', array_map('intval', $userIds));
  $query = "SELECT id, nama, email FROM users WHERE id IN ($idList) ORDER BY FIELD(id, $idList)";
  
  $result = mysqli_query($conn, $query);
  
  if(mysqli_num_rows($result) > 0){
    while($user = mysqli_fetch_assoc($result)){
      echo '<div class="user-chip">';
      echo '<i class="fas fa-user-circle"></i>';
      echo '<span>'.$user['nama'].' ('.substr($user['email'], 0, 15).'...)</span>';
      echo '<span class="remove" onclick="removeUser('.$user['id'].')" title="Hapus">✕</span>';
      echo '</div>';
    }
  }
  exit;
}

?>
