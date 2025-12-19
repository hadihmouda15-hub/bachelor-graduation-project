<?php
session_start();
require_once 'db_connection.php';
// $_SESSION['nurse_id'] = 1; // Example: manually set nurse ID
// $_SESSION['user_type'] = 'nurse';
// $_SESSION['logged_in'] = true;

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

function hasTimeConflict($conn, $nurseId, $newDate, $newTime, $newDuration)
{
    // Main application-based query
    $query = "SELECT r.Date, r.Time, r.Duration 
              FROM request r
              JOIN request_applications ra ON r.RequestID = ra.RequestID
              WHERE ra.NurseID = ? 
                AND ra.ApplicationStatus = 'inprocess' 
                AND r.RequestStatus = 'inprocess'";

    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $nurseId);
    $stmt->execute();
    $result = $stmt->get_result();

    $requests = [];
    while ($row = $result->fetch_assoc()) {
        $requests[] = $row;
    }

    // Private requests directly assigned to the nurse
    $private_query = "SELECT r.Date, r.Time, r.Duration
                      FROM request r
                      WHERE r.NurseID = ? AND r.RequestStatus = 'inprocess'";

    $stmt2 = $conn->prepare($private_query);
    $stmt2->bind_param("i", $nurseId);
    $stmt2->execute();
    $private_result = $stmt2->get_result();

    while ($row = $private_result->fetch_assoc()) {
        $requests[] = $row;
    }

    // Convert new request time to timestamps
    $newStart = strtotime("$newDate $newTime");
    $newEnd = $newStart + ($newDuration * 3600); // Duration in seconds

    // Check for overlap with existing requests
    foreach ($requests as $existing) {
        $existingStart = strtotime($existing['Date'] . ' ' . $existing['Time']);
        $existingEnd = $existingStart + ($existing['Duration'] * 3600);

        if ($newStart < $existingEnd && $newEnd > $existingStart) {
            return true; // Conflict exists
        }
    }

    return false; // No conflict
}




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

                    <!-- private requests tab -->
                    <!-- <div class="tab-pane fade show active" id="private-requests">
                            <div class="btn-toolbar mb-2 mb-md-0">
                                <div class="btn-group me-2">
                                    <button class="btn btn-sm btn-outline-secondary">All</button>
                                    <button class="btn btn-sm btn-outline-secondary">New</button>
                                    <button class="btn btn-sm btn-outline-secondary">Accepted</button>
                                </div>
                            </div>
                        </div> -->

                        <div class="card shadow mb-4">
                            <div class="card-header py-3">
                                <h6 class="m-0 fw-bold text-primary">Requests Sent Directly to You</h6>
                            </div>
                            <div class="card-body p-0">
                                <div class="list-group list-group-flush" style="max-height: 500px; overflow-y: auto;">
                                    <?php
                                    // Database connection
                                    

                                    // Query to get private requests assigned to this nurse
$query = "SELECT r.*, 
                 u.FullName AS PatientFullName,
                 a.Country, a.City, a.Street, a.Building, a.Latitude, a.Longitude, a.Notes AS AddressNotes,
                 GROUP_CONCAT(cn.Name SEPARATOR ', ') AS CareNeeded, 
                 s.Name AS Type
          FROM request r
          LEFT JOIN address a ON r.AddressID = a.AddressID
          LEFT JOIN patient p ON r.PatientID = p.PatientID
          LEFT JOIN user u ON p.UserID = u.UserID
          LEFT JOIN request_care_needed rcn ON r.RequestID = rcn.RequestID
          LEFT JOIN care_needed cn ON rcn.CareID = cn.CareID
          LEFT JOIN service s ON r.Type = s.ServiceID
          WHERE r.ispublic = 0 
            AND r.NurseID = ? 
            AND r.RequestStatus = 'pending'
          GROUP BY r.RequestID
          ORDER BY r.Date DESC";


                                    $stmt = $conn->prepare($query);
                                    $stmt->bind_param("i", $_SESSION['nurse_id']);
                                    $stmt->execute();
                                    $result = $stmt->get_result();

                                    while ($request = $result->fetch_assoc()) {
                                        // Calculate time ago
                                        $postedTime = strtotime($request['Date'] . ' ' . $request['Time']);
                                        $timeAgo = time() - $postedTime;
                                        if ($timeAgo < 60) {
                                            $timeText = "less than a minute ago";
                                        } elseif ($timeAgo < 3600) {
                                            $minutes = floor($timeAgo / 60);
                                            $timeText = "$minutes minute" . ($minutes > 1 ? "s" : "") . " ago";
                                        } elseif ($timeAgo < 86400) {
                                            $hours = floor($timeAgo / 3600);
                                            $timeText = "$hours hour" . ($hours > 1 ? "s" : "") . " ago";
                                        } else {
                                            $days = floor($timeAgo / 86400);
                                            $timeText = "$days day" . ($days > 1 ? "s" : "") . " ago";
                                        }

                                        // Format date and time
                                        $formattedDate = date("M j, Y", strtotime($request['Date']));
                                        $formattedTime = date("g:i A", strtotime($request['Time']));

                                        // Determine if urgent (example logic - you can adjust)
                                        $isUrgent = strtotime($request['Date']) - time() < 86400; // If less than 24 hours away
                                    ?>
                                        <!-- Private Request Item -->
                                        <div class="list-group-item">
                                            <div class="d-flex justify-content-between align-items-start mb-2">
                                                <div>
                                                    <h6 class="mb-1"><?php echo htmlspecialchars($request['Type']); ?>
                                                        <small class="text-muted">(<?php echo htmlspecialchars($request['Type']); ?>)</small>
                                                    </h6>
                                                    <small class="text-muted">
                                                        Requested you specifically • Posted <?php echo $timeText; ?> •
                                                        For <?php echo htmlspecialchars($request['AgeType']); ?> patient
                                                    </small>
                                                </div>
                                                <span class="badge bg-warning">Pending</span>
                                            </div>
                                            <p class="mb-2"><?php echo htmlspecialchars($request['MedicalCondition']); ?></p>
                                            <div class="d-flex justify-content-between align-items-center">
                                                <div>
                                                    <span class="small text-muted">
                                                        <i class="fas fa-map-marker-alt me-1"></i>
                                                        <?php echo htmlspecialchars($request['City'] . ', ' . $request['Street']); ?> •
                                                        3.2 miles away
                                                    </span>
                                                    <span class="small text-muted ms-3">
                                                        <i class="fas fa-calendar me-1"></i>
                                                        <?php echo $formattedDate; ?> at <?php echo $formattedTime; ?>
                                                    </span>
                                                    <span class="small text-muted ms-3">
                                                        <i class="fas fa-clock me-1"></i>
                                                        <?php echo $request['Duration'] ? $request['Duration'] . ' hours duration' : 'Flexible timing'; ?>
                                                    </span>
                                                </div>
                                                <div>
                                                    <button data-bs-toggle="modal" data-bs-target="#requestDetailsModal<?php echo $request['RequestID']; ?>"
                                                        class="btn btn-sm btn-outline-secondary me-2">
                                                        <i class="fas fa-info-circle"></i> View Details
                                                    </button>


                                                    <!-- from here  -->

                                                
                                                    <?php



                                                        $hasConflict = hasTimeConflict($conn, $_SESSION['nurse_id'], $request['Date'], $request['Time'], $request['Duration']);


                                                if ($hasConflict) {
                                                            // Show disabled button that triggers conflict modal
                                                            echo '<button type="button" class="btn btn-sm btn-danger m-0 " data-bs-toggle="modal" data-bs-target="#timeConflictModal' . $request['RequestID'] . '">
            Accept
          </button>';

                                                            // Conflict modal
                                                            echo '
    <div class="modal fade" id="timeConflictModal' . $request['RequestID'] . '" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title">Schedule Conflict</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>You cannot apply for this request because it conflicts with your current schedule:</p>
                    <ul>
                        <li>Date: ' . date("F j, Y", strtotime($request['Date'])) . '</li>
                        <li>Time: ' . date("g:i A", strtotime($request['Time'])) . '</li>
                        <li>Duration: ' . $request['Duration'] . ' hours</li>
                    </ul>
                    <p>Please complete your current assignment before taking new requests at this time.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>';
                                                        } else {
                                               ?>
                                                    <form action="accept_private_request.php" method="post" style="display: inline;">
                                                        <input type="hidden" name="request_id" value="<?php echo $request['RequestID']; ?>">
                                                        <button type="submit" class="btn btn-sm btn-success me-2">
                                                            Accept
                                                        </button>
                                                    </form>

                                                    <?php   }  ?>


                                                    <!-- to here  -->

                                                    

                                                    <button
                                                        class="btn btn-sm btn-outline-danger"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#declineReasonModal"
                                                        data-request-id="<?php echo $request['RequestID']; ?>">
                                                        Decline
                                                    </button>



                                                </div>
                                            </div>
                                        </div>

                                        <!-- Modal for this request -->
                                        <div class="modal fade" id="requestDetailsModal<?php echo $request['RequestID']; ?>" tabindex="-1"
                                            aria-labelledby="requestDetailsModalLabel<?php echo $request['RequestID']; ?>" aria-hidden="true">
                                            <div class="modal-dialog modal-lg">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title" id="requestDetailsModalLabel<?php echo $request['RequestID']; ?>">
                                                            Private Request Details
                                                        </h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <div class="row">
                                                            <div class="col-md-6">
                                                                <h6>Basic Information</h6>
                                                                <ul class="list-unstyled">

                                                                    <li><strong>Patient Name:</strong> <?php echo htmlspecialchars($request['PatientFullName']); ?></li>
                                                                    <li><strong>Type:</strong> <?php echo htmlspecialchars($request['Type']); ?></li>
                                                                    <li><strong>Date & Time:</strong> <?php echo $formattedDate; ?> at <?php echo $formattedTime; ?></li>
                                                                    <li><strong>Duration:</strong> <?php echo $request['Duration'] ? $request['Duration'] . ' hours' : 'Flexible'; ?></li>
                                                                    <li><strong>Nurse Gender Preference:</strong> <?php echo htmlspecialchars($request['NurseGender']); ?></li>
                                                                    <li><strong>Patient Age Type:</strong> <?php echo htmlspecialchars($request['AgeType']); ?></li>
                                                                    <li><strong>Service Fee Percentage:</strong> <?php echo $request['ServiceFeePercentage']; ?>%</li>
                                                                    <li><strong>Care Needed: </strong> <?php echo $request['CareNeeded']; ?></li>
                                                                    <li><?php echo $request['RequestID']; ?></li>

                                                                </ul>
                                                            </div>
                                                            <div class="col-md-6">
                                                                <h6>Location Details</h6>
                                                                <ul class="list-unstyled">
                                                                    <li><strong>Country:</strong> <?php echo htmlspecialchars($request['Country']); ?></li>
                                                                    <li><strong>City:</strong> <?php echo htmlspecialchars($request['City']); ?></li>
                                                                    <li><strong>Street:</strong> <?php echo htmlspecialchars($request['Street']); ?></li>
                                                                    <li><strong>Building:</strong> <?php echo htmlspecialchars($request['Building']); ?></li>
                                                                    <li><strong>Notes:</strong> <?php echo htmlspecialchars($request['AddressNotes']); ?></li>
                                                                </ul>
                                                                <?php if ($request['Latitude'] && $request['Longitude']) { ?>
                                                                    <div class="mt-2">
                                                                        <iframe width="100%" height="200" frameborder="0" style="border:0"
                                                                            src="https://maps.google.com/maps?q=<?php echo $request['Latitude']; ?>,<?php echo $request['Longitude']; ?>&z=15&output=embed">
                                                                        </iframe>
                                                                    </div>
                                                                <?php } ?>
                                                            </div>
                                                        </div>

                                                        <div class="mt-3">
                                                            <h6>Medical Information</h6>
                                                            <p><strong>Medical Condition:</strong> <?php echo htmlspecialchars($request['MedicalCondition']); ?></p>
                                                            <p><strong>Special Instructions:</strong> <?php echo htmlspecialchars($request['SpecialInstructions']); ?></p>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php } ?>

                                    <?php if ($result->num_rows == 0) { ?>
                                        <div class="list-group-item text-center py-4">
                                            <p class="text-muted">No private requests available at this time.</p>
                                        </div>
                                    <?php } ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Decline Reason Modal -->
    <div class="modal fade" id="declineReasonModal" tabindex="-1" aria-labelledby="declineReasonModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="declineReasonModalLabel">Reason for Declining</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="decline_request.php" method="post">
                    <div class="modal-body">
                        <input type="hidden" name="request_id" id="modalRequestId" value="">
                        <div class="mb-3">
                            <label for="declineReason" class="form-label">Reason:</label>
                            <textarea class="form-control" id="declineReason" name="decline_reason" rows="3" required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger">Submit</button>
                    </div>
                </form>
            </div>
        </div>
    </div>


    <?php include "logoutmodal.php" ?>

</body>
<!-- Bootstrap JS Bundle with Popper -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<!-- Custom JS -->
<script src="nurse.js"></script>


<script>
    // This minimal script just sets the request ID in the modal form
    document.addEventListener('DOMContentLoaded', function() {
        var declineReasonModal = document.getElementById('declineReasonModal');
        if (declineReasonModal) {
            declineReasonModal.addEventListener('show.bs.modal', function(event) {
                var button = event.relatedTarget; // Button that triggered the modal
                var requestId = button.getAttribute('data-request-id');
                var modalRequestId = document.getElementById('modalRequestId');
                modalRequestId.value = requestId;
            });
        }
    });
</script>


</html>