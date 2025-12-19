<?php
require_once 'db_connection.php';





// Determine which tab is active
$status_filter = 'all';
if (isset($_GET['status'])) {
    $status_filter = $_GET['status'];
}

// Prepare the SQL query based on the filter
$sql = "SELECT r.*, 
        reporter.FullName as ReporterName, 
        reported.FullName as ReportedName,
        req.RequestID as RequestID
        FROM report r
        LEFT JOIN user reporter ON r.ReporterID = reporter.UserID
        LEFT JOIN user reported ON r.ReportedID = reported.UserID
        LEFT JOIN request req ON r.RequestID = req.RequestID";

// Add WHERE clause based on filter
if ($status_filter !== 'all') {
    $sql .= " WHERE r.Status = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $status_filter);
} else {
    $stmt = $conn->prepare($sql);
}

$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports Management</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <style>
        .status {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }

        .status-pending {
            background-color: #FFF3CD;
            color: #856404;
        }

        .status-resolved {
            background-color: #D4EDDA;
            color: #155724;
        }

        .btn {

            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.2s;

        }

        .btn-primary {
            background-color: #007BFF;
            color: white;
        }

        .btn-sm {
            font-size: 12px;
            padding: 3px 8px;
        }

        .tab.active {
            font-weight: bold;
            border-bottom: 2px solid #007BFF;
        }

        .tab {
            cursor: pointer;
            padding: 5px 15px;
            display: inline-block;
        }

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
            justify-content: center;
            align-items: center;
        }

        .modal-content {
            background-color: #fefefe;
            padding: 20px;
            border-radius: 8px;
            width: 80%;
            max-width: 600px;
            max-height: 80vh;
            overflow-y: auto;
        }

        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }

        .close:hover {
            color: black;
        }

        .report-detail {
            margin-bottom: 10px;
        }

        .report-detail label {
            font-weight: bold;
            display: inline-block;
            width: 150px;
        }
    </style>
</head>

<body>
    <div class="container">
        <?php include "sidebar.php" ?>
        <div class="main-content">
            <div class="tab-content active" id="reports">
                <div class="card">
                    <div class="card-header">
                        <h3>Reports Management</h3>
                        <div class="tabs">
                            <div class="tab <?= $status_filter === 'all' ? 'active' : '' ?>"
                                onclick="window.location.href='?status=all'">All</div>
                            <div class="tab <?= $status_filter === 'pending' ? 'active' : '' ?>"
                                onclick="window.location.href='?status=pending'">Pending</div>
                            <div class="tab <?= $status_filter === 'resolved' ? 'active' : '' ?>"
                                onclick="window.location.href='?status=resolved'">Resolved</div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Report ID</th>
                                        <th>Reporter</th>
                                        <th>Role</th>
                                        <th>Reported</th>
                                        <th>Role</th>
                                        <th>Request ID</th>
                                        <th>Type</th>
                                        <th>Date</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($result->num_rows > 0): ?>
                                        <?php while ($row = $result->fetch_assoc()): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($row['ReportID']) ?></td>
                                                <td><?= htmlspecialchars($row['ReporterName'] ?? 'User #' . $row['ReporterID']) ?></td>
                                                <td><?= htmlspecialchars($row['ReporterRole']) ?></td>
                                                <td><?= htmlspecialchars($row['ReportedName'] ?? 'User #' . $row['ReportedID']) ?></td>
                                                <td><?= htmlspecialchars($row['ReportedRole']) ?></td>
                                                <td><?= htmlspecialchars($row['RequestID']) ?></td>
                                                <td><?= htmlspecialchars($row['Type']) ?></td>
                                                <td><?= htmlspecialchars($row['Date']) ?></td>
                                                <td>
                                                    <span class="status status-<?= strtolower($row['Status']) ?>">
                                                        <?= htmlspecialchars($row['Status']) ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <button class="btn btn-primary btn-sm"
                                                        onclick="viewReport(<?= $row['ReportID'] ?>)">
                                                        View
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="10" style="text-align: center;">No reports found</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- View Report Modal -->
    <div id="viewReportModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('viewReportModal')">&times;</span>
            <h2 id="reportModalTitle">Report Details</h2>
            <div id="reportModalContent">
                <div class="loader">Loading...</div>
            </div>

            <!-- Notification Form (initially hidden) -->
            <div id="notificationForm" style="display: none; margin-top: 20px;">
                <br>
                <h3>Send Notification</h3>
                <br>
                <div class="form-group">
                    <label>Recipient:</label>
                    <select id="notificationRecipient" class="form-control">
                        <option value="reporter">Reporter</option>
                        <option value="reported">Reported User</option>
                        <option value="both">Both</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Title:</label>
                    <input type="text" id="notificationTitle" class="form-control" value="Report Update">
                </div>
                <div class="form-group">
                    <label>Message:</label>
                    <textarea id="notificationMessage" class="form-control" rows="3"></textarea>
                </div>
                <br>
                <button class="btn btn-primary" onclick="sendNotification()">Send Notification</button>
                <br>
            </div>
            <br>

            <div class="modal-actions">
                <button id="resolveBtn" class="btn btn-success" style="display: none;" onclick="resolveReport()">
                    Mark as Resolved
                </button>
                <br>
                <button id="notifyBtn" class="btn btn-info" style="display: none;" onclick="showNotificationForm()">
                    Send Notification
                </button>
                <br>
                <button class="btn btn-secondary" onclick="closeModal('viewReportModal')">Close</button>
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
        // Current report details
        let currentReport = null;

        // Function to view report details
        function viewReport(reportId) {
            currentReportId = reportId;
            const modal = document.getElementById('viewReportModal');
            const modalContent = document.getElementById('reportModalContent');

            // Show loading state
            modalContent.innerHTML = '<div class="loader">Loading report details...</div>';
            document.getElementById('reportModalTitle').textContent = 'Report #' + reportId + ' Details';

            // Hide notification form initially
            document.getElementById('notificationForm').style.display = 'none';

            // Show the modal
            modal.style.display = 'flex';

            // Fetch report details via AJAX
            fetch(`get_report_details.php?report_id=${reportId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        modalContent.innerHTML = `<div class="error">${data.error}</div>`;
                        return;
                    }

                    // Store the report data
                    currentReport = data;

                    // Format the report details
                    let html = `
                <div class="report-detail">
                    <label>Reporter:</label>
                    <span>${data.ReporterName || 'User #' + data.ReporterID} (${data.ReporterRole})</span>
                </div>
                <div class="report-detail">
                    <label>Reported:</label>
                    <span>${data.ReportedName || 'User #' + data.ReportedID} (${data.ReportedRole})</span>
                </div>
                <div class="report-detail">
                    <label>Request ID:</label>
                    <span>${data.RequestID}</span>
                </div>
                <div class="report-detail">
                    <label>Report Type:</label>
                    <span>${data.Type}</span>
                </div>
                <div class="report-detail">
                    <label>Date:</label>
                    <span>${data.Date}</span>
                </div>
                <div class="report-detail">
                    <label>Status:</label>
                    <span class="status status-${data.Status.toLowerCase()}">${data.Status}</span>
                </div>
                <div class="report-detail">
                    <label>Description:</label>
                    <p>${data.Description || 'No description provided'}</p>
                </div>`;

                    if (data.File) {
                        html += `
                <div class="report-detail">
                    <label>Attachment:</label>
                    <a href="${data.File}" target="_blank">View File</a>
                </div>`;
                    }

                    modalContent.innerHTML = html;

                    // Show action buttons based on status
                    const resolveBtn = document.getElementById('resolveBtn');
                    const notifyBtn = document.getElementById('notifyBtn');

                    resolveBtn.style.display = data.Status.toLowerCase() === 'pending' ? 'block' : 'none';
                    notifyBtn.style.display = 'block';
                })
                .catch(error => {
                    modalContent.innerHTML = `<div class="error">Error loading report details: ${error.message}</div>`;
                });
        }

        // Show notification form
        function showNotificationForm() {
            document.getElementById('notificationForm').style.display = 'block';
            document.getElementById('notificationMessage').value =
                `Regarding report #${currentReportId}:\n\n[Enter your message here]`;
        }

        // Send notification
        function sendNotification() {
            const recipient = document.getElementById('notificationRecipient').value;
            const title = document.getElementById('notificationTitle').value;
            const message = document.getElementById('notificationMessage').value;

            if (!title || !message) {
                alert('Please fill in all fields');
                return;
            }

            // Determine recipients
            let recipients = [];
            if (recipient === 'reporter' || recipient === 'both') {
                recipients.push({
                    id: currentReport.ReporterID,
                    type: currentReport.ReporterRole
                });
            }
            if (recipient === 'reported' || recipient === 'both') {
                recipients.push({
                    id: currentReport.ReportedID,
                    type: currentReport.ReportedRole
                });
            }

            // Send notifications
            Promise.all(recipients.map(recipient => {
                    return fetch('send_notification.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: new URLSearchParams({
                            recipient_id: recipient.id,
                            recipient_type: recipient.type,
                            title: title,
                            message: message,
                            type: 'report_update'
                        })
                    });
                }))
                .then(responses => Promise.all(responses.map(r => r.json())))
                .then(results => {
                    if (results.every(r => r.success)) {
                        // alert('Notification(s) sent successfully!');
                        document.getElementById('notificationForm').style.display = 'none';
                    } else {
                        // alert('Some notifications failed to send');
                    }
                })
                .catch(error => {
                    alert('Error: ' + error.message);
                });
        }

        // Rest of your existing JavaScript...




        // Function to resolve a report
        function resolveReport() {
            if (!currentReportId) return;

            fetch(`resolve_report.php?report_id=${currentReportId}`, {
                    method: 'POST'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // alert('Report marked as resolved!');
                        closeModal('viewReportModal');
                        // Refresh the page to show updated status
                        window.location.reload();
                    } else {
                        alert('Error: ' + (data.error || 'Failed to resolve report'));
                    }
                })
                .catch(error => {
                    alert('Error: ' + error.message);
                });
        }

        // Function to close modal
        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }

        // Close modal when clicking outside
        window.addEventListener('click', function(event) {
            if (event.target.className === 'modal') {
                event.target.style.display = 'none';
            }
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