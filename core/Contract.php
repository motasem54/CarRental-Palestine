<?php
/**
 * Contract Management Class
 * 📝 إدارة العقود
 */

class Contract {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Generate contract for rental
     */
    public function generate($rentalId) {
        try {
            // Get rental details
            $rental = $this->getRentalDetails($rentalId);
            
            if (!$rental) {
                throw new Exception('الحجز غير موجود');
            }

            // Generate contract HTML
            $html = $this->generateContractHTML($rental);

            // Generate PDF
            $pdfPath = $this->generatePDF($html, $rental['rental_number']);

            // Update rental with contract path
            $stmt = $this->db->prepare("
                UPDATE rentals SET 
                    contract_path = ?,
                    contract_signed = 1,
                    updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$pdfPath, $rentalId]);

            return [
                'success' => true,
                'contract_path' => $pdfPath,
                'message' => 'تم إنشاء العقد بنجاح'
            ];

        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Get rental details for contract
     */
    private function getRentalDetails($rentalId) {
        $sql = "
            SELECT 
                r.*,
                c.plate_number, c.brand, c.model, c.year, c.color, c.type,
                c.transmission, c.fuel_type, c.seats,
                cu.full_name as customer_name, cu.id_number, cu.phone as customer_phone,
                cu.email as customer_email, cu.address as customer_address,
                cu.driver_license, cu.license_expiry
            FROM rentals r
            JOIN cars c ON r.car_id = c.id
            JOIN customers cu ON r.customer_id = cu.id
            WHERE r.id = ?
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$rentalId]);
        return $stmt->fetch();
    }

    /**
     * Generate contract HTML
     */
    private function generateContractHTML($rental) {
        ob_start();
        ?>
        <!DOCTYPE html>
        <html lang="ar" dir="rtl">
        <head>
            <meta charset="UTF-8">
            <title>عقد إيجار - <?php echo $rental['rental_number']; ?></title>
            <style>
                @page { margin: 2cm; }
                body { font-family: 'DejaVu Sans', Arial, sans-serif; font-size: 12pt; line-height: 1.6; }
                .header { text-align: center; border-bottom: 3px solid #FF5722; padding-bottom: 20px; margin-bottom: 30px; }
                .header h1 { color: #FF5722; margin: 0; }
                .section { margin-bottom: 25px; }
                .section-title { background: #FF5722; color: white; padding: 8px 15px; margin-bottom: 15px; font-weight: bold; }
                table { width: 100%; border-collapse: collapse; margin: 15px 0; }
                table td { padding: 8px; border: 1px solid #ddd; }
                table td:first-child { background: #f5f5f5; font-weight: bold; width: 35%; }
                .terms { background: #f9f9f9; padding: 15px; border-right: 4px solid #FF5722; }
                .terms li { margin-bottom: 8px; }
                .signature-box { margin-top: 50px; display: flex; justify-content: space-between; }
                .signature { text-align: center; border-top: 2px solid #333; padding-top: 10px; width: 40%; }
                .footer { text-align: center; margin-top: 50px; padding-top: 20px; border-top: 2px solid #ddd; color: #666; font-size: 10pt; }
                .qr-code { text-align: center; margin: 20px 0; }
            </style>
        </head>
        <body>
            <div class="header">
                <h1><?php echo COMPANY_NAME; ?></h1>
                <p><strong>عقد إيجار سيارة</strong></p>
                <p>رقم العقد: <strong><?php echo $rental['rental_number']; ?></strong></p>
                <p>التاريخ: <?php echo formatDate(date('Y-m-d'), 'd/m/Y'); ?></p>
            </div>

            <div class="section">
                <div class="section-title">بيانات الشركة (المؤجر)</div>
                <table>
                    <tr><td>اسم الشركة</td><td><?php echo COMPANY_NAME; ?></td></tr>
                    <tr><td>العنوان</td><td><?php echo COMPANY_ADDRESS; ?></td></tr>
                    <tr><td>الهاتف</td><td><?php echo COMPANY_PHONE; ?></td></tr>
                    <tr><td>البريد الإلكتروني</td><td><?php echo COMPANY_EMAIL; ?></td></tr>
                </table>
            </div>

            <div class="section">
                <div class="section-title">بيانات العميل (المستأجر)</div>
                <table>
                    <tr><td>الاسم الكامل</td><td><?php echo $rental['customer_name']; ?></td></tr>
                    <tr><td>رقم الهوية</td><td><?php echo $rental['id_number']; ?></td></tr>
                    <tr><td>الهاتف</td><td><?php echo $rental['customer_phone']; ?></td></tr>
                    <tr><td>البريد الإلكتروني</td><td><?php echo $rental['customer_email'] ?? '-'; ?></td></tr>
                    <tr><td>رخصة القيادة</td><td><?php echo $rental['driver_license']; ?></td></tr>
                    <tr><td>صلاحية الرخصة</td><td><?php echo formatDate($rental['license_expiry'], 'd/m/Y'); ?></td></tr>
                </table>
            </div>

            <div class="section">
                <div class="section-title">بيانات السيارة</div>
                <table>
                    <tr><td>رقم اللوحة</td><td><?php echo $rental['plate_number']; ?></td></tr>
                    <tr><td>الماركة</td><td><?php echo $rental['brand']; ?></td></tr>
                    <tr><td>الموديل</td><td><?php echo $rental['model']; ?></td></tr>
                    <tr><td>سنة الصنع</td><td><?php echo $rental['year']; ?></td></tr>
                    <tr><td>اللون</td><td><?php echo $rental['color']; ?></td></tr>
                    <tr><td>عدد الركاب</td><td><?php echo $rental['seats']; ?> راكب</td></tr>
                </table>
            </div>

            <div class="section">
                <div class="section-title">تفاصيل الإيجار</div>
                <table>
                    <tr><td>تاريخ الاستلام</td><td><?php echo formatDate($rental['start_date'], 'd/m/Y'); ?></td></tr>
                    <tr><td>تاريخ التسليم</td><td><?php echo formatDate($rental['end_date'], 'd/m/Y'); ?></td></tr>
                    <tr><td>مكان الاستلام</td><td><?php echo $rental['pickup_location']; ?></td></tr>
                    <tr><td>مكان التسليم</td><td><?php echo $rental['return_location']; ?></td></tr>
                    <tr><td>عدد الأيام</td><td><?php echo $rental['total_days']; ?> يوم</td></tr>
                    <tr><td>قراءة العداد عند الاستلام</td><td><?php echo $rental['mileage_start'] ?? '-'; ?> كم</td></tr>
                    <tr><td>مستوى الوقود</td><td><?php echo $rental['fuel_level_start']; ?></td></tr>
                </table>
            </div>

            <div class="section">
                <div class="section-title">التفاصيل المالية</div>
                <table>
                    <tr><td>الأجرة اليومية</td><td><?php echo formatCurrency($rental['daily_rate']); ?></td></tr>
                    <tr><td>المجموع الجزئي</td><td><?php echo formatCurrency($rental['subtotal']); ?></td></tr>
                    <?php if ($rental['discount_amount'] > 0): ?>
                    <tr><td>الخصم (<?php echo $rental['discount_reason']; ?>)</td><td>-<?php echo formatCurrency($rental['discount_amount']); ?></td></tr>
                    <?php endif; ?>
                    <tr><td>الضريبة (<?php echo TAX_RATE; ?>%)</td><td><?php echo formatCurrency($rental['tax_amount']); ?></td></tr>
                    <tr style="background: #fff3cd; font-weight: bold; font-size: 14pt;">
                        <td>الإجمالي النهائي</td>
                        <td><?php echo formatCurrency($rental['total_amount']); ?></td>
                    </tr>
                </table>
            </div>

            <div class="section">
                <div class="section-title">الشروط والأحكام</div>
                <div class="terms">
                    <ol>
                        <li>يلتزم المستأجر بإرجاع السيارة في الموعد والمكان المحددين.</li>
                        <li>غرامة التأخير: <?php echo formatCurrency(LATE_FEE_PER_DAY); ?> عن كل يوم تأخير.</li>
                        <li>يجب إرجاع السيارة بنفس مستوى الوقود عند الاستلام.</li>
                        <li>المستأجر مسؤول عن أي أضرار أو مخالفات مرورية خلال فترة الإيجار.</li>
                        <li>يمنع استخدام السيارة في أغراض غير قانونية أو لنقل الركاب بأجر.</li>
                        <li>السيارة مؤمنة ضد الغير فقط خلال فترة العقد.</li>
                        <li>يجب الإبلاغ فوراً عن أي حادث أو عطل.</li>
                        <li>يمنع التدخين داخل السيارة.</li>
                    </ol>
                </div>
            </div>

            <div class="signature-box">
                <div class="signature">
                    <p><strong>توقيع المؤجر</strong></p>
                    <p style="margin-top: 40px;">__________________</p>
                    <p><?php echo COMPANY_NAME; ?></p>
                </div>
                <div class="signature">
                    <p><strong>توقيع المستأجر</strong></p>
                    <p style="margin-top: 40px;">__________________</p>
                    <p><?php echo $rental['customer_name']; ?></p>
                </div>
            </div>

            <div class="footer">
                <p><?php echo COMPANY_NAME; ?> | <?php echo COMPANY_ADDRESS; ?></p>
                <p>هاتف: <?php echo COMPANY_PHONE; ?> | بريد: <?php echo COMPANY_EMAIL; ?></p>
                <p style="font-size: 9pt; margin-top: 10px;">تم إنشاء العقد إلكترونياً بتاريخ <?php echo date('Y-m-d H:i'); ?></p>
            </div>
        </body>
        </html>
        <?php
        return ob_get_clean();
    }

    /**
     * Generate PDF from HTML
     */
    private function generatePDF($html, $rentalNumber) {
        // For now, save as HTML. Later integrate TCPDF or mPDF
        $filename = 'contract_' . $rentalNumber . '.html';
        $filepath = CONTRACTS_UPLOAD_DIR . '/' . $filename;
        
        file_put_contents($filepath, $html);
        
        return $filepath;
    }
}
?>