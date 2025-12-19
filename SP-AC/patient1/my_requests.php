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


// Automatically reject pending requests with past dates
$current_date = date('Y-m-d');
$sql = "UPDATE request 
        SET RequestStatus = 'rejected', 
            SpecialInstructions = CONCAT(IFNULL(SpecialInstructions, ''), ' Rejection reason: This request has been automatically rejected as the scheduled date has passed without confirmation.')
        WHERE RequestStatus = 'pending' 
        AND Date < '$current_date' 
        AND PatientID = $patient_id";
$conn->query($sql);

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Accept request (for private requests)
    if (isset($_POST['accept_request'])) {
        $request_id = (int)$_POST['request_id'];
        $sql = "UPDATE request SET RequestStatus = 'inprocess', PatientStatus = 'inprocess' 
                WHERE RequestID = $request_id AND PatientID = $patient_id";
        $conn->query($sql);
    }

    // Reject request with declinereason
    if (isset($_POST['reject_request'])) {
        $request_id = (int)$_POST['request_id'];
        $declinereason = $conn->real_escape_string($_POST['rejection_declinereason']);

        $sql = "UPDATE request SET RequestStatus = 'rejected', PatientStatus = 'rejected', 
                SpecialInstructions = CONCAT(IFNULL(SpecialInstructions,''), ' Rejection reason: ', '$declinereason')
                WHERE RequestID = $request_id AND PatientID = $patient_id";
        $conn->query($sql);
    }

    // Submit rating
    if (isset($_POST['submit_rating'])) {
        $request_id = (int)$_POST['request_id'];
        $nurse_id = (int)$_POST['nurse_id'];
        $rating = (int)$_POST['rating'];
        $comment = $conn->real_escape_string($_POST['rating_comment']);

        if ($rating >= 1 && $rating <= 5) {
            $sql = "INSERT INTO rating (RequestID, Rating, Description, PatientID, NurseID) 
                    VALUES ($request_id, $rating, " . ($comment ? "'$comment'" : 'NULL') . ", $patient_id, $nurse_id)";
            $conn->query($sql);
        }
    }

    // Cancel request
    if (isset($_POST['cancel_request'])) {
        $request_id = (int)$_POST['request_id'];
        $sql = "UPDATE request SET RequestStatus = 'rejected', PatientStatus = 'rejected' 
                WHERE RequestID = $request_id AND PatientID = $patient_id AND RequestStatus = 'pending'";
        $conn->query($sql);
    }

    // Redirect to refresh the page
    header("Location: my_requests.php?filter=" . (isset($_GET['filter']) ? $_GET['filter'] : 'all'));
    exit();
}

// Get filter from URL
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';

// Build SQL with filter condition
$filter_condition = '';
if ($filter == 'pending') {
    $filter_condition = " AND r.RequestStatus = 'pending'";
} elseif ($filter == 'in_progress') {
    $filter_condition = " AND r.RequestStatus = 'inprocess'";
} elseif ($filter == 'rejected') {
    $filter_condition = " AND r.RequestStatus = 'rejected'";
} elseif ($filter == 'completed') {
    $filter_condition = " AND r.RequestStatus = 'completed'";
}

$sql = "SELECT DISTINCT r.RequestID, r.AgeType, r.Date, r.Time, s.Name AS Type, 
       r.NumberOfNurses, r.SpecialInstructions, r.MedicalCondition, r.Duration, 
       r.NurseStatus, r.PatientStatus, r.RequestStatus, r.ServiceFeePercentage, 
       r.NurseID, r.PatientID, r.ispublic, r.declinereason, 
       u.FullName AS NurseName, a.City, a.Street, a.Building,
       (SELECT COUNT(*) FROM request_applications ra WHERE ra.RequestID = r.RequestID) AS ApplicationCount,
       (SELECT COUNT(*) FROM request_applications ra WHERE ra.RequestID = r.RequestID AND ra.ApplicationStatus = 'accepted') AS AcceptedApplicationCount
FROM request r
LEFT JOIN nurse n ON r.NurseID = n.NurseID
LEFT JOIN user u ON n.UserID = u.UserID
LEFT JOIN address a ON r.AddressID = a.AddressID
LEFT JOIN service s ON r.Type = s.ServiceID
WHERE r.PatientID = $patient_id $filter_condition
ORDER BY r.Date DESC, r.Time DESC";

$result = $conn->query($sql);
$requests = [];
$request_ids = [];
while ($row = $result->fetch_assoc()) {
    if (!in_array($row['RequestID'], $request_ids)) {
        $requests[] = $row;
        $request_ids[] = $row['RequestID'];
    }
}

// Fetch existing ratings
$ratings_result = $conn->query("SELECT RequestID, NurseID, Rating, Description FROM rating WHERE PatientID = $patient_id");
$ratings = [];
while ($row = $ratings_result->fetch_assoc()) {
    $ratings[$row['RequestID'] . '_' . $row['NurseID']] = $row;
}

// Fetch nurses for each in progress or completed request
$nurses_per_request = [];
foreach ($requests as $request) {
    if ($request['RequestStatus'] == 'in progress' || $request['RequestStatus'] == 'completed') {
        $request_id = $request['RequestID'];
        $nurses = [];

        // Private request: get nurse from request.NurseID
        if ($request['NurseID'] && $request['ispublic'] == 0) {
            // $sql = "SELECT n.NurseID, u.FullName AS NurseName, n.Bio AS NurseBio, 
            //                na.Specialization, na.Gender AS NurseGender, na.Language
            //         FROM nurse n
            //         JOIN user u ON n.UserID = u.UserID
            //         JOIN nurseapplication na ON n.NAID = na.NAID
            //         WHERE n.NurseID = " . (int)$request['NurseID'];

            $sql = "SELECT n.NurseID, u.FullName AS NurseName, n.Bio AS NurseBio, 
               na.Specialization, na.Gender AS NurseGender, na.Language, n.image_path
        FROM nurse n
        JOIN user u ON n.UserID = u.UserID
        JOIN nurseapplication na ON n.NAID = na.NAID
        WHERE n.NurseID = " . (int)$request['NurseID'];


            $result = $conn->query($sql);
            if ($row = $result->fetch_assoc()) {
                $nurses[] = $row;
            }
        }

        // Public request: get accepted nurses from request_applications
        if ($request['ispublic'] == 1) {
            // $sql = "SELECT ra.NurseID, u.FullName AS NurseName, n.Bio AS NurseBio, 
            //                na.Specialization, na.Gender AS NurseGender, na.Language
            //         FROM request_applications ra
            //         JOIN nurse n ON ra.NurseID = n.NurseID
            //         JOIN user u ON n.UserID = u.UserID
            //         JOIN nurseapplication na ON n.NAID = na.NAID
            //         WHERE ra.RequestID = $request_id AND ra.ApplicationStatus = 'accepted'";

            $sql = "SELECT ra.NurseID, u.FullName AS NurseName, n.Bio AS NurseBio, 
               na.Specialization, na.Gender AS NurseGender, na.Language, ra.ApplicationStatus, n.image_path
        FROM request_applications ra
        JOIN nurse n ON ra.NurseID = n.NurseID
        JOIN user u ON n.UserID = u.UserID
        JOIN nurseapplication na ON n.NAID = na.NAID
        WHERE ra.RequestID = $request_id";


            $result = $conn->query($sql);
            while ($row = $result->fetch_assoc()) {
                $nurses[] = $row;
            }
        }

        $nurses_per_request[$request_id] = $nurses;
    }
}

// Fetch applicants for each public request with applications
$applicants_per_request = [];
foreach ($requests as $request) {
    if ($request['ispublic'] == 1 && $request['ApplicationCount'] > 0) {
        $request_id = $request['RequestID'];
        $sql = "SELECT ra.NurseID, u.FullName AS NurseName, n.Bio AS NurseBio, 
                       na.Specialization, na.Gender AS NurseGender, na.Language, ra.ApplicationStatus , n.image_path
                FROM request_applications ra
                JOIN nurse n ON ra.NurseID = n.NurseID
                JOIN user u ON n.UserID = u.UserID
                JOIN nurseapplication na ON n.NAID = na.NAID
                WHERE ra.RequestID = $request_id";
        $result = $conn->query($sql);
        $applicants_per_request[$request_id] = $result->fetch_all(MYSQLI_ASSOC);
    }
}

// Update private request status based on NurseStatus before fetching requests
$sql = "UPDATE request r
        SET r.RequestStatus = 'inprocess'
        WHERE r.ispublic = 0 AND r.NurseID IS NOT NULL 
        AND r.NurseStatus = 'inprocess' AND r.RequestStatus != 'inprocess'
        AND r.PatientID = $patient_id";
$conn->query($sql);

$sql = "UPDATE request r
        SET r.RequestStatus = 'completed', r.PatientStatus = 'completed'
        WHERE r.ispublic = 0 AND r.NurseID IS NOT NULL 
        AND r.NurseStatus = 'completed' AND r.RequestStatus != 'completed'
        AND r.PatientID = $patient_id";
$conn->query($sql);


?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Requests</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/patient.css">
    <style>
        .badge {
            font-size: 0.85em;
            padding: 5px 10px;
        }

        .bg-warning {
            background-color: #ffc107 !important;
        }

        .bg-success {
            background-color: #198754 !important;
        }

        .bg-danger {
            background-color: #dc3545 !important;
        }

        .bg-primary {
            background-color: #0d6efd !important;
        }

        .star-rating {
            color: #ffc107;
        }

        .filter-buttons .btn {
            margin-right: 5px;
            margin-bottom: 5px;
        }

        .nav-tabs .nav-link {
            color: #495057;
        }

        .nav-tabs .nav-link.active {
            font-weight: bold;
        }

        .actions-column {
            text-align: center;
        }

        /* Center the actions column */
        .action-buttons {
            display: flex;
            flex-wrap: nowrap;
            /* Ensure buttons stay in one line */
            gap: 0.5rem;
            align-items: center;
            justify-content: center;
            /* Center buttons within the div */
        }

        .action-buttons .btn {
            white-space: nowrap;
        }

        /* Change the color of btn-info for Details button here */
        .btn-info {
            background-color: #17a2b8;
            /* Default Bootstrap info color */
            border-color: #17a2b8;
        }

        .btn-info:hover {
            background-color: #138496;
            /* Darker shade for hover */
            border-color: #117a8b;
        }

        .star-rating {
            margin: 0 5px;
            /* Add spacing around stars */
        }
    </style>
</head>

<body>
    <div class="container-fluid">
        <div class="row">
            <?php include 'sidebar.php'; ?>

            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4 main-content">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2 class="h4 mb-0">My Requests</h2>
                    <div>
                        <a href="request_service.php" class="btn btn-primary me-2">Post New Request</a>
                        <a href="manage_posted_requests.php" class="btn btn-outline-primary">Manage Posted Requests</a>
                    </div>
                </div>

                <?php if (isset($_GET['success']) && $_GET['success'] == '1') { ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        Request successfully submitted!
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                <?php } ?>

                <!-- Filter buttons -->
                <div class="filter-buttons mb-4">
                    <a href="my_requests.php?filter=all" class="btn btn-sm btn-outline-secondary <?php echo $filter == 'all' ? 'active' : ''; ?>">All Requests</a>
                    <a href="my_requests.php?filter=pending" class="btn btn-sm btn-outline-warning <?php echo $filter == 'pending' ? 'active' : ''; ?>">Pending</a>
                    <a href="my_requests.php?filter=in_progress" class="btn btn-sm btn-outline-primary <?php echo $filter == 'in_progress' ? 'active' : ''; ?>">inprocess</a>
                    <a href="my_requests.php?filter=rejected" class="btn btn-sm btn-outline-danger <?php echo $filter == 'rejected' ? 'active' : ''; ?>">Rejected</a>
                    <a href="my_requests.php?filter=completed" class="btn btn-sm btn-outline-secondary <?php echo $filter == 'completed' ? 'active' : ''; ?>">Completed</a>
                </div>

                <div class="card shadow mb-4">
                    <div class="card-body">
                        <?php if (empty($requests)): ?>
                            <div class="alert alert-info">No requests found.</div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-bordered table-hover">
                                    <thead>
                                        <tr>
                                            <th>Request</th>
                                            <th>Service Type</th>
                                            <th>Date & Time</th>
                                            <th>Nurse</th>
                                            <th>Status</th>
                                            <th class="actions-column">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $displayed_request_ids = []; // Track displayed RequestIDs
                                        $row_number = 1; // Initialize row number
                                        foreach ($requests as $request):
                                            if (!in_array($request['RequestID'], $displayed_request_ids)):
                                                $displayed_request_ids[] = $request['RequestID'];
                                        ?>
                                                <tr>
                                                    <td><?php echo $row_number; ?></td>
                                                    <td><?php echo htmlspecialchars($request['Type']); ?></td>
                                                    <td>
                                                        <?php echo date('M d, Y', strtotime($request['Date'])) . ' at ' . date('h:i A', strtotime($request['Time'])); ?>
                                                    </td>
                                                    <td>
                                                        <?php if ($request['NurseID'] && $request['ispublic'] == 0): ?>
                                                            <button type="button" class="btn p-0" style="text-decoration: none; color: #0d6efd;"
                                                                data-bs-toggle="modal" data-bs-target="#nurseModal<?php echo $request['RequestID']; ?>">
                                                                <?php echo htmlspecialchars($request['NurseName'] ?: 'Unknown'); ?>
                                                            </button>
                                                        <?php elseif ($request['ApplicationCount'] > 0 && $request['ispublic'] == 1 && $request['RequestStatus'] == 'rejected') : ?>
                                                            <?php echo $request['ApplicationCount']; ?> applicants
                                                        <?php elseif ($request['ApplicationCount'] > 0 && $request['ispublic'] == 1): ?>
                                                            <?php echo $request['AcceptedApplicationCount']; ?> applicants
                                                        <?php else: ?>
                                                            No applicants yet
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <span class="badge <?php
                                                                            echo $request['RequestStatus'] == 'pending' ? 'bg-warning' : ($request['RequestStatus'] == 'inprocess' ? 'bg-primary' : ($request['RequestStatus'] == 'rejected' ? 'bg-danger' : 'bg-success'));
                                                                            ?>">
                                                            <?php echo ucfirst($request['RequestStatus']); ?>
                                                        </span>
                                                    </td>
                                                    <td class="actions-column">




                                                        <div class="action-buttons" style="justify-content: end;">
                                                            <!-- View Applications (Far Left) -->
                                                            <?php if ($request['ispublic'] == 1 && $request['ApplicationCount'] > 0): ?>
                                                                <button type="button" class="btn btn-sm btn-primary"
                                                                    data-bs-toggle="modal"
                                                                    data-bs-target="#applicationsModal<?php echo $request['RequestID']; ?>">
                                                                    View Applications
                                                                </button>
                                                            <?php endif; ?>

                                                            <!-- Rating Stars or Rate Button (After View Applications, Far Left if no View Applications) -->
                                                            <?php if ($request['RequestStatus'] == 'completed' && $request['ispublic'] == 0 && isset($nurses_per_request[$request['RequestID']])): ?>
                                                                <?php foreach ($nurses_per_request[$request['RequestID']] as $nurse): ?>
                                                                    <?php if (!isset($ratings[$request['RequestID'] . '_' . $nurse['NurseID']])): ?>
                                                                        <button type="button" class="btn btn-sm btn-success"
                                                                            data-bs-toggle="modal"
                                                                            data-bs-target="#ratingModal<?php echo $request['RequestID'] . '_' . $nurse['NurseID']; ?>">
                                                                            Rate
                                                                        </button>
                                                                    <?php else: ?>
                                                                        <div class="star-rating">
                                                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                                                                <i class="fas fa-star <?php echo $i <= $ratings[$request['RequestID'] . '_' . $nurse['NurseID']]['Rating'] ? 'text-warning' : 'text-secondary'; ?>"></i>
                                                                            <?php endfor; ?>
                                                                        </div>
                                                                    <?php endif; ?>
                                                                <?php endforeach; ?>
                                                            <?php endif; ?>

                                                            <!-- Accept/Reject for private pending requests (Before Details) -->
                                                            <?php if ($request['RequestStatus'] == 'pending' && $request['NurseID'] && $request['NurseStatus'] == 'accepted' && $request['ispublic'] == 0): ?>
                                                                <form method="POST" class="d-inline">
                                                                    <input type="hidden" name="request_id" value="<?php echo $request['RequestID']; ?>">
                                                                    <button type="submit" name="accept_request" class="btn btn-sm btn-success">Accept</button>
                                                                </form>
                                                                <button type="button" class="btn btn-sm btn-danger"
                                                                    data-bs-toggle="modal"
                                                                    data-bs-target="#rejectModal<?php echo $request['RequestID']; ?>">
                                                                    Reject
                                                                </button>
                                                            <?php endif; ?>

                                                            <!-- Details (Before Cancel, Far Right if no Cancel) -->
                                                            <button class="btn btn-sm btn-info text-white"
                                                                data-bs-toggle="modal"
                                                                data-bs-target="#detailsModal<?php echo $request['RequestID']; ?>">
                                                                Details
                                                            </button>

                                                            <!-- Cancel (Far Right) -->
                                                            <?php if ($request['RequestStatus'] == 'pending'): ?>
                                                                <button type="button" class="btn btn-sm btn-danger"
                                                                    data-bs-toggle="modal"
                                                                    data-bs-target="#cancelModal<?php echo $request['RequestID']; ?>">
                                                                    Cancel
                                                                </button>
                                                            <?php else: ?>
                                                                <button type="button" class="btn btn-sm hidden" style="visibility: none; cursor: default;">
                                                                    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                                                                </button>
                                                            <?php endif; ?>
                                                        </div>




                                                    </td>
                                                </tr>
                                        <?php
                                                $row_number++; // Increment row number
                                            endif;
                                        endforeach;
                                        ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Nurse Modals (for private requests) -->
    <?php foreach ($requests as $request): ?>
        <?php if ($request['NurseID'] && $request['ispublic'] == 0 && isset($nurses_per_request[$request['RequestID']])): ?>
            <?php $nurse = $nurses_per_request[$request['RequestID']][0]; ?>
            <div class="modal fade" id="nurseModal<?php echo $request['RequestID']; ?>" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Nurse Profile</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="text-center mb-3">

                                <img src="<?php echo !empty($nurse['image_path']) ? "../nurse/" . htmlspecialchars($nurse['image_path']) : '../nurse/uploads/profile_photos/default.png'; ?>"
                                    class="rounded-circle profile-img" width="130" height="130" alt="Nurse">

                                    
                                <h5><?php echo htmlspecialchars($nurse['NurseName'] ?: 'Unknown'); ?></h5>
                                <p class="text-muted"><?php echo htmlspecialchars($nurse['Specialization'] ?: 'N/A'); ?></p>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <p><strong>Gender:</strong> <?php echo htmlspecialchars($nurse['NurseGender'] ?: 'N/A'); ?></p>
                                    <p><strong>Languages:</strong> <?php echo htmlspecialchars($nurse['Language'] ?: 'N/A'); ?></p>
                                </div>
                            </div>
                            <hr>
                            <h6>About</h6>
                            <p><?php echo htmlspecialchars($nurse['NurseBio'] ?: 'No bio available.'); ?></p>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    <?php endforeach; ?>

    <!-- Rejection Modals -->
    <?php foreach ($requests as $request): ?>
        <?php if ($request['RequestStatus'] == 'pending' && $request['NurseID'] && $request['NurseStatus'] == 'accepted' && $request['ispublic'] == 0): ?>
            <div class="modal fade" id="rejectModal<?php echo $request['RequestID']; ?>" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Reject Request</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <form method="POST">
                            <div class="modal-body">
                                <input type="hidden" name="request_id" value="<?php echo $request['RequestID']; ?>">
                                <div class="mb-3">
                                    <label for="rejectiondeclinereason<?php echo $request['RequestID']; ?>" class="form-label">Reason for Rejection</label>
                                    <textarea class="form-control" id="rejectiondeclinereason<?php echo $request['RequestID']; ?>"
                                        name="rejection_declinereason" rows="3" required></textarea>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" name="reject_request" class="btn btn-danger">Confirm Rejection</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    <?php endforeach; ?>

    <!-- Cancel Confirmation Modals -->
    <?php foreach ($requests as $request): ?>
        <?php if ($request['RequestStatus'] == 'pending'): ?>
            <div class="modal fade" id="cancelModal<?php echo $request['RequestID']; ?>" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Confirm Cancellation</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <p>Are you sure you want to cancel Request ? This will mark the request as rejected.</p>
                        </div>
                        <form method="POST">
                            <input type="hidden" name="request_id" value="<?php echo $request['RequestID']; ?>">
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                <button type="submit" name="cancel_request" class="btn btn-danger">Confirm Cancel</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    <?php endforeach; ?>

    <!-- Rating Modals -->
    <?php foreach ($requests as $request): ?>
        <?php if ($request['ispublic'] == 1 && $request['ApplicationCount'] > 0): ?>
            <?php foreach ($applicants_per_request[$request['RequestID']] as $applicant): ?>
                <?php if ($request['RequestStatus'] == 'completed' && $applicant['ApplicationStatus'] == 'accepted' && !isset($ratings[$request['RequestID'] . '_' . $applicant['NurseID']])): ?>
                    <div class="modal fade" id="ratingModal<?php echo $request['RequestID'] . '_' . $applicant['NurseID']; ?>" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Rate Nurse</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <form method="POST">
                                    <div class="modal-body">
                                        <input type="hidden" name="request_id" value="<?php echo $request['RequestID']; ?>">
                                        <input type="hidden" name="nurse_id" value="<?php echo $applicant['NurseID']; ?>">

                                        <div class="mb-3 text-center">
                                            <label class="form-label">Rating</label>
                                            <div class="mb-2">
                                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                                    <span class="rating-star" data-rating="<?php echo $i; ?>" style="cursor:pointer; font-size: 2rem;">
                                                        <i class="far fa-star"></i>
                                                    </span>
                                                <?php endfor; ?>
                                            </div>
                                            <input type="hidden" name="rating" id="ratingValue<?php echo $request['RequestID'] . '_' . $applicant['NurseID']; ?>" value="0">
                                        </div>

                                        <div class="mb-3">
                                            <label for="comment<?php echo $request['RequestID'] . '_' . $applicant['NurseID']; ?>" class="form-label">Comments (optional)</label>
                                            <textarea class="form-control" id="comment<?php echo $request['RequestID'] . '_' . $applicant['NurseID']; ?>"
                                                name="rating_comment" rows="3"></textarea>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                        <button type="submit" name="submit_rating" class="btn btn-primary">Submit</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
        <?php endif; ?>
        <?php if ($request['RequestStatus'] == 'completed' && $request['ispublic'] == 0 && isset($nurses_per_request[$request['RequestID']])): ?>
            <?php foreach ($nurses_per_request[$request['RequestID']] as $nurse): ?>
                <?php if (!isset($ratings[$request['RequestID'] . '_' . $nurse['NurseID']])): ?>
                    <div class="modal fade" id="ratingModal<?php echo $request['RequestID'] . '_' . $nurse['NurseID']; ?>" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Rate Nurse</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <form method="POST">
                                    <div class="modal-body">
                                        <input type="hidden" name="request_id" value="<?php echo $request['RequestID']; ?>">
                                        <input type="hidden" name="nurse_id" value="<?php echo $nurse['NurseID']; ?>">

                                        <div class="mb-3 text-center">
                                            <label class="form-label">Rating</label>
                                            <div class="mb-2">
                                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                                    <span class="rating-star" data-rating="<?php echo $i; ?>" style="cursor:pointer; font-size: 2rem;">
                                                        <i class="far fa-star"></i>
                                                    </span>
                                                <?php endfor; ?>
                                            </div>
                                            <input type="hidden" name="rating" id="ratingValue<?php echo $request['RequestID'] . '_' . $nurse['NurseID']; ?>" value="0">
                                        </div>

                                        <div class="mb-3">
                                            <label for="comment<?php echo $request['RequestID'] . '_' . $nurse['NurseID']; ?>" class="form-label">Comments (optional)</label>
                                            <textarea class="form-control" id="comment<?php echo $request['RequestID'] . '_' . $nurse['NurseID']; ?>"
                                                name="rating_comment" rows="3"></textarea>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                        <button type="submit" name="submit_rating" class="btn btn-primary">Submit</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
        <?php endif; ?>
    <?php endforeach; ?>

    <!-- Details Modals -->
    <?php foreach ($requests as $request): ?>
        <div class="modal fade" id="detailsModal<?php echo $request['RequestID']; ?>" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Request Details</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p><strong>Service Type:</strong> <?php echo htmlspecialchars($request['Type']); ?></p>
                        <p><strong>Medical Condition:</strong> <?php echo htmlspecialchars($request['MedicalCondition'] ?: 'N/A'); ?></p>
                        <p><strong>Duration:</strong> <?php echo htmlspecialchars($request['Duration'] ?: 'N/A'); ?> hours</p>
                        <p><strong>Address:</strong> <?php echo htmlspecialchars($request['City'] . ', ' . $request['Street'] . ', ' . $request['Building']); ?></p>
                        <p><strong>Special Instructions:</strong> <?php echo htmlspecialchars($request['SpecialInstructions'] ?: 'None'); ?></p>
                        <?php if ($request['RequestStatus'] == 'rejected' && strpos($request['SpecialInstructions'], 'Rejection reason:') !== false): ?>
                            <div class="alert alert-danger mt-3">
                                <strong>Rejection reason:</strong>
                                <?php echo htmlspecialchars(substr($request['SpecialInstructions'], strpos($request['SpecialInstructions'], 'Rejection reason:') + 17)); ?>
                            </div>
                        <?php elseif ($request['RequestStatus'] == 'rejected' && $request['declinereason']): ?>
                            <div class="alert alert-danger mt-3">
                                <strong>Rejection reason:</strong>
                                <?php echo htmlspecialchars($request['declinereason']); ?>
                            </div>
                        <?php endif; ?>
                        <?php if ($request['RequestStatus'] == 'completed' && isset($nurses_per_request[$request['RequestID']])): ?>
                            <?php foreach ($nurses_per_request[$request['RequestID']] as $nurse): ?>
                                <?php if (isset($ratings[$request['RequestID'] . '_' . $nurse['NurseID']]) && $ratings[$request['RequestID'] . '_' . $nurse['NurseID']]['Description']): ?>
                                    <div class="alert alert-info mt-3">
                                        <strong>Comment for <?php echo htmlspecialchars($nurse['NurseName']); ?>:</strong>
                                        <?php echo htmlspecialchars($ratings[$request['RequestID'] . '_' . $nurse['NurseID']]['Description']); ?>
                                    </div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>

    <!-- Applications Modals -->
    <?php foreach ($requests as $request): ?>
        <?php if ($request['ispublic'] == 1 && $request['ApplicationCount'] > 0): ?>
            <div class="modal fade" id="applicationsModal<?php echo $request['RequestID']; ?>" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Applications for Request #<?php echo $request['RequestID']; ?></h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>Nurse Name</th>
                                        <th>Specialization</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($applicants_per_request[$request['RequestID']] as $applicant): ?>

                                        <?php if ($applicant["ApplicationStatus"] != "rejected") : ?>

                                            <tr>
                                                <td><?php echo htmlspecialchars($applicant['NurseName']); ?></td>
                                                <td><?php echo htmlspecialchars($applicant['Specialization']); ?></td>
                                                <td>
                                                    <div class="d-flex gap-2">
                                                        <?php if ($request['RequestStatus'] == 'pending'): ?>
                                                            <a href="manage_posted_requests.php?nurse_id=<?php echo $applicant['NurseID']; ?>&request_id=<?php echo $request['RequestID']; ?>#heading<?php echo $request['RequestID']; ?>"
                                                                class="btn btn-sm btn-primary">
                                                                View
                                                            </a>
                                                        <?php else: ?>
                                                            <button type="button" class="btn btn-sm btn-primary"
                                                                data-bs-toggle="modal"
                                                                data-bs-target="#nurseProfileModal<?php echo $request['RequestID'] . '_' . $applicant['NurseID']; ?>">
                                                                View
                                                            </button>
                                                        <?php endif; ?>
                                                        <?php if ($request['RequestStatus'] == 'completed' && $applicant['ApplicationStatus'] == 'accepted' && !isset($ratings[$request['RequestID'] . '_' . $applicant['NurseID']])): ?>
                                                            <button type="button" class="btn btn-sm btn-success"
                                                                data-bs-toggle="modal"
                                                                data-bs-target="#ratingModal<?php echo $request['RequestID'] . '_' . $applicant['NurseID']; ?>">
                                                                Rate
                                                            </button>
                                                        <?php elseif ($request['RequestStatus'] == 'completed' && $applicant['ApplicationStatus'] == 'accepted' && isset($ratings[$request['RequestID'] . '_' . $applicant['NurseID']])): ?>
                                                            <div class="star-rating">
                                                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                                                    <i class="fas fa-star <?php echo $i <= $ratings[$request['RequestID'] . '_' . $applicant['NurseID']]['Rating'] ? 'text-warning' : 'text-secondary'; ?>"></i>
                                                                <?php endfor; ?>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                            </tr>

                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    <?php endforeach; ?>

    <!-- Nurse Profile Modals for public requests (completed, inprocess, rejected) -->
    <?php foreach ($requests as $request): ?>
        <?php if ($request['ispublic'] == 1 && $request['ApplicationCount'] > 0): ?>
            <?php foreach ($applicants_per_request[$request['RequestID']] as $applicant): ?>
                <?php if ($request['RequestStatus'] != 'pending'): ?>
                    <div class="modal fade" id="nurseProfileModal<?php echo $request['RequestID'] . '_' . $applicant['NurseID']; ?>" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Nurse Profile</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <div class="text-center mb-3">
                                        <img src="<?php echo !empty($applicant['image_path']) ? "../nurse/" . htmlspecialchars($applicant['image_path']) : '../nurse/uploads/profile_photos/default.jpg'; ?>"
                                            class="rounded-circle profile-img" width="130" height="130" alt="Nurse">
                                            
                                        <h5 class="mt-5"><?php echo htmlspecialchars($applicant['NurseName'] ?: 'Unknown'); ?></h5>
                                        <p class="text-muted"><?php echo htmlspecialchars($applicant['Specialization'] ?: 'N/A'); ?></p>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <p><strong>Gender:</strong> <?php echo htmlspecialchars($applicant['NurseGender'] ?: 'N/A'); ?></p>
                                            <p><strong>Languages:</strong> <?php echo htmlspecialchars($applicant['Language'] ?: 'N/A'); ?></p>
                                        </div>
                                    </div>
                                    <hr>
                                    <h6>About</h6>
                                    <p><?php echo htmlspecialchars($applicant['NurseBio'] ?: 'No bio available.'); ?></p>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
        <?php endif; ?>
    <?php endforeach; ?>


        <?php include "logout.php" ?>


    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Handle star rating
            document.querySelectorAll('.rating-star').forEach(star => {
                star.addEventListener('click', function() {
                    let rating = this.getAttribute('data-rating');
                    let modalId = this.closest('.modal').id;
                    let ratingInput = document.querySelector(`#${modalId} input[name="rating"]`);
                    ratingInput.value = rating;

                    // Update star visuals
                    let stars = this.parentElement.querySelectorAll('.rating-star');
                    stars.forEach(s => {
                        let starRating = s.getAttribute('data-rating');
                        s.innerHTML = starRating <= rating ? '<i class="fas fa-star text-warning"></i>' : '<i class="far fa-star"></i>';
                    });
                });
            });
        });
    </script>
</body>

</html>