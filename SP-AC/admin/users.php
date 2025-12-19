<?php
// Include database connection
include '../connect.php';

// Initialize variables for filtering
$role_filter = isset($_GET['role']) ? $_GET['role'] : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$alert_message = '';
$alert_type = '';

// Check for message from add_staff.php
if (isset($_GET['message']) && isset($_GET['type'])) {
    $alert_message = $_GET['message'];
    $alert_type = $_GET['type'];
}

// Handle block/activate actions
if (isset($_GET['action']) && isset($_GET['id'])) {
    $user_id = $_GET['id'];
    $action = $_GET['action'];

    if ($action === 'block') {
        $update_query = "UPDATE user SET Status = 'blocked' WHERE UserID = $user_id";
        if (mysqli_query($conn, $update_query)) {
            $alert_message = 'User blocked successfully!';
            $alert_type = 'success';
        } else {
            $alert_message = 'Error blocking user: ' . mysqli_error($conn);
            $alert_type = 'danger';
        }
    } elseif ($action === 'activate') {
        $update_query = "UPDATE user SET Status = 'active' WHERE UserID = $user_id";
        if (mysqli_query($conn, $update_query)) {
            $alert_message = 'User activated successfully!';
            $alert_type = 'success';
        } else {
            $alert_message = 'Error activating user: ' . mysqli_error($conn);
            $alert_type = 'danger';
        }
    }
}

// Build the query with filters
$query = "SELECT u.UserID, u.FullName, u.Email, u.Role, u.Status 
          FROM user u 
          WHERE 1=1";

if ($role_filter) {
    $query .= " AND u.Role = '$role_filter'";
}
if ($status_filter) {
    $query .= " AND u.Status = '$status_filter'";
}

$result = mysqli_query($conn, $query);
if (!$result) {
    $alert_message = 'Error fetching users: ' . mysqli_error($conn);
    $alert_type = 'danger';
}
?>

<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Admin panel to manage users on Home Care Platform.">
    <title>Manage Users - Admin - Home Care</title>
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
            <?php if ($alert_message): ?>
                <div class="alert alert-<?php echo $alert_type; ?> alert-dismissible fade show" role="alert">
                    <?php echo $alert_message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="mb-0">Manage Users</h2>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addStaffModal">
                    <i class="fas fa-user-plus"></i> Add New Staff
                </button>
            </div>

            <!-- Filters and Search -->
            <div class="row mb-3">
                <div class="col-md-4">
                    <label for="roleFilter" class="form-label">Filter by Role</label>
                    <select class="form-select" id="roleFilter" name="role" onchange="updateFilters()">
                        <option value="">All Roles</option>
                        <option value="nurse" <?php echo $role_filter === 'nurse' ? 'selected' : ''; ?>>Nurses</option>
                        <option value="patient" <?php echo $role_filter === 'patient' ? 'selected' : ''; ?>>Patients</option>
                        <option value="staff" <?php echo $role_filter === 'staff' ? 'selected' : ''; ?>>Staff</option>
                        <option value="admin" <?php echo $role_filter === 'admin' ? 'selected' : ''; ?>>Admin</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="statusFilter" class="form-label">Filter by Status</label>
                    <select class="form-select" id="statusFilter" name="status" onchange="updateFilters()">
                        <option value="">All Statuses</option>
                        <option value="active" <?php echo $status_filter === 'active' ? 'selected' : ''; ?>>Active</option>
                        <option value="inactive" <?php echo $status_filter === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                        <option value="blocked" <?php echo $status_filter === 'blocked' ? 'selected' : ''; ?>>Blocked</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="emailFilter" class="form-label">Search by Email</label>
                    <input type="text" class="form-control" id="emailFilter" placeholder="Enter email" oninput="filterTable()">
                </div>
            </div>

            <!-- Users Table -->
            <table class="table table-striped shadow-sm" id="usersTable">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = mysqli_fetch_assoc($result)) { ?>
                        <tr>
                            <td><?php echo $row['UserID']; ?></td>
                            <td><?php echo $row['FullName']; ?></td>
                            <td><?php echo $row['Email']; ?></td>
                            <td><?php echo ucfirst($row['Role']); ?></td>
                            <td>
                                <span class="badge <?php echo $row['Status'] === 'active' ? 'bg-success' : ($row['Status'] === 'inactive' ? 'bg-warning' : 'bg-danger'); ?>">
                                    <?php echo ucfirst($row['Status']); ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($row['Status'] === 'active') { ?>
                                    <a href="#" class="btn btn-danger btn-sm" onclick="showConfirmation('Block', 'users.php?action=block&id=<?php echo $row['UserID']; ?>&role=<?php echo $role_filter; ?>&status=<?php echo $status_filter; ?>')">Block</a>
                                <?php } else { ?>
                                    <a href="#" class="btn btn-success btn-sm" onclick="showConfirmation('Activate', 'users.php?action=activate&id=<?php echo $row['UserID']; ?>&role=<?php echo $role_filter; ?>&status=<?php echo $status_filter; ?>')">Activate</a>
                                <?php } ?>
                                <a href="send_message.php?id=<?php echo $row['UserID']; ?>" class="btn btn-info btn-sm">Send Message</a>
                            </td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Add Staff Modal -->
    <div class="modal fade" id="addStaffModal" tabindex="-1" aria-labelledby="addStaffModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addStaffModalLabel">Add New Staff</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="addStaffForm" action="add_staff.php" method="POST">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="fullName" class="form-label">Full Name</label>
                                <input type="text" class="form-control" id="fullName" name="fullName" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email" pattern=".+@gmail\.com" title="Email must be a Gmail address (e.g., example@gmail.com)" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="password" class="form-label">Password</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="phoneNumber" class="form-label">Phone Number</label>
                                <input type="text" class="form-control" id="phoneNumber" name="phoneNumber" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="gender" class="form-label">Gender</label>
                                <select class="form-select" id="gender" name="gender" required>
                                    <option value="male">Male</option>
                                    <option value="female">Female</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="dateOfBirth" class="form-label">Date of Birth</label>
                                <input type="date" class="form-control" id="dateOfBirth" name="dateOfBirth" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="country" class="form-label">Country</label>
                                <input type="text" class="form-control" id="country" name="country" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="city" class="form-label">City</label>
                                <input type="text" class="form-control" id="city" name="city" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="street" class="form-label">Street</label>
                                <input type="text" class="form-control" id="street" name="street" required>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-primary" onclick="showConfirmAddStaff()">Add Staff</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Confirm Add Staff Modal -->
    <div class="modal fade" id="confirmAddStaffModal" tabindex="-1" aria-labelledby="confirmAddStaffModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="confirmAddStaffModalLabel">Confirm Adding New Staff</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Please review the staff details:</p>
                    <ul id="staffDetails">
                        <li><strong>Full Name:</strong> <span id="confirmFullName"></span></li>
                        <li><strong>Email:</strong> <span id="confirmEmail"></span></li>
                        <li><strong>Phone Number:</strong> <span id="confirmPhoneNumber"></span></li>
                        <li><strong>Gender:</strong> <span id="confirmGender"></span></li>
                        <li><strong>Date of Birth:</strong> <span id="confirmDateOfBirth"></span></li>
                        <li><strong>Address:</strong> <span id="confirmAddress"></span></li>
                    </ul>
                    <p>Are you sure you want to add this staff member?</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="submitAddStaffForm()">Confirm</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Confirmation Modal for Block/Activate -->
    <div class="modal fade" id="confirmationModal" tabindex="-1" aria-labelledby="confirmationModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="confirmationModalLabel">Confirm Action</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p id="confirmationMessage"></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <a id="confirmAction" class="btn btn-danger">Confirm</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Filter and Modal Scripts -->
    <script>
        function filterTable() {
            const roleFilter = document.getElementById('roleFilter').value.toLowerCase();
            const statusFilter = document.getElementById('statusFilter').value.toLowerCase();
            const emailFilter = document.getElementById('emailFilter').value.toLowerCase();
            const rows = document.querySelectorAll('#usersTable tbody tr');

            rows.forEach(row => {
                const role = row.cells[3].textContent.toLowerCase();
                const status = row.cells[4].textContent.toLowerCase();
                const email = row.cells[2].textContent.toLowerCase();

                const roleMatch = roleFilter === '' || role === roleFilter;
                const statusMatch = statusFilter === '' || status.includes(statusFilter);
                const emailMatch = emailFilter === '' || email.includes(emailFilter);

                row.style.display = roleMatch && statusMatch && emailMatch ? '' : 'none';
            });
        }

        function updateFilters() {
            const roleFilter = document.getElementById('roleFilter').value;
            const statusFilter = document.getElementById('statusFilter').value;
            let url = 'users.php';
            if (roleFilter || statusFilter) {
                url += '?';
                if (roleFilter) url += 'role=' + roleFilter;
                if (statusFilter) url += (roleFilter ? '&' : '') + 'status=' + statusFilter;
            }
            window.location.href = url;
        }

        function showConfirmation(action, url) {
            document.getElementById('confirmationMessage').textContent = `Are you sure you want to ${action.toLowerCase()} this user?`;
            document.getElementById('confirmAction').href = url;
            
            var confirmationModal = new bootstrap.Modal(document.getElementById('confirmationModal'));
            confirmationModal.show();
        }

        function showConfirmAddStaff() {
            // Get the form
            const form = document.getElementById('addStaffForm');

            // Check if the form is valid (all required fields are filled)
            if (!form.checkValidity()) {
                // Trigger the browser's default validation messages
                form.reportValidity();
                return;
            }

            // Get form values
            const fullName = document.getElementById('fullName').value;
            const email = document.getElementById('email').value;
            const phoneNumber = document.getElementById('phoneNumber').value;
            const gender = document.getElementById('gender').value;
            const dateOfBirth = document.getElementById('dateOfBirth').value;
            const country = document.getElementById('country').value;
            const city = document.getElementById('city').value;
            const street = document.getElementById('street').value;

            // Populate confirmation modal
            document.getElementById('confirmFullName').textContent = fullName;
            document.getElementById('confirmEmail').textContent = email;
            document.getElementById('confirmPhoneNumber').textContent = phoneNumber;
            document.getElementById('confirmGender').textContent = gender.charAt(0).toUpperCase() + gender.slice(1);
            document.getElementById('confirmDateOfBirth').textContent = dateOfBirth;
            document.getElementById('confirmAddress').textContent = street + ', ' + city + ', ' + country;

            // Show confirmation modal
            var confirmModal = new bootstrap.Modal(document.getElementById('confirmAddStaffModal'));
            confirmModal.show();
        }

        function submitAddStaffForm() {
            // Submit the form directly
            document.getElementById('addStaffForm').submit();
        }

        window.onload = function() {    
            <?php if (isset($_GET['role'])): ?>
                document.getElementById('roleFilter').value = '<?php echo $_GET['role']; ?>';
            <?php endif; ?>
            <?php if (isset($_GET['status'])): ?>
                document.getElementById('statusFilter').value = '<?php echo $_GET['status']; ?>';
            <?php endif; ?>
            filterTable();
        };
    </script>
</body>
</html>