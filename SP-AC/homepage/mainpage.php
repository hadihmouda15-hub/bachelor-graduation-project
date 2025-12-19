<?php
require_once 'db_connection.php';
session_start();

 $sql = "SELECT * FROM settings 
                WHERE 1";
        $result = $conn->query($sql);
        $settings = $result->fetch_assoc();



// Initialize variables
$login_error = '';
$register_success = '';
$nurse_application_message = '';

// Handle Login

// Handle Login
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['login'])) {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    // Validate inputs
    if (empty($email) || empty($password)) {
        $login_error = 'Please enter both email and password.';
    } else {
        // Prepare SQL to prevent SQL injection
        $stmt = $conn->prepare("SELECT * FROM user WHERE Email = ? LIMIT 1");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows == 1) {
            $user = $result->fetch_assoc();

            // Verify password (assuming passwords are hashed)
            if (password_verify($password, $user['Password'])) {
                if ($user['Status'] != 'active') {
                    echo "<script>
                        alert('Your account has been deactivated by the administrator.');
                        window.location.href = 'mainpage.php';
                    </script>";
                    header("Refresh: 0; url=login.php");
                    exit;
                } else {
                    // Password is correct, set session variables
                    $_SESSION['email'] = $user['Email'];
                    $_SESSION['role'] = $user['Role'];
                    $_SESSION['full_name'] = $user['FullName'];

                    // Redirect based on role   
                    switch ($user['Role']) {
                        case 'patient':
                            $userID = $user['UserID'];
                            $sql = "SELECT PatientID FROM patient WHERE UserID = $userID";
                            $result = mysqli_query($conn, $sql);
                            if ($row = mysqli_fetch_assoc($result)) {
                                $_SESSION['patient_id'] = $row['PatientID'];
                            }
                            header("Location: ../patient1/request_service.php");
                            exit();
                        case 'nurse':
                            $userID = $user['UserID'];
                            $sql = "SELECT NurseID FROM nurse WHERE UserID = $userID";
                            $result = mysqli_query($conn, $sql);
                            if ($row = mysqli_fetch_assoc($result)) {
                                $_SESSION['nurse_id'] = $row['NurseID'];
                            }
                            header("Location: ../nurse/publicrequests.php");
                            exit();
                            case 'staff':
                            $_SESSION['staff_id'] = $row['NurseID'];
                            header("Location: ../staff/applications.php");
                            exit();
                            case 'admin':
                            $_SESSION['admin_id'] = $row['NurseID'];
                            header("Location: ../admin/dashboard.php");
                            exit();
                        default:
                            header("Location: index.php");
                            exit();
                    }
                }
            } else {
                $login_error = 'Invalid email or password.';
            }
        } else {
            $login_error = 'Invalid email or password.';
        }
        $stmt->close();
    }
}


// Handle Patient Registration
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['register_patient'])) {
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);
    $gender = $_POST['gender'];
    $date_of_birth = $_POST['date_of_birth'];
    $phone_number = trim($_POST['phone_number']);

    // Address fields (you'll need to add these to your form)
    $country = trim($_POST['country']);
    $city = trim($_POST['city']);
    $street = trim($_POST['street']);
    $building = trim($_POST['building']);
    $latitude = trim($_POST['latitude']);
    $longitude = trim($_POST['longitude']);
    $address_notes = trim($_POST['address_notes']);

    // Validate inputs
    if (
        empty($full_name) || empty($email) || empty($password) || empty($confirm_password) ||
        empty($gender) || empty($date_of_birth) || empty($country) || empty($city) ||
        empty($street) || empty($building)
    ) {
        $register_error = 'Please fill in all required fields.';
    } elseif ($password !== $confirm_password) {
        $register_error = 'Passwords do not match.';
    } elseif (strlen($password) < 3) {
        $register_error = 'Password must be at least 8 characters long.';
    } else {
        // Check if email already exists
        $stmt = $conn->prepare("SELECT UserID FROM user WHERE Email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $register_error = 'Email already exists. Please use a different email.';
        } else {
            // Start transaction
            $conn->begin_transaction();

            try {
                // Insert address first
                $stmt = $conn->prepare("INSERT INTO address (Country, City, Street, Building, Latitude, Longitude, Notes) 
                                       VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("ssssdds", $country, $city, $street, $building, $latitude, $longitude, $address_notes);
                $stmt->execute();
                $address_id = $stmt->insert_id;
                $stmt->close();

                // Hash password
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);

                // Insert into user table with address ID
                $stmt = $conn->prepare("INSERT INTO user (FullName, Gender, DateOfBirth, PhoneNumber, Email, Password, Role, Status, AddressID) 
                                       VALUES (?, ?, ?, ?, ?, ?, 'patient', 'active', ?)");
                $stmt->bind_param("ssssssi", $full_name, $gender, $date_of_birth, $phone_number, $email, $hashed_password, $address_id);

                if ($stmt->execute()) {
                    $user_id = $stmt->insert_id;

                    // Insert into patient table
                    $stmt = $conn->prepare("INSERT INTO patient (UserID) VALUES (?)");
                    $stmt->bind_param("i", $user_id);
                    $stmt->execute();

                    // Commit transaction
                    $conn->commit();

                    // Set session variables
                    $_SESSION['user_id'] = $user_id;
                    $_SESSION['email'] = $email;
                    $_SESSION['role'] = 'patient';
                    $_SESSION['full_name'] = $full_name;

                    // Redirect to patient dashboard
                    header("Location: mainpage.php");
                    exit();
                } else {
                    $register_error = 'Registration failed. Please try again.';
                    $conn->rollback();
                }
            } catch (Exception $e) {
                $conn->rollback();
                $register_error = 'Registration failed: ' . $e->getMessage();
            }
        }
        $stmt->close();
    }
}




// Handle Nurse Application/Registration
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['register_nurse'])) {
    // Personal Information
    $full_name = trim($_POST['FullName']);
    $date_of_birth = $_POST['DateOfBirth'];
    $gender = $_POST['Gender'];

    // Contact Information
    $phone_number = trim($_POST['PhoneNumber']);
    $email = trim($_POST['Email']);

    // Address Information
    $country = trim($_POST['Country']);
    $city = trim($_POST['City']);
    $street = trim($_POST['Street']);
    $building = trim($_POST['Building']);
    $latitude = trim($_POST['Latitude']);
    $longitude = trim($_POST['Longitude']);
    $address_notes = trim($_POST['Notes']);

    // Professional Information
    $language = trim($_POST['Language']);
    $syndicate_number = trim($_POST['SyndicateNumber']);
    $specialization = trim($_POST['Specialization']);
    $bio = trim($_POST['Bio']);
    $comments = trim($_POST['Comments']);

    // Account Security
    // $password = trim($_POST['Password']);
    // $confirm_password = trim($_POST['confirm_password']);

    // Validate inputs
    $errors = [];

    if (empty($full_name)) $errors[] = 'Full name is required.';
    if (empty($date_of_birth)) $errors[] = 'Date of birth is required.';
    if (empty($gender)) $errors[] = 'Gender is required.';
    if (empty($phone_number)) $errors[] = 'Phone number is required.';
    if (empty($email)) $errors[] = 'Email is required.';
    if (empty($country)) $errors[] = 'Country is required.';
    if (empty($city)) $errors[] = 'City is required.';
    if (empty($street)) $errors[] = 'Street is required.';
    if (empty($syndicate_number)) $errors[] = 'Syndicate number is required.';
    if (empty($specialization)) $errors[] = 'Specialization is required.';
    // if (empty($password)) $errors[] = 'Password is required.';
    // if ($password !== $confirm_password) $errors[] = 'Passwords do not match.';
    // if (strlen($password) < 8) $errors[] = 'Password must be at least 8 characters long.';

    // Check if email already exists
    $stmt = $conn->prepare("SELECT UserID FROM user WHERE Email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $errors[] = 'Email already exists. Please use a different email.';
    }
    $stmt->close();

    // Handle file uploads
    $picture_path = '';
    $cv_path = '';

    // Process profile picture upload
    if (isset($_FILES['Picture']) && $_FILES['Picture']['error'] == UPLOAD_ERR_OK) {
        $picture_info = $_FILES['Picture'];
        $picture_ext = strtolower(pathinfo($picture_info['name'], PATHINFO_EXTENSION));
        $allowed_picture_ext = ['jpg', 'jpeg', 'png', 'gif'];

        if (in_array($picture_ext, $allowed_picture_ext)) {
            $picture_name = uniqid('nurse_', true) . '.' . $picture_ext;
            $picture_path = 'uploads/images/' . $picture_name;

            if (!move_uploaded_file($picture_info['tmp_name'], $picture_path)) {
                $errors[] = 'Failed to upload profile picture.';
            }
        } else {
            $errors[] = 'Invalid profile picture format. Only JPG, JPEG, PNG, and GIF are allowed.';
        }
    }

    // Process CV upload
    if (isset($_FILES['URL_CV']) && $_FILES['URL_CV']['error'] == UPLOAD_ERR_OK) {
        $cv_info = $_FILES['URL_CV'];
        $cv_ext = strtolower(pathinfo($cv_info['name'], PATHINFO_EXTENSION));
        $allowed_cv_ext = ['pdf', 'doc', 'docx'];

        if (in_array($cv_ext, $allowed_cv_ext)) {
            $cv_name = uniqid('cv_', true) . '.' . $cv_ext;
            $cv_path = 'uploads/cvs/' . $cv_name;

            if (!move_uploaded_file($cv_info['tmp_name'], $cv_path)) {
                $errors[] = 'Failed to upload CV.';
            }
        } else {
            $errors[] = 'Invalid CV format. Only PDF, DOC, and DOCX are allowed.';
        }
    } else {
        $errors[] = 'CV is required.';
    }

    // If no errors, proceed with registration
    if (empty($errors)) {
        // Start transaction
        $conn->begin_transaction();

        try {
            // Hash password
            // $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            // Insert address first
            $stmt = $conn->prepare("INSERT INTO address (Country, City, Street, Building, Latitude, Longitude, Notes) 
                                   VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssdds", $country, $city, $street, $building, $latitude, $longitude, $address_notes);
            $stmt->execute();
            $address_id = $stmt->insert_id;
            $stmt->close();

            // Insert into user table
            // $stmt = $conn->prepare("INSERT INTO user (FullName, Gender, DateOfBirth, PhoneNumber, Email, Password, Role, Status, AddressID) 
            //                        VALUES (?, ?, ?, ?, ?, ?, 'nurse', 'pending', ?)");
            // $stmt->bind_param("ssssssi", $full_name, $gender, $date_of_birth, $phone_number, $email, $hashed_password, $address_id);
            // $stmt->execute();
            // $user_id = $stmt->insert_id;
            // $stmt->close();

            // Insert into nurseapplication table
            $stmt = $conn->prepare("INSERT INTO nurseapplication 
                                   (FullName, DateOfBirth, PhoneNumber, Email, Picture, URL_CV, Language, Gender, SyndicateNumber, Comments, Specialization, Status) 
                                   VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')");
            $stmt->bind_param("sssssssssss", $full_name, $date_of_birth, $phone_number, $email, $picture_path, $cv_path, $language, $gender, $syndicate_number, $comments, $specialization);
            $stmt->execute();
            $na_id = $stmt->insert_id;
            $stmt->close();

            // Insert into nurse table (but nurse won't be active until approved)
            // $stmt = $conn->prepare("INSERT INTO nurse (Bio, Availability, NAID, UserID, image_path) 
            //                        VALUES (?, 0, ?, ?, ?)");
            // $stmt->bind_param("siis", $bio, $na_id, $user_id, $picture_path);
            // $stmt->execute();
            // $stmt->close();

            // Commit transaction
            $conn->commit();

            // Set success message
            $nurse_application_message = 'Your application has been submitted successfully! We will review your information and send a response to your email.';

            // You would typically send an email notification here
            // mail($email, "Nurse Application Received", "Thank you for your application...");

        } catch (Exception $e) {
            // Rollback transaction on error
            $conn->rollback();
            $errors[] = 'Registration failed: ' . $e->getMessage();
        }
    }

    // If there were errors, prepare them for display
    if (!empty($errors)) {
        $register_error = implode('<br>', $errors);
    }
}
?>



<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home Care - Home Nursing Services</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #0d6efd;
            --secondary: #6c757d;
            --success: #198754;
            --info: #0dcaf0;
            --warning: #ffc107;
            --danger: #dc3545;
            --light: #f8f9fa;
            --dark: #212529;
            --teal: #20c997;
            --indigo: #6610f2;
        }

        body {
            font-family: 'Poppins', sans-serif;
            color: #333;
            overflow-x: hidden;
        }

        .hero-section {
            background: linear-gradient(135deg, rgba(13, 110, 253, 0.9) 0%, rgba(32, 201, 151, 0.9) 100%),
                url('https://images.unsplash.com/photo-1576091160550-2173dba999ef?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1470&q=80');
            background-size: cover;
            background-position: center;
            color: white;
            padding: 100px 0;
            position: relative;

            min-height: 100vh;
            height: 100vh;
            padding: 0 !important;
            margin: 0 !important;
        }

        .hero-section .container {
            height: 100%;
            display: flex;
            align-items: center;
            /* Vertical center */
            justify-content: center;
            /* Horizontal center */
        }

        .navbar {
            transition: all 0.3s;
        }

        .navbar.scrolled {
            background-color: white !important;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .navbar.scrolled .nav-link {
            color: var(--dark) !important;
        }

        .navbar.scrolled .navbar-brand {
            color: var(--primary) !important;
        }

        .feature-icon {
            width: 70px;
            height: 70px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 28px;
        }

        .service-card {
            transition: all 0.3s;
            border: none;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
        }

        .service-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.1);
        }

        .testimonial-card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            transition: all 0.3s;
        }

        .testimonial-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }

        .bg-teal {
            background-color: var(--teal) !important;
        }

        .btn-teal {
            background-color: var(--teal);
            color: white;
        }

        .btn-teal:hover {
            background-color: #1aa179;
            color: white;
        }

        .login-modal .modal-dialog {
            max-width: 400px;
        }

        .login-tabs .nav-link {
            color: var(--dark);
            font-weight: 500;
            border: none;
            padding: 12px 20px;
        }

        .login-tabs .nav-link.active {
            color: var(--primary);
            background-color: transparent;
            border-bottom: 3px solid var(--primary);
        }

        .how-it-works-step {
            position: relative;
            padding-left: 80px;
            margin-bottom: 40px;
        }

        .how-it-works-step-number {
            position: absolute;
            left: 0;
            top: 0;
            width: 60px;
            height: 60px;
            background-color: var(--primary);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            font-weight: bold;
        }

        footer {
            background-color: #2c3e50;
            color: white;
        }

        .footer-links a {
            color: rgba(255, 255, 255, 0.7);
            text-decoration: none;
            transition: color 0.3s;
        }

        footer .contact-us li,
        footer .footer-paragraph {
            color: rgba(255, 255, 255, 0.7);
        }

        .footer-links a:hover {
            color: white;
        }

        .social-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: rgba(255, 255, 255, 0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s;
        }

        .social-icon:hover {
            background-color: var(--primary);
            transform: translateY(-3px);
        }
    </style>
</head>

<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark fixed-top">
        <div class="container">
            <a class="navbar-brand fw-bold" href="#">
                <i class="fas fa-hand-holding-medical me-2"></i>
                <?php echo $settings['SiteName'] ?>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="#home">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#services">Services</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#how-it-works">How It Works</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#about">About Us</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#testimonials">Testimonials</a>
                    </li>
                </ul>
                <div class="ms-lg-3 mt-3 mt-lg-0">
                    <button class="btn btn-outline-light me-2" data-bs-toggle="modal" data-bs-target="#loginModal">Login</button>
                    <button class="btn btn-light" data-bs-toggle="modal" data-bs-target="#registerModal">Register</button>
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section id="home" class="hero-section">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <h1 class="display-4 fw-bold mb-4">Professional Home Nursing Care When You Need It</h1>
                    <p class="lead mb-4">Connecting patients with qualified nurses for personalized in-home healthcare services.</p>
                    <div class="d-flex flex-wrap gap-3">
                        <a href="#services" class="btn btn-light btn-lg px-4">Our Services</a>
                        <button class="btn btn-outline-light btn-lg px-4" data-bs-toggle="modal" data-bs-target="#registerModal">Get Started</button>
                    </div>
                </div>
                <div class="col-lg-6 d-none d-lg-block">
                    <img src="https://images.unsplash.com/photo-1588776814546-1ffcf47267a5?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1470&q=80" alt="Nurse with patient" class="img-fluid rounded shadow-lg">
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="py-5 bg-light">
        <div class="container py-5">
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="text-center p-4">
                        <div class="feature-icon bg-primary bg-opacity-10 text-primary ">
                            <i class="fas fa-user-clock"></i>
                        </div>
                        <h3 class="h4">On-Demand Care</h3>
                        <p class="text-muted">Request nursing services whenever you need them, with flexible scheduling options.</p>

                        <?php
                        // $newPassword = "staff123";
                        // $hashedPassword1 = password_hash($newPassword, PASSWORD_BCRYPT);
                        // echo $hashedPassword1 ;
                        ?>

                    </div>
                </div>
                <div class="col-md-4">
                    <div class="text-center p-4">
                        <div class="feature-icon bg-success bg-opacity-10 text-success">
                            <i class="fas fa-shield-alt"></i>
                        </div>
                        <h3 class="h4">Verified Professionals</h3>
                        <p class="text-muted">All nurses are licensed, experienced, and thoroughly vetted by our team.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="text-center p-4">
                        <div class="feature-icon bg-warning bg-opacity-10 text-warning">
                            <i class="fas fa-heartbeat"></i>
                        </div>
                        <h3 class="h4">Personalized Care</h3>
                        <p class="text-muted">Tailored healthcare services designed to meet your specific needs and preferences.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Services Section -->
    <section id="services" class="py-5">
        <div class="container py-5">
            <div class="row justify-content-center mb-5">
                <div class="col-lg-8 text-center">
                    <h2 class="display-5 fw-bold mb-3">Our Services</h2>
                    <p class="lead text-muted">Comprehensive home nursing services to support your health and recovery.</p>
                </div>
            </div>

            <div class="row g-4">
                <div class="col-md-6 col-lg-4">
                    <div class="service-card card h-100">
                        <img src="https://images.unsplash.com/photo-1579684385127-1ef15d508118?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1470&q=80" class="card-img-top" alt="Wound Care">
                        <div class="card-body">
                            <h3 class="h5 card-title">Wound Care</h3>
                            <p class="card-text text-muted">Professional wound assessment, dressing changes, and management of acute or chronic wounds.</p>
                            <ul class="list-unstyled text-muted">
                                <li class="mb-1"><i class="fas fa-check-circle text-success me-2"></i> Post-surgical care</li>
                                <li class="mb-1"><i class="fas fa-check-circle text-success me-2"></i> Ulcer management</li>
                                <li class="mb-1"><i class="fas fa-check-circle text-success me-2"></i> Burn treatment</li>
                            </ul>
                        </div>
                        <div class="card-footer bg-transparent border-0">
                            <!-- <button class="btn btn-sm btn-outline-primary">Learn More</button> -->
                        </div>
                    </div>
                </div>

                <div class="col-md-6 col-lg-4">
                    <div class="service-card card h-100">
                        <img src="https://images.unsplash.com/photo-1584308666744-24d5c474f2ae?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1470&q=80" class="card-img-top" alt="Medication Management">
                        <div class="card-body">
                            <h3 class="h5 card-title">Medication Management</h3>
                            <p class="card-text text-muted">Safe administration of medications, injections, and IV therapy in the comfort of your home.</p>
                            <ul class="list-unstyled text-muted">
                                <li class="mb-1"><i class="fas fa-check-circle text-success me-2"></i> Oral medications</li>
                                <li class="mb-1"><i class="fas fa-check-circle text-success me-2"></i> Insulin injections</li>
                                <li class="mb-1"><i class="fas fa-check-circle text-success me-2"></i> IV therapy</li>
                            </ul>
                        </div>
                        <div class="card-footer bg-transparent border-0">
                            <!-- <button class="btn btn-sm btn-outline-primary">Learn More</button> -->
                        </div>
                    </div>
                </div>

                <div class="col-md-6 col-lg-4">
                    <div class="service-card card h-100">
                        <img src="https://images.unsplash.com/photo-1530026186672-2cd00ffc50fe?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1470&q=80" class="card-img-top" alt="Elderly Care">
                        <div class="card-body">
                            <h3 class="h5 card-title">Elderly Care</h3>
                            <p class="card-text text-muted">Compassionate care for seniors including assistance with daily activities and health monitoring.</p>
                            <ul class="list-unstyled text-muted">
                                <li class="mb-1"><i class="fas fa-check-circle text-success me-2"></i> Personal care</li>
                                <li class="mb-1"><i class="fas fa-check-circle text-success me-2"></i> Mobility assistance</li>
                                <li class="mb-1"><i class="fas fa-check-circle text-success me-2"></i> Companionship</li>
                            </ul>
                        </div>
                        <div class="card-footer bg-transparent border-0">
                            <!-- <button class="btn btn-sm btn-outline-primary">Learn More</button> -->
                        </div>
                    </div>
                </div>
            </div>

            <div class="text-center mt-5">
                <a href="#" class="btn btn-primary px-4">View All Services</a>
            </div>
        </div>
    </section>

    <!-- How It Works Section -->
    <section id="how-it-works" class="py-5 bg-light">
        <div class="container py-5">
            <div class="row justify-content-center mb-5">
                <div class="col-lg-8 text-center">
                    <h2 class="display-5 fw-bold mb-3">How Home Care Works</h2>
                    <p class="lead text-muted">Getting quality home nursing care has never been easier.</p>
                </div>
            </div>

            <div class="row">
                <div class="col-lg-10 mx-auto">
                    <div class="how-it-works-step">
                        <div class="how-it-works-step-number">1</div>
                        <div>
                            <h3 class="h4">Create Your Account</h3>
                            <p class="text-muted">Register as a patient and complete your profile with health information and preferences. Our simple onboarding process takes just a few minutes.</p>
                        </div>
                    </div>

                    <div class="how-it-works-step">
                        <div class="how-it-works-step-number">2</div>
                        <div>
                            <h3 class="h4">Request a Service</h3>
                            <p class="text-muted">Select the type of care you need, choose your preferred date and time, and provide any special instructions for our nurses.</p>
                        </div>
                    </div>

                    <div class="how-it-works-step">
                        <div class="how-it-works-step-number">3</div>
                        <div>
                            <h3 class="h4">Get Matched</h3>
                            <p class="text-muted">Our system matches you with the most qualified available nurse based on your needs, location, and preferences.</p>
                        </div>
                    </div>

                    <div class="how-it-works-step">
                        <div class="how-it-works-step-number">4</div>
                        <div>
                            <h3 class="h4">Receive Care</h3>
                            <p class="text-muted">Your nurse arrives at your home at the scheduled time, provides professional care, and documents the visit in your secure health record.</p>
                        </div>
                    </div>

                    <div class="how-it-works-step">
                        <div class="how-it-works-step-number">5</div>
                        <div>
                            <h3 class="h4">Rate Your Experience</h3>
                            <p class="text-muted">After your visit, provide feedback to help us maintain our high standards of care and improve our services.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- About Section -->
    <section id="about" class="py-5">
        <div class="container py-5">
            <div class="row align-items-center">
                <div class="col-lg-6 mb-5 mb-lg-0">
                    <img src="https://images.unsplash.com/photo-1576091160399-112ba8d25d1d?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1470&q=80" alt="About Us" class="img-fluid rounded shadow-lg">
                </div>
                <div class="col-lg-6">
                    <h2 class="display-5 fw-bold mb-4">About <?php echo $settings['SiteName'] ?></h2>
                    <p class="lead text-muted mb-4">Bridging the gap between patients and professional nursing care.</p>
                    <p><?php echo $settings['SiteName'] ?> was founded in 2023 with a mission to make quality healthcare accessible to everyone in the comfort of their homes. Our platform connects patients with a network of highly skilled and compassionate nurses who provide personalized care tailored to individual needs.</p>
                    <p>We understand the challenges of accessing healthcare services, especially for those with mobility issues or chronic conditions. That's why we've created a seamless digital experience that puts you in control of your care.</p>
                    <div class="row mt-4">
                        <div class="col-md-6">
                            <div class="d-flex align-items-center mb-3">
                                <div class="bg-primary bg-opacity-10 text-primary rounded-circle p-3 me-3">
                                    <i class="fas fa-user-md"></i>
                                </div>
                                <div>
                                    <h4 class="h6 mb-0">100+ Nurses</h4>
                                    <small class="text-muted">In our network</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="d-flex align-items-center mb-3">
                                <div class="bg-success bg-opacity-10 text-success rounded-circle p-3 me-3">
                                    <i class="fas fa-smile"></i>
                                </div>
                                <div>
                                    <h4 class="h6 mb-0">500+ Patients</h4>
                                    <small class="text-muted">Served monthly</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Testimonials Section -->
    <section id="testimonials" class="py-5 bg-light">
        <div class="container py-5">
            <div class="row justify-content-center mb-5">
                <div class="col-lg-8 text-center">
                    <h2 class="display-5 fw-bold mb-3">What Our Patients Say</h2>
                    <p class="lead text-muted">Hear from people who have experienced our home nursing services.</p>
                </div>
            </div>

            <div class="row g-4">
                <div class="col-md-6 col-lg-4">
                    <div class="testimonial-card card h-100">
                        <div class="card-body">
                            <div class="d-flex mb-3">
                                <div class="position-relative avatar bg-primary bg-opacity-10 text-primary rounded-circle me-3" style="width: 50px; height: 50px;">
                                    <i class="fas fa-user" style="position: absolute; left: 50%; top: 50%; transform: translate(-50% , -50%);"></i>
                                </div>
                                <div>
                                    <h5 class="mb-0">Sarah Johnson</h5>
                                    <small class="text-muted">Post-Surgical Patient</small>
                                </div>
                            </div>
                            <p class="card-text">"The nurse from <?php echo $settings['SiteName'] ?> was incredibly professional and caring after my knee surgery. She made sure I understood all my medications and helped me with my physical therapy exercises."</p>
                            <div class="text-warning">
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-6 col-lg-4">
                    <div class="testimonial-card card h-100">
                        <div class="card-body">
                            <div class="d-flex mb-3">
                                <div class="position-relative avatar bg-success bg-opacity-10 text-success rounded-circle me-3" style="width: 50px; height: 50px;">
                                    <i class="fas fa-user" style="position: absolute; left: 50%; top: 50%; transform: translate(-50% , -50%);"></i>
                                </div>
                                <div>
                                    <h5 class="mb-0">Michael Brown</h5>
                                    <small class="text-muted">Diabetes Patient</small>
                                </div>
                            </div>
                            <p class="card-text">"As someone with diabetes, having a nurse come to my home for regular check-ups has been life-changing. The convenience and quality of care are unmatched."</p>
                            <div class="text-warning">
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-6 col-lg-4">
                    <div class="testimonial-card card h-100">
                        <div class="card-body">
                            <div class="d-flex mb-3">
                                <div class="position-relative avatar bg-warning bg-opacity-10 text-warning rounded-circle me-3" style="width: 50px; height: 50px;">
                                    <i class="fas fa-user" style="position: absolute; left: 50%; top: 50%; transform: translate(-50% , -50%);"></i>
                                </div>
                                <div>
                                    <h5 class="mb-0">Emily Davis</h5>
                                    <small class="text-muted">Elderly Care</small>
                                </div>
                            </div>
                            <p class="card-text">"The care my mother receives through <?php echo $settings['SiteName'] ?> has given our family peace of mind. The nurses are compassionate and truly go above and beyond."</p>
                            <div class="text-warning">
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star-half-alt"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="py-5 bg-primary text-white">
        <div class="container py-5">
            <div class="row justify-content-center">
                <div class="col-lg-8 text-center">
                    <h2 class="display-5 fw-bold mb-4">Ready to Experience Better Home Healthcare?</h2>
                    <p class="lead mb-4">Join thousands of patients who have transformed their healthcare experience with <?php echo $settings['SiteName'] ?>.</p>
                    <div class="d-flex flex-wrap justify-content-center gap-3">
                        <button class="btn btn-light btn-lg px-4" data-bs-toggle="modal" data-bs-target="#registerModal">Get Started</button>
                        <a href="#how-it-works" class="btn btn-outline-light btn-lg px-4">Learn More</a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="py-5">
        <div class="container py-4">
            <div class="row g-4">
                <div class="col-lg-4">
                    <h3 class="h4 text-white mb-4">
                        <i class="fas fa-hand-holding-medical me-2"></i>
                        <?php echo $settings['SiteName'] ?>
                    </h3>
                    <p class=" footer-paragraph">Connecting patients with professional nursing care in the comfort of their homes.</p>
                    <div class="d-flex gap-3 mt-4">
                        <a href="#" class="social-icon"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" class="social-icon"><i class="fab fa-twitter"></i></a>
                        <a href="#" class="social-icon"><i class="fab fa-linkedin-in"></i></a>
                        <a href="#" class="social-icon"><i class="fab fa-instagram"></i></a>
                    </div>
                </div>

                <div class="col-lg-2 col-md-4">
                    <h4 class="h5 text-white mb-4">Quick Links</h4>
                    <ul class="list-unstyled footer-links">
                        <li class="mb-2"><a href="#home">Home</a></li>
                        <li class="mb-2"><a href="#services">Services</a></li>
                        <li class="mb-2"><a href="#how-it-works">How It Works</a></li>
                        <li class="mb-2"><a href="#about">About Us</a></li>
                        <li class="mb-2"><a href="#testimonials">Testimonials</a></li>
                    </ul>
                </div>

                <div class="col-lg-2 col-md-4">
                    <h4 class="h5 text-white mb-4">Services</h4>
                    <ul class="list-unstyled footer-links">
                        <li class="mb-2"><a href="#">Wound Care</a></li>
                        <li class="mb-2"><a href="#">Medication Management</a></li>
                        <li class="mb-2"><a href="#">Elderly Care</a></li>
                        <li class="mb-2"><a href="#">Physical Therapy</a></li>
                        <li class="mb-2"><a href="#">Post-Surgical Care</a></li>
                    </ul>
                </div>

                <div class="col-lg-4 col-md-4">
                    <h4 class="h5 text-white mb-4">Contact Us</h4>
                    <ul class="list-unstyled text-muted contact-us">
                        <li class="mb-2"><i class="fas fa-map-marker-alt me-2"></i> <?php echo $settings['Location'] ?></li>
                        <li class="mb-2 "><i class="fas fa-phone me-2"></i> <?php echo $settings['ContactPhone'] ?></li>
                        <li class="mb-2 "><i class="fas fa-envelope me-2"></i> <?php echo $settings['ContactEmail'] ?></li>
                    </ul>
                </div>
            </div>

            <hr class="my-4 bg-secondary">

            <div class="row">
                <div class="col-md-6 text-center text-md-start">
                    <p class="small text-muted mb-0">&copy; 2023 <?php echo $settings['SiteName'] ?>. All rights reserved.</p>
                </div>
                <div class="col-md-6 text-center text-md-end">
                    <p class="small text-muted mb-0">Designed with <i class="fas fa-heart text-danger"></i> for better healthcare</p>
                </div>
            </div>
        </div>
    </footer>

    <!-- Login Modal -->
    <div class="modal fade login-modal" id="loginModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header border-0">
                    <h5 class="modal-title">Login to <?php echo $settings['SiteName'] ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <?php if (!empty($login_error)): ?>
                        <div class="alert alert-danger"><?php echo $login_error; ?></div>
                    <?php endif; ?>

                    <form method="POST" action="">
                        <div class="mb-3">
                            <label for="loginEmail" class="form-label">Email address</label>
                            <input type="email" class="form-control" id="loginEmail" name="email" required>
                        </div>
                        <div class="mb-3">
                            <label for="loginPassword" class="form-label">Password</label>
                            <input type="password" class="form-control" id="loginPassword" name="password" required>
                        </div>
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="rememberMe">
                            <label class="form-check-label" for="rememberMe">Remember me</label>
                        </div>
                        <button type="submit" name="login" class="btn btn-primary w-100">Login</button>
                    </form>

                    <div class="text-center mt-3">
                        <a href="#" class="text-decoration-none">Forgot password?</a>
                    </div>
                </div>
            </div>
        </div>
    </div>




    <!-- Register Modal -->
    <div class="modal fade" id="registerModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header border-0">
                    <h5 class="modal-title">Create Your Account</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <ul class="nav nav-tabs login-tabs border-0 mb-4" id="registerTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="patient-reg-tab" data-bs-toggle="tab" data-bs-target="#patient-reg" type="button" role="tab">Patient</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="nurse-reg-tab" data-bs-toggle="tab" data-bs-target="#nurse-reg" type="button" role="tab">Nurse</button>
                        </li>
                    </ul>

                    <div class="tab-content" id="registerTabsContent">



                        <!-- patient registration -->
                        <div class="tab-pane fade show active" id="patient-reg" role="tabpanel">
                            <?php if (!empty($register_error)): ?>
                                <div class="alert alert-danger"><?php echo $register_error; ?></div>
                            <?php endif; ?>

                            <form method="POST" action="">
                                <div class="row mb-3">
                                    <div class="col-md-12">
                                        <label for="full_name" class="form-label">Full Name</label>
                                        <input type="text" class="form-control" id="full_name" name="full_name" required>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label for="patientRegEmail" class="form-label">Email address</label>
                                    <input type="email" class="form-control" id="patientRegEmail" name="email" required>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="gender" class="form-label">Gender</label>
                                        <select class="form-control" id="gender" name="gender" required>
                                            <option value="">Select Gender</option>
                                            <option value="Male">Male</option>
                                            <option value="Female">Female</option>
                                            <option value="Other">Other</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="date_of_birth" class="form-label">Date of Birth</label>
                                        <input type="date" class="form-control" id="date_of_birth" name="date_of_birth" required>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label for="phone_number" class="form-label">Phone Number</label>
                                    <input type="tel" class="form-control" id="phone_number" name="phone_number" required>
                                </div>
                                <!-- Address Section -->
                                <h5 class="mb-3 mt-4">Address Information</h5>
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="patientCountry" class="form-label">Country</label>
                                        <input type="text" class="form-control" id="patientCountry" name="country" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="patientCity" class="form-label">City</label>
                                        <input type="text" class="form-control" id="patientCity" name="city" required>
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="patientStreet" class="form-label">Street</label>
                                        <input type="text" class="form-control" id="patientStreet" name="street" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="patientBuilding" class="form-label">Building</label>
                                        <input type="text" class="form-control" id="patientBuilding" name="building" required>
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="patientLatitude" class="form-label">Latitude</label>
                                        <input type="text" class="form-control" id="patientLatitude" name="latitude" >
                                    </div>
                                    <div class="col-md-6">
                                        <label for="patientLongitude" class="form-label">Longitude</label>
                                        <input type="text" class="form-control" id="patientLongitude" name="longitude" >
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label for="patientAddressNotes" class="form-label">Address Notes</label>
                                    <textarea class="form-control" id="patientAddressNotes" name="address_notes" rows="2"></textarea>
                                </div>
                                <div class="mb-3">
                                    <button type="button" class="btn btn-outline-primary" id="patientGetLocationBtn">
                                        <i class="fas fa-map-marker-alt"></i> Use My Current Location
                                    </button>
                                </div>
                                <div class="mb-3">
                                    <label for="patientRegPassword" class="form-label">Password</label>
                                    <input type="password" class="form-control" id="patientRegPassword" name="password" required>
                                </div>
                                <div class="mb-3">
                                    <label for="patientConfirmPassword" class="form-label">Confirm Password</label>
                                    <input type="password" class="form-control" id="patientConfirmPassword" name="confirm_password" required>
                                </div>
                                <div class="mb-3 form-check">
                                    <input type="checkbox" class="form-check-input" id="agreeTerms" name="agree_terms" required>
                                    <label class="form-check-label" for="agreeTerms">I agree to the <a href="#">Terms of Service</a> and <a href="#">Privacy Policy</a></label>
                                </div>
                                <button type="submit" name="register_patient" class="btn btn-primary w-100">Register as Patient</button>
                            </form>
                        </div>
                        <!-- patient registration -->



                        <!-- from here -->
                        <div class="tab-pane fade" id="nurse-reg" role="tabpanel">
                            <?php if (!empty($register_error)): ?>
                                <div class="alert alert-danger"><?php echo $register_error; ?></div>
                            <?php endif; ?>

                            <?php if (!empty($nurse_application_message)): ?>
                                <div class="alert alert-success"><?php echo $nurse_application_message; ?></div>
                            <?php else: ?>
                                <form id="nurseRegistrationForm" method="POST" action="" enctype="multipart/form-data">
                                    <!-- Personal Information Section -->
                                    <h5 class="mb-3">Personal Information</h5>
                                    <div class="row mb-3">
                                        <div class="col-md-12">
                                            <label for="nurseFullName" class="form-label">Full Name</label>
                                            <input type="text" class="form-control" id="nurseFullName" name="FullName" required>
                                        </div>
                                    </div>
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label for="nurseDateOfBirth" class="form-label">Date of Birth</label>
                                            <input type="date" class="form-control" id="nurseDateOfBirth" name="DateOfBirth" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="nurseGender" class="form-label">Gender</label>
                                            <select class="form-control" id="nurseGender" name="Gender" required>
                                                <option value="">Select Gender</option>
                                                <option value="Male">Male</option>
                                                <option value="Female">Female</option>
                                                <option value="Other">Other</option>
                                            </select>
                                        </div>
                                    </div>

                                    <!-- Contact Information Section -->
                                    <h5 class="mb-3 mt-4">Contact Information</h5>
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label for="nursePhoneNumber" class="form-label">Phone Number</label>
                                            <input type="tel" class="form-control" id="nursePhoneNumber" name="PhoneNumber" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="nurseEmail" class="form-label">Email</label>
                                            <input type="email" class="form-control" id="nurseEmail" name="Email" required>
                                        </div>
                                    </div>

                                    <!-- Address Section -->
                                    <h5 class="mb-3 mt-4">Address Information</h5>
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label for="country" class="form-label">Country</label>
                                            <input type="text" class="form-control" id="country" name="Country" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="city" class="form-label">City</label>
                                            <input type="text" class="form-control" id="city" name="City" required>
                                        </div>
                                    </div>
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label for="street" class="form-label">Street</label>
                                            <input type="text" class="form-control" id="street" name="Street" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="building" class="form-label">Building</label>
                                            <input type="text" class="form-control" id="building" name="Building" required>
                                        </div>
                                    </div>
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label for="latitude" class="form-label">Latitude</label>
                                            <input type="text" class="form-control" id="latitude" name="Latitude" readonly>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="longitude" class="form-label">Longitude</label>
                                            <input type="text" class="form-control" id="longitude" name="Longitude" readonly>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label for="addressNotes" class="form-label">Address Notes</label>
                                        <textarea class="form-control" id="addressNotes" name="Notes" rows="2"></textarea>
                                    </div>
                                    <div class="mb-3">
                                        <button type="button" class="btn btn-outline-primary" id="getLocationBtn">
                                            <i class="fas fa-map-marker-alt"></i> Use My Current Location
                                        </button>
                                    </div>

                                    <!-- Professional Information Section -->
                                    <h5 class="mb-3 mt-4">Professional Information</h5>
                                    <div class="mb-3">
                                        <label for="nursePicture" class="form-label">Profile Picture</label>
                                        <input type="file" class="form-control" id="nursePicture" name="Picture" accept="image/*">
                                    </div>
                                    <div class="mb-3">
                                        <label for="nurseCV" class="form-label">Upload CV</label>
                                        <input type="file" class="form-control" id="nurseCV" name="URL_CV" accept=".pdf,.doc,.docx" required>
                                    </div>
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label for="nurseLanguage" class="form-label">Language(s)</label>
                                            <input type="text" class="form-control" id="nurseLanguage" name="Language" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="nurseSyndicateNumber" class="form-label">Syndicate Number</label>
                                            <input type="text" class="form-control" id="nurseSyndicateNumber" name="SyndicateNumber" required>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label for="nurseSpecialization" class="form-label">Specialization</label>
                                        <input type="text" class="form-control" id="nurseSpecialization" name="Specialization" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="nurseBio" class="form-label">Professional Bio</label>
                                        <textarea class="form-control" id="nurseBio" name="Bio" rows="3"></textarea>
                                    </div>
                                    <div class="mb-3">
                                        <label for="nurseComments" class="form-label">Additional Comments</label>
                                        <textarea class="form-control" id="nurseComments" name="Comments" rows="3"></textarea>
                                    </div>

                                    <!-- Account Security Section -->
                                    <!-- <h5 class="mb-3 mt-4">Account Security</h5>
                                    <div class="mb-3">
                                        <label for="nurseRegPassword" class="form-label">Password</label>
                                        <input type="password" class="form-control" id="nurseRegPassword" name="Password" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="nurseConfirmPassword" class="form-label">Confirm Password</label>
                                        <input type="password" class="form-control" id="nurseConfirmPassword" name="confirm_password" required>
                                    </div> -->

                                    <!-- Terms and Submission -->
                                    <!-- <div class="mb-3 form-check">
                                        <input type="checkbox" class="form-check-input" id="nurseAgreeTerms" name="agree_terms" required>
                                        <label class="form-check-label" for="nurseAgreeTerms">I agree to the <a href="#">Terms of Service</a> and <a href="#">Privacy Policy</a></label>
                                    </div> -->
                                    <button type="submit" name="register_nurse" class="btn btn-primary w-100">Apply as Nurse</button>
                                </form>
                            <?php endif; ?>
                        </div>
                        <!-- to here -->

                    </div>

                    <div class="text-center mt-3">
                        <p class="mb-0">Already have an account? <a href="#" data-bs-toggle="modal" data-bs-target="#loginModal" data-bs-dismiss="modal">Login</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Custom JS -->
    <script>
        // Navbar scroll effect
        window.addEventListener('scroll', function() {
            const navbar = document.querySelector('.navbar');
            if (window.scrollY > 50) {
                navbar.classList.add('scrolled');
            } else {
                navbar.classList.remove('scrolled');
            }
        });

        // Initialize tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        var tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });

        // Smooth scrolling for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function(e) {
                e.preventDefault();

                const targetId = this.getAttribute('href');
                const targetElement = document.querySelector(targetId);

                if (targetElement) {
                    window.scrollTo({
                        top: targetElement.offsetTop - 70,
                        behavior: 'smooth'
                    });

                    // Close mobile menu if open
                    const navbarCollapse = document.querySelector('.navbar-collapse');
                    if (navbarCollapse.classList.contains('show')) {
                        new bootstrap.Collapse(navbarCollapse).hide();
                    }
                }
            });
        });

        // Form validation for registration
        // document.querySelectorAll('#registerModal form').forEach(form => {
        //     form.addEventListener('submit', function(e) {
        //         e.preventDefault();
        //         // In a real app, you would validate and submit to your PHP backend
        //         alert('Registration form submitted! In a real app, this would be processed by your backend.');
        //         bootstrap.Modal.getInstance(document.getElementById('registerModal')).hide();
        //     });
        // });

        // Form validation for login
        // document.querySelectorAll('#loginModal form').forEach(form => {
        //     form.addEventListener('submit', function(e) {
        //         e.preventDefault();
        //         // In a real app, you would validate and submit to your PHP backend
        //         alert('Login form submitted! In a real app, this would be processed by your backend.');
        //         bootstrap.Modal.getInstance(document.getElementById('loginModal')).hide();
        //     });
        // });


        document.addEventListener('DOMContentLoaded', function() {
            const getLocationBtn = document.getElementById('getLocationBtn');

            if (getLocationBtn) {
                getLocationBtn.addEventListener('click', function() {
                    if (navigator.geolocation) {
                        navigator.geolocation.getCurrentPosition(
                            function(position) {
                                // Set latitude and longitude
                                document.getElementById('latitude').value = position.coords.latitude;
                                document.getElementById('longitude').value = position.coords.longitude;

                                // Reverse geocoding to get address details
                                fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${position.coords.latitude}&lon=${position.coords.longitude}`)
                                    .then(response => response.json())
                                    .then(data => {
                                        if (data.address) {
                                            document.getElementById('country').value = data.address.country || '';
                                            document.getElementById('city').value = data.address.city || data.address.town || data.address.village || '';
                                            document.getElementById('street').value = data.address.road || '';
                                            document.getElementById('building').value = data.address.house_number || '';
                                        }
                                    })
                                    .catch(error => {
                                        console.error('Error fetching address details:', error);
                                    });
                            },
                            function(error) {
                                alert('Error getting location: ' + error.message);
                            }
                        );
                    } else {
                        alert('Geolocation is not supported by your browser.');
                    }
                });
            }
        });



        // Add this to your existing JavaScript
        document.addEventListener('DOMContentLoaded', function() {
            const patientGetLocationBtn = document.getElementById('patientGetLocationBtn');

            if (patientGetLocationBtn) {
                patientGetLocationBtn.addEventListener('click', function() {
                    if (navigator.geolocation) {
                        navigator.geolocation.getCurrentPosition(
                            function(position) {
                                // Set latitude and longitude
                                document.getElementById('patientLatitude').value = position.coords.latitude;
                                document.getElementById('patientLongitude').value = position.coords.longitude;

                                // Reverse geocoding to get address details
                                fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${position.coords.latitude}&lon=${position.coords.longitude}`)
                                    .then(response => response.json())
                                    .then(data => {
                                        if (data.address) {
                                            document.getElementById('patientCountry').value = data.address.country || '';
                                            document.getElementById('patientCity').value = data.address.city || data.address.town || data.address.village || '';
                                            document.getElementById('patientStreet').value = data.address.road || '';
                                            document.getElementById('patientBuilding').value = data.address.house_number || '';
                                        }
                                    })
                                    .catch(error => {
                                        console.error('Error fetching address details:', error);
                                    });
                            },
                            function(error) {
                                alert('Error getting location: ' + error.message);
                            }
                        );
                    } else {
                        alert('Geolocation is not supported by your browser.');
                    }
                });
            }
        });
    </script>
</body>

</html>