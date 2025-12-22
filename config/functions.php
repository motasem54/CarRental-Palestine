<?php
/**
 * Helper Functions
 * 🇵🇸 دوال مساعدة عامة للنظام
 */

/**
 * Note: Some functions are already defined in constants.php
 * This file contains additional helper functions
 */

/**
 * عرض رسالة تنبيه مع أيقونة
 */
function showAlert($message, $type = 'success') {
    $icons = [
        'success' => 'check-circle',
        'error' => 'exclamation-circle',
        'warning' => 'exclamation-triangle',
        'info' => 'info-circle'
    ];
    
    $icon = $icons[$type] ?? 'info-circle';
    
    return '<div class="alert alert-' . $type . ' alert-dismissible fade show" role="alert">
                <i class="fas fa-' . $icon . ' me-2"></i>
                ' . $message . '
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>';
}

/**
 * تنسيق التاريخ بالعربي
 */
function formatDateArabic($date) {
    if (empty($date) || $date == '0000-00-00' || $date == '0000-00-00 00:00:00') {
        return '-';
    }
    
    $months = MONTHS_AR;
    
    $timestamp = strtotime($date);
    $day = date('d', $timestamp);
    $month = $months[(int)date('m', $timestamp)];
    $year = date('Y', $timestamp);
    
    return "$day $month $year";
}

/**
 * حساب النسبة المئوية
 */
function calculatePercentage($value, $total) {
    if ($total == 0) return 0;
    return round(($value / $total) * 100, 2);
}

/**
 * اختصار النص
 */
function truncateText($text, $length = 100, $suffix = '...') {
    if (mb_strlen($text) <= $length) {
        return $text;
    }
    return mb_substr($text, 0, $length) . $suffix;
}

/**
 * التحقق من رقم الهاتف الفلسطيني
 */
function validatePalestinePhone($phone) {
    $phone = str_replace([' ', '-', '(', ')'], '', $phone);
    $pattern = '/^(\+970|00970|0)(5[0-9]|2[0-9])[0-9]{7}$/';
    return preg_match($pattern, $phone);
}

/**
 * تنسيق رقم الهاتف
 */
function formatPhone($phone) {
    $phone = str_replace([' ', '-', '(', ')'], '', $phone);
    
    if (preg_match('/^(\+970|00970)(5[0-9]|2[0-9])([0-9]{7})$/', $phone, $matches)) {
        return '+970-' . $matches[2] . '-' . substr($matches[3], 0, 3) . '-' . substr($matches[3], 3);
    }
    
    if (preg_match('/^0(5[0-9]|2[0-9])([0-9]{7})$/', $phone, $matches)) {
        return '0' . $matches[1] . '-' . substr($matches[2], 0, 3) . '-' . substr($matches[2], 3);
    }
    
    return $phone;
}

/**
 * الحصول على الوقت المنقضي (منذ)
 */
function timeAgo($datetime) {
    if (empty($datetime) || $datetime == '0000-00-00 00:00:00') {
        return '-';
    }
    
    $timestamp = strtotime($datetime);
    $diff = time() - $timestamp;
    
    if ($diff < 60) {
        return 'منذ لحظات';
    } elseif ($diff < 3600) {
        $mins = floor($diff / 60);
        return "منذ $mins دقيقة";
    } elseif ($diff < 86400) {
        $hours = floor($diff / 3600);
        return "منذ $hours ساعة";
    } elseif ($diff < 2592000) {
        $days = floor($diff / 86400);
        return "منذ $days يوم";
    } else {
        return formatDateArabic($datetime);
    }
}

/**
 * توليد كود عشوائي
 */
function generateCode($length = 6, $type = 'numeric') {
    switch ($type) {
        case 'numeric':
            $characters = '0123456789';
            break;
        case 'alpha':
            $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
            break;
        case 'alphanumeric':
            $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
            break;
        default:
            $characters = '0123456789';
    }
    
    $code = '';
    $max = strlen($characters) - 1;
    
    for ($i = 0; $i < $length; $i++) {
        $code .= $characters[random_int(0, $max)];
    }
    
    return $code;
}

/**
 * التحقق من صلاحية التاريخ
 */
function isValidDate($date, $format = 'Y-m-d') {
    $d = DateTime::createFromFormat($format, $date);
    return $d && $d->format($format) === $date;
}

/**
 * حساب العمر
 */
function calculateAge($birthdate) {
    if (empty($birthdate) || $birthdate == '0000-00-00') {
        return 0;
    }
    $birth = new DateTime($birthdate);
    $today = new DateTime('today');
    return $birth->diff($today)->y;
}

/**
 * تحويل الحجم للقراءة (KB, MB, GB)
 */
function formatFileSize($bytes) {
    if ($bytes >= 1073741824) {
        return number_format($bytes / 1073741824, 2) . ' GB';
    } elseif ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        return number_format($bytes / 1024, 2) . ' KB';
    } else {
        return $bytes . ' Bytes';
    }
}

/**
 * تسجيل النشاط في قاعدة البيانات
 */
function logActivity($userId, $action, $description) {
    try {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare(
            "INSERT INTO activity_log (user_id, action, description, ip_address, user_agent, created_at) 
             VALUES (?, ?, ?, ?, ?, NOW())"
        );
        
        $stmt->execute([
            $userId,
            $action,
            $description,
            $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
        ]);
        
        return true;
    } catch (Exception $e) {
        error_log('Activity log error: ' . $e->getMessage());
        return false;
    }
}

/**
 * التحقق من الصلاحيات
 */
function checkPermission($permission) {
    if (!isset($_SESSION['user_id'])) {
        return false;
    }
    
    if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
        return true;
    }
    
    return false;
}

/**
 * تنظيف اسم الملف
 */
function sanitizeFileName($filename) {
    $filename = preg_replace('/[^a-zA-Z0-9._-]/', '_', $filename);
    $filename = preg_replace('/_+/', '_', $filename);
    return $filename;
}

/**
 * الحصول على امتداد الملف
 */
function getFileExtension($filename) {
    return strtolower(pathinfo($filename, PATHINFO_EXTENSION));
}

/**
 * التحقق من نوع الملف المسموح
 */
function isAllowedFileType($filename, $allowedTypes) {
    $ext = getFileExtension($filename);
    return in_array($ext, $allowedTypes);
}

/**
 * رفع ملف بشكل آمن
 */
function uploadFile($file, $targetDir, $allowedTypes = ['jpg', 'jpeg', 'png', 'pdf']) {
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'خطأ في رفع الملف'];
    }
    
    $fileName = basename($file['name']);
    $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    
    if (!in_array($fileExt, $allowedTypes)) {
        return ['success' => false, 'message' => 'نوع الملف غير مسموح'];
    }
    
    if ($file['size'] > MAX_FILE_SIZE) {
        return ['success' => false, 'message' => 'حجم الملف كبير جداً'];
    }
    
    $newFileName = uniqid() . '.' . $fileExt;
    $targetPath = $targetDir . '/' . $newFileName;
    
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0755, true);
    }
    
    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        return ['success' => true, 'filename' => $newFileName, 'path' => $targetPath];
    }
    
    return ['success' => false, 'message' => 'فشل في حفظ الملف'];
}

/**
 * حذف ملف بشكل آمن
 */
function deleteFile($filePath) {
    if (file_exists($filePath) && is_file($filePath)) {
        return unlink($filePath);
    }
    return false;
}

/**
 * تنسيق رقم بفواصل
 */
function formatNumber($number, $decimals = 0) {
    return number_format($number, $decimals);
}

/**
 * الحصول على Badge HTML لحالة السيارة
 */
function getCarStatusBadge($status) {
    $badges = [
        'available' => '<span class="badge bg-success">متاحة</span>',
        'rented' => '<span class="badge bg-primary">مؤجرة</span>',
        'maintenance' => '<span class="badge bg-warning">صيانة</span>',
        'reserved' => '<span class="badge bg-info">محجوزة</span>'
    ];
    
    return $badges[$status] ?? '<span class="badge bg-secondary">' . $status . '</span>';
}

/**
 * الحصول على Badge HTML لحالة الحجز
 */
function getRentalStatusBadge($status) {
    $badges = [
        'pending' => '<span class="badge bg-warning">معلق</span>',
        'confirmed' => '<span class="badge bg-info">مؤكد</span>',
        'active' => '<span class="badge bg-success">نشط</span>',
        'completed' => '<span class="badge bg-primary">مكتمل</span>',
        'cancelled' => '<span class="badge bg-danger">ملغي</span>'
    ];
    
    return $badges[$status] ?? '<span class="badge bg-secondary">' . $status . '</span>';
}

/**
 * الحصول على Badge HTML لحالة الدفع
 */
function getPaymentStatusBadge($status) {
    $badges = [
        'pending' => '<span class="badge bg-warning">معلق</span>',
        'partial' => '<span class="badge bg-info">جزئي</span>',
        'paid' => '<span class="badge bg-success">مدفوع</span>',
        'refunded' => '<span class="badge bg-danger">مسترد</span>'
    ];
    
    return $badges[$status] ?? '<span class="badge bg-secondary">' . $status . '</span>';
}

/**
 * التحقق من البريد الإلكتروني
 */
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * توليد رقم فريد مع بادئة
 */
function generateUniqueNumber($prefix = '', $length = 8) {
    $number = $prefix . strtoupper(substr(uniqid(), -$length));
    return $number;
}
?>