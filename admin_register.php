<?php
$pageTitle = "Admin Registration";
require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

// Process registration form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $adminKey = isset($_POST['admin_key']) ? $_POST['admin_key'] : '';
    
    // Validate admin key (you can change this to a more secure key)
    $validAdminKey = 'admin123';
    
    if ($adminKey !== $validAdminKey) {
        $_SESSION['error_message'] = 'Invalid admin key. Access denied.';
    } else {
        $userData = [
            'name' => isset($_POST['name']) ? $_POST['name'] : '',
            'email' => isset($_POST['email']) ? $_POST['email'] : '',
            'password' => isset($_POST['password']) ? $_POST['password'] : '',
            'phone' => isset($_POST['phone']) ? $_POST['phone'] : '',
            'role' => 'admin' // Force role to be admin
        ];
        
        // Confirm password
        $confirmPassword = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';
        
        // Validate password match
        if ($userData['password'] !== $confirmPassword) {
            $_SESSION['error_message'] = 'Passwords do not match.';
        } else {
            $result = registerUser($userData);
            
            if ($result['status'] === 'success') {
                $_SESSION['success_message'] = $result['message'] . ' You can now log in with your admin credentials.';
                header('Location: login.php');
                exit;
            } else {
                $_SESSION['error_message'] = $result['message'];
            }
        }
    }
}

require_once 'includes/header.php';
?>

<div class="auth-container">
    <h2>Admin Registration</h2>
    <p>This page is for creating admin accounts only. Regular users should register <a href="register.php">here</a>.</p>
    
    <form action="admin_register.php" method="POST" class="needs-validation">
        <div class="form-group">
            <label for="admin_key" class="form-label">Admin Key</label>
            <input type="password" id="admin_key" name="admin_key" class="form-control" required>
            <small class="text-muted">This is a secret key required to create admin accounts.</small>
        </div>
        
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
        
        <div class="form-group">
            <button type="submit" class="btn btn-primary btn-block">Register Admin Account</button>
        </div>
    </form>
    
    <div class="auth-links">
        <p><a href="login.php">Back to Login</a></p>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
