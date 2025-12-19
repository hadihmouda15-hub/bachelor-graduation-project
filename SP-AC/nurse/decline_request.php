<?php


require_once 'db_connection.php'; // Make sure this connects like in the simple test

// Check if the form is submitted and contains the necessary data
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_id']) && isset($_POST['decline_reason'])) {
    $requestId = $_POST['request_id'];
    $declineReason = $_POST['decline_reason'];

    // Update the request's status to 'rejected' and save the decline reason
    $query = "UPDATE request SET RequestStatus = 'rejected', declinereason = ? WHERE RequestID = ?";
    
    if ($stmt = $conn->prepare($query)) {
        $stmt->bind_param("si", $declineReason, $requestId);
        if ($stmt->execute()) {
            header("Location: privaterequests.php"); // Adjust the redirect URL as needed
            exit();
        } else {
            // Error message
            echo "Error: " . $stmt->error;
        }
    } else {
        echo "Error: " . $conn->error;
    }

    $stmt->close();
} else {
    echo "Invalid request.";
}

$conn->close();
?>
