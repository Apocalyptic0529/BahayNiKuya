<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

// Set content type to JSON
header('Content-Type: application/json');

// Handle GET requests (favorites retrieval)
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Check if user is logged in as a buyer
    if (!isLoggedIn() || !hasRole('buyer')) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Permission denied'
        ]);
        exit;
    }
    
    // Get favorites for current user
    $favorites = getFavoriteProperties($_SESSION['user_id']);
    
    echo json_encode([
        'status' => 'success',
        'favorites' => $favorites
    ]);
    exit;
}

// Handle POST requests (add/remove favorites)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get request body
    $requestData = json_decode(file_get_contents('php://input'), true);
    
    // Check if user is logged in as a buyer
    if (!isLoggedIn() || !hasRole('buyer')) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Only buyers can save favorites'
        ]);
        exit;
    }
    
    // Get property ID and action
    $propertyId = isset($requestData['property_id']) ? intval($requestData['property_id']) : 0;
    $action = isset($requestData['action']) ? $requestData['action'] : '';
    
    // Validate inputs
    if ($propertyId <= 0 || empty($action) || !in_array($action, ['add', 'remove'])) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Invalid property ID or action'
        ]);
        exit;
    }
    
    // Check if property exists
    $property = getPropertyById($propertyId);
    if (!$property) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Property not found'
        ]);
        exit;
    }
    
    $buyerId = $_SESSION['user_id'];
    
    if ($action === 'add') {
        // Check if already favorited
        if (isPropertyFavorite($propertyId, $buyerId)) {
            echo json_encode([
                'status' => 'success',
                'message' => 'Property is already in your favorites'
            ]);
            exit;
        }
        
        // Add to favorites
        $query = "INSERT INTO favorites (buyer_id, property_id) VALUES (?, ?)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ii", $buyerId, $propertyId);
        
        if ($stmt->execute()) {
            echo json_encode([
                'status' => 'success',
                'message' => 'Property added to favorites'
            ]);
        } else {
            echo json_encode([
                'status' => 'error',
                'message' => 'Error adding to favorites: ' . $conn->error
            ]);
        }
        
        $stmt->close();
    } else {
        // Remove from favorites
        $query = "DELETE FROM favorites WHERE buyer_id = ? AND property_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ii", $buyerId, $propertyId);
        
        if ($stmt->execute()) {
            echo json_encode([
                'status' => 'success',
                'message' => 'Property removed from favorites'
            ]);
        } else {
            echo json_encode([
                'status' => 'error',
                'message' => 'Error removing from favorites: ' . $conn->error
            ]);
        }
        
        $stmt->close();
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
