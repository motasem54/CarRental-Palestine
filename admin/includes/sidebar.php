<?php
$user_name = $_SESSION['full_name'] ?? 'مستخدم';
$user_role = $_SESSION['role'] ?? 'customer';
$user_initial = mb_substr($user_name, 0, 1);
?>

<!-- Sidebar -->
<div class="sidebar" id="sidebar">
    <!-- Logo -->
    <div class="sidebar-logo">
        <i class="fas fa-car"></i>
        <h4><?php echo SITE_NAME; ?></h4>
        <p>🇵🇸 نظام إدارة متكامل</p>
    </div>

    <!-- Menu -->
    <ul class="sidebar-menu">
        <li class="menu-item">
            <a href="dashboard.php" class="menu-link <?php echo $current_page == 'dashboard' ? 'active' : ''; ?>">
                <i class="fas fa-home"></i>
                <span>الرئيسية</span>
            </a>
        </li>

        <div class="menu-section-title">إدارة السيارات</div>
        <li class="menu-item">
            <a href="cars.php" class="menu-link <?php echo $current_page == 'cars' ? 'active' : ''; ?>">
                <i class="fas fa-car"></i>
                <span>السيارات</span>
            </a>
        </li>
        <li class="menu-item">
            <a href="maintenance.php" class="menu-link <?php echo $current_page == 'maintenance' ? 'active' : ''; ?>">
                <i class="fas fa-tools"></i>
                <span>الصيانة</span>
            </a>
        </li>

        <div class="menu-section-title">الحجوزات والإيجار</div>
        <li class="menu-item">
            <a href="rentals.php" class="menu-link <?php echo $current_page == 'rentals' ? 'active' : ''; ?>">
                <i class="fas fa-calendar-alt"></i>
                <span>الحجوزات</span>
            </a>
        </li>
        <li class="menu-item">
            <a href="customers.php" class="menu-link <?php echo $current_page == 'customers' ? 'active' : ''; ?>">
                <i class="fas fa-users"></i>
                <span>العملاء</span>
            </a>
        </li>
        <li class="menu-item">
            <a href="contracts.php" class="menu-link <?php echo $current_page == 'contracts' ? 'active' : ''; ?>">
                <i class="fas fa-file-contract"></i>
                <span>العقود</span>
            </a>
        </li>

        <div class="menu-section-title">المالية</div>
        <li class="menu-item">
            <a href="payments.php" class="menu-link <?php echo $current_page == 'payments' ? 'active' : ''; ?>">
                <i class="fas fa-money-bill-wave"></i>
                <span>المدفوعات</span>
            </a>
        </li>
        <li class="menu-item">
            <a href="expenses.php" class="menu-link <?php echo $current_page == 'expenses' ? 'active' : ''; ?>">
                <i class="fas fa-receipt"></i>
                <span>المصروفات</span>
            </a>
        </li>
        <li class="menu-item">
            <a href="reports.php" class="menu-link <?php echo $current_page == 'reports' ? 'active' : ''; ?>">
                <i class="fas fa-chart-line"></i>
                <span>التقارير</span>
            </a>
        </li>

        <div class="menu-section-title">التسويق</div>
        <li class="menu-item">
            <a href="discounts.php" class="menu-link <?php echo $current_page == 'discounts' ? 'active' : ''; ?>">
                <i class="fas fa-tag"></i>
                <span>الخصومات</span>
            </a>
        </li>
        <li class="menu-item">
            <a href="loyalty.php" class="menu-link <?php echo $current_page == 'loyalty' ? 'active' : ''; ?>">
                <i class="fas fa-gift"></i>
                <span>نظام الولاء</span>
            </a>
        </li>

        <?php if ($user_role === 'admin'): ?>
        <div class="menu-section-title">الإدارة</div>
        <li class="menu-item">
            <a href="users.php" class="menu-link <?php echo $current_page == 'users' ? 'active' : ''; ?>">
                <i class="fas fa-user-shield"></i>
                <span>المستخدمين</span>
            </a>
        </li>
        <li class="menu-item">
            <a href="branches.php" class="menu-link <?php echo $current_page == 'branches' ? 'active' : ''; ?>">
                <i class="fas fa-building"></i>
                <span>الفروع</span>
            </a>
        </li>
        <li class="menu-item">
            <a href="settings.php" class="menu-link <?php echo $current_page == 'settings' ? 'active' : ''; ?>">
                <i class="fas fa-cog"></i>
                <span>الإعدادات</span>
            </a>
        </li>
        <?php endif; ?>

        <div class="menu-section-title">الحساب</div>
        <li class="menu-item">
            <a href="profile.php" class="menu-link <?php echo $current_page == 'profile' ? 'active' : ''; ?>">
                <i class="fas fa-user"></i>
                <span>الملف الشخصي</span>
            </a>
        </li>
        <li class="menu-item">
            <a href="logout.php" class="menu-link" onclick="return confirm('هل تريد تسجيل الخروج؟')">
                <i class="fas fa-sign-out-alt"></i>
                <span>تسجيل الخروج</span>
            </a>
        </li>
    </ul>
</div>