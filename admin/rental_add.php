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

$page_title = 'تأجير جديد - ' . SITE_NAME;
include 'includes/header.php';
include 'includes/sidebar.php';
?>

<style>
.car-search-container {
    position: relative;
    margin-bottom: 15px;
}

.car-search-input {
    width: 100%;
    padding: 12px 45px 12px 15px;
    border: 2px solid #e0e0e0;
    border-radius: 8px;
    font-size: 1rem;
    transition: all 0.3s;
}

.car-search-input:focus {
    border-color: #FF5722;
    outline: none;
    box-shadow: 0 0 0 3px rgba(255, 87, 34, 0.1);
}

.search-icon {
    position: absolute;
    left: 15px;
    top: 50%;
    transform: translateY(-50%);
    color: #999;
    pointer-events: none;
}

.car-results {
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    background: white;
    border: 2px solid #FF5722;
    border-radius: 8px;
    max-height: 400px;
    overflow-y: auto;
    z-index: 1000;
    box-shadow: 0 8px 24px rgba(0,0,0,0.15);
    display: none;
}

.car-results.active {
    display: block;
}

.car-item {
    padding: 15px;
    border-bottom: 1px solid #f0f0f0;
    cursor: pointer;
    transition: all 0.2s;
    display: flex;
    align-items: center;
    gap: 15px;
}

.car-item:hover {
    background: #fff3e0;
}

.car-item:last-child {
    border-bottom: none;
}

.car-icon {
    font-size: 2rem;
    color: #FF5722;
}

.car-info {
    flex: 1;
}

.car-name {
    font-weight: 700;
    color: #333;
    margin-bottom: 3px;
}

.car-details {
    font-size: 0.85rem;
    color: #666;
}

.car-price {
    font-weight: 700;
    color: #FF5722;
    font-size: 1.1rem;
}

.selected-car-info {
    background: linear-gradient(135deg, #fff3e0, #ffe0b2);
    border: 2px solid #FF5722;
    border-radius: 10px;
    padding: 20px;
    margin-bottom: 20px;
    display: none;
}

.selected-car-info.active {
    display: block;
}

.rental-type-selector {
    display: flex;
    gap: 10px;
    margin-bottom: 15px;
}

.rental-type-btn {
    flex: 1;
    padding: 12px;
    border: 2px solid #e0e0e0;
    background: white;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.3s;
    font-weight: 600;
}

.rental-type-btn:hover {
    border-color: #FF5722;
}

.rental-type-btn.active {
    background: #FF5722;
    color: white;
    border-color: #FF5722;
}

.price-editor {
    display: flex;
    align-items: center;
    gap: 10px;
    background: white;
    padding: 15px;
    border-radius: 8px;
    border: 2px solid #e0e0e0;
}

.price-editor input {
    flex: 1;
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 5px;
    font-size: 1.1rem;
    font-weight: 700;
}

.modal {
    display: none;
    position: fixed;
    z-index: 9999;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    overflow: auto;
    background-color: rgba(0,0,0,0.6);
    animation: fadeIn 0.3s;
}

.modal.active {
    display: flex;
    align-items: center;
    justify-content: center;
}

.modal-content {
    background-color: #fefefe;
    padding: 0;
    border-radius: 15px;
    width: 90%;
    max-width: 600px;
    max-height: 90vh;
    overflow-y: auto;
    animation: slideDown 0.3s;
    box-shadow: 0 10px 40px rgba(0,0,0,0.3);
}

.modal-header {
    background: linear-gradient(135deg, #FF5722, #E64A19);
    color: white;
    padding: 20px;
    border-radius: 15px 15px 0 0;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.modal-header h3 {
    margin: 0;
    font-size: 1.3rem;
}

.close-modal {
    background: rgba(255,255,255,0.2);
    border: none;
    color: white;
    font-size: 1.5rem;
    width: 35px;
    height: 35px;
    border-radius: 50%;
    cursor: pointer;
    transition: all 0.3s;
}

.close-modal:hover {
    background: rgba(255,255,255,0.3);
    transform: rotate(90deg);
}

.modal-body {
    padding: 25px;
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

@keyframes slideDown {
    from {
        transform: translateY(-50px);
        opacity: 0;
    }
    to {
        transform: translateY(0);
        opacity: 1;
    }
}

.add-customer-btn {
    background: linear-gradient(135deg, #4CAF50, #388E3C);
    color: white;
    border: none;
    padding: 10px 20px;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 600;
    transition: all 0.3s;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.add-customer-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(76, 175, 80, 0.3);
}

.location-custom {
    display: flex;
    gap: 10px;
    align-items: center;
}

.location-custom select {
    flex: 0 0 40%;
}

.location-custom input {
    flex: 1;
}
</style>

<div class="main-content">
    <div class="top-bar">
        <div class="welcome-text">
            <h5><i class="fas fa-car-side me-2"></i>تأجير جديد</h5>
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
            <input type="hidden" name="car_id" id="selected_car_id">
            
            <div class="row g-3">
                <!-- Car Search -->
                <div class="col-md-12">
                    <label class="form-label">🔍 ابحث عن السيارة *</label>
                    <div class="car-search-container">
                        <input type="text" 
                               id="carSearch" 
                               class="car-search-input" 
                               placeholder="ابحث بالماركة، الموديل، رقم اللوحة..." 
                               autocomplete="off">
                        <i class="fas fa-search search-icon"></i>
                        <div class="car-results" id="carResults"></div>
                    </div>
                </div>

                <!-- Selected Car Info -->
                <div class="col-md-12">
                    <div class="selected-car-info" id="selectedCarInfo">
                        <div class="row align-items-center">
                            <div class="col-md-6">
                                <h5 style="color: #FF5722; margin-bottom: 10px;">
                                    <i class="fas fa-car"></i> <span id="selectedCarName"></span>
                                </h5>
                                <p class="mb-1"><strong>اللوحة:</strong> <span id="selectedCarPlate"></span></p>
                                <p class="mb-1"><strong>اللون:</strong> <span id="selectedCarColor"></span></p>
                                <p class="mb-0"><strong>النوع:</strong> <span id="selectedCarType"></span></p>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label"><strong>نوع التأجير:</strong></label>
                                <div class="rental-type-selector">
                                    <button type="button" class="rental-type-btn active" data-type="daily" data-rate="0">
                                        <i class="fas fa-calendar-day"></i> يومي
                                    </button>
                                    <button type="button" class="rental-type-btn" data-type="weekly" data-rate="0">
                                        <i class="fas fa-calendar-week"></i> أسبوعي
                                    </button>
                                    <button type="button" class="rental-type-btn" data-type="monthly" data-rate="0">
                                        <i class="fas fa-calendar-alt"></i> شهري
                                    </button>
                                </div>
                                
                                <label class="form-label mt-3"><strong>السعر (يمكن التعديل):</strong></label>
                                <div class="price-editor">
                                    <input type="number" id="customPrice" class="" min="0" step="0.01" required>
                                    <span style="font-weight: 700; color: #FF5722;">₪</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Customer Selection -->
                <div class="col-md-12">
                    <label class="form-label">👤 اختر العميل *</label>
                    <div style="display: flex; gap: 10px;">
                        <select name="customer_id" id="customer_id" class="form-control" required style="flex: 1;">
                            <option value="">-- اختر العميل --</option>
                            <?php foreach ($customers as $customer): ?>
                            <option value="<?php echo $customer['id']; ?>">
                                <?php echo $customer['full_name']; ?> - <?php echo $customer['phone']; ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <button type="button" class="add-customer-btn" onclick="openCustomerModal()">
                            <i class="fas fa-user-plus"></i> عميل جديد
                        </button>
                    </div>
                </div>

                <!-- Dates -->
                <div class="col-md-6">
                    <label class="form-label">📅 تاريخ الاستلام *</label>
                    <input type="datetime-local" name="start_date" id="start_date" class="form-control" required onchange="calculateTotal()">
                </div>

                <div class="col-md-6">
                    <label class="form-label">📅 تاريخ التسليم *</label>
                    <input type="datetime-local" name="end_date" id="end_date" class="form-control" required onchange="calculateTotal()">
                </div>

                <!-- Locations -->
                <div class="col-md-6">
                    <label class="form-label">📍 مكان الاستلام</label>
                    <div class="location-custom">
                        <select id="pickup_city" class="form-control">
                            <option value="">-- اختر المدينة --</option>
                            <?php foreach (PALESTINE_CITIES as $city): ?>
                            <option value="<?php echo $city; ?>"><?php echo $city; ?></option>
                            <?php endforeach; ?>
                        </select>
                        <input type="text" name="pickup_location" id="pickup_location" class="form-control" placeholder="العنوان التفصيلي">
                    </div>
                </div>

                <div class="col-md-6">
                    <label class="form-label">📍 مكان التسليم</label>
                    <div class="location-custom">
                        <select id="return_city" class="form-control">
                            <option value="">-- اختر المدينة --</option>
                            <?php foreach (PALESTINE_CITIES as $city): ?>
                            <option value="<?php echo $city; ?>"><?php echo $city; ?></option>
                            <?php endforeach; ?>
                        </select>
                        <input type="text" name="return_location" id="return_location" class="form-control" placeholder="العنوان التفصيلي">
                    </div>
                </div>

                <!-- Notes -->
                <div class="col-md-12">
                    <label class="form-label">📝 ملاحظات</label>
                    <textarea name="notes" class="form-control" rows="2" placeholder="أي ملاحظات إضافية..."></textarea>
                </div>

                <!-- Price Summary -->
                <div class="col-md-12" id="priceCalculation" style="display: none;">
                    <div class="alert alert-info">
                        <h5><i class="fas fa-calculator"></i> ملخص الحجز:</h5>
                        <div class="row">
                            <div class="col-md-4">
                                <p class="mb-1"><strong>عدد الأيام:</strong> <span id="totalDays">0</span> يوم</p>
                            </div>
                            <div class="col-md-4">
                                <p class="mb-1"><strong>السعر:</strong> <span id="dailyRate">0</span>₪</p>
                            </div>
                            <div class="col-md-4">
                                <p class="mb-0"><strong>الإجمالي التقريبي:</strong> <span id="totalAmount" class="text-success fs-4">0</span>₪</p>
                            </div>
                        </div>
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

<!-- Add Customer Modal -->
<div id="customerModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fas fa-user-plus"></i> إضافة عميل جديد</h3>
            <button class="close-modal" onclick="closeCustomerModal()">&times;</button>
        </div>
        <div class="modal-body">
            <form id="addCustomerForm">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">الاسم الكامل *</label>
                        <input type="text" name="full_name" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">رقم الهاتف *</label>
                        <input type="tel" name="phone" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">رقم الهوية *</label>
                        <input type="text" name="id_number" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">رقم الرخصة</label>
                        <input type="text" name="driver_license" class="form-control">
                    </div>
                    <div class="col-md-12">
                        <label class="form-label">العنوان</label>
                        <input type="text" name="address" class="form-control">
                    </div>
                    <div class="col-md-12">
                        <label class="form-label">البريد الإلكتروني</label>
                        <input type="email" name="email" class="form-control">
                    </div>
                </div>
                <div class="mt-4">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>حفظ العميل
                    </button>
                    <button type="button" class="btn btn-secondary" onclick="closeCustomerModal()">
                        <i class="fas fa-times me-2"></i>إلغاء
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
const carsData = <?php echo json_encode($cars); ?>;
let selectedCar = null;
let currentRentalType = 'daily';

// Car Search
document.getElementById('carSearch').addEventListener('input', function(e) {
    const searchTerm = e.target.value.toLowerCase();
    const results = document.getElementById('carResults');
    
    if (searchTerm.length < 1) {
        results.classList.remove('active');
        return;
    }
    
    const filtered = carsData.filter(car => {
        return car.brand.toLowerCase().includes(searchTerm) ||
               car.model.toLowerCase().includes(searchTerm) ||
               car.plate_number.toLowerCase().includes(searchTerm) ||
               car.year.toString().includes(searchTerm);
    });
    
    if (filtered.length > 0) {
        results.innerHTML = filtered.map(car => `
            <div class="car-item" onclick="selectCar(${car.id})">
                <div class="car-icon">🚗</div>
                <div class="car-info">
                    <div class="car-name">${car.brand} ${car.model} (${car.year})</div>
                    <div class="car-details">
                        <span>📋 ${car.plate_number}</span> | 
                        <span>🎨 ${car.color}</span> | 
                        <span>👥 ${car.seats} مقاعد</span>
                    </div>
                </div>
                <div class="car-price">${parseFloat(car.daily_rate).toFixed(2)}₪/يوم</div>
            </div>
        `).join('');
        results.classList.add('active');
    } else {
        results.innerHTML = '<div class="car-item" style="text-align:center; color:#999;">لا توجد نتائج</div>';
        results.classList.add('active');
    }
});

// Select Car
function selectCar(carId) {
    selectedCar = carsData.find(c => c.id === carId);
    if (!selectedCar) return;
    
    document.getElementById('selected_car_id').value = carId;
    document.getElementById('carSearch').value = `${selectedCar.brand} ${selectedCar.model} (${selectedCar.year})`;
    document.getElementById('carResults').classList.remove('active');
    
    // Update selected car info
    document.getElementById('selectedCarName').textContent = `${selectedCar.brand} ${selectedCar.model} ${selectedCar.year}`;
    document.getElementById('selectedCarPlate').textContent = selectedCar.plate_number;
    document.getElementById('selectedCarColor').textContent = selectedCar.color;
    document.getElementById('selectedCarType').textContent = selectedCar.type || 'سيارة';
    
    // Update rental type buttons
    document.querySelectorAll('.rental-type-btn').forEach(btn => {
        const type = btn.dataset.type;
        if (type === 'daily') btn.dataset.rate = selectedCar.daily_rate;
        if (type === 'weekly') btn.dataset.rate = selectedCar.weekly_rate;
        if (type === 'monthly') btn.dataset.rate = selectedCar.monthly_rate;
    });
    
    // Set initial price
    document.getElementById('customPrice').value = parseFloat(selectedCar.daily_rate).toFixed(2);
    document.getElementById('selectedCarInfo').classList.add('active');
    
    calculateTotal();
}

// Rental Type Selection
document.querySelectorAll('.rental-type-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        document.querySelectorAll('.rental-type-btn').forEach(b => b.classList.remove('active'));
        this.classList.add('active');
        
        currentRentalType = this.dataset.type;
        const rate = parseFloat(this.dataset.rate);
        document.getElementById('customPrice').value = rate.toFixed(2);
        
        calculateTotal();
    });
});

// Price Change
document.getElementById('customPrice')?.addEventListener('input', calculateTotal);

// Location City Selection
document.getElementById('pickup_city')?.addEventListener('change', function() {
    const address = document.getElementById('pickup_location');
    if (this.value) {
        address.value = this.value + ', ';
        address.focus();
    }
});

document.getElementById('return_city')?.addEventListener('change', function() {
    const address = document.getElementById('return_location');
    if (this.value) {
        address.value = this.value + ', ';
        address.focus();
    }
});

// Calculate Total
function calculateTotal() {
    const startDate = document.getElementById('start_date').value;
    const endDate = document.getElementById('end_date').value;
    const price = parseFloat(document.getElementById('customPrice')?.value || 0);
    
    if (!startDate || !endDate || !price) return;
    
    const start = new Date(startDate);
    const end = new Date(endDate);
    const diffTime = Math.abs(end - start);
    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24)) + 1;
    
    if (diffDays > 0) {
        const total = diffDays * price;
        document.getElementById('totalDays').textContent = diffDays;
        document.getElementById('dailyRate').textContent = price.toFixed(2);
        document.getElementById('totalAmount').textContent = total.toFixed(2);
        document.getElementById('priceCalculation').style.display = 'block';
    }
}

// Customer Modal
function openCustomerModal() {
    document.getElementById('customerModal').classList.add('active');
}

function closeCustomerModal() {
    document.getElementById('customerModal').classList.remove('active');
    document.getElementById('addCustomerForm').reset();
}

// Add Customer Form Submit
document.getElementById('addCustomerForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    
    try {
        const response = await fetch('ajax/add_customer.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            // Add to select
            const select = document.getElementById('customer_id');
            const option = new Option(`${formData.get('full_name')} - ${formData.get('phone')}`, result.customer_id, true, true);
            select.add(option);
            
            closeCustomerModal();
            
            // Show success message
            alert('تم إضافة العميل بنجاح!');
        } else {
            alert('خطأ: ' + result.message);
        }
    } catch (error) {
        alert('حدث خطأ في الاتصال');
    }
});

// Close results when clicking outside
document.addEventListener('click', function(e) {
    if (!e.target.closest('.car-search-container')) {
        document.getElementById('carResults').classList.remove('active');
    }
});
</script>

<?php include 'includes/footer.php'; ?>