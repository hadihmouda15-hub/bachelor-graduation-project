            <!-- Sidebar -->
<?php
$scoped_user_id = $_SESSION['nurse_id']; // scoped variable to avoid conflicts

$sql_nurse_user = "
SELECT user.FullName
FROM user
JOIN nurse ON user.UserID = nurse.UserID
WHERE nurse.NurseID =  ? ;
";

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


 
            <div class="col-md-3 col-lg-2 d-md-block sidebar bg-primary collapse position-fixed"  id="sidebarMenu">
                <div class="position-sticky pt-3">
                    <div class="text-center mb-4">
                        <h4 class="text-white">Home Nursing</h4>
                        <p class="text-white-50 small"> <?php echo $scoped_user_fullname  ?></p>
                    </div>

                    <ul class="nav flex-column">
                        <!-- <li class="nav-item">
                            <a class="nav-link" href="Dashboard.php" >
                                <i class="fas fa-fw fa-tachometer-alt"></i>
                                Dashboard
                            </a>
                        </li> -->
                        <li class="nav-item">
                            <a class="nav-link" href="publicrequests.php" >
                                <i class="fas fa-fw fa-bullhorn"></i>
                                Public Requests
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="privaterequests.php" >
                                <i class="fas fa-fw fa-envelope"></i>
                                Private Requests
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="requestsstatus.php" >
                                <i class="fas fa-fw fa-tasks"></i>
                                Request Status
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="MySchedule.php" >
                                <i class="fas fa-fw fa-calendar-alt"></i>
                                My Schedule
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="Notifications.php" >
                                <i class="fas fa-fw fa-bell"></i>
                                Notifications
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="Profile.php" >
                                <i class="fas fa-fw fa-user"></i>
                                My Profile
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="ReportIssues.php" >
                                <i class="fas fa-fw fa-flag"></i>
                                Report Issues
                            </a>
                        </li>
                        <li class="nav-item mt-auto">
                            <a class="nav-link"
                             href="#" id="logoutBtn"
                             data-bs-toggle="modal" 
                             data-bs-target="#logoutConfirmModal">
                                <i class="fas fa-fw fa-sign-out-alt"></i>
                                Logout
                            </a>
                        </li>
                    </ul>
                </div>
            </div>



