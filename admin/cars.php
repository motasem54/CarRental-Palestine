<?php
require_once '../config/settings.php';
require_once '../core/Auth.php';

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    redirect(ADMIN_URL . '/login.php');
}

$db = Database::getInstance()->getConnection();

// Handle delete
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $db->prepare("DELETE FROM cars WHERE id = ?");
    if ($stmt->execute([$id])) {
        $_SESSION['success'] = 'تم حذف السيارة بنجاح';
    }
    redirect('cars.php');
}

// Get all cars
$stmt = $db->query("
    SELECT * FROM cars 
    ORDER BY created_at DESC
");
$cars = $stmt->fetchAll();

$page_title = 'إدارة السيارات - ' . SITE_NAME;
include 'includes/header.php';
include 'includes/sidebar.php';
?>

<div class="main-content">
    <!-- Top Bar -->
    <div class="top-bar">
        <div class="welcome-text">
            <h5><i class="fas fa-car me-2"></i>إدارة السيارات</h5>
            <p>إدارة أسطول السيارات وتفاصيلها</p>
        </div>
        <div class="top-bar-right">
            <a href="car_add.php" class="btn btn-primary">
                <i class="fas fa-plus me-2"></i>إضافة سيارة جديدة
            </a>
        </div>
    </div>

    <?php if (isset($_SESSION['success'])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle me-2"></i>
        <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <!-- Statistics Cards -->
    <div class="row g-3 mb-4">
        <?php
        $statusCounts = [
            'available' => 0,
            'rented' => 0,
            'maintenance' => 0,
            'reserved' => 0
        ];
        foreach ($cars as $car) {
            $statusCounts[$car['status']]++;
        }
        ?>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon" style="background: rgba(76, 175, 80, 0.1); color: #4CAF50;">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-value"><?php echo $statusCounts['available']; ?></div>
                <div class="stat-label">متاحة</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon" style="background: rgba(255, 152, 0, 0.1); color: #FF9800;">
                    <i class="fas fa-key"></i>
                </div>
                <div class="stat-value"><?php echo $statusCounts['rented']; ?></div>
                <div class="stat-label">مؤجرة</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon" style="background: rgba(244, 67, 54, 0.1); color: #F44336;">
                    <i class="fas fa-tools"></i>
                </div>
                <div class="stat-value"><?php echo $statusCounts['maintenance']; ?></div>
                <div class="stat-label">صيانة</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon" style="background: rgba(33, 150, 243, 0.1); color: #2196F3;">
                    <i class="fas fa-bookmark"></i>
                </div>
                <div class="stat-value"><?php echo $statusCounts['reserved']; ?></div>
                <div class="stat-label">محجوزة</div>
            </div>
        </div>
    </div>

    <!-- Cars Table -->
    <div class="table-container">
        <h5 class="mb-3">
            <i class="fas fa-list text-primary"></i>
            قائمة السيارات
        </h5>
        <div class="table-responsive">
            <table class="table table-hover data-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>الصورة</th>
                        <th>رقم اللوحة</th>
                        <th>الماركة</th>
                        <th>الموديل</th>
                        <th>السنة</th>
                        <th>النوع</th>
                        <th>الأجرة اليومية</th>
                        <th>الحالة</th>
                        <th>الإجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($cars as $index => $car): ?>
                    <tr>
                        <td><?php echo $index + 1; ?></td>
                        <td>
                            <?php if ($car['image']): ?>
                                <img src="<?php echo UPLOADS_URL . '/cars/' . $car['image']; ?>" 
                                     alt="<?php echo $car['brand']; ?>" 
                                     style="width: 60px; height: 40px; object-fit: cover; border-radius: 8px;">
                            <?php else: ?>
                                <div style="width: 60px; height: 40px; background: #f0f0f0; border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                                    <i class="fas fa-car text-muted"></i>
                                </div>
                            <?php endif; ?>
                        </td>
                        <td><strong><?php echo $car['plate_number']; ?></strong></td>
                        <td><?php echo $car['brand']; ?></td>
                        <td><?php echo $car['model']; ?></td>
                        <td><?php echo $car['year']; ?></td>
                        <td><span class="badge bg-secondary"><?php echo CAR_TYPES[$car['type']]; ?></span></td>
                        <td><strong><?php echo formatCurrency($car['daily_rate']); ?></strong></td>
                        <td>
                            <?php
                            $statusColors = [
                                'available' => 'success',
                                'rented' => 'warning',
                                'maintenance' => 'danger',
                                'reserved' => 'info'
                            ];
                            $color = $statusColors[$car['status']] ?? 'secondary';
                            ?>
                            <span class="badge bg-<?php echo $color; ?>">
                                <?php echo CAR_STATUS[$car['status']]; ?>
                            </span>
                        </td>
                        <td>
                            <div class="btn-group" role="group">
                                <a href="car_view.php?id=<?php echo $car['id']; ?>" class="btn btn-sm btn-info" title="عرض">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="car_edit.php?id=<?php echo $car['id']; ?>" class="btn btn-sm btn-primary" title="تعديل">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="cars.php?delete=<?php echo $car['id']; ?>" 
                                   class="btn btn-sm btn-danger" 
                                   title="حذف"
                                   onclick="return confirm('هل أنت متأكد من حذف هذه السيارة؟')">
                                    <i class="fas fa-trash"></i>
                                </a>
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