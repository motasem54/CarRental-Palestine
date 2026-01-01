<?php
require_once '../config/settings.php';
require_once '../core/Auth.php';
require_once '../core/Rental.php';

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    redirect(ADMIN_URL . '/login.php');
}

$db = Database::getInstance()->getConnection();

$rentalId = (int)$_GET['id'] ?? 0;
if (!$rentalId) {
    redirect('rentals.php');
}

// Get rental info
$stmt = $db->prepare("SELECT * FROM rentals WHERE id = ?");
$stmt->execute([$rentalId]);
$rental = $stmt->fetch();

if (!$rental) {
    redirect('rentals.php');
}

// Get customer and car info
$stmt = $db->prepare("SELECT * FROM customers WHERE id = ?");
$stmt->execute([$rental['customer_id']]);
$customer = $stmt->fetch();

$stmt = $db->prepare("SELECT * FROM cars WHERE id = ?");
$stmt->execute([$rental['car_id']]);
$car = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $contractType = sanitizeInput($_POST['contract_type'] ?? 'simple');
    
    if (!in_array($contractType, ['simple', 'with_promissory'])) {
        $contractType = 'simple';
    }
    
    $hasPromissory = $contractType === 'with_promissory' ? 1 : 0;
    
    // Create contract record - ✅ بالحقول الصحيحة فقط
    $stmt = $db->prepare("
        INSERT INTO rental_contracts (rental_id, contract_type, has_promissory_note, created_at)
        VALUES (?, ?, ?, NOW())
        ON DUPLICATE KEY UPDATE contract_type = VALUES(contract_type), has_promissory_note = VALUES(has_promissory_note)
    ");
    
    $stmt->execute([
        $rentalId,
        $contractType,
        $hasPromissory
    ]);
    
    // Update rental contract_signed field
    $stmt = $db->prepare("UPDATE rentals SET contract_signed = 1 WHERE id = ?");
    $stmt->execute([$rentalId]);
    
    $_SESSION['success'] = 'تم اختيار نوع العقد بنجاح';
    redirect('contract_print.php?id=' . $rentalId);
}

$page_title = 'اختيار نوع العقد - ' . SITE_NAME;
include 'includes/header.php';
include 'includes/sidebar.php';
?>

<style>
.contract-chooser-container {
    max-width: 900px;
    margin: 0 auto;
    padding: 40px 20px;
}

.contract-header {
    background: linear-gradient(135deg, #FF5722, #E64A19);
    color: white;
    padding: 40px;
    border-radius: 15px;
    text-align: center;
    margin-bottom: 40px;
    box-shadow: 0 10px 30px rgba(255, 87, 34, 0.2);
}

.contract-header h2 {
    margin: 0 0 10px 0;
    font-size: 2rem;
}

.contract-header p {
    margin: 0;
    opacity: 0.95;
    font-size: 1.1rem;
}

.rental-summary {
    background: white;
    padding: 25px;
    border-radius: 10px;
    border-left: 5px solid #FF5722;
    margin-bottom: 40px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
}

.summary-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-bottom: 15px;
}

.summary-row:last-child {
    margin-bottom: 0;
}

.summary-item {
    display: flex;
    flex-direction: column;
}

.summary-label {
    font-size: 0.9rem;
    color: #666;
    margin-bottom: 5px;
    font-weight: 600;
}

.summary-value {
    font-size: 1.1rem;
    color: #333;
    font-weight: 700;
}

.contract-options {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 25px;
    margin-bottom: 40px;
}

.contract-option {
    background: white;
    border: 3px solid #e0e0e0;
    border-radius: 15px;
    padding: 30px;
    text-align: center;
    cursor: pointer;
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}

.contract-option::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 5px;
    background: linear-gradient(90deg, #FF5722, #E64A19);
    transform: scaleX(0);
    transform-origin: left;
    transition: transform 0.3s ease;
}

.contract-option:hover {
    border-color: #FF5722;
    box-shadow: 0 10px 30px rgba(255, 87, 34, 0.15);
    transform: translateY(-5px);
}

.contract-option:hover::before {
    transform: scaleX(1);
}

.contract-option input[type="radio"] {
    position: absolute;
    opacity: 0;
    width: 0;
    height: 0;
}

.contract-option input[type="radio"]:checked + .option-content {
    color: #FF5722;
}

.contract-option input[type="radio"]:checked ~ .option-check {
    opacity: 1;
}

.option-content {
    position: relative;
    z-index: 1;
    transition: all 0.3s ease;
}

.option-icon {
    font-size: 3.5rem;
    margin-bottom: 15px;
    display: block;
}

.option-title {
    font-size: 1.3rem;
    font-weight: 700;
    margin-bottom: 10px;
    color: #333;
    transition: color 0.3s ease;
}

.contract-option:hover .option-title {
    color: #FF5722;
}

.option-description {
    color: #666;
    font-size: 0.95rem;
    line-height: 1.6;
    margin-bottom: 15px;
}

.option-features {
    text-align: left;
    background: #f8f9fa;
    border-radius: 8px;
    padding: 15px;
    margin-top: 15px;
}

.feature-item {
    padding: 8px 0;
    border-bottom: 1px solid #e0e0e0;
    font-size: 0.9rem;
    color: #555;
    display: flex;
    align-items: center;
    gap: 10px;
}

.feature-item:last-child {
    border-bottom: none;
}

.feature-item::before {
    content: '✓';
    color: #4CAF50;
    font-weight: bold;
    font-size: 1.1rem;
}

.option-check {
    position: absolute;
    top: 15px;
    right: 15px;
    width: 30px;
    height: 30px;
    background: #FF5722;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: bold;
    opacity: 0;
    transition: all 0.3s ease;
    box-shadow: 0 3px 10px rgba(255, 87, 34, 0.3);
}

.form-actions {
    display: flex;
    gap: 15px;
    justify-content: center;
    margin-top: 40px;
}

.form-actions button,
.form-actions a {
    padding: 15px 40px;
    font-size: 1.05rem;
    font-weight: 600;
    border-radius: 10px;
    border: none;
    cursor: pointer;
    transition: all 0.3s ease;
}

.btn-continue {
    background: linear-gradient(135deg, #FF5722, #E64A19);
    color: white;
    min-width: 200px;
}

.btn-continue:hover {
    transform: translateY(-3px);
    box-shadow: 0 10px 25px rgba(255, 87, 34, 0.3);
}

.btn-continue:disabled {
    opacity: 0.5;
    cursor: not-allowed;
    transform: none;
}

.btn-back {
    background: #f0f0f0;
    color: #333;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.btn-back:hover {
    background: #e0e0e0;
    color: #FF5722;
}

.info-box {
    background: #e3f2fd;
    border-left: 4px solid #2196F3;
    padding: 15px;
    border-radius: 5px;
    margin-bottom: 20px;
    color: #1565c0;
}

.info-box strong {
    display: block;
    margin-bottom: 5px;
}

@media (max-width: 768px) {
    .contract-chooser-container {
        padding: 20px 10px;
    }
    
    .contract-header {
        padding: 25px 15px;
        margin-bottom: 25px;
    }
    
    .contract-header h2 {
        font-size: 1.5rem;
    }
    
    .contract-options {
        grid-template-columns: 1fr;
        gap: 15px;
    }
    
    .form-actions {
        flex-direction: column;
    }
    
    .btn-continue,
    .btn-back {
        width: 100%;
        justify-content: center;
    }
}
</style>

<div class="main-content">
    <div class="contract-chooser-container">
        <!-- Header -->
        <div class="contract-header">
            <h2><i class="fas fa-file-contract"></i> اختيار نوع العقد</h2>
            <p>يرجى اختيار نوع العقد المناسب قبل الطباعة والتوقيع</p>
        </div>

        <!-- Rental Summary -->
        <div class="rental-summary">
            <h4 style="margin-top: 0; color: #FF5722;">
                <i class="fas fa-info-circle"></i> ملخص الحجز
            </h4>
            
            <div class="summary-row">
                <div class="summary-item">
                    <span class="summary-label">رقم الحجز</span>
                    <span class="summary-value">#<?php echo $rental['rental_number']; ?></span>
                </div>
                <div class="summary-item">
                    <span class="summary-label">اسم العميل</span>
                    <span class="summary-value"><?php echo $customer['full_name']; ?></span>
                </div>
                <div class="summary-item">
                    <span class="summary-label">الهاتف</span>
                    <span class="summary-value"><?php echo $customer['phone']; ?></span>
                </div>
            </div>
            
            <div class="summary-row">
                <div class="summary-item">
                    <span class="summary-label">السيارة</span>
                    <span class="summary-value"><?php echo $car['brand'] . ' ' . $car['model'] . ' (' . $car['year'] . ')'; ?></span>
                </div>
                <div class="summary-item">
                    <span class="summary-label">رقم اللوحة</span>
                    <span class="summary-value" style="font-family: monospace;"><?php echo $car['plate_number']; ?></span>
                </div>
                <div class="summary-item">
                    <span class="summary-label">عداد الاستلام</span>
                    <span class="summary-value"><?php echo $rental['mileage_start'] ?? 0; ?> كم</span>
                </div>
            </div>
            
            <div class="summary-row">
                <div class="summary-item">
                    <span class="summary-label">تاريخ الاستلام</span>
                    <span class="summary-value"><?php echo date('d/m/Y H:i', strtotime($rental['start_date'])); ?></span>
                </div>
                <div class="summary-item">
                    <span class="summary-label">تاريخ التسليم</span>
                    <span class="summary-value"><?php echo date('d/m/Y H:i', strtotime($rental['end_date'])); ?></span>
                </div>
                <div class="summary-item">
                    <span class="summary-label">الإجمالي</span>
                    <span class="summary-value" style="color: #FF5722;"><?php echo $rental['total_amount']; ?>₪</span>
                </div>
            </div>
        </div>

        <!-- Info Box -->
        <div class="info-box">
            <strong><i class="fas fa-lightbulb"></i> معلومة مهمة:</strong>
            <p style="margin: 0;">
                اختر نوع العقد المناسب:
                <br>
                <strong>• العقد البسيط:</strong> للحجوزات العادية والقصيرة
                <br>
                <strong>• العقد مع الكمبيالة:</strong> للحجوزات الطويلة والعملاء الجدد
            </p>
        </div>

        <!-- Contract Options Form -->
        <form method="POST" id="contractForm">
            <div class="contract-options">
                <!-- Simple Contract Option -->
                <label class="contract-option">
                    <input type="radio" name="contract_type" value="simple" checked>
                    <div class="option-content">
                        <span class="option-icon">📋</span>
                        <div class="option-title">العقد البسيط</div>
                        <p class="option-description">
                            عقد تأجير قياسي بسيط وواضح
                        </p>
                        <div class="option-features">
                            <div class="feature-item">معلومات العميل والسيارة</div>
                            <div class="feature-item">شروط التأجير والأسعار</div>
                            <div class="feature-item">شروط الدفع والإلغاء</div>
                            <div class="feature-item">توقيع العميل والموظف</div>
                            <div class="feature-item">مناسب للحجوزات القصيرة</div>
                        </div>
                    </div>
                    <div class="option-check">✓</div>
                </label>

                <!-- Promissory Contract Option -->
                <label class="contract-option">
                    <input type="radio" name="contract_type" value="with_promissory">
                    <div class="option-content">
                        <span class="option-icon">✅</span>
                        <div class="option-title">عقد مع كمبيالة</div>
                        <p class="option-description">
                            عقد متقدم مع كمبيالة للضمان
                        </p>
                        <div class="option-features">
                            <div class="feature-item">كل محتويات العقد البسيط</div>
                            <div class="feature-item">كمبيالة استحقاق</div>
                            <div class="feature-item">بيانات كاملة للعميل</div>
                            <div class="feature-item">ضمان مالي إضافي</div>
                            <div class="feature-item">مناسب للحجوزات الطويلة</div>
                        </div>
                    </div>
                    <div class="option-check">✓</div>
                </label>
            </div>

            <!-- Form Actions -->
            <div class="form-actions">
                <button type="submit" class="btn-continue">
                    <i class="fas fa-arrow-left"></i> اختر نوع العقد والمتابعة
                </button>
                <a href="rental_add.php" class="btn-back">
                    <i class="fas fa-arrow-right"></i> العودة
                </a>
            </div>
        </form>
    </div>
</div>

<script>
// Smooth radio button interaction
document.querySelectorAll('.contract-option input[type="radio"]').forEach(input => {
    input.addEventListener('change', function() {
        document.querySelectorAll('.contract-option').forEach(option => {
            option.style.borderColor = '#e0e0e0';
        });
        this.closest('.contract-option').style.borderColor = '#FF5722';
    });
});

// Auto select first option
document.querySelector('.contract-option input[type="radio"]').closest('.contract-option').style.borderColor = '#FF5722';

// Form validation
document.getElementById('contractForm').addEventListener('submit', function(e) {
    const selected = document.querySelector('.contract-option input[type="radio"]:checked');
    if (!selected) {
        e.preventDefault();
        alert('يرجى اختيار نوع العقد');
    }
});
</script>

<?php include 'includes/footer.php'; ?>