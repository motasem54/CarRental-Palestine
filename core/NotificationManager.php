<?php

/**
 * Notification Manager
 * Send in-app notifications to users
 */
class NotificationManager {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * Create notification
     */
    public function create($userId, $title, $message, $type = 'info', $link = null) {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO notifications (user_id, title, message, type, link, created_at)
                VALUES (?, ?, ?, ?, ?, NOW())
            ");
            
            return $stmt->execute([$userId, $title, $message, $type, $link]);
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Send to all admins
     */
    public function notifyAdmins($title, $message, $type = 'info') {
        $stmt = $this->db->query("SELECT id FROM users WHERE role = 'admin'");
        $admins = $stmt->fetchAll();
        
        foreach ($admins as $admin) {
            $this->create($admin['id'], $title, $message, $type);
        }
    }
    
    /**
     * Notify about new booking
     */
    public function notifyNewBooking($bookingId) {
        $this->notifyAdmins(
            'حجز جديد',
            'تم استلام حجز جديد من الموقع. يرجى المراجعة.',
            'info'
        );
    }
    
    /**
     * Notify about payment
     */
    public function notifyPaymentReceived($rentalId, $amount) {
        $this->notifyAdmins(
            'دفعة جديدة',
            'تم استلام دفعة بقيمة ' . formatCurrency($amount),
            'success'
        );
    }
    
    /**
     * Notify about rental due
     */
    public function notifyRentalDue($rentalNumber) {
        $this->notifyAdmins(
            'تذكير: حجز قريب الانتهاء',
            'الحجز ' . $rentalNumber . ' سينتهي قريباً',
            'warning'
        );
    }
    
    /**
     * Notify about overdue rental
     */
    public function notifyOverdueRental($rentalNumber) {
        $this->notifyAdmins(
            'تنبيه: حجز متأخر',
            'الحجز ' . $rentalNumber . ' متأخر عن موعد التسليم',
            'error'
        );
    }
    
    /**
     * Get unread count
     */
    public function getUnreadCount($userId) {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) FROM notifications 
            WHERE user_id = ? AND is_read = 0
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchColumn();
    }
}
?>