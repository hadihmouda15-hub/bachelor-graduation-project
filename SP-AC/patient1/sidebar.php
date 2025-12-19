            <!-- Sidebar -->
<?php
$scoped_user_id = $_SESSION['patient_id']; // scoped variable to avoid conflicts

$sql_nurse_user = "
SELECT user.FullName
FROM user
JOIN patient ON user.UserID = patient.UserID
WHERE patient.PatientID = ? ";

$stmt_nurse_user = $conn->prepare($sql_nurse_user);
$stmt_nurse_user->bind_param("i", $scoped_user_id);
$stmt_nurse_user->execute();
$result_nurse_user = $stmt_nurse_user->get_result();

if ($row_nurse_user = $result_nurse_user->fetch_assoc()) {
    $scoped_user_fullname = $row_nurse_user['FullName'];
} else {
    $scoped_user_fullname = "Unknown Nurse";
}
?>

<div class="col-md-3 col-lg-2 d-md-block sidebar bg-primary collapse position-fixed" id="sidebarMenu">
    <div class="position-sticky pt-3">
        <div class="text-center mb-4">
            <h4 class="text-white">Home Patient</h4>
            <p class="text-white-50 small"><?php echo $scoped_user_fullname ?>
            </p>
        </div>
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link" href="request_service.php">
                    <i class="fas fa-fw fa-plus-circle"></i>
                    Request Service
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="my_requests.php">
                    <i class="fas fa-fw fa-list"></i>
                    My Requests
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="nurses_available.php">
                    <i class="fas fa-fw fa-user-nurse"></i>
                    Nurses Available
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="notifications.php">
                    <i class="fas fa-fw fa-bell"></i>
                    Notifications
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="profile.php">
                    <i class="fas fa-fw fa-user"></i>
                    My Profile
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="report_issues.php">
                    <i class="fas fa-fw fa-flag"></i>
                    Report Issues
                </a>
            </li>
            <li class="nav-item mt-auto">
                <a class="nav-link" href="logout.php" 
                data-bs-toggle="modal" 
                data-bs-target="#logoutConfirmModal">
                    <i class="fas fa-fw fa-sign-out-alt"></i>
                    Logout
                </a>
            </li>
        </ul>
    </div>
</div>