-- System Settings Table
-- جدول إعدادات النظام لحفظ الألوان والشعار

CREATE TABLE IF NOT EXISTS system_settings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT,
    setting_group VARCHAR(50) DEFAULT 'general',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_setting_key (setting_key),
    INDEX idx_setting_group (setting_group)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default settings
INSERT INTO system_settings (setting_key, setting_value, setting_group) VALUES
('site_primary_color', '#FF5722', 'appearance'),
('site_secondary_color', '#E64A19', 'appearance'),
('site_logo_text', 'نظام تأجير السيارات', 'appearance'),
('site_logo_icon', 'fa-car', 'appearance')
ON DUPLICATE KEY UPDATE 
    setting_value = VALUES(setting_value),
    updated_at = NOW();