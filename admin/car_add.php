<?php
require_once '../config/settings.php';
require_once '../core/Auth.php';

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    redirect(ADMIN_URL . '/login.php');
}

$db = Database::getInstance()->getConnection();
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'plate_number' => sanitizeInput($_POST['plate_number']),
        'brand' => sanitizeInput($_POST['brand']),
        'model' => sanitizeInput($_POST['model']),
        'year' => (int)$_POST['year'],
        'color' => sanitizeInput($_POST['color']),
        'type' => $_POST['type'],
        'transmission' => $_POST['transmission'],
        'fuel_type' => $_POST['fuel_type'],
        'seats' => (int)$_POST['seats'],
        'daily_rate' => (float)$_POST['daily_rate'],
        'weekly_rate' => !empty($_POST['weekly_rate']) ? (float)$_POST['weekly_rate'] : null,
        'monthly_rate' => !empty($_POST['monthly_rate']) ? (float)$_POST['monthly_rate'] : null,
        'mileage' => (int)$_POST['mileage'],
        'status' => $_POST['status'],
        'condition' => $_POST['condition'],
        'features' => sanitizeInput($_POST['features']),
        'notes' => sanitizeInput($_POST['notes'])
    ];

    // Handle image upload
    $imageName = null;
    if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
        $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png'];
        if (in_array($_FILES['image']['type'], $allowedTypes)) {
            $imageName = time() . '_' . $_FILES['image']['name'];
            move_uploaded_file($_FILES['image']['tmp_name'], CARS_UPLOAD_DIR . '/' . $imageName);
        }
    }

    try {
        $sql = "INSERT INTO cars (
            plate_number, brand, model, year, color, type, transmission, fuel_type,
            seats, daily_rate, weekly_rate, monthly_rate, mileage, status, `condition`,
            features, image, notes, created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";

        $stmt = $db->prepare($sql);
        $result = $stmt->execute([
            $data['plate_number'], $data['brand'], $data['model'], $data['year'],
            $data['color'], $data['type'], $data['transmission'], $data['fuel_type'],
            $data['seats'], $data['daily_rate'], $data['weekly_rate'], $data['monthly_rate'],
            $data['mileage'], $data['status'], $data['condition'], $data['features'],
            $imageName, $data['notes']
        ]);

        if ($result) {
            $_SESSION['success'] = 'تم إضافة السيارة بنجاح';
            redirect('cars.php');
        }
    } catch (Exception $e) {
        $error = 'خطأ في إضافة السيارة: ' . $e->getMessage();
    }
}

$page_title = 'إضافة سيارة جديدة - ' . SITE_NAME;
include 'includes/header.php';
include 'includes/sidebar.php';
?>

<div class="main-content">
    <div class="top-bar">
        <div class="welcome-text">
            <h5><i class="fas fa-plus-circle me-2"></i>إضافة سيارة جديدة</h5>
        </div>
        <div class="top-bar-right">
            <a href="cars.php" class="btn btn-secondary">
                <i class="fas fa-arrow-right me-2"></i>العودة للقائمة
            </a>
        </div>
    </div>

    <?php if ($error): ?>
    <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>

    <div class="table-container">
        <form method="POST" enctype="multipart/form-data">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">رقم اللوحة *</label>
                    <input type="text" name="plate_number" class="form-control" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">الماركة *</label>
                    <input type="text" name="brand" class="form-control" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">الموديل *</label>
                    <input type="text" name="model" class="form-control" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">السنة *</label>
                    <input type="number" name="year" class="form-control" min="2000" max="2025" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">اللون *</label>
                    <input type="text" name="color" class="form-control" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">النوع *</label>
                    <select name="type" class="form-control" required>
                        <?php foreach (CAR_TYPES as $key => $value): ?>
                        <option value="<?php echo $key; ?>"><?php echo $value; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">ناقل الحركة *</label>
                    <select name="transmission" class="form-control" required>
                        <?php foreach (TRANSMISSION_TYPES as $key => $value): ?>
                        <option value="<?php echo $key; ?>"><?php echo $value; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">نوع الوقود *</label>
                    <select name="fuel_type" class="form-control" required>
                        <?php foreach (FUEL_TYPES as $key => $value): ?>
                        <option value="<?php echo $key; ?>"><?php echo $value; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">عدد المقاعد *</label>
                    <input type="number" name="seats" class="form-control" min="2" max="12" value="5" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">الأجرة اليومية (₪) *</label>
                    <input type="number" name="daily_rate" class="form-control" step="0.01" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">الأجرة الأسبوعية (₪)</label>
                    <input type="number" name="weekly_rate" class="form-control" step="0.01">
                </div>
                <div class="col-md-3">
                    <label class="form-label">الأجرة الشهرية (₪)</label>
                    <input type="number" name="monthly_rate" class="form-control" step="0.01">
                </div>
                <div class="col-md-3">
                    <label class="form-label">قراءة العداد (كم)</label>
                    <input type="number" name="mileage" class="form-control" value="0">
                </div>
                <div class="col-md-3">
                    <label class="form-label">الحالة *</label>
                    <select name="status" class="form-control" required>
                        <?php foreach (CAR_STATUS as $key => $value): ?>
                        <option value="<?php echo $key; ?>"><?php echo $value; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">حالة السيارة *</label>
                    <select name="condition" class="form-control" required>
                        <option value="excellent">ممتازة</option>
                        <option value="good" selected>جيدة</option>
                        <option value="fair">مقبولة</option>
                        <option value="poor">سيئة</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">صورة السيارة</label>
                    <input type="file" name="image" class="form-control" accept="image/*">
                </div>
                <div class="col-md-12">
                    <label class="form-label">المميزات</label>
                    <textarea name="features" class="form-control" rows="2" placeholder="مثال: مكيف، نظام ملاحة، كاميرا خلفية"></textarea>
                </div>
                <div class="col-md-12">
                    <label class="form-label">ملاحظات</label>
                    <textarea name="notes" class="form-control" rows="2"></textarea>
                </div>
            </div>
            <div class="mt-4">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-2"></i>حفظ السيارة
                </button>
                <a href="cars.php" class="btn btn-secondary">
                    <i class="fas fa-times me-2"></i>إلغاء
                </a>
            </div>
        </form>
    </div>
</div>

<?php include 'includes/footer.php'; ?>