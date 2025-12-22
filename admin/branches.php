<?php
require_once '../config/settings.php';
require_once '../core/Auth.php';

$auth = new Auth();
if (!$auth->isLoggedIn() || $_SESSION['role'] !== 'admin') {
    redirect(ADMIN_URL . '/dashboard.php');
}

$current_page = 'branches';
$page_title = 'إدارة الفروع - ' . SITE_NAME;
include 'includes/header.php';
include 'includes/sidebar.php';
?>

<div class="main-content">
    <div class="top-bar">
        <div class="welcome-text">
            <h5><i class="fas fa-building me-2"></i>إدارة الفروع</h5>
            <p>إدارة فروع الشركة في فلسطين</p>
        </div>
        <div class="top-bar-right">
            <button class="btn btn-primary">
                <i class="fas fa-plus me-2"></i>إضافة فرع
            </button>
        </div>
    </div>

    <div class="table-container">
        <div class="alert alert-info">
            <i class="fas fa-info-circle me-2"></i>
            هذه الصفحة قيد التطوير. سيتم إضافة ميزة إدارة الفروع قريباً.
        </div>
        
        <h6 class="mb-3">الفروع الحالية:</h6>
        <div class="row">
            <div class="col-md-4 mb-3">
                <div class="card">
                    <div class="card-body">
                        <h5><i class="fas fa-map-marker-alt text-primary me-2"></i>الفرع الرئيسي</h5>
                        <p class="mb-1"><strong>المدينة:</strong> رام الله</p>
                        <p class="mb-1"><strong>الهاتف:</strong> +970599123456</p>
                        <p class="mb-0"><strong>الحالة:</strong> <span class="badge bg-success">نشط</span></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>