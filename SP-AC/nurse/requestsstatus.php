<?php
require_once 'db_connection.php';
// Start session and include database connection
session_start();
// $_SESSION['nurse_id'] = 1; 
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

$nurse_id = $_SESSION['nurse_id'];




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




// Function to get formatted address
function getFormattedAddress($address_id, $conn)
{
    if (!$address_id) return "Address not specified";

    $stmt = $conn->prepare("SELECT Country, City, Street, Building FROM address WHERE AddressID = ?");
    $stmt->bind_param("i", $address_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $address = $result->fetch_assoc();
        return $address['Street'] . ', ' . $address['Building'] . ', ' . $address['City'] . ', ' . $address['Country'];
    }
    return "Address not found";
}


function getNurseRequests($nurse_id, $status, $conn)
{
    $requests = [];

    // Case 1: InProcess Requests
    if ($status === 'inprocess') {


        // Private requests (assigned directly to the nurse)
$private_query = "
    SELECT r.*, 
           s.Name AS Type, 
           u.FullName AS PatientFullName
    FROM request r
    JOIN service s ON r.Type = s.ServiceID
    LEFT JOIN patient p ON r.PatientID = p.PatientID
    LEFT JOIN user u ON p.UserID = u.UserID
    WHERE r.NurseID = ? 
      AND r.RequestStatus = 'inprocess'
";

        $stmt = $conn->prepare($private_query);
        $stmt->bind_param("i", $nurse_id);
        $stmt->execute();
        $private_results = $stmt->get_result();
        while ($row = $private_results->fetch_assoc()) {
            $requests[] = $row;
        }

$public_query = "SELECT 
        r.*, 
        u.FullName AS PatientFullName,
        a.Country, a.City, a.Street, a.Building, a.Latitude, a.Longitude, a.Notes AS AddressNotes,
        GROUP_CONCAT(cn.Name SEPARATOR ', ') AS CareNeeded, 
        s.Name AS Type
    FROM request r
    JOIN request_applications ra ON r.RequestID = ra.RequestID
    LEFT JOIN address a ON r.AddressID = a.AddressID
    LEFT JOIN patient p ON r.PatientID = p.PatientID
    LEFT JOIN user u ON p.UserID = u.UserID
    LEFT JOIN request_care_needed rcn ON r.RequestID = rcn.RequestID
    LEFT JOIN care_needed cn ON rcn.CareID = cn.CareID
    LEFT JOIN service s ON r.Type = s.ServiceID
    WHERE ra.NurseID = ? 
      AND ra.ApplicationStatus = 'accepted' 
      AND r.RequestStatus = 'inprocess'
    GROUP BY r.RequestID ";




        $stmt = $conn->prepare($public_query);
        $stmt->bind_param("i", $nurse_id);
        $stmt->execute();
        $public_results = $stmt->get_result();
        while ($row = $public_results->fetch_assoc()) {
            $requests[] = $row;
        }


    }
    // Case 2: Pending Requests
    elseif ($status === 'pending') {
        // Private requests (assigned directly to the nurse)
$private_query = "
    SELECT r.*, 
           s.Name AS Type, 
           u.FullName AS PatientFullName
    FROM request r
    JOIN service s ON r.Type = s.ServiceID
    LEFT JOIN patient p ON r.PatientID = p.PatientID
    LEFT JOIN user u ON p.UserID = u.UserID
    WHERE r.NurseID = ? 
      AND r.RequestStatus = 'pending'
";


        $stmt = $conn->prepare($private_query);
        $stmt->bind_param("i", $nurse_id);
        $stmt->execute();
        $private_results = $stmt->get_result();
        while ($row = $private_results->fetch_assoc()) {
            $requests[] = $row;
        }

        // Public requests (nurse applied but still pending)
        // $public_query = "SELECT r.* FROM request r
        //                  JOIN request_applications ra ON r.RequestID = ra.RequestID
        //                  WHERE ra.NurseID = ? AND ra.ApplicationStatus = 'pending' 
        //                  AND r.RequestStatus = 'pending'";


$public_query = "SELECT r.*, 
                        u.FullName AS PatientFullName,
                        a.Country, a.City, a.Street, a.Building, a.Latitude, a.Longitude, a.Notes AS AddressNotes,
                        GROUP_CONCAT(cn.Name SEPARATOR ', ') AS CareNeeded, 
                        s.Name AS Type
                 FROM request r
                 JOIN request_applications ra ON r.RequestID = ra.RequestID
                 LEFT JOIN address a ON r.AddressID = a.AddressID
                 LEFT JOIN patient p ON r.PatientID = p.PatientID
                 LEFT JOIN user u ON p.UserID = u.UserID
                 LEFT JOIN request_care_needed rcn ON r.RequestID = rcn.RequestID
                 LEFT JOIN care_needed cn ON rcn.CareID = cn.CareID
                 LEFT JOIN service s ON r.Type = s.ServiceID
                 WHERE ra.NurseID = ? 
                   AND ra.ApplicationStatus = 'pending' 
                   AND r.RequestStatus = 'pending'
                 GROUP BY r.RequestID";



        $stmt = $conn->prepare($public_query);
        $stmt->bind_param("i", $nurse_id);
        $stmt->execute();
        $public_results = $stmt->get_result();
        while ($row = $public_results->fetch_assoc()) {
            $requests[] = $row;
        }
    }
    // Case 3: completed Requests
    elseif ($status === 'completed') {
        // Private requests (assigned directly to the nurse)
        $private_query = "SELECT r.*, 
                         s.Name AS Type,
                         u.FullName AS PatientFullName
                  FROM request r
                  JOIN service s ON r.Type = s.ServiceID
                  LEFT JOIN patient p ON r.PatientID = p.PatientID
                  LEFT JOIN user u ON p.UserID = u.UserID
                  WHERE r.NurseID = ? 
                    AND r.RequestStatus = 'completed'";

        $stmt = $conn->prepare($private_query);
        $stmt->bind_param("i", $nurse_id);
        $stmt->execute();
        $private_results = $stmt->get_result();
        while ($row = $private_results->fetch_assoc()) {
            $requests[] = $row;
        }

        // Public requests (nurse applied and completed)
        // $public_query = "SELECT r.* FROM request r
        //                  JOIN request_applications ra ON r.RequestID = ra.RequestID
        //                  WHERE ra.NurseID = ? AND ra.ApplicationStatus = 'accepted' 
        //                  AND r.RequestStatus = 'completed'";

//         $public_query = "
//     SELECT 
//         r.*, 
//         a.Country, a.City, a.Street, a.Building, a.Latitude, a.Longitude, a.Notes AS AddressNotes,
//         GROUP_CONCAT(cn.Name SEPARATOR ', ') AS CareNeeded
//     FROM request r
//     JOIN request_applications ra ON r.RequestID = ra.RequestID
//     LEFT JOIN address a ON r.AddressID = a.AddressID
//     LEFT JOIN request_care_needed rcn ON r.RequestID = rcn.RequestID
//     LEFT JOIN care_needed cn ON rcn.CareID = cn.CareID
//     WHERE ra.NurseID = ? 
//       AND ra.ApplicationStatus = 'accepted' 
//       AND r.RequestStatus = 'completed'
//     GROUP BY r.RequestID
// ";

$public_query = "
    SELECT 
        r.*, 
        u.FullName AS PatientFullName,
        a.Country, a.City, a.Street, a.Building, a.Latitude, a.Longitude, a.Notes AS AddressNotes,
        GROUP_CONCAT(cn.Name SEPARATOR ', ') AS CareNeeded, 
        s.Name AS Type
    FROM request r
    JOIN request_applications ra 
        ON r.RequestID = ra.RequestID 
        AND ra.NurseID = ? 
        AND ra.ApplicationStatus = 'accepted'
    LEFT JOIN address a ON r.AddressID = a.AddressID
    LEFT JOIN request_care_needed rcn ON r.RequestID = rcn.RequestID
    LEFT JOIN care_needed cn ON rcn.CareID = cn.CareID
    LEFT JOIN service s ON r.Type = s.ServiceID
    LEFT JOIN patient p ON r.PatientID = p.PatientID
    LEFT JOIN user u ON p.UserID = u.UserID
    WHERE r.RequestStatus = 'completed'
    GROUP BY r.RequestID
";




        $stmt = $conn->prepare($public_query);
        $stmt->bind_param("i", $nurse_id);
        $stmt->execute();
        $public_results = $stmt->get_result();
        while ($row = $public_results->fetch_assoc()) {
            $requests[] = $row;
        }
    }

    return $requests;
}


// Get requests for each tab
$current_work = getNurseRequests($nurse_id, 'inprocess', $conn);
$waiting_requests = getNurseRequests($nurse_id, 'pending', $conn);
$completed_requests = getNurseRequests($nurse_id, 'completed', $conn);

// Count for badges
$waiting_count = count($waiting_requests);
$completed_count = count($completed_requests);
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
    <style>
        .star-rating {
            font-size: 24px;
            display: inline-block;
        }

        .star {
            color: #ddd;
            cursor: pointer;
        }

        .star.filled {
            color: #ffc107;
            /* Bootstrap's warning color for yellow stars */
        }
    </style>
</head>

<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <?php include "sidebar.php" ?>

            <!-- Main Content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
                <!-- Tab Navigation -->
                <ul class="nav nav-tabs mb-4" id="statusTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="all-tab" data-bs-toggle="tab" data-bs-target="#all" type="button" role="tab">
                            Current Work
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="waiting-tab" data-bs-toggle="tab" data-bs-target="#waiting" type="button" role="tab">
                            Waiting <span class="badge bg-warning ms-1"><?php echo $waiting_count; ?></span>
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="completed-tab" data-bs-toggle="tab" data-bs-target="#completed" type="button" role="tab">
                            Completed <span class="badge bg-success ms-1"><?php echo $completed_count; ?></span>
                        </button>

                    </li>
                </ul>

                <!-- Tab Content -->
                <div class="tab-content" id="statusTabsContent">
                    <!-- Current Work Tab -->
                    <div class="tab-pane fade show active" id="all" role="tabpanel">
                        <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3">
                            <h2 class="h4 fw-bold">Current Work</h2>
                            <div class="btn-toolbar mb-2 mb-md-0">
                                <div class="btn-group me-2">
                                    <button class="btn btn-sm btn-outline-secondary"><i class="fas fa-sync-alt"></i></button>
                                </div>
                            </div>
                        </div>

                        <!-- Table for Current Work -->
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>Request ID</th>
                                        <th>Service Type</th>
                                        <th>Address</th>
                                        <th>Date/Time</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($current_work as $request): ?>
                                        <tr data-status="inprocess">
                                            <td>#REQ-<?php echo $request['RequestID']; ?></td>
                                            <td><?php echo htmlspecialchars($request['Type']); ?></td>
                                            <td><?php echo getFormattedAddress($request['AddressID'], $conn); ?></td>
                                            <td><?php echo date('M j, Y, g:i A', strtotime($request['Date'] . ' ' . $request['Time'])); ?></td>
                                            <td>
                                                <!-- <span class="badge bg-primary">In Progress</span> -->


                                                <form action="completed_request.php" method="post" style="display: inline;">
                                                    <input type="hidden" name="request_id" value="<?php echo $request['RequestID']; ?>">
                                                    <button type="submit" class="me-1 btn btn-sm btn-primary">completed</button>
                                                </form>
                                                <button data-bs-toggle="modal" data-bs-target="#CurrentDetailsModal<?php echo $request['RequestID']; ?>"
                                                    class="btn btn-sm btn-outline-secondary">
                                                    <i class="fas fa-info-circle"></i> View Details
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                    <?php if (empty($current_work)): ?>
                                        <tr>
                                            <td colspan="5" class="text-center">No current work found</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>


                    <!-- test -->
                    <?php foreach ($current_work as $request): ?>
                        <?php
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

                        <!-- request details modal -->
                        <div class="modal fade" id="CurrentDetailsModal<?php echo $request['RequestID']; ?>" tabindex="-1"
                            aria-labelledby="CurrentDetailsModalLabel<?php echo $request['RequestID']; ?>" aria-hidden="true">
                            <div class="modal-dialog modal-lg">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="CurrentDetailsModalLabel<?php echo $request['RequestID']; ?>">
                                            Request Details
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
                                                    <li><strong>Number of Nurses Needed:</strong> <?php echo $request['NumberOfNurses']; ?></li>
                                                    <li><strong>Service Fee Percentage:</strong> <?php echo $request['ServiceFeePercentage']; ?>%</li>
                                                    <li><strong>Care Needed:</strong> <?php echo $request['CareNeeded'] ?? 'Not specified'; ?>
                                                    </li>
                                                </ul>
                                            </div>
                                            <div class="col-md-6">
                                                <h6>Location Details</h6>
                                                <ul class="list-unstyled">
                                                    <li><strong>Country:</strong> <?php echo htmlspecialchars($request['Country'] ?? 'Not specified'); ?></li>
                                                    <li><strong>City:</strong> <?php echo htmlspecialchars($request['City'] ?? 'Not specified'); ?></li>
                                                    <li><strong>Street:</strong> <?php echo htmlspecialchars($request['Street'] ?? 'Not specified'); ?></li>
                                                    <li><strong>Building:</strong> <?php echo htmlspecialchars($request['Building'] ?? 'Not specified'); ?></li>
                                                    <li><strong>Notes:</strong> <?php echo htmlspecialchars($request['AddressNotes'] ?? 'None'); ?></li>
                                                </ul>

                                                <?php if (!empty($request['Latitude']) && !empty($request['Longitude'])) { ?>
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
                                        <form action="accept_request.php" method="post">
                                            <input type="hidden" name="request_id" value="<?php echo $request['RequestID']; ?>">
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <!-- test -->











                    <!-- Waiting Tab -->
                    <div class="tab-pane fade" id="waiting" role="tabpanel">
                        <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3">
                            <h2 class="h4 fw-bold">Waiting Requests</h2>
                        </div>

                        <!-- Table for Waiting Requests -->
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>Request ID</th>
                                        <th>Service Type</th>
                                        <th>Address</th>
                                        <th>Date/Time</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($waiting_requests as $request): ?>
                                        <tr data-status="pending">
                                            <td>#REQ-<?php echo $request['RequestID']; ?></td>
                                            <td><?php echo htmlspecialchars($request['Type']); ?></td>
                                            <td><?php echo getFormattedAddress($request['AddressID'], $conn); ?></td>
                                            <td><?php echo date('M j, Y, g:i A', strtotime($request['Date'] . ' ' . $request['Time'])); ?></td>
                                            <td>
                                                <?php if ($request['ispublic'] == 1): ?>
                                                    <span class="badge bg-warning">Pending</span>
                                                <?php else: ?>
                                                    <span class="badge bg-info">Waiting Patient</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($request['ispublic'] == 0): ?>
                                                    <!-- heree -->
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
                                                        <input type="hidden" name="source_page" value="<?php echo basename($_SERVER['PHP_SELF']); ?>">
                                                        <button type="submit" class="btn btn-sm btn-success me-2">
                                                            Accept
                                                        </button>
                                                    </form>

                                                    <?php   }  ?>

                                                    <button
                                                        class="btn btn-sm btn-outline-danger"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#declineReasonModal"
                                                        data-request-id="<?php echo $request['RequestID']; ?>">
                                                        Decline
                                                    </button>
                                                <?php else: ?>
                                                    <button data-bs-toggle="modal" data-bs-target="#requestDetailsModal<?php echo $request['RequestID']; ?>"
                                                        class="btn btn-sm btn-outline-secondary me-2">
                                                        <i class="fas fa-info-circle"></i> View Details
                                                    </button>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                    <?php if (empty($waiting_requests)): ?>
                                        <tr>
                                            <td colspan="6" class="text-center">No waiting requests found</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- test -->
                    <?php foreach ($waiting_requests as $request): ?>
                        <?php
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

                        <!-- request details modal -->
                        <div class="modal fade" id="requestDetailsModal<?php echo $request['RequestID']; ?>" tabindex="-1"
                            aria-labelledby="requestDetailsModalLabel<?php echo $request['RequestID']; ?>" aria-hidden="true">
                            <div class="modal-dialog modal-lg">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="requestDetailsModalLabel<?php echo $request['RequestID']; ?>">
                                            Request Details
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
                                                    <li><strong>Number of Nurses Needed:</strong> <?php echo $request['NumberOfNurses']; ?></li>
                                                    <li><strong>Service Fee Percentage:</strong> <?php echo $request['ServiceFeePercentage']; ?>%</li>
                                                    <li><strong>Care Needed:</strong> <?php echo $request['CareNeeded'] ?? 'Not specified'; ?>
                                                    </li>
                                                </ul>
                                            </div>
                                            <div class="col-md-6">
                                                <h6>Location Details</h6>
                                                <ul class="list-unstyled">
                                                    <li><strong>Country:</strong> <?php echo htmlspecialchars($request['Country'] ?? 'Not specified'); ?></li>
                                                    <li><strong>City:</strong> <?php echo htmlspecialchars($request['City'] ?? 'Not specified'); ?></li>
                                                    <li><strong>Street:</strong> <?php echo htmlspecialchars($request['Street'] ?? 'Not specified'); ?></li>
                                                    <li><strong>Building:</strong> <?php echo htmlspecialchars($request['Building'] ?? 'Not specified'); ?></li>
                                                    <li><strong>Notes:</strong> <?php echo htmlspecialchars($request['AddressNotes'] ?? 'None'); ?></li>
                                                </ul>

                                                <?php if (!empty($request['Latitude']) && !empty($request['Longitude'])) { ?>
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
                                        <form action="accept_request.php" method="post">
                                            <input type="hidden" name="request_id" value="<?php echo $request['RequestID']; ?>">
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <!-- test -->







                    <!-- Completed Tab -->
                    <div class="tab-pane fade" id="completed" role="tabpanel">
                        <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3">
                            <h2 class="h4 fw-bold">Completed Requests</h2>
                            <div class="btn-toolbar mb-2 mb-md-0">
                                <div class="btn-group me-2">
                                </div>
                            </div>
                        </div>

                        <!-- Table for Completed Requests -->
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>Request ID</th>
                                        <th>Service Type</th>
                                        <th>Address</th>
                                        <th>Date Completed</th>
                                        <th>Rating</th>
                                        <th>Details</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($completed_requests as $request): ?>
                                        <tr>
                                            <td>#REQ-<?php echo $request['RequestID']; ?></td>
                                            <td><?php echo htmlspecialchars($request['Type']); ?></td>
                                            <td><?php echo getFormattedAddress($request['AddressID'], $conn); ?></td>
                                            <td><?php echo date('M j, Y', strtotime($request['Date'])); ?></td>
                                            <td>
                                                <button data-bs-toggle="modal"
                                                    data-bs-target="#rate<?php echo $request['RequestID']; ?>" class="btn btn-sm btn-outline-success">View</button>
                                            </td>
                                            <td>
                                                <button data-bs-toggle="modal" data-bs-target="#CompletedDetailsModal<?php echo $request['RequestID']; ?>"
                                                    class="btn btn-sm btn-outline-secondary">
                                                    <i class="fas fa-info-circle"></i> View Details
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                    <?php if (empty($completed_requests)): ?>
                                        <tr>
                                            <td colspan="6" class="text-center">No completed requests found</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>




    <!-- test -->
    <?php foreach ($completed_requests as $request): ?>
        <?php
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

        <!-- request details modal -->
        <div class="modal fade" id="CompletedDetailsModal<?php echo $request['RequestID']; ?>" tabindex="-1"
            aria-labelledby="CompletedDetailsModalLabel<?php echo $request['RequestID']; ?>" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="CompletedDetailsModalLabel<?php echo $request['RequestID']; ?>">
                            Request Details
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
                                    <li><strong>Number of Nurses Needed:</strong> <?php echo $request['NumberOfNurses']; ?></li>
                                    <li><strong>Service Fee Percentage:</strong> <?php echo $request['ServiceFeePercentage']; ?>%</li>
                                    <li><strong>Care Needed:</strong> <?php echo $request['CareNeeded'] ?? 'Not specified'; ?>
                                    </li>
                                </ul>
                            </div>
                            <div class="col-md-6">
                                <h6>Location Details</h6>
                                <ul class="list-unstyled">
                                    <li><strong>Country:</strong> <?php echo htmlspecialchars($request['Country'] ?? 'Not specified'); ?></li>
                                    <li><strong>City:</strong> <?php echo htmlspecialchars($request['City'] ?? 'Not specified'); ?></li>
                                    <li><strong>Street:</strong> <?php echo htmlspecialchars($request['Street'] ?? 'Not specified'); ?></li>
                                    <li><strong>Building:</strong> <?php echo htmlspecialchars($request['Building'] ?? 'Not specified'); ?></li>
                                    <li><strong>Notes:</strong> <?php echo htmlspecialchars($request['AddressNotes'] ?? 'None'); ?></li>
                                </ul>

                                <?php if (!empty($request['Latitude']) && !empty($request['Longitude'])) { ?>
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
                        <form action="accept_request.php" method="post">
                            <input type="hidden" name="request_id" value="<?php echo $request['RequestID']; ?>">
                        </form>
                    </div>
                </div>
            </div>
        </div>



        <!-- Modal for each request -->
        <div class="modal fade" id="rate<?php echo $request['RequestID']; ?>" tabindex="-1" aria-labelledby="ratingModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="ratingModalLabel">Rating Details</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <?php
                        // Fetch the rating for this request
                        $ratingQuery = "SELECT * FROM rating WHERE RequestID = ? AND NurseID = ?";
                        $stmt = $conn->prepare($ratingQuery);
                        $stmt->bind_param("ii", $request['RequestID'], $nurse_id);
                        $stmt->execute();
                        $ratingResult = $stmt->get_result();
                        $rating = $ratingResult->fetch_assoc();

                        if ($rating) {
                        ?>
                            <div class="rating-container mb-3">
                                <h6>Rating:</h6>
                                <div class="star-rating">
                                    <?php
                                    // Display filled and empty stars based on rating
                                    for ($i = 1; $i <= 5; $i++) {
                                        if ($i <= $rating['Rating']) {
                                            echo '<span class="star filled">★</span>';
                                        } else {
                                            echo '<span class="star">★</span>';
                                        }
                                    }
                                    ?>
                                </div>
                                <p class="mt-2">Rating: <?php echo $rating['Rating']; ?>/5</p>
                            </div>

                            <div class="mb-3">
                                <h6>Feedback:</h6>
                                <p><?php echo htmlspecialchars($rating['Description'] ?? 'No feedback provided'); ?></p>
                            </div>
                        <?php
                        } else {
                            echo '<p>No rating available for this request.</p>';
                        }
                        ?>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>

    <?php endforeach; ?>
    <!-- test -->


    <?php include "logoutmodal.php" ?>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- Custom JS -->
    <script src="nurse.js"></script>
</body>

</html>