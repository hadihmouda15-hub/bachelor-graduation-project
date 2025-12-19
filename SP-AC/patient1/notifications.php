<?php
require '../connect.php';

session_start();
$patient_id = $_SESSION['patient_id'] ;

if (isset($_POST['confirm_logout'])) {
    // session_destroy();
    unset($_SESSION['email']);
    unset($_SESSION['role']);
    unset($_SESSION['full_name']);
    unset($_SESSION['patient_id']);
    session_destroy();
    header("Location: ../homepage/mainpage.php");
    exit();
}

$sql = "SELECT n.*, u.FullName
        FROM notification n
        JOIN user u ON n.SenderID = u.UserID
        WHERE n.RecipientID = (SELECT UserID FROM patient WHERE PatientID = $patient_id)
        ORDER BY n.Date DESC";
$result = $conn->query($sql);
$notifications = [];
while ($row = $result->fetch_assoc()) {
    $notifications[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home Nursing - Notifications</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/patient.css">
</head>

<body>
    <div class="container-fluid">
        <div class="row">
            <?php include 'sidebar.php'; ?>

            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3">
                    <h2 class="h4 fw-bold">Notifications</h2>
                </div>
                <div class="card shadow">
                    <div class="card-body p-0">
                        <div class="list-group list-group-flush">
                            <?php foreach ($notifications as $notif): ?>
                                <div class="list-group-item">
                                    <div class="d-flex align-items-center">
                                        <div class="me-3 text-<?php echo $notif['Type'] == 'request' ? 'primary' : ($notif['Type'] == 'message' ? 'info' : 'warning'); ?>">
                                            <i class="fas fa-<?php echo $notif['Type'] == 'request' ? 'calendar-check' : ($notif['Type'] == 'message' ? 'comment' : 'exclamation-triangle'); ?> fa-2x"></i>
                                        </div>
                                        <div class="flex-grow-1">
                                            <div class="d-flex justify-content-between">
                                                <h6 class="mb-1"><?php echo htmlspecialchars($notif['Title']); ?></h6>
                                                <small class="text-muted"><?php echo date('M d, H:i', strtotime($notif['Date'])); ?></small>
                                            </div>
                                            <p class="mb-0 small"><?php echo htmlspecialchars($notif['Message']); ?></p>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

        <?php include "logout.php" ?>


    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/patient.js"></script>
</body>
</html>