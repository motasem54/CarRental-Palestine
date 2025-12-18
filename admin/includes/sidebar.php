<!-- Sidebar -->
<div class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <div class="sidebar-logo">
            <i class="fas fa-car"></i>
        </div>
        <h4 class="sidebar-title"><?php echo SITE_NAME; ?></h4>
    </div>

    <ul class="sidebar-menu">
        <li class="menu-item">
            <a href="<?php echo ADMIN_URL; ?>/dashboard.php" class="menu-link <?php echo $current_page === 'dashboard' ? 'active' : ''; ?>">
                <i class="fas fa-home"></i>
                <span>الرئيسية</span>
            </a>
        </li>

        <li class="menu-item">
            <a href="<?php echo ADMIN_URL; ?>/cars.php" class="menu-link <?php echo $current_page === 'cars' ? 'active' : ''; ?>">
                <i class="fas fa-car"></i>
                <span>السيارات</span>
            </a>
        </li>

        <li class="menu-item">
            <a href="<?php echo ADMIN_URL; ?>/customers.php" class="menu-link <?php echo $current_page === 'customers' ? 'active' : ''; ?>">
                <i class="fas fa-users"></i>
                <span>العملاء</span>
            </a>
        </li>

        <li class="menu-item">
            <a href="<?php echo ADMIN_URL; ?>/rentals.php" class="menu-link <?php echo $current_page === 'rentals' ? 'active' : ''; ?>">
                <i class="fas fa-file-contract"></i>
                <span>الحجوزات</span>
            </a>
        </li>

        <li class="menu-item">
            <a href="<?php echo ADMIN_URL; ?>/payments.php" class="menu-link <?php echo $current_page === 'payments' ? 'active' : ''; ?>">
                <i class="fas fa-money-bill-wave"></i>
                <span>المدفوعات</span>
            </a>
        </li>

        <li class="menu-item">
            <a href="<?php echo ADMIN_URL; ?>/maintenance.php" class="menu-link <?php echo $current_page === 'maintenance' ? 'active' : ''; ?>">
                <i class="fas fa-tools"></i>
                <span>الصيانة</span>
            </a>
        </li>

        <li class="menu-item">
            <a href="<?php echo ADMIN_URL; ?>/bookings.php" class="menu-link <?php echo $current_page === 'bookings' ? 'active' : ''; ?>">
                <i class="fas fa-calendar-check"></i>
                <span>حجوزات الموقع</span>
            </a>
        </li>

        <li class="menu-item">
            <a href="<?php echo ADMIN_URL; ?>/reports.php" class="menu-link <?php echo $current_page === 'reports' ? 'active' : ''; ?>">
                <i class="fas fa-chart-line"></i>
                <span>التقارير</span>
            </a>
        </li>

        <?php if ($auth->isAdmin()): ?>
        <li class="menu-item" style="margin-top: 20px; padding-top: 20px; border-top: 1px solid rgba(255,255,255,0.1);">
            <a href="<?php echo ADMIN_URL; ?>/settings.php" class="menu-link <?php echo $current_page === 'settings' ? 'active' : ''; ?>">
                <i class="fas fa-cog"></i>
                <span>الإعدادات</span>
            </a>
        </li>

        <li class="menu-item">
            <a href="<?php echo ADMIN_URL; ?>/users.php" class="menu-link <?php echo $current_page === 'users' ? 'active' : ''; ?>">
                <i class="fas fa-user-shield"></i>
                <span>المستخدمين</span>
            </a>
        </li>
        <?php endif; ?>

        <li class="menu-item" style="margin-top: 20px;">
            <a href="<?php echo PUBLIC_URL; ?>/index.php" class="menu-link" target="_blank">
                <i class="fas fa-globe"></i>
                <span>الموقع العام</span>
            </a>
        </li>

        <li class="menu-item">
            <a href="<?php echo ADMIN_URL; ?>/logout.php" class="menu-link" onclick="return confirm('هل تريد تسجيل الخروج؟')">
                <i class="fas fa-sign-out-alt"></i>
                <span>تسجيل الخروج</span>
            </a>
        </li>
    </ul>
</div>