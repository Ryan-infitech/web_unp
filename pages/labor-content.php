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
<title>Daftar Labor - Aplikasi Labor</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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

// Pastikan tabel labor ada
 $create_labor = "CREATE TABLE IF NOT EXISTS labor (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nama VARCHAR(191) NOT NULL,
  deskripsi TEXT,
  icon VARCHAR(50),
  color VARCHAR(20),
  image VARCHAR(255),
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
)";
mysqli_query($conn, $create_labor);

// Pastikan kolom image ada di tabel labor (untuk kompatibilitas dengan data lama)
 $check_image_col = mysqli_query($conn, "SHOW COLUMNS FROM labor LIKE 'image'");
if(mysqli_num_rows($check_image_col) == 0) {
  mysqli_query($conn, "ALTER TABLE labor ADD COLUMN image VARCHAR(255) AFTER color");
}

// Pastikan tabel barang_labor ada dengan field stok_awal
 $create_barang = "CREATE TABLE IF NOT EXISTS barang_labor (
  id INT AUTO_INCREMENT PRIMARY KEY,
  labor_id INT NOT NULL,
  nama VARCHAR(191) NOT NULL,
  deskripsi TEXT,
  stok INT DEFAULT 1,
  stok_awal INT DEFAULT 1,
  status VARCHAR(20) DEFAULT 'Tersedia',
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (labor_id) REFERENCES labor(id)
)";
mysqli_query($conn, $create_barang);

// Cek dan tambahkan kolom stok_awal jika belum ada
 $check_column = mysqli_query($conn, "SHOW COLUMNS FROM barang_labor LIKE 'stok_awal'");
if(mysqli_num_rows($check_column) == 0) {
  mysqli_query($conn, "ALTER TABLE barang_labor ADD COLUMN stok_awal INT DEFAULT 1 AFTER stok");
  
  // Update stok_awal dengan nilai stok saat ini untuk data yang sudah ada
  mysqli_query($conn, "UPDATE barang_labor SET stok_awal = stok WHERE stok_awal = 1");
}

// Insert default labor jika kosong
 $labor_count = mysqli_num_rows(mysqli_query($conn, "SELECT id FROM labor"));
if($labor_count == 0){
  mysqli_query($conn, "INSERT INTO labor (nama, deskripsi, icon, color) VALUES 
    ('Labor Animasi', 'Fasilitas lengkap untuk pembuatan animasi 2D dan 3D dengan perangkat profesional', 'fa-film', 'primary'),
    ('Labor Game', 'Studio pengembangan game dengan perangkat gaming dan development tools terlengkap', 'fa-gamepad', 'success'),
    ('Labor Audio', 'Studio rekaman dan produksi audio profesional dengan peralatan berkualitas tinggi', 'fa-volume-up', 'danger')
  ");
  
  $labor_q = mysqli_query($conn, "SELECT id, nama FROM labor");
  while($l = mysqli_fetch_assoc($labor_q)){
    if($l['nama'] == 'Labor Animasi'){
      mysqli_query($conn, "INSERT INTO barang_labor (labor_id, nama, deskripsi, stok, stok_awal) VALUES 
        (".$l['id'].", 'Tablet Wacom Pro', 'Tablet grafis profesional 22 inch', 2, 2),
        (".$l['id'].", 'Monitor 4K', 'Monitor resolusi 4K untuk color grading', 3, 3),
        (".$l['id'].", 'Laptop Rendering', 'Laptop dengan GPU RTX 3080 Ti', 2, 2),
        (".$l['id'].", 'Stylus Pressure Pen', 'Pressure sensitive stylus untuk digital painting', 5, 5)
      ");
    } elseif($l['nama'] == 'Labor Game'){
      mysqli_query($conn, "INSERT INTO barang_labor (labor_id, nama, deskripsi, stok, stok_awal) VALUES 
        (".$l['id'].", 'VR Headset HTC Vive', 'Virtual reality headset untuk development', 2, 2),
        (".$l['id'].", 'Gaming PC High-End', 'PC dengan RTX 4070 untuk game development', 3, 3),
        (".$l['id'].", 'Motion Capture Suit', 'Suit untuk motion capture 3D character', 1, 1),
        (".$l['id'].", 'Game Controller', 'Xbox controller untuk game development', 8, 8)
      ");
    } elseif($l['nama'] == 'Labor Audio'){
      mysqli_query($conn, "INSERT INTO barang_labor (labor_id, nama, deskripsi, stok, stok_awal) VALUES 
        (".$l['id'].", 'Microphone Neumann U87', 'Microphone studio kondenser profesional', 2, 2),
        (".$l['id'].", 'Audio Interface MOTU', 'Audio interface 16 channel profesional', 2, 2),
        (".$l['id'].", 'Monitor Speaker Yamaha', 'Speaker monitor studio aktif', 4, 4),
        (".$l['id'].", 'XLR Cables', 'Professional XLR audio cables', 10, 10)
      ");
    }
  }
}

 $labor_all = mysqli_query($conn, "SELECT * FROM labor ORDER BY id ASC");
?>

<style>
  :root {
    --primary: #667eea;
    --secondary: #764ba2;
    --success: #28a745;
    --danger: #dc3545;
    --warning: #ffc107;
    --text-dark: #333;
    --text-light: #666;
    --gray-light: #f8f9fa;
    --border: #e0e0e0;
  }

  .labor-section-header {
    margin-bottom: 40px;
    text-align: center;
  }

  .labor-section-header h2 {
    font-size: 32px;
    font-weight: 800;
    margin: 0 0 10px 0;
    color: var(--text-dark);
    background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
  }

  .labor-section-header p {
    color: var(--text-light);
    font-size: 16px;
    margin: 0;
  }

  .labor-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 30px;
    animation: fadeIn 0.6s ease-out;
  }

  .labor-card {
    background: white;
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 2px 12px rgba(0,0,0,0.08);
    transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    cursor: pointer;
    position: relative;
    display: flex;
    flex-direction: column;
    border: 2px solid transparent;
  }

  .labor-card:hover {
    transform: translateY(-12px) scale(1.02);
    box-shadow: 0 12px 35px rgba(0,0,0,0.15);
    border-color: var(--primary);
  }

  .labor-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, var(--primary) 0%, var(--secondary) 100%);
    opacity: 0;
    transition: opacity 0.3s ease;
    z-index: 2;
  }

  .labor-card:hover::before {
    opacity: 1;
  }

  .labor-card-image {
    position: relative;
    width: 100%;
    height: 200px;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
    font-size: 90px;
  }

  .labor-card-image.primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  }

  .labor-card-image.success {
    background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
  }

  .labor-card-image.danger {
    background: linear-gradient(135deg, #dc3545 0%, #e83e8c 100%);
  }

  .labor-card-image::after {
    content: '';
    position: absolute;
    top: -50%;
    left: -50%;
    width: 200%;
    height: 200%;
    background: rgba(255,255,255,0.15);
    transform: rotate(45deg);
    animation: shimmer 3s infinite;
  }

  @keyframes shimmer {
    0% { transform: rotate(45deg) translateX(-100%); }
    100% { transform: rotate(45deg) translateX(100%); }
  }

  .labor-card-image i {
    color: white;
    position: relative;
    z-index: 2;
    transition: transform 0.4s ease;
  }

  .labor-card:hover .labor-card-image i {
    transform: scale(1.15) rotate(5deg);
  }

  .labor-card-image img {
    transition: transform 0.4s ease;
  }

  .labor-card:hover .labor-card-image img {
    transform: scale(1.05);
  }

  .labor-card-badge {
    position: absolute;
    top: 15px;
    right: 15px;
    background: rgba(40, 167, 69, 0.95);
    color: white;
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 700;
    display: flex;
    align-items: center;
    gap: 4px;
    z-index: 3;
    backdrop-filter: blur(5px);
  }

  .labor-card-content {
    padding: 25px;
    flex: 1;
    display: flex;
    flex-direction: column;
  }

  .labor-card-title {
    font-size: 20px;
    font-weight: 700;
    color: var(--text-dark);
    margin: 0 0 8px 0;
    transition: color 0.3s ease;
  }

  .labor-card:hover .labor-card-title {
    color: var(--primary);
  }

  .labor-card-desc {
    font-size: 13px;
    color: var(--text-light);
    line-height: 1.6;
    margin: 0 0 20px 0;
    flex: 1;
  }

  .labor-card-stats {
    display: flex;
    gap: 10px;
    margin-bottom: 15px;
    padding-bottom: 15px;
    border-bottom: 1px solid var(--border);
  }

  .stat-item {
    flex: 1;
    text-align: center;
    padding: 8px;
    background: var(--gray-light);
    border-radius: 8px;
    transition: all 0.3s ease;
  }

  .labor-card:hover .stat-item {
    background: rgba(102, 126, 234, 0.1);
  }

  .stat-value {
    font-size: 18px;
    font-weight: 700;
    color: var(--primary);
    display: block;
  }

  .stat-label {
    font-size: 11px;
    color: var(--text-light);
    text-transform: uppercase;
    letter-spacing: 0.5px;
  }

  .labor-card-footer {
    display: flex;
    gap: 10px;
  }

  .btn-detail {
    flex: 1;
    background: white;
    border: 2px solid var(--primary);
    color: var(--primary);
    padding: 10px;
    border-radius: 8px;
    font-weight: 600;
    font-size: 13px;
    cursor: pointer;
    transition: all 0.3s ease;
  }

  .btn-detail:hover {
    background: var(--primary);
    color: white;
    transform: translateY(-2px);
  }

  .btn-ajukan {
    flex: 1;
    background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
    border: none;
    color: white;
    padding: 10px;
    border-radius: 8px;
    font-weight: 600;
    font-size: 13px;
    cursor: pointer;
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
  }

  .btn-ajukan::before {
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

  .btn-ajukan:active::before {
    width: 200px;
    height: 200px;
  }

  .btn-ajukan:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
  }

  .modal-overlay {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0,0,0,0.5);
    z-index: 1000;
    animation: fadeIn 0.3s ease;
  }

  .modal-overlay.active {
    display: flex;
    align-items: center;
    justify-content: center;
  }

  .modal-content {
    background: white;
    border-radius: 20px;
    max-width: 600px;
    width: 90%;
    max-height: 90vh;
    overflow-y: auto;
    box-shadow: 0 20px 60px rgba(0,0,0,0.3);
    animation: slideUp 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    position: relative;
  }

  @keyframes slideUp {
    from {
      opacity: 0;
      transform: translateY(40px);
    }
    to {
      opacity: 1;
      transform: translateY(0);
    }
  }

  @keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
  }

  .modal-header {
    position: relative;
    height: 220px;
    padding: 0;
    overflow: hidden;
  }

  .modal-image {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 120px;
    position: relative;
  }

  .modal-image::after {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(135deg, rgba(0,0,0,0) 0%, rgba(0,0,0,0.2) 100%);
  }

  .modal-close {
    position: absolute;
    top: 15px;
    right: 15px;
    background: rgba(255,255,255,0.95);
    border: none;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    font-size: 24px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 10;
    transition: all 0.3s ease;
    color: var(--text-dark);
  }

  .modal-close:hover {
    background: white;
    transform: rotate(90deg);
  }

  .modal-body {
    padding: 30px;
  }

  .modal-title {
    font-size: 28px;
    font-weight: 700;
    margin: 0 0 10px 0;
    color: var(--text-dark);
  }

  .modal-badge {
    display: inline-block;
    background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
    color: white;
    padding: 6px 14px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    margin-bottom: 15px;
  }

  .modal-desc {
    font-size: 15px;
    color: var(--text-light);
    line-height: 1.8;
    margin-bottom: 25px;
  }

  .modal-section {
    margin-bottom: 25px;
  }

  .modal-section-title {
    font-size: 16px;
    font-weight: 700;
    color: var(--text-dark);
    margin-bottom: 15px;
    padding-bottom: 10px;
    border-bottom: 2px solid var(--border);
    display: flex;
    align-items: center;
    gap: 8px;
  }

  .modal-section-title i {
    color: var(--primary);
    font-size: 18px;
  }

  .barang-list {
    display: grid;
    gap: 10px;
  }

  .barang-item-modal {
    padding: 12px;
    background: var(--gray-light);
    border-radius: 8px;
    display: flex;
    align-items: center;
    gap: 12px;
    transition: all 0.3s ease;
    border-left: 3px solid var(--primary);
  }

  .barang-item-modal:hover {
    background: rgba(102, 126, 234, 0.1);
    transform: translateX(5px);
  }

  .barang-icon-modal {
    width: 35px;
    height: 35px;
    border-radius: 8px;
    background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 16px;
    flex-shrink: 0;
  }

  .barang-info-modal {
    flex: 1;
  }

  .barang-nama-modal {
    font-weight: 600;
    color: var(--text-dark);
    font-size: 13px;
    margin-bottom: 2px;
  }

  .barang-desc-modal {
    font-size: 12px;
    color: var(--text-light);
  }

  .barang-stok-modal {
    background: rgba(40, 167, 69, 0.2);
    color: #155724;
    padding: 4px 10px;
    border-radius: 6px;
    font-size: 12px;
    font-weight: 600;
  }

  .modal-footer {
    padding: 20px 30px;
    border-top: 1px solid var(--border);
    display: flex;
    justify-content: center;
  }

  .modal-footer button {
    padding: 12px 30px;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    font-size: 14px;
    cursor: pointer;
    transition: all 0.3s ease;
  }

  .btn-close-modal {
    background: var(--gray-light);
    color: var(--text-dark);
  }

  .btn-close-modal:hover {
    background: var(--border);
  }

  @media (max-width: 768px) {
    .labor-grid {
      grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
      gap: 20px;
    }

    .labor-card-image {
      font-size: 60px;
    }

    .modal-content {
      width: 95%;
      max-height: 95vh;
    }

    .modal-body {
      padding: 20px;
    }
  }
</style>

<div class="labor-section-header">
  <h2>Daftar Labor</h2>
  <p>Temukan dan pelajari fasilitas labor yang tersedia untuk kebutuhan Anda</p>
</div>

<div class="labor-grid">
  <?php while($labor = mysqli_fetch_assoc($labor_all)): ?>
    <?php
      $barang_q = mysqli_query($conn, "SELECT * FROM barang_labor WHERE labor_id=".$labor['id']);
      $barang_count = mysqli_num_rows($barang_q);
      
      // Query untuk menghitung total stok awal (jumlah semua item saat awal)
      $total_stok_q = mysqli_query($conn, "SELECT SUM(stok_awal) as total_stok FROM barang_labor WHERE labor_id=".$labor['id']);
      $total_stok = mysqli_fetch_assoc($total_stok_q)['total_stok'] ?? 0;
      
      // Query untuk menghitung stok yang tersedia (hanya item dengan status 'Tersedia')
      $stok_tersedia_q = mysqli_query($conn, "SELECT SUM(stok) as total_stok FROM barang_labor WHERE labor_id=".$labor['id']." AND status='Tersedia'");
      $stok_tersedia = mysqli_fetch_assoc($stok_tersedia_q)['total_stok'] ?? 0;
      
      $colorClass = $labor['color'] ?: 'primary';
    ?>
    <div class="labor-card">
      <div class="labor-card-image <?php echo $colorClass; ?>">
        <?php if(!empty($labor['image'])): ?>
          <img src="../assets/uploads/labor/<?php echo htmlspecialchars($labor['image']); ?>" alt="<?php echo htmlspecialchars($labor['nama']); ?>" style="width: 100%; height: 100%; object-fit: cover; position: relative; z-index: 2;">
        <?php else: ?>
          <i class="fas fa-<?php echo htmlspecialchars($labor['icon']); ?>"></i>
        <?php endif; ?>
        <span class="labor-card-badge">
          <i class="fas fa-check-circle"></i> Tersedia
        </span>
      </div>
      
      <div class="labor-card-content">
        <h3 class="labor-card-title"><?php echo htmlspecialchars($labor['nama']); ?></h3>
        <p class="labor-card-desc"><?php echo htmlspecialchars($labor['deskripsi']); ?></p>
        
        <div class="labor-card-stats">
          <div class="stat-item">
            <span class="stat-value"><?php echo $total_stok; ?></span>
            <span class="stat-label">Total Stok</span>
          </div>
          <div class="stat-item">
            <span class="stat-value" style="color: #28a745;"><?php echo $stok_tersedia; ?></span>
            <span class="stat-label">Tersedia</span>
          </div>
        </div>

        <div class="labor-card-footer">
          <button class="btn-detail" onclick="openModal(<?php echo $labor['id']; ?>)">
            <i class="fas fa-info-circle"></i> Detail
          </button>
          <button class="btn-ajukan" onclick="window.location.href='peminjaman-content.php?labor_id=<?php echo $labor['id']; ?>'">
            <i class="fas fa-plus-circle"></i> Pinjam
          </button>
        </div>
      </div>
    </div>
  <?php endwhile; ?>
</div>

<!-- Modal Popup -->
<div class="modal-overlay" id="laborModal" onclick="closeModal(event)">
  <div class="modal-content" onclick="event.stopPropagation()">
    <div class="modal-header" id="modalImageContainer">
      <!-- Konten gambar akan diisi oleh JavaScript -->
    </div>
    <button class="modal-close" onclick="closeModal()">×</button>
    
    <div class="modal-body">
      <h2 class="modal-title" id="modalTitle"></h2>
      <span class="modal-badge" id="modalBadge"></span>
      <p class="modal-desc" id="modalDesc"></p>

      <div class="modal-section">
        <div class="modal-section-title">
          <i class="fas fa-cube"></i> Daftar Peralatan
        </div>
        <div class="barang-list" id="barangList"></div>
      </div>

      <div class="modal-section">
        <div class="modal-section-title">
          <i class="fas fa-info-circle"></i> Informasi
        </div>
        <div style="display: grid; gap: 10px;">
          <div style="padding: 12px; background: #f8f9fa; border-radius: 8px;">
            <div style="font-size: 12px; color: #666; margin-bottom: 4px;">Status</div>
            <div style="font-weight: 600; color: #28a745;"><i class="fas fa-check-circle"></i> Tersedia</div>
          </div>
          <div style="padding: 12px; background: #f8f9fa; border-radius: 8px;">
            <div style="font-size: 12px; color: #666; margin-bottom: 4px;">Total Peralatan</div>
            <div style="font-weight: 600; color: #333;" id="modalTotalBarang"></div>
          </div>
          <div style="padding: 12px; background: #f8f9fa; border-radius: 8px;">
            <div style="font-size: 12px; color: #666; margin-bottom: 4px;">Total Stok</div>
            <div style="font-weight: 600; color: #333;" id="modalTotalStok"></div>
          </div>
          <div style="padding: 12px; background: #f8f9fa; border-radius: 8px;">
            <div style="font-size: 12px; color: #666; margin-bottom: 4px;">Stok Tersedia</div>
            <div style="font-weight: 600; color: #28a745;" id="modalStokTersedia"></div>
          </div>
        </div>
      </div>
    </div>

    <div class="modal-footer">
      <button class="modal-footer button btn-close-modal" onclick="closeModal()">
        Tutup
      </button>
    </div>
  </div>
</div>

<script>
// Data untuk modal
const laborData = {
  <?php 
  $labor_all = mysqli_query($conn, "SELECT * FROM labor ORDER BY id ASC");
  $first = true;
  while($labor = mysqli_fetch_assoc($labor_all)): 
    $barang_q = mysqli_query($conn, "SELECT * FROM barang_labor WHERE labor_id=".$labor['id']);
    $barang_count = mysqli_num_rows($barang_q);
    
    // Hitung total stok awal untuk data modal
    $total_stok_q = mysqli_query($conn, "SELECT SUM(stok_awal) as total_stok FROM barang_labor WHERE labor_id=".$labor['id']);
    $total_stok = mysqli_fetch_assoc($total_stok_q)['total_stok'] ?? 0;
    
    // Hitung stok tersedia untuk data modal
    $stok_tersedia_q = mysqli_query($conn, "SELECT SUM(stok) as total_stok FROM barang_labor WHERE labor_id=".$labor['id']." AND status='Tersedia'");
    $stok_tersedia = mysqli_fetch_assoc($stok_tersedia_q)['total_stok'] ?? 0;
    
    $colorClass = $labor['color'] ?: 'primary';
    if(!$first) echo ",\n  ";
    $first = false;
  ?>
  <?php echo $labor['id']; ?>: {
    nama: "<?php echo addslashes($labor['nama']); ?>",
    deskripsi: "<?php echo addslashes($labor['deskripsi']); ?>",
    icon: "<?php echo htmlspecialchars($labor['icon']); ?>",
    color: "<?php echo $colorClass; ?>",
    image: "<?php echo htmlspecialchars($labor['image'] ?? ''); ?>",
    totalStok: <?php echo $total_stok; ?>,
    stokTersedia: <?php echo $stok_tersedia; ?>,
    barang: [
      <?php 
      mysqli_data_seek($barang_q, 0);
      $b_first = true;
      while($b = mysqli_fetch_assoc($barang_q)): 
        if(!$b_first) echo ",\n        ";
        $b_first = false;
      ?>
      {
        nama: "<?php echo addslashes($b['nama']); ?>",
        deskripsi: "<?php echo addslashes($b['deskripsi']); ?>",
        stok: <?php echo $b['stok']; ?>,
        stokAwal: <?php echo $b['stok_awal']; ?>,
        status: "<?php echo addslashes($b['status']); ?>"
      }
      <?php endwhile; ?>
    ]
  }
  <?php endwhile; ?>
};

function openModal(laborId) {
  const labor = laborData[laborId];
  const colorClass = labor.color;
  
  // Set header image
  const imageContainer = document.getElementById('modalImageContainer');
  if(labor.image && labor.image.trim()) {
    imageContainer.innerHTML = `<img src="../assets/uploads/labor/${labor.image}" alt="${labor.nama}" style="width: 100%; height: 100%; object-fit: cover;">`;
  } else {
    imageContainer.innerHTML = `
      <div class="modal-image ${colorClass}" style="background: linear-gradient(135deg, ${getColorGradient(colorClass)[0]} 0%, ${getColorGradient(colorClass)[1]} 100%);">
        <i class="fas fa-${labor.icon}"></i>
      </div>
    `;
  }
  
  // Set title and badge
  document.getElementById('modalTitle').textContent = labor.nama;
  document.getElementById('modalBadge').innerHTML = '<i class="fas fa-check-circle"></i> Tersedia';
  document.getElementById('modalDesc').textContent = labor.deskripsi;
  document.getElementById('modalTotalBarang').textContent = labor.barang.length + ' peralatan';
  document.getElementById('modalTotalStok').textContent = labor.totalStok + ' unit';
  document.getElementById('modalStokTersedia').textContent = labor.stokTersedia + ' unit';
  
  // Set barang list
  const barangList = document.getElementById('barangList');
  barangList.innerHTML = labor.barang.map(b => {
    // Menampilkan status stok yang sesuai
    let statusBadge = '';
    if (b.status !== 'Tersedia') {
      statusBadge = '<span class="barang-status-badge not-available"><i class="fas fa-ban"></i> ' + b.status + '</span>';
    }
    
    return `
      <div class="barang-item-modal">
        <div class="barang-icon-modal">
          <i class="fas fa-cube"></i>
        </div>
        <div class="barang-info-modal">
          <div class="barang-nama-modal">${b.nama}</div>
          <div class="barang-desc-modal">${b.deskripsi}</div>
        </div>
        <div class="barang-stok-modal">
          ${b.stok}/${b.stokAwal} unit ${b.status === 'Tersedia' ? '<i class="fas fa-check-circle"></i>' : '<i class="fas fa-times-circle"></i>'}
        </div>
      </div>
    `;
  }).join('');
  
  // Show modal
  document.getElementById('laborModal').classList.add('active');
  document.body.style.overflow = 'hidden';
}

function closeModal(event) {
  if(event && event.target.id !== 'laborModal') return;
  document.getElementById('laborModal').classList.remove('active');
  document.body.style.overflow = 'auto';
}

function getColorGradient(color) {
  const gradients = {
    'primary': ['#667eea', '#764ba2'],
    'success': ['#28a745', '#20c997'],
    'danger': ['#dc3545', '#e83e8c']
  };
  return gradients[color] || gradients['primary'];
}

// Close modal with Escape key
document.addEventListener('keydown', function(e) {
  if(e.key === 'Escape') {
    closeModal();
  }
});
</script>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
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