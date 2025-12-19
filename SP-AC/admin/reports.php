<?php
include '../connect.php';

$admin_id = 1;

$complaints_query = "
    SELECT r.ReportID, r.RequestID, r.Description, r.Date, r.Type,
           reported.FullName AS ReportedUser, reporter.FullName AS Reporter,
           reported.UserID AS ReportedID, reporter.UserID AS ReporterID
    FROM report r
    JOIN user reported ON r.ReportedID = reported.UserID
    JOIN user reporter ON r.ReporterID = reporter.UserID
    ORDER BY r.Date DESC
";
$complaints_result = mysqli_query($conn, $complaints_query);
$complaints = mysqli_fetch_all($complaints_result, MYSQLI_ASSOC);

$suggestions_query = "
    SELECT r.ReportID, r.Description, r.Date, r.Type,
           reporter.FullName AS Reporter, reporter.UserID AS ReporterID
    FROM report r
    JOIN user reporter ON r.ReporterID = reporter.UserID
    ORDER BY r.Date DESC
";
$suggestions_result = mysqli_query($conn, $suggestions_query);
$suggestions = mysqli_fetch_all($suggestions_result, MYSQLI_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_notification'])) {
    $recipient_id = $_POST['recipient_id'];
    $recipient_type = $_POST['recipient_type'];
    $subject = $_POST['subject'];
    $message = $_POST['message'];
    $sender_type = 'admin';
    $type = 'message';
    $status = 'unread';

    $insert_query = "
        INSERT INTO notification (SenderID, SenderType, RecipientID, RecipientType, Title, Message, Type, Status, Date)
        VALUES ($admin_id, '$sender_type', $recipient_id, '$recipient_type', '$subject', '$message', '$type', '$status', NOW())
    ";
    mysqli_query($conn, $insert_query);
    header("Location: reports.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Admin panel to view and manage reports on Home Care Platform.">
    <title>Reports - Admin - Home Care</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/admin.css">
    <style>
        .table th, .table td {
            padding: 15px;
            vertical-align: middle;
        }
        .table .date-column, .table .name-column {
            white-space: nowrap;
        }
        .table {
            margin-bottom: 3rem;
        }
        .table th.number-column, .table td.number-column {
            min-width: 80px;
            text-align: center;
        }
        .table th.request-id-column, .table td.request-id-column {
            min-width: 120px;
            text-align: center;
        }
        .table th.name-column, .table td.name-column {
            min-width: 200px;
        }
        .table th.actions-column, .table td.actions-column {
            min-width: 220px; /* Ensure enough width for buttons */
            white-space: nowrap; /* Prevent wrapping of buttons */
        }
        .table td.actions-column .btn {
            display: inline-block; /* Ensure buttons stay side by side */
            margin-right: 5px; /* Add small spacing between buttons */
        }
    </style>
</head>
<body>
    <div class="d-flex">
        <?php include 'sidebar.php'; ?>

        <div class="content flex-grow-1 p-4" style="margin-left: 250px;">
            <h2 class="mb-4">Reports</h2>
            <ul class="nav nav-tabs mb-3" id="reportTabs" role="tablist">
                <li class="nav-item">
                    <a class="nav-link active" id="complaints-tab" data-bs-toggle="tab" href="#complaints" role="tab">Complaints</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="suggestions-tab" data-bs-toggle="tab" href="#suggestions" role="tab">Suggestions/Issues</a>
                </li>
            </ul>
            <div class="tab-content" id="reportTabContent">
                <div class="tab-pane fade show active" id="complaints" role="tabpanel">
                    <table class="table table-striped shadow-sm">
                        <thead>
                            <tr>
                                <th class="number-column">#</th>
                                <th class="request-id-column">Request ID</th>
                                <th class="name-column">Reported User</th>
                                <th class="name-column">Reporter</th>
                                <th>Reason</th>
                                <th class="date-column">Date</th>
                                <th class="actions-column">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $row_number = 1; ?>
                            <?php foreach ($complaints as $report): ?>
                                <tr>
                                    <td class="number-column"><?php echo $row_number++; ?></td>
                                    <td class="request-id-column"><?php echo $report['RequestID'] != '' ? $report['RequestID'] : 'N/A'; ?></td>
                                    <td class="name-column"><?php echo $report['ReportedUser']; ?></td>
                                    <td class="name-column"><?php echo $report['Reporter']; ?></td>
                                    <td><?php echo $report['Description']; ?></td>
                                    <td class="date-column"><?php echo $report['Date']; ?></td>
                                    <td class="actions-column">
                                        <a href="view_report.php?report_id=<?php echo $report['ReportID']; ?>&tab=complaints" class="btn btn-info btn-sm">View</a>
                                        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#sendNotificationModal<?php echo $report['ReportID']; ?>">Send Notification</button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="tab-pane fade" id="suggestions" role="tabpanel">
                    <table class="table table-striped shadow-sm">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Reporter</th>
                                <th>Type</th>
                                <th>Description</th>
                                <th class="date-column">Date</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $row_number = 1; ?>
                            <?php foreach ($suggestions as $report): ?>
                                <tr>
                                    <td><?php echo $row_number++; ?></td>
                                    <td><?php echo $report['Reporter']; ?></td>
                                    <td><?php echo ucfirst($report['Type']); ?></td>
                                    <td><?php echo $report['Description']; ?></td>
                                    <td class="date-column"><?php echo $report['Date']; ?></td>
                                    <td>
                                        <a href="view_report.php?report_id=<?php echo $report['ReportID']; ?>&tab=suggestions" class="btn btn-info btn-sm">View</a>
                                        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#sendNotificationModalReporter<?php echo $report['ReportID']; ?>">Send to Reporter</button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <?php foreach ($complaints as $report): ?>
        <div class="modal fade" id="sendNotificationModal<?php echo $report['ReportID']; ?>" tabindex="-1" aria-labelledby="sendNotificationModalLabel<?php echo $report['ReportID']; ?>" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="sendNotificationModalLabel<?php echo $report['ReportID']; ?>">Send Notification</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form method="POST">
                            <div class="mb-3">
                                <label for="recipient_type_<?php echo $report['ReportID']; ?>" class="form-label">Recipient</label>
                                <select class="form-select" id="recipient_type_<?php echo $report['ReportID']; ?>" name="recipient_type" required>
                                    <option value="" disabled selected>Select recipient</option>
                                    <option value="reported" data-id="<?php echo $report['ReportedID']; ?>" data-name="<?php echo $report['ReportedUser']; ?>">Reported: <?php echo $report['ReportedUser']; ?></option>
                                    <option value="reporter" data-id="<?php echo $report['ReporterID']; ?>" data-name="<?php echo $report['Reporter']; ?>">Reporter: <?php echo $report['Reporter']; ?></option>
                                </select>
                                <input type="hidden" name="recipient_id" id="recipient_id_<?php echo $report['ReportID']; ?>">
                                <input type="hidden" name="recipient_type" value="specific">
                            </div>
                            <div class="mb-3">
                                <label for="subject_<?php echo $report['ReportID']; ?>" class="form-label">Subject</label>
                                <input type="text" class="form-control" id="subject_<?php echo $report['ReportID']; ?>" name="subject" placeholder="Enter subject" required>
                            </div>
                            <div class="mb-3">
                                <label for="message_<?php echo $report['ReportID']; ?>" class="form-label">Message</label>
                                <textarea class="form-control" id="message_<?php echo $report['ReportID']; ?>" name="message" rows="3" placeholder="Enter your message" required></textarea>
                            </div>
                            <button type="submit" name="send_notification" class="btn btn-primary">Send</button>
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>

    <?php foreach ($suggestions as $report): ?>
        <div class="modal fade" id="sendNotificationModalReporter<?php echo $report['ReportID']; ?>" tabindex="-1" aria-labelledby="sendNotificationModalLabelReporter<?php echo $report['ReportID']; ?>" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="sendNotificationModalLabelReporter<?php echo $report['ReportID']; ?>">Send Notification to Reporter</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form method="POST">
                            <div class="mb-3">
                                <label for="recipient" class="form-label">Recipient</label>
                                <input type="text" class="form-control" value="<?php echo $report['Reporter']; ?>" readonly>
                                <input type="hidden" name="recipient_id" value="<?php echo $report['ReporterID']; ?>">
                                <input type="hidden" name="recipient_type" value="specific">
                            </div>
                            <div class="mb-3">
                                <label for="subjectReporter<?php echo $report['ReportID']; ?>" class="form-label">Subject</label>
                                <input type="text" class="form-control" id="subjectReporter<?php echo $report['ReportID']; ?>" name="subject" placeholder="Enter subject" required>
                            </div>
                            <div class="mb-3">
                                <label for="messageReporter<?php echo $report['ReportID']; ?>" class="form-label">Message</label>
                                <textarea class="form-control" id="messageReporter<?php echo $report['ReportID']; ?>" name="message" rows="3" placeholder="Enter your message" required></textarea>
                            </div>
                            <button type="submit" name="send_notification" class="btn btn-primary">Send</button>
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Activate the correct tab based on URL parameter
        window.onload = function() {
            const urlParams = new URLSearchParams(window.location.search);
            const tab = urlParams.get('tab');
            if (tab === 'suggestions') {
                document.getElementById('suggestions-tab').click();
            }

            // Update recipient_id based on selection
            document.querySelectorAll('select[name="recipient_type"]').forEach(select => {
                select.addEventListener('change', function() {
                    const selectedOption = this.options[this.selectedIndex];
                    const recipientId = selectedOption.getAttribute('data-id');
                    const recipientIdInput = this.parentElement.querySelector('input[name="recipient_id"]');
                    recipientIdInput.value = recipientId;
                });
            });
        };
    </script>
</body>
</html>