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
           car.type as car_type, car.seats, car.transmission,
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

// Get contract type
$stmt = $db->prepare("SELECT contract_type, has_promissory_note FROM rental_contracts WHERE rental_id = ? LIMIT 1");
$stmt->execute([$rental_id]);
$contractRecord = $stmt->fetch();
$with_promissory = ($contractRecord['has_promissory_note'] ?? 0) == 1;

// Safe values
$rental['base_amount'] = $rental['base_amount'] ?? ($rental['total_amount'] - ($rental['insurance_amount'] ?? 0));
$rental['insurance_amount'] = $rental['insurance_amount'] ?? 0;
$rental['discount_amount'] = $rental['discount_amount'] ?? 0;
$rental['paid_amount'] = $rental['paid_amount'] ?? 0;
$rental['mileage_start'] = $rental['mileage_start'] ?? 0;

$remaining_amount = $rental['total_amount'] - $rental['paid_amount'];
$page_title = 'عقد إيجار رقم ' . $rental['rental_number'];
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;900&display=swap" rel="stylesheet">
    <style>
        @page {
            size: A4;
            margin: 8mm;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Cairo', sans-serif;
        }
        
        body {
            background: #f0f2f5;
            padding: 10px;
        }
        
        @media print {
            body { background: white; padding: 0; }
            .no-print { display: none !important; }
            .page-break { page-break-before: always; }
            .avoid-break { page-break-inside: avoid; }
        }
        
        .contract {
            background: white;
            width: 210mm;
            margin: 0 auto;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        
        /* ===== HEADER ===== */
        .header {
            background: linear-gradient(135deg, #1a237e 0%, #0d47a1 100%);
            color: white;
            padding: 15px 20px;
            display: grid;
            grid-template-columns: auto 1fr auto;
            gap: 15px;
            align-items: center;
            border-bottom: 4px solid #ff6f00;
        }
        
        .logo-circle {
            width: 55px;
            height: 55px;
            background: linear-gradient(135deg, #ff6f00, #ff9100);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            font-weight: 900;
            box-shadow: 0 4px 12px rgba(255, 111, 0, 0.4);
        }
        
        .header-info h1 {
            font-size: 22px;
            font-weight: 900;
            margin-bottom: 3px;
        }
        
        .header-info .company {
            font-size: 13px;
            color: #ffeb3b;
            font-weight: 700;
        }
        
        .header-info .details {
            font-size: 10px;
            margin-top: 5px;
            line-height: 1.5;
            color: #eceff1;
        }
        
        .stamp {
            border: 3px solid #ff6f00;
            border-radius: 50%;
            width: 65px;
            height: 65px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 9px;
            font-weight: 900;
            color: #ff6f00;
            transform: rotate(-15deg);
            background: rgba(255, 255, 255, 0.1);
            text-align: center;
            line-height: 1.2;
        }
        
        /* ===== CONTRACT BAR ===== */
        .contract-bar {
            background: linear-gradient(90deg, #ff6f00, #ffb74d);
            color: white;
            padding: 10px 15px;
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 20px;
            font-weight: 700;
            font-size: 12px;
        }
        
        /* ===== SECTION HEADER ===== */
        .section-header {
            background: linear-gradient(90deg, #1a237e, #283593);
            color: white;
            padding: 8px 12px;
            font-weight: 900;
            font-size: 12px;
            border-right: 4px solid #ff6f00;
            margin: 12px 0 8px 0;
        }
        
        /* ===== TABLES ===== */
        .info-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
            font-size: 11px;
        }
        
        .info-table td {
            padding: 8px;
            border: 1px solid #ddd;
        }
        
        .info-table td:first-child {
            background: #f5f5f5;
            font-weight: 700;
            color: #1a237e;
            width: 25%;
        }
        
        .info-table tr:nth-child(even) td:first-child {
            background: #ede7f6;
        }
        
        /* ===== FINANCIAL TABLE ===== */
        .financial-table {
            width: 100%;
            border-collapse: collapse;
            margin: 12px 0;
            font-size: 11px;
        }
        
        .financial-table td {
            padding: 10px;
            border: 2px solid #1a237e;
        }
        
        .financial-table td:first-child {
            background: #ede7f6;
            font-weight: 700;
            width: 50%;
            text-align: right;
        }
        
        .financial-table td:last-child {
            background: #f5f5f5;
            text-align: center;
            font-weight: 700;
            font-size: 12px;
        }
        
        .financial-table tr:last-child td {
            background: linear-gradient(90deg, #1a237e, #283593);
            color: white;
            font-size: 13px;
            font-weight: 900;
        }
        
        /* ===== CAR INSPECTION ===== */
        .inspection-section {
            margin-top: 15px;
            padding: 12px;
            background: #f9f9f9;
            border: 2px solid #1a237e;
            border-radius: 6px;
        }
        
        .inspection-header {
            background: linear-gradient(90deg, #1a237e, #283593);
            color: white;
            padding: 10px;
            font-weight: 900;
            font-size: 12px;
            text-align: center;
            border-radius: 4px;
            margin-bottom: 12px;
        }
        
        .car-views {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
            margin-bottom: 12px;
        }
        
        .car-view {
            background: white;
            border: 2px solid #1a237e;
            border-radius: 6px;
            padding: 10px;
            page-break-inside: avoid;
        }
        
        .view-title {
            background: linear-gradient(90deg, #1a237e, #283593);
            color: white;
            padding: 6px;
            font-weight: 700;
            font-size: 11px;
            text-align: center;
            border-radius: 3px;
            margin-bottom: 8px;
        }
        
        .car-diagram {
            position: relative;
            width: 100%;
            height: 160px;
            background: white;
            border: 2px solid #1a237e;
            border-radius: 4px;
            margin-bottom: 8px;
        }
        
        .drawing-canvas {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            cursor: crosshair;
            z-index: 10;
        }
        
        .view-notes {
            font-size: 9px;
            color: #666;
            background: #fff3e0;
            padding: 6px;
            border-radius: 3px;
            text-align: center;
        }
        
        /* ===== SEDAN CAR SVG ===== */
        .sedan-front {
            width: 100%;
            height: 100%;
        }
        
        /* ===== SIGNATURES ===== */
        .signature-section {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin: 20px 0;
            padding: 0 12px;
            page-break-inside: avoid;
        }
        
        .signature-box {
            text-align: center;
        }
        
        .signature-canvas {
            border: 2px dashed #1a237e;
            background: white;
            border-radius: 4px;
            cursor: crosshair;
            width: 100%;
            height: 100px;
            margin-bottom: 10px;
        }
        
        .signature-line {
            border-top: 2px solid #1a237e;
            padding-top: 8px;
            font-size: 11px;
        }
        
        .signature-line strong {
            color: #ff6f00;
            display: block;
            margin-bottom: 3px;
        }
        
        /* ===== TERMS ===== */
        .terms-section {
            padding: 12px;
            background: #ede7f6;
            margin: 12px 0;
            border-right: 4px solid #ff6f00;
            font-size: 10px;
            line-height: 1.7;
            page-break-inside: avoid;
        }
        
        .terms-section h4 {
            color: #1a237e;
            margin-bottom: 8px;
            font-size: 11px;
        }
        
        /* ===== PROMISSORY NOTE ===== */
        .promissory-note {
            border: 4px double #1a237e;
            margin: 20px;
            padding: 25px;
            background: linear-gradient(135deg, #fff 0%, #f8f9fa 100%);
            border-radius: 8px;
            position: relative;
            page-break-inside: avoid;
        }
        
        .promissory-header {
            text-align: center;
            border-bottom: 3px solid #ff6f00;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }
        
        .promissory-header h2 {
            font-size: 24px;
            color: #1a237e;
            font-weight: 900;
            margin-bottom: 5px;
        }
        
        .promissory-header .subtitle {
            font-size: 13px;
            color: #666;
            font-weight: 600;
        }
        
        .promissory-body {
            line-height: 2.2;
            font-size: 12px;
            text-align: center;
        }
        
        .promissory-body .debtor-name {
            font-size: 16px;
            font-weight: 900;
            color: #1a237e;
            display: block;
            margin: 10px 0;
        }
        
        .amount-box {
            background: linear-gradient(135deg, #fff3e0, #ffe0b2);
            border: 3px solid #ff6f00;
            padding: 20px;
            margin: 20px 0;
            border-radius: 8px;
            text-align: center;
        }
        
        .amount-box .label {
            font-size: 11px;
            color: #666;
            margin-bottom: 8px;
        }
        
        .amount-box .amount {
            font-size: 28px;
            font-weight: 900;
            color: #ff6f00;
            margin: 8px 0;
        }
        
        .amount-box .words {
            font-size: 11px;
            color: #666;
            font-style: italic;
        }
        
        .promissory-details {
            margin: 15px 0;
            font-size: 12px;
        }
        
        .promissory-details strong {
            color: #1a237e;
            font-weight: 900;
        }
        
        .promissory-signature {
            margin-top: 30px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
        }
        
        /* ===== FOOTER ===== */
        .footer {
            text-align: center;
            padding: 12px;
            border-top: 3px solid #1a237e;
            font-size: 9px;
            color: #666;
            background: #f5f5f5;
            margin-top: 15px;
        }
        
        /* ===== BUTTONS ===== */
        .actions {
            position: fixed;
            bottom: 20px;
            left: 20px;
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            z-index: 1000;
        }
        
        .btn {
            background: linear-gradient(135deg, #1a237e, #0d47a1);
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 25px;
            font-weight: 700;
            cursor: pointer;
            box-shadow: 0 4px 15px rgba(26, 35, 126, 0.3);
            font-size: 11px;
            transition: all 0.3s;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(26, 35, 126, 0.4);
        }
        
        .btn-orange { background: linear-gradient(135deg, #ff6f00, #ff9100); }
        .btn-green { background: linear-gradient(135deg, #00695c, #004d40); }
        .btn-back {
            background: linear-gradient(135deg, #455a64, #37474f);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
        }
        
        @media (max-width: 768px) {
            .contract { width: 100%; }
            .car-views { grid-template-columns: 1fr; }
            .signature-section { grid-template-columns: 1fr; }
            .actions { position: static; justify-content: center; margin: 20px 0; }
        }
    </style>
</head>
<body>
    <!-- BUTTONS -->
    <div class="actions no-print">
        <button class="btn btn-orange" onclick="window.print()">🖨️ طباعة</button>
        <button class="btn btn-green" onclick="exportPDF()">📥 تحميل PDF</button>
        <button class="btn" onclick="clearDrawings()">🔄 مسح الرسم</button>
        <a href="rentals.php" class="btn btn-back">← عودة</a>
    </div>
    
    <div class="contract" id="contract">
        <!-- HEADER -->
        <div class="header">
            <div class="logo-box">
                <div class="logo-circle">🚗</div>
            </div>
            <div class="header-info">
                <h1>عقد إيجار سيارة</h1>
                <div class="company"><?php echo COMPANY_NAME; ?></div>
                <div class="details">
                    📞 <?php echo COMPANY_PHONE; ?> | 
                    📧 <?php echo COMPANY_EMAIL; ?><br>
                    📍 <?php echo COMPANY_ADDRESS; ?> | 🇵🇸
                </div>
            </div>
            <div class="header-stamp">
                <div class="stamp">عقد<br>رسمي</div>
            </div>
        </div>
        
        <!-- CONTRACT BAR -->
        <div class="contract-bar">
            <div>رقم العقد: <strong><?php echo $rental['rental_number']; ?></strong></div>
            <div>التاريخ: <strong><?php echo formatDate($rental['created_at']); ?></strong></div>
            <div>النوع: <strong><?php echo $with_promissory ? '✅ مع كمبيالة' : '📋 بسيط'; ?></strong></div>
        </div>
        
        <!-- CUSTOMER DATA -->
        <div class="section-header">👤 بيانات المستأجر</div>
        <table class="info-table">
            <tr>
                <td>الاسم الكامل</td>
                <td><strong><?php echo htmlspecialchars($rental['customer_name']); ?></strong></td>
                <td>رقم الهوية</td>
                <td><strong><?php echo $rental['id_number']; ?></strong></td>
            </tr>
            <tr>
                <td>الجنسية</td>
                <td>فلسطيني</td>
                <td>الهاتف</td>
                <td><strong><?php echo $rental['customer_phone']; ?></strong></td>
            </tr>
            <tr>
                <td>مكان الإقامة</td>
                <td colspan="3"><?php echo htmlspecialchars($rental['customer_address']); ?></td>
            </tr>
        </table>
        
        <!-- CAR DATA -->
        <div class="section-header">🚙 بيانات السيارة</div>
        <table class="info-table">
            <tr>
                <td>نوع السيارة</td>
                <td><strong><?php echo $rental['brand'] . ' ' . $rental['model'] . ' (' . $rental['year'] . ')'; ?></strong></td>
                <td>اللون</td>
                <td><?php echo $rental['color']; ?></td>
            </tr>
            <tr>
                <td>رقم اللوحة</td>
                <td><strong style="color:#ff6f00; font-size:13px;"><?php echo $rental['plate_number']; ?></strong></td>
                <td>النوع</td>
                <td><?php echo $rental['car_type']; ?></td>
            </tr>
            <tr>
                <td>عدد المقاعد</td>
                <td><?php echo $rental['seats']; ?></td>
                <td>ناقل الحركة</td>
                <td><?php echo $rental['transmission'] ?? 'أوتوماتيك'; ?></td>
            </tr>
            <tr>
                <td>⚙️ عداد الكيلومتر عند الاستلام</td>
                <td colspan="3"><strong style="color:#ff6f00; font-size:14px;"><?php echo number_format($rental['mileage_start']); ?> كم</strong></td>
            </tr>
        </table>
        
        <!-- RENTAL PERIOD -->
        <div class="section-header">📅 فترة الإيجار</div>
        <table class="info-table">
            <tr>
                <td>تاريخ الاستلام</td>
                <td><?php echo formatDate($rental['start_date']); ?></td>
                <td>⏰ الوقت</td>
                <td><strong style="color:#ff6f00;"><?php echo date('h:i A', strtotime($rental['start_date'])); ?></strong></td>
            </tr>
            <tr>
                <td>تاريخ التسليم</td>
                <td><?php echo formatDate($rental['end_date']); ?></td>
                <td>⏰ الوقت</td>
                <td><strong style="color:#ff6f00;"><?php echo date('h:i A', strtotime($rental['end_date'])); ?></strong></td>
            </tr>
            <tr>
                <td>عدد الأيام</td>
                <td colspan="3"><strong><?php echo $rental['total_days']; ?> يوم</strong></td>
            </tr>
        </table>
        
        <!-- FINANCIAL -->
        <div class="section-header">💰 التفاصيل المالية</div>
        <table class="financial-table">
            <tr>
                <td>السعر الأساسي لليوم الواحد</td>
                <td><?php echo formatCurrency($rental['base_amount'] / $rental['total_days']); ?></td>
            </tr>
            <tr>
                <td>المبلغ الأساسي (<?php echo $rental['total_days']; ?> يوم)</td>
                <td><?php echo formatCurrency($rental['base_amount']); ?></td>
            </tr>
            <?php if ($rental['discount_amount'] > 0): ?>
            <tr>
                <td>الخصم</td>
                <td style="color: #4caf50;">-<?php echo formatCurrency($rental['discount_amount']); ?></td>
            </tr>
            <?php endif; ?>
            <tr>
                <td>رسوم التأمين</td>
                <td><?php echo formatCurrency($rental['insurance_amount']); ?></td>
            </tr>
            <tr>
                <td>المبلغ الإجمالي</td>
                <td><?php echo formatCurrency($rental['total_amount']); ?></td>
            </tr>
        </table>
        
        <!-- CAR INSPECTION -->
        <div class="page-break"></div>
        
        <div class="inspection-section avoid-break">
            <div class="inspection-header">🔍 نموذج فحص حالة السيارة - قم بالتعليم على الرسومات</div>
            
            <div class="car-views">
                <!-- FRONT VIEW -->
                <div class="car-view">
                    <div class="view-title">🔴 الجهة الأمامية</div>
                    <div class="car-diagram">
                        <svg class="sedan-front" viewBox="0 0 300 200" xmlns="http://www.w3.org/2000/svg">
                            <!-- Body -->
                            <rect x="50" y="70" width="200" height="80" fill="#ddd" stroke="#333" stroke-width="3" rx="8"/>
                            <!-- Windshield -->
                            <polygon points="100,70 200,70 180,50 120,50" fill="#87ceeb" stroke="#333" stroke-width="2"/>
                            <!-- Hood ornament -->
                            <rect x="140" y="35" width="20" height="15" fill="#555" stroke="#333" stroke-width="2" rx="2"/>
                            <!-- Headlights -->
                            <ellipse cx="80" cy="85" rx="18" ry="12" fill="#ffffcc" stroke="#333" stroke-width="2"/>
                            <ellipse cx="220" cy="85" rx="18" ry="12" fill="#ffffcc" stroke="#333" stroke-width="2"/>
                            <!-- Grille -->
                            <rect x="120" y="80" width="60" height="25" fill="#333" stroke="#333" stroke-width="2" rx="3"/>
                            <line x1="125" y1="85" x2="175" y2="85" stroke="#666" stroke-width="1"/>
                            <line x1="125" y1="92" x2="175" y2="92" stroke="#666" stroke-width="1"/>
                            <line x1="125" y1="99" x2="175" y2="99" stroke="#666" stroke-width="1"/>
                            <!-- Bumper -->
                            <rect x="60" y="110" width="180" height="15" fill="#888" stroke="#333" stroke-width="2" rx="4"/>
                            <!-- License Plate -->
                            <rect x="130" y="112" width="40" height="10" fill="#fff" stroke="#333" stroke-width="1"/>
                            <!-- Wheels -->
                            <circle cx="90" cy="145" r="25" fill="#333" stroke="#222" stroke-width="3"/>
                            <circle cx="90" cy="145" r="15" fill="#444" stroke="#555" stroke-width="2"/>
                            <circle cx="210" cy="145" r="25" fill="#333" stroke="#222" stroke-width="3"/>
                            <circle cx="210" cy="145" r="15" fill="#444" stroke="#555" stroke-width="2"/>
                        </svg>
                        <canvas id="carFront" class="drawing-canvas"></canvas>
                    </div>
                    <div class="view-notes">📝 اخترق على أي خدوش أو تجنيشات في الأمام</div>
                </div>
                
                <!-- BACK VIEW -->
                <div class="car-view">
                    <div class="view-title">🟡 الجهة الخلفية</div>
                    <div class="car-diagram">
                        <svg class="sedan-front" viewBox="0 0 300 200" xmlns="http://www.w3.org/2000/svg">
                            <!-- Body -->
                            <rect x="50" y="70" width="200" height="80" fill="#ddd" stroke="#333" stroke-width="3" rx="8"/>
                            <!-- Rear Window -->
                            <polygon points="100,70 200,70 180,50 120,50" fill="#87ceeb" stroke="#333" stroke-width="2"/>
                            <!-- Tail Lights -->
                            <rect x="60" y="80" width="30" height="20" fill="#ff6b6b" stroke="#333" stroke-width="2" rx="3"/>
                            <rect x="210" y="80" width="30" height="20" fill="#ff6b6b" stroke="#333" stroke-width="2" rx="3"/>
                            <!-- Trunk -->
                            <rect x="100" y="105" width="100" height="8" fill="#bbb" stroke="#333" stroke-width="1"/>
                            <!-- Bumper -->
                            <rect x="60" y="110" width="180" height="15" fill="#888" stroke="#333" stroke-width="2" rx="4"/>
                            <!-- License Plate -->
                            <rect x="130" y="112" width="40" height="10" fill="#fff" stroke="#333" stroke-width="1"/>
                            <!-- Exhaust -->
                            <rect x="200" y="120" width="15" height="8" fill="#444" stroke="#333" stroke-width="1" rx="2"/>
                            <!-- Wheels -->
                            <circle cx="90" cy="145" r="25" fill="#333" stroke="#222" stroke-width="3"/>
                            <circle cx="90" cy="145" r="15" fill="#444" stroke="#555" stroke-width="2"/>
                            <circle cx="210" cy="145" r="25" fill="#333" stroke="#222" stroke-width="3"/>
                            <circle cx="210" cy="145" r="15" fill="#444" stroke="#555" stroke-width="2"/>
                        </svg>
                        <canvas id="carBack" class="drawing-canvas"></canvas>
                    </div>
                    <div class="view-notes">📝 اخترق على أي أضرار في الخلف</div>
                </div>
                
                <!-- LEFT SIDE VIEW -->
                <div class="car-view">
                    <div class="view-title">🟢 الجانب الأيسر</div>
                    <div class="car-diagram">
                        <svg class="sedan-front" viewBox="0 0 300 200" xmlns="http://www.w3.org/2000/svg">
                            <!-- Body -->
                            <ellipse cx="150" cy="110" rx="110" ry="40" fill="#ddd" stroke="#333" stroke-width="3"/>
                            <!-- Roof -->
                            <rect x="80" y="75" width="140" height="30" fill="#bbb" stroke="#333" stroke-width="2" rx="5"/>
                            <!-- Windows -->
                            <rect x="90" y="78" width="50" height="22" fill="#87ceeb" stroke="#333" stroke-width="1.5" rx="2"/>
                            <rect x="145" y="78" width="50" height="22" fill="#87ceeb" stroke="#333" stroke-width="1.5" rx="2"/>
                            <!-- Door Handle -->
                            <rect x="135" y="110" width="15" height="4" fill="#555" stroke="#333" stroke-width="1" rx="1"/>
                            <!-- Side Mirror -->
                            <ellipse cx="65" cy="95" rx="8" ry="12" fill="#888" stroke="#333" stroke-width="2"/>
                            <!-- Front Light -->
                            <ellipse cx="245" cy="105" rx="10" ry="15" fill="#ffeb3b" stroke="#333" stroke-width="2"/>
                            <!-- Rear Light -->
                            <ellipse cx="55" cy="105" rx="10" ry="15" fill="#ff6b6b" stroke="#333" stroke-width="2"/>
                            <!-- Bottom Line -->
                            <line x1="40" y1="135" x2="260" y2="135" stroke="#333" stroke-width="2"/>
                            <!-- Wheels -->
                            <circle cx="80" cy="140" r="22" fill="#333" stroke="#222" stroke-width="3"/>
                            <circle cx="80" cy="140" r="12" fill="#444" stroke="#555" stroke-width="2"/>
                            <circle cx="220" cy="140" r="22" fill="#333" stroke="#222" stroke-width="3"/>
                            <circle cx="220" cy="140" r="12" fill="#444" stroke="#555" stroke-width="2"/>
                        </svg>
                        <canvas id="carLeft" class="drawing-canvas"></canvas>
                    </div>
                    <div class="view-notes">📝 اخترق على الجانب الأيسر</div>
                </div>
                
                <!-- RIGHT SIDE VIEW -->
                <div class="car-view">
                    <div class="view-title">🔵 الجانب الأيمن</div>
                    <div class="car-diagram">
                        <svg class="sedan-front" viewBox="0 0 300 200" xmlns="http://www.w3.org/2000/svg">
                            <!-- Body (mirrored) -->
                            <ellipse cx="150" cy="110" rx="110" ry="40" fill="#ddd" stroke="#333" stroke-width="3"/>
                            <!-- Roof -->
                            <rect x="80" y="75" width="140" height="30" fill="#bbb" stroke="#333" stroke-width="2" rx="5"/>
                            <!-- Windows (mirrored) -->
                            <rect x="105" y="78" width="50" height="22" fill="#87ceeb" stroke="#333" stroke-width="1.5" rx="2"/>
                            <rect x="160" y="78" width="50" height="22" fill="#87ceeb" stroke="#333" stroke-width="1.5" rx="2"/>
                            <!-- Door Handle -->
                            <rect x="150" y="110" width="15" height="4" fill="#555" stroke="#333" stroke-width="1" rx="1"/>
                            <!-- Side Mirror (right) -->
                            <ellipse cx="235" cy="95" rx="8" ry="12" fill="#888" stroke="#333" stroke-width="2"/>
                            <!-- Front Light (mirrored) -->
                            <ellipse cx="55" cy="105" rx="10" ry="15" fill="#ffeb3b" stroke="#333" stroke-width="2"/>
                            <!-- Rear Light (mirrored) -->
                            <ellipse cx="245" cy="105" rx="10" ry="15" fill="#ff6b6b" stroke="#333" stroke-width="2"/>
                            <!-- Bottom Line -->
                            <line x1="40" y1="135" x2="260" y2="135" stroke="#333" stroke-width="2"/>
                            <!-- Wheels -->
                            <circle cx="80" cy="140" r="22" fill="#333" stroke="#222" stroke-width="3"/>
                            <circle cx="80" cy="140" r="12" fill="#444" stroke="#555" stroke-width="2"/>
                            <circle cx="220" cy="140" r="22" fill="#333" stroke="#222" stroke-width="3"/>
                            <circle cx="220" cy="140" r="12" fill="#444" stroke="#555" stroke-width="2"/>
                        </svg>
                        <canvas id="carRight" class="drawing-canvas"></canvas>
                    </div>
                    <div class="view-notes">📝 اخترق على الجانب الأيمن</div>
                </div>
            </div>
        </div>
        
        <!-- TERMS -->
        <div class="section-header">📋 الشروط والأحكام العامة</div>
        <div class="terms-section avoid-break">
            <h4>🔹 الشروط الأساسية:</h4>
            1. المستأجر مسؤول عن السيارة طوال فترة الإيجار من لحظة الاستلام.<br>
            2. يتعين إعادة السيارة بنفس الحالة التي تم استلامها فيها.<br>
            3. المستأجر مسؤول عن جميع المخالفات المرورية والغرامات.<br>
            4. غرامة التأخير عند الإعادة: <?php echo formatCurrency(LATE_RETURN_FEE); ?> لكل ساعة.<br>
            5. التأمين ينطبق على الحوادث غير المقصودة فقط.<br>
            6. أي أضرار متعمدة لا تغطيها بوليشة التأمين.
        </div>
        
        <!-- SIGNATURES -->
        <div class="section-header">✍️ التوقيعات والاتفاق</div>
        <div class="signature-section avoid-break">
            <div class="signature-box">
                <canvas id="customerSig" class="signature-canvas"></canvas>
                <div class="signature-line">
                    <strong>توقيع المستأجر</strong>
                    <?php echo htmlspecialchars($rental['customer_name']); ?>
                </div>
            </div>
            <div class="signature-box">
                <canvas id="companySig" class="signature-canvas"></canvas>
                <div class="signature-line">
                    <strong>توقيع الشركة</strong>
                    <?php echo COMPANY_NAME; ?>
                </div>
            </div>
        </div>
        
        <!-- PROMISSORY NOTE -->
        <?php if ($with_promissory && $remaining_amount > 0): ?>
        <div class="page-break"></div>
        
        <div class="promissory-note">
            <div class="promissory-header">
                <h2>🧾 كمبيالة / سند إذني</h2>
                <div class="subtitle">Promissory Note</div>
            </div>
            
            <div class="promissory-body">
                <p>أتعهد أنا الموقع أدناه</p>
                <span class="debtor-name"><?php echo htmlspecialchars($rental['customer_name']); ?></span>
                <p>رقم الهوية: <strong><?php echo $rental['id_number']; ?></strong> | الجنسية: فلسطيني</p>
                
                <div class="amount-box">
                    <div class="label">بدفع مبلغ وقدره</div>
                    <div class="amount"><?php echo formatCurrency($remaining_amount); ?></div>
                    <div class="words">(<?php echo numberToArabicWords($remaining_amount); ?> شيكل فقط لا غير)</div>
                </div>
                
                <div class="promissory-details">
                    <p>لصالح: <strong><?php echo COMPANY_NAME; ?></strong></p>
                    <p>بتاريخ: <strong><?php echo formatDate($rental['end_date']); ?></strong></p>
                    <p>المرجع: عقد إيجار رقم <strong><?php echo $rental['rental_number']; ?></strong></p>
                    <p style="margin-top: 15px; font-size: 10px; color: #666;">
                        هذا السند قابل للتداول وملزم قانونياً، وفي حالة التأخر عن السداد يحق للدائن اتخاذ الإجراءات القانونية
                    </p>
                </div>
                
                <div class="promissory-signature">
                    <div></div>
                    <div class="signature-box">
                        <canvas id="promissorySig" class="signature-canvas"></canvas>
                        <div class="signature-line">
                            <strong>توقيع المدين</strong>
                            <?php echo htmlspecialchars($rental['customer_name']); ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- FOOTER -->
        <div class="footer">
            <p>🇵🇸 نظام تأجير السيارات | Made with ❤️ in Palestine</p>
            <p>هذا العقد صادر إلكترونياً وله نفس قيمة العقد الورقي</p>
            <p style="margin-top: 8px; color: #999;">© <?php echo date('Y'); ?> <?php echo COMPANY_NAME; ?> - جميع الحقوق محفوظة</p>
        </div>
    </div>
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <script>
        // Canvas Drawing Setup
        function setupCanvas(canvasId) {
            const canvas = document.getElementById(canvasId);
            if (!canvas) return;
            
            const ctx = canvas.getContext('2d');
            let isDrawing = false;
            let lastX, lastY;
            
            // Set canvas size
            const rect = canvas.getBoundingClientRect();
            canvas.width = rect.width;
            canvas.height = rect.height;
            
            function getCoords(e) {
                const rect = canvas.getBoundingClientRect();
                const scaleX = canvas.width / rect.width;
                const scaleY = canvas.height / rect.height;
                
                if (e.touches && e.touches[0]) {
                    return {
                        x: (e.touches[0].clientX - rect.left) * scaleX,
                        y: (e.touches[0].clientY - rect.top) * scaleY
                    };
                }
                return {
                    x: (e.clientX - rect.left) * scaleX,
                    y: (e.clientY - rect.top) * scaleY
                };
            }
            
            function startDrawing(e) {
                isDrawing = true;
                const { x, y } = getCoords(e);
                lastX = x;
                lastY = y;
            }
            
            function draw(e) {
                if (!isDrawing) return;
                e.preventDefault();
                
                const { x, y } = getCoords(e);
                
                ctx.strokeStyle = '#d32f2f';
                ctx.lineWidth = 3;
                ctx.lineCap = 'round';
                ctx.lineJoin = 'round';
                
                ctx.beginPath();
                ctx.moveTo(lastX, lastY);
                ctx.lineTo(x, y);
                ctx.stroke();
                
                lastX = x;
                lastY = y;
            }
            
            function stopDrawing() {
                isDrawing = false;
            }
            
            canvas.addEventListener('mousedown', startDrawing);
            canvas.addEventListener('mousemove', draw);
            canvas.addEventListener('mouseup', stopDrawing);
            canvas.addEventListener('mouseout', stopDrawing);
            
            canvas.addEventListener('touchstart', startDrawing);
            canvas.addEventListener('touchmove', draw);
            canvas.addEventListener('touchend', stopDrawing);
        }
        
        // Initialize all canvases
        ['carFront', 'carBack', 'carLeft', 'carRight', 'customerSig', 'companySig', 'promissorySig'].forEach(setupCanvas);
        
        // Clear all drawings
        function clearDrawings() {
            ['carFront', 'carBack', 'carLeft', 'carRight', 'customerSig', 'companySig', 'promissorySig'].forEach(id => {
                const canvas = document.getElementById(id);
                if (canvas) {
                    const ctx = canvas.getContext('2d');
                    ctx.clearRect(0, 0, canvas.width, canvas.height);
                }
            });
        }
        
        // Export to PDF
        function exportPDF() {
            const element = document.getElementById('contract');
            const opt = {
                margin: 5,
                filename: 'contract-<?php echo $rental['rental_number']; ?>-<?php echo date('Y-m-d'); ?>.pdf',
                image: { type: 'jpeg', quality: 0.98 },
                html2canvas: { scale: 2, useCORS: true },
                jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' },
                pagebreak: { mode: ['avoid-all', 'css', 'legacy'] }
            };
            
            html2pdf().set(opt).from(element).save();
        }
    </script>
</body>
</html>

<?php
function numberToArabicWords($number) {
    $number = (int)$number;
    $ones = ['', 'واحد', 'اثنان', 'ثلاثة', 'أربعة', 'خمسة', 'ستة', 'سبعة', 'ثمانية', 'تسعة'];
    $tens = ['', 'عشرة', 'عشرون', 'ثلاثون', 'أربعون', 'خمسون', 'ستون', 'سبعون', 'ثمانون', 'تسعون'];
    $hundreds = ['', 'مئة', 'مئتان', 'ثلاثمئة', 'أربعمئة', 'خمسمئة', 'ستمئة', 'سبعمئة', 'ثمانمئة', 'تسعمئة'];
    
    if ($number == 0) return 'صفر';
    if ($number < 10) return $ones[$number];
    if ($number < 100) {
        $ten = floor($number / 10);
        $one = $number % 10;
        return $tens[$ten] . ($one > 0 ? ' و' . $ones[$one] : '');
    }
    if ($number < 1000) {
        $hundred = floor($number / 100);
        $remainder = $number % 100;
        $result = $hundreds[$hundred];
        if ($remainder > 0) $result .= ' و' . numberToArabicWords($remainder);
        return $result;
    }
    if ($number < 1000000) {
        $thousand = floor($number / 1000);
        $remainder = $number % 1000;
        $result = ($thousand == 1 ? 'ألف' : ($thousand == 2 ? 'ألفان' : numberToArabicWords($thousand) . ' ألف'));
        if ($remainder > 0) $result .= ' و' . numberToArabicWords($remainder);
        return $result;
    }
    return (string)$number;
}
?>
