<?php
require '../connect.php';


session_start();
$patient_id = $_SESSION['patient_id'];

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



// Delete old schedules
$sql_delete_old = "DELETE FROM schedule WHERE Date < CURDATE()";
$conn->query($sql_delete_old);

// Fetch patient address
$sql_patient_address = "SELECT a.AddressID, a.City, a.Street, a.Building
                       FROM address a
                       INNER JOIN user u ON u.AddressID = a.AddressID
                       INNER JOIN patient p ON p.UserID = u.UserID
                       WHERE p.PatientID = '$patient_id'";
$result_patient_address = $conn->query($sql_patient_address);
$patient_address = $result_patient_address && $result_patient_address->num_rows > 0 ? $result_patient_address->fetch_assoc() : [];

// Handle form submission
$error = '';
$nurse_name = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nurse_id = isset($_POST['nurse_id']) ? intval($_POST['nurse_id']) : 0;
    $patient_id = isset($_POST['patient_id']) ? intval($_POST['patient_id']) : 0;
    $service_id = isset($_POST['service_id']) ? intval($_POST['service_id']) : 0;
    $request_date = isset($_POST['request_date']) ? trim($_POST['request_date']) : '';
    $preferred_time = isset($_POST['preferred_time']) ? trim($_POST['preferred_time']) : '';
    $care_needed = isset($_POST['care_needed']) ? array_map('trim', $_POST['care_needed']) : [];
    $street = isset($_POST['address_street']) ? trim($_POST['address_street']) : '';
    $building = isset($_POST['address_building']) ? trim($_POST['address_building']) : '';
    $city = isset($_POST['city']) ? trim($_POST['city']) : '';
    $duration = isset($_POST['duration']) ? intval($_POST['duration']) : 0;

    // Fetch nurse name
    if ($nurse_id) {
        $sql_nurse_name = "SELECT u.FullName FROM nurse n INNER JOIN user u ON n.UserID = u.UserID WHERE n.NurseID = '$nurse_id'";
        $result_nurse_name = $conn->query($sql_nurse_name);
        if ($result_nurse_name->num_rows > 0) {
            $nurse_name = $result_nurse_name->fetch_assoc()['FullName'];
        }
    }

    // Validate inputs
    if (!$nurse_id || !$patient_id || !$service_id || empty($care_needed) || !$street || !$building || !$city || !$request_date || $duration < 1 || $duration > 24) {
        $error = "All fields are required, except preferred time. Ensure valid date and duration between 1-24 hours.";
    } else {
        // Validate time format
        if (!empty($preferred_time) && !preg_match('/^([0-1][0-9]|2[0-3]):[0-5][0-9](:[0-5][0-9])?$/', $preferred_time)) {
            $error = "Invalid time format.";
        } else {
            // Validate date
            $today = new DateTime();
            $request_date_obj = new DateTime($request_date);
            if ($request_date_obj < $today) {
                $error = "The selected date is in the past.";
            } else {
                // Validate schedule
                $date = $request_date;
                $schedule_id = null;
                $availability_id = null;
                $schedule_start_time = '';
                $schedule_end_time = '';
                $is_24_hours = false;

                // Check weekly schedule
                $day_of_week = strtolower($request_date_obj->format('l'));
                $day_field = ucfirst($day_of_week);
                $sql_weekly = "SELECT AvailabilityID, StartTime, EndTime, $day_field 
                               FROM weekly_availability 
                               WHERE NurseID = '$nurse_id'";
                $result_weekly = $conn->query($sql_weekly);
                if ($result_weekly->num_rows > 0) {
                    $weekly_row = $result_weekly->fetch_assoc();
                    if ($weekly_row[$day_field] == 1) {
                        $availability_id = $weekly_row['AvailabilityID'];
                        $schedule_start_time = $weekly_row['StartTime'] ?? '00:00:00';
                        $schedule_end_time = $weekly_row['EndTime'] ?? '00:00:00';
                        if ($schedule_start_time == '00:00:00' && $schedule_end_time == '00:00:00') {
                            $is_24_hours = true;
                        }
                    }
                }

                // Check daily schedule
                if (!$availability_id) {
                    $sql_schedule = "SELECT ScheduleID, Date, StartTime, EndTime 
                                     FROM schedule 
                                     WHERE NurseID = '$nurse_id' AND Status = 'available' AND Date = '$date' AND Date >= CURDATE()";
                    $result_schedule = $conn->query($sql_schedule);
                    if ($result_schedule->num_rows > 0) {
                        $schedule_row = $result_schedule->fetch_assoc();
                        $schedule_id = $schedule_row['ScheduleID'];
                        $schedule_start_time = $schedule_row['StartTime'] ?? '00:00:00';
                        $schedule_end_time = $schedule_row['EndTime'] ?? '00:00:00';
                    }
                }

                if (!$schedule_start_time || !$schedule_end_time) {
                    $error = "The selected date is not available in the nurse's schedule.";
                } else {
                    // Set default time
                    if (empty($preferred_time)) {
                        $preferred_time = $is_24_hours ? '00:00:00' : $schedule_start_time;
                    }

                    // Validate time and duration
                    if (!$is_24_hours) {
                        $preferred_datetime = new DateTime("$date $preferred_time");
                        $start_datetime = new DateTime("$date $schedule_start_time");
                        $end_datetime = new DateTime("$date $schedule_end_time");

                        if ($preferred_datetime < $start_datetime || $preferred_datetime > $end_datetime) {
                            $error = "The preferred time is outside the available schedule ($schedule_start_time - $schedule_end_time).";
                        } else {
                            $request_end_datetime = clone $preferred_datetime;
                            $request_end_datetime->modify("+$duration hours");
                            if ($request_end_datetime > $end_datetime) {
                                $error = "The requested duration extends beyond the available schedule ($schedule_end_time).";
                            }
                        }
                    }

                    if (!$error) {
                        // Check conflicts
                        $sql_conflicts = "SELECT Time, Duration 
                                          FROM request 
                                          WHERE NurseID = '$nurse_id' AND Date = '$date' AND RequestStatus = 'pending'";
                        $result_conflicts = $conn->query($sql_conflicts);
                        $is_conflict = false;
                        while ($conflict_row = $result_conflicts->fetch_assoc()) {
                            $existing_start = new DateTime("$date {$conflict_row['Time']}");
                            $existing_end = clone $existing_start;
                            $existing_end->modify("+{$conflict_row['Duration']} hours");
                            $preferred_datetime = new DateTime("$date $preferred_time");
                            $request_end_datetime = clone $preferred_datetime;
                            $request_end_datetime->modify("+$duration hours");
                            if (
                                ($preferred_datetime >= $existing_start && $preferred_datetime < $existing_end) ||
                                ($request_end_datetime > $existing_start && $request_end_datetime <= $existing_end) ||
                                ($preferred_datetime <= $existing_start && $request_end_datetime >= $existing_end)
                            ) {
                                $is_conflict = true;
                                break;
                            }
                        }
                        if ($is_conflict) {
                            $error = "The requested time slot conflicts with an existing request.";
                        } else {
                            // Check total booked duration
                            $sql_booked_duration = "SELECT SUM(Duration) AS TotalDuration 
                                                   FROM request 
                                                   WHERE NurseID = '$nurse_id' AND Date = '$date' AND RequestStatus = 'pending'";
                            $result_booked_duration = $conn->query($sql_booked_duration);
                            $booked_duration = $result_booked_duration->fetch_assoc()['TotalDuration'] ?? 0;
                            $available_duration = $is_24_hours ? 24 : (strtotime($schedule_end_time) - strtotime($schedule_start_time)) / 3600;
                            if ($booked_duration + $duration > $available_duration) {
                                $error = "The requested duration exceeds the available duration for this day.";
                            } else {
                                // Insert address
                                $address_id = null;
                                if (
                                    !empty($patient_address) &&
                                    $city === $patient_address['City'] &&
                                    $street === $patient_address['Street'] &&
                                    $building === $patient_address['Building']
                                ) {
                                    $address_id = $patient_address['AddressID'];
                                } else {
                                    $sql_address = "INSERT INTO address (City, Street, Building) VALUES ('$city', '$street', '$building')";
                                    if ($conn->query($sql_address)) {
                                        $address_id = $conn->insert_id;
                                    } else {
                                        $error = "Error saving address: " . $conn->error;
                                    }
                                }

                                if (!$error) {
                                    $special_instructions = implode(', ', $care_needed);
                                    $sql_request = "INSERT INTO request (
                                        NurseGender, AgeType, Date, Time, Type, NumberOfNurses, SpecialInstructions, 
                                        MedicalCondition, Duration, NurseStatus, PatientStatus, RequestStatus, 
                                        ServiceFeePercentage, PatientID, NurseID, AddressID, ispublic
                                    ) VALUES (
                                        'No Preference', 'No Preference', '$date', '$preferred_time', 
                                        $service_id, 1, '$special_instructions', 
                                        NULL, $duration, 'pending', 'completed', 'pending', 10.00, 
                                        $patient_id, $nurse_id, $address_id, 0
                                    )";
                                    if ($conn->query($sql_request)) {
                                        $request_id = $conn->insert_id;
                                        foreach ($care_needed as $care_name) {
                                            $sql_care = "INSERT INTO request_care_needed (RequestID, CareID) 
                                                         SELECT $request_id, CareID FROM care_needed WHERE Name = '$care_name'";
                                            if (!$conn->query($sql_care)) {
                                                $error = "Error linking care type: " . $conn->error;
                                                break;
                                            }
                                        }

                                        if (!$error) {
                                            if ($schedule_id) {
                                                $start_datetime = new DateTime("$date $preferred_time");
                                                $end_datetime = new DateTime("$date $schedule_end_time");
                                                $request_end_datetime = clone $start_datetime;
                                                $request_end_datetime->modify("+$duration hours");
                                                if ($request_end_datetime < $end_datetime) {
                                                    $new_start_time = $request_end_datetime->format('H:i:s');
                                                    $sql_insert_remaining = "INSERT INTO schedule (Date, StartTime, EndTime, Notes, Status, NurseID) 
                                                                            VALUES ('$date', '$new_start_time', '$schedule_end_time', 'Remaining slot', 'available', '$nurse_id')";
                                                    $conn->query($sql_insert_remaining);
                                                }
                                                $sql_update_schedule = "UPDATE schedule SET Status = 'booked' WHERE ScheduleID = '$schedule_id'";
                                                $conn->query($sql_update_schedule);
                                            } elseif ($availability_id) {
                                                $sql_insert_schedule = "INSERT INTO schedule (Date, StartTime, EndTime, Notes, Status, NurseID) 
                                                                       VALUES ('$date', '$preferred_time', '$schedule_end_time', 'Auto-generated from weekly availability', 'booked', '$nurse_id')";
                                                $conn->query($sql_insert_schedule);
                                                $start_datetime = new DateTime("$date $preferred_time");
                                                $request_end_datetime = clone $start_datetime;
                                                $request_end_datetime->modify("+$duration hours");
                                                if ($request_end_datetime < new DateTime("$date $schedule_end_time")) {
                                                    $new_start_time = $request_end_datetime->format('H:i:s');
                                                    $sql_insert_remaining = "INSERT INTO schedule (Date, StartTime, EndTime, Notes, Status, NurseID) 
                                                                            VALUES ('$date', '$new_start_time', '$schedule_end_time', 'Remaining slot', 'available', '$nurse_id')";
                                                    $conn->query($sql_insert_remaining);
                                                }
                                            }
                                            header("Location: my_requests.php");
                                            exit;
                                        }
                                    } else {
                                        $error = "Error submitting request: " . $conn->error;
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
    }
}

// Handle service filter
$service_filter = isset($_GET['service']) ? $_GET['service'] : '';

// Fetch nurses with proper service filtering
$sql_nurses = "SELECT 
    n.NurseID, 
    u.FullName, 
    n.Bio, 
    n.image_path, 
    na.Specialization, 
    na.Gender, 
    na.Language, 
    u.DateOfBirth, 
    a.City, 
    a.Street, 
    IFNULL(AVG(r.Rating), 0) AS AvgRating, 
    COUNT(r.RID) AS ReviewCount
FROM nurse n
INNER JOIN user u ON n.UserID = u.UserID
LEFT JOIN nurseapplication na ON n.NAID = na.NAID
LEFT JOIN address a ON u.AddressID = a.AddressID
LEFT JOIN rating r ON n.NurseID = r.NurseID";

// Add service join if filtering by service
if ($service_filter && $service_filter !== 'All') {
    $sql_nurses .= " INNER JOIN nurseservices ns ON n.NurseID = ns.NurseID
                     INNER JOIN service s ON ns.ServiceID = s.ServiceID AND s.Name = ?";
}

$sql_nurses .= " WHERE n.Availability = 1
                 GROUP BY n.NurseID
                 ORDER BY AvgRating DESC";

// Prepare statement to prevent SQL injection
$stmt = $conn->prepare($sql_nurses);
if ($service_filter && $service_filter !== 'All') {
    $stmt->bind_param("s", $service_filter);
}
$stmt->execute();
$result_nurses = $stmt->get_result();

$nurses = [];
while ($row = $result_nurses->fetch_assoc()) {
    // Calculate age
    $dob = new DateTime($row['DateOfBirth']);
    $now = new DateTime();
    $row['Age'] = $now->diff($dob)->y;

    // Get services with prepared statement
    $nurse_id = $row['NurseID'];
    $stmt_services = $conn->prepare("SELECT s.ServiceID, s.Name 
                                   FROM nurseservices ns 
                                   INNER JOIN service s ON ns.ServiceID = s.ServiceID 
                                   WHERE ns.NurseID = ?");
    $stmt_services->bind_param("i", $nurse_id);
    $stmt_services->execute();
    $result_services = $stmt_services->get_result();
    $row['services'] = $result_services->fetch_all(MYSQLI_ASSOC);

    // Get available schedules with prepared statement
    $stmt_schedules = $conn->prepare("SELECT ScheduleID, Date, StartTime, EndTime, Notes 
                                     FROM schedule 
                                     WHERE NurseID = ? AND Status = 'available' AND Date >= CURDATE()");
    $stmt_schedules->bind_param("i", $nurse_id);
    $stmt_schedules->execute();
    $result_schedules = $stmt_schedules->get_result();

    $row['schedules'] = [];
    while ($schedule_row = $result_schedules->fetch_assoc()) {
        $schedule_date = $schedule_row['Date'];
        $stmt_booked = $conn->prepare("SELECT SUM(Duration) AS TotalDuration 
                                      FROM request 
                                      WHERE NurseID = ? AND Date = ? AND RequestStatus = 'pending'");
        $stmt_booked->bind_param("is", $nurse_id, $schedule_date);
        $stmt_booked->execute();
        $booked_result = $stmt_booked->get_result();
        $booked_duration = $booked_result->fetch_assoc()['TotalDuration'] ?? 0;

        $available_duration = (strtotime($schedule_row['EndTime']) - strtotime($schedule_row['StartTime'])) / 3600;
        if ($booked_duration < $available_duration) {
            $row['schedules'][] = $schedule_row;
        }
    }

    // Get weekly availability
    $stmt_weekly = $conn->prepare("SELECT AvailabilityID, Monday, Tuesday, Wednesday, Thursday, 
                                  Friday, Saturday, Sunday, StartTime, EndTime 
                                  FROM weekly_availability 
                                  WHERE NurseID = ?");
    $stmt_weekly->bind_param("i", $nurse_id);
    $stmt_weekly->execute();
    $result_weekly = $stmt_weekly->get_result();
    $row['weekly_availability'] = $result_weekly->fetch_all(MYSQLI_ASSOC);

    $nurses[] = $row;
}



// Fetch services
$sql_services = "SELECT DISTINCT Name FROM service WHERE ServiceID > 0";
$result_services = $conn->query($sql_services);
$services = [];
while ($row = $result_services->fetch_assoc()) {
    $services[] = $row['Name'];
}



// Fetch selected nurse
// Fetch selected nurse
$selected_nurse = null;
$certifications = [];
$schedule = [];
$weekly_availability = [];
$prices = [];
$image_base_path = '../nurse/';

if (isset($_GET['nurse_id']) && is_numeric($_GET['nurse_id']) && $_GET['nurse_id'] > 0) {
    $nurse_id = (int)$_GET['nurse_id'];

    // Main nurse query with proper LEFT JOINs and prepared statement
    $sql_nurse = "SELECT 
        n.NurseID, 
        u.FullName, 
        n.Bio, 
        n.image_path, 
        na.Specialization, 
        na.Gender, 
        na.Language, 
        u.DateOfBirth, 
        a.City, 
        a.Street, 
        IFNULL(AVG(r.Rating), 0) AS AvgRating, 
        COUNT(r.RID) AS ReviewCount
    FROM nurse n
    INNER JOIN user u ON n.UserID = u.UserID  
    LEFT JOIN nurseapplication na ON n.NAID = na.NAID  
    LEFT JOIN address a ON u.AddressID = a.AddressID  
    LEFT JOIN rating r ON n.NurseID = r.NurseID  
    WHERE n.NurseID = ?
    GROUP BY n.NurseID, u.FullName, n.Bio, n.image_path, na.Specialization, 
             na.Gender, na.Language, u.DateOfBirth, a.City, a.Street";

    $stmt_nurse = $conn->prepare($sql_nurse);
    $stmt_nurse->bind_param("i", $nurse_id);
    $stmt_nurse->execute();
    $result_nurse = $stmt_nurse->get_result();
    
    if ($result_nurse->num_rows > 0) {
        $row = $result_nurse->fetch_assoc();
        $dob = new DateTime($row['DateOfBirth']);
        $now = new DateTime();
        $row['Age'] = $now->diff($dob)->y;
        $row['Location'] = ($row['City'] && $row['Street']) ? $row['City'] . ', ' . $row['Street'] : ($row['City'] ?: 'Unknown');
        $row['image_path'] = (!empty($row['image_path']))
            ? '../nurse/' . $row['image_path']
            : '../nurse/uploads/profile_photos/default.jpg';

        $selected_nurse = $row;
    }

    // Certifications with prepared statement
    $sql_certs = "SELECT Name, Image, Comment FROM certification WHERE NurseID = ? AND Status = 'approved'";
    $stmt_certs = $conn->prepare($sql_certs);
    $stmt_certs->bind_param("i", $nurse_id);
    $stmt_certs->execute();
    $result_certs = $stmt_certs->get_result();
    
    while ($cert_row = $result_certs->fetch_assoc()) {
        $cert_row['Image'] = !empty($cert_row['Image']) ?  "../nurse/" . $cert_row['Image'] :
            '/nurse/uploads/certifications/default.jpg';
        $certifications[] = $cert_row;
    }

    // Schedule with prepared statements
    $sql_schedule = "SELECT ScheduleID, Date, StartTime, EndTime, Notes 
                     FROM schedule 
                     WHERE NurseID = ? AND Status = 'available' AND Date >= CURDATE()";
    $stmt_schedule = $conn->prepare($sql_schedule);
    $stmt_schedule->bind_param("i", $nurse_id);
    $stmt_schedule->execute();
    $result_schedule = $stmt_schedule->get_result();
    
    while ($sched_row = $result_schedule->fetch_assoc()) {
        $schedule_date = $sched_row['Date'];
        
        $sql_booked_duration = "SELECT SUM(Duration) AS TotalDuration 
                               FROM request 
                               WHERE NurseID = ? AND Date = ? AND RequestStatus = 'pending'";
        $stmt_booked = $conn->prepare($sql_booked_duration);
        $stmt_booked->bind_param("is", $nurse_id, $schedule_date);
        $stmt_booked->execute();
        $result_booked_duration = $stmt_booked->get_result();
        
        $booked_duration = $result_booked_duration->fetch_assoc()['TotalDuration'] ?? 0;
        $available_duration = (strtotime($sched_row['EndTime']) - strtotime($sched_row['StartTime'])) / 3600;
        
        if ($booked_duration < $available_duration) {
            $schedule[] = $sched_row;
        }
    }

    // Weekly availability
    $sql_weekly_availability = "SELECT AvailabilityID, Monday, Tuesday, Wednesday, Thursday, 
                               Friday, Saturday, Sunday, StartTime, EndTime 
                               FROM weekly_availability 
                               WHERE NurseID = ?";
    $stmt_weekly = $conn->prepare($sql_weekly_availability);
    $stmt_weekly->bind_param("i", $nurse_id);
    $stmt_weekly->execute();
    $result_weekly_availability = $stmt_weekly->get_result();
    
    while ($weekly_row = $result_weekly_availability->fetch_assoc()) {
        $weekly_availability[] = $weekly_row;
    }

    // Prices
    $sql_prices = "SELECT s.ServiceID, s.Name, ns.Price 
                  FROM nurseservices ns 
                  INNER JOIN service s ON ns.ServiceID = s.ServiceID 
                  WHERE ns.NurseID = ?";
    $stmt_prices = $conn->prepare($sql_prices);
    $stmt_prices->bind_param("i", $nurse_id);
    $stmt_prices->execute();
    $result_prices = $stmt_prices->get_result();
    
    while ($price_row = $result_prices->fetch_assoc()) {
        $prices[] = $price_row;
    }
}


// Fetch care options
$sql_care_needed = "SELECT Name FROM care_needed";
$result_care_needed = $conn->query($sql_care_needed);
$care_options = [];
while ($row = $result_care_needed->fetch_assoc()) {
    $care_options[] = $row['Name'];
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home Nursing - Available Nurses</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">
    <link rel="stylesheet" href="assets/patient.css">
</head>

<body>


    <?php include "logout.php" ?>



    <div class="container-fluid">
        <div class="row">
            <?php include 'sidebar.php'; ?>
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4 main-content">
                <h2 class="h4 mb-4 fw-bold">Available Nurses</h2>
                <div class="row">
                    <div class="col-lg-12">
                        <div class="card shadow mb-4">
                            <div class="card-header py-3 d-flex justify-content-between align-items-center">
                                <h6 class="m-0 fw-bold text-primary">Currently Available Nurses</h6>
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-outline-primary dropdown-toggle" type="button" id="filterDropdown" data-bs-toggle="dropdown">
                                        <?php echo htmlspecialchars($service_filter && $service_filter !== 'All' ? $service_filter : 'Filter by Service'); ?>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end">
                                        <li><a class="dropdown-item" href="?service=All">All Services</a></li>
                                        <li>
                                            <hr class="dropdown-divider">
                                        </li>
                                        <?php foreach ($services as $service): ?>
                                            <li><a class="dropdown-item" href="?service=<?php echo urlencode($service); ?>"><?php echo htmlspecialchars($service); ?></a></li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            </div>
                            <div class="card-body">
                                <?php if ($error): ?>
                                    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                                <?php endif; ?>
                                <?php if (empty($nurses)): ?>
                                    <div class="alert alert-warning text-center">
                                        No nurses are currently available. Please check the database or try a different filter.
                                    </div>
                                <?php else: ?>
                                    <div class="row">
                                        <?php foreach ($nurses as $nurse): ?>
                                            <div class="col-md-4 mb-4">
                                                <div class="card h-100 border-start border-primary border-4" data-nurse-id="<?php echo htmlspecialchars($nurse['NurseID']); ?>">
                                                    <div class="card-body text-center">
                                                        <?php
                                                        $nurse_image = !empty($nurse['image_path'])
                                                            ? $image_base_path . $nurse['image_path']
                                                            : '../nurse/uploads/profile_photos/default.jpg';
                                                        ?>


                                                        <img src="<?php echo htmlspecialchars($nurse_image)  ?>" class="rounded-circle mb-3 " width="130" height="130" alt="Nurse">


                                                        <h5 class="card-title"><?php echo htmlspecialchars($nurse['FullName']); ?></h5>
                                                        <p class="text-muted small"><?php echo htmlspecialchars($nurse['Specialization'] ?? 'General Nurse'); ?></p>
                                                        <div class="mb-3">
                                                            <?php
                                                            $rating = isset($nurse['AvgRating']) && $nurse['AvgRating'] !== null ? round($nurse['AvgRating'], 1) : 0;
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
                                                            <span class="small text-muted ms-muted"><?php echo $rating . ' (' . (isset($nurse['ReviewCount']) ? $nurse['ReviewCount'] : 0) . ')'; ?></span>
                                                        </div>
                                                        <div class="d-grid gap-2">
                                                            <button class="btn btn-sm btn-outline-primary view-profile-btn"
                                                                data-nurse-id="<?php echo htmlspecialchars($nurse['NurseID']); ?>">
                                                                View Profile
                                                            </button>
                                                            <button class="btn btn-sm btn-primary request-service-btn"
                                                                data-nurse-id="<?php echo htmlspecialchars($nurse['NurseID']); ?>"
                                                                data-nurse-name="<?php echo htmlspecialchars($nurse['FullName']); ?>"
                                                                data-services='<?php echo htmlspecialchars(json_encode($nurse['services'])); ?>'
                                                                data-schedules='<?php echo htmlspecialchars(json_encode($nurse['schedules'])); ?>'
                                                                data-weekly-availability='<?php echo htmlspecialchars(json_encode($nurse['weekly_availability'])); ?>'>
                                                                Request Service
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                            </div>
                        <?php endif; ?>
                        </div>
                    </div>
                </div>
        </div>
    </div>
    </main>
    </div>
    </div>

    <!-- Nurse Profile Modal -->

    <div class="modal fade" id="nurseProfileModal" tabindex="-1" role="dialog" aria-labelledby="nurseProfileModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-5" id="nurseProfileModalLabel">Nurse Profile</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <?php if ($selected_nurse): ?>
                        <div class="row">
                            <div class="col-md-4 text-center">
                                <img src="<?php echo htmlspecialchars($selected_nurse['image_path']); ?>" class="rounded-circle mb-3" width="150" height="150" alt="Nurse Photo">

                                <h4><?php echo htmlspecialchars($selected_nurse['FullName']); ?></h4>
                                <p class="text-muted"><?php echo htmlspecialchars($selected_nurse['Specialization'] ?? 'General Nurse'); ?></p>
                                <div class="mb-3">
                                    <?php
                                    $rating = isset($selected_nurse['AvgRating']) && $selected_nurse['AvgRating'] !== null ? round($selected_nurse['AvgRating'], 1) : 0;
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
                                    <small class="text-muted"><?php echo $rating . ' (' . (isset($selected_nurse['ReviewCount']) ? $selected_nurse['ReviewCount'] : 0) . ' reviews)'; ?></small>
                                </div>
                            </div>
                            <div class="col-md-8">
                                <ul class="nav nav-tabs" id="nurseProfileTabs" role="tablist">
                                    <li class="nav-item" role="presentation">
                                        <a class="nav-link active" id="profile-tab" data-bs-toggle="tab" href="#profile" role="tab" aria-controls="profile" aria-selected="true">Details</a>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <a class="nav-link" id="schedule-tab" data-bs-toggle="tab" href="#schedule" role="tab" aria-controls="schedule" aria-selected="false">Schedule</a>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <a class="nav-link" id="certification-tab" data-bs-toggle="tab" href="#certification" role="tab" aria-controls="certification" aria-selected="false">Certifications</a>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <a class="nav-link" id="pricing-tab" data-bs-toggle="tab" href="#pricing" role="tab" aria-controls="pricing" aria-selected="false">Pricing</a>
                                    </li>
                                </ul>
                                <div class="tab-content p-3 border border-top-0 rounded-bottom" id="nurseProfileTabsContent">
                                    <div class="tab-pane fade show active" id="profile" role="tabpanel" aria-labelledby="profile-tab">
                                        <div class="row">
                                            <div class="col-sm-6">
                                                <p><strong>Age:</strong> <?php echo htmlspecialchars($selected_nurse['Age'] ?? 'Unknown'); ?></p>
                                                <p><strong>Gender:</strong> <?php echo htmlspecialchars($selected_nurse['Gender'] ?? 'Unknown'); ?></p>
                                                <p><strong>Languages:</strong> <?php echo htmlspecialchars($selected_nurse['Language'] ?? 'Unknown'); ?></p>
                                            </div>
                                            <div class="col-sm-6">
                                                <p><strong>Location:</strong> <?php echo htmlspecialchars($selected_nurse['Location'] ?? 'Unknown'); ?></p>
                                            </div>
                                        </div>
                                        <hr>
                                        <h6>About</h6>
                                        <p><?php echo htmlspecialchars($selected_nurse['Bio'] ?? 'No bio available.'); ?></p>
                                    </div>
                                    <div class="tab-pane fade" id="schedule" role="tabpanel" aria-labelledby="schedule-tab">
                                        <div class="alert alert-info small">
                                            <i class="fas fa-info-circle me-1"></i>
                                            Green indicates available slots
                                        </div>
                                        <h6>Weekly Availability</h6>
                                        <?php
                                        $has_availability = false;
                                        if (!empty($weekly_availability)) {
                                            foreach ($weekly_availability as $weekly) {
                                                if (
                                                    $weekly['Monday'] == 1 ||
                                                    $weekly['Tuesday'] == 1 ||
                                                    $weekly['Wednesday'] == 1 ||
                                                    $weekly['Thursday'] == 1 ||
                                                    $weekly['Friday'] == 1 ||
                                                    $weekly['Saturday'] == 1 ||
                                                    $weekly['Sunday'] == 1
                                                ) {
                                                    $has_availability = true;
                                                    break;
                                                }
                                            }
                                        }
                                        if (empty($weekly_availability) || !$has_availability): ?>
                                            <p class="text-muted">No weekly availability set.</p>
                                        <?php else: ?>
                                            <ul class="list-group mb-3">
                                                <?php foreach ($weekly_availability as $weekly): ?>
                                                    <?php
                                                    $days = [];
                                                    if ($weekly['Monday'] == 1) $days[] = 'Monday';
                                                    if ($weekly['Tuesday'] == 1) $days[] = 'Tuesday';
                                                    if ($weekly['Wednesday'] == 1) $days[] = 'Wednesday';
                                                    if ($weekly['Thursday'] == 1) $days[] = 'Thursday';
                                                    if ($weekly['Friday'] == 1) $days[] = 'Friday';
                                                    if ($weekly['Saturday'] == 1) $days[] = 'Saturday';
                                                    if ($weekly['Sunday'] == 1) $days[] = 'Sunday';
                                                    if (!empty($days)):
                                                        $time_display = ($weekly['StartTime'] == '00:00:00' && $weekly['EndTime'] == '00:00:00')
                                                            ? '<br>Available 24 hours'
                                                            : htmlspecialchars($weekly['StartTime'] . ' - ' . $weekly['EndTime']);
                                                    ?>
                                                        <li class="list-group-item">
                                                            <?php echo htmlspecialchars(implode(', ', $days)); ?>
                                                            <?php echo $time_display; ?>
                                                        </li>
                                                    <?php endif; ?>
                                                <?php endforeach; ?>
                                            </ul>
                                        <?php endif; ?>


                                        <h6>Daily Schedules</h6>
                                        <?php if (empty($schedule)): ?>
                                            <p class="text-muted">No daily schedules available.</p>
                                        <?php else: ?>
                                            <ul class="list-group">
                                                <?php foreach ($schedule as $slot): ?>
                                                    <li class="list-group-item">
                                                        <strong><?php echo htmlspecialchars($slot['Date']); ?>:</strong>
                                                        <?php echo htmlspecialchars($slot['StartTime'] . ' - ' . $slot['EndTime']); ?>
                                                        <?php if ($slot['Notes']): ?>
                                                            <small class="text-muted">(<?php echo htmlspecialchars($slot['Notes']); ?>)</small>
                                                        <?php endif; ?>
                                                    </li>
                                                <?php endforeach; ?>
                                            </ul>
                                        <?php endif; ?>
                                    </div>







                                    <div class="tab-pane fade" id="certification" role="tabpanel" aria-labelledby="certification-tab">
                                        <?php if (empty($certifications)): ?>
                                            <p class="text-muted">No certifications available.</p>
                                        <?php else: ?>
                                            <div class="row">
                                                <?php foreach ($certifications as $cert): ?>
                                                    <div class="col-md-6 mb-3">
                                                        <div class="card h-100">
                                                            <div class="card-body">
                                                                <h6 class="card-title"><?php echo htmlspecialchars($cert['Name']); ?></h6>
                                                                <?php if ($cert['Image']): ?>
                                                                    <!-- <img src="" class="img-fluid mb-2" alt="Certification" width="100"> -->

                                                                <?php endif; ?>
                                                            </div>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>






                                    <div class="tab-pane fade" id="pricing" role="tabpanel" aria-labelledby="pricing-tab">
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
                        <button class="btn btn-primary request-service-btn"
                            data-nurse-id="<?php echo htmlspecialchars($selected_nurse['NurseID']); ?>"
                            data-nurse-name="<?php echo htmlspecialchars($selected_nurse['FullName']); ?>"
                            data-services='<?php echo htmlspecialchars(json_encode($prices)); ?>'
                            data-schedules='<?php echo htmlspecialchars(json_encode($schedule)); ?>'
                            data-weekly-availability='<?php echo htmlspecialchars(json_encode($weekly_availability)); ?>'>
                            Request This Nurse
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <!-- nurse profile modal2 -->





    <!-- Service Request Modal -->
    <div class="modal fade" id="serviceRequestModal" tabindex="-1" role="dialog" aria-labelledby="serviceRequestModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="serviceRequestModalLabel">Request Service for Nurse</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="alert alert-info">You are requesting a service from <strong id="selectedNurseName"><?php echo htmlspecialchars($nurse_name); ?></strong>. Please fill in the details below.</p>
                    <form id="modalServiceRequestForm" action="nurses_available.php" method="POST">
                        <input type="hidden" name="nurse_id" id="modalNurseId">
                        <input type="hidden" name="patient_id" value="<?php echo htmlspecialchars($patient_id); ?>">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Service Type <span class="text-danger">*</span></label>
                            <select class="form-select" name="service_id" id="modalServiceId" required>
                                <option value="">Select a service</option>
                            </select>
                            <div class="invalid-feedback">Please select a service type.</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Preferred Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" name="request_date" id="modalRequestDate" required>
                            <div class="invalid-feedback">Please select a valid date.</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Preferred Time</label>
                            <input type="time" class="form-control" name="preferred_time" id="modalPreferredTime">
                            <div class="invalid-feedback">Please select a valid time.</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Type of Care Needed <span class="text-danger">*</span></label>
                            <div class="row">
                                <?php foreach ($care_options as $index => $care): ?>
                                    <div class="col-md-6">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="care_needed[]" value="<?php echo htmlspecialchars($care); ?>" id="modalCare<?php echo $index; ?>">
                                            <label class="form-check-label" for="modalCare<?php echo $index; ?>"><?php echo htmlspecialchars($care); ?></label>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <div class="invalid-feedback" id="careNeededFeedback">Please select at least one type of care.</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Service Duration (Hours) <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" name="duration" id="modalDuration" min="1" max="24" required placeholder="Enter number of hours">
                            <div class="invalid-feedback">Please enter a valid duration between 1 and 24 hours.</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Address for Service <span class="text-danger">*</span></label>
                            <div class="row">
                                <div class="col-md-6 mb-2">
                                    <label class="form-label">Street</label>
                                    <input type="text" class="form-control" name="address_street" id="modalStreet" placeholder="Street (e.g., 123 Main St)" value="<?php echo htmlspecialchars($patient_address['Street'] ?? ''); ?>" required>
                                    <div class="invalid-feedback">Please enter the street address.</div>
                                </div>
                                <div class="col-md-6 mb-2">
                                    <label class="form-label">Building</label>
                                    <input type="text" class="form-control" name="address_building" id="modalBuilding" placeholder="Building (e.g., Apt 4B)" value="<?php echo htmlspecialchars($patient_address['Building'] ?? ''); ?>" required>
                                    <div class="invalid-feedback">Please enter the building details.</div>
                                </div>
                                <div class="col-12 mb-2">
                                    <label class="form-label">City</label>
                                    <input type="text" class="form-control" name="city" id="modalCity" placeholder="City (e.g., New York)" value="<?php echo htmlspecialchars($patient_address['City'] ?? ''); ?>" required>
                                    <div class="invalid-feedback">Please enter the city.</div>
                                </div>
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-primary mt-2 fw-bold" id="useCurrentLocation">
                                <i class="fas fa-location-arrow me-1"></i> Use My Current Location
                            </button>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">Submit Request</button>
                        </div>
                </div>
            </div>
        </div>
    </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        var patientAddress = {
            city: '<?php echo htmlspecialchars($patient_address['City'] ?? ''); ?>',
            street: '<?php echo htmlspecialchars($patient_address['Street'] ?? ''); ?>',
            building: '<?php echo htmlspecialchars($patient_address['Building'] ?? ''); ?>'
        };

        document.addEventListener('DOMContentLoaded', function() {
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.has('nurse_id') && urlParams.get('nurse_id') !== '') {
                const profileModal = new bootstrap.Modal(document.getElementById('nurseProfileModal'), {
                    backdrop: 'static',
                    keyboard: false
                });
                profileModal.show();
            }

            const viewProfileButtons = document.querySelectorAll('.view-profile-btn');
            viewProfileButtons.forEach(button => {
                button.addEventListener('click', function(event) {
                    event.preventDefault();
                    const nurseId = this.getAttribute('data-nurse-id');
                    const serviceFilter = urlParams.get('service') || 'All';
                    const url = `${window.location.pathname}?nurse_id=${nurseId}&service=${encodeURIComponent(serviceFilter)}`;
                    window.location.href = url;
                });
            });

            const requestServiceButtons = document.querySelectorAll('.request-service-btn');
            requestServiceButtons.forEach(button => {
                button.addEventListener('click', function(event) {
                    event.preventDefault();
                    const nurseId = this.getAttribute('data-nurse-id');
                    const nurseName = this.getAttribute('data-nurse-name');
                    const services = JSON.parse(this.getAttribute('data-services') || '[]');
                    const modalElement = document.getElementById('serviceRequestModal');
                    const form = document.getElementById('modalServiceRequestForm');
                    const modalTitle = document.getElementById('serviceRequestModalLabel');
                    const selectedNurseName = document.getElementById('selectedNurseName');

                    if (!modalElement || !form || !modalTitle || !selectedNurseName) return;

                    modalTitle.textContent = `Request Service for ${nurseName}`;
                    selectedNurseName.textContent = nurseName;
                    document.getElementById('modalNurseId').value = nurseId;
                    form.reset();
                    document.getElementById('modalCity').value = patientAddress.city || '';
                    document.getElementById('modalStreet').value = patientAddress.street || '';
                    document.getElementById('modalBuilding').value = patientAddress.building || '';

                    const serviceSelect = document.getElementById('modalServiceId');
                    serviceSelect.innerHTML = '<option value="">Select a service</option>';
                    if (services.length === 0) {
                        serviceSelect.innerHTML += '<option value="">No services available</option>';
                    } else {
                        services.forEach(service => {
                            const option = document.createElement('option');
                            option.value = service.ServiceID;
                            option.textContent = service.Name;
                            serviceSelect.appendChild(option);
                        });
                    }

                    const modal = new bootstrap.Modal(modalElement, {
                        backdrop: 'static',
                        keyboard: false
                    });
                    modal.show();
                });
            });

            const useCurrentLocationBtn = document.getElementById('useCurrentLocationBtn');
            if (useCurrentLocationBtn) {
                useCurrentLocationBtn.addEventListener('click', function(event) {
                    event.preventDefault();
                    if (patientAddress.city || patientAddress.street || patientAddress.building) {
                        document.getElementById('modalCity').value = patientAddress.city;
                        document.getElementById('modalStreet').value = patientAddress.street;
                        document.getElementById('modalBuilding').value = patientAddress.building;
                    } else {
                        alert('No address registered for this patient. Please enter the address manually.');
                    }
                });
            }
        });
    </script>
</body>

</html>