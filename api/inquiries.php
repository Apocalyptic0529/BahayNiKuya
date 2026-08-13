<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

// Set content type to JSON
header('Content-Type: application/json');

// Handle GET requests (inquiry retrieval)
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Check if user is logged in
    if (!isLoggedIn()) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Authentication required'
        ]);
        exit;
    }

    // Get inquiries for current user based on role
    $userId = $_SESSION['user_id'];
    $userRole = $_SESSION['user_role'];

    // Handle filtering
    $filters = [];
    if (isset($_GET['status'])) {
        $filters['status'] = sanitize($_GET['status']);
    }

    if (isset($_GET['property_id'])) {
        $filters['property_id'] = intval($_GET['property_id']);
    }

    $inquiries = getUserInquiries($userId, $userRole, $filters);

    echo json_encode([
        'status' => 'success',
        'inquiries' => $inquiries
    ]);
    exit;
}

// Handle POST requests (inquiry operations)
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
        case 'create':
            // Create a new inquiry - buyer only
            if (!hasRole('buyer')) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Only buyers can make inquiries'
                ]);
                exit;
            }

            $propertyId = isset($requestData['property_id']) ? intval($requestData['property_id']) : 0;
            $sellerId = isset($requestData['seller_id']) ? intval($requestData['seller_id']) : 0;
            $message = isset($requestData['message']) ? sanitize($requestData['message']) : '';

            // Validate inputs
            if ($propertyId <= 0 || $sellerId <= 0 || empty($message)) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Property ID, seller ID and message are required'
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

            // Insert inquiry
            $buyerId = $_SESSION['user_id'];
            $query = "INSERT INTO inquiries (property_id, buyer_id, seller_id, message, status) VALUES (?, ?, ?, ?, 'new')";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("iiis", $propertyId, $buyerId, $sellerId, $message);

            if ($stmt->execute()) {
                echo json_encode([
                    'status' => 'success',
                    'message' => 'Your inquiry has been sent to the property owner.'
                ]);
            } else {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Error sending inquiry: ' . $conn->error
                ]);
            }

            $stmt->close();
            break;

        case 'update_status':
            // Update inquiry status - only for own inquiries (seller or admin)
            if (!hasRole('seller') && !hasRole('admin')) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Permission denied'
                ]);
                exit;
            }

            $inquiryId = isset($requestData['inquiry_id']) ? intval($requestData['inquiry_id']) : 0;
            $status = isset($requestData['status']) ? sanitize($requestData['status']) : '';

            // Validate inputs
            if ($inquiryId <= 0 || empty($status) || !in_array($status, ['new', 'read', 'replied', 'closed'])) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Invalid inquiry ID or status'
                ]);
                exit;
            }

            // Get inquiry info
            $query = "SELECT * FROM inquiries WHERE id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("i", $inquiryId);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows == 0) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Inquiry not found'
                ]);
                exit;
            }

            $inquiry = $result->fetch_assoc();
            $stmt->close();

            // Check if user has permission
            if (hasRole('seller') && $inquiry['seller_id'] != $_SESSION['user_id']) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'You do not have permission to update this inquiry'
                ]);
                exit;
            }

            // Update inquiry status
            $query = "UPDATE inquiries SET status = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("si", $status, $inquiryId);

            if ($stmt->execute()) {
                // Also update reports if status is being changed to 'dismissed'
                if ($status === 'dismissed') {
                    $reportQuery = "UPDATE reports SET status = 'dismissed' WHERE inquiry_id = ?";
                    $reportStmt = $conn->prepare($reportQuery);
                    $reportStmt->bind_param('i', $inquiryId);
                    $reportStmt->execute();
                }
                echo json_encode([
                    'status' => 'success',
                    'message' => 'Inquiry status updated'
                ]);
            } else {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Error updating inquiry: ' . $conn->error
                ]);
            }

            $stmt->close();
            break;

        case 'reply':
            // Reply to an inquiry - seller only
            if (!hasRole('seller') && !hasRole('admin')) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Permission denied'
                ]);
                exit;
            }

            $inquiryId = isset($requestData['inquiry_id']) ? intval($requestData['inquiry_id']) : 0;
            $message = isset($requestData['message']) ? sanitize($requestData['message']) : '';

            // Validate inputs
            if ($inquiryId <= 0 || empty($message)) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Inquiry ID and message are required'
                ]);
                exit;
            }

            // Get inquiry info
            $query = "SELECT * FROM inquiries WHERE id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("i", $inquiryId);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows == 0) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Inquiry not found'
                ]);
                exit;
            }

            $inquiry = $result->fetch_assoc();
            $stmt->close();

            // Check if user has permission
            if (hasRole('seller') && $inquiry['seller_id'] != $_SESSION['user_id']) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'You do not have permission to reply to this inquiry'
                ]);
                exit;
            }

            // Update inquiry status and reply message
            $query = "UPDATE inquiries SET status = 'replied', reply_message = ?, reply_date = CURRENT_TIMESTAMP WHERE id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("si", $message, $inquiryId);

            if ($stmt->execute()) {
                // Now we would typically send an email or notification here
                // For this demo, we'll just update the status

                echo json_encode([
                    'status' => 'success',
                    'message' => 'Your reply has been sent to the buyer'
                ]);
            } else {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Error sending reply: ' . $conn->error
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