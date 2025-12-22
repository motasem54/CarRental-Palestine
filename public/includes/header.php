<?php
if (!isset($page_title)) {
    $page_title = SITE_NAME . ' - أفضل خدمة تأجير سيارات في فلسطين';
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="أفضل خدمة تأجير سيارات في فلسطين - أسعار منافسة وسيارات حديثة">
    <title><?php echo $page_title; ?></title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800;900&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary: #FF5722;
            --primary-dark: #E64A19;
            --primary-light: #FF7043;
            --dark: #1a1a2e;
            --darker: #16213e;
            --accent: #0f3460;
        }
        
        * { 
            font-family: 'Cairo', sans-serif;
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        html { scroll-behavior: smooth; }
        body { background: #f8f9fa; overflow-x: hidden; }
        
        /* Navbar */
        .navbar {
            background: rgba(26, 26, 46, 0.98) !important;
            backdrop-filter: blur(10px);
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            padding: 15px 0;
        }
        
        .navbar.scrolled { background: var(--dark) !important; }
        
        .navbar-brand {
            font-size: 1.8rem;
            font-weight: 900;
            color: white !important;
            transition: 0.3s;
        }
        
        .navbar-brand:hover { color: var(--primary) !important; }
        
        .nav-link {
            color: rgba(255,255,255,0.9) !important;
            font-weight: 600;
            transition: 0.3s;
            padding: 8px 20px !important;
            position: relative;
        }
        
        .nav-link::after {
            content: '';
            position: absolute;
            bottom: 0;
            right: 50%;
            width: 0;
            height: 2px;
            background: var(--primary);
            transition: 0.3s;
        }
        
        .nav-link:hover::after {
            width: 80%;
            right: 10%;
        }
        
        .nav-link:hover,
        .nav-link.active {
            color: var(--primary) !important;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            border: none;
            padding: 12px 30px;
            font-weight: 700;
            border-radius: 50px;
            transition: all 0.3s;
            box-shadow: 0 5px 20px rgba(255, 87, 34, 0.3);
        }
        
        .btn-primary:hover {
            background: linear-gradient(135deg, var(--primary-dark) 0%, var(--primary) 100%);
            transform: translateY(-3px);
            box-shadow: 0 8px 30px rgba(255, 87, 34, 0.5);
        }
        
        /* Page Header */
        .page-header {
            background: linear-gradient(135deg, var(--darker) 0%, var(--dark) 50%, var(--accent) 100%);
            padding: 100px 0 60px 0;
            margin-top: 70px;
            position: relative;
            overflow: hidden;
        }
        
        .page-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: radial-gradient(circle at 30% 50%, rgba(255, 87, 34, 0.2) 0%, transparent 50%);
        }
        
        .page-header h1 {
            color: white;
            font-weight: 900;
            font-size: 3rem;
            text-shadow: 2px 2px 10px rgba(0,0,0,0.3);
        }
        
        .breadcrumb {
            background: transparent;
            margin: 0;
        }
        
        .breadcrumb-item a {
            color: rgba(255,255,255,0.8);
            text-decoration: none;
        }
        
        .breadcrumb-item.active,
        .breadcrumb-item a:hover {
            color: var(--primary);
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark fixed-top">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-car me-2"></i>
                <?php echo SITE_NAME; ?>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item">
                        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>" href="index.php">الرئيسية</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'cars.php' ? 'active' : ''; ?>" href="cars.php">السيارات</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'services.php' ? 'active' : ''; ?>" href="services.php">الخدمات</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'about.php' ? 'active' : ''; ?>" href="about.php">من نحن</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'contact.php' ? 'active' : ''; ?>" href="contact.php">اتصل بنا</a>
                    </li>
                </ul>
                <a href="<?php echo ADMIN_URL; ?>/login.php" class="btn btn-primary">
                    <i class="fas fa-sign-in-alt me-2"></i>تسجيل الدخول
                </a>
            </div>
        </div>
    </nav>
    
    <script>
        window.addEventListener('scroll', function() {
            const navbar = document.querySelector('.navbar');
            if (window.scrollY > 50) {
                navbar.classList.add('scrolled');
            } else {
                navbar.classList.remove('scrolled');
            }
        });
    </script>