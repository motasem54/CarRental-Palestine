# 🚀 دليل التثبيت والفحص الشامل
CarRental Palestine - Installation & Testing Guide

---

## 📋 **جدول المحتويات**

1. [المتطلبات](#%D8%A7%D9%84%D9%85%D8%AA%D8%B7%D9%84%D8%A8%D8%A7%D8%AA)
2. [التثبيت](#%D8%A7%D9%84%D8%AA%D8%AB%D8%A8%D9%8A%D8%AA)
3. [إعداد قاعدة البيانات](#%D8%A5%D8%B9%D8%AF%D8%A7%D8%AF-%D9%82%D8%A7%D8%B9%D8%AF%D8%A9-%D8%A7%D9%84%D8%A8%D9%8A%D8%A7%D9%86%D8%A7%D8%AA)
4. [الإعدادات](#%D8%A7%D9%84%D8%A5%D8%B9%D8%AF%D8%A7%D8%AF%D8%A7%D8%AA)
5. [الفحص والتأكد](#%D8%A7%D9%84%D9%81%D8%AD%D8%B5-%D9%88%D8%A7%D9%84%D8%AA%D8%A3%D9%83%D8%AF)
6. [استكشاف الأخطاء](#%D8%A7%D8%B3%D8%AA%D9%83%D8%B4%D8%A7%D9%81-%D8%A7%D9%84%D8%A3%D8%AE%D8%B7%D8%A7%D8%A1)

---

## 💻 **المتطلبات**

### **Server Requirements:**
- PHP 7.4 أو أحدث
- MySQL 5.7 أو MariaDB 10.2 أو أحدث
- Apache أو Nginx
- PHP Extensions:
  - PDO
  - pdo_mysql
  - mbstring
  - fileinfo
  - gd (للصور)

### **Recommended:**
- PHP 8.0+
- MySQL 8.0+
- 2GB RAM minimum
- 10GB Storage

---

## 📥 **التثبيت**

### **الطريقة 1: Clone من GitHub**

```bash
# Clone المشروع
git clone https://github.com/motasem54/CarRental-Palestine.git

# الانتقال للمجلد
cd CarRental-Palestine

# إعطاء صلاحيات الكتابة
chmod -R 755 uploads/
chmod -R 755 cache/
```

### **الطريقة 2: تحميل ZIP**

1. حمّل الملف من GitHub
2. فك الضغط في مجلد `htdocs` (XAMPP) أو `www` (WAMP)
3. أعط صلاحيات الكتابة للمجلدات المطلوبة

---

## 🗄️ **إعداد قاعدة البيانات**

### **الخطوة 1: إنشاء قاعدة البيانات**

```sql
CREATE DATABASE carrental_palestine CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### **الخطوة 2: استيراد الجداول**

#### **عبر phpMyAdmin:**
1. افتح phpMyAdmin
2. اختر قاعدة البيانات `carrental_palestine`
3. اذهب لتبويب "Import"
4. اختر ملف `database/palestine-rental-full.sql`
5. اضغط "Go"

#### **عبر Command Line:**

```bash
mysql -u root -p carrental_palestine < database/palestine-rental-full.sql
```

### **الخطوة 3: التحقق من الجداول**

```sql
USE carrental_palestine;
SHOW TABLES;
```

**يجب أن تظهر 32 جدول:**
- users
- customers
- cars
- car_images
- rentals
- payments
- maintenance
- loyalty_programs
- customer_points
- discounts
- penalties
- penalty_settings
- online_bookings
- website_settings
- activity_log
- settings
- rewards
- reward_redemptions
- branches
- expenses
- insurance_companies
- insurance_claims
- notification_settings
- notification_templates
- notification_log
- website_pages
- website_gallery
- testimonials
- faqs
- contact_messages
- security_logs
- backups

---

## ⚙️ **الإعدادات**

### **1. إعدادات قاعدة البيانات**

افتح ملف `config/database.php` وعدّل:

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'carrental_palestine');
define('DB_USER', 'root');
define('DB_PASS', '');  // كلمة مرور MySQL
```

### **2. إعدادات الروابط**

افتح ملف `config/settings.php` وعدّل:

```php
// إذا كان المشروع في مجلد فرعي
define('BASE_URL', 'http://localhost/CarRental-Palestine');

// إذا كان في الجذر
define('BASE_URL', 'http://localhost');

// أو Domain خاص
define('BASE_URL', 'http://carrental.ps');
```

### **3. إعدادات الشركة**

```php
define('COMPANY_NAME', 'اسم شركتك');
define('COMPANY_PHONE', '+970599123456');
define('COMPANY_EMAIL', 'info@yourcompany.ps');
define('COMPANY_ADDRESS', 'رام الله - فلسطين');
```

---

## ✅ **الفحص والتأكد**

### **1. فحص الاتصال بقاعدة البيانات**

أنشئ ملف `test_db.php` في الجذر:

```php
<?php
require_once 'config/settings.php';

try {
    $db = Database::getInstance()->getConnection();
    echo "✅ الاتصال بقاعدة البيانات ناجح!<br>";
    
    // عدد الجداول
    $stmt = $db->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "✅ عدد الجداول: " . count($tables) . "<br>";
    
    // فحص المستخدم الافتراضي
    $stmt = $db->query("SELECT COUNT(*) FROM users");
    $userCount = $stmt->fetchColumn();
    echo "✅ عدد المستخدمين: " . $userCount . "<br>";
    
} catch (Exception $e) {
    echo "❌ خطأ: " . $e->getMessage();
}
?>
```

زر: `http://localhost/CarRental-Palestine/test_db.php`

### **2. فحص الصلاحيات**

```php
<?php
// test_permissions.php
$dirs = [
    'uploads',
    'uploads/cars',
    'uploads/contracts',
    'uploads/receipts'
];

foreach ($dirs as $dir) {
    if (is_writable($dir)) {
        echo "✅ $dir - قابل للكتابة<br>";
    } else {
        echo "❌ $dir - غير قابل للكتابة<br>";
    }
}
?>
```

### **3. تسجيل الدخول للنظام**

#### **بيانات الدخول الافتراضية:**
```
Username: admin
Password: Admin@123
```

#### **الرابط:**
```
http://localhost/CarRental-Palestine/admin/login.php
```

### **4. فحص الصفحات**

تأكد من عمل هذه الصفحات:

✅ **صفحات Admin:**
- `/admin/login.php` - تسجيل الدخول
- `/admin/dashboard.php` - لوحة التحكم
- `/admin/cars.php` - إدارة السيارات
- `/admin/customers.php` - إدارة العملاء
- `/admin/rentals.php` - إدارة الحجوزات

✅ **الموقع العام:**
- `/public/index.php` - الصفحة الرئيسية

---

## 🔍 **فحص المميزات**

### **1. نظام المصادقة**
```
✅ تسجيل الدخول
✅ تسجيل الخروج
✅ Session Management
✅ Password Hashing
✅ Activity Logging
```

### **2. إدارة السيارات**
```
✅ عرض قائمة السيارات
✅ إضافة سيارة جديدة
✅ رفع صور
✅ إحصائيات حسب الحالة
✅ فلترة وبحث (DataTables)
```

### **3. إدارة العملاء**
```
✅ عرض قائمة العملاء
✅ نظام الولاء (برونزي، فضي، ذهبي، بلاتينيوم)
✅ حساب النقاط
✅ إحصائيات العملاء
```

### **4. إدارة الحجوزات**
```
✅ عرض جميع الحجوزات
✅ حالات الحجوزات المختلفة
✅ حساب المبالغ التلقائي
✅ تتبع المدفوعات
```

---

## 🛠️ **استكشاف الأخطاء**

### **مشكلة: "Class Database not found"**

**الحل:**
```php
// تأكد من تضمين الملفات الصحيحة
require_once '../config/settings.php';
require_once '../config/database.php';
```

### **مشكلة: "Access denied for user"**

**الحل:**
1. تأكد من صحة اسم المستخدم وكلمة المرور
2. تأكد من صلاحيات المستخدم في MySQL
3. جرب:
```sql
GRANT ALL PRIVILEGES ON carrental_palestine.* TO 'root'@'localhost';
FLUSH PRIVILEGES;
```

### **مشكلة: "Cannot modify header information"**

**الحل:**
1. تأكد من عدم وجود مسافات قبل `<?php`
2. تأكد من عدم وجود `echo` قبل `redirect()`
3. استخدم `ob_start()` في بداية الملف

### **مشكلة: الصور لا تظهر**

**الحل:**
```bash
# إعطاء صلاحيات
chmod -R 755 uploads/
chown -R www-data:www-data uploads/  # Linux

# تأكد من المسار الصحيح
echo UPLOADS_URL;  // يجب أن يطابع رابط المجلد
```

### **مشكلة: Arabic characters appear as ????**

**الحل:**
```php
// في database.php
$options = [
    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
];

// في MySQL
ALTER DATABASE carrental_palestine CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

---

## 📊 **اختبار الأداء**

### **1. سرعة الصفحات**
```php
// في بداية الصفحة
$start_time = microtime(true);

// في نهاية الصفحة
$end_time = microtime(true);
$execution_time = ($end_time - $start_time);
echo "Page loaded in: " . $execution_time . " seconds";
```

### **2. عدد الاستعلامات**
```php
// عدّ الاستعلامات
$queryCount = 0;
// زد العداد في كل استعلام
```

---

## 📝 **Checklist النهائي**

- [ ] قاعدة البيانات منشأة (32 جدول)
- [ ] المستخدم الافتراضي موجود
- [ ] الاتصال بقاعدة البيانات يعمل
- [ ] مجلدات uploads قابلة للكتابة
- [ ] تسجيل الدخول يعمل
- [ ] Dashboard تظهر الإحصائيات الصحيحة
- [ ] يمكن إضافة سيارة جديدة
- [ ] يمكن رفع صور
- [ ] العملاء يظهرون بشكل صحيح
- [ ] الحجوزات تظهر بشكل صحيح
- [ ] الموقع العام يعمل
- [ ] النصوص العربية تظهر بشكل صحيح
- [ ] التصميم responsive على الموبايل

---

## 🎉 **مبروك!**

إذا اجتزت جميع الفحوصات، فالنظام الآن جاهز للاستخدام! 🇵🇸

### **الخطوات التالية:**

1. غيّر كلمة المرور الافتراضية
2. أضف بيانات الشركة الحقيقية
3. ارفع صور للسيارات
4. أضف عملاء تجريبيين
5. جرب إنشاء حجز كامل
6. راجع التقارير والإحصائيات

---

## 📞 **الدعم**

للمساعدة أو الاستفسارات:
- GitHub Issues: [Create Issue](https://github.com/motasem54/CarRental-Palestine/issues)
- Email: support@carrental.ps

---

**Made with ❤️ in Palestine 🇵🇸**