<?php
session_start();
include '../config/database.php';

if(!isset($_SESSION['user_id'])){
  header("HTTP/1.1 401 Unauthorized");
  exit;
}

$id = intval($_SESSION['user_id']);
$response = ['success' => false, 'message' => ''];

if(isset($_POST['simpan'])){
  $nama = mysqli_real_escape_string($conn, $_POST['nama']);
  $email = mysqli_real_escape_string($conn, $_POST['email']);
  
  if(empty($nama)){
    $response['message'] = 'Nama harus diisi!';
    echo json_encode($response);
    exit;
  }
  
  // Handle foto upload
  $foto = null;
  if(isset($_FILES['foto']) && $_FILES['foto']['error'] == 0){
    $allowed = ['jpg', 'jpeg', 'png', 'gif'];
    $filename = $_FILES['foto']['name'];
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    
    if(!in_array($ext, $allowed)){
      $response['message'] = 'Format file tidak didukung! Gunakan JPG, JPEG, PNG, atau GIF.';
      echo json_encode($response);
      exit;
    }
    
    if($_FILES['foto']['size'] > 5000000){ // 5MB
      $response['message'] = 'Ukuran file terlalu besar! Maksimal 5MB.';
      echo json_encode($response);
      exit;
    }
    
    // Generate nama file unik
    $foto = 'profile_' . $id . '_' . time() . '.' . $ext;
    $target_dir = '../assets/img/';
    
    if(!is_dir($target_dir)){
      mkdir($target_dir, 0777, true);
    }
    
    if(move_uploaded_file($_FILES['foto']['tmp_name'], $target_dir . $foto)){
      // Hapus foto lama jika ada
      $old_user = mysqli_fetch_assoc(mysqli_query($conn, "SELECT foto FROM users WHERE id=$id"));
      if($old_user['foto'] && file_exists($target_dir . $old_user['foto'])){
        unlink($target_dir . $old_user['foto']);
      }
    } else {
      $response['message'] = 'Gagal mengupload foto!';
      echo json_encode($response);
      exit;
    }
  }
  
  // Build query
  $set_clause = "nama='$nama', email='$email'";
  if($foto){
    $set_clause .= ", foto='$foto'";
  }
  
  $upd = mysqli_query($conn, "UPDATE users SET $set_clause WHERE id=$id");
  
  if($upd){
    $response['success'] = true;
    $response['message'] = 'Profil berhasil diperbarui!';
  } else {
    $response['message'] = 'Terjadi kesalahan: ' . mysqli_error($conn);
  }
}

echo json_encode($response);
?>
