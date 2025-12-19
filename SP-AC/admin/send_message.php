<?php
// Include database connection
include '../connect.php';

// Check if user ID is provided
$user_id = isset($_GET['id']) ? $_GET['id'] : 0;

// Fetch recipient details
$query = "SELECT FullName, Role FROM user WHERE UserID = $user_id";
$result = mysqli_query($conn, $query);
$recipient = mysqli_fetch_assoc($result);

// Map Role to RecipientType
$recipient_type = $recipient['Role'] === 'nurse' ? 'nurse' : ($recipient['Role'] === 'patient' ? 'patient' : 'staff');

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_send'])) {
    $subject = $_POST['subject'];
    $message = $_POST['message'];
    $sender_id = 21; // Assuming admin UserID is 21 (Hadi Hmouda)
    $sender_type = 'admin';
    $type = 'message';
    $status = 'unread';

    $insert_query = "INSERT INTO notification (SenderID, SenderType, RecipientID, RecipientType, Title, Message, Type, Status) 
                     VALUES ($sender_id, '$sender_type', $user_id, '$recipient_type', '$subject', '$message', '$type', '$status')";
    mysqli_query($conn, $insert_query);
    header("location: users.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Admin panel to send messages to users on Home Care Platform.">
    <title>Send Message - Admin - Home Care</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/admin.css">
</head>
<body>
    <div class="d-flex">
        <!-- Sidebar -->
        <?php include 'sidebar.php'; ?>

        <!-- Main Content -->
        <div class="content flex-grow-1 p-4" style="margin-left: 250px;">
            <h2 class="mb-4">Send Message</h2>
            <div class="card shadow-sm">
                <div class="card-body">
                    <form method="POST" id="sendMessageForm">
                        <div class="mb-3">
                            <label for="recipient" class="form-label">Recipient</label>
                            <input type="text" class="form-control" id="recipient" value="<?php echo $recipient['FullName']; ?>" readonly>
                        </div>
                        <div class="mb-3">
                            <label for="subject" class="form-label">Subject</label>
                            <input type="text" class="form-control" id="subject" name="subject" placeholder="Enter subject" required>
                        </div>
                        <div class="mb-3">
                            <label for="message" class="form-label">Message</label>
                            <textarea class="form-control" id="message" name="message" rows="5" placeholder="Enter your message" required></textarea>
                        </div>
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#confirmModal">Send Message</button>
                        <a href="users.php" class="btn btn-secondary ms-2">Cancel</a>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Confirmation Modal -->
    <div class="modal fade" id="confirmModal" tabindex="-1" aria-labelledby="confirmModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="confirmModalLabel">Confirm Send</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    Are you sure you want to send this message?
                </div>
                <div class="modal-footer">
                    <button type="submit" form="sendMessageForm" name="confirm_send" class="btn btn-primary">Yes</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">No</button>
                </div>
            </div>
        </div>
    </div>



    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>