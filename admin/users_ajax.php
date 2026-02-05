<?php
session_start();
include '../config/database.php';

// Cek autentikasi
if(!isset($_SESSION['admin_id'])){
  die("Unauthorized");
}

$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$filter = isset($_GET['filter']) ? mysqli_real_escape_string($conn, $_GET['filter']) : '';

// Query untuk admin
$adminQuery = "SELECT id, nama, username, 'admin' as role, CURRENT_TIMESTAMP as created_at FROM admin WHERE 1=1";
if($search) {
  $adminQuery .= " AND (nama LIKE '%$search%' OR username LIKE '%$search%')";
}

// Query untuk mahasiswa
$userQuery = "SELECT id, nama, email, nim, 'mahasiswa' as role, is_active, created_at FROM users WHERE 1=1";
if($search) {
  $userQuery .= " AND (nama LIKE '%$search%' OR email LIKE '%$search%' OR nim LIKE '%$search%')";
}

$results = array();

// Get admin
if(!$filter || $filter === 'admin') {
  $adminResult = mysqli_query($conn, $adminQuery . " ORDER BY id DESC");
  while($row = mysqli_fetch_assoc($adminResult)) {
    $results[] = $row;
  }
}

// Get users
if(!$filter || $filter === 'mahasiswa') {
  $userResult = mysqli_query($conn, $userQuery . " ORDER BY created_at DESC");
  while($row = mysqli_fetch_assoc($userResult)) {
    $results[] = $row;
  }
}

// Sort by latest added (newest first)
usort($results, function($a, $b) {
  return strtotime($b['id'] ?? '0') - strtotime($a['id'] ?? '0');
});

if(empty($results)) {
  echo '<div class="empty-state">
    <i class="fas fa-search"></i>
    <p>Tidak ada user yang ditemukan</p>
  </div>';
  exit;
}

foreach($results as $user) {
  $role = $user['role'];
  $roleLabel = $role === 'admin' ? 'Admin' : 'Mahasiswa';
  $badgeClass = $role === 'admin' ? 'badge-admin' : 'badge-mahasiswa';
  
  echo '<div class="user-card">';
  
  // User Header dengan Badge
  echo '<div class="user-header">';
  echo '<div>';
  echo '<div class="user-badges">';
  echo '<span class="user-badge '.$badgeClass.'"><i class="fas fa-'.($role === 'admin' ? 'shield-alt' : 'user-graduate').'"></i> '.$roleLabel.'</span>';
  
  if($role === 'mahasiswa') {
    if($user['is_active']) {
      echo ' <span class="user-badge badge-active"><i class="fas fa-check-circle"></i> Aktif</span>';
    } else {
      echo ' <span class="user-badge badge-inactive"><i class="fas fa-times-circle"></i> Tidak Aktif</span>';
    }
  }
  echo '</div>';
  echo '</div>';
  echo '</div>';
  
  // User Name
  echo '<div class="user-name">'.$user['nama'].'</div>';
  
  // User Details
  echo '<div class="user-details">';
  
  if($role === 'admin') {
    echo '<div class="user-detail-item"><i class="fas fa-user-tag"></i> <strong>Username:</strong> '.$user['username'].'</div>';
  } else {
    echo '<div class="user-detail-item"><i class="fas fa-envelope"></i> <strong>Email:</strong> '.$user['email'].'</div>';
    echo '<div class="user-detail-item"><i class="fas fa-id-card"></i> <strong>NIM:</strong> '.$user['nim'].'</div>';
  }
  
  echo '</div>';
  
  // User Actions
  echo '<div class="user-actions">';
  
  // Edit button
  if($role === 'admin') {
    echo '<button class="btn-action btn-edit" onclick="openModalEditAdmin('.$user['id'].', \''.addslashes($user['nama']).'\', \''.addslashes($user['username']).'\')">';
    echo '<i class="fas fa-edit"></i> Edit';
    echo '</button>';
  } else {
    echo '<button class="btn-action btn-edit" onclick="openModalEditMahasiswa('.$user['id'].', \''.addslashes($user['nama']).'\', \''.addslashes($user['email']).'\', \''.addslashes($user['nim']).'\')">';
    echo '<i class="fas fa-edit"></i> Edit';
    echo '</button>';
  }
  
  // Toggle status button for mahasiswa
  if($role === 'mahasiswa') {
    $statusClass = $user['is_active'] ? 'btn-toggle' : 'btn-toggle inactive';
    $statusIcon = $user['is_active'] ? 'fa-ban' : 'fa-check';
    $statusText = $user['is_active'] ? 'Nonaktifkan' : 'Aktifkan';
    echo '<button class="btn-action '.$statusClass.'" onclick="toggleStatus('.$user['id'].', '.$user['is_active'].')">';
    echo '<i class="fas '.$statusIcon.'"></i> '.$statusText;
    echo '</button>';
  }
  
  // Delete button
  echo '<button class="btn-action btn-delete" onclick="deleteUser('.$user['id'].', \''.$role.'\', \''.addslashes($user['nama']).'\')">';
  echo '<i class="fas fa-trash"></i> Hapus';
  echo '</button>';
  
  echo '</div>';
  echo '</div>';
}
?>
