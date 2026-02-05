# 📚 Sistem Peminjaman Peralatan Laboratorium - Dokumentasi Lengkap

## 📋 Daftar Isi
1. [Overview Fitur](#overview-fitur)
2. [Fitur Utama](#fitur-utama)
3. [Database Schema](#database-schema)
4. [Alur Kerja Sistem](#alur-kerja-sistem)
5. [Setup & Instalasi](#setup--instalasi)
6. [Testing Guide](#testing-guide)

---

## Overview Fitur

Sistem Peminjaman Peralatan Laboratorium ini adalah aplikasi web berbasis PHP untuk manajemen peminjaman dan pengembalian barang di laboratorium. Sistem telah diupdate dengan fitur-fitur modern termasuk notifikasi real-time, email automation, aktivasi akun, dan UI yang responsif.

### Versi: Update V3
- ✅ Sistem aktivasi akun admin
- ✅ Notifikasi real-time dengan badge counter
- ✅ Email automation untuk approval/penolakan
- ✅ Konfirmasi pengembalian barang dengan tracking
- ✅ UI redesign dengan animasi smooth
- ✅ Stock reduction system yang reliable
- ✅ Fitur tampilkan barang tidak tersedia (status disabled)

---

## Fitur Utama

### 1. ✅ Sistem Aktivasi Akun (Account Activation System)

**Tujuan:** Mengontrol akses user baru sebelum bisa login

**Komponen Utama:**
- **`/admin/aktivasi.php`** - Dashboard admin untuk aktivasi akun
- **`/auth/register.php`** - Modifikasi form registrasi
- **`/auth/login.php`** - Validasi status akun saat login

**Fitur Aktivasi:**
- Daftar akun yang menunggu aktivasi dengan UI cards
- Search & filter (by nama, NIM, sort)
- Statistik: Menunggu, Sudah Aktif, Total User
- Tombol: Aktifkan / Tolak & Hapus
- Animasi smooth dan responsive design

**Database:**
```sql
ALTER TABLE users ADD COLUMN is_active TINYINT(1) DEFAULT 1;
-- 0 = Belum diaktifkan, 1 = Sudah aktif
```

**Workflow:**
```
User Registrasi → is_active = 0 
    ↓
Admin Aktivasi → is_active = 1 → User Bisa Login
    atau
Admin Tolak → Delete User
```

---

### 2. 🔔 Sistem Notifikasi & Email Mailer

**Tujuan:** Otomatis memberitahu admin dan user tentang status peminjaman

**Fitur:**
- **Notifikasi ke Admin:** Ketika user mengajukan peminjaman
- **Email ke User:** Saat admin approve/tolak peminjaman
- **In-App Notification:** Notifikasi di dalam sistem
- **Badge Counter:** Real-time notification badge di sidebar

**File Implementasi:**
- `config/mailer.php` - Mailer system (PHP mail() / SMTP)
- `config/mailer_config.php` - Konfigurasi SMTP
- `pages/peminjaman-content.php` - Insert notifikasi saat submit
- `admin/peminjaman.php` - Kirim email & notifikasi saat approve
- `admin/test_mailer.php` - Tool untuk test konfigurasi email
- `config/get_admin_unread_notifications.php` - API untuk badge counter

**Workflow Notifikasi:**
```
USER MEMBUAT PEMINJAMAN
    ↓ (Insert notifikasi ke DB)
ADMIN MELIHAT BADGE COUNTER
    ↓
ADMIN APPROVE
    ↓ (Kirim email HTML + in-app notification)
USER TERIMA EMAIL & NOTIFIKASI
```

**Setup Email (3 Opsi):**

**Option 1: PHP mail() - Default (No Setup)**
```php
// config/mailer_config.php
'use_smtp' => false  // Gunakan PHP mail() function
```

**Option 2: SMTP Gmail**
```php
'use_smtp' => true,
'smtp_host' => 'smtp.gmail.com',
'smtp_port' => 587,
'smtp_user' => 'your-email@gmail.com',
'smtp_pass' => 'your-app-password'  // Bukan password akun
```

**Option 3: SMTP Custom**
```php
'use_smtp' => true,
'smtp_host' => 'mail.yourdomain.com',
'smtp_port' => 587,
'smtp_user' => 'no-reply@yourdomain.com',
'smtp_pass' => 'password'
```

**Test Email:** Akses `/admin/test_mailer.php` untuk test konfigurasi

---

### 3. 📨 Fitur Konfirmasi Pengembalian Barang

**Tujuan:** Tracking pengembalian barang dan update stok otomatis

**Komponen:**
- `admin/pengembalian.php` - Konfirmasi pengembalian barang
- `admin/history_pengembalian.php` - History pengembalian yang sudah dikonfirmasi

**Fitur:**
- Daftar peminjaman yang status "Disetujui" dan belum dikembalikan
- Admin confirm pengembalian → stok otomatis bertambah
- Admin tolak pengembalian dengan alasan
- Statistik pengembalian
- History tracking untuk audit

**Workflow Pengembalian:**
```
USER AMBIL BARANG (Status: Disetujui)
    ↓
USER KEMBALIKAN KE ADMIN
    ↓
ADMIN CONFIRM DI pengembalian.php
    ↓ (Stok bertambah, status → Dikembalikan)
HISTORY TERSIMPAN
```

**Database:**
```sql
ALTER TABLE peminjaman ADD tanggal_kembali DATETIME NULL;
-- Status values: Menunggu, Disetujui, Dikembalikan, Ditolak
```

---

### 4. 🎨 UI Redesign dengan Animasi

**Pages yang Diupdate:**
- `admin/aktivasi.php` - Redesign lengkap dengan welcome card, stats, user cards
- `admin/pengembalian.php` - Tambah animasi cascade
- `admin/edit_peralatan.php` - Tambah animasi smooth
- `admin/notification_logs.php` - Tambah animasi fade & slide

**Animasi yang Diterapkan:**
- `slideInDown` - Elemen muncul dari atas
- `slideInUp` - Elemen muncul dari bawah
- `fadeIn` - Fade opacity animation
- `pulse` - Animasi badge untuk menarik perhatian
- **Cascade Delay** - Elemen muncul berurutan (0.2s, 0.3s, 0.4s)

**Responsive Design:**
- Mobile first approach
- Breakpoint: 768px (mobile vs desktop)
- Sidebar toggle di mobile
- Full-width buttons di mobile

---

### 5. 🔧 Admin Sidebar Refactoring

**Tujuan:** Centralize sidebar component untuk menghilangkan code duplication

**Komponen Baru:**
- `assets/admin_sidebar.php` - Single reusable sidebar component

**Keuntungan:**
- Mengurangi code duplikasi ~1,330 baris (88% reduction)
- Menu updates di 1 tempat, otomatis ke semua pages
- Active page detection otomatis
- Easier maintenance

**Menu Items di Sidebar:**
```
1. Beranda (Dashboard)
2. Data Peminjaman
3. Konfirmasi Pengembalian
4. History Pengembalian
5. Kelola Peralatan
6. Log Notifikasi Email
7. Kelola Info Dashboard
```

---

### 6. ✨ Fitur Tampilkan Barang Tidak Tersedia

**Tujuan:** User bisa lihat daftar lengkap barang, namun barang tidak tersedia dalam status disabled

**Implementasi:**
- Query filter: `status='Tersedia' AND stok > 0` (untuk tersedia)
- Tampilkan semua barang, disabled items dalam grey state
- Badge "Habis" untuk stok = 0
- Badge "Tidak Tersedia" untuk barang dengan status = 0

**User Experience:**
```
Labor: Elektronika
├─ ✅ Osciloscope (Stok: 5) - DAPAT DIPILIH
├─ ✅ Multimeter (Stok: 10) - DAPAT DIPILIH
├─ 🚫 Power Supply [Habis] (Stok: 0) - TIDAK DAPAT DIPILIH
└─ 🚫 Voltmeter [Tidak Tersedia] - TIDAK DAPAT DIPILIH
```

**Benefit:**
- Transparansi informasi ketersediaan
- User tahu barang apa saja di lab
- UX lebih informatif
- Mengurangi pertanyaan "barang ada tidak?"

---

### 7. 🔄 Stock Reduction System (Reliable)

**Masalah yang Ditangani:**
- Stock tidak berkurang saat approval
- Browser cache menampilkan stok lama
- API tidak filter barang tidak tersedia

**Solusi:**
1. **Transaction-based Approval** - `mysqli_begin_transaction()` & `mysqli_commit()`
2. **Cache Busting** - Timestamp parameter di fetch URL
3. **API Filter** - Hanya return `stok > 0`
4. **Detailed Error Handling** - Rollback jika ada error

**Approval Flow:**
```
Admin Click Approve
    ↓
1. Get peminjaman details (barang_id, jumlah)
2. UPDATE peminjaman status → 'Disetujui'
3. GET current stok
4. CALC new_stok = stok - jumlah
5. SET status = (stok=0 ? 'Tidak Tersedia' : 'Tersedia')
6. UPDATE barang_labor
7. COMMIT transaction
    ↓ (atau ROLLBACK jika error)
SUCCESS
```

---

## Database Schema

### Tables Utama

#### `users` Table
```sql
id (PRIMARY KEY)
nama (VARCHAR)
nim (VARCHAR - UNIQUE)
email (VARCHAR)
password (VARCHAR - hashed)
foto (VARCHAR)
is_active (TINYINT) -- 0=pending, 1=active
created_at (TIMESTAMP)
```

#### `labor` Table
```sql
id (PRIMARY KEY)
nama (VARCHAR)
deskripsi (TEXT)
lokasi (VARCHAR)
```

#### `barang_labor` Table
```sql
id (PRIMARY KEY)
labor_id (FOREIGN KEY → labor)
nama (VARCHAR)
deskripsi (TEXT)
stok (INT)
status (ENUM: 'Tersedia', 'Tidak Tersedia')
kondisi (TEXT)
```

#### `peminjaman` Table
```sql
id (PRIMARY KEY)
user_id (FOREIGN KEY → users)
barang_labor_id (FOREIGN KEY → barang_labor)
jumlah (INT)
tanggal_peminjaman (DATETIME)
tanggal_kembali (DATETIME - NULL)
status (ENUM: 'Menunggu', 'Disetujui', 'Dikembalikan', 'Ditolak')
alasan_penolakan (TEXT - NULL)
```

#### `notifications` Table (Auto-created)
```sql
id (PRIMARY KEY)
user_id (INT)
title (VARCHAR)
message (TEXT)
is_read (TINYINT)
created_at (TIMESTAMP)
```

---

## Alur Kerja Sistem

### Complete Workflow: Peminjaman → Approval → Pengembalian

```
┌─────────────────────────────────────────────────────────────┐
│ 1. USER MEMBUAT PEMINJAMAN                                  │
├─────────────────────────────────────────────────────────────┤
│ - User pilih labor & barang yang tersedia (stok > 0)       │
│ - Input jumlah peminjaman                                   │
│ - Click "Ajukan Peminjaman"                                │
│ - Status: 'Menunggu'                                        │
│ - AUTO: Insert notifikasi ke admin                         │
└─────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────┐
│ 2. ADMIN MELIHAT NOTIFIKASI MASUK                           │
├─────────────────────────────────────────────────────────────┤
│ - Badge counter di sidebar menampilkan jumlah notifikasi   │
│ - Admin buka halaman "Data Peminjaman" (peminjaman.php)   │
│ - Admin lihat detail: barang, jumlah, user                │
└─────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────┐
│ 3. ADMIN APPROVE PEMINJAMAN                                 │
├─────────────────────────────────────────────────────────────┤
│ - Click "✔ Approve" button                                 │
│ - AUTO: Update status → 'Disetujui'                        │
│ - AUTO: Kurangi stok barang (transaction)                  │
│ - AUTO: Kirim email HTML ke user                           │
│ - AUTO: Create in-app notification ke user                 │
└─────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────┐
│ 4. USER TERIMA EMAIL & NOTIFIKASI                           │
├─────────────────────────────────────────────────────────────┤
│ - Email masuk dengan detail peminjaman                     │
│ - Notifikasi di sidebar user                               │
│ - User bisa ambil barang dari lab                          │
└─────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────┐
│ 5. USER KEMBALIKAN BARANG KE ADMIN                          │
├─────────────────────────────────────────────────────────────┤
│ - User memberikan barang fisik ke admin                    │
│ - Admin verifikasi kondisi barang                          │
└─────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────┐
│ 6. ADMIN CONFIRM PENGEMBALIAN                               │
├─────────────────────────────────────────────────────────────┤
│ - Buka halaman "Konfirmasi Pengembalian"                  │
│ - Click "Konfirmasi" atau "Tolak Pengembalian"            │
│ - AUTO: Update status → 'Dikembalikan'                     │
│ - AUTO: Tambah stok barang                                 │
│ - AUTO: Catat tanggal_kembali                              │
└─────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────┐
│ 7. HISTORY PENGEMBALIAN TERSIMPAN                           │
├─────────────────────────────────────────────────────────────┤
│ - Lihat di halaman "History Pengembalian"                 │
│ - Data lengkap: tanggal pinjam, tanggal kembali, user     │
│ - Untuk audit & laporan                                    │
└─────────────────────────────────────────────────────────────┘
```

### Aktivasi Akun Flow

```
┌─────────────────────────────────────────────────────────────┐
│ 1. USER REGISTRASI                                          │
├─────────────────────────────────────────────────────────────┤
│ - User isi form (nama, NIM, email, password)              │
│ - Password di-hash                                         │
│ - AUTO: is_active = 0 (pending)                           │
└─────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────┐
│ 2. USER LIHAT PESAN MENUNGGU AKTIVASI                       │
├─────────────────────────────────────────────────────────────┤
│ - "Akun Anda perlu diaktifkan admin terlebih dahulu"      │
│ - User belum bisa login                                    │
└─────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────┐
│ 3. ADMIN BUKA HALAMAN AKTIVASI                              │
├─────────────────────────────────────────────────────────────┤
│ - URL: /admin/aktivasi.php                                │
│ - Lihat daftar akun yang menunggu (is_active=0)          │
│ - Search by nama / NIM                                     │
│ - Statistik: menunggu, sudah aktif, total                |
└─────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────┐
│ 4. ADMIN DECIDE: AKTIFKAN ATAU TOLAK                        │
├─────────────────────────────────────────────────────────────┤
│ A. AKTIFKAN:                                               │
│    - Click "Aktifkan Akun"                                │
│    - is_active = 1                                         │
│    - User bisa login                                       │
│                                                             │
│ B. TOLAK & HAPUS:                                          │
│    - Click "Tolak & Hapus"                                │
│    - Delete user dari database                            │
│    - User tidak bisa login                                │
└─────────────────────────────────────────────────────────────┘
```

---

## Setup & Instalasi

### 1. Database Setup

**Untuk Database Baru:**
- Run file `database/aplikasi_labor.sql`
- Semua tables otomatis dibuat dengan struktur lengkap

**Untuk Database yang Sudah Ada:**
```sql
-- Tambahkan kolom is_active jika belum ada
ALTER TABLE users ADD COLUMN is_active TINYINT(1) DEFAULT 1;
UPDATE users SET is_active = 1;  -- Set existing users as active

-- Tambahkan kolom tanggal_kembali jika belum ada
ALTER TABLE peminjaman ADD tanggal_kembali DATETIME NULL;

-- Notifications table otomatis dibuat saat first run
```

### 2. Email Configuration

**Edit file:** `config/mailer_config.php`

**Pilih salah satu opsi:**

**Development (PHP mail()):**
```php
return [
  'use_smtp' => false
];
```

**Production (Gmail):**
```php
return [
  'use_smtp' => true,
  'smtp_host' => 'smtp.gmail.com',
  'smtp_port' => 587,
  'smtp_secure' => 'tls',
  'smtp_user' => 'your-email@gmail.com',
  'smtp_pass' => 'your-app-password',  // Bukan password akun biasa
  'from_email' => 'your-email@gmail.com',
  'from_name' => 'Sistem Peminjaman'
];
```

**Test Konfigurasi:**
- Akses: `/admin/test_mailer.php`
- Input email test
- Click "Kirim Email Test"
- Verifikasi email diterima

### 3. Directory Permissions

```bash
# Pastikan folder uploads dapat ditulis
chmod 755 assets/uploads
chmod 755 database/
```

### 4. Verifikasi Sistem

**Akses URL:** `verify_system.php`

Script akan check:
- ✅ Database connection
- ✅ Required tables exist
- ✅ Sample data available
- ✅ API functionality
- ✅ Stock reduction status

---

## Testing Guide

### Test Case 1: User Membuat Peminjaman
```
1. Login sebagai user
2. Buka "Peminjaman Peralatan" / peminjaman-content.php
3. Pilih labor
4. Lihat barang yang tersedia (stok > 0)
5. Verifikasi barang tidak tersedia dalam grey state
6. Pilih barang dan jumlah
7. Click "Ajukan Peminjaman"
8. ✅ Expected: Status → "Menunggu"
9. ✅ Expected: Notifikasi masuk ke admin
```

### Test Case 2: Admin Terima Notifikasi
```
1. Login sebagai admin
2. Lihat badge counter di sidebar
3. Verifikasi jumlah notifikasi
4. Click menu "Data Peminjaman"
5. ✅ Expected: Lihat peminjaman dari user
6. ✅ Expected: Detail lengkap ditampilkan
```

### Test Case 3: Admin Approve & Email
```
1. Di halaman peminjaman.php
2. Click "Approve" button
3. Verifikasi dialog confirm muncul
4. Click "Ya, Approve"
5. ✅ Expected: Status berubah menjadi "Disetujui"
6. ✅ Expected: Email terkirim ke user
7. ✅ Expected: Stok berkurang di database
8. ✅ Expected: Notifikasi masuk ke user
```

### Test Case 4: User Lihat Notifikasi & Email
```
1. Login sebagai user yang meminjam
2. Lihat sidebar → notifikasi badge muncul
3. Buka "Notifikasi" / notifikasi-content.php
4. ✅ Expected: Lihat notifikasi "Peminjaman Disetujui"
5. Check email inbox
6. ✅ Expected: Email diterima dengan detail peminjaman
```

### Test Case 5: User Kembalikan Barang
```
1. User memberikan barang fisik ke admin
2. Admin buka "Konfirmasi Pengembalian" / pengembalian.php
3. Lihat peminjaman status "Disetujui" yang belum dikembalikan
4. Click "Konfirmasi" button
5. Verifikasi dialog confirm
6. ✅ Expected: Status → "Dikembalikan"
7. ✅ Expected: Stok bertambah kembali
8. ✅ Expected: Tanggal kembali tercatat
```

### Test Case 6: Admin Aktivasi Akun Baru
```
1. User baru registrasi di auth/register.php
2. Admin buka admin/aktivasi.php
3. ✅ Expected: User baru muncul di daftar "Menunggu Aktivasi"
4. Admin search user by nama atau NIM
5. Admin click "Aktifkan Akun"
6. ✅ Expected: is_active = 1, user bisa login
```

### Test Case 7: Stock Reduction Reliability
```
1. Barang "Osciloscope" stok = 50
2. User 1 pinjam 10 pcs
3. Admin approve
4. ✅ Expected: Database stok = 40
5. User 2 buka form peminjaman
6. ✅ Expected: Lihat stok = 40 (bukan 50)
7. Jika stok = 0, barang tidak ditampilkan di form
```

---

## Catatan Penting

### Security
- ✅ Semua input menggunakan prepared statements / escape
- ✅ Password di-hash dengan PHP password_hash()
- ✅ Session check untuk authorization
- ✅ CSRF protection (add if needed)
- ✅ Confirm dialog sebelum delete/approve

### Performance
- ✅ Sidebar component centralized (reduce code duplication)
- ✅ Cache busting untuk fresh API data
- ✅ Transaction-based approval untuk data consistency
- ✅ Badge counter polling setiap 5 detik (efficient)

### Maintenance
- ✅ Dokumentasi lengkap di file ini
- ✅ Testing guide untuk QA
- ✅ Verify system script untuk troubleshooting
- ✅ Error logging di mailer system

---

## File Structure (Important Files)

```
/admin
  ├── aktivasi.php                 # Aktivasi akun
  ├── peminjaman.php              # Approval peminjaman (send email)
  ├── pengembalian.php            # Konfirmasi pengembalian
  ├── history_pengembalian.php    # History pengembalian
  ├── test_mailer.php             # Test email configuration
  └── ... (other admin pages)

/auth
  ├── login.php                    # Check is_active status
  └── register.php                 # Set is_active = 0

/config
  ├── database.php                 # Database connection
  ├── mailer.php                   # Mailer functions
  ├── mailer_config.php            # SMTP configuration
  ├── get_barang.php               # API untuk barang (cache busting)
  └── get_admin_unread_notifications.php

/pages
  ├── peminjaman-content.php       # Form peminjaman (create notification)
  ├── notifikasi-content.php       # User notifications
  └── ...

/assets
  └── admin_sidebar.php            # Centralized sidebar component

/database
  ├── aplikasi_labor.sql          # Database schema
  └── (migration files if any)
```

---

## Contact & Support

Untuk pertanyaan lebih lanjut atau bantuan teknis, silakan hubungi tim development.

**Last Updated:** 2025  
**Version:** Update V3  
**Status:** Production Ready ✅

---

*Dokumentasi ini menggabungkan semua fitur dan dokumentasi teknis sistem peminjaman peralatan laboratorium.*
