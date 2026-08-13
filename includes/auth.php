<?php
// Include database configuration
require_once 'config.php';
require_once 'functions.php';

/**
 * Register a new user
 * 
 * @param array $userData User data (name, email, password, phone, role)
 * @return array Response with status and message
 */
function registerUser($userData) {
    global $conn;
    
    // Validate required fields
    if (empty($userData['name']) || empty($userData['email']) || empty($userData['password'])) {
        return ['status' => 'error', 'message' => 'Name, email and password are required'];
    }
    
    // Check if email already exists
    $email = strtolower(trim($userData['email']));
    $query = "SELECT id FROM users WHERE email = '$email'";
    $result = $conn->query($query);
    
    if ($result && $result->num_rows > 0) {
        return ['status' => 'error', 'message' => 'Email already exists. Please use a different email.'];
    }
    
    // Hash password
    $password = password_hash($userData['password'], PASSWORD_DEFAULT);
    
    // Prepare role
    $role = 'buyer'; // Default role
    if (isset($userData['role']) && in_array($userData['role'], ['buyer', 'seller', 'admin'])) {
        $role = $userData['role'];
    }
    
    // Prepare phone
    $phone = isset($userData['phone']) ? sanitize($userData['phone']) : '';
    
    // Insert user
    $name = sanitize($userData['name']);
    $query = "INSERT INTO users (name, email, password, phone, role) VALUES ('$name', '$email', '$password', '$phone', '$role')";
    
    if ($conn->query($query)) {
        return ['status' => 'success', 'message' => 'Registration successful! You can now log in.'];
    } else {
        return ['status' => 'error', 'message' => 'Registration failed: ' . $conn->error];
    }
}

/**
 * Login a user
 * 
 * @param string $email User email
 * @param string $password User password
 * @return array Response with status and message
 */
function loginUser($email, $password) {
    global $conn;
    
    if (empty($email) || empty($password)) {
        return ['status' => 'error', 'message' => 'Email and password are required'];
    }
    
    $email = strtolower(trim($email));
    
    $query = "SELECT * FROM users WHERE email = '$email'";
    $result = $conn->query($query);
    
    if ($result && $result->num_rows > 0) {
        $user = $result->fetch_assoc();
        
        if (password_verify($password, $user['password'])) {
            // Set session variables
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_role'] = $user['role'];
            
            return [
                'status' => 'success', 
                'message' => 'Login successful!',
                'redirect' => getRedirectUrl($user['role'])
            ];
        } else {
            return ['status' => 'error', 'message' => 'Invalid password'];
        }
    } else {
        return ['status' => 'error', 'message' => 'User not found'];
    }
}

/**
 * Get redirect URL based on user role
 * 
 * @param string $role User role
 * @return string Redirect URL
 */
function getRedirectUrl($role) {
    switch ($role) {
        case 'buyer':
            return 'buyer_dashboard.php';
        case 'seller':
            return 'seller_dashboard.php';
        case 'admin':
            return 'admin_dashboard.php';
        default:
            return 'index.php';
    }
}

/**
 * Check if user is logged in
 * 
 * @return bool True if user is logged in
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

/**
 * Check if current user has a specific role
 * 
 * @param string $role Role to check
 * @return bool True if user has the role
 */
function hasRole($role) {
    return isLoggedIn() && $_SESSION['user_role'] == $role;
}

/**
 * Get current user data
 * 
 * @return array User data or empty array with default values if not found
 */
function getCurrentUser() {
    global $conn;
    
    if (!isLoggedIn()) {
        // Return default user data structure instead of false
        return [
            'id' => 0,
            'name' => 'Guest',
            'email' => '',
            'phone' => '',
            'role' => '',
            'created_at' => '',
            'updated_at' => ''
        ];
    }
    
    $userId = intval($_SESSION['user_id']);
    $query = "SELECT * FROM users WHERE id = $userId";
    $result = $conn->query($query);
    
    if ($result && $result->num_rows > 0) {
        return $result->fetch_assoc();
    }
    
    // Return default user data structure instead of false
    return [
        'id' => $userId,
        'name' => $_SESSION['user_name'] ?? 'Unknown',
        'email' => $_SESSION['user_email'] ?? '',
        'phone' => '',
        'role' => $_SESSION['user_role'] ?? '',
        'created_at' => '',
        'updated_at' => ''
    ];
}

/**
 * Logout the current user
 */
function logoutUser() {
    // Unset all session variables
    $_SESSION = [];
    
    // Destroy the session
    session_destroy();
}

/**
 * Check if page requires authentication
 * 
 * @param string $requiredRole Required role (optional)
 */
function requireAuth($requiredRole = '') {
    if (!isLoggedIn()) {
        // Redirect to login page
        header('Location: login.php');
        exit;
    }
    
    if (!empty($requiredRole) && !hasRole($requiredRole)) {
        // Redirect to appropriate dashboard
        header('Location: ' . getRedirectUrl($_SESSION['user_role']));
        exit;
    }
}
?>
