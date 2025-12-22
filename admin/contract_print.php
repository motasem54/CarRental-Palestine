<?php
require_once '../config/settings.php';
require_once '../core/Auth.php';

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    redirect(ADMIN_URL . '/login.php');
}

$db = Database::getInstance()->getConnection();

if (!isset($_GET['id'])) {
    redirect('rentals.php');
}

$rental_id = (int)$_GET['id'];

// Get rental details
$stmt = $db->prepare("
    SELECT r.*, 
           c.full_name as customer_name, c.phone as customer_phone, 
           c.address as customer_address, c.id_number, c.driver_license,
           car.brand, car.model, car.year, car.color, car.plate_number,
           car.type as car_type, car.seats,
           u.full_name as created_by_name
    FROM rentals r
    JOIN customers c ON r.customer_id = c.id
    JOIN cars car ON r.car_id = car.id
    LEFT JOIN users u ON r.created_by = u.id
    WHERE r.id = ?
");
$stmt->execute([$rental_id]);
$rental = $stmt->fetch();

if (!$rental) {
    redirect('rentals.php');
}

// Get payments
$stmt = $db->prepare("
    SELECT * FROM payments 
    WHERE rental_id = ? 
    ORDER BY payment_date
");
$stmt->execute([$rental_id]);
$payments = $stmt->fetchAll();

$page_title = 'عقد إيجار رقم ' . $rental['rental_number'];
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap" rel="stylesheet">
    
    <style>
        * { font-family: 'Cairo', sans-serif; }
        body { background: white; }
        
        @media print {
            .no-print { display: none !important; }
            body { margin: 0; padding: 20px; }
        }
        
        .contract-container {
            max-width: 900px;
            margin: 30px auto;
            background: white;
            padding: 40px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        
        .contract-header {
            text-align: center;
            border-bottom: 3px solid #FF5722;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        
        .contract-header h1 {
            color: #FF5722;
            font-weight: 700;
            margin-bottom: 10px;
        }
        
        .contract-number {
            background: #f8f9fa;
            padding: 15px;
            border-right: 4px solid #FF5722;
            margin-bottom: 30px;
        }
        
        .section-title {
            background: #FF5722;
            color: white;
            padding: 10px 15px;
            margin: 25px 0 15px 0;
            font-weight: 600;
        }
        
        .info-table {
            width: 100%;
            margin-bottom: 20px;
        }
        
        .info-table td {
            padding: 10px;
            border-bottom: 1px solid #eee;
        }
        
        .info-table td:first-child {
            font-weight: 600;
            width: 30%;
            color: #555;
        }
        
        .terms-list {
            list-style: arabic-indic;
            padding-right: 25px;
        }
        
        .terms-list li {
            margin-bottom: 10px;
            line-height: 1.8;
        }
        
        .signature-section {
            margin-top: 50px;
            display: flex;
            justify-content: space-between;
        }
        
        .signature-box {
            width: 45%;
            text-align: center;
        }
        
        .signature-line {
            border-top: 2px solid #000;
            margin-top: 60px;
            padding-top: 10px;
        }
        
        .watermark {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-45deg);
            font-size: 120px;
            color: rgba(255, 87, 34, 0.05);
            font-weight: 700;
            z-index: -1;
            pointer-events: none;
        }
        
        .total-box {
            background: #f8f9fa;
            border: 2px solid #FF5722;
            padding: 20px;
            margin: 20px 0;
        }
        
        .total-box h4 {
            color: #FF5722;
            margin: 0;
        }
    </style>
</head>
<body>
    <div class="watermark">نظام تأجير سيارات</div>
    
    <!-- Print Button -->
    <div class="text-center mb-3 no-print">
        <button onclick="window.print()" class="btn btn-primary btn-lg">
            <i class="fas fa-print"></i> طباعة
        </button>
        <a href="rentals.php" class="btn btn-secondary btn-lg">رجوع</a>
    </div>
    
    <div class="contract-container">
        <!-- Header -->
        <div class="contract-header">
            <h1>🚗 عقد إيجار سيارة</h1>
            <h5><?php echo COMPANY_NAME; ?></h5>
            <p class="mb-0">هاتف: <?php echo COMPANY_PHONE; ?> | بريد: <?php echo COMPANY_EMAIL; ?></p>
            <p class="mb-0">🇵🇸 فلسطين</p>
        </div>
        
        <!-- Contract Number -->
        <div class="contract-number">
            <strong>رقم العقد:</strong> <?php echo $rental['rental_number']; ?>
            &nbsp;&nbsp;|&nbsp;&nbsp;
            <strong>التاريخ:</strong> <?php echo formatDate($rental['created_at']); ?>
        </div>
        
        <!-- Customer Information -->
        <div class="section-title">بيانات المستأجر (الطرف الأول)</div>
        <table class="info-table">
            <tr>
                <td>الاسم الكامل:</td>
                <td><?php echo htmlspecialchars($rental['customer_name']); ?></td>
            </tr>
            <tr>
                <td>رقم الهوية:</td>
                <td><?php echo $rental['id_number']; ?></td>
            </tr>
            <tr>
                <td>رقم الهاتف:</td>
                <td><?php echo $rental['customer_phone']; ?></td>
            </tr>
            <tr>
                <td>العنوان:</td>
                <td><?php echo htmlspecialchars($rental['customer_address']); ?></td>
            </tr>
            <tr>
                <td>رخصة القيادة:</td>
                <td><?php echo $rental['driver_license']; ?></td>
            </tr>
        </table>
        
        <!-- Car Information -->
        <div class="section-title">بيانات السيارة</div>
        <table class="info-table">
            <tr>
                <td>نوع السيارة:</td>
                <td><?php echo $rental['brand'] . ' ' . $rental['model'] . ' (' . $rental['year'] . ')'; ?></td>
            </tr>
            <tr>
                <td>رقم اللوحة:</td>
                <td><strong><?php echo $rental['plate_number']; ?></strong></td>
            </tr>
            <tr>
                <td>اللون:</td>
                <td><?php echo $rental['color']; ?></td>
            </tr>
            <tr>
                <td>عدد المقاعد:</td>
                <td><?php echo $rental['seats']; ?> مقعد</td>
            </tr>
        </table>
        
        <!-- Rental Details -->
        <div class="section-title">تفاصيل الإيجار</div>
        <table class="info-table">
            <tr>
                <td>تاريخ بدء الإيجار:</td>
                <td><?php echo formatDate($rental['start_date']); ?></td>
            </tr>
            <tr>
                <td>تاريخ انتهاء الإيجار:</td>
                <td><?php echo formatDate($rental['end_date']); ?></td>
            </tr>
            <tr>
                <td>مدة الإيجار:</td>
                <td><?php echo $rental['total_days']; ?> يوم</td>
            </tr>
            <tr>
                <td>قيمة التأمين:</td>
                <td><?php echo formatCurrency($rental['insurance_amount']); ?></td>
            </tr>
        </table>
        
        <!-- Financial Details -->
        <div class="section-title">التفاصيل المالية</div>
        <table class="info-table">
            <tr>
                <td>المبلغ الأساسي:</td>
                <td><?php echo formatCurrency($rental['base_amount']); ?></td>
            </tr>
            <?php if ($rental['discount_amount'] > 0): ?>
            <tr>
                <td>الخصم:</td>
                <td>-<?php echo formatCurrency($rental['discount_amount']); ?></td>
            </tr>
            <?php endif; ?>
            <tr>
                <td>الضريبة (<?php echo TAX_RATE * 100; ?>%):</td>
                <td><?php echo formatCurrency($rental['tax_amount']); ?></td>
            </tr>
        </table>
        
        <div class="total-box text-center">
            <h4>المبلغ الإجمالي: <?php echo formatCurrency($rental['total_amount']); ?></h4>
        </div>
        
        <!-- Terms and Conditions -->
        <div class="section-title">الشروط والأحكام</div>
        <ol class="terms-list">
            <li>يتعهد المستأجر بالمحافظة على السيارة وعدم استخدامها في أغراض غير قانونية.</li>
            <li>يتم دفع غرامة في حالة التأخير عن الموعد المحدد بمعدل <?php echo formatCurrency(LATE_RETURN_FEE); ?> عن كل يوم تأخير.</li>
            <li>المستأجر مسؤول عن أي ضرر يلحق بالسيارة خلال فترة الإيجار.</li>
            <li>يجب إعادة السيارة بنفس الحالة التي استلمت بها، بما في ذلك مستوى الوقود.</li>
            <li>لا يحق للمستأجر تأجير السيارة من الباطن لأي طرف ثالث.</li>
            <li>في حالة وجود عطل فني في السيارة، يجب إبلاغ الشركة فوراً.</li>
            <li>تم استلام مبلغ التأمين وسيتم إرجاعه عند تسليم السيارة بحالة جيدة.</li>
            <li>يحق للشركة إنهاء العقد في حالة مخالفة أي من الشروط المذكورة.</li>
        </ol>
        
        <!-- Signatures -->
        <div class="signature-section">
            <div class="signature-box">
                <div class="signature-line">
                    <strong>توقيع المستأجر</strong><br>
                    <?php echo htmlspecialchars($rental['customer_name']); ?>
                </div>
            </div>
            <div class="signature-box">
                <div class="signature-line">
                    <strong>توقيع الشركة</strong><br>
                    <?php echo COMPANY_NAME; ?>
                </div>
            </div>
        </div>
        
        <div class="text-center mt-5" style="color: #999; font-size: 0.9rem;">
            <p>هذا العقد صادر إلكترونياً من نظام تأجير السيارات</p>
            <p>🇵🇸 Made with ❤️ in Palestine</p>
        </div>
    </div>
    
    <script>
        // Auto print on load (optional)
        // window.onload = function() { window.print(); }
    </script>
</body>
</html>