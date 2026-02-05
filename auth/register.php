<?php
include '../config/database.php';

$error = '';
$success = '';

if(isset($_POST['daftar'])){
  $email = mysqli_real_escape_string($conn, $_POST['email']);
  $nama = mysqli_real_escape_string($conn, $_POST['nama']);
  $nim = mysqli_real_escape_string($conn, $_POST['nim']);
  $password = $_POST['password'];
  $foto = $_FILES['foto']['name'];

  // Validasi input
  if(empty($email) || empty($nama) || empty($nim) || empty($password)){
    $error = "Semua field harus diisi!";
  } else if(strlen($password) < 6){
    $error = "Password minimal 6 karakter!";
  } else {
    // Cek apakah NIM sudah terdaftar
    $check = mysqli_query($conn, "SELECT id FROM users WHERE nim='$nim'");
    if(!$check){
      $error = "Terjadi kesalahan database: " . mysqli_error($conn);
    } else if(mysqli_num_rows($check) > 0){
      $error = "NIM sudah terdaftar!";
    } else {
      // Hash password
      $hashed_pass = password_hash($password, PASSWORD_DEFAULT);
      
      // Upload foto jika ada
      $foto_final = '';
      if(!empty($foto) && $_FILES['foto']['error'] == 0){
        $upload_dir = "../assets/img/";
        // Buat direktori jika belum ada
        if(!is_dir($upload_dir)){
          mkdir($upload_dir, 0755, true);
        }
        
        if(move_uploaded_file($_FILES['foto']['tmp_name'], $upload_dir.$foto)){
          $foto_final = $foto;
        }
      }
      
      // Simpan ke database
      $insert = mysqli_query($conn, "INSERT INTO users(email, nama, nim, password, foto, is_active) VALUES('$email', '$nama', '$nim', '$hashed_pass', '$foto_final', 0)");
      
      if($insert){
        $success = "Registrasi berhasil! Akun Anda perlu diaktifkan oleh admin terlebih dahulu sebelum bisa login. Silakan tunggu konfirmasi dari admin.";
        $_POST = array();
      } else {
        $error = "Terjadi kesalahan: " . mysqli_error($conn);
      }
    }
  }
}
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Register Mahasiswa - Sistem Peminjaman</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
  * {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
  }
  
  body {
    background: linear-gradient(135deg, #0d4d4d 0%, #1a6b6b 50%, #0d4d4d 100%);
    min-height: 100vh;
    font-family: 'Cooper Black', 'Arial Black', sans-serif;
    position: relative;
    overflow-y: auto;
    overflow-x: hidden;
    display: flex;
    align-items: center;
    justify-content: center;
  }
  
  body::before {
    content: '';
    position: absolute;
    width: 100%;
    height: 100%;
    background: 
      linear-gradient(45deg, transparent 48%, rgba(255,255,255,0.03) 49%, rgba(255,255,255,0.03) 51%, transparent 52%),
      linear-gradient(-45deg, transparent 48%, rgba(255,255,255,0.03) 49%, rgba(255,255,255,0.03) 51%, transparent 52%);
    background-size: 80px 80px;
    opacity: 0.5;
  }
  
  .header-box {
    position: absolute;
    top: 20px;
    left: 50%;
    transform: translateX(-50%);
    background: rgba(135, 206, 235, 0.4);
    backdrop-filter: blur(10px);
    padding: 20px 60px;
    border-radius: 20px;
    text-align: center;
    box-shadow: 0 8px 32px rgba(0,0,0,0.2);
    border: 2px solid rgba(255,255,255,0.2);
    z-index: 100;
  }
  
  .header-box h2 {
    color: white;
    font-size: 24px;
    font-weight: 900;
    margin: 0;
    text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
    letter-spacing: 1px;
  }
  
  .header-box p {
    color: white;
    font-size: 14px;
    margin: 5px 0 0 0;
    font-weight: 600;
    text-shadow: 1px 1px 2px rgba(0,0,0,0.3);
  }
  
  .logo-container {
    position: absolute;
    top: 10px;
    left: 30px;
    display: flex;
    align-items: center;
    gap: 15px;
    z-index: 101;
  }
  
  .logo-circle {
    width: 100px;
    height: 100px;
    background: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 8px 28px rgba(0,0,0,0.4);
    border: 5px solid rgba(135, 206, 235, 0.65);
  }
  
  .logo-circle img {
    width: 100px;
    height: 100px;
    object-fit: contain;
  }
  
  .logo-text h3 {
    color: rgba(19, 236, 236, 0.9);
    text-shadow: 2px 2px 4px rgba(0,0,0,0.4);
    font-size: 20px;
    margin: 0;
    font-weight: 900;
    letter-spacing: 0.5px;
  }
  
  .logo-text p {
    color: rgba(255, 251, 40, 0.9);
    text-shadow: 2px 2px 4px rgba(0,0,0,0.4);
    font-size: 12px;
    margin: 0;
    font-weight: 600;
    opacity: 0.9;
  }
  
  .login-container {
    position: relative;
    z-index: 10;
    padding: 50px 0;
  }
  
  .card {
    border: none;
    border-radius: 30px;
    background: linear-gradient(180deg, #40e0d0 0%, #20b2aa 100%);
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5),
                0 0 0 3px rgba(0,0,0,0.3);
    overflow: hidden;
    border: 4px solid rgba(0,0,0,0.4);
  }
  
  .card-body {
    padding: 50px 45px;
  }
  
  h4 {
    color: white;
    font-weight: 900;
    margin-bottom: 30px;
    font-size: 36px;
    text-align: center;
    text-shadow: 3px 3px 6px rgba(0,0,0,0.3);
    letter-spacing: 1px;
  }
  
  .form-group {
    margin-bottom: 25px;
  }
  
  .form-row {
    display: flex;
    flex-direction: column;
    align-items: stretch;
    gap: 8px;
    margin-bottom: 20px;
  }
  
  .form-label {
    color: white;
    font-weight: 900;
    font-size: 15px;
    text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
    display: block;
    margin-bottom: 6px;
  }
  
  .form-control {
    border: none;
    border-radius: 10px;
    padding: 12px 18px;
    font-size: 16px;
    background: white;
    box-shadow: inset 0 2px 5px rgba(0,0,0,0.2);
    font-weight: 600;
  }
  
  .form-control:focus {
    outline: none;
    box-shadow: 0 0 0 3px rgba(255,255,255,0.5),
                inset 0 2px 5px rgba(0,0,0,0.2);
  }
  
  .btn-register {
    background: linear-gradient(180deg, #48d1cc 0%, #20b2aa 100%);
    border: none;
    padding: 12px;
    font-weight: 900;
    font-size: 18px;
    border-radius: 15px;
    color: white;
    text-transform: capitalize;
    letter-spacing: 1px;
    margin-top: 20px;
    box-shadow: 0 6px 20px rgba(0,0,0,0.4);
    text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
    transition: all 0.3s ease;
    border: 3px solid rgba(255,255,255,0.3);
  }
  
  .btn-register:hover {
    transform: translateY(-3px);
    box-shadow: 0 10px 30px rgba(0,0,0,0.5);
    background: linear-gradient(180deg, #5fe0d5 0%, #30c2b9 100%);
    color: white;
  }
  
  .register-link {
    text-align: center;
    margin-top: 25px;
    padding-top: 0;
    border-top: none;
  }
  
  .register-link p {
    color: white;
    font-size: 14px;
    font-weight: 600;
    text-shadow: 1px 1px 3px rgba(0,0,0,0.3);
  }
  
  .register-link a {
    color: #ffe600;
    text-decoration: none;
    font-weight: 900;
    transition: all 0.3s;
    text-shadow: 1px 1px 3px rgba(0,0,0,0.5);
  }
  
  .register-link a:hover {
    color: #ffd700;
    text-decoration: underline;
  }
  
  .alert {
    border: none;
    border-radius: 12px;
    padding: 15px 20px;
    margin-bottom: 25px;
    font-size: 14px;
    font-weight: 600;
    background: rgba(255,107,107,0.9);
    color: white;
    box-shadow: 0 4px 15px rgba(0,0,0,0.3);
  }
  
  .alert-success {
    background: rgba(40,167,69,0.9);
  }
  
  .alert i {
    margin-right: 8px;
  }
  
  .btn-close {
    filter: brightness(0) invert(1);
  }
  
  @media (max-width: 768px) {
    .header-box {
      top: 10px;
      padding: 15px 30px;
    }
    
    .logo-container {
      left: 15px;
      top: 5px;
      gap: 10px;
    }
    
    .logo-circle {
      width: 90px;
      height: 90px;
    }
    
    .logo-circle img {
      width: 75px;
      height: 75px;
    }
    
    .logo-text h3 {
      font-size: 16px;
    }
    
    .logo-text p {
      font-size: 10px;
    }
    
    .card-body {
      padding: 30px 20px;
    }
    
    h4 {
      font-size: 26px;
      margin-bottom: 20px;
    }
    
    .form-row {
      margin-bottom: 15px;
    }
    
    .form-label {
      font-size: 14px;
      margin-bottom: 5px;
    }
    
    .form-control {
      font-size: 14px;
      padding: 10px 14px;
    }
    
    .btn-register {
      font-size: 16px;
      padding: 10px;
      margin-top: 15px;
    }
  }
</style>
</head>
<body>

<div class="logo-container">
  <div class="logo-circle"><img src="../logo unp.png" alt=""></div>
  <div class="logo-text"><h3>Sekolah Vokasi</h3><p>Universitas Negeri Padang</p></div>
</div>

<div class="header-box">
  <h2>Selamat Datang</h2>
  <p>Di Labor vokasi Universitas Negeri Padang</p>
</div>

<div class="container login-container">
  <div class="row justify-content-center">
    <div class="col-md-5 col-lg-4">
      <div class="card">
        <div class="card-body">
          <h4>Daftar Mahasiswa</h4>

          <?php if($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
              <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
              <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
          <?php endif; ?>

          <?php if($success): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
              <i class="fas fa-check-circle"></i> <?php echo $success; ?>
              <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
          <?php endif; ?>

          <form method="post" enctype="multipart/form-data">
            <div class="form-row">
              <label class="form-label"><i class="fas fa-envelope"></i> Email</label>
              <input type="email" class="form-control" name="email" placeholder="Masukkan email Anda" required value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
            </div>
            
            <div class="form-row">
              <label class="form-label"><i class="fas fa-user"></i> Nama Lengkap</label>
              <input type="text" class="form-control" name="nama" placeholder="Masukkan nama lengkap" required value="<?php echo isset($_POST['nama']) ? htmlspecialchars($_POST['nama']) : ''; ?>">
            </div>
            
            <div class="form-row">
              <label class="form-label"><i class="fas fa-id-card"></i> NIM</label>
              <input type="text" class="form-control" name="nim" placeholder="Masukkan NIM Anda" required value="<?php echo isset($_POST['nim']) ? htmlspecialchars($_POST['nim']) : ''; ?>">
            </div>
            
            <div class="form-row">
              <label class="form-label"><i class="fas fa-lock"></i> Password</label>
              <input type="password" class="form-control" name="password" placeholder="Masukkan password" required>
            </div>
            
            <div class="form-row">
              <label class="form-label"><i class="fas fa-image"></i> Foto Profil</label>
              <input type="file" class="form-control" name="foto" accept="image/*">
            </div>
            
            <button class="btn btn-register w-100" name="daftar">
              <i class="fas fa-user-plus"></i> Daftar
            </button>
          </form>
          
          <div class="register-link">
            <p>Sudah punya akun? <a href="login.php">Login di sini</a></p>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

</body>
</html>
