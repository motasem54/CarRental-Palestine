<?php
require_once '../config/settings.php';
require_once '../core/Auth.php';

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    redirect(ADMIN_URL . '/login.php');
}

$db = Database::getInstance()->getConnection();
$car_id = isset($_GET['car_id']) ? (int)$_GET['car_id'] : 0;

if ($car_id <= 0) {
    redirect('cars.php');
}

// Get car details
$stmt = $db->prepare("SELECT * FROM cars WHERE id = ?");
$stmt->execute([$car_id]);
$car = $stmt->fetch();

if (!$car) {
    redirect('cars.php');
}

// Get all rental records for this car
$stmt = $db->prepare("
    SELECT r.*, c.full_name as customer_name, c.phone as customer_phone
    FROM rentals r
    JOIN customers c ON r.customer_id = c.id
    WHERE r.car_id = ?
    ORDER BY r.start_date DESC, r.created_at DESC
");
$stmt->execute([$car_id]);
$rentals = $stmt->fetchAll();

$statusNames = [
    'active' => 'نشط',
    'completed' => 'مكتمل',
    'cancelled' => 'ملغى'
];

$totalRevenue = array_sum(array_column($rentals, 'total_amount'));
$totalDays = 0;
foreach ($rentals as $r) {
    $start = new DateTime($r['start_date']);
    $end = new DateTime($r['end_date']);
    $totalDays += $start->diff($end)->days + 1;
}

$page_title = 'سجل تأجير السيارة - ' . SITE_NAME;
include 'includes/header.php';
include 'includes/sidebar.php';
?>

<div class="main-content">
    <div class="top-bar">
        <div class="welcome-text">
            <h5><i class="fas fa-history me-2"></i>سجل تأجير السيارة</h5>
            <p><?php echo $car['brand'] . ' ' . $car['model'] . ' ' . $car['year']; ?> - <?php echo $car['plate_number']; ?></p>
        </div>
        <div class="top-bar-right">
            <a href="rental_add.php?car_id=<?php echo $car_id; ?>" class="btn btn-primary">
                <i class="fas fa-plus me-2"></i>إضافة تأجير
            </a>
            <a href="cars.php" class="btn btn-secondary">
                <i class="fas fa-arrow-right me-2"></i>رجوع
            </a>
        </div>
    </div>

    <!-- Car Info Card -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="stat-card" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white;">
                <?php if ($car['image']): ?>
                    <img src="<?php echo UPLOADS_URL . '/cars/' . $car['image']; ?>" 
                         alt="<?php echo $car['brand']; ?>" 
                         style="width: 100%; height: 150px; object-fit: cover; border-radius: 12px; margin-bottom: 15px;">
                <?php else: ?>
                    <div style="width: 100%; height: 150px; background: rgba(255,255,255,0.1); border-radius: 12px; display: flex; align-items: center; justify-content: center; margin-bottom: 15px;">
                        <i class="fas fa-car fa-4x" style="opacity: 0.5;"></i>
                    </div>
                <?php endif; ?>
                <h5 style="color: white;"><?php echo $car['brand'] . ' ' . $car['model']; ?></h5>
                <p style="opacity: 0.9;"><?php echo $car['plate_number']; ?> - <?php echo $car['year']; ?></p>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon" style="background: rgba(33, 150, 243, 0.1); color: #2196F3;">
                    <i class="fas fa-file-contract"></i>
                </div>
                <div class="stat-value"><?php echo count($rentals); ?></div>
                <div class="stat-label">إجمالي عمليات التأجير</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon" style="background: rgba(76, 175, 80, 0.1); color: #4CAF50;">
                    <i class="fas fa-shekel-sign"></i>
                </div>
                <div class="stat-value"><?php echo number_format($totalRevenue, 2); ?> ₪</div>
                <div class="stat-label">إجمالي الإيرادات</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon" style="background: rgba(255, 152, 0, 0.1); color: #FF9800;">
                    <i class="fas fa-calendar-alt"></i>
                </div>
                <div class="stat-value"><?php echo $totalDays; ?></div>
                <div class="stat-label">إجمالي الأيام</div>
            </div>
        </div>
    </div>

    <!-- Rentals Timeline -->
    <div class="stat-card">
        <h5 class="mb-4">
            <i class="fas fa-history text-primary"></i>
            سجل التأجير
        </h5>
        
        <?php if (count($rentals) > 0): ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>العميل</th>
                            <th>تاريخ البداية</th>
                            <th>تاريخ النهاية</th>
                            <th>الأيام</th>
                            <th>المبلغ</th>
                            <th>الحالة</th>
                            <th>الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rentals as $index => $r): 
                            $start = new DateTime($r['start_date']);
                            $end = new DateTime($r['end_date']);
                            $days = $start->diff($end)->days + 1;
                        ?>
                        <tr>
                            <td><?php echo $index + 1; ?></td>
                            <td>
                                <strong><?php echo $r['customer_name']; ?></strong><br>
                                <small class="text-muted"><i class="fas fa-phone"></i> <?php echo $r['customer_phone']; ?></small>
                            </td>
                            <td><?php echo date('d/m/Y', strtotime($r['start_date'])); ?></td>
                            <td><?php echo date('d/m/Y', strtotime($r['end_date'])); ?></td>
                            <td><span class="badge bg-secondary"><?php echo $days; ?> يوم</span></td>
                            <td><strong class="text-success"><?php echo number_format($r['total_amount'], 2); ?> ₪</strong></td>
                            <td>
                                <?php
                                $statusColors = [
                                    'active' => 'primary',
                                    'completed' => 'success',
                                    'cancelled' => 'danger'
                                ];
                                $color = $statusColors[$r['status']] ?? 'secondary';
                                ?>
                                <span class="badge bg-<?php echo $color; ?>">
                                    <?php echo $statusNames[$r['status']] ?? $r['status']; ?>
                                </span>
                            </td>
                            <td>
                                <div class="btn-group" role="group">
                                    <a href="rental_view.php?id=<?php echo $r['id']; ?>" class="btn btn-sm btn-info" title="عرض">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="rental_edit.php?id=<?php echo $r['id']; ?>" class="btn btn-sm btn-primary" title="تعديل">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr class="table-light">
                            <td colspan="5" class="text-end"><strong>الإجمالي:</strong></td>
                            <td colspan="3"><strong class="text-success"><?php echo number_format($totalRevenue, 2); ?> ₪</strong></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        <?php else: ?>
            <div class="text-center py-5">
                <i class="fas fa-file-contract fa-4x text-muted mb-3" style="opacity: 0.3;"></i>
                <h5 class="text-muted">لا توجد سجلات تأجير لهذه السيارة</h5>
                <p class="text-muted">هذه السيارة لم يتم تأجيرها بعد</p>
                <a href="rental_add.php?car_id=<?php echo $car_id; ?>" class="btn btn-primary mt-3">
                    <i class="fas fa-plus me-2"></i>إضافة تأجير جديد
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>