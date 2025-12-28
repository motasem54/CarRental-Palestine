<?php
require_once '../config/settings.php';
require_once '../core/Auth.php';

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    redirect(ADMIN_URL . '/login.php');
}

$db = Database::getInstance()->getConnection();
$success = '';
$error = '';

// Get all cars
$cars_stmt = $db->query("SELECT id, brand, model, plate_number FROM cars ORDER BY brand");
$cars = $cars_stmt->fetchAll();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $car_id = (int)$_POST['car_id'];
    $type = $_POST['type'];
    $description = $_POST['description'];
    $maintenance_date = $_POST['maintenance_date'];
    $cost = (float)$_POST['cost'];
    $status = $_POST['status'] ?? 'pending';
    $notes = $_POST['notes'] ?? '';
    
    try {
        $stmt = $db->prepare("
            INSERT INTO maintenance (car_id, type, description, maintenance_date, cost, status, notes, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([$car_id, $type, $description, $maintenance_date, $cost, $status, $notes]);
        
        // If maintenance status is in_progress, update car status
        if ($status === 'in_progress') {
            $db->prepare("UPDATE cars SET status = 'maintenance' WHERE id = ?")
               ->execute([$car_id]);
        }
        
        $success = 'تم إضافة سجل الصيانة بنجاح!';
        
    } catch (Exception $e) {
        $error = 'حدث خطأ: ' . $e->getMessage();
    }
}

$page_title = 'إضافة صيانة - ' . SITE_NAME;
include 'includes/header.php';
include 'includes/sidebar.php';
?>

<div class="main-content">
    <div class="top-bar">
        <div class="welcome-text">
            <h5><i class="fas fa-plus-circle me-2"></i>إضافة صيانة جديدة</h5>
            <p>إضافة سجل صيانة أو إصلاح للسيارة</p>
        </div>
        <div class="top-bar-right">
            <a href="maintenance.php" class="btn btn-secondary">
                <i class="fas fa-arrow-right me-2"></i>رجوع
            </a>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <i class="fas fa-check-circle me-2"></i><?php echo $success; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="fas fa-times-circle me-2"></i><?php echo $error; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="row g-3">
                    <!-- Car Selection -->
                    <div class="col-md-6">
                        <label class="form-label">السيارة <span class="text-danger">*</span></label>
                        <select name="car_id" class="form-control" required>
                            <option value="">اختر السيارة</option>
                            <?php foreach ($cars as $car): ?>
                            <option value="<?php echo $car['id']; ?>">
                                <?php echo $car['brand'] . ' ' . $car['model'] . ' - ' . $car['plate_number']; ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <!-- Maintenance Type -->
                    <div class="col-md-6">
                        <label class="form-label">نوع الصيانة <span class="text-danger">*</span></label>
                        <select name="type" class="form-control" required>
                            <option value="">اختر النوع</option>
                            <?php foreach (MAINTENANCE_TYPES as $key => $value): ?>
                            <option value="<?php echo $key; ?>"><?php echo $value; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <!-- Maintenance Date -->
                    <div class="col-md-6">
                        <label class="form-label">تاريخ الصيانة <span class="text-danger">*</span></label>
                        <input type="date" name="maintenance_date" class="form-control" 
                               value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    
                    <!-- Cost -->
                    <div class="col-md-6">
                        <label class="form-label">التكلفة <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="number" name="cost" class="form-control" 
                                   step="0.01" min="0" placeholder="0.00" required>
                            <span class="input-group-text"><?php echo CURRENCY; ?></span>
                        </div>
                    </div>
                    
                    <!-- Status -->
                    <div class="col-md-6">
                        <label class="form-label">الحالة <span class="text-danger">*</span></label>
                        <select name="status" class="form-control" required>
                            <?php foreach (MAINTENANCE_STATUS as $key => $value): ?>
                            <option value="<?php echo $key; ?>"><?php echo $value; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <!-- Description -->
                    <div class="col-12">
                        <label class="form-label">وصف الصيانة <span class="text-danger">*</span></label>
                        <textarea name="description" class="form-control" rows="3" 
                                  placeholder="وصف تفصيلي للمشكلة أو الصيانة المطلوبة" required></textarea>
                    </div>
                    
                    <!-- Notes -->
                    <div class="col-12">
                        <label class="form-label">ملاحظات إضافية</label>
                        <textarea name="notes" class="form-control" rows="2" 
                                  placeholder="ملاحظات أو تفاصيل إضافية (اختياري)"></textarea>
                    </div>
                </div>
                
                <hr class="my-4">
                
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>حفظ سجل الصيانة
                    </button>
                    <a href="maintenance.php" class="btn btn-secondary">
                        <i class="fas fa-times me-2"></i>إلغاء
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>