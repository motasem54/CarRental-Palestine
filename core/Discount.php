<?php
/**
 * Discount Management Class
 * 💸 نظام الخصومات
 */

class Discount {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Validate discount code
     */
    public function validateCode($code) {
        $stmt = $this->db->prepare("
            SELECT * FROM discounts 
            WHERE code = ? 
            AND is_active = 1
            AND (start_date IS NULL OR start_date <= CURDATE())
            AND (end_date IS NULL OR end_date >= CURDATE())
            AND (max_uses IS NULL OR used_count < max_uses)
        ");
        $stmt->execute([$code]);
        $discount = $stmt->fetch();

        if (!$discount) {
            return ['valid' => false, 'message' => 'كود خصم غير صالح'];
        }

        return ['valid' => true, 'discount' => $discount];
    }

    /**
     * Calculate discount amount
     */
    public function calculateDiscount($discountId, $subtotal, $days = 1) {
        $stmt = $this->db->prepare("SELECT * FROM discounts WHERE id = ?");
        $stmt->execute([$discountId]);
        $discount = $stmt->fetch();

        if (!$discount) {
            return 0;
        }

        $amount = 0;

        switch ($discount['type']) {
            case 'percentage':
                $amount = ($subtotal * $discount['value']) / 100;
                break;
            
            case 'fixed':
                $amount = $discount['value'];
                break;
            
            case 'duration':
                // Duration-based discount (e.g., rent 7 days get X% off)
                if ($days >= $discount['min_days']) {
                    $amount = ($subtotal * $discount['value']) / 100;
                }
                break;
        }

        return min($amount, $subtotal); // Don't exceed subtotal
    }

    /**
     * Apply discount code
     */
    public function applyCode($code) {
        try {
            $validation = $this->validateCode($code);
            
            if (!$validation['valid']) {
                return $validation;
            }

            $discount = $validation['discount'];

            // Increment usage count
            $stmt = $this->db->prepare("
                UPDATE discounts 
                SET used_count = used_count + 1 
                WHERE id = ?
            ");
            $stmt->execute([$discount['id']]);

            return [
                'valid' => true,
                'discount' => $discount,
                'message' => 'تم تطبيق الخصم بنجاح'
            ];

        } catch (Exception $e) {
            return ['valid' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Get active discounts
     */
    public function getActiveDiscounts() {
        $stmt = $this->db->prepare("
            SELECT * FROM discounts 
            WHERE is_active = 1
            AND (start_date IS NULL OR start_date <= CURDATE())
            AND (end_date IS NULL OR end_date >= CURDATE())
            ORDER BY value DESC
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Create discount
     */
    public function create($data) {
        try {
            $sql = "INSERT INTO discounts (
                name, code, type, value, min_days, max_uses,
                start_date, end_date, description, is_active, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1, NOW())";

            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute([
                $data['name'],
                $data['code'] ?? null,
                $data['type'],
                $data['value'],
                $data['min_days'] ?? null,
                $data['max_uses'] ?? null,
                $data['start_date'] ?? null,
                $data['end_date'] ?? null,
                $data['description'] ?? null
            ]);

            if ($result) {
                return [
                    'success' => true,
                    'discount_id' => $this->db->lastInsertId(),
                    'message' => 'تم إضافة الخصم بنجاح'
                ];
            }

            return ['success' => false, 'message' => 'فشل في الإضافة'];

        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}
?>