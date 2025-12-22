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

// Get rental ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['error'] = 'معرف الحجز غير موجود';
    redirect('rentals.php');
}

$rental_id = (int)$_GET['id'];

// Fetch rental data
try {
    $stmt = $db->prepare("
        SELECT r.*, c.full_name as customer_name, car.brand, car.model, car.plate_number
        FROM rentals r
        JOIN customers c ON r.customer_id = c.id
        JOIN cars car ON r.car_id = car.id
        WHERE r.id = ?
    ");
    $stmt->execute([$rental_id]);
    $rental = $stmt->fetch();
    
    if (!$rental) {
        $_SESSION['error'] = 'الحجز غير موجود';
        redirect('rentals.php');
    }
} catch (Exception $e) {
    $_SESSION['error'] = 'خطأ في جلب بيانات الحجز';
    redirect('rentals.php');
}

// Get customers and cars for dropdowns
$customers = $db->query("SELECT id, full_name FROM customers WHERE status = 'active' ORDER BY full_name")->fetchAll();
$cars = $db->query("SELECT id, brand, model, plate_number FROM cars WHERE status IN ('available', 'rented') ORDER BY brand")->fetchAll();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $total_days = calculateDays($start_date, $end_date);
    
    $daily_rate = (float)$_POST['daily_rate'];
    $subtotal = $total_days * $daily_rate;
    $discount = (float)$_POST['discount_amount'];
    $tax = $subtotal * (TAX_RATE / 100);
    $total = $subtotal - $discount + $tax;

    $data = [
        'car_id' => (int)$_POST['car_id'],
        'customer_id' => (int)$_POST['customer_id'],
        'start_date' => $start_date,
        'end_date' => $end_date,
        'total_days' => $total_days,
        'daily_rate' => $daily_rate,
        'subtotal' => $subtotal,
        'discount_amount' => $discount,
        'discount_reason' => sanitizeInput($_POST['discount_reason']),
        'tax_amount' => $tax,
        'total_amount' => $total,
        'status' => $_POST['status'],
        'payment_status' => $_POST['payment_status'],
        'pickup_location' => sanitizeInput($_POST['pickup_location']),
        'return_location' => sanitizeInput($_POST['return_location']),
        'notes' => sanitizeInput($_POST['notes'])
    ];

    try {
        $sql = "UPDATE rentals SET 
            car_id = ?, customer_id = ?, start_date = ?, end_date = ?, total_days = ?,
            daily_rate = ?, subtotal = ?, discount_amount = ?, discount_reason = ?,
            tax_amount = ?, total_amount = ?, status = ?, payment_status = ?,
            pickup_location = ?, return_location = ?, notes = ?, updated_at = NOW()
            WHERE id = ?";

        $stmt = $db->prepare($sql);
        $result = $stmt->execute([
            $data['car_id'], $data['customer_id'], $data['start_date'], $data['end_date'],
            $data['total_days'], $data['daily_rate'], $data['subtotal'], $data['discount_amount'],
            $data['discount_reason'], $data['tax_amount'], $data['total_amount'], $data['status'],
            $data['payment_status'], $data['pickup_location'], $data['return_location'],
            $data['notes'], $rental_id
        ]);

        if ($result) {
            logActivity($_SESSION['user_id'], 'rental_update', 'تم تعديل الحجز رقم: ' . $rental['rental_number']);
            $_SESSION['success'] = 'تم تحديث بيانات الحجز بنجاح';
            redirect('rentals.php');
        }
    } catch (Exception $e) {
        $error = 'خطأ في تحديث بيانات الحجز: ' . $e->getMessage();
    }
}

$page_title = 'تعديل الحجز - ' . SITE_NAME;
include 'includes/header.php';
include 'includes/sidebar.php';
?>

<div class="main-content">
    <div class="top-bar">
        <div class="welcome-text">
            <h5><i class="fas fa-edit me-2"></i>تعديل الحجز: <?php echo $rental['rental_number']; ?></h5>
        </div>
        <div class="top-bar-right">
            <a href="rentals.php" class="btn btn-secondary">
                <i class="fas fa-arrow-right me-2"></i>العودة للقائمة
            </a>
        </div>
    </div>

    <?php if ($error): ?>
    <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>

    <div class="table-container">
        <form method="POST" id="rentalForm">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">العميل *</label>
                    <select name="customer_id" class="form-control" required>
                        <?php foreach ($customers as $customer): ?>
                        <option value="<?php echo $customer['id']; ?>" 
                                <?php echo $rental['customer_id'] == $customer['id'] ? 'selected' : ''; ?>>
                            <?php echo $customer['full_name']; ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">السيارة *</label>
                    <select name="car_id" class="form-control" required>
                        <?php foreach ($cars as $car): ?>
                        <option value="<?php echo $car['id']; ?>" 
                                <?php echo $rental['car_id'] == $car['id'] ? 'selected' : ''; ?>>
                            <?php echo $car['brand'] . ' ' . $car['model'] . ' - ' . $car['plate_number']; ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">تاريخ البدء *</label>
                    <input type="date" name="start_date" class="form-control" 
                           value="<?php echo $rental['start_date']; ?>" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">تاريخ الانتهاء *</label>
                    <input type="date" name="end_date" class="form-control" 
                           value="<?php echo $rental['end_date']; ?>" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">الأجرة اليومية (₪) *</label>
                    <input type="number" name="daily_rate" class="form-control" step="0.01" 
                           value="<?php echo $rental['daily_rate']; ?>" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">مبلغ الخصم (₪)</label>
                    <input type="number" name="discount_amount" class="form-control" step="0.01" 
                           value="<?php echo $rental['discount_amount']; ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">سبب الخصم</label>
                    <input type="text" name="discount_reason" class="form-control" 
                           value="<?php echo htmlspecialchars($rental['discount_reason']); ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">حالة الحجز *</label>
                    <select name="status" class="form-control" required>
                        <?php foreach (RENTAL_STATUS as $key => $value): ?>
                        <option value="<?php echo $key; ?>" <?php echo $rental['status'] == $key ? 'selected' : ''; ?>>
                            <?php echo $value; ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">حالة الدفع *</label>
                    <select name="payment_status" class="form-control" required>
                        <?php foreach (PAYMENT_STATUS as $key => $value): ?>
                        <option value="<?php echo $key; ?>" <?php echo $rental['payment_status'] == $key ? 'selected' : ''; ?>>
                            <?php echo $value; ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">مكان الاستلام</label>
                    <input type="text" name="pickup_location" class="form-control" 
                           value="<?php echo htmlspecialchars($rental['pickup_location']); ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">مكان التسليم</label>
                    <input type="text" name="return_location" class="form-control" 
                           value="<?php echo htmlspecialchars($rental['return_location']); ?>">
                </div>
                <div class="col-md-12">
                    <label class="form-label">ملاحظات</label>
                    <textarea name="notes" class="form-control" rows="2"><?php echo htmlspecialchars($rental['notes']); ?></textarea>
                </div>
            </div>
            <div class="mt-4">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-2"></i>حفظ التعديلات
                </button>
                <a href="rentals.php" class="btn btn-secondary">
                    <i class="fas fa-times me-2"></i>إلغاء
                </a>
            </div>
        </form>
    </div>
</div>

<?php include 'includes/footer.php'; ?>