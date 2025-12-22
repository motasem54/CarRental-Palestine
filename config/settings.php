<?php
/**
 * System Settings
 * 🇵🇸 Palestine Car Rental System
 * إعدادات النظام
 */

// Start session if not started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Error reporting (disable in production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Timezone - Palestine (Jerusalem)
date_default_timezone_set('Asia/Jerusalem');

// System Constants
define('SITE_NAME', 'نظام تأجير السيارات - فلسطين');
define('SITE_NAME_EN', 'CarRental Palestine');
define('COMPANY_NAME', 'شركة تأجير السيارات');

// Palestine Settings
define('COUNTRY', 'Palestine');
define('COUNTRY_AR', 'فلسطين');
define('DEFAULT_CITY', 'رام الله');
define('CURRENCY', 'ILS');
define('CURRENCY_SYMBOL', '₪');
define('CURRENCY_NAME', 'شيكل');
define('TIMEZONE', 'Asia/Jerusalem');

// Palestinian Cities
$PALESTINIAN_CITIES = [
    'رام الله',
    'نابلس',
    'الخليل',
    'بيت لحم',
    'جنين',
    'طولكرم',
    'قلقيلية',
    'رافت',
    'أريحا',
    'سلفيت',
    'طوباس',
    'يطا'
];

// Contact Information
define('COMPANY_PHONE', '+970599123456');
define('COMPANY_EMAIL', 'info@carrental.ps');
define('COMPANY_ADDRESS', 'رام الله - فلسطين');
define('WHATSAPP_NUMBER', '+970599123456');

// System Paths
define('ROOT_PATH', dirname(__DIR__));
define('ADMIN_PATH', ROOT_PATH . '/admin');
define('PUBLIC_PATH', ROOT_PATH . '/public');
define('UPLOADS_PATH', ROOT_PATH . '/uploads');
define('ASSETS_PATH', ROOT_PATH . '/assets');

// URL Paths (adjust based on your domain)
define('BASE_URL', 'http://localhost/CarRental-Palestine');
define('ADMIN_URL', BASE_URL . '/admin');
define('PUBLIC_URL', BASE_URL . '/public');
define('ASSETS_URL', BASE_URL . '/assets');
define('UPLOADS_URL', BASE_URL . '/uploads');

// Upload directories
define('CARS_UPLOAD_DIR', UPLOADS_PATH . '/cars');
define('CONTRACTS_UPLOAD_DIR', UPLOADS_PATH . '/contracts');
define('RECEIPTS_UPLOAD_DIR', UPLOADS_PATH . '/receipts');
define('LOGOS_UPLOAD_DIR', UPLOADS_PATH . '/logos');
define('GALLERY_UPLOAD_DIR', UPLOADS_PATH . '/gallery');

// File upload settings
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/jpg', 'image/png', 'image/gif']);
define('ALLOWED_DOC_TYPES', ['application/pdf', 'application/msword']);

// Pagination
define('ITEMS_PER_PAGE', 20);
define('ADMIN_ITEMS_PER_PAGE', 25);

// Business Rules
define('MIN_RENTAL_DAYS', 1);
define('MAX_RENTAL_DAYS', 90);
define('TAX_RATE', 16); // 16% VAT
define('LATE_FEE_PER_DAY', 50); // 50 ILS per day

// Loyalty Program
define('POINTS_PER_SHEKEL', 1);
define('BRONZE_MIN_POINTS', 0);
define('SILVER_MIN_POINTS', 500);
define('GOLD_MIN_POINTS', 1500);
define('PLATINUM_MIN_POINTS', 5000);

// Notification Settings (default disabled)
define('WHATSAPP_ENABLED', false);
define('SMS_ENABLED', false);
define('EMAIL_ENABLED', true);

// WhatsApp API (configure when ready)
define('WHATSAPP_API_URL', '');
define('WHATSAPP_API_KEY', '');

// SMS API (configure when ready)
define('SMS_API_URL', '');
define('SMS_API_KEY', '');

// Email Settings
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', COMPANY_EMAIL);
define('SMTP_PASSWORD', '');
define('SMTP_FROM_NAME', COMPANY_NAME);

// Security
define('SESSION_TIMEOUT', 3600); // 1 hour
define('PASSWORD_MIN_LENGTH', 8);
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_LOCKOUT_TIME', 900); // 15 minutes

// Admin default credentials (change after first login)
define('DEFAULT_ADMIN_USERNAME', 'admin');
define('DEFAULT_ADMIN_PASSWORD', 'Admin@123');

// Load database
require_once __DIR__ . '/database.php';

// Load constants (car types, statuses, etc.)
require_once __DIR__ . '/constants.php';

// Load helper functions
require_once __DIR__ . '/functions.php';

// Auto-create upload directories
$uploadDirs = [
    UPLOADS_PATH,
    CARS_UPLOAD_DIR,
    CONTRACTS_UPLOAD_DIR,
    RECEIPTS_UPLOAD_DIR,
    LOGOS_UPLOAD_DIR,
    GALLERY_UPLOAD_DIR
];

foreach ($uploadDirs as $dir) {
    if (!file_exists($dir)) {
        mkdir($dir, 0755, true);
    }
}

// Helper function to get setting from database
function getSetting($key, $default = null) {
    try {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
        $stmt->execute([$key]);
        $result = $stmt->fetch();
        return $result ? $result['setting_value'] : $default;
    } catch (Exception $e) {
        return $default;
    }
}

// Helper function to update setting
function updateSetting($key, $value, $group = 'general') {
    try {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("
            INSERT INTO settings (setting_key, setting_value, setting_group) 
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE setting_value = ?, updated_at = NOW()
        ");
        return $stmt->execute([$key, $value, $group, $value]);
    } catch (Exception $e) {
        return false;
    }
}
?>