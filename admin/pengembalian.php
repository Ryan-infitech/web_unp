<?php
session_start();
include '../config/database.php';

if(! isset($_SESSION['admin_id'])){
  header("Location: login.php");
  exit;
}

/* ================== AUTO COLUMN CHECK ================== */
$check = mysqli_query($conn,"SHOW COLUMNS FROM peminjaman LIKE 'tanggal_kembali'");
if(mysqli_num_rows($check)==0){
  mysqli_query($conn,"ALTER TABLE peminjaman ADD tanggal_kembali DATETIME NULL");
}

$message=''; $message_type='';

// Handle bulk confirm return
if(isset($_POST['bulk_confirm_return']) && isset($_POST['selected_return'])){
  $selected_ids = array_map('intval', $_POST['selected_return']);
  $count = 0;
  $failed = 0;
  
  foreach($selected_ids as $id){
    $pem_q = mysqli_query($conn, "SELECT barang_labor_id, jumlah FROM peminjaman WHERE id=$id AND status='Disetujui'");
    
    if(mysqli_num_rows($pem_q) > 0){
      $pem = mysqli_fetch_assoc($pem_q);
      $barang_id = intval($pem['barang_labor_id']);
      $jumlah = intval($pem['jumlah']);
      
      mysqli_begin_transaction($conn);
      try {
        $update_pem = mysqli_query($conn, "UPDATE peminjaman SET status='Dikembalikan', status_pengembalian=1, tanggal_kembali=NOW() WHERE id=$id");
        $update_barang = mysqli_query($conn, "UPDATE barang_labor SET stok=stok+$jumlah, status='Tersedia' WHERE id=$barang_id");
        
        if($update_pem && $update_barang){
          mysqli_commit($conn);
          $count++;
        } else {
          mysqli_rollback($conn);
          $failed++;
        }
      } catch(Exception $e) {
        mysqli_rollback($conn);
        $failed++;
      }
    } else {
      $failed++;
    }
  }
  
  if($count > 0){
    $message = "Total $count pengembalian berhasil dikonfirmasi!";
    $message_type = "success";
  }
  if($failed > 0){
    $message .= ($count > 0 ? " " : "") . "Total $failed pengembalian gagal dikonfirmasi.";
    $message_type = ($count > 0) ? "warning" : "danger";
  }
}

// Handle bulk reject return
if(isset($_POST['bulk_reject_return']) && isset($_POST['selected_return'])){
  $selected_ids = array_map('intval', $_POST['selected_return']);
  $alasan = mysqli_real_escape_string($conn, $_POST['alasan_penolakan'] ?? 'Ditolak oleh admin');
  $count = 0;
  
  foreach($selected_ids as $id){
    $update = mysqli_query($conn, "UPDATE peminjaman SET keterangan='Pengembalian ditolak: $alasan' WHERE id=$id AND status='Disetujui'");
    if($update && mysqli_affected_rows($conn) > 0){
      $count++;
    }
  }
  
  if($count > 0){
    $message = "Total $count pengembalian berhasil ditolak!";
    $message_type = "warning";
  }
}

// Handle return confirmation
if(isset($_POST['confirm_return'])){
  $id = intval($_POST['peminjaman_id']);
  
  // Get peminjaman details
  $pem_q = mysqli_query($conn, "SELECT barang_labor_id, jumlah FROM peminjaman WHERE id=$id");
  if(! $pem_q) {
    error_log("ERROR: Failed to query peminjaman:  " . mysqli_error($conn));
    $message = "Error:  Gagal mengambil data peminjaman";
    $message_type = "danger";
  } else if(mysqli_num_rows($pem_q) === 0) {
    error_log("ERROR: Peminjaman ID $id not found");
    $message = "Error: Peminjaman tidak ditemukan";
    $message_type = "danger";
  } else {
    $pem = mysqli_fetch_assoc($pem_q);
    $barang_id = intval($pem['barang_labor_id']);
    $jumlah = intval($pem['jumlah']);
    
    // Start transaction
    mysqli_begin_transaction($conn);
    
    try {
      // Update peminjaman status to Dikembalikan
      $update_pem = mysqli_query($conn, "UPDATE peminjaman SET status='Dikembalikan', status_pengembalian=1, tanggal_kembali=NOW() WHERE id=$id");
      if(! $update_pem) throw new Exception("Failed to update peminjaman:  " . mysqli_error($conn));
      
      // Restore stok barang_labor (add back the returned quantity)
      $update_barang = mysqli_query($conn, "UPDATE barang_labor SET stok=stok+$jumlah, status='Tersedia' WHERE id=$barang_id");
      if(!$update_barang) throw new Exception("Failed to update barang_labor: " . mysqli_error($conn));
      
      mysqli_commit($conn);
      $message = "Pengembalian barang berhasil dikonfirmasi!  Stok telah ditambahkan kembali.";
      $message_type = "success";
    } catch(Exception $e) {
      mysqli_rollback($conn);
      error_log("ERROR: " . $e->getMessage());
      $message = "Error: " . $e->getMessage();
      $message_type = "danger";
    }
  }
}

// Handle rejection of return
if(isset($_POST['reject_return'])){
  $id = intval($_POST['peminjaman_id']);
  $alasan = mysqli_real_escape_string($conn, $_POST['alasan_penolakan']);
  
  $update = mysqli_query($conn, "UPDATE peminjaman SET keterangan='Pengembalian ditolak:  $alasan' WHERE id=$id");
  if($update){
    $message = "Pengembalian barang ditolak";
    $message_type = "warning";
  } else {
    $message = "Error:  Gagal menolak pengembalian";
    $message_type = "danger";
  }
}

// Get data - peminjaman yang sudah disetujui tapi belum dikembalikan
$q = mysqli_query($conn, "
  SELECT p.*, u.nama, u.nim, l.nama AS labor_nama, bl.nama AS barang_nama, bl.stok
  FROM peminjaman p
  JOIN users u ON p.user_id=u.id
  LEFT JOIN labor l ON p.labor_id=l.id
  LEFT JOIN barang_labor bl ON p.barang_labor_id=bl.id
  WHERE p.status='Disetujui'
  ORDER BY p.created_at DESC
");

$approved = mysqli_num_rows(mysqli_query($conn,"SELECT id FROM peminjaman WHERE status='Disetujui'"));
$returned = mysqli_num_rows(mysqli_query($conn,"SELECT id FROM peminjaman WHERE status='Dikembalikan'"));
$rejected_returns = mysqli_num_rows(mysqli_query($conn,"SELECT id FROM peminjaman WHERE status='Disetujui' AND keterangan LIKE '%ditolak%'"));

$id = $_SESSION['admin_id'];
$admin_q = mysqli_query($conn, "SELECT * FROM admin WHERE id=$id");
$a = mysqli_fetch_assoc($admin_q);
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Konfirmasi Pengembalian Barang</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<style>
  * { margin: 0; padding: 0; box-sizing: border-box; }
  
  :root {
    --primary: #f59e0b;
    --primary-light: #fbbf24;
    --primary-dark: #d97706;
    --secondary: #fb923c;
    --bg-primary: #ffffff;
    --bg-secondary: #f8fafc;
    --text-primary: #0f172a;
    --text-secondary: #64748b;
    --border-color: #e0e0e0;
    --shadow: 0 4px 15px rgba(0,0,0,0.1);
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

  @keyframes pulse {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.05); }
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

  @keyframes pulse {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.05); }
  }
  
  body {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
    background-color: var(--bg-secondary);
    color: var(--text-primary);
    animation: fadeIn 0.3s ease;
  }
  
  .main-content {
    margin-left: 250px;
    padding: 40px 30px;
    min-height: 100vh;
    animation: fadeIn 0.3s ease;
  }
  
  .page-header {
    margin-bottom: 40px;
    animation: slideInDown 0.4s ease;
  }
  
  .page-title {
    font-size: 28px;
    font-weight: 700;
    margin-bottom: 8px;
    color: var(--text-primary);
  }
  
  .page-subtitle {
    font-size: 14px;
    color: var(--text-secondary);
  }
  
  .welcome-card {
    background: var(--bg-primary);
    border-radius: 10px;
    padding: 24px;
    border: 2px solid var(--border-color);
    transition: all 0.2s;
    margin-bottom: 30px;
    animation: slideInDown 0.5s ease 0.1s both;
  }
  
  .welcome-card h2 {
    font-size: 28px;
    font-weight: 700;
    margin-bottom: 8px;
    color: var(--text-primary);
    display: flex;
    align-items: center;
    gap: 10px;
  }

  .welcome-card h2 i {
    color: var(--primary);
    margin-bottom: 0;
  }
  
  .stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 40px;
  }
  
  .stat-card {
    background: var(--bg-primary);
    border-radius: 10px;
    padding: 24px;
    border: 2px solid var(--border-color);
    transition: all 0.2s;
    animation: slideInUp 0.5s ease backwards;
  }
  
  .stat-card:nth-child(1) { animation-delay: 0.2s; }
  .stat-card:nth-child(2) { animation-delay: 0.3s; }
  .stat-card:nth-child(3) { animation-delay: 0.4s; }
  
  .stat-card:hover {
    border-color: #2563eb;
    box-shadow: 0 4px 12px rgba(37, 99, 235, 0.15);
  }
  
  .stat-icon {
    width: 48px;
    height: 48px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 12px;
    font-size: 20px;
  }
  
  .stat-card:nth-child(1) .stat-icon {
    background-color: #fef3c7;
    color: #92400e;
  }
  
  .stat-card:nth-child(2) .stat-icon {
    background-color: #dcfce7;
    color: #16a34a;
  }
  
  .stat-card:nth-child(3) .stat-icon {
    background-color: #fee2e2;
    color: #dc2626;
  }
  
  .stat-title {
    font-size: 13px;
    color: var(--text-secondary);
    margin-bottom: 6px;
    font-weight: 500;
  }
  
  .stat-value {
    font-size: 28px;
    font-weight: 700;
    color: var(--text-primary);
  }
  
  .table-card {
    background: var(--bg-primary);
    border-radius: 10px;
    padding: 30px;
    border: 2px solid var(--border-color);
    margin-bottom: 40px;
    animation: slideInUp 0.5s ease 0.5s both;
  }
  
  .table-card h4 {
    font-size: 20px;
    font-weight: 600;
    color: var(--text-primary);
    margin-bottom: 25px;
  }
  
  .table-responsive {
    border-radius: 8px;
    overflow: hidden;
  }
  
  .table {
    margin-bottom: 0;
    border-collapse: collapse;
  }
  
  .table thead {
    background-color: var(--bg-secondary);
    border-bottom: 2px solid var(--border-color);
  }
  
  .table thead th {
    font-weight: 600;
    color: var(--text-primary);
    padding: 14px;
    font-size: 13px;
    border: none;
    text-align: left;
  }
  
  .table tbody td {
    padding: 14px;
    border-bottom: 1px solid var(--border-color);
    font-size: 14px;
  }
  
  .table tbody tr:hover {
    background-color: var(--bg-secondary);
  }
  
  .btn-confirm {
    background-color: #16a34a;
    border: none;
    font-weight: 600;
    padding: 8px 16px;
    border-radius: 6px;
    color: white;
    font-size: 12px;
    cursor: pointer;
    transition: all 0.2s;
  }
  
  .btn-confirm:hover {
    background-color: #15803d;
    transform: translateY(-1px);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
  }
  
  .btn-reject {
    background-color: #dc2626;
    border: none;
    font-weight: 600;
    padding: 8px 16px;
    border-radius: 6px;
    color: white;
    font-size: 12px;
    cursor: pointer;
    transition: all 0.2s;
  }
  
  .btn-reject:hover {
    background-color: #b91c1c;
    transform: translateY(-1px);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
  }

  .reject-form {
    display: none;
    margin-top: 15px;
    padding: 15px;
    background: #fef3c7;
    border-radius: 8px;
    border-left: 4px solid #f59e0b;
  }

  .reject-form.active {
    display: block;
  }

  .reject-form textarea {
    margin-bottom: 10px;
    border: 1px solid #fcd34d;
  }

  .reject-form-buttons {
    display: flex;
    gap: 10px;
  }

  .reject-form-buttons button {
    flex: 1;
  }

  .action-buttons {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
  }

  .action-buttons form {
    margin: 0;
  }
  
  .alert {
    border-radius: 8px;
    border: 2px solid;
    margin-bottom: 30px;
    font-size: 14px;
    font-weight: 500;
    padding: 12px 16px;
    animation: slideInDown 0.4s ease;
  }
  
  .alert-success {
    background-color: #dcfce7;
    color: #166534;
  }
  
  .alert-danger {
    background-color: #fee2e2;
    color: #991b1b;
  }
  
  .alert-warning {
    background-color: #fef3c7;
    color: #92400e;
  }
  
  .badge-status {
    padding: 6px 12px;
    border-radius: 6px;
    font-size: 12px;
    font-weight: 600;
  }
  
  .badge-approved {
    background-color: #dcfce7;
    color: #166534;
  }

  /* Checkbox Styles */
  input[type="checkbox"] {
    width: 18px;
    height: 18px;
    cursor: pointer;
    accent-color: #dc2626;
    transition: all 0.2s ease;
  }

  input[type="checkbox"]:hover {
    transform: scale(1.1);
  }

  /* Selected Count */
  #selectedCountReturn {
    padding: 8px 16px;
    background: rgba(220, 38, 38, 0.1);
    border-radius: 6px;
    border: 1px solid #dc2626;
    font-size: 14px;
  }

  .btn-sm {
    display: inline-flex;
    align-items: center;
    gap: 6px;
  }
  
  @media (max-width: 1200px) {
    .stats-grid {
      grid-template-columns: repeat(2, 1fr);
    }
  }

  @media (max-width: 768px) {
    .main-content { padding: 20px; }
    .welcome-card h2 { font-size: 24px; }
    .stats-grid { 
      grid-template-columns: 1fr;
    }
    .action-buttons {
      flex-direction: column;
    }
  }
</style>
</head>

<body>

<?php include '../assets/admin_sidebar.php'; ?>

<div class="main-content">
<?php if($message): ?>
<div class="alert alert-<?= $message_type ?> alert-dismissible fade show">
  <?= $message ?>
  <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<div class="welcome-card">
  <h2><i class="fas fa-box"></i> Konfirmasi Pengembalian Barang</h2>
  <p>Verifikasi dan konfirmasi pengembalian barang yang dipinjam</p>
</div>

<div class="stats-grid">
  <div class="stat-card">
    <div class="stat-icon"><i class="fas fa-hourglass-half"></i></div>
    <div class="stat-title">Menunggu Konfirmasi</div>
    <div class="stat-value"><?= $approved ?></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
    <div class="stat-title">Sudah Dikembalikan</div>
    <div class="stat-value"><?= $returned ?></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon"><i class="fas fa-times-circle"></i></div>
    <div class="stat-title">Penolakan</div>
    <div class="stat-value"><?= $rejected_returns ?></div>
  </div>
</div>

<div class="table-card">
  <h4>Daftar Peminjaman yang Menunggu Pengembalian</h4>

  <div class="table-responsive">
    <form id="pengembalianForm" method="post">
      <div style="margin-bottom: 16px; display: flex; gap: 10px; align-items: center;">
        <span id="selectedCountReturn" style="color: var(--text-secondary); font-weight: 500; display: none;">
          <strong id="countSelectedReturn">0</strong> item dipilih
        </span>
        <button type="button" id="bulkConfirmBtnReturn" class="btn btn-confirm btn-sm" style="display: none;" onclick="bulkConfirmSelected()">
          <i class="fas fa-check-circle"></i> Konfirmasi Dipilih
        </button>
        <button type="button" id="bulkRejectBtnReturn" class="btn btn-reject btn-sm" style="display: none;" onclick="showBulkRejectForm()">
          <i class="fas fa-times-circle"></i> Tolak Dipilih
        </button>
      </div>

      <table class="table table-bordered table-hover">
        <thead>
          <tr>
            <th style="width: 40px; padding-left: 16px;">
              <input type="checkbox" id="selectAllReturn" onclick="toggleSelectAllReturn(this)">
            </th>
            <th>Nama</th>
            <th>NIM</th>
            <th>Tanggal Peminjaman</th>
            <th>Labor</th>
            <th>Peralatan</th>
            <th>Jumlah</th>
            <th>Status</th>
            <th>Aksi</th>
          </tr>
        </thead>
        <tbody>
        <?php while($d=mysqli_fetch_assoc($q)): ?>
          <tr>
            <td style="padding-left: 16px;">
              <input type="checkbox" name="selected_return[]" value="<?= $d['id'] ?>" class="return-checkbox" onchange="updateDeleteButtonReturn()">
            </td>
            <td><?= htmlspecialchars($d['nama']) ?></td>
            <td><?= htmlspecialchars($d['nim']) ?></td>
            <td><?= date('d-m-Y H:i',strtotime($d['created_at'])) ?></td>
            <td><?= htmlspecialchars($d['labor_nama'] ??'-') ?></td>
            <td><?= htmlspecialchars($d['barang_nama'] ??'-') ?></td>
            <td><?= intval($d['jumlah']) ?> pcs</td>
            <td><span class="badge-status badge-approved"><?= htmlspecialchars($d['status']) ?></span></td>
            <td>
              <div class="action-buttons">
                <form method="post" style="display: inline;" onsubmit="return confirm('Konfirmasi pengembalian barang ini?');">
                  <input type="hidden" name="peminjaman_id" value="<?= $d['id'] ?>">
                  <button type="submit" name="confirm_return" class="btn btn-confirm btn-sm">
                    <i class="fas fa-check"></i> Konfirmasi
                  </button>
                </form>
                <button class="btn btn-reject btn-sm" onclick="toggleRejectForm(<?= $d['id'] ?>)">
                  <i class="fas fa-times"></i> Tolak
                </button>
              </div>
              
              <!-- REJECT FORM INLINE -->
              <div class="reject-form" id="reject-form-<?= $d['id'] ?>">
                <form method="post">
                  <input type="hidden" name="peminjaman_id" value="<?= $d['id'] ?>">
                  <textarea name="alasan_penolakan" class="form-control form-control-sm" rows="3" placeholder="Alasan penolakan (Contoh: Barang rusak, ada yang hilang, dll... )" required></textarea>
                  <div class="reject-form-buttons">
                    <button type="submit" name="reject_return" class="btn btn-warning btn-sm">
                      <i class="fas fa-check"></i> Kirim Penolakan
                    </button>
                    <button type="button" class="btn btn-secondary btn-sm" onclick="toggleRejectForm(<?= $d['id'] ?>)">
                      <i class="fas fa-times"></i> Batal
                    </button>
                  </div>
                </form>
              </div>
            </td>
          </tr>
      <?php endwhile ?>
        </tbody>
      </table>
      </form>
    
    <?php if(mysqli_num_rows($q) === 0): ?>
    <div style="padding: 40px; text-align: center; color: #999;">
      <i class="fas fa-check-circle" style="font-size: 48px; margin-bottom: 20px; color: #28a745;"></i>
      <p style="font-size: 18px; font-weight: 700;">Semua barang sudah dikonfirmasi pengembalian!</p>
    </div>
    <?php endif; ?>
  </div>
</div>

</div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
function toggleRejectForm(id) {
  const form = document.getElementById('reject-form-' + id);
  form.classList.toggle('active');
  if(form.classList.contains('active')) {
    form.querySelector('textarea').focus();
  }
}

// Fungsi untuk checkbox pada pengembalian
function toggleSelectAllReturn(checkbox) {
  const allCheckboxes = document.querySelectorAll('.return-checkbox');
  allCheckboxes.forEach(cb => {
    cb.checked = checkbox.checked;
  });
  updateDeleteButtonReturn();
}

function updateDeleteButtonReturn() {
  const selectedCheckboxes = document.querySelectorAll('.return-checkbox:checked');
  const confirmBtn = document.getElementById('bulkConfirmBtnReturn');
  const rejectBtn = document.getElementById('bulkRejectBtnReturn');
  const selectedCount = document.getElementById('selectedCountReturn');
  const countSelected = document.getElementById('countSelectedReturn');
  const selectAllCheckbox = document.getElementById('selectAllReturn');

  if(selectedCheckboxes.length > 0) {
    confirmBtn.style.display = 'inline-flex';
    rejectBtn.style.display = 'inline-flex';
    selectedCount.style.display = 'inline-block';
    countSelected.textContent = selectedCheckboxes.length;
  } else {
    confirmBtn.style.display = 'none';
    rejectBtn.style.display = 'none';
    selectedCount.style.display = 'none';
    selectAllCheckbox.checked = false;
  }
}

function bulkConfirmSelected() {
  const selectedCheckboxes = document.querySelectorAll('.return-checkbox:checked');
  
  if(selectedCheckboxes.length === 0) {
    alert('Pilih minimal 1 pengembalian untuk dikonfirmasi');
    return;
  }

  const count = selectedCheckboxes.length;
  if(confirm(`Apakah Anda yakin ingin mengkonfirmasi ${count} pengembalian?`)) {
    const form = document.getElementById('pengembalianForm');
    const input = document.createElement('input');
    input.type = 'hidden';
    input.name = 'bulk_confirm_return';
    input.value = '1';
    form.appendChild(input);
    form.submit();
  }
}

function showBulkRejectForm() {
  const selectedCheckboxes = document.querySelectorAll('.return-checkbox:checked');
  
  if(selectedCheckboxes.length === 0) {
    alert('Pilih minimal 1 pengembalian untuk ditolak');
    return;
  }

  const count = selectedCheckboxes.length;
  const form = document.getElementById('pengembalianForm');
  
  // Show modal or prompt untuk input alasan
  const alasan = prompt(`Masukkan alasan penolakan untuk ${count} pengembalian:`, "");
  
  if(alasan !== null && alasan.trim() !== "") {
    const input1 = document.createElement('input');
    input1.type = 'hidden';
    input1.name = 'bulk_reject_return';
    input1.value = '1';
    
    const input2 = document.createElement('input');
    input2.type = 'hidden';
    input2.name = 'alasan_penolakan';
    input2.value = alasan;
    
    form.appendChild(input1);
    form.appendChild(input2);
    form.submit();
  } else if(alasan !== null) {
    alert("Alasan penolakan harus diisi!");
  }
}

// Listen untuk perubahan checkbox
document.querySelectorAll('.return-checkbox').forEach(checkbox => {
  checkbox.addEventListener('change', updateDeleteButtonReturn);
});

// Load sidebar state from localStorage
if(localStorage.getItem('sidebarCollapsed')==='true') {
  document.body.classList.add('sidebar-collapsed');
}
document.addEventListener('click', function(e) {
  if(window.innerWidth<=768 && !document.querySelector('.sidebar').contains(e.target) && !document.querySelector('.sidebar-toggle').contains(e.target) && !document.body.classList.contains('sidebar-collapsed')) {
    document.body.classList.add('sidebar-collapsed');
  }
});
</script>
</body>
</html>