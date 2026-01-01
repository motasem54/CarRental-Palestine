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
$with_promissory = isset($_GET['promissory']) && $_GET['promissory'] == '1';

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

// ✅ معالجة القيم المفقودة بقيم افتراضية آمنة
$rental['base_amount'] = $rental['base_amount'] ?? ($rental['total_amount'] - ($rental['tax_amount'] ?? 0) - ($rental['insurance_amount'] ?? 0));
$rental['insurance_amount'] = $rental['insurance_amount'] ?? 0;
$rental['tax_amount'] = $rental['tax_amount'] ?? 0;
$rental['discount_amount'] = $rental['discount_amount'] ?? 0;

// Get contract type from rental_contracts table
$stmt = $db->prepare("SELECT contract_type, has_promissory_note FROM rental_contracts WHERE rental_id = ? LIMIT 1");
$stmt->execute([$rental_id]);
$contractRecord = $stmt->fetch();
$contract_type = $contractRecord['contract_type'] ?? 'simple';
$with_promissory = ($contractRecord['has_promissory_note'] ?? 0) == 1;

// Calculate remaining amount for promissory note
$paid_amount = $rental['paid_amount'] ?? 0;
$remaining_amount = $rental['total_amount'] - $paid_amount;

// Get inspection record if exists
$stmt = $db->prepare("SELECT * FROM inspection_forms WHERE rental_id = ? LIMIT 1");
$stmt->execute([$rental_id]);
$inspection = $stmt->fetch();

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
            margin: 15mm;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Cairo', sans-serif;
        }
        
        body {
            background: #f5f5f5;
            padding: 10px;
        }
        
        @media print {
            body {
                background: white;
                padding: 0;
            }
            .no-print { display: none !important; }
            .page-break { page-break-after: always; }
        }
        
        .contract-container {
            max-width: 210mm;
            margin: 0 auto;
            background: white;
            padding: 20mm;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            position: relative;
        }
        
        /* Watermark */
        .watermark {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-45deg);
            font-size: 80px;
            color: rgba(255, 87, 34, 0.03);
            font-weight: 900;
            z-index: 0;
            pointer-events: none;
        }
        
        /* Header */
        .contract-header {
            text-align: center;
            border-bottom: 3px solid #FF5722;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }
        
        .contract-header .logo {
            width: 50px;
            height: 50px;
            margin: 0 auto 10px;
            background: linear-gradient(135deg, #FF5722, #E64A19);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
        }
        
        .contract-header h1 {
            color: #FF5722;
            font-size: 1.5rem;
            font-weight: 900;
            margin-bottom: 8px;
        }
        
        .contract-header .company-name {
            font-size: 1.1rem;
            color: #333;
            font-weight: 700;
            margin-bottom: 5px;
        }
        
        .contract-header .company-info {
            color: #666;
            font-size: 0.85rem;
            line-height: 1.5;
        }
        
        /* Contract Number */
        .contract-number {
            background: #f8f9fa;
            border-right: 4px solid #FF5722;
            padding: 12px 15px;
            margin-bottom: 20px;
            font-size: 0.9rem;
            display: flex;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 10px;
        }
        
        .contract-number strong {
            color: #FF5722;
        }
        
        /* Section Title */
        .section-title {
            background: linear-gradient(135deg, #FF5722, #E64A19);
            color: white;
            padding: 8px 15px;
            margin: 20px 0 12px 0;
            font-weight: 700;
            font-size: 0.95rem;
            border-radius: 5px;
        }
        
        /* Info Table */
        .info-table {
            width: 100%;
            margin-bottom: 15px;
            font-size: 0.85rem;
        }
        
        .info-table td {
            padding: 8px 12px;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .info-table td:first-child {
            font-weight: 600;
            color: #555;
            width: 30%;
            background: #fafafa;
        }
        
        /* Total Box */
        .total-box {
            background: linear-gradient(135deg, #fff3e0, #ffe0b2);
            border: 2px solid #FF5722;
            border-radius: 8px;
            padding: 15px;
            margin: 15px 0;
            text-align: center;
        }
        
        .total-box h4 {
            color: #FF5722;
            font-size: 1.3rem;
            font-weight: 900;
            margin: 0;
        }
        
        /* Terms List */
        .terms-list {
            list-style: none;
            counter-reset: term-counter;
            padding: 0;
            font-size: 0.8rem;
        }
        
        .terms-list li {
            counter-increment: term-counter;
            margin-bottom: 8px;
            padding-right: 30px;
            position: relative;
            line-height: 1.6;
        }
        
        .terms-list li::before {
            content: counter(term-counter);
            position: absolute;
            right: 0;
            top: 0;
            width: 22px;
            height: 22px;
            background: #FF5722;
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 0.75rem;
        }
        
        /* Signatures */
        .signature-section {
            margin-top: 30px;
            display: flex;
            justify-content: space-between;
            gap: 20px;
        }
        
        .signature-box {
            flex: 1;
            text-align: center;
        }
        
        .signature-canvas {
            border: 2px dashed #ddd;
            border-radius: 8px;
            cursor: crosshair;
            background: #fafafa;
            width: 100%;
            height: 120px;
        }
        
        .signature-canvas.signed {
            border-color: #4CAF50;
            background: white;
        }
        
        .signature-line {
            border-top: 2px solid #333;
            margin-top: 20px;
            padding-top: 8px;
            font-size: 0.85rem;
        }
        
        .signature-title {
            font-weight: 700;
            color: #FF5722;
            margin-bottom: 5px;
        }
        
        /* Promissory Note */
        .promissory-note {
            margin-top: 30px;
            padding: 20px;
            border: 3px double #FF5722;
            border-radius: 10px;
            background: #fffaf5;
        }
        
        .promissory-note h3 {
            text-align: center;
            color: #FF5722;
            font-weight: 900;
            margin-bottom: 15px;
            font-size: 1.2rem;
        }
        
        .promissory-note .amount-box {
            background: white;
            border: 2px solid #FF5722;
            padding: 15px;
            text-align: center;
            margin: 15px 0;
            border-radius: 5px;
        }
        
        .promissory-note .amount-box .amount {
            font-size: 1.5rem;
            font-weight: 900;
            color: #FF5722;
        }
        
        /* Inspection Form - 4 Car Images */
        .inspection-section {
            margin-top: 20px;
            padding: 20px;
            border: 2px solid #2196F3;
            background: #E3F2FD;
            border-radius: 10px;
        }
        
        .inspection-section h3 {
            color: #1565C0;
            margin-bottom: 15px;
            text-align: center;
        }
        
        .car-images-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .car-image-box {
            border: 2px solid #2196F3;
            border-radius: 8px;
            padding: 15px;
            background: white;
            text-align: center;
        }
        
        .car-image-box .position-label {
            font-weight: 700;
            color: #1565C0;
            margin-bottom: 10px;
        }
        
        .car-sketch-canvas {
            border: 2px solid #ccc;
            border-radius: 5px;
            cursor: crosshair;
            background: white;
            width: 100%;
            height: 150px;
            margin-bottom: 10px;
        }
        
        .canvas-notes {
            font-size: 0.8rem;
            color: #666;
            margin-top: 8px;
            font-style: italic;
        }
        
        /* Inspection Notes */
        .inspection-notes {
            margin-top: 15px;
            padding: 12px;
            background: white;
            border-radius: 5px;
            border-right: 4px solid #2196F3;
        }
        
        .inspection-notes label {
            display: block;
            font-weight: 600;
            color: #333;
            margin-bottom: 8px;
        }
        
        .inspection-notes textarea {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-family: 'Cairo', sans-serif;
            font-size: 0.9rem;
            resize: vertical;
            min-height: 80px;
        }
        
        /* Footer */
        .contract-footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 15px;
            border-top: 1px dashed #ddd;
            color: #999;
            font-size: 0.75rem;
        }
        
        /* Buttons */
        .action-buttons {
            position: fixed;
            bottom: 20px;
            left: 20px;
            z-index: 1000;
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            max-width: 500px;
        }
        
        .btn {
            background: linear-gradient(135deg, #FF5722, #E64A19);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 25px;
            font-weight: 700;
            cursor: pointer;
            box-shadow: 0 4px 15px rgba(255, 87, 34, 0.3);
            transition: all 0.3s;
            font-size: 0.9rem;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(255, 87, 34, 0.4);
        }
        
        .btn-secondary {
            background: linear-gradient(135deg, #607D8B, #455A64);
        }
        
        .btn-success {
            background: linear-gradient(135deg, #4CAF50, #388E3C);
        }
        
        .btn-info {
            background: linear-gradient(135deg, #2196F3, #1976D2);
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .contract-container {
                padding: 15px;
            }
            
            .signature-section {
                flex-direction: column;
            }
            
            .car-images-grid {
                grid-template-columns: 1fr;
            }
            
            .action-buttons {
                position: static;
                justify-content: center;
                margin: 20px 0;
                max-width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="watermark">نظام تأجير سيارات</div>
    
    <!-- Action Buttons -->
    <div class="action-buttons no-print">
        <button class="btn" onclick="window.print()">🖨️ طباعة</button>
        <button class="btn btn-success" onclick="saveToPDF()">📥 تحميل PDF</button>
        <button class="btn btn-info" onclick="saveContractDraft()">💾 حفظ مسودة</button>
        <button class="btn btn-secondary" onclick="clearAllDrawings()">🔄 مسح الرسومات</button>
        <a href="rental_add.php" class="btn btn-secondary" style="text-decoration:none; display:inline-flex; align-items:center;">← العودة</a>
    </div>
    
    <div class="contract-container" id="contract-content">
        
        <!-- Header -->
        <div class="contract-header">
            <div class="logo">🚗</div>
            <h1>عقد إيجار سيارة</h1>
            <div class="company-name"><?php echo COMPANY_NAME; ?></div>
            <div class="company-info">
                📞 <?php echo COMPANY_PHONE; ?> | 📧 <?php echo COMPANY_EMAIL; ?><br>
                📍 <?php echo COMPANY_ADDRESS; ?> | 🇵🇸 فلسطين
            </div>
        </div>
        
        <!-- Contract Number -->
        <div class="contract-number">
            <span><strong>رقم العقد:</strong> <?php echo $rental['rental_number']; ?></span>
            <span><strong>التاريخ:</strong> <?php echo formatDate($rental['created_at']); ?></span>
            <span><strong>النوع:</strong> <?php echo $with_promissory ? '✅ مع كمبيالة' : '📋 بسيط'; ?></span>
        </div>
        
        <!-- Customer Information -->
        <div class="section-title">👤 بيانات المستأجر</div>
        <table class="info-table">
            <tr>
                <td>الاسم:</td>
                <td><?php echo htmlspecialchars($rental['customer_name']); ?></td>
            </tr>
            <tr>
                <td>رقم الهوية:</td>
                <td><?php echo $rental['id_number']; ?></td>
            </tr>
            <tr>
                <td>الهاتف:</td>
                <td><?php echo $rental['customer_phone']; ?></td>
            </tr>
            <tr>
                <td>العنوان:</td>
                <td><?php echo htmlspecialchars($rental['customer_address']); ?></td>
            </tr>
        </table>
        
        <!-- Car Information -->
        <div class="section-title">🚙 بيانات السيارة</div>
        <table class="info-table">
            <tr>
                <td>النوع:</td>
                <td><strong><?php echo $rental['brand'] . ' ' . $rental['model'] . ' (' . $rental['year'] . ')'; ?></strong></td>
            </tr>
            <tr>
                <td>اللوحة:</td>
                <td><strong style="color:#FF5722;"><?php echo $rental['plate_number']; ?></strong></td>
            </tr>
            <tr>
                <td>اللون:</td>
                <td><?php echo $rental['color']; ?></td>
            </tr>
        </table>
        
        <!-- Rental Period -->
        <div class="section-title">📅 فترة الإيجار</div>
        <table class="info-table">
            <tr>
                <td>من:</td>
                <td><?php echo formatDate($rental['start_date']); ?></td>
            </tr>
            <tr>
                <td>إلى:</td>
                <td><?php echo formatDate($rental['end_date']); ?></td>
            </tr>
            <tr>
                <td>المدة:</td>
                <td><strong><?php echo $rental['total_days']; ?> يوم</strong></td>
            </tr>
        </table>
        
        <!-- Financial -->
        <div class="section-title">💰 التفاصيل المالية</div>
        <table class="info-table">
            <tr>
                <td>المبلغ الأساسي:</td>
                <td><?php echo formatCurrency($rental['base_amount']); ?></td>
            </tr>
            <?php if ($rental['discount_amount'] > 0): ?>
            <tr>
                <td>الخصم:</td>
                <td style="color:#4CAF50;">-<?php echo formatCurrency($rental['discount_amount']); ?></td>
            </tr>
            <?php endif; ?>
            <tr>
                <td>الضريبة:</td>
                <td><?php echo formatCurrency($rental['tax_amount']); ?></td>
            </tr>
            <tr>
                <td>التأمين:</td>
                <td><?php echo formatCurrency($rental['insurance_amount']); ?></td>
            </tr>
        </table>
        
        <div class="total-box">
            <h4>المبلغ الإجمالي: <?php echo formatCurrency($rental['total_amount']); ?></h4>
        </div>
        
        <!-- Terms -->
        <div class="section-title">📋 الشروط والأحكام</div>
        <ol class="terms-list">
            <li>المستأجر مسؤول عن السيارة طوال فترة الإيجار.</li>
            <li>غرامة التأخير: <?php echo formatCurrency(LATE_RETURN_FEE); ?> لكل يوم.</li>
            <li>يجب إعادة السيارة بنفس حالة الاستلام.</li>
            <li>المستأجر مسؤول عن المخالفات المرورية.</li>
            <li>التأمين يُسترد عند إعادة السيارة بحالة جيدة.</li>
        </ol>
        
        <!-- Signatures -->
        <div class="section-title">✍️ التوقيعات</div>
        <div class="signature-section">
            <div class="signature-box">
                <canvas id="customerSignature" class="signature-canvas" width="250" height="120"></canvas>
                <div class="signature-line">
                    <div class="signature-title">توقيع المستأجر</div>
                    <div><?php echo htmlspecialchars($rental['customer_name']); ?></div>
                </div>
            </div>
            <div class="signature-box">
                <canvas id="companySignature" class="signature-canvas" width="250" height="120"></canvas>
                <div class="signature-line">
                    <div class="signature-title">توقيع الشركة</div>
                    <div><?php echo COMPANY_NAME; ?></div>
                </div>
            </div>
        </div>
        
        <!-- INSPECTION FORM - 4 CAR IMAGES -->
        <div class="page-break"></div>
        
        <div class="inspection-section">
            <h3>🔍 نموذج فحص السيارة - اخترق على الأضرار والملاحظات</h3>
            
            <div class="car-images-grid">
                <!-- Front -->
                <div class="car-image-box">
                    <div class="position-label">🔴 الأمام (Front)</div>
                    <canvas id="carFront" class="car-sketch-canvas"></canvas>
                    <div class="canvas-notes">اخترق على أي خدوش أو تجنيشات في الأمام</div>
                </div>
                
                <!-- Back -->
                <div class="car-image-box">
                    <div class="position-label">🟡 الخلف (Back)</div>
                    <canvas id="carBack" class="car-sketch-canvas"></canvas>
                    <div class="canvas-notes">اخترق على أي أضرار في الخلف</div>
                </div>
                
                <!-- Left -->
                <div class="car-image-box">
                    <div class="position-label">🟢 اليسار (Left Side)</div>
                    <canvas id="carLeft" class="car-sketch-canvas"></canvas>
                    <div class="canvas-notes">اخترق على الجنب الأيسر</div>
                </div>
                
                <!-- Right -->
                <div class="car-image-box">
                    <div class="position-label">🔵 اليمين (Right Side)</div>
                    <canvas id="carRight" class="car-sketch-canvas"></canvas>
                    <div class="canvas-notes">اخترق على الجنب الأيمن</div>
                </div>
            </div>
            
            <!-- Inspection Notes -->
            <div class="inspection-notes">
                <label>📝 ملاحظات الفحص:</label>
                <textarea id="inspectionNotes" placeholder="اكتب ملاحظاتك حول حالة السيارة (الخدوش، البقع، مستوى الوقود، حالة الإطارات، إلخ)...">اسم المستأجر: <?php echo htmlspecialchars($rental['customer_name']); ?>
رقم السيارة: <?php echo $rental['plate_number']; ?>
عداد الكيلومترات: <?php echo $rental['mileage_start']; ?> كم
الملاحظات:
- الحالة الخارجية: جيدة
- الإطارات: سليمة
- الأضواء: تعمل
</textarea>
            </div>
        </div>
        
        <!-- PROMISSORY NOTE -->
        <?php if ($with_promissory && $remaining_amount > 0): ?>
        <div class="page-break"></div>
        
        <div class="promissory-note">
            <h3>🧾 كمبيالة (سند إذني)</h3>
            
            <p style="text-align:center; margin:15px 0; line-height:2;">
                أتعهد أنا <strong><?php echo htmlspecialchars($rental['customer_name']); ?></strong><br>
                رقم الهوية: <strong><?php echo $rental['id_number']; ?></strong><br>
                بدفع مبلغ وقدره:
            </p>
            
            <div class="amount-box">
                <div class="amount"><?php echo formatCurrency($remaining_amount); ?></div>
                <div style="margin-top:5px; color:#666; font-size:0.9rem;">
                    (<?php echo numberToArabicWords($remaining_amount); ?> شيكل فقط لا غير)
                </div>
            </div>
            
            <p style="text-align:center; margin:15px 0; line-height:2;">
                لصالح: <strong><?php echo COMPANY_NAME; ?></strong><br>
                في تاريخ: <strong><?php echo formatDate($rental['end_date']); ?></strong><br>
                المرجع: عقد إيجار رقم <strong><?php echo $rental['rental_number']; ?></strong>
            </p>
            
            <div class="signature-section" style="margin-top:30px;">
                <div class="signature-box">
                    <canvas id="promissorySignature" class="signature-canvas" width="250" height="120"></canvas>
                    <div class="signature-line">
                        <div class="signature-title">توقيع المدين</div>
                        <div><?php echo htmlspecialchars($rental['customer_name']); ?></div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Footer -->
        <div class="contract-footer">
            <p>هذا العقد صادر إلكترونياً من نظام تأجير السيارات</p>
            <p>🇵🇸 Made with ❤️ in Palestine</p>
        </div>
        
    </div>
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <script>
        // ✅ Signature functionality
        function initSignature(canvasId) {
            const canvas = document.getElementById(canvasId);
            if (!canvas) return;
            
            const ctx = canvas.getContext('2d');
            ctx.lineCap = 'round';
            ctx.lineJoin = 'round';
            
            let isDrawing = false;
            let lastX = 0;
            let lastY = 0;
            
            function getCoordinates(e) {
                const rect = canvas.getBoundingClientRect();
                const x = (e.clientX || e.touches?.[0]?.clientX || 0) - rect.left;
                const y = (e.clientY || e.touches?.[0]?.clientY || 0) - rect.top;
                return { x, y };
            }
            
            function startDrawing(e) {
                isDrawing = true;
                const coords = getCoordinates(e);
                [lastX, lastY] = [coords.x, coords.y];
                canvas.classList.add('signed');
            }
            
            function draw(e) {
                if (!isDrawing) return;
                e.preventDefault();
                
                const coords = getCoordinates(e);
                ctx.strokeStyle = '#000';
                ctx.lineWidth = 2;
                
                ctx.beginPath();
                ctx.moveTo(lastX, lastY);
                ctx.lineTo(coords.x, coords.y);
                ctx.stroke();
                
                [lastX, lastY] = [coords.x, coords.y];
            }
            
            function stopDrawing() {
                isDrawing = false;
            }
            
            canvas.addEventListener('mousedown', startDrawing);
            canvas.addEventListener('mousemove', draw);
            canvas.addEventListener('mouseup', stopDrawing);
            canvas.addEventListener('mouseout', stopDrawing);
            
            canvas.addEventListener('touchstart', startDrawing, { passive: false });
            canvas.addEventListener('touchmove', draw, { passive: false });
            canvas.addEventListener('touchend', stopDrawing);
        }
        
        // ✅ Initialize all canvases
        initSignature('customerSignature');
        initSignature('companySignature');
        initSignature('promissorySignature');
        initSignature('carFront');
        initSignature('carBack');
        initSignature('carLeft');
        initSignature('carRight');
        
        // ✅ Clear all drawings
        function clearAllDrawings() {
            const canvases = ['customerSignature', 'companySignature', 'promissorySignature', 'carFront', 'carBack', 'carLeft', 'carRight'];
            canvases.forEach(id => {
                const canvas = document.getElementById(id);
                if (canvas) {
                    const ctx = canvas.getContext('2d');
                    ctx.clearRect(0, 0, canvas.width, canvas.height);
                    canvas.classList.remove('signed');
                }
            });
        }
        
        // ✅ Save to PDF with all content
        function saveToPDF() {
            const element = document.getElementById('contract-content');
            const opt = {
                margin: 10,
                filename: 'contract-<?php echo $rental['rental_number']; ?>-' + new Date().toISOString().slice(0,10) + '.pdf',
                image: { type: 'jpeg', quality: 0.98 },
                html2canvas: { scale: 2, useCORS: true, allowTaint: true },
                jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' },
                pagebreak: { mode: ['avoid-all', 'css', 'legacy'] }
            };
            
            html2pdf().set(opt).from(element).save();
        }
        
        // ✅ Save contract draft (AJAX)
        function saveContractDraft() {
            const notes = document.getElementById('inspectionNotes').value;
            
            // Get signature data as base64
            const signatures = {};
            ['customerSignature', 'companySignature', 'promissorySignature', 'carFront', 'carBack', 'carLeft', 'carRight'].forEach(id => {
                const canvas = document.getElementById(id);
                if (canvas) {
                    signatures[id] = canvas.toDataURL('image/png');
                }
            });
            
            fetch('save_contract_draft.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    rental_id: <?php echo $rental_id; ?>,
                    inspection_notes: notes,
                    signatures: signatures,
                    has_promissory: <?php echo $with_promissory ? 'true' : 'false'; ?>
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('✅ تم حفظ مسودة العقد بنجاح!');
                } else {
                    alert('❌ خطأ: ' + data.message);
                }
            })
            .catch(err => {
                console.error('Error:', err);
                alert('❌ حدث خطأ في الحفظ');
            });
        }
    </script>
</body>
</html>

<?php
// Helper function to convert numbers to Arabic words
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
        if ($remainder > 0) {
            $result .= ' و' . numberToArabicWords($remainder);
        }
        return $result;
    }
    
    return (string)$number;
}
?>
