<?php

/**
 * PDF Generation Class
 * Simple PDF generator for contracts and invoices
 */
class PDF {
    private $db;
    
    public function __construct($db) {
        $this->db = $db;
    }
    
    /**
     * Generate rental contract HTML
     */
    public function generateContractHTML($rentalId) {
        $stmt = $this->db->prepare("
            SELECT r.*, 
                   c.full_name as customer_name, c.phone as customer_phone, 
                   c.id_number, c.address,
                   ca.brand, ca.model, ca.year, ca.plate_number, ca.color,
                   u.full_name as created_by
            FROM rentals r
            JOIN customers c ON r.customer_id = c.id
            JOIN cars ca ON r.car_id = ca.id
            JOIN users u ON r.created_by = u.id
            WHERE r.id = ?
        ");
        $stmt->execute([$rentalId]);
        $rental = $stmt->fetch();
        
        if (!$rental) {
            return false;
        }
        
        $html = '
<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; direction: rtl; }
        .header { text-align: center; border-bottom: 3px solid #FF5722; padding-bottom: 20px; margin-bottom: 30px; }
        .company-name { font-size: 28px; font-weight: bold; color: #FF5722; }
        .contract-title { font-size: 24px; margin: 20px 0; }
        .section { margin: 20px 0; }
        .section-title { background: #f5f5f5; padding: 10px; font-weight: bold; border-right: 4px solid #FF5722; }
        .info-row { padding: 8px 0; border-bottom: 1px dashed #ddd; }
        .info-label { font-weight: bold; display: inline-block; width: 150px; }
        .footer { margin-top: 50px; border-top: 2px solid #ddd; padding-top: 20px; }
        .signature-box { display: inline-block; width: 45%; text-align: center; margin: 20px 2%; }
        .signature-line { border-top: 2px solid #000; margin-top: 60px; padding-top: 10px; }
        table { width: 100%; border-collapse: collapse; margin: 15px 0; }
        table th, table td { padding: 10px; border: 1px solid #ddd; text-align: right; }
        table th { background: #FF5722; color: white; }
        .total-row { background: #f5f5f5; font-weight: bold; font-size: 18px; }
    </style>
</head>
<body>
    <div class="header">
        <div class="company-name">' . SITE_NAME . '</div>
        <div>' . COMPANY_ADDRESS . '</div>
        <div>هاتف: ' . COMPANY_PHONE . ' | ' . COMPANY_EMAIL . '</div>
    </div>
    
    <div class="contract-title" style="text-align: center;">
        <strong>عقد تأجير سيارة</strong><br>
        رقم العقد: ' . $rental['rental_number'] . '
    </div>
    
    <div class="section">
        <div class="section-title">معلومات العميل (الطرف الأول)</div>
        <div class="info-row">
            <span class="info-label">الاسم الكامل:</span>
            <span>' . $rental['customer_name'] . '</span>
        </div>
        <div class="info-row">
            <span class="info-label">رقم الهوية:</span>
            <span>' . $rental['id_number'] . '</span>
        </div>
        <div class="info-row">
            <span class="info-label">رقم الهاتف:</span>
            <span>' . $rental['customer_phone'] . '</span>
        </div>
        <div class="info-row">
            <span class="info-label">العنوان:</span>
            <span>' . $rental['address'] . '</span>
        </div>
    </div>
    
    <div class="section">
        <div class="section-title">معلومات السيارة</div>
        <div class="info-row">
            <span class="info-label">نوع السيارة:</span>
            <span>' . $rental['brand'] . ' ' . $rental['model'] . ' ' . $rental['year'] . '</span>
        </div>
        <div class="info-row">
            <span class="info-label">رقم اللوحة:</span>
            <span>' . $rental['plate_number'] . '</span>
        </div>
        <div class="info-row">
            <span class="info-label">اللون:</span>
            <span>' . $rental['color'] . '</span>
        </div>
    </div>
    
    <div class="section">
        <div class="section-title">تفاصيل الإيجار</div>
        <div class="info-row">
            <span class="info-label">تاريخ الاستلام:</span>
            <span>' . formatDate($rental['start_date'], 'd/m/Y H:i') . '</span>
        </div>
        <div class="info-row">
            <span class="info-label">تاريخ التسليم:</span>
            <span>' . formatDate($rental['end_date'], 'd/m/Y H:i') . '</span>
        </div>
        <div class="info-row">
            <span class="info-label">عدد الأيام:</span>
            <span>' . $rental['total_days'] . ' يوم</span>
        </div>
        <div class="info-row">
            <span class="info-label">مكان الاستلام:</span>
            <span>' . $rental['pickup_location'] . '</span>
        </div>
        <div class="info-row">
            <span class="info-label">مكان التسليم:</span>
            <span>' . $rental['return_location'] . '</span>
        </div>
    </div>
    
    <div class="section">
        <div class="section-title">التفاصيل المالية</div>
        <table>
            <tr>
                <th>البيان</th>
                <th>المبلغ</th>
            </tr>
            <tr>
                <td>الأجرة الأساسية (' . $rental['total_days'] . ' × ' . formatCurrency($rental['daily_rate']) . ')</td>
                <td>' . formatCurrency($rental['subtotal']) . '</td>
            </tr>
            <tr>
                <td>الضريبة</td>
                <td>' . formatCurrency($rental['tax_amount']) . '</td>
            </tr>
            <tr>
                <td>الخصم</td>
                <td>' . formatCurrency($rental['discount_amount']) . '</td>
            </tr>
            <tr class="total-row">
                <td>الإجمالي</td>
                <td>' . formatCurrency($rental['total_amount']) . '</td>
            </tr>
            <tr>
                <td>المبلغ المدفوع</td>
                <td>' . formatCurrency($rental['paid_amount']) . '</td>
            </tr>
            <tr style="background: #fff3cd;">
                <td>المتبقي</td>
                <td>' . formatCurrency($rental['remaining_amount']) . '</td>
            </tr>
        </table>
    </div>
    
    <div class="section">
        <div class="section-title">الشروط والأحكام</div>
        <ol style="line-height: 2;">
            <li>يلتزم المستأجر بإرجاع السيارة في الموعد المحدد وإلا سيتحمل غرامة تأخير قدرها 50 شيكل لكل يوم.</li>
            <li>السيارة مؤمنة تأميناً شاملاً، ويتحمل المستأجر التحمل في حالة وقوع حادث.</li>
            <li>يمنع استخدام السيارة خارج حدود فلسطين إلا بموافقة خطية.</li>
            <li>يتعهد المستأجر بإرجاع السيارة بنفس الحالة التي استلمها عليها.</li>
            <li>أي ضرر أو تلف سيتحمله المستأجر.</li>
            <li>يمنع تأجير السيارة من الباطن.</li>
            <li>في حالة عدم الالتزام بالشروط يحق للشركة استرداد السيارة فوراً.</li>
        </ol>
    </div>
    
    <div class="footer">
        <div class="signature-box">
            <strong>توقيع الطرف الأول (المستأجر)</strong>
            <div class="signature-line"></div>
        </div>
        <div class="signature-box">
            <strong>توقيع الطرف الثاني (الشركة)</strong>
            <div class="signature-line">' . $rental['created_by'] . '</div>
        </div>
        <div style="text-align: center; margin-top: 30px; color: #666;">
            تاريخ الإصدار: ' . formatDate($rental['created_at'], 'd/m/Y H:i') . '
        </div>
    </div>
</body>
</html>';
        
        return $html;
    }
    
    /**
     * Generate invoice HTML
     */
    public function generateInvoiceHTML($rentalId) {
        // Get rental and payments data
        $stmt = $this->db->prepare("
            SELECT r.*, 
                   c.full_name as customer_name, c.phone as customer_phone,
                   ca.brand, ca.model, ca.plate_number
            FROM rentals r
            JOIN customers c ON r.customer_id = c.id
            JOIN cars ca ON r.car_id = ca.id
            WHERE r.id = ?
        ");
        $stmt->execute([$rentalId]);
        $rental = $stmt->fetch();
        
        if (!$rental) {
            return false;
        }
        
        // Get payments
        $paymentsStmt = $this->db->prepare("
            SELECT * FROM payments WHERE rental_id = ? ORDER BY created_at
        ");
        $paymentsStmt->execute([$rentalId]);
        $payments = $paymentsStmt->fetchAll();
        
        $html = '
<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; direction: rtl; }
        .header { text-align: center; border-bottom: 3px solid #FF5722; padding-bottom: 20px; margin-bottom: 30px; }
        .invoice-title { font-size: 32px; color: #FF5722; font-weight: bold; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        table th, table td { padding: 12px; border: 1px solid #ddd; text-align: right; }
        table th { background: #FF5722; color: white; }
        .total { background: #f5f5f5; font-weight: bold; font-size: 20px; }
    </style>
</head>
<body>
    <div class="header">
        <div class="invoice-title">فاتورة</div>
        <div>' . SITE_NAME . '</div>
        <div>' . COMPANY_ADDRESS . ' | ' . COMPANY_PHONE . '</div>
    </div>
    
    <div style="margin: 20px 0;">
        <strong>رقم الفاتورة:</strong> ' . $rental['rental_number'] . '<br>
        <strong>التاريخ:</strong> ' . formatDate($rental['created_at'], 'd/m/Y') . '<br>
        <strong>العميل:</strong> ' . $rental['customer_name'] . '<br>
        <strong>الهاتف:</strong> ' . $rental['customer_phone'] . '
    </div>
    
    <table>
        <tr>
            <th>البيان</th>
            <th>المبلغ</th>
        </tr>
        <tr>
            <td>تأجير ' . $rental['brand'] . ' ' . $rental['model'] . ' (' . $rental['plate_number'] . ')</td>
            <td>' . formatCurrency($rental['subtotal']) . '</td>
        </tr>
        <tr>
            <td>الفترة: من ' . formatDate($rental['start_date'], 'd/m/Y') . ' إلى ' . formatDate($rental['end_date'], 'd/m/Y') . '</td>
            <td>' . $rental['total_days'] . ' يوم</td>
        </tr>
        <tr>
            <td>الضريبة</td>
            <td>' . formatCurrency($rental['tax_amount']) . '</td>
        </tr>
        <tr>
            <td>الخصم</td>
            <td>-' . formatCurrency($rental['discount_amount']) . '</td>
        </tr>
        <tr class="total">
            <td>الإجمالي</td>
            <td>' . formatCurrency($rental['total_amount']) . '</td>
        </tr>
    </table>
    
    <h3>المدفوعات:</h3>
    <table>
        <tr>
            <th>التاريخ</th>
            <th>الطريقة</th>
            <th>المبلغ</th>
        </tr>';
        
        foreach ($payments as $payment) {
            $html .= '
        <tr>
            <td>' . formatDate($payment['created_at'], 'd/m/Y H:i') . '</td>
            <td>' . PAYMENT_METHODS[$payment['payment_method']] . '</td>
            <td>' . formatCurrency($payment['amount']) . '</td>
        </tr>';
        }
        
        $html .= '
        <tr class="total">
            <td colspan="2">إجمالي المدفوع</td>
            <td>' . formatCurrency($rental['paid_amount']) . '</td>
        </tr>
        <tr style="background: #fff3cd;">
            <td colspan="2">المتبقي</td>
            <td>' . formatCurrency($rental['remaining_amount']) . '</td>
        </tr>
    </table>
    
    <div style="margin-top: 50px; text-align: center; color: #666;">
        شكراً لتعاملكم معنا | ' . SITE_NAME . ' 🇵🇸
    </div>
</body>
</html>';
        
        return $html;
    }
}
?>