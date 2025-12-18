<?php

/**
 * Email Notification Class
 * Simple email sender for notifications
 */
class Email {
    private $from;
    private $fromName;
    
    public function __construct() {
        $this->from = COMPANY_EMAIL;
        $this->fromName = SITE_NAME;
    }
    
    /**
     * Send email
     */
    public function send($to, $subject, $body, $isHTML = true) {
        $headers = [];
        $headers[] = 'From: ' . $this->fromName . ' <' . $this->from . '>';
        $headers[] = 'Reply-To: ' . $this->from;
        $headers[] = 'X-Mailer: PHP/' . phpversion();
        
        if ($isHTML) {
            $headers[] = 'MIME-Version: 1.0';
            $headers[] = 'Content-Type: text/html; charset=UTF-8';
        }
        
        return mail($to, $subject, $body, implode("\r\n", $headers));
    }
    
    /**
     * Send booking confirmation
     */
    public function sendBookingConfirmation($bookingData) {
        $subject = 'تأكيد الحجز - ' . SITE_NAME;
        $body = '
        <html dir="rtl">
        <body style="font-family: Arial; direction: rtl;">
            <div style="background: #f5f5f5; padding: 20px;">
                <div style="background: white; padding: 30px; border-radius: 10px; max-width: 600px; margin: 0 auto;">
                    <h2 style="color: #FF5722; text-align: center;">' . SITE_NAME . '</h2>
                    <h3>شكراً لحجزك!</h3>
                    <p>عزيزي/تي <strong>' . $bookingData['customer_name'] . '</strong></p>
                    <p>تم استلام طلب الحجز بنجاح. سيتم التواصل معك قريباً لتأكيد الحجز.</p>
                    
                    <div style="background: #f9f9f9; padding: 20px; border-radius: 10px; margin: 20px 0;">
                        <h4 style="color: #FF5722;">تفاصيل الحجز:</h4>
                        <p><strong>رقم الحجز:</strong> ' . $bookingData['booking_number'] . '</p>
                        <p><strong>السيارة:</strong> ' . $bookingData['car'] . '</p>
                        <p><strong>الفترة:</strong> ' . $bookingData['period'] . '</p>
                    </div>
                    
                    <p>للاستفسار اتصل على: ' . COMPANY_PHONE . '</p>
                    
                    <hr>
                    <p style="text-align: center; color: #666; font-size: 12px;">
                        ' . SITE_NAME . ' | ' . COMPANY_ADDRESS . '<br>
                        ' . COMPANY_PHONE . ' | ' . COMPANY_EMAIL . '
                    </p>
                </div>
            </div>
        </body>
        </html>';
        
        return $this->send($bookingData['email'], $subject, $body, true);
    }
    
    /**
     * Send rental reminder
     */
    public function sendRentalReminder($rentalData) {
        $subject = 'تذكير بموعد التسليم - ' . SITE_NAME;
        $body = '
        <html dir="rtl">
        <body style="font-family: Arial; direction: rtl;">
            <div style="background: #f5f5f5; padding: 20px;">
                <div style="background: white; padding: 30px; border-radius: 10px; max-width: 600px; margin: 0 auto;">
                    <h2 style="color: #FF5722; text-align: center;">تذكير</h2>
                    <p>عزيزي/تي <strong>' . $rentalData['customer_name'] . '</strong></p>
                    <p>نذكرك بأن موعد تسليم السيارة قريب:</p>
                    
                    <div style="background: #fff3cd; padding: 20px; border-radius: 10px; margin: 20px 0;">
                        <p><strong>رقم الحجز:</strong> ' . $rentalData['rental_number'] . '</p>
                        <p><strong>السيارة:</strong> ' . $rentalData['car'] . '</p>
                        <p><strong>موعد التسليم:</strong> ' . $rentalData['end_date'] . '</p>
                    </div>
                    
                    <p>الرجاء الالتزام بالموعد لتجنب أي غرامات.</p>
                    
                    <p>شكراً لثقتك 🚗</p>
                </div>
            </div>
        </body>
        </html>';
        
        return $this->send($rentalData['email'], $subject, $body, true);
    }
}
?>