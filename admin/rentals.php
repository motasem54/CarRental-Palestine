<?php
require_once '../config/settings.php';
require_once '../core/Auth.php';

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    redirect(ADMIN_URL . '/login.php');
}

$db = Database::getInstance()->getConnection();

// Get all rentals
$stmt = $db->query("
    SELECT r.*, 
           c.full_name as customer_name, c.phone as customer_phone,
           ca.plate_number, ca.brand, ca.model
    FROM rentals r
    JOIN customers c ON r.customer_id = c.id
    JOIN cars ca ON r.car_id = ca.id
    ORDER BY r.created_at DESC
");
$rentals = $stmt->fetchAll();

$page_title = 'إدارة الحجوزات - ' . SITE_NAME;
include 'includes/header.php';
include 'includes/sidebar.php';
?>

<div class="main-content">
    <div class="top-bar">
        <div class="welcome-text">
            <h5><i class="fas fa-calendar-alt me-2"></i>إدارة الحجوزات والإيجار</h5>
            <p>متابعة جميع الحجوزات والعقود</p>
        </div>
        <div class="top-bar-right">
            <a href="rental_add.php" class="btn btn-primary">
                <i class="fas fa-plus me-2"></i>حجز جديد
            </a>
        </div>
    </div>

    <!-- Statistics -->
    <div class="row g-3 mb-4">
        <?php
        $stats = ['pending' => 0, 'confirmed' => 0, 'active' => 0, 'completed' => 0];
        $totalRevenue = 0;
        $pendingPayments = 0;
        foreach ($rentals as $rental) {
            $stats[$rental['status']]++;
            $totalRevenue += $rental['total_amount'];
            $pendingPayments += $rental['remaining_amount'];
        }
        ?>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon" style="background: rgba(255, 152, 0, 0.1); color: #FF9800;">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stat-value"><?php echo $stats['pending']; ?></div>
                <div class="stat-label">قيد الانتظار</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon" style="background: rgba(76, 175, 80, 0.1); color: #4CAF50;">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-value"><?php echo $stats['active']; ?></div>
                <div class="stat-label">نشطة حالياً</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon" style="background: rgba(33, 150, 243, 0.1); color: #2196F3;">
                    <i class="fas fa-coins"></i>
                </div>
                <div class="stat-value"><?php echo formatCurrency($totalRevenue); ?></div>
                <div class="stat-label">إجمالي الإيرادات</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon" style="background: rgba(244, 67, 54, 0.1); color: #F44336;">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <div class="stat-value"><?php echo formatCurrency($pendingPayments); ?></div>
                <div class="stat-label">مدفوعات معلقة</div>
            </div>
        </div>
    </div>

    <!-- Rentals Table -->
    <div class="table-container">
        <h5 class="mb-3">
            <i class="fas fa-list text-primary"></i>
            قائمة الحجوزات
        </h5>
        <div class="table-responsive">
            <table class="table table-hover data-table">
                <thead>
                    <tr>
                        <th>رقم الحجز</th>
                        <th>العميل</th>
                        <th>السيارة</th>
                        <th>التاريخ</th>
                        <th>الأيام</th>
                        <th>المبلغ</th>
                        <th>المدفوع</th>
                        <th>المتبقي</th>
                        <th>الحالة</th>
                        <th>الإجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rentals as $rental): ?>
                    <tr>
                        <td>
                            <strong><?php echo $rental['rental_number']; ?></strong><br>
                            <small class="text-muted"><?php echo formatDate($rental['created_at'], 'd/m/Y'); ?></small>
                        </td>
                        <td>
                            <?php echo $rental['customer_name']; ?><br>
                            <small class="text-muted"><?php echo $rental['customer_phone']; ?></small>
                        </td>
                        <td><?php echo $rental['brand'] . ' ' . $rental['model']; ?><br>
                            <small class="text-muted"><?php echo $rental['plate_number']; ?></small>
                        </td>
                        <td>
                            <?php echo formatDate($rental['start_date'], 'd/m'); ?> - 
                            <?php echo formatDate($rental['end_date'], 'd/m/Y'); ?>
                        </td>
                        <td><span class="badge bg-info"><?php echo $rental['total_days']; ?> يوم</span></td>
                        <td><strong><?php echo formatCurrency($rental['total_amount']); ?></strong></td>
                        <td><?php echo formatCurrency($rental['paid_amount']); ?></td>
                        <td>
                            <?php if ($rental['remaining_amount'] > 0): ?>
                            <span class="text-danger fw-bold"><?php echo formatCurrency($rental['remaining_amount']); ?></span>
                            <?php else: ?>
                            <span class="text-success">مدفوع</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php
                            $statusColors = [
                                'pending' => 'warning',
                                'confirmed' => 'info',
                                'active' => 'success',
                                'completed' => 'secondary',
                                'cancelled' => 'danger'
                            ];
                            $color = $statusColors[$rental['status']];
                            ?>
                            <span class="badge bg-<?php echo $color; ?>">
                                <?php echo RENTAL_STATUS[$rental['status']]; ?>
                            </span>
                        </td>
                        <td>
                            <div class="btn-group" role="group">
                                <button class="btn btn-sm btn-info" title="عرض">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <button class="btn btn-sm btn-success" title="دفعة">
                                    <i class="fas fa-dollar-sign"></i>
                                </button>
                                <button class="btn btn-sm btn-primary" title="عقد">
                                    <i class="fas fa-file-contract"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>