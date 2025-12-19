<?php
include '../connect.php';

$alert_message = '';
$alert_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = isset($_POST['fullName']) ? $_POST['fullName'] : '';
    $email = isset($_POST['email']) ? $_POST['email'] : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $phoneNumber = isset($_POST['phoneNumber']) ? $_POST['phoneNumber'] : '';
    $gender = isset($_POST['gender']) ? $_POST['gender'] : '';
    $dateOfBirth = isset($_POST['dateOfBirth']) ? $_POST['dateOfBirth'] : '';
    $country = isset($_POST['country']) ? $_POST['country'] : '';
    $city = isset($_POST['city']) ? $_POST['city'] : '';
    $street = isset($_POST['street']) ? $_POST['street'] : '';

    // Validate required fields
    if (empty($fullName) || empty($email) || empty($password) || empty($phoneNumber) || empty($gender) || empty($dateOfBirth) || empty($country) || empty($city) || empty($street)) {
        $alert_message = 'Error: All required fields must be filled';
        $alert_type = 'danger';
    // } elseif (!str_ends_with(strtolower($email), '@gmail.com')) {
    //     $alert_message = 'Error: Email must be a Gmail address (e.g., example@gmail.com)';
    //     $alert_type = 'danger';
    } 
    else {
        // Check if email already exists
        $check_email_query = "SELECT UserID FROM user WHERE Email = '$email'";
        $check_email_result = mysqli_query($conn, $check_email_query);
        if (!$check_email_result) {
            $alert_message = 'Error checking email: ' . mysqli_error($conn);
            $alert_type = 'danger';
        } elseif (mysqli_num_rows($check_email_result) > 0) {
            $alert_message = 'Error: Email already exists';
            $alert_type = 'danger';
        } else {
            // Insert into address table
            $address_query = "INSERT INTO address (Country, City, Street) 
                              VALUES ('$country', '$city', '$street')";
            if (mysqli_query($conn, $address_query)) {
                $address_id = mysqli_insert_id($conn);

                // Insert into user table
                $user_query = "INSERT INTO user (FullName, Gender, DateOfBirth, PhoneNumber, Email, Password, Role, Status, AddressID) 
                               VALUES ('$fullName', '$gender', '$dateOfBirth', '$phoneNumber', '$email', '$password', 'staff', 'active', $address_id)";
                if (mysqli_query($conn, $user_query)) {
                    $user_id = mysqli_insert_id($conn);

                    // Insert into staff table
                    $staff_query = "INSERT INTO staff (UserID) VALUES ($user_id)";
                    if (mysqli_query($conn, $staff_query)) {
                        $alert_message = 'Staff added successfully!';
                        $alert_type = 'success';
                    } else {
                        $alert_message = 'Error adding staff to staff table: ' . mysqli_error($conn);
                        $alert_type = 'danger';
                    }
                } else {
                    $alert_message = 'Error adding user: ' . mysqli_error($conn);
                    $alert_type = 'danger';
                }
            } else {
                $alert_message = 'Error adding address: ' . mysqli_error($conn);
                $alert_type = 'danger';
            }
        }
    }
}

// Redirect to users.php with the message and type
header('Location: users.php?message=' . urlencode($alert_message) . '&type=' . urlencode($alert_type));
exit;