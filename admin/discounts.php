<?php
require_once '../config/settings.php';
require_once '../core/Auth.php';

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    redirect(ADMIN_URL . '/login.php');
}

$current_page = 'discounts';
$page_title = 'إدارة الخصومات - ' . SITE_NAME;
include 'includes/header.php';
include 'includes/sidebar.php';
?>

<div class="main-content">
    <div class="top-bar">
        <div class="welcome-text">
            <h5><i class="fas fa-tag me-2"></i>إدارة الخصومات والعروض</h5>
            <p>إدارة الخصومات وعروض التأجير</p>
        </div>
        <div class="top-bar-right">
            <button class="btn btn-primary">
                <i class="fas fa-plus me-2"></i>إضافة خصم
            </button>
        </div>
    </div>

    <div class="table-container">
        <div class="alert alert-info">
            <i class="fas fa-info-circle me-2"></i>
            هذه الصفحة قيد التطوير. يمكنك إضافة الخصومات يدوياً عند إنشاء الحجز.
        </div>
        
        <h6 class="mb-3">أنواع الخصومات المتاحة:</h6>
        <ul>
            <li>خصم الحجز المبكر</li>
            <li>خصم الحجز طويل المدة</li>
            <li>خصم العملاء المميزين</li>
            <li>خصم موسمي</li>
            <li>خصم خاص للمناسبات</li>
        </ul>
    </div>
</div>

<?php include 'includes/footer.php'; ?>