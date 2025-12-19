<?php
include '../connect.php';

// Initialize variables for filtering
$status_filter = isset($_GET['status']) ? mysqli_real_escape_string($conn, trim($_GET['status'])) : '';
$debug_message = ''; // For debugging

// Handle reject action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $request_id = intval($_POST['request_id']);
    $action = $_POST['action'];

    if ($action === 'reject') {
        $declinereason = mysqli_real_escape_string($conn, $_POST['reject_reason']);
        if (!empty($declinereason)) {
            $update_query = "UPDATE request SET RequestStatus = 'rejected', declinereason = '$declinereason' WHERE RequestID = $request_id";
            if (mysqli_query($conn, $update_query)) {
                header("Location: requests.php" . ($status_filter ? "?status=$status_filter" : ""));
                exit();
            } else {
                error_log("Failed to update request $request_id: " . mysqli_error($conn));
                $debug_message = "Error updating request: " . mysqli_error($conn);
            }
        } else {
            $debug_message = "Please provide a reason for rejection.";
        }
    }
}

// Build the query with filters
$query = "SELECT r.RequestID, u.FullName AS PatientName, s.Name AS ServiceName, r.Date AS RequestDate, r.RequestStatus, r.SpecialInstructions, r.MedicalCondition, r.NurseGender, r.AgeType, r.Time, r.Duration, r.NumberOfNurses, p.UserID, r.declinereason
          FROM request r
          LEFT JOIN patient p ON r.PatientID = p.PatientID
          LEFT JOIN user u ON p.UserID = u.UserID
          LEFT JOIN service s ON r.Type = s.Name
          WHERE 1=1";
if ($status_filter) {
    $query .= " AND r.RequestStatus = '$status_filter'";
}
$result = mysqli_query($conn, $query);

// Debugging: Count rows returned
$row_count = mysqli_num_rows($result);
?>

<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Admin panel to manage requests on Home Care Platform.">
    <title>Manage Requests - Admin - Home Care</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/admin.css">
</head>
<body>
    <div class="d-flex">
        <!-- Sidebar -->
        <?php include 'sidebar.php'; ?>

        <!-- Main Content -->
        <div class="content flex-grow-1 p-4" style="margin-left: 250px;">
            <h2 class="mb-4">Manage Requests</h2>
            <!-- Debug Message -->
            <?php if ($debug_message) { ?>
                <div class="alert alert-info"><?php echo $debug_message; ?></div>
            <?php } ?>
            <!-- Filter and Search -->
            <div class="row mb-3">
                <div class="col-md-4">
                    <label for="statusFilter" class="form-label">Filter by Status</label>
                    <select class="form-select" id="statusFilter" name="status" onchange="updateFilters()">
                        <option value="">All Statuses</option>
                        <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="inprocess" <?php echo $status_filter === 'inprocess' ? 'selected' : ''; ?>>In Process</option>
                        <option value="completed" <?php echo $status_filter === 'completed' ? 'selected' : ''; ?>>Completed</option>
                        <option value="rejected" <?php echo $status_filter === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="nameFilter" class="form-label">Search by Patient Name</label>
                    <input type="text" class="form-control" id="nameFilter" placeholder="Enter patient name" oninput="filterTable()">
                </div>
            </div>
            <!-- Requests Table -->
            <table class="table table-striped shadow-sm" id="requestsTable">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Patient</th>
                        <th>Service</th>
                        <th>Date</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($row_count > 0) { ?>
                        <?php while ($row = mysqli_fetch_assoc($result)) { ?>
                            <tr>
                                <td><?php echo $row['RequestID']; ?></td>
                                <td><?php echo htmlspecialchars($row['PatientName'] ?? 'Unknown'); ?></td>
                                <td><?php echo htmlspecialchars($row['ServiceName'] ?? 'Unknown'); ?></td>
                                <td><?php echo $row['RequestDate'] ?? 'N/A'; ?></td>
                                <td>
                                    <span class="badge <?php 
                                        if ($row['RequestStatus'] === 'pending') echo 'bg-warning';
                                        elseif ($row['RequestStatus'] === 'inprocess') echo 'bg-primary';
                                        elseif ($row['RequestStatus'] === 'completed') echo 'bg-success';
                                        else echo 'bg-danger';
                                    ?>">
                                        <?php echo ucfirst($row['RequestStatus']); ?>
                                    </span>
                                </td>
                                <td style="display: flex; justify-content: start;" class="pe-5">
                                    <!-- <button class="btn btn-danger btn-sm">Reject</button> -->
                                    <button class="btn btn-success btn-sm me-3" data-bs-toggle="modal" data-bs-target="#detailsModal_<?php echo $row['RequestID']; ?>">Details</button>
                                    <a href="send_message.php?id=<?php echo $row['UserID'] ?? ''; ?>" class="btn btn-primary me-3 btn-sm <?php echo !$row['UserID'] ? 'disabled' : ''; ?>">Send Message</a>
                                    <?php if ($row['RequestStatus'] === 'pending' || $row['RequestStatus'] === 'inprocess') { ?>
                                        <button class="btn btn-danger btn-sm me-3" data-bs-toggle="modal" data-bs-target="#rejectModal_<?php echo $row['RequestID']; ?>">Reject</button>
                                    <?php } ?>
                                </td>
                            </tr>
                            <!-- Reject Modal -->
                            <div class="modal fade" id="rejectModal_<?php echo $row['RequestID']; ?>" tabindex="-1" aria-labelledby="rejectModalLabel_<?php echo $row['RequestID']; ?>" aria-hidden="true">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title" id="rejectModalLabel_<?php echo $row['RequestID']; ?>">Confirm Rejection</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body">
                                            <p>Are you sure you want to reject this request?</p>
                                            <form method="POST" id="rejectForm_<?php echo $row['RequestID']; ?>">
                                                <input type="hidden" name="request_id" value="<?php echo $row['RequestID']; ?>">
                                                <input type="hidden" name="action" value="reject">
                                                <input type="hidden" name="status" value="<?php echo $status_filter; ?>">
                                                <div class="mb-3">
                                                    <label for="rejectReason_<?php echo $row['RequestID']; ?>" class="form-label">Reason for Rejection</label>
                                                    <textarea class="form-control" id="rejectReason_<?php echo $row['RequestID']; ?>" name="reject_reason" rows="3" required></textarea>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="submit" class="btn btn-primary">Yes</button>
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">No</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <!-- Details Modal -->
                            <div class="modal fade" id="detailsModal_<?php echo $row['RequestID']; ?>" tabindex="-1" aria-labelledby="detailsModalLabel_<?php echo $row['RequestID']; ?>" aria-hidden="true">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title" id="detailsModalLabel_<?php echo $row['RequestID']; ?>">Request Details</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body">
                                            <p><strong>Request ID:</strong> <?php echo $row['RequestID']; ?></p>
                                            <p><strong>Patient Name:</strong> <?php echo htmlspecialchars($row['PatientName'] ?? 'Unknown'); ?></p>
                                            <p><strong>Service Type:</strong> <?php echo htmlspecialchars($row['ServiceName'] ?? 'Unknown'); ?></p>
                                            <p><strong>Request Date:</strong> <?php echo $row['RequestDate'] ?? 'N/A'; ?></p>
                                            <p><strong>Request Time:</strong> <?php echo $row['Time'] ?? 'N/A'; ?></p>
                                            <p><strong>Status:</strong> <?php echo ucfirst($row['RequestStatus']); ?></p>
                                            <p><strong>Nurse Gender:</strong> <?php echo $row['NurseGender'] ?? 'N/A'; ?></p>
                                            <p><strong>Age Type:</strong> <?php echo $row['AgeType'] ?? 'N/A'; ?></p>
                                            <p><strong>Duration:</strong> <?php echo $row['Duration'] ? $row['Duration'] . ' hours' : 'N/A'; ?></p>
                                            <p><strong>Number of Nurses:</strong> <?php echo $row['NumberOfNurses'] ?? 'N/A'; ?></p>
                                            <p><strong>Medical Condition:</strong> <?php echo $row['MedicalCondition'] ? htmlspecialchars($row['MedicalCondition']) : 'None'; ?></p>
                                            <p><strong>Special Instructions:</strong> <?php echo $row['SpecialInstructions'] ? htmlspecialchars($row['SpecialInstructions']) : 'None'; ?></p>
                                            <p><strong>Reason for Rejection:</strong> <?php echo $row['declinereason'] ? htmlspecialchars($row['declinereason']) : 'None'; ?></p>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php } ?>
                    <?php } else { ?>
                        <tr>
                            <td colspan="6" class="text-center">No requests found.</td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Filter Script -->
    <script>
        function filterTable() {
            const statusFilter = document.getElementById('statusFilter').value.toLowerCase();
            const nameFilter = document.getElementById('nameFilter').value.toLowerCase();
            const rows = document.querySelectorAll('#requestsTable tbody tr');

            rows.forEach(row => {
                const status = row.cells[4].textContent.toLowerCase();
                const name = row.cells[1].textContent.toLowerCase();

                const statusMatch = statusFilter === '' || status.includes(statusFilter);
                const nameMatch = nameFilter === '' || name.includes(nameFilter);

                row.style.display = statusMatch && nameMatch ? '' : 'none';
            });
        }

        function updateFilters() {
            const statusFilter = document.getElementById('statusFilter').value;
            let url = 'requests.php';
            if (statusFilter) {
                url += '?status=' + statusFilter;
            }
            window.location.href = url;
        }

        // Apply filter automatically on page load based on URL parameters
        window.onload = function() {
            <?php if (isset($_GET['status'])): ?>
                document.getElementById('statusFilter').value = '<?php echo $_GET['status']; ?>';
            <?php endif; ?>
            filterTable();
        };
    </script>
</body>
</html>