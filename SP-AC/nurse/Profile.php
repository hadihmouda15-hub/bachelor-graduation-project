<?php
session_start();
// $_SESSION['nurse_id'] = 28;
$_SESSION['user_type'] = 'nurse';
$_SESSION['logged_in'] = true;


require_once 'db_connection.php';
$nurse_id = $_SESSION['nurse_id'];


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

// Fetch nurse and user data
$nurse_data = [];
$user_data = [];
$address_data = [];
$services_data = [];
$certifications_data = [];
$all_services = [];

try {
    // Get nurse and user data
    $stmt = $conn->prepare("
        SELECT n.*, u.*, a.* 
        FROM nurse n
        JOIN user u ON n.UserID = u.UserID
        LEFT JOIN address a ON u.AddressID = a.AddressID
        WHERE n.NurseID = ?
    ");
    $stmt->bind_param("i", $nurse_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $nurse_data = $result->fetch_assoc();
        $user_data = $nurse_data; // Since we joined the tables
        $address_data = [
            'Country' => $nurse_data['Country'],
            'City' => $nurse_data['City'],
            'Street' => $nurse_data['Street'],
            'Building' => $nurse_data['Building'],
            'Notes' => $nurse_data['Notes']
        ];
    }

    // Get nurse services
    $stmt = $conn->prepare("
        SELECT s.ServiceID, s.Name, s.Description, ns.Price 
        FROM nurseservices ns
        JOIN service s ON ns.ServiceID = s.ServiceID
        WHERE ns.NurseID = ?
    ");
    $stmt->bind_param("i", $nurse_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $services_data[] = $row;
    }

    // Get all available services for dropdown
    $result = $conn->query("SELECT ServiceID, Name FROM service");
    while ($row = $result->fetch_assoc()) {
        $all_services[] = $row;
    }

    // Get certifications
    $stmt = $conn->prepare("SELECT * FROM certification WHERE NurseID = ?");
    $stmt->bind_param("i", $nurse_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $certifications_data[] = $row;
    }
} catch (Exception $e) {
    // Handle error
    $error = "Error fetching data: " . $e->getMessage();
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // In the POST handling section, update the profile update block:
    if (isset($_POST['update_profile'])) {
        // Handle profile update
        $fullname = $_POST['fullname'];
        $phone = $_POST['phone'];
        $bio = $_POST['bio'];

        try {
            $conn->begin_transaction();

            // Handle file upload if a new photo was provided
            $image_path = $nurse_data['image_path']; // Keep existing if no new upload
            if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] == UPLOAD_ERR_OK) {
                $target_dir = "uploads/profile_photos/";

                // Create directory if it doesn't exist
                if (!file_exists($target_dir)) {
                    mkdir($target_dir, 0777, true);
                }

                // Generate unique filename
                $file_extension = pathinfo($_FILES["profile_photo"]["name"], PATHINFO_EXTENSION);
                $filename = "nurse_" . $nurse_id . "_" . time() . "." . $file_extension;
                $target_file = $target_dir . $filename;

                // Check if image file is a actual image
                $check = getimagesize($_FILES["profile_photo"]["tmp_name"]);
                if ($check === false) {
                    throw new Exception("File is not an image.");
                }

                // Check file size (max 2MB)
                if ($_FILES["profile_photo"]["size"] > 90000000) {
                    throw new Exception("Sorry, your file is too large. Max 2MB allowed.");
                }

                // Allow certain file formats
                $allowed_extensions = ["jpg", "jpeg", "png", "gif"];
                if (!in_array(strtolower($file_extension), $allowed_extensions)) {
                    throw new Exception("Sorry, only JPG, JPEG, PNG & GIF files are allowed.");
                }

                // Delete old photo if it exists
                if ($image_path && file_exists($image_path)) {
                    unlink($image_path);
                }

                // Try to upload file
                if (move_uploaded_file($_FILES["profile_photo"]["tmp_name"], $target_file)) {
                    $image_path = $target_file;
                } else {
                    throw new Exception("Sorry, there was an error uploading your file.");
                }
            }

            // Update user table
            $stmt = $conn->prepare("UPDATE user SET FullName = ?, PhoneNumber = ? WHERE UserID = ?");
            $stmt->bind_param("ssi", $fullname, $phone, $user_data['UserID']);
            $stmt->execute();

            // Update nurse table with bio and image path
            $stmt = $conn->prepare("UPDATE nurse SET Bio = ?, image_path = ? WHERE NurseID = ?");
            $stmt->bind_param("ssi", $bio, $image_path, $nurse_id);
            $stmt->execute();

            $conn->commit();
            $_SESSION['success'] = "Profile updated successfully!";
            header("Refresh:0");
            exit();
        } catch (Exception $e) {
            $conn->rollback();
            $_SESSION['error'] = "Error updating profile: " . $e->getMessage();
        }
    } elseif (isset($_POST['add_service'])) {
        // Handle adding new service
        $service_id = $_POST['service_id'];
        $price = $_POST['price'];

        try {
            $stmt = $conn->prepare("INSERT INTO nurseservices (NurseID, ServiceID, Price) VALUES (?, ?, ?)");
            $stmt->bind_param("iid", $nurse_id, $service_id, $price);
            $stmt->execute();
            $_SESSION['success'] = "Service added successfully!";
            header("Refresh:0");
            exit();
        } catch (Exception $e) {
            $_SESSION['error'] = "Error adding service: " . $e->getMessage();
        }
    } elseif (isset($_POST['request_certification'])) {
        // Handle certification request
        $cert_name = $_POST['cert_name'];
        $comment = $_POST['comment'];

        // Handle file upload
        $target_dir = "uploads/certifications/";
        $target_file = $target_dir . basename($_FILES["cert_image"]["name"]);
        $uploadOk = 1;
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

        // Check if image file is a actual image or fake image
        $check = getimagesize($_FILES["cert_image"]["tmp_name"]);
        if ($check !== false) {
            $uploadOk = 1;
        } else {
            $_SESSION['error'] = "File is not an image.";
            $uploadOk = 0;
        }

        // Check file size
        if ($_FILES["cert_image"]["size"] > 9000000) {
            $_SESSION['error'] = "Sorry, your file is too large.";
            $uploadOk = 0;
        }

        // Allow certain file formats
        if (
            $imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg"
            && $imageFileType != "gif"
        ) {
            $_SESSION['error'] = "Sorry, only JPG, JPEG, PNG & GIF files are allowed.";
            $uploadOk = 0;
        }

        // Check if $uploadOk is set to 0 by an error
        if ($uploadOk == 1) {
            if (move_uploaded_file($_FILES["cert_image"]["tmp_name"], $target_file)) {
                try {
                    $stmt = $conn->prepare("INSERT INTO certification (Name, Image, Comment, Status, NurseID) VALUES (?, ?, ?, 'pending', ?)");
                    $stmt->bind_param("sssi", $cert_name, $target_file, $comment, $nurse_id);
                    $stmt->execute();
                    $_SESSION['success'] = "Certification request submitted successfully!";
                    header("Refresh:0");
                    exit();
                } catch (Exception $e) {
                    $_SESSION['error'] = "Error submitting certification: " . $e->getMessage();
                }
            } else {
                $_SESSION['error'] = "Sorry, there was an error uploading your file.";
            }
        }
    } elseif (isset($_POST['update_service'])) {
        // Handle updating service price
        $service_id = $_POST['service_id'];
        $price = $_POST['price'];

        try {
            $stmt = $conn->prepare("UPDATE nurseservices SET Price = ? WHERE NurseID = ? AND ServiceID = ?");
            $stmt->bind_param("dii", $price, $nurse_id, $service_id);
            $stmt->execute();
            $_SESSION['success'] = "Service updated successfully!";
            header("Refresh:0");
            exit();
        } catch (Exception $e) {
            $_SESSION['error'] = "Error updating service: " . $e->getMessage();
        }
    } elseif (isset($_POST['delete_service'])) {
        // Handle deleting service
        $service_id = $_POST['service_id'];

        try {
            $stmt = $conn->prepare("DELETE FROM nurseservices WHERE NurseID = ? AND ServiceID = ?");
            $stmt->bind_param("ii", $nurse_id, $service_id);
            $stmt->execute();
            $_SESSION['success'] = "Service deleted successfully!";
            header("Refresh:0");
            exit();
        } catch (Exception $e) {
            $_SESSION['error'] = "Error deleting service: " . $e->getMessage();
        }
    } elseif (isset($_POST['toggle_availability'])) {
        // Handle availability toggle
        try {
            // Get current availability status
            $stmt = $conn->prepare("SELECT Availability FROM nurse WHERE NurseID = ?");
            $stmt->bind_param("i", $nurse_id);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                $nurse = $result->fetch_assoc();
                $new_availability = $nurse['Availability'] ? 0 : 1;

                // Update availability
                $stmt = $conn->prepare("UPDATE nurse SET Availability = ? WHERE NurseID = ?");
                $stmt->bind_param("ii", $new_availability, $nurse_id);
                $stmt->execute();

                $_SESSION['success'] = "Availability updated successfully!";
                header("Refresh:0");
                exit();
            }
        } catch (Exception $e) {
            $_SESSION['error'] = "Error updating availability: " . $e->getMessage();
        }
    }
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

    <style>
        .profile-img {
            width: 150px;
            height: 150px;
            object-fit: cover;
        }

        .service-card,
        .certification-card {
            transition: transform 0.3s;
        }

        .service-card:hover,
        .certification-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }

        .nav-pills .nav-link.active {
            background-color: #0d6efd;
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
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?= $_SESSION['error'];
                        unset($_SESSION['error']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?= $_SESSION['success'];
                        unset($_SESSION['success']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Nurse Profile</h1>
                </div>

                <ul class="nav nav-pills mb-4" id="profileTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="profile-tab" data-bs-toggle="pill" data-bs-target="#profile" type="button" role="tab">Profile</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="services-tab" data-bs-toggle="pill" data-bs-target="#services" type="button" role="tab">Services</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="certifications-tab" data-bs-toggle="pill" data-bs-target="#certifications" type="button" role="tab">Certifications</button>
                    </li>
                </ul>

                <div class="tab-content" id="profileTabsContent">


                    <!-- Profile Tab - Enhanced Design -->
                    <div class="tab-pane fade show active" id="profile" role="tabpanel">
                        <div class="row">
                            <div class="col-lg-4">
                                <div class="card mb-4 border-0 shadow-sm">
                                    <div class="card-body text-center p-4">
                                        <div class="position-relative d-inline-block">

                                            <img src="<?php echo !empty($nurse_data['image_path']) ? htmlspecialchars($nurse_data['image_path']) : 'uploads/profile_photos/default.jpg'; ?>"
                                                class=" rounded-circle mb-3 mb-3 border border-3 border-primary" width="150" height="150" alt="profile">






                                            <div class="position-absolute bottom-0 end-0 bg-primary rounded-circle p-2 border border-3 border-white">
                                                <label for="profile_photo" class="mb-0 cursor-pointer">
                                                    <i class="fas fa-camera text-white"></i>
                                                    <input type="file" id="profile_photo" name="profile_photo" accept="image/*"
                                                        onchange="previewProfilePhoto(this)" class="d-none">
                                                </label>
                                            </div>
                                        </div>
                                        <h4 class="card-title mb-1"><?= htmlspecialchars($user_data['FullName'] ?? '') ?></h4>
                                        <p class="text-muted mb-2">Nurse ID: <?= $nurse_id ?> <?= $_SESSION['nurse_id']  ?></p>

                                        <div class="d-flex justify-content-center gap-2 mb-3">
                                            <a href="mailto:<?= htmlspecialchars($user_data['Email'] ?? '') ?>" class="text-decoration-none">
                                                <span class="badge bg-light text-dark p-2">
                                                    <i class="fas fa-envelope me-1 text-primary"></i>
                                                    <?= htmlspecialchars($user_data['Email'] ?? '') ?>
                                                </span>
                                            </a>
                                        </div>

                                        <div class="d-flex justify-content-center">
                                            <a href="tel:<?= htmlspecialchars($user_data['PhoneNumber'] ?? '') ?>" class="btn btn-sm btn-outline-primary rounded-pill">
                                                <i class="fas fa-phone me-1"></i>
                                                <?= htmlspecialchars($user_data['PhoneNumber'] ?? '') ?>
                                            </a>
                                        </div>

                                        <!-- Add this right after the phone number button in the profile card -->
                                        <div class="d-flex justify-content-center gap-2 mb-3 mt-3">
                                            <form method="POST" class="d-inline">
                                                <button type="submit" name="toggle_availability"
                                                    class="btn btn-sm <?= $nurse_data['Availability'] ? 'btn-success' : 'btn-secondary' ?> rounded-pill">
                                                    <i class="fas fa-<?= $nurse_data['Availability'] ? 'check-circle' : 'times-circle' ?> me-1"></i>
                                                    <?= $nurse_data['Availability'] ? 'Available' : 'Not Available' ?>
                                                </button>
                                            </form>
                                        </div>


                                        <hr class="my-3">

                                        <div class="text-start">
                                            <h6 class="fw-bold mb-2">Address Information</h6>
                                            <ul class="list-unstyled small">
                                                <li class="mb-1">
                                                    <i class="fas fa-map-marker-alt text-primary me-2"></i>
                                                    <?= htmlspecialchars($address_data['Street'] ?? '') ?>,
                                                    <?= htmlspecialchars($address_data['Building'] ?? '') ?>
                                                </li>
                                                <li class="mb-1">
                                                    <i class="fas fa-city text-primary me-2"></i>
                                                    <?= htmlspecialchars($address_data['City'] ?? '') ?>
                                                </li>
                                                <li>
                                                    <i class="fas fa-flag text-primary me-2"></i>
                                                    <?= htmlspecialchars($address_data['Country'] ?? '') ?>
                                                </li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-lg-8">
                                <div class="card border-0 shadow-sm mb-4">
                                    <div class="card-header bg-white border-0 py-3">
                                        <h5 class="mb-0">Profile Information</h5>
                                    </div>
                                    <div class="card-body pt-1">
                                        <form method="POST" enctype="multipart/form-data">
                                            <div class="row">
                                                <div class="col-md-6 mb-3">
                                                    <label for="fullname" class="form-label fw-semibold">Full Name</label>
                                                    <div class="input-group">
                                                        <span class="input-group-text bg-light"><i class="fas fa-user text-primary"></i></span>
                                                        <input type="text" class="form-control" id="fullname" name="fullname"
                                                            value="<?= htmlspecialchars($user_data['FullName'] ?? '') ?>" required>
                                                    </div>
                                                </div>
                                                <div class="col-md-6 mb-3">
                                                    <label for="phone" class="form-label fw-semibold">Phone Number</label>
                                                    <div class="input-group">
                                                        <span class="input-group-text bg-light"><i class="fas fa-phone text-primary"></i></span>
                                                        <input type="tel" class="form-control" id="phone" name="phone"
                                                            value="<?= htmlspecialchars($user_data['PhoneNumber'] ?? '') ?>">
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="mb-3">
                                                <label for="bio" class="form-label fw-semibold">Professional Bio</label>
                                                <textarea class="form-control" id="bio" name="bio" rows="4"
                                                    placeholder="Tell patients about your experience and specialties"><?= htmlspecialchars($nurse_data['Bio'] ?? '') ?></textarea>
                                                <div class="form-text">This will be displayed on your public profile.</div>
                                            </div>

                                            <div class="mb-3">
                                                <label class="form-label fw-semibold">Profile Photo</label>
                                                <div class="d-flex align-items-center gap-3">
                                                    <div class="flex-shrink-0">
                                                        <img src="<?= $nurse_data['image_path'] ?? 'https://via.placeholder.com/150' ?>"
                                                            alt="Current Photo" class="rounded-circle" width="60" height="60">
                                                    </div>
                                                    <div class="flex-grow-1">
                                                        <input class="form-control form-control-sm" type="file" id="profile_photo"
                                                            name="profile_photo" accept="image/*" onchange="previewProfilePhoto(this)">
                                                        <div class="form-text small">JPG, PNG or GIF. Max size 2MB.</div>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="d-flex justify-content-end mt-4">
                                                <button type="submit" name="update_profile" class="btn btn-primary px-4">
                                                    <i class="fas fa-save me-1"></i> Save Changes
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>

                                <div class="card border-0 shadow-sm">
                                    <div class="card-header bg-white border-0 py-3">
                                        <h5 class="mb-0">Account Details</h5>
                                    </div>
                                    <div class="card-body pt-1">
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label fw-semibold">Email Address</label>
                                                <div class="input-group">
                                                    <span class="input-group-text bg-light"><i class="fas fa-envelope text-primary"></i></span>
                                                    <input type="text" class="form-control" value="<?= htmlspecialchars($user_data['Email'] ?? '') ?>" readonly>
                                                </div>
                                                <div class="form-text">Contact admin to change email</div>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label fw-semibold">Account Status</label>
                                                <div class="input-group">
                                                    <span class="input-group-text bg-light"><i class="fas fa-shield-alt text-primary"></i></span>
                                                    <input type="text" class="form-control" value="Active" readonly>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="alert alert-warning mt-3">
                                            <div class="d-flex align-items-center">
                                                <i class="fas fa-exclamation-circle me-2"></i>
                                                <div>
                                                    <h6 class="alert-heading mb-1">Security Notice</h6>
                                                    <p class="small mb-0">For security reasons, some account details can only be changed by administrators.</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>




                    <!-- Services Tab -->
                    <div class="tab-pane fade" id="services" role="tabpanel">


                        <div class="card mb-4">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5>My Services</h5>
                                <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addServiceModal">
                                    <i class="fas fa-plus"></i> Add Service
                                </button>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <?php if (empty($services_data)): ?>
                                        <div class="col-12">
                                            <div class="alert alert-info">No services added yet.</div>
                                        </div>
                                    <?php else: ?>
                                        <?php foreach ($services_data as $service): ?>
                                            <div class="col-md-4 mb-3">
                                                <div class="card service-card h-100">
                                                    <div class="card-body">
                                                        <h5 class="card-title"><?= htmlspecialchars($service['Name']) ?></h5>
                                                        <p class="card-text"><?= htmlspecialchars($service['Description']) ?></p>
                                                    </div>
                                                    <div class="card-footer bg-transparent">
                                                        <div class="d-flex justify-content-between align-items-center">
                                                            <span class="fw-bold">$<?= number_format($service['Price'], 2) ?></span>
                                                            <div class="btn-group">
                                                                <button class="btn btn-sm btn-outline-primary me-1"
                                                                    data-bs-toggle="modal"
                                                                    data-bs-target="#editServiceModal"
                                                                    data-service-id="<?= $service['ServiceID'] ?>"
                                                                    data-service-name="<?= htmlspecialchars($service['Name']) ?>"
                                                                    data-service-price="<?= $service['Price'] ?>">
                                                                    <i class="fas fa-edit"></i>
                                                                </button>
                                                                <button class="btn btn-sm btn-outline-danger"
                                                                    data-bs-toggle="modal"
                                                                    data-bs-target="#deleteServiceModal"
                                                                    data-service-id="<?= $service['ServiceID'] ?>"
                                                                    data-service-name="<?= htmlspecialchars($service['Name']) ?>">
                                                                    <i class="fas fa-trash"></i>
                                                                </button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>


                    </div>

                    <!-- Certifications Tab -->
                    <div class="tab-pane fade" id="certifications" role="tabpanel">
                        <div class="card mb-4">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5>My Certifications</h5>
                                <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#requestCertificationModal">
                                    <i class="fas fa-plus"></i> Request New Certification
                                </button>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <?php if (empty($certifications_data)): ?>
                                        <div class="col-12">
                                            <div class="alert alert-info">No certifications yet.</div>
                                        </div>
                                    <?php else: ?>
                                        <?php foreach ($certifications_data as $cert): ?>
                                            <div class="col-md-4 mb-3">
                                                <div class="card certification-card h-100">
                                                    <?php if ($cert['Image']): ?>
                                                        <img src="<?= htmlspecialchars($cert['Image']) ?>" class="card-img-top" alt="Certification Image">
                                                    <?php endif; ?>
                                                    <div class="card-body">
                                                        <h5 class="card-title"><?= htmlspecialchars($cert['Name']) ?></h5>
                                                        <p class="card-text"><?= htmlspecialchars($cert['Comment']) ?></p>
                                                        <span class="badge bg-<?= $cert['Status'] == 'approved' ? 'success' : ($cert['Status'] == 'rejected' ? 'danger' : 'warning') ?>">
                                                            <?= htmlspecialchars($cert['Status']) ?>
                                                        </span>
                                                    </div>
                                                    <div class="card-footer bg-transparent">
                                                        <small class="text-muted">Requested on: <?= date('M d, Y', strtotime($cert['CreatedAt'])) ?></small>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Add Service Modal -->
    <div class="modal fade" id="addServiceModal" tabindex="-1" aria-labelledby="addServiceModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title" id="addServiceModalLabel">Add New Service</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="service_id" class="form-label">Service</label>
                            <select class="form-select" id="service_id" name="service_id" required>
                                <option value="">Select a service</option>
                                <?php foreach ($all_services as $service): ?>
                                    <option value="<?= $service['ServiceID'] ?>"><?= htmlspecialchars($service['Name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="price" class="form-label">Price ($)</label>
                            <input type="number" step="0.01" min="0" class="form-control" id="price" name="price" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" name="add_service" class="btn btn-primary">Add Service</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Request Certification Modal -->
    <div class="modal fade" id="requestCertificationModal" tabindex="-1" aria-labelledby="requestCertificationModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" enctype="multipart/form-data">
                    <div class="modal-header">
                        <h5 class="modal-title" id="requestCertificationModalLabel">Request New Certification</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="cert_name" class="form-label">Certification Name</label>
                            <input type="text" class="form-control" id="cert_name" name="cert_name" required>
                        </div>
                        <div class="mb-3">
                            <label for="cert_image" class="form-label">Certification Image</label>
                            <input class="form-control" type="file" id="cert_image" name="cert_image" accept="image/*" required>
                        </div>
                        <div class="mb-3">
                            <label for="comment" class="form-label">Comments</label>
                            <textarea class="form-control" id="comment" name="comment" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" name="request_certification" class="btn btn-primary">Submit Request</button>
                    </div>
                </form>
            </div>
        </div>
    </div>


    <!-- Edit Service Modal -->
    <div class="modal fade" id="editServiceModal" tabindex="-1" aria-labelledby="editServiceModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title" id="editServiceModalLabel">Edit Service</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="service_id" id="edit_service_id">
                        <div class="mb-3">
                            <label class="form-label">Service</label>
                            <input type="text" class="form-control" id="edit_service_name" readonly>
                        </div>
                        <div class="mb-3">
                            <label for="edit_price" class="form-label">Price ($)</label>
                            <input type="number" step="0.01" min="0" class="form-control" id="edit_price" name="price" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" name="update_service" class="btn btn-primary">Update Service</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Service Modal -->
    <div class="modal fade" id="deleteServiceModal" tabindex="-1" aria-labelledby="deleteServiceModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title" id="deleteServiceModalLabel">Delete Service</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="service_id" id="delete_service_id">
                        <p>Are you sure you want to delete <strong id="delete_service_name"></strong> from your services?</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="delete_service" class="btn btn-danger">Delete Service</button>
                    </div>
                </form>
            </div>
        </div>
    </div>


    <?php include "logoutmodal.php" ?>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Custom JS -->
    <script>
        // Activate tab from URL hash
        document.addEventListener('DOMContentLoaded', function() {
            if (window.location.hash) {
                const tabTrigger = new bootstrap.Tab(document.querySelector(`[href="${window.location.hash}"]`));
                tabTrigger.show();
            }

            // Update URL hash when tab changes
            document.querySelectorAll('#profileTabs .nav-link').forEach(tab => {
                tab.addEventListener('click', function() {
                    window.location.hash = this.getAttribute('href');
                });
            });
        });




        // Add this to the script section at the bottom of the page
        function previewProfilePhoto(input) {
            const preview = document.getElementById('profilePhotoPreview');
            const file = input.files[0];
            const reader = new FileReader();

            reader.onload = function(e) {
                preview.src = e.target.result;
            }

            if (file) {
                reader.readAsDataURL(file);
            }
        }

        // Handle edit service modal
        document.getElementById('editServiceModal').addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const serviceId = button.getAttribute('data-service-id');
            const serviceName = button.getAttribute('data-service-name');
            const servicePrice = button.getAttribute('data-service-price');

            const modal = this;
            modal.querySelector('#edit_service_id').value = serviceId;
            modal.querySelector('#edit_service_name').value = serviceName;
            modal.querySelector('#edit_price').value = servicePrice;
        });

        // Handle delete service modal
        document.getElementById('deleteServiceModal').addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const serviceId = button.getAttribute('data-service-id');
            const serviceName = button.getAttribute('data-service-name');

            const modal = this;
            modal.querySelector('#delete_service_id').value = serviceId;
            modal.querySelector('#delete_service_name').textContent = serviceName;
        });
    </script>
</body>

</html>