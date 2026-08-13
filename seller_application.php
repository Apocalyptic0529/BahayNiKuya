
<?php
$pageTitle = "Become a Seller";
require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

// Require buyer authentication
requireAuth('buyer');

// Process application form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $applicationData = [
        'user_id' => $_SESSION['user_id'],
        'business_name' => isset($_POST['business_name']) ? sanitize($_POST['business_name']) : '',
        'experience' => isset($_POST['experience']) ? sanitize($_POST['experience']) : '',
        'license_number' => isset($_POST['license_number']) ? sanitize($_POST['license_number']) : ''
    ];
    
    // Check if already applied
    $checkQuery = "SELECT id FROM seller_applications WHERE user_id = ?";
    $stmt = $conn->prepare($checkQuery);
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $_SESSION['error_message'] = 'You have already submitted an application.';
    } else {
        // Insert application
        $query = "INSERT INTO seller_applications (user_id, business_name, experience, license_number) 
                  VALUES (?, ?, ?, ?)";
        
        $stmt = $conn->prepare($query);
        if ($stmt) {
            $stmt->bind_param("isss", 
                $applicationData['user_id'],
                $applicationData['business_name'],
                $applicationData['experience'],
                $applicationData['license_number']
            );
            
            if ($stmt->execute()) {
                $_SESSION['success_message'] = 'Your application has been submitted successfully! Please wait for admin approval.';
                header('Location: buyer_dashboard.php');
                exit;
            } else {
                $_SESSION['error_message'] = 'Error submitting application: ' . $conn->error;
            }
            $stmt->close();
        }
    }
}

require_once 'includes/header.php';
?>

<div class="container mt-5">
    <h1>Become a Seller</h1>
    
    <?php if (isset($_SESSION['error_message'])): ?>
        <div class="alert alert-danger">
            <?php 
            echo $_SESSION['error_message'];
            unset($_SESSION['error_message']);
            ?>
        </div>
    <?php endif; ?>
    
    <div class="form-container">
        <form action="seller_application.php" method="POST" class="needs-validation">
            <div class="form-group mb-3">
                <label for="business_name" class="form-label">Business Name (if applicable)</label>
                <input type="text" id="business_name" name="business_name" class="form-control">
            </div>
            
            <div class="form-group mb-3">
                <label for="license_number" class="form-label">Real Estate License Number (if available)</label>
                <input type="text" id="license_number" name="license_number" class="form-control">
            </div>
            
            <div class="form-group mb-3">
                <label for="experience" class="form-label">Real Estate Experience *</label>
                <textarea id="experience" name="experience" class="form-control" rows="5" required 
                    placeholder="Please describe your experience in real estate, including years of experience, types of properties you've dealt with, and any relevant certifications."></textarea>
            </div>
            
            <div class="form-group">
                <button type="submit" class="btn btn-primary">Submit Application</button>
            </div>
        </form>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
