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

// Handle ADD/EDIT Labor
if(isset($_POST['save_labor'])){
  $labor_id = isset($_POST['labor_id']) ? intval($_POST['labor_id']) : 0;
  $nama = mysqli_real_escape_string($conn, $_POST['nama']);
  $deskripsi = mysqli_real_escape_string($conn, $_POST['deskripsi']);
  $color = mysqli_real_escape_string($conn, $_POST['color']);
  $image = '';
  
  // Handle file upload
  if(isset($_FILES['image']) && $_FILES['image']['size'] > 0) {
    $upload_dir = '../assets/uploads/labor/';
    
    // Buat folder jika belum ada
    if(!is_dir($upload_dir)) {
      mkdir($upload_dir, 0755, true);
    }
    
    $file = $_FILES['image'];
    $file_name = $file['name'];
    $file_tmp = $file['tmp_name'];
    $file_error = $file['error'];
    $file_size = $file['size'];
    
    // Validasi file
    $allowed_ext = ['jpg', 'jpeg', 'png', 'gif'];
    $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
    
    if($file_error === UPLOAD_ERR_OK) {
      if(in_array($file_ext, $allowed_ext) && $file_size <= 2000000) {
        // Generate unique filename
        $new_filename = 'labor_' . time() . '_' . mt_rand(1000, 9999) . '.' . $file_ext;
        $file_path = $upload_dir . $new_filename;
        
        if(move_uploaded_file($file_tmp, $file_path)) {
          $image = $new_filename;
        } else {
          $message = "Gagal mengupload gambar!";
          $message_type = "danger";
        }
      } else {
        $message = "Format file tidak didukung atau ukuran terlalu besar (max 2MB)!";
        $message_type = "danger";
      }
    }
  }
  
  if(empty($nama) || empty($deskripsi) || empty($color)){
    $message = "Semua field harus diisi!";
    $message_type = "danger";
  } else {
    if($labor_id > 0){
      // Edit labor
      if(!empty($image)) {
        // Hapus gambar lama jika ada
        $old_labor = mysqli_fetch_assoc(mysqli_query($conn, "SELECT image FROM labor WHERE id=$labor_id"));
        if(!empty($old_labor['image'])) {
          $old_file = '../assets/uploads/labor/' . $old_labor['image'];
          if(file_exists($old_file)) {
            unlink($old_file);
          }
        }
        $update = mysqli_query($conn, "UPDATE labor SET nama='$nama', deskripsi='$deskripsi', color='$color', image='$image' WHERE id=$labor_id");
      } else {
        $update = mysqli_query($conn, "UPDATE labor SET nama='$nama', deskripsi='$deskripsi', color='$color' WHERE id=$labor_id");
      }
      
      if($update){
        $message = "Labor berhasil diperbarui!";
        $message_type = "success";
      } else {
        $message = "Gagal memperbarui labor!";
        $message_type = "danger";
      }
    } else {
      // Add new labor
      if(!empty($image)) {
        $insert = mysqli_query($conn, "INSERT INTO labor (nama, deskripsi, color, image) VALUES ('$nama', '$deskripsi', '$color', '$image')");
      } else {
        $insert = mysqli_query($conn, "INSERT INTO labor (nama, deskripsi, color) VALUES ('$nama', '$deskripsi', '$color')");
      }
      
      if($insert){
        $message = "Labor baru berhasil ditambahkan!";
        $message_type = "success";
      } else {
        $message = "Gagal menambahkan labor!";
        $message_type = "danger";
      }
    }
  }
}

// Handle DELETE Labor
if(isset($_POST['delete_labor'])){
  $labor_id = intval($_POST['labor_id']);
  
  // Cek apakah ada peralatan terkait
  $check_barang = mysqli_query($conn, "SELECT COUNT(*) as total FROM barang_labor WHERE labor_id=$labor_id");
  $barang_count = mysqli_fetch_assoc($check_barang)['total'];
  
  if($barang_count > 0){
    $message = "Tidak dapat menghapus labor karena masih terdapat $barang_count peralatan terkait!";
    $message_type = "danger";
  } else {
    $delete = mysqli_query($conn, "DELETE FROM labor WHERE id=$labor_id");
    if($delete){
      $message = "Labor berhasil dihapus!";
      $message_type = "success";
    } else {
      $message = "Gagal menghapus labor!";
      $message_type = "danger";
    }
  }
}

// Get all labor
$labor_list = mysqli_query($conn, "SELECT * FROM labor ORDER BY created_at DESC");

// Color options
$color_options = ['primary', 'secondary', 'success', 'danger', 'warning', 'info', 'light', 'dark'];
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Kelola Labor</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    :root {
      --primary: #f59e0b;
      --primary-light: #fbbf24;
      --primary-dark: #d97706;
      --secondary: #fb923c;
      --success: #22c55e;
      --danger: #ef4444;
      --warning: #eab308;
      --bg-primary: #ffffff;
      --bg-secondary: #f8fafc;
      --text-primary: #0f172a;
      --text-secondary: #64748b;
      --border-color: #e2e8f0;
      --shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    }

    body.dark-mode {
      --primary: #fbbf24;
      --primary-light: #fcd34d;
      --primary-dark: #f59e0b;
      --secondary: #fb923c;
      --success: #22c55e;
      --danger: #ef4444;
      --warning: #eab308;
      --bg-primary: #1e293b;
      --bg-secondary: #0f172a;
      --text-primary: #f1f5f9;
      --text-secondary: #cbd5e1;
      --border-color: #475569;
      --shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
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
      margin-left: 250px;
      transition: margin-left 0.3s ease;
    }

    /* Page Header */
    .page-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 30px;
      gap: 20px;
      flex-wrap: wrap;
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

    /* Alert */
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
      border: 2px solid var(--success);
      color: var(--success);
    }

    .alert-danger {
      background: rgba(239, 68, 68, 0.1);
      border: 2px solid var(--danger);
      color: var(--danger);
    }

    .alert-warning {
      background: rgba(234, 179, 8, 0.1);
      border: 2px solid var(--warning);
      color: var(--warning);
    }

    .alert-custom i {
      font-size: 18px;
    }

    /* Button Add */
    .btn-add {
      background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
      color: white;
      border: none;
      padding: 10px 20px;
      border-radius: 8px;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.3s;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .btn-add:hover {
      transform: translateY(-2px);
      box-shadow: 0 6px 20px rgba(245, 158, 11, 0.3);
      color: white;
    }

    /* Labor Grid */
    .labor-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
      gap: 20px;
      margin-top: 30px;
    }

    .labor-card {
      background: var(--bg-primary);
      border-radius: 12px;
      padding: 20px;
      box-shadow: var(--shadow);
      transition: all 0.3s;
      border: 2px solid transparent;
      overflow: hidden;
    }

    .labor-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
      border-color: var(--primary);
    }

    .labor-image-container {
      width: 100%;
      height: 180px;
      background: linear-gradient(135deg, #f59e0b 0%, #fb923c 100%);
      border-radius: 8px;
      display: flex;
      align-items: center;
      justify-content: center;
      margin-bottom: 15px;
      overflow: hidden;
      position: relative;
    }

    .labor-image-container img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      transition: transform 0.3s ease;
    }

    .labor-card:hover .labor-image-container img {
      transform: scale(1.08);
    }

    .labor-image-container i {
      font-size: 64px;
      color: white;
      position: relative;
      z-index: 1;
      transition: transform 0.3s ease;
    }

    .labor-card:hover .labor-image-container i {
      transform: scale(1.15) rotate(10deg);
    }

    .labor-name {
      font-size: 18px;
      font-weight: 700;
      color: var(--text-primary);
      margin-bottom: 8px;
    }

    .labor-desc {
      font-size: 13px;
      color: var(--text-secondary);
      margin-bottom: 15px;
      line-height: 1.5;
    }

    .labor-info {
      display: flex;
      gap: 8px;
      align-items: center;
      font-size: 12px;
      color: var(--text-secondary);
      margin-bottom: 15px;
      padding-bottom: 15px;
      border-bottom: 1px solid var(--border-color);
    }

    .labor-actions {
      display: flex;
      gap: 8px;
    }

    .btn-action {
      flex: 1;
      padding: 8px 12px;
      border: none;
      border-radius: 6px;
      font-size: 12px;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.3s;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 4px;
    }

    .btn-edit {
      background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
      color: white;
    }

    .btn-edit:hover {
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(245, 158, 11, 0.3);
    }

    .btn-delete {
      background: rgba(239, 68, 68, 0.1);
      color: var(--danger);
      border: 2px solid var(--danger);
    }

    .btn-delete:hover {
      background: var(--danger);
      color: white;
    }

    .btn-toggle {
      background: rgba(34, 197, 94, 0.1);
      color: var(--success);
      border: 2px solid var(--success);
      flex: 1;
    }

    .btn-toggle:hover {
      background: var(--success);
      color: white;
    }

    .btn-toggle.inactive {
      background: rgba(239, 68, 68, 0.1);
      color: var(--danger);
      border-color: var(--danger);
    }

    .empty-state {
      text-align: center;
      padding: 60px 20px;
      color: var(--text-secondary);
    }

    .empty-state i {
      font-size: 48px;
      margin-bottom: 12px;
      opacity: 0.5;
    }

    .empty-state p {
      font-size: 16px;
      margin: 0;
    }

    /* Modal */
    .modal-overlay {
      display: none;
      position: fixed;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background: rgba(0, 0, 0, 0.5);
      z-index: 1000;
      align-items: center;
      justify-content: center;
    }

    .modal-overlay.active {
      display: flex;
    }

    .modal-content {
      background: var(--bg-primary);
      border-radius: 12px;
      padding: 30px;
      max-width: 500px;
      width: 90%;
      max-height: 90vh;
      overflow-y: auto;
      box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
      animation: slideInUp 0.3s ease;
    }

    .modal-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 20px;
      border-bottom: 2px solid var(--border-color);
      padding-bottom: 15px;
    }

    .modal-header h2 {
      font-size: 20px;
      font-weight: 700;
      color: var(--text-primary);
      display: flex;
      align-items: center;
      gap: 10px;
      margin: 0;
    }

    .modal-header h2 i {
      color: var(--primary);
    }

    .modal-close {
      background: none;
      border: none;
      font-size: 24px;
      cursor: pointer;
      color: var(--text-secondary);
      transition: all 0.3s;
    }

    .modal-close:hover {
      color: var(--primary);
      transform: rotate(90deg);
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

    .form-group input,
    .form-group textarea,
    .form-group select {
      width: 100%;
      padding: 12px 15px;
      border: 2px solid var(--border-color);
      border-radius: 8px;
      font-size: 14px;
      transition: all 0.3s;
      background: var(--bg-secondary);
      color: var(--text-primary);
    }

    .form-group input:focus,
    .form-group textarea:focus,
    .form-group select:focus {
      outline: none;
      border-color: var(--primary);
      box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.1);
    }

    .form-group textarea {
      resize: vertical;
      min-height: 100px;
    }

    /* Custom File Input */
    .file-input-wrapper {
      position: relative;
      overflow: hidden;
      display: inline-block;
      width: 100%;
    }

    .file-input-wrapper input[type="file"] {
      display: none;
    }

    .file-input-label {
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 10px;
      padding: 20px;
      border: 2px dashed var(--primary);
      border-radius: 8px;
      background: rgba(245, 158, 11, 0.05);
      cursor: pointer;
      transition: all 0.3s;
      color: var(--primary);
      font-weight: 600;
    }

    .file-input-label:hover {
      background: rgba(245, 158, 11, 0.15);
      border-color: var(--primary-dark);
      transform: translateY(-2px);
    }

    .file-input-label i {
      font-size: 24px;
    }

    .file-input-name {
      display: block;
      margin-top: 8px;
      font-size: 12px;
      color: var(--text-secondary);
      text-align: center;
    }

    .file-input-hint {
      font-size: 12px;
      color: var(--text-secondary);
      text-align: center;
      margin-top: 8px;
    }

    .image-preview-container.show {
      display: flex;
      justify-content: center;
      align-items: center;
    }

    .image-preview-container {
      margin-top: 12px;
      display: none;
    }

    .image-preview {
      max-width: 120px;
      height: 120px;
      border-radius: 8px;
      object-fit: cover;
      border: 2px solid var(--border-color);
      transition: all 0.3s;
    }

    .image-preview:hover {
      border-color: var(--primary);
      box-shadow: 0 4px 12px rgba(245, 158, 11, 0.2);
    }

    .color-preview {
      display: flex;
      gap: 8px;
      margin-top: 8px;
      flex-wrap: wrap;
    }

    .color-option {
      width: 40px;
      height: 40px;
      border-radius: 6px;
      cursor: pointer;
      border: 2px solid transparent;
      transition: all 0.3s;
    }

    .color-option:hover {
      transform: scale(1.1);
    }

    .color-option.selected {
      border-color: var(--text-primary);
      box-shadow: 0 0 0 2px var(--bg-primary);
    }

    .modal-footer {
      display: flex;
      gap: 10px;
      margin-top: 25px;
    }

    .btn-modal {
      flex: 1;
      padding: 12px 20px;
      border: none;
      border-radius: 8px;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.3s;
      font-size: 14px;
    }

    .btn-save {
      background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
      color: white;
    }

    .btn-save:hover {
      transform: translateY(-2px);
      box-shadow: 0 6px 20px rgba(245, 158, 11, 0.3);
    }

    .btn-cancel {
      background: var(--bg-secondary);
      color: var(--text-primary);
      border: 2px solid var(--border-color);
    }

    .btn-cancel:hover {
      background: var(--border-color);
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

    @keyframes slideInUp {
      from {
        opacity: 0;
        transform: translateY(20px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    @media (max-width: 768px) {
      .main-content {
        margin-left: 70px;
        padding: 20px 15px;
      }

      .page-header {
        flex-direction: column;
        align-items: flex-start;
      }

      .labor-grid {
        grid-template-columns: 1fr;
      }

      .modal-content {
        width: 95%;
      }

      body.sidebar-collapsed .main-content {
        margin-left: 70px;
      }
    }
  </style>
</head>
<body>

<?php include '../assets/admin_sidebar.php'; ?>

<div class="main-content">
  <!-- Alert Messages -->
  <?php if(!empty($message)): ?>
    <div class="alert-custom alert-<?php echo $message_type; ?>">
      <i class="fas fa-<?php echo $message_type === 'success' ? 'check-circle' : ($message_type === 'danger' ? 'exclamation-circle' : 'exclamation-triangle'); ?>"></i>
      <span><?php echo $message; ?></span>
    </div>
  <?php endif; ?>

  <!-- Page Header -->
  <div class="page-header">
    <h1>
      <i class="fas fa-building"></i>
      Kelola Labor
    </h1>
    <button class="btn-add" onclick="openModalAdd()">
      <i class="fas fa-plus"></i> Tambah Labor
    </button>
  </div>

  <!-- Labor Grid -->
  <div class="labor-grid" id="laborGrid">
    <?php if(mysqli_num_rows($labor_list) > 0): ?>
      <?php while($labor = mysqli_fetch_assoc($labor_list)): 
        // Get equipment count
        $eq_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM barang_labor WHERE labor_id=".$labor['id']))['total'];
      ?>
      <div class="labor-card">
        <div class="labor-image-container">
          <?php if(!empty($labor['image'])): ?>
            <img src="../assets/uploads/labor/<?php echo htmlspecialchars($labor['image']); ?>" alt="<?php echo htmlspecialchars($labor['nama']); ?>">
          <?php endif; ?>
        </div>
        <div class="labor-name"><?php echo htmlspecialchars($labor['nama']); ?></div>
        <div class="labor-desc"><?php echo htmlspecialchars(substr($labor['deskripsi'], 0, 100)); ?>...</div>
        <div class="labor-info">
          <i class="fas fa-tools"></i>
          <span><?php echo $eq_count; ?> Peralatan</span>
        </div>
        <div class="labor-actions">
          <button class="btn-action btn-edit" onclick="openModalEdit(<?php echo $labor['id']; ?>, '<?php echo addslashes($labor['nama']); ?>', '<?php echo addslashes($labor['deskripsi']); ?>', '<?php echo htmlspecialchars($labor['color']); ?>', '<?php echo htmlspecialchars($labor['image'] ?? ''); ?>')">
            <i class="fas fa-edit"></i> Edit
          </button>
          <button class="btn-action btn-delete" onclick="deleteLabor(<?php echo $labor['id']; ?>, '<?php echo addslashes($labor['nama']); ?>')">
            <i class="fas fa-trash"></i> Hapus
          </button>
        </div>
      </div>
      <?php endwhile; ?>
    <?php else: ?>
      <div class="empty-state" style="grid-column: 1 / -1;">
        <i class="fas fa-inbox"></i>
        <p>Belum ada labor yang terdaftar</p>
      </div>
    <?php endif; ?>
  </div>
</div>

<!-- Modal Add Labor -->
<div class="modal-overlay" id="modalAdd">
  <div class="modal-content">
    <div class="modal-header">
      <h2><i class="fas fa-plus-circle"></i> Tambah Labor Baru</h2>
      <button class="modal-close" onclick="closeModalAdd()">×</button>
    </div>
    <form method="POST" enctype="multipart/form-data">
      <input type="hidden" name="save_labor">
      <div class="form-group">
        <label><i class="fas fa-heading"></i> Nama Labor</label>
        <input type="text" name="nama" placeholder="Masukkan nama labor..." required>
      </div>
      <div class="form-group">
        <label><i class="fas fa-file-alt"></i> Deskripsi</label>
        <textarea name="deskripsi" placeholder="Masukkan deskripsi labor..." required></textarea>
      </div>
      <div class="form-group">
        <label><i class="fas fa-palette"></i> Warna</label>
        <select name="color" required>
          <option value="">-- Pilih Warna --</option>
          <?php foreach($color_options as $color): ?>
            <option value="<?php echo $color; ?>"><?php echo ucfirst($color); ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group">
        <label><i class="fas fa-image"></i> Gambar Labor</label>
        <div class="file-input-wrapper">
          <input type="file" id="addImage" name="image" accept="image/*" onchange="previewImage(this, 'addImagePreview'); updateFileName(this, 'addFileName')">
          <label for="addImage" class="file-input-label">
            <i class="fas fa-cloud-upload-alt"></i>
            <span>Pilih Gambar atau Drag & Drop</span>
          </label>
          <span class="file-input-name" id="addFileName"></span>
        </div>
        <div class="file-input-hint">Format: JPG, PNG, GIF | Max: 2MB</div>
        <div class="image-preview-container" id="addImagePreview">
          <img class="image-preview" id="addImagePreviewImg" src="" alt="Preview">
        </div>
      </div>
      <div class="modal-footer">
        <button type="submit" class="btn-modal btn-save">
          <i class="fas fa-save"></i> Simpan
        </button>
        <button type="button" class="btn-modal btn-cancel" onclick="closeModalAdd()">
          <i class="fas fa-times"></i> Batal
        </button>
      </div>
    </form>
  </div>
</div>

<!-- Modal Edit Labor -->
<div class="modal-overlay" id="modalEdit">
  <div class="modal-content">
    <div class="modal-header">
      <h2><i class="fas fa-edit"></i> Edit Labor</h2>
      <button class="modal-close" onclick="closeModalEdit()">×</button>
    </div>
    <form method="POST" enctype="multipart/form-data">
      <input type="hidden" name="save_labor">
      <input type="hidden" name="labor_id" id="editLaborId">
      <input type="hidden" name="old_image" id="editOldImage">
      <div class="form-group">
        <label><i class="fas fa-heading"></i> Nama Labor</label>
        <input type="text" name="nama" id="editNama" placeholder="Masukkan nama labor..." required>
      </div>
      <div class="form-group">
        <label><i class="fas fa-file-alt"></i> Deskripsi</label>
        <textarea name="deskripsi" id="editDeskripsi" placeholder="Masukkan deskripsi labor..." required></textarea>
      </div>
      <div class="form-group">
        <label><i class="fas fa-palette"></i> Warna</label>
        <select name="color" id="editColor" required>
          <option value="">-- Pilih Warna --</option>
          <?php foreach($color_options as $color): ?>
            <option value="<?php echo $color; ?>"><?php echo ucfirst($color); ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group">
        <label><i class="fas fa-image"></i> Gambar Labor</label>
        <div class="file-input-wrapper">
          <input type="file" id="editImage" name="image" accept="image/*" onchange="previewImage(this, 'editImagePreview'); updateFileName(this, 'editFileName')">
          <label for="editImage" class="file-input-label">
            <i class="fas fa-cloud-upload-alt"></i>
            <span>Pilih Gambar atau Drag & Drop</span>
          </label>
          <span class="file-input-name" id="editFileName"></span>
        </div>
        <div class="file-input-hint">Format: JPG, PNG, GIF | Max: 2MB (Kosongkan jika tidak ingin mengubah)</div>
        <div class="image-preview-container" id="editImagePreview">
          <img class="image-preview" id="editImagePreviewImg" src="" alt="Preview">
        </div>
      </div>
      <div class="modal-footer">
        <button type="submit" class="btn-modal btn-save">
          <i class="fas fa-save"></i> Simpan
        </button>
        <button type="button" class="btn-modal btn-cancel" onclick="closeModalEdit()">
          <i class="fas fa-times"></i> Batal
        </button>
      </div>
    </form>
  </div>
</div>

<script>
// Update file name display
function updateFileName(input, elementId) {
  const element = document.getElementById(elementId);
  if(input.files && input.files[0]) {
    element.textContent = '✓ ' + input.files[0].name;
    element.style.color = 'var(--success)';
  } else {
    element.textContent = '';
  }
}

// Image Preview function
function previewImage(input, previewContainerId) {
  const container = document.getElementById(previewContainerId);
  const img = document.querySelector(`#${previewContainerId} img`);
  
  if(input.files && input.files[0]) {
    const reader = new FileReader();
    reader.onload = function(e) {
      img.src = e.target.result;
      container.classList.add('show');
    };
    reader.readAsDataURL(input.files[0]);
  } else {
    container.classList.remove('show');
  }
}

// Drag and drop functionality
document.addEventListener('DOMContentLoaded', function() {
  setupDragAndDrop('addImage', 'addImagePreview', 'addFileName');
  setupDragAndDrop('editImage', 'editImagePreview', 'editFileName');
});

function setupDragAndDrop(inputId, previewId, fileNameId) {
  const fileInput = document.getElementById(inputId);
  const label = fileInput.nextElementSibling;
  
  if(!label) return;
  
  ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
    label.addEventListener(eventName, preventDefaults, false);
  });
  
  function preventDefaults(e) {
    e.preventDefault();
    e.stopPropagation();
  }
  
  ['dragenter', 'dragover'].forEach(eventName => {
    label.addEventListener(eventName, () => {
      label.style.background = 'rgba(245, 158, 11, 0.25)';
      label.style.borderColor = 'var(--primary-dark)';
    }, false);
  });
  
  ['dragleave', 'drop'].forEach(eventName => {
    label.addEventListener(eventName, () => {
      label.style.background = 'rgba(245, 158, 11, 0.05)';
      label.style.borderColor = 'var(--primary)';
    }, false);
  });
  
  label.addEventListener('drop', (e) => {
    const dt = e.dataTransfer;
    const files = dt.files;
    fileInput.files = files;
    
    // Trigger preview
    previewImage(fileInput, previewId);
    updateFileName(fileInput, fileNameId);
  }, false);
}

// Modal functions
function openModalAdd() {
  document.getElementById('modalAdd').classList.add('active');
}

function closeModalAdd() {
  document.getElementById('modalAdd').classList.remove('active');
}

function openModalEdit(id, nama, deskripsi, color, image = null) {
  document.getElementById('editLaborId').value = id;
  document.getElementById('editNama').value = nama;
  document.getElementById('editDeskripsi').value = deskripsi;
  document.getElementById('editColor').value = color;
  document.getElementById('editOldImage').value = image || '';
  
  // Reset file input
  document.getElementById('editImage').value = '';
  document.getElementById('editFileName').textContent = '';
  
  // Tampilkan preview gambar dari database jika ada
  const previewContainer = document.getElementById('editImagePreview');
  const previewImg = document.getElementById('editImagePreviewImg');
  
  if(image && image.trim()) {
    previewImg.src = '../assets/uploads/labor/' + image;
    previewContainer.classList.add('show');
  } else {
    previewContainer.classList.remove('show');
  }
  
  document.getElementById('modalEdit').classList.add('active');
}

function closeModalEdit() {
  document.getElementById('modalEdit').classList.remove('active');
}

// Delete function
function deleteLabor(id, nama) {
  if(confirm(`Apakah Anda yakin ingin menghapus labor "${nama}"?`)) {
    const form = document.createElement('form');
    form.method = 'POST';
    form.innerHTML = `
      <input type="hidden" name="delete_labor" value="1">
      <input type="hidden" name="labor_id" value="${id}">
    `;
    document.body.appendChild(form);
    form.submit();
  }
}

// Close modal when clicking outside
document.addEventListener('click', function(e) {
  if(e.target.classList.contains('modal-overlay')) {
    e.target.classList.remove('active');
  }
});
</script>

</body>
</html>
