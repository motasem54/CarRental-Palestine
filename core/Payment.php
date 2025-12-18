<?php
/**
 * Payment Management Class
 * 💰 إدارة المدفوعات
 */

class Payment {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Add payment
     */
    public function addPayment($data) {
        try {
            $this->db->beginTransaction();

            // Generate payment number
            $paymentNumber = generatePaymentNumber();

            // Insert payment
            $sql = "INSERT INTO payments (
                payment_number, rental_id, amount, payment_method,
                payment_date, reference_number, notes, created_by, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())";

            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute([
                $paymentNumber,
                $data['rental_id'],
                $data['amount'],
                $data['payment_method'],
                $data['payment_date'],
                $data['reference_number'] ?? null,
                $data['notes'] ?? null,
                $_SESSION['user_id'] ?? null
            ]);

            if ($result) {
                $paymentId = $this->db->lastInsertId();

                // Update rental payment status
                $this->updateRentalPayment($data['rental_id'], $data['amount']);

                $this->db->commit();

                return [
                    'success' => true,
                    'payment_id' => $paymentId,
                    'payment_number' => $paymentNumber,
                    'message' => 'تم تسجيل الدفعة بنجاح'
                ];
            }

            throw new Exception('فشل في تسجيل الدفعة');

        } catch (Exception $e) {
            $this->db->rollBack();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Update rental payment
     */
    private function updateRentalPayment($rentalId, $amount) {
        // Get current rental
        $stmt = $this->db->prepare("SELECT total_amount, paid_amount FROM rentals WHERE id = ?");
        $stmt->execute([$rentalId]);
        $rental = $stmt->fetch();

        $newPaidAmount = $rental['paid_amount'] + $amount;
        $remaining = $rental['total_amount'] - $newPaidAmount;

        // Determine payment status
        $paymentStatus = 'pending';
        if ($remaining <= 0) {
            $paymentStatus = 'paid';
        } elseif ($newPaidAmount > 0) {
            $paymentStatus = 'partial';
        }

        // Update rental
        $updateStmt = $this->db->prepare("
            UPDATE rentals SET
                paid_amount = ?,
                remaining_amount = ?,
                payment_status = ?,
                updated_at = NOW()
            WHERE id = ?
        ");

        return $updateStmt->execute([$newPaidAmount, max(0, $remaining), $paymentStatus, $rentalId]);
    }

    /**
     * Get payments for rental
     */
    public function getRentalPayments($rentalId) {
        $stmt = $this->db->prepare("
            SELECT p.*, u.full_name as created_by_name
            FROM payments p
            LEFT JOIN users u ON p.created_by = u.id
            WHERE p.rental_id = ?
            ORDER BY p.payment_date DESC, p.created_at DESC
        ");
        $stmt->execute([$rentalId]);
        return $stmt->fetchAll();
    }

    /**
     * Get all payments with filters
     */
    public function getPayments($filters = [], $page = 1, $perPage = ITEMS_PER_PAGE) {
        $where = [];
        $params = [];

        if (!empty($filters['rental_id'])) {
            $where[] = "p.rental_id = ?";
            $params[] = $filters['rental_id'];
        }

        if (!empty($filters['payment_method'])) {
            $where[] = "p.payment_method = ?";
            $params[] = $filters['payment_method'];
        }

        if (!empty($filters['date_from'])) {
            $where[] = "p.payment_date >= ?";
            $params[] = $filters['date_from'];
        }

        if (!empty($filters['date_to'])) {
            $where[] = "p.payment_date <= ?";
            $params[] = $filters['date_to'];
        }

        $whereClause = !empty($where) ? "WHERE " . implode(' AND ', $where) : "";

        // Get total
        $countSql = "SELECT COUNT(*) as total FROM payments p $whereClause";
        $countStmt = $this->db->prepare($countSql);
        $countStmt->execute($params);
        $total = $countStmt->fetch()['total'];

        // Get paginated results
        $offset = ($page - 1) * $perPage;
        $sql = "
            SELECT p.*, r.rental_number, 
                   c.full_name as customer_name,
                   u.full_name as created_by_name
            FROM payments p
            JOIN rentals r ON p.rental_id = r.id
            JOIN customers c ON r.customer_id = c.id
            LEFT JOIN users u ON p.created_by = u.id
            $whereClause
            ORDER BY p.payment_date DESC, p.created_at DESC
            LIMIT $perPage OFFSET $offset
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return [
            'data' => $stmt->fetchAll(),
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
            'pages' => ceil($total / $perPage)
        ];
    }

    /**
     * Get payment statistics
     */
    public function getStatistics($dateFrom = null, $dateTo = null) {
        $where = [];
        $params = [];

        if ($dateFrom) {
            $where[] = "payment_date >= ?";
            $params[] = $dateFrom;
        }

        if ($dateTo) {
            $where[] = "payment_date <= ?";
            $params[] = $dateTo;
        }

        $whereClause = !empty($where) ? "WHERE " . implode(' AND ', $where) : "";

        $sql = "
            SELECT 
                COUNT(*) as total_payments,
                SUM(amount) as total_amount,
                AVG(amount) as average_amount,
                payment_method,
                COUNT(*) as method_count
            FROM payments
            $whereClause
            GROUP BY payment_method
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }
}
?>