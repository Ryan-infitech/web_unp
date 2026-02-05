<?php
session_start();
include '../config/database.php';
include '../assets/sidebar.php';

$id=$_SESSION['user_id'];
$message = '';
$message_type = '';

if(isset($_POST['simpan'])){
  $nama = mysqli_real_escape_string($conn, $_POST['nama']);
  
  if(empty($nama)){
    $message = 'Nama harus diisi!';
    $message_type = 'warning';
  } else {
    $upd = mysqli_query($conn, "UPDATE users SET nama='$nama' WHERE id=$id");
    if($upd){
      $message = 'Profil berhasil diperbarui!';
      $message_type = 'success';
    }
  }
}

$u=mysqli_fetch_assoc(mysqli_query($conn,"SELECT * FROM users WHERE id=$id"));
?>

<div class="container py-4">
  <a href="profil.php" class="btn btn-secondary mb-3"><i class="fas fa-arrow-left"></i> Kembali</a>
  
  <?php if($message): ?>
    <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
      <i class="fas fa-info-circle"></i> <?php echo $message; ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php endif; ?>
  
  <div class="card shadow" style="max-width: 600px;">
    <div class="card-body">
      <h4 class="mb-4"><i class="fas fa-edit"></i> Edit Profil</h4>
      
      <form method="post">
        <div class="mb-3">
          <label class="form-label"><strong>Nama</strong></label>
          <input class="form-control" name="nama" value="<?=$u['nama']?>" required>
        </div>
        <button class="btn btn-primary" name="simpan"><i class="fas fa-save"></i> Simpan Perubahan</button>
      </form>
    </div>
  </div>
</div>

<?php include '../assets/footer.php'; ?>
