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

// Get all maintenance records for this car
$stmt = $db->prepare("
    SELECT * FROM maintenance
    WHERE car_id = ?
    ORDER BY maintenance_date DESC, created_at DESC
");
$stmt->execute([$car_id]);
$maintenances = $stmt->fetchAll();

$maintenanceTypeNames = [
    'oil_change' => 'تغيير زيت',
    'regular_maintenance' => 'صيانة دورية',
    'tire_change' => 'تغيير إطارات',
    'inspection' => 'فحص دوري',
    'brake_repair' => 'إصلاح فرامل',
    'engine_repair' => 'إصلاح محرك',
    'transmission' => 'ناقل الحركة',
    'electrical' => 'كهرباء',
    'ac_repair' => 'إصلاح مكيف',
    'body_work' => 'أعمال صفيح',
    'repair' => 'إصلاح عام',
    'other' => 'أخرى'
];

$statusNames = [
    'pending' => 'معلقة',
    'in_progress' => 'قيد التنفيذ',
    'completed' => 'مكتملة'
];

$totalCost = array_sum(array_column($maintenances, 'cost'));

$page_title = 'سجل صيانة السيارة - ' . SITE_NAME;
include 'includes/header.php';
include 'includes/sidebar.php';
?>

<div class="main-content">
    <div class="top-bar">
        <div class="welcome-text">
            <h5><i class="fas fa-tools me-2"></i>سجل صيانة السيارة</h5>
            <p><?php echo $car['brand'] . ' ' . $car['model'] . ' ' . $car['year']; ?> - <?php echo $car['plate_number']; ?></p>
        </div>
        <div class="top-bar-right">
            <a href="maintenance_add.php?car_id=<?php echo $car_id; ?>" class="btn btn-primary">
                <i class="fas fa-plus me-2"></i>إضافة صيانة
            </a>
            <a href="cars.php" class="btn btn-secondary">
                <i class="fas fa-arrow-right me-2"></i>رجوع
            </a>
        </div>
    </div>

    <!-- Car Info Card -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="stat-card" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
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
                    <i class="fas fa-tools"></i>
                </div>
                <div class="stat-value"><?php echo count($maintenances); ?></div>
                <div class="stat-label">إجمالي عمليات الصيانة</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon" style="background: rgba(244, 67, 54, 0.1); color: #F44336;">
                    <i class="fas fa-shekel-sign"></i>
                </div>
                <div class="stat-value"><?php echo number_format($totalCost, 2); ?> ₪</div>
                <div class="stat-label">إجمالي التكلفة</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon" style="background: rgba(76, 175, 80, 0.1); color: #4CAF50;">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-value"><?php echo count(array_filter($maintenances, fn($m) => $m['status'] == 'completed')); ?></div>
                <div class="stat-label">مكتملة</div>
            </div>
        </div>
    </div>

    <!-- Maintenance Timeline -->
    <div class="stat-card">
        <h5 class="mb-4">
            <i class="fas fa-history text-primary"></i>
            سجل الصيانة
        </h5>
        
        <?php if (count($maintenances) > 0): ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>التاريخ</th>
                            <th>النوع</th>
                            <th>الوصف</th>
                            <th>التكلفة</th>
                            <th>الحالة</th>
                            <th>الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($maintenances as $index => $m): ?>
                        <tr>
                            <td><?php echo $index + 1; ?></td>
                            <td>
                                <strong><?php echo date('d/m/Y', strtotime($m['maintenance_date'])); ?></strong><br>
                                <small class="text-muted"><?php echo date('H:i', strtotime($m['created_at'])); ?></small>
                            </td>
                            <td>
                                <span class="badge bg-info">
                                    <?php echo $maintenanceTypeNames[$m['maintenance_type']] ?? $m['maintenance_type']; ?>
                                </span>
                            </td>
                            <td>
                                <?php 
                                $desc = $m['description'];
                                echo mb_strlen($desc) > 50 ? mb_substr($desc, 0, 50) . '...' : $desc;
                                ?>
                            </td>
                            <td><strong class="text-danger"><?php echo number_format($m['cost'], 2); ?> ₪</strong></td>
                            <td>
                                <?php
                                $statusColors = [
                                    'pending' => 'warning',
                                    'in_progress' => 'info',
                                    'completed' => 'success'
                                ];
                                $color = $statusColors[$m['status']] ?? 'secondary';
                                ?>
                                <span class="badge bg-<?php echo $color; ?>">
                                    <?php echo $statusNames[$m['status']] ?? $m['status']; ?>
                                </span>
                            </td>
                            <td>
                                <div class="btn-group" role="group">
                                    <a href="maintenance_view.php?id=<?php echo $m['id']; ?>" class="btn btn-sm btn-info" title="عرض">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="maintenance_edit.php?id=<?php echo $m['id']; ?>" class="btn btn-sm btn-primary" title="تعديل">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr class="table-light">
                            <td colspan="4" class="text-end"><strong>الإجمالي:</strong></td>
                            <td colspan="3"><strong class="text-danger"><?php echo number_format($totalCost, 2); ?> ₪</strong></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        <?php else: ?>
            <div class="text-center py-5">
                <i class="fas fa-tools fa-4x text-muted mb-3" style="opacity: 0.3;"></i>
                <h5 class="text-muted">لا توجد سجلات صيانة لهذه السيارة</h5>
                <p class="text-muted">هذه السيارة لم تخضع لأي صيانة بعد</p>
                <a href="maintenance_add.php?car_id=<?php echo $car_id; ?>" class="btn btn-primary mt-3">
                    <i class="fas fa-plus me-2"></i>إضافة صيانة جديدة
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>