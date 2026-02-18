<?php
// includes/admin_header.php - Admin Header
// Start session if not started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: ../admin/login.php");
    exit();
}

// Get admin info
$admin_name = $_SESSION['admin_name'] ?? 'Admin';
$admin_role = $_SESSION['admin_role'] ?? 'Administrator';
$admin_email = $_SESSION['admin_email'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - <?php echo defined('SITE_NAME') ? SITE_NAME : 'Jenny\'s Cosmetics & Jewelry'; ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        /* ===== PROFESSIONAL CORPORATE ADMIN STYLES ===== */
        :root {
            --primary: #1e3a5f;
            --primary-light: #2b4c7c;
            --primary-dark: #0f2a44;
            --secondary: #2c3e50;
            --success: #198754;
            --warning: #ffc107;
            --danger: #dc3545;
            --info: #0dcaf0;
            --dark: #212529;
            --dark-gray: #6c757d;
            --light: #f8fafc;
            --light-gray: #e9ecef;
            --border: #dee2e6;
            
            --sidebar-width: 280px;
            --header-height: 70px;
            --transition: all 0.3s ease;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--light);
            color: var(--dark);
            line-height: 1.6;
            overflow-x: hidden;
        }

        /* Layout */
        .admin-wrapper {
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar */
        .sidebar {
            width: var(--sidebar-width);
            background: white;
            border-right: 1px solid var(--border);
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            transition: var(--transition);
            z-index: 1000;
            box-shadow: 2px 0 10px rgba(0,0,0,0.02);
        }

        .sidebar-header {
            height: var(--header-height);
            padding: 1rem 1.5rem;
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            background: white;
        }

        .sidebar-header .logo {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .sidebar-header .logo i {
            font-size: 1.8rem;
            color: var(--primary);
        }

        .sidebar-header .logo span {
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .sidebar-nav {
            padding: 1.5rem 0;
        }

        .nav-section {
            margin-bottom: 1.5rem;
        }

        .nav-section-title {
            padding: 0 1.5rem;
            margin-bottom: 0.5rem;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--dark-gray);
        }

        .nav-item {
            list-style: none;
        }

        .nav-link {
            display: flex;
            align-items: center;
            padding: 0.75rem 1.5rem;
            color: var(--dark);
            text-decoration: none;
            transition: var(--transition);
            gap: 0.75rem;
            border-left: 3px solid transparent;
        }

        .nav-link:hover {
            background: var(--light);
            border-left-color: var(--primary);
            color: var(--primary);
        }

        .nav-link.active {
            background: var(--light);
            border-left-color: var(--primary);
            color: var(--primary);
            font-weight: 500;
        }

        .nav-link i {
            width: 20px;
            color: var(--dark-gray);
            transition: var(--transition);
        }

        .nav-link:hover i,
        .nav-link.active i {
            color: var(--primary);
        }

        .nav-link .badge {
            margin-left: auto;
            background: var(--primary-light);
            color: white;
            font-size: 0.7rem;
            padding: 0.2rem 0.5rem;
            border-radius: 20px;
        }

        /* Main Content */
        .main-content {
            flex: 1;
            margin-left: var(--sidebar-width);
            min-height: 100vh;
            background: var(--light);
            transition: var(--transition);
        }

        /* Top Navbar */
        .top-navbar {
            height: var(--header-height);
            background: white;
            border-bottom: 1px solid var(--border);
            padding: 0 2rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 999;
            box-shadow: 0 2px 10px rgba(0,0,0,0.02);
        }

        .navbar-left {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .menu-toggle {
            display: none;
            background: none;
            border: none;
            font-size: 1.5rem;
            color: var(--dark);
            cursor: pointer;
            width: 40px;
            height: 40px;
            border-radius: 8px;
            transition: var(--transition);
        }

        .menu-toggle:hover {
            background: var(--light);
            color: var(--primary);
        }

        .page-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--dark);
            margin: 0;
        }

        .page-title i {
            color: var(--primary);
            margin-right: 0.5rem;
        }

        .navbar-right {
            display: flex;
            align-items: center;
            gap: 1.5rem;
        }

        .search-box {
            position: relative;
        }

        .search-box input {
            width: 300px;
            padding: 0.5rem 1rem 0.5rem 2.5rem;
            border: 1px solid var(--border);
            border-radius: 8px;
            font-size: 0.9rem;
            transition: var(--transition);
            background: var(--light);
        }

        .search-box input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(30,58,95,0.1);
        }

        .search-box i {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--dark-gray);
            font-size: 0.9rem;
        }

        .navbar-icons {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .icon-btn {
            background: none;
            border: none;
            font-size: 1.2rem;
            color: var(--dark-gray);
            cursor: pointer;
            position: relative;
            width: 40px;
            height: 40px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: var(--transition);
        }

        .icon-btn:hover {
            background: var(--light);
            color: var(--primary);
        }

        .icon-btn .badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background: var(--danger);
            color: white;
            font-size: 0.7rem;
            padding: 0.2rem 0.4rem;
            border-radius: 20px;
            min-width: 18px;
        }

        .user-dropdown {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.5rem 1rem;
            background: var(--light);
            border-radius: 8px;
            cursor: pointer;
            transition: var(--transition);
            border: 1px solid var(--border);
        }

        .user-dropdown:hover {
            background: white;
            border-color: var(--primary);
        }

        .user-avatar {
            width: 36px;
            height: 36px;
            border-radius: 8px;
            background: var(--primary-light);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 1rem;
        }

        .user-info {
            line-height: 1.4;
        }

        .user-name {
            font-weight: 600;
            color: var(--dark);
            font-size: 0.9rem;
        }

        .user-role {
            font-size: 0.75rem;
            color: var(--dark-gray);
        }

        .user-dropdown i {
            color: var(--dark-gray);
            font-size: 0.8rem;
            margin-left: 0.5rem;
        }

        /* Content Wrapper */
        .content-wrapper {
            padding: 2rem;
        }

        /* Dropdown Menu */
        .dropdown-menu {
            border: 1px solid var(--border);
            border-radius: 8px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            padding: 0.5rem 0;
        }

        .dropdown-item {
            padding: 0.6rem 1.5rem;
            color: var(--dark);
            transition: var(--transition);
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .dropdown-item:hover {
            background: var(--light);
            color: var(--primary);
        }

        .dropdown-item i {
            width: 18px;
            color: var(--dark-gray);
        }

        .dropdown-item:hover i {
            color: var(--primary);
        }

        .dropdown-divider {
            border-top: 1px solid var(--border);
            margin: 0.5rem 0;
        }

        /* Responsive */
        @media (max-width: 1024px) {
            .search-box input {
                width: 200px;
            }
        }

        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }

            .sidebar.mobile-hidden {
                transform: translateX(0);
            }

            .main-content {
                margin-left: 0;
            }

            .main-content.mobile-full {
                margin-left: 0;
            }

            .menu-toggle {
                display: flex;
                align-items: center;
                justify-content: center;
            }

            .search-box input {
                width: 150px;
            }

            .user-info {
                display: none;
            }

            .user-dropdown {
                padding: 0.5rem;
            }

            .content-wrapper {
                padding: 1.5rem;
            }
        }

        @media (max-width: 576px) {
            .top-navbar {
                padding: 0 1rem;
            }

            .search-box {
                display: none;
            }

            .navbar-icons {
                gap: 0.5rem;
            }

            .content-wrapper {
                padding: 1rem;
            }
        }
    </style>
</head>
<body>
    <div class="admin-wrapper">
        <!-- Sidebar -->
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <a href="../admin/dashboard.php" class="logo">
                    <i class="fas fa-gem"></i>
                    <span>Admin</span>
                </a>
            </div>
            
            <nav class="sidebar-nav">
                <div class="nav-section">
                    <div class="nav-section-title">Main</div>
                    <ul class="nav">
                        <li class="nav-item">
                            <a href="../admin/dashboard.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
                                <i class="fas fa-tachometer-alt"></i>
                                <span>Dashboard</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="../admin/orders.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'orders.php' ? 'active' : ''; ?>">
                                <i class="fas fa-shopping-cart"></i>
                                <span>Orders</span>
                                <?php
                                // Include database connection for count
                                if (file_exists('../config/database.php')) {
                                    require_once '../config/database.php';
                                    $pending_query = "SELECT COUNT(*) as count FROM orders WHERE status = 'Pending'";
                                    $pending_result = mysqli_query($connection ?? null, $pending_query);
                                    $pending_count = $pending_result ? mysqli_fetch_assoc($pending_result)['count'] : 0;
                                    if ($pending_count > 0):
                                    ?>
                                    <span class="badge"><?php echo $pending_count; ?></span>
                                    <?php endif; 
                                } ?>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="../admin/products.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'products.php' ? 'active' : ''; ?>">
                                <i class="fas fa-box"></i>
                                <span>Products</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="../admin/categories.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'categories.php' ? 'active' : ''; ?>">
                                <i class="fas fa-folder"></i>
                                <span>Categories</span>
                            </a>
                        </li>
                    </ul>
                </div>
                
                <div class="nav-section">
                    <div class="nav-section-title">Management</div>
                    <ul class="nav">
                        <li class="nav-item">
                            <a href="../admin/users.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'users.php' ? 'active' : ''; ?>">
                                <i class="fas fa-users"></i>
                                <span>Users</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="../admin/reviews.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'reviews.php' ? 'active' : ''; ?>">
                                <i class="fas fa-star"></i>
                                <span>Reviews</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="../admin/inventory.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'inventory.php' ? 'active' : ''; ?>">
                                <i class="fas fa-warehouse"></i>
                                <span>Inventory</span>
                            </a>
                        </li>
                    </ul>
                </div>
                
                <div class="nav-section">
                    <div class="nav-section-title">Reports</div>
                    <ul class="nav">
                        <li class="nav-item">
                            <a href="../admin/sales_report.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'sales_report.php' ? 'active' : ''; ?>">
                                <i class="fas fa-chart-line"></i>
                                <span>Sales Report</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="../admin/analytics.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'analytics.php' ? 'active' : ''; ?>">
                                <i class="fas fa-chart-bar"></i>
                                <span>Analytics</span>
                            </a>
                        </li>
                    </ul>
                </div>
                
                <div class="nav-section">
                    <div class="nav-section-title">Settings</div>
                    <ul class="nav">
                        <li class="nav-item">
                            <a href="../admin/profile.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'profile.php' ? 'active' : ''; ?>">
                                <i class="fas fa-user-circle"></i>
                                <span>Profile</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="../admin/settings.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'settings.php' ? 'active' : ''; ?>">
                                <i class="fas fa-cog"></i>
                                <span>Settings</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="#" class="nav-link" data-bs-toggle="modal" data-bs-target="#logoutModal">
                                <i class="fas fa-sign-out-alt"></i>
                                <span>Logout</span>
                            </a>
                        </li>
                    </ul>
                </div>
            </nav>
        </aside>
        
        <!-- Main Content -->
        <main class="main-content" id="mainContent">
            <!-- Top Navbar -->
            <header class="top-navbar">
                <div class="navbar-left">
                    <button class="menu-toggle" onclick="toggleSidebar()">
                        <i class="fas fa-bars"></i>
                    </button>
                    <h1 class="page-title">
                        <?php
                        $page_title = ucfirst(str_replace('.php', '', basename($_SERVER['PHP_SELF'])));
                        if ($page_title == 'Dashboard') {
                            echo '<i class="fas fa-tachometer-alt"></i> Dashboard';
                        } elseif ($page_title == 'Orders') {
                            echo '<i class="fas fa-shopping-cart"></i> Orders';
                        } elseif ($page_title == 'Products') {
                            echo '<i class="fas fa-box"></i> Products';
                        } elseif ($page_title == 'Users') {
                            echo '<i class="fas fa-users"></i> Users';
                        } else {
                            echo '<i class="fas fa-cog"></i> ' . $page_title;
                        }
                        ?>
                    </h1>
                </div>
                
                <div class="navbar-right">
                    <div class="search-box">
                        <input type="text" placeholder="Search..." id="globalSearch">
                        <i class="fas fa-search"></i>
                    </div>
                    
                    <div class="navbar-icons">
                        <button class="icon-btn" id="notificationsBtn">
                            <i class="far fa-bell"></i>
                            <span class="badge">3</span>
                        </button>
                        <button class="icon-btn" id="messagesBtn">
                            <i class="far fa-envelope"></i>
                        </button>
                        
                        <div class="dropdown">
                            <div class="user-dropdown" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                <div class="user-avatar">
                                    <?php echo strtoupper(substr($admin_name, 0, 1)); ?>
                                </div>
                                <div class="user-info">
                                    <div class="user-name"><?php echo htmlspecialchars($admin_name); ?></div>
                                    <div class="user-role"><?php echo htmlspecialchars($admin_role); ?></div>
                                </div>
                                <i class="fas fa-chevron-down"></i>
                            </div>
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                                <li><a class="dropdown-item" href="../admin/profile.php"><i class="fas fa-user-circle"></i> Profile</a></li>
                                <li><a class="dropdown-item" href="../admin/settings.php"><i class="fas fa-cog"></i> Settings</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#logoutModal"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </header>
            
            <!-- Content Wrapper - Page content will be injected here -->
            <div class="content-wrapper">