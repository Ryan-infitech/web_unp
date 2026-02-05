<?php
session_start();
include '../config/database.php';

// Cek apakah user sudah login sebagai admin
if(isset($_SESSION['admin_id'])){
  header("Location: dashboard.php");
  exit;
}

$error = '';
$success = '';

if(isset($_POST['register'])){
  $username = mysqli_real_escape_string($conn, $_POST['username']);
  $password = $_POST['password'];
  $confirm_pass = $_POST['confirm_password'];

  // Validasi input
  if(empty($username) || empty($password) || empty($confirm_pass)){
    $error = "Semua field harus diisi!";
  } else if(strlen($password) < 6){
    $error = "Password minimal 6 karakter!";
  } else if($password !== $confirm_pass){
    $error = "Password tidak cocok!";
  } else {
    // Cek apakah username sudah terdaftar
    $check = mysqli_query($conn, "SELECT id FROM admin WHERE username='$username'");
    if(!$check){
      $error = "Terjadi kesalahan database: " . mysqli_error($conn);
    } else if(mysqli_num_rows($check) > 0){
      $error = "Username sudah terdaftar!";
    } else {
      // Hash password
      $hashed_pass = password_hash($password, PASSWORD_DEFAULT);
      
      // Simpan ke database
      $insert = mysqli_query($conn, "INSERT INTO admin (username, password) VALUES ('$username', '$hashed_pass')");
      
      if($insert){
        $success = "Registrasi berhasil! <a href='login.php'>Login di sini</a>";
        // Clear form
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
<title>Registrasi Admin</title>
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
  .header-box h2 { color: white; font-size: 24px; font-weight: 900; margin: 0; text-shadow: 2px 2px 4px rgba(0,0,0,0.3); }
  .header-box p { color: white; font-size: 14px; margin: 5px 0 0 0; font-weight: 600 }

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
  }

  .login-container { position: relative; z-index:10; margin-top:0; padding:50px 0 }
  .card { border:none; border-radius:30px; background: linear-gradient(180deg, #40e0d0 0%, #20b2aa 100%); box-shadow:0 20px 60px rgba(0,0,0,0.5), 0 0 0 3px rgba(0,0,0,0.3); overflow:hidden; border:4px solid rgba(0,0,0,0.4) }
  .card-body { padding:50px 45px }
  h4 { color:white; font-weight:900; margin-bottom:30px; font-size:36px; text-align:center; text-shadow:3px 3px 6px rgba(0,0,0,0.3) }
  .form-label { color:white; font-weight:900; font-size:15px; text-shadow:2px 2px 4px rgba(0,0,0,0.3); display:block; margin-bottom:6px }
  .form-control { border:none; border-radius:10px; padding:12px 18px; font-size:16px; background:white; flex:1; box-shadow: inset 0 2px 5px rgba(0,0,0,0.2); font-weight:600 }
  .form-control:focus{ outline:none; box-shadow:0 0 0 3px rgba(255,255,255,0.5), inset 0 2px 5px rgba(0,0,0,0.2) }
  .btn-register { background: linear-gradient(180deg, #48d1cc 0%, #20b2aa 100%); border:none; padding:12px; font-weight:900; font-size:18px; border-radius:15px; color:white; letter-spacing:1px; margin-top:20px; box-shadow:0 6px 20px rgba(0,0,0,0.4); border:3px solid rgba(255,255,255,0.3); transition:all 0.3s ease; text-shadow:2px 2px 4px rgba(0,0,0,0.3) }
  .btn-register:hover{ transform:translateY(-3px); box-shadow:0 10px 30px rgba(0,0,0,0.5) }
  .register-link { text-align:center; margin-top:25px; padding-top:15px; border-top:1px solid rgba(255,255,255,0.2) }
  .register-link p { color:white; font-size:14px; font-weight:600; text-shadow:1px 1px 3px rgba(0,0,0,0.3) }
  .register-link a { color:#ffe600; text-decoration:none; font-weight:900; transition:all 0.3s; text-shadow:1px 1px 3px rgba(0,0,0,0.5) }
  .register-link a:hover { color:#ffd700; text-decoration:underline }

  @media (max-width:768px){ .header-box{ top:10px; padding:15px 30px } .logo-container{ left:15px; top:5px } .logo-circle{ width:90px; height:90px } .logo-circle img{ width:75px; height:75px } .card-body{ padding:30px 20px } h4{ font-size:26px; margin-bottom:20px } .form-label{ font-size:14px; margin-bottom:5px } .form-control{ font-size:14px; padding:10px 14px } .btn-register{ font-size:16px; padding:10px; margin-top:15px } }

</style>
</head>
<body>

<div class="logo-container">
  <div class="logo-circle"><img src="../logo unp.png" alt=""></div>
  <div class="logo-text"><h3>Sekolah Vokasi</h3><p>Universitas Negeri Padang</p></div>
</div>

<div class="header-box">
  <h2>Belum Punya Akun</h2>
  <p>Di Labor vokasi Universitas Negeri Padang</p>
</div>

<div class="container login-container">
  <div class="row justify-content-center">
    <div class="col-md-5 col-lg-4">
      <div class="card">
        <div class="card-body">
          <h4>Registrasi Admin</h4>

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

          <form method="post">
            <div class="form-row">
              <label class="form-label"><i class="fas fa-user"></i> Username</label>
              <input type="text" class="form-control" name="username" placeholder="Masukkan username" required value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
            </div>

            <div class="form-row">
              <label class="form-label"><i class="fas fa-lock"></i> Password</label>
              <input type="password" class="form-control" name="password" placeholder="Minimal 6 karakter" required>
            </div>

            <div class="form-row">
              <label class="form-label"><i class="fas fa-lock"></i> Konfirmasi Password</label>
              <input type="password" class="form-control" name="confirm_password" placeholder="Ulangi password" required>
            </div>

            <button type="submit" class="btn btn-register w-100" name="register">
              <i class="fas fa-user-check"></i> Registrasi
            </button>
          </form>

          <div class="register-link">
            <p class="mb-0">Sudah punya akun? <a href="login.php">Login di sini</a></p>
          </div>

        </div>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
