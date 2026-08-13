<?php
$pageTitle = "Buyer Dashboard";
require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

// Require buyer authentication
requireAuth('buyer');

// Get current tab
$currentTab = isset($_GET['tab']) ? $_GET['tab'] : 'dashboard';

// Get current user
$user = getCurrentUser();

// Get favorites
$favorites = getFavoriteProperties($_SESSION['user_id']);

// Get inquiries
$inquiries = getUserInquiries($_SESSION['user_id'], 'buyer');

// Get inquiry statistics
$inquiryStats = getInquiryStatistics($_SESSION['user_id'], 'buyer');

// Additional scripts
$additionalScripts = '<script src="assets/js/dashboard.js"></script>';

require_once 'includes/header.php';
?>

<h1>Buyer Dashboard</h1>

<div class="dashboard">
    <div class="dashboard-sidebar">
        <ul class="dashboard-nav">
        <li>
            <a href="seller_application.php" class="btn btn-success">
                <i class="fas fa-user-tie"></i> Become a Seller
            </a>
        </li>
            <li>
                <a href="?tab=dashboard" class="<?php echo $currentTab === 'dashboard' ? 'active' : ''; ?>">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a>
            </li>
            <li>
                <a href="?tab=favorites" class="<?php echo $currentTab === 'favorites' ? 'active' : ''; ?>">
                    <i class="fas fa-heart"></i> Favorite Properties
                </a>
            </li>
            <li>
                <a href="?tab=inquiries" class="<?php echo $currentTab === 'inquiries' ? 'active' : ''; ?>">
                    <i class="fas fa-envelope"></i> My Inquiries
                </a>
            </li>
            <li>
                <a href="?tab=profile" class="<?php echo $currentTab === 'profile' ? 'active' : ''; ?>">
                    <i class="fas fa-user"></i> My Profile
                </a>
            </li>
            <li>
                <a href="index.php?logout=1">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </li>
        </ul>
    </div>

    <div class="dashboard-content">
        <?php if ($currentTab === 'dashboard'): ?>
            <!-- Dashboard Overview -->
            <div class="tab-content active" id="dashboard">
                <h2>Welcome, <?php echo $user['name']; ?>!</h2>

                <div class="stats-grid">
                    <div class="stat-card">
                        <i class="fas fa-heart"></i>
                        <h3><?php echo count($favorites); ?></h3>
                        <p>Saved Properties</p>
                    </div>

                    <div class="stat-card">
                        <i class="fas fa-envelope"></i>
                        <h3><?php echo $inquiryStats['total']; ?></h3>
                        <p>Total Inquiries</p>
                    </div>

                    <div class="stat-card">
                        <i class="fas fa-envelope-open"></i>
                        <h3><?php echo $inquiryStats['replied']; ?></h3>
                        <p>Replied Inquiries</p>
                    </div>
                </div>

                <h3>Recent Favorites</h3>
                <?php if (empty($favorites)): ?>
                    <div class="alert alert-info">You haven't saved any properties yet. <a href="index.php">Browse properties</a> and add some to your favorites.</div>
                <?php else: ?>
                    <div class="property-grid">
                        <?php 
                        // Display only the first 3 favorites
                        $recentFavorites = array_slice($favorites, 0, 3);
                        foreach($recentFavorites as $property): 
                        ?>
                            <div class="card">
                                <div class="card-image">
                                    <img src="<?php echo $property['image1']; ?>" alt="<?php echo $property['title']; ?>">
                                </div>
                                <div class="card-body">
                                    <div class="card-price"><?php echo formatCurrency($property['price']); ?></div>
                                    <h3 class="card-title"><?php echo $property['title']; ?></h3>
                                    <p class="card-text"><?php echo substr($property['description'], 0, 100) . '...'; ?></p>
                                    <div class="card-details">
                                        <span><i class="fas fa-bed"></i> <?php echo $property['bedrooms']; ?> Beds</span>
                                        <span><i class="fas fa-bath"></i> <?php echo $property['bathrooms']; ?> Baths</span>
                                        <span><i class="fas fa-ruler-combined"></i> <?php echo $property['area']; ?> sqft</span>
                                    </div>
                                    <a href="property_details.php?id=<?php echo $property['id']; ?>" class="btn btn-primary btn-block">View Details</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <?php if (count($favorites) > 3): ?>
                        <p class="text-center">
                            <a href="?tab=favorites" class="btn btn-secondary">View All Favorites</a>
                        </p>
                    <?php endif; ?>
                <?php endif; ?>

                <h3>Recent Inquiries</h3>
                <?php if (empty($inquiries)): ?>
                    <div class="alert alert-info">You haven't made any inquiries yet. Contact sellers about properties you're interested in.</div>
                <?php else: ?>
                    <div class="table-container">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Property</th>
                                    <th>Date</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                // Display only the first 5 inquiries
                                $recentInquiries = array_slice($inquiries, 0, 5);
                                foreach($recentInquiries as $inquiry): 
                                ?>
                                    <tr>
                                        <td>
                                            <div class="property-mini">
                                                <img src="<?php echo $inquiry['image1']; ?>" alt="<?php echo $inquiry['property_title']; ?>" width="50" height="50">
                                                <span><?php echo $inquiry['property_title']; ?></span>
                                            </div>
                                        </td>
                                        <td><?php echo formatDate($inquiry['created_at']); ?></td>
                                        <td>
                                            <span class="badge <?php 
                                                switch($inquiry['status']) {
                                                    case 'new': echo 'badge-primary'; break;
                                                    case 'read': echo 'badge-info'; break;
                                                    case 'replied': echo 'badge-success'; break;
                                                    case 'closed': echo 'badge-secondary'; break;
                                                }
                                            ?>">
                                                <?php echo ucfirst($inquiry['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <a href="?tab=inquiries#inquiry-<?php echo $inquiry['id']; ?>" class="btn btn-primary btn-sm">View</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php if (count($inquiries) > 5): ?>
                        <p class="text-center">
                            <a href="?tab=inquiries" class="btn btn-secondary">View All Inquiries</a>
                        </p>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        <?php elseif ($currentTab === 'favorites'): ?>
            <!-- Favorites Tab -->
            <div class="tab-content active" id="favorites">
                <h2>My Favorite Properties</h2>

                <?php if (empty($favorites)): ?>
                    <div class="alert alert-info">You haven't saved any properties yet. <a href="index.php">Browse properties</a> and add some to your favorites.</div>
                <?php else: ?>
                    <div class="property-grid">
                        <?php foreach($favorites as $property): ?>
                            <div class="card">
                                <div class="card-image">
                                    <img src="<?php echo $property['image1']; ?>" alt="<?php echo $property['title']; ?>">
                                </div>
                                <div class="card-body">
                                    <div class="card-price"><?php echo formatCurrency($property['price']); ?></div>
                                    <h3 class="card-title"><?php echo $property['title']; ?></h3>
                                    <p class="card-text"><?php echo substr($property['description'], 0, 100) . '...'; ?></p>
                                    <div class="card-details">
                                        <span><i class="fas fa-bed"></i> <?php echo $property['bedrooms']; ?> Beds</span>
                                        <span><i class="fas fa-bath"></i> <?php echo $property['bathrooms']; ?> Baths</span>
                                        <span><i class="fas fa-ruler-combined"></i> <?php echo $property['area']; ?> sqft</span>
                                    </div>
                                    <a href="property_details.php?id=<?php echo $property['id']; ?>" class="btn btn-primary">View Details</a>
                                    <a href="#" class="btn btn-danger favorite-btn favorited" data-property-id="<?php echo $property['id']; ?>">
                                        <i class="fas fa-heart"></i> Remove
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php elseif ($currentTab === 'inquiries'): ?>
            <!-- Inquiries Tab -->
            <div class="tab-content active" id="inquiries">
                <h2>My Inquiries</h2>

                <?php if (empty($inquiries)): ?>
                    <div class="alert alert-info">You haven't made any inquiries yet. Contact sellers about properties you're interested in.</div>
                <?php else: ?>
                    <div class="inquiries-list">
                        <?php foreach($inquiries as $inquiry): ?>
                            <div class="inquiry-card" id="inquiry-<?php echo $inquiry['id']; ?>">
                                <div class="inquiry-header">
                                    <div class="property-mini">
                                        <img src="<?php echo $inquiry['image1']; ?>" alt="<?php echo $inquiry['property_title']; ?>" width="80" height="80">
                                        <div>
                                            <h3><?php echo $inquiry['property_title']; ?></h3>
                                            <p>Seller: <?php echo $inquiry['seller_name']; ?></p>
                                            <p>Date: <?php echo formatDate($inquiry['created_at']); ?></p>
                                            <span class="badge <?php 
                                                switch($inquiry['status']) {
                                                    case 'new': echo 'badge-primary'; break;
                                                    case 'read': echo 'badge-info'; break;
                                                    case 'replied': echo 'badge-success'; break;
                                                    case 'closed': echo 'badge-secondary'; break;
                                                }
                                            ?>">
                                                <?php echo ucfirst($inquiry['status']); ?>
                                            </span>
                                        </div>
                                    </div>
                                    <a href="property_details.php?id=<?php echo $inquiry['property_id']; ?>" class="btn btn-primary btn-sm">View Property</a>
                                </div>

                                <div class="inquiry-body">
                                    <div class="message-box">
                                        <h4>Your Inquiry</h4>
                                        <div class="message buyer-message">
                                            <p><?php echo nl2br($inquiry['message']); ?></p>
                                            <small class="text-muted">Sent on: <?php echo formatDate($inquiry['created_at']); ?></small>
                                        </div>
                                        <?php if ($inquiry['status'] === 'replied' && !empty($inquiry['reply_message'])): ?>
                                        <div class="seller-reply mt-3">
                                            <h4>Seller's Reply</h4>
                                            <div class="message seller-message">
                                                <p><?php echo nl2br($inquiry['reply_message']); ?></p>
                                                <small class="text-muted">Replied on: <?php echo formatDate($inquiry['reply_date']); ?></small>
                                            </div>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php elseif ($currentTab === 'profile'): ?>
            <!-- Profile Tab -->
            <div class="tab-content active" id="profile">
                <h2>My Profile</h2>

                <div class="profile-info">
                    <form action="update_profile.php" method="POST" class="needs-validation">
                        <div class="form-group">
                            <label for="name" class="form-label">Full Name</label>
                            <input type="text" id="name" name="name" class="form-control" value="<?php echo $user['name']; ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="email" class="form-label">Email Address</label>
                            <input type="email" id="email" name="email" class="form-control" value="<?php echo $user['email']; ?>" required readonly>
                            <small class="text-muted">Email address cannot be changed.</small>
                        </div>

                        <div class="form-group">
                            <label for="phone" class="form-label">Phone Number</label>
                            <input type="tel" id="phone" name="phone" class="form-control" value="<?php echo $user['phone']; ?>">
                        </div>

                        <div class="form-group">
                            <label for="new_password" class="form-label">New Password</label>
                            <input type="password" id="new_password" name="new_password" class="form-control">
                            <small class="text-muted">Leave blank to keep current password.</small>
                        </div>

                        <div class="form-group">
                            <label for="confirm_password" class="form-label">Confirm New Password</label>
                            <input type="password" id="confirm_password" name="confirm_password" class="form-control">
                        </div>

                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">Update Profile</button>
                        </div>
                    </form>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
/* Additional dashboard styles */
.property-mini {
    display: flex;
    align-items: center;
}

.property-mini img {
    border-radius: 4px;
    margin-right: 10px;
    object-fit: cover;
}

.inquiry-card {
    background-color: white;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    margin-bottom: 20px;
    overflow: hidden;
}

.inquiry-header {
    padding: 15px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    background-color: #f9f9f9;
    border-bottom: 1px solid #eee;
}

.inquiry-body {
    padding: 15px;
}

.message-box {
    margin-bottom: 20px;
}

.message {
    background-color: #f9f9f9;
    border-radius: 8px;
    padding: 15px;
    margin-top: 10px;
}

.buyer-message {
    background-color: #e3f2fd;
}

.seller-message {
    background-color: #f1f8e9;
}

.badge {
    display: inline-block;
    padding: 5px 10px;
    border-radius: 4px;
    font-size: 0.8rem;
    font-weight: 500;
}

.badge-primary {
    background-color: #0288d1;
    color: white;
}

.badge-info {
    background-color: #29b6f6;
    color: white;
}

.badge-success {
    background-color: #4caf50;
    color: white;
}

.badge-secondary {
    background-color: #9e9e9e;
    color: white;
}
</style>

<?php require_once 'includes/footer.php'; ?>