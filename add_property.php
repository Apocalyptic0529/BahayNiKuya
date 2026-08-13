<?php
$pageTitle = "Add Property";
require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

// Make database connection available in all scopes
global $conn;

// Require seller authentication
requireAuth('seller');

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
    $featured = isset($_POST['featured']) ? 1 : 0;
    
    // Force pending status for all new properties
    $status = 'pending';
    
    // Unset any attempt to override status
    unset($_POST['status']);
    
    // Validate required fields
    if (empty($title) || empty($description) || $price <= 0 || $bedrooms <= 0 || 
        $bathrooms <= 0 || $area <= 0 || empty($address) || empty($city) || 
        empty($state) || empty($zip_code) || empty($property_type) || empty($status)) {

        $_SESSION['error_message'] = 'All fields are required.';
    } else {
        // Process image uploads
        $image1 = '';
        $image2 = '';
        $image3 = '';
        $image4 = '';
        $uploadError = false;

        // Check if uploads directory exists, create if not
        $uploadDir = 'uploads/properties';
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        // Function to handle image uploads
        function handleImageUpload($fileKey, $index) {
            global $uploadError, $conn;

            // Check for direct file upload
            if (isset($_FILES[$fileKey]) && $_FILES[$fileKey]['error'] == 0) {
                $file = $_FILES[$fileKey];
                $fileName = $file['name'];
                $fileTmpName = $file['tmp_name'];
                $fileSize = $file['size'];
                $fileError = $file['error'];

                // Get file extension
                $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                // Allowed extensions
                $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];

                if (in_array($fileExt, $allowedExtensions)) {
                    if ($fileSize < 5000000) { // 5MB max
                        // Create unique filename
                        $fileNameNew = uniqid('property_') . '_' . time() . '.' . $fileExt;
                        $fileDestination = 'uploads/properties/' . $fileNameNew;

                        if (move_uploaded_file($fileTmpName, $fileDestination)) {
                            return $fileDestination;
                        } else {
                            // Handle error but continue
                            if ($index == 1) {
                                $_SESSION['error_message'] = 'Error saving uploaded file.';
                                $uploadError = true;
                            }
                            return '';
                        }
                    } else {
                        // File too large
                        if ($index == 1) {
                            $_SESSION['error_message'] = 'File too large. Max size is 5MB.';
                            $uploadError = true;
                        }
                        return '';
                    }
                } else {
                    // Invalid file type
                    if ($index == 1) {
                        $_SESSION['error_message'] = 'Invalid file type. Only JPG, JPEG, PNG, and GIF are allowed.';
                        $uploadError = true;
                    }
                    return '';
                }
            } 

            // Check for URL input if no file uploaded
            $urlKey = $fileKey . '_url';
            if (isset($_POST[$urlKey]) && !empty($_POST[$urlKey])) {
                $imageUrl = sanitize($_POST[$urlKey]);

                // Basic URL validation
                if (filter_var($imageUrl, FILTER_VALIDATE_URL)) {
                    return $imageUrl;
                } else {
                    if ($index == 1) {
                        $_SESSION['error_message'] = 'Invalid image URL format.';
                        $uploadError = true;
                    }
                    return '';
                }
            }

            // If this is the first image and no file or URL was provided, mark as error
            if ($index == 1) {
                $_SESSION['error_message'] = 'At least one property image is required.';
                $uploadError = true;
            }

            return '';
        }

        // Process each image
        $image1 = handleImageUpload('image1', 1);
        $image2 = handleImageUpload('image2', 2);
        $image3 = handleImageUpload('image3', 3);
        $image4 = handleImageUpload('image4', 4);

        // Ensure at least one image is provided
        if (empty($image1) || $uploadError) {
            if (empty($_SESSION['error_message'])) {
                $_SESSION['error_message'] = 'At least one image is required.';
            }
        } else {
            // Insert property into database
            $sellerId = $_SESSION['user_id'];
            global $conn;  // Make sure we have access to the database connection

            $query = "INSERT INTO properties (
                    seller_id, title, description, price, bedrooms, bathrooms, area, 
                    address, city, state, zip_code, latitude, longitude, 
                    property_type, status, featured, image1, image2, image3, image4
                ) VALUES (
                    ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
                )";

            // Set empty images to null
            $image2 = empty($image2) ? null : $image2;
            $image3 = empty($image3) ? null : $image3;
            $image4 = empty($image4) ? null : $image4;

            $stmt = $conn->prepare($query);
            if ($stmt) {
                $stmt->bind_param(
                    "issdddssssddssisssss",
                    $sellerId, $title, $description, $price, $bedrooms, $bathrooms, $area,
                    $address, $city, $state, $zip_code, $latitude, $longitude,
                    $property_type, $status, $featured, $image1, $image2, $image3, $image4
                );

                if ($stmt->execute()) {
                    $propertyId = $conn->insert_id;
                    $_SESSION['success_message'] = 'Property added successfully!';
                    header('Location: property_details.php?id=' . $propertyId);
                    exit;
                } else {
                    $_SESSION['error_message'] = 'Error adding property: ' . $conn->error;
                }

                $stmt->close();
            } else {
                $_SESSION['error_message'] = 'Database error: ' . $conn->error;
            }
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

<h1>Add New Property</h1>

<div class="form-container">
    <form id="property-form" action="add_property.php" method="POST" class="needs-validation" enctype="multipart/form-data">
        <div class="form-section">
            <h3>Basic Information</h3>

            <div class="form-group">
                <label for="title" class="form-label">Property Title *</label>
                <input type="text" id="title" name="title" class="form-control" required>
            </div>

            <div class="form-group">
                <label for="description" class="form-label">Description *</label>
                <textarea id="description" name="description" class="form-control" rows="5" required></textarea>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="price" class="form-label">Price ($) *</label>
                    <input type="number" id="price" name="price" class="form-control" min="1" step="0.01" required>
                </div>

                <div class="form-group">
                    <label for="property_type" class="form-label">Property Type *</label>
                    <select id="property_type" name="property_type" class="form-select" required>
                        <option value="">Select Type</option>
                        <option value="house">House</option>
                        <option value="apartment">Apartment</option>
                        <option value="condo">Condo</option>
                        <option value="land">Land</option>
                        <option value="commercial">Commercial</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Status</label>
                    <input type="text" class="form-control" value="Pending Admin Approval" disabled>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="bedrooms" class="form-label">Bedrooms *</label>
                    <input type="number" id="bedrooms" name="bedrooms" class="form-control" min="0" required>
                </div>

                <div class="form-group">
                    <label for="bathrooms" class="form-label">Bathrooms *</label>
                    <input type="number" id="bathrooms" name="bathrooms" class="form-control" min="0" step="0.5" required>
                </div>

                <div class="form-group">
                    <label for="area" class="form-label">Area (sq ft) *</label>
                    <input type="number" id="area" name="area" class="form-control" min="1" step="0.01" required>
                </div>
            </div>

            </div>

        <div class="form-section">
            <h3>Location Information</h3>

            <div class="form-group">
                <label for="address" class="form-label">Address *</label>
                <input type="text" id="address" name="address" class="form-control" required>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="city" class="form-label">City *</label>
                    <input type="text" id="city" name="city" class="form-control" required>
                </div>

                <div class="form-group">
                    <label for="state" class="form-label">State *</label>
                    <input type="text" id="state" name="state" class="form-control" required>
                </div>

                <div class="form-group">
                    <label for="zip_code" class="form-label">ZIP Code *</label>
                    <input type="text" id="zip_code" name="zip_code" class="form-control" required>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="latitude" class="form-label">Latitude *</label>
                    <input type="number" id="latitude" name="latitude" class="form-control" step="0.00000001" required>
                </div>

                <div class="form-group">
                    <label for="longitude" class="form-label">Longitude *</label>
                    <input type="number" id="longitude" name="longitude" class="form-control" step="0.00000001" required>
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
            <p>Upload property images or provide image URLs. At least one image is required.</p>

            <div class="form-group">
                <label for="image1" class="form-label">Main Property Image *</label>
                <div class="input-group mb-3">
                    <input type="file" id="image1" name="image1" class="form-control" accept="image/*">
                </div>
                <div class="mt-2">
                    <label class="form-label">Or enter an image URL:</label>
                    <input type="url" id="image1_url" name="image1_url" class="form-control" placeholder="https://...">
                </div>
                <small class="text-muted">Suggested image URLs:</small>
                <div class="image-suggestions">
                    <button type="button" class="btn btn-sm btn-outline-primary image-suggestion" data-target="image1_url" data-url="https://pixabay.com/get/g48813c3c4b1c54c75a6c1ad75c62fd6b13d9ca382dbcf348f84e9cb7bd32aadde9e5a1f0dd12552a10416caa682fbb0a8cfb21902acc9eea0418219388b3b237_1280.jpg">Property 1</button>
                    <button type="button" class="btn btn-sm btn-outline-primary image-suggestion" data-target="image1_url" data-url="https://pixabay.com/get/g6d4c467a3666eab2d21f8a557a5571dd903263035aa1f54c0d2e2868e8ea8a5bc245fcec5d07ca7adf2e745de80f64a833da466bcee0db5043229f8f1d255522_1280.jpg">Property 2</button>
                    <button type="button" class="btn btn-sm btn-outline-primary image-suggestion" data-target="image1_url" data-url="https://pixabay.com/get/gb74c7be7cefc0d8f3ad165d869a52f0ca7db30a3b2c55d86b17cddc6059c539fba3f6132662f70439b331e364d9a678d256df4f72334e8057ec1918ab0ba370f_1280.jpg">Property 3</button>
                </div>
                <div class="image-preview-container">
                    <img id="image-preview-1" class="image-preview" style="display: none;">
                </div>
            </div>

            <div class="form-group">
                <label for="image2" class="form-label">Additional Property Image</label>
                <div class="input-group mb-3">
                    <input type="file" id="image2" name="image2" class="form-control" accept="image/*">
                </div>
                <div class="mt-2">
                    <label class="form-label">Or enter an image URL:</label>
                    <input type="url" id="image2_url" name="image2_url" class="form-control" placeholder="https://...">
                </div>
                <small class="text-muted">Suggested image URLs:</small>
                <div class="image-suggestions">
                    <button type="button" class="btn btn-sm btn-outline-primary image-suggestion" data-target="image2_url" data-url="https://pixabay.com/get/gcd67267c234f76f7b705a47672907145ec492181668fb2060eed17a208e5eb1443fb1429e583e2ecd4f6d8d36b5640755cbfcf7f6877c78fa4e02ad05328502a_1280.jpg">Modern House 1</button>
                    <button type="button" class="btn btn-sm btn-outline-primary image-suggestion" data-target="image2_url" data-url="https://pixabay.com/get/g6aee0f5e3fcf475706cbc804f7e7aa569cab2e16d59dda71ac640e01daf66028305299b889e81b4ad82a1fc91bd92ab308fb0364138e09855249f06b5b2580d2_1280.jpg">Modern House 2</button>
                </div>
                <div class="image-preview-container">
                    <img id="image-preview-2" class="image-preview" style="display: none;">
                </div>
            </div>

            <div class="form-group">
                <label for="image3" class="form-label">Additional Property Image</label>
                <div class="input-group mb-3">
                    <input type="file" id="image3" name="image3" class="form-control" accept="image/*">
                </div>
                <div class="mt-2">
                    <label class="form-label">Or enter an image URL:</label>
                    <input type="url" id="image3_url" name="image3_url" class="form-control" placeholder="https://...">
                </div>
                <div class="image-preview-container">
                    <img id="image-preview-3" class="image-preview" style="display: none;">
                </div>
            </div>

            <div class="form-group">
                <label for="image4" class="form-label">Additional Property Image</label>
                <div class="input-group mb-3">
                    <input type="file" id="image4" name="image4" class="form-control" accept="image/*">
                </div>
                <div class="mt-2">
                    <label class="form-label">Or enter an image URL:</label>
                    <input type="url" id="image4_url" name="image4_url" class="form-control" placeholder="https://...">
                </div>
                <div class="image-preview-container">
                    <img id="image-preview-4" class="image-preview" style="display: none;">
                </div>
            </div>
        </div>

        <div class="form-group">
            <button type="submit" class="btn btn-primary btn-block">Add Property</button>
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

.image-suggestions {
    display: flex;
    flex-wrap: wrap;
    gap: 5px;
    margin: 5px 0 10px 0;
}
</style>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize map for location picking
    const mapPicker = L.map('map-picker').setView([34.0522, -118.2437], 10);

    // Add OpenStreetMap tile layer
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
    }).addTo(mapPicker);

    // Add a marker that will be moved when clicking on the map
    let marker = null;

    // Handle click on map
    mapPicker.on('click', function(e) {
        const lat = e.latlng.lat;
        const lng = e.latlng.lng;

        // Update form fields
        document.getElementById('latitude').value = lat;
        document.getElementById('longitude').value = lng;

        // Update or create marker
        if (marker) {
            marker.setLatLng(e.latlng);
        } else {
            marker = L.marker(e.latlng).addTo(mapPicker);
        }
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

        // Check initial value
        if (input.value) {
            updateImagePreview(id, `image-preview-${index + 1}`);
        }
    });

    // Handle image suggestion clicks
    const imageSuggestions = document.querySelectorAll('.image-suggestion');
    imageSuggestions.forEach(button => {
        button.addEventListener('click', function() {
            const url = this.getAttribute('data-url');
            const parent = this.closest('.form-group');
            const input = parent.querySelector('input[type="url"]');

            input.value = url;

            // Trigger input event to update preview
            const event = new Event('input');
            input.dispatchEvent(event);
        });
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>