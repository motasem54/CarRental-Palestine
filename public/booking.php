<?php
require_once '../config/settings.php';

$db = Database::getInstance()->getConnection();
$success = '';
$error = '';

// Handle booking submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $db->beginTransaction();
        
        // Generate booking number
        $bookingNumber = generateBookingNumber();
        
        // Insert booking
        $sql = "INSERT INTO online_bookings (
            booking_number, car_id, customer_name, customer_phone, 
            customer_email, id_number, start_date, end_date, notes, 
            status, created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())";
        
        $stmt = $db->prepare($sql);
        $result = $stmt->execute([
            $bookingNumber,
            (int)$_POST['car_id'],
            sanitizeInput($_POST['customer_name']),
            sanitizeInput($_POST['customer_phone']),
            sanitizeInput($_POST['customer_email']),
            sanitizeInput($_POST['id_number']),
            $_POST['start_date'],
            $_POST['end_date'],
            sanitizeInput($_POST['notes'] ?? '')
        ]);
        
        if ($result) {
            // Update car status to reserved
            $updateStmt = $db->prepare("UPDATE cars SET status = 'reserved' WHERE id = ?");
            $updateStmt->execute([(int)$_POST['car_id']]);
            
            $db->commit();
            $success = 'تم إرسال طلب الحجز بنجاح! رقم الحجز: ' . $bookingNumber . '<br>سيتم التواصل معك قريباً.';
        }
    } catch (Exception $e) {
        $db->rollBack();
        $error = 'حدث خطأ في إرسال الطلب. الرجاء المحاولة مرة أخرى.';
    }
}

// Get car details if car_id provided
$car = null;
if (isset($_GET['car_id'])) {
    $stmt = $db->prepare("SELECT * FROM cars WHERE id = ? AND status = 'available'");
    $stmt->execute([(int)$_GET['car_id']]);
    $car = $stmt->fetch();
}

// Get all available cars
$stmt = $db->query("
    SELECT * FROM cars 
    WHERE status = 'available'
    ORDER BY daily_rate ASC
");
$cars = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>احجز سيارتك الآن - <?php echo SITE_NAME; ?></title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary: #FF5722;
            --primary-dark: #E64A19;
            --dark: #121212;
        }
        
        body {
            font-family: 'Cairo', sans-serif;
            background: #f8f9fa;
        }
        
        .navbar {
            background: rgba(18, 18, 18, 0.95) !important;
            backdrop-filter: blur(10px);
            padding: 15px 0;
            box-shadow: 0 2px 20px rgba(0,0,0,0.3);
        }
        
        .navbar-brand {
            font-size: 1.5rem;
            font-weight: bold;
            color: white !important;
        }
        
        .booking-container {
            max-width: 1000px;
            margin: 100px auto 50px;
            padding: 20px;
        }
        
        .booking-card {
            background: white;
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 5px 30px rgba(0,0,0,0.1);
        }
        
        .booking-title {
            text-align: center;
            margin-bottom: 40px;
            color: var(--dark);
        }
        
        .booking-title i {
            font-size: 3rem;
            color: var(--primary);
            margin-bottom: 15px;
        }
        
        .form-label {
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 8px;
        }
        
        .form-control, .form-select {
            border-radius: 10px;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            transition: all 0.3s;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 0.25rem rgba(255, 87, 34, 0.25);
        }
        
        .btn-book {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            padding: 15px 40px;
            border-radius: 50px;
            font-weight: bold;
            border: none;
            font-size: 1.1rem;
            transition: all 0.3s;
            width: 100%;
        }
        
        .btn-book:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(255, 87, 34, 0.4);
            color: white;
        }
        
        .car-preview {
            background: #f8f9fa;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 30px;
        }
        
        .car-preview h5 {
            color: var(--primary);
            font-weight: bold;
        }
        
        .price-info {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            padding: 20px;
            border-radius: 15px;
            text-align: center;
            margin-bottom: 20px;
        }
        
        .price-info h3 {
            margin: 0;
            font-size: 2.5rem;
        }
        
        .alert {
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 30px;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark fixed-top">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-car"></i> <?php echo SITE_NAME; ?>
            </a>
            <div class="ms-auto">
                <a href="index.php" class="btn btn-outline-light">
                    <i class="fas fa-home me-2"></i>الرئيسية
                </a>
            </div>
        </div>
    </nav>

    <div class="booking-container">
        <div class="booking-card">
            <div class="booking-title">
                <i class="fas fa-calendar-check"></i>
                <h2>احجز سيارتك الآن</h2>
                <p class="text-muted">املأ البيانات وسنتواصل معك فوراً</p>
            </div>

            <?php if ($success): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle me-2"></i>
                <?php echo $success; ?>
            </div>
            <?php endif; ?>

            <?php if ($error): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle me-2"></i>
                <?php echo $error; ?>
            </div>
            <?php endif; ?>

            <?php if (!$success): ?>
            
            <?php if ($car): ?>
            <div class="car-preview">
                <h5>
                    <i class="fas fa-car me-2"></i>
                    السيارة المختارة: <?php echo $car['brand'] . ' ' . $car['model'] . ' ' . $car['year']; ?>
                </h5>
                <div class="price-info mt-3">
                    <h3><?php echo formatCurrency($car['daily_rate']); ?></h3>
                    <small>لليوم الواحد</small>
                </div>
            </div>
            <?php endif; ?>

            <form method="POST" action="" id="bookingForm">
                <div class="row g-3">
                    <div class="col-md-12">
                        <label class="form-label">
                            <i class="fas fa-car text-primary me-2"></i>
                            اختر السيارة *
                        </label>
                        <select name="car_id" class="form-select" required onchange="updatePrice(this)">
                            <option value="">-- اختر السيارة --</option>
                            <?php foreach ($cars as $c): ?>
                            <option value="<?php echo $c['id']; ?>" 
                                    data-price="<?php echo $c['daily_rate']; ?>"
                                    <?php echo ($car && $car['id'] == $c['id']) ? 'selected' : ''; ?>>
                                <?php echo $c['brand'] . ' ' . $c['model'] . ' ' . $c['year']; ?>
                                - <?php echo formatCurrency($c['daily_rate']); ?>/يوم
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">
                            <i class="fas fa-user text-primary me-2"></i>
                            الاسم الكامل *
                        </label>
                        <input type="text" name="customer_name" class="form-control" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">
                            <i class="fas fa-id-card text-primary me-2"></i>
                            رقم الهوية *
                        </label>
                        <input type="text" name="id_number" class="form-control" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">
                            <i class="fas fa-phone text-primary me-2"></i>
                            رقم الهاتف *
                        </label>
                        <input type="tel" name="customer_phone" class="form-control" 
                               placeholder="+970599123456" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">
                            <i class="fas fa-envelope text-primary me-2"></i>
                            البريد الإلكتروني
                        </label>
                        <input type="email" name="customer_email" class="form-control">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">
                            <i class="fas fa-calendar-day text-primary me-2"></i>
                            تاريخ الاستلام *
                        </label>
                        <input type="date" name="start_date" id="start_date" class="form-control" 
                               min="<?php echo date('Y-m-d'); ?>" required onchange="calculateDays()">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">
                            <i class="fas fa-calendar-check text-primary me-2"></i>
                            تاريخ التسليم *
                        </label>
                        <input type="date" name="end_date" id="end_date" class="form-control" 
                               min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>" required onchange="calculateDays()">
                    </div>

                    <div class="col-md-12" id="daysDisplay" style="display: none;">
                        <div class="alert alert-info">
                            <strong>
                                <i class="fas fa-info-circle me-2"></i>
                                عدد الأيام: <span id="totalDays">0</span> يوم
                                | الإجمالي التقريبي: <span id="totalPrice">0</span>₪
                            </strong>
                        </div>
                    </div>

                    <div class="col-md-12">
                        <label class="form-label">
                            <i class="fas fa-comment text-primary me-2"></i>
                            ملاحظات إضافية
                        </label>
                        <textarea name="notes" class="form-control" rows="3" 
                                  placeholder="أي ملاحظات أو طلبات خاصة..."></textarea>
                    </div>

                    <div class="col-md-12">
                        <div class="alert alert-warning">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>ملاحظة:</strong> هذا طلب حجز أولي. سيتم التواصل معك لتأكيد الحجز وإتمام الإجراءات.
                        </div>
                    </div>

                    <div class="col-md-12">
                        <button type="submit" class="btn btn-book">
                            <i class="fas fa-paper-plane me-2"></i>
                            إرسال طلب الحجز
                        </button>
                    </div>
                </div>
            </form>
            
            <?php else: ?>
            <div class="text-center">
                <a href="index.php" class="btn btn-book">
                    <i class="fas fa-home me-2"></i>
                    العودة للرئيسية
                </a>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function updatePrice(select) {
            const selectedOption = select.options[select.selectedIndex];
            const price = selectedOption.getAttribute('data-price');
            calculateDays();
        }

        function calculateDays() {
            const startDate = document.getElementById('start_date').value;
            const endDate = document.getElementById('end_date').value;
            const carSelect = document.querySelector('select[name="car_id"]');
            const selectedOption = carSelect.options[carSelect.selectedIndex];
            const dailyRate = parseFloat(selectedOption.getAttribute('data-price') || 0);

            if (startDate && endDate && dailyRate > 0) {
                const start = new Date(startDate);
                const end = new Date(endDate);
                const diffTime = Math.abs(end - start);
                const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24)) + 1;

                if (diffDays > 0) {
                    const totalPrice = diffDays * dailyRate;
                    document.getElementById('totalDays').textContent = diffDays;
                    document.getElementById('totalPrice').textContent = totalPrice.toFixed(2);
                    document.getElementById('daysDisplay').style.display = 'block';
                }
            }
        }

        // Set minimum end date when start date changes
        document.getElementById('start_date').addEventListener('change', function() {
            const startDate = new Date(this.value);
            startDate.setDate(startDate.getDate() + 1);
            const minEndDate = startDate.toISOString().split('T')[0];
            document.getElementById('end_date').setAttribute('min', minEndDate);
        });
    </script>
</body>
</html>