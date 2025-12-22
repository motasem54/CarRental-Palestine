<?php
require_once '../config/settings.php';
require_once '../core/Auth.php';

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    redirect(ADMIN_URL . '/login.php');
}

$db = Database::getInstance()->getConnection();

if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['error'] = 'معرف الحجز غير موجود';
    redirect('rentals.php');
}

$rental_id = (int)$_GET['id'];

// Fetch rental details with relations
try {
    $stmt = $db->prepare("
        SELECT r.*, 
               c.full_name as customer_name, c.phone as customer_phone, c.email as customer_email,
               c.id_number, c.driver_license, c.address,
               car.brand, car.model, car.plate_number, car.year, car.color,
               u.full_name as created_by_name
        FROM rentals r
        JOIN customers c ON r.customer_id = c.id
        JOIN cars car ON r.car_id = car.id
        LEFT JOIN users u ON r.created_by = u.id
        WHERE r.id = ?
    ");
    $stmt->execute([$rental_id]);
    $rental = $stmt->fetch();
    
    if (!$rental) {
        $_SESSION['error'] = 'الحجز غير موجود';
        redirect('rentals.php');
    }
    
    // Get payments
    $payments_stmt = $db->prepare("
        SELECT p.*, u.full_name as created_by_name
        FROM payments p
        LEFT JOIN users u ON p.created_by = u.id
        WHERE p.rental_id = ?
        ORDER BY p.payment_date DESC
    ");
    $payments_stmt->execute([$rental_id]);
    $payments = $payments_stmt->fetchAll();
    
    // Get penalties
    $penalties_stmt = $db->prepare("
        SELECT * FROM penalties
        WHERE rental_id = ?
        ORDER BY created_at DESC
    ");
    $penalties_stmt->execute([$rental_id]);
    $penalties = $penalties_stmt->fetchAll();
    
} catch (Exception $e) {
    $_SESSION['error'] = 'خطأ في جلب بيانات الحجز';
    redirect('rentals.php');
}

$page_title = 'تفاصيل الحجز - ' . SITE_NAME;
include 'includes/header.php';
include 'includes/sidebar.php';
?>

<div class="main-content">
    <div class="top-bar">
        <div class="welcome-text">
            <h5><i class="fas fa-file-contract me-2"></i>الحجز رقم: <?php echo $rental['rental_number']; ?></h5>
        </div>
        <div class="top-bar-right">
            <a href="rental_edit.php?id=<?php echo $rental['id']; ?>" class="btn btn-primary">
                <i class="fas fa-edit me-2"></i>تعديل
            </a>
            <button onclick="window.print()" class="btn btn-info">
                <i class="fas fa-print me-2"></i>طباعة
            </button>
            <a href="rentals.php" class="btn btn-secondary">
                <i class="fas fa-arrow-right me-2"></i>عودة
            </a>
        </div>
    </div>

    <div class="row">
        <!-- Rental Info -->
        <div class="col-md-6">
            <div class="table-container">
                <h5 class="mb-3"><i class="fas fa-info-circle me-2"></i>معلومات الحجز</h5>
                <table class="table table-sm">
                    <tr>
                        <th width="40%">رقم الحجز:</th>
                        <td><strong><?php echo $rental['rental_number']; ?></strong></td>
                    </tr>
                    <tr>
                        <th>تاريخ البدء:</th>
                        <td><?php echo formatDate($rental['start_date']); ?></td>
                    </tr>
                    <tr>
                        <th>تاريخ الانتهاء:</th>
                        <td><?php echo formatDate($rental['end_date']); ?></td>
                    </tr>
                    <tr>
                        <th>عدد الأيام:</th>
                        <td><?php echo $rental['total_days']; ?> يوم</td>
                    </tr>
                    <tr>
                        <th>الحالة:</th>
                        <td><?php echo getRentalStatusBadge($rental['status']); ?></td>
                    </tr>
                    <tr>
                        <th>حالة الدفع:</th>
                        <td><?php echo getPaymentStatusBadge($rental['payment_status']); ?></td>
                    </tr>
                    <tr>
                        <th>مكان الاستلام:</th>
                        <td><?php echo htmlspecialchars($rental['pickup_location']); ?></td>
                    </tr>
                    <tr>
                        <th>مكان التسليم:</th>
                        <td><?php echo htmlspecialchars($rental['return_location']); ?></td>
                    </tr>
                    <tr>
                        <th>تم الإنشاء بواسطة:</th>
                        <td><?php echo $rental['created_by_name']; ?></td>
                    </tr>
                    <tr>
                        <th>تاريخ الإنشاء:</th>
                        <td><?php echo formatDateTime($rental['created_at']); ?></td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- Customer Info -->
        <div class="col-md-6">
            <div class="table-container">
                <h5 class="mb-3"><i class="fas fa-user me-2"></i>بيانات العميل</h5>
                <table class="table table-sm">
                    <tr>
                        <th width="40%">الاسم:</th>
                        <td><strong><?php echo $rental['customer_name']; ?></strong></td>
                    </tr>
                    <tr>
                        <th>رقم الهوية:</th>
                        <td><?php echo $rental['id_number']; ?></td>
                    </tr>
                    <tr>
                        <th>الهاتف:</th>
                        <td><?php echo $rental['customer_phone']; ?></td>
                    </tr>
                    <tr>
                        <th>البريد:</th>
                        <td><?php echo $rental['customer_email'] ?: '-'; ?></td>
                    </tr>
                    <tr>
                        <th>رخصة القيادة:</th>
                        <td><?php echo $rental['driver_license'] ?: '-'; ?></td>
                    </tr>
                    <tr>
                        <th>العنوان:</th>
                        <td><?php echo htmlspecialchars($rental['address']) ?: '-'; ?></td>
                    </tr>
                </table>
                
                <h5 class="mb-3 mt-4"><i class="fas fa-car me-2"></i>بيانات السيارة</h5>
                <table class="table table-sm">
                    <tr>
                        <th width="40%">السيارة:</th>
                        <td><strong><?php echo $rental['brand'] . ' ' . $rental['model']; ?></strong></td>
                    </tr>
                    <tr>
                        <th>رقم اللوحة:</th>
                        <td><?php echo $rental['plate_number']; ?></td>
                    </tr>
                    <tr>
                        <th>السنة:</th>
                        <td><?php echo $rental['year']; ?></td>
                    </tr>
                    <tr>
                        <th>اللون:</th>
                        <td><?php echo $rental['color']; ?></td>
                    </tr>
                </table>
            </div>
        </div>
    </div>

    <!-- Financial Details -->
    <div class="table-container mt-4">
        <h5 class="mb-3"><i class="fas fa-dollar-sign me-2"></i>التفاصيل المالية</h5>
        <table class="table table-bordered">
            <tr>
                <th width="30%">الأجرة اليومية:</th>
                <td><?php echo formatCurrency($rental['daily_rate']); ?></td>
                <th width="30%">عدد الأيام:</th>
                <td><?php echo $rental['total_days']; ?></td>
            </tr>
            <tr>
                <th>المجموع الفرعي:</th>
                <td colspan="3"><strong><?php echo formatCurrency($rental['subtotal']); ?></strong></td>
            </tr>
            <tr>
                <th>الخصم:</th>
                <td><?php echo formatCurrency($rental['discount_amount']); ?></td>
                <th>سبب الخصم:</th>
                <td><?php echo htmlspecialchars($rental['discount_reason']) ?: '-'; ?></td>
            </tr>
            <tr>
                <th>الضريبة (<?php echo TAX_RATE; ?>%):</th>
                <td colspan="3"><?php echo formatCurrency($rental['tax_amount']); ?></td>
            </tr>
            <tr>
                <th>الغرامات:</th>
                <td colspan="3"><?php echo formatCurrency($rental['penalty_amount']); ?></td>
            </tr>
            <tr class="table-primary">
                <th>المجموع الكلي:</th>
                <td colspan="3"><strong class="fs-5"><?php echo formatCurrency($rental['total_amount']); ?></strong></td>
            </tr>
            <tr class="table-success">
                <th>المدفوع:</th>
                <td colspan="3"><strong><?php echo formatCurrency($rental['paid_amount']); ?></strong></td>
            </tr>
            <tr class="table-warning">
                <th>المتبقي:</th>
                <td colspan="3"><strong><?php echo formatCurrency($rental['remaining_amount']); ?></strong></td>
            </tr>
        </table>
    </div>

    <!-- Payments History -->
    <?php if (count($payments) > 0): ?>
    <div class="table-container mt-4">
        <h5 class="mb-3"><i class="fas fa-money-bill-wave me-2"></i>سجل المدفوعات</h5>
        <table class="table table-hover">
            <thead>
                <tr>
                    <th>رقم الدفعة</th>
                    <th>التاريخ</th>
                    <th>المبلغ</th>
                    <th>طريقة الدفع</th>
                    <th>الرقم المرجعي</th>
                    <th>تم بواسطة</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($payments as $payment): ?>
                <tr>
                    <td><?php echo $payment['payment_number']; ?></td>
                    <td><?php echo formatDate($payment['payment_date']); ?></td>
                    <td><strong><?php echo formatCurrency($payment['amount']); ?></strong></td>
                    <td><?php echo PAYMENT_METHODS[$payment['payment_method']]; ?></td>
                    <td><?php echo $payment['reference_number'] ?: '-'; ?></td>
                    <td><?php echo $payment['created_by_name']; ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>

    <!-- Penalties -->
    <?php if (count($penalties) > 0): ?>
    <div class="table-container mt-4">
        <h5 class="mb-3"><i class="fas fa-exclamation-triangle me-2"></i>الغرامات</h5>
        <table class="table table-hover">
            <thead>
                <tr>
                    <th>النوع</th>
                    <th>المبلغ</th>
                    <th>الوصف</th>
                    <th>الحالة</th>
                    <th>التاريخ</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($penalties as $penalty): ?>
                <tr>
                    <td><?php echo PENALTY_TYPES[$penalty['penalty_type']]; ?></td>
                    <td><strong><?php echo formatCurrency($penalty['amount']); ?></strong></td>
                    <td><?php echo htmlspecialchars($penalty['description']); ?></td>
                    <td>
                        <span class="badge bg-<?php echo $penalty['status'] == 'paid' ? 'success' : 'warning'; ?>">
                            <?php echo $penalty['status']; ?>
                        </span>
                    </td>
                    <td><?php echo formatDateTime($penalty['created_at']); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>

    <?php if ($rental['notes']): ?>
    <div class="table-container mt-4">
        <h5 class="mb-3"><i class="fas fa-sticky-note me-2"></i>ملاحظات</h5>
        <p><?php echo nl2br(htmlspecialchars($rental['notes'])); ?></p>
    </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>