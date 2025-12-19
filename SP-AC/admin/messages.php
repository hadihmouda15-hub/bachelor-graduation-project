<?php
include '../connect.php';

$admin_id = 21;

$sent_query = "
    SELECT n.NotificationID, n.Title, n.Message, n.Date, n.RecipientType, n.RecipientID, 
           MIN(u.FullName) AS RecipientName
    FROM notification n
    LEFT JOIN user u ON n.RecipientID = u.UserID AND n.RecipientType = 'specific'
    WHERE n.SenderType = 'admin' AND n.SenderID = $admin_id
    GROUP BY n.Title, n.Message, n.RecipientType
    ORDER BY MAX(n.Date) DESC
";
$sent_result = mysqli_query($conn, $sent_query);
$sent_messages = mysqli_fetch_all($sent_result, MYSQLI_ASSOC);

$users_query = "SELECT UserID, FullName, Email, Role FROM user WHERE Role IN ('staff', 'nurse', 'patient')";
$users_result = mysqli_query($conn, $users_query);
$users = mysqli_fetch_all($users_result, MYSQLI_ASSOC);

if (isset($_POST['send_message'])) {
    $recipient_type = $_POST['recipient_type'];
    $recipient_id = isset($_POST['specific_user']) ? (int)$_POST['specific_user'] : 0;
    $subject = $_POST['subject'];
    $message = $_POST['message'];
    $sender_type = 'admin';
    $type = 'message';
    $status = 'unread';

    if (in_array($recipient_type, ['staff', 'nurse', 'patient'])) {
        $group_query = "SELECT UserID FROM user WHERE Role = '$recipient_type'";
        $group_result = mysqli_query($conn, $group_query);
        $user_count = mysqli_num_rows($group_result); // عدد المستخدمين في المجموعة
        echo "<p>تم العثور على $user_count مستخدمين بدور $recipient_type</p>"; // رسالة تصحيح
        while ($user = mysqli_fetch_assoc($group_result)) {
            $insert_query = "
                INSERT INTO notification (SenderID, SenderType, RecipientID, RecipientType, Title, Message, Type, Status, Date)
                VALUES ($admin_id, '$sender_type', {$user['UserID']}, '$recipient_type', '$subject', '$message', '$type', '$status', NOW())
            ";
            mysqli_query($conn, $insert_query);
        }
    } elseif ($recipient_type == 'specific' && $recipient_id) {
        $user_query = "SELECT Role FROM user WHERE UserID = $recipient_id";
        $user_result = mysqli_query($conn, $user_query);
        $user = mysqli_fetch_assoc($user_result);
        $insert_query = "
            INSERT INTO notification (SenderID, SenderType, RecipientID, RecipientType, Title, Message, Type, Status, Date)
            VALUES ($admin_id, '$sender_type', $recipient_id, 'specific', '$subject', '$message', '$type', '$status', NOW())
        ";
        mysqli_query($conn, $insert_query);
    }

    header("Location: messages.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Admin panel to send messages on Home Care Platform.">
    <title>Messages - Admin - Home Care</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/admin.css">
</head>
<body>
    <div class="d-flex">
        <?php include 'sidebar.php'; ?>

        <div class="content flex-grow-1 p-4" style="margin-left: 250px;">
            <h2 class="mb-4">Messages</h2>
            <ul class="nav nav-tabs mb-3" id="messageTabs" role="tablist">
                <li class="nav-item">
                    <a class="nav-link active" id="sent-tab" data-bs-toggle="tab" href="#sent" role="tab">Sent</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="send-tab" data-bs-toggle="tab" href="#send" role="tab">Send Message</a>
                </li>
            </ul>
            <div class="tab-content" id="messageTabContent">
                <div class="tab-pane fade show active" id="sent" role="tabpanel">
                    <table class="table table-striped shadow-sm">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Recipient</th>
                                <th>Subject</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($sent_messages as $msg): ?>
                                <tr>
                                    <td><?php echo $msg['NotificationID']; ?></td>
                                    <td>
                                        <?php
                                        if ($msg['RecipientType'] == 'specific') {
                                            echo $msg['RecipientName'];
                                        } else {
                                            echo 'All ' . ucfirst($msg['RecipientType']) . 's';
                                        }
                                        ?>
                                    </td>
                                    <td><?php echo $msg['Title']; ?></td>
                                    <td><?php echo date('Y-m-d', strtotime($msg['Date'])); ?></td>
                                    <td>
                                        <button class="btn btn-info btn-sm" data-bs-toggle="modal" data-bs-target="#viewSentModal<?php echo $msg['NotificationID']; ?>">View</button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="tab-pane fade" id="send" role="tabpanel">
                    <div class="card shadow-sm">
                        <div class="card-body">
                            <form method="POST" id="sendMessageForm">
                                <div class="mb-3">
                                    <label for="recipient_type" class="form-label">Recipient</label>
                                    <select class="form-select" id="recipient_type" name="recipient_type">
                                        <option value="">Select Recipient</option>
                                        <option value="staff">All Staff</option>
                                        <option value="nurse">All Nurses</option>
                                        <option value="patient">All Patients</option>
                                        <option value="specific">Specific User</option>
                                    </select>
                                </div>
                                <div class="mb-3" id="specificUser" style="display: none;">
                                    <label for="specific_user" class="form-label">Select User</label>
                                    <select class="form-select" id="specific_user" name="specific_user">
                                        <option value="">Select a user</option>
                                        <?php foreach ($users as $user): ?>
                                            <option value="<?php echo $user['UserID']; ?>">
                                                <?php echo $user['FullName'] . ' (' . $user['Email'] . ') - ' . ucfirst($user['Role']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label for="subject" class="form-label">Subject</label>
                                    <input type="text" class="form-control" id="subject" name="subject" placeholder="Enter subject">
                                </div>
                                <div class="mb-3">
                                    <label for="message" class="form-label">Message</label>
                                    <textarea class="form-control" id="message" name="message" rows="3" placeholder="Enter your message"></textarea>
                                </div>
                                <button type="submit" name="send_message" class="btn btn-primary">Send Message</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php foreach ($sent_messages as $msg): ?>
        <div class="modal fade" id="viewSentModal<?php echo $msg['NotificationID']; ?>" tabindex="-1" aria-labelledby="viewSentModalLabel<?php echo $msg['NotificationID']; ?>" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="viewSentModalLabel<?php echo $msg['NotificationID']; ?>">Message #<?php echo $msg['NotificationID']; ?></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p><strong>Recipient:</strong> 
                            <?php 
                            if ($msg['RecipientType'] == 'specific') {
                                echo $msg['RecipientName'];
                            } else {
                                echo 'All ' . ucfirst($msg['RecipientType']) . 's';
                            }
                            ?>
                        </p>
                        <p><strong>Subject:</strong> <?php echo $msg['Title']; ?></p>
                        <p><strong>Message:</strong> <?php echo $msg['Message']; ?></p>
                        <p><strong>Date:</strong> <?php echo date('Y-m-d', strtotime($msg['Date'])); ?></p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.getElementById('recipient_type').addEventListener('change', function() {
            document.getElementById('specificUser').style.display = this.value === 'specific' ? 'block' : 'none';
        });
    </script>
</body>
</html>