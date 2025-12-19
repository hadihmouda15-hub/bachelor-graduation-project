<?php
require_once 'db_connection.php'; // Make sure this connects like in the simple test


// Check if the form is submitted and contains the necessary data
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_id'])) {
    $requestId = $_POST['request_id'];
    $source_page = $_POST['source_page'];


    // Update the request's status to 'inprocess'
    $query = "UPDATE request SET RequestStatus = 'inprocess' WHERE RequestID = ?";

    if ($stmt = $conn->prepare($query)) {
        $stmt->bind_param("i", $requestId);
        if ($stmt->execute()) {
            if ( $source_page == 'requestsstatus.php' ) {
            header("Location: requestsstatus.php"); // Adjust the redirect URL as needed
            exit();    
            }
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
