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


// Automatically reject pending public requests with past dates
$current_date = date('Y-m-d');
$sql = "UPDATE request 
        SET RequestStatus = 'rejected', 
            SpecialInstructions = CONCAT(IFNULL(SpecialInstructions, ''), ' Rejection reason: This request has been automatically rejected as the scheduled date has passed without confirmation.')
        WHERE RequestStatus = 'pending' 
        AND ispublic = 1 
        AND Date < '$current_date' 
        AND PatientID = $patient_id";
$conn->query($sql);

// Handle nurse selection
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['select_nurse'])) {
    $request_id = (int)$_POST['request_id'];
    $nurse_id = (int)$_POST['nurse_id'];

    // Get NumberOfNurses for the request
    $sql = "SELECT NumberOfNurses FROM request WHERE RequestID = $request_id AND PatientID = $patient_id";
    $result = $conn->query($sql);
    if (!$result || $result->num_rows == 0) {
        $error = "Invalid request ID.";
    } else {
        $request = $result->fetch_assoc();
        $number_of_nurses = $request['NumberOfNurses'];

        // Count currently selected nurses
        $sql = "SELECT COUNT(*) AS selected_count FROM request_applications 
                WHERE RequestID = $request_id AND ApplicationStatus = 'selected'";
        $result = $conn->query($sql);
        $selected_count = $result->fetch_assoc()['selected_count'];

        // Check if adding this nurse exceeds the required number
        if ($selected_count < $number_of_nurses) {
            // Mark nurse as selected
            $sql = "UPDATE request_applications SET ApplicationStatus = 'selected' 
                    WHERE RequestID = $request_id AND NurseID = $nurse_id";
            if ($conn->query($sql)) {
                header("Location: manage_posted_requests.php?request_id=$request_id");
                exit();
            } else {
                $error = "Error selecting nurse: " . $conn->error;
            }
        } else {
            $error = "Cannot select more nurses than required.";
        }
    }
}

// Handle nurse removal
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['remove_nurse'])) {
    $request_id = (int)$_POST['request_id'];
    $nurse_id = (int)$_POST['nurse_id'];

    // Mark nurse as pending again
    $sql = "UPDATE request_applications SET ApplicationStatus = 'pending' 
            WHERE RequestID = $request_id AND NurseID = $nurse_id AND ApplicationStatus = 'selected'";
    if ($conn->query($sql)) {
        // Ensure request is pending if nurses are removed
        $sql = "UPDATE request SET RequestStatus = 'pending' 
                WHERE RequestID = $request_id AND PatientID = $patient_id";
        $conn->query($sql);

        header("Location: manage_posted_requests.php?request_id=$request_id");
        exit();
    } else {
        $error = "Error removing nurse: " . $conn->error;
    }
}

// Handle request confirmation
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['confirm_request'])) {
    $request_id = (int)$_POST['request_id'];

    // Get NumberOfNurses and selected count
    $sql = "SELECT NumberOfNurses FROM request WHERE RequestID = $request_id AND PatientID = $patient_id";
    $result = $conn->query($sql);
    if (!$result || $result->num_rows == 0) {
        $error = "Invalid request ID.";
    } else {
        $request = $result->fetch_assoc();
        $number_of_nurses = $request['NumberOfNurses'];

        $sql = "SELECT COUNT(*) AS selected_count FROM request_applications 
                WHERE RequestID = $request_id AND ApplicationStatus = 'selected'";
        $result = $conn->query($sql);
        $selected_count = $result->fetch_assoc()['selected_count'];

        // Confirm only if selected_count equals NumberOfNurses
        if ($selected_count <= $number_of_nurses) {
            // Confirm the request
            $sql = "UPDATE request SET RequestStatus = 'inprocess' 
                    WHERE RequestID = $request_id AND PatientID = $patient_id";
            $conn->query($sql);

            // Accept selected nurses
            $sql = "UPDATE request_applications SET ApplicationStatus = 'accepted' 
                    WHERE RequestID = $request_id AND ApplicationStatus = 'selected'";
            $conn->query($sql);

            // Reject other nurses
            $sql = "UPDATE request_applications SET ApplicationStatus = 'rejected' 
                    WHERE RequestID = $request_id AND ApplicationStatus = 'pending'";
            $conn->query($sql);

            header("Location: manage_posted_requests.php?success=confirmed");
            exit();
        } else {
            $error = "Cannot confirm request: Number of selected nurses does not match required number.";
        }
    }
}

// Handle view profile via GET
$selected_nurse = null;
$certifications = [];
$prices = [];
if (isset($_GET['nurse_id']) && is_numeric($_GET['nurse_id']) && $_GET['nurse_id'] > 0) {
    $nurse_id = (int)$_GET['nurse_id'];
    
    // Fetch nurse details
    $sql = "SELECT u.FullName, u.DateOfBirth, u.PhoneNumber, n.image_path , u.Email,
                   n.NurseID, n.Bio, n.Availability,
                   na.Specialization, na.Gender, na.Language,
                   a.City AS Location,
                   (SELECT AVG(Rating) FROM rating WHERE NurseID = n.NurseID) AS avg_rating,
                   (SELECT COUNT(*) FROM rating WHERE NurseID = n.NurseID) AS rating_count
            FROM nurse n
            JOIN user u ON n.UserID = u.UserID
            JOIN nurseapplication na ON n.NAID = na.NAID
            LEFT JOIN address a ON u.AddressID = a.AddressID
            WHERE n.NurseID = $nurse_id";
    
    $result = $conn->query($sql);
    if ($result && $result->num_rows > 0) {
        $selected_nurse = $result->fetch_assoc();
        
        // Calculate age
        if ($selected_nurse['DateOfBirth']) {
            $dob = new DateTime($selected_nurse['DateOfBirth']);
            $now = new DateTime();
            $selected_nurse['Age'] = $now->diff($dob)->y;
        } else {
            $selected_nurse['Age'] = 'Unknown';
        }
        
        // Fetch certifications
        $sql = "SELECT Name, Image, Comment
                FROM certification
                WHERE NurseID = $nurse_id AND Status = 'approved'";
        $result = $conn->query($sql);
        $certifications = $result->fetch_all(MYSQLI_ASSOC);
        
        // Fetch pricing
        $sql = "SELECT s.Name, ns.Price
                FROM nurseservices ns
                JOIN service s ON ns.ServiceID = s.ServiceID
                WHERE ns.NurseID = $nurse_id";
        $result = $conn->query($sql);
        $prices = $result->fetch_all(MYSQLI_ASSOC);
    }
}

// Get all public pending requests 
$sql = "SELECT r.*, s.Name AS Type,
       (SELECT COUNT(*) FROM request_applications ra WHERE ra.RequestID = r.RequestID) AS applicant_count,
       (SELECT COUNT(*) FROM request_applications ra WHERE ra.RequestID = r.RequestID AND ra.ApplicationStatus = 'selected') AS selected_count
FROM request r
LEFT JOIN service s ON r.Type = s.ServiceID
WHERE r.PatientID = $patient_id 
AND r.RequestStatus = 'pending'
AND r.ispublic = 1
ORDER BY r.Date DESC";
        
$result = $conn->query($sql);
$posted_requests = $result->fetch_all(MYSQLI_ASSOC);

// Get applications for each request and additional nurse data
$applications = [];
$selected_nurses = [];
foreach ($posted_requests as $request) {
    // Get all applicants (pending and selected)
    $sql = "SELECT ra.*, 
                   u.FullName, u.PhoneNumber, n.image_path , u.Email, u.DateOfBirth,
                   n.NurseID, n.Bio, n.Availability,
                   na.Specialization, na.Gender, na.Language, 
                   a.City AS Location,
                   (SELECT AVG(Rating) FROM rating WHERE NurseID = ra.NurseID) AS avg_rating,
                   (SELECT COUNT(*) FROM rating WHERE NurseID = ra.NurseID) AS rating_count,
                   (SELECT GROUP_CONCAT(Name) FROM certification WHERE NurseID = ra.NurseID AND Status = 'approved') AS certifications
            FROM request_applications ra
            JOIN nurse n ON ra.NurseID = n.NurseID
            JOIN user u ON n.UserID = u.UserID
            JOIN nurseapplication na ON n.NAID = na.NAID
            LEFT JOIN address a ON u.AddressID = a.AddressID
            WHERE ra.RequestID = {$request['RequestID']}
            AND ra.ApplicationStatus IN ('pending', 'selected')";
    
    $result = $conn->query($sql);
    $applications[$request['RequestID']] = $result->fetch_all(MYSQLI_ASSOC);

    // Separate selected nurses for display
    $selected_nurses[$request['RequestID']] = array_filter(
        $applications[$request['RequestID']],
        function ($app) { return $app['ApplicationStatus'] == 'selected'; }
    );
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Posted Requests</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/patient.css">
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <?php include 'sidebar.php'; ?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <?php if (isset($_GET['success']) && $_GET['success'] == '1') { ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            Request successfully submitted!
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                    <?php } ?>

                    <h2 class="h4">Manage Posted Requests</h2>
                    <div>
                        <a href="my_requests.php" class="btn btn-outline-secondary me-2">
                            <i class="fas fa-arrow-left me-1"></i> Back to My Requests
                        </a>
                        <a href="request_service.php" class="btn btn-primary">
                            <i class="fas fa-plus me-1"></i> Post New Request
                        </a>
                    </div>
                </div>
                
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                
                <?php if (isset($_GET['success']) && $_GET['success'] == 'confirmed'): ?>
                    <div class="alert alert-success">
                        Request is now inprocess!
                    </div>
                <?php endif; ?>
                
                <?php if (empty($posted_requests)): ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        You have no open posted requests. 
                        <a href="request_service.php" class="alert-link">Post a new request</a> to get started.
                    </div>
                <?php else: ?>
                    <div class="accordion" id="requestsAccordion">
                        <?php foreach ($posted_requests as $request): ?>
                            <div class="card request-card">
                                <div class="card-header request-header d-flex justify-content-between align-items-center" 
                                     id="heading<?php echo $request['RequestID']; ?>" 
                                     data-bs-toggle="collapse" 
                                     data-bs-target="#collapse<?php echo $request['RequestID']; ?>">
                                    <div>
                                        <span class="fw-bold"><?php echo htmlspecialchars($request['Type']); ?></span>
                                        <span class="text-muted ms-3">
                                            <?php echo date('M d, Y', strtotime($request['Date'])); ?> at 
                                            <?php echo date('h:i A', strtotime($request['Time'])); ?>
                                        </span>
                                    </div>
                                    <div>
                                        <span class="badge bg-primary rounded-pill me-2">
                                            <?php echo $request['applicant_count']; ?> applicant(s)
                                        </span>
                                        <span class="badge badge-nurses-needed rounded-pill">
                                            Needs <?php echo $request['NumberOfNurses']; ?> nurse(s)
                                        </span>
                                    </div>
                                </div>
                                
                                <div id="collapse<?php echo $request['RequestID']; ?>" 
                                     class="collapse <?php echo (isset($_GET['request_id']) && $_GET['request_id'] == $request['RequestID']) ? 'show' : ''; ?>" 
                                     aria-labelledby="heading<?php echo $request['RequestID']; ?>" 
                                     data-bs-parent="#requestsAccordion">
                                    <div class="card-body">
                                        <div class="mb-4">
                                            <h5 class="h6 text-muted mb-3">REQUEST DETAILS</h5>
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="request-detail-row mb-2">
                                                        <strong>Service Type:</strong> <?php echo htmlspecialchars($request['Type']); ?>
                                                    </div>
                                                    <div class="request-detail-row mb-2">
                                                        <strong>Date & Time:</strong> 
                                                        <?php echo date('l, F j, Y', strtotime($request['Date'])); ?> at 
                                                        <?php echo date('h:i A', strtotime($request['Time'])); ?>
                                                    </div>
                                                    <div class="request-detail-row mb-2">
                                                        <strong>Nurses Required:</strong> <?php echo $request['NumberOfNurses']; ?>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <?php if ($request['Duration']): ?>
                                                        <div class="request-detail-row mb-2">
                                                            <strong>Duration:</strong> <?php echo htmlspecialchars($request['Duration']); ?> hours
                                                        </div>
                                                    <?php endif; ?>
                                                    <?php if ($request['SpecialInstructions']): ?>
                                                        <div class="request-detail-row mb-2">
                                                            <strong>Special Instructions:</strong> <?php echo htmlspecialchars($request['SpecialInstructions']); ?>
                                                        </div>
                                                    <?php endif; ?>
                                                    <?php if ($request['MedicalCondition']): ?>
                                                        <div class="request-detail-row mb-2">
                                                            <strong>Medical Condition:</strong> <?php echo htmlspecialchars($request['MedicalCondition']); ?>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <hr>
                                        
                                        <h5 class="h6 text-muted mb-3">SELECTED NURSES</h5>
                                        <?php if (empty($selected_nurses[$request['RequestID']])): ?>
                                            <div class="alert alert-info">
                                                <i class="fas fa-info-circle me-2"></i>
                                                No nurses selected yet.
                                            </div>
                                        <?php else: ?>
                                            <div class="row row-cols-1 row-cols-md-2 g-4 mb-4">
                                                <?php foreach ($selected_nurses[$request['RequestID']] as $nurse): ?>
                                                    <div class="col">
                                                        <div class="card nurse-card h-100">
                                                            <div class="card-body">
                                                                <div class="d-flex align-items-start mb-3">


                                                                    <img src="<?php echo !empty($nurse['image_path']) ? "../nurse/" . htmlspecialchars($nurse['image_path']) : '../nurse/uploads/profile_photos/default.jpg'; ?>"
                                                                    class="rounded-circle profile-img me-3" width="50" height="50" alt="Nurse">



                                                                    <div>
                                                                        <h5 class="card-title mb-1"><?php echo htmlspecialchars($nurse['FullName']); ?></h5>
                                                                        <p class="text-muted mb-1"><?php echo htmlspecialchars($nurse['Specialization']); ?></p>
                                                                        <div class="star-rating small">
                                                                            <?php
                                                                            $rating = $nurse['avg_rating'] ? round($nurse['avg_rating'], 1) : 0;
                                                                            for ($i = 1; $i <= 5; $i++) {
                                                                                if ($i <= floor($rating)) {
                                                                                    echo '<i class="fas fa-star text-warning"></i>';
                                                                                } elseif ($i == ceil($rating) && $rating - floor($rating) > 0) {
                                                                                    echo '<i class="fas fa-star-half-alt text-warning"></i>';
                                                                                } else {
                                                                                    echo '<i class="far fa-star text-warning"></i>';
                                                                                }
                                                                            }
                                                                            ?>
                                                                            <small class="text-muted ms-1">(<?php echo $nurse['rating_count'] ?: 'No'; ?> reviews)</small>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <div class="d-grid gap-2">
                                                                    <form method="POST">
                                                                        <input type="hidden" name="request_id" value="<?php echo $request['RequestID']; ?>">
                                                                        <input type="hidden" name="nurse_id" value="<?php echo $nurse['NurseID']; ?>">
                                                                        <button type="submit" name="remove_nurse" class="btn btn-sm btn-danger">
                                                                            <i class="fas fa-trash me-1"></i> Remove Nurse
                                                                        </button>
                                                                    </form>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                            <?php if ($request['selected_count'] <= $request['NumberOfNurses']): ?>
                                                <form method="POST" class="mt-3">
                                                    <input type="hidden" name="request_id" value="<?php echo $request['RequestID']; ?>">
                                                    <button type="submit" name="confirm_request" class="btn btn-primary btn-sm mb-4">
                                                        <i class="fas fa-check me-1"></i> Confirm Request
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                        
                                        <h5 class="h6 text-muted mb-3">NURSE APPLICANTS</h5>
                                        <?php 
                                        $pending_applicants = array_filter(
                                            $applications[$request['RequestID']],
                                            function ($app) { return $app['ApplicationStatus'] == 'pending'; }
                                        );
                                        ?>
                                        <?php if (empty($pending_applicants)): ?>
                                            <div class="alert alert-warning">
                                                <i class="fas fa-exclamation-circle me-2"></i>
                                                No pending applicants.
                                            </div>
                                        <?php else: ?>
                                            <div class="row row-cols-1 row-cols-md-2 g-4">
                                                <?php foreach ($pending_applicants as $application): ?>
                                                    <div class="col">
                                                        <div class="card nurse-card h-100">
                                                            <div class="card-body">
                                                                <div class="d-flex align-items-start mb-3">


                                


                                                                              <img src="<?php echo !empty($application['image_path']) ? "../nurse/" . htmlspecialchars($application['image_path']) : '../nurse/uploads/profile_photos/default.jpg'; ?>"
                                                class="rounded-circle profile-img me-3" width="50" height="50" alt="Nurse">

                                                                    <div>
                                                                        <h5 class="card-title mb-1"><?php echo htmlspecialchars($application['FullName']); ?></h5>
                                                                        <p class="text-muted mb-1"><?php echo htmlspecialchars($application['Specialization']); ?></p>
                                                                        <div class="star-rating small">
                                                                            <?php
                                                                            $rating = $application['avg_rating'] ? round($application['avg_rating'], 1) : 0;
                                                                            for ($i = 1; $i <= 5; $i++) {
                                                                                if ($i <= floor($rating)) {
                                                                                    echo '<i class="fas fa-star text-warning"></i>';
                                                                                } elseif ($i == ceil($rating) && $rating - floor($rating) > 0) {
                                                                                    echo '<i class="fas fa-star-half-alt text-warning"></i>';
                                                                                } else {
                                                                                    echo '<i class="far fa-star text-warning"></i>';
                                                                                }
                                                                            }
                                                                            ?>
                                                                            <small class="text-muted ms-1">(<?php echo $application['rating_count'] ?: 'No'; ?> reviews)</small>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                
                                                                <div class="d-grid gap-2">
                                                                    <button class="btn btn-sm btn-outline-primary view-profile-btn" 
                                                                            data-nurse-id="<?php echo $application['NurseID']; ?>" 
                                                                            data-request-id="<?php echo $request['RequestID']; ?>">
                                                                        <i class="fas fa-eye me-1"></i> View Full Profile
                                                                    </button>
                                                                    <form method="POST">
                                                                        <input type="hidden" name="request_id" value="<?php echo $request['RequestID']; ?>">
                                                                        <input type="hidden" name="nurse_id" value="<?php echo $application['NurseID']; ?>">
                                                                        <button type="submit" name="select_nurse" class="btn btn-success btn-sm">
                                                                            <i class="fas fa-check me-1"></i> Select This Nurse
                                                                        </button>
                                                                    </form>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                
                <!-- Nurse Profile Modal -->
                <div class="modal fade" id="nurseProfileModal" tabindex="-1" aria-labelledby="nurseProfileModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="nurseProfileModalLabel">Nurse Profile</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <?php if ($selected_nurse): ?>
                                    <div class="row">

                                    <!-- here -->
                                        <div class="col-md-4 text-center">

                                         
                                            

                                                 <img src="<?php echo !empty($selected_nurse['image_path']) ? "../nurse/" . htmlspecialchars($selected_nurse['image_path']) : '../nurse/uploads/profile_photos/default.jpg'; ?>"
                                                class="rounded-circle mb-3" width="150" height="150" alt="Nurse">


                                            



                                            <h4><?php echo htmlspecialchars($selected_nurse['FullName']); ?></h4>
                                            <p class="text-muted"><?php echo htmlspecialchars($selected_nurse['Specialization'] ?: 'General Nurse'); ?></p>
                                            <div class="mb-3">
                                                <?php
                                                $rating = $selected_nurse['avg_rating'] ? round($selected_nurse['avg_rating'], 1) : 0;
                                                for ($i = 1; $i <= 5; $i++) {
                                                    if ($i <= floor($rating)) {
                                                        echo '<i class="fas fa-star text-warning"></i>';
                                                    } elseif ($i == ceil($rating) && $rating - floor($rating) > 0) {
                                                        echo '<i class="fas fa-star-half-alt text-warning"></i>';
                                                    } else {
                                                        echo '<i class="far fa-star text-warning"></i>';
                                                    }
                                                }
                                                ?>
                                                <small class="text-muted"><?php echo $rating . ' (' . ($selected_nurse['rating_count'] ?: 0) . ' reviews)'; ?></small>
                                            </div>
                                        </div>
                                        <div class="col-md-8">
                                            <ul class="nav nav-tabs" id="nurseProfileTabs">
                                                <li class="nav-item">
                                                    <a class="nav-link active" data-bs-toggle="tab" href="#profile">Details</a>
                                                </li>
                                                <li class="nav-item">
                                                    <a class="nav-link" data-bs-toggle="tab" href="#certification">Certifications</a>
                                                </li>
                                                <li class="nav-item">
                                                    <a class="nav-link" data-bs-toggle="tab" href="#pricing">Pricing</a>
                                                </li>
                                            </ul>
                                            <div class="tab-content p-3 border border-top-0 rounded-bottom">
                                                <div class="tab-pane fade show active" id="profile">
                                                    <div class="row">
                                                        <div class="col-sm-6">
                                                            <p><strong>Age:</strong> <?php echo htmlspecialchars($selected_nurse['Age'] ?: 'Unknown'); ?></p>
                                                            <p><strong>Gender:</strong> <?php echo htmlspecialchars($selected_nurse['Gender'] ?: 'Unknown'); ?></p>
                                                            <p><strong>Languages:</strong> <?php echo htmlspecialchars($selected_nurse['Language'] ?: 'Unknown'); ?></p>
                                                        </div>
                                                        <div class="col-sm-6">
                                                            <p><strong>Location:</strong> <?php echo htmlspecialchars($selected_nurse['Location'] ?: 'Unknown'); ?></p>
                                                        </div>
                                                    </div>
                                                    <hr>
                                                    <h6>About</h6>
                                                    <p><?php echo htmlspecialchars($selected_nurse['Bio'] ?: 'No bio available.'); ?></p>
                                                </div>
                                                <div class="tab-pane fade" id="certification">
                                                    <?php if (empty($certifications)): ?>
                                                        <p class="text-muted">No certifications available.</p>
                                                    <?php else: ?>
                                                        <div class="row">
                                                            <?php foreach ($certifications as $cert): ?>
                                                                <div class="col-md-6 mb-3">
                                                                    <div class="card h-100">
                                                                        <div class="card-body">
                                                                            <h6 class="card-title"><?php echo htmlspecialchars($cert['Name']); ?></h6>
                                                                           
                                                                            <p class="small text-muted"><?php echo htmlspecialchars($cert['Comment'] ?: 'No additional comments.'); ?></p>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            <?php endforeach; ?>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="tab-pane fade" id="pricing">
                                                    <?php if (empty($prices)): ?>
                                                        <p class="text-muted">No pricing information available.</p>
                                                    <?php else: ?>
                                                        <ul class="list-group">
                                                            <?php foreach ($prices as $price): ?>
                                                                <li class="list-group-item">
                                                                    <strong><?php echo htmlspecialchars($price['Name']); ?>:</strong>
                                                                    $<?php echo htmlspecialchars($price['Price']); ?>
                                                                </li>
                                                            <?php endforeach; ?>
                                                        </ul>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <div class="alert alert-warning">
                                        No nurse selected. Please choose a nurse to view their profile.
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                <?php if ($selected_nurse): ?>
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="request_id" value="<?php echo isset($_GET['request_id']) ? (int)$_GET['request_id'] : ''; ?>">
                                        <input type="hidden" name="nurse_id" value="<?php echo $selected_nurse['NurseID']; ?>">
                                        <button type="submit" name="select_nurse" class="btn btn-success">
                                            <i class="fas fa-check me-1"></i> Select This Nurse
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

        <?php include "logout.php" ?>


    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Open modal if nurse_id is in URL
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.has('nurse_id') && urlParams.get('nurse_id') !== '') {
                const modal = new bootstrap.Modal(document.getElementById('nurseProfileModal'), {
                    backdrop: 'static',
                    keyboard: false
                });
                modal.show();
            }

            // Ensure accordion card is open if request_id is in URL
            const requestId = urlParams.get('request_id');
            if (requestId) {
                const collapseElement = document.getElementById('collapse' + requestId);
                if (collapseElement) {
                    collapseElement.classList.add('show');
                }
            }   

            // Handle View Profile buttons
            document.querySelectorAll('.view-profile-btn').forEach(button => {
                button.addEventListener('click', function(event) {
                    event.preventDefault();
                    const nurseId = this.getAttribute('data-nurse-id');
                    const requestId = this.getAttribute('data-request-id');
                    const url = window.location.pathname + '?nurse_id=' + nurseId + 
                                (requestId ? '&request_id=' + requestId : '') + 
                                (requestId ? '#heading' + requestId : '');
                    window.location.href = url;
                });
            });
        });
    </script>
</body>
</html>