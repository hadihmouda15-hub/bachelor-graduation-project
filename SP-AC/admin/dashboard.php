<?php
include '../connect.php';

// Get filter parameter
$roleFilter = isset($_GET['role']) ? mysqli_real_escape_string($conn, $_GET['role']) : '';

// Build user query with filter
$query = "SELECT u.UserID, u.FullName, u.Email, u.Role, u.Status 
          FROM user u 
          WHERE 1=1";
if ($roleFilter) {
    $query .= " AND u.Role = '$roleFilter'";
}
$result = mysqli_query($conn, $query);

// Calculate statistics
$totalUsers = mysqli_num_rows(mysqli_query($conn, "SELECT UserID FROM user"));
$patients = mysqli_num_rows(mysqli_query($conn, "SELECT UserID FROM user WHERE Role = 'patient'"));
$nurses = mysqli_num_rows(mysqli_query($conn, "SELECT UserID FROM user WHERE Role = 'nurse'"));
$staff = mysqli_num_rows(mysqli_query($conn, "SELECT UserID FROM user WHERE Role = 'staff' OR Role = 'admin'"));
$pendingRequests = mysqli_num_rows(mysqli_query($conn, "SELECT RequestID FROM request WHERE RequestStatus = 'pending'"));
$inProgressRequests = mysqli_num_rows(mysqli_query($conn, "SELECT RequestID FROM request WHERE RequestStatus = 'inprocess'"));
$completedRequests = mysqli_num_rows(mysqli_query($conn, "SELECT RequestID FROM request WHERE RequestStatus = 'completed'"));
$rejectedRequests = mysqli_num_rows(mysqli_query($conn, "SELECT RequestID FROM request WHERE RequestStatus = 'rejected'"));
$activeSubscriptions = mysqli_num_rows(mysqli_query($conn, "SELECT SID FROM subscribe WHERE Status = 'active'"));
?>

<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Admin dashboard for Home Care Platform.">
    <title>Dashboard - Admin - Home Care</title>
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
            <h2 class="mb-4">Dashboard</h2>

            <!-- User Statistics -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <a href="users.php" class="text-decoration-none">
                        <div class="card shadow-sm">
                            <div class="card-body">
                                <h5 class="card-title">Total Users</h5>
                                <p class="card-text display-6"><?php echo $totalUsers; ?></p>
                            </div>
                        </div>
                    </a>
                </div>
                <div class="col-md-3">
                    <a href="users.php?role=patient" class="text-decoration-none">
                        <div class="card shadow-sm">
                            <div class="card-body">
                                <h5 class="card-title">Patients</h5>
                                <p class="card-text display-6"><?php echo $patients; ?></p>
                            </div>
                        </div>
                    </a>
                </div>
                <div class="col-md-3">
                    <a href="users.php?role=nurse" class="text-decoration-none">
                        <div class="card shadow-sm">
                            <div class="card-body">
                                <h5 class="card-title">Nurses</h5>
                                <p class="card-text display-6"><?php echo $nurses; ?></p>
                            </div>
                        </div>
                    </a>
                </div>
                <div class="col-md-3">
                    <a href="users.php?role=staff" class="text-decoration-none">
                        <div class="card shadow-sm">
                            <div class="card-body">
                                <h5 class="card-title">Staff</h5>
                                <p class="card-text display-6"><?php echo $staff - 1; ?></p>
                            </div>
                        </div>
                    </a>
                </div>
            </div>

            <!-- Request Statistics -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <a href="requests.php?status=pending" class="text-decoration-none">
                        <div class="card shadow-sm">
                            <div class="card-body">
                                <h5 class="card-title">Pending Requests</h5>
                                <p class="card-text display-6"><?php echo $pendingRequests; ?></p>
                            </div>
                        </div>
                    </a>
                </div>
                <div class="col-md-3">
                    <a href="requests.php?status=inprocess" class="text-decoration-none">
                        <div class="card shadow-sm">
                            <div class="card-body">
                                <h5 class="card-title">inprocess Requests</h5>
                                <p class="card-text display-6"><?php echo $inProgressRequests; ?></p>
                            </div>
                        </div>
                    </a>
                </div>
                <div class="col-md-3">
                    <a href="requests.php?status=completed" class="text-decoration-none">
                        <div class="card shadow-sm">
                            <div class="card-body">
                                <h5 class="card-title">Completed Requests</h5>
                                <p class="card-text display-6"><?php echo $completedRequests; ?></p>
                            </div>
                        </div>
                    </a>
                </div>
                <div class="col-md-3">
                    <a href="requests.php?status=rejected" class="text-decoration-none">
                        <div class="card shadow-sm">
                            <div class="card-body">
                                <h5 class="card-title">Rejected Requests</h5>
                                <p class="card-text display-6"><?php echo $rejectedRequests; ?></p>
                            </div>
                        </div>
                    </a>
                </div>
            </div>

            <!-- Subscriptions & Revenue -->
            <div class="row mb-4">
                <div class="col-md-6">
                    <a href="subscriptions.php?status=active" class="text-decoration-none">
                        <div class="card shadow-sm">
                            <div class="card-body">
                                <h5 class="card-title">Active Subscriptions</h5>
                                <p class="card-text display-6"><?php echo $activeSubscriptions; ?></p>
                            </div>
                        </div>
                    </a>
                </div>
            </div>

            

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>