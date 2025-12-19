<?php
session_start();
require_once 'db_connection.php';
require_once 'send_email.php'; // Include the PHPMailer email function

// Initialize variables
$message = '';
$error = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['approve_application'])) {
        // Approve application
        $naid = $_POST['naid'];
        $username = $_POST['username'];
        $password = $_POST['password'];

        // Get application details
        $stmt = $conn->prepare("SELECT * FROM nurseapplication WHERE NAID = ?");
        $stmt->bind_param("i", $naid);
        $stmt->execute();
        $application = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($application) {
            try {
                // Begin transaction
                $conn->begin_transaction();

                // Insert into user table
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("INSERT INTO user (FullName, Gender, DateOfBirth, PhoneNumber, Email, Password, Role, Status) 
                                     VALUES (?, ?, ?, ?, ?, ?, 'nurse', 'active')");
                $stmt->bind_param(
                    "ssssss",
                    $application['FullName'],
                    $application['Gender'],
                    $application['DateOfBirth'],
                    $application['PhoneNumber'],
                    $application['Email'],
                    $hashed_password
                );
                $stmt->execute();
                $user_id = $stmt->insert_id;
                $stmt->close();

                // Insert into nurse table - linking to both user and nurse application
                $stmt = $conn->prepare("INSERT INTO nurse (NAID, UserID, Availability, Bio) VALUES (?, ?, 1, ?)");
                $bio = "Professional nurse specializing in " . $application['Specialization'];
                $stmt->bind_param("iis", $naid, $user_id, $bio);
                $stmt->execute();
                $nurse_id = $stmt->insert_id;
                $stmt->close();

                // Update application status
                $stmt = $conn->prepare("UPDATE nurseapplication SET Status = 'approved' WHERE NAID = ?");
                $stmt->bind_param("i", $naid);
                $stmt->execute();
                $stmt->close();

                // Commit transaction
                $conn->commit();

                // Send email with credentials
                $subject = "Your Nurse Application Has Been Approved";
                $email_message = "Dear " . $application['FullName'] . ",\n\n";
                $email_message .= "Your application has been approved. Here are your login credentials:\n";
                $email_message .= "Username: " . $username . "\n";
                $email_message .= "Password: " . $password . "\n\n";
                $email_message .= "Please log in and change your password immediately for security reasons.\n\n";
                $email_message .= "Best regards,\nThe Healthcare Team";

                if (sendEmail($application['Email'], $subject, $email_message)) {
                    $message = "Application approved successfully. Nurse profile created and credentials sent.";
                } else {
                    throw new Exception("Failed to send approval email");
                }
            } catch (Exception $e) {
                // Rollback transaction if something went wrong
                $conn->rollback();
                error_log("Approval Error: " . $e->getMessage());
                $error = "Error processing approval. Please try again or contact support.";
            }
        } else {
            $error = "Application not found.";
        }
    } elseif (isset($_POST['reject_application'])) {
        // Reject application (unchanged from original)
        $naid = $_POST['naid'];
        $reason = $_POST['rejection_reason'];

        // Get application details
        $stmt = $conn->prepare("SELECT * FROM nurseapplication WHERE NAID = ?");
        $stmt->bind_param("i", $naid);
        $stmt->execute();
        $application = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($application) {
            try {
                // Update application status and reason
                $stmt = $conn->prepare("UPDATE nurseapplication SET Status = 'rejected', RejectedReason = ? WHERE NAID = ?");
                $stmt->bind_param("si", $reason, $naid);
                $stmt->execute();
                $stmt->close();

                // Send email
                $subject = "Your Nurse Application Status";
                $email_message = "Dear " . $application['FullName'] . ",\n\n";
                $email_message .= "We regret to inform you that your application has been rejected.\n";
                $email_message .= "Reason: " . $reason . "\n\n";
                $email_message .= "Thank you for your interest in our platform.\n\n";
                $email_message .= "Best regards,\nThe Healthcare Team";

                if (sendEmail($application['Email'], $subject, $email_message)) {
                    $message = "Application rejected and notification sent to the nurse.";
                } else {
                    throw new Exception("Failed to send rejection email");
                }
            } catch (Exception $e) {
                error_log("Rejection Error: " . $e->getMessage());
                $error = "Error processing rejection. Please try again or contact support.";
            }
        } else {
            $error = "Application not found.";
        }
    }
}

// Fetch all nurse applications
$applications = [];
$query = "SELECT * FROM nurseapplication ORDER BY NAID DESC";
$result = $conn->query($query);
if ($result) {
    $applications = $result->fetch_all(MYSQLI_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nurse Applications</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <style>
        /* Your existing CSS styles remain unchanged */
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f5f5f5;
        }

.cert-details-container {
    font-family: 'Segoe UI', Roboto, sans-serif;
    max-width: 1400px;
    margin: 0 auto;
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    overflow: hidden;
}

.cert-header {
    padding: 16px 20px;
    background: #2c3e50;
    color: white;
}

.cert-header h3 {
    margin: 0;
    font-size: 1.3rem;
}

.cert-content {
    display: flex;
    padding: 20px;
    gap: 20px;
}

.cert-column {
    flex: 1;
}

.detail-group {
    margin-bottom: 15px;
}

.detail-group label {
    display: block;
    font-weight: 600;
    color: #555;
    margin-bottom: 5px;
    font-size: 0.9rem;
}

.detail-value {
    color: #333;
    padding: 8px 0;
    font-size: 0.95rem;
}

.status {
    display: inline-block;
    padding: 4px 10px;
    border-radius: 12px;
    font-size: 0.85rem;
    font-weight: 500;
}

.pending {
    background: #fff3cd;
    color: #856404;
}

.approved {
    background: #d4edda;
    color: #155724;
}

.rejected {
    background: #f8d7da;
    color: #721c24;
}

.document-preview {
    padding: 0 20px 20px;
}

.document-preview label {
    display: block;
    font-weight: 600;
    color: #555;
    margin-bottom: 10px;
    font-size: 0.9rem;
}

.image-container {
    text-align: center;
    border: 1px solid #eee;
    padding: 10px;
    border-radius: 6px;
}

.image-container img {
    max-width: 100%;
    max-height: 200px;
    display: block;
    margin: 0 auto 10px;
}

.view-link {
    color: #3498db;
    text-decoration: none;
    font-size: 0.9rem;
}

.view-link:hover {
    text-decoration: underline;
}

.action-buttons {
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    padding: 15px 20px;
    background: #f8f9fa;
    border-top: 1px solid #eee;
}

.btn {
    padding: 8px 16px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-weight: 500;
    font-size: 0.9rem;
    transition: background 0.2s;
}

.approve-btn {
    background: #28a745;
    color: white;
}

.approve-btn:hover {
    background: #218838;
}

.reject-btn {
    background: #dc3545;
    color: white;
}

.reject-btn:hover {
    background: #c82333;
}

.alert {
    padding: 15px;
    background: #f8d7da;
    color: #721c24;
    border-radius: 4px;
    margin: 10px;
}
        /* ... rest of your CSS ... */
        

        .modal-content {
            width: 50vw;
        }

        .document-preview {
    border: 1px solid #ddd;
    border-radius: 6px;
    padding: 10px;
    background: #f9f9f9;
}

.document-preview img {
    max-width: 100%;
    max-height: 300px;
    display: block;
    margin: 0 auto;
    border-radius: 4px;
}

.document-actions {
    margin-top: 10px;
    text-align: center;
}

.view-full-btn {
    display: inline-block;
    padding: 6px 12px;
    background: #3498db;
    color: white;
    border-radius: 4px;
    text-decoration: none;
    font-size: 13px;
    transition: background 0.2s;
}

.view-full-btn:hover {
    background: #2980b9;
}

.view-cv-btn {
    display: inline-block;
    padding: 6px 14px;
    margin-top: 20px;
    background-color: #17a2b8;
    color: white;
    text-decoration: none;
    border-radius: 5px;
    font-size: 13px;
    font-weight: 500;
    transition: background-color 0.2s ease, box-shadow 0.2s ease;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
}

.view-cv-btn:hover {
    background-color: #138496;
    box-shadow: 0 3px 8px rgba(0, 0, 0, 0.2);
}


    </style>
</head>

<body>
    <div class="container">
        <?php include "sidebar.php" ?>
        <div class="main-content">
            <div class="tab-content active" id="applications">
                <div class="card">
                    <div class="card-header">
                        <h3>Nurse Applications</h3>
                        <div class="tabs">
                            <div class="tab active" data-app-tab="all">All</div>
                            <div class="tab" data-app-tab="pending">Pending</div>
                            <div class="tab" data-app-tab="approved">Approved</div>
                            <div class="tab" data-app-tab="rejected">Rejected</div>
                        </div>
                    </div>

                    <?php if ($message): ?>
                        <div class="alert alert-success"><?php echo $message; ?></div>
                    <?php endif; ?>
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>

                    <div class="card-body">
                        <div class="table-responsive">
                            <table>
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Specialization</th>
                                        <th>Date Applied</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($applications as $app): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($app['NAID']); ?></td>
                                            <td><?php echo htmlspecialchars($app['FullName']); ?></td>
                                            <td><?php echo htmlspecialchars($app['Email']); ?></td>
                                            <td><?php echo htmlspecialchars($app['Specialization']); ?></td>
                                            <td><?php echo htmlspecialchars($app['DateOfBirth']); ?></td>
                                            <td>
                                                <span class="status status-<?php echo strtolower($app['Status']); ?>">
                                                    <?php echo htmlspecialchars($app['Status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($app['Status'] == 'pending'): ?>
                                                    <button class="btn btn-success btn-sm" onclick="openReviewModal(<?php echo $app['NAID']; ?>, 'approve')">Approve</button>
                                                    <button class="btn btn-danger btn-sm" onclick="openReviewModal(<?php echo $app['NAID']; ?>, 'reject')">Reject</button>
                                                <?php endif; ?>
                                                <button class="btn btn-primary btn-sm" onclick="viewApplication(<?php echo $app['NAID']; ?>)">View</button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- review modal -->
    <div class="modal" id="reviewModal">
        <div class="modal-content">
            <!-- Approve Form -->
            <form method="post" id="approveForm" style="display: none;">
                <input type="hidden" name="naid" id="approveNAID">
                <div class="modal-header">
                    <h3>Approve Application</h3>
                    <span class="close" onclick="closeModal('reviewModal')">&times;</span>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to approve this application?</p>
                    <div class="form-group">
                        <label for="username">Username</label>
                        <input type="text" name="username" id="username" class="form-control" required>
                        <button type="button" class="btn btn-sm btn-secondary mt-1" onclick="generateSuggestedUsername()">Suggest Username</button>
                    </div>
                    <div class="form-group">
                        <label for="password">Password</label>
                        <div class="input-group">
                            <input type="text" name="password" id="password" class="form-control" required>
                            <button type="button" class="btn btn-secondary" onclick="generateStrongPassword()">Generate</button>
                        </div>
                        <small class="text-muted">This password will be sent to the nurse via email</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" onclick="closeModal('reviewModal')">Cancel</button>
                    <button type="submit" name="approve_application" class="btn btn-success">Approve</button>
                </div>
            </form>

            <!-- Reject Form -->
            <form method="post" id="rejectForm" style="display: none;">
                <input type="hidden" name="naid" id="rejectNAID">
                <div class="modal-header">
                    <h3>Reject Application</h3>
                    <span class="close" onclick="closeModal('reviewModal')">&times;</span>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to reject this application?</p>
                    <div class="form-group">
                        <label for="rejectionReason">Reason for Rejection</label>
                        <textarea name="rejection_reason" id="rejectionReason" class="form-control" required></textarea>
                        <small class="text-muted">This reason will be sent to the nurse via email</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" onclick="closeModal('reviewModal')">Cancel</button>
                    <button type="submit" name="reject_application" class="btn btn-danger">Reject</button>
                </div>
            </form>
        </div>
    </div>

    <!-- show application modal -->
    <div class="modal" id="viewApplicationModal">
        <div class="modal-content" style="border-radius: 50%;">
            <!-- <div class="modal-header">
                <h3>Application Details</h3>
            </div> -->
            <div class="modal-body" style="padding: 0px;" id="applicationDetails">
                <!-- Content will be loaded via AJAX -->
                <!-- <span class="close" onclick="closeModal('viewApplicationModal')">&times;</span> -->
            </div>
            <!-- <div class="modal-footer">
                <button class="btn btn-light" onclick="closeModal('viewApplicationModal')">Close</button>
            </div> -->
        </div>
    </div>


<!-- Add this just before the closing </body> tag -->
<div id="logoutModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Confirm Logout</h3>
            <span class="close">&times;</span>
        </div>
        <div class="modal-body">
            <p>Are you sure you want to log out?</p>
        </div>
        <div class="modal-footer">
            <button class="btn btn-light" id="cancelLogout">Cancel</button>
            <button class="btn btn-danger" id="confirmLogout">Log Out</button>
        </div>
    </div>
</div>



    <script>
        // Application tabs - Updated to properly filter applications
        // Application tabs - Updated to properly filter applications
        document.querySelectorAll("[data-app-tab]").forEach((tab) => {
            tab.addEventListener("click", function() {
                document.querySelectorAll("[data-app-tab]").forEach((t) => t.classList.remove("active"));
                this.classList.add("active");

                const status = this.getAttribute('data-app-tab');
                const rows = document.querySelectorAll('tbody tr');

                rows.forEach(row => {
                    const rowStatus = row.querySelector('.status').textContent.toLowerCase().trim();
                    const statusMatch =
                        status === 'all' ||
                        (status === 'pending' && rowStatus === 'pending') ||
                        (status === 'approved' && rowStatus === 'approved') ||
                        (status === 'rejected' && rowStatus === 'rejected');

                    row.style.display = statusMatch ? '' : 'none';
                });
            });
        });

        // Generate random password
        function generatePassword() {
            const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789!@#$%^&*()';
            let password = '';
            for (let i = 0; i < 12; i++) {
                password += chars.charAt(Math.floor(Math.random() * chars.length));
            }
            return password;
        }

        // Generate username from name
        function generateUsername(fullName) {
            const nameParts = fullName.toLowerCase().split(' ');
            let username = nameParts[0];
            if (nameParts.length > 1) {
                username += '_' + nameParts[1].charAt(0);
            }
            username += Math.floor(Math.random() * 100);
            return username;
        }

        // Generate a strong password and populate the field
        function generateStrongPassword() {
            document.getElementById('password').value = generatePassword();
        }

        // Generate a suggested username based on the nurse's name
        function generateSuggestedUsername() {
            const naid = document.getElementById('approveNAID').value;
            // Find the row with matching NAID and get the FullName
            const rows = document.querySelectorAll('tbody tr');
            let fullName = '';

            rows.forEach(row => {
                if (row.querySelector('td:first-child').textContent === naid) {
                    fullName = row.querySelector('td:nth-child(2)').textContent;
                }
            });

            if (fullName) {
                document.getElementById('username').value = generateUsername(fullName);
            }
        }

        // Modal functions
        function openReviewModal(id, action) {
            const modal = document.getElementById("reviewModal");
            const approveForm = document.getElementById("approveForm");
            const rejectForm = document.getElementById("rejectForm");

            if (action === "approve") {
                // Set up approve form
                document.getElementById("approveNAID").value = id;

                // Clear previous values
                document.getElementById("username").value = '';
                document.getElementById("password").value = '';

                // Show approve form, hide reject form
                approveForm.style.display = "block";
                rejectForm.style.display = "none";
            } else {
                // Set up reject form
                document.getElementById("rejectNAID").value = id;
                document.getElementById("rejectionReason").value = "";

                // Show reject form, hide approve form
                rejectForm.style.display = "block";
                approveForm.style.display = "none";
            }

            modal.style.display = "flex";
        }

        function viewApplication(id) {
            // Fetch application details via AJAX
            fetch('get_application_details.php?id=' + id)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.text();
                })
                .then(data => {
                    document.getElementById('applicationDetails').innerHTML = data;
                    document.getElementById('viewApplicationModal').style.display = 'flex';
                })
                .catch(error => {
                    console.error('Error:', error);
                    document.getElementById('applicationDetails').innerHTML = `
                        <div class="alert alert-danger">
                            Error loading application details. Please try again.
                        </div>
                    `;
                    document.getElementById('viewApplicationModal').style.display = 'flex';
                });
        }

        function closeModal(modalId) {
            document.getElementById(modalId).style.display = "none";
        }

        // Close modal when clicking outside of it
        window.addEventListener("click", function(event) {
            if (event.target.className === "modal") {
                event.target.style.display = "none";
            }
        });

        // Initialize the table with 'All' applications shown
        document.addEventListener('DOMContentLoaded', function() {
            // Trigger click on the 'All' tab to ensure proper initialization
            document.querySelector('[data-app-tab="all"]').click();
        });

            // Get modal elements
    const logoutModal = document.getElementById('logoutModal');
    const logoutBtn = document.querySelector('.logout-btn');
    const closeBtn = document.querySelector('.close');
    const cancelBtn = document.getElementById('cancelLogout');
    const confirmBtn = document.getElementById('confirmLogout');

    // Open modal when logout button is clicked
    logoutBtn.addEventListener('click', function(e) {
        e.preventDefault();
        logoutModal.style.display = 'flex';
    });

    // Close modal when X is clicked
    closeBtn.addEventListener('click', function() {
        logoutModal.style.display = 'none';
    });

    // Close modal when Cancel is clicked
    cancelBtn.addEventListener('click', function() {
        logoutModal.style.display = 'none';
    });

    // Redirect to logout page when confirmed
confirmBtn.addEventListener('click', function() {
    fetch('../includes/logout.php', {
        method: 'POST',
        credentials: 'same-origin'
    })
    .then(response => {
        if (response.ok) {
            window.location.href = '../homepage/mainpage.php';
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred during logout.');
    });
});// Redirect to logout page when confirmed
    confirmBtn.addEventListener('click', function() {
        window.location.href = '../homepage/mainpage.php';
    });



    // Close modal when clicking outside
    window.addEventListener('click', function(e) {
        if (e.target === logoutModal) {
            logoutModal.style.display = 'none';
        }
    });
    </script>
</body>

</html>
