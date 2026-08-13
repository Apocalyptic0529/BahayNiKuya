<?php
$pageTitle = "Edit Property";
require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

// Require seller or admin authentication
if (!isLoggedIn() || !(hasRole('seller') || hasRole('admin'))) {
    $_SESSION['error_message'] = 'You do not have permission to access this page.';
    header('Location: login.php');
    exit;
}

// Get property ID from URL
$propertyId = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Fetch property details
$property = getPropertyById($propertyId);

// If property not found, redirect
if (!$property) {
    $_SESSION['error_message'] = 'Property not found.';
    header('Location: ' . (hasRole('seller') ? 'seller_dashboard.php' : 'admin_dashboard.php'));
    exit;
}

// Check if user has permission to edit this property
if (hasRole('seller') && $property['seller_id'] != $_SESSION['user_id']) {
    $_SESSION['error_message'] = 'You do not have permission to edit this property.';
    header('Location: seller_dashboard.php');
    exit;
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate form inputs
    $title = isset($_POST['title']) ? sanitize($_POST['title']) : '';
    $description = isset($_POST['description']) ? sanitize($_POST['description']) : '';
    $price = isset($_POST['price']) ? floatval($_POST['price']) : 0;
    $bedrooms = isset($_POST['bedrooms']) ? intval($_POST['bedrooms']) : 0;
    $bathrooms = isset($_POST['bathrooms']) ? intval($_POST['bathrooms']) : 0;
    $area = isset($_POST['area']) ? floatval($_POST['area']) : 0;
    $address = isset($_POST['address']) ? sanitize($_POST['address']) : '';
    $city = isset($_POST['city']) ? sanitize($_POST['city']) : '';
    $state = isset($_POST['state']) ? sanitize($_POST['state']) : '';
    $zip_code = isset($_POST['zip_code']) ? sanitize($_POST['zip_code']) : '';
    $latitude = isset($_POST['latitude']) ? floatval($_POST['latitude']) : 0;
    $longitude = isset($_POST['longitude']) ? floatval($_POST['longitude']) : 0;
    $property_type = isset($_POST['property_type']) ? sanitize($_POST['property_type']) : '';
    $status = isset($_POST['status']) ? sanitize($_POST['status']) : '';
    $featured = isset($_POST['featured']) ? 1 : 0;
    
    // Validate required fields
    if (empty($title) || empty($description) || $price <= 0 || $bedrooms <= 0 || 
        $bathrooms <= 0 || $area <= 0 || empty($address) || empty($city) || 
        empty($state) || empty($zip_code) || empty($property_type) || empty($status)) {
        
        $_SESSION['error_message'] = 'All fields are required.';
    } else {
        // Process image uploads
        function handleImageUpload($fileKey, $currentImage) {
            global $conn;
            
            if (isset($_FILES[$fileKey]) && $_FILES[$fileKey]['error'] == 0) {
                $file = $_FILES[$fileKey];
                $fileName = $file['name'];
                $fileTmpName = $file['tmp_name'];
                $fileSize = $file['size'];
                
                // Get file extension
                $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
                
                if (in_array($fileExt, $allowedExtensions) && $fileSize < 5000000) {
                    $fileNameNew = uniqid('property_') . '_' . time() . '.' . $fileExt;
                    $uploadDir = 'uploads/properties/';
                    
                    if (!file_exists($uploadDir)) {
                        mkdir($uploadDir, 0777, true);
                    }
                    
                    $destination = $uploadDir . $fileNameNew;
                    if (move_uploaded_file($fileTmpName, $destination)) {
                        return $destination;
                    }
                }
            }
            
            return $currentImage;
        }
        
        $image1 = handleImageUpload('image1', isset($_POST['current_image1']) ? $_POST['current_image1'] : $property['image1']);
        $image2 = handleImageUpload('image2', isset($_POST['current_image2']) ? $_POST['current_image2'] : $property['image2']);
        $image3 = handleImageUpload('image3', isset($_POST['current_image3']) ? $_POST['current_image3'] : $property['image3']);
        $image4 = handleImageUpload('image4', isset($_POST['current_image4']) ? $_POST['current_image4'] : $property['image4']);
        
        // Ensure at least one image is provided
        if (empty($image1)) {
            $_SESSION['error_message'] = 'At least one image URL is required.';
        } else {
            // Update property in database
            $query = "UPDATE properties SET 
                    title = ?, description = ?, price = ?, bedrooms = ?, bathrooms = ?, 
                    area = ?, address = ?, city = ?, state = ?, zip_code = ?, 
                    latitude = ?, longitude = ?, property_type = ?, status = ?, 
                    featured = ?, image1 = ?, image2 = ?, image3 = ?, image4 = ?
                WHERE id = ?";
                
            $stmt = $conn->prepare($query);
            $stmt->bind_param(
                "ssdddssssddssisssssi",
                $title, $description, $price, $bedrooms, $bathrooms, $area,
                $address, $city, $state, $zip_code, $latitude, $longitude,
                $property_type, $status, $featured, $image1, $image2, $image3, $image4,
                $propertyId
            );
            
            if ($stmt->execute()) {
                $_SESSION['success_message'] = 'Property updated successfully!';
                header('Location: property_details.php?id=' . $propertyId);
                exit;
            } else {
                $_SESSION['error_message'] = 'Error updating property: ' . $conn->error;
            }
            
            $stmt->close();
        }
    }
}

// Additional styles and scripts
$additionalStyles = '';
$additionalScripts = '
    <script src="assets/js/property.js"></script>
';

require_once 'includes/header.php';
?>

<h1>Edit Property</h1>

<div class="form-container">
    <form id="property-form" action="edit_property.php?id=<?php echo $propertyId; ?>" method="POST" class="needs-validation">
        <div class="form-section">
            <h3>Basic Information</h3>
            
            <div class="form-group">
                <label for="title" class="form-label">Property Title *</label>
                <input type="text" id="title" name="title" class="form-control" value="<?php echo htmlspecialchars($property['title']); ?>" required>
            </div>
            
            <div class="form-group">
                <label for="description" class="form-label">Description *</label>
                <textarea id="description" name="description" class="form-control" rows="5" required><?php echo htmlspecialchars($property['description']); ?></textarea>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="price" class="form-label">Price ($) *</label>
                    <input type="number" id="price" name="price" class="form-control" min="1" step="0.01" value="<?php echo $property['price']; ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="property_type" class="form-label">Property Type *</label>
                    <select id="property_type" name="property_type" class="form-select" required>
                        <option value="">Select Type</option>
                        <option value="house" <?php if($property['property_type'] == 'house') echo 'selected'; ?>>House</option>
                        <option value="apartment" <?php if($property['property_type'] == 'apartment') echo 'selected'; ?>>Apartment</option>
                        <option value="condo" <?php if($property['property_type'] == 'condo') echo 'selected'; ?>>Condo</option>
                        <option value="land" <?php if($property['property_type'] == 'land') echo 'selected'; ?>>Land</option>
                        <option value="commercial" <?php if($property['property_type'] == 'commercial') echo 'selected'; ?>>Commercial</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="status" class="form-label">Status *</label>
                    <select id="status" name="status" class="form-select" required>
                        <option value="">Select Status</option>
                        <option value="for_sale" <?php if($property['status'] == 'for_sale') echo 'selected'; ?>>For Sale</option>
                        <option value="for_rent" <?php if($property['status'] == 'for_rent') echo 'selected'; ?>>For Rent</option>
                        <option value="sold" <?php if($property['status'] == 'sold') echo 'selected'; ?>>Sold</option>
                        <option value="rented" <?php if($property['status'] == 'rented') echo 'selected'; ?>>Rented</option>
                    </select>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="bedrooms" class="form-label">Bedrooms *</label>
                    <input type="number" id="bedrooms" name="bedrooms" class="form-control" min="0" value="<?php echo $property['bedrooms']; ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="bathrooms" class="form-label">Bathrooms *</label>
                    <input type="number" id="bathrooms" name="bathrooms" class="form-control" min="0" step="0.5" value="<?php echo $property['bathrooms']; ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="area" class="form-label">Area (sq ft) *</label>
                    <input type="number" id="area" name="area" class="form-control" min="1" step="0.01" value="<?php echo $property['area']; ?>" required>
                </div>
            </div>
            
            <div class="form-group">
                <div class="form-check">
                    <input type="checkbox" id="featured" name="featured" class="form-check-input" value="1" <?php if($property['featured']) echo 'checked'; ?>>
                    <label for="featured" class="form-check-label">Feature this property (appears on homepage)</label>
                </div>
            </div>
        </div>
        
        <div class="form-section">
            <h3>Location Information</h3>
            
            <div class="form-group">
                <label for="address" class="form-label">Address *</label>
                <input type="text" id="address" name="address" class="form-control" value="<?php echo htmlspecialchars($property['address']); ?>" required>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="city" class="form-label">City *</label>
                    <input type="text" id="city" name="city" class="form-control" value="<?php echo htmlspecialchars($property['city']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="state" class="form-label">State *</label>
                    <input type="text" id="state" name="state" class="form-control" value="<?php echo htmlspecialchars($property['state']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="zip_code" class="form-label">ZIP Code *</label>
                    <input type="text" id="zip_code" name="zip_code" class="form-control" value="<?php echo htmlspecialchars($property['zip_code']); ?>" required>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="latitude" class="form-label">Latitude *</label>
                    <input type="number" id="latitude" name="latitude" class="form-control" step="0.00000001" value="<?php echo $property['latitude']; ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="longitude" class="form-label">Longitude *</label>
                    <input type="number" id="longitude" name="longitude" class="form-control" step="0.00000001" value="<?php echo $property['longitude']; ?>" required>
                </div>
            </div>
            
            <div class="form-group">
                <label class="form-label">Find on Map (click to set location)</label>
                <div id="map-picker" style="height: 300px; border-radius: 8px;"></div>
                <small class="text-muted">Click on the map to set the property location.</small>
            </div>
        </div>
        
        <div class="form-section">
            <h3>Property Images</h3>
            <p>Provide URLs for property images. At least one image URL is required.</p>
            
            <div class="form-group">
                <label for="image1" class="form-label">Main Property Image *</label>
                <div class="input-group mb-3">
                    <input type="file" id="image1" name="image1" class="form-control" accept="image/*">
                    <input type="hidden" name="current_image1" value="<?php echo htmlspecialchars($property['image1']); ?>">
                </div>
                <div class="image-preview-container">
                    <img id="image-preview-1" class="image-preview" src="<?php echo htmlspecialchars($property['image1']); ?>">
                </div>
            </div>
            
            <div class="form-group">
                <label for="image2" class="form-label">Additional Image URL</label>
                <input type="url" id="image2" name="image2" class="form-control" value="<?php echo htmlspecialchars($property['image2'] ?? ''); ?>">
                <div class="image-preview-container">
                    <?php if(!empty($property['image2'])): ?>
                    <img id="image-preview-2" class="image-preview" src="<?php echo htmlspecialchars($property['image2']); ?>">
                    <?php else: ?>
                    <img id="image-preview-2" class="image-preview" style="display: none;">
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="form-group">
                <label for="image3" class="form-label">Additional Image URL</label>
                <input type="url" id="image3" name="image3" class="form-control" value="<?php echo htmlspecialchars($property['image3'] ?? ''); ?>">
                <div class="image-preview-container">
                    <?php if(!empty($property['image3'])): ?>
                    <img id="image-preview-3" class="image-preview" src="<?php echo htmlspecialchars($property['image3']); ?>">
                    <?php else: ?>
                    <img id="image-preview-3" class="image-preview" style="display: none;">
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="form-group">
                <label for="image4" class="form-label">Additional Image URL</label>
                <input type="url" id="image4" name="image4" class="form-control" value="<?php echo htmlspecialchars($property['image4'] ?? ''); ?>">
                <div class="image-preview-container">
                    <?php if(!empty($property['image4'])): ?>
                    <img id="image-preview-4" class="image-preview" src="<?php echo htmlspecialchars($property['image4']); ?>">
                    <?php else: ?>
                    <img id="image-preview-4" class="image-preview" style="display: none;">
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="form-group">
            <button type="submit" class="btn btn-primary btn-block">Update Property</button>
        </div>
    </form>
</div>

<style>
.form-container {
    background-color: white;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    padding: 30px;
    margin-bottom: 40px;
}

.form-section {
    margin-bottom: 30px;
    padding-bottom: 20px;
    border-bottom: 1px solid #eee;
}

.form-section h3 {
    margin-bottom: 20px;
}

.form-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
}

.image-preview-container {
    margin-top: 10px;
}

.image-preview {
    max-width: 100%;
    max-height: 200px;
    border-radius: 4px;
    margin-top: 10px;
}
</style>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize map for location picking
    const initialLat = <?php echo $property['latitude']; ?>;
    const initialLng = <?php echo $property['longitude']; ?>;
    
    const mapPicker = L.map('map-picker').setView([initialLat, initialLng], 13);
    
    // Add OpenStreetMap tile layer
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
    }).addTo(mapPicker);
    
    // Add initial marker
    let marker = L.marker([initialLat, initialLng]).addTo(mapPicker);
    
    // Handle click on map
    mapPicker.on('click', function(e) {
        const lat = e.latlng.lat;
        const lng = e.latlng.lng;
        
        // Update form fields
        document.getElementById('latitude').value = lat;
        document.getElementById('longitude').value = lng;
        
        // Update marker position
        marker.setLatLng(e.latlng);
    });
    
    // Image URL preview
    function updateImagePreview(inputId, previewId) {
        const input = document.getElementById(inputId);
        const preview = document.getElementById(previewId);
        
        if (input.value) {
            preview.src = input.value;
            preview.style.display = 'block';
            
            // Handle image load error
            preview.onerror = function() {
                preview.style.display = 'none';
                input.setCustomValidity('Invalid image URL. Please provide a valid URL.');
            };
            
            preview.onload = function() {
                input.setCustomValidity('');
            };
        } else {
            preview.style.display = 'none';
        }
    }
    
    // Monitor image URL inputs
    const imageInputs = ['image1', 'image2', 'image3', 'image4'];
    imageInputs.forEach((id, index) => {
        const input = document.getElementById(id);
        input.addEventListener('input', function() {
            updateImagePreview(id, `image-preview-${index + 1}`);
        });
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>
