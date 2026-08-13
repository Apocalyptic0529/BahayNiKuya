<?php
$pageTitle = "Login";
require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

// Check if user is already logged in
if(isLoggedIn()) {
    // Redirect to appropriate dashboard
    header('Location: ' . getRedirectUrl($_SESSION['user_role']));
    exit;
}

// Process login form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = isset($_POST['email']) ? $_POST['email'] : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    
    $result = loginUser($email, $password);
    
    if ($result['status'] === 'success') {
        // Redirect to appropriate dashboard
        header('Location: ' . $result['redirect']);
        exit;
    } else {
        // Store error message
        $_SESSION['error_message'] = $result['message'];
    }
}

require_once 'includes/header.php';
?>

<div class="auth-container">
    <h2>Login to Your Account</h2>
    
    <form action="login.php" method="POST" class="needs-validation">
        <div class="form-group">
            <label for="email" class="form-label">Email Address</label>
            <input type="email" id="email" name="email" class="form-control" required>
        </div>
        
        <div class="form-group">
            <label for="password" class="form-label">Password</label>
            <input type="password" id="password" name="password" class="form-control" required>
        </div>
        
        <div class="form-group">
            <button type="submit" class="btn btn-primary btn-block">Login</button>
        </div>
    </form>
    
    <div class="auth-links">
        <p>Don't have an account? <a href="register.php">Register here</a></p>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
