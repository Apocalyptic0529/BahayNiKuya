<?php
$pageTitle = "Register";
require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

// Check if user is already logged in
if(isLoggedIn()) {
    // Redirect to appropriate dashboard
    header('Location: ' . getRedirectUrl($_SESSION['user_role']));
    exit;
}

// Process registration form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userData = [
        'name' => isset($_POST['name']) ? $_POST['name'] : '',
        'email' => isset($_POST['email']) ? $_POST['email'] : '',
        'password' => isset($_POST['password']) ? $_POST['password'] : '',
        'phone' => isset($_POST['phone']) ? $_POST['phone'] : '',
        'role' => isset($_POST['role']) && in_array($_POST['role'], ['buyer', 'seller']) ? $_POST['role'] : 'buyer'
    ];
    
    // Confirm password
    $confirmPassword = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';
    
    // Validate password match
    if ($userData['password'] !== $confirmPassword) {
        $_SESSION['error_message'] = 'Passwords do not match.';
    } else {
        $result = registerUser($userData);
        
        if ($result['status'] === 'success') {
            $_SESSION['success_message'] = $result['message'];
            header('Location: login.php');
            exit;
        } else {
            $_SESSION['error_message'] = $result['message'];
        }
    }
}

require_once 'includes/header.php';
?>

<div class="auth-container">
    <h2>Create New Account</h2>
    
    <form action="register.php" method="POST" class="needs-validation">
        <div class="form-group">
            <label for="name" class="form-label">Full Name</label>
            <input type="text" id="name" name="name" class="form-control" required>
        </div>
        
        <div class="form-group">
            <label for="email" class="form-label">Email Address</label>
            <input type="email" id="email" name="email" class="form-control" required>
        </div>
        
        <div class="form-group">
            <label for="phone" class="form-label">Phone Number (optional)</label>
            <input type="tel" id="phone" name="phone" class="form-control">
        </div>
        
        <div class="form-group">
            <label for="password" class="form-label">Password</label>
            <input type="password" id="password" name="password" class="form-control" required>
        </div>
        
        <div class="form-group">
            <label for="confirm_password" class="form-label">Confirm Password</label>
            <input type="password" id="confirm_password" name="confirm_password" class="form-control" required>
        </div>
        
        <input type="hidden" name="role" value="buyer">
        
        <div class="form-group">
            <button type="submit" class="btn btn-primary btn-block">Register</button>
        </div>
    </form>
    
    <div class="auth-links">
        <p>Already have an account? <a href="login.php">Login here</a></p>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
