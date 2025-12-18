<?php

/**
 * Backup Manager Class
 * Database backup and restore
 */
class BackupManager {
    private $db;
    private $backupDir;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
        $this->backupDir = '../backups/';
        
        if (!is_dir($this->backupDir)) {
            mkdir($this->backupDir, 0755, true);
        }
    }
    
    /**
     * Create database backup
     */
    public function createBackup() {
        try {
            $filename = 'backup_' . date('Y-m-d_H-i-s') . '.sql';
            $filepath = $this->backupDir . $filename;
            
            // Get all tables
            $tables = [];
            $result = $this->db->query('SHOW TABLES');
            while ($row = $result->fetch(PDO::FETCH_NUM)) {
                $tables[] = $row[0];
            }
            
            $sql = "-- Car Rental System Database Backup\n";
            $sql .= "-- Created: " . date('Y-m-d H:i:s') . "\n\n";
            $sql .= "SET FOREIGN_KEY_CHECKS=0;\n\n";
            
            // Export each table
            foreach ($tables as $table) {
                // Drop table
                $sql .= "DROP TABLE IF EXISTS `$table`;\n";
                
                // Create table
                $createTable = $this->db->query("SHOW CREATE TABLE `$table`")->fetch();
                $sql .= $createTable['Create Table'] . ";\n\n";
                
                // Insert data
                $rows = $this->db->query("SELECT * FROM `$table`")->fetchAll(PDO::FETCH_ASSOC);
                if (!empty($rows)) {
                    foreach ($rows as $row) {
                        $values = array_map(function($value) {
                            return $value === null ? 'NULL' : $this->db->quote($value);
                        }, array_values($row));
                        
                        $sql .= "INSERT INTO `$table` VALUES (" . implode(', ', $values) . ");\n";
                    }
                    $sql .= "\n";
                }
            }
            
            $sql .= "SET FOREIGN_KEY_CHECKS=1;\n";
            
            // Save to file
            file_put_contents($filepath, $sql);
            
            return ['success' => true, 'filename' => $filename];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Restore from backup
     */
    public function restoreBackup($filename) {
        try {
            $filepath = $this->backupDir . $filename;
            
            if (!file_exists($filepath)) {
                throw new Exception('الملف غير موجود');
            }
            
            $sql = file_get_contents($filepath);
            
            // Execute SQL
            $this->db->exec($sql);
            
            return ['success' => true];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Download backup file
     */
    public function downloadBackup($filename) {
        $filepath = $this->backupDir . $filename;
        
        if (file_exists($filepath)) {
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Content-Length: ' . filesize($filepath));
            readfile($filepath);
        }
    }
    
    /**
     * Delete backup file
     */
    public function deleteBackup($filename) {
        $filepath = $this->backupDir . $filename;
        
        if (file_exists($filepath)) {
            return unlink($filepath);
        }
        
        return false;
    }
}
?>