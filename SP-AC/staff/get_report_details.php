<?php
require_once 'db_connection.php';

if (isset($_GET['report_id'])) {
    $report_id = intval($_GET['report_id']);
    
    $sql = "SELECT r.*, 
            reporter.FullName as ReporterName, 
            reported.FullName as ReportedName,
            req.RequestID as RequestID,
            req.Type as RequestType
            FROM report r
            LEFT JOIN user reporter ON r.ReporterID = reporter.UserID
            LEFT JOIN user reported ON r.ReportedID = reported.UserID
            LEFT JOIN request req ON r.RequestID = req.RequestID
            WHERE r.ReportID = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $report_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $report = $result->fetch_assoc();
        header('Content-Type: application/json');
        echo json_encode($report);
    } else {
        echo json_encode(['error' => 'Report not found']);
    }
} else {
    echo json_encode(['error' => 'No report ID provided']);
}
?>