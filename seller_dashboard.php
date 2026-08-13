<?php
$pageTitle = "Seller Dashboard";
require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

// Require seller authentication
requireAuth('seller');

// Get current tab
$currentTab = isset($_GET['tab']) ? $_GET['tab'] : 'dashboard';

// Get current user
$user = getCurrentUser();

// Get properties for this seller
$properties = getProperties(['seller_id' => $_SESSION['user_id']]);

// Get property statistics
$propertyStats = getPropertyStatistics($_SESSION['user_id']);

// Get inquiries
$inquiries = getUserInquiries($_SESSION['user_id'], 'seller');

// Get inquiry statistics
$inquiryStats = getInquiryStatistics($_SESSION['user_id'], 'seller');

// Additional scripts
$additionalScripts = '<script src="assets/js/dashboard.js"></script>';

require_once 'includes/header.php';
?>

<h1>Seller Dashboard</h1>

<div class="dashboard">
    <div class="dashboard-sidebar">
        <ul class="dashboard-nav">
            <li>
                <a href="?tab=dashboard" class="<?php echo $currentTab === 'dashboard' ? 'active' : ''; ?>">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a>
            </li>
            <li>
                <a href="?tab=properties" class="<?php echo $currentTab === 'properties' ? 'active' : ''; ?>">
                    <i class="fas fa-home"></i> My Properties
                </a>
            </li>
            <li>
                <a href="?tab=inquiries" class="<?php echo $currentTab === 'inquiries' ? 'active' : ''; ?>">
                    <i class="fas fa-envelope"></i> Inquiries
                </a>
            </li>
            <li>
                <a href="add_property.php">
                    <i class="fas fa-plus-circle"></i> Add Property
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
                        <i class="fas fa-home"></i>
                        <h3><?php echo $propertyStats['total']; ?></h3>
                        <p>Total Properties</p>
                    </div>

                    <div class="stat-card">
                        <i class="fas fa-tags"></i>
                        <h3><?php echo $propertyStats['for_sale']; ?></h3>
                        <p>For Sale</p>
                    </div>

                    <div class="stat-card">
                        <i class="fas fa-handshake"></i>
                        <h3><?php echo $propertyStats['sold']; ?></h3>
                        <p>Sold</p>
                    </div>

                    <div class="stat-card">
                        <i class="fas fa-envelope"></i>
                        <h3><?php echo $inquiryStats['total']; ?></h3>
                        <p>Total Inquiries</p>
                    </div>
                </div>

                <div class="quick-actions">
                    <h3>Quick Actions</h3>
                    <div class="action-buttons">
                        <a href="add_property.php" class="btn btn-primary">
                            <i class="fas fa-plus-circle"></i> Add New Property
                        </a>
                        <a href="?tab=inquiries&status=new" class="btn btn-info">
                            <i class="fas fa-envelope"></i> View New Inquiries 
                            <?php if ($inquiryStats['new'] > 0): ?>
                                <span class="badge badge-light"><?php echo $inquiryStats['new']; ?></span>
                            <?php endif; ?>
                        </a>
                    </div>
                </div>

                <h3>Recent Properties</h3>
                <?php if (empty($properties)): ?>
                    <div class="alert alert-info">You haven't listed any properties yet. <a href="add_property.php">Add your first property</a>.</div>
                <?php else: ?>
                    <div class="table-container">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Property</th>
                                    <th>Price</th>
                                    <th>Status</th>
                                    <th>Date Listed</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                // Display only the most recent 5 properties
                                $recentProperties = array_slice($properties, 0, 5);
                                foreach($recentProperties as $property): 
                                ?>
                                    <tr>
                                        <td>
                                            <div class="property-mini">
                                                <img src="<?php echo $property['image1']; ?>" alt="<?php echo $property['title']; ?>" width="50" height="50">
                                                <span><?php echo $property['title']; ?></span>
                                            </div>
                                        </td>
                                        <td><?php echo formatCurrency($property['price']); ?></td>
                                        <td>
                                            <span class="badge <?php 
                                                switch($property['status']) {
                                                    case 'for_sale': echo 'badge-primary'; break;
                                                    case 'for_rent': echo 'badge-info'; break;
                                                    case 'sold': echo 'badge-success'; break;
                                                    case 'rented': echo 'badge-secondary'; break;
                                                }
                                            ?>">
                                                <?php echo str_replace('_', ' ', ucfirst($property['status'])); ?>
                                            </span>
                                        </td>
                                        <td><?php echo formatDate($property['created_at']); ?></td>
                                        <td>
                                            <a href="property_details.php?id=<?php echo $property['id']; ?>" class="btn btn-info btn-sm">View</a>
                                            <a href="edit_property.php?id=<?php echo $property['id']; ?>" class="btn btn-primary btn-sm">Edit</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php if (count($properties) > 5): ?>
                        <p class="text-center">
                            <a href="?tab=properties" class="btn btn-secondary">View All Properties</a>
                        </p>
                    <?php endif; ?>
                <?php endif; ?>

                <h3>Recent Inquiries</h3>
                <?php if (empty($inquiries)): ?>
                    <div class="alert alert-info">You don't have any inquiries yet.</div>
                <?php else: ?>
                    <div class="table-container">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Property</th>
                                    <th>From</th>
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
                                        <td><?php echo $inquiry['buyer_name']; ?></td>
                                        <td><?php echo formatDate($inquiry['created_at']); ?></td>
                                        <td>
                                            <span class="inquiry-status badge <?php 
                                                switch($inquiry['status']) {
                                                    case 'new': echo 'badge-primary'; break;
                                                    case 'read': echo 'badge-info'; break;
                                                    case 'replied': echo 'badge-success'; break;
                                                    case 'closed': echo 'badge-secondary'; break;
                                                }
                                            ?>" data-inquiry-id="<?php echo $inquiry['id']; ?>">
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
        <?php elseif ($currentTab === 'properties'): ?>
            <!-- Properties Tab -->
            <div class="tab-content active" id="properties">
                <div class="tab-header">
                    <h2>My Properties</h2>
                    <a href="add_property.php" class="btn btn-primary">
                        <i class="fas fa-plus-circle"></i> Add New Property
                    </a>
                </div>

                <?php if (empty($properties)): ?>
                    <div class="alert alert-info">You haven't listed any properties yet. <a href="add_property.php">Add your first property</a>.</div>
                <?php else: ?>
                    <div class="table-container">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Property</th>
                                    <th>Price</th>
                                    <th>Type</th>
                                    <th>Status</th>
                                    <th>Featured</th>
                                    <th>Date Listed</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($properties as $property): ?>
                                    <tr>
                                        <td>
                                            <div class="property-mini">
                                                <img src="<?php echo $property['image1']; ?>" alt="<?php echo $property['title']; ?>" width="50" height="50">
                                                <span><?php echo $property['title']; ?></span>
                                            </div>
                                        </td>
                                        <td><?php echo formatCurrency($property['price']); ?></td>
                                        <td><?php echo ucfirst($property['property_type']); ?></td>
                                        <td>
                                            <span class="badge <?php 
                                                switch($property['status']) {
                                                    case 'for_sale': echo 'badge-primary'; break;
                                                    case 'for_rent': echo 'badge-info'; break;
                                                    case 'sold': echo 'badge-success'; break;
                                                    case 'rented': echo 'badge-secondary'; break;
                                                }
                                            ?>">
                                                <?php echo str_replace('_', ' ', ucfirst($property['status'])); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge <?php echo $property['featured'] ? 'badge-success' : 'badge-secondary'; ?>">
                                                <?php echo $property['featured'] ? 'Featured' : 'Not Featured'; ?>
                                            </span>
                                        </td>
                                        <td><?php echo formatDate($property['created_at']); ?></td>
                                        <td>
                                            <div class="btn-group">
                                                <a href="property_details.php?id=<?php echo $property['id']; ?>" class="btn btn-info btn-sm">View</a>
                                                <a href="edit_property.php?id=<?php echo $property['id']; ?>" class="btn btn-primary btn-sm">Edit</a>
                                                <a href="#" class="btn btn-danger btn-sm delete-property-btn" data-property-id="<?php echo $property['id']; ?>" data-property-title="<?php echo htmlspecialchars($property['title']); ?>">Delete</a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        <?php elseif ($currentTab === 'inquiries'): ?>
            <!-- Inquiries Tab -->
            <div class="tab-content active" id="inquiries">
                <h2>Property Inquiries</h2>

                <?php if (empty($inquiries)): ?>
                    <div class="alert alert-info">You don't have any inquiries yet.</div>
                <?php else: ?>
                    <div class="inquiries-list">
                        <?php foreach($inquiries as $inquiry): ?>
                            <div class="inquiry-card" id="inquiry-<?php echo $inquiry['id']; ?>">
                                <div class="inquiry-header">
                                    <div class="property-mini">
                                        <img src="<?php echo $inquiry['image1']; ?>" alt="<?php echo $inquiry['property_title']; ?>" width="80" height="80">
                                        <div>
                                            <h3><?php echo $inquiry['property_title']; ?></h3>
                                            <p>From: <?php echo $inquiry['buyer_name']; ?></p>
                                            <p>Date: <?php echo formatDate($inquiry['created_at']); ?></p>
                                            <span class="inquiry-status badge <?php 
                                                switch($inquiry['status']) {
                                                    case 'new': echo 'badge-primary'; break;
                                                    case 'read': echo 'badge-info'; break;
                                                    case 'replied': echo 'badge-success'; break;
                                                    case 'closed': echo 'badge-secondary'; break;
                                                }
                                            ?>" data-inquiry-id="<?php echo $inquiry['id']; ?>">
                                                <?php echo ucfirst($inquiry['status']); ?>
                                            </span>
                                        </div>
                                    </div>
                                    <div class="inquiry-actions">
                                        <a href="property_details.php?id=<?php echo $inquiry['property_id']; ?>" class="btn btn-info btn-sm">View Property</a>
                                        <?php if ($inquiry['status'] !== 'closed'): ?>
                                            <button class="btn btn-secondary btn-sm update-inquiry-status" data-inquiry-id="<?php echo $inquiry['id']; ?>" data-status="closed">Mark as Closed</button>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <div class="inquiry-body">
                                    <div class="message-box">
                                        <h4>Buyer's Inquiry</h4>
                                        <div class="message buyer-message">
                                            <p><?php echo nl2br($inquiry['message']); ?></p>
                                        </div>
                                    </div>

                                    <?php if ($inquiry['status'] === 'new'): ?>
                                        <button class="btn btn-info update-inquiry-status" data-inquiry-id="<?php echo $inquiry['id']; ?>" data-status="read">Mark as Read</button>
                                    <?php endif; ?>

                                    <div class="reply-section">
                                        <button class="btn btn-primary show-reply-form" data-inquiry-id="<?php echo $inquiry['id']; ?>">Reply</button>

                                        <div id="reply-form-<?php echo $inquiry['id']; ?>" class="reply-form" style="display: none;">
                                            <form class="reply-inquiry-form">
                                                <input type="hidden" name="inquiry_id" value="<?php echo $inquiry['id']; ?>">

                                                <div class="form-group">
                                                    <label for="reply-<?php echo $inquiry['id']; ?>" class="form-label">Your Reply</label>
                                                    <textarea id="reply-<?php echo $inquiry['id']; ?>" name="reply_message" class="form-control" rows="5" required></textarea>
                                                </div>

                                                <div class="form-group">
                                                    <button type="submit" class="btn btn-primary">Send Reply</button>
                                                </div>
                                            </form>
                                        </div>
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
.tab-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

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

.reply-section {
    margin-top: 20px;
}

.reply-form {
    margin-top: 15px;
    padding: 15px;
    background-color: #f9f9f9;
    border-radius: 8px;
}

.quick-actions {
    margin-bottom: 30px;
}

.action-buttons {
    display: flex;
    gap: 10px;
    margin-top: 10px;
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

.badge-light {
    background-color: white;
    color: #0288d1;
}

.btn-group {
    display: flex;
    gap: 5px;
}
</style>

<?php require_once 'includes/footer.php'; ?>