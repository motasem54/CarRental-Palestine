# 🚗 Car Rental System - Complete Documentation

## 🇵🇸 Made with Love in Palestine

---

## 📚 **Table of Contents**

1. [System Overview](#system-overview)
2. [Features Checklist](#features-checklist)
3. [Installation Guide](#installation-guide)
4. [Admin Panel Guide](#admin-panel-guide)
5. [Customer Portal Guide](#customer-portal-guide)
6. [Cron Jobs Setup](#cron-jobs-setup)
7. [API Documentation](#api-documentation)
8. [Security Guide](#security-guide)
9. [Backup & Restore](#backup--restore)
10. [Troubleshooting](#troubleshooting)

---

## 📊 **System Overview**

### **Complete Features List:**

#### **✅ Phase 1: Core System**
- ✅ Dashboard with 7+ statistics
- ✅ Cars Management (CRUD)
- ✅ Customers Management with Loyalty
- ✅ Rentals Management
- ✅ Online Bookings
- ✅ Payments Tracking
- ✅ Maintenance Records
- ✅ Users & Roles
- ✅ Settings
- ✅ Reports

#### **✅ Phase 2: Advanced Dashboard**
- ✅ Chart.js Integration
- ✅ Revenue Charts (Daily/Monthly)
- ✅ Cars Status Charts
- ✅ Rentals Pie Charts
- ✅ Top 5 Cars Performance
- ✅ Monthly Comparison

#### **✅ Phase 3: PDF & Activity**
- ✅ PDF Contract Generator
- ✅ PDF Invoice Generator
- ✅ Activity Logging (All Actions)
- ✅ Activity Filters & Search

#### **✅ Phase 4: Customer Portal**
- ✅ Customer Login System
- ✅ Customer Dashboard
- ✅ Rental History
- ✅ Loyalty Points Tracking
- ✅ Personal Statistics

#### **✅ Phase 5: Calendar & Dark Mode**
- ✅ FullCalendar Integration
- ✅ Visual Rental Calendar
- ✅ Dark Mode Toggle
- ✅ Theme Persistence

#### **✅ Phase 6: Email Notifications**
- ✅ Booking Confirmation Emails
- ✅ Rental Reminders
- ✅ HTML Email Templates
- ✅ Customizable Messages

#### **✅ Phase 7: Advanced Reports**
- ✅ 6 Report Types:
  1. Revenue Report
  2. Cars Performance
  3. Customers Analysis
  4. Payments Report
  5. Maintenance Report
  6. Profit & Loss (P&L)
- ✅ Excel Export (.xls)
- ✅ PDF Export
- ✅ CSV Export
- ✅ Print Function
- ✅ Advanced Filters
- ✅ Auto Totals

#### **✅ Phase 8: Visual Reports**
- ✅ 4 Interactive Charts:
  1. Revenue Trend (12 months)
  2. Payment Methods Distribution
  3. Cars Utilization
  4. Monthly Rentals Count

#### **✅ Phase 9: Backup System**
- ✅ Create Database Backup
- ✅ Restore from Backup
- ✅ Download Backup Files
- ✅ Delete Old Backups
- ✅ Auto Backup (Cron)

#### **✅ Phase 10: Notification Center**
- ✅ In-App Notifications
- ✅ Mark as Read/Unread
- ✅ Notification Types (info, success, warning, error)
- ✅ Admin Alerts
- ✅ Auto Notifications:
  - New Bookings
  - Payments Received
  - Rentals Due
  - Overdue Rentals

#### **✅ Phase 11: Security**
- ✅ Two-Factor Authentication (2FA)
- ✅ Login Activity Tracking
- ✅ Failed Login Attempts
- ✅ IP Address Logging
- ✅ Security Dashboard

#### **✅ Phase 12: System Settings**
- ✅ Auto Backup Settings
- ✅ Notification Preferences
- ✅ Email/SMS Toggle
- ✅ Reminder Days Configuration
- ✅ Overdue Penalty Settings
- ✅ Loyalty Points Rate
- ✅ Maintenance Alerts

#### **✅ Phase 13: Cron Jobs**
- ✅ Daily Auto Backup
- ✅ Rental Reminders
- ✅ Overdue Penalty Calculation
- ✅ Loyalty Level Updates
- ✅ Data Cleanup (Old records)

#### **✅ Phase 14: Performance Monitor**
- ✅ Database Size Tracking
- ✅ Table Statistics
- ✅ Server Information
- ✅ Activity Charts
- ✅ System Health

---

## 🛠️ **Technology Stack**

- **Backend:** PHP 7.4+ (Native)
- **Database:** MySQL 5.7+
- **Frontend:** HTML5, CSS3, Bootstrap 5, JavaScript
- **Charts:** Chart.js 4.4
- **Calendar:** FullCalendar 6.1
- **Icons:** Font Awesome 6.4
- **PDF:** HTML to PDF (Print)
- **Export:** Excel/CSV

---

## 📝 **File Structure**

```
CarRental-Palestine/
├── admin/
│   ├── dashboard.php
│   ├── dashboard_advanced.php
│   ├── cars.php, car_add.php, car_edit.php
│   ├── customers.php, customer_add.php
│   ├── rentals.php, rental_add.php
│   ├── bookings.php
│   ├── payments.php
│   ├── maintenance.php
│   ├── reports.php, reports_advanced.php, reports_charts.php
│   ├── calendar.php
│   ├── activity_log.php
│   ├── notifications.php
│   ├── backup.php
│   ├── security.php
│   ├── system_settings.php
│   ├── performance.php
│   ├── settings.php, users.php, profile.php
│   └── dark_mode_toggle.js
├── public/
│   ├── index.php
│   ├── booking.php
│   └── customer/
│       ├── login.php
│       ├── dashboard.php
│       └── logout.php
├── core/
│   ├── Database.php
│   ├── Auth.php
│   ├── Car.php
│   ├── Customer.php
│   ├── Rental.php
│   ├── Booking.php
│   ├── PDF.php
│   ├── ExcelExport.php
│   ├── PDFReport.php
│   ├── Email.php
│   ├── BackupManager.php
│   ├── NotificationManager.php
│   └── TwoFactorAuth.php
├── config/
│   ├── database.php
│   ├── constants.php
│   └── settings.php
├── cron/
│   └── daily_tasks.php
├── backups/
└── uploads/
```

---

## 💻 **Admin Panel Pages**

### **Dashboard:**
- `/admin/dashboard.php` - Basic dashboard
- `/admin/dashboard_advanced.php` - Charts & analytics

### **Management:**
- `/admin/cars.php` - Cars list
- `/admin/customers.php` - Customers list
- `/admin/rentals.php` - Rentals list
- `/admin/bookings.php` - Online bookings
- `/admin/payments.php` - Payments
- `/admin/maintenance.php` - Maintenance

### **Reports:**
- `/admin/reports.php` - Basic reports
- `/admin/reports_advanced.php` - Advanced with export
- `/admin/reports_charts.php` - Visual charts

### **System:**
- `/admin/calendar.php` - Rental calendar
- `/admin/activity_log.php` - Activity logs
- `/admin/notifications.php` - Notifications center
- `/admin/backup.php` - Backup management
- `/admin/security.php` - Security settings
- `/admin/system_settings.php` - System configuration
- `/admin/performance.php` - Performance monitor
- `/admin/settings.php` - General settings
- `/admin/users.php` - Users management
- `/admin/profile.php` - Profile settings

---

## 👥 **Customer Portal**

- `/public/customer/login.php` - Customer login
- `/public/customer/dashboard.php` - Customer dashboard
- `/public/customer/logout.php` - Logout

---

## ⏰ **Cron Jobs Setup**

### **Daily Tasks:**
```bash
# Add to crontab
crontab -e

# Run daily at 2 AM
0 2 * * * /usr/bin/php /path/to/cron/daily_tasks.php
```

### **Tasks Performed:**
1. Auto database backup
2. Send rental reminders
3. Calculate overdue penalties
4. Update loyalty levels
5. Clean old data

---

## 🔒 **Default Login**

```
Username: admin
Password: admin123
```

**⚠️ Change immediately after first login!**

---

## 🎉 **All Features Count**

- **Total Pages:** 30+
- **Reports:** 6 types
- **Charts:** 8+ interactive
- **Export Formats:** Excel, PDF, CSV
- **Notifications:** 5+ types
- **Security:** 2FA, Activity Logs
- **Automation:** Cron jobs
- **Languages:** Arabic (RTL)

---

## 📞 **Support**

For questions or issues:
- GitHub: https://github.com/motasem54/CarRental-Palestine
- Made with ❤️ in Palestine 🇵🇸

---

**© 2024 Car Rental System - Palestine**
