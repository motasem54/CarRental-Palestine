<?php
/**
 * Rental Management Class
 * 🚗 إدارة الحجوزات والإيجارات
 */

class Rental {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Create new rental
     */
    public function create($data) {
        try {
            $this->db->beginTransaction();

            // Generate rental number
            $rentalNumber = generateRentalNumber();

            // Calculate total days
            $totalDays = calculateDays($data['start_date'], $data['end_date']);

            // Calculate amounts
            $dailyRate = $data['daily_rate'];
            $subtotal = $totalDays * $dailyRate;
            $discountAmount = $data['discount_amount'] ?? 0;
            $taxAmount = ($subtotal - $discountAmount) * (TAX_RATE / 100);
            $totalAmount = $subtotal - $discountAmount + $taxAmount;

            // Insert rental
            $sql = "INSERT INTO rentals (
                rental_number, car_id, customer_id, start_date, end_date,
                pickup_location, return_location, total_days, daily_rate,
                subtotal, discount_amount, discount_reason, tax_amount,
                total_amount, remaining_amount, payment_status, status,
                mileage_start, fuel_level_start, notes, created_by, created_at
            ) VALUES (
                ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', 'pending',
                ?, 'full', ?, ?, NOW()
            )";

            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute([
                $rentalNumber,
                $data['car_id'],
                $data['customer_id'],
                $data['start_date'],
                $data['end_date'],
                $data['pickup_location'] ?? DEFAULT_CITY,
                $data['return_location'] ?? DEFAULT_CITY,
                $totalDays,
                $dailyRate,
                $subtotal,
                $discountAmount,
                $data['discount_reason'] ?? null,
                $taxAmount,
                $totalAmount,
                $totalAmount, // Initially all remaining
                $data['mileage_start'] ?? null,
                $data['notes'] ?? null,
                $_SESSION['user_id'] ?? null
            ]);

            if ($result) {
                $rentalId = $this->db->lastInsertId();

                // Update car status to reserved
                $this->updateCarStatus($data['car_id'], 'reserved');

                // Add loyalty points to customer
                $this->addLoyaltyPoints($data['customer_id'], $totalAmount, $rentalId);

                $this->db->commit();

                return [
                    'success' => true,
                    'rental_id' => $rentalId,
                    'rental_number' => $rentalNumber,
                    'message' => 'تم إنشاء الحجز بنجاح'
                ];
            }

            throw new Exception('فشل في إنشاء الحجز');

        } catch (Exception $e) {
            $this->db->rollBack();
            error_log('Rental creation error: ' . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Update rental
     */
    public function update($id, $data) {
        try {
            // Recalculate if dates changed
            if (isset($data['start_date']) && isset($data['end_date'])) {
                $totalDays = calculateDays($data['start_date'], $data['end_date']);
                $subtotal = $totalDays * $data['daily_rate'];
                $discountAmount = $data['discount_amount'] ?? 0;
                $taxAmount = ($subtotal - $discountAmount) * (TAX_RATE / 100);
                $totalAmount = $subtotal - $discountAmount + $taxAmount;

                $data['total_days'] = $totalDays;
                $data['subtotal'] = $subtotal;
                $data['tax_amount'] = $taxAmount;
                $data['total_amount'] = $totalAmount;
            }

            $fields = [];
            $values = [];

            foreach ($data as $key => $value) {
                $fields[] = "$key = ?";
                $values[] = $value;
            }

            $values[] = $id;

            $sql = "UPDATE rentals SET " . implode(', ', $fields) . ", updated_at = NOW() WHERE id = ?";
            $stmt = $this->db->prepare($sql);

            if ($stmt->execute($values)) {
                return ['success' => true, 'message' => 'تم تحديث الحجز بنجاح'];
            }

            return ['success' => false, 'message' => 'فشل في التحديث'];

        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Complete rental (return car)
     */
    public function complete($id, $returnData) {
        try {
            $this->db->beginTransaction();

            // Get rental details
            $rental = $this->getRental($id);
            if (!$rental) {
                throw new Exception('الحجز غير موجود');
            }

            // Calculate late fee if any
            $penaltyAmount = 0;
            if (isset($returnData['actual_return_date'])) {
                $lateFee = calculateLateFee($rental['end_date'], $returnData['actual_return_date']);
                if ($lateFee > 0) {
                    $penaltyAmount = $lateFee;
                    // Add penalty record
                    $this->addPenalty($id, 'late_return', $lateFee, 'تأخير في التسليم');
                }
            }

            // Update rental
            $stmt = $this->db->prepare("
                UPDATE rentals SET
                    actual_return_date = ?,
                    mileage_end = ?,
                    fuel_level_end = ?,
                    penalty_amount = penalty_amount + ?,
                    total_amount = total_amount + ?,
                    status = 'completed',
                    updated_at = NOW()
                WHERE id = ?
            ");

            $stmt->execute([
                $returnData['actual_return_date'],
                $returnData['mileage_end'],
                $returnData['fuel_level_end'],
                $penaltyAmount,
                $penaltyAmount,
                $id
            ]);

            // Update car status
            $this->updateCarStatus($rental['car_id'], 'available');

            // Update customer stats
            $this->updateCustomerStats($rental['customer_id']);

            $this->db->commit();

            return ['success' => true, 'message' => 'تم إغلاق الحجز بنجاح'];

        } catch (Exception $e) {
            $this->db->rollBack();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Get rental by ID
     */
    public function getRental($id) {
        $stmt = $this->db->prepare("
            SELECT r.*, 
                   c.plate_number, c.brand, c.model, c.year,
                   cu.full_name as customer_name, cu.phone as customer_phone
            FROM rentals r
            JOIN cars c ON r.car_id = c.id
            JOIN customers cu ON r.customer_id = cu.id
            WHERE r.id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    /**
     * Get all rentals with filters
     */
    public function getRentals($filters = [], $page = 1, $perPage = ITEMS_PER_PAGE) {
        $where = [];
        $params = [];

        if (!empty($filters['status'])) {
            $where[] = "r.status = ?";
            $params[] = $filters['status'];
        }

        if (!empty($filters['car_id'])) {
            $where[] = "r.car_id = ?";
            $params[] = $filters['car_id'];
        }

        if (!empty($filters['customer_id'])) {
            $where[] = "r.customer_id = ?";
            $params[] = $filters['customer_id'];
        }

        if (!empty($filters['date_from'])) {
            $where[] = "r.start_date >= ?";
            $params[] = $filters['date_from'];
        }

        if (!empty($filters['date_to'])) {
            $where[] = "r.end_date <= ?";
            $params[] = $filters['date_to'];
        }

        $whereClause = !empty($where) ? "WHERE " . implode(' AND ', $where) : "";

        // Get total count
        $countSql = "SELECT COUNT(*) as total FROM rentals r $whereClause";
        $countStmt = $this->db->prepare($countSql);
        $countStmt->execute($params);
        $total = $countStmt->fetch()['total'];

        // Get paginated results
        $offset = ($page - 1) * $perPage;
        $sql = "
            SELECT r.*, 
                   c.plate_number, c.brand, c.model,
                   cu.full_name as customer_name, cu.phone as customer_phone
            FROM rentals r
            JOIN cars c ON r.car_id = c.id
            JOIN customers cu ON r.customer_id = cu.id
            $whereClause
            ORDER BY r.created_at DESC
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
     * Helper: Update car status
     */
    private function updateCarStatus($carId, $status) {
        $stmt = $this->db->prepare("UPDATE cars SET status = ? WHERE id = ?");
        return $stmt->execute([$status, $carId]);
    }

    /**
     * Helper: Add loyalty points
     */
    private function addLoyaltyPoints($customerId, $amount, $rentalId) {
        $points = floor($amount * POINTS_PER_SHEKEL);

        $stmt = $this->db->prepare("
            INSERT INTO customer_points (customer_id, rental_id, points, type, description, created_at)
            VALUES (?, ?, ?, 'earned', 'نقاط من حجز', NOW())
        ");
        $stmt->execute([$customerId, $rentalId, $points]);

        // Update customer total points
        $updateStmt = $this->db->prepare("
            UPDATE customers SET 
                loyalty_points = loyalty_points + ?,
                total_bookings = total_bookings + 1
            WHERE id = ?
        ");
        return $updateStmt->execute([$points, $customerId]);
    }

    /**
     * Helper: Add penalty
     */
    private function addPenalty($rentalId, $type, $amount, $description) {
        $stmt = $this->db->prepare("
            INSERT INTO penalties (rental_id, penalty_type, amount, description, status, created_at)
            VALUES (?, ?, ?, ?, 'pending', NOW())
        ");
        return $stmt->execute([$rentalId, $type, $amount, $description]);
    }

    /**
     * Helper: Update customer stats
     */
    private function updateCustomerStats($customerId) {
        // Update loyalty level based on points
        $stmt = $this->db->prepare("SELECT loyalty_points FROM customers WHERE id = ?");
        $stmt->execute([$customerId]);
        $customer = $stmt->fetch();

        $level = 'bronze';
        if ($customer['loyalty_points'] >= PLATINUM_MIN_POINTS) {
            $level = 'platinum';
        } elseif ($customer['loyalty_points'] >= GOLD_MIN_POINTS) {
            $level = 'gold';
        } elseif ($customer['loyalty_points'] >= SILVER_MIN_POINTS) {
            $level = 'silver';
        }

        $updateStmt = $this->db->prepare("UPDATE customers SET loyalty_level = ? WHERE id = ?");
        return $updateStmt->execute([$level, $customerId]);
    }
}
?>