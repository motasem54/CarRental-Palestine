<?php
require_once '../config/settings.php';
require_once '../core/Auth.php';
require_once '../core/Rental.php';

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    redirect(ADMIN_URL . '/login.php');
}

$db = Database::getInstance()->getConnection();
$rental = new Rental($db);
$error = '';

// Get available cars
$carsStmt = $db->query("SELECT * FROM cars WHERE status = 'available' ORDER BY brand, model");
$cars = $carsStmt->fetchAll();

// Get customers
$customersStmt = $db->query("SELECT * FROM customers WHERE status = 'active' ORDER BY full_name");
$customers = $customersStmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $data = [
            'car_id' => (int)$_POST['car_id'],
            'customer_id' => (int)$_POST['customer_id'],
            'start_date' => $_POST['start_date'],
            'end_date' => $_POST['end_date'],
            'pickup_location' => sanitizeInput($_POST['pickup_location']),
            'return_location' => sanitizeInput($_POST['return_location']),
            'notes' => sanitizeInput($_POST['notes'] ?? ''),
            'discount_code' => sanitizeInput($_POST['discount_code'] ?? ''),
            'user_id' => $_SESSION['user_id']
        ];

        $rentalId = $rental->createRental($data);
        
        if ($rentalId) {
            $_SESSION['success'] = 'تم إنشاء الحجز بنجاح';
            redirect('rentals.php');
        }
    } catch (Exception $e) {
        $error = 'خطأ في إنشاء الحجز: ' . $e->getMessage();
    }
}

$page_title = 'حجز جديد - ' . SITE_NAME;
include 'includes/header.php';
include 'includes/sidebar.php';
?>

<div class="main-content">
    <div class="top-bar">
        <div class="welcome-text">
            <h5><i class="fas fa-plus-circle me-2"></i>إضافة حجز جديد</h5>
        </div>
        <div class="top-bar-right">
            <a href="rentals.php" class="btn btn-secondary">
                <i class="fas fa-arrow-right me-2"></i>العودة
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
                    <label class="form-label">اختر السيارة *</label>
                    <select name="car_id" id="car_id" class="form-control" required onchange="updateCarPrice()">
                        <option value="">-- اختر السيارة --</option>
                        <?php foreach ($cars as $car): ?>
                        <option value="<?php echo $car['id']; ?>" 
                                data-daily="<?php echo $car['daily_rate']; ?>"
                                data-weekly="<?php echo $car['weekly_rate']; ?>"
                                data-monthly="<?php echo $car['monthly_rate']; ?>">
                            <?php echo $car['brand'] . ' ' . $car['model'] . ' ' . $car['year']; ?>
                            (<?php echo $car['plate_number']; ?>) - 
                            <?php echo formatCurrency($car['daily_rate']); ?>/يوم
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-6">
                    <label class="form-label">اختر العميل *</label>
                    <select name="customer_id" class="form-control" required>
                        <option value="">-- اختر العميل --</option>
                        <?php foreach ($customers as $customer): ?>
                        <option value="<?php echo $customer['id']; ?>">
                            <?php echo $customer['full_name']; ?> - <?php echo $customer['phone']; ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-6">
                    <label class="form-label">تاريخ الاستلام *</label>
                    <input type="datetime-local" name="start_date" id="start_date" class="form-control" required onchange="calculateTotal()">
                </div>

                <div class="col-md-6">
                    <label class="form-label">تاريخ التسليم *</label>
                    <input type="datetime-local" name="end_date" id="end_date" class="form-control" required onchange="calculateTotal()">
                </div>

                <div class="col-md-6">
                    <label class="form-label">مكان الاستلام</label>
                    <select name="pickup_location" class="form-control">
                        <?php foreach (PALESTINE_CITIES as $city): ?>
                        <option value="<?php echo $city; ?>"><?php echo $city; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-6">
                    <label class="form-label">مكان التسليم</label>
                    <select name="return_location" class="form-control">
                        <?php foreach (PALESTINE_CITIES as $city): ?>
                        <option value="<?php echo $city; ?>"><?php echo $city; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-6">
                    <label class="form-label">كود الخصم</label>
                    <input type="text" name="discount_code" class="form-control" placeholder="اختياري">
                </div>

                <div class="col-md-12">
                    <label class="form-label">ملاحظات</label>
                    <textarea name="notes" class="form-control" rows="2"></textarea>
                </div>

                <div class="col-md-12" id="priceCalculation" style="display: none;">
                    <div class="alert alert-info">
                        <h5>ملخص الحجز:</h5>
                        <p class="mb-1"><strong>عدد الأيام:</strong> <span id="totalDays">0</span> يوم</p>
                        <p class="mb-1"><strong>الأجرة اليومية:</strong> <span id="dailyRate">0</span>₪</p>
                        <p class="mb-0"><strong>الإجمالي التقريبي:</strong> <span id="totalAmount" class="text-success fs-4">0</span>₪</p>
                    </div>
                </div>
            </div>

            <div class="mt-4">
                <button type="submit" class="btn btn-primary btn-lg">
                    <i class="fas fa-save me-2"></i>حفظ الحجز
                </button>
                <a href="rentals.php" class="btn btn-secondary btn-lg">
                    <i class="fas fa-times me-2"></i>إلغاء
                </a>
            </div>
        </form>
    </div>
</div>

<script>
function updateCarPrice() {
    calculateTotal();
}

function calculateTotal() {
    const carSelect = document.getElementById('car_id');
    const startDate = document.getElementById('start_date').value;
    const endDate = document.getElementById('end_date').value;
    
    if (!carSelect.value || !startDate || !endDate) return;
    
    const selectedOption = carSelect.options[carSelect.selectedIndex];
    const dailyRate = parseFloat(selectedOption.getAttribute('data-daily'));
    
    const start = new Date(startDate);
    const end = new Date(endDate);
    const diffTime = Math.abs(end - start);
    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24)) + 1;
    
    if (diffDays > 0) {
        const total = diffDays * dailyRate;
        document.getElementById('totalDays').textContent = diffDays;
        document.getElementById('dailyRate').textContent = dailyRate.toFixed(2);
        document.getElementById('totalAmount').textContent = total.toFixed(2);
        document.getElementById('priceCalculation').style.display = 'block';
    }
}
</script>

<?php include 'includes/footer.php'; ?>