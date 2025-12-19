<?php
include '../connect.php';

// Initialize status filter
$status_filter = isset($_GET['status']) ? mysqli_real_escape_string($conn, strtolower(trim($_GET['status']))) : '';
$debug_message = ''; // For debugging

// Fetch subscriptions with nurse name
$query = "
    SELECT s.SID, s.Amount, s.PaymentDate, s.expiryDate, s.PaymentMethod, s.PlanType, s.Status, u.FullName, n.NurseID
    FROM subscribe s
    LEFT JOIN nurse n ON s.NurseID = n.NurseID
    LEFT JOIN user u ON n.UserID = u.UserID
    WHERE 1=1
";
if ($status_filter) {
    if ($status_filter === 'active') {
        $query .= " AND s.Status = 'active' AND s.expiryDate >= CURDATE()";
    } elseif ($status_filter === 'expired') {
        $query .= " AND (s.Status = 'active' AND s.expiryDate < CURDATE())";
    } elseif ($status_filter === 'cancelled') {
        $query .= " AND s.Status = 'Cancelled'";
    }
}
$result = mysqli_query($conn, $query);

// Check for query errors
if (!$result) {
    $debug_message = "Query error: " . mysqli_error($conn);
}

// Determine status based on expiry date
$subscriptions = [];
while ($row = mysqli_fetch_assoc($result)) {
    $expiryDate = strtotime($row['expiryDate']);
    $currentDate = strtotime(date('Y-m-d'));
    $row['DerivedStatus'] = ($row['Status'] == 'active' && $expiryDate >= $currentDate) ? 'active' : ($row['Status'] == 'Cancelled' ? 'cancelled' : 'expired');
    $subscriptions[] = $row;
}

// Handle subscription cancellation or activation
if (isset($_POST['cancel_subscription'])) {
    $sid = intval($_POST['sid']);
    $status_filter = isset($_POST['status']) ? $_POST['status'] : '';
    $updateQuery = "UPDATE subscribe SET Status = 'Cancelled' WHERE SID = $sid";
    if (mysqli_query($conn, $updateQuery)) {
        header("Location: subscriptions.php" . ($status_filter ? "?status=$status_filter" : ""));
        exit();
    } else {
        $debug_message .= "Error cancelling subscription: " . mysqli_error($conn);
    }
} elseif (isset($_POST['activate_subscription'])) {
    $sid = intval($_POST['sid']);
    $status_filter = isset($_POST['status']) ? $_POST['status'] : '';
    $updateQuery = "UPDATE subscribe SET Status = 'active' WHERE SID = $sid";
    if (mysqli_query($conn, $updateQuery)) {
        header("Location: subscriptions.php" . ($status_filter ? "?status=$status_filter" : ""));
        exit();
    } else {
        $debug_message .= "Error activating subscription: " . mysqli_error($conn);
    }
}
?>

<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Admin panel to manage subscriptions on Home Care Platform.">
    <title>Subscriptions - Admin - Home Care</title>
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
            <h2 class="mb-4">Manage Subscriptions</h2>
            <!-- Debug Message -->
            <?php if ($debug_message) { ?>
                <div class="alert alert-info"><?php echo htmlspecialchars($debug_message); ?></div>
            <?php } ?>
            <!-- Filter and Search -->
            <div class="row mb-3">
                <div class="col-md-4">
                    <label for="statusFilter" class="form-label">Filter by Status</label>
                    <select class="form-select" id="statusFilter" name="status" onchange="updateFilters()">
                        <option value="">All Statuses</option>
                        <option value="active" <?php echo $status_filter === 'active' ? 'selected' : ''; ?>>Active</option>
                        <option value="expired" <?php echo $status_filter === 'expired' ? 'selected' : ''; ?>>Expired</option>
                        <option value="cancelled" <?php echo $status_filter === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="nameFilter" class="form-label">Search by Nurse Name</label>
                    <input type="text" class="form-control" id="nameFilter" placeholder="Enter nurse name" oninput="filterTable()">
                </div>
            </div>
            <!-- Subscriptions Table -->
            <table class="table table-striped shadow-sm" id="subscriptionsTable">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>User</th>
                        <th>Start Date</th>
                        <th>End Date</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($subscriptions) > 0): ?>
                        <?php foreach ($subscriptions as $sub): ?>
                            <tr>
                                <td><?php echo $sub['SID']; ?></td>
                                <td><?php echo htmlspecialchars($sub['FullName'] ?? 'Unknown'); ?></td>
                                <td><?php echo $sub['PaymentDate'] ?? 'N/A'; ?></td>
                                <td><?php echo $sub['expiryDate'] ?? 'N/A'; ?></td>
                                <td>
                                    <span class="badge <?php echo $sub['DerivedStatus'] == 'active' ? 'bg-success' : ($sub['DerivedStatus'] == 'expired' ? 'bg-warning' : 'bg-danger'); ?>">
                                        <?php echo ucfirst($sub['DerivedStatus']); ?>
                                    </span>
                                </td>
                                <td>
                                    <button class="btn btn-info btn-sm" data-bs-toggle="modal" data-bs-target="#viewModal<?php echo $sub['SID']; ?>">View</button>
                                    <a href="send_message.php?id=<?php echo $sub['NurseID'] ?? ''; ?>" class="btn btn-primary btn-sm <?php echo !$sub['NurseID'] ? 'disabled' : ''; ?>">Send Notification</a>
                                    <?php if ($sub['DerivedStatus'] == 'active'): ?>
                                        <button class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#cancelModal<?php echo $sub['SID']; ?>">Cancel</button>
                                    <?php elseif ($sub['DerivedStatus'] == 'cancelled'): ?>
                                        <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#activateModal<?php echo $sub['SID']; ?>">Activate</button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <!-- View Modal -->
                            <div class="modal fade" id="viewModal<?php echo $sub['SID']; ?>" tabindex="-1" aria-labelledby="viewModalLabel<?php echo $sub['SID']; ?>" aria-hidden="true">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title" id="viewModalLabel<?php echo $sub['SID']; ?>">Subscription Details #<?php echo $sub['SID']; ?></h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body">
                                            <p><strong>ID:</strong> <?php echo $sub['SID']; ?></p>
                                            <p><strong>User:</strong> <?php echo htmlspecialchars($sub['FullName'] ?? 'Unknown'); ?></p>
                                            <p><strong>Amount:</strong> $<?php echo $sub['Amount'] ?? 'N/A'; ?></p>
                                            <p><strong>Payment Date:</strong> <?php echo $sub['PaymentDate'] ?? 'N/A'; ?></p>
                                            <p><strong>Expiry Date:</strong> <?php echo $sub['expiryDate'] ?? 'N/A'; ?></p>
                                            <p><strong>Payment Method:</strong> <?php echo $sub['PaymentMethod'] ?? 'N/A'; ?></p>
                                            <p><strong>Plan Type:</strong> <?php echo $sub['PlanType'] ?? 'N/A'; ?></p>
                                            <p><strong>Status:</strong> <?php echo ucfirst($sub['DerivedStatus']); ?></p>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <!-- Cancel Modal -->
                            <?php if ($sub['DerivedStatus'] == 'active'): ?>
                                <div class="modal fade" id="cancelModal<?php echo $sub['SID']; ?>" tabindex="-1" aria-labelledby="cancelModalLabel<?php echo $sub['SID']; ?>" aria-hidden="true">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title" id="cancelModalLabel<?php echo $sub['SID']; ?>">Confirm Cancellation</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body">
                                                Are you sure you want to cancel subscription #<?php echo $sub['SID']; ?>?
                                            </div>
                                            <div class="modal-footer">
                                                <form method="POST">
                                                    <input type="hidden" name="sid" value="<?php echo $sub['SID']; ?>">
                                                    <input type="hidden" name="status" value="<?php echo $status_filter; ?>">
                                                    <button type="submit" name="cancel_subscription" class="btn btn-danger">Yes, Cancel</button>
                                                </form>
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">No</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                            <!-- Activate Modal -->
                            <?php if ($sub['DerivedStatus'] == 'cancelled'): ?>
                                <div class="modal fade" id="activateModal<?php echo $sub['SID']; ?>" tabindex="-1" aria-labelledby="activateModalLabel<?php echo $sub['SID']; ?>" aria-hidden="true">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title" id="activateModalLabel<?php echo $sub['SID']; ?>">Confirm Activation</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body">
                                                Are you sure you want to activate subscription #<?php echo $sub['SID']; ?>?
                                            </div>
                                            <div class="modal-footer">
                                                <form method="POST">
                                                    <input type="hidden" name="sid" value="<?php echo $sub['SID']; ?>">
                                                    <input type="hidden" name="status" value="<?php echo $status_filter; ?>">
                                                    <button type="submit" name="activate_subscription" class="btn btn-success">Yes, Activate</button>
                                                </form>
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">No</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center">No subscriptions found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function filterTable() {
            const statusFilter = document.getElementById('statusFilter').value.toLowerCase();
            const nameFilter = document.getElementById('nameFilter').value.toLowerCase();
            const rows = document.querySelectorAll('#subscriptionsTable tbody tr');

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
            let url = 'subscriptions.php';
            if (statusFilter) {
                url += '?status=' + statusFilter;
            }
            window.location.href = url;
        }

        // Apply filter automatically on page load based on URL parameters
        window.onload = function() {
            <?php if (isset($_GET['status'])): ?>
                document.getElementById('statusFilter').value = '<?php echo $status_filter; ?>';
            <?php endif; ?>
            filterTable();
        };
    </script>
</body>
</html>