<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Admin panel to view rejected nurse applications on Home Care Platform.">
    <title>Rejected Nurse Applications - Admin - Home Care</title>
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
            <h2 class="mb-4">Rejected Nurse Applications</h2>

            <!-- Filter Form -->
            <form method="GET" class="mb-3">
                <div class="row">
                    <div class="col-md-3">
                        <label for="reasonFilter" class="form-label">Filter by Reason</label>
                        <select class="form-select" id="reasonFilter" name="reason" onchange="this.form.submit()">
                            <option value="">All</option>
                            <option value="Incomplete Documents" <?php if (isset($_GET['reason']) && $_GET['reason'] == 'Incomplete Documents') echo 'selected'; ?>>Incomplete Documents</option>
                            <option value="Unqualified" <?php if (isset($_GET['reason']) && $_GET['reason'] == 'Unqualified') echo 'selected'; ?>>Unqualified</option>
                            <option value="Other" <?php if (isset($_GET['reason']) && $_GET['reason'] == 'Other') echo 'selected'; ?>>Other</option>
                        </select>
                    </div>
                </div>
            </form>

            <!-- Table -->
            <table class="table table-striped shadow-sm">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Full Name</th>
                        <th>Email</th>
                        <th>Phone Number</th>
                        <th>Reason</th>
                        <th>Rejected By</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    include '../connect.php';

                    $reason = isset($_GET['reason']) ? mysqli_real_escape_string($conn, $_GET['reason']) : '';
                    $query = "SELECT na.NAID, na.FullName, na.Email, na.PhoneNumber, na.RejectedReason, na.RejectionDate, u.FullName AS StaffName
                              FROM nurseapplication na
                              LEFT JOIN staff s ON na.RejectedBy = s.StaffID
                              LEFT JOIN user u ON s.UserID = u.UserID
                              WHERE na.Status = 'rejected'";
                    if ($reason) {
                        $query .= " AND na.RejectedReason LIKE '%$reason%'";
                    }
                    $result = mysqli_query($conn, $query);

                    if (mysqli_num_rows($result) > 0) {
                        while ($row = mysqli_fetch_assoc($result)) {
                            $rejectedBy = $row['StaffName'] ?? 'Unknown';
                            $rejectionDate = $row['RejectionDate'] ?? 'Unknown';
                            echo "<tr>
                                <td>{$row['NAID']}</td>
                                <td>" . htmlspecialchars($row['FullName']) . "</td>
                                <td>" . htmlspecialchars($row['Email'] ?? 'N/A') . "</td>
                                <td>" . htmlspecialchars($row['PhoneNumber'] ?? 'N/A') . "</td>
                                <td>" . htmlspecialchars($row['RejectedReason']) . "</td>
                                <td>" . htmlspecialchars($rejectedBy) . "</td>
                                <td>" . htmlspecialchars($rejectionDate) . "</td>
                            </tr>";
                        }
                    } else {
                        echo "<tr><td colspan='7'>No rejected nurse applications found.</td></tr>";
                    }

                    mysqli_close($conn);
                    ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>