<?php
require_once 'db_connection.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['staff_id'])) {
    $staff_id = intval($_POST['staff_id']);
    $admin_id = 20; // Admin ID
    
    $stmt = $conn->prepare("UPDATE notification SET Status = 'Read' 
                          WHERE SenderID = ? AND RecipientID = ? AND Status = 'Unread'");
    $stmt->bind_param("ii", $admin_id, $staff_id);
    $stmt->execute();
    $stmt->close();
}

header("Location: notifications.php");
exit();
?>