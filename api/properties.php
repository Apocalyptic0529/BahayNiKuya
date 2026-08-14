<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

// Set content type to JSON
header('Content-Type: application/json');

// Handle GET requests (property retrieval and search)
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Check if this is a map request
    $isMapRequest = isset($_GET['map']) && $_GET['map'] == 1;
    
    // Get filter parameters
    $filters = [];
    
    if (isset($_GET['min_price']) && !empty($_GET['min_price'])) {
        $filters['min_price'] = floatval($_GET['min_price']);
    }
    
    if (isset($_GET['max_price']) && !empty($_GET['max_price'])) {
        $filters['max_price'] = floatval($_GET['max_price']);
    }
    
    if (isset($_GET['bedrooms']) && !empty($_GET['bedrooms'])) {
        $filters['bedrooms'] = intval($_GET['bedrooms']);
    }
    
    if (isset($_GET['bathrooms']) && !empty($_GET['bathrooms'])) {
        $filters['bathrooms'] = intval($_GET['bathrooms']);
    }
    
    if (isset($_GET['property_type']) && !empty($_GET['property_type'])) {
        $filters['property_type'] = sanitize($_GET['property_type']);
    }
    
    if (isset($_GET['city']) && !empty($_GET['city'])) {
        $filters['city'] = sanitize($_GET['city']);
    }
    
    if (isset($_GET['status']) && !empty($_GET['status'])) {
        $filters['status'] = sanitize($_GET['status']);
    }
    
    if (isset($_GET['search']) && !empty($_GET['search'])) {
        $filters['search'] = sanitize($_GET['search']);
    }
    
    if (isset($_GET['seller_id']) && !empty($_GET['seller_id'])) {
        $filters['seller_id'] = intval($_GET['seller_id']);
    }
    
    if (isset($_GET['featured']) && $_GET['featured'] == 1) {
        $filters['featured'] = true;
    }
    
    if (isset($_GET['limit']) && !empty($_GET['limit'])) {
        $filters['limit'] = intval($_GET['limit']);
    }
    
    // Only show pending properties to admins
    if (!hasRole('admin')) {
        $filters['status_not'] = ['pending', 'rejected'];
    }
    $properties = getProperties($filters);
    
    // If this is a map request, just return basic info
    if ($isMapRequest) {
        // Return success response with properties
        echo json_encode([
            'status' => 'success',
            'properties' => $properties
        ]);
        exit;
    }
    
    // Get property by ID if specified
    if (isset($_GET['id']) && !empty($_GET['id'])) {
        $propertyId = intval($_GET['id']);
        $property = getPropertyById($propertyId);
        $canViewPrivate = hasRole('admin') || (isLoggedIn() && intval($property['seller_id'] ?? 0) === intval($_SESSION['user_id'] ?? 0));
        if ($property && (!in_array($property['status'] ?? '', ['pending', 'rejected', 'pending_deletion'], true) || $canViewPrivate)) {
            echo json_encode([
                'status' => 'success',
                'property' => $property
            ]);
        } else {
            echo json_encode([
                'status' => 'error',
                'message' => 'Property not found'
            ]);
        }
        exit;
    }
    
    // Return success response with properties
    echo json_encode([
        'status' => 'success',
        'properties' => $properties
    ]);
    exit;
}

// Handle POST requests (property creation, update, deletion)
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
        case 'request_delete':
            // Seller requests deletion; admin must approve it before permanent deletion.
            if (!hasRole('seller')) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Permission denied'
                ]);
                exit;
            }
            
            $propertyId = isset($requestData['property_id']) ? intval($requestData['property_id']) : 0;
            
            // Get property info
            $property = getPropertyById($propertyId);
            
            if (!$property) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Property not found'
                ]);
                exit;
            }
            
            // Check if user owns the property
            if ($property['seller_id'] != $_SESSION['user_id']) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'You do not have permission to delete this property'
                ]);
                exit;
            }
            
            // Delete property directly
            $query = "UPDATE properties SET status = 'pending_deletion' WHERE id = ? AND status IN ('pending', 'for_sale', 'for_rent', 'sold', 'rented')";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("i", $propertyId);

            if ($stmt->execute()) {
                echo json_encode([
                    'status' => 'success',
                    'message' => 'Deletion request submitted. An administrator must approve it.'
                ]);
            } else {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Error deleting property: ' . $conn->error
                ]);
            }
            
            $stmt->close();
            break;

        case 'approve_deletion':
            // Approve property deletion (admin only)
            if (!hasRole('admin')) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Permission denied'
                ]);
                exit;
            }
            
            $propertyId = isset($requestData['property_id']) ? intval($requestData['property_id']) : 0;
            
            // Delete property
            $query = "DELETE FROM properties WHERE id = ? AND status = 'pending_deletion'";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("i", $propertyId);
            
            if ($stmt->execute()) {
                echo json_encode([
                    'status' => 'success',
                    'message' => 'Property deleted successfully'
                ]);
            } else {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Error deleting property: ' . $conn->error
                ]);
            }
            
            $stmt->close();
            break;
            
        case 'toggle_featured':
            // Toggle featured status
            if (!hasRole('seller') && !hasRole('admin')) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Permission denied'
                ]);
                exit;
            }
            
            $propertyId = isset($requestData['property_id']) ? intval($requestData['property_id']) : 0;
            $featured = isset($requestData['featured']) ? ($requestData['featured'] ? 1 : 0) : 0;
            
            // Get property info
            $property = getPropertyById($propertyId);
            
            if (!$property) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Property not found'
                ]);
                exit;
            }
            
            // Only admins can feature/unfeature listings.
            if (!hasRole('admin')) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Only administrators can change featured status'
                ]);
                exit;
            }
            
            // Check if user has permission to update
            if (hasRole('seller') && $property['seller_id'] != $_SESSION['user_id']) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'You do not have permission to update this property'
                ]);
                exit;
            }
            
            // Update featured status
            $query = "UPDATE properties SET featured = ? WHERE id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("ii", $featured, $propertyId);
            
            if ($stmt->execute()) {
                echo json_encode([
                    'status' => 'success',
                    'message' => 'Property featured status updated'
                ]);
            } else {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Error updating property: ' . $conn->error
                ]);
            }
            
            $stmt->close();
            break;
            
        case 'report':
            // Report property
            if (!isLoggedIn()) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'You must be logged in to report a property'
                ]);
                exit;
            }
            
            $propertyId = isset($requestData['property_id']) ? intval($requestData['property_id']) : 0;
            $reason = isset($requestData['reason']) ? sanitize($requestData['reason']) : '';
            
            if (empty($reason)) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Reason is required'
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
            
            // Insert report
            $userId = $_SESSION['user_id'];
            $query = "INSERT INTO reports (property_id, user_id, reason) VALUES (?, ?, ?)";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("iis", $propertyId, $userId, $reason);
            
            if ($stmt->execute()) {
                echo json_encode([
                    'status' => 'success',
                    'message' => 'Property reported successfully. An administrator will review the report.'
                ]);
            } else {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Error reporting property: ' . $conn->error
                ]);
            }
            
            $stmt->close();
            break;
            
        case 'update_report':
            // Update report status (admin only)
            if (!hasRole('admin')) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Permission denied'
                ]);
                exit;
            }
            
            $reportId = isset($requestData['report_id']) ? intval($requestData['report_id']) : 0;
            $status = isset($requestData['status']) ? sanitize($requestData['status']) : '';
            
            if (empty($status) || !in_array($status, ['resolved', 'dismissed'])) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Invalid status'
                ]);
                exit;
            }
            
            // Update report status
            $query = "UPDATE reports SET status = ? WHERE id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("si", $status, $reportId);
            
            if ($stmt->execute()) {
                echo json_encode([
                    'status' => 'success',
                    'message' => 'Report updated successfully'
                ]);
            } else {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Error updating report: ' . $conn->error
                ]);
            }
            
            $stmt->close();
            break;
            
        case 'approve_property':
            // Approve property (admin only)
            if (!hasRole('admin')) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Permission denied'
                ]);
                exit;
            }
            
            $propertyId = isset($requestData['property_id']) ? intval($requestData['property_id']) : 0;
            
            // Update property status to approved
            $query = "UPDATE properties SET status = ? WHERE id = ?";
            $newStatus = isset($requestData['new_status']) ? sanitize($requestData['new_status']) : 'for_sale';
            $stmt = $conn->prepare($query);
            $stmt->bind_param("si", $newStatus, $propertyId);
            
            if ($stmt->execute()) {
                echo json_encode([
                    'status' => 'success',
                    'message' => 'Property approved'
                ]);
            } else {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Error approving property: ' . $conn->error
                ]);
            }
            
            $stmt->close();
            break;
            
        case 'reject_property':
            // Reject property without deleting it (admin only)
            if (!hasRole('admin')) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Permission denied'
                ]);
                exit;
            }
            
            $propertyId = isset($requestData['property_id']) ? intval($requestData['property_id']) : 0;
            
            // Keep the property record and mark it rejected.
            $query = "UPDATE properties SET status = 'rejected' WHERE id = ? AND status = 'pending'";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("i", $propertyId);
            
            if ($stmt->execute()) {
                echo json_encode([
                    'status' => 'success',
                    'message' => 'Property rejected'
                ]);
            } else {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Error rejecting property: ' . $conn->error
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
