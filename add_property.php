<?php
$pageTitle = "Add Property";
require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

// Make database connection available in all scopes
global $conn;

// Require seller authentication
requireAuth('seller');

// Restore a previously entered property draft when validation fails.
$propertyDraft = $_SESSION['property_draft'] ?? [];

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Collect and sanitize all non-file form values.
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
    $status = 'pending';

    // Keep URL images from the current submission, otherwise retain the draft.
    $draftDir = 'uploads/property_drafts/' . intval($_SESSION['user_id']);
    if (!is_dir($draftDir)) {
        @mkdir($draftDir, 0775, true);
    }

    $imageValues = [];
    $uploadError = '';

    for ($i = 1; $i <= 4; $i++) {
        $fileKey = 'image' . $i;
        $urlKey = $fileKey . '_url';
        $existing = isset($propertyDraft[$fileKey]) ? $propertyDraft[$fileKey] : '';
        $value = '';

        // A newly uploaded file takes precedence over an old draft image.
        if (isset($_FILES[$fileKey]) && $_FILES[$fileKey]['error'] !== UPLOAD_ERR_NO_FILE) {
            $file = $_FILES[$fileKey];
            if ($file['error'] !== UPLOAD_ERR_OK) {
                $uploadError = 'There was a problem uploading image ' . $i . '. Please try again.';
            } else {
                $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                if (!in_array($ext, $allowedExtensions, true)) {
                    $uploadError = 'Image ' . $i . ' has an invalid file type. Allowed types: JPG, JPEG, PNG, GIF, and WEBP.';
                } elseif ($file['size'] > 5000000) {
                    $uploadError = 'Image ' . $i . ' is too large. The maximum size is 5MB.';
                } else {
                    $fileNameNew = uniqid('property_draft_', true) . '.' . $ext;
                    $fileDestination = $draftDir . '/' . $fileNameNew;
                    if (move_uploaded_file($file['tmp_name'], $fileDestination)) {
                        // Remove the old draft file if it was a local upload.
                        if (!empty($existing) && strpos($existing, $draftDir . '/') === 0 && file_exists($existing)) {
                            @unlink($existing);
                        }
                        $value = $fileDestination;
                    } else {
                        $uploadError = 'Error saving image ' . $i . '. Please try again.';
                    }
                }
            }
        }

        // If no new file was selected, use the supplied URL when present.
        if ($value === '' && isset($_POST[$urlKey]) && trim($_POST[$urlKey]) !== '') {
            $imageUrl = trim($_POST[$urlKey]);
            if (filter_var($imageUrl, FILTER_VALIDATE_URL)) {
                if (!empty($existing) && strpos($existing, $draftDir . '/') === 0 && file_exists($existing)) {
                    @unlink($existing);
                }
                $value = sanitize($imageUrl);
            } else {
                $uploadError = 'Invalid image URL format for image ' . $i . '.';
                $value = $existing;
            }
        }

        // Keep the previously saved image when this submission did not replace it.
        if ($value === '') {
            $value = $existing;
        }

        $imageValues[$fileKey] = $value;
    }

    $image1 = $imageValues['image1'];
    $image2 = $imageValues['image2'];
    $image3 = $imageValues['image3'];
    $image4 = $imageValues['image4'];

    // Save the complete draft before validation so nothing entered by the seller is lost.
    $propertyDraft = [
        'title' => $title,
        'description' => $description,
        'price' => $_POST['price'] ?? '',
        'bedrooms' => $_POST['bedrooms'] ?? '',
        'bathrooms' => $_POST['bathrooms'] ?? '',
        'area' => $_POST['area'] ?? '',
        'address' => $address,
        'city' => $city,
        'state' => $state,
        'zip_code' => $zip_code,
        'latitude' => $_POST['latitude'] ?? '',
        'longitude' => $_POST['longitude'] ?? '',
        'property_type' => $property_type,
        'featured' => $featured,
        'image1' => $image1,
        'image2' => $image2,
        'image3' => $image3,
        'image4' => $image4,
        'image1_url' => $_POST['image1_url'] ?? '',
        'image2_url' => $_POST['image2_url'] ?? '',
        'image3_url' => $_POST['image3_url'] ?? '',
        'image4_url' => $_POST['image4_url'] ?? ''
    ];
    $_SESSION['property_draft'] = $propertyDraft;

    $validationErrors = [];
    if ($title === '') $validationErrors[] = 'Property title is required.';
    if ($description === '') $validationErrors[] = 'Description is required.';
    if ($price <= 0) $validationErrors[] = 'A valid price is required.';
    if ($bedrooms <= 0) $validationErrors[] = 'Bedrooms must be greater than 0.';
    if ($bathrooms <= 0) $validationErrors[] = 'Bathrooms must be greater than 0.';
    if ($area <= 0) $validationErrors[] = 'Area must be greater than 0.';
    if ($address === '') $validationErrors[] = 'Address is required.';
    if ($city === '') $validationErrors[] = 'City is required.';
    if ($state === '') $validationErrors[] = 'State/Province is required.';
    if ($zip_code === '') $validationErrors[] = 'ZIP code is required.';
    if ($property_type === '') $validationErrors[] = 'Property type is required.';
    if ($image1 === '') $validationErrors[] = 'At least one property image is required.';
    if ($uploadError !== '') $validationErrors[] = $uploadError;

    if (!empty($validationErrors)) {
        $_SESSION['error_message'] = implode(' ', $validationErrors) . ' Your entries have been temporarily saved.';
    } else {
        $sellerId = $_SESSION['user_id'];

        $query = "INSERT INTO properties (
                seller_id, title, description, price, bedrooms, bathrooms, area,
                address, city, state, zip_code, latitude, longitude,
                property_type, status, featured, image1, image2, image3, image4
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        // Move local draft images to the permanent property upload directory.
        $uploadDir = 'uploads/properties';
        if (!is_dir($uploadDir)) {
            @mkdir($uploadDir, 0775, true);
        }

        foreach (['image1', 'image2', 'image3', 'image4'] as $imageKey) {
            if (!empty($propertyDraft[$imageKey]) && strpos($propertyDraft[$imageKey], $draftDir . '/') === 0 && file_exists($propertyDraft[$imageKey])) {
                $permanentName = basename($propertyDraft[$imageKey]);
                $permanentPath = $uploadDir . '/' . $permanentName;
                if (@rename($propertyDraft[$imageKey], $permanentPath)) {
                    $propertyDraft[$imageKey] = $permanentPath;
                }
            }
        }

        $image1 = $propertyDraft['image1'] ?? '';
        $image2 = !empty($propertyDraft['image2']) ? $propertyDraft['image2'] : null;
        $image3 = !empty($propertyDraft['image3']) ? $propertyDraft['image3'] : null;
        $image4 = !empty($propertyDraft['image4']) ? $propertyDraft['image4'] : null;

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
                unset($_SESSION['property_draft']);
                $_SESSION['success_message'] = 'Property added successfully!';
                header('Location: property_details.php?id=' . $propertyId);
                exit;
            } else {
                $_SESSION['error_message'] = 'Error adding property: ' . $conn->error . ' Your entries have been temporarily saved.';
            }
            $stmt->close();
        } else {
            $_SESSION['error_message'] = 'Database error: ' . $conn->error . ' Your entries have been temporarily saved.';
        }
    }
}

// Use the latest saved draft to repopulate the form.
$propertyDraft = $_SESSION['property_draft'] ?? $propertyDraft;

// Additional styles and scripts
$additionalStyles = '';
$additionalScripts = '
    <script src="assets/js/property.js"></script>
';

require_once 'includes/header.php';
?>

<h1>Add New Property</h1>
<?php if (!empty($_SESSION['property_draft'])): ?>
    <div class="alert alert-info"><i class="fas fa-save"></i> Your previous entries have been temporarily saved. You can continue editing them below.</div>
<?php endif; ?>

<div class="form-container">
    <form id="property-form" action="add_property.php" method="POST" class="needs-validation" enctype="multipart/form-data">
        <div class="form-section">
            <h3>Basic Information</h3>

            <div class="form-group">
                <label for="title" class="form-label">Property Title *</label>
                <input type="text" id="title" name="title" class="form-control" value="<?php echo htmlspecialchars($propertyDraft['title'] ?? '', ENT_QUOTES); ?>" required>
            </div>

            <div class="form-group">
                <label for="description" class="form-label">Description *</label>
                <textarea id="description" name="description" class="form-control" rows="5" required><?php echo htmlspecialchars($propertyDraft['description'] ?? '', ENT_QUOTES); ?></textarea>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="price" class="form-label">Price ($) *</label>
                    <input type="number" id="price" name="price" class="form-control" min="1" step="0.01" value="<?php echo htmlspecialchars($propertyDraft['price'] ?? '', ENT_QUOTES); ?>" required>
                </div>

                <div class="form-group">
                    <label for="property_type" class="form-label">Property Type *</label>
                    <select id="property_type" name="property_type" class="form-select" required>
                        <option value="">Select Type</option>
                        <option value="house" <?php echo (($propertyDraft['property_type'] ?? '') === 'house') ? 'selected' : ''; ?>>House</option>
                        <option value="apartment" <?php echo (($propertyDraft['property_type'] ?? '') === 'apartment') ? 'selected' : ''; ?>>Apartment</option>
                        <option value="condo" <?php echo (($propertyDraft['property_type'] ?? '') === 'condo') ? 'selected' : ''; ?>>Condo</option>
                        <option value="land" <?php echo (($propertyDraft['property_type'] ?? '') === 'land') ? 'selected' : ''; ?>>Land</option>
                        <option value="commercial" <?php echo (($propertyDraft['property_type'] ?? '') === 'commercial') ? 'selected' : ''; ?>>Commercial</option>
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
                    <input type="number" id="bedrooms" name="bedrooms" class="form-control" value="<?php echo htmlspecialchars($propertyDraft['bedrooms'] ?? '', ENT_QUOTES); ?>" min="0" required>
                </div>

                <div class="form-group">
                    <label for="bathrooms" class="form-label">Bathrooms *</label>
                    <input type="number" id="bathrooms" name="bathrooms" class="form-control" value="<?php echo htmlspecialchars($propertyDraft['bathrooms'] ?? '', ENT_QUOTES); ?>" min="0" step="0.5" required>
                </div>

                <div class="form-group">
                    <label for="area" class="form-label">Area (sq ft) *</label>
                    <input type="number" id="area" name="area" class="form-control" value="<?php echo htmlspecialchars($propertyDraft['area'] ?? '', ENT_QUOTES); ?>" min="1" step="0.01" required>
                </div>
            </div>

            </div>

        <div class="form-section">
            <h3>Location Information</h3>

            <div class="form-group">
                <label for="address" class="form-label">Address *</label>
                <input type="text" id="address" name="address" class="form-control" value="<?php echo htmlspecialchars($propertyDraft['address'] ?? '', ENT_QUOTES); ?>" required>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="city" class="form-label">City *</label>
                    <input type="text" id="city" name="city" class="form-control" value="<?php echo htmlspecialchars($propertyDraft['city'] ?? '', ENT_QUOTES); ?>" required>
                </div>

                <div class="form-group">
                    <label for="state" class="form-label">State *</label>
                    <input type="text" id="state" name="state" class="form-control" value="<?php echo htmlspecialchars($propertyDraft['state'] ?? '', ENT_QUOTES); ?>" required>
                </div>

                <div class="form-group">
                    <label for="zip_code" class="form-label">ZIP Code *</label>
                    <input type="text" id="zip_code" name="zip_code" class="form-control" value="<?php echo htmlspecialchars($propertyDraft['zip_code'] ?? '', ENT_QUOTES); ?>" required>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="latitude" class="form-label">Latitude *</label>
                    <input type="number" id="latitude" name="latitude" class="form-control" value="<?php echo htmlspecialchars($propertyDraft['latitude'] ?? '', ENT_QUOTES); ?>" step="0.00000001" required>
                </div>

                <div class="form-group">
                    <label for="longitude" class="form-label">Longitude *</label>
                    <input type="number" id="longitude" name="longitude" class="form-control" value="<?php echo htmlspecialchars($propertyDraft['longitude'] ?? '', ENT_QUOTES); ?>" step="0.00000001" required>
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
                    <input type="url" id="image1_url" name="image1_url" class="form-control" placeholder="https://..." value="<?php echo htmlspecialchars($propertyDraft['image1_url'] ?? '', ENT_QUOTES); ?>">
                </div>
                <small class="text-muted">Suggested image URLs:</small>
                <div class="image-suggestions">
                    <button type="button" class="btn btn-sm btn-outline-primary image-suggestion" data-target="image1_url" data-url="https://pixabay.com/get/g48813c3c4b1c54c75a6c1ad75c62fd6b13d9ca382dbcf348f84e9cb7bd32aadde9e5a1f0dd12552a10416caa682fbb0a8cfb21902acc9eea0418219388b3b237_1280.jpg">Property 1</button>
                    <button type="button" class="btn btn-sm btn-outline-primary image-suggestion" data-target="image1_url" data-url="https://pixabay.com/get/g6d4c467a3666eab2d21f8a557a5571dd903263035aa1f54c0d2e2868e8ea8a5bc245fcec5d07ca7adf2e745de80f64a833da466bcee0db5043229f8f1d255522_1280.jpg">Property 2</button>
                    <button type="button" class="btn btn-sm btn-outline-primary image-suggestion" data-target="image1_url" data-url="https://pixabay.com/get/gb74c7be7cefc0d8f3ad165d869a52f0ca7db30a3b2c55d86b17cddc6059c539fba3f6132662f70439b331e364d9a678d256df4f72334e8057ec1918ab0ba370f_1280.jpg">Property 3</button>
                </div>
                <div class="image-preview-container">
                    <img id="image-preview-1" class="image-preview" style="display: none;" src="">
                    <?php if (!empty($propertyDraft['image1'])): ?>
                        <div class="saved-image-note">Saved image: <strong><?php echo htmlspecialchars(basename($propertyDraft['image1']), ENT_QUOTES); ?></strong></div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="form-group">
                <label for="image2" class="form-label">Additional Property Image</label>
                <div class="input-group mb-3">
                    <input type="file" id="image2" name="image2" class="form-control" accept="image/*">
                </div>
                <div class="mt-2">
                    <label class="form-label">Or enter an image URL:</label>
                    <input type="url" id="image2_url" name="image2_url" class="form-control" placeholder="https://..." value="<?php echo htmlspecialchars($propertyDraft['image2_url'] ?? '', ENT_QUOTES); ?>">
                </div>
                <small class="text-muted">Suggested image URLs:</small>
                <div class="image-suggestions">
                    <button type="button" class="btn btn-sm btn-outline-primary image-suggestion" data-target="image2_url" data-url="https://pixabay.com/get/gcd67267c234f76f7b705a47672907145ec492181668fb2060eed17a208e5eb1443fb1429e583e2ecd4f6d8d36b5640755cbfcf7f6877c78fa4e02ad05328502a_1280.jpg">Modern House 1</button>
                    <button type="button" class="btn btn-sm btn-outline-primary image-suggestion" data-target="image2_url" data-url="https://pixabay.com/get/g6aee0f5e3fcf475706cbc804f7e7aa569cab2e16d59dda71ac640e01daf66028305299b889e81b4ad82a1fc91bd92ab308fb0364138e09855249f06b5b2580d2_1280.jpg">Modern House 2</button>
                </div>
                <div class="image-preview-container">
                    <img id="image-preview-2" class="image-preview" style="display: none;" src="">
                    <?php if (!empty($propertyDraft['image2'])): ?>
                        <div class="saved-image-note">Saved image: <strong><?php echo htmlspecialchars(basename($propertyDraft['image2']), ENT_QUOTES); ?></strong></div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="form-group">
                <label for="image3" class="form-label">Additional Property Image</label>
                <div class="input-group mb-3">
                    <input type="file" id="image3" name="image3" class="form-control" accept="image/*">
                </div>
                <div class="mt-2">
                    <label class="form-label">Or enter an image URL:</label>
                    <input type="url" id="image3_url" name="image3_url" class="form-control" placeholder="https://..." value="<?php echo htmlspecialchars($propertyDraft['image3_url'] ?? '', ENT_QUOTES); ?>">
                </div>
                <div class="image-preview-container">
                    <img id="image-preview-3" class="image-preview" style="display: none;" src="">
                    <?php if (!empty($propertyDraft['image3'])): ?>
                        <div class="saved-image-note">Saved image: <strong><?php echo htmlspecialchars(basename($propertyDraft['image3']), ENT_QUOTES); ?></strong></div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="form-group">
                <label for="image4" class="form-label">Additional Property Image</label>
                <div class="input-group mb-3">
                    <input type="file" id="image4" name="image4" class="form-control" accept="image/*">
                </div>
                <div class="mt-2">
                    <label class="form-label">Or enter an image URL:</label>
                    <input type="url" id="image4_url" name="image4_url" class="form-control" placeholder="https://..." value="<?php echo htmlspecialchars($propertyDraft['image4_url'] ?? '', ENT_QUOTES); ?>">
                </div>
                <div class="image-preview-container">
                    <img id="image-preview-4" class="image-preview" style="display: none;" src="">
                    <?php if (!empty($propertyDraft['image4'])): ?>
                        <div class="saved-image-note">Saved image: <strong><?php echo htmlspecialchars(basename($propertyDraft['image4']), ENT_QUOTES); ?></strong></div>
                    <?php endif; ?>
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

.saved-image-note {
    margin-top: 8px;
    font-size: 0.9rem;
    color: #666;
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

    // Image URL preview. File inputs cannot be repopulated by browsers after a failed
    // submission for security reasons, so the server saves uploaded files temporarily
    // and displays the saved preview instead.
    function updateImagePreview(inputId, previewId) {
        const input = document.getElementById(inputId);
        const preview = document.getElementById(previewId);
        const fileInput = document.getElementById(inputId.replace('_url', ''));

        if (!input || !preview) return;
        if (input.value) {
            preview.src = input.value;
            preview.style.display = 'block';
            preview.onerror = function() {
                preview.style.display = 'none';
                input.setCustomValidity('Invalid image URL. Please provide a valid URL.');
            };
            preview.onload = function() {
                input.setCustomValidity('');
            };
        } else if (!fileInput || !fileInput.files.length) {
            preview.style.display = 'none';
        }
    }

    const imageInputs = ['image1', 'image2', 'image3', 'image4'];
    imageInputs.forEach((id, index) => {
        const fileInput = document.getElementById(id);
        const urlInput = document.getElementById(id + '_url');
        const preview = document.getElementById(`image-preview-${index + 1}`);

        fileInput.addEventListener('change', function() {
            if (this.files && this.files[0]) {
                const objectUrl = URL.createObjectURL(this.files[0]);
                preview.src = objectUrl;
                preview.style.display = 'block';
            }
        });

        urlInput.addEventListener('input', function() {
            updateImagePreview(id + '_url', `image-preview-${index + 1}`);
        });

        if (urlInput.value) {
            updateImagePreview(id + '_url', `image-preview-${index + 1}`);
        }
    });

    // Show server-saved draft image previews.
    const savedDraftImages = <?php echo json_encode([
        1 => $propertyDraft['image1'] ?? '',
        2 => $propertyDraft['image2'] ?? '',
        3 => $propertyDraft['image3'] ?? '',
        4 => $propertyDraft['image4'] ?? ''
    ], JSON_UNESCAPED_SLASHES); ?>;
    Object.keys(savedDraftImages).forEach(function(index) {
        const saved = savedDraftImages[index];
        const preview = document.getElementById(`image-preview-${index}`);
        if (saved && preview && !document.getElementById(`image${index}_url`).value) {
            preview.src = saved;
            preview.style.display = 'block';
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