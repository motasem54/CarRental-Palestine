<?php
require_once '../config/settings.php';
require_once '../core/Auth.php';

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    redirect(ADMIN_URL . '/login.php');
}

$db = Database::getInstance()->getConnection();

if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['error'] = 'معرف العميل غير موجود';
    redirect('customers.php');
}

$customer_id = (int)$_GET['id'];

// Fetch customer details
try {
    $stmt = $db->prepare("SELECT * FROM customers WHERE id = ?");
    $stmt->execute([$customer_id]);
    $customer = $stmt->fetch();
    
    if (!$customer) {
        $_SESSION['error'] = 'العميل غير موجود';
        redirect('customers.php');
    }
    
    // Get rental history
    $rentals_stmt = $db->prepare("
        SELECT r.*, car.brand, car.model, car.plate_number
        FROM rentals r
        JOIN cars car ON r.car_id = car.id
        WHERE r.customer_id = ?
        ORDER BY r.start_date DESC
    ");
    $rentals_stmt->execute([$customer_id]);
    $rentals = $rentals_stmt->fetchAll();
    
    // Get statistics
    $stats_stmt = $db->prepare("
        SELECT 
            COUNT(*) as total_rentals,
            SUM(total_amount) as total_spent,
            SUM(paid_amount) as total_paid,
            SUM(remaining_amount) as total_remaining
        FROM rentals
        WHERE customer_id = ?
    ");
    $stats_stmt->execute([$customer_id]);
    $stats = $stats_stmt->fetch();
    
} catch (Exception $e) {
    $_SESSION['error'] = 'خطأ في جلب بيانات العميل';
    redirect('customers.php');
}

$page_title = 'تفاصيل العميل - ' . SITE_NAME;
include 'includes/header.php';
include 'includes/sidebar.php';
?>

<div class="main-content">
    <div class="top-bar">
        <div class="welcome-text">
            <h5><i class="fas fa-user me-2"></i><?php echo htmlspecialchars($customer['full_name']); ?></h5>
        </div>
        <div class="top-bar-right">
            <a href="customer_edit.php?id=<?php echo $customer['id']; ?>" class="btn btn-primary">
                <i class="fas fa-edit me-2"></i>تعديل
            </a>
            <a href="rental_add.php?customer_id=<?php echo $customer['id']; ?>" class="btn btn-success">
                <i class="fas fa-plus me-2"></i>حجز جديد
            </a>
            <a href="customers.php" class="btn btn-secondary">
                <i class="fas fa-arrow-right me-2"></i>عودة
            </a>
        </div>
    </div>

    <div class="row">
        <!-- Customer Info -->
        <div class="col-md-6">
            <div class="table-container">
                <h5 class="mb-3"><i class="fas fa-address-card me-2"></i>المعلومات الشخصية</h5>
                <table class="table table-sm">
                    <tr>
                        <th width="40%">الاسم الكامل:</th>
                        <td><strong><?php echo htmlspecialchars($customer['full_name']); ?></strong></td>
                    </tr>
                    <tr>
                        <th>رقم الهوية:</th>
                        <td><?php echo htmlspecialchars($customer['id_number']); ?></td>
                    </tr>
                    <tr>
                        <th>رقم الهاتف:</th>
                        <td>
                            <a href="tel:<?php echo $customer['phone']; ?>">
                                <i class="fas fa-phone me-1"></i><?php echo htmlspecialchars($customer['phone']); ?>
                            </a>
                        </td>
                    </tr>
                    <tr>
                        <th>البريد الإلكتروني:</th>
                        <td>
                            <?php if ($customer['email']): ?>
                                <a href="mailto:<?php echo $customer['email']; ?>">
                                    <i class="fas fa-envelope me-1"></i><?php echo htmlspecialchars($customer['email']); ?>
                                </a>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <th>تاريخ الميلاد:</th>
                        <td>
                            <?php 
                            if ($customer['date_of_birth'] && $customer['date_of_birth'] != '0000-00-00') {
                                echo formatDate($customer['date_of_birth']);
                                $age = calculateAge($customer['date_of_birth']);
                                echo " ($age سنة)";
                            } else {
                                echo '-';
                            }
                            ?>
                        </td>
                    </tr>
                    <tr>
                        <th>المدينة:</th>
                        <td><?php echo htmlspecialchars($customer['city']); ?></td>
                    </tr>
                    <tr>
                        <th>العنوان:</th>
                        <td><?php echo htmlspecialchars($customer['address']) ?: '-'; ?></td>
                    </tr>
                    <tr>
                        <th>الحالة:</th>
                        <td>
                            <?php 
                            $status_class = $customer['status'] == 'active' ? 'success' : 
                                          ($customer['status'] == 'blacklist' ? 'danger' : 'secondary');
                            ?>
                            <span class="badge bg-<?php echo $status_class; ?>">
                                <?php echo CUSTOMER_STATUS[$customer['status']]; ?>
                            </span>
                        </td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- License & Stats -->
        <div class="col-md-6">
            <div class="table-container">
                <h5 class="mb-3"><i class="fas fa-id-card me-2"></i>رخصة القيادة</h5>
                <table class="table table-sm">
                    <tr>
                        <th width="40%">رقم الرخصة:</th>
                        <td><?php echo htmlspecialchars($customer['driver_license']) ?: '-'; ?></td>
                    </tr>
                    <tr>
                        <th>تاريخ الانتهاء:</th>
                        <td>
                            <?php 
                            if ($customer['license_expiry'] && $customer['license_expiry'] != '0000-00-00') {
                                $expiry = formatDate($customer['license_expiry']);
                                $is_expired = strtotime($customer['license_expiry']) < time();
                                $badge = $is_expired ? 'danger' : 'success';
                                echo "<span class='badge bg-$badge'>$expiry</span>";
                            } else {
                                echo '-';
                            }
                            ?>
                        </td>
                    </tr>
                </table>

                <h5 class="mb-3 mt-4"><i class="fas fa-chart-line me-2"></i>الإحصائيات</h5>
                <div class="row text-center">
                    <div class="col-6 mb-3">
                        <div class="stats-box">
                            <div class="stats-number text-primary"><?php echo $stats['total_rentals']; ?></div>
                            <div class="stats-label">إجمالي الحجوزات</div>
                        </div>
                    </div>
                    <div class="col-6 mb-3">
                        <div class="stats-box">
                            <div class="stats-number text-success"><?php echo formatCurrency($stats['total_spent']); ?></div>
                            <div class="stats-label">إجمالي الإنفاق</div>
                        </div>
                    </div>
                    <div class="col-6 mb-3">
                        <div class="stats-box">
                            <div class="stats-number text-info"><?php echo formatCurrency($stats['total_paid']); ?></div>
                            <div class="stats-label">إجمالي المدفوع</div>
                        </div>
                    </div>
                    <div class="col-6 mb-3">
                        <div class="stats-box">
                            <div class="stats-number text-warning"><?php echo formatCurrency($stats['total_remaining']); ?></div>
                            <div class="stats-label">المتبقي</div>
                        </div>
                    </div>
                </div>

                <h5 class="mb-3 mt-4"><i class="fas fa-star me-2"></i>برنامج الولاء</h5>
                <table class="table table-sm">
                    <tr>
                        <th width="40%">النقاط:</th>
                        <td><strong><?php echo $customer['loyalty_points']; ?></strong></td>
                    </tr>
                    <tr>
                        <th>المستوى:</th>
                        <td>
                            <?php 
                            $level_class = [
                                'bronze' => 'secondary',
                                'silver' => 'info',
                                'gold' => 'warning',
                                'platinum' => 'primary'
                            ];
                            $level = $customer['loyalty_level'];
                            ?>
                            <span class="badge bg-<?php echo $level_class[$level]; ?>">
                                <?php echo LOYALTY_LEVELS[$level]; ?>
                            </span>
                        </td>
                    </tr>
                </table>
            </div>
        </div>
    </div>

    <?php if ($customer['notes']): ?>
    <div class="table-container mt-4">
        <h5 class="mb-3"><i class="fas fa-sticky-note me-2"></i>ملاحظات</h5>
        <p><?php echo nl2br(htmlspecialchars($customer['notes'])); ?></p>
    </div>
    <?php endif; ?>

    <!-- Rental History -->
    <?php if (count($rentals) > 0): ?>
    <div class="table-container mt-4">
        <h5 class="mb-3"><i class="fas fa-history me-2"></i>سجل الحجوزات</h5>
        <table class="table table-hover">
            <thead>
                <tr>
                    <th>رقم الحجز</th>
                    <th>السيارة</th>
                    <th>تاريخ البدء</th>
                    <th>تاريخ الانتهاء</th>
                    <th>الأيام</th>
                    <th>الحالة</th>
                    <th>الدفع</th>
                    <th>المبلغ</th>
                    <th>إجراءات</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rentals as $rental): ?>
                <tr>
                    <td><?php echo $rental['rental_number']; ?></td>
                    <td><?php echo $rental['brand'] . ' ' . $rental['model']; ?></td>
                    <td><?php echo formatDate($rental['start_date']); ?></td>
                    <td><?php echo formatDate($rental['end_date']); ?></td>
                    <td><?php echo $rental['total_days']; ?></td>
                    <td><?php echo getRentalStatusBadge($rental['status']); ?></td>
                    <td><?php echo getPaymentStatusBadge($rental['payment_status']); ?></td>
                    <td><strong><?php echo formatCurrency($rental['total_amount']); ?></strong></td>
                    <td>
                        <a href="rental_view.php?id=<?php echo $rental['id']; ?>" class="btn btn-sm btn-info">
                            <i class="fas fa-eye"></i>
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php else: ?>
    <div class="table-container mt-4">
        <div class="alert alert-info text-center">
            <i class="fas fa-info-circle me-2"></i>لا توجد حجوزات لهذا العميل
        </div>
    </div>
    <?php endif; ?>
</div>

<style>
.stats-box {
    padding: 20px;
    background: #f8f9fa;
    border-radius: 8px;
    border: 1px solid #dee2e6;
}
.stats-number {
    font-size: 24px;
    font-weight: bold;
    margin-bottom: 5px;
}
.stats-label {
    font-size: 13px;
    color: #6c757d;
}
</style>

<?php include 'includes/footer.php'; ?>