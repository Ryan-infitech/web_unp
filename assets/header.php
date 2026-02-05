<?php
// Header file for mahasiswa pages
// Load dark mode preference from localStorage via inline script
?>
<script>
// Apply dark mode from localStorage immediately to prevent flash
if(localStorage.getItem('darkMode') === 'true') {
  document.documentElement.style.colorScheme = 'dark';
  document.body.classList.add('dark-mode');
}
</script>
<style>
  /* Dark mode support for mahasiswa pages */
  body.dark-mode {
    background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
    color: #f1f5f9;
  }

  body.dark-mode .container,
  body.dark-mode .card,
  body.dark-mode .card-body {
    background-color: #1e293b;
    color: #f1f5f9;
  }

  body.dark-mode .card {
    border-color: #334155;
  }

  body.dark-mode .table {
    color: #f1f5f9;
    border-color: #334155;
  }

  body.dark-mode .table thead {
    background-color: #0f172a;
    color: #f1f5f9;
  }

  body.dark-mode .table-light {
    background-color: #0f172a !important;
  }

  body.dark-mode .table tbody tr:hover {
    background-color: #334155;
  }

  body.dark-mode .btn {
    border-color: #475569;
  }

  body.dark-mode .btn-secondary {
    background-color: #475569;
    border-color: #475569;
  }

  body.dark-mode .btn-secondary:hover {
    background-color: #64748b;
    border-color: #64748b;
  }

  body.dark-mode input,
  body.dark-mode select,
  body.dark-mode textarea {
    background-color: #0f172a;
    color: #f1f5f9;
    border-color: #334155;
  }

  body.dark-mode input:focus,
  body.dark-mode select:focus,
  body.dark-mode textarea:focus {
    background-color: #0f172a;
    color: #f1f5f9;
    border-color: #2563eb;
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
  }

  body.dark-mode .form-control,
  body.dark-mode .form-select {
    background-color: #0f172a;
    color: #f1f5f9;
    border-color: #334155;
  }

  body.dark-mode .text-muted {
    color: #94a3b8 !important;
  }

  body.dark-mode a:not(.btn) {
    color: #93c5fd;
  }

  body.dark-mode a:not(.btn):hover {
    color: #bfdbfe;
  }
</style>
