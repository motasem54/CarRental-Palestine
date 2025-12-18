<?php
/**
 * Loyalty Program Class
 * ⭐ نظام الولاء
 */

class Loyalty {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Get customer loyalty level
     */
    public function getCustomerLevel($customerId) {
        $stmt = $this->db->prepare("
            SELECT loyalty_level, loyalty_points 
            FROM customers 
            WHERE id = ?
        ");
        $stmt->execute([$customerId]);
        return $stmt->fetch();
    }

    /**
     * Calculate points for amount
     */
    public function calculatePoints($amount, $loyaltyLevel = 'bronze') {
        $stmt = $this->db->prepare("
            SELECT points_per_shekel 
            FROM loyalty_programs 
            WHERE level = ?
        ");
        $stmt->execute([$loyaltyLevel]);
        $program = $stmt->fetch();

        $multiplier = $program['points_per_shekel'] ?? POINTS_PER_SHEKEL;
        return floor($amount * $multiplier);
    }

    /**
     * Get loyalty discount percentage
     */
    public function getDiscountPercentage($loyaltyLevel) {
        $stmt = $this->db->prepare("
            SELECT discount_percentage 
            FROM loyalty_programs 
            WHERE level = ?
        ");
        $stmt->execute([$loyaltyLevel]);
        $program = $stmt->fetch();

        return $program['discount_percentage'] ?? 0;
    }

    /**
     * Get available rewards for customer
     */
    public function getAvailableRewards($customerId) {
        $customer = $this->getCustomerLevel($customerId);
        
        $stmt = $this->db->prepare("
            SELECT * FROM rewards 
            WHERE is_active = 1 
            AND points_required <= ?
            AND (loyalty_level IS NULL OR loyalty_level = ?)
            ORDER BY points_required ASC
        ");
        $stmt->execute([
            $customer['loyalty_points'],
            $customer['loyalty_level']
        ]);

        return $stmt->fetchAll();
    }

    /**
     * Redeem reward
     */
    public function redeemReward($customerId, $rewardId, $rentalId = null) {
        try {
            $this->db->beginTransaction();

            // Get reward details
            $stmt = $this->db->prepare("SELECT * FROM rewards WHERE id = ? AND is_active = 1");
            $stmt->execute([$rewardId]);
            $reward = $stmt->fetch();

            if (!$reward) {
                throw new Exception('المكافأة غير متاحة');
            }

            // Check customer points
            $customer = $this->getCustomerLevel($customerId);
            if ($customer['loyalty_points'] < $reward['points_required']) {
                throw new Exception('نقاط غير كافية');
            }

            // Deduct points
            $stmt = $this->db->prepare("
                INSERT INTO customer_points 
                (customer_id, rental_id, points, type, description, created_at)
                VALUES (?, ?, ?, 'redeemed', ?, NOW())
            ");
            $stmt->execute([
                $customerId,
                $rentalId,
                -$reward['points_required'],
                'استبدال مكافأة: ' . $reward['name']
            ]);

            // Update customer total
            $stmt = $this->db->prepare("
                UPDATE customers 
                SET loyalty_points = loyalty_points - ? 
                WHERE id = ?
            ");
            $stmt->execute([$reward['points_required'], $customerId]);

            // Record redemption
            $stmt = $this->db->prepare("
                INSERT INTO reward_redemptions 
                (customer_id, reward_id, rental_id, points_used, status, created_at)
                VALUES (?, ?, ?, ?, 'applied', NOW())
            ");
            $stmt->execute([
                $customerId,
                $rewardId,
                $rentalId,
                $reward['points_required']
            ]);

            // Update reward count
            $stmt = $this->db->prepare("
                UPDATE rewards 
                SET redemption_count = redemption_count + 1 
                WHERE id = ?
            ");
            $stmt->execute([$rewardId]);

            $this->db->commit();

            return [
                'success' => true,
                'reward' => $reward,
                'message' => 'تم استبدال المكافأة بنجاح'
            ];

        } catch (Exception $e) {
            $this->db->rollBack();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Get customer point history
     */
    public function getPointHistory($customerId, $limit = 50) {
        $stmt = $this->db->prepare("
            SELECT cp.*, r.rental_number
            FROM customer_points cp
            LEFT JOIN rentals r ON cp.rental_id = r.id
            WHERE cp.customer_id = ?
            ORDER BY cp.created_at DESC
            LIMIT ?
        ");
        $stmt->execute([$customerId, $limit]);
        return $stmt->fetchAll();
    }
}
?>