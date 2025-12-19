<?php
include '../connect.php';

$active_tab = isset($_POST['active_tab']) ? $_POST['active_tab'] : 'services';
$debug_message = ''; // For debugging

// Handle Service Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_service') {
    $name = mysqli_real_escape_string($conn, trim($_POST['name']));
    $type = mysqli_real_escape_string($conn, trim($_POST['type']));
    $duration = intval($_POST['duration']);
    $description = mysqli_real_escape_string($conn, trim($_POST['description']));
    
    if (!empty($name)) {
        $insert_query = "INSERT INTO service (Name, Type, Duration, Description) VALUES ('$name', '$type', $duration, '$description')";
        if (mysqli_query($conn, $insert_query)) {
            $debug_message = "Service added successfully.";
        } else {
            $debug_message = "Error adding service: " . mysqli_error($conn);
        }
    } else {
        $debug_message = "Service name is required.";
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit_service') {
    $service_id = intval($_POST['service_id']);
    $name = mysqli_real_escape_string($conn, trim($_POST['name']));
    $type = mysqli_real_escape_string($conn, trim($_POST['type']));
    $duration = intval($_POST['duration']);
    $description = mysqli_real_escape_string($conn, trim($_POST['description']));
    
    if (!empty($name)) {
        $update_query = "UPDATE service SET Name = '$name', Type = '$type', Duration = $duration, Description = '$description' WHERE ServiceID = $service_id";
        if (mysqli_query($conn, $update_query)) {
            $debug_message = "Service updated successfully.";
        } else {
            $debug_message = "Error updating service: " . mysqli_error($conn);
        }
    } else {
        $debug_message = "Service name is required.";
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_service') {
    $service_id = intval($_POST['service_id']);
    $delete_query = "DELETE FROM service WHERE ServiceID = $service_id";
    if (mysqli_query($conn, $delete_query)) {
        $debug_message = "Service deleted successfully.";
    } else {
        $debug_message = "Error deleting service: " . mysqli_error($conn);
    }
}

// Handle Care Needed Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_care') {
    $name = mysqli_real_escape_string($conn, trim($_POST['name']));
    if (!empty($name)) {
        $insert_query = "INSERT INTO care_needed (Name) VALUES ('$name')";
        if (mysqli_query($conn, $insert_query)) {
            $debug_message = "Care type added successfully.";
        } else {
            $debug_message = "Error adding care type: " . mysqli_error($conn);
        }
    } else {
        $debug_message = "Care type name is required.";
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit_care') {
    $care_id = intval($_POST['care_id']);
    $name = mysqli_real_escape_string($conn, trim($_POST['name']));
    if (!empty($name)) {
        $update_query = "UPDATE care_needed SET Name = '$name' WHERE CareID = $care_id";
        if (mysqli_query($conn, $update_query)) {
            $debug_message = "Care type updated successfully.";
        } else {
            $debug_message = "Error updating care type: " . mysqli_error($conn);
        }
    } else {
        $debug_message = "Care type name is required.";
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_care') {
    $care_id = intval($_POST['care_id']);
    $delete_query = "DELETE FROM care_needed WHERE CareID = $care_id";
    if (mysqli_query($conn, $delete_query)) {
        $debug_message = "Care type deleted successfully.";
    } else {
        $debug_message = "Error deleting care type: " . mysqli_error($conn);
    }
}

// Fetch Services
$service_query = "SELECT * FROM service";
$service_result = mysqli_query($conn, $service_query);
$services = mysqli_fetch_all($service_result, MYSQLI_ASSOC);

// Fetch Care Needed
$care_query = "SELECT * FROM care_needed";
$care_result = mysqli_query($conn, $care_query);
$care_types = mysqli_fetch_all($care_result, MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Admin panel to manage services and care needed on Home Care Platform.">
    <title>Manage Services - Admin - Home Care</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/admin.css">
    <style>
        /* Ensure consistent button sizes and spacing */
        .action-buttons {
            display: flex;
            gap: 8px; /* Space between buttons */
            white-space: nowrap; /* Prevent wrapping */
        }
        .action-buttons .btn {
            min-width: 70px; /* Consistent button width */
            padding: 5px 10px; /* Consistent padding */
            font-size: 14px; /* Consistent font size */
        }
        /* Consistent table column widths for Manage Care Needed */
        .care-needed-table th:nth-child(1),
        .care-needed-table td:nth-child(1) {
            width: 10%; /* ID column */
        }
        .care-needed-table th:nth-child(2),
        .care-needed-table td:nth-child(2) {
            width: 60%; /* Name column */
        }
        .care-needed-table th:nth-child(3),
        .care-needed-table td:nth-child(3) {
            width: 30%; /* Actions column */
        }
        /* Consistent table cell padding */
        .care-needed-table th,
        .care-needed-table td {
            padding: 12px; /* Consistent padding */
            vertical-align: middle; /* Center content vertically */
        }
        /* Smooth spacing for the table */
        .care-needed-table {
            border-collapse: separate;
            border-spacing: 0;
        }
    </style>
</head>
<body>
    <div class="d-flex">
        <?php include 'sidebar.php'; ?>

        <div class="content flex-grow-1 p-4" style="margin-left: 250px;">
            <h2 class="mb-4">Manage Services</h2>

            <!-- Debug Message -->
            <?php if ($debug_message) { ?>
                <div class="alert alert-info"><?php echo $debug_message; ?></div>
            <?php } ?>

            <!-- Tabs -->
            <ul class="nav nav-tabs mb-3" id="serviceTabs" role="tablist">
                <li class="nav-item">
                    <a class="nav-link <?php echo $active_tab === 'services' ? 'active' : ''; ?>" id="services-tab" data-bs-toggle="tab" href="#services" role="tab">Manage Services</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $active_tab === 'care-needed' ? 'active' : ''; ?>" id="care-needed-tab" data-bs-toggle="tab" href="#care-needed" role="tab">Manage Care Needed</a>
                </li>
            </ul>

            <div class="tab-content" id="serviceTabContent">
                <!-- Manage Services Tab -->
                <div class="tab-pane fade <?php echo $active_tab === 'services' ? 'show active' : ''; ?>" id="services" role="tabpanel">
                    <!-- Add Service Button -->
                    <div class="mb-4">
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addServiceModal">Add New Service</button>
                    </div>
                    <!-- Services Table -->
                    <table class="table table-striped shadow-sm">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Type</th>
                                <th>Duration</th>
                                <th>Description</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($services) > 0) { ?>
                                <?php foreach ($services as $service) { ?>
                                    <tr>
                                        <td><?php echo $service['ServiceID']; ?></td>
                                        <td><?php echo htmlspecialchars($service['Name']); ?></td>
                                        <td><?php echo htmlspecialchars($service['Type'] ?? 'N/A'); ?></td>
                                        <td><?php echo $service['Duration'] ?? 'N/A'; ?></td>
                                        <td><?php echo htmlspecialchars($service['Description'] ?? 'N/A'); ?></td>
                                        <td>
                                            <div class="action-buttons">
                                                <button class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#editServiceModal_<?php echo $service['ServiceID']; ?>">Edit</button>
                                                <button type="button" class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#deleteServiceModal_<?php echo $service['ServiceID']; ?>">Delete</button>
                                            </div>
                                        </td>
                                    </tr>
                                    <!-- Edit Service Modal -->
                                    <div class="modal fade" id="editServiceModal_<?php echo $service['ServiceID']; ?>" tabindex="-1" aria-labelledby="editServiceModalLabel_<?php echo $service['ServiceID']; ?>" aria-hidden="true">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title" id="editServiceModalLabel_<?php echo $service['ServiceID']; ?>">Edit Service</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <form method="POST">
                                                        <input type="hidden" name="action" value="edit_service">
                                                        <input type="hidden" name="service_id" value="<?php echo $service['ServiceID']; ?>">
                                                        <input type="hidden" name="active_tab" value="services">
                                                        <div class="mb-3">
                                                            <label for="edit_service_name_<?php echo $service['ServiceID']; ?>" class="form-label">Service Name</label>
                                                            <input type="text" class="form-control" id="edit_service_name_<?php echo $service['ServiceID']; ?>" name="name" value="<?php echo htmlspecialchars($service['Name']); ?>" required>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label for="edit_service_type_<?php echo $service['ServiceID']; ?>" class="form-label">Type</label>
                                                            <select class="form-select" name="type" id="edit_service_type_<?php echo $service['ServiceID']; ?>" required>
                                                                <option value="">Select an option</option>
                                                                <option value="Non-Medical" <?php echo ($service['Type'] === 'Non-Medical') ? 'selected' : ''; ?>>Non-Medical</option>
                                                                <option value="Medical" <?php echo ($service['Type'] === 'Medical') ? 'selected' : ''; ?>>Medical</option>
                                                            </select>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label for="edit_service_duration_<?php echo $service['ServiceID']; ?>" class="form-label">Duration (minutes)</label>
                                                            <input type="number" class="form-control" id="edit_service_duration_<?php echo $service['ServiceID']; ?>" name="duration" value="<?php echo $service['Duration'] ?? ''; ?>">
                                                        </div>
                                                        <div class="mb-3">
                                                            <label for="edit_service_description_<?php echo $service['ServiceID']; ?>" class="form-label">Description</label>
                                                            <textarea class="form-control" id="edit_service_description_<?php echo $service['ServiceID']; ?>" name="description" rows="3"><?php echo htmlspecialchars($service['Description'] ?? ''); ?></textarea>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="submit" class="btn btn-primary">Save Changes</button>
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <!-- Delete Service Modal -->
                                    <div class="modal fade" id="deleteServiceModal_<?php echo $service['ServiceID']; ?>" tabindex="-1" aria-labelledby="deleteServiceModalLabel_<?php echo $service['ServiceID']; ?>" aria-hidden="true">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title" id="deleteServiceModalLabel_<?php echo $service['ServiceID']; ?>">Confirm Deletion</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <p>Are you sure you want to delete the service "<?php echo htmlspecialchars($service['Name']); ?>"?</p>
                                                </div>
                                                <div class="modal-footer">
                                                    <form method="POST">
                                                        <input type="hidden" name="action" value="delete_service">
                                                        <input type="hidden" name="service_id" value="<?php echo $service['ServiceID']; ?>">
                                                        <input type="hidden" name="active_tab" value="services">
                                                        <button type="submit" class="btn btn-danger">Confirm</button>
                                                    </form>
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php } ?>
                            <?php } else { ?>
                                <tr>
                                    <td colspan="6" class="text-center">No services found.</td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                    <!-- Add Service Modal -->
                    <div class="modal fade" id="addServiceModal" tabindex="-1" aria-labelledby="addServiceModalLabel" aria-hidden="true">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="addServiceModalLabel">Add New Service</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <form method="POST">
                                        <input type="hidden" name="action" value="add_service">
                                        <input type="hidden" name="active_tab" value="services">
                                        <div class="mb-3">
                                            <label for="add_service_name" class="form-label">Service Name</label>
                                            <input type="text" class="form-control" id="add_service_name" name="name" required>
                                        </div>
                                        <div class="mb-3">
                                            <label for="add_service_type" class="form-label fw-bold">Type</label>
                                            <select class="form-select" name="type" id="add_service_type" required>
                                                <option value="">Select an option</option>
                                                <option value="Non-Medical">Non-Medical</option>
                                                <option value="Medical">Medical</option>
                                            </select>
                                        </div>
                                        <div class="mb-3">
                                            <label for="add_service_duration" class="form-label">Duration (minutes)</label>
                                            <input type="number" class="form-control" id="add_service_duration" name="duration">
                                        </div>
                                        <div class="mb-3">
                                            <label for="add_service_description" class="form-label">Description</label>
                                            <textarea class="form-control" id="add_service_description" name="description" rows="3"></textarea>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="submit" class="btn btn-primary">Add Service</button>
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Manage Care Needed Tab -->
                <div class="tab-pane fade <?php echo $active_tab === 'care-needed' ? 'show active' : ''; ?>" id="care-needed" role="tabpanel">
                    <!-- Add Care Needed Button -->
                    <div class="mb-4">
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCareModal">Add New Care Type</button>
                    </div>
                    <!-- Care Needed Table -->
                    <table class="table table-striped shadow-sm care-needed-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($care_types) > 0) { ?>
                                <?php foreach ($care_types as $care) { ?>
                                    <tr>
                                        <td><?php echo $care['CareID']; ?></td>
                                        <td><?php echo htmlspecialchars($care['Name']); ?></td>
                                        <td>
                                            <div class="action-buttons">
                                                <button class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#editCareModal_<?php echo $care['CareID']; ?>">Edit</button>
                                                <button type="button" class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#deleteCareModal_<?php echo $care['CareID']; ?>">Delete</button>
                                            </div>
                                        </td>
                                    </tr>
                                    <!-- Edit Care Needed Modal -->
                                    <div class="modal fade" id="editCareModal_<?php echo $care['CareID']; ?>" tabindex="-1" aria-labelledby="editCareModalLabel_<?php echo $care['CareID']; ?>" aria-hidden="true">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title" id="editCareModalLabel_<?php echo $care['CareID']; ?>">Edit Care Type</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <form method="POST">
                                                        <input type="hidden" name="action" value="edit_care">
                                                        <input type="hidden" name="care_id" value="<?php echo $care['CareID']; ?>">
                                                        <input type="hidden" name="active_tab" value="care-needed">
                                                        <div class="mb-3">
                                                            <label for="edit_care_name_<?php echo $care['CareID']; ?>" class="form-label">Care Type Name</label>
                                                            <input type="text" class="form-control" id="edit_care_name_<?php echo $care['CareID']; ?>" name="name" value="<?php echo htmlspecialchars($care['Name']); ?>" required>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="submit" class="btn btn-primary">Save Changes</button>
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <!-- Delete Care Modal -->
                                    <div class="modal fade" id="deleteCareModal_<?php echo $care['CareID']; ?>" tabindex="-1" aria-labelledby="deleteCareModalLabel_<?php echo $care['CareID']; ?>" aria-hidden="true">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title" id="deleteCareModalLabel_<?php echo $care['CareID']; ?>">Confirm Deletion</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <p>Are you sure you want to delete the care type "<?php echo htmlspecialchars($care['Name']); ?>"?</p>
                                                </div>
                                                <div class="modal-footer">
                                                    <form method="POST">
                                                        <input type="hidden" name="action" value="delete_care">
                                                        <input type="hidden" name="care_id" value="<?php echo $care['CareID']; ?>">
                                                        <input type="hidden" name="active_tab" value="care-needed">
                                                        <button type="submit" class="btn btn-danger">Confirm</button>
                                                    </form>
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php } ?>
                            <?php } else { ?>
                                <tr>
                                    <td colspan="3" class="text-center">No care types found.</td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                    <!-- Add Care Needed Modal -->
                    <div class="modal fade" id="addCareModal" tabindex="-1" aria-labelledby="addCareModalLabel" aria-hidden="true">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="addCareModalLabel">Add New Care Type</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <form method="POST">
                                        <input type="hidden" name="action" value="add_care">
                                        <input type="hidden" name="active_tab" value="care-needed">
                                        <div class="mb-3">
                                            <label for="add_care_name" class="form-label">Care Type Name</label>
                                            <input type="text" class="form-control" id="add_care_name" name="name" required>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="submit" class="btn btn-primary">Add Care Type</button>
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>