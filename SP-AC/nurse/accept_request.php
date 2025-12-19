<?php
session_start();
require_once 'db_connection.php'; // Make sure this connects like in the simple test

// Check if user is logged in and has user_id
if (!isset($_SESSION['nurse_id'])) {
    die("Error: User not logged in.");
}

// // Check if form sent a request_id
if (!isset($_POST['request_id'])) {
    die("Error: No request ID received.");
}

// Get nurse ID from session
$nurse_id = $_SESSION['nurse_id'];

// Get request ID from form
$request_id = $_POST['request_id'];

// Insert into the database
$sql = "INSERT INTO request_applications (RequestID, NurseID, ApplicationStatus)
        VALUES (?, ?, 'pending')";

$stmt = $conn->prepare($sql);


if ($stmt) {
    $stmt->bind_param("ii", $request_id, $nurse_id);
    if ($stmt->execute()) {
        header("Location: publicrequests.php"); // Adjust the redirect URL as needed
        exit();
    } else {
        echo "Error inserting request: " . $stmt->error;
    }
    $stmt->close();
} else {
    echo "Error preparing query: " . $conn->error;
}

$conn->close();
?>
