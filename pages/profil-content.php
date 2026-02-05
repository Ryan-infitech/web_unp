<?php
session_start();
if(!isset($_SESSION['user_id'])){
  header("Location: ../auth/login.php");
  exit;
}
include '../config/database.php';
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Profil Saya - Aplikasi Labor</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
<style>
  * {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
  }
  
  body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background-color: #f5f7fb;
    overflow-x: hidden;
  }
  
  .main-content {
    margin-left: 250px;
    padding: 30px 20px;
    min-height: 100vh;
    transition: margin-left 0.3s ease;
  }

  @media (max-width: 992px) {
    .main-content {
      margin-left: 0;
      padding: 60px 20px 20px 20px;
    }
  }

  @media (max-width: 768px) {
    .main-content {
      padding: 60px 15px 20px 15px;
    }
  }

  @media (max-width: 480px) {
    .main-content {
      padding: 55px 12px 20px 12px;
    }
  }
</style>
</head>
<body>

<!-- Include Sidebar -->
<?php include '../assets/sidebar.php'; ?>

<!-- Main Content -->
<div class="main-content">

<?php
if(!isset($_SESSION['user_id'])){
  header("HTTP/1.1 401 Unauthorized");
  exit;
}

$id = intval($_SESSION['user_id']);
$u = mysqli_fetch_assoc(mysqli_query($conn,"SELECT * FROM users WHERE id=$id"));
?>

<style>
  .profile-card {
    background: white;
    border-radius: 12px;
    padding: 40px;
    box-shadow: 0 2px 15px rgba(0,0,0,0.08);
    max-width: 700px;
    margin: 0 auto;
    animation: slideUp 0.5s ease-out;
  }

  .profile-section {
    text-align: center;
    margin-bottom: 40px;
  }

  .profile-avatar-section {
    position: relative;
    display: inline-block;
    margin-bottom: 25px;
  }

  .profile-avatar {
    width: 120px;
    height: 120px;
    border-radius: 50%;
    border: 4px solid #667eea;
    object-fit: cover;
    box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
    transition: transform 0.3s ease;
  }

  .profile-avatar:hover {
    transform: scale(1.05);
  }

  .avatar-upload-overlay {
    position: absolute;
    bottom: 0;
    right: 0;
    width: 40px;
    height: 40px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    cursor: pointer;
    box-shadow: 0 2px 10px rgba(102, 126, 234, 0.3);
    transition: all 0.3s ease;
  }

  .avatar-upload-overlay:hover {
    transform: scale(1.1);
    box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
  }

  .avatar-input {
    display: none;
  }

  .profile-card h3 {
    color: #333;
    font-weight: 700;
    margin-bottom: 25px;
    font-size: 28px;
  }

  /* Info Sections */
  .profile-info-section {
    background: #f8f9ff;
    border-radius: 10px;
    padding: 25px;
    margin-bottom: 20px;
    text-align: left;
  }

  .info-group {
    margin-bottom: 15px;
  }

  .info-group:last-child {
    margin-bottom: 0;
  }

  .info-label {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 12px;
    font-weight: 600;
    color: #667eea;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 5px;
  }

  .info-label i {
    font-size: 14px;
  }

  .info-value {
    font-size: 16px;
    font-weight: 600;
    color: #333;
    padding: 0 10px;
  }

  /* Password Edit Section */
  .password-section {
    background: linear-gradient(135deg, #667eea15 0%, #764ba215 100%);
    border: 2px solid #667eea20;
    border-radius: 10px;
    padding: 25px;
    margin-top: 30px;
  }

  .password-section h4 {
    display: flex;
    align-items: center;
    gap: 10px;
    color: #333;
    font-weight: 600;
    margin: 0 0 20px 0;
    padding-bottom: 15px;
    border-bottom: 2px solid #667eea;
  }

  .password-section i {
    color: #667eea;
    font-size: 20px;
  }

  .form-group {
    margin-bottom: 15px;
  }

  .form-group:last-child {
    margin-bottom: 0;
  }

  .form-group label {
    display: flex;
    align-items: center;
    gap: 8px;
    font-weight: 600;
    color: #333;
    margin-bottom: 8px;
    font-size: 14px;
  }

  .form-group label i {
    color: #667eea;
    font-size: 16px;
  }

  .form-group input {
    width: 100%;
    padding: 12px 15px;
    border: 2px solid #e0e0e0;
    border-radius: 8px;
    font-size: 14px;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    transition: all 0.3s ease;
  }

  .form-group input:focus {
    outline: none;
    border-color: #667eea;
    background: #fafbff;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
  }

  .helper-text {
    font-size: 12px;
    color: #999;
    margin-top: 5px;
    display: block;
  }

  .profile-actions {
    display: flex;
    gap: 12px;
    justify-content: center;
    margin-top: 30px;
  }

  .profile-actions button {
    padding: 14px 24px;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    font-size: 14px;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
  }

  .btn-save {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    flex: 1;
  }

  .btn-save:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
  }

  .btn-save:disabled {
    opacity: 0.5;
    cursor: not-allowed;
  }

  .btn-back {
    background: #f0f0f0;
    color: #333;
    flex: 1;
  }

  .btn-back:hover {
    background: #e0e0e0;
    transform: translateY(-2px);
  }

  @keyframes slideUp {
    from {
      opacity: 0;
      transform: translateY(20px);
    }
    to {
      opacity: 1;
      transform: translateY(0);
    }
  }
</style>

<!-- SweetAlert2 CDN -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<div class="profile-card">
  <!-- Profile Section -->
  <div class="profile-section">
    <div class="profile-avatar-section">
      <img src="../assets/img/<?php echo htmlspecialchars($u['foto']); ?>" alt="Avatar" class="profile-avatar" id="profileAvatar">
      <div class="avatar-upload-overlay" id="uploadOverlay" title="Klik untuk ganti foto">
        <i class="fas fa-camera"></i>
      </div>
      <input type="file" id="avatarInput" class="avatar-input" accept="image/*">
    </div>
    <h3><?php echo htmlspecialchars($u['nama']); ?></h3>
  </div>

  <!-- Info Section (Read-only) -->
  <div class="profile-info-section">
    <div class="info-group">
      <div class="info-label">
        <i class="fas fa-id-card"></i> NIM
      </div>
      <div class="info-value"><?php echo htmlspecialchars($u['nim']); ?></div>
    </div>

    <div class="info-group">
      <div class="info-label">
        <i class="fas fa-user"></i> Nama Lengkap
      </div>
      <div class="info-value"><?php echo htmlspecialchars($u['nama']); ?></div>
    </div>

    <div class="info-group">
      <div class="info-label">
        <i class="fas fa-envelope"></i> Email
      </div>
      <div class="info-value"><?php echo htmlspecialchars($u['email']); ?></div>
    </div>
  </div>

  <!-- Password Edit Section -->
  <form id="formEditPassword">
    <div class="password-section">
      <h4>
        <i class="fas fa-lock"></i> Ganti Password
      </h4>

      <div class="form-group">
        <label for="password_lama">
          <i class="fas fa-key"></i> Password Saat Ini
        </label>
        <input type="password" id="password_lama" name="password_lama" placeholder="Masukkan password saat ini">
        <span class="helper-text">Kosongkan jika tidak ingin mengubah password</span>
      </div>

      <div class="form-group">
        <label for="password_baru">
          <i class="fas fa-lock-open"></i> Password Baru
        </label>
        <input type="password" id="password_baru" name="password_baru" placeholder="Masukkan password baru">
      </div>

      <div class="form-group">
        <label for="password_confirm">
          <i class="fas fa-check-circle"></i> Konfirmasi Password
        </label>
        <input type="password" id="password_confirm" name="password_confirm" placeholder="Konfirmasi password baru">
      </div>
    </div>

    <!-- Action Buttons -->
    <div class="profile-actions">
      <button type="button" class="btn-back" onclick="navigateToPage('dashboard')">
        <i class="fas fa-arrow-left"></i> Kembali
      </button>
      <button type="submit" class="btn-save" id="btnSave">
        <i class="fas fa-save"></i> Simpan Perubahan
      </button>
    </div>
  </form>
</div>

<script>
  // Global variable untuk menyimpan file foto yang dipilih
  let selectedFotoFile = null;
  let originalAvatarSrc = '';

  // Handle Avatar Upload
  const uploadOverlay = document.getElementById('uploadOverlay');
  const avatarInput = document.getElementById('avatarInput');
  const profileAvatar = document.getElementById('profileAvatar');

  // Simpan original image
  originalAvatarSrc = profileAvatar.src;

  uploadOverlay.addEventListener('click', () => {
    avatarInput.click();
  });

  avatarInput.addEventListener('change', function(e) {
    const file = this.files[0];
    if(!file) return;

    // Validate file
    const allowed = ['image/jpeg', 'image/png', 'image/gif'];
    if(!allowed.includes(file.type)) {
      Swal.fire({
        icon: 'error',
        title: 'Format Tidak Didukung',
        text: 'Gunakan format JPG, PNG, atau GIF'
      });
      return;
    }

    if(file.size > 5000000) {
      Swal.fire({
        icon: 'error',
        title: 'File Terlalu Besar',
        text: 'Maksimal ukuran file adalah 5MB'
      });
      return;
    }

    // Show preview
    const reader = new FileReader();
    reader.onload = function(event) {
      profileAvatar.src = event.target.result;
      selectedFotoFile = file; // Simpan file untuk upload nanti
      
      Swal.fire({
        icon: 'info',
        title: 'Foto Dipilih',
        text: 'Klik "Simpan Perubahan" untuk mengupload foto ini',
        confirmButtonColor: '#667eea'
      });
    };
    reader.readAsDataURL(file);
  });

  // Handle Password Form Submit
  document.getElementById('formEditPassword').addEventListener('submit', async function(e) {
    e.preventDefault();

    const passwordLama = document.getElementById('password_lama').value;
    const passwordBaru = document.getElementById('password_baru').value;
    const passwordConfirm = document.getElementById('password_confirm').value;

  // Handle Password Form Submit
  document.getElementById('formEditPassword').addEventListener('submit', async function(e) {
    e.preventDefault();

    const passwordLama = document.getElementById('password_lama').value;
    const passwordBaru = document.getElementById('password_baru').value;
    const passwordConfirm = document.getElementById('password_confirm').value;
    
    // Check if ada perubahan (foto atau password)
    const hasPhotoChange = selectedFotoFile !== null;
    const hasPasswordChange = passwordLama || passwordBaru;

    // Validation password jika ada perubahan password
    if(passwordLama && !passwordBaru) {
      Swal.fire({
        icon: 'warning',
        title: 'Data Tidak Lengkap',
        text: 'Password baru harus diisi!'
      });
      return;
    }

    if(passwordBaru && passwordBaru.length < 6) {
      Swal.fire({
        icon: 'warning',
        title: 'Password Terlalu Pendek',
        text: 'Password baru minimal 6 karakter!'
      });
      return;
    }

    if(passwordBaru && passwordBaru !== passwordConfirm) {
      Swal.fire({
        icon: 'warning',
        title: 'Password Tidak Cocok',
        text: 'Password baru dan konfirmasi tidak cocok!'
      });
      return;
    }

    if(!hasPhotoChange && !hasPasswordChange) {
      Swal.fire({
        icon: 'info',
        title: 'Tidak Ada Perubahan',
        text: 'Tidak ada data yang diubah'
      });
      return;
    }

    // Confirmation dialog
    const confirmResult = await Swal.fire({
      icon: 'question',
      title: 'Simpan Perubahan?',
      text: 'Apakah Anda yakin ingin menyimpan perubahan?',
      showCancelButton: true,
      confirmButtonText: 'Ya, Simpan',
      cancelButtonText: 'Batal',
      confirmButtonColor: '#667eea'
    });

    if(!confirmResult.isConfirmed) {
      return;
    }

    const btnSave = document.getElementById('btnSave');
    btnSave.disabled = true;
    btnSave.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Menyimpan...';

    try {
      // Upload foto terlebih dahulu jika ada
      if(hasPhotoChange) {
        const fotoFormData = new FormData();
        fotoFormData.append('foto', selectedFotoFile);
        fotoFormData.append('action', 'upload_foto');

        const fotoResponse = await fetch('process-profil.php', {
          method: 'POST',
          body: fotoFormData
        });

        const fotoResult = await fotoResponse.json();

        if(!fotoResult.success) {
          throw new Error(fotoResult.message || 'Gagal mengupload foto');
        }

        // Update foto di UI
        profileAvatar.src = '../assets/img/' + fotoResult.foto + '?' + new Date().getTime();
        selectedFotoFile = null;
      }

      // Update password jika ada
      if(hasPasswordChange) {
        const passwordFormData = new FormData();
        passwordFormData.append('action', 'update_password');
        passwordFormData.append('password_lama', passwordLama);
        passwordFormData.append('password_baru', passwordBaru);
        passwordFormData.append('password_confirm', passwordConfirm);

        const passwordResponse = await fetch('process-profil.php', {
          method: 'POST',
          body: passwordFormData
        });

        const passwordResult = await passwordResponse.json();

        if(!passwordResult.success) {
          throw new Error(passwordResult.message || 'Gagal mengupdate password');
        }
      }

      // Semua berhasil
      await Swal.fire({
        icon: 'success',
        title: 'Berhasil!',
        text: 'Perubahan berhasil disimpan. Anda akan kembali ke dashboard...',
        confirmButtonColor: '#667eea',
        allowOutsideClick: false,
        allowEscapeKey: false
      });
      
      // Reset form
      document.getElementById('password_lama').value = '';
      document.getElementById('password_baru').value = '';
      document.getElementById('password_confirm').value = '';
      
      // Redirect
      setTimeout(() => {
        navigateToPage('dashboard');
      }, 1500);

    } catch(error) {
      Swal.fire({
        icon: 'error',
        title: 'Gagal!',
        text: error.message || 'Terjadi kesalahan saat menyimpan perubahan'
      });
      btnSave.disabled = false;
      btnSave.innerHTML = '<i class="fas fa-save"></i> Simpan Perubahan';
    }
  });
  });
</script>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
  function navigateToPage(page) {
    const pageMap = {
      'dashboard': 'dashboard-content.php',
      'labor': 'labor-content.php',
      'peminjaman': 'peminjaman-content.php',
      'riwayat': 'riwayat-content.php',
      'profil': 'profil-content.php',
      'notifikasi': 'notifikasi-content.php'
    };
    
    const filename = pageMap[page] || 'dashboard-content.php';
    window.location.href = filename;
  }
</script>

</body>
</html>