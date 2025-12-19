<?php
require_once 'db_connection.php';

header('Content-Type: application/json');

if (isset($_GET['report_id'])) {
    $report_id = intval($_GET['report_id']);
    
    // First get report details to know who to notify
    $sql = "SELECT ReporterID, ReporterRole, ReportedID, ReportedRole FROM report WHERE ReportID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $report_id);
    $stmt->execute();
    $report = $stmt->get_result()->fetch_assoc();
    
    if (!$report) {
        echo json_encode(['success' => false, 'error' => 'Report not found']);
        exit;
    }
    
    // Update report status
    $update_sql = "UPDATE report SET Status = 'resolved' WHERE ReportID = ?";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param("i", $report_id);
    
    if ($update_stmt->execute()) {
        // Send notification to reporter
        $notify_reporter = sendNotification(
            $conn,
            18, // Staff ID (sender)
            'staff',
            $report['ReporterID'],
            $report['ReporterRole'],
            'Report Resolved',
            'Your report #'.$report_id.' has been resolved by the staff.',
            'report_resolved'
        );
        
        // Send notification to reported user
        $notify_reported = sendNotification(
            $conn,
            18, // Staff ID (sender)
            'staff',
            $report['ReportedID'],
            $report['ReportedRole'],
            'Report Resolved',
            'The report #'.$report_id.' about you has been resolved by the staff.',
            'report_resolved'
        );
        
        echo json_encode(['success' => true]);
    }
     else {
        echo json_encode(['success' => false, 'error' => 'Database error']);
    }
 }
else {
    echo json_encode(['success' => false, 'error' => 'No report ID provided']);
}

function sendNotification($conn, $senderId, $senderType, $recipientId, $recipientType, $title, $message, $type) {
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
    
    return $stmt->execute();
}
?>