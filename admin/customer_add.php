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
    try {
        $sql = "INSERT INTO customers (
            full_name, id_number, phone, email, date_of_birth, city, address, 
            status, loyalty_level, created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, 'active', 'bronze', NOW())";

        $stmt = $db->prepare($sql);
        $result = $stmt->execute([
            sanitizeInput($_POST['full_name']),
            sanitizeInput($_POST['id_number']),
            sanitizeInput($_POST['phone']),
            sanitizeInput($_POST['email']),
            $_POST['date_of_birth'],
            $_POST['city'],
            sanitizeInput($_POST['address'])
        ]);

        if ($result) {
            $_SESSION['success'] = 'تم إضافة العميل بنجاح';
            redirect('customers.php');
        }
    } catch (Exception $e) {
        $error = 'خطأ في إضافة العميل: ' . $e->getMessage();
    }
}

$page_title = 'إضافة عميل جديد - ' . SITE_NAME;
include 'includes/header.php';
include 'includes/sidebar.php';
?>

<div class="main-content">
    <div class="top-bar">
        <div class="welcome-text">
            <h5><i class="fas fa-user-plus me-2"></i>إضافة عميل جديد</h5>
        </div>
        <div class="top-bar-right">
            <a href="customers.php" class="btn btn-secondary">
                <i class="fas fa-arrow-right me-2"></i>العودة
            </a>
        </div>
    </div>

    <?php if ($error): ?>
    <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>

    <div class="table-container">
        <form method="POST">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">الاسم الكامل *</label>
                    <input type="text" name="full_name" class="form-control" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">رقم الهوية *</label>
                    <input type="text" name="id_number" class="form-control" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">رقم الهاتف *</label>
                    <input type="tel" name="phone" class="form-control" placeholder="+970599123456" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">البريد الإلكتروني</label>
                    <input type="email" name="email" class="form-control">
                </div>
                <div class="col-md-6">
                    <label class="form-label">تاريخ الميلاد *</label>
                    <input type="date" name="date_of_birth" class="form-control" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">المدينة *</label>
                    <select name="city" class="form-control" required>
                        <option value="">-- اختر المدينة --</option>
                        <?php foreach (PALESTINE_CITIES as $city): ?>
                        <option value="<?php echo $city; ?>"><?php echo $city; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-12">
                    <label class="form-label">العنوان</label>
                    <textarea name="address" class="form-control" rows="2"></textarea>
                </div>
            </div>
            <div class="mt-4">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-2"></i>حفظ العميل
                </button>
                <a href="customers.php" class="btn btn-secondary">
                    <i class="fas fa-times me-2"></i>إلغاء
                </a>
            </div>
        </form>
    </div>
</div>

<?php include 'includes/footer.php'; ?>