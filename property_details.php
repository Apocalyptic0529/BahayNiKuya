<?php
$pageTitle = "Property Details";
require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

// Get property ID from URL
$propertyId = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Fetch property details
$property = getPropertyById($propertyId);

// If property not found, redirect to home
if (!$property) {
    $_SESSION['error_message'] = 'Property not found.';
    header('Location: index.php');
    exit;
}

// Check if property is in user's favorites
$isFavorite = false;
if (isLoggedIn() && hasRole('buyer')) {
    $isFavorite = isPropertyFavorite($propertyId, $_SESSION['user_id']);
}

// Additional styles and scripts
$additionalStyles = '';
$additionalScripts = '
    <script src="assets/js/property.js"></script>
';

require_once 'includes/header.php';
?>

<div class="property-details">
    <div class="property-main">
        <div class="property-gallery">
            <div class="main-image">
                <img id="main-property-image" src="<?php echo $property['image1']; ?>" alt="<?php echo $property['title']; ?>">
            </div>
            
            <div class="thumbnail-container">
                <div class="thumbnail active">
                    <img src="<?php echo $property['image1']; ?>" alt="Thumbnail 1">
                </div>
                
                <?php if (!empty($property['image2'])): ?>
                <div class="thumbnail">
                    <img src="<?php echo $property['image2']; ?>" alt="Thumbnail 2">
                </div>
                <?php endif; ?>
                
                <?php if (!empty($property['image3'])): ?>
                <div class="thumbnail">
                    <img src="<?php echo $property['image3']; ?>" alt="Thumbnail 3">
                </div>
                <?php endif; ?>
                
                <?php if (!empty($property['image4'])): ?>
                <div class="thumbnail">
                    <img src="<?php echo $property['image4']; ?>" alt="Thumbnail 4">
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="property-info">
            <div class="card-price"><?php echo formatCurrency($property['price']); ?></div>
            <h1><?php echo $property['title']; ?></h1>
            
            <p class="property-address">
                <i class="fas fa-map-marker-alt"></i> 
                <?php echo $property['address']; ?>, <?php echo $property['city']; ?>, <?php echo $property['state']; ?> <?php echo $property['zip_code']; ?>
            </p>
            
            <div class="property-features">
                <div class="feature-item">
                    <i class="fas fa-bed"></i>
                    <span><?php echo $property['bedrooms']; ?> Bedrooms</span>
                </div>
                <div class="feature-item">
                    <i class="fas fa-bath"></i>
                    <span><?php echo $property['bathrooms']; ?> Bathrooms</span>
                </div>
                <div class="feature-item">
                    <i class="fas fa-ruler-combined"></i>
                    <span><?php echo $property['area']; ?> sqft</span>
                </div>
                <div class="feature-item">
                    <i class="fas fa-home"></i>
                    <span><?php echo ucfirst($property['property_type']); ?></span>
                </div>
                <div class="feature-item">
                    <i class="fas fa-tag"></i>
                    <span><?php echo str_replace('_', ' ', ucfirst($property['status'])); ?></span>
                </div>
                <div class="feature-item">
                    <i class="fas fa-calendar-alt"></i>
                    <span>Listed on <?php echo formatDate($property['created_at']); ?></span>
                </div>
            </div>
            
            <div class="property-description">
                <h3>Description</h3>
                <p><?php echo nl2br($property['description']); ?></p>
            </div>
            
            <div class="property-location">
                <h3>Location</h3>
                <div id="property-map" data-latitude="<?php echo $property['latitude']; ?>" data-longitude="<?php echo $property['longitude']; ?>" data-title="<?php echo htmlspecialchars($property['title']); ?>"></div>
            </div>
            
            <?php if (isLoggedIn() && hasRole('buyer')): ?>
            <div class="property-actions">
                <button class="btn btn-secondary favorite-btn <?php echo $isFavorite ? 'favorited' : ''; ?>" data-property-id="<?php echo $property['id']; ?>">
                    <i class="<?php echo $isFavorite ? 'fas' : 'far'; ?> fa-heart"></i> 
                    <?php echo $isFavorite ? 'Remove from Favorites' : 'Add to Favorites'; ?>
                </button>
                
                <button id="report-property-btn" class="btn btn-danger">
                    <i class="fas fa-flag"></i> Report Property
                </button>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="property-sidebar">
        <div class="property-contact">
            <h3>Contact Seller</h3>
            
            <div class="agent-info">
                <?php if (isset($property['seller_id'])): ?>
                <img src="https://pixabay.com/get/gcea7682eb2b6bac84bc10a762558a70e3580cd08c987b25e5935842b97e526ac55e01faef8eff24f8fcef0c01b9af4de3665121c4a94ec5ef2dd9824f7c15f5e_1280.jpg" alt="<?php echo $property['seller_name']; ?>">
                <div>
                    <h4><?php echo $property['seller_name']; ?></h4>
                    <p><i class="fas fa-phone"></i> <?php echo $property['seller_phone'] ? $property['seller_phone'] : 'Not provided'; ?></p>
                    <p><i class="fas fa-envelope"></i> <?php echo $property['seller_email']; ?></p>
                </div>
                <?php else: ?>
                <p>Seller information not available</p>
                <?php endif; ?>
            </div>
            
            <?php if (isLoggedIn() && hasRole('buyer')): ?>
            <form id="property-inquiry-form" class="needs-validation">
                <input type="hidden" name="property_id" value="<?php echo $property['id']; ?>">
                <input type="hidden" name="seller_id" value="<?php echo $property['seller_id']; ?>">
                
                <div class="form-group">
                    <label for="message" class="form-label">Message</label>
                    <textarea id="message" name="message" class="form-control" rows="5" required></textarea>
                </div>
                
                <div class="form-group">
                    <button type="submit" class="btn btn-primary btn-block">Send Inquiry</button>
                </div>
            </form>
            <?php elseif (!isLoggedIn()): ?>
            <div class="alert alert-info">
                Please <a href="login.php">login</a> or <a href="register.php">register</a> to contact the seller.
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php if (isLoggedIn() && hasRole('buyer')): ?>
<!-- Report Property Modal -->
<div id="report-property-modal" class="modal">
    <div class="modal-content">
        <span id="close-report-modal" class="close">&times;</span>
        <h3>Report Property</h3>
        
        <form id="report-property-form" class="needs-validation">
            <input type="hidden" name="property_id" value="<?php echo $property['id']; ?>">
            
            <div class="form-group">
                <label for="reason" class="form-label">Reason for Reporting</label>
                <textarea id="reason" name="reason" class="form-control" rows="5" required></textarea>
            </div>
            
            <div class="form-group">
                <button type="submit" class="btn btn-danger">Submit Report</button>
            </div>
        </form>
    </div>
</div>

<style>
/* Modal styles */
.modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    overflow: auto;
    background-color: rgba(0,0,0,0.4);
}

.modal-content {
    background-color: #fefefe;
    margin: 10% auto;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.3);
    width: 80%;
    max-width: 500px;
}

.close {
    color: #aaa;
    float: right;
    font-size: 28px;
    font-weight: bold;
    cursor: pointer;
}

.close:hover,
.close:focus {
    color: black;
    text-decoration: none;
}
</style>
<?php endif; ?>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize property map
    const propertyMap = document.getElementById('property-map');
    if (propertyMap) {
        const latitude = parseFloat(propertyMap.getAttribute('data-latitude'));
        const longitude = parseFloat(propertyMap.getAttribute('data-longitude'));
        const title = propertyMap.getAttribute('data-title');
        
        if (latitude && longitude) {
            // Create map centered on property
            const map = L.map('property-map').setView([latitude, longitude], 14);
            
            // Add OpenStreetMap tile layer
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
            }).addTo(map);
            
            // Add marker for property
            const marker = L.marker([latitude, longitude]).addTo(map);
            
            // Add popup
            marker.bindPopup(`<strong>${title}</strong>`).openPopup();
        }
    }
});
</script>

<?php require_once 'includes/footer.php'; ?>
