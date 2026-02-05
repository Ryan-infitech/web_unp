<?php
session_start();
include '../config/database.php';

// Cek apakah admin sudah login
if(!isset($_SESSION['admin_id'])){
  header("Location: login.php");
  exit;
}

$id = $_SESSION['admin_id'];
$q = mysqli_query($conn, "SELECT * FROM admin WHERE id=$id");
$a = mysqli_fetch_assoc($q);

if(!$a){
  die("Admin tidak ditemukan");
}

$message = '';
$message_type = '';

if(isset($_POST['simpan'])){
  $judul = mysqli_real_escape_string($conn, $_POST['judul']);
  $deskripsi = mysqli_real_escape_string($conn, $_POST['deskripsi']);
  
  if(empty($judul) || empty($deskripsi)){
    $message = "Semua field harus diisi!";
    $message_type = "warning";
  } else {
    $update = mysqli_query($conn, "UPDATE info_dashboard SET judul='$judul', deskripsi='$deskripsi', terakhir_update=NOW() WHERE id=1");
    
    if($update){
      $message = "Informasi dashboard berhasil diperbarui!";
      $message_type = "success";
    } else {
      $message = "Gagal memperbarui informasi: " . mysqli_error($conn);
      $message_type = "danger";
    }
  }
}

$info_query = mysqli_query($conn, "SELECT * FROM info_dashboard WHERE id=1");
$info = mysqli_fetch_assoc($info_query);

if(!$info){
  $info = ['judul' => '', 'deskripsi' => '', 'terakhir_update' => ''];
}
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Kelola Info Dashboard</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
  * {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
  }

  @keyframes slideInUp {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
  }

  @keyframes slideInDown {
    from { opacity: 0; transform: translateY(-20px); }
    to { opacity: 1; transform: translateY(0); }
  }

  @keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
  }
  
  body {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
    background-color: #f8fafc;
    color: #1e293b;
  }
  
  /* Main Content */
  .main-content {
    margin-left: 250px;
    padding: 40px 30px;
    min-height: 100vh;
  }
  
  .page-header {
    background: #ffffff;
    color: #0f172a;
    border-radius: 10px;
    padding: 24px;
    margin-bottom: 30px;
    border: 1px solid #e2e8f0;
    transition: all 0.3s cubic-bezier(0.25, 0.46, 0.45, 0.94);
    animation: slideInDown 0.5s ease-out;
  }
  
  .page-header h2 {
    font-weight: 700;
    margin-bottom: 8px;
    font-size: 28px;
    color: #0f172a;
  }
  
  .page-header p {
    margin-bottom: 0;
    font-size: 14px;
    color: #64748b;
  }
  
  /* Alert Messages */
  .alert {
    border-radius: 8px;
    border: none;
    margin-bottom: 30px;
    font-size: 14px;
    font-weight: 500;
    padding: 12px 16px;
  }
  
  .alert-success {
    background-color: #dcfce7;
    color: #166534;
  }
  
  .alert-warning {
    background-color: #fef3c7;
    color: #92400e;
  }
  
  .alert-danger {
    background-color: #fee2e2;
    color: #991b1b;
  }
  
  /* Form Container */
  .form-container {
    background: #ffffff;
    border-radius: 10px;
    padding: 30px;
    margin-bottom: 30px;
    border: 1px solid #e2e8f0;
    animation: slideInUp 0.6s ease-out 0.2s both;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
  }
  
  .form-group {
    margin-bottom: 25px;
  }
  
  .form-label {
    color: #0f172a;
    font-weight: 600;
    margin-bottom: 12px;
    display: block;
    font-size: 14px;
  }
  
  .form-label i {
    margin-right: 8px;
    color: #2563eb;
  }
  
  .form-control {
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    padding: 10px 12px;
    background: #ffffff;
    font-weight: 500;
    transition: border-color 0.2s, box-shadow 0.2s;
    font-size: 14px;
    color: #1e293b;
  }
  
  .form-control:focus {
    border-color: #2563eb;
    box-shadow: 0 0 0 2px rgba(37, 99, 235, 0.1);
    background: white;
  }
  
  .form-control::placeholder {
    color: #94a3b8;
  }
  
  textarea.form-control {
    resize: vertical;
    min-height: 150px;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
  }
  
  /* Buttons */
  .btn-save {
    background-color: #2563eb;
    border: none;
    color: white;
    font-weight: 600;
    padding: 10px 24px;
    border-radius: 8px;
    transition: all 0.2s;
  }
  
  .btn-save:hover {
    background-color: #1d4ed8;
    transform: translateY(-1px);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    color: white;
  }
  
  .btn-outline-secondary {
    background: transparent;
    border: 1px solid #e2e8f0;
    color: #475569;
    font-weight: 600;
    padding: 10px 24px;
    border-radius: 8px;
    transition: all 0.2s;
  }
  
  .btn-outline-secondary:hover {
    background: #f1f5f9;
    border-color: #cbd5e1;
    color: #1e293b;
  }
  
  /* Info Box */
  .info-box {
    background: #fef3c7;
    border-left: 4px solid #f59e0b;
    border-radius: 8px;
    padding: 12px 16px;
    margin-top: 20px;
    color: #92400e;
    font-size: 13px;
    font-weight: 500;
  }
  
  .info-box i {
    color: #f59e0b;
    margin-right: 8px;
  }
  
  /* Character Count */
  .char-count {
    color: #64748b;
    font-size: 12px;
    margin-top: 4px;
    text-align: right;
    font-weight: 500;
  }
  
  /* Last Update */
  .last-update {
    color: #64748b;
    font-size: 13px;
    margin-top: 20px;
    padding-top: 15px;
    border-top: 1px solid #e2e8f0;
    font-weight: 500;
  }
  
  .last-update i {
    color: #2563eb;
  }
  
  /* Preview Box */
  .preview-box {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    padding: 20px;
    margin-top: 30px;
    display: none;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
  }
  
  .preview-box.show {
    display: block;
    border-color: #cbd5e1;
  }
  
  .preview-title {
    color: #0f172a;
    font-weight: 700;
    font-size: 18px;
    margin-bottom: 10px;
  }
  
  .preview-desc {
    color: #475569;
    line-height: 1.6;
    font-weight: 500;
  }
  
  /* Mobile responsive */
  @media (max-width: 768px) {
    .main-content {
      padding: 20px;
    }
    
    .page-header h2 {
      font-size: 24px;
    }
  }
</style>
</head>
<body>

<?php include '../assets/admin_sidebar.php'; ?>

<!-- Main Content -->
<div class="main-content">
  <!-- Message Alert -->
  <?php if($message): ?>
    <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
      <i class="fas fa-info-circle"></i> <?php echo $message; ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php endif; ?>

  <!-- Page Header -->
  <div class="page-header">
    <h2><i class="fas fa-cog"></i> Kelola Informasi Dashboard User</h2>
    <p>Perbarui judul dan deskripsi yang ditampilkan di halaman utama aplikasi</p>
  </div>

  <!-- Form Container -->
  <div class="form-container">
    <form method="post">
      <!-- Judul -->
      <div class="form-group">
        <label for="judul" class="form-label">
          <i class="fas fa-heading"></i> Judul Informasi
        </label>
        <input 
          type="text" 
          class="form-control" 
          id="judul"
          name="judul" 
          placeholder="Masukkan judul informasi..."
          value="<?php echo htmlspecialchars($info['judul'] ?? ''); ?>"
          required
          maxlength="100"
        >
        <div class="char-count"><span id="judul-count">0</span>/100 karakter</div>
      </div>

      <!-- Deskripsi -->
      <div class="form-group">
        <label for="deskripsi" class="form-label">
          <i class="fas fa-file-alt"></i> Deskripsi / Pengumuman
        </label>
        <textarea 
          class="form-control" 
          id="deskripsi"
          name="deskripsi" 
          placeholder="Masukkan deskripsi atau pengumuman penting..."
          required
          maxlength="1000"
        ><?php echo htmlspecialchars($info['deskripsi'] ?? ''); ?></textarea>
        <div class="char-count"><span id="deskripsi-count">0</span>/1000 karakter</div>
      </div>

      <!-- Preview -->
      <div class="preview-box" id="previewBox">
        <div class="preview-title" id="previewJudul"></div>
        <div class="preview-desc" id="previewDeskripsi"></div>
      </div>

      <!-- Info Box -->
      <div class="info-box">
        <i class="fas fa-lightbulb"></i>
        <strong>Tip:</strong> Informasi ini akan ditampilkan di halaman dashboard user. Pastikan konten informatif dan jelas.
      </div>

      <!-- Last Update -->
      <?php if(!empty($info['terakhir_update'])): ?>
        <div class="last-update">
          <i class="fas fa-history"></i> 
          <strong>Terakhir diperbarui:</strong> <?php echo date('d-m-Y H:i', strtotime($info['terakhir_update'])); ?>
        </div>
      <?php endif; ?>

      <!-- Buttons -->
      <div style="margin-top: 30px;">
        <button type="submit" class="btn btn-save" name="simpan">
          <i class="fas fa-save"></i> Simpan Perubahan
        </button>
        <a href="dashboard.php" class="btn btn-outline-secondary ms-2">
          <i class="fas fa-times"></i> Batal
        </a>
      </div>
    </form>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Load sidebar state from localStorage
if(localStorage.getItem('sidebarCollapsed')==='true') document.body.classList.add('sidebar-collapsed');

// Click-outside detection for mobile
document.addEventListener('click', function(e) {
  if(window.innerWidth<=768 && !document.querySelector('.sidebar').contains(e.target) && !document.querySelector('.sidebar-toggle').contains(e.target) && !document.body.classList.contains('sidebar-collapsed')) {
    document.body.classList.add('sidebar-collapsed');
  }
});

// Character counter untuk judul
document.getElementById('judul').addEventListener('input', function() {
  document.getElementById('judul-count').textContent = this.value.length;
  updatePreview();
});

// Character counter untuk deskripsi
document.getElementById('deskripsi').addEventListener('input', function() {
  document.getElementById('deskripsi-count').textContent = this.value.length;
  updatePreview();
});

// Update preview
function updatePreview() {
  const judul = document.getElementById('judul').value;
  const deskripsi = document.getElementById('deskripsi').value;
  
  if(judul || deskripsi) {
    document.getElementById('previewBox').classList.add('show');
    document.getElementById('previewJudul').textContent = judul || 'Judul Informasi';
    document.getElementById('previewDeskripsi').textContent = deskripsi || 'Deskripsi akan muncul di sini...';
  } else {
    document.getElementById('previewBox').classList.remove('show');
  }
}
</script>
</body>
</html>