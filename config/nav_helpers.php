<?php
/**
 * Navigation Helper Functions
 * File: config/nav_helpers.php
 * 
 * Fungsi helper untuk navigasi yang konsisten di seluruh aplikasi
 */

/**
 * Menampilkan tombol back button dengan styling yang konsisten
 * 
 * @param string $url - URL tujuan
 * @param string $text - Teks button (default: "Kembali")
 * @param string $icon - Font Awesome icon class (default: "fas fa-arrow-left")
 * @param array $extra_classes - Class CSS tambahan
 * 
 * @return string - HTML button
 */
function get_back_button($url = 'dashboard.php', $text = 'Kembali', $icon = 'fas fa-arrow-left', $extra_classes = []) {
    $classes = 'btn btn-secondary btn-sm';
    if (!empty($extra_classes)) {
        $classes .= ' ' . implode(' ', $extra_classes);
    }
    
    return sprintf(
        '<a href="%s" class="%s"><i class="%s"></i> %s</a>',
        htmlspecialchars($url),
        $classes,
        $icon,
        htmlspecialchars($text)
    );
}

/**
 * Menampilkan back button dengan wrapper div
 * 
 * @param string $url - URL tujuan
 * @param string $text - Teks button
 * @param string $icon - Font Awesome icon class
 * 
 * @return string - HTML button dengan wrapper
 */
function display_back_button($url = 'dashboard.php', $text = 'Kembali', $icon = 'fas fa-arrow-left') {
    return sprintf(
        '<div style="margin-bottom: 20px;">%s</div>',
        get_back_button($url, $text, $icon)
    );
}

/**
 * Menampilkan breadcrumb navigation
 * 
 * @param array $breadcrumbs - Array dengan struktur [['url' => 'link', 'title' => 'Title'], ...]
 * 
 * @return string - HTML breadcrumb
 */
function display_breadcrumb($breadcrumbs = []) {
    if (empty($breadcrumbs)) {
        return '';
    }
    
    $html = '<nav aria-label="breadcrumb" class="mb-4"><ol class="breadcrumb">';
    
    foreach ($breadcrumbs as $index => $item) {
        $is_last = ($index === count($breadcrumbs) - 1);
        
        if ($is_last) {
            $html .= sprintf(
                '<li class="breadcrumb-item active">%s</li>',
                htmlspecialchars($item['title'])
            );
        } else {
            $html .= sprintf(
                '<li class="breadcrumb-item"><a href="%s">%s</a></li>',
                htmlspecialchars($item['url']),
                htmlspecialchars($item['title'])
            );
        }
    }
    
    $html .= '</ol></nav>';
    
    return $html;
}

/**
 * Menampilkan page header dengan title dan back button
 * 
 * @param string $title - Judul halaman
 * @param string $description - Deskripsi halaman (opsional)
 * @param string $back_url - URL untuk back button (opsional)
 * @param string $icon - Font Awesome icon (opsional)
 * 
 * @return string - HTML page header
 */
function display_page_header($title, $description = '', $back_url = 'dashboard.php', $icon = '') {
    $html = '';
    
    // Tampilkan back button jika URL provided
    if (!empty($back_url)) {
        $html .= display_back_button($back_url);
    }
    
    // Page header
    $html .= '<div class="welcome-card">';
    $html .= '<h2>';
    if (!empty($icon)) {
        $html .= sprintf('<i class="%s"></i> ', htmlspecialchars($icon));
    }
    $html .= htmlspecialchars($title) . '</h2>';
    
    if (!empty($description)) {
        $html .= sprintf('<p>%s</p>', htmlspecialchars($description));
    }
    
    $html .= '</div>';
    
    return $html;
}

?>
