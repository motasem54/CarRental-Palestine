<?php
/**
 * System Constants
 * 🇵🇸 Palestine Car Rental System
 * ثوابت النظام
 */

// Car Types
define('CAR_TYPES', [
    'sedan' => 'سيدان',
    'suv' => 'SUV',
    'van' => 'فان',
    'luxury' => 'فاخرة',
    'sport' => 'رياضية',
    'economy' => 'اقتصادية'
]);

// Car Status
define('CAR_STATUS', [
    'available' => 'متاحة',
    'rented' => 'مؤجرة',
    'maintenance' => 'صيانة',
    'reserved' => 'محجوزة'
]);

// Transmission Types
define('TRANSMISSION_TYPES', [
    'manual' => 'يدوي',
    'automatic' => 'أوتوماتيك'
]);

// Fuel Types
define('FUEL_TYPES', [
    'petrol' => 'بنزين',
    'diesel' => 'ديزل',
    'hybrid' => 'هجين',
    'electric' => 'كهربائية'
]);

// Rental Status
define('RENTAL_STATUS', [
    'pending' => 'قيد الانتظار',
    'confirmed' => 'مؤكد',
    'active' => 'نشط',
    'completed' => 'مكتمل',
    'cancelled' => 'ملغي'
]);

// Payment Status
define('PAYMENT_STATUS', [
    'pending' => 'معلق',
    'partial' => 'جزئي',
    'paid' => 'مدفوع',
    'refunded' => 'مسترجع'
]);

// Payment Methods
define('PAYMENT_METHODS', [
    'cash' => 'نقدي',
    'credit_card' => 'بطاقة ائتمان',
    'bank_transfer' => 'تحويل بنكي',
    'check' => 'شيك'
]);

// User Roles
define('USER_ROLES', [
    'admin' => 'مدير',
    'employee' => 'موظف',
    'customer' => 'عميل'
]);

// Customer Status
define('CUSTOMER_STATUS', [
    'active' => 'نشط',
    'inactive' => 'غير نشط',
    'blacklist' => 'قائمة سوداء'
]);

// Loyalty Levels
define('LOYALTY_LEVELS', [
    'bronze' => 'برونزي',
    'silver' => 'فضي',
    'gold' => 'ذهبي',
    'platinum' => 'بلاتيني'
]);

// Maintenance Types
define('MAINTENANCE_TYPES', [
    'regular' => 'دورية',
    'repair' => 'إصلاح',
    'inspection' => 'فحص',
    'other' => 'أخرى'
]);

// Penalty Types
define('PENALTY_TYPES', [
    'late_return' => 'تأخير التسليم',
    'damage' => 'أضرار',
    'fuel' => 'وقود',
    'traffic' => 'مخالفات مرورية',
    'other' => 'أخرى'
]);

// Expense Types
define('EXPENSE_TYPES', [
    'fuel' => 'وقود',
    'maintenance' => 'صيانة',
    'insurance' => 'تأمين',
    'salary' => 'رواتب',
    'rent' => 'إيجار',
    'utilities' => 'فواتير',
    'marketing' => 'تسويق',
    'other' => 'أخرى'
]);

// Notification Channels
define('NOTIFICATION_CHANNELS', [
    'whatsapp' => 'WhatsApp',
    'sms' => 'SMS',
    'email' => 'Email',
    'system' => 'إشعار نظام'
]);

// Days of Week in Arabic
define('DAYS_AR', [
    'Sunday' => 'الأحد',
    'Monday' => 'الإثنين',
    'Tuesday' => 'الثلاثاء',
    'Wednesday' => 'الأربعاء',
    'Thursday' => 'الخميس',
    'Friday' => 'الجمعة',
    'Saturday' => 'السبت'
]);

// Months in Arabic
define('MONTHS_AR', [
    1 => 'يناير',
    2 => 'فبراير',
    3 => 'مارس',
    4 => 'أبريل',
    5 => 'مايو',
    6 => 'يونيو',
    7 => 'يوليو',
    8 => 'أغسطس',
    9 => 'سبتمبر',
    10 => 'أكتوبر',
    11 => 'نوفمبر',
    12 => 'ديسمبر'
]);

// ====================================================================
// Helper Functions
// ====================================================================

/**
 * تنسيق العملة
 */
function formatCurrency($amount, $currency = null) {
    $currency = $currency ?? CURRENCY_SYMBOL;
    return number_format($amount, 2) . ' ' . $currency;
}

/**
 * تنسيق المبلغ (اسم بديل)
 */
function formatMoney($amount, $currency = null) {
    return formatCurrency($amount, $currency);
}

/**
 * تنسيق الأرقام مع فواصل
 */
function formatNumber($number, $decimals = 0) {
    return number_format($number, $decimals);
}

/**
 * تنسيق التاريخ
 */
function formatDate($date, $format = 'Y-m-d') {
    if (empty($date) || $date == '0000-00-00' || $date == '0000-00-00 00:00:00') {
        return '-';
    }
    return date($format, strtotime($date));
}

/**
 * تنسيق التاريخ والوقت
 */
function formatDateTime($datetime, $format = 'Y-m-d H:i') {
    if (empty($datetime) || $datetime == '0000-00-00 00:00:00') {
        return '-';
    }
    return date($format, strtotime($datetime));
}

/**
 * توليد رقم حجز
 */
function generateRentalNumber() {
    return 'RNT-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
}

/**
 * توليد رقم دفعة
 */
function generatePaymentNumber() {
    return 'PAY-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
}

/**
 * توليد رقم حجز موقع
 */
function generateBookingNumber() {
    return 'BKG-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
}

/**
 * حساب الأيام بين تاريخين
 */
function calculateDays($start_date, $end_date) {
    $start = new DateTime($start_date);
    $end = new DateTime($end_date);
    $diff = $start->diff($end);
    return $diff->days + 1; // Include both start and end date
}

/**
 * حساب غرامة التأخير
 */
function calculateLateFee($end_date, $return_date, $fee_per_day = LATE_FEE_PER_DAY) {
    $end = new DateTime($end_date);
    $return = new DateTime($return_date);
    
    if ($return <= $end) {
        return 0;
    }
    
    $diff = $end->diff($return);
    return $diff->days * $fee_per_day;
}

/**
 * تنظيف المدخلات
 */
function sanitizeInput($data) {
    if (is_array($data)) {
        return array_map('sanitizeInput', $data);
    }
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

/**
 * إعادة التوجيه
 */
function redirect($url) {
    if (!headers_sent()) {
        header("Location: " . $url);
        exit();
    } else {
        echo '<script>window.location.href="' . $url . '";</script>';
        exit();
    }
}

/**
 * التحقق من تسجيل الدخول
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * التحقق من صلاحيات المدير
 */
function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

/**
 * فحص المصادقة
 */
function checkAuth() {
    if (!isLoggedIn()) {
        redirect(ADMIN_URL . '/login.php');
    }
}

/**
 * فحص صلاحيات المدير
 */
function checkAdminAuth() {
    checkAuth();
    if (!isAdmin()) {
        redirect(ADMIN_URL . '/dashboard.php');
    }
}
?>