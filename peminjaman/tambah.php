<?php
session_start();
include '../config/database.php';
include '../assets/header.php';

// Create tables if not exist
$create_labor = "CREATE TABLE IF NOT EXISTS labor (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nama VARCHAR(191) NOT NULL,
  deskripsi TEXT,
  icon VARCHAR(50),
  color VARCHAR(20),
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
)";
mysqli_query($conn, $create_labor);

$create_barang = "CREATE TABLE IF NOT EXISTS barang_labor (
  id INT AUTO_INCREMENT PRIMARY KEY,
  labor_id INT NOT NULL,
  nama VARCHAR(191) NOT NULL,
  deskripsi TEXT,
  stok INT DEFAULT 1,
  status VARCHAR(20) DEFAULT 'Tersedia',
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (labor_id) REFERENCES labor(id)
)";
mysqli_query($conn, $create_barang);

// Ensure columns exist
$check_labor_col = mysqli_query($conn, "SHOW COLUMNS FROM peminjaman LIKE 'labor_id'");
if($check_labor_col && mysqli_num_rows($check_labor_col) == 0){
  mysqli_query($conn, "ALTER TABLE peminjaman ADD COLUMN labor_id INT NULL");
}

$check_barang_col = mysqli_query($conn, "SHOW COLUMNS FROM peminjaman LIKE 'barang_labor_id'");
if($check_barang_col && mysqli_num_rows($check_barang_col) == 0){
  mysqli_query($conn, "ALTER TABLE peminjaman ADD COLUMN barang_labor_id INT NULL");
}

$check_qty_col = mysqli_query($conn, "SHOW COLUMNS FROM peminjaman LIKE 'jumlah'");
if($check_qty_col && mysqli_num_rows($check_qty_col) == 0){
  mysqli_query($conn, "ALTER TABLE peminjaman ADD COLUMN jumlah INT DEFAULT 1");
}

$message = '';
$message_type = '';

if(isset($_POST['kirim'])){
  $labor_id = isset($_POST['labor_id']) && !empty($_POST['labor_id']) ? intval($_POST['labor_id']) : null;
  $barang_id = isset($_POST['barang_labor_id']) && !empty($_POST['barang_labor_id']) ? intval($_POST['barang_labor_id']) : null;
  $tanggal_mulai = mysqli_real_escape_string($conn, $_POST['tanggal_mulai']);
  $tanggal_selesai = mysqli_real_escape_string($conn, $_POST['tanggal_selesai']);
  $jumlah = isset($_POST['jumlah']) ? intval($_POST['jumlah']) : 1;
  $keperluan = mysqli_real_escape_string($conn, $_POST['tanggung']);
  $user_id = intval($_SESSION['user_id']);
  
  if(empty($tanggal_mulai) || empty($tanggal_selesai) || empty($keperluan) || !$labor_id || !$barang_id || $jumlah < 1){
    $message = "Semua field harus diisi dengan benar!";
    $message_type = "danger";
  } else if(strtotime($tanggal_selesai) <= strtotime($tanggal_mulai)){
    $message = "Tanggal selesai harus setelah tanggal mulai!";
    $message_type = "danger";
  } else {
    // Check stock availability
    $stock_q = mysqli_query($conn, "SELECT stok FROM barang_labor WHERE id = $barang_id");
    $stock = mysqli_fetch_assoc($stock_q);
    
    if($stock['stok'] < $jumlah){
      $message = "Stok peralatan tidak mencukupi! Tersedia: " . $stock['stok'];
      $message_type = "danger";
    } else {
      $ins = mysqli_query($conn, "INSERT INTO peminjaman(user_id, tanggal, tanggung_jawab, labor_id, barang_labor_id, jumlah, status) 
        VALUES('$user_id', '$tanggal_mulai', '$keperluan', $labor_id, $barang_id, $jumlah, 'Menunggu')");
      
      if($ins){
        $message = "Peminjaman berhasil diajukan! Tunggu persetujuan dari admin.";
        $message_type = "success";
      } else {
        $message = "Gagal mengajukan peminjaman!";
        $message_type = "danger";
      }
    }
  }
}

// Get user info
$user_q = mysqli_query($conn, "SELECT * FROM users WHERE id='".$_SESSION['user_id']."'");
$user = mysqli_fetch_assoc($user_q);

// Get labor list
$labor_q = mysqli_query($conn, "SELECT * FROM labor ORDER BY nama ASC");
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Form Peminjaman Labor</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
  :root {
    --primary: #667eea;
    --secondary: #764ba2;
    --success: #28a745;
    --danger: #dc3545;
    --text-dark: #333;
    --text-light: #666;
    --gray-light: #f8f9fa;
    --border: #e0e0e0;
    --shadow: 0 4px 15px rgba(0,0,0,0.1);
  }

  body.dark-mode {
    --primary: #667eea;
    --secondary: #764ba2;
    --success: #28a745;
    --danger: #dc3545;
    --text-dark: #f1f5f9;
    --text-light: #cbd5e1;
    --gray-light: #1e293b;
    --border: #334155;
    --shadow: 0 4px 15px rgba(0,0,0,0.3);
  }

  body {
    background: linear-gradient(135deg, #667eea15 0%, #764ba215 100%);
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
  }

  .page-header {
    padding: 20px;
    background: white;
    border-bottom: 2px solid var(--border);
  }

  .page-header a {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    color: var(--primary);
    text-decoration: none;
    font-weight: 600;
    transition: all 0.3s ease;
  }

  .page-header a:hover {
    gap: 12px;
    color: var(--secondary);
  }

  .booking-container {
    display: grid;
    grid-template-columns: 320px 1fr;
    gap: 30px;
    padding: 30px;
    max-width: 1200px;
    margin: 0 auto;
  }

  .user-card {
    background: white;
    border-radius: 16px;
    padding: 25px;
    box-shadow: var(--shadow);
    height: fit-content;
    position: sticky;
    top: 30px;
  }

  .user-avatar {
    width: 100%;
    height: 150px;
    background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 60px;
    color: white;
    margin-bottom: 20px;
  }

  .user-info-item {
    padding: 12px;
    background: var(--gray-light);
    border-radius: 8px;
    margin-bottom: 10px;
    border-left: 3px solid var(--primary);
  }

  .user-label {
    font-size: 11px;
    color: var(--text-light);
    text-transform: uppercase;
    font-weight: 600;
    margin-bottom: 4px;
  }

  .user-value {
    font-weight: 600;
    color: var(--text-dark);
    font-size: 14px;
  }

  .form-card {
    background: white;
    border-radius: 16px;
    padding: 30px;
    box-shadow: var(--shadow);
  }

  .form-header {
    display: flex;
    align-items: center;
    gap: 15px;
    margin-bottom: 30px;
    padding-bottom: 20px;
    border-bottom: 2px solid var(--border);
  }

  .form-header i {
    font-size: 36px;
    background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
  }

  .form-header h2 {
    margin: 0;
    color: var(--text-dark);
    font-weight: 700;
    font-size: 24px;
  }

  .alert {
    border-radius: 12px;
    border-left: 4px solid;
    margin-bottom: 25px;
    padding: 15px;
  }

  .alert-success {
    background-color: #d4edda;
    border-left-color: var(--success);
    color: #155724;
  }

  .alert-danger {
    background-color: #f8d7da;
    border-left-color: var(--danger);
    color: #721c24;
  }

  .form-section {
    margin-bottom: 30px;
  }

  .section-title {
    font-size: 16px;
    font-weight: 700;
    color: var(--text-dark);
    margin-bottom: 18px;
    padding-bottom: 10px;
    border-bottom: 2px solid var(--border);
    display: flex;
    align-items: center;
    gap: 10px;
  }

  .section-title i {
    color: var(--primary);
    font-size: 18px;
  }

  .labor-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
    gap: 15px;
  }

  .labor-option {
    position: relative;
    cursor: pointer;
  }

  .labor-option input[type="radio"] {
    display: none;
  }

  .labor-card-select {
    padding: 0;
    border: 2px solid var(--border);
    border-radius: 12px;
    overflow: hidden;
    transition: all 0.3s ease;
    background: white;
  }

  .labor-option input[type="radio"]:checked + .labor-card-select {
    border-color: var(--primary);
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
    transform: scale(1.02);
  }

  .labor-card-select:hover {
    border-color: var(--primary);
  }

  .labor-image-preview {
    width: 100%;
    height: 120px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 48px;
    position: relative;
    color: white;
  }

  .labor-image-preview::after {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(135deg, rgba(0,0,0,0) 0%, rgba(0,0,0,0.15) 100%);
  }

  .labor-image-preview.primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  }

  .labor-image-preview.success {
    background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
  }

  .labor-image-preview.danger {
    background: linear-gradient(135deg, #dc3545 0%, #e83e8c 100%);
  }

  .labor-card-info {
    padding: 12px;
  }

  .labor-card-name {
    font-weight: 700;
    font-size: 13px;
    color: var(--text-dark);
    margin-bottom: 4px;
  }

  .labor-card-status {
    font-size: 11px;
    color: var(--success);
    font-weight: 600;
  }

  .form-group {
    margin-bottom: 20px;
  }

  .form-label {
    display: flex;
    align-items: center;
    gap: 8px;
    color: var(--text-dark);
    font-weight: 600;
    margin-bottom: 10px;
    font-size: 14px;
  }

  .form-label i {
    color: var(--primary);
  }

  .form-control {
    width: 100%;
    padding: 12px 15px;
    border: 2px solid var(--border);
    border-radius: 8px;
    font-size: 14px;
    transition: all 0.3s ease;
    font-family: inherit;
  }

  .form-control:focus {
    outline: none;
    border-color: var(--primary);
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
  }

  .form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
  }

  .form-control textarea {
    resize: vertical;
    min-height: 100px;
  }

  .equipment-selection {
    display: grid;
    gap: 10px;
  }

  .equipment-item {
    padding: 15px;
    border: 2px solid var(--border);
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 15px;
    background: white;
  }

  .equipment-item input[type="radio"] {
    margin: 0;
    width: 18px;
    height: 18px;
    cursor: pointer;
  }

  .equipment-item:hover {
    border-color: var(--primary);
    background: rgba(102, 126, 234, 0.02);
  }

  .equipment-item input[type="radio"]:checked + label {
    color: var(--primary);
    font-weight: 700;
  }

  .equipment-item-parent {
    border-color: var(--primary);
    background: rgba(102, 126, 234, 0.05);
  }

  .equipment-info {
    flex: 1;
    cursor: pointer;
  }

  .equipment-name {
    font-weight: 600;
    color: var(--text-dark);
    font-size: 14px;
    margin-bottom: 4px;
  }

  .equipment-desc {
    font-size: 12px;
    color: var(--text-light);
  }

  .equipment-stock {
    background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
    color: white;
    padding: 6px 12px;
    border-radius: 8px;
    font-size: 12px;
    font-weight: 600;
    text-align: center;
    min-width: 70px;
  }

  .quantity-control {
    display: flex;
    align-items: center;
    gap: 8px;
    border: 2px solid var(--border);
    border-radius: 8px;
    padding: 4px 8px;
    width: fit-content;
  }

  .quantity-btn {
    background: none;
    border: none;
    color: var(--primary);
    font-size: 16px;
    cursor: pointer;
    padding: 4px 8px;
    transition: all 0.2s ease;
  }

  .quantity-btn:hover {
    background: var(--gray-light);
    border-radius: 4px;
  }

  .quantity-input {
    width: 50px;
    text-align: center;
    border: none;
    font-weight: 600;
    color: var(--text-dark);
  }

  .quantity-input:focus {
    outline: none;
  }

  .form-footer {
    display: flex;
    gap: 15px;
    margin-top: 30px;
    padding-top: 20px;
    border-top: 2px solid var(--border);
  }

  .btn {
    padding: 12px 25px;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    font-size: 14px;
    cursor: pointer;
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    gap: 8px;
  }

  .btn-cancel {
    background: var(--gray-light);
    color: var(--text-dark);
    flex: 1;
  }

  .btn-cancel:hover {
    background: var(--border);
  }

  .btn-submit {
    background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
    color: white;
    flex: 2;
    position: relative;
    overflow: hidden;
  }

  .btn-submit::before {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    width: 0;
    height: 0;
    border-radius: 50%;
    background: rgba(255,255,255,0.3);
    transform: translate(-50%, -50%);
    transition: width 0.5s, height 0.5s;
  }

  .btn-submit:active::before {
    width: 300px;
    height: 300px;
  }

  .btn-submit:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(102, 126, 234, 0.3);
  }

  @media (max-width: 992px) {
    .booking-container {
      grid-template-columns: 1fr;
    }

    .user-card {
      position: static;
    }

    .labor-grid {
      grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
    }
  }

  @media (max-width: 576px) {
    .booking-container {
      padding: 15px;
      gap: 20px;
    }

    .form-card {
      padding: 20px;
    }

    .form-row {
      grid-template-columns: 1fr;
    }

    .labor-grid {
      grid-template-columns: repeat(2, 1fr);
    }

    .form-footer {
      flex-direction: column;
    }

    .btn-cancel, .btn-submit {
      flex: 1;
    }
  }
</style>
</head>
<body>

<div class="page-header">
  <a href="../index.php"><i class="fas fa-arrow-left"></i> Kembali ke Dashboard</a>
</div>

<div class="booking-container">
  <!-- User Info Card -->
  <div class="user-card">
    <div class="user-avatar">
      <i class="fas fa-user-circle"></i>
    </div>
    <?php if($user): ?>
      <div class="user-info-item">
        <div class="user-label"><i class="fas fa-id-card"></i> Nama</div>
        <div class="user-value"><?php echo htmlspecialchars($user['nama']); ?></div>
      </div>
      <div class="user-info-item">
        <div class="user-label"><i class="fas fa-barcode"></i> NIM</div>
        <div class="user-value"><?php echo htmlspecialchars($user['nim'] ?? '-'); ?></div>
      </div>
      <div class="user-info-item">
        <div class="user-label"><i class="fas fa-envelope"></i> Email</div>
        <div class="user-value"><?php echo htmlspecialchars($user['email'] ?? '-'); ?></div>
      </div>
    <?php endif; ?>
  </div>

  <!-- Form Card -->
  <div class="form-card">
    <div class="form-header">
      <i class="fas fa-file-contract"></i>
      <h2>Ajukan Peminjaman</h2>
    </div>

    <?php if($message): ?>
      <div class="alert alert-<?php echo $message_type; ?>">
        <i class="fas fa-<?php echo $message_type === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
        <?php echo $message; ?>
      </div>
    <?php endif; ?>

    <form method="post">
      <!-- Labor Selection -->
      <div class="form-section">
        <div class="section-title">
          <i class="fas fa-building"></i> Pilih Laboratorium
        </div>
        <div class="labor-grid">
          <?php 
          mysqli_data_seek($labor_q, 0);
          while($l = mysqli_fetch_assoc($labor_q)): 
            $colorClass = $l['color'] ?: 'primary';
          ?>
            <label class="labor-option">
              <input type="radio" name="labor_id" value="<?php echo $l['id']; ?>" required onchange="loadBarang(this.value)">
              <div class="labor-card-select">
                <div class="labor-image-preview <?php echo $colorClass; ?>">
                  <i class="fas fa-<?php echo htmlspecialchars($l['icon'] ?: 'flask'); ?>"></i>
                </div>
                <div class="labor-card-info">
                  <div class="labor-card-name"><?php echo htmlspecialchars($l['nama']); ?></div>
                  <div class="labor-card-status"><i class="fas fa-circle" style="font-size: 8px;"></i> Tersedia</div>
                </div>
              </div>
            </label>
          <?php endwhile; ?>
        </div>
      </div>

      <!-- Equipment Selection -->
      <div class="form-section">
        <div class="section-title">
          <i class="fas fa-toolbox"></i> Pilih Peralatan
        </div>
        <div class="equipment-selection" id="barangContainer">
          <p style="color: #999; text-align: center; padding: 20px;">Pilih laboratorium terlebih dahulu</p>
        </div>
      </div>

      <!-- Date & Quantity -->
      <div class="form-section">
        <div class="section-title">
          <i class="fas fa-calendar-alt"></i> Jadwal & Jumlah
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label"><i class="fas fa-clock"></i> Tanggal Mulai</label>
            <input type="datetime-local" name="tanggal_mulai" class="form-control" id="tanggal_mulai" required>
          </div>
          <div class="form-group">
            <label class="form-label"><i class="fas fa-hourglass-end"></i> Tanggal Selesai</label>
            <input type="datetime-local" name="tanggal_selesai" class="form-control" required>
          </div>
        </div>
        <div class="form-group">
          <label class="form-label"><i class="fas fa-cube"></i> Jumlah Peralatan</label>
          <div class="quantity-control">
            <button type="button" class="quantity-btn" onclick="decreaseQty()">
              <i class="fas fa-minus"></i>
            </button>
            <input type="number" name="jumlah" class="quantity-input" id="qty" value="1" min="1" max="10">
            <button type="button" class="quantity-btn" onclick="increaseQty()">
              <i class="fas fa-plus"></i>
            </button>
          </div>
        </div>
      </div>

      <!-- Keperluan -->
      <div class="form-section">
        <div class="section-title">
          <i class="fas fa-comment-dots"></i> Keperluan Peminjaman
        </div>
        <div class="form-group">
          <label class="form-label"><i class="fas fa-pen"></i> Jelaskan Alasan Peminjaman</label>
          <textarea name="tanggung" class="form-control" placeholder="Jelaskan dengan detail keperluan meminjam peralatan ini..." required></textarea>
        </div>
      </div>

      <!-- Buttons -->
      <div class="form-footer">
        <a href="../index.php" class="btn btn-cancel">
          <i class="fas fa-times"></i> Batalkan
        </a>
        <button type="submit" class="btn btn-submit" name="kirim">
          <i class="fas fa-paper-plane"></i> Ajukan Peminjaman
        </button>
      </div>
    </form>
  </div>
</div>

<?php include '../assets/footer.php'; ?>

<script>
// Set default date to today
document.addEventListener('DOMContentLoaded', function() {
  const now = new Date();
  const today = new Date(now.getTime() - now.getTimezoneOffset() * 60000).toISOString().slice(0, 16);
  document.getElementById('tanggal_mulai').value = today;
  document.getElementById('tanggal_mulai').min = today;
});

// Update tanggal_selesai minimum
document.getElementById('tanggal_mulai').addEventListener('change', function() {
  const endDateInput = document.querySelector('input[name="tanggal_selesai"]');
  endDateInput.min = this.value;
});

// Load equipment when labor is selected
function loadBarang(laborId) {
  const barangContainer = document.getElementById('barangContainer');
  
  if(!laborId) {
    barangContainer.innerHTML = '<p style="color: #999; text-align: center; padding: 20px;">Pilih laboratorium terlebih dahulu</p>';
    return;
  }
  
  barangContainer.innerHTML = '<p style="color: #999; text-align: center; padding: 20px;"><i class="fas fa-spinner fa-spin"></i> Memuat peralatan...</p>';
  
  fetch('../config/get_barang.php?labor_id=' + laborId)
    .then(response => response.json())
    .then(data => {
      if(data.length > 0) {
        barangContainer.innerHTML = data.map((barang, index) => `
          <label style="cursor: pointer;">
            <div class="equipment-item ${index === 0 ? 'equipment-item-parent' : ''}">
              <input type="radio" name="barang_labor_id" value="${barang.id}" ${index === 0 ? 'checked' : ''} required>
              <label class="equipment-info" style="margin: 0; cursor: pointer;">
                <div class="equipment-name"><i class="fas fa-cube"></i> ${barang.nama}</div>
                <div class="equipment-desc">${barang.deskripsi}</div>
              </label>
              <div class="equipment-stock">
                <i class="fas fa-warehouse"></i> ${barang.stok}
              </div>
            </div>
          </label>
        `).join('');
      } else {
        barangContainer.innerHTML = '<p style="color: #999; text-align: center; padding: 20px;">Tidak ada peralatan tersedia untuk laboratorium ini</p>';
      }
    })
    .catch(error => {
      console.error('Error:', error);
      barangContainer.innerHTML = '<p style="color: #999; text-align: center; padding: 20px;">Gagal memuat peralatan</p>';
    });
}

// Quantity controls
function increaseQty() {
  const qty = document.getElementById('qty');
  if(parseInt(qty.value) < 10) {
    qty.value = parseInt(qty.value) + 1;
  }
}

function decreaseQty() {
  const qty = document.getElementById('qty');
  if(parseInt(qty.value) > 1) {
    qty.value = parseInt(qty.value) - 1;
  }
}

// Allow direct input in quantity field
document.getElementById('qty').addEventListener('change', function() {
  let val = parseInt(this.value);
  if(isNaN(val) || val < 1) this.value = 1;
  if(val > 10) this.value = 10;
});

// Load dark mode from localStorage
if(localStorage.getItem('darkMode') === 'true') {
  document.body.classList.add('dark-mode');
}
</script>
</body>
</html>
