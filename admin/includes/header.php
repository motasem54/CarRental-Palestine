<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle . ' - ' : ''; ?><?php echo SITE_NAME; ?></title>
    
    <!-- Bootstrap 5 RTL -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <!-- Font Awesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts - Cairo -->
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary: #FF5722;
            --primary-dark: #E64A19;
            --dark: #121212;
            --dark-light: #1a1a1a;
            --sidebar-width: 260px;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Cairo', sans-serif;
            background: linear-gradient(135deg, #121212 0%, #1a1a1a 100%);
            color: #fff;
            min-height: 100vh;
        }

        /* Sidebar */
        .sidebar {
            position: fixed;
            right: 0;
            top: 0;
            width: var(--sidebar-width);
            height: 100vh;
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(20px);
            border-left: 1px solid rgba(255, 255, 255, 0.1);
            z-index: 1000;
            overflow-y: auto;
        }

        .sidebar-logo {
            padding: 20px;
            text-align: center;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .sidebar-logo h4 {
            color: var(--primary);
            margin: 10px 0 0;
        }

        .sidebar-menu {
            padding: 20px 0;
        }

        .menu-item {
            display: block;
            padding: 12px 20px;
            color: #ccc;
            text-decoration: none;
            transition: all 0.3s;
            border-right: 3px solid transparent;
        }

        .menu-item:hover, .menu-item.active {
            background: rgba(255, 87, 34, 0.1);
            color: var(--primary);
            border-right-color: var(--primary);
        }

        .menu-item i {
            width: 25px;
            margin-left: 10px;
        }

        /* Main Content */
        .main-content {
            margin-right: var(--sidebar-width);
            min-height: 100vh;
        }

        /* Topbar */
        .topbar {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(20px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .topbar-right {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .user-menu {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* Glass Card */
        .glass-card {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 15px;
        }

        /* Stat Cards */
        .stat-card {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 15px;
            padding: 20px;
            display: flex;
            align-items: center;
            gap: 20px;
            transition: transform 0.3s;
        }

        .stat-card:hover { transform: translateY(-5px); }

        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }

        .stat-primary .stat-icon { background: rgba(255, 87, 34, 0.2); color: var(--primary); }
        .stat-success .stat-icon { background: rgba(40, 167, 69, 0.2); color: #28a745; }
        .stat-warning .stat-icon { background: rgba(255, 193, 7, 0.2); color: #ffc107; }
        .stat-info .stat-icon { background: rgba(23, 162, 184, 0.2); color: #17a2b8; }

        .stat-info h3 { margin: 0; font-size: 2rem; font-weight: bold; }
        .stat-info p { margin: 5px 0; color: #ccc; }
        .stat-info small { font-size: 0.85rem; }

        /* Table */
        .table { color: #fff; }
        .table thead th { border-bottom: 2px solid rgba(255, 255, 255, 0.1); }
        .table tbody td { border-bottom: 1px solid rgba(255, 255, 255, 0.05); }
        .table-hover tbody tr:hover { background: rgba(255, 255, 255, 0.02); }

        /* Buttons */
        .btn-primary {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            border: none;
        }
        .btn-primary:hover { transform: translateY(-2px); }

        .btn-outline-primary { border-color: var(--primary); color: var(--primary); }
        .btn-outline-primary:hover { background: var(--primary); color: white; }
    </style>
</head>
<body>

    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-logo">
            <div style="width:60px;height:60px;background:linear-gradient(135deg,#FF5722,#E64A19);border-radius:15px;margin:0 auto;display:flex;align-items:center;justify-content:center;font-size:2rem;">
                <i class="fas fa-car"></i>
            </div>
            <h4><?php echo SITE_NAME; ?></h4>
        </div>

        <div class="sidebar-menu">
            <a href="dashboard.php" class="menu-item active">
                <i class="fas fa-chart-line"></i> لوحة التحكم
            </a>
            <a href="cars.php" class="menu-item">
                <i class="fas fa-car"></i> إدارة السيارات
            </a>
            <a href="rentals.php" class="menu-item">
                <i class="fas fa-key"></i> الحجوزات
            </a>
            <a href="customers.php" class="menu-item">
                <i class="fas fa-users"></i> العملاء
            </a>
            <a href="payments.php" class="menu-item">
                <i class="fas fa-money-bill-wave"></i> المدفوعات
            </a>
            <a href="maintenance.php" class="menu-item">
                <i class="fas fa-tools"></i> الصيانة
            </a>
            <a href="reports.php" class="menu-item">
                <i class="fas fa-file-alt"></i> التقارير
            </a>
            <a href="discounts.php" class="menu-item">
                <i class="fas fa-tags"></i> الخصومات
            </a>
            <?php if (isAdmin()): ?>
            <a href="settings.php" class="menu-item">
                <i class="fas fa-cog"></i> الإعدادات
            </a>
            <a href="users.php" class="menu-item">
                <i class="fas fa-user-shield"></i> المستخدمين
            </a>
            <?php endif; ?>
            <a href="logout.php" class="menu-item text-danger">
                <i class="fas fa-sign-out-alt"></i> تسجيل الخروج
            </a>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Topbar -->
        <div class="topbar">
            <div class="topbar-left">
                <h5 class="mb-0"><i class="fas fa-calendar-alt me-2"></i><?php echo date('l, d F Y'); ?></h5>
            </div>
            <div class="topbar-right">
                <div class="user-menu">
                    <div class="user-avatar">
                        <i class="fas fa-user"></i>
                    </div>
                    <div>
                        <div><strong><?php echo $_SESSION['full_name']; ?></strong></div>
                        <small class="text-muted"><?php echo USER_ROLES[$_SESSION['role']]; ?></small>
                    </div>
                </div>
            </div>
        </div>