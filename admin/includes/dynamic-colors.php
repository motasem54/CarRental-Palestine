<?php
// Get dynamic colors from database
$db = Database::getInstance()->getConnection();
try {
    $stmt = $db->query("SELECT setting_key, setting_value FROM system_settings WHERE setting_key IN ('site_primary_color', 'site_secondary_color')");
    $color_settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    
    $primary_color = $color_settings['site_primary_color'] ?? '#FF5722';
    $secondary_color = $color_settings['site_secondary_color'] ?? '#E64A19';
} catch (Exception $e) {
    $primary_color = '#FF5722';
    $secondary_color = '#E64A19';
}

// Output CSS with dynamic colors
header('Content-Type: text/css');
?>
:root {
    --primary: <?php echo $primary_color; ?>;
    --primary-dark: <?php echo $secondary_color; ?>;
    --primary-light: <?php echo adjustBrightness($primary_color, 20); ?>;
    --primary-rgb: <?php echo hexToRgb($primary_color); ?>;
}

/* Buttons */
.btn-primary {
    background: var(--primary) !important;
    border-color: var(--primary) !important;
}

.btn-primary:hover {
    background: var(--primary-dark) !important;
    border-color: var(--primary-dark) !important;
}

/* Links */
a.text-primary,
.text-primary {
    color: var(--primary) !important;
}

/* Badges */
.badge.bg-primary {
    background-color: var(--primary) !important;
}

/* Cards */
.card-header.bg-primary {
    background-color: var(--primary) !important;
}

/* Sidebar */
.sidebar .nav-link.active {
    background: var(--primary) !important;
}

.sidebar .nav-link:hover {
    background: rgba(<?php echo hexToRgb($primary_color); ?>, 0.1) !important;
    color: var(--primary) !important;
}

/* Top Bar */
.top-bar {
    border-left: 4px solid var(--primary);
}

/* Stats Icons */
.stat-card .stat-icon {
    background: rgba(<?php echo hexToRgb($primary_color); ?>, 0.1) !important;
    color: var(--primary) !important;
}

/* Forms */
.form-control:focus,
.form-select:focus {
    border-color: var(--primary) !important;
    box-shadow: 0 0 0 0.2rem rgba(<?php echo hexToRgb($primary_color); ?>, 0.25) !important;
}

/* Progress bars */
.progress-bar {
    background-color: var(--primary) !important;
}

/* Tables */
.table thead {
    background: var(--primary) !important;
    color: white !important;
}

/* Alerts */
.alert-primary {
    background-color: rgba(<?php echo hexToRgb($primary_color); ?>, 0.1) !important;
    border-color: var(--primary) !important;
    color: var(--primary-dark) !important;
}

<?php
// Helper functions
function hexToRgb($hex) {
    $hex = str_replace('#', '', $hex);
    $r = hexdec(substr($hex, 0, 2));
    $g = hexdec(substr($hex, 2, 2));
    $b = hexdec(substr($hex, 4, 2));
    return "$r, $g, $b";
}

function adjustBrightness($hex, $steps) {
    $hex = str_replace('#', '', $hex);
    $r = hexdec(substr($hex, 0, 2));
    $g = hexdec(substr($hex, 2, 2));
    $b = hexdec(substr($hex, 4, 2));
    
    $r = max(0, min(255, $r + $steps));
    $g = max(0, min(255, $g + $steps));
    $b = max(0, min(255, $b + $steps));
    
    return '#' . str_pad(dechex($r), 2, '0', STR_PAD_LEFT)
                . str_pad(dechex($g), 2, '0', STR_PAD_LEFT)
                . str_pad(dechex($b), 2, '0', STR_PAD_LEFT);
}
?>