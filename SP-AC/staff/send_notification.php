<?php
require_once 'db_connection.php';

header('Content-Type: application/json');

// Staff ID and type are fixed as per requirements
$senderId = 21;
$senderType = 'staff';

// Get POST data
$recipientId = $_POST['recipient_id'] ?? null;
$recipientType = $_POST['recipient_type'] ?? null;
$title = $_POST['title'] ?? '';
$message = $_POST['message'] ?? '';
$type = $_POST['type'] ?? 'report_update';

if (!$recipientId || !$recipientType) {
    echo json_encode(['success' => false, 'error' => 'Missing recipient information']);
    exit;
}

// Insert notification
$sql = "INSERT INTO notification 
        (SenderID, SenderType, RecipientID, RecipientType, Title, Message, Type)
        VALUES (?, ?, ?, ?, ?, ?, ?)";

$stmt = $conn->prepare($sql);
$stmt->bind_param(
    "isissss",
    $senderId,
    $senderType,
    $recipientId,
    $recipientType,
    $title,
    $message,
    $type
);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => 'Database error']);
}
?>