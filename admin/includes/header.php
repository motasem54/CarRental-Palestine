<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$current_page = basename($_SERVER['PHP_SELF'], '.php');
$user_name = $_SESSION['full_name'] ?? $_SESSION['username'] ?? 'مستخدم';
$user_initial = mb_substr($user_name, 0, 1);
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title ?? SITE_NAME; ?></title>
    
    <!-- Bootstrap 5 RTL -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <!-- Font Awesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts - Cairo -->
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <!-- DataTables -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
    
    <style>
        :root {
            --primary: #FF5722;
            --primary-dark: #E64A19;
            --primary-light: #FF8A65;
            --dark: #121212;
            --dark-light: #1a1a1a;
            --sidebar-width: 280px;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Cairo', sans-serif;
            background: #f5f5f5;
            overflow-x: hidden;
        }

        /* Sidebar */
        .sidebar {
            position: fixed;
            right: 0;
            top: 0;
            width: var(--sidebar-width);
            height: 100vh;
            background: linear-gradient(180deg, var(--dark) 0%, var(--dark-light) 100%);
            padding: 20px 0;
            transition: all 0.3s ease;
            z-index: 1000;
            box-shadow: -2px 0 10px rgba(0,0,0,0.1);
            overflow-y: auto;
        }

        .sidebar::-webkit-scrollbar { width: 5px; }
        .sidebar::-webkit-scrollbar-track { background: rgba(255,255,255,0.05); }
        .sidebar::-webkit-scrollbar-thumb { background: var(--primary); border-radius: 10px; }

        .sidebar-logo {
            text-align: center;
            padding: 20px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            margin-bottom: 20px;
        }

        .sidebar-logo i {
            font-size: 3rem;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 10px;
        }

        .sidebar-logo h4 {
            color: white;
            font-weight: bold;
            margin: 0;
            font-size: 1.2rem;
        }

        .sidebar-logo p {
            color: #999;
            font-size: 0.8rem;
            margin: 5px 0 0 0;
        }

        .sidebar-menu {
            list-style: none;
            padding: 0 15px;
        }

        .menu-item {
            margin-bottom: 5px;
        }

        .menu-link {
            display: flex;
            align-items: center;
            padding: 12px 20px;
            color: #ccc;
            text-decoration: none;
            border-radius: 10px;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .menu-link::before {
            content: '';
            position: absolute;
            right: -100%;
            top: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,87,34,0.2), transparent);
            transition: 0.5s;
        }

        .menu-link:hover::before { right: 100%; }

        .menu-link:hover {
            background: rgba(255,87,34,0.1);
            color: var(--primary);
            transform: translateX(-5px);
        }

        .menu-link.active {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            font-weight: 600;
        }

        .menu-link i {
            font-size: 1.2rem;
            margin-left: 15px;
            width: 25px;
        }

        .menu-section-title {
            color: #666;
            font-size: 0.75rem;
            text-transform: uppercase;
            font-weight: 600;
            margin: 20px 0 10px 20px;
            letter-spacing: 1px;
        }

        /* Main Content */
        .main-content {
            margin-right: var(--sidebar-width);
            min-height: 100vh;
            padding: 20px;
        }

        /* Top Bar */
        .top-bar {
            background: white;
            padding: 15px 25px;
            border-radius: 15px;
            margin-bottom: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .welcome-text h5 {
            margin: 0;
            color: var(--dark);
            font-weight: 700;
        }

        .welcome-text p {
            margin: 0;
            color: #666;
            font-size: 0.9rem;
        }

        .top-bar-right {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .notification-icon {
            position: relative;
            font-size: 1.3rem;
            color: var(--dark);
            cursor: pointer;
            padding: 8px;
            border-radius: 10px;
            transition: all 0.3s;
        }

        .notification-icon:hover {
            background: #f5f5f5;
            color: var(--primary);
        }

        .notification-badge {
            position: absolute;
            top: 0;
            right: 0;
            background: var(--primary);
            color: white;
            font-size: 0.65rem;
            padding: 2px 5px;
            border-radius: 10px;
            font-weight: bold;
        }

        .user-profile {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 8px 15px;
            background: #f5f5f5;
            border-radius: 50px;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
        }

        .user-profile:hover { background: var(--dark); }
        .user-profile:hover .user-name { color: white; }
        .user-profile:hover .user-avatar { background: var(--primary); }

        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            transition: all 0.3s;
        }

        .user-name {
            font-weight: 600;
            color: var(--dark);
            transition: all 0.3s;
        }

        /* Logout Button */
        .logout-btn {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            color: white;
            text-decoration: none;
            border-radius: 10px;
            font-weight: 600;
            transition: all 0.3s;
            border: none;
            cursor: pointer;
        }

        .logout-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(220, 53, 69, 0.3);
            color: white;
        }

        .logout-btn i {
            font-size: 1rem;
        }

        /* Cards */
        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 100px;
            height: 100px;
            background: linear-gradient(135deg, var(--primary-light) 0%, var(--primary) 100%);
            opacity: 0.1;
            border-radius: 0 0 0 100%;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(255,87,34,0.2);
        }

        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
            margin-bottom: 15px;
        }

        .stat-value {
            font-size: 2rem;
            font-weight: bold;
            color: var(--dark);
            margin-bottom: 5px;
        }

        .stat-label {
            color: #666;
            font-size: 0.95rem;
        }

        /* Buttons */
        .btn-primary {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            border: none;
            border-radius: 10px;
            padding: 10px 25px;
            font-weight: 600;
            transition: all 0.3s;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255,87,34,0.3);
        }

        /* Tables */
        .table-container {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        .table thead th {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            border: none;
            font-weight: 600;
        }

        /* Mobile */
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(100%);
            }
            .sidebar.active {
                transform: translateX(0);
            }
            .main-content {
                margin-right: 0;
            }
            .logout-btn span {
                display: none;
            }
            .user-name {
                display: none;
            }
        }
    </style>
    <?php if (isset($extra_css)) echo $extra_css; ?>
</head>
<body>