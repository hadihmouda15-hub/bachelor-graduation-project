<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Admin panel to edit report details on Home Care Platform.">
    <title>Edit Report - Admin - Home Care</title>
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
            <h2 class="mb-4 animate__animated animate__fadeIn">Edit Report</h2>
            <div class="card shadow-sm animate__animated animate__fadeInUp">
                <div class="card-body">
                    <form onsubmit="alert('Report updated successfully!'); return false;">
                        <div class="mb-3">
                            <label for="reportedUser" class="form-label">Reported User</label>
                            <input type="text" class="form-control" id="reportedUser" value="John Doe" required>
                        </div>
                        <div class="mb-3">
                            <label for="reporter" class="form-label">Reporter</label>
                            <input type="text" class="form-control" id="reporter" value="Jane Smith" required>
                        </div>
                        <div class="mb-3">
                            <label for="reason" class="form-label">Reason</label>
                            <textarea class="form-control" id="reason" rows="3" required>Unprofessional behavior</textarea>
                        </div>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                        <a href="admin_reports.php" class="btn btn-secondary ms-2">Cancel</a>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Styles -->
    <style>
        body { background-color: #f8f9fa; font-family: 'Arial', sans-serif; }
        .sidebar .nav-link:hover { background-color: #3498DB; border-radius: 5px; }
        .sidebar .nav-link.active { background-color: #3498DB; border-radius: 5px; }
        .card { transition: transform 0.3s ease; }
        .card:hover { transform: translateY(-10px); }
        .btn-primary { background-color: #3498DB; border: none; }
        .btn-primary:hover { background-color: #2980B9; }
        .btn-secondary { background-color: #7F8C8D; border: none; }
        .btn-secondary:hover { background-color: #6C7A89; }
        .shadow-sm { box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1); }
    </style>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>