<?php
// Konfigurasi SMTP untuk PHPMailer
return [
  // Set true jika ingin menggunakan PHPMailer+SMTP, false untuk fallback mail()
  'use_smtp' => false,
  'smtp_host' => 'smtp.example.com',
  'smtp_port' => 587,
  'smtp_secure' => 'tls', // 'tls' or 'ssl' or ''
  'smtp_user' => 'user@example.com',
  'smtp_pass' => 'password',
  'from_email' => 'no-reply@example.com',
  'from_name' => 'Sistem Peminjaman'
];
