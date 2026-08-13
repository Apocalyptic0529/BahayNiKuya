<?php
$pageTitle = "Update Profile";
require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

// Require authentication
if (!isLoggedIn()) {
    $_SESSION['error_message'] = 'You must be logged in to update your profile.';
    header('Location: login.php');
    exit;
}

// Get current user
$user = getCurrentUser();

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate form inputs
    $name = isset($_POST['name']) ? sanitize($_POST['name']) : '';
    $phone = isset($_POST['phone']) ? sanitize($_POST['phone']) : '';
    $newPassword = isset($_POST['new_password']) ? $_POST['new_password'] : '';
    $confirmPassword = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';
    
    // Validate required fields
    if (empty($name)) {
        $_SESSION['error_message'] = 'Name is required.';
    } elseif (!empty($newPassword) && $newPassword !== $confirmPassword) {
        $_SESSION['error_message'] = 'Passwords do not match.';
    } else {
        // Prepare query to update user info
        $query = "UPDATE users SET name = ?, phone = ?";
        $params = [$name, $phone];
        $types = "ss";
        
        // Update password if provided
        if (!empty($newPassword)) {
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $query .= ", password = ?";
            $params[] = $hashedPassword;
            $types .= "s";
        }
        
        $query .= " WHERE id = ?";
        $params[] = $_SESSION['user_id'];
        $types .= "i";
        
        // Execute query
        $stmt = $conn->prepare($query);
        $stmt->bind_param($types, ...$params);
        
        if ($stmt->execute()) {
            // Update session data
            $_SESSION['user_name'] = $name;
            
            $_SESSION['success_message'] = 'Profile updated successfully!';
            
            // Redirect to appropriate dashboard
            header('Location: ' . getRedirectUrl($_SESSION['user_role']));
            exit;
        } else {
            $_SESSION['error_message'] = 'Error updating profile: ' . $conn->error;
        }
        
        $stmt->close();
    }
}

// Redirect to appropriate dashboard
header('Location: ' . getRedirectUrl($_SESSION['user_role']));
exit;
?>
