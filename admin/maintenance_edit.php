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
$maintenance_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($maintenance_id <= 0) {
    redirect('maintenance.php');
}

// Get maintenance details
$stmt = $db->prepare("
    SELECT m.*, c.brand, c.model, c.plate_number
    FROM maintenance m
    JOIN cars c ON m.car_id = c.id
    WHERE m.id = ?
");
$stmt->execute([$maintenance_id]);
$maintenance = $stmt->fetch();

if (!$maintenance) {
    redirect('maintenance.php');
}

// Get all cars
$cars_stmt = $db->query("SELECT id, brand, model, plate_number FROM cars WHERE status != 'sold' ORDER BY brand");
$cars = $cars_stmt->fetchAll();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $car_id = (int)$_POST['car_id'];
    $maintenance_type = $_POST['maintenance_type'];
    $description = $_POST['description'];
    $maintenance_date = $_POST['maintenance_date'];
    $cost = (float)$_POST['cost'];
    $status = $_POST['status'];
    $notes = $_POST['notes'] ?? '';
    
    try {
        $stmt = $db->prepare("
            UPDATE maintenance 
            SET car_id = ?, maintenance_type = ?, description = ?, 
                maintenance_date = ?, cost = ?, status = ?, notes = ?
            WHERE id = ?
        ");
        $stmt->execute([$car_id, $maintenance_type, $description, $maintenance_date, $cost, $status, $notes, $maintenance_id]);
        
        $success = 'تم تحديث سجل الصيانة بنجاح!';
        
        // Refresh data
        $stmt = $db->prepare("
            SELECT m.*, c.brand, c.model, c.plate_number
            FROM maintenance m
            JOIN cars c ON m.car_id = c.id
            WHERE m.id = ?
        ");
        $stmt->execute([$maintenance_id]);
        $maintenance = $stmt->fetch();
        
    } catch (Exception $e) {
        $error = 'حدث خطأ: ' . $e->getMessage();
    }
}

$page_title = 'تعديل صيانة - ' . SITE_NAME;
include 'includes/header.php';
include 'includes/sidebar.php';
?>

<div class="main-content">
    <div class="top-bar">
        <div class="welcome-text">
            <h5><i class="fas fa-edit me-2"></i>تعديل سجل الصيانة #<?php echo $maintenance_id; ?></h5>
            <p>تحديث بيانات الصيانة</p>
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
                <div class="col-md-6">
                    <label class="form-label"><i class="fas fa-car me-2"></i>السيارة <span class="text-danger">*</span></label>
                    <select name="car_id" class="form-select" required>
                        <?php foreach ($cars as $car): ?>
                        <option value="<?php echo $car['id']; ?>" <?php echo $car['id'] == $maintenance['car_id'] ? 'selected' : ''; ?>>
                            <?php echo $car['brand'] . ' ' . $car['model'] . ' - ' . $car['plate_number']; ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-md-6">
                    <label class="form-label"><i class="fas fa-tools me-2"></i>نوع الصيانة <span class="text-danger">*</span></label>
                    <select name="maintenance_type" class="form-select" required>
                        <optgroup label="صيانة دورية">
                            <option value="oil_change" <?php echo $maintenance['maintenance_type'] == 'oil_change' ? 'selected' : ''; ?>>🛢️ تغيير زيت</option>
                            <option value="regular_maintenance" <?php echo $maintenance['maintenance_type'] == 'regular_maintenance' ? 'selected' : ''; ?>>⚙️ صيانة دورية</option>
                            <option value="tire_change" <?php echo $maintenance['maintenance_type'] == 'tire_change' ? 'selected' : ''; ?>>🛞 تغيير إطارات</option>
                            <option value="inspection" <?php echo $maintenance['maintenance_type'] == 'inspection' ? 'selected' : ''; ?>>🔍 فحص دوري</option>
                        </optgroup>
                        <optgroup label="إصلاحات">
                            <option value="brake_repair" <?php echo $maintenance['maintenance_type'] == 'brake_repair' ? 'selected' : ''; ?>>🛑 إصلاح فرامل</option>
                            <option value="engine_repair" <?php echo $maintenance['maintenance_type'] == 'engine_repair' ? 'selected' : ''; ?>>🔧 إصلاح محرك</option>
                            <option value="transmission" <?php echo $maintenance['maintenance_type'] == 'transmission' ? 'selected' : ''; ?>>⚙️ ناقل الحركة</option>
                            <option value="electrical" <?php echo $maintenance['maintenance_type'] == 'electrical' ? 'selected' : ''; ?>>⚡ كهرباء</option>
                            <option value="ac_repair" <?php echo $maintenance['maintenance_type'] == 'ac_repair' ? 'selected' : ''; ?>>❄️ إصلاح مكيف</option>
                            <option value="body_work" <?php echo $maintenance['maintenance_type'] == 'body_work' ? 'selected' : ''; ?>>🔨 أعمال صفيح</option>
                        </optgroup>
                        <optgroup label="أخرى">
                            <option value="repair" <?php echo $maintenance['maintenance_type'] == 'repair' ? 'selected' : ''; ?>>🔧 إصلاح عام</option>
                            <option value="other" <?php echo $maintenance['maintenance_type'] == 'other' ? 'selected' : ''; ?>>📝 أخرى</option>
                        </optgroup>
                    </select>
                </div>
                
                <div class="col-md-4">
                    <label class="form-label"><i class="fas fa-calendar me-2"></i>تاريخ الصيانة <span class="text-danger">*</span></label>
                    <input type="date" name="maintenance_date" class="form-control" 
                           value="<?php echo $maintenance['maintenance_date']; ?>" required>
                </div>
                
                <div class="col-md-4">
                    <label class="form-label"><i class="fas fa-money-bill me-2"></i>التكلفة <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <input type="number" name="cost" class="form-control" 
                               step="0.01" min="0" value="<?php echo $maintenance['cost']; ?>" required>
                        <span class="input-group-text">₪</span>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <label class="form-label"><i class="fas fa-flag me-2"></i>الحالة <span class="text-danger">*</span></label>
                    <select name="status" class="form-select" required>
                        <option value="pending" <?php echo $maintenance['status'] == 'pending' ? 'selected' : ''; ?>>⏳ معلقة</option>
                        <option value="in_progress" <?php echo $maintenance['status'] == 'in_progress' ? 'selected' : ''; ?>>🔧 قيد التنفيذ</option>
                        <option value="completed" <?php echo $maintenance['status'] == 'completed' ? 'selected' : ''; ?>>✅ مكتملة</option>
                    </select>
                </div>
                
                <div class="col-12">
                    <label class="form-label"><i class="fas fa-align-right me-2"></i>وصف الصيانة <span class="text-danger">*</span></label>
                    <textarea name="description" class="form-control" rows="3" required><?php echo htmlspecialchars($maintenance['description']); ?></textarea>
                </div>
                
                <div class="col-12">
                    <label class="form-label"><i class="fas fa-sticky-note me-2"></i>ملاحظات إضافية</label>
                    <textarea name="notes" class="form-control" rows="2"><?php echo htmlspecialchars($maintenance['notes']); ?></textarea>
                </div>
            </div>
            
            <hr class="my-4">
            
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-2"></i>حفظ التغييرات
                </button>
                <a href="maintenance.php" class="btn btn-secondary">
                    <i class="fas fa-times me-2"></i>إلغاء
                </a>
            </div>
        </form>
    </div>
</div>

<?php include 'includes/footer.php'; ?>