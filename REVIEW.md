# Review Mendalam: Sistem Peminjaman Peralatan Laboratorium

## Ringkasan Project

Aplikasi web berbasis **PHP + MySQL (mysqli)** untuk manajemen peminjaman peralatan laboratorium dengan fitur face recognition, notifikasi real-time, dan email automation.

**Stack:** PHP 8.2, MariaDB 10.4, Bootstrap 5, face-api.js  
**Versi:** Update V3

---

## Daftar Isi

1. [Keamanan - Kritis](#1-keamanan---kritis)
2. [Keamanan - Medium](#2-keamanan---medium)
3. [Bug & Logic Error](#3-bug--logic-error)
4. [Arsitektur & Code Quality](#4-arsitektur--code-quality)
5. [Database](#5-database)
6. [Prioritas Perbaikan](#6-prioritas-perbaikan)

---

## 1. KEAMANAN - KRITIS

### 1.1 SQL Injection (Sistemik)

**Tidak ada satupun prepared statement di seluruh codebase.** Semua query menggunakan string concatenation.

| File | Contoh |
|------|--------|
| `auth/login.php` (line 10) | `SELECT * FROM users WHERE nim='$nim'` — `$nim` **tanpa escape** |
| `admin/peminjaman.php` | Reject handler: `$id=$_POST['peminjaman_id']` tanpa `intval()` |
| `admin/face-recognition/process_attendance.php` | `$_POST['status']` langsung masuk query tanpa validasi |
| `pages/peminjaman-content.php` | `htmlspecialchars()` dipakai sebagai pengganti SQL escaping — **salah fungsi** |
| `admin/kelola_labor.php` (line 83) | `INSERT INTO labor ... VALUES ('$nama', '$deskripsi', '$color', '$image')` |
| `admin/peralatan.php` (line 68) | `INSERT INTO barang_labor ... VALUES ($labor_id, '$nama', '$deskripsi', $stok, $stok)` |
| `admin/users.php` (line 59) | `UPDATE admin SET nama='$nama', username='$username', password='$hashedPassword' WHERE id=$admin_id` |
| `admin/notifikasi.php` (line 57) | `INSERT INTO notifications ... VALUES ($user_id, 'user', '$title', '$message_content', 0, NOW())` |

**Rekomendasi:** Ganti seluruh query ke `mysqli_prepare()` + `bind_param()`.

```php
// SEBELUM (rentan):
$q = mysqli_query($conn, "SELECT * FROM users WHERE nim='$nim'");

// SESUDAH (aman):
$stmt = mysqli_prepare($conn, "SELECT * FROM users WHERE nim = ?");
mysqli_stmt_bind_param($stmt, "s", $nim);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
```

---

### 1.2 Registrasi Admin Terbuka

**File:** `admin/register.php`

**Siapapun** bisa membuat akun admin tanpa autentikasi. Tidak ada invitation token, approval process, atau auth check. Penyerang bisa langsung membuat akun admin dan mengambil alih sistem.

```php
// Satu-satunya "proteksi" — hanya redirect jika SUDAH login sebagai admin
if(isset($_SESSION['admin_id'])){
  header("Location: dashboard.php");
  exit;
}
// User anonim bisa langsung registrasi admin!
```

**Rekomendasi:** Tambahkan auth check (`$_SESSION['admin_id']` required) atau hapus file ini dan buat admin melalui command line / seeder.

---

### 1.3 XSS (Cross-Site Scripting)

| File | Variabel Tidak Di-escape | Tipe |
|------|--------------------------|------|
| `admin/users_ajax.php` | `$user['nama']`, `$user['email']`, `$user['nim']`, `$user['username']` | Stored XSS |
| `admin/notifikasi_ajax.php` | `$user['nama']`, `$user['email']`, `$user['nim']` | Stored XSS |
| `user/profil.php` | `$u['nama']`, `$u['foto']`, `$u['nim']`, `$u['email']` | Stored XSS |
| `peminjaman/riwayat.php` | `$d['labor_nama']`, `$d['barang_nama']` | Stored XSS |
| `pages/riwayat-content.php` | `$d['labor_nama']`, `$d['barang_nama']` | Stored XSS |
| `pages/labor-content.php` | `addslashes()` dipakai untuk konteks JavaScript | DOM XSS |
| `admin/users_ajax.php` | `addslashes()` untuk onclick handler | DOM XSS |
| `admin/face-recognition/face-capture.php` | `innerHTML` dengan data unsanitized | DOM XSS |

**Contoh kerentanan:**
```php
// RENTAN - user/profil.php:
<img src="../assets/img/<?=$u['foto']?>" ...>
<h4><?=$u['nama']?></h4>

// AMAN:
<img src="../assets/img/<?= htmlspecialchars($u['foto'], ENT_QUOTES, 'UTF-8') ?>" ...>
<h4><?= htmlspecialchars($u['nama'], ENT_QUOTES, 'UTF-8') ?></h4>
```

**Untuk konteks JavaScript, gunakan `json_encode()` bukan `addslashes()`:**
```php
// RENTAN:
nama: "<?php echo addslashes($labor['nama']); ?>",

// AMAN:
nama: <?php echo json_encode($labor['nama']); ?>,
```

---

### 1.4 Tidak Ada CSRF Protection

**Seluruh form dan AJAX endpoint tanpa CSRF token.** Dampak — penyerang bisa membuat halaman yang otomatis:

- Approve/tolak peminjaman (`admin/peminjaman.php`)
- Konfirmasi/tolak pengembalian (`admin/pengembalian.php`)
- Aktifkan/hapus akun user (`admin/aktivasi.php`)
- Tambah/hapus peralatan (`admin/peralatan.php`)
- Tambah/hapus akun admin (`admin/users.php`)
- Hapus laboratorium (`admin/kelola_labor.php`)
- Kirim notifikasi massal (`admin/notifikasi.php`)
- Ubah password user (`pages/process-profil.php`)
- Submit peminjaman (`pages/peminjaman-content.php`)

**Rekomendasi:** Implementasi CSRF token di setiap form:

```php
// Buat token (simpan di session):
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));

// Di form HTML:
<input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

// Validasi saat proses:
if(!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])){
    die("Invalid CSRF token");
}
```

---

### 1.5 File Yang Tidak Dilindungi Auth

File-file berikut bisa diakses **tanpa login**:

| File | Dampak |
|------|--------|
| `peminjaman/tambah.php` | **User anonim bisa submit peminjaman** |
| `user/edit.php` | **Modifikasi profil tanpa login** |
| `user/profil.php` | Data profil terekspos |
| `peminjaman/riwayat.php` | Riwayat peminjaman terekspos |
| `user/process-edit.php` | Endpoint update profil tanpa auth |

**Rekomendasi:** Tambahkan auth check di awal setiap file:

```php
session_start();
if(!isset($_SESSION['user_id'])){
    header("Location: ../auth/login.php");
    exit;
}
```

---

### 1.6 File Upload Tidak Aman

**File:** `pages/process-profil.php`, `user/process-edit.php`, `admin/kelola_labor.php`

Masalah:
- Hanya validasi ekstensi file, **tidak ada cek MIME type** atau konten file
- Direktori upload menggunakan permission `0777`
- Tidak ada `.htaccess` untuk mencegah eksekusi PHP di folder upload
- Potensi upload PHP web shell dengan double extension (misal: `shell.php.jpg`)

**Rekomendasi:**
```php
// Validasi MIME type:
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime = finfo_file($finfo, $_FILES['foto']['tmp_name']);
$allowed_mimes = ['image/jpeg', 'image/png', 'image/gif'];
if(!in_array($mime, $allowed_mimes)){
    die("Tipe file tidak diizinkan");
}

// Rename file dengan random name:
$new_name = bin2hex(random_bytes(16)) . '.' . $ext;

// Buat .htaccess di folder upload:
// php_flag engine off

// Gunakan permission yang lebih ketat:
mkdir($target_dir, 0755, true);
```

---

## 2. KEAMANAN - MEDIUM

### 2.1 Session Fixation

**File:** `auth/login.php`, `admin/login.php`

Tidak ada `session_regenerate_id()` setelah login berhasil:

```php
// SEKARANG:
$_SESSION['user_id'] = $u['id'];
header("Location: ../pages/dashboard-content.php");

// SEHARUSNYA:
session_regenerate_id(true);
$_SESSION['user_id'] = $u['id'];
header("Location: ../pages/dashboard-content.php");
```

### 2.2 Hardcoded Database Credentials

**File:** `config/database.php`

```php
$conn = mysqli_connect("localhost","root","","aplikasi_labor");
```

Root user tanpa password. Meskipun untuk development, jika ini sampai ke production akan sangat berbahaya.

**Rekomendasi:** Gunakan environment variables:
```php
$conn = mysqli_connect(
    getenv('DB_HOST') ?: 'localhost',
    getenv('DB_USER') ?: 'root',
    getenv('DB_PASS') ?: '',
    getenv('DB_NAME') ?: 'aplikasi_labor'
);
```

### 2.3 Information Disclosure via `mysqli_error()`

Database error details yang di-expose ke user:

| File | Contoh |
|------|--------|
| `admin/aktivasi.php` (line 66) | `"Terjadi kesalahan saat mengaktifkan akun: " . mysqli_error($conn)` |
| `admin/register.php` (line 41) | `"Terjadi kesalahan: " . mysqli_error($conn)` |
| `admin/peralatan.php` (line 72) | `"Gagal menambahkan barang: " . mysqli_error($conn)` |
| `admin/login.php` (line 28) | `"Terjadi kesalahan database: " . mysqli_error($conn)` |
| `pages/process-profil.php` (line 108) | `'Terjadi kesalahan: ' . mysqli_error($conn)` |
| `admin/face-recognition/process_attendance.php` | `'Gagal menyimpan embedding: ' . mysqli_error($conn)` |
| `admin/face-recognition/api_search_students.php` | `'Database error: ' . mysqli_error($conn)` |
| `admin/face-recognition/index.php` | `die("Query error: " . mysqli_error($conn))` |

Ini mengungkapkan nama tabel, nama kolom, dan struktur query kepada penyerang.

**Rekomendasi:** Log error ke file, tampilkan pesan generik ke user:
```php
error_log("DB Error: " . mysqli_error($conn));
$error = "Terjadi kesalahan sistem. Silakan coba lagi.";
```

### 2.4 Race Condition pada Manajemen Stok

**File:** `admin/peminjaman.php`

Single approve menggunakan `mysqli_begin_transaction()` (bagus), tapi **bulk approve tidak menggunakan transaction**. Dua admin yang approve bersamaan bisa menyebabkan stok negatif. `max(0, ...)` hanya menyamarkan masalah.

### 2.5 Admin Bisa Hapus Diri Sendiri

**File:** `admin/users.php`

Tidak ada validasi apakah admin sedang menghapus akunnya sendiri. Jika ini admin terakhir, semua orang terkunci dari sistem.

```php
// Tambahkan validasi:
if($user_id == $_SESSION['admin_id']){
    $error = "Anda tidak dapat menghapus akun Anda sendiri!";
}
```

### 2.6 Face Recognition Threshold Terlalu Rendah

Data `attendance_logs` menunjukkan confidence score serendah **0.023** yang diterima. Ini sangat rentan false positive — orang yang salah bisa muncul sebagai user lain.

### 2.7 Weak Session Destruction

**File:** `admin/logout.php`, `auth/logout.php`

```php
session_start();
session_destroy();
header("Location: login.php");
```

Tidak memanggil `session_unset()`, tidak menghapus cookie session, tidak `session_regenerate_id()`.

**Rekomendasi:**
```php
session_start();
$_SESSION = [];
if(ini_get("session.use_cookies")){
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $params["path"], $params["domain"], $params["secure"], $params["httponly"]);
}
session_destroy();
header("Location: login.php");
```

---

## 3. BUG & LOGIC ERROR

### 3.1 Duplicate Event Listener (Bug Kritis)

**File:** `pages/profil-content.php`

`formEditPassword` submit handler terdaftar **dua kali secara nested**, menyebabkan form submission berlipat ganda secara eksponensial:

```javascript
// Handler pertama
document.getElementById('formEditPassword').addEventListener('submit', async function(e) {
    // ... code ...
    // BUG: Handler kedua terdaftar di dalam handler pertama!
    document.getElementById('formEditPassword').addEventListener('submit', async function(e) {
        // ...
    });
});
```

**Dampak:** Setiap kali form di-submit, listener baru ditambahkan. Submit ke-2 mengirim 2 request, submit ke-3 mengirim 4 request, dst.

### 3.2 Dead Code / Unreachable Branch

**File:** `pages/peminjaman-content.php` (line ~109-137)

```php
if($all_success){
    // Buat notifikasi admin (15 baris kode)
} else if(empty($failed_items)){
    // EXACT SAME CODE — tidak pernah tercapai!
    // Jika $all_success=false, $failed_items pasti tidak empty
}
```

### 3.3 Broken Sort Logic

**File:** `admin/users_ajax.php` (line 44-46)

```php
usort($results, function($a, $b) {
    return strtotime($b['id'] ?? '0') - strtotime($a['id'] ?? '0');
});
```

`strtotime()` pada integer `id` mengembalikan `false` (cast ke `0`), sehingga sort ini tidak melakukan apa-apa.

### 3.4 DATE() pada Kolom Integer

**File:** `admin/aktivasi.php` (line 48)

```php
$where .= " AND DATE(id) = '$filter_date'";
```

Fungsi `DATE()` pada kolom integer `id` — secara logika salah total. Seharusnya menggunakan kolom `created_at`.

### 3.5 Broken Include Path

**File:** `admin/test_mailer.php` (line 3)

```php
include 'config/database.php';  // SALAH — file ini di folder admin/
// SEHARUSNYA:
include '../config/database.php';
```

### 3.6 Inconsistent URL Paths di loadPage()

**File:** `pages/dashboard-content.php` (line 756-778)

```javascript
case 'labor': url = '../pages/labor-content.php'; break;      // ../pages/
case 'peminjaman': url = 'pages/peminjaman-content.php'; break; // pages/
case 'notifikasi': url = '../pages/notifikasi-content.php'; break; // ../pages/
```

Mix antara `../pages/` dan `pages/` — navigasi akan rusak tergantung current URL context.

### 3.7 Double Auth Check (Redundant & Broken)

5+ file di `pages/` melakukan cek session dua kali:

```php
<?php session_start();
if(!isset($_SESSION['user_id'])){ header("Location: ..."); exit; } // Check #1
?>
<!DOCTYPE html>
...
<?php
if(!isset($_SESSION['user_id'])){ header("HTTP/1.1 401"); exit; } // Check #2 — BROKEN
// Headers sudah terkirim setelah HTML output!
```

### 3.8 setInterval + Async Race Condition

**File:** `admin/face-recognition/face-capture.php`

```javascript
setInterval(detectFace, 100); // 100ms interval
```

`setInterval` tidak menunggu iterasi sebelumnya selesai. Karena `detectFace` bersifat async (API calls), multiple detection cycles berjalan bersamaan, menyebabkan race condition dan duplicate submissions.

**Rekomendasi:** Gunakan `setTimeout` recursive:
```javascript
async function detectLoop() {
    await detectFace();
    setTimeout(detectLoop, 100);
}
detectLoop();
```

---

## 4. ARSITEKTUR & CODE QUALITY

### 4.1 Tidak Ada Separation of Concerns

Setiap file mencampur:
1. DDL/Schema management (`CREATE TABLE`, `ALTER TABLE`)
2. Business logic (approve/reject/stock management)
3. Data access (raw SQL)
4. Email sending
5. HTML/CSS/JavaScript (200-500 baris inline CSS per file)

Contoh: `admin/peminjaman.php` = **~950 baris** menggabungkan semuanya dalam satu file.

### 4.2 DDL di Setiap Page Load

File-file berikut menjalankan `CREATE TABLE IF NOT EXISTS`, `SHOW COLUMNS`, dan `ALTER TABLE` pada **setiap HTTP request**:

| File | DDL Queries per Request |
|------|------------------------|
| `admin/peminjaman.php` | 4 schema checks + `CREATE TABLE` + `ALTER TABLE` |
| `admin/pengembalian.php` | 1 `ALTER TABLE` check |
| `admin/peralatan.php` | 2 `CREATE TABLE IF NOT EXISTS` + `ALTER TABLE` |
| `admin/notifikasi.php` | 1 `ALTER TABLE` check |
| `admin/notification_logs.php` | `CREATE TABLE IF NOT EXISTS` + `ALTER TABLE ADD COLUMN IF NOT EXISTS` |
| `pages/peminjaman-content.php` | Multiple `SHOW COLUMNS` + `ALTER TABLE` |
| `pages/labor-content.php` | `CREATE TABLE IF NOT EXISTS` |
| `pages/dashboard-content.php` | `SHOW COLUMNS` + `ALTER TABLE` |

Ini menambah **5-10 query tambahan** per page load dan seharusnya dipindahkan ke migration script.

### 4.3 CSS Duplicasi Masif

Estimasi **3000+ baris CSS** yang diduplikasi. Contoh blok yang identik di hampir setiap file:

- `:root` CSS variables
- `@keyframes slideInUp, slideInDown, fadeIn, pulse`
- `.main-content` responsive breakpoints
- `.stat-card`, `.table`, `.badge-*` styles

`assets/admin-styles.css` sudah ada tapi **tidak digunakan** oleh file admin manapun.

### 4.4 Tidak Ada Auth Middleware Terpusat

30+ file mengulang pola identik:

```php
session_start();
include '../config/database.php';
if(!isset($_SESSION['admin_id'])){
    header("Location: login.php");
    exit;
}
$id = $_SESSION['admin_id'];
$admin_q = mysqli_query($conn, "SELECT * FROM admin WHERE id=$id");
$a = mysqli_fetch_assoc($admin_q);
if(!$a){ die("Admin tidak ditemukan"); }
```

**Rekomendasi:** Buat satu file `config/auth.php`:

```php
<?php
session_start();
require_once __DIR__ . '/database.php';

function require_admin_auth($conn) {
    if(!isset($_SESSION['admin_id'])){
        header("Location: " . dirname($_SERVER['SCRIPT_NAME']) . "/../admin/login.php");
        exit;
    }
    $id = intval($_SESSION['admin_id']);
    $stmt = mysqli_prepare($conn, "SELECT id, nama, username FROM admin WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $admin = mysqli_fetch_assoc($result);
    if(!$admin){ session_destroy(); header("Location: login.php"); exit; }
    return $admin;
}

function require_user_auth($conn) {
    if(!isset($_SESSION['user_id'])){
        header("Location: " . dirname($_SERVER['SCRIPT_NAME']) . "/../auth/login.php");
        exit;
    }
    $id = intval($_SESSION['user_id']);
    $stmt = mysqli_prepare($conn, "SELECT id, nama, nim, email, foto FROM users WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $user = mysqli_fetch_assoc($result);
    if(!$user){ session_destroy(); header("Location: login.php"); exit; }
    return $user;
}
```

### 4.5 `SELECT *` di Mana-mana

Seluruh auth check menggunakan `SELECT * FROM admin/users` — mengambil semua kolom termasuk password hash, padahal hanya butuh `id` dan `nama`.

### 4.6 Counting via Full Result Set

```php
// TIDAK EFISIEN — fetch semua row lalu hitung:
$pending = mysqli_num_rows(mysqli_query($conn, "SELECT id FROM peminjaman WHERE status='Menunggu'"));

// EFISIEN — hitung di database:
$row = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM peminjaman WHERE status='Menunggu'"));
$pending = $row['total'];
```

Ditemukan di: `admin/peminjaman.php`, `admin/pengembalian.php`, `admin/history.php`, `admin/aktivasi.php`, `admin/peralatan.php`, `assets/sidebar.php`.

### 4.7 Face Comparison O(n)

**File:** `admin/face-recognition/api_face_compare.php`

Memuat **SEMUA face embedding** dari database ke PHP memory untuk setiap comparison request. Dengan ribuan user (masing-masing 5 embedding), ini akan sangat lambat dan memakan memory.

### 4.8 Inconsistent AJAX Response Format

| File | Format |
|------|--------|
| `admin/users_ajax.php` | Raw HTML fragments |
| `admin/notifikasi_ajax.php` | Raw HTML fragments |
| `admin/history_ajax.php` | JSON `{total, rows}` |
| `admin/peralatan_ajax.php` | JSON `{total, rows}` |
| `admin/face-recognition/history.php` | JSON `{success, html}` |

Tidak ada standar API contract.

### 4.9 `header("Refresh: 2")` Anti-Pattern

**File:** `admin/aktivasi.php`

```php
if($update){
    $success = "Akun berhasil diaktifkan!";
    header("Refresh: 2");
}
```

Ini menyebabkan form resubmission saat browser refresh. Gunakan pola **PRG (Post-Redirect-Get)**:

```php
if($update){
    $_SESSION['flash_success'] = "Akun berhasil diaktifkan!";
    header("Location: aktivasi.php");
    exit;
}
```

### 4.10 Admin Name Fetched via AJAX

**File:** `assets/admin_sidebar.php`

Memanggil `fetch('../config/get_admin_info.php')` pada setiap page load untuk mendapatkan nama admin, padahal session sudah aktif di server. Nama admin seharusnya di-render server-side langsung di sidebar PHP.

### 4.11 Empty/Misleading Files

- `assets/footer.php` — File kosong, tidak digunakan
- `assets/header.php` — Hanya berisi dark-mode CSS + localStorage script, bukan `<head>` atau `<header>` HTML

---

## 5. DATABASE

### 5.1 Schema Issues

| Masalah | Detail |
|---------|--------|
| Tidak ada index pada `notifications.user_id` | Query notifikasi lambat dengan banyak data |
| Tidak ada foreign key pada `notifications` | Data orphan bisa terjadi |
| `email` di tabel `users` tidak UNIQUE | Duplikasi email diperbolehkan (data sample sudah menunjukkan: `azik@gmail.com` x2) |
| `face_embeddings.embedding` sebagai TEXT | Tidak ada validasi format JSON, data tidak bisa di-query |
| Tidak ada soft delete | User yang dihapus langsung hilang, referensi di tabel lain bisa break |
| Kolom `password` di `admin` nullable | `varchar(255) DEFAULT NULL` — admin bisa tanpa password |
| Tidak ada kolom `created_at` di `admin` | Tidak bisa track kapan admin dibuat |
| `status` di `barang_labor` varchar(20) | Seharusnya ENUM untuk data integrity |

### 5.2 Data Quality

- `stok` bisa menjadi negatif karena race condition
- Tidak ada constraint `CHECK (stok >= 0)`
- `nim` unique tapi `email` tidak — inconsistent
- Face embeddings disimpan sebagai JSON string dalam TEXT column — tidak efisien

---

## 6. PRIORITAS PERBAIKAN

### Urgensi Tertinggi (Harus Segera)

| # | Masalah | File | Effort |
|---|---------|------|--------|
| 1 | Tutup `/admin/register.php` dari akses publik | `admin/register.php` | Rendah |
| 2 | Tambahkan auth check ke file yang terbuka | `peminjaman/tambah.php`, `user/edit.php`, dll | Rendah |
| 3 | Ganti seluruh query ke prepared statements | Semua file | Tinggi |
| 4 | Escape semua output dengan `htmlspecialchars()` | Semua file | Sedang |
| 5 | Tambahkan CSRF token ke semua form | Semua file | Sedang |

### Urgensi Tinggi

| # | Masalah | File | Effort |
|---|---------|------|--------|
| 6 | Validasi file upload (MIME type + .htaccess) | `process-profil.php`, `process-edit.php`, `kelola_labor.php` | Rendah |
| 7 | Session regeneration setelah login | `auth/login.php`, `admin/login.php` | Rendah |
| 8 | Pindahkan credentials ke environment variables | `config/database.php` | Rendah |
| 9 | Hapus `mysqli_error()` dari response user | 8+ file | Rendah |
| 10 | Fix duplicate event listener bug | `pages/profil-content.php` | Rendah |

### Peningkatan Kualitas

| # | Masalah | File | Effort |
|---|---------|------|--------|
| 11 | Buat auth middleware terpusat | Baru: `config/auth.php` | Sedang |
| 12 | Pindahkan DDL ke migration script | Semua file dengan DDL | Sedang |
| 13 | Ekstrak CSS ke shared stylesheet | `assets/admin-styles.css` + refactor | Tinggi |
| 14 | Standardisasi format AJAX response | Semua AJAX files | Sedang |
| 15 | Ganti `addslashes()` dengan `json_encode()` untuk JS context | `labor-content.php`, `users_ajax.php` | Rendah |
| 16 | Implementasi PRG pattern | `admin/aktivasi.php` + lainnya | Rendah |
| 17 | Optimasi `SELECT *` dan counting queries | Semua file | Sedang |
| 18 | Face comparison optimization | `api_face_compare.php` | Tinggi |

---

## Kesimpulan

Project ini memiliki fitur yang cukup lengkap (peminjaman, notifikasi, face recognition, email automation) namun memiliki **kerentanan keamanan serius** yang harus diperbaiki sebelum deploy ke production. 

**3 masalah paling kritis:**
1. **Open admin registration** — siapapun bisa jadi admin
2. **SQL Injection sistemik** — seluruh query rentan
3. **Tidak ada CSRF protection** — semua aksi admin bisa di-forge

Dari sisi arsitektur, project akan sangat terbantu dengan refactoring ke pola yang lebih terstruktur: auth middleware terpusat, prepared statements, shared CSS, dan migration script terpisah.
