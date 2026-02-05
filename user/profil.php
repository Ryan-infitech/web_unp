<?php
session_start();
include '../config/database.php';
include '../assets/header.php';

$id=$_SESSION['user_id'];
$u=mysqli_fetch_assoc(mysqli_query($conn,"SELECT * FROM users WHERE id=$id"));
?>

<div class="container py-4">
  <a href="../index.php" class="btn btn-secondary mb-3"><i class="fas fa-arrow-left"></i> Kembali</a>
  
  <div class="card shadow">
    <div class="card-body text-center">
      <img src="../assets/img/<?=$u['foto']?>" width="150" class="rounded-circle mb-3" style="border: 3px solid #667eea;"><br>
      <h4 class="mb-3"><?=$u['nama']?></h4>
      <div class="mb-2"><strong>NIM:</strong> <?=$u['nim']?></div>
      <div class="mb-3"><strong>Email:</strong> <?=$u['email']?></div>
      <a href="edit.php" class="btn btn-primary"><i class="fas fa-edit"></i> Edit Profil</a>
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
