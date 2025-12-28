<?php
require_once '../config/settings.php';
require_once '../core/Auth.php';

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    redirect(ADMIN_URL . '/login.php');
}

if (!isset($_GET['id'])) {
    redirect('rentals.php');
}

$rental_id = (int)$_GET['id'];

$db = Database::getInstance()->getConnection();
$stmt = $db->prepare("SELECT rental_number, total_amount, paid_amount FROM rentals WHERE id = ?");
$stmt->execute([$rental_id]);
$rental = $stmt->fetch();

if (!$rental) {
    redirect('rentals.php');
}

$remaining = $rental['total_amount'] - $rental['paid_amount'];

include 'includes/header.php';
?>

<div class="main-content">
    <div class="top-bar">
        <div class="welcome-text">
            <h5>خيارات طباعة العقد</h5>
            <p>اختر نوع العقد المطلوب طباعته</p>
        </div>
    </div>
    
    <div class="row g-4">
        <!-- Contract Only -->
        <div class="col-md-6">
            <div class="stat-card text-center" style="padding: 40px;">
                <div style="font-size: 4rem; color: #FF5722; margin-bottom: 20px;">📄</div>
                <h4 style="margin-bottom: 15px;">عقد فقط</h4>
                <p style="color: #666; margin-bottom: 25px;">
                    طباعة عقد الإيجار بدون كمبيالة
                </p>
                <a href="contract_print.php?id=<?php echo $rental_id; ?>" target="_blank" class="btn btn-primary btn-lg">
                    <i class="fas fa-print me-2"></i>طباعة العقد
                </a>
            </div>
        </div>
        
        <!-- Contract with Promissory -->
        <div class="col-md-6">
            <div class="stat-card text-center" style="padding: 40px; <?php echo $remaining <= 0 ? 'opacity: 0.5;' : ''; ?>">
                <div style="font-size: 4rem; color: #FF5722; margin-bottom: 20px;">📋</div>
                <h4 style="margin-bottom: 15px;">عقد + كمبيالة</h4>
                <p style="color: #666; margin-bottom: 15px;">
                    طباعة عقد الإيجار مع سند إذني (كمبيالة)
                </p>
                <?php if ($remaining > 0): ?>
                    <div style="background: #fff3e0; padding: 15px; border-radius: 10px; margin-bottom: 20px;">
                        <strong style="color: #FF5722;">قيمة الكمبيالة:</strong><br>
                        <span style="font-size: 1.5rem; font-weight: 900; color: #333;">
                            <?php echo formatCurrency($remaining); ?>
                        </span>
                    </div>
                    <a href="contract_print.php?id=<?php echo $rental_id; ?>&promissory=1" target="_blank" class="btn btn-primary btn-lg">
                        <i class="fas fa-print me-2"></i>طباعة مع الكمبيالة
                    </a>
                <?php else: ?>
                    <div class="alert alert-success" style="margin-bottom: 20px;">
                        <i class="fas fa-check-circle me-2"></i>
                        تم دفع المبلغ بالكامل - لا حاجة لكمبيالة
                    </div>
                    <button class="btn btn-secondary btn-lg" disabled>
                        <i class="fas fa-ban me-2"></i>غير متاح
                    </button>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Contract Info -->
    <div class="stat-card" style="margin-top: 30px;">
        <h5 style="color: #FF5722; margin-bottom: 20px;">
            <i class="fas fa-info-circle me-2"></i>معلومات العقد
        </h5>
        <div class="row">
            <div class="col-md-4">
                <strong>رقم العقد:</strong><br>
                <span style="color: #666;"><?php echo $rental['rental_number']; ?></span>
            </div>
            <div class="col-md-4">
                <strong>المبلغ الإجمالي:</strong><br>
                <span style="color: #666;"><?php echo formatCurrency($rental['total_amount']); ?></span>
            </div>
            <div class="col-md-4">
                <strong>المبلغ المتبقي:</strong><br>
                <span style="color: <?php echo $remaining > 0 ? '#f44336' : '#4CAF50'; ?>; font-weight: 700;">
                    <?php echo formatCurrency($remaining); ?>
                </span>
            </div>
        </div>
    </div>
    
    <div class="text-center" style="margin-top: 30px;">
        <a href="rentals.php" class="btn btn-secondary">
            <i class="fas fa-arrow-right me-2"></i>رجوع للتأجيرات
        </a>
    </div>

<?php include 'includes/footer.php'; ?>