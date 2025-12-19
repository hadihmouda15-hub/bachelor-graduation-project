<?php
include '../connect.php';

$admin_id = 21;

$notifications_query = "SELECT n.NotificationID, n.Message,n.SenderID,u.FullName,n.Title, n.Date, n.Status FROM notification n, user u WHERE RecipientID = $admin_id AND RecipientType = 'admin' AND SenderType = 'staff' AND SenderID = UserID  ORDER BY Date DESC";
$notifications_result = mysqli_query($conn, $notifications_query);
$notifications = mysqli_fetch_all($notifications_result, MYSQLI_ASSOC);

if (isset($_POST['mark_all_read'])) {
    $update_query = "UPDATE notification SET Status = 'Read' WHERE RecipientID = $admin_id AND RecipientType = 'admin' AND Status = 'Unread'";
    mysqli_query($conn, $update_query);
    header("Location: notifications.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Admin panel to manage notifications on Home Care Platform.">
    <title>Notifications - Admin - Home Care</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/admin.css">
</head>
<body>
    <div class="d-flex">
        <?php include 'sidebar.php'; ?>

        <div class="content flex-grow-1 p-4" style="margin-left: 250px;">
            <h2 class="mb-4">Notifications</h2>
            <div class="mb-3">
                <form method="POST">
                    <button type="submit" name="mark_all_read" class="btn btn-primary">Mark All as Read</button>
                </form>
            </div>
            <table class="table table-striped shadow-sm" id="notificationsTable">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Send by</th>
                        <th>Subject</th>
                        <th>Date</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($notifications as $notification): ?>
                        <tr>
                            <td><?php echo $notification['NotificationID']; ?></td>
                            <td><?php echo $notification['FullName']; ?></td>
                            <td><?php echo $notification['Title']; ?></td>
                            <td><?php echo date('Y-m-d', strtotime($notification['Date'])); ?></td>
                            <td>
                                <span class="badge <?php echo $notification['Status'] == 'Unread' ? 'bg-warning' : 'bg-success'; ?>">
                                    <?php echo $notification['Status']; ?>
                                </span>
                            </td>
                            <td>
                                <a href="view_notification.php?notification_id=<?php echo $notification['NotificationID']; ?>" class="btn btn-info btn-sm">View</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>