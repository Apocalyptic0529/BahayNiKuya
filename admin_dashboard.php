<?php
$pageTitle = "Admin Dashboard";
require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

// Require admin authentication
requireAuth('admin');

// Get current tab
$currentTab = isset($_GET['tab']) ? $_GET['tab'] : 'dashboard';

// Get current user
$user = getCurrentUser();

// Get admin statistics
$adminStats = getAdminStatistics();

// Get all properties
$properties = getProperties();

// Get all users
$users = getAllUsers();

// Get all reports
$reports = getAllReports();

// Get all inquiries for the administrator
$inquiries = getUserInquiries($_SESSION['user_id'], 'admin');

// Additional scripts
$additionalScripts = '<script src="assets/js/admin.js"></script><script src="assets/js/dashboard.js"></script>';

require_once 'includes/header.php';
?>

<h1>Admin Dashboard</h1>

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
                    <i class="fas fa-home"></i> Properties
                </a>
            </li>
            <li>
                <a href="?tab=users" class="<?php echo $currentTab === 'users' ? 'active' : ''; ?>">
                    <i class="fas fa-users"></i> Users
                </a>
            </li>
            
            <li>
                <a href="?tab=inquiries" class="<?php echo $currentTab === 'inquiries' ? 'active' : ''; ?>">
                    <i class="fas fa-envelope"></i> All Inquiries
                </a>
            </li>
            <li>
                <a href="?tab=reports" class="<?php echo $currentTab === 'reports' ? 'active' : ''; ?>">
                    <i class="fas fa-flag"></i> Reports
                    <?php if ($adminStats['pending_reports'] > 0): ?>
                        <span class="badge badge-danger"><?php echo $adminStats['pending_reports']; ?></span>
                    <?php endif; ?>
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
                        <h3><?php echo $adminStats['total_properties']; ?></h3>
                        <p>Total Properties</p>
                    </div>
                    <div class="stat-card">
                        <i class="fas fa-trash"></i>
                        <h3><?php echo count(array_filter($properties, function($p) { return $p['status'] === 'pending_deletion'; })); ?></h3>
                        <p>Pending Deletions</p>
                    </div>

                    <div class="stat-card">
                        <i class="fas fa-users"></i>
                        <h3><?php echo $adminStats['total_users']; ?></h3>
                        <p>Total Users</p>
                    </div>

                    <div class="stat-card">
                        <i class="fas fa-envelope"></i>
                        <h3><?php echo $adminStats['total_inquiries']; ?></h3>
                        <p>Total Inquiries</p>
                    </div>

                    <div class="stat-card">
                        <i class="fas fa-flag"></i>
                        <h3><?php echo $adminStats['pending_reports']; ?></h3>
                        <p>Pending Reports</p>
                    </div>
                </div>

                <div class="charts-container">
                    <div class="chart-box">
                        <h3>Users by Role</h3>
                        <canvas id="users-chart" 
                            data-roles="<?php echo htmlspecialchars(json_encode(array_keys($adminStats['users_by_role']))); ?>" 
                            data-counts="<?php echo htmlspecialchars(json_encode(array_values($adminStats['users_by_role']))); ?>">
                        </canvas>
                    </div>

                    <div class="chart-box">
                        <h3>Properties by Type</h3>
                        <canvas id="properties-chart" 
                            data-types="<?php echo htmlspecialchars(json_encode(array_keys($adminStats['properties_by_type']))); ?>" 
                            data-counts="<?php echo htmlspecialchars(json_encode(array_values($adminStats['properties_by_type']))); ?>">
                        </canvas>
                    </div>
                </div>

                <h3>Recent Properties</h3>
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Property</th>
                                <th>Seller</th>
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
                                            <img src="<?php echo htmlspecialchars(propertyImageUrl($property['image1'] ?? ''), ENT_QUOTES); ?>" alt="<?php echo $property['title']; ?>" width="50" height="50">
                                            <span><?php echo $property['title']; ?></span>
                                        </div>
                                    </td>
                                    <td><?php echo $property['seller_name']; ?></td>
                                    <td><?php echo formatCurrency($property['price']); ?></td>
                                    <td>
                                        <span class="property-status badge <?php 
                                            switch($property['status']) {
                                                case 'pending': echo 'badge-warning'; break;
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
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <p class="text-center">
                    <a href="?tab=properties" class="btn btn-secondary">View All Properties</a>
                </p>

                <h3>Recent Reports</h3>
                <?php if (empty($reports)): ?>
                    <div class="alert alert-info">There are no property reports.</div>
                <?php else: ?>
                    <div class="table-container">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Property</th>
                                    <th>Reported By</th>
                                    <th>Reason</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                    <th>Actions</th>
                                </tr>
</thead>
                            <tbody>
                                <?php 
                                // Display only the first 5 reports
                                $recentReports = array_slice($reports, 0, 5);
                                foreach($recentReports as $report): 
                                ?>
                                    <tr>
                                        <td><?php echo $report['property_title']; ?></td>
                                        <td><?php echo $report['reporter_name']; ?></td>
                                        <td><?php echo substr($report['reason'], 0, 50) . (strlen($report['reason']) > 50 ? '...' : ''); ?></td>
                                        <td>
                                            <span class="report-status badge <?php 
                                                switch($report['status']) {
                                                    case 'pending': echo 'badge-warning'; break;
                                                    case 'resolved': echo 'badge-success'; break;
                                                    case 'dismissed': echo 'badge-secondary'; break;
                                                }
                                            ?>">
                                                <?php echo ucfirst($report['status']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo formatDate($report['created_at']); ?></td>
                                        <td>
                                            <?php if ($report['status'] === 'pending'): ?>
                                                <button class="btn btn-success btn-sm resolve-report-btn" data-report-id="<?php echo $report['id']; ?>">Resolve</button>
                                                <button class="btn btn-secondary btn-sm dismiss-report-btn" data-report-id="<?php echo $report['id']; ?>">Dismiss</button>
                                            <?php else: ?>
                                                <span class="text-muted">No actions needed</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <p class="text-center">
                        <a href="?tab=reports" class="btn btn-secondary">View All Reports</a>
                    </p>
                <?php endif; ?>
            </div>
        <?php elseif ($currentTab === 'properties'): ?>
            <!-- Properties Tab -->
            <div class="tab-content active" id="properties">
                <h2>All Properties</h2>

                <div class="filter-bar">
                    <form action="admin_dashboard.php" method="GET" class="filter-form">
                        <input type="hidden" name="tab" value="properties">

                        <div class="form-group">
                            <input type="text" name="search" class="form-control" placeholder="Search by title, location...">
                        </div>

                        <div class="form-group">
                            <select name="property_type" class="form-select">
                                <option value="">All Types</option>
                                <option value="house">House</option>
                                <option value="apartment">Apartment</option>
                                <option value="condo">Condo</option>
                                <option value="land">Land</option>
                                <option value="commercial">Commercial</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <select name="status" class="form-select">
                                <option value="">All Status</option>
                                <option value="for_sale">For Sale</option>
                                <option value="for_rent">For Rent</option>
                                <option value="sold">Sold</option>
                                <option value="rented">Rented</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">Filter</button>
                        </div>
                    </form>
                </div>

                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Property</th>
                                <th>Seller</th>
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
                                    <td><?php echo $property['id']; ?></td>
                                    <td>
                                        <div class="property-mini">
                                            <img src="<?php echo htmlspecialchars(propertyImageUrl($property['image1'] ?? ''), ENT_QUOTES); ?>" alt="<?php echo $property['title']; ?>" width="50" height="50">
                                            <span><?php echo $property['title']; ?></span>
                                        </div>
                                    </td>
                                    <td><?php echo $property['seller_name']; ?></td>
                                    <td><?php echo formatCurrency($property['price']); ?></td>
                                    <td><?php echo ucfirst($property['property_type']); ?></td>
                                    <td>
                                        <span class="badge <?php 
                                            switch($property['status']) {
                                                case 'pending': echo 'badge-warning'; break;
                                                case 'for_sale': echo 'badge-primary'; break;
                                                case 'for_rent': echo 'badge-info'; break;
                                                case 'sold': echo 'badge-success'; break;
                                                case 'rented': echo 'badge-secondary'; break;
                                                case 'pending_deletion': echo 'badge-warning'; break;
                                                case 'rejected': echo 'badge-danger'; break;
                                                    case 'pending_deletion': echo 'badge-warning'; break;
                                            }
                                        ?>">
                                            <?php echo str_replace('_', ' ', ucfirst($property['status'])); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm toggle-featured-btn"
                                                data-property-id="<?php echo (int)$property['id']; ?>"
                                                data-featured="<?php echo !empty($property['featured']) ? '1' : '0'; ?>"
                                                data-status="<?php echo htmlspecialchars((string)($property['status'] ?? ''), ENT_QUOTES); ?>"
                                                <?php echo in_array(($property['status'] ?? ''), ['for_sale', 'for_rent'], true) ? '' : 'disabled title="Only approved properties can be featured."'; ?>>
                                            <?php if (!empty($property['featured'])): ?>
                                                <i class="fas fa-star"></i> Featured
                                            <?php else: ?>
                                                <i class="far fa-star"></i> Set Featured
                                            <?php endif; ?>
                                        </button>
                                    </td>
                                    <td><?php echo formatDate($property['created_at']); ?></td>
                                    <td>
                                        <div class="btn-group">
                                            <a href="property_details.php?id=<?php echo $property['id']; ?>" class="btn btn-info btn-sm">View</a>
                                            <a href="edit_property.php?id=<?php echo $property['id']; ?>" class="btn btn-primary btn-sm">Edit</a>
                                            <?php if ($property['status'] === 'pending'): ?>
                                                <button class="btn btn-success btn-sm approve-property-btn" data-property-id="<?php echo $property['id']; ?>">Approve</button>
                                                <button class="btn btn-danger btn-sm reject-property-btn" data-property-id="<?php echo $property['id']; ?>">Reject</button>
                                            <?php elseif ($property['status'] === 'pending_deletion'): ?>
                                                <button class="btn btn-success btn-sm approve-deletion-btn" data-property-id="<?php echo $property['id']; ?>">Approve Deletion</button>
                                            <?php endif; ?>
                                            <a href="#" class="btn btn-danger btn-sm delete-property-btn" data-property-id="<?php echo $property['id']; ?>" data-property-title="<?php echo htmlspecialchars($property['title']); ?>">Delete</a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php elseif ($currentTab === 'users'): ?>
            <!-- Users Tab -->
            <div class="tab-content active" id="users">
                <h2>All Users</h2>

                <div class="filter-bar">
                    <form action="admin_dashboard.php" method="GET" class="filter-form">
                        <input type="hidden" name="tab" value="users">

                        <div class="form-group">
                            <input type="text" name="search" class="form-control" placeholder="Search by name, email...">
                        </div>

                        <div class="form-group">
                            <select name="role" class="form-select">
                                <option value="">All Roles</option>
                                <option value="buyer">Buyers</option>
                                <option value="seller">Sellers</option>
                                <option value="admin">Admins</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">Filter</button>
                        </div>
                    </form>
                </div>

                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Role</th>
                                <th>Join Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($users as $userData): ?>
                                <tr>
                                    <td><?php echo $userData['id']; ?></td>
                                    <td><?php echo $userData['name']; ?></td>
                                    <td><?php echo $userData['email']; ?></td>
                                    <td><?php echo $userData['phone'] ? $userData['phone'] : 'Not provided'; ?></td>
                                    <td>
                                        <select class="form-control user-role-select" data-user-id="<?php echo $userData['id']; ?>" data-current-role="<?php echo $userData['role']; ?>">
                                            <option value="buyer" <?php if($userData['role'] == 'buyer') echo 'selected'; ?>>Buyer</option>
                                            <option value="seller" <?php if($userData['role'] == 'seller') echo 'selected'; ?>>Seller</option>
                                            <option value="admin" <?php if($userData['role'] == 'admin') echo 'selected'; ?>>Admin</option>
                                        </select>
                                    </td>
                                    <td><?php echo formatDate($userData['created_at']); ?></td>
                                    <td>
                                        <?php if ($userData['id'] != $_SESSION['user_id']): ?>
                                            <a href="#" class="btn btn-danger btn-sm delete-user-btn" data-user-id="<?php echo $userData['id']; ?>" data-user-name="<?php echo htmlspecialchars($userData['name']); ?>">Delete</a>
                                        <?php else: ?>
                                            <span class="text-muted">Current User</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php elseif ($currentTab === 'inquiries'): ?>
            <div class="tab-content active" id="inquiries">
                <h2>All Inquiries</h2>
                <?php if (empty($inquiries)): ?>
                    <div class="alert alert-info">There are no inquiries yet.</div>
                <?php else: ?>
                    <div class="inquiries-list">
                        <?php foreach ($inquiries as $inquiry): ?>
                            <div class="inquiry-card" id="inquiry-<?php echo (int)$inquiry['id']; ?>">
                                <div class="inquiry-header">
                                    <div>
                                        <h3><?php echo htmlspecialchars($inquiry['property_title'] ?? 'Property'); ?></h3>
                                        <p>Buyer: <?php echo htmlspecialchars($inquiry['buyer_name'] ?? 'Unknown'); ?> | Seller: <?php echo htmlspecialchars($inquiry['seller_name'] ?? 'Unknown'); ?></p>
                                        <span class="inquiry-status badge" data-inquiry-id="<?php echo (int)$inquiry['id']; ?>"><?php echo htmlspecialchars(ucfirst($inquiry['status'])); ?></span>
                                    </div>
                                    <div class="inquiry-actions">
                                        <a href="property_details.php?id=<?php echo (int)$inquiry['property_id']; ?>" class="btn btn-info btn-sm">View Property</a>
                                        <?php if ($inquiry['status'] !== 'closed'): ?>
                                            <button type="button" class="btn btn-secondary btn-sm update-inquiry-status" data-inquiry-id="<?php echo (int)$inquiry['id']; ?>" data-status="closed">Close</button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="inquiry-body">
                                    <div class="conversation-thread">
                                        <?php foreach (($inquiry['messages'] ?? []) as $message): ?>
                                            <div class="message-box <?php echo $message['sender_role'] === 'buyer' ? 'buyer-message-box' : ($message['sender_role'] === 'seller' ? 'seller-message-box' : 'admin-message-box'); ?>">
                                                <h4><?php echo htmlspecialchars($message['sender_name']); ?> <small>(<?php echo htmlspecialchars(ucfirst($message['sender_role'])); ?>)</small></h4>
                                                <div class="message">
                                                    <p><?php echo nl2br(htmlspecialchars($message['message'])); ?></p>
                                                    <small class="text-muted"><?php echo formatDate($message['created_at']); ?></small>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                    <div class="reply-section">
                                        <button type="button" class="btn btn-primary show-reply-form" data-inquiry-id="<?php echo (int)$inquiry['id']; ?>">Reply as Admin</button>
                                        <div id="reply-form-<?php echo (int)$inquiry['id']; ?>" class="reply-form" style="display:none;">
                                            <form class="reply-inquiry-form">
                                                <input type="hidden" name="inquiry_id" value="<?php echo (int)$inquiry['id']; ?>">
                                                <textarea name="reply_message" class="form-control" rows="4" required placeholder="Write a reply..."></textarea>
                                                <button type="submit" class="btn btn-primary">Send Reply</button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php elseif ($currentTab === 'reports'): ?>
            <!-- Reports Tab -->
            <div class="tab-content active" id="reports">
                <h2>Property Reports</h2>

                <?php if (empty($reports)): ?>
                    <div class="alert alert-info">There are no property reports.</div>
                <?php else: ?>
                    <div class="filter-bar">
                        <form action="admin_dashboard.php" method="GET" class="filter-form">
                            <input type="hidden" name="tab" value="reports">

                            <div class="form-group">
                                <select name="status" class="form-select">
                                    <option value="">All Status</option>
                                    <option value="pending">Pending</option>
                                    <option value="resolved">Resolved</option>
                                    <option value="dismissed">Dismissed</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <button type="submit" class="btn btn-primary">Filter</button>
                            </div>
                        </form>
                    </div>

                    <div class="table-container">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Property</th>
                                    <th>Reported By</th>
                                    <th>Reason</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($reports as $report): ?>
                                    <tr>
                                        <td><?php echo $report['id']; ?></td>
                                        <td>
                                            <a href="property_details.php?id=<?php echo $report['property_id']; ?>"><?php echo $report['property_title']; ?></a>
                                        </td>
                                        <td><?php echo $report['reporter_name']; ?></td>
                                        <td><?php echo substr($report['reason'], 0, 100) . (strlen($report['reason']) > 100 ? '...' : ''); ?></td>
                                        <td>
                                            <span class="report-status badge <?php 
                                                switch($report['status']) {
                                                    case 'pending': echo 'badge-warning'; break;
                                                    case 'resolved': echo 'badge-success'; break;
                                                    case 'dismissed': echo 'badge-secondary'; break;
                                                }
                                            ?>">
                                                <?php echo ucfirst($report['status']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo formatDate($report['created_at']); ?></td>
                                        <td>
                                            <?php if ($report['status'] === 'pending'): ?>
                                                <button class="btn btn-success btn-sm resolve-report-btn" data-report-id="<?php echo $report['id']; ?>">Resolve</button>
                                                <button class="btn btn-secondary btn-sm dismiss-report-btn" data-report-id="<?php echo $report['id']; ?>">Dismiss</button>
                                            <?php else: ?>
                                                <span class="text-muted">No actions needed</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        <?php elseif ($currentTab === 'pending'): ?>
            <!-- Pending Approvals Tab -->
            <div class="tab-content active" id="pending">
                <h2>Pending Approvals</h2>

                <h3>Pending Properties</h3>
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Property</th>
                                <th>Seller</th>
                                <th>Type</th>
                                <th>Price</th>
                                <th>Date Submitted</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $pendingProperties = getProperties(['status' => 'pending']);
                            if (!empty($pendingProperties)):
                                foreach($pendingProperties as $property): 
                            ?>
                                <tr>
                                    <td>
                                        <div class="property-mini">
                                            <img src="<?php echo htmlspecialchars(propertyImageUrl($property['image1'] ?? ''), ENT_QUOTES); ?>" alt="<?php echo $property['title']; ?>" width="50" height="50">
                                            <span><?php echo $property['title']; ?></span>
                                        </div>
                                    </td>
                                    <td><?php echo $property['seller_name']; ?></td>
                                    <td><?php echo ucfirst($property['property_type']); ?></td>
                                    <td><?php echo formatCurrency($property['price']); ?></td>
                                    <td><?php echo formatDate($property['created_at']); ?></td>
                                    <td>
                                        <div class="btn-group">
                                            <a href="property_details.php?id=<?php echo $property['id']; ?>" class="btn btn-info btn-sm">View</a>
                                            <button class="btn btn-success btn-sm approve-property-btn" data-property-id="<?php echo $property['id']; ?>">Approve</button>
                                            <button class="btn btn-danger btn-sm reject-property-btn" data-property-id="<?php echo $property['id']; ?>">Reject</button>
                                        </div>
                                    </td>
                                </tr>
                            <?php 
                                endforeach; 
                            endif;
                            ?>
                        </tbody>
                    </table>
                </div>
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

                <h3 class="mt-5">Pending Seller Applications</h3>
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Applicant</th>
                                <th>Business Name</th>
                                <th>License Number</th>
                                <th>Experience</th>
                                <th>Date Applied</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $query = "SELECT sa.*, u.name as applicant_name, u.email 
                                     FROM seller_applications sa 
                                     JOIN users u ON sa.user_id = u.id 
                                     WHERE sa.status = 'pending'";
                            $result = $conn->query($query);

                            if ($result && $result->num_rows > 0):
                                while($application = $result->fetch_assoc()): 
                            ?>
                                <tr>
                                    <td><?php echo $application['applicant_name']; ?></td>
                                    <td><?php echo $application['business_name'] ?: 'N/A'; ?></td>
                                    <td><?php echo $application['license_number'] ?: 'N/A'; ?></td>
                                    <td><?php echo substr($application['experience'], 0, 100) . '...'; ?></td>
                                    <td><?php echo formatDate($application['created_at']); ?></td>
                                    <td>
                                        <button class="btn btn-success btn-sm approve-seller-btn" 
                                                data-user-id="<?php echo $application['user_id']; ?>"
                                                data-application-id="<?php echo $application['id']; ?>">
                                            Approve
                                        </button>
                                        <button class="btn btn-danger btn-sm reject-seller-btn"
                                                data-user-id="<?php echo $application['user_id']; ?>"
                                                data-application-id="<?php echo $application['id']; ?>">
                                            Reject
                                        </button>
                                    </td>
                                </tr>
                            <?php 
                                endwhile;
                            endif;
                            ?>
                        </tbody>
                    </table>
                </div>

                        </div>
                    </form>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
/* Additional dashboard styles */
.charts-container {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.chart-box {
    background-color: white;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    padding: 20px;
}

.chart-box h3 {
    text-align: center;
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

.badge-warning {
    background-color: #ffc107;
    color: #333;
}

.badge-danger {
    background-color: #f44336;
    color: white;
}

.btn-group {
    display: flex;
    gap: 5px;
}

.user-role-select {
    width: auto;
    padding: 5px;
    font-size: 0.9rem;
}
</style>

<!-- Include Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.7.1/dist/chart.min.js"></script>

<?php require_once 'includes/footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Delete property button click event
    document.querySelectorAll('.delete-property-btn').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const propertyId = this.dataset.propertyId;
            const propertyTitle = this.dataset.propertyTitle;

            if (confirm(`Are you sure you want to request deletion of "${propertyTitle}"?`)) {
                fetch('api/admin/property_actions.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        property_id: propertyId,
                        action: 'delete'
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        window.location.reload();
                    } else {
                        alert('Failed to request property deletion. ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while requesting property deletion.');
                });
            }
        });
    });

    // Toggle featured property button click event
    document.querySelectorAll('.toggle-featured-btn:not([disabled])').forEach(btn => {
        btn.addEventListener('click', async function() {
            const propertyId = this.dataset.propertyId;
            const currentlyFeatured = this.dataset.featured === '1';
            const action = currentlyFeatured ? 'unfeature' : 'feature';
            const originalHtml = this.innerHTML;
            this.disabled = true;
            this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';

            try {
                const response = await fetch('api/admin/property_actions.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({property_id: propertyId, action})
                });
                const text = await response.text();
                let data;
                try { data = JSON.parse(text); }
                catch (e) { throw new Error('The server returned an invalid response.'); }

                if (data.status !== 'success') {
                    throw new Error(data.message || 'Failed to update featured status.');
                }

                this.dataset.featured = currentlyFeatured ? '0' : '1';
                this.innerHTML = currentlyFeatured
                    ? '<i class="far fa-star"></i> Set Featured'
                    : '<i class="fas fa-star"></i> Featured';
                this.disabled = false;
            } catch (error) {
                console.error(error);
                this.innerHTML = originalHtml;
                this.disabled = false;
                alert(error.message || 'An error occurred while updating the featured status.');
            }
        });
    });

    // Approve seller-requested property deletion
    document.querySelectorAll('.approve-deletion-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const propertyId = this.dataset.propertyId;
            if (!confirm('Permanently delete this property? This cannot be undone.')) return;
            fetch('api/admin/property_actions.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({property_id: propertyId, action: 'approve_deletion'})
            }).then(r => r.json()).then(data => {
                if (data.status === 'success') window.location.reload();
                else alert(data.message || 'Failed to approve deletion.');
            }).catch(() => alert('An error occurred while approving the deletion.'));
        });
    });

    // Seller application approval/rejection
    document.querySelectorAll('.approve-seller-btn, .reject-seller-btn').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();

            const applicationId = this.dataset.applicationId;
            const userId = this.dataset.userId;
            const isApproval = this.classList.contains('approve-seller-btn');
            const action = isApproval ? 'approve_seller' : 'reject_seller';
            const label = isApproval ? 'approve' : 'reject';

            if (!confirm(`Are you sure you want to ${label} this seller application?`)) {
                return;
            }

            fetch('api/admin/property_actions.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    application_id: applicationId,
                    user_id: userId,
                    action: action
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    window.location.reload();
                } else {
                    alert(`Failed to ${label} seller application. ${data.message || ''}`);
                }
            })
            .catch(error => {
                console.error('Seller application error:', error);
                alert(`An error occurred while trying to ${label} the seller application.`);
            });
        });
    });

    // Delete user button click event
    document.querySelectorAll('.delete-user-btn').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const userId = this.dataset.userId;
            const userName = this.dataset.userName;

            if (confirm(`Are you sure you want to delete user "${userName}"?`)) {
                fetch('api/users.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        user_id: userId,
                        action: 'delete'
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        window.location.reload();
                    } else {
                        alert('Failed to delete user. ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while deleting user.');
                });
            }
        });
    });

    // User role select change event
    document.querySelectorAll('.user-role-select').forEach(select => {
        select.addEventListener('change', function() {
            const userId = this.dataset.userId;
            const newRole = this.value;
            const currentRole = this.dataset.currentRole;

            if (confirm(`Are you sure you want to change the role for this user from ${currentRole} to ${newRole}?`)) {
                fetch('api/users.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        user_id: userId,
                        action: 'update_role',
                        role: newRole
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        window.location.reload();
                    } else {
                        alert('Failed to update user role. ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while updating user role.');
                });
            } else {
                // Reset the select to the previous value if the user cancels
                this.value = currentRole;
            }
        });
    });

        // Resolve report button click event
        document.querySelectorAll('.resolve-report-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const reportId = this.dataset.reportId;

                fetch('api/admin/report_actions.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        report_id: reportId,
                        action: 'resolve'
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        window.location.reload();
                    } else {
                        alert('Failed to resolve report. ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while resolving report.');
                });
            });
        });

        // Dismiss report button click event
        document.querySelectorAll('.dismiss-report-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const reportId = this.dataset.reportId;

                fetch('api/admin/report_actions.php', {
                    method: 'POST',
                                        headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        report_id: reportId,
                        action: 'dismiss'
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        window.location.reload();
                    } else {
                        alert('Failed to dismiss report. ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while dismissing report.');
                });
            });
        });
    });
</script>