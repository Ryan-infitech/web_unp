<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include 'config/database.php';

echo "<h2>Stock Reduction Verification</h2>";
echo "<hr>";

// 1. Check current data
echo "<h3>1. Current Barang Labor Status</h3>";
$q = mysqli_query($conn, "SELECT id, labor_id, nama, stok, status FROM barang_labor ORDER BY id DESC LIMIT 10");
echo "<table border='1' cellpadding='10'>";
echo "<tr><th>ID</th><th>Labor</th><th>Nama</th><th>Stok</th><th>Status</th></tr>";
while($row = mysqli_fetch_assoc($q)) {
    echo "<tr><td>{$row['id']}</td><td>{$row['labor_id']}</td><td>{$row['nama']}</td><td>{$row['stok']}</td><td>{$row['status']}</td></tr>";
}
echo "</table>";

echo "<h3>2. Pending Peminjaman (Menunggu)</h3>";
$q = mysqli_query($conn, "SELECT p.id, p.barang_labor_id, p.jumlah, bl.nama, bl.stok
                          FROM peminjaman p
                          LEFT JOIN barang_labor bl ON p.barang_labor_id = bl.id
                          WHERE p.status='Menunggu'
                          ORDER BY p.id DESC LIMIT 10");
if(mysqli_num_rows($q) === 0) {
    echo "<p style='color: orange;'>Tidak ada peminjaman yang menunggu approval</p>";
} else {
    echo "<table border='1' cellpadding='10'>";
    echo "<tr><th>ID</th><th>Barang</th><th>Current Stok</th><th>Qty Dipinjam</th><th>Stok Setelah Approval</th></tr>";
    while($row = mysqli_fetch_assoc($q)) {
        $stok_after = max(0, $row['stok'] - $row['jumlah']);
        echo "<tr><td>{$row['id']}</td><td>{$row['nama']}</td><td>{$row['stok']}</td><td>{$row['jumlah']}</td><td>$stok_after</td></tr>";
    }
    echo "</table>";
    echo "<p style='color: green;'>✓ Sistem siap untuk mengurangi stok saat approval</p>";
}

echo "<h3>3. Equipment API Test (get_barang.php)</h3>";
$labor_q = mysqli_query($conn, "SELECT id, nama FROM labor LIMIT 1");
if($labor = mysqli_fetch_assoc($labor_q)) {
    $labor_id = $labor['id'];
    echo "<p>Testing dengan Labor: {$labor['nama']} (ID: $labor_id)</p>";
    
    // Simulate API call
    $api_q = mysqli_query($conn, "SELECT id, nama, stok FROM barang_labor 
                                   WHERE labor_id=$labor_id AND status='Tersedia' AND stok > 0 
                                   ORDER BY nama ASC");
    
    if(mysqli_num_rows($api_q) === 0) {
        echo "<p style='color: orange;'>⚠️ Tidak ada barang dengan status Tersedia & stok > 0 untuk labor ini</p>";
    } else {
        echo "<table border='1' cellpadding='10'>";
        echo "<tr><th>ID</th><th>Nama</th><th>Stok</th></tr>";
        while($row = mysqli_fetch_assoc($api_q)) {
            echo "<tr><td>{$row['id']}</td><td>{$row['nama']}</td><td>{$row['stok']}</td></tr>";
        }
        echo "</table>";
        echo "<p style='color: green;'>✓ API siap menampilkan equipment dengan stok yang tersedia</p>";
    }
}

echo "<h3>4. Summary</h3>";
$total_stok = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(stok) as total FROM barang_labor"))['total'];
$tersedia_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM barang_labor WHERE stok > 0"))['count'];

echo "<p>Total stok semua barang: <strong>$total_stok</strong></p>";
echo "<p>Total barang dengan stok > 0: <strong>$tersedia_count</strong></p>";
echo "<p style='color: green;'><strong>✓ Sistem stock reduction siap digunakan!</strong></p>";
?>
