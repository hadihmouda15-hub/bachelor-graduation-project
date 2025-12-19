<?php
include '../connect.php';

$report_id = isset($_GET['report_id']) ? (int)$_GET['report_id'] : 0;
$tab = isset($_GET['tab']) ? $_GET['tab'] : 'complaints'; // Get tab parameter

$report_query = "
    SELECT r.ReportID, r.RequestID, r.Description, r.Date, r.Type,
           reported.FullName AS ReportedUser, reporter.FullName AS Reporter
    FROM report r
    LEFT JOIN user reported ON r.ReportedID = reported.UserID
    JOIN user reporter ON r.ReporterID = reporter.UserID
    WHERE r.ReportID = $report_id
";
$report_result = mysqli_query($conn, $report_query);
$report = mysqli_fetch_assoc($report_result);

if (!$report) {
    echo "<p>التقرير غير موجود.</p>";
    exit();
}
?>

<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Admin panel to view report details on Home Care Platform.">
    <title>View Report - Admin - Home Care</title>
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
            <h2 class="mb-4">View Report</h2>
            <div class="card shadow-sm">
                <div class="card-body">
                    <h5 class="card-title">Report Details</h5>
                    <p><strong>ID:</strong> <?php echo $report['ReportID']; ?></p>
                    <p><strong>Type:</strong> <?php echo ucfirst($report['Type']); ?></p>
                    <p><strong>Request ID:</strong> <?php echo $report['RequestID'] ? $report['RequestID'] : 'N/A'; ?></p>
                    <?php if ($report['Type'] == 'complaint'): ?>
                        <p><strong>Reported User:</strong> <?php echo $report['ReportedUser'] ? $report['ReportedUser'] : 'N/A'; ?></p>
                    <?php endif; ?>
                    <p><strong>Reporter:</strong> <?php echo $report['Reporter']; ?></p>
                    <p><strong>Reason:</strong> <?php echo $report['Description']; ?></p>
                    <p><strong>Date:</strong> <?php echo $report['Date']; ?></p>
                    <a href="reports.php?tab=<?php echo $tab; ?>" class="btn btn-primary">Back to Reports</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>