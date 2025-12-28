<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Load dynamic colors
$db = Database::getInstance()->getConnection();
try {
    $stmt = $db->query("SELECT setting_key, setting_value FROM system_settings WHERE setting_key IN ('site_primary_color', 'site_secondary_color', 'site_logo_text', 'site_logo_icon')");
    $color_settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    
    $primary_color = $color_settings['site_primary_color'] ?? '#FF5722';
    $secondary_color = $color_settings['site_secondary_color'] ?? '#E64A19';
    $logo_text = $color_settings['site_logo_text'] ?? SITE_NAME;
    $logo_icon = $color_settings['site_logo_icon'] ?? 'fa-car';
} catch (Exception $e) {
    $primary_color = '#FF5722';
    $secondary_color = '#E64A19';
    $logo_text = SITE_NAME;
    $logo_icon = 'fa-car';
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
    <meta name="description" content="نظام تأجير سيارات في فلسطين - أفضل الأسعار وسيارات حديثة">
    <meta name="keywords" content="تأجير سيارات, فلسطين, رام الله, نابلس">
    <title><?php echo $page_title ?? SITE_NAME; ?></title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="<?php echo ASSETS_URL; ?>/images/favicon.ico">
    
    <!-- Bootstrap 5 RTL -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    
    <!-- Font Awesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Google Fonts - Cairo -->
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700;800;900&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary: <?php echo $primary_color; ?>;
            --primary-dark: <?php echo $secondary_color; ?>;
            --primary-light: <?php echo adjustBrightness($primary_color, 30); ?>;
            --dark: #1a1a1a;
            --darker: #121212;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Cairo', sans-serif;
            background: #f8f9fa;
            overflow-x: hidden;
        }
        
        /* Navbar */
        .navbar {
            background: linear-gradient(135deg, var(--darker) 0%, var(--dark) 100%);
            padding: 15px 0;
            box-shadow: 0 2px 15px rgba(0,0,0,0.3);
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
        }
        
        .navbar-brand {
            display: flex;
            align-items: center;
            gap: 12px;
            color: white !important;
            font-weight: 900;
            font-size: 1.5rem;
            transition: all 0.3s;
        }
        
        .navbar-brand:hover {
            transform: scale(1.05);
        }
        
        .navbar-brand .logo-icon {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
            color: white;
            box-shadow: 0 4px 15px rgba(255, 87, 34, 0.4);
        }
        
        .navbar-brand .logo-text {
            display: flex;
            flex-direction: column;
            line-height: 1.2;
        }
        
        .navbar-brand .logo-text .main-text {
            font-size: 1.3rem;
            font-weight: 900;
        }
        
        .navbar-brand .logo-text .sub-text {
            font-size: 0.75rem;
            color: #999;
            font-weight: 400;
        }
        
        .navbar-nav .nav-link {
            color: rgba(255,255,255,0.8) !important;
            font-weight: 600;
            padding: 10px 20px !important;
            margin: 0 5px;
            border-radius: 8px;
            transition: all 0.3s;
            position: relative;
        }
        
        .navbar-nav .nav-link::before {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 0;
            height: 3px;
            background: var(--primary);
            transition: width 0.3s;
        }
        
        .navbar-nav .nav-link:hover,
        .navbar-nav .nav-link.active {
            color: white !important;
            background: rgba(255, 87, 34, 0.15);
        }
        
        .navbar-nav .nav-link:hover::before,
        .navbar-nav .nav-link.active::before {
            width: 80%;
        }
        
        .navbar-toggler {
            border: 2px solid var(--primary);
            padding: 8px 12px;
        }
        
        .navbar-toggler-icon {
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 30 30'%3e%3cpath stroke='rgba(255, 87, 34, 1)' stroke-linecap='round' stroke-miterlimit='10' stroke-width='2' d='M4 7h22M4 15h22M4 23h22'/%3e%3c/svg%3e");
        }
        
        /* Mobile Menu */
        @media (max-width: 991px) {
            .navbar-collapse {
                background: var(--darker);
                padding: 20px;
                border-radius: 10px;
                margin-top: 15px;
                box-shadow: 0 5px 20px rgba(0,0,0,0.3);
            }
            
            .navbar-nav .nav-link {
                margin: 5px 0;
            }
            
            .navbar-brand .logo-text .main-text {
                font-size: 1.1rem;
            }
            
            .navbar-brand .logo-icon {
                width: 40px;
                height: 40px;
                font-size: 1.4rem;
            }
        }
        
        /* Breadcrumb */
        .breadcrumb-section {
            background: linear-gradient(135deg, var(--darker), var(--dark));
            padding: 100px 0 40px 0;
            margin-top: 70px;
            color: white;
            text-align: center;
        }
        
        .breadcrumb-section h2 {
            font-weight: 900;
            font-size: 2.5rem;
            margin-bottom: 15px;
        }
        
        .breadcrumb {
            background: transparent;
            justify-content: center;
            margin-bottom: 0;
        }
        
        .breadcrumb-item {
            color: rgba(255,255,255,0.7);
        }
        
        .breadcrumb-item.active {
            color: var(--primary);
        }
        
        .breadcrumb-item + .breadcrumb-item::before {
            color: rgba(255,255,255,0.5);
        }
        
        .breadcrumb-item a {
            color: white;
            text-decoration: none;
        }
        
        .breadcrumb-item a:hover {
            color: var(--primary);
        }
        
        /* Mobile Breadcrumb */
        @media (max-width: 768px) {
            .breadcrumb-section {
                padding: 80px 0 30px 0;
            }
            
            .breadcrumb-section h2 {
                font-size: 1.8rem;
            }
        }
        
        /* Footer */
        footer {
            background: linear-gradient(135deg, var(--darker), var(--dark));
            color: white;
            padding: 50px 0 20px 0;
            margin-top: 80px;
        }
        
        footer h5 {
            color: var(--primary);
            font-weight: 700;
            margin-bottom: 20px;
        }
        
        footer a {
            color: rgba(255,255,255,0.7);
            text-decoration: none;
            transition: 0.3s;
        }
        
        footer a:hover {
            color: var(--primary);
            padding-right: 5px;
        }
        
        footer .social-links a {
            display: inline-block;
            width: 40px;
            height: 40px;
            background: rgba(255, 87, 34, 0.2);
            border-radius: 50%;
            text-align: center;
            line-height: 40px;
            margin: 0 5px;
        }
        
        footer .social-links a:hover {
            background: var(--primary);
            color: white;
            transform: translateY(-3px);
        }
        
        /* Scroll to Top Button */
        #scrollTop {
            position: fixed;
            bottom: 30px;
            left: 30px;
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            border: none;
            border-radius: 50%;
            font-size: 1.5rem;
            cursor: pointer;
            display: none;
            z-index: 999;
            box-shadow: 0 4px 15px rgba(255, 87, 34, 0.4);
            transition: all 0.3s;
        }
        
        #scrollTop:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 20px rgba(255, 87, 34, 0.6);
        }
        
        #scrollTop.show {
            display: block;
            animation: fadeIn 0.3s;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        /* Responsive */
        @media (max-width: 576px) {
            #scrollTop {
                bottom: 20px;
                left: 20px;
                width: 45px;
                height: 45px;
                font-size: 1.2rem;
            }
        }
    </style>
    
    <?php if (isset($extra_css)) echo $extra_css; ?>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <div class="logo-icon">
                    <i class="fas <?php echo $logo_icon; ?>"></i>
                </div>
                <div class="logo-text">
                    <span class="main-text"><?php echo $logo_text; ?></span>
                    <span class="sub-text">🇵🇸 فلسطين</span>
                </div>
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>" href="index.php">
                            <i class="fas fa-home me-2"></i>الرئيسية
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'cars.php' ? 'active' : ''; ?>" href="cars.php">
                            <i class="fas fa-car me-2"></i>السيارات
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'about.php' ? 'active' : ''; ?>" href="about.php">
                            <i class="fas fa-info-circle me-2"></i>من نحن
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'services.php' ? 'active' : ''; ?>" href="services.php">
                            <i class="fas fa-cogs me-2"></i>الخدمات
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'contact.php' ? 'active' : ''; ?>" href="contact.php">
                            <i class="fas fa-envelope me-2"></i>اتصل بنا
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    
    <!-- Scroll to Top -->
    <button id="scrollTop" onclick="scrollToTop()">
        <i class="fas fa-arrow-up"></i>
    </button>

<?php
// Helper function for color adjustment
function adjustBrightness($hex, $steps) {
    $hex = str_replace('#', '', $hex);
    $r = hexdec(substr($hex, 0, 2));
    $g = hexdec(substr($hex, 2, 2));
    $b = hexdec(substr($hex, 4, 2));
    
    $r = max(0, min(255, $r + $steps));
    $g = max(0, min(255, $g + $steps));
    $b = max(0, min(255, $b + $steps));
    
    return '#' . str_pad(dechex($r), 2, '0', STR_PAD_LEFT)
                . str_pad(dechex($g), 2, '0', STR_PAD_LEFT)
                . str_pad(dechex($b), 2, '0', STR_PAD_LEFT);
}
?>