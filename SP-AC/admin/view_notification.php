<?php
include '../connect.php';

$admin_id = 21;
$notification_id = isset($_GET['notification_id']) ? (int)$_GET['notification_id'] : 0;

$notification_query = "SELECT NotificationID, Message, Date, Status FROM notification WHERE NotificationID = $notification_id AND RecipientID = $admin_id AND RecipientType = 'admin'";
$notification_result = mysqli_query($conn, $notification_query);
$notification = mysqli_fetch_assoc($notification_result);

if (!$notification) {
    echo "<p>الإشعار غير موجود.</p>";
    exit();
}

// تغيير الحالة إلى Read إذا كانت Unread
if ($notification['Status'] == 'Unread') {
    $update_query = "UPDATE notification SET Status = 'Read' WHERE NotificationID = $notification_id";
    mysqli_query($conn, $update_query);
    $notification['Status'] = 'Read';
}
?>

<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Admin panel to view notification details on Home Care Platform.">
    <title>View Notification - Admin - Home Care</title>
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
            <h2 class="mb-4 animate__animated animate__fadeIn">View Notification</h2>
            <div class="card shadow-sm animate__animated animate__fadeInUp">
                <div class="card-body">
                    <h5 class="card-title">Notification Details</h5>
                    <p><strong>ID:</strong> <?php echo $notification['NotificationID']; ?></p>
                    <p><strong>Message:</strong> <?php echo $notification['Message']; ?></p>
                    <p><strong>Date:</strong> <?php echo date('Y-m-d', strtotime($notification['Date'])); ?></p>
                    <p><strong>Status:</strong> 
                        <span class="badge <?php echo $notification['Status'] == 'Unread' ? 'bg-warning' : 'bg-success'; ?>">
                            <?php echo $notification['Status']; ?>
                        </span>
                    </p>
                    <a href="notifications.php" class="btn btn-primary">Back to Notifications</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>