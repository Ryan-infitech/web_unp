<?php
session_start();
include '../config/database.php';

if(!isset($_SESSION['user_id'])){
  header("HTTP/1.1 401 Unauthorized");
  exit;
}

$id = intval($_SESSION['user_id']);
$response = ['success' => false, 'message' => ''];

// Handle upload foto
if(isset($_POST['action']) && $_POST['action'] == 'upload_foto'){
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
      
      // Update database
      $upd = mysqli_query($conn, "UPDATE users SET foto='$foto' WHERE id=$id");
      if($upd){
        $response['success'] = true;
        $response['message'] = 'Foto profil berhasil diperbarui!';
        $response['foto'] = $foto;
      } else {
        $response['message'] = 'Gagal menyimpan data ke database!';
      }
    } else {
      $response['message'] = 'Gagal mengupload foto!';
    }
  } else {
    $response['message'] = 'File tidak ditemukan!';
  }
}

// Handle update password
else if(isset($_POST['action']) && $_POST['action'] == 'update_password'){
  $password_lama = isset($_POST['password_lama']) ? $_POST['password_lama'] : '';
  $password_baru = isset($_POST['password_baru']) ? $_POST['password_baru'] : '';
  $password_confirm = isset($_POST['password_confirm']) ? $_POST['password_confirm'] : '';
  
  // Validasi
  if(!$password_lama || !$password_baru){
    $response['message'] = 'Password saat ini dan password baru harus diisi!';
    echo json_encode($response);
    exit;
  }

  if(strlen($password_baru) < 6){
    $response['message'] = 'Password baru minimal 6 karakter!';
    echo json_encode($response);
    exit;
  }

  if($password_baru !== $password_confirm){
    $response['message'] = 'Password baru dan konfirmasi tidak cocok!';
    echo json_encode($response);
    exit;
  }

  // Verifikasi password lama
  $user = mysqli_fetch_assoc(mysqli_query($conn, "SELECT password FROM users WHERE id=$id"));
  if(!password_verify($password_lama, $user['password'])){
    $response['message'] = 'Password saat ini salah!';
    echo json_encode($response);
    exit;
  }

  // Hash dan update password
  $hashed_password = password_hash($password_baru, PASSWORD_DEFAULT);
  $upd = mysqli_query($conn, "UPDATE users SET password='$hashed_password' WHERE id=$id");
  
  if($upd){
    $response['success'] = true;
    $response['message'] = 'Password berhasil diperbarui!';
  } else {
    $response['message'] = 'Terjadi kesalahan: ' . mysqli_error($conn);
  }
}

echo json_encode($response);
?>
