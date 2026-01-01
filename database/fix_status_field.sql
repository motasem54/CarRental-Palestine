-- إصلاح حقل status في جدول maintenance
-- Fix status field in maintenance table

-- الطريقة 1: تغيير إلى VARCHAR (الأفضل للمرونة)
-- Method 1: Change to VARCHAR (Better for flexibility)
ALTER TABLE maintenance 
MODIFY COLUMN status VARCHAR(20) NOT NULL DEFAULT 'pending';

-- الطريقة 2: تحديث ENUM بالقيم الصحيحة
-- Method 2: Update ENUM with correct values
/*
ALTER TABLE maintenance 
MODIFY COLUMN status ENUM('pending', 'in_progress', 'completed', 'cancelled') NOT NULL DEFAULT 'pending';
*/

-- التحقق من التحديث
-- Verify the update
DESCRIBE maintenance;

-- عرض القيم الحالية
-- Show current values
SELECT DISTINCT status, COUNT(*) as count 
FROM maintenance 
GROUP BY status;