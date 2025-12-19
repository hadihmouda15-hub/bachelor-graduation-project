<?php
use function PHPSTORM_META\type;

// Start output buffering
ob_start();

// Start session securely
if (session_status() === PHP_SESSION_NONE) {
    session_start([
        'use_strict_mode' => true,
        'cookie_secure' => true,    // Enable in production (HTTPS)
        'cookie_httponly' => true,  // Prevent JavaScript access
        'cookie_samesite' => 'Lax'  // CSRF protection
    ]);
}

?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Certification Applications</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <style>
        /* Modal styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            overflow: auto;
        }

        .modal-content {
            background-color: #fefefe;
            margin: 5% auto;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
            width: 60%;
            max-width: 700px;
            border: none;
            position: relative;
            animation: modalopen 0.3s;
        }

        @keyframes modalopen {
            from {
                opacity: 0;
                transform: translateY(-50px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .close {
            position: absolute;
            right: 20px;
            top: 15px;
            color: #aaa;
            font-size: 24px;
            font-weight: bold;
            cursor: pointer;
            transition: color 0.2s;
        }

        .close:hover {
            color: #333;
        }

        #modal-body {
            padding: 15px 0;
        }

        .certification-details {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .certification-details img {
            max-width: 100%;
            border: 1px solid #ddd;
            border-radius: 4px;
            margin-top: 10px;
        }

        .detail-group {
            margin-bottom: 15px;
        }

        .detail-group label {
            display: block;
            font-weight: 600;
            color: #555;
            margin-bottom: 5px;
        }

        .detail-group p {
            margin: 0;
            padding: 8px 12px;
            background: #f5f5f5;
            border-radius: 4px;
        }

        .action-buttons {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }

        .action-buttons form {
            margin: 0;
        }

        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.2s;
        }

        .btn-success {
            background-color: #28a745;
            color: white;
        }

        .btn-success:hover {
            background-color: #218838;
        }

        .btn-danger {
            background-color: #dc3545;
            color: white;
        }

        .btn-danger:hover {
            background-color: #c82333;
        }

        .btn-primary {
            background-color: #007bff;
            color: white;
        }

        .btn-primary:hover {
            background-color: #0069d9;
        }

        .btn-sm {
            padding: 6px 12px;
            font-size: 14px;
        }

        /* Status badges */
        .status {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: capitalize;
        }

        .status-pending {
            background-color: #fff3cd;
            color: #856404;
        }

        .status-approved {
            background-color: #d4edda;
            color: #155724;
        }

        .status-rejected {
            background-color: #f8d7da;
            color: #721c24;
        }


        /* Confirmation Modal Styles */
        #confirmModal .modal-content {
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
            animation: modalopen 0.3s;
        }


        #confirmModalTitle {
            margin-top: 0;
            color: #333;
        }

        #confirmModalMessage {
            color: #555;
        }

        .btn-secondary {
            background-color: #6c757d;
            color: white;
        }

        .btn-secondary:hover {
            background-color: #5a6268;
        }

        /* Certification Modal Redesign */
        .certification-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }

        .certification-header h3 {
            margin: 0;
            color: #2c3e50;
            font-size: 1.4rem;
        }

        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: capitalize;
        }

        .pending-badge {
            background-color: #fff3cd;
            color: #856404;
        }

        .approved-badge {
            background-color: #d4edda;
            color: #155724;
        }

        .rejected-badge {
            background-color: #f8d7da;
            color: #721c24;
        }

        .certification-details-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 25px;
        }

        .details-column {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .detail-section {
            background: #fff;
            border-radius: 8px;
            padding: 15px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }

        .detail-section h4 {
            margin-top: 0;
            margin-bottom: 15px;
            color: #3498db;
            font-size: 1.1rem;
        }

        .detail-row {
            display: flex;
            margin-bottom: 12px;
        }

        .detail-label {
            font-weight: 600;
            color: #555;
            width: 120px;
            flex-shrink: 0;
        }

        .detail-value {
            color: #333;
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

        .comments-box {
            background: #f8f9fa;
            border: 1px solid #eee;
            border-radius: 6px;
            padding: 12px;
            min-height: 80px;
            white-space: pre-wrap;
        }

        .no-data-found,
        .invalid-request {
            text-align: center;
            padding: 30px;
            color: #6c757d;
        }

        .no-data-found i,
        .invalid-request i {
            font-size: 3rem;
            margin-bottom: 15px;
            color: #dc3545;
        }

        .invalid-request i {
            color: #ffc107;
        }
    </style>
</head>

<body>
    <?php
    require_once 'db_connection.php';
    // session_start();

    // Check if user is logged in and has staff privileges
    // if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'staff') {
    //     header("Location: login.php");
    //     exit();
    // }

    // Handle approve/reject actions
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['action'])) {
            $certificationId = $_POST['certification_id'];
            $action = $_POST['action'];

            // Validate action
            if (in_array($action, ['approve', 'reject'])) {
                $newStatus = $action === 'approve' ? 'approved' : 'rejected';

                $stmt = $conn->prepare("UPDATE certification SET Status = ? WHERE CertificationID = ?");
                $stmt->bind_param("si", $newStatus, $certificationId);

                $stmtnurseID = $conn->prepare("SELECT NurseID, Name FROM certification WHERE CertificationID = ?");
                $stmtnurseID->bind_param("i", $certificationId);
                $stmtnurseID->execute();

                $resultnurseID = $stmtnurseID->get_result(); // Get the result set
                $nurseData = $resultnurseID->fetch_assoc();  // Fetch the row as an associative array
                $nurseId = $nurseData['NurseID'];            // Extract the NurseID

                $stmtnurseID->close(); // Close the statement
                $message = 'Your certification of name  "'. $nurseData['Name']. '" has '  . $newStatus;
                $title = 'Certification ' . $newStatus;
                $type = $newStatus;
                $senderId = 21;

                $notificationStmt = $conn->prepare("INSERT INTO notification 
                                (SenderID, SenderType, RecipientID, RecipientType, Title, Message, Type, Status) 
                                VALUES (?, 'staff', ?, 'nurse', ?, ?, ?, 'Unread')");

                $notificationStmt->bind_param("iisss", $senderId, $nurseId, $title, $message, $type);

                $notificationStmt->execute();
                $notificationStmt->close();


                if ($stmt->execute()) {
                    $_SESSION['message'] = "Certification request has been $newStatus.";
                    $_SESSION['message_type'] = "success";
                } else {
                    $_SESSION['message'] = "Error updating certification status.";
                    $_SESSION['message_type'] = "error";
                }

                $stmt->close();

                // Refresh the page to show updated status
                header("Location: " . $_SERVER['PHP_SELF']);
                exit();
            }
        }
    }
    ?>


    <div class="container">
        <?php include "sidebar.php" ?>
        <div class="main-content">
            <div class="tab-content active" id="certifications">
                <div class="card">
                    <div class="card-header">
                        <h3>Certification Applications</h3>
                    </div>
                    <div class="card-body">
                        <?php if (isset($_SESSION['message'])): ?>
                            <div class="alert alert-<?php echo $_SESSION['message_type']; ?>">
                                <?php
                                echo $_SESSION['message'];
                                unset($_SESSION['message']);
                                unset($_SESSION['message_type']);
                                ?>
                            </div>
                        <?php endif; ?>

                        <div class="table-responsive">
                            <table>
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Nurse Name</th>
                                        <th>Certification Type</th>
                                        <th>Date Submitted</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    // Fetch certification requests with nurse and user information
                                    $query = "SELECT c.CertificationID, c.Name AS CertificationName, c.Status, c.CreatedAt, 
                                              u.FullName AS NurseName, u.UserID
                                              FROM certification c
                                              JOIN nurse n ON c.NurseID = n.NurseID
                                              JOIN user u ON n.UserID = u.UserID
                                              ORDER BY c.CreatedAt DESC";

                                    $result = $conn->query($query);

                                    if ($result && $result->num_rows > 0) {
                                        while ($row = $result->fetch_assoc()) {
                                            $statusClass = strtolower($row['Status']);
                                            echo "<tr>";
                                            echo "<td>{$row['CertificationID']}</td>";
                                            echo "<td>{$row['NurseName']}</td>";
                                            echo "<td>{$row['CertificationName']}</td>";
                                            echo "<td>" . date('Y-m-d', strtotime($row['CreatedAt'])) . "</td>";
                                            echo "<td><span class='status status-$statusClass'>{$row['Status']}</span></td>";
                                            echo "<td class='action-buttons'>";

                                            // Show approve/reject only for pending certifications
                                            if ($row['Status'] === 'pending') {
                                                echo "<form method='post' onsubmit='return confirmAction(\"approve\")'>
                                                        <input type='hidden' name='certification_id' value='{$row['CertificationID']}'>
                                                        <input type='hidden' name='action' value='approve'>
                                                        <button type='submit' class='btn btn-success btn-sm'>Approve</button>
                                                      </form>";
                                                echo "<form method='post' onsubmit='return confirmAction(\"reject\")'>
                                                        <input type='hidden' name='certification_id' value='{$row['CertificationID']}'>
                                                        <input type='hidden' name='action' value='reject'>
                                                        <button type='submit' class='btn btn-danger btn-sm'>Reject</button>
                                                      </form>";
                                            }

                                            echo "<button class='btn btn-primary btn-sm view-btn' data-id='{$row['CertificationID']}'>View</button>";
                                            echo "</td>";
                                            echo "</tr>";
                                        }
                                    } else {
                                        echo "<tr><td colspan='6'>No certification requests found.</td></tr>";
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal for viewing certification details -->
    <div id="certificationModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <div id="modal-body">
                <!-- Content will be loaded via AJAX -->
            </div>
        </div>
    </div>

    <!-- Confirmation Modal -->
    <div id="confirmModal" class="modal">
        <div class="modal-content" style="width: 400px;">
            <div id="confirmModalBody">
                <h4 id="confirmModalTitle">Confirm Action</h4>
                <p id="confirmModalMessage">Are you sure you want to perform this action?</p>
                <div class="action-buttons" style="margin-top: 20px; justify-content: flex-end;">
                    <button id="confirmModalCancel" class="btn btn-secondary" style="margin-right: 10px;">Cancel</button>
                    <button id="confirmModalConfirm" class="btn btn-danger">Confirm</button>
                </div>
            </div>
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
        // View button functionality
        document.querySelectorAll('.view-btn').forEach(button => {
            button.addEventListener('click', function() {
                const certId = this.getAttribute('data-id');
                const modal = document.getElementById('certificationModal');
                const modalBody = document.getElementById('modal-body');

                // Show loading state
                modalBody.innerHTML = '<div style="text-align: center; padding: 20px;"><i class="fas fa-spinner fa-spin"></i> Loading...</div>';
                modal.style.display = 'block';

                // Load content via AJAX
                fetch('get_certification_details.php?id=' + certId)
                    .then(response => response.text())
                    .then(data => {
                        modalBody.innerHTML = data;
                    })
                    .catch(error => {
                        modalBody.innerHTML = '<div class="alert alert-error">Error loading certification details.</div>';
                    });
            });
        });

        // Close modal
        document.querySelector('.close').addEventListener('click', function() {
            document.getElementById('certificationModal').style.display = 'none';
        });

        // Close modal when clicking outside
        window.addEventListener('click', function(event) {
            const modal = document.getElementById('certificationModal');
            if (event.target === modal) {
                modal.style.display = 'none';
            }
        });

        // Confirm action before approving/rejecting
        function confirmAction(action) {
            return confirm(`Are you sure you want to ${action} this certification request?`);
        }





        // Confirmation modal functionality
        let currentForm = null;
        let currentAction = null;

        // Function to show confirmation modal
        function showConfirmation(action, form) {
            currentForm = form;
            currentAction = action;

            const modal = document.getElementById('confirmModal');
            const title = document.getElementById('confirmModalTitle');
            const message = document.getElementById('confirmModalMessage');
            const confirmBtn = document.getElementById('confirmModalConfirm');

            title.textContent = `Confirm ${action.charAt(0).toUpperCase() + action.slice(1)}`;
            message.textContent = `Are you sure you want to ${action} this certification request?`;

            // Set button color based on action
            if (action === 'approve') {
                confirmBtn.className = 'btn btn-success';
            } else {
                confirmBtn.className = 'btn btn-danger';
            }

            confirmBtn.textContent = action.charAt(0).toUpperCase() + action.slice(1);
            modal.style.display = 'block';
        }

        // Confirm button handler
        document.getElementById('confirmModalConfirm').addEventListener('click', function() {
            if (currentForm) {
                currentForm.submit();
            }
            document.getElementById('confirmModal').style.display = 'none';
        });

        // Cancel button handler
        document.getElementById('confirmModalCancel').addEventListener('click', function() {
            document.getElementById('confirmModal').style.display = 'none';
        });

        // Close modal when clicking outside
        window.addEventListener('click', function(event) {
            if (event.target === document.getElementById('confirmModal')) {
                document.getElementById('confirmModal').style.display = 'none';
            }
        });

        // Modify your existing forms to use this modal
        document.querySelectorAll('form[onsubmit]').forEach(form => {
            form.onsubmit = function(e) {
                e.preventDefault();
                const action = this.querySelector('[name="action"]').value;
                showConfirmation(action, this);
                return false;
            };
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


<?php ob_end_flush(); ?>