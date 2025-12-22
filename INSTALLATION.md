# 🚀 تعليمات التثبيت - نظام تأجير السيارات

## المتطلبات
- PHP 7.4 أو أحدث
- MySQL 5.7 أو أحدث
- Apache/Nginx
- cPanel (اختياري)

## خطوات التثبيت

### 1️⃣ رفع الملفات
```bash
cd /home/leadership/public_html/
rm -rf RentalDemopp
git clone https://github.com/motasem54/CarRental-Palestine.git RentalDemopp
cd RentalDemopp
```

### 2️⃣ إنشاء قاعدة البيانات
1. افتح cPanel → phpMyAdmin
2. أنشئ قاعدة بيانات جديدة: `leadership_rental`
3. استورد الملفات بالترتيب:
   - `database/schema.sql` (بنية الجداول)
   - `database/sample_data.sql` (البيانات الأساسية)
   - `database/sample_cars.sql` (15 سيارة تجريبية)

### 3️⃣ إعداد ملف الإعدادات
عدّل ملف `config/database.php`:
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'leadership_rental');
define('DB_USER', 'leadership_user');
define('DB_PASS', 'your_password_here');
```

### 4️⃣ ضبط الصلاحيات
```bash
chmod 755 -R /home/leadership/public_html/RentalDemopp
chmod 777 -R uploads/
chmod 777 -R backups/
```

### 5️⃣ بيانات تسجيل الدخول الافتراضية

**Admin:**
- المستخدم: `admin`
- الباسورد: `admin123`

**Manager:**
- المستخدم: `manager`
- الباسورد: `manager123`

### 6️⃣ الوصول للنظام
- **لوحة التحكم:** `https://yoursite.com/RentalDemopp/admin/`
- **الموقع العام:** `https://yoursite.com/RentalDemopp/`

## ⚠️ ملاحظات هامة

1. **غير كلمات المرور فوراً!**
2. تأكد من تفعيل `mod_rewrite` في Apache
3. صور no_image.jpg موجودة في: `uploads/cars/`

## 📊 السيارات التجريبية
تم إضافة 15 سيارة فلسطينية:

### اقتصادية (150-170 ₪/يوم)
- Hyundai Accent 2022
- Kia Picanto 2023
- Nissan Sunny 2021
- Chevrolet Aveo 2022
- Renault Symbol 2023

### متوسطة (190-210 ₪/يوم)
- Toyota Corolla 2022
- Hyundai Elantra 2023
- Volkswagen Jetta 2022
- Mazda 3 2023
- Skoda Octavia 2022

### فاخرة/SUV (250-280 ₪/يوم)
- Honda CR-V 2023
- Kia Sportage 2022
- Nissan X-Trail 2023
- Hyundai Tucson 2023
- Mitsubishi Outlander 2022

## 🐞 حل المشاكل الشائعة

### خطأ الاتصال بقاعدة البيانات
- تحقق من `config/database.php`
- تأكد من اسم المستخدم وكلمة المرور

### مشاكل رفع الصور
```bash
chmod 777 uploads/cars/
chown www-data:www-data uploads/cars/
```

### صفحة بيضاء
- فعّل عرض الأخطاء في `config/settings.php`
```php
ini_set('display_errors', 1);
error_reporting(E_ALL);
```

## 📞 الدعم
للمساعدة أو الاستفسارات:
- GitHub: https://github.com/motasem54/CarRental-Palestine
- Email: motasem.almohtaseb@gmail.com

---

❤️ **صُنع بكل حب في فلسطين** 🇵🇸