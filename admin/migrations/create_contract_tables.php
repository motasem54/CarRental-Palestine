<?php
/**
 * Migration: Create Contract and Inspection Tables
 * Run this file once to create necessary database tables
 */

require_once '../../config/settings.php';

$db = Database::getInstance()->getConnection();

try {
    echo "<h2>💾 Creating contract tables...</h2>";
    
    // 1. Create inspection_forms table
    echo "<p>Creating inspection_forms table...</p>";
    $db->exec("
        CREATE TABLE IF NOT EXISTS inspection_forms (
            id INT PRIMARY KEY AUTO_INCREMENT,
            rental_id INT NOT NULL UNIQUE,
            inspection_date DATETIME DEFAULT CURRENT_TIMESTAMP,
            exterior_condition VARCHAR(255),
            fuel_level VARCHAR(50),
            mileage INT,
            notes LONGTEXT,
            car_front LONGBLOB,
            car_back LONGBLOB,
            car_left LONGBLOB,
            car_right LONGBLOB,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (rental_id) REFERENCES rentals(id) ON DELETE CASCADE,
            INDEX (rental_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "<span style='color:green;'>✅ inspection_forms table created</span><br>";
    
    // 2. Create contract_drafts table
    echo "<p>Creating contract_drafts table...</p>";
    $db->exec("
        CREATE TABLE IF NOT EXISTS contract_drafts (
            id INT PRIMARY KEY AUTO_INCREMENT,
            rental_id INT NOT NULL UNIQUE,
            inspection_notes LONGTEXT,
            has_promissory TINYINT DEFAULT 0,
            customer_signature LONGBLOB,
            company_signature LONGBLOB,
            promissory_signature LONGBLOB,
            car_front LONGBLOB,
            car_back LONGBLOB,
            car_left LONGBLOB,
            car_right LONGBLOB,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (rental_id) REFERENCES rentals(id) ON DELETE CASCADE,
            INDEX (rental_id),
            INDEX (updated_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "<span style='color:green;'>✅ contract_drafts table created</span><br>";
    
    // 3. Add missing columns to rentals if they don't exist
    echo "<p>Updating rentals table...</p>";
    
    $stmt = $db->prepare("SHOW COLUMNS FROM rentals LIKE 'base_amount'");
    $stmt->execute();
    if ($stmt->rowCount() == 0) {
        $db->exec("ALTER TABLE rentals ADD COLUMN base_amount DECIMAL(10, 2) DEFAULT 0");
        echo "<span style='color:green;'>✅ Added base_amount column</span><br>";
    }
    
    $stmt = $db->prepare("SHOW COLUMNS FROM rentals LIKE 'insurance_amount'");
    $stmt->execute();
    if ($stmt->rowCount() == 0) {
        $db->exec("ALTER TABLE rentals ADD COLUMN insurance_amount DECIMAL(10, 2) DEFAULT 0");
        echo "<span style='color:green;'>✅ Added insurance_amount column</span><br>";
    }
    
    $stmt = $db->prepare("SHOW COLUMNS FROM rentals LIKE 'contract_signed'");
    $stmt->execute();
    if ($stmt->rowCount() == 0) {
        $db->exec("ALTER TABLE rentals ADD COLUMN contract_signed TINYINT DEFAULT 0");
        echo "<span style='color:green;'>✅ Added contract_signed column</span><br>";
    }
    
    $stmt = $db->prepare("SHOW COLUMNS FROM rentals LIKE 'paid_amount'");
    $stmt->execute();
    if ($stmt->rowCount() == 0) {
        $db->exec("ALTER TABLE rentals ADD COLUMN paid_amount DECIMAL(10, 2) DEFAULT 0");
        echo "<span style='color:green;'>✅ Added paid_amount column</span><br>";
    }
    
    // 4. Create activity_logs table for audit trail
    echo "<p>Creating activity_logs table...</p>";
    $db->exec("
        CREATE TABLE IF NOT EXISTS activity_logs (
            id INT PRIMARY KEY AUTO_INCREMENT,
            user_id INT,
            action VARCHAR(255),
            description TEXT,
            table_name VARCHAR(100),
            record_id INT,
            old_values JSON,
            new_values JSON,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX (user_id),
            INDEX (created_at),
            INDEX (table_name)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "<span style='color:green;'>✅ activity_logs table created</span><br>";
    
    echo "<h3 style='color:green;'>✅ المهمة المنجزة بنجاح!</h3>";
    echo "<p>🇦 الملفات الجديدة من الآن جاهزة!</p>";
    
} catch (Exception $e) {
    echo "<h3 style='color:red;'>❌ خطأ: " . $e->getMessage() . "</h3>";
}
?>
