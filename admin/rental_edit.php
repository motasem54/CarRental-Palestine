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
if (!isset($_GET['id'])) {
    redirect('rentals.php');
}

$rentalId = (int)$_GET['id'];

// Fetch rental data
try {
    $stmt = $db->prepare("
        SELECT r.*, c.full_name as customer_name, car.brand, car.model, car.plate_number
        FROM rentals r
        JOIN customers c ON r.customer_id = c.id
        JOIN cars car ON r.car_id = car.id
        WHERE r.id = ?
    ");
    $stmt->execute([$rentalId]);
    $rental = $stmt->fetch();
    
    if (!$rental) {
        $_SESSION['error'] = 'الحجز غير موجود';
        redirect('rentals.php');
    }
} catch (Exception $e) {
    $error = 'خطأ في جلب بيانات الحجز';
}

// Get customers list
$customersStmt = $db->query("SELECT id, full_name, phone FROM customers WHERE status = 'active' ORDER BY full_name");
$customers = $customersStmt->fetchAll();

// Get available cars
$carsStmt = $db->query("SELECT id, brand, model, plate_number, daily_rate FROM cars WHERE status IN ('available', 'reserved') ORDER BY brand, model");
$cars = $carsStmt->fetchAll();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'customer_id' => (int)$_POST['customer_id'],
        'car_id' => (int)$_POST['car_id'],
        'start_date' => $_POST['start_date'],
        'end_date' => $_POST['end_date'],
        'actual_return_date' => $_POST['actual_return_date'] ?: null,
        'pickup_location' => sanitizeInput($_POST['pickup_location']),
        'return_location' => sanitizeInput($_POST['return_location']),
        'daily_rate' => (float)$_POST['daily_rate'],
        'discount_amount' => (float)($_POST['discount_amount'] ?? 0),
        'discount_reason' => sanitizeInput($_POST['discount_reason']),
        'status' => $_POST['status'],
        'payment_status' => $_POST['payment_status'],
        'notes' => sanitizeInput($_POST['notes'])
    ];

    // Calculate days and amounts
    $totalDays = calculateDays($data['start_date'], $data['end_date']);
    $subtotal = $totalDays * $data['daily_rate'];
    $taxAmount = $subtotal * (TAX_RATE / 100);
    $totalAmount = $subtotal + $taxAmount - $data['discount_amount'];

    try {
        $sql = "UPDATE rentals SET 
                customer_id = ?, car_id = ?, start_date = ?, end_date = ?, 
                actual_return_date = ?, pickup_location = ?, return_location = ?,
                total_days = ?, daily_rate = ?, subtotal = ?, 
                discount_amount = ?, discount_reason = ?, tax_amount = ?,
                total_amount = ?, status = ?, payment_status = ?, 
                notes = ?, updated_at = NOW()
                WHERE id = ?";

        $stmt = $db->prepare($sql);
        $result = $stmt->execute([
            $data['customer_id'], $data['car_id'], $data['start_date'], $data['end_date'],
            $data['actual_return_date'], $data['pickup_location'], $data['return_location'],
            $totalDays, $data['daily_rate'], $subtotal,
            $data['discount_amount'], $data['discount_reason'], $taxAmount,
            $totalAmount, $data['status'], $data['payment_status'],
            $data['notes'], $rentalId
        ]);

        if ($result) {
            // Update car status
            if ($data['status'] === 'active') {
                $db->prepare("UPDATE cars SET status = 'rented' WHERE id = ?")->execute([$data['car_id']]);
            } elseif ($data['status'] === 'completed' || $data['status'] === 'cancelled') {
                $db->prepare("UPDATE cars SET status = 'available' WHERE id = ?")->execute([$data['car_id']]);
            }
            
            $_SESSION['success'] = 'تم تحديث بيانات الحجز بنجاح';
            redirect('rentals.php');
        }
    } catch (Exception $e) {
        $error = 'خطأ في تحديث بيانات الحجز: ' . $e->getMessage();
    }
}

$page_title = 'تعديل بيانات الحجز - ' . SITE_NAME;
include 'includes/header.php';
include 'includes/sidebar.php';
?>

<div class="main-content">
    <div class="top-bar">
        <div class="welcome-text">
            <h5><i class="fas fa-edit me-2"></i>تعديل بيانات الحجز #<?php echo $rental['rental_number']; ?></h5>
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
                    <label class="form-label">رقم الحجز</label>
                    <input type="text" class="form-control" value="<?php echo $rental['rental_number']; ?>" readonly>
                </div>
                <div class="col-md-6">
                    <label class="form-label">العميل *</label>
                    <select name="customer_id" class="form-control" required>
                        <?php foreach ($customers as $customer): ?>
                        <option value="<?php echo $customer['id']; ?>" 
                                <?php echo ($rental['customer_id'] == $customer['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($customer['full_name'] . ' - ' . $customer['phone']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">السيارة *</label>
                    <select name="car_id" id="car_id" class="form-control" required>
                        <option value="<?php echo $rental['car_id']; ?>" selected>
                            <?php echo htmlspecialchars($rental['brand'] . ' ' . $rental['model'] . ' - ' . $rental['plate_number']); ?>
                        </option>
                        <?php foreach ($cars as $car): 
                            if ($car['id'] != $rental['car_id']):
                        ?>
                        <option value="<?php echo $car['id']; ?>" data-rate="<?php echo $car['daily_rate']; ?>">
                            <?php echo htmlspecialchars($car['brand'] . ' ' . $car['model'] . ' - ' . $car['plate_number']); ?>
                        </option>
                        <?php 
                            endif;
                        endforeach; 
                        ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">الأجرة اليومية (₪) *</label>
                    <input type="number" name="daily_rate" id="daily_rate" class="form-control" 
                           value="<?php echo $rental['daily_rate']; ?>" step="0.01" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">تاريخ البداية *</label>
                    <input type="date" name="start_date" id="start_date" class="form-control" 
                           value="<?php echo $rental['start_date']; ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">تاريخ الانتهاء *</label>
                    <input type="date" name="end_date" id="end_date" class="form-control" 
                           value="<?php echo $rental['end_date']; ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">تاريخ الإرجاع الفعلي</label>
                    <input type="date" name="actual_return_date" class="form-control" 
                           value="<?php echo $rental['actual_return_date']; ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">مكان الاستلام</label>
                    <input type="text" name="pickup_location" class="form-control" 
                           value="<?php echo htmlspecialchars($rental['pickup_location']); ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">مكان الإرجاع</label>
                    <input type="text" name="return_location" class="form-control" 
                           value="<?php echo htmlspecialchars($rental['return_location']); ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">قيمة الخصم (₪)</label>
                    <input type="number" name="discount_amount" class="form-control" 
                           value="<?php echo $rental['discount_amount']; ?>" step="0.01">
                </div>
                <div class="col-md-6">
                    <label class="form-label">سبب الخصم</label>
                    <input type="text" name="discount_reason" class="form-control" 
                           value="<?php echo htmlspecialchars($rental['discount_reason'] ?? ''); ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">حالة الحجز *</label>
                    <select name="status" class="form-control" required>
                        <?php foreach (RENTAL_STATUS as $key => $value): ?>
                        <option value="<?php echo $key; ?>" 
                                <?php echo ($rental['status'] === $key) ? 'selected' : ''; ?>>
                            <?php echo $value; ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">حالة الدفع *</label>
                    <select name="payment_status" class="form-control" required>
                        <?php foreach (PAYMENT_STATUS as $key => $value): ?>
                        <option value="<?php echo $key; ?>" 
                                <?php echo ($rental['payment_status'] === $key) ? 'selected' : ''; ?>>
                            <?php echo $value; ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-12">
                    <label class="form-label">ملاحظات</label>
                    <textarea name="notes" class="form-control" rows="3"><?php echo htmlspecialchars($rental['notes'] ?? ''); ?></textarea>
                </div>
            </div>
            <div class="mt-4">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-2"></i>حفظ التغييرات
                </button>
                <a href="rentals.php" class="btn btn-secondary">
                    <i class="fas fa-times me-2"></i>إلغاء
                </a>
            </div>
        </form>
    </div>
</div>

<script>
// Update daily rate when car changes
document.getElementById('car_id').addEventListener('change', function() {
    const selectedOption = this.options[this.selectedIndex];
    const rate = selectedOption.getAttribute('data-rate');
    if (rate) {
        document.getElementById('daily_rate').value = rate;
    }
});
</script>

<?php include 'includes/footer.php'; ?>