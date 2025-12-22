-- Sample Cars Data for Palestine Car Rental
-- 15 Palestinian Market Cars with realistic prices

-- Insert sample cars
INSERT INTO `cars` (`brand`, `model`, `year`, `plate_number`, `color`, `seats`, `transmission`, `fuel_type`, `daily_rate`, `weekly_rate`, `monthly_rate`, `mileage`, `status`, `features`, `image`, `created_at`) VALUES

-- Economy Cars (150-200 NIS/day)
('Hyundai', 'Accent', 2022, '🇵🇸 12-345-67', 'أبيض', 5, 'automatic', 'gasoline', 150.00, 900.00, 3200.00, 45000, 'available', 'AC,Bluetooth,USB', 'no_image.jpg', NOW()),
('Kia', 'Picanto', 2023, '🇵🇸 23-456-78', 'أحمر', 4, 'manual', 'gasoline', 150.00, 900.00, 3200.00, 12000, 'available', 'AC,Radio', 'no_image.jpg', NOW()),
('Nissan', 'Sunny', 2021, '🇵🇸 34-567-89', 'فضي', 5, 'automatic', 'gasoline', 170.00, 1000.00, 3500.00, 68000, 'available', 'AC,Bluetooth,USB,Cruise Control', 'no_image.jpg', NOW()),
('Chevrolet', 'Aveo', 2022, '🇵🇸 45-678-90', 'أزرق', 5, 'automatic', 'gasoline', 160.00, 950.00, 3400.00, 34000, 'available', 'AC,Bluetooth,Rear Camera', 'no_image.jpg', NOW()),
('Renault', 'Symbol', 2023, '🇵🇸 56-789-01', 'أسود', 5, 'manual', 'gasoline', 155.00, 920.00, 3300.00, 8000, 'available', 'AC,USB', 'no_image.jpg', NOW()),

-- Mid-Range Cars (180-250 NIS/day)
('Toyota', 'Corolla', 2022, '🇵🇸 67-890-12', 'أبيض', 5, 'automatic', 'gasoline', 200.00, 1200.00, 4200.00, 42000, 'available', 'AC,Bluetooth,USB,Rear Camera,Cruise Control,Leather Seats', 'no_image.jpg', NOW()),
('Hyundai', 'Elantra', 2023, '🇵🇸 78-901-23', 'رمادي', 5, 'automatic', 'gasoline', 190.00, 1150.00, 4000.00, 28000, 'available', 'AC,Bluetooth,USB,Sunroof,Cruise Control', 'no_image.jpg', NOW()),
('Volkswagen', 'Jetta', 2022, '🇵🇸 89-012-34', 'أسود', 5, 'automatic', 'gasoline', 210.00, 1250.00, 4400.00, 38000, 'available', 'AC,Bluetooth,USB,Rear Camera,Leather Seats', 'no_image.jpg', NOW()),
('Mazda', '3', 2023, '🇵🇸 90-123-45', 'أحمر', 5, 'automatic', 'gasoline', 195.00, 1170.00, 4100.00, 15000, 'available', 'AC,Bluetooth,USB,Cruise Control', 'no_image.jpg', NOW()),
('Skoda', 'Octavia', 2022, '🇵🇸 01-234-56', 'فضي', 5, 'automatic', 'gasoline', 200.00, 1200.00, 4200.00, 46000, 'available', 'AC,Bluetooth,USB,Sunroof,Rear Camera', 'no_image.jpg', NOW()),

-- Premium/SUV Cars (220-300+ NIS/day)
('Honda', 'CR-V', 2023, '🇵🇸 12-345-99', 'أبيض', 7, 'automatic', 'gasoline', 280.00, 1680.00, 5800.00, 22000, 'available', 'AC,Bluetooth,USB,4WD,Sunroof,Leather Seats,Rear Camera,GPS', 'no_image.jpg', NOW()),
('Kia', 'Sportage', 2022, '🇵🇸 23-456-88', 'أسود', 5, 'automatic', 'gasoline', 250.00, 1500.00, 5200.00, 35000, 'available', 'AC,Bluetooth,USB,Rear Camera,Cruise Control,Leather Seats', 'no_image.jpg', NOW()),
('Nissan', 'X-Trail', 2023, '🇵🇸 34-567-77', 'رمادي', 7, 'automatic', 'gasoline', 270.00, 1620.00, 5600.00, 18000, 'available', 'AC,Bluetooth,USB,4WD,Sunroof,GPS,Leather Seats', 'no_image.jpg', NOW()),
('Hyundai', 'Tucson', 2023, '🇵🇸 45-678-66', 'أزرق', 5, 'automatic', 'gasoline', 260.00, 1560.00, 5400.00, 24000, 'available', 'AC,Bluetooth,USB,Rear Camera,Cruise Control,Sunroof', 'no_image.jpg', NOW()),
('Mitsubishi', 'Outlander', 2022, '🇵🇸 56-789-55', 'أبيض', 7, 'automatic', 'gasoline', 265.00, 1590.00, 5500.00, 41000, 'available', 'AC,Bluetooth,USB,4WD,Leather Seats,Rear Camera', 'no_image.jpg', NOW());

-- Update car count
UPDATE `cars` SET `created_at` = DATE_SUB(NOW(), INTERVAL FLOOR(RAND() * 180) DAY);

-- Add some maintenance records
INSERT INTO `maintenance` (`car_id`, `maintenance_type`, `description`, `cost`, `maintenance_date`, `next_due_date`, `created_by`, `created_at`) 
SELECT 
    id,
    'oil_change',
    'تغيير زيت دوري',
    180.00,
    DATE_SUB(NOW(), INTERVAL 30 DAY),
    DATE_ADD(NOW(), INTERVAL 90 DAY),
    1,
    DATE_SUB(NOW(), INTERVAL 30 DAY)
FROM `cars` 
WHERE id IN (1, 3, 5, 7, 9)
LIMIT 5;

-- Success message
SELECT '15 Palestinian cars added successfully! ✅' AS message;