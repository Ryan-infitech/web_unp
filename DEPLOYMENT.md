# Panduan Deployment: Sistem Peminjaman Peralatan Laboratorium

## Deployment ke VPS Fresh (Ubuntu 22.04 / 24.04 LTS)

Panduan ini mencakup seluruh langkah dari VPS kosong hingga aplikasi berjalan dengan HTTPS.

---

## Daftar Isi

1. [Persyaratan Server](#1-persyaratan-server)
2. [Setup Awal VPS](#2-setup-awal-vps)
3. [Install LEMP Stack](#3-install-lemp-stack-nginx--mariadb--php-82)
4. [Konfigurasi MariaDB](#4-konfigurasi-mariadb)
5. [Deploy Kode Aplikasi](#5-deploy-kode-aplikasi)
6. [Konfigurasi Nginx](#6-konfigurasi-nginx)
7. [Konfigurasi PHP-FPM](#7-konfigurasi-php-fpm)
8. [Konfigurasi Aplikasi](#8-konfigurasi-aplikasi)
9. [File Permission & Security Hardening](#9-file-permission--security-hardening)
10. [SSL/HTTPS dengan Let's Encrypt](#10-sslhttps-dengan-lets-encrypt)
11. [Setup Email (PHPMailer + SMTP)](#11-setup-email-phpmailer--smtp)
12. [Monitoring & Maintenance](#12-monitoring--maintenance)
13. [Troubleshooting](#13-troubleshooting)
14. [Checklist Final](#14-checklist-final)

---

## 1. Persyaratan Server

### Spesifikasi Minimum VPS

| Komponen | Minimum | Rekomendasi |
|----------|---------|-------------|
| CPU | 1 vCPU | 2 vCPU |
| RAM | 1 GB | 2 GB |
| Storage | 10 GB SSD | 20 GB SSD |
| OS | Ubuntu 22.04 LTS | Ubuntu 24.04 LTS |
| Bandwidth | 1 TB/bulan | Unlimited |

### Software Stack

| Software | Versi | Fungsi |
|----------|-------|--------|
| **Nginx** | 1.18+ | Web server & reverse proxy |
| **PHP** | 8.2+ | Runtime aplikasi |
| **MariaDB** | 10.6+ | Database server |
| **Certbot** | Latest | SSL certificate (Let's Encrypt) |
| **Composer** | 2.x | PHP dependency manager |
| **Git** | 2.x | Version control |

### PHP Extensions yang Dibutuhkan

| Extension | Fungsi di Aplikasi |
|-----------|-------------------|
| `php-mysqli` | Semua operasi database |
| `php-json` | API response, face embeddings |
| `php-session` | Autentikasi user/admin |
| `php-mbstring` | String handling, PHPMailer |
| `php-openssl` | Koneksi SMTP TLS/SSL |
| `php-fileinfo` | Validasi file upload |
| `php-gd` | Manipulasi gambar (opsional) |
| `php-curl` | HTTP client (opsional) |
| `php-xml` | Dependency PHPMailer |

### Domain & DNS

- Siapkan domain/subdomain (contoh: `labor.kampus.ac.id`)
- Arahkan A Record ke IP VPS
- **HTTPS wajib** — browser memblokir akses kamera (`getUserMedia`) tanpa HTTPS, fitur face recognition tidak akan berfungsi di HTTP

---

## 2. Setup Awal VPS

### 2.1 Login & Update Sistem

```bash
# Login ke VPS
ssh root@IP_VPS_ANDA

# Update sistem
apt update && apt upgrade -y

# Install paket dasar
apt install -y curl wget git unzip software-properties-common ufw fail2ban
```

### 2.2 Buat User Non-Root

```bash
# Buat user deploy
adduser deploy
usermod -aG sudo deploy

# Setup SSH key untuk user deploy
mkdir -p /home/deploy/.ssh
cp ~/.ssh/authorized_keys /home/deploy/.ssh/
chown -R deploy:deploy /home/deploy/.ssh
chmod 700 /home/deploy/.ssh
chmod 600 /home/deploy/.ssh/authorized_keys
```

### 2.3 Konfigurasi Firewall (UFW)

```bash
# Reset dan setup UFW
ufw default deny incoming
ufw default allow outgoing

# Izinkan SSH, HTTP, HTTPS
ufw allow 22/tcp
ufw allow 80/tcp
ufw allow 443/tcp

# Aktifkan firewall
ufw enable
ufw status verbose
```

### 2.4 Konfigurasi Fail2Ban

```bash
# Buat konfigurasi lokal
cp /etc/fail2ban/jail.conf /etc/fail2ban/jail.local
```

Edit `/etc/fail2ban/jail.local`:

```ini
[DEFAULT]
bantime  = 3600
findtime = 600
maxretry = 5

[sshd]
enabled = true
port    = 22
logpath = /var/log/auth.log
maxretry = 3

[nginx-http-auth]
enabled = true
port    = http,https
logpath = /var/log/nginx/error.log

[nginx-botsearch]
enabled = true
port    = http,https
logpath = /var/log/nginx/access.log
```

```bash
systemctl restart fail2ban
systemctl enable fail2ban
```

### 2.5 Set Timezone

```bash
timedatezone set-timezone Asia/Jakarta
# Atau sesuaikan:
# timedatectl set-timezone Asia/Makassar
```

---

## 3. Install LEMP Stack (Nginx + MariaDB + PHP 8.2)

### 3.1 Install Nginx

```bash
apt install -y nginx

# Verifikasi
nginx -v
systemctl enable nginx
systemctl start nginx
```

### 3.2 Install MariaDB

```bash
apt install -y mariadb-server mariadb-client

# Verifikasi
mariadb --version
systemctl enable mariadb
systemctl start mariadb

# Amankan instalasi
mysql_secure_installation
```

Jawab pertanyaan `mysql_secure_installation`:
```
Enter current password for root: [ENTER - kosong]
Switch to unix_socket authentication? [Y]
Change the root password? [Y] → masukkan password baru yang kuat
Remove anonymous users? [Y]
Disallow root login remotely? [Y]
Remove test database? [Y]
Reload privilege tables? [Y]
```

### 3.3 Install PHP 8.2 + Extensions

```bash
# Tambahkan repository PHP (jika Ubuntu 22.04)
add-apt-repository ppa:ondrej/php -y
apt update

# Install PHP 8.2 + semua extension yang dibutuhkan
apt install -y \
    php8.2-fpm \
    php8.2-mysqli \
    php8.2-mbstring \
    php8.2-xml \
    php8.2-curl \
    php8.2-gd \
    php8.2-zip \
    php8.2-intl \
    php8.2-fileinfo \
    php8.2-opcache

# Verifikasi
php -v
php -m | grep -E "mysqli|mbstring|json|openssl|fileinfo|gd|session"

# Enable & start PHP-FPM
systemctl enable php8.2-fpm
systemctl start php8.2-fpm
```

### 3.4 Install Composer

```bash
curl -sS https://getcomposer.org/installer | php
mv composer.phar /usr/local/bin/composer
composer --version
```

---

## 4. Konfigurasi MariaDB

### 4.1 Buat Database & User

```bash
mysql -u root -p
```

```sql
-- Buat database
CREATE DATABASE aplikasi_labor CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

-- Buat user khusus aplikasi (JANGAN gunakan root!)
CREATE USER 'labor_app'@'localhost' IDENTIFIED BY 'GantiDenganPasswordKuatAnda!2026';

-- Berikan privilege hanya yang dibutuhkan
GRANT SELECT, INSERT, UPDATE, DELETE, CREATE, ALTER, INDEX, DROP
ON aplikasi_labor.* TO 'labor_app'@'localhost';

FLUSH PRIVILEGES;
EXIT;
```

### 4.2 Import Schema Database

```bash
# Upload file SQL ke VPS terlebih dahulu, lalu:
mysql -u labor_app -p aplikasi_labor < "/path/to/aplikasi_labor (6).sql"
```

### 4.3 Verifikasi Import

```bash
mysql -u labor_app -p aplikasi_labor -e "SHOW TABLES;"
```

Output yang diharapkan:
```
+---------------------------+
| Tables_in_aplikasi_labor  |
+---------------------------+
| admin                     |
| attendance_logs           |
| barang_labor              |
| face_embeddings           |
| info_dashboard            |
| labor                     |
| notification_logs         |
| notifications             |
| peminjaman                |
| users                     |
+---------------------------+
```

### 4.4 Konfigurasi MariaDB untuk Production

Edit `/etc/mysql/mariadb.conf.d/50-server.cnf`:

```ini
[mysqld]
# === Keamanan ===
bind-address            = 127.0.0.1
local-infile            = 0
skip-symbolic-links     = yes

# === Performance (sesuaikan dengan RAM VPS) ===
# Untuk VPS 1GB RAM:
innodb_buffer_pool_size = 256M
innodb_log_file_size    = 64M
innodb_flush_log_at_trx_commit = 2

# Untuk VPS 2GB RAM:
# innodb_buffer_pool_size = 512M
# innodb_log_file_size    = 128M

# === Koneksi ===
max_connections         = 50
wait_timeout            = 300
interactive_timeout     = 300

# === Query Cache ===
query_cache_type        = 1
query_cache_size        = 32M
query_cache_limit       = 2M

# === Logging ===
slow_query_log          = 1
slow_query_log_file     = /var/log/mysql/slow-query.log
long_query_time         = 2

# === Charset ===
character-set-server    = utf8mb4
collation-server        = utf8mb4_general_ci
```

```bash
systemctl restart mariadb
```

---

## 5. Deploy Kode Aplikasi

### 5.1 Buat Direktori Aplikasi

```bash
# Buat direktori web
mkdir -p /var/www/labor
chown deploy:www-data /var/www/labor
```

### 5.2 Clone Repository

```bash
# Login sebagai user deploy
su - deploy

# Clone dari GitHub
cd /var/www/labor
git clone https://github.com/Ryan-infitech/web_unp.git .

# Atau upload manual via SCP:
# scp -r /path/to/local/web_unp/* deploy@IP_VPS:/var/www/labor/
```

### 5.3 Struktur Direktori Hasil Deploy

```
/var/www/labor/
├── index.php                  ← Entry point
├── admin/                     ← Panel admin
│   ├── face-recognition/      ← Modul face recognition
│   │   └── models/            ← Model ML (~12MB, static files)
│   ├── dashboard.php
│   ├── login.php
│   └── ...
├── assets/
│   ├── img/                   ← Upload foto profil user
│   ├── uploads/labor/         ← Upload gambar laboratorium
│   ├── sidebar.php
│   └── admin_sidebar.php
├── auth/                      ← Login/register user
├── config/
│   ├── database.php           ← ⚠ HARUS DIEDIT
│   └── mailer_config.php      ← ⚠ HARUS DIEDIT (jika pakai SMTP)
├── database/
│   └── aplikasi_labor (6).sql ← SQL dump
├── pages/                     ← Halaman user
├── peminjaman/                ← Form peminjaman
└── user/                      ← Profil user
```

---

## 6. Konfigurasi Nginx

### 6.1 Buat Server Block

Buat file `/etc/nginx/sites-available/labor`:

```nginx
server {
    listen 80;
    listen [::]:80;
    
    # Ganti dengan domain Anda
    server_name labor.kampus.ac.id www.labor.kampus.ac.id;
    
    # Document root
    root /var/www/labor;
    index index.php index.html;
    
    # Logging
    access_log /var/log/nginx/labor-access.log;
    error_log  /var/log/nginx/labor-error.log warn;
    
    # === Security Headers ===
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header Referrer-Policy "strict-origin-when-cross-origin" always;
    add_header Permissions-Policy "camera=(self), microphone=()" always;
    
    # === Upload size (untuk foto profil & gambar labor) ===
    client_max_body_size 10M;
    
    # === Main location ===
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
    
    # === PHP Processing ===
    location ~ \.php$ {
        include snippets/fastcgi-params.conf;
        fastcgi_pass unix:/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
        
        # Timeout untuk operasi yang lama (email sending, face compare)
        fastcgi_read_timeout 120;
        fastcgi_send_timeout 120;
    }
    
    # === Blokir akses langsung ke file konfigurasi ===
    location ~ ^/config/ {
        # Izinkan hanya file yang dipanggil via AJAX (harus melalui PHP)
        location ~ ^/config/(get_barang|get_unread_notifications|get_admin_unread_notifications|get_admin_unread_notifications_detail|get_admin_info|mark_admin_notification_read|dashboard_ajax)\.php$ {
            include snippets/fastcgi-params.conf;
            fastcgi_pass unix:/run/php/php8.2-fpm.sock;
            fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
            include fastcgi_params;
        }
        # Blokir semua file lain di config/
        deny all;
        return 403;
    }
    
    # === Blokir akses ke file sensitif ===
    location ~ ^/database/ {
        deny all;
        return 403;
    }
    
    location ~ ^/verify_system\.php$ {
        deny all;
        return 403;
    }
    
    location ~ /\.ht {
        deny all;
    }
    
    location ~ /\.git {
        deny all;
    }
    
    # === Blokir eksekusi PHP di folder upload ===
    location ~ ^/assets/img/.*\.php$ {
        deny all;
        return 403;
    }
    
    location ~ ^/assets/uploads/.*\.php$ {
        deny all;
        return 403;
    }
    
    # === Cache static files ===
    location ~* \.(jpg|jpeg|png|gif|ico|css|js|woff|woff2|ttf|svg|eot)$ {
        expires 30d;
        add_header Cache-Control "public, immutable";
        access_log off;
    }
    
    # === Face recognition models (static files, enable caching) ===
    location ~ ^/admin/face-recognition/models/ {
        expires 7d;
        add_header Cache-Control "public";
        access_log off;
    }
    
    # === Gzip Compression ===
    gzip on;
    gzip_vary on;
    gzip_proxied any;
    gzip_comp_level 6;
    gzip_types
        text/plain
        text/css
        text/javascript
        application/json
        application/javascript
        application/xml
        font/woff2;
    gzip_min_length 1024;
}
```

### 6.2 Aktifkan Site

```bash
# Buat symlink
ln -s /etc/nginx/sites-available/labor /etc/nginx/sites-enabled/

# Hapus default site (opsional)
rm -f /etc/nginx/sites-enabled/default

# Test konfigurasi
nginx -t

# Reload Nginx
systemctl reload nginx
```

### 6.3 Konfigurasi Nginx Global (Opsional)

Edit `/etc/nginx/nginx.conf`:

```nginx
user www-data;
worker_processes auto;
pid /run/nginx.pid;

events {
    worker_connections 1024;
    multi_accept on;
}

http {
    # === Dasar ===
    sendfile on;
    tcp_nopush on;
    tcp_nodelay on;
    keepalive_timeout 65;
    types_hash_max_size 2048;
    server_tokens off;  # Sembunyikan versi Nginx
    
    include /etc/nginx/mime.types;
    default_type application/octet-stream;
    
    # === Logging ===
    access_log /var/log/nginx/access.log;
    error_log /var/log/nginx/error.log;
    
    # === Include sites ===
    include /etc/nginx/conf.d/*.conf;
    include /etc/nginx/sites-enabled/*;
}
```

---

## 7. Konfigurasi PHP-FPM

### 7.1 PHP.ini untuk Production

Edit `/etc/php/8.2/fpm/php.ini`:

```ini
; ============================================
; PHP PRODUCTION CONFIGURATION
; ============================================

; === Error Handling ===
display_errors = Off
display_startup_errors = Off
log_errors = On
error_log = /var/log/php/error.log
error_reporting = E_ALL & ~E_DEPRECATED & ~E_STRICT

; === Upload ===
upload_max_filesize = 5M
post_max_size = 10M
max_file_uploads = 5

; === Memory & Execution ===
memory_limit = 256M
max_execution_time = 120
max_input_time = 60
max_input_vars = 3000

; === Session Security ===
session.cookie_httponly = 1
session.cookie_secure = 1
session.use_strict_mode = 1
session.use_only_cookies = 1
session.cookie_samesite = "Lax"
session.name = "LABOR_SESSID"
session.gc_maxlifetime = 3600
session.sid_length = 48
session.sid_bits_per_character = 6

; === Timezone ===
date.timezone = Asia/Jakarta

; === OPcache (Performance) ===
opcache.enable = 1
opcache.memory_consumption = 128
opcache.interned_strings_buffer = 16
opcache.max_accelerated_files = 10000
opcache.revalidate_freq = 60
opcache.validate_timestamps = 1

; === Security ===
expose_php = Off
allow_url_fopen = On
allow_url_include = Off
open_basedir = /var/www/labor:/tmp:/usr/share/php
disable_functions = exec,passthru,shell_exec,system,proc_open,popen,parse_ini_file,show_source
```

### 7.2 PHP-FPM Pool Configuration

Edit `/etc/php/8.2/fpm/pool.d/www.conf`:

```ini
[www]
; === User & Group ===
user = www-data
group = www-data
listen.owner = www-data
listen.group = www-data

; === Socket ===
listen = /run/php/php8.2-fpm.sock

; === Process Management ===
; Untuk VPS 1GB RAM:
pm = dynamic
pm.max_children = 10
pm.start_servers = 3
pm.min_spare_servers = 2
pm.max_spare_servers = 5
pm.max_requests = 500

; Untuk VPS 2GB RAM:
; pm.max_children = 20
; pm.start_servers = 5
; pm.min_spare_servers = 3
; pm.max_spare_servers = 10

; === Logging ===
php_admin_flag[log_errors] = on
php_admin_value[error_log] = /var/log/php/fpm-error.log

; === Security ===
security.limit_extensions = .php
php_admin_value[open_basedir] = /var/www/labor:/tmp:/usr/share/php
```

### 7.3 Buat Direktori Log & Restart

```bash
mkdir -p /var/log/php
chown www-data:www-data /var/log/php

systemctl restart php8.2-fpm
systemctl status php8.2-fpm
```

---

## 8. Konfigurasi Aplikasi

### 8.1 Database Connection

Edit `/var/www/labor/config/database.php`:

```php
<?php
$db_host = getenv('DB_HOST') ?: 'localhost';
$db_user = getenv('DB_USER') ?: 'labor_app';
$db_pass = getenv('DB_PASS') ?: 'GantiDenganPasswordKuatAnda!2026';
$db_name = getenv('DB_NAME') ?: 'aplikasi_labor';

$conn = mysqli_connect($db_host, $db_user, $db_pass, $db_name);
if(!$conn){
    error_log("Database connection failed: " . mysqli_connect_error());
    die("Sistem sedang mengalami gangguan. Silakan coba beberapa saat lagi.");
}

// Set charset UTF-8
mysqli_set_charset($conn, "utf8mb4");
?>
```

### 8.2 Email/Mailer Configuration (Opsional)

Jika ingin mengaktifkan fitur email notifikasi, edit `/var/www/labor/config/mailer_config.php`:

```php
<?php
return [
    // === SMTP Configuration ===
    'use_smtp' => true,

    // --- Opsi 1: Gmail SMTP ---
    'smtp_host' => 'smtp.gmail.com',
    'smtp_port' => 587,
    'smtp_secure' => 'tls',
    'smtp_user' => 'email-kampus@gmail.com',
    'smtp_pass' => 'xxxx xxxx xxxx xxxx',  // App Password (bukan password biasa!)
    // Untuk Gmail: Buka https://myaccount.google.com/apppasswords

    // --- Opsi 2: Mailgun/SendGrid/Custom SMTP ---
    // 'smtp_host' => 'smtp.mailgun.org',
    // 'smtp_port' => 587,
    // 'smtp_secure' => 'tls',
    // 'smtp_user' => 'postmaster@mg.kampus.ac.id',
    // 'smtp_pass' => 'your-api-key',

    // === From Address ===
    'from_email' => 'noreply@kampus.ac.id',
    'from_name'  => 'Sistem Peminjaman Labor'
];
```

### 8.3 Install PHPMailer (Jika Pakai SMTP)

```bash
cd /var/www/labor

# Install composer dependencies
composer require phpmailer/phpmailer

# Pastikan vendor/ accessible
chown -R deploy:www-data vendor/
```

### 8.4 Buat Admin Account Awal

Jika database di-import dari SQL dump, sudah ada akun admin default. Jika ingin buat baru:

```bash
# Buat password hash
php -r "echo password_hash('PasswordAdminKuat!2026', PASSWORD_DEFAULT) . PHP_EOL;"
```

```sql
-- Di MariaDB
USE aplikasi_labor;
INSERT INTO admin (nama, username, password)
VALUES ('Administrator', 'admin', '$2y$10$HASH_DARI_PERINTAH_DI_ATAS');
```

---

## 9. File Permission & Security Hardening

### 9.1 Set Ownership & Permission

```bash
# Set ownership
chown -R deploy:www-data /var/www/labor

# Set permission default (read-only untuk web server)
find /var/www/labor -type f -exec chmod 644 {} \;
find /var/www/labor -type d -exec chmod 755 {} \;

# Direktori upload — writable oleh web server
chmod 775 /var/www/labor/assets/img
chmod 775 /var/www/labor/assets/uploads
chmod 775 /var/www/labor/assets/uploads/labor

# Proteksi file konfigurasi — read-only
chmod 640 /var/www/labor/config/database.php
chmod 640 /var/www/labor/config/mailer_config.php
chown deploy:www-data /var/www/labor/config/database.php
chown deploy:www-data /var/www/labor/config/mailer_config.php

# Proteksi file SQL dump
chmod 600 /var/www/labor/database/*.sql
```

### 9.2 Cegah Eksekusi PHP di Folder Upload

Buat file `/var/www/labor/assets/uploads/.user.ini`:
```ini
; Disable PHP execution in uploads directory
engine = off
```

Buat file `/var/www/labor/assets/img/.user.ini`:
```ini
; Disable PHP execution in uploads directory
engine = off
```

### 9.3 Buat .htaccess Backup (Jika Pakai Apache)

Meskipun menggunakan Nginx, buat `.htaccess` sebagai backup jika ada migrasi:

Buat file `/var/www/labor/.htaccess`:
```apache
# Prevent directory listing
Options -Indexes

# Protect sensitive files
<FilesMatch "\.(sql|md|git|env|log)$">
    Require all denied
</FilesMatch>

# Block access to config directory
<Directory "config">
    <Files "database.php">
        Require all denied
    </Files>
    <Files "mailer_config.php">
        Require all denied
    </Files>
</Directory>
```

### 9.4 Hapus File Sensitif dari Production

```bash
# Hapus file yang tidak diperlukan di production
rm -f /var/www/labor/verify_system.php
rm -f /var/www/labor/admin/test_mailer.php

# Hapus SQL dump dari web root (simpan di tempat lain)
mv /var/www/labor/database/ /home/deploy/backups/initial-schema/

# Hapus file README dan REVIEW dari public
rm -f /var/www/labor/README.md
rm -f /var/www/labor/REVIEW.md
rm -f /var/www/labor/DEPLOYMENT.md
```

### 9.5 Proteksi Admin Registration

**KRITIS:** Secara default, `/admin/register.php` bisa diakses siapa saja. Opsi proteksi:

**Opsi A — Hapus file:**
```bash
rm /var/www/labor/admin/register.php
```

**Opsi B — Blokir di Nginx:**
Tambahkan di server block Nginx:
```nginx
location = /admin/register.php {
    deny all;
    return 403;
}
```

**Opsi C — Proteksi dengan auth check (edit file):**
```php
// Di awal admin/register.php, tambahkan:
session_start();
if(!isset($_SESSION['admin_id'])){
    header("HTTP/1.1 403 Forbidden");
    exit("Akses ditolak");
}
```

---

## 10. SSL/HTTPS dengan Let's Encrypt

### 10.1 Install Certbot

```bash
apt install -y certbot python3-certbot-nginx
```

### 10.2 Dapatkan SSL Certificate

```bash
# Pastikan domain sudah pointing ke IP VPS
certbot --nginx -d labor.kampus.ac.id -d www.labor.kampus.ac.id
```

Jawab pertanyaan:
```
Email: admin@kampus.ac.id
Agree to terms: Y
Share email with EFF: N
Redirect HTTP to HTTPS: 2 (Yes, redirect)
```

### 10.3 Konfigurasi Nginx Setelah SSL (Otomatis oleh Certbot)

Certbot akan memodifikasi server block secara otomatis. Verifikasi hasilnya di `/etc/nginx/sites-available/labor`:

```nginx
server {
    listen 80;
    server_name labor.kampus.ac.id www.labor.kampus.ac.id;
    return 301 https://$host$request_uri;
}

server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    
    server_name labor.kampus.ac.id www.labor.kampus.ac.id;
    
    # SSL Certificate (dikelola Certbot)
    ssl_certificate /etc/letsencrypt/live/labor.kampus.ac.id/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/labor.kampus.ac.id/privkey.pem;
    include /etc/letsencrypt/options-ssl-nginx.conf;
    ssl_dhparam /etc/letsencrypt/ssl-dhparams.pem;
    
    # === Tambahkan HSTS ===
    add_header Strict-Transport-Security "max-age=31536000; includeSubDomains" always;
    
    # ... (sisa konfigurasi sama seperti Section 6) ...
    
    root /var/www/labor;
    index index.php index.html;
    
    # ... dst
}
```

### 10.4 Auto-Renewal Certificate

```bash
# Test renewal
certbot renew --dry-run

# Certbot sudah otomatis menambahkan cron/systemd timer
# Verifikasi:
systemctl list-timers | grep certbot
```

### 10.5 Tambahkan SSL Hardening

Edit `/etc/nginx/sites-available/labor`, di blok `server` HTTPS:

```nginx
# === SSL Hardening ===
ssl_protocols TLSv1.2 TLSv1.3;
ssl_prefer_server_ciphers on;
ssl_ciphers ECDHE-ECDSA-AES128-GCM-SHA256:ECDHE-RSA-AES128-GCM-SHA256:ECDHE-ECDSA-AES256-GCM-SHA384:ECDHE-RSA-AES256-GCM-SHA384;
ssl_session_timeout 1d;
ssl_session_cache shared:SSL:10m;
ssl_session_tickets off;
ssl_stapling on;
ssl_stapling_verify on;
resolver 8.8.8.8 8.8.4.4 valid=300s;
```

```bash
nginx -t && systemctl reload nginx
```

---

## 11. Setup Email (PHPMailer + SMTP)

### 11.1 Opsi A: Gmail SMTP (Paling Mudah)

1. **Aktifkan 2-Factor Authentication** di Google Account
2. **Buat App Password:**
   - Buka https://myaccount.google.com/apppasswords
   - Pilih "Mail" → "Other" → nama: "Labor App"
   - Copy 16-char password yang dihasilkan

3. **Update `config/mailer_config.php`:**
```php
<?php
return [
    'use_smtp' => true,
    'smtp_host' => 'smtp.gmail.com',
    'smtp_port' => 587,
    'smtp_secure' => 'tls',
    'smtp_user' => 'email-kampus@gmail.com',
    'smtp_pass' => 'abcd efgh ijkl mnop',  // App Password
    'from_email' => 'email-kampus@gmail.com',
    'from_name' => 'Sistem Peminjaman Labor'
];
```

> **Catatan:** Gmail memiliki limit 500 email/hari. Cukup untuk aplikasi skala kampus.

### 11.2 Opsi B: Mailgun (Untuk Volume Lebih Besar)

```php
<?php
return [
    'use_smtp' => true,
    'smtp_host' => 'smtp.mailgun.org',
    'smtp_port' => 587,
    'smtp_secure' => 'tls',
    'smtp_user' => 'postmaster@mg.kampus.ac.id',
    'smtp_pass' => 'mailgun-api-key',
    'from_email' => 'noreply@kampus.ac.id',
    'from_name' => 'Sistem Peminjaman Labor'
];
```

### 11.3 Test Email

```bash
# Akses dari browser (setelah login admin):
# https://labor.kampus.ac.id/admin/test_mailer.php

# Atau test via CLI:
cd /var/www/labor
php -r "
include 'config/mailer.php';
\$result = send_html_mail('test@gmail.com', 'Test Email', '<h1>Email berhasil!</h1>');
echo \$result ? 'SUCCESS' : 'FAILED';
"
```

---

## 12. Monitoring & Maintenance

### 12.1 Setup Log Rotation

Buat file `/etc/logrotate.d/labor`:

```
/var/log/nginx/labor-*.log {
    daily
    missingok
    rotate 30
    compress
    delaycompress
    notifempty
    create 0640 www-data adm
    sharedscripts
    postrotate
        [ -f /var/run/nginx.pid ] && kill -USR1 $(cat /var/run/nginx.pid)
    endscript
}

/var/log/php/*.log {
    daily
    missingok
    rotate 14
    compress
    delaycompress
    notifempty
    create 0640 www-data adm
}
```

### 12.2 Script Backup Database

Buat file `/home/deploy/scripts/backup-db.sh`:

```bash
#!/bin/bash
# =============================================
# Database Backup Script
# =============================================

BACKUP_DIR="/home/deploy/backups/db"
DB_NAME="aplikasi_labor"
DB_USER="labor_app"
DB_PASS="GantiDenganPasswordKuatAnda!2026"
DATE=$(date +%Y%m%d_%H%M%S)
RETENTION_DAYS=30

# Buat direktori backup
mkdir -p "$BACKUP_DIR"

# Dump database
mysqldump -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" | gzip > "$BACKUP_DIR/${DB_NAME}_${DATE}.sql.gz"

# Hapus backup lama
find "$BACKUP_DIR" -name "*.sql.gz" -mtime +$RETENTION_DAYS -delete

# Log
echo "[$(date)] Backup completed: ${DB_NAME}_${DATE}.sql.gz" >> /home/deploy/logs/backup.log
```

```bash
chmod +x /home/deploy/scripts/backup-db.sh
mkdir -p /home/deploy/logs

# Jadwalkan cron (setiap hari jam 02:00)
crontab -e
# Tambahkan baris:
0 2 * * * /home/deploy/scripts/backup-db.sh
```

### 12.3 Script Backup File Upload

Buat file `/home/deploy/scripts/backup-uploads.sh`:

```bash
#!/bin/bash
BACKUP_DIR="/home/deploy/backups/uploads"
DATE=$(date +%Y%m%d)

mkdir -p "$BACKUP_DIR"

tar -czf "$BACKUP_DIR/uploads_${DATE}.tar.gz" \
    /var/www/labor/assets/img/ \
    /var/www/labor/assets/uploads/

# Hapus backup > 30 hari
find "$BACKUP_DIR" -name "*.tar.gz" -mtime +30 -delete

echo "[$(date)] Upload backup completed" >> /home/deploy/logs/backup.log
```

```bash
chmod +x /home/deploy/scripts/backup-uploads.sh

# Jadwalkan cron (setiap minggu, Minggu jam 03:00)
crontab -e
# Tambahkan:
0 3 * * 0 /home/deploy/scripts/backup-uploads.sh
```

### 12.4 Monitoring Sederhana

Buat file `/home/deploy/scripts/healthcheck.sh`:

```bash
#!/bin/bash
# =============================================
# Health Check Script
# =============================================

DOMAIN="https://labor.kampus.ac.id"
ALERT_EMAIL="admin@kampus.ac.id"

# Check web server
HTTP_STATUS=$(curl -s -o /dev/null -w "%{http_code}" "$DOMAIN")
if [ "$HTTP_STATUS" != "200" ]; then
    echo "[$(date)] ALERT: Web server returned HTTP $HTTP_STATUS" >> /home/deploy/logs/healthcheck.log
    # Kirim alert (jika mail CLI tersedia)
    # echo "Web server down! HTTP $HTTP_STATUS" | mail -s "ALERT: Labor App Down" "$ALERT_EMAIL"
fi

# Check disk usage
DISK_USAGE=$(df -h / | awk 'NR==2{print $5}' | sed 's/%//')
if [ "$DISK_USAGE" -gt 85 ]; then
    echo "[$(date)] WARNING: Disk usage at ${DISK_USAGE}%" >> /home/deploy/logs/healthcheck.log
fi

# Check MariaDB
if ! systemctl is-active --quiet mariadb; then
    echo "[$(date)] ALERT: MariaDB is down! Attempting restart..." >> /home/deploy/logs/healthcheck.log
    systemctl restart mariadb
fi

# Check PHP-FPM
if ! systemctl is-active --quiet php8.2-fpm; then
    echo "[$(date)] ALERT: PHP-FPM is down! Attempting restart..." >> /home/deploy/logs/healthcheck.log
    systemctl restart php8.2-fpm
fi

# Check Nginx
if ! systemctl is-active --quiet nginx; then
    echo "[$(date)] ALERT: Nginx is down! Attempting restart..." >> /home/deploy/logs/healthcheck.log
    systemctl restart nginx
fi
```

```bash
chmod +x /home/deploy/scripts/healthcheck.sh

# Jalankan setiap 5 menit
crontab -e
# Tambahkan:
*/5 * * * * /home/deploy/scripts/healthcheck.sh
```

### 12.5 Update Aplikasi (Deployment Ulang)

```bash
# Login sebagai deploy
su - deploy
cd /var/www/labor

# Pull perubahan terbaru
git pull origin main

# Set ulang permission
find . -type f -exec chmod 644 {} \;
find . -type d -exec chmod 755 {} \;
chmod 775 assets/img assets/uploads assets/uploads/labor
chmod 640 config/database.php config/mailer_config.php

# Clear OPcache
sudo systemctl reload php8.2-fpm

echo "Deploy selesai: $(date)"
```

---

## 13. Troubleshooting

### 13.1 502 Bad Gateway

```bash
# Cek PHP-FPM status
systemctl status php8.2-fpm
journalctl -u php8.2-fpm --no-pager -n 50

# Cek socket exists
ls -la /run/php/php8.2-fpm.sock

# Cek Nginx error log
tail -50 /var/log/nginx/labor-error.log

# Restart services
systemctl restart php8.2-fpm nginx
```

### 13.2 Permission Denied pada Upload

```bash
# Cek ownership
ls -la /var/www/labor/assets/img/
ls -la /var/www/labor/assets/uploads/labor/

# Fix
chown -R deploy:www-data /var/www/labor/assets/img
chown -R deploy:www-data /var/www/labor/assets/uploads
chmod 775 /var/www/labor/assets/img
chmod 775 /var/www/labor/assets/uploads/labor
```

### 13.3 Database Connection Error

```bash
# Test koneksi manual
mysql -u labor_app -p aplikasi_labor -e "SELECT 1;"

# Cek MariaDB running
systemctl status mariadb

# Cek log
tail -50 /var/log/mysql/error.log

# Cek config database.php
cat /var/www/labor/config/database.php
```

### 13.4 Face Recognition Tidak Berfungsi

```bash
# HTTPS wajib untuk akses kamera!
# Verifikasi:
curl -I https://labor.kampus.ac.id

# Cek model files bisa diakses
curl -I https://labor.kampus.ac.id/admin/face-recognition/models/ssd_mobilenetv1_model-weights_manifest.json

# Pastikan MIME type benar untuk model files
# Seharusnya Content-Type: application/json untuk .json files
```

### 13.5 Email Tidak Terkirim

```bash
# Cek PHP error log
tail -50 /var/log/php/error.log | grep -i mail

# Test koneksi SMTP
php -r "
\$fp = fsockopen('ssl://smtp.gmail.com', 465, \$errno, \$errstr, 10);
if(\$fp){ echo 'SMTP connection OK'; fclose(\$fp); }
else { echo 'FAILED: ' . \$errstr; }
"

# Pastikan openssl extension terinstall
php -m | grep openssl
```

### 13.6 Slow Performance

```bash
# Cek OPcache status
php -i | grep opcache

# Cek slow query log
tail -50 /var/log/mysql/slow-query.log

# Monitor real-time
htop
# atau
top -c

# Cek koneksi database yang aktif
mysql -u root -p -e "SHOW PROCESSLIST;"
```

---

## 14. Checklist Final

### Pre-Launch

```
[ ] VPS sudah di-update dan di-secure (UFW, Fail2Ban)
[ ] User non-root sudah dibuat
[ ] Nginx, PHP-FPM, MariaDB terinstall dan running
[ ] Database sudah dibuat dan schema di-import
[ ] User database khusus sudah dibuat (bukan root!)
[ ] config/database.php sudah diedit dengan credentials production
[ ] File permission sudah diatur dengan benar
[ ] Folder upload writable oleh www-data
[ ] PHP.ini sudah dikonfigurasi untuk production (display_errors=Off, dll)
[ ] SSL/HTTPS sudah aktif via Let's Encrypt
[ ] admin/register.php sudah diproteksi/dihapus
[ ] File sensitif dihapus dari web root (verify_system.php, test_mailer.php, database/*.sql)
```

### Post-Launch

```
[ ] Bisa akses halaman utama via HTTPS
[ ] Login user berfungsi
[ ] Login admin berfungsi
[ ] Upload foto profil berfungsi
[ ] Face recognition berfungsi (kamera bisa diakses)
[ ] Peminjaman bisa dibuat dan di-approve
[ ] Email notifikasi terkirim (jika dikonfigurasi)
[ ] Backup database terjadwal
[ ] Health check script berjalan
[ ] Log rotation aktif
[ ] Certificate auto-renewal berfungsi (certbot renew --dry-run)
```

### Ringkasan Semua File Konfigurasi

| File | Lokasi | Fungsi |
|------|--------|--------|
| Nginx server block | `/etc/nginx/sites-available/labor` | Web server config |
| PHP.ini | `/etc/php/8.2/fpm/php.ini` | PHP runtime config |
| PHP-FPM pool | `/etc/php/8.2/fpm/pool.d/www.conf` | FPM worker config |
| MariaDB config | `/etc/mysql/mariadb.conf.d/50-server.cnf` | Database server config |
| Fail2Ban | `/etc/fail2ban/jail.local` | Brute force protection |
| Log rotation | `/etc/logrotate.d/labor` | Log management |
| App DB config | `/var/www/labor/config/database.php` | Database credentials |
| App Mail config | `/var/www/labor/config/mailer_config.php` | SMTP settings |
| Backup DB script | `/home/deploy/scripts/backup-db.sh` | Cron: daily 02:00 |
| Backup uploads script | `/home/deploy/scripts/backup-uploads.sh` | Cron: weekly Sun 03:00 |
| Health check script | `/home/deploy/scripts/healthcheck.sh` | Cron: every 5 min |

---

## Arsitektur Deployment

```
┌─────────────────────────────────────────────────┐
│                    INTERNET                      │
│              (User / Admin Browser)              │
└──────────────────┬──────────────────────────────┘
                   │ HTTPS :443
                   ▼
┌─────────────────────────────────────────────────┐
│               UFW FIREWALL                       │
│          (Port 22, 80, 443 only)                │
└──────────────────┬──────────────────────────────┘
                   │
                   ▼
┌─────────────────────────────────────────────────┐
│            NGINX (Reverse Proxy)                 │
│  ┌─────────────────────────────────────────┐    │
│  │ SSL Termination (Let's Encrypt)         │    │
│  │ Static File Serving (CSS, JS, Images)   │    │
│  │ Gzip Compression                        │    │
│  │ Security Headers                        │    │
│  │ Upload Directory PHP Blocking           │    │
│  └──────────────┬──────────────────────────┘    │
└─────────────────┼───────────────────────────────┘
                  │ Unix Socket
                  ▼
┌─────────────────────────────────────────────────┐
│           PHP 8.2-FPM                            │
│  ┌─────────────────────────────────────────┐    │
│  │ OPcache Enabled                         │    │
│  │ Session Security (httponly, secure)      │    │
│  │ open_basedir Restriction                │    │
│  │ Dangerous Functions Disabled            │    │
│  └──────────────┬──────────────────────────┘    │
└─────────────────┼───────────────────────────────┘
                  │ TCP 127.0.0.1:3306
                  ▼
┌─────────────────────────────────────────────────┐
│           MariaDB 10.6+                          │
│  ┌─────────────────────────────────────────┐    │
│  │ Bind: 127.0.0.1 only                   │    │
│  │ Dedicated User (labor_app)              │    │
│  │ InnoDB Buffer Pool Optimized            │    │
│  │ Slow Query Logging                      │    │
│  │ Daily Backup via Cron                   │    │
│  └─────────────────────────────────────────┘    │
└─────────────────────────────────────────────────┘
```
