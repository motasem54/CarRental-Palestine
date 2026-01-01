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

// Get all cars for autocomplete
$cars_stmt = $db->query("SELECT id, brand, model, plate_number, year, status FROM cars WHERE status != 'sold' ORDER BY brand");
$cars = $cars_stmt->fetchAll();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $car_id = (int)$_POST['car_id'];
    $maintenance_type = $_POST['maintenance_type']; // Changed from 'type'
    $description = $_POST['description'];
    $maintenance_date = $_POST['maintenance_date'];
    $cost = (float)$_POST['cost'];
    $status = $_POST['status'] ?? 'pending';
    $notes = $_POST['notes'] ?? '';
    
    try {
        $stmt = $db->prepare("
            INSERT INTO maintenance (car_id, maintenance_type, description, maintenance_date, cost, status, notes, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([$car_id, $maintenance_type, $description, $maintenance_date, $cost, $status, $notes]);
        
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

<style>
/* Autocomplete Styles */
.autocomplete {
    position: relative;
    display: inline-block;
    width: 100%;
}

.autocomplete-items {
    position: absolute;
    border: 1px solid #d4d4d4;
    border-bottom: none;
    border-top: none;
    z-index: 99;
    top: 100%;
    left: 0;
    right: 0;
    max-height: 200px;
    overflow-y: auto;
    background: white;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
}

.autocomplete-items div {
    padding: 10px;
    cursor: pointer;
    background-color: #fff;
    border-bottom: 1px solid #d4d4d4;
}

.autocomplete-items div:hover {
    background-color: #e9e9e9;
}

.autocomplete-active {
    background-color: #FF5722 !important;
    color: #ffffff;
}
</style>

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
        
        <form method="POST" id="maintenanceForm">
            <div class="row g-3">
                <!-- Car Search with Autocomplete -->
                <div class="col-md-6">
                    <label class="form-label"><i class="fas fa-car me-2"></i>السيارة <span class="text-danger">*</span></label>
                    <div class="autocomplete">
                        <input type="text" id="carSearch" class="form-control" 
                               placeholder="ابحث عن السيارة (الماركة، الموديل، أو اللوحة)" 
                               autocomplete="off" required>
                        <input type="hidden" name="car_id" id="carId" required>
                    </div>
                    <small class="text-muted">ابدأ بالكتابة للبحث...</small>
                </div>
                
                <!-- Maintenance Type (ENUM VALUES FROM DB) -->
                <div class="col-md-6">
                    <label class="form-label"><i class="fas fa-tools me-2"></i>نوع الصيانة <span class="text-danger">*</span></label>
                    <select name="maintenance_type" class="form-select" required>
                        <option value="">اختر النوع</option>
                        <option value="regular">صيانة دورية</option>
                        <option value="repair">إصلاح</option>
                        <option value="inspection">فحص</option>
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
            <i class="fas fa-info-circle me-2"></i>أنواع الصيانة المتاحة
        </h6>
        <div class="row">
            <div class="col-md-3">
                <strong>⚙️ صيانة دورية (Regular):</strong>
                <p style="margin: 5px 0; opacity: 0.9;">صيانة روتينية كل 5,000 كم</p>
            </div>
            <div class="col-md-3">
                <strong>🔧 إصلاح (Repair):</strong>
                <p style="margin: 5px 0; opacity: 0.9;">إصلاح أعطال وقطع</p>
            </div>
            <div class="col-md-3">
                <strong>🔍 فحص (Inspection):</strong>
                <p style="margin: 5px 0; opacity: 0.9;">فحص دوري أو قبل البيع</p>
            </div>
            <div class="col-md-3">
                <strong>📝 أخرى (Other):</strong>
                <p style="margin: 5px 0; opacity: 0.9;">أي نوع آخر</p>
            </div>
        </div>
    </div>
</div>

<script>
// Cars data for autocomplete
const carsData = <?php echo json_encode($cars); ?>;

// Autocomplete functionality
function autocomplete(inp, arr) {
    let currentFocus;
    
    inp.addEventListener('input', function(e) {
        let val = this.value;
        closeAllLists();
        if (!val) { return false; }
        currentFocus = -1;
        
        let a = document.createElement('DIV');
        a.setAttribute('id', this.id + 'autocomplete-list');
        a.setAttribute('class', 'autocomplete-items');
        this.parentNode.appendChild(a);
        
        let matches = 0;
        for (let i = 0; i < arr.length; i++) {
            const car = arr[i];
            const searchText = (car.brand + ' ' + car.model + ' ' + car.plate_number + ' ' + car.year).toLowerCase();
            
            if (searchText.includes(val.toLowerCase())) {
                matches++;
                let b = document.createElement('DIV');
                
                // Display format
                let displayText = `<strong>${car.brand} ${car.model} ${car.year}</strong> - ${car.plate_number}`;
                if (car.status === 'maintenance') {
                    displayText += ' <span style="color: #ff9800;">(في الصيانة)</span>';
                }
                
                b.innerHTML = displayText;
                b.innerHTML += `<input type='hidden' value='${car.id}' data-text='${car.brand} ${car.model} - ${car.plate_number}'>`;
                
                b.addEventListener('click', function(e) {
                    const input = this.getElementsByTagName('input')[0];
                    document.getElementById('carSearch').value = input.getAttribute('data-text');
                    document.getElementById('carId').value = input.value;
                    closeAllLists();
                });
                
                a.appendChild(b);
                
                if (matches >= 10) break; // Limit results
            }
        }
        
        if (matches === 0) {
            let b = document.createElement('DIV');
            b.innerHTML = '<em style="color: #999;">لا توجد نتائج</em>';
            a.appendChild(b);
        }
    });
    
    inp.addEventListener('keydown', function(e) {
        let x = document.getElementById(this.id + 'autocomplete-list');
        if (x) x = x.getElementsByTagName('div');
        if (e.keyCode == 40) {
            currentFocus++;
            addActive(x);
        } else if (e.keyCode == 38) {
            currentFocus--;
            addActive(x);
        } else if (e.keyCode == 13) {
            e.preventDefault();
            if (currentFocus > -1) {
                if (x) x[currentFocus].click();
            }
        }
    });
    
    function addActive(x) {
        if (!x) return false;
        removeActive(x);
        if (currentFocus >= x.length) currentFocus = 0;
        if (currentFocus < 0) currentFocus = (x.length - 1);
        x[currentFocus].classList.add('autocomplete-active');
    }
    
    function removeActive(x) {
        for (let i = 0; i < x.length; i++) {
            x[i].classList.remove('autocomplete-active');
        }
    }
    
    function closeAllLists(elmnt) {
        const x = document.getElementsByClassName('autocomplete-items');
        for (let i = 0; i < x.length; i++) {
            if (elmnt != x[i] && elmnt != inp) {
                x[i].parentNode.removeChild(x[i]);
            }
        }
    }
    
    document.addEventListener('click', function (e) {
        closeAllLists(e.target);
    });
}

// Initialize autocomplete
autocomplete(document.getElementById('carSearch'), carsData);

// Form validation
document.getElementById('maintenanceForm').addEventListener('submit', function(e) {
    const carId = document.getElementById('carId').value;
    if (!carId) {
        e.preventDefault();
        alert('الرجاء اختيار سيارة من القائمة');
        document.getElementById('carSearch').focus();
    }
});
</script>

<?php include 'includes/footer.php'; ?>