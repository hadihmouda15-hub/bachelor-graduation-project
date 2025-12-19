<?php
require '../connect.php';
require 'algorithm.php' ;

session_start();

if (isset($_POST['confirm_logout'])) {
    session_destroy();
    unset($_SESSION['email']);
    unset($_SESSION['role']);
    unset($_SESSION['full_name']);
    unset($_SESSION['patient_id']);
    session_destroy();
    header("Location: ../homepage/mainpage.php");
    exit();
}


// Check database connection
if (!$conn) {
    $error = "Database connection failed.";
    die($error);
}

// Fetch patient address (hardcoded patient_id = 1)
$patient_id = $_SESSION['patient_id'] ;



$sql_patient_address = "SELECT a.City, a.Street, a.Building, a.Latitude, a.Longitude
                       FROM address a
                       INNER JOIN user u ON u.AddressID = a.AddressID
                       INNER JOIN patient p ON p.UserID = u.UserID
                       WHERE p.PatientID = '$patient_id'";
$result_patient_address = $conn->query($sql_patient_address);
$patient_address = $result_patient_address && $result_patient_address->num_rows > 0 ? $result_patient_address->fetch_assoc() : [];

// Fetch all services
$sql_services = "SELECT ServiceID, Name FROM service WHERE ServiceID > 0";
$result_services = $conn->query($sql_services);
$services = [];
while ($row = $result_services->fetch_assoc()) {
    $services[] = $row;
}

// Fetch all care types
$sql_care_needed = "SELECT CareID, Name FROM care_needed";
$result_care_needed = $conn->query($sql_care_needed);
$care_options = [];
while ($row = $result_care_needed->fetch_assoc()) {
    $care_options[] = $row;
}

// Initialize variables
$nurses = [];
$selected_nurse = null;
$certifications = [];
$schedule = [];
$prices = [];
$image_base_path = '../nurse/';
$show_nurse_list = false;
$error = '';
$form_data = [];
$nb_nurse_needed = 1;
$selected_nurse_ids = [];
$selected_nurse_names = [];

function getFormData($post) {
    $duration = '';
    if (isset($post['duration'])) {
        if ($post['duration'] === 'Full day') {
            $duration = '24';
        } elseif ($post['duration'] === 'Half day') {
            $duration = '12';
        } elseif ($post['duration'] === 'Custom' && isset($post['custom_duration']) && is_numeric($post['custom_duration']) && $post['custom_duration'] > 0) {
            $duration = $post['custom_duration'];
        }
    } elseif (isset($post['form_duration'])) {
        $duration = $post['form_duration'];
    }

    return [
        'service_id' => $post['service_id'] ?? ($post['form_service_id'] ?? ''),
        'date' => $post['date'] ?? ($post['form_date'] ?? ''),
        'time' => $post['time'] ?? ($post['form_time'] ?? ''),
        'number_of_nurses' => $post['number_of_nurses'] ?? ($post['form_number_of_nurses'] ?? ''),
        'gender' => $post['gender'] ?? ($post['form_gender'] ?? ''),
        'age_type' => $post['age_type'] ?? ($post['form_age_type'] ?? ''),
        'care_needed' => isset($post['care_needed']) ? implode(', ', $post['care_needed']) : ($post['form_care_needed'] ?? ''),
        'address_street' => $post['address_street'] ?? ($post['form_address_street'] ?? ''),
        'address_building' => $post['address_building'] ?? ($post['form_address_building'] ?? ''),
        'city' => $post['city'] ?? ($post['form_city'] ?? ''),
        'MedicalCondition' => $post['MedicalCondition'] ?? ($post['form_MedicalCondition'] ?? ''),
        'duration' => $duration,
        'instructions' => $post['instructions'] ?? ($post['form_instructions'] ?? ''),
        'request_type' => $post['request_type'] ?? ($post['form_request_type'] ?? '')
    ];
}

function insertRequest($conn, $form_data, $patient_id, $patient_address, $nurse_id = null) {
    global $error;

    $address_id = null;
    if (
        !empty($patient_address) &&
        $form_data['city'] === ($patient_address['City'] ?? '') &&
        $form_data['address_street'] === ($patient_address['Street'] ?? '') &&
        $form_data['address_building'] === ($patient_address['Building'] ?? '')
    ) {
        $sql_get_address_id = "SELECT AddressID FROM address WHERE City = '{$form_data['city']}' AND Street = '{$form_data['address_street']}' AND Building = '{$form_data['address_building']}'";
        $result_address = $conn->query($sql_get_address_id);
        if ($result_address && $result_address->num_rows > 0) {
            $address_id = $result_address->fetch_assoc()['AddressID'];
        }
    }

    if (!$address_id) {
        $sql_address = "INSERT INTO address (City, Street, Building) VALUES ('{$form_data['city']}', '{$form_data['address_street']}', '{$form_data['address_building']}')";
        if (!$conn->query($sql_address)) {
            $error = "Error saving address: " . $conn->error;
            return false;
        }
        $address_id = $conn->insert_id;
    }

    $ispublic = ($form_data['request_type'] === 'post') ? 1 : 0;
    $nurse_id_sql = $nurse_id ? "'$nurse_id'" : 'NULL';

    $sql_request = "INSERT INTO request (
        NurseGender, AgeType, Date, Time, Type, NumberOfNurses, SpecialInstructions, 
        MedicalCondition, Duration, NurseStatus, PatientStatus, RequestStatus, 
        ServiceFeePercentage, PatientID, NurseID, AddressID, ispublic
    ) VALUES (
        '{$form_data['gender']}', '{$form_data['age_type']}', '{$form_data['date']}', '{$form_data['time']}', 
        '{$form_data['service_id']}', 
        '{$form_data['number_of_nurses']}', '{$form_data['instructions']}', '{$form_data['MedicalCondition']}', 
        '{$form_data['duration']}', 'pending', 'completed', 'pending', 10.00, 
        '$patient_id', $nurse_id_sql, '$address_id', '$ispublic'
    )";
    if (!$conn->query($sql_request)) {
        $error = "Error submitting request: " . $conn->error;
        return false;
    }
    $request_id = $conn->insert_id;

    if (empty($form_data['care_needed'])) {
        $error = "At least one type of care must be selected.";
        return false;
    }

    foreach (explode(', ', $form_data['care_needed']) as $care_name) {
        $sql_care_id = "SELECT CareID FROM care_needed WHERE Name = '$care_name'";
        $result_care_id = $conn->query($sql_care_id);
        if ($result_care_id && $result_care_id->num_rows > 0) {
            $care_id = $result_care_id->fetch_assoc()['CareID'];
            $sql_care_link = "INSERT INTO request_care_needed (RequestID, CareID) VALUES ('$request_id', '$care_id')";
            if (!$conn->query($sql_care_link)) {
                $error = "Error linking care needed: " . $conn->error;
                return false;
            }
        } else {
            $error = "Invalid care type: $care_name";
            return false;
        }
    }

    return true;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $form_data = getFormData($_POST);

    $nb_nurse_needed = isset($form_data['number_of_nurses']) && is_numeric($form_data['number_of_nurses']) 
        ? intval($form_data['number_of_nurses']) 
        : 1;

    if (isset($_POST['selected_nurse_ids'])) {
        $selected_nurse_ids = array_filter(explode(',', $_POST['selected_nurse_ids']), 'is_numeric');
    }

    if (isset($_POST['submit_form'])) {
        $required_fields = ['service_id', 'date', 'time', 'number_of_nurses', 'address_street', 'address_building', 'city', 'request_type'];
        $missing_field = false;
        foreach ($required_fields as $field) {
            if (empty($form_data[$field])) {
                $error = "Missing required field: $field";
                $missing_field = true;
                break;
            }
        }

        if (!$missing_field && !empty($form_data['date'])) {
            $today = date('Y-m-d');
            if ($form_data['date'] < $today) {
                $error = "Selected date cannot be in the past.";
                $missing_field = true;
            }
        }

        if (!$missing_field && !empty($form_data['care_needed'])) {
            if ($form_data['request_type'] === 'post') {
                if (insertRequest($conn, $form_data, $patient_id, $patient_address)) {
                    header("Location: my_requests.php");
                    exit;
                }
            } else {
                $patient_lat = $patient_address['Latitude'] ?? 0;
                $patient_lon = $patient_address['Longitude'] ?? 0;
                $nurses = selectBestNurses($conn, $form_data['service_id'], $form_data['gender'], $form_data['age_type'], $patient_lat, $patient_lon);
                $show_nurse_list = true;
            }
        } else {
            $error = $error ?: "Please select at least one type of care.";
        }
    }

    if (isset($_POST['select_nurse'])) {
        $nurse_id = $_POST['nurse_id'];
        $patient_lat = $patient_address['Latitude'] ?? 0;
        $patient_lon = $patient_address['Longitude'] ?? 0;
        $nurses = selectBestNurses($conn, $form_data['service_id'], $form_data['gender'], $form_data['age_type'], $patient_lat, $patient_lon);
        $show_nurse_list = true;

        $sql_nurse = "SELECT n.NurseID, u.FullName, n.Bio, na.Specialization, na.Gender, na.Language, u.DateOfBirth, a.City, a.Street, AVG(r.Rating) AS AvgRating, COUNT(r.RID) AS ReviewCount , n.image_path
                      FROM nurse n
                      INNER JOIN user u ON n.UserID = u.UserID
                      INNER JOIN nurseapplication na ON n.NAID = na.NAID
                      INNER JOIN address a ON u.AddressID = a.AddressID
                      LEFT JOIN rating r ON n.NurseID = r.NurseID
                      WHERE n.NurseID = '$nurse_id'
                      GROUP BY n.NurseID";
        $result_nurse = $conn->query($sql_nurse);
        if ($result_nurse && $result_nurse->num_rows > 0) {
            $row = $result_nurse->fetch_assoc();
            $dob = new DateTime($row['DateOfBirth']);
            $now = new DateTime();
            $row['Age'] = $now->diff($dob)->y;
            $row['Location'] = ($row['City'] && $row['Street']) ? $row['City'] . ', ' . $row['Street'] : ($row['City'] ?: 'Unknown');

            $row['image_path'] = !empty($row['image_path']) ? $image_base_path . $row['image_path'] 
                : '../nurse/uploads/profile_photos/default.jpg';
            $selected_nurse = $row;
        }

        $sql_certs = "SELECT Name, Image, Comment FROM certification WHERE NurseID = '$nurse_id' AND Status = 'approved'";
        $result_certs = $conn->query($sql_certs);
        while ($cert_row = $result_certs->fetch_assoc()) {
            $cert_row['Image'] = !empty($cert_row['Image']) && file_exists($_SERVER['DOCUMENT_ROOT'] . $image_base_path . $cert_row['Image']) 
                ? $image_base_path . $cert_row['Image'] 
                : '/patient1/images/default_cert.jpg';
            $certifications[] = $cert_row;
        }

        $sql_schedule = "SELECT ScheduleID, Date, StartTime, EndTime, Notes FROM schedule WHERE NurseID = '$nurse_id' AND Status = 'available'";
        $result_schedule = $conn->query($sql_schedule);
        while ($sched_row = $result_schedule->fetch_assoc()) {
            $schedule[] = $sched_row;
        }

        $sql_prices = "SELECT s.ServiceID, s.Name, ns.Price FROM nurseservices ns INNER JOIN service s ON ns.ServiceID = s.ServiceID WHERE ns.NurseID = '$nurse_id'";
        $result_prices = $conn->query($sql_prices);
        while ($price_row = $result_prices->fetch_assoc()) {
            $prices[] = $price_row;
        }
    }

    if (isset($_POST['select_nurse_for_request'])) {
        $nurse_id = $_POST['nurse_id'];
        if (!in_array($nurse_id, $selected_nurse_ids) && count($selected_nurse_ids) < $nb_nurse_needed) {
            $selected_nurse_ids[] = $nurse_id;
        }
        $patient_lat = $patient_address['Latitude'] ?? 0;
        $patient_lon = $patient_address['Longitude'] ?? 0;
        $nurses = selectBestNurses($conn, $form_data['service_id'], $form_data['gender'], $form_data['age_type'], $patient_lat, $patient_lon);
        $show_nurse_list = true;
    }

    if (isset($_POST['remove_nurse'])) {
        $remove_nurse_id = $_POST['remove_nurse_id'];
        $selected_nurse_ids = array_diff($selected_nurse_ids, [$remove_nurse_id]);
        $patient_lat = $patient_address['Latitude'] ?? 0;
        $patient_lon = $patient_address['Longitude'] ?? 0;
        $nurses = selectBestNurses($conn, $form_data['service_id'], $form_data['gender'], $form_data['age_type'], $patient_lat, $patient_lon);
        $show_nurse_list = true;
    }

    if (isset($_POST['cancel'])) {
        $show_nurse_list = false;
        $form_data = [];
        $selected_nurse_ids = [];
    }

    if (isset($_POST['confirm_request']) && !empty($selected_nurse_ids)) {
        $success = true;
        $conn->query("BEGIN");
        try {
            foreach ($selected_nurse_ids as $nurse_id) {
                if (!insertRequest($conn, $form_data, $patient_id, $patient_address, $nurse_id)) {
                    $success = false;
                    $error = "Failed to insert request for nurse ID: $nurse_id. Error: " . $conn->error;
                    break;
                }
            }
if ($success) {
    $conn->commit();
    if ($is_public == 1) {
        header("Location: manage_posted_requests.php?success=1");
    } else {
        header("Location: my_requests.php?success=1");
    }
    exit;
}
        } catch (Exception $e) {
            $conn->rollback();
            $error = "Transaction failed: " . htmlspecialchars($e->getMessage());
            $show_nurse_list = true;
        }
    }

    if (!empty($selected_nurse_ids)) {
        $nurse_ids_sql = implode(',', array_map('intval', $selected_nurse_ids));
        $sql_nurse_names = "SELECT n.NurseID, u.FullName , n.image_path
                            FROM nurse n
                            INNER JOIN user u ON n.UserID = u.UserID
                            WHERE n.NurseID IN ($nurse_ids_sql)";
        $result_nurse_names = $conn->query($sql_nurse_names);
        while ($row = $result_nurse_names->fetch_assoc()) {
            $selected_nurse_names[$row['NurseID']] = $row['FullName'];
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home Nursing - Request Service</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">
    <link rel="stylesheet" href="assets/patient.css">
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <?php include 'sidebar.php'; ?>
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4 main-content">
                <h2 class="h4 mb-4 fw-bold">Request Service</h2>
                <div class="row">
                    <div class="col-lg-12">
                        <div class="card shadow mb-4">
                            <div class="card-header py-3">
                                <h6 class="m-0 fw-bold text-primary"><?php echo $show_nurse_list ? 'Select a Nurse' : 'Service Request Form'; ?></h6>
                            </div>
                            <div class="card-body">
                                <?php if ($error): ?>
                                    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                                <?php endif; ?>

                                <?php if ($show_nurse_list): ?>
                                    <form method="POST" action="request_service.php">
                                        <button type="submit" class="btn btn-danger mb-3" name="cancel">Cancel</button>
                                        <input type="hidden" name="selected_nurse_ids" value="<?php echo htmlspecialchars(implode(',', $selected_nurse_ids)); ?>">
                                    </form>
                                    <p><strong>Nurses Selected: <?php echo count($selected_nurse_ids); ?> / <?php echo htmlspecialchars($nb_nurse_needed); ?></strong></p>
                                    <?php if (empty($nurses)): ?>
                                        <div class="alert alert-warning text-center">
                                            No nurses are currently available for your criteria. Please try different preferences.
                                        </div>
                                    <?php else: ?>
                                        <div class="row">
                                            <?php foreach ($nurses as $nurse): ?>
                                                <div class="col-md-4 mb-4">
                                                    <div class="card h-100 border-start border-primary border-4" data-nurse-id="<?php echo htmlspecialchars($nurse['NurseID']); ?>">
                                                        <div class="card-body text-center">



                                                            <?php
                                                            $nurse_image = !empty($nurse['image_path']) 
                                                                ? "../nurse/" . $nurse['image_path'] 
                                                                : '../nurse/uploads/profile_photos/default.jpg';
                                                            ?>


                                                            
                                                            <img src="<?php echo htmlspecialchars($nurse_image); ?>" class="rounded-circle mb-3" width="130" height="130" alt="Nurse">



                                                             



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
                                                                <span class="small text-muted ms-1"><?php echo $rating; ?></span>
                                                            </div>
                                                            <div class="d-grid gap-2">
                                                                <?php if (!in_array($nurse['NurseID'], $selected_nurse_ids)): ?>
                                                                    <form action="request_service.php" method="POST">
                                                                        <input type="hidden" name="nurse_id" value="<?php echo htmlspecialchars($nurse['NurseID']); ?>">
                                                                        <input type="hidden" name="selected_nurse_ids" value="<?php echo htmlspecialchars(implode(',', $selected_nurse_ids)); ?>">
                                                                        <input type="hidden" name="form_service_id" value="<?php echo htmlspecialchars($form_data['service_id']); ?>">
                                                                        <input type="hidden" name="form_date" value="<?php echo htmlspecialchars($form_data['date']); ?>">
                                                                        <input type="hidden" name="form_time" value="<?php echo htmlspecialchars($form_data['time']); ?>">
                                                                        <input type="hidden" name="form_number_of_nurses" value="<?php echo htmlspecialchars($form_data['number_of_nurses']); ?>">
                                                                        <input type="hidden" name="form_gender" value="<?php echo htmlspecialchars($form_data['gender']); ?>">
                                                                        <input type="hidden" name="form_age_type" value="<?php echo htmlspecialchars($form_data['age_type']); ?>">
                                                                        <input type="hidden" name="form_care_needed" value="<?php echo htmlspecialchars($form_data['care_needed']); ?>">
                                                                        <input type="hidden" name="form_address_street" value="<?php echo htmlspecialchars($form_data['address_street']); ?>">
                                                                        <input type="hidden" name="form_address_building" value="<?php echo htmlspecialchars($form_data['address_building']); ?>">
                                                                        <input type="hidden" name="form_city" value="<?php echo htmlspecialchars($form_data['city']); ?>">
                                                                        <input type="hidden" name="form_MedicalCondition" value="<?php echo htmlspecialchars($form_data['MedicalCondition']); ?>">
                                                                        <input type="hidden" name="form_duration" value="<?php echo htmlspecialchars($form_data['duration']); ?>">
                                                                        <input type="hidden" name="form_instructions" value="<?php echo htmlspecialchars($form_data['instructions']); ?>">
                                                                        <input type="hidden" name="form_request_type" value="<?php echo htmlspecialchars($form_data['request_type']); ?>">
                                                                        <button type="submit" class="btn btn-sm btn-outline-primary" name="select_nurse">View Profile</button>
                                                                    </form>
                                                                <?php endif; ?>
                                                                <?php if (in_array($nurse['NurseID'], $selected_nurse_ids)): ?>
                                                                    <form action="request_service.php" method="POST">
                                                                        <input type="hidden" name="remove_nurse_id" value="<?php echo htmlspecialchars($nurse['NurseID']); ?>">
                                                                        <input type="hidden" name="selected_nurse_ids" value="<?php echo htmlspecialchars(implode(',', $selected_nurse_ids)); ?>">
                                                                        <input type="hidden" name="form_service_id" value="<?php echo htmlspecialchars($form_data['service_id']); ?>">
                                                                        <input type="hidden" name="form_date" value="<?php echo htmlspecialchars($form_data['date']); ?>">
                                                                        <input type="hidden" name="form_time" value="<?php echo htmlspecialchars($form_data['time']); ?>">
                                                                        <input type="hidden" name="form_number_of_nurses" value="<?php echo htmlspecialchars($form_data['number_of_nurses']); ?>">
                                                                        <input type="hidden" name="form_gender" value="<?php echo htmlspecialchars($form_data['gender']); ?>">
                                                                        <input type="hidden" name="form_age_type" value="<?php echo htmlspecialchars($form_data['age_type']); ?>">
                                                                        <input type="hidden" name="form_care_needed" value="<?php echo htmlspecialchars($form_data['care_needed']); ?>">
                                                                        <input type="hidden" name="form_address_street" value="<?php echo htmlspecialchars($form_data['address_street']); ?>">
                                                                        <input type="hidden" name="form_address_building" value="<?php echo htmlspecialchars($form_data['address_building']); ?>">
                                                                        <input type="hidden" name="form_city" value="<?php echo htmlspecialchars($form_data['city']); ?>">
                                                                        <input type="hidden" name="form_MedicalCondition" value="<?php echo htmlspecialchars($form_data['MedicalCondition']); ?>">
                                                                        <input type="hidden" name="form_duration" value="<?php echo htmlspecialchars($form_data['duration']); ?>">
                                                                        <input type="hidden" name="form_instructions" value="<?php echo htmlspecialchars($form_data['instructions']); ?>">
                                                                        <input type="hidden" name="form_request_type" value="<?php echo htmlspecialchars($form_data['request_type']); ?>">
                                                                        <button type="submit" class="btn btn-sm btn-outline-danger" name="remove_nurse">Remove</button>
                                                                    </form>
                                                                <?php else: ?>
                                                                    <form action="request_service.php" method="POST">
                                                                        <input type="hidden" name="nurse_id" value="<?php echo htmlspecialchars($nurse['NurseID']); ?>">
                                                                        <input type="hidden" name="selected_nurse_ids" value="<?php echo htmlspecialchars(implode(',', $selected_nurse_ids)); ?>">
                                                                        <input type="hidden" name="form_service_id" value="<?php echo htmlspecialchars($form_data['service_id']); ?>">
                                                                        <input type="hidden" name="form_date" value="<?php echo htmlspecialchars($form_data['date']); ?>">
                                                                        <input type="hidden" name="form_time" value="<?php echo htmlspecialchars($form_data['time']); ?>">
                                                                        <input type="hidden" name="form_number_of_nurses" value="<?php echo htmlspecialchars($form_data['number_of_nurses']); ?>">
                                                                        <input type="hidden" name="form_gender" value="<?php echo htmlspecialchars($form_data['gender']); ?>">
                                                                        <input type="hidden" name="form_age_type" value="<?php echo htmlspecialchars($form_data['age_type']); ?>">
                                                                        <input type="hidden" name="form_care_needed" value="<?php echo htmlspecialchars($form_data['care_needed']); ?>">
                                                                        <input type="hidden" name="form_address_street" value="<?php echo htmlspecialchars($form_data['address_street']); ?>">
                                                                        <input type="hidden" name="form_address_building" value="<?php echo htmlspecialchars($form_data['address_building']); ?>">
                                                                        <input type="hidden" name="form_city" value="<?php echo htmlspecialchars($form_data['city']); ?>">
                                                                        <input type="hidden" name="form_MedicalCondition" value="<?php echo htmlspecialchars($form_data['MedicalCondition']); ?>">
                                                                        <input type="hidden" name="form_duration" value="<?php echo htmlspecialchars($form_data['duration']); ?>">
                                                                        <input type="hidden" name="form_instructions" value="<?php echo htmlspecialchars($form_data['instructions']); ?>">
                                                                        <input type="hidden" name="form_request_type" value="<?php echo htmlspecialchars($form_data['request_type']); ?>">
                                                                        <button type="submit" class="btn btn-sm btn-primary" name="select_nurse_for_request" <?php echo count($selected_nurse_ids) >= $nb_nurse_needed ? 'disabled' : ''; ?>>Select</button>
                                                                    </form>
                                                                <?php endif; ?>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                    <?php if (count($selected_nurse_ids) >= 1): ?>
                                        <button type="button" id="confirmSelectionButton" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#confirmSelectionModal">Confirm Selection</button>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <form id="serviceRequestForm" method="POST" action="request_service.php" name="submit_form">
                                        <div class="step active" id="step1">
                                            <div class="mb-3">
                                                <label class="form-label fw-bold">Service Type <span class="text-danger">*</span></label>
                                                <select class="form-select" name="service_id" id="service_id" required>
                                                    <option value="">Select a service</option>
                                                    <?php foreach ($services as $service): ?>
                                                        <option value="<?php echo $service['ServiceID']; ?>" <?php echo isset($form_data['service_id']) && $form_data['service_id'] == $service['ServiceID'] ? 'selected' : ''; ?>>
                                                            <?php echo htmlspecialchars($service['Name']); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                                <div class="invalid-feedback">Please select a service type.</div>
                                            </div>
                                            <div class="row mb-3">
                                                <div class="col-md-6">
                                                    <label class="form-label fw-bold">Preferred Date <span class="text-danger">*</span></label>
                                                    <input type="date" class="form-control" name="date" id="date" min="<?php echo date('Y-m-d'); ?>" value="<?php echo isset($form_data['date']) ? htmlspecialchars($form_data['date']) : ''; ?>" required>
                                                    <div class="invalid-feedback">Please select a date that is today or in the future.</div>
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label fw-bold">Preferred Time <span class="text-danger">*</span></label>
                                                    <input type="time" class="form-control" name="time" id="time" value="<?php echo isset($form_data['time']) ? htmlspecialchars($form_data['time']) : ''; ?>" required>
                                                    <div class="invalid-feedback">Please select a preferred time.</div>
                                                </div>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label fw-bold">Number of Nurses <span class="text-danger">*</span></label>
                                                <input type="number" class="form-control" name="number_of_nurses" id="number_of_nurses" min="1" value="<?php echo isset($form_data['number_of_nurses']) ? htmlspecialchars($form_data['number_of_nurses']) : '1'; ?>" required>
                                                <div class="invalid-feedback">Please enter a valid number of nurses (at least 1).</div>
                                            </div>
                                            <div class="text-end">
                                                <button type="button" class="btn btn-primary next-step" id="nextStep1">Next</button>
                                            </div>
                                        </div>
                                        <div class="step" id="step2">
                                            <div class="mb-3">
                                                <label class="form-label fw-bold">Preferred Nurse Gender</label>
                                                <select class="form-select" name="gender" id="gender">
                                                    <option value="No Preference" <?php echo isset($form_data['gender']) && $form_data['gender'] == 'No Preference' ? 'selected' : ''; ?>>No Preference</option>
                                                    <option value="Male" <?php echo isset($form_data['gender']) && $form_data['gender'] == 'Male' ? 'selected' : ''; ?>>Male</option>
                                                    <option value="Female" <?php echo isset($form_data['gender']) && $form_data['gender'] == 'Female' ? 'selected' : ''; ?>>Female</option>
                                                </select>
                                                <div class="invalid-feedback">Please select a gender.</div>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label fw-bold">Preferred Nurse Age Type</label>
                                                <select class="form-select" name="age_type" id="age_type">
                                                    <option value="No Preference" <?php echo isset($form_data['age_type']) && $form_data['age_type'] == 'No Preference' ? 'selected' : ''; ?>>No Preference</option>
                                                    <option value="Adult" <?php echo isset($form_data['age_type']) && $form_data['age_type'] == 'Adult' ? 'selected' : ''; ?>>Adult (18-40 years)</option>
                                                    <option value="Mature" <?php echo isset($form_data['age_type']) && $form_data['age_type'] == 'Mature' ? 'selected' : ''; ?>>Mature (40-60 years)</option>
                                                </select>
                                                <div class="invalid-feedback">Please select a preferred nurse age type.</div>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label fw-bold">Type of Care Needed <span class="text-danger">*</span></label>
                                                <?php
                                                $care_needed_array = isset($form_data['care_needed']) ? explode(', ', $form_data['care_needed']) : [];
                                                foreach ($care_options as $option):
                                                    $option_id = str_replace(' ', '', $option['Name']);
                                                ?>
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" name="care_needed[]" value="<?php echo htmlspecialchars($option['Name']); ?>" id="<?php echo htmlspecialchars($option_id); ?>" <?php echo in_array($option['Name'], $care_needed_array) ? 'checked' : ''; ?>>
                                                        <label class="form-check-label" for="<?php echo htmlspecialchars($option_id); ?>"><?php echo htmlspecialchars($option['Name']); ?></label>
                                                    </div>
                                                <?php endforeach; ?>
                                                <div class="invalid-feedback" id="careNeededFeedback">Please select at least one type of care.</div>
                                            </div>
                                            <div class="text-end">
                                                <button type="button" class="btn btn-secondary me-2 prev-step">Back</button>
                                                <button type="button" class="btn btn-primary next-step">Next</button>
                                            </div>
                                        </div>
                                        <div class="step" id="step3">
                                            <div class="mb-3">
                                                <label class="form-label fw-bold">Address for Service</label>
                                                <div class="row">
                                                    <div class="col-md-6 mb-2">
                                                        <label class="form-label">Street <span class="text-danger">*</span></label>
                                                        <input type="text" class="form-control" id="addressStreet" name="address_street" placeholder="Street (e.g., 123 Main St)" value="<?php echo isset($form_data['address_street']) ? htmlspecialchars($form_data['address_street']) : ''; ?>" required>
                                                        <div class="invalid-feedback">Please enter the street address.</div>
                                                    </div>
                                                    <div class="col-md-6 mb-2">
                                                        <label class="form-label">Building <span class="text-danger">*</span></label>
                                                        <input type="text" class="form-control" id="addressBuilding" name="address_building" placeholder="Building (e.g., Apt 4B)" value="<?php echo isset($form_data['address_building']) ? htmlspecialchars($form_data['address_building']) : ''; ?>" required>
                                                        <div class="invalid-feedback">Please enter the building details.</div>
                                                    </div>
                                                    <div class="col-12 mb-2">
                                                        <label class="form-label">City <span class="text-danger">*</span></label>
                                                        <input type="text" class="form-control" id="city" name="city" placeholder="City (e.g., New York)" value="<?php echo isset($form_data['city']) ? htmlspecialchars($form_data['city']) : ''; ?>" required>
                                                        <div class="invalid-feedback">Please enter the city.</div>
                                                    </div>
                                                    <!-- from here  -->
                                                    <div class="col-12 mb-2">
                                                        <label class="form-label">Latitude <span class="text-danger">*</span></label>
                                                        <input type="text" class="form-control" id="Latitude" name="Latitude" placeholder="" value="<?php echo isset($form_data['Latitude']) ? htmlspecialchars($form_data['Latitude']) : ''; ?>" required>
                                                        <div class="invalid-feedback">Please enter the Latitude.</div>
                                                    </div>
                                                    <div class="col-12 mb-2">
                                                        <label class="form-label">Longitude <span class="text-danger">*</span></label>
                                                        <input type="text" class="form-control" id="Longitude" name="Longitude" placeholder="" value="<?php echo isset($form_data['Longitude']) ? htmlspecialchars($form_data['Longitude']) : ''; ?>" required>
                                                        <div class="invalid-feedback">Please enter the Longitude.</div>
                                                    </div>
                                                    <!-- to here  -->

                                                </div>
                                                <button type="button" class="btn btn-sm btn-outline-primary mt-2 fw-bold" id="detectAddressBtn">
                                                    <i class="fas fa-location-arrow me-1"></i> Use My Current Location
                                                </button>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label fw-bold">Medical Condition</label>
                                                <textarea class="form-control" id="MedicalCondition" name="MedicalCondition" rows="2" placeholder="Any medical conditions (e.g., Hypertension, Diabetes)"><?php echo isset($form_data['MedicalCondition']) ? htmlspecialchars($form_data['MedicalCondition']) : ''; ?></textarea>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label fw-bold">Duration <span class="text-danger">*</span></label>
                                                <div class="input-group">
                                                    <select class="form-select" id="durationSelect" name="duration" required>
                                                        <option value="">Select duration</option>
                                                        <option value="Full day" <?php echo isset($form_data['duration']) && $form_data['duration'] == '24' ? 'selected' : ''; ?>>Full day (24 hours)</option>
                                                        <option value="Half day" <?php echo isset($form_data['duration']) && $form_data['duration'] == '12' ? 'selected' : ''; ?>>Half day (12 hours)</option>
                                                        <option value="Custom" <?php echo isset($form_data['duration']) && !in_array($form_data['duration'], ['24', '12']) ? 'selected' : ''; ?>>Custom (enter hours)</option>
                                                    </select>
                                                    <input type="number" class="form-control" id="customDuration" name="custom_duration" placeholder="Enter hours (e.g., 3)" style="display:<?php echo isset($form_data['duration']) && !in_array($form_data['duration'], ['24', '12']) ? 'block' : 'none'; ?>;" min="1" value="<?php echo isset($form_data['duration']) && !in_array($form_data['duration'], ['24', '12']) ? htmlspecialchars($form_data['duration']) : ''; ?>">
                                                    <div class="invalid-feedback">Please select or enter a valid duration.</div>
                                                </div>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label fw-bold">Special Instructions</label>
                                                <textarea class="form-control" name="instructions" rows="3" placeholder="Any special requirements..."><?php echo isset($form_data['instructions']) ? htmlspecialchars($form_data['instructions']) : ''; ?></textarea>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label fw-bold">Request Type <span class="text-danger">*</span></label>
                                                <select class="form-select" name="request_type" required>
                                                    <option value="">Select an option</option>
                                                    <option value="post" <?php echo isset($form_data['request_type']) && $form_data['request_type'] == 'post' ? 'selected' : ''; ?>>Post Request Publicly</option>
                                                    <option value="select_nurse" <?php echo isset($form_data['request_type']) && $form_data['request_type'] == 'select_nurse' ? 'selected' : ''; ?>>Select a Nurse</option>
                                                </select>
                                                <div class="invalid-feedback">Please select a request type.</div>
                                            </div>
                                            <div class="text-end">
                                                <button type="button" class="btn btn-secondary me-2 prev-step">Back</button>
                                                <button type="submit" class="btn btn-success" name="submit_form">Submit Request</button>
                                            </div>
                                        </div>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <div class="modal fade" id="nurseProfileModal" tabindex="-1" aria-labelledby="nurseProfileModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="nurseProfileModalLabel">Nurse Profile</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-4 mb-3 text-center">


                            <img src="<?php echo !empty($selected_nurse['image_path']) ? "../nurse/" . htmlspecialchars($selected_nurse['image_path']) : '../nurse/uploads/profile_photos/default.jpg'; ?>" class="rounded-circle mb-3" width="150" height="150" alt="Nurse Photo">


                            <!-- <pre><?php // print_r($selected_nurse); ?></pre> -->



                            <h4><?php echo htmlspecialchars($selected_nurse['FullName'] ?? 'Unknown'); ?></h4>
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
                            <ul class="nav nav-tabs" id="nurseProfileTabs">
                                <li class="nav-item">
                                    <a class="nav-link active" data-bs-toggle="tab" href="#profile">Details</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" data-bs-toggle="tab" href="#schedule">Schedule</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" data-bs-toggle="tab" href="#certificate">Certifications</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" data-bs-toggle="tab" href="#pricing">Pricing</a>
                                </li>
                            </ul>
                            <div class="tab-content p-3 border border-top-0 rounded-bottom">
                                <div class="tab-pane fade show active" id="profile">
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
                                <div class="tab-pane fade" id="schedule">
                                    <div class="alert alert-info small">
                                        <i class="fas fa-info-circle me-2"></i>
                                        Green indicates available time slots
                                    </div>
                                    <?php if (empty($schedule)): ?>
                                        <p class="text-muted">No schedule available.</p>
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
                                <div class="tab-pane fade" id="certificate">
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
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <?php if ($selected_nurse): ?>
                        <?php if (!in_array($selected_nurse['NurseID'], $selected_nurse_ids)): ?>
                            <form action="request_service.php" method="POST">
                                <input type="hidden" name="nurse_id" value="<?php echo htmlspecialchars($selected_nurse['NurseID']); ?>">
                                <input type="hidden" name="selected_nurse_ids" value="<?php echo htmlspecialchars(implode(',', $selected_nurse_ids)); ?>">
                                <input type="hidden" name="form_service_id" value="<?php echo htmlspecialchars($form_data['service_id']); ?>">
                                <input type="hidden" name="form_date" value="<?php echo htmlspecialchars($form_data['date']); ?>">
                                <input type="hidden" name="form_time" value="<?php echo htmlspecialchars($form_data['time']); ?>">
                                <input type="hidden" name="form_number_of_nurses" value="<?php echo htmlspecialchars($form_data['number_of_nurses']); ?>">
                                <input type="hidden" name="form_gender" value="<?php echo htmlspecialchars($form_data['gender']); ?>">
                                <input type="hidden" name="form_age_type" value="<?php echo htmlspecialchars($form_data['age_type']); ?>">
                                <input type="hidden" name="form_care_needed" value="<?php echo htmlspecialchars($form_data['care_needed']); ?>">
                                <input type="hidden" name="form_address_street" value="<?php echo htmlspecialchars($form_data['address_street']); ?>">
                                <input type="hidden" name="form_address_building" value="<?php echo htmlspecialchars($form_data['address_building']); ?>">
                                <input type="hidden" name="form_city" value="<?php echo htmlspecialchars($form_data['city']); ?>">
                                <input type="hidden" name="form_MedicalCondition" value="<?php echo htmlspecialchars($form_data['MedicalCondition']); ?>">
                                <input type="hidden" name="form_duration" value="<?php echo htmlspecialchars($form_data['duration']); ?>">
                                <input type="hidden" name="form_instructions" value="<?php echo htmlspecialchars($form_data['instructions']); ?>">
                                <input type="hidden" name="form_request_type" value="<?php echo htmlspecialchars($form_data['request_type']); ?>">
                                <button type="submit" class="btn btn-success" name="select_nurse_for_request" <?php echo count($selected_nurse_ids) >= $nb_nurse_needed ? 'disabled' : ''; ?>>Select</button>
                            </form>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="confirmSelectionModal" tabindex="-1" role="dialog" aria-labelledby="confirmSelectionModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="confirmSelectionModalLabel">Confirm Nurse Selection</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to request services from the following nurses?</p>
                <?php if (!empty($selected_nurse_names)): ?>
                    <ul class="list-group">
                        <?php foreach ($selected_nurse_names as $nurse_id => $name): ?>
                            <li class="list-group-item"><?php echo htmlspecialchars($name); ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p class="text-warning">No nurses selected.</p>
                <?php endif; ?>
                <?php if (count($selected_nurse_ids) < $nb_nurse_needed): ?>
                    <p class="text-warning mt-3">Note: You have selected <?php echo count($selected_nurse_ids); ?> out of <?php echo htmlspecialchars($nb_nurse_needed); ?> requested nurses.</p>
                <?php endif; ?>
            </div>
<div class="modal-footer">
    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" onclick="document.getElementById('confirmSelectionModal').classList.remove('show');document.getElementById('confirmSelectionModal').style.display='none';document.body.classList.remove('modal-open');document.querySelector('.modal-backdrop')?.remove();">Cancel</button>
    <?php if (!empty($selected_nurse_ids)): ?>
        <form action="request_service.php" method="POST">
            <input type="hidden" name="selected_nurse_ids" value="<?php echo htmlspecialchars(implode(',', $selected_nurse_ids)); ?>">
            <input type="hidden" name="form_service_id" value="<?php echo htmlspecialchars($form_data['service_id']); ?>">
            <input type="hidden" name="form_date" value="<?php echo htmlspecialchars($form_data['date']); ?>">
            <input type="hidden" name="form_time" value="<?php echo htmlspecialchars($form_data['time']); ?>">
            <input type="hidden" name="form_number_of_nurses" value="<?php echo htmlspecialchars($form_data['number_of_nurses']); ?>">
            <input type="hidden" name="form_gender" value="<?php echo htmlspecialchars($form_data['gender']); ?>">
            <input type="hidden" name="form_age_type" value="<?php echo htmlspecialchars($form_data['age_type']); ?>">
            <input type="hidden" name="form_care_needed" value="<?php echo htmlspecialchars($form_data['care_needed']); ?>">
            <input type="hidden" name="form_address_street" value="<?php echo htmlspecialchars($form_data['address_street']); ?>">
            <input type="hidden" name="form_address_building" value="<?php echo htmlspecialchars($form_data['address_building']); ?>">
            <input type="hidden" name="form_city" value="<?php echo htmlspecialchars($form_data['city']); ?>">
            <input type="hidden" name="form_MedicalCondition" value="<?php echo htmlspecialchars($form_data['MedicalCondition']); ?>">
            <input type="hidden" name="form_duration" value="<?php echo htmlspecialchars($form_data['duration']); ?>">
            <input type="hidden" name="form_instructions" value="<?php echo htmlspecialchars($form_data['instructions']); ?>">
            <input type="hidden" name="form_request_type" value="<?php echo htmlspecialchars($form_data['request_type']); ?>">
            <button type="submit" class="btn btn-primary" name="confirm_request">Confirm</button>
        </form>
    <?php endif; ?>
</div>
        </div>
    </div>
</div>

    <?php include "logout.php" ?>


    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/patient.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Duration select handling
    const durationSelect = document.getElementById('durationSelect');
    const customDuration = document.getElementById('customDuration');
    if (durationSelect && customDuration) {
        durationSelect.addEventListener('change', function() {
            if (durationSelect.value === 'Custom') {
                customDuration.style.display = 'block';
                customDuration.required = true;
            } else {
                customDuration.style.display = 'none';
                customDuration.required = false;
                customDuration.value = '';
                customDuration.classList.remove('is-invalid');
            }
        });
    }

    // Detect address button
    const detectAddressBtn = document.getElementById('detectAddressBtn');
    if (detectAddressBtn) {
        detectAddressBtn.addEventListener('click', function() {
            document.getElementById('city').value = '<?php echo htmlspecialchars($patient_address['City'] ?? ''); ?>';
            document.getElementById('addressStreet').value = '<?php echo htmlspecialchars($patient_address['Street'] ?? ''); ?>';
            document.getElementById('addressBuilding').value = '<?php echo htmlspecialchars($patient_address['Building'] ?? ''); ?>';
            document.getElementById('Latitude').value = '<?php echo htmlspecialchars($patient_address['Latitude'] ?? ''); ?>';
            document.getElementById('Longitude').value = '<?php echo htmlspecialchars($patient_address['Longitude'] ?? ''); ?>';
        });
    }

    // Nurse profile modal
    <?php if (isset($selected_nurse) && $selected_nurse): ?>
        const profileModal = new bootstrap.Modal(document.getElementById('nurseProfileModal'), { backdrop: true, keyboard: true });
        profileModal.show();
    <?php endif; ?>

    // Confirm selection modal
    const confirmButton = document.getElementById('confirmSelectionButton');
    const confirmModalElement = document.getElementById('confirmSelectionModal');
    if (confirmButton && confirmModalElement) {
        confirmButton.addEventListener('click', function(e) {
            e.preventDefault();
            const confirmModal = new bootstrap.Modal(confirmModalElement, { backdrop: true, keyboard: true });
            confirmModal.show();
            console.log('Confirm button clicked, modal should open'); // Debugging
        });
    } else {
        console.log('Confirm button or modal not found'); // Debugging
    }
    
});
</script>
</body>
</html>