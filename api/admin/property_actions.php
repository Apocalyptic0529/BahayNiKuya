<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

// Require admin authentication
requireAuth('admin');

// Get the POST data
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['action'])) {
    jsonResponse(['status' => 'error', 'message' => 'Missing required action parameter'], 400);
}

$action = $data['action'];

// Handle seller applications first
if ($action === 'approve_seller' || $action === 'reject_seller') {
    if (!isset($data['application_id']) || !isset($data['user_id'])) {
        jsonResponse(['status' => 'error', 'message' => 'Missing required parameters'], 400);
        exit;
    }

    $applicationId = intval($data['application_id']);
    $userId = intval($data['user_id']);

    if ($action === 'approve_seller') {
        // Update application status and user role
        $conn->begin_transaction();
        try {
            // Update application status
            $appQuery = "UPDATE seller_applications SET status = 'approved' WHERE id = ?";
            $stmt = $conn->prepare($appQuery);
            $stmt->bind_param('i', $applicationId);
            $stmt->execute();
            $stmt->close();

            // Update user role
            $roleQuery = "UPDATE users SET role = 'seller' WHERE id = ?";
            $stmt = $conn->prepare($roleQuery);
            $stmt->bind_param('i', $userId);
            $stmt->execute();
            $stmt->close();

            $conn->commit();
            jsonResponse(['status' => 'success', 'message' => 'Seller application approved']);
        } catch (Exception $e) {
            $conn->rollback();
            jsonResponse(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()], 500);
        }
    } else {
        // Reject seller application
        $query = "UPDATE seller_applications SET status = 'rejected' WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param('i', $applicationId);
        if ($stmt->execute()) {
            jsonResponse(['status' => 'success', 'message' => 'Seller application rejected']);
        } else {
            jsonResponse(['status' => 'error', 'message' => 'Database error'], 500);
        }
    }
    exit;
}

// Handle property actions
if (!isset($data['property_id'])) {
    jsonResponse(['status' => 'error', 'message' => 'Missing required property_id parameter'], 400);
}

$propertyId = intval($data['property_id']);

// Get the property
$property = getPropertyById($propertyId);
if (!$property) {
    jsonResponse(['status' => 'error', 'message' => 'Property not found'], 404);
}

switch ($action) {
    case 'approve':
        // Only pending properties can be approved.
        if (($property['status'] ?? '') !== 'pending') {
            jsonResponse(['status' => 'error', 'message' => 'Only pending properties can be approved.'], 400);
        }
        $listingType = strtolower(trim((string)($property['listing_type'] ?? 'sale')));
        if (!in_array($listingType, ['sale', 'rent'], true)) {
            jsonResponse(['status' => 'error', 'message' => 'Property has an invalid listing type. Please edit it before approving.'], 400);
        }
        $newStatus = $listingType === 'rent' ? 'for_rent' : 'for_sale';
        $query = "UPDATE properties SET status = ? WHERE id = ? AND status = 'pending'";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("si", $newStatus, $propertyId);
        break;

    case 'reject':
        // Keep the property record so the seller/admin can see that it was rejected.
        if (($property['status'] ?? '') !== 'pending') {
            jsonResponse(['status' => 'error', 'message' => 'Only pending properties can be rejected.'], 400);
        }
        $newStatus = 'rejected';
        $query = "UPDATE properties SET status = ? WHERE id = ? AND status = 'pending'";
        $stmt = $conn->prepare($query);
        $stmt->bind_param('si', $newStatus, $propertyId);
        break;

    case 'approve_deletion':
        if (($property['status'] ?? '') !== 'pending_deletion') {
            jsonResponse(['status' => 'error', 'message' => 'This property is not awaiting deletion approval.'], 400);
        }
        $query = "DELETE FROM properties WHERE id = ? AND status = 'pending_deletion'";
        $stmt = $conn->prepare($query);
        $stmt->bind_param('i', $propertyId);
        if ($stmt->execute()) {
            jsonResponse(['status' => 'success', 'message' => 'Property deletion approved.']);
        }
        jsonResponse(['status' => 'error', 'message' => 'Failed to delete property.'], 500);

    case 'delete':
        // Direct permanent deletion is available only to administrators because
        // this endpoint itself requires admin authentication.
        $query = "DELETE FROM properties WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param('i', $propertyId);
        $result = $stmt->execute();
        
        if ($result) {
            jsonResponse(['status' => 'success', 'message' => 'Property deleted successfully']);
        } else {
            jsonResponse(['status' => 'error', 'message' => 'Failed to delete property'], 500);
        }
        exit;

    case 'feature':
    case 'unfeature':
        if (!in_array($property['status'] ?? '', ['for_sale', 'for_rent'], true)) {
            jsonResponse(['status' => 'error', 'message' => 'Only approved properties can be featured.'], 400);
        }
        $featured = ($action === 'feature') ? 1 : 0;
        $query = "UPDATE properties SET featured = ? WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param('ii', $featured, $propertyId);

        if ($stmt->execute()) {
            jsonResponse(['status' => 'success', 'message' => 'Property featured status updated']);
        } else {
            jsonResponse(['status' => 'error', 'message' => 'Database error'], 500);
        }
        exit;

    default:
        jsonResponse(['status' => 'error', 'message' => 'Invalid action'], 400);
}

if ($stmt) {
    if ($stmt->execute()) {
        jsonResponse(['status' => 'success', 'message' => 'Property ' . $action . 'd successfully']);
    } else {
        jsonResponse(['status' => 'error', 'message' => 'Database error'], 500);
    }
}
?>