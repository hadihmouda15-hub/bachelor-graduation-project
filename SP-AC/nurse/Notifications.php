<?php 
session_start();
// $_SESSION['nurse_id'] = 1; 
// $_SESSION['user_type'] = 'nurse';
// $_SESSION['logged_in'] = true;

require_once 'db_connection.php';

if (isset($_POST['confirm_logout'])) {
    // session_destroy();
    unset($_SESSION['email']);
    unset($_SESSION['role']);
    unset($_SESSION['full_name']);
    unset($_SESSION['nurse_id']);
    session_destroy();
    header("Location: ../homepage/mainpage.php");
    exit();
}

// Fetch notifications for the current nurse user
$nurseId = $_SESSION['nurse_id'];
$query = "SELECT * FROM notification 
          WHERE RecipientID = ? AND RecipientType = 'nurse'
          ORDER BY Date DESC";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $nurseId);
$stmt->execute();
$result = $stmt->get_result();
$notifications = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home Nursing - Nurse Portal</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="nurse.css">
</head>

<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <?php include "sidebar.php" ?>

            <!-- Main Content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
                <!-- Tab Content -->
                <div class="tab-content">

                    <!-- Notifications Tab -->
                    <div class="tab-pane fade show active" id="notifications">
                        <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3">
                            <h2 class="h4 fw-bold">Notifications</h2>
                            <button class="btn btn-sm btn-outline-secondary">Mark all as read</button>
                        </div>

                        <div class="card shadow">
                            <div class="card-body p-0">
                                <?php if (empty($notifications)): ?>
                                    <div class="text-center py-4">
                                        <i class="fas fa-bell-slash fa-3x text-muted mb-3"></i>
                                        <p class="text-muted">No notifications found</p>
                                    </div>
                                <?php else: ?>
                                    <div class="list-group list-group-flush">
                                        <?php foreach ($notifications as $notification): 
                                            // Determine icon and color based on notification type
                                            $icon = 'fa-bell';
                                            $color = 'text-secondary';
                                            
                                            switch ($notification['Type']) {
                                                case 'report_update':
                                                    $icon = 'fa-file-pen';
                                                    $color = 'text-primary';
                                                    break;
                                                case 'report_resolve':
                                                    $icon = 'fa-check-circle';
                                                    $color = 'text-success';
                                                    break;
                                                case 'accept_certification':
                                                    $icon = 'fa-certificate';
                                                    $color = 'text-warning';
                                                    break;
                                            }
                                            
                                            // Format the date
                                            $date = new DateTime($notification['Date']);
                                            $formattedDate = $date->format('M j, Y g:i A');
                                        ?>
                                        <div class="list-group-item <?= $notification['Status'] === 'Unread' ? 'bg-light' : '' ?>">
                                            <div class="d-flex align-items-center">
                                                <div class="me-3 <?= $color ?>">
                                                    <i class="fas <?= $icon ?> fa-2x"></i>
                                                </div>
                                                <div class="flex-grow-1">
                                                    <div class="d-flex justify-content-between">
                                                        <h6 class="mb-1"><?= htmlspecialchars($notification['Title']) ?></h6>
                                                        <small class="text-muted"><?= $formattedDate ?></small>
                                                    </div>
                                                    <p class="mb-0 small"><?= htmlspecialchars($notification['Message']) ?></p>
                                                </div>
                                            </div>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <?php include "logoutmodal.php" ?>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- Custom JS -->
    <script src="nurse.js"></script>
</body>
</html>