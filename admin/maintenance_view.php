<?php
require_once '../config/settings.php';
require_once '../core/Auth.php';

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    redirect(ADMIN_URL . '/login.php');
}

$db = Database::getInstance()->getConnection();
$maintenance_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($maintenance_id <= 0) {
    redirect('maintenance.php');
}

// Get maintenance details
$stmt = $db->prepare("
    SELECT m.*, c.brand, c.model, c.plate_number, c.year
    FROM maintenance m
    JOIN cars c ON m.car_id = c.id
    WHERE m.id = ?
");
$stmt->execute([$maintenance_id]);
$maintenance = $stmt->fetch();

if (!$maintenance) {
    redirect('maintenance.php');
}

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

$page_title = 'عرض صيانة - ' . SITE_NAME;
include 'includes/header.php';
include 'includes/sidebar.php';
?>

<div class="main-content">
    <div class="top-bar">
        <div class="welcome-text">
            <h5><i class="fas fa-eye me-2"></i>تفاصيل سجل الصيانة #<?php echo $maintenance_id; ?></h5>
            <p>عرض معلومات الصيانة بالتفصيل</p>
        </div>
        <div class="top-bar-right">
            <a href="maintenance_edit.php?id=<?php echo $maintenance_id; ?>" class="btn btn-primary">
                <i class="fas fa-edit me-2"></i>تعديل
            </a>
            <a href="maintenance.php" class="btn btn-secondary">
                <i class="fas fa-arrow-right me-2"></i>رجوع
            </a>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-md-8">
            <div class="stat-card">
                <h5 class="mb-3">معلومات الصيانة</h5>
                
                <table class="table table-bordered">
                    <tr>
                        <th width="30%" class="bg-light">السيارة</th>
                        <td><strong><?php echo $maintenance['brand'] . ' ' . $maintenance['model'] . ' ' . $maintenance['year']; ?></strong><br>
                            <small class="text-muted"><?php echo $maintenance['plate_number']; ?></small>
                        </td>
                    </tr>
                    <tr>
                        <th class="bg-light">نوع الصيانة</th>
                        <td>
                            <span class="badge bg-info" style="font-size: 1rem;">
                                <?php echo $maintenanceTypeNames[$maintenance['maintenance_type']] ?? $maintenance['maintenance_type']; ?>
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <th class="bg-light">تاريخ الصيانة</th>
                        <td><?php echo date('d/m/Y', strtotime($maintenance['maintenance_date'])); ?></td>
                    </tr>
                    <tr>
                        <th class="bg-light">التكلفة</th>
                        <td><strong class="text-danger" style="font-size: 1.2rem;"><?php echo number_format($maintenance['cost'], 2); ?> ₪</strong></td>
                    </tr>
                    <tr>
                        <th class="bg-light">الحالة</th>
                        <td>
                            <?php
                            $statusColors = [
                                'pending' => 'warning',
                                'in_progress' => 'info',
                                'completed' => 'success'
                            ];
                            $color = $statusColors[$maintenance['status']] ?? 'secondary';
                            ?>
                            <span class="badge bg-<?php echo $color; ?>" style="font-size: 1rem;">
                                <?php echo $statusNames[$maintenance['status']] ?? $maintenance['status']; ?>
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <th class="bg-light">الوصف</th>
                        <td><?php echo nl2br(htmlspecialchars($maintenance['description'])); ?></td>
                    </tr>
                    <?php if (!empty($maintenance['notes'])): ?>
                    <tr>
                        <th class="bg-light">ملاحظات</th>
                        <td><?php echo nl2br(htmlspecialchars($maintenance['notes'])); ?></td>
                    </tr>
                    <?php endif; ?>
                    <tr>
                        <th class="bg-light">تاريخ الإنشاء</th>
                        <td><?php echo date('d/m/Y H:i', strtotime($maintenance['created_at'])); ?></td>
                    </tr>
                </table>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="stat-card" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
                <h5 style="color: white;">ملخص سريع</h5>
                <hr style="border-color: rgba(255,255,255,0.2);">
                <div class="mb-3">
                    <i class="fas fa-car fa-2x mb-2"></i>
                    <h6 style="color: white;"><?php echo $maintenance['brand'] . ' ' . $maintenance['model']; ?></h6>
                    <p style="opacity: 0.9;"><?php echo $maintenance['plate_number']; ?></p>
                </div>
                <div class="mb-3">
                    <i class="fas fa-tools fa-2x mb-2"></i>
                    <h6 style="color: white;"><?php echo $maintenanceTypeNames[$maintenance['maintenance_type']] ?? $maintenance['maintenance_type']; ?></h6>
                </div>
                <div>
                    <i class="fas fa-money-bill fa-2x mb-2"></i>
                    <h4 style="color: white;"><?php echo number_format($maintenance['cost'], 2); ?> ₪</h4>
                </div>
            </div>
            
            <div class="stat-card mt-3">
                <h6>إجراءات</h6>
                <div class="d-grid gap-2">
                    <a href="maintenance_edit.php?id=<?php echo $maintenance_id; ?>" class="btn btn-primary">
                        <i class="fas fa-edit me-2"></i>تعديل الصيانة
                    </a>
                    <button onclick="window.print()" class="btn btn-secondary">
                        <i class="fas fa-print me-2"></i>طباعة
                    </button>
                    <a href="maintenance.php" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-right me-2"></i>رجوع للقائمة
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>