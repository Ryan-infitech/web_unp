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
<title>Riwayat Peminjaman - Aplikasi Labor</title>
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

$q = mysqli_query($conn,"
SELECT p.id, p.created_at, p.tanggung_jawab, p.status, p.status_pengembalian, bl.nama as barang_nama, l.nama as labor_nama, p.jumlah
FROM peminjaman p
LEFT JOIN barang_labor bl ON p.barang_labor_id=bl.id
LEFT JOIN labor l ON p.labor_id=l.id
WHERE p.user_id=".$_SESSION['user_id']."
ORDER BY p.id DESC, p.created_at DESC
");
?>

<style>
  .riwayat-container {
    background: white;
    border-radius: 12px;
    padding: 30px;
    box-shadow: 0 2px 15px rgba(0,0,0,0.08);
    animation: slideUp 0.5s ease-out;
  }

  .riwayat-header {
    display: flex;
    align-items: center;
    margin-bottom: 30px;
    padding-bottom: 20px;
    border-bottom: 2px solid #f0f0f0;
  }

  .riwayat-header i {
    font-size: 32px;
    color: #667eea;
    margin-right: 15px;
    animation: bounce 2s ease-in-out infinite;
  }

  .riwayat-header h4 {
    margin: 0;
    color: #333;
    font-weight: 700;
    font-size: 24px;
  }

  .table {
    margin-bottom: 0;
  }

  .table thead {
    background: linear-gradient(135deg, #667eea15 0%, #764ba215 100%);
  }

  .table thead th {
    color: #667eea;
    font-weight: 700;
    border: none;
    padding: 15px;
  }

  .table tbody td {
    padding: 15px;
    vertical-align: middle;
  }

  .table tbody tr {
    transition: all 0.3s ease;
    border-bottom: 1px solid #f0f0f0;
  }

  .table tbody tr:hover {
    background: linear-gradient(135deg, #667eea10 0%, #764ba210 100%);
    transform: scale(1.01);
  }

  .badge {
    padding: 8px 12px;
    border-radius: 20px;
    font-weight: 600;
    font-size: 12px;
  }

  .badge.bg-warning {
    background: linear-gradient(135deg, #ffc107 0%, #fd7e14 100%) !important;
    color: white;
  }

  .badge.bg-success {
    background: linear-gradient(135deg, #28a745 0%, #20c997 100%) !important;
    color: white;
  }

  .badge.bg-danger {
    background: linear-gradient(135deg, #dc3545 0%, #e83e8c 100%) !important;
    color: white;
  }

  .badge.bg-info {
    background: linear-gradient(135deg, #17a2b8 0%, #00bcd4 100%) !important;
    color: white;
  }

  .empty-message {
    text-align: center;
    padding: 60px 20px;
  }

  .empty-message i {
    font-size: 80px;
    color: #ddd;
    margin-bottom: 20px;
    display: block;
    animation: float 3s ease-in-out infinite;
  }

  .empty-message p {
    color: #999;
    font-size: 18px;
    margin-bottom: 30px;
  }

  .btn-back-riwayat {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border: none;
    padding: 10px 20px;
    border-radius: 8px;
    font-weight: 600;
    transition: all 0.3s ease;
  }

  .btn-back-riwayat:hover {
    color: white;
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
  }
</style>

<div class="riwayat-container">
  <div class="riwayat-header">
    <i class="fas fa-history"></i>
    <h4>Riwayat Peminjaman</h4>
  </div>
  
  <?php if(mysqli_num_rows($q) > 0): ?>
    <div class="table-responsive">
      <table class="table table-hover">
        <thead>
          <tr>
            <th> No</th>
            <th><i class="fas fa-calendar"></i> Tanggal</th>
            <th><i class="fas fa-building"></i> Labor</th>
            <th><i class="fas fa-box"></i> Peralatan</th>
            <th><i class="fas fa-box-check"></i> Jumlah</th>
            <th><i class="fas fa-comment"></i> Keperluan</th>
            <th><i class="fas fa-check-circle"></i> Status</th>
          </tr>
        </thead>
        <tbody>
          <?php $no=1; while($d=mysqli_fetch_assoc($q)){ ?>
          <tr>
            <td><?php echo $no++; ?></td>
            <td><?php echo date('d M Y • H:i', strtotime($d['created_at'])); ?></td>
            <td><?php echo $d['labor_nama'] ? '<strong>'.$d['labor_nama'].'</strong>' : '<em class="text-muted">-</em>'; ?></td>
            <td><?php echo $d['barang_nama'] ? $d['barang_nama'] : '<em class="text-muted">-</em>'; ?></td>
            <td><strong><?php echo intval($d['jumlah']); ?> pcs</strong></td>
            <td><?php echo htmlspecialchars($d['tanggung_jawab']); ?></td>
            <td>
              <?php 
                // Prioritas tampilan: Dikembalikan > Status lainnya
                if(!empty($d['status_pengembalian']) && $d['status_pengembalian'] == 1) {
                  echo '<span class="badge bg-info"><i class="fas fa-reply"></i> Dikembalikan</span>';
                } else if($d['status'] === 'Menunggu') {
                  echo '<span class="badge bg-warning"><i class="fas fa-hourglass-half"></i> Menunggu</span>';
                } else if($d['status'] === 'Disetujui') {
                  echo '<span class="badge bg-success"><i class="fas fa-check"></i> Disetujui</span>';
                } else if($d['status'] === 'Ditolak') {
                  echo '<span class="badge bg-danger"><i class="fas fa-times"></i> Ditolak</span>';
                }
              ?>
            </td>
          </tr>
          <?php } ?>
        </tbody>
      </table>
    </div>
  <?php else: ?>
    <div class="empty-message">
      <i class="fas fa-inbox"></i>
      <p>Tidak ada riwayat peminjaman</p>
    </div>
  <?php endif; ?>
</div>
