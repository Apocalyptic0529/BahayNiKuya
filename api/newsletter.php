
<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = isset($_POST['email']) ? sanitize($_POST['email']) : '';
    
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        jsonResponse(['status' => 'error', 'message' => 'Please enter a valid email address'], 400);
    }
    
    $query = "INSERT INTO newsletter_subscribers (email) VALUES (?)";
    $stmt = $conn->prepare($query);
    
    if ($stmt) {
        $stmt->bind_param("s", $email);
        if ($stmt->execute()) {
            jsonResponse(['status' => 'success', 'message' => 'Thank you for subscribing to our newsletter!']);
        } else {
            jsonResponse(['status' => 'error', 'message' => 'Error subscribing. Please try again.'], 500);
        }
    }
}
?>
