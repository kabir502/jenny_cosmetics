<?php
// includes/header.php - WITHOUT session_start

// Include session handler FIRST
require_once __DIR__ . '/../session_handler.php';

// Get current page
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SITE_NAME; ?></title>
    
    <!-- Google Fonts - Elegant Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700;800&family=Cormorant+Garamond:wght@300;400;500;600;700&family=Montserrat:wght@300;400;500;600&display=swap" rel="stylesheet">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- AOS Animation Library -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    
    <style>
        /* ===== LUXURY JEWELRY THEME ===== */
        :root {
            --gold: #D4AF37;
            --gold-light: #F4E5C1;
            --gold-dark: #AA8C2F;
            --rose-gold: #B76E79;
            --navy: #1A2A4F;
            --navy-light: #2A3F6F;
            --burgundy: #800020;
            --pearl: #F8F6F0;
            --charcoal: #36454F;
            --transition: all 0.4s cubic-bezier(0.165, 0.84, 0.44, 1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Montserrat', sans-serif;
            background: var(--pearl);
            color: var(--charcoal);
            overflow-x: hidden;
        }

        h1, h2, h3, h4, h5, h6 {
            font-family: 'Playfair Display', serif;
            letter-spacing: 0.5px;
        }

        /* ===== LUXURY NAVBAR ===== */
        .navbar {
            background: linear-gradient(135deg, var(--navy) 0%, var(--navy-light) 100%) !important;
            padding: 1rem 0;
            box-shadow: 0 4px 20px rgba(0,0,0,0.15);
            position: relative;
            border-bottom: 2px solid var(--gold);
        }

        .navbar::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            width: 100%;
            height: 1px;
            background: linear-gradient(90deg, 
                transparent 0%, 
                var(--gold) 20%, 
                var(--gold) 80%, 
                transparent 100%
            );
        }

        .navbar-brand {
            font-family: 'Playfair Display', serif;
            font-weight: 700;
            font-size: 1.8rem;
            background: linear-gradient(135deg, var(--gold-light) 0%, var(--gold) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            position: relative;
            padding-left: 2.5rem;
        }

        .navbar-brand i {
            position: absolute;
            left: 0;
            top: 50%;
            transform: translateY(-50%);
            font-size: 2rem;
            background: linear-gradient(135deg, var(--gold-light) 0%, var(--gold) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            animation: sparkle 2s infinite;
        }

        @keyframes sparkle {
            0%, 100% { opacity: 1; transform: translateY(-50%) scale(1); }
            50% { opacity: 0.8; transform: translateY(-50%) scale(1.1); text-shadow: 0 0 10px var(--gold); }
        }

        .nav-link {
            color: var(--pearl) !important;
            font-weight: 500;
            padding: 0.5rem 1rem !important;
            margin: 0 0.2rem;
            position: relative;
            transition: var(--transition);
        }

        .nav-link::before {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 0;
            height: 2px;
            background: var(--gold);
            transition: var(--transition);
        }

        .nav-link:hover::before,
        .nav-link.active::before {
            width: 80%;
        }

        .nav-link:hover,
        .nav-link.active {
            color: var(--gold) !important;
        }

        .nav-link i {
            margin-right: 0.3rem;
            color: var(--gold);
        }

        /* ===== SEARCH FORM ===== */
        .search-form {
            position: relative;
            margin-right: 1rem;
        }

        .search-form input {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid var(--gold);
            border-radius: 30px;
            padding: 0.5rem 1rem;
            color: white;
            width: 250px;
            transition: var(--transition);
        }

        .search-form input:focus {
            background: rgba(255, 255, 255, 0.2);
            outline: none;
            box-shadow: 0 0 15px rgba(212, 175, 55, 0.3);
            width: 300px;
        }

        .search-form input::placeholder {
            color: rgba(255, 255, 255, 0.7);
            font-family: 'Cormorant Garamond', serif;
            font-style: italic;
        }

        .search-form button {
            position: absolute;
            right: 5px;
            top: 50%;
            transform: translateY(-50%);
            background: transparent;
            border: none;
            color: var(--gold);
            cursor: pointer;
            transition: var(--transition);
        }

        .search-form button:hover {
            transform: translateY(-50%) scale(1.1);
        }

        /* ===== CART BADGE ===== */
        .cart-count {
            position: absolute;
            top: -5px;
            right: -5px;
            background: var(--gold);
            color: var(--navy);
            font-size: 0.7rem;
            padding: 0.2rem 0.5rem;
            border-radius: 50%;
            font-weight: bold;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.1); }
        }

        /* ===== DROPDOWN MENU ===== */
        .dropdown-menu {
            background: var(--navy);
            border: 1px solid var(--gold);
            border-radius: 10px;
            margin-top: 0.5rem;
            padding: 0.5rem;
            animation: slideDown 0.3s ease;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .dropdown-item {
            color: var(--pearl);
            border-radius: 5px;
            transition: var(--transition);
            padding: 0.5rem 1rem;
        }

        .dropdown-item:hover {
            background: var(--gold);
            color: var(--navy);
            transform: translateX(5px);
        }

        .dropdown-item i {
            color: var(--gold);
            width: 20px;
            transition: var(--transition);
        }

        .dropdown-item:hover i {
            color: var(--navy);
        }

        .dropdown-divider {
            border-top: 1px solid var(--gold);
            opacity: 0.3;
        }

        /* ===== ALERTS ===== */
        .alert {
            border: none;
            border-radius: 10px;
            padding: 1rem 1.5rem;
            margin-bottom: 1rem;
            animation: slideInRight 0.5s ease;
            position: relative;
            overflow: hidden;
        }

        .alert::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 5px;
            height: 100%;
        }

        .alert-info {
            background: linear-gradient(135deg, var(--navy-light) 0%, var(--navy) 100%);
            color: var(--pearl);
        }

        .alert-info::before {
            background: var(--gold);
        }

        .alert-danger {
            background: linear-gradient(135deg, #C41E3A 0%, #A51C30 100%);
            color: white;
        }

        .alert-danger::before {
            background: var(--gold);
        }

        .alert-success {
            background: linear-gradient(135deg, #2A6230 0%, #1D4721 100%);
            color: white;
        }

        .alert-success::before {
            background: var(--gold);
        }

        @keyframes slideInRight {
            from {
                opacity: 0;
                transform: translateX(30px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        .btn-close {
            filter: brightness(0) invert(1);
            opacity: 0.8;
            transition: var(--transition);
        }

        .btn-close:hover {
            opacity: 1;
            transform: rotate(90deg);
        }

        /* ===== RESPONSIVE ===== */
        @media (max-width: 991px) {
            .navbar-brand {
                font-size: 1.5rem;
            }
            
            .search-form {
                margin: 1rem 0;
            }
            
            .search-form input {
                width: 100%;
            }
            
            .search-form input:focus {
                width: 100%;
            }
            
            .navbar-nav {
                padding: 1rem 0;
            }
            
            .nav-link::before {
                display: none;
            }
        }

        @media (max-width: 576px) {
            .navbar-brand {
                font-size: 1.2rem;
                padding-left: 2rem;
            }
            
            .navbar-brand i {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-gem"></i>
                <?php echo SITE_NAME; ?>
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="mainNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($current_page == 'index.php') ? 'active' : ''; ?>" href="index.php">
                            <i class="fas fa-home"></i>Home
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($current_page == 'products.php') ? 'active' : ''; ?>" href="products.php">
                            <i class="fas fa-store"></i>Products
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($current_page == 'categories.php') ? 'active' : ''; ?>" href="categories.php">
                            <i class="fas fa-list"></i>Categories
                        </a>
                    </li>
                </ul>
                
                <!-- Search Form -->
                <form class="search-form" action="search.php" method="GET">
                    <input type="search" name="q" placeholder="Search our collection..." aria-label="Search">
                    <button type="submit">
                        <i class="fas fa-search"></i>
                    </button>
                </form>
                
                <!-- User Menu -->
                <ul class="navbar-nav">
                    <li class="nav-item position-relative">
                        <a class="nav-link" href="cart.php">
                            <i class="fas fa-shopping-cart"></i>Cart
                            <?php if (isset($_SESSION['cart']) && count($_SESSION['cart']) > 0): ?>
                                <span class="cart-count"><?php echo count($_SESSION['cart']); ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                    
                    <?php if (isLoggedIn()): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="userDropdown" 
                               data-bs-toggle="dropdown">
                                <i class="fas fa-user"></i>
                                <?php echo htmlspecialchars($_SESSION['username'] ?? 'User'); ?>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="profile.php">
                                    <i class="fas fa-user-circle"></i>My Profile
                                </a></li>
                                <li><a class="dropdown-item" href="orders.php">
                                    <i class="fas fa-shopping-bag"></i>My Orders
                                </a></li>
                                <?php if (isAdminLoggedIn()): ?>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item" href="admin/dashboard.php">
                                        <i class="fas fa-cog"></i>Admin Panel
                                    </a></li>
                                <?php endif; ?>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item text-danger" href="logout.php">
                                    <i class="fas fa-sign-out-alt"></i>Logout
                                </a></li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link <?php echo ($current_page == 'login.php') ? 'active' : ''; ?>" href="login.php">
                                <i class="fas fa-sign-in-alt"></i>Login
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo ($current_page == 'signup.php') ? 'active' : ''; ?>" href="signup.php">
                                <i class="fas fa-user-plus"></i>Sign Up
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>
    
    <!-- Main Content Container -->
    <main class="container mt-4">
        <?php if (isset($_SESSION['message'])): ?>
            <div class="alert alert-info alert-dismissible fade show" role="alert">
                <i class="fas fa-info-circle me-2"></i>
                <?php echo htmlspecialchars($_SESSION['message']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['message']); ?>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i>
                <?php echo htmlspecialchars($_SESSION['error']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i>
                <?php echo htmlspecialchars($_SESSION['success']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>