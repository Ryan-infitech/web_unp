<?php
session_start();
include 'database.php';

if(isset($_GET['labor_id'])){
  $labor_id = intval($_GET['labor_id']);
  
  // Query untuk mendapatkan SEMUA barang (termasuk yang tidak tersedia) untuk ditampilkan
  // Items tersedia akan ditampilkan normal, items tidak tersedia tampil dengan status disabled
  $q = mysqli_query($conn, "SELECT id, nama, stok, deskripsi, status FROM barang_labor 
                            WHERE labor_id=$labor_id 
                            ORDER BY CASE WHEN status='Tersedia' AND stok > 0 THEN 0 ELSE 1 END, nama ASC");
  
  $barang = [];
  while($row = mysqli_fetch_assoc($q)){
    $barang[] = $row;
  }
  
  header('Content-Type: application/json');
  header('Cache-Control: no-cache, no-store, must-revalidate');
  header('Pragma: no-cache');
  header('Expires: 0');
  echo json_encode($barang);
} else {
  header('Content-Type: application/json');
  header('Cache-Control: no-cache, no-store, must-revalidate');
  header('Pragma: no-cache');
  header('Expires: 0');
  echo json_encode([]);
}
?>
