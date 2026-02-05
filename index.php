<?php
// Redirect jika sudah login
session_start();
if(isset($_SESSION['user_id'])){
  header("Location: pages/dashboard-content.php");
  exit;
}
if(isset($_SESSION['admin_id'])){
  header("Location: admin/dashboard.php");
  exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Aplikasi Labor - Sistem Peminjaman Peralatan</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
  * {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
  }
  
  body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    overflow-x: hidden;
    background: #f8f9fa;
    min-height: 100vh;
    display: flex;
    flex-direction: column;
    position: relative;
  }

  /* Animated Background */
  body::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-image: 
      radial-gradient(circle at 20% 50%, rgba(25, 55, 109, 0.05) 0%, transparent 50%),
      radial-gradient(circle at 80% 80%, rgba(255, 153, 0, 0.05) 0%, transparent 50%);
    animation: moveBackground 15s ease-in-out infinite;
    pointer-events: none;
  }

  @keyframes moveBackground {
    0%, 100% { transform: translate(0, 0); }
    50% { transform: translate(20px, 20px); }
  }

  /* Header */
  .navbar-header {
    background: linear-gradient(135deg, #193570 0%, #2c5aa0 100%);
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    padding: 12px 20px;
    top: 0;
    z-index: 1000;
    position: sticky;
  }

  .navbar-content {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 15px;
    max-width: 1200px;
    margin: 0 auto;
    width: 100%;
  }

  .logo-section {
    display: flex;
    align-items: center;
    gap: 12px;
    flex-shrink: 0;
  }

  .logo-image {
    width: 60px;
    height: 60px;
    background: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 6px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.15);
    flex-shrink: 0;
  }

  .logo-image img {
    width: 100%;
    height: 100%;
    object-fit: contain;
  }

  .logo-text h3 {
    color: white;
    margin: 0;
    font-size: 16px;
    font-weight: 700;
  }

  .logo-text p {
    color: rgba(255, 255, 255, 0.8);
    margin: 0;
    font-size: 11px;
  }

  .header-info {
    display: flex;
    align-items: center;
    gap: 20px;
    flex: 1;
  }

  .header-divider {
    width: 1px;
    height: 35px;
    background: rgba(255, 255, 255, 0.2);
  }

  .info-section {
    display: flex;
    align-items: center;
    gap: 8px;
    color: white;
    white-space: nowrap;
  }

  .info-section i {
    font-size: 14px;
    color: #ff9900;
    flex-shrink: 0;
  }

  .info-section-content {
    display: flex;
    flex-direction: column;
    gap: 1px;
  }

  .info-label {
    font-size: 9px;
    color: rgba(255, 255, 255, 0.6);
    text-transform: uppercase;
    font-weight: 600;
    letter-spacing: 0.4px;
  }

  .info-value {
    font-size: 12px;
    color: white;
    font-weight: 600;
  }

  .header-spacer {
    flex: 1;
  }

  .header-quote {
    text-align: right;
    color: rgba(255, 255, 255, 0.85);
    font-size: 11px;
    font-style: italic;
    line-height: 1.3;
    flex-shrink: 0;
  }

  .main-content {
    flex: 1;
    position: relative;
    z-index: 1;
  }

  /* Hero Section */
  .hero-section {
    text-align: center;
    color: #193570;
    margin-bottom: 60px;
    animation: fadeInDown 0.8s ease-out;
    padding: 60px 20px;
  }

  .hero-section h1 {
    font-size: 48px;
    font-weight: 800;
    margin-bottom: 15px;
    background: linear-gradient(135deg, #193570 0%, #ff9900 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    animation: slideDown 0.8s ease-out;
  }

  .hero-section .subtitle {
    font-size: 20px;
    color: #193570;
    margin-bottom: 10px;
    animation: slideDown 1s ease-out;
    font-weight: 600;
  }

  .hero-section .description {
    font-size: 16px;
    color: #555;
    max-width: 600px;
    margin: 0 auto;
    animation: slideDown 1.2s ease-out;
    line-height: 1.6;
  }

  .hero-icon {
    font-size: 80px;
    margin-bottom: 20px;
    animation: bounce 2s ease-in-out infinite;
    color: #ff9900;
  }

  .container {
    position: relative;
    z-index: 1;
    max-width: 1200px;
    padding: 20px;
    margin: 0 auto;
    flex: 1;
  }

  /* Portal Cards Grid */
  .portal-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 40px;
    margin-top: 40px;
  }

  .portal-card {
    background: white;
    border-radius: 15px;
    padding: 0;
    overflow: hidden;
    box-shadow: 0 8px 30px rgba(0, 0, 0, 0.1);
    transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    cursor: pointer;
    position: relative;
    animation: fadeInUp 0.8s ease-out;
    animation-fill-mode: both;
    border: 2px solid transparent;
  }

  .portal-card:nth-child(1) { 
    animation-delay: 0.2s; 
  }

  .portal-card:nth-child(2) { 
    animation-delay: 0.4s; 
  }

  .portal-card:hover {
    transform: translateY(-15px) scale(1.02);
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
    border-color: currentColor;
  }

  .portal-header {
    height: 200px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    position: relative;
    overflow: hidden;
  }

  .portal-card.mahasiswa .portal-header {
    background: linear-gradient(135deg, #193570 0%, #2c5aa0 100%);
  }

  .portal-card.admin .portal-header {
    background: linear-gradient(135deg, #ff9900 0%, #ffa500 100%);
  }

  .portal-header::before {
    content: '';
    position: absolute;
    top: -50%;
    right: -50%;
    width: 200%;
    height: 200%;
    background: rgba(255, 255, 255, 0.15);
    border-radius: 50%;
    transition: all 0.5s ease;
  }

  .portal-card:hover .portal-header::before {
    top: -10%;
    right: -10%;
  }

  .portal-icon {
    font-size: 80px;
    position: relative;
    z-index: 1;
    transition: all 0.3s ease;
  }

  .portal-card:hover .portal-icon {
    transform: scale(1.2) rotate(10deg);
  }

  .portal-body {
    padding: 30px;
    text-align: center;
  }

  .portal-title {
    font-size: 24px;
    font-weight: 700;
    color: #193570;
    margin-bottom: 15px;
    transition: color 0.3s ease;
  }

  .portal-card.mahasiswa:hover .portal-title {
    color: #193570;
  }

  .portal-card.admin:hover .portal-title {
    color: #ff9900;
  }

  .portal-description {
    font-size: 14px;
    color: #666;
    margin-bottom: 25px;
    line-height: 1.6;
  }

  .portal-features {
    text-align: left;
    margin-bottom: 25px;
  }

  .feature-item {
    display: flex;
    align-items: center;
    padding: 8px 0;
    font-size: 13px;
    color: #555;
  }

  .feature-item i {
    margin-right: 10px;
    font-size: 14px;
  }

  .portal-card.mahasiswa .feature-item i {
    color: #193570;
  }

  .portal-card.admin .feature-item i {
    color: #ff9900;
  }

  .portal-button {
    width: 100%;
    padding: 14px 20px;
    border: none;
    border-radius: 10px;
    font-size: 16px;
    font-weight: 600;
    color: white;
    cursor: pointer;
    transition: all 0.3s ease;
    text-decoration: none;
    display: inline-block;
  }

  .portal-card.mahasiswa .portal-button {
    background: linear-gradient(135deg, #193570 0%, #2c5aa0 100%);
  }

  .portal-card.admin .portal-button {
    background: linear-gradient(135deg, #ff9900 0%, #ffa500 100%);
  }

  .portal-button:hover {
    transform: scale(1.05);
    box-shadow: 0 5px 20px rgba(0, 0, 0, 0.2);
    color: white;
  }

  /* Footer */
  .footer {
    background: linear-gradient(135deg, #193570 0%, #2c5aa0 100%);
    color: white;
    padding: 40px 20px 20px;
    text-align: center;
    border-top: 4px solid #ff9900;
    margin-top: auto;
    box-shadow: 0 -4px 15px rgba(0, 0, 0, 0.1);
  }

  .footer-content {
    max-width: 1200px;
    margin: 0 auto;
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 30px;
    margin-bottom: 30px;
    text-align: left;
  }

  .footer-section h5 {
    font-size: 14px;
    font-weight: 700;
    margin-bottom: 15px;
    color: #fff9e6;
    text-transform: uppercase;
    letter-spacing: 0.5px;
  }

  .footer-section p {
    font-size: 13px;
    opacity: 0.95;
    line-height: 1.8;
    margin: 0;
  }

  .footer-section a {
    color: #fff9e6;
    text-decoration: none;
    transition: color 0.3s ease;
  }

  .footer-section a:hover {
    color: #ff9900;
    text-decoration: underline;
  }

  .footer-divider {
    height: 1px;
    background: rgba(255, 255, 255, 0.2);
    margin: 20px 0;
  }

  .footer-bottom {
    text-align: center;
    font-size: 12px;
    opacity: 0.85;
    border-top: 1px solid rgba(255, 255, 255, 0.1);
    padding-top: 20px;
  }

  .footer-bottom p {
    margin: 5px 0;
  }

  .creators {
    font-weight: 600;
    color: #ff9900;
  }

  /* Animations */
  @keyframes fadeInDown {
    from {
      opacity: 0;
      transform: translateY(-30px);
    }
    to {
      opacity: 1;
      transform: translateY(0);
    }
  }

  @keyframes fadeInUp {
    from {
      opacity: 0;
      transform: translateY(30px);
    }
    to {
      opacity: 1;
      transform: translateY(0);
    }
  }

  @keyframes slideDown {
    from {
      opacity: 0;
      transform: translateY(-20px);
    }
    to {
      opacity: 1;
      transform: translateY(0);
    }
  }

  @keyframes bounce {
    0%, 100% {
      transform: translateY(0);
    }
    50% {
      transform: translateY(-10px);
    }
  }

  @keyframes fadeIn {
    from {
      opacity: 0;
    }
    to {
      opacity: 1;
    }
  }

  /* Responsive */
  @media (max-width: 992px) {
    .navbar-content {
      gap: 12px;
    }

    .header-info {
      gap: 15px;
    }

    .info-section {
      gap: 6px;
    }

    .info-label {
      font-size: 8px;
    }

    .info-value {
      font-size: 11px;
    }
  }

  @media (max-width: 768px) {
    .navbar-header {
      padding: 10px 15px;
    }

    .navbar-content {
      flex-wrap: wrap;
      row-gap: 10px;
    }

    .logo-section {
      gap: 8px;
    }

    .logo-image {
      width: 50px;
      height: 50px;
      padding: 5px;
    }

    .logo-text h3 {
      font-size: 14px;
    }

    .logo-text p {
      font-size: 10px;
    }

    .header-info {
      width: 100%;
      gap: 10px;
      justify-content: flex-start;
    }

    .header-divider {
      display: none;
    }

    .header-quote {
      display: none;
    }

    .info-section {
      gap: 6px;
      font-size: 12px;
    }

    .info-label {
      font-size: 8px;
    }

    .info-value {
      font-size: 11px;
    }

    .hero-section {
      padding: 40px 15px;
      margin-bottom: 40px;
    }

    .hero-section h1 {
      font-size: 28px;
      margin-bottom: 10px;
    }

    .hero-section .subtitle {
      font-size: 16px;
      margin-bottom: 8px;
    }

    .hero-section .description {
      font-size: 14px;
      max-width: 100%;
    }

    .hero-icon {
      font-size: 60px;
      margin-bottom: 15px;
    }

    .container {
      padding: 15px;
      max-width: 100%;
    }

    .portal-grid {
      grid-template-columns: 1fr;
      gap: 25px;
      margin-top: 25px;
    }

    .portal-card {
      border-radius: 12px;
    }

    .portal-header {
      height: 150px;
    }

    .portal-icon {
      font-size: 60px;
    }

    .portal-body {
      padding: 20px;
    }

    .portal-title {
      font-size: 20px;
      margin-bottom: 12px;
    }

    .portal-description {
      font-size: 13px;
      margin-bottom: 15px;
    }

    .feature-item {
      font-size: 12px;
      padding: 6px 0;
    }

    .feature-item i {
      font-size: 12px;
      margin-right: 8px;
    }

    .portal-button {
      padding: 12px 18px;
      font-size: 14px;
      border-radius: 8px;
    }

    .footer {
      padding: 30px 15px 15px;
      border-top: 3px solid #ff9900;
    }

    .footer-content {
      grid-template-columns: 1fr;
      gap: 20px;
      margin-bottom: 20px;
    }

    .footer-section {
      text-align: center;
    }

    .footer-section h5 {
      font-size: 12px;
      margin-bottom: 10px;
    }

    .footer-section p {
      font-size: 12px;
      line-height: 1.6;
    }

    .footer-bottom {
      font-size: 11px;
      padding-top: 15px;
    }

    .footer-bottom p {
      margin: 3px 0;
    }
  }

  @media (max-width: 480px) {
    .navbar-header {
      padding: 8px 12px;
    }

    .logo-image {
      width: 45px;
      height: 45px;
      padding: 4px;
    }

    .logo-text h3 {
      font-size: 13px;
    }

    .logo-text p {
      font-size: 9px;
    }

    .info-section {
      gap: 5px;
    }

    .info-label {
      font-size: 7px;
    }

    .info-value {
      font-size: 10px;
    }

    .hero-section {
      padding: 30px 12px;
    }

    .hero-section h1 {
      font-size: 24px;
    }

    .hero-section .subtitle {
      font-size: 14px;
    }

    .hero-section .description {
      font-size: 13px;
    }

    .hero-icon {
      font-size: 50px;
    }

    .container {
      padding: 12px;
    }

    .portal-grid {
      gap: 20px;
      margin-top: 20px;
    }

    .portal-header {
      height: 130px;
    }

    .portal-icon {
      font-size: 50px;
    }

    .portal-body {
      padding: 18px;
    }

    .portal-title {
      font-size: 18px;
      margin-bottom: 10px;
    }

    .portal-description {
      font-size: 12px;
      margin-bottom: 12px;
    }

    .portal-features {
      margin-bottom: 15px;
    }

    .feature-item {
      font-size: 11px;
      padding: 5px 0;
    }

    .portal-button {
      padding: 10px 16px;
      font-size: 13px;
    }

    .footer {
      padding: 25px 12px 12px;
    }

    .footer-content {
      gap: 15px;
      margin-bottom: 15px;
    }

    .footer-section h5 {
      font-size: 11px;
      margin-bottom: 8px;
    }

    .footer-section p {
      font-size: 11px;
    }
  }

  /* Floating particles animation */
  .particles {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    overflow: hidden;
    pointer-events: none;
  }

  .particle {
    position: absolute;
    width: 10px;
    height: 10px;
    background: rgba(255, 255, 255, 0.3);
    border-radius: 50%;
    animation: float 15s infinite;
  }

  .particle:nth-child(1) { left: 10%; animation-delay: 0s; }
  .particle:nth-child(2) { left: 20%; animation-delay: 2s; }
  .particle:nth-child(3) { left: 30%; animation-delay: 4s; }
  .particle:nth-child(4) { left: 40%; animation-delay: 6s; }
  .particle:nth-child(5) { left: 50%; animation-delay: 8s; }
  .particle:nth-child(6) { left: 60%; animation-delay: 10s; }
  .particle:nth-child(7) { left: 70%; animation-delay: 12s; }
  .particle:nth-child(8) { left: 80%; animation-delay: 14s; }

  @keyframes float {
    0% {
      transform: translateY(100vh) scale(0);
      opacity: 0;
    }
    10% {
      opacity: 0.5;
    }
    90% {
      opacity: 0.5;
    }
    100% {
      transform: translateY(-100px) scale(1);
      opacity: 0;
    }
  }
</style>
</head>
<body>

<!-- Header -->
<div class="navbar-header">
  <div class="navbar-content">
    <!-- Logo Section -->
    <div class="logo-section">
      <div class="logo-image">
        <img src="logo unp.png" viewBox='0 0 100 100'%3E%3Ccircle cx='50' cy='50' r='48' fill='%23193570' stroke='%23000' stroke-width='2'/%3E%3Ctext x='50' y='65' font-size='40' font-weight='bold' text-anchor='middle' fill='%23ff9900'%3EUP%3C/text%3E%3C/svg%3E" alt="UNP Logo">
      </div>
      <div class="logo-text">
        <h3>Aplikasi Labor</h3>
        <p>UNP Padang</p>
      </div>
    </div>

    <!-- Divider -->
    <div class="header-divider"></div>

    <!-- Header Info -->
    <div class="header-info">
      <!-- Time Info -->
      <div class="info-section">
        <i class="fas fa-clock"></i>
        <div class="info-section-content">
          <span class="info-label">Waktu</span>
          <span class="info-value" id="current-time">--:--:--</span>
        </div>
      </div>

      <!-- Date and Day Info -->
      <div class="info-section">
        <i class="fas fa-calendar-alt"></i>
        <div class="info-section-content">
          <span class="info-label">Tanggal & Hari</span>
          <span class="info-value" id="current-date">--/--/--</span>
        </div>
      </div>
    </div>

    <!-- Quote/Tagline -->
    <div class="header-quote">
      <div>"Manajemen Peralatan<br>Lebih Efisien & Transparan"</div>
    </div>
  </div>
</div>

<div class="main-content">
  <!-- Hero Section -->
  <div class="container">
    <div class="hero-section">
      <div class="hero-icon">
        <i class="fas fa-flask"></i>
      </div>
      <h1>Aplikasi Labor</h1>
      <p class="subtitle">Sistem Peminjaman Peralatan Laboratorium</p>
      <p class="description">Platform digital untuk memudahkan peminjaman dan pengelolaan peralatan laboratorium secara efisien dan terorganisir</p>
    </div>

    <!-- Portal Cards -->
    <div class="portal-grid">
      <!-- Portal Mahasiswa -->
      <div class="portal-card mahasiswa" onclick="window.location.href='auth/login.php'">
        <div class="portal-header">
          <div class="portal-icon">
            <i class="fas fa-user-graduate"></i>
          </div>
        </div>
        <div class="portal-body">
          <h2 class="portal-title">Portal Mahasiswa</h2>
          <p class="portal-description">
            Akses sistem peminjaman peralatan laboratorium untuk kebutuhan praktikum dan penelitian Anda
          </p>
          <div class="portal-features">
            <div class="feature-item">
              <i class="fas fa-check-circle"></i>
              <span>Pinjam peralatan laboratorium</span>
            </div>
            <div class="feature-item">
              <i class="fas fa-check-circle"></i>
              <span>Lihat riwayat peminjaman</span>
            </div>
            <div class="feature-item">
              <i class="fas fa-check-circle"></i>
              <span>Notifikasi status real-time</span>
            </div>
            <div class="feature-item">
              <i class="fas fa-check-circle"></i>
              <span>Kelola profil akun</span>
            </div>
          </div>
          <a href="auth/login.php" class="portal-button">
            <i class="fas fa-sign-in-alt"></i> Masuk Mahasiswa
          </a>
        </div>
      </div>

      <!-- Portal Admin -->
      <div class="portal-card admin" onclick="window.location.href='admin/login.php'">
        <div class="portal-header">
          <div class="portal-icon">
            <i class="fas fa-user-shield"></i>
          </div>
        </div>
        <div class="portal-body">
          <h2 class="portal-title">Portal Admin</h2>
          <p class="portal-description">
            Dashboard administrator untuk mengelola sistem, peralatan, dan persetujuan peminjaman
          </p>
          <div class="portal-features">
            <div class="feature-item">
              <i class="fas fa-check-circle"></i>
              <span>Kelola data peralatan</span>
            </div>
            <div class="feature-item">
              <i class="fas fa-check-circle"></i>
              <span>Verifikasi peminjaman</span>
            </div>
            <div class="feature-item">
              <i class="fas fa-check-circle"></i>
              <span>Monitor semua transaksi</span>
            </div>
            <div class="feature-item">
              <i class="fas fa-check-circle"></i>
              <span>Laporan dan statistik</span>
            </div>
          </div>
          <a href="admin/login.php" class="portal-button">
            <i class="fas fa-shield-alt"></i> Masuk Admin
          </a>
        </div>
      </div>
    </div>
  </div>

</div>

<!-- Footer -->
<div class="footer">
  <div class="footer-content">
    <div class="footer-section">
      <h5><i class="fas fa-university"></i> Tentang</h5>
      <p>Aplikasi Labor adalah sistem manajemen peminjaman peralatan laboratorium yang dikembangkan untuk Universitas Negeri Padang.</p>
    </div>
    <div class="footer-section">
      <h5><i class="fas fa-users"></i> Tim Pengembang</h5>
      <p><span class="creators">Taufik Riandra</span></p>
      <p><span class="creators">Zikhrul Azmi</span></p>
    </div>
    <div class="footer-section">
      <h5><i class="fas fa-link"></i> Kontak</h5>
      <p><a href="mailto:info@unp.ac.id">info@unp.ac.id</a></p>
      <p><a href="tel:+62751-40309">+62751-40309</a></p>
    </div>
  </div>
  <div class="footer-divider"></div>
  <div class="footer-bottom">
    <p><i class="fas fa-copyright"></i> 2025 Aplikasi Labor - Universitas Negeri Padang</p>
    <p>Dikembangkan dengan oleh <strong>Taufik Riandra</strong> & <strong>Zikhrul Azmi</strong></p>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
  // Update waktu dan tanggal sistem secara real-time
  function updateDateTime() {
    const now = new Date();
    
    // Update waktu
    const hours = String(now.getHours()).padStart(2, '0');
    const minutes = String(now.getMinutes()).padStart(2, '0');
    const seconds = String(now.getSeconds()).padStart(2, '0');
    document.getElementById('current-time').textContent = `${hours}:${minutes}:${seconds}`;
    
    // Update tanggal dan hari
    const days = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
    const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
    
    const dayName = days[now.getDay()];
    const date = String(now.getDate()).padStart(2, '0');
    const month = months[now.getMonth()];
    const year = now.getFullYear();
    
    document.getElementById('current-date').textContent = `${dayName}, ${date} ${month} ${year}`;
  }
  
  // Update setiap detik
  setInterval(updateDateTime, 1000);
  
  // Update pertama kali saat halaman load
  updateDateTime();
</script>
</body>
</html>