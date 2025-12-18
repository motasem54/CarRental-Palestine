<?php
require_once '../config/settings.php';
require_once '../core/Auth.php';

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    redirect(ADMIN_URL . '/login.php');
}

$db = Database::getInstance()->getConnection();

// Get all payments
$stmt = $db->query("
    SELECT p.*, r.rental_number, c.full_name as customer_name, u.username as received_by_name
    FROM payments p
    JOIN rentals r ON p.rental_id = r.id
    JOIN customers c ON r.customer_id = c.id
    LEFT JOIN users u ON p.received_by = u.id
    ORDER BY p.created_at DESC
");
$payments = $stmt->fetchAll();

$page_title = 'المدفوعات - ' . SITE_NAME;
include 'includes/header.php';
include 'includes/sidebar.php';
?>

<div class="main-content">
    <div class="top-bar">
        <div class="welcome-text">
            <h5><i class="fas fa-money-bill-wave me-2"></i>إدارة المدفوعات</h5>
            <p>متابعة جميع المدفوعات والإيرادات</p>
        </div>
    </div>

    <!-- Statistics -->
    <div class="row g-3 mb-4">
        <?php
        $stats = ['total' => 0, 'cash' => 0, 'card' => 0, 'transfer' => 0];
        foreach ($payments as $payment) {
            $stats['total'] += $payment['amount'];
            $stats[$payment['payment_method']] += $payment['amount'];
        }
        ?>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon" style="background: rgba(76, 175, 80, 0.1); color: #4CAF50;">
                    <i class="fas fa-coins"></i>
                </div>
                <div class="stat-value"><?php echo formatCurrency($stats['total']); ?></div>
                <div class="stat-label">إجمالي المدفوعات</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon" style="background: rgba(33, 150, 243, 0.1); color: #2196F3;">
                    <i class="fas fa-money-bill"></i>
                </div>
                <div class="stat-value"><?php echo formatCurrency($stats['cash']); ?></div>
                <div class="stat-label">نقدي</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon" style="background: rgba(156, 39, 176, 0.1); color: #9C27B0;">
                    <i class="fas fa-credit-card"></i>
                </div>
                <div class="stat-value"><?php echo formatCurrency($stats['card']); ?></div>
                <div class="stat-label">بطاقة</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon" style="background: rgba(255, 152, 0, 0.1); color: #FF9800;">
                    <i class="fas fa-exchange-alt"></i>
                </div>
                <div class="stat-value"><?php echo formatCurrency($stats['transfer']); ?></div>
                <div class="stat-label">تحويل</div>
            </div>
        </div>
    </div>

    <!-- Payments Table -->
    <div class="table-container">
        <h5 class="mb-3">
            <i class="fas fa-list text-primary"></i>
            قائمة المدفوعات
        </h5>
        <div class="table-responsive">
            <table class="table table-hover data-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>رقم الحجز</th>
                        <th>العميل</th>
                        <th>المبلغ</th>
                        <th>الطريقة</th>
                        <th>التاريخ</th>
                        <th>استلمه</th>
                        <th>ملاحظات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($payments as $index => $payment): ?>
                    <tr>
                        <td><?php echo $index + 1; ?></td>
                        <td><strong><?php echo $payment['rental_number']; ?></strong></td>
                        <td><?php echo $payment['customer_name']; ?></td>
                        <td><strong class="text-success"><?php echo formatCurrency($payment['amount']); ?></strong></td>
                        <td>
                            <?php
                            $methodColors = [
                                'cash' => 'success',
                                'card' => 'primary',
                                'transfer' => 'info',
                                'check' => 'warning'
                            ];
                            $color = $methodColors[$payment['payment_method']];
                            ?>
                            <span class="badge bg-<?php echo $color; ?>">
                                <?php echo PAYMENT_METHODS[$payment['payment_method']]; ?>
                            </span>
                        </td>
                        <td><?php echo formatDate($payment['created_at'], 'd/m/Y H:i'); ?></td>
                        <td><?php echo $payment['received_by_name']; ?></td>
                        <td><?php echo $payment['notes']; ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>