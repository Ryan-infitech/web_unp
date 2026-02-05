<?php
session_start();
if(!isset($_SESSION['user_id'])){
  header('Location: ../auth/login.php');
  exit;
}
include '../config/database.php';
include '../assets/header.php';

// Mark as read
if(isset($_POST['mark_read']) && isset($_POST['notif_id'])){
  $nid = mysqli_real_escape_string($conn, $_POST['notif_id']);
  mysqli_query($conn, "UPDATE notifications SET is_read=1 WHERE id='$nid' AND user_id='".intval($_SESSION['user_id'])."'");
}

// Delete notification
if(isset($_POST['delete_notif']) && isset($_POST['notif_id'])){
  $nid = mysqli_real_escape_string($conn, $_POST['notif_id']);
  mysqli_query($conn, "DELETE FROM notifications WHERE id='$nid' AND user_id='".intval($_SESSION['user_id'])."'");
}

$notifs_q = mysqli_query($conn, "SELECT * FROM notifications WHERE user_id='".intval($_SESSION['user_id'])."' ORDER BY created_at DESC");
?>

<div class="container py-4">
  <a href="../index.php" class="btn btn-secondary mb-3"><i class="fas fa-arrow-left"></i> Kembali</a>
  
  <h3><i class="fas fa-bell"></i> Notifikasi Saya</h3>

  <div class="card mt-3 shadow">
    <div class="card-body">
      <?php if(mysqli_num_rows($notifs_q) > 0): ?>
        <ul class="list-group">
          <?php while($n = mysqli_fetch_assoc($notifs_q)): ?>
            <li class="list-group-item d-flex justify-content-between align-items-start <?php echo $n['is_read'] ? '' : 'bg-light'; ?>">
              <div>
                <div style="font-weight:600"><?php echo htmlspecialchars($n['title']); ?></div>
                <div style="font-size:13px; color:#666"><?php echo nl2br(htmlspecialchars($n['message'])); ?></div>
                <div class="text-muted" style="font-size:12px; margin-top:6px"><?php echo date('d-m-Y H:i', strtotime($n['created_at'])); ?></div>
              </div>
              <div>
                <?php if(!$n['is_read']): ?>
                <form method="post" style="display:inline">
                  <input type="hidden" name="notif_id" value="<?php echo $n['id']; ?>">
                  <button type="submit" name="mark_read" class="btn btn-sm btn-primary">Tandai dibaca</button>
                </form>
                <?php endif; ?>
                <form method="post" style="display:inline; margin-left:6px">
                  <input type="hidden" name="notif_id" value="<?php echo $n['id']; ?>">
                  <button type="submit" name="delete_notif" class="btn btn-sm btn-outline-danger">Hapus</button>
                </form>
              </div>
            </li>
          <?php endwhile; ?>
        </ul>
      <?php else: ?>
        <div class="text-muted">Tidak ada notifikasi.</div>
      <?php endif; ?>
    </div>
  </div>

</div>

<?php include '../assets/footer.php'; ?>
