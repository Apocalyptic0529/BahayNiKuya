<?php
require_once '../../includes/config.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';

requireAuth('admin');
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true) ?: [];
$reportId = intval($data['report_id'] ?? 0);
$action = $data['action'] ?? '';

if (!$reportId || !in_array($action, ['resolve', 'dismiss'], true)) {
    jsonResponse(['status' => 'error', 'message' => 'Invalid report action.'], 400);
}

$status = $action === 'resolve' ? 'resolved' : 'dismissed';
$stmt = $conn->prepare("UPDATE reports SET status = ? WHERE id = ? AND status = 'pending'");
if (!$stmt) {
    jsonResponse(['status' => 'error', 'message' => 'Database error: ' . $conn->error], 500);
}
$stmt->bind_param('si', $status, $reportId);
if ($stmt->execute()) {
    jsonResponse(['status' => 'success', 'message' => 'Report updated successfully.']);
}
jsonResponse(['status' => 'error', 'message' => 'Report could not be updated.'], 500);
