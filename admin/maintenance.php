<?php
require_once '../config/settings.php';
require_once '../core/Auth.php';

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    redirect(ADMIN_URL . '/login.php');
}

$db = Database::getInstance()->getConnection();

// Get all maintenance records
$stmt = $db->query("
    SELECT m.*, c.brand, c.model, c.plate_number
    FROM maintenance m
    JOIN cars c ON m.car_id = c.id
    ORDER BY m.maintenance_date DESC
");
$maintenances = $stmt->fetchAll();

// Maintenance type names in Arabic
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

// Status names in Arabic
$statusNames = [
    'pending' => 'معلقة',
    'in_progress' => 'قيد التنفيذ',
    'completed' => 'مكتملة',
    'cancelled' => 'ملغاة'
];

$page_title = 'الصيانة - ' . SITE_NAME;
include 'includes/header.php';
include 'includes/sidebar.php';
?>

<div class="main-content">
    <div class="top-bar">
        <div class="welcome-text">
            <h5><i class="fas fa-tools me-2"></i>إدارة الصيانة</h5>
            <p>متابعة صيانة السيارات والإصلاحات</p>
        </div>
        <div class="top-bar-right">
            <a href="maintenance_add.php" class="btn btn-primary">
                <i class="fas fa-plus me-2"></i>إضافة صيانة
            </a>
        </div>
    </div>

    <!-- Statistics -->
    <div class="row g-3 mb-4">
        <?php
        $stats = ['total' => 0, 'pending' => 0, 'in_progress' => 0, 'completed' => 0];
        $totalCost = 0;
        foreach ($maintenances as $m) {
            $stats['total']++;
            if (isset($stats[$m['status']])) {
                $stats[$m['status']]++;
            }
            $totalCost += $m['cost'];
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
                <div class="stat-icon" style="background: rgba(33, 150, 243, 0.1); color: #2196F3;">
                    <i class="fas fa-wrench"></i>
                </div>
                <div class="stat-value"><?php echo $stats['in_progress']; ?></div>
                <div class="stat-label">قيد التنفيذ</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon" style="background: rgba(76, 175, 80, 0.1); color: #4CAF50;">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-value"><?php echo $stats['completed']; ?></div>
                <div class="stat-label">مكتملة</div>
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
    </div>

    <!-- Maintenance Table -->
    <div class="stat-card">
        <h5 class="mb-3">
            <i class="fas fa-list text-primary"></i>
            سجل الصيانة
        </h5>
        <div class="table-responsive">
            <table class="table table-hover data-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>السيارة</th>
                        <th>النوع</th>
                        <th>الوصف</th>
                        <th>التاريخ</th>
                        <th>التكلفة</th>
                        <th>الحالة</th>
                        <th>الإجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($maintenances) > 0): ?>
                        <?php foreach ($maintenances as $index => $m): ?>
                        <tr>
                            <td><?php echo $index + 1; ?></td>
                            <td>
                                <strong><?php echo $m['brand'] . ' ' . $m['model']; ?></strong><br>
                                <small class="text-muted"><?php echo $m['plate_number']; ?></small>
                            </td>
                            <td>
                                <span class="badge bg-info">
                                    <?php echo isset($maintenanceTypeNames[$m['maintenance_type']]) ? $maintenanceTypeNames[$m['maintenance_type']] : $m['maintenance_type']; ?>
                                </span>
                            </td>
                            <td>
                                <?php 
                                $desc = $m['description'];
                                echo mb_strlen($desc) > 40 ? mb_substr($desc, 0, 40) . '...' : $desc;
                                ?>
                            </td>
                            <td><?php echo date('d/m/Y', strtotime($m['maintenance_date'])); ?></td>
                            <td><strong class="text-danger"><?php echo number_format($m['cost'], 2); ?> ₪</strong></td>
                            <td>
                                <?php
                                $statusColors = [
                                    'pending' => 'warning',
                                    'in_progress' => 'info',
                                    'completed' => 'success',
                                    'cancelled' => 'secondary'
                                ];
                                $color = isset($statusColors[$m['status']]) ? $statusColors[$m['status']] : 'secondary';
                                ?>
                                <span class="badge bg-<?php echo $color; ?>">
                                    <?php echo isset($statusNames[$m['status']]) ? $statusNames[$m['status']] : $m['status']; ?>
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
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="text-center text-muted py-5">
                                <i class="fas fa-tools fa-3x mb-3 d-block" style="opacity: 0.3;"></i>
                                <p style="font-size: 1.1rem;">لا توجد سجلات صيانة</p>
                                <a href="maintenance_add.php" class="btn btn-primary mt-2">
                                    <i class="fas fa-plus me-2"></i>إضافة صيانة جديدة
                                </a>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>