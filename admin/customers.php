<?php
require_once '../config/settings.php';
require_once '../core/Auth.php';

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    redirect(ADMIN_URL . '/login.php');
}

$db = Database::getInstance()->getConnection();

// Get all customers
$stmt = $db->query("
    SELECT c.*, 
           COUNT(DISTINCT r.id) as total_rentals,
           COALESCE(SUM(r.total_amount), 0) as total_spent
    FROM customers c
    LEFT JOIN rentals r ON c.id = r.customer_id
    GROUP BY c.id
    ORDER BY c.created_at DESC
");
$customers = $stmt->fetchAll();

$page_title = 'إدارة العملاء - ' . SITE_NAME;
include 'includes/header.php';
include 'includes/sidebar.php';
?>

<div class="main-content">
    <div class="top-bar">
        <div class="welcome-text">
            <h5><i class="fas fa-users me-2"></i>إدارة العملاء</h5>
            <p>إدارة قاعدة بيانات العملاء ونظام الولاء</p>
        </div>
        <div class="top-bar-right">
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCustomerModal">
                <i class="fas fa-user-plus me-2"></i>إضافة عميل جديد
            </button>
        </div>
    </div>

    <!-- Statistics -->
    <div class="row g-3 mb-4">
        <?php
        $stats = [
            'total' => count($customers),
            'active' => 0,
            'bronze' => 0,
            'silver' => 0,
            'gold' => 0,
            'platinum' => 0
        ];
        foreach ($customers as $customer) {
            if ($customer['status'] === 'active') $stats['active']++;
            $stats[$customer['loyalty_level']]++;
        }
        ?>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon" style="background: rgba(33, 150, 243, 0.1); color: #2196F3;">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-value"><?php echo $stats['total']; ?></div>
                <div class="stat-label">إجمالي العملاء</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon" style="background: rgba(76, 175, 80, 0.1); color: #4CAF50;">
                    <i class="fas fa-user-check"></i>
                </div>
                <div class="stat-value"><?php echo $stats['active']; ?></div>
                <div class="stat-label">عملاء نشطين</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon" style="background: rgba(255, 193, 7, 0.1); color: #FFC107;">
                    <i class="fas fa-crown"></i>
                </div>
                <div class="stat-value"><?php echo $stats['gold'] + $stats['platinum']; ?></div>
                <div class="stat-label">عملاء VIP</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon" style="background: rgba(156, 39, 176, 0.1); color: #9C27B0;">
                    <i class="fas fa-star"></i>
                </div>
                <div class="stat-value"><?php echo $stats['platinum']; ?></div>
                <div class="stat-label">بلاتينيوم</div>
            </div>
        </div>
    </div>

    <!-- Customers Table -->
    <div class="table-container">
        <h5 class="mb-3">
            <i class="fas fa-list text-primary"></i>
            قائمة العملاء
        </h5>
        <div class="table-responsive">
            <table class="table table-hover data-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>الاسم الكامل</th>
                        <th>الهوية</th>
                        <th>الهاتف</th>
                        <th>المدينة</th>
                        <th>مستوى الولاء</th>
                        <th>النقاط</th>
                        <th>الحجوزات</th>
                        <th>الإنفاق الكلي</th>
                        <th>الحالة</th>
                        <th>الإجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($customers as $index => $customer): ?>
                    <tr>
                        <td><?php echo $index + 1; ?></td>
                        <td><strong><?php echo $customer['full_name']; ?></strong></td>
                        <td><?php echo $customer['id_number']; ?></td>
                        <td><?php echo $customer['phone']; ?></td>
                        <td><?php echo $customer['city']; ?></td>
                        <td>
                            <?php
                            $loyaltyColors = [
                                'bronze' => 'secondary',
                                'silver' => 'info',
                                'gold' => 'warning',
                                'platinum' => 'dark'
                            ];
                            $color = $loyaltyColors[$customer['loyalty_level']];
                            ?>
                            <span class="badge bg-<?php echo $color; ?>">
                                <?php echo LOYALTY_LEVELS[$customer['loyalty_level']]; ?>
                            </span>
                        </td>
                        <td><strong><?php echo number_format($customer['loyalty_points']); ?></strong> نقطة</td>
                        <td><?php echo $customer['total_rentals']; ?> حجز</td>
                        <td><strong><?php echo formatCurrency($customer['total_spent']); ?></strong></td>
                        <td>
                            <?php
                            $statusColors = [
                                'active' => 'success',
                                'inactive' => 'secondary',
                                'blacklist' => 'danger'
                            ];
                            $color = $statusColors[$customer['status']];
                            ?>
                            <span class="badge bg-<?php echo $color; ?>">
                                <?php echo CUSTOMER_STATUS[$customer['status']]; ?>
                            </span>
                        </td>
                        <td>
                            <div class="btn-group" role="group">
                                <button class="btn btn-sm btn-info" title="عرض">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <button class="btn btn-sm btn-primary" title="تعديل">
                                    <i class="fas fa-edit"></i>
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