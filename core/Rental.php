<?php
/**
 * Rental Management Class
 * 🚗 إدارة الحجوزات والإيجارات
 */

class Rental {
    private $db;

    public function __construct($db = null) {
        if ($db) {
            $this->db = $db;
        } else {
            $this->db = Database::getInstance()->getConnection();
        }
    }

    /**
     * Create new rental (main method used in rental_add.php)
     */
    public function createRental($data) {
        try {
            $this->db->beginTransaction();

            // Generate rental number
            $rentalNumber = $this->generateRentalNumber();

            // Get car details to calculate pricing
            $carStmt = $this->db->prepare("SELECT * FROM cars WHERE id = ?");
            $carStmt->execute([$data['car_id']]);
            $car = $carStmt->fetch();

            if (!$car) {
                throw new Exception('السيارة غير موجودة');
            }

            // Calculate total days
            $startDate = new DateTime($data['start_date']);
            $endDate = new DateTime($data['end_date']);
            $interval = $startDate->diff($endDate);
            $totalDays = $interval->days + 1;

            // Calculate amounts
            $dailyRate = $data['custom_rate'] ?? $car['daily_rate'];
            $baseAmount = $totalDays * $dailyRate;
            
            // Apply discount if code provided
            $discountAmount = 0;
            $discountCode = '';
            if (!empty($data['discount_code'])) {
                $discount = $this->applyDiscountCode($data['discount_code'], $baseAmount);
                $discountAmount = $discount['amount'];
                $discountCode = $data['discount_code'];
            }

            $subtotal = $baseAmount - $discountAmount;
            $taxAmount = $subtotal * (TAX_RATE / 100);
            $insuranceAmount = $car['insurance_amount'] ?? 0;
            $totalAmount = $subtotal + $taxAmount + $insuranceAmount;

            // Insert rental
            $sql = "INSERT INTO rentals (
                rental_number, car_id, customer_id, start_date, end_date,
                pickup_location, return_location, total_days, daily_rate,
                base_amount, discount_amount, discount_code, tax_amount,
                insurance_amount, total_amount, paid_amount, remaining_amount,
                payment_status, status, notes, created_by, created_at
            ) VALUES (
                ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, ?, 'pending', 'pending',
                ?, ?, NOW()
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
                $baseAmount,
                $discountAmount,
                $discountCode,
                $taxAmount,
                $insuranceAmount,
                $totalAmount,
                $totalAmount, // Initially all remaining
                $data['notes'] ?? null,
                $data['user_id'] ?? $_SESSION['user_id'] ?? null
            ]);

            if ($result) {
                $rentalId = $this->db->lastInsertId();

                // Update car status to reserved
                $this->updateCarStatus($data['car_id'], 'reserved');

                // Add loyalty points to customer
                $this->addLoyaltyPoints($data['customer_id'], $totalAmount, $rentalId);

                $this->db->commit();

                return $rentalId;
            }

            throw new Exception('فشل في إنشاء الحجز');

        } catch (Exception $e) {
            $this->db->rollBack();
            error_log('Rental creation error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Alternative create method (backward compatibility)
     */
    public function create($data) {
        return $this->createRental($data);
    }

    /**
     * Update rental
     */
    public function update($id, $data) {
        try {
            // Recalculate if dates changed
            if (isset($data['start_date']) && isset($data['end_date'])) {
                $startDate = new DateTime($data['start_date']);
                $endDate = new DateTime($data['end_date']);
                $interval = $startDate->diff($endDate);
                $totalDays = $interval->days + 1;
                
                $baseAmount = $totalDays * $data['daily_rate'];
                $discountAmount = $data['discount_amount'] ?? 0;
                $taxAmount = ($baseAmount - $discountAmount) * (TAX_RATE / 100);
                $totalAmount = $baseAmount - $discountAmount + $taxAmount;

                $data['total_days'] = $totalDays;
                $data['base_amount'] = $baseAmount;
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
                $endDate = new DateTime($rental['end_date']);
                $actualReturn = new DateTime($returnData['actual_return_date']);
                
                if ($actualReturn > $endDate) {
                    $lateDays = $actualReturn->diff($endDate)->days;
                    $penaltyAmount = $lateDays * (LATE_FEE_PER_DAY ?? 50);
                    
                    // Add penalty record
                    $this->addPenalty($id, 'late_return', $penaltyAmount, "تأخير في التسليم: $lateDays يوم");
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
                $returnData['mileage_end'] ?? null,
                $returnData['fuel_level_end'] ?? 'full',
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
     * Generate unique rental number
     */
    private function generateRentalNumber() {
        $prefix = 'R-' . date('Ym') . '-';
        $stmt = $this->db->query("SELECT COUNT(*) as count FROM rentals WHERE rental_number LIKE '{$prefix}%'");
        $count = $stmt->fetch()['count'] + 1;
        return $prefix . str_pad($count, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Apply discount code
     */
    private function applyDiscountCode($code, $amount) {
        $stmt = $this->db->prepare("
            SELECT * FROM discount_codes 
            WHERE code = ? AND status = 'active' 
            AND (expiry_date IS NULL OR expiry_date >= CURDATE())
            AND (usage_limit IS NULL OR usage_count < usage_limit)
        ");
        $stmt->execute([$code]);
        $discount = $stmt->fetch();

        if (!$discount) {
            return ['amount' => 0];
        }

        // Calculate discount
        $discountAmount = 0;
        if ($discount['discount_type'] === 'percentage') {
            $discountAmount = $amount * ($discount['discount_value'] / 100);
        } else {
            $discountAmount = $discount['discount_value'];
        }

        // Update usage count
        $this->db->prepare("UPDATE discount_codes SET usage_count = usage_count + 1 WHERE id = ?")
                 ->execute([$discount['id']]);

        return ['amount' => $discountAmount];
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
        $points = floor($amount * (POINTS_PER_SHEKEL ?? 1));

        $stmt = $this->db->prepare("
            INSERT INTO customer_points (customer_id, rental_id, points, type, description, created_at)
            VALUES (?, ?, ?, 'earned', 'نقاط من حجز', NOW())
        ");
        
        try {
            $stmt->execute([$customerId, $rentalId, $points]);
        } catch (Exception $e) {
            // Table might not exist, ignore
        }

        // Update customer total points
        $updateStmt = $this->db->prepare("
            UPDATE customers SET 
                loyalty_points = loyalty_points + ?,
                total_bookings = total_bookings + 1
            WHERE id = ?
        ");
        
        try {
            return $updateStmt->execute([$points, $customerId]);
        } catch (Exception $e) {
            // Columns might not exist, ignore
            return true;
        }
    }

    /**
     * Helper: Add penalty
     */
    private function addPenalty($rentalId, $type, $amount, $description) {
        $stmt = $this->db->prepare("
            INSERT INTO penalties (rental_id, penalty_type, amount, description, status, created_at)
            VALUES (?, ?, ?, ?, 'pending', NOW())
        ");
        
        try {
            return $stmt->execute([$rentalId, $type, $amount, $description]);
        } catch (Exception $e) {
            // Table might not exist, ignore
            return true;
        }
    }

    /**
     * Helper: Update customer stats
     */
    private function updateCustomerStats($customerId) {
        // Update loyalty level based on points
        $stmt = $this->db->prepare("SELECT loyalty_points FROM customers WHERE id = ?");
        $stmt->execute([$customerId]);
        $customer = $stmt->fetch();

        if (!$customer) return false;

        $level = 'bronze';
        $points = $customer['loyalty_points'] ?? 0;
        
        if ($points >= (PLATINUM_MIN_POINTS ?? 5000)) {
            $level = 'platinum';
        } elseif ($points >= (GOLD_MIN_POINTS ?? 1500)) {
            $level = 'gold';
        } elseif ($points >= (SILVER_MIN_POINTS ?? 500)) {
            $level = 'silver';
        }

        $updateStmt = $this->db->prepare("UPDATE customers SET loyalty_level = ? WHERE id = ?");
        
        try {
            return $updateStmt->execute([$level, $customerId]);
        } catch (Exception $e) {
            // Column might not exist, ignore
            return true;
        }
    }
}
?>