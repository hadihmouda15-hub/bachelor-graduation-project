<?php
require '../connect.php';

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $siteName = $_POST['siteName'];
    $contactEmail = $_POST['contactEmail'];
    $contactPhone = $_POST['contactPhone'];
    $location = $_POST['location'];
    $maintenanceMode = $_POST['maintenanceMode'];

    // Update settings table
    $sql = "UPDATE settings 
            SET ContactEmail = '$contactEmail', 
                ContactPhone = '$contactPhone', 
                Location = '$location', 
                MaintenanceMode = '$maintenanceMode' ,
             SiteName = '$siteName'
              WHERE 1";

    if ($conn->query($sql) === TRUE) {
        // Redirect to the same page with success parameter
        header("Location: settings.php?success=1");
        exit();
    } else {
        echo "Error updating settings: " . $conn->error;
    }
}

// Fetch current settings to populate the form
$sql = "SELECT * FROM settings WHERE 1";
$result = $conn->query($sql);
$settings = $result->fetch_assoc();

$conn->close();
?>

<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Admin panel to manage settings on Home Care Platform.">
    <title>Settings - Admin - Home Care</title>
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
            <h2 class="mb-4">Settings</h2>
            <div class="card shadow-sm">
                <div class="card-body">
                    <h5 class="card-title">Platform Settings</h5>
                    <form action="settings.php" method="post">
                        <div class="mb-3">
                            <label for="siteName" class="form-label">Site Name</label>
                            <input type="text" class="form-control" id="siteName" name="siteName" value="<?php echo $settings['SiteName']; ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="contactEmail" class="form-label">Contact Email</label>
                            <input type="email" class="form-control" id="contactEmail" name="contactEmail" value="<?php echo $settings['ContactEmail']; ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="contactPhone" class="form-label">Contact Phone</label>
                            <input type="text" class="form-control" id="contactPhone" name="contactPhone" value="<?php echo $settings['ContactPhone']; ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="location" class="form-label">Location</label>
                            <input type="text" class="form-control" id="location" name="location" value="<?php echo $settings['Location']; ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="maintenanceMode" class="form-label">Maintenance Mode</label>
                            <select class="form-select" id="maintenanceMode" name="maintenanceMode" required>
                                <option value="0" <?php if ($settings['MaintenanceMode'] == 0) echo 'selected'; ?>>Off</option>
                                <option value="1" <?php if ($settings['MaintenanceMode'] == 1) echo 'selected'; ?>>On</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary">Save Settings</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal -->
    <div class="modal fade" id="successModal" tabindex="-1" aria-labelledby="successModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="successModalLabel">Success</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    Settings saved successfully!
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" data-bs-dismiss="modal">OK</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- JavaScript to show modal if success parameter is present -->
    <script>
        <?php if (isset($_GET['success']) && $_GET['success'] == '1') { ?>
            const modal = new bootstrap.Modal(document.getElementById('successModal'));
            modal.show();
        <?php } ?>
    </script>
</body>
</html>