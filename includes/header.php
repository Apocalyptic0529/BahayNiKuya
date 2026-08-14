<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

// Handle logout before any HTML is sent. Calling header() after the page
// markup has started causes the "headers already sent" error.
if (isset($_GET['logout']) && $_GET['logout'] == 1) {
    logoutUser();
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle . ' - ' . SITE_NAME : SITE_NAME; ?></title>
    <link rel="icon" type="image/png" href="generated-icon.png">
    <link rel="stylesheet" href="assets/css/styles.css">
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Leaflet CSS for maps -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
    <!-- Additional page-specific styles -->
    <?php if (isset($additionalStyles)) echo $additionalStyles; ?>
</head>
<body>
    <header>
        <div class="logo">
            <a href="index.php">
                <i class="fas fa-home"></i>
                <span><?php echo SITE_NAME; ?></span>
            </a>
        </div>
        <nav>
            <ul class="main-nav">
                <li><a href="index.php">Home</a></li>
                <?php if (!isLoggedIn()): ?>
                    <li><a href="login.php">Login</a></li>
                    <li><a href="register.php">Register</a></li>
                <?php else: ?>
                    <?php if (hasRole('buyer')): ?>
                        <li><a href="buyer_dashboard.php">My Dashboard</a></li>
                    <?php elseif (hasRole('seller')): ?>
                        <li><a href="seller_dashboard.php">Seller Dashboard</a></li>
                    <?php elseif (hasRole('admin')): ?>
                        <li><a href="admin_dashboard.php">Admin Panel</a></li>
                    <?php endif; ?>
                    <li>
                        <a href="#" class="user-menu-toggle">
                            <?php echo $_SESSION['user_name']; ?> 
                            <i class="fas fa-chevron-down"></i>
                        </a>
                        <ul class="user-dropdown">
                            <?php if (hasRole('buyer')): ?>
                                <li><a href="buyer_dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                                <li><a href="buyer_dashboard.php?tab=favorites"><i class="fas fa-heart"></i> Favorites</a></li>
                                <li><a href="buyer_dashboard.php?tab=inquiries"><i class="fas fa-envelope"></i> My Inquiries</a></li>
                            <?php elseif (hasRole('seller')): ?>
                                <li><a href="seller_dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                                <li><a href="add_property.php"><i class="fas fa-plus-circle"></i> Add Property</a></li>
                                <li><a href="seller_dashboard.php?tab=properties"><i class="fas fa-home"></i> My Properties</a></li>
                                <li><a href="seller_dashboard.php?tab=inquiries"><i class="fas fa-envelope"></i> Inquiries</a></li>
                            <?php elseif (hasRole('admin')): ?>
                                <li><a href="admin_dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                                <li><a href="admin_dashboard.php?tab=properties"><i class="fas fa-home"></i> Properties</a></li>
                                <li><a href="admin_dashboard.php?tab=users"><i class="fas fa-users"></i> Users</a></li>
                                <li><a href="admin_dashboard.php?tab=reports"><i class="fas fa-flag"></i> Reports</a></li>
                            <?php endif; ?>
                            <li><a href="index.php?logout=1"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                        </ul>
                    </li>
                <?php endif; ?>
                <li><a href="contact.php">Contact</a></li>
            </ul>
            <div class="mobile-nav-toggle">
                <i class="fas fa-bars"></i>
            </div>
        </nav>
    </header>
    
    <div class="container">
        <?php
        // Display success message if set
        if (isset($_SESSION['success_message'])) {
            echo '<div class="alert alert-success">' . $_SESSION['success_message'] . '</div>';
            unset($_SESSION['success_message']);
        }
        
        // Display error message if set
        if (isset($_SESSION['error_message'])) {
            echo '<div class="alert alert-danger">' . $_SESSION['error_message'] . '</div>';
            unset($_SESSION['error_message']);
        }
        
        ?>
