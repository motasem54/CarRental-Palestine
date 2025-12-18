<?php
/**
 * Daily Cron Jobs
 * Run this file once per day
 * Add to crontab: 0 2 * * * /usr/bin/php /path/to/daily_tasks.php
 */

require_once '../config/settings.php';
require_once '../core/BackupManager.php';
require_once '../core/NotificationManager.php';
require_once '../core/Email.php';

$db = Database::getInstance()->getConnection();
$notif = new NotificationManager();
$email = new Email();

echo "[" . date('Y-m-d H:i:s') . "] Starting daily tasks...\n";

// 1. Auto Backup
$settingsStmt = $db->query("SELECT * FROM system_settings WHERE setting_key = 'auto_backup'");
$autoBackup = $settingsStmt->fetch();

if ($autoBackup && $autoBackup['setting_value'] == 1) {
    echo "Creating automatic backup...\n";
    $backup = new BackupManager();
    $result = $backup->createBackup();
    if ($result['success']) {
        echo "Backup created: {$result['filename']}\n";
    } else {
        echo "Backup failed: {$result['message']}\n";
    }
}

// 2. Check rentals due tomorrow (reminder)
$reminderDaysStmt = $db->query("SELECT setting_value FROM system_settings WHERE setting_key = 'reminder_days'");
$reminderDays = $reminderDaysStmt->fetchColumn() ?: 1;

$stmt = $db->prepare("
    SELECT r.*, c.full_name, c.email, c.phone, ca.brand, ca.model
    FROM rentals r
    JOIN customers c ON r.customer_id = c.id
    JOIN cars ca ON r.car_id = ca.id
    WHERE r.status = 'active' 
    AND DATE(r.end_date) = DATE_ADD(CURDATE(), INTERVAL ? DAY)
");
$stmt->execute([$reminderDays]);
$upcomingRentals = $stmt->fetchAll();

echo "Found " . count($upcomingRentals) . " rentals due in {$reminderDays} day(s)\n";

foreach ($upcomingRentals as $rental) {
    // Send email reminder
    $emailSent = $email->sendRentalReminder([
        'customer_name' => $rental['full_name'],
        'email' => $rental['email'],
        'rental_number' => $rental['rental_number'],
        'car' => $rental['brand'] . ' ' . $rental['model'],
        'end_date' => formatDate($rental['end_date'], 'd/m/Y H:i')
    ]);
    
    if ($emailSent) {
        echo "Reminder sent to {$rental['full_name']} ({$rental['email']})\n";
    }
    
    // Notify admins
    $notif->notifyRentalDue($rental['rental_number']);
}

// 3. Check overdue rentals
$overdueStmt = $db->query("
    SELECT r.*, c.full_name
    FROM rentals r
    JOIN customers c ON r.customer_id = c.id
    WHERE r.status = 'active' 
    AND r.end_date < NOW()
");
$overdueRentals = $overdueStmt->fetchAll();

echo "Found " . count($overdueRentals) . " overdue rentals\n";

foreach ($overdueRentals as $rental) {
    // Calculate penalty
    $daysOverdue = ceil((time() - strtotime($rental['end_date'])) / 86400);
    $penaltyStmt = $db->query("SELECT setting_value FROM system_settings WHERE setting_key = 'overdue_penalty'");
    $dailyPenalty = $penaltyStmt->fetchColumn() ?: 50;
    $totalPenalty = $daysOverdue * $dailyPenalty;
    
    // Update rental with penalty
    $updateStmt = $db->prepare("
        UPDATE rentals 
        SET total_amount = total_amount + ?,
            remaining_amount = remaining_amount + ?
        WHERE id = ?
    ");
    $updateStmt->execute([$totalPenalty, $totalPenalty, $rental['id']]);
    
    echo "Applied penalty of " . formatCurrency($totalPenalty) . " to rental {$rental['rental_number']}\n";
    
    // Notify admins
    $notif->notifyOverdueRental($rental['rental_number']);
}

// 4. Update loyalty levels
$customersStmt = $db->query("SELECT * FROM customers");
$customers = $customersStmt->fetchAll();

foreach ($customers as $customer) {
    $points = $customer['loyalty_points'];
    $newLevel = 'bronze';
    
    if ($points >= 1000) $newLevel = 'platinum';
    elseif ($points >= 500) $newLevel = 'gold';
    elseif ($points >= 200) $newLevel = 'silver';
    
    if ($newLevel !== $customer['loyalty_level']) {
        $updateStmt = $db->prepare("UPDATE customers SET loyalty_level = ? WHERE id = ?");
        $updateStmt->execute([$newLevel, $customer['id']]);
        echo "Updated {$customer['full_name']} to {$newLevel} level\n";
    }
}

// 5. Clean old notifications (older than 30 days)
$db->query("DELETE FROM notifications WHERE created_at < DATE_SUB(NOW(), INTERVAL 30 DAY)");
echo "Cleaned old notifications\n";

// 6. Clean old activity logs (older than 90 days)
$db->query("DELETE FROM activity_log WHERE created_at < DATE_SUB(NOW(), INTERVAL 90 DAY)");
echo "Cleaned old activity logs\n";

echo "[" . date('Y-m-d H:i:s') . "] Daily tasks completed!\n";
?>