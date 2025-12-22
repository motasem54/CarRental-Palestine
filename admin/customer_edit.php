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

// Get customer ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['error'] = 'معرف العميل غير موجود';
    redirect('customers.php');
}

$customer_id = (int)$_GET['id'];

// Fetch customer data
try {
    $stmt = $db->prepare("SELECT * FROM customers WHERE id = ?");
    $stmt->execute([$customer_id]);
    $customer = $stmt->fetch();
    
    if (!$customer) {
        $_SESSION['error'] = 'العميل غير موجود';
        redirect('customers.php');
    }
} catch (Exception $e) {
    $_SESSION['error'] = 'خطأ في جلب بيانات العميل';
    redirect('customers.php');
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'full_name' => sanitizeInput($_POST['full_name']),
        'id_number' => sanitizeInput($_POST['id_number']),
        'phone' => sanitizeInput($_POST['phone']),
        'email' => !empty($_POST['email']) ? sanitizeInput($_POST['email']) : null,
        'address' => sanitizeInput($_POST['address']),
        'city' => sanitizeInput($_POST['city']),
        'driver_license' => sanitizeInput($_POST['driver_license']),
        'license_expiry' => !empty($_POST['license_expiry']) ? $_POST['license_expiry'] : null,
        'date_of_birth' => !empty($_POST['date_of_birth']) ? $_POST['date_of_birth'] : null,
        'status' => $_POST['status'],
        'notes' => sanitizeInput($_POST['notes'])
    ];

    try {
        $sql = "UPDATE customers SET 
            full_name = ?, id_number = ?, phone = ?, email = ?, address = ?, 
            city = ?, driver_license = ?, license_expiry = ?, date_of_birth = ?, 
            status = ?, notes = ?, updated_at = NOW()
            WHERE id = ?";

        $stmt = $db->prepare($sql);
        $result = $stmt->execute([
            $data['full_name'], $data['id_number'], $data['phone'], $data['email'],
            $data['address'], $data['city'], $data['driver_license'], 
            $data['license_expiry'], $data['date_of_birth'], $data['status'],
            $data['notes'], $customer_id
        ]);

        if ($result) {
            logActivity($_SESSION['user_id'], 'customer_update', 'تم تعديل بيانات العميل: ' . $data['full_name']);
            $_SESSION['success'] = 'تم تحديث بيانات العميل بنجاح';
            redirect('customers.php');
        }
    } catch (Exception $e) {
        $error = 'خطأ في تحديث بيانات العميل: ' . $e->getMessage();
    }
}

$page_title = 'تعديل بيانات العميل - ' . SITE_NAME;
include 'includes/header.php';
include 'includes/sidebar.php';
?>

<div class="main-content">
    <div class="top-bar">
        <div class="welcome-text">
            <h5><i class="fas fa-user-edit me-2"></i>تعديل بيانات العميل: <?php echo htmlspecialchars($customer['full_name']); ?></h5>
        </div>
        <div class="top-bar-right">
            <a href="customers.php" class="btn btn-secondary">
                <i class="fas fa-arrow-right me-2"></i>العودة للقائمة
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
                    <input type="text" name="full_name" class="form-control" 
                           value="<?php echo htmlspecialchars($customer['full_name']); ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">رقم الهوية *</label>
                    <input type="text" name="id_number" class="form-control" 
                           value="<?php echo htmlspecialchars($customer['id_number']); ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">رقم الهاتف *</label>
                    <input type="text" name="phone" class="form-control" 
                           value="<?php echo htmlspecialchars($customer['phone']); ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">البريد الإلكتروني</label>
                    <input type="email" name="email" class="form-control" 
                           value="<?php echo htmlspecialchars($customer['email']); ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">المدينة *</label>
                    <select name="city" class="form-control" required>
                        <?php foreach ($PALESTINIAN_CITIES as $city): ?>
                        <option value="<?php echo $city; ?>" <?php echo $customer['city'] == $city ? 'selected' : ''; ?>>
                            <?php echo $city; ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">رقم رخصة القيادة</label>
                    <input type="text" name="driver_license" class="form-control" 
                           value="<?php echo htmlspecialchars($customer['driver_license']); ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">تاريخ انتهاء الرخصة</label>
                    <input type="date" name="license_expiry" class="form-control" 
                           value="<?php echo $customer['license_expiry']; ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">تاريخ الميلاد</label>
                    <input type="date" name="date_of_birth" class="form-control" 
                           value="<?php echo $customer['date_of_birth']; ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">الحالة *</label>
                    <select name="status" class="form-control" required>
                        <?php foreach (CUSTOMER_STATUS as $key => $value): ?>
                        <option value="<?php echo $key; ?>" <?php echo $customer['status'] == $key ? 'selected' : ''; ?>>
                            <?php echo $value; ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-12">
                    <label class="form-label">العنوان</label>
                    <textarea name="address" class="form-control" rows="2"><?php echo htmlspecialchars($customer['address']); ?></textarea>
                </div>
                <div class="col-md-12">
                    <label class="form-label">ملاحظات</label>
                    <textarea name="notes" class="form-control" rows="2"><?php echo htmlspecialchars($customer['notes']); ?></textarea>
                </div>
            </div>
            <div class="mt-4">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-2"></i>حفظ التعديلات
                </button>
                <a href="customers.php" class="btn btn-secondary">
                    <i class="fas fa-times me-2"></i>إلغاء
                </a>
            </div>
        </form>
    </div>
</div>

<?php include 'includes/footer.php'; ?>