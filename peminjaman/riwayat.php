<?php
session_start();
include '../config/database.php';
include '../assets/header.php';

$q=mysqli_query($conn,"
SELECT p.id, p.tanggal, p.tanggung_jawab, p.status, bl.nama as barang_nama, l.nama as labor_nama 
FROM peminjaman p
LEFT JOIN barang_labor bl ON p.barang_labor_id=bl.id
LEFT JOIN labor l ON p.labor_id=l.id
WHERE p.user_id=$_SESSION[user_id]
ORDER BY p.tanggal DESC
");
?>

<div class="container py-4">
  <a href="../index.php" class="btn btn-secondary mb-3"><i class="fas fa-arrow-left"></i> Kembali</a>
  
  <h4 class="mb-3"><i class="fas fa-history"></i> Riwayat Peminjaman</h4>
  
  <div class="card shadow">
    <div class="card-body">
      <?php if(mysqli_num_rows($q) > 0): ?>
        <div class="table-responsive">
          <table class="table table-hover mb-0">
            <thead class="table-light">
              <tr>
                <th>No</th>
                <th>Tanggal</th>
                <th>Labor</th>
                <th>Peralatan</th>
                <th>Keperluan</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody>
              <?php $no=1; while($d=mysqli_fetch_assoc($q)){ ?>
              <tr>
                <td><?=$no++?></td>
                <td><?=date('d-m-Y H:i', strtotime($d['tanggal']))?></td>
                <td><?=$d['labor_nama'] ? '<strong>'.$d['labor_nama'].'</strong>' : '-'?></td>
                <td><?=$d['barang_nama'] ? $d['barang_nama'] : '-'?></td>
                <td><?=htmlspecialchars($d['tanggung_jawab'])?></td>
                <td>
                  <?php 
                    if($d['status'] === 'Menunggu') echo '<span class="badge bg-warning text-dark">Menunggu</span>';
                    elseif($d['status'] === 'Disetujui') echo '<span class="badge bg-success">Disetujui</span>';
                    elseif($d['status'] === 'Ditolak') echo '<span class="badge bg-danger">Ditolak</span>';
                  ?>
                </td>
              </tr>
              <?php } ?>
            </tbody>
          </table>
        </div>
      <?php else: ?>
        <div class="text-muted text-center py-5">
          <i class="fas fa-inbox" style="font-size: 48px; color: #ddd;"></i>
          <p>Tidak ada riwayat peminjaman</p>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php include '../assets/footer.php'; ?>

<script>
// Load dark mode from localStorage
if(localStorage.getItem('darkMode') === 'true') {
  document.body.classList.add('dark-mode');
}
</script>

