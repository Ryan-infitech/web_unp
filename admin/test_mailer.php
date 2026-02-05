<?php
session_start();
include 'config/database.php';

// Check if admin logged in
if(!isset($_SESSION['admin_id'])){
  die("Silakan login sebagai admin terlebih dahulu");
}

$message = '';
$message_type = '';

// Handle test email send
if(isset($_POST['send_test'])){
  $test_email = mysqli_real_escape_string($conn, $_POST['test_email']);
  
  if(empty($test_email) || !filter_var($test_email, FILTER_VALIDATE_EMAIL)){
    $message = "Email tidak valid!";
    $message_type = "danger";
  } else {
    require_once __DIR__ . '/config/mailer.php';
    
    $email_subject = "Test Email - Sistem Peminjaman Laboratorium";
    $email_body = "
    <html>
    <head>
      <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; background: #f9f9f9; border-radius: 8px; }
        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; border-radius: 8px 8px 0 0; text-align: center; }
        .content { background: white; padding: 20px; border-radius: 0 0 8px 8px; }
        .success { color: #22c55e; font-weight: bold; }
        .footer { margin-top: 20px; padding-top: 20px; border-top: 1px solid #ddd; color: #666; font-size: 12px; }
      </style>
    </head>
    <body>
      <div class='container'>
        <div class='header'>
          <h2>Email Test Berhasil!</h2>
        </div>
        <div class='content'>
          <p>Selamat! Email test telah terkirim dengan sukses.</p>
          
          <p><strong>Info Test:</strong></p>
          <ul>
            <li>Tanggal: " . date('d-m-Y H:i:s') . "</li>
            <li>Tujuan: $test_email</li>
            <li>Status: <span class='success'>SUCCESS</span></li>
          </ul>
          
          <p>Jika Anda menerima email ini, berarti mailer system sudah berfungsi dengan baik.</p>
          
          <div class='footer'>
            <p>Email ini adalah email test. Anda dapat mengabaikannya.</p>
            <p>&copy; Sistem Peminjaman Laboratorium</p>
          </div>
        </div>
      </div>
    </body>
    </html>
    ";
    
    $result = send_html_mail($test_email, $email_subject, $email_body);
    
    if($result){
      $message = "Email test berhasil dikirim ke $test_email. Silakan cek inbox Anda.";
      $message_type = "success";
    } else {
      $message = "Gagal mengirim email. Silakan cek konfigurasi mailer.";
      $message_type = "danger";
    }
  }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Test Mailer - Admin Panel</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    :root {
      --primary: #f59e0b;
      --primary-dark: #d97706;
      --bg-primary: #ffffff;
      --text-primary: #0f172a;
      --text-secondary: #64748b;
      --border-color: #e2e8f0;
      --shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    }

    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      background: linear-gradient(135deg, #f59e0b15 0%, #fb923c15 100%);
      color: var(--text-primary);
    }

    .main-content {
      padding: 40px 30px;
      min-height: 100vh;
    }

    .page-header {
      margin-bottom: 30px;
    }

    .page-header h1 {
      font-size: 28px;
      font-weight: 700;
      color: var(--text-primary);
      display: flex;
      align-items: center;
      gap: 12px;
    }

    .page-header h1 i {
      color: var(--primary);
      font-size: 32px;
    }

    .alert-custom {
      padding: 15px 20px;
      border-radius: 10px;
      margin-bottom: 20px;
      display: flex;
      align-items: center;
      gap: 12px;
      animation: slideInDown 0.3s ease;
    }

    .alert-success {
      background: rgba(34, 197, 94, 0.1);
      border: 2px solid #22c55e;
      color: #22c55e;
    }

    .alert-danger {
      background: rgba(239, 68, 68, 0.1);
      border: 2px solid #ef4444;
      color: #ef4444;
    }

    .test-card {
      background: var(--bg-primary);
      border-radius: 12px;
      padding: 25px;
      box-shadow: var(--shadow);
      max-width: 600px;
      margin: 0 auto;
    }

    .test-card h3 {
      font-size: 18px;
      font-weight: 700;
      color: var(--text-primary);
      margin-bottom: 20px;
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .test-card h3 i {
      color: var(--primary);
    }

    .form-group {
      margin-bottom: 18px;
    }

    .form-group label {
      display: block;
      margin-bottom: 8px;
      font-weight: 600;
      color: var(--text-primary);
      font-size: 14px;
    }

    .form-group input {
      width: 100%;
      padding: 12px 15px;
      border: 2px solid var(--border-color);
      border-radius: 8px;
      font-size: 14px;
      transition: all 0.3s;
    }

    .form-group input:focus {
      outline: none;
      border-color: var(--primary);
      box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.1);
    }

    .btn-test {
      width: 100%;
      background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
      color: white;
      border: none;
      padding: 12px 20px;
      border-radius: 8px;
      font-weight: 600;
      font-size: 14px;
      cursor: pointer;
      transition: all 0.3s;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
    }

    .btn-test:hover {
      transform: translateY(-2px);
      box-shadow: 0 6px 20px rgba(245, 158, 11, 0.3);
    }

    .btn-test:active {
      transform: translateY(0);
    }

    .info-box {
      background: rgba(245, 158, 11, 0.05);
      border: 2px solid rgba(245, 158, 11, 0.2);
      border-radius: 8px;
      padding: 15px;
      margin-bottom: 20px;
    }

    .info-box h4 {
      color: var(--primary);
      font-weight: 700;
      margin-bottom: 10px;
      font-size: 14px;
    }

    .info-box ul {
      margin: 0;
      padding-left: 20px;
    }

    .info-box li {
      font-size: 13px;
      color: var(--text-secondary);
      margin-bottom: 5px;
    }

    .success-message {
      background: rgba(34, 197, 94, 0.05);
      border-left: 4px solid #22c55e;
      padding: 15px;
      border-radius: 8px;
      margin-top: 20px;
      color: #166534;
      font-size: 13px;
    }

    @keyframes slideInDown {
      from {
        opacity: 0;
        transform: translateY(-20px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }
  </style>
</head>
<body>

<div class="main-content">
  <!-- Page Header -->
  <div class="page-header">
    <h1>
      <i class="fas fa-envelope"></i>
      Test Email Mailer
    </h1>
  </div>

  <!-- Alert Messages -->
  <?php if(!empty($message)): ?>
    <div class="alert-custom alert-<?php echo $message_type; ?>">
      <i class="fas fa-<?php echo $message_type === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
      <span><?php echo $message; ?></span>
    </div>
  <?php endif; ?>

  <!-- Test Card -->
  <div class="test-card">
    <h3>
      <i class="fas fa-paper-plane"></i>
      Kirim Email Test
    </h3>

    <div class="info-box">
      <h4><i class="fas fa-info-circle"></i> Informasi</h4>
      <ul>
        <li>Gunakan form ini untuk test konfigurasi mailer</li>
        <li>Email test akan dikirim ke alamat yang Anda masukkan</li>
        <li>Pastikan email address valid dan terdaftar</li>
        <li>Cek folder spam jika tidak terlihat di inbox</li>
      </ul>
    </div>

    <form method="POST" id="testForm">
      <div class="form-group">
        <label for="testEmail">
          <i class="fas fa-envelope"></i> Alamat Email Tujuan
        </label>
        <input 
          type="email" 
          id="testEmail" 
          name="test_email" 
          placeholder="masukkan@email.com" 
          required
        >
      </div>

      <button type="submit" name="send_test" class="btn-test">
        <i class="fas fa-paper-plane"></i>
        Kirim Email Test
      </button>
    </form>

    <?php if($message_type === 'success'): ?>
      <div class="success-message">
        <i class="fas fa-check-circle"></i>
        Email berhasil dikirim! Silakan check inbox Anda dalam beberapa detik.
      </div>
    <?php endif; ?>
  </div>
</div>

</body>
</html>
