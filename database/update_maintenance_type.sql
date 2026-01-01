-- تحديث جدول maintenance لدعم جميع أنواع الصيانة
-- Update maintenance table to support all maintenance types

-- الطريقة 1: تغيير نوع الحقل من ENUM إلى VARCHAR (الأفضل للمرونة)
-- Method 1: Change field type from ENUM to VARCHAR (Better for flexibility)
ALTER TABLE maintenance 
MODIFY COLUMN maintenance_type VARCHAR(50) NOT NULL;

-- الطريقة 2: إذا أردت الاحتفاظ بـ ENUM وإضافة جميع الأنواع
-- Method 2: If you want to keep ENUM and add all types
/*
ALTER TABLE maintenance 
MODIFY COLUMN maintenance_type ENUM(
    'oil_change',
    'regular_maintenance',
    'tire_change',
    'inspection',
    'brake_repair',
    'engine_repair',
    'transmission',
    'electrical',
    'ac_repair',
    'body_work',
    'repair',
    'other'
) NOT NULL;
*/

-- التحقق من التحديث
-- Verify the update
DESCRIBE maintenance;

-- اختياري: تحديث القيم القديمة إن وجدت
-- Optional: Update old values if they exist
UPDATE maintenance 
SET maintenance_type = 'regular_maintenance' 
WHERE maintenance_type = 'regular';

UPDATE maintenance 
SET maintenance_type = 'inspection' 
WHERE maintenance_type IN ('check', 'fhs');

-- عرض إحصائيات الأنواع
-- Show types statistics
SELECT 
    maintenance_type,
    COUNT(*) as count,
    SUM(cost) as total_cost
FROM maintenance
GROUP BY maintenance_type
ORDER BY count DESC;
