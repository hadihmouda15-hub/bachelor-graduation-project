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


// Fetch patient data
$sql = "SELECT u.FullName, u.Email, u.PhoneNumber, 
               a.Country, a.City, a.Street, a.Building, 
               p.image_path

            FROM patient p, user u, address a

                WHERE p.UserID = u.UserID
                    AND u.AddressID = a.AddressID
                    AND p.PatientID = $patient_id;";

$result = $conn->query($sql);
$patient = $result->fetch_assoc();

// Handle image upload
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES["image_path"])) {
    $filename = $_FILES["image_path"]["name"];
    $tempname = $_FILES["image_path"]["tmp_name"];
    $folder = "image/" . $filename;

    if (!file_exists("image")) {
        mkdir("image", 0777, true);
    }

    $sql = "UPDATE patient SET image_path = '$folder' WHERE PatientID = $patient_id";
    if ($conn->query($sql) === TRUE && move_uploaded_file($tempname, $folder)) {
        header("Location: profile.php");
        exit();
    } else {
        echo "Error: " . $conn->error;
    }
}

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['full_name'])) {
    $full_name = $conn->real_escape_string($_POST['full_name']);
    $email = $conn->real_escape_string($_POST['email']);
    $phone = $conn->real_escape_string($_POST['phone']);
    $country = $conn->real_escape_string($_POST['country']);
    $city = $conn->real_escape_string($_POST['city']);
    $street = $conn->real_escape_string($_POST['street']);
    $building = $conn->real_escape_string($_POST['building']);

    // Update user
    $sql = "UPDATE user u , patient p
            SET u.FullName = '$full_name', u.Email = '$email', u.PhoneNumber = '$phone'
            WHERE p.PatientID = $patient_id AND p.UserID = u.UserID";
    if ($conn->query($sql) !== TRUE) {
        echo "Error: " . $conn->error;
    }

    // Update address (assuming one address per patient)
    $sql = "UPDATE address a , patient p , user u
            SET a.country = '$country', a.city = '$city', a.street = '$street', a.building = '$building'
            WHERE p.PatientID = $patient_id AND p.UserID = u.UserID AND u.AddressID = a.AddressID";
    if ($conn->query($sql) !== TRUE) {
        $error = "Error updating address: " . $conn->error;
    }else {
        header("Location: profile.php");
        exit();
    }
}
// Store error for display
if (isset($error)) {
    $error_message = $error;
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home Nursing - My Profile</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/patient.css">
</head>

<body>
    <div class="container-fluid">
        <div class="row">
            <?php include 'sidebar.php'; ?>

            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3">
                    <h2 class="h4 fw-bold">My Profile</h2>
                    <button class="btn btn-primary" id="editProfileBtn">Edit Profile</button>
                </div>

                <div class="row">
                    <div class="col-lg-4">
                        <div class="card shadow mb-4">
                            <div class="card-header py-3">
                                <h6 class="m-0 fw-bold text-primary">Profile Picture</h6>
                            </div>
                            <div class="card-body text-center">
                                <form method="POST" enctype="multipart/form-data">


                                    
                                    <img id="profileImagePreview" src="<?php echo !empty($patient['image_path']) ? htmlspecialchars($patient['image_path']) : '../nurse/uploads/profile_photos/default.jpg'; ?>"
                                    class=" rounded-circle mb-3" width="150" height="150" alt="profile">



                                    <div class="small font-italic text-muted mb-4">JPG or PNG no larger than 5 MB</div>
                                    <input type="file" id="profileImageUpload" name="image_path" accept="image/jpeg, image/png" style="display: none;">
                                    <button type="button" class="btn btn-sm btn-primary" onclick="document.getElementById('profileImageUpload').click()">
                                        Upload new image
                                    </button>
                                    <button type="submit" class="btn btn-sm btn-primary mt-2" style="display: none;" id="submitImageBtn">Save Image</button>
                                    <div id="uploadError" class="text-danger small mt-2" style="display: none;"></div>
                                </form>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-8">
                        <div class="card shadow mb-4">
                            <div class="card-header py-3 d-flex justify-content-between align-items-center">
                                <h6 class="m-0 fw-bold text-primary">Account Information</h6>
                            </div>
                            <div class="card-body">

                            <form id="profileForm" method="POST">
                                    <div id="viewMode">
                                        <div class="mb-3">
                                            <label class="form-label text-muted small">Name</label>
                                            <p class="mb-0" id="viewFullName"><?php echo htmlspecialchars($patient['FullName']); ?></p>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label text-muted small">Email</label>
                                            <p class="mb-0" id="viewEmail"><?php echo htmlspecialchars($patient['Email']); ?></p>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label text-muted small">Phone Number</label>
                                            <p class="mb-0" id="viewPhone"><?php echo htmlspecialchars($patient['PhoneNumber'] ?? 'Not Available'); ?></p>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label text-muted small">Address</label>
                                            <p class="mb-0" id="viewAddress">
                                                <?php 
                                                $addressParts = array_filter([
                                                    $patient['Building'],
                                                    $patient['Street'],
                                                    $patient['City'],
                                                    $patient['Country']
                                                ]);
                                                echo htmlspecialchars(implode(', ', $addressParts) ?: 'Not Available'); 
                                                ?>
                                            </p>
                                        </div>
                                    </div>
                                    <div id="editMode" style="display: none;">
                                        <div class="mb-3">
                                            <label class="form-label">Name</label>
                                            <input type="text" class="form-control" id="editFullName" name="full_name" value="<?php echo htmlspecialchars($patient['FullName']); ?>">
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Email</label>
                                            <input type="email" class="form-control" id="editEmail" name="email" value="<?php echo htmlspecialchars($patient['Email']); ?>">
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Phone Number</label>
                                            <input type="tel" class="form-control" id="editPhone" name="phone" value="<?php echo htmlspecialchars($patient['PhoneNumber'] ?? ''); ?>">
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Country</label>
                                            <input type="text" class="form-control" id="editCountry" name="country" value="<?php echo htmlspecialchars($patient['Country'] ?? ''); ?>">
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">City</label>
                                            <input type="text" class="form-control" id="editCity" name="city" value="<?php echo htmlspecialchars($patient['City'] ?? ''); ?>">
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Street</label>
                                            <input type="text" class="form-control" id="editStreet" name="street" value="<?php echo htmlspecialchars($patient['Street'] ?? ''); ?>">
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Building</label>
                                            <input type="text" class="form-control" id="editBuilding" name="building" value="<?php echo htmlspecialchars($patient['Building'] ?? ''); ?>">
                                        </div>
                                        <div class="text-end">
                                            <button type="button" class="btn btn-secondary" id="cancelEditBtn">Cancel</button>
                                            <button type="submit" class="btn btn-primary">Save Changes</button>
                                        </div>
                                    </div>
                            </form>
                                
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

        <?php include "logout.php" ?>


    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/patient.js"></script>
</body>
</html>