<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

// Set content type to JSON
header('Content-Type: application/json');

// Handle GET requests (user retrieval)
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Check if user is logged in as admin
    if (!isLoggedIn() || !hasRole('admin')) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Permission denied'
        ]);
        exit;
    }
    
    // Get specific user if ID is provided
    if (isset($_GET['id']) && !empty($_GET['id'])) {
        $userId = intval($_GET['id']);
        
        $query = "SELECT id, name, email, phone, role, created_at, updated_at FROM users WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            
            echo json_encode([
                'status' => 'success',
                'user' => $user
            ]);
        } else {
            echo json_encode([
                'status' => 'error',
                'message' => 'User not found'
            ]);
        }
        
        $stmt->close();
        exit;
    }
    
    // Get users with optional role filter
    $role = '';
    if (isset($_GET['role']) && !empty($_GET['role'])) {
        $role = sanitize($_GET['role']);
    }
    
    $users = getAllUsers($role);
    
    echo json_encode([
        'status' => 'success',
        'users' => $users
    ]);
    exit;
}

// Handle POST requests (user operations)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get request body
    $requestData = json_decode(file_get_contents('php://input'), true);
    
    // Check if user is logged in
    if (!isLoggedIn()) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Authentication required'
        ]);
        exit;
    }
    
    // Handle different actions
    $action = isset($requestData['action']) ? $requestData['action'] : '';
    
    switch ($action) {
        case 'delete':
            // Delete user (admin only)
            if (!hasRole('admin')) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Permission denied'
                ]);
                exit;
            }
            
            $userId = isset($requestData['user_id']) ? intval($requestData['user_id']) : 0;
            
            // Make sure admin is not deleting themselves
            if ($userId == $_SESSION['user_id']) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'You cannot delete your own account'
                ]);
                exit;
            }
            
            // Delete user
            $query = "DELETE FROM users WHERE id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("i", $userId);
            
            if ($stmt->execute()) {
                echo json_encode([
                    'status' => 'success',
                    'message' => 'User deleted successfully'
                ]);
            } else {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Error deleting user: ' . $conn->error
                ]);
            }
            
            $stmt->close();
            break;
            
        case 'update_role':
            // Update user role (admin only)
            if (!hasRole('admin')) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Permission denied'
                ]);
                exit;
            }
            
            $userId = isset($requestData['user_id']) ? intval($requestData['user_id']) : 0;
            $role = isset($requestData['role']) ? sanitize($requestData['role']) : '';
            
            // Validate role
            if (empty($role) || !in_array($role, ['buyer', 'seller', 'admin'])) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Invalid role'
                ]);
                exit;
            }
            
            // Update user role
            $query = "UPDATE users SET role = ? WHERE id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("si", $role, $userId);
            
            if ($stmt->execute()) {
                echo json_encode([
                    'status' => 'success',
                    'message' => 'User role updated to ' . ucfirst($role)
                ]);
            } else {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Error updating user role: ' . $conn->error
                ]);
            }
            
            $stmt->close();
            break;
            
        default:
            echo json_encode([
                'status' => 'error',
                'message' => 'Invalid action'
            ]);
            break;
    }
    
    exit;
}

// Handle other request methods
echo json_encode([
    'status' => 'error',
    'message' => 'Invalid request method'
]);
exit;
?>
