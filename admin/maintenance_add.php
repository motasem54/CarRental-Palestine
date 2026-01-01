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
$cars_stmt = $db->query("SELECT id, brand, model, plate_number, status FROM cars WHERE status != 'sold' ORDER BY brand");
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
        
        // Update car maintenance tracking
        if ($status === 'completed') {
            $db->prepare("
                UPDATE cars 
                SET last_maintenance_date = ?,
                    last_maintenance_km = current_km
                WHERE id = ?
            ")->execute([$maintenance_date, $car_id]);
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

    <div class="stat-card">
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
                    <label class="form-label"><i class="fas fa-car me-2"></i>السيارة <span class="text-danger">*</span></label>
                    <select name="car_id" class="form-select" required>
                        <option value="">اختر السيارة</option>
                        <?php foreach ($cars as $car): ?>
                        <option value="<?php echo $car['id']; ?>">
                            <?php echo $car['brand'] . ' ' . $car['model'] . ' - ' . $car['plate_number']; ?>
                            <?php if ($car['status'] == 'maintenance'): ?>
                                (في الصيانة)
                            <?php endif; ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <!-- Maintenance Type -->
                <div class="col-md-6">
                    <label class="form-label"><i class="fas fa-tools me-2"></i>نوع الصيانة <span class="text-danger">*</span></label>
                    <select name="type" class="form-select" required>
                        <option value="">اختر النوع</option>
                        <option value="oil_change">تغيير زيت</option>
                        <option value="tire_change">تغيير إطارات</option>
                        <option value="brake_repair">إصلاح فرامل</option>
                        <option value="engine_repair">إصلاح محرك</option>
                        <option value="transmission">ناقل الحركة</option>
                        <option value="electrical">كهرباء</option>
                        <option value="ac_repair">إصلاح مكيف</option>
                        <option value="body_work">أعمال صفيح</option>
                        <option value="regular_maintenance">صيانة دورية</option>
                        <option value="other">أخرى</option>
                    </select>
                </div>
                
                <!-- Maintenance Date -->
                <div class="col-md-4">
                    <label class="form-label"><i class="fas fa-calendar me-2"></i>تاريخ الصيانة <span class="text-danger">*</span></label>
                    <input type="date" name="maintenance_date" class="form-control" 
                           value="<?php echo date('Y-m-d'); ?>" required>
                </div>
                
                <!-- Cost -->
                <div class="col-md-4">
                    <label class="form-label"><i class="fas fa-money-bill me-2"></i>التكلفة <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <input type="number" name="cost" class="form-control" 
                               step="0.01" min="0" placeholder="0.00" required>
                        <span class="input-group-text">₪</span>
                    </div>
                </div>
                
                <!-- Status -->
                <div class="col-md-4">
                    <label class="form-label"><i class="fas fa-flag me-2"></i>الحالة <span class="text-danger">*</span></label>
                    <select name="status" class="form-select" required>
                        <option value="pending">معلقة</option>
                        <option value="in_progress">قيد التنفيذ</option>
                        <option value="completed">مكتملة</option>
                    </select>
                </div>
                
                <!-- Description -->
                <div class="col-12">
                    <label class="form-label"><i class="fas fa-align-right me-2"></i>وصف الصيانة <span class="text-danger">*</span></label>
                    <textarea name="description" class="form-control" rows="3" 
                              placeholder="وصف تفصيلي للمشكلة أو الصيانة المطلوبة" required></textarea>
                </div>
                
                <!-- Notes -->
                <div class="col-12">
                    <label class="form-label"><i class="fas fa-sticky-note me-2"></i>ملاحظات إضافية</label>
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

    <!-- Help Section -->
    <div class="stat-card mt-4" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
        <h6 style="color: white; margin-bottom: 15px;">
            <i class="fas fa-info-circle me-2"></i>معلومات مفيدة
        </h6>
        <div class="row">
            <div class="col-md-4">
                <strong>⚙️ الصيانة الدورية:</strong>
                <p style="margin: 5px 0; opacity: 0.9;">كل 5,000 كم أو 6 أشهر</p>
            </div>
            <div class="col-md-4">
                <strong>🔧 تغيير الزيت:</strong>
                <p style="margin: 5px 0; opacity: 0.9;">كل 5,000 كم</p>
            </div>
            <div class="col-md-4">
                <strong>🛞 الإطارات:</strong>
                <p style="margin: 5px 0; opacity: 0.9;">فحص كل 10,000 كم</p>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>