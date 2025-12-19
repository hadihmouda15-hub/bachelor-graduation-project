<?php
// Start session and include database connection
session_start();
$_SESSION['logged_in'] = true;

require_once 'db_connection.php';

$nurse_id = $_SESSION['nurse_id'];

if (isset($_POST['confirm_logout'])) {
    // session_destroy();
    unset($_SESSION['email']);
    unset($_SESSION['role']);
    unset($_SESSION['full_name']);
    unset($_SESSION['nurse_id']);
    session_destroy();
    header("Location: ../homepage/mainpage.php");
    exit();
}

// Handle Delete Schedule
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_schedule'])) {
    $schedule_id = $_POST['schedule_id'];
    
    $stmt = $conn->prepare("DELETE FROM schedule WHERE ScheduleID = ? AND NurseID = ?");
    $stmt->bind_param("ii", $schedule_id, $nurse_id);
    
    if ($stmt->execute()) {
        $_SESSION['success_message'] = "Schedule deleted successfully!";
    } else {
        $_SESSION['error_message'] = "Error deleting schedule: " . $conn->error;
    }
    header("Location: ".$_SERVER['PHP_SELF']);
    exit();
}

// Handle Update Schedule
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_schedule'])) {
    $schedule_id = $_POST['schedule_id'];
    $date = $_POST['date'];
    $start_time = $_POST['start_time'];
    $end_time = $_POST['end_time'];
    $status = $_POST['status'];
    $notes = $_POST['notes'];
    
    $stmt = $conn->prepare("UPDATE schedule SET 
                          Date = ?, StartTime = ?, EndTime = ?, 
                          Status = ?, Notes = ?
                          WHERE ScheduleID = ? AND NurseID = ?");
    $stmt->bind_param("sssssii", $date, $start_time, $end_time, $status, $notes, $schedule_id, $nurse_id);
    
    if ($stmt->execute()) {
        $_SESSION['success_message'] = "Schedule updated successfully!";
    } else {
        $_SESSION['error_message'] = "Error updating schedule: " . $conn->error;
    }
    header("Location: ".$_SERVER['PHP_SELF']);
    exit();
}

// Display success/error messages
if (isset($_SESSION['success_message'])) {
    $success_message = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}
if (isset($_SESSION['error_message'])) {
    $error_message = $_SESSION['error_message'];
    unset($_SESSION['error_message']);
}

// Rest of your existing PHP code for fetching schedules...





// Handle weekly schedule form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_weekly_schedule'])) {
    $days = $_POST['days'] ?? [];
    $start_time = $_POST['weekly_start_time'];
    $end_time = $_POST['weekly_end_time'];
    
    // Check if nurse already has weekly availability
    $check_stmt = $conn->prepare("SELECT * FROM weekly_availability WHERE NurseID = ?");
    $check_stmt->bind_param("i", $nurse_id);
    $check_stmt->execute();
    $exists = $check_stmt->get_result()->num_rows > 0;
    
    if ($exists) {
        // Update existing record
        $stmt = $conn->prepare("UPDATE weekly_availability SET 
            Sunday = ?, Monday = ?, Tuesday = ?, Wednesday = ?, 
            Thursday = ?, Friday = ?, Saturday = ?,
            StartTime = ?, EndTime = ?
            WHERE NurseID = ?");
    } else {
        // Insert new record
        $stmt = $conn->prepare("INSERT INTO weekly_availability (
            Sunday, Monday, Tuesday, Wednesday, Thursday, Friday, Saturday,
            StartTime, EndTime, NurseID
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    }
    
    // Prepare values for each day
    $day_values = [
        in_array('sunday', $days) ? '1' : '0',
        in_array('monday', $days) ? '1' : '0',
        in_array('tuesday', $days) ? '1' : '0',
        in_array('wednesday', $days) ? '1' : '0',
        in_array('thursday', $days) ? '1' : '0',
        in_array('friday', $days) ? '1' : '0',
        in_array('saturday', $days) ? '1' : '0',
        $start_time,
        $end_time,
        $nurse_id
    ];
    
    $stmt->bind_param(str_repeat("s", 9) . "i", ...$day_values);
    
    if ($stmt->execute()) {
        $success_message = "Weekly availability saved successfully!";
    } else {
        $error_message = "Error saving weekly availability: " . $conn->error;
    }
}

// Check if nurse is logged in
if (!isset($_SESSION['nurse_id'])) {
    header("Location: login.php");
    exit();
}

// Handle single schedule form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_schedule'])) {
    $date = $_POST['date'];
    $start_time = $_POST['start_time'] ? $_POST['start_time'] :  "" ;
    $end_time = $_POST['end_time'] ? $_POST['end_time'] : "" ;
    $status = $_POST['status'];
    $notes = $_POST['notes'];

    $stmt = $conn->prepare("INSERT INTO schedule (Date, StartTime, EndTime, Notes, Status, NurseID) 
                           VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssssi", $date, $start_time, $end_time, $notes, $status, $nurse_id);

    if ($stmt->execute()) {
        $success_message = "Schedule added successfully!";
    } else {
        $error_message = "Error adding schedule: " . $conn->error;
    }
}

// Get nurse's schedules from database
$schedules = [];
$stmt = $conn->prepare("SELECT * FROM schedule WHERE NurseID = ? ORDER BY Date, StartTime");
$stmt->bind_param("i", $nurse_id);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $schedules[] = $row;
}

// Prepare data for FullCalendar
$calendar_events = [];
foreach ($schedules as $schedule) {
    $calendar_events[] = [
        'title' => substr($schedule['Notes'], 0, 20) . '...', // Shorten notes for display
        'start' => $schedule['Date'] . 'T' . $schedule['StartTime'],
        'end' => $schedule['Date'] . 'T' . $schedule['EndTime'],
        'extendedProps' => [
            'status' => $schedule['Status']
        ],
        'color' => $schedule['Status'] == 'available' ? '#2ecc71' : ($schedule['Status'] == 'booked' ? '#e74c3c' : '#f39c12')
    ];
}

// Get weekly availability data
$weekly_availability = [];
$weekly_stmt = $conn->prepare("SELECT * FROM weekly_availability WHERE NurseID = ?");
$weekly_stmt->bind_param("i", $nurse_id);
$weekly_stmt->execute();
$weekly_result = $weekly_stmt->get_result();

if ($weekly_row = $weekly_result->fetch_assoc()) {
    $days = ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'];
    $start_time = $weekly_row['StartTime'];
    $end_time = $weekly_row['EndTime'];
    
    foreach ($days as $day) {
        if ($weekly_row[ucfirst($day)] == '1') {
            // Get day of week (0=Sunday, 6=Saturday)
            $day_num = array_search($day, $days);
            
            // Add to weekly availability array
            $weekly_availability[] = [
                'daysOfWeek' => [$day_num],
                'startTime' => $start_time,
                'endTime' => $end_time,
                'display' => 'background',
                'color' => '#e3f2fd',
                'title' => 'Available',
                'extendedProps' => [
                    'type' => 'weekly_availability'
                ]
            ];
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nurse Scheduling Portal | HomeCare</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- FullCalendar CSS -->
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css" rel="stylesheet">
    <link rel="stylesheet" href="nurse.css">
    <style>
        :root {
            --primary-color: #3498db;
            --secondary-color: #2c3e50;
            --accent-color: #e74c3c;
            --light-color: #ecf0f1;
            --dark-color: #2c3e50;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            font-family: 'Nunito', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background-color: #f8f9fa;
        }

        .sidebar {
            height: 100vh;
            position: sticky;
            top: 0;
            background-color: var(--secondary-color);
            color: white;
        }


        main {
            height: 100vh;
            overflow-y: auto;
            padding: 20px;
            background-color: white;
        }

        .card {
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
            border: none;
        }

        .card-header {
            background-color: var(--primary-color);
            color: white;
            border-radius: 10px 10px 0 0 !important;
            padding: 15px 20px;
        }

        .fc-event {
            cursor: pointer;
            border-radius: 5px;
            padding: 3px 5px;
            font-size: 0.85em;
        }

        .fc-daygrid-event-dot {
            display: none;
        }

        .status-available {
            background-color: #2ecc71;
            border-color: #2ecc71;
        }

        .status-booked {
            background-color: #e74c3c;
            border-color: #e74c3c;
        }

        .status-pending {
            background-color: #f39c12;
            border-color: #f39c12;
        }

        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }

        .btn-primary:hover {
            background-color: #2980b9;
            border-color: #2980b9;
        }

        .table th {
            background-color: var(--light-color);
        }

        #calendar {
            background-color: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }






.fc-weekly-availability {
    padding: 2px 4px;
    font-size: 0.85em;
    color: #0d47a1;
    text-align: center;
}

.fc-timeGridWeek .fc-timegrid-slot.fc-timegrid-slot-lane {
    background-color: rgba(227, 242, 253, 0.3);
}

.fc-timeGridWeek .fc-timegrid-slot.fc-timegrid-slot-label.fc-scrollgrid-shrink {
    background-color: #e3f2fd;
}

/* Add this to your existing styles */
.fc-event {
    cursor: pointer;
    border-radius: 5px;
    padding: 3px 5px;
    font-size: 0.85em;
    z-index: 1 !important; /* Ensure events appear above availability blocks */
}
    </style>
</head>

<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <?php include "sidebar.php"; ?>

            <!-- Main Content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4 overflow-auto">
                <?php if (isset($success_message)): ?>
                    <div class="alert alert-success"><?= $success_message ?></div>
                <?php endif; ?>
                <?php if (isset($error_message)): ?>
                    <div class="alert alert-danger"><?= $error_message ?></div>
                <?php endif; ?>

                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">My Schedule</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <button class="btn btn-sm btn-outline-secondary" id="dayView">Day</button>
                            <button class="btn btn-sm btn-outline-secondary" id="weekView">Week</button>
                            <button class="btn btn-sm btn-outline-secondary active" id="monthView">Month</button>
                        </div>
                        <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addScheduleModal">
                            <i class="fas fa-plus me-1"></i> Add Schedule
                        </button>
                    </div>
                </div>

                <div class="card shadow mb-4">
                    <div class="card-header">
                        <h5 class="m-0 fw-bold"><i class="fas fa-calendar me-2"></i>Calendar View</h5>
                    </div>
                    <div class="card-body">
                        <div id="calendar"></div>
                    </div>
                </div>

                <div class="card shadow">
                    <div class="card-header">
                        <h5 class="m-0 fw-bold"><i class="fas fa-list me-2"></i>Upcoming Schedules</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Time</th>
                                        <th>Duration</th>
                                        <th>Status</th>
                                        <th>Notes</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>


                                <tbody>
    <?php foreach ($schedules as $schedule): ?>
        <tr>
            <td><?= date('M j, Y', strtotime($schedule['Date'])) ?></td>
            <td><?= date('H:i', strtotime($schedule['StartTime'])) ?> - <?= date('H:i', strtotime($schedule['EndTime'])) ?></td>
            <td>
                <?php
                $start = new DateTime($schedule['StartTime']);
                $end = new DateTime($schedule['EndTime']);
                $diff = $start->diff($end);
                echo $diff->h . ' hours' . ($diff->i > 0 ? ' ' . $diff->i . ' minutes' : '');
                ?>
            </td>
            <td>
                <span class="badge bg-<?= $schedule['Status'] == 'available' ? 'success' : ($schedule['Status'] == 'booked' ? 'danger' : 'warning') ?>">
                    <?= ucfirst($schedule['Status']) ?>
                </span>
            </td>
            <td><?= htmlspecialchars($schedule['Notes']) ?></td>
            <td>
                <!-- Edit Button - Opens Modal -->
                <button class="btn btn-sm btn-outline-primary edit-btn" 
                        data-bs-toggle="modal" 
                        data-bs-target="#editScheduleModal"
                        data-id="<?= $schedule['ScheduleID'] ?>"
                        data-date="<?= $schedule['Date'] ?>"
                        data-start="<?= $schedule['StartTime'] ?>"
                        data-end="<?= $schedule['EndTime'] ?>"
                        data-status="<?= $schedule['Status'] ?>"
                        data-notes="<?= htmlspecialchars($schedule['Notes']) ?>">
                    <i class="fas fa-edit"></i>
                </button>
                
                <!-- Delete Button -->
                <form method="POST" action="" class="d-inline">
                    <input type="hidden" name="schedule_id" value="<?= $schedule['ScheduleID'] ?>">
                    <button type="submit" name="delete_schedule" class="btn btn-sm btn-outline-danger">
                        <i class="fas fa-trash"></i>
                    </button>
                </form>
            </td>
        </tr>
    <?php endforeach; ?>
</tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Add Schedule Modal -->
<!-- Replace your existing Add Schedule Modal with this -->
<div class="modal fade" id="addScheduleModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">Add Schedule</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <ul class="nav nav-tabs" id="scheduleTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="single-tab" data-bs-toggle="tab" data-bs-target="#single" type="button" role="tab">Single Day</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="weekly-tab" data-bs-toggle="tab" data-bs-target="#weekly" type="button" role="tab">Weekly Availability</button>
                    </li>
                </ul>
                
                <div class="tab-content p-3 border border-top-0 rounded-bottom">
                    <!-- Single Day Tab -->
                    <div class="tab-pane fade show active" id="single" role="tabpanel">
                        <form method="POST" action="">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="date" class="form-label">Date</label>
                                    <input type="date" class="form-control" id="date" name="date" required>
                                </div>
                                <div class="col-md-3">
                                    <label for="start_time" class="form-label">Start Time</label>
                                    <input type="time" class="form-control" id="start_time" name="start_time" >
                                </div>
                                <div class="col-md-3">
                                    <label for="end_time" class="form-label">End Time</label>
                                    <input type="time" class="form-control" id="end_time" name="end_time" >
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="status" class="form-label">Status</label>
                                <select class="form-select" id="status" name="status" required>
                                    <option value="available">Available</option>
                                    <option value="booked">Booked</option>
                                    <option value="pending">Pending</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="notes" class="form-label">Notes</label>
                                <textarea class="form-control" id="notes" name="notes" rows="3"></textarea>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" name="add_schedule" class="btn btn-primary">Save Schedule</button>
                            </div>
                        </form>
                    </div>
                    
                    <!-- Weekly Availability Tab -->
                    <div class="tab-pane fade" id="weekly" role="tabpanel">
                        <form method="POST" action="">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">Days of Week</label>
                                    <div class="d-flex flex-wrap gap-2">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="monday" name="days[]" value="monday">
                                            <label class="form-check-label" for="monday">Monday</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="tuesday" name="days[]" value="tuesday">
                                            <label class="form-check-label" for="tuesday">Tuesday</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="wednesday" name="days[]" value="wednesday">
                                            <label class="form-check-label" for="wednesday">Wednesday</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="thursday" name="days[]" value="thursday">
                                            <label class="form-check-label" for="thursday">Thursday</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="friday" name="days[]" value="friday">
                                            <label class="form-check-label" for="friday">Friday</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="saturday" name="days[]" value="saturday">
                                            <label class="form-check-label" for="saturday">Saturday</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="sunday" name="days[]" value="sunday">
                                            <label class="form-check-label" for="sunday">Sunday</label>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <label for="weekly_start_time" class="form-label">Start Time</label>
                                    <input type="time" class="form-control" id="weekly_start_time" name="weekly_start_time" >
                                </div>
                                <div class="col-md-3">
                                    <label for="weekly_end_time" class="form-label">End Time</label>
                                    <input type="time" class="form-control" id="weekly_end_time" name="weekly_end_time" >
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" name="add_weekly_schedule" class="btn btn-primary">Save Weekly Availability</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>


<!-- Edit Schedule Modal -->
<div class="modal fade" id="editScheduleModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">Edit Schedule</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="">
                <div class="modal-body">
                    <input type="hidden" name="schedule_id" id="edit_schedule_id">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="edit_date" class="form-label">Date</label>
                            <input type="date" class="form-control" id="edit_date" name="date" required>
                        </div>
                        <div class="col-md-3">
                            <label for="edit_start_time" class="form-label">Start Time</label>
                            <input type="time" class="form-control" id="edit_start_time" name="start_time" required>
                        </div>
                        <div class="col-md-3">
                            <label for="edit_end_time" class="form-label">End Time</label>
                            <input type="time" class="form-control" id="edit_end_time" name="end_time" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="edit_status" class="form-label">Status</label>
                        <select class="form-select" id="edit_status" name="status" required>
                            <option value="available">Available</option>
                            <option value="booked">Booked</option>
                            <option value="pending">Pending</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="edit_notes" class="form-label">Notes</label>
                        <textarea class="form-control" id="edit_notes" name="notes" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="update_schedule" class="btn btn-primary">Update Schedule</button>
                </div>
            </form>
        </div>
    </div>
</div>


    <!-- Logout Modal -->
    <div class="modal fade" id="logoutModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title">Confirm Logout</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to logout from the system?</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <a href="logout.php" class="btn btn-warning">Logout</a>
                </div>
            </div>
        </div>
    </div>

    <?php include "logoutmodal.php" ?>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- FullCalendar JS -->
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js"></script>
    <!-- Custom JS -->
    <script>
        
//         document.addEventListener('DOMContentLoaded', function() {


            
//             // Initialize calendar with PHP data
//             var calendarEl = document.getElementById('calendar');
//             var calendar = new FullCalendar.Calendar(calendarEl, {
//                 initialView: 'dayGridMonth',
//                 headerToolbar: {
//                     left: 'prev,next today',
//                     center: 'title',
//                     right: 'dayGridMonth,timeGridWeek,timeGridDay'
//                 },
//                 events: <?= json_encode($calendar_events) ?>,
//                 eventContent: function(arg) {
//                     let statusBadge = '';
//                     if (arg.event.extendedProps.status === 'available') {
//                         statusBadge = '<span class="badge bg-success">Available</span>';
//                     } else if (arg.event.extendedProps.status === 'booked') {
//                         statusBadge = '<span class="badge bg-danger">Booked</span>';
//                     } else {
//                         statusBadge = '<span class="badge bg-warning">Pending</span>';
//                     }

//                     let timeHtml = '<div class="fc-event-time">' + arg.timeText + '</div>';
//                     let titleHtml = '<div class="fc-event-title">' + arg.event.title + '</div>';
//                     let statusHtml = '<div class="fc-event-status">' + statusBadge + '</div>';

//                     return {
//                         html: timeHtml + titleHtml + statusHtml
//                     };
//                 }
//             });
//             calendar.render();

//             // View switchers
//             document.getElementById('dayView').addEventListener('click', function() {
//                 calendar.changeView('timeGridDay');
//                 this.classList.add('active');
//                 document.getElementById('weekView').classList.remove('active');
//                 document.getElementById('monthView').classList.remove('active');
//             });

//             document.getElementById('weekView').addEventListener('click', function() {
//                 calendar.changeView('timeGridWeek');
//                 this.classList.add('active');
//                 document.getElementById('dayView').classList.remove('active');
//                 document.getElementById('monthView').classList.remove('active');
//             });

//             document.getElementById('monthView').addEventListener('click', function() {
//                 calendar.changeView('dayGridMonth');
//                 this.classList.add('active');
//                 document.getElementById('dayView').classList.remove('active');
//                 document.getElementById('weekView').classList.remove('active');
//             });
//         });



// // Add this to your existing script section
// document.getElementById('addScheduleModal').addEventListener('show.bs.modal', function() {
//     // Reset forms when modal opens
//     document.getElementById('single').querySelector('form').reset();
//     document.getElementById('weekly').querySelector('form').reset();
    
//     // Activate the first tab
//     var firstTab = new bootstrap.Tab(document.getElementById('single-tab'));
//     firstTab.show();
// });
 
 </script>


<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize calendar with PHP data
    var calendarEl = document.getElementById('calendar');
    var calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek,timeGridDay'
        },
        events: <?= json_encode(array_merge($calendar_events, $weekly_availability)) ?>,
        eventContent: function(arg) {
            if (arg.event.extendedProps.type === 'weekly_availability') {
                // Custom rendering for weekly availability blocks
                return {
                    html: '<div class="fc-weekly-availability">Available</div>'
                };
            }
            
            let statusBadge = '';
            if (arg.event.extendedProps.status === 'available') {
                statusBadge = '<span class="badge bg-success">Available</span>';
            } else if (arg.event.extendedProps.status === 'booked') {
                statusBadge = '<span class="badge bg-danger">Booked</span>';
            } else {
                statusBadge = '<span class="badge bg-warning">Pending</span>';
            }

            let timeHtml = '<div class="fc-event-time">' + arg.timeText + '</div>';
            let titleHtml = '<div class="fc-event-title">' + arg.event.title + '</div>';
            let statusHtml = '<div class="fc-event-status">' + statusBadge + '</div>';

            return { html: timeHtml + titleHtml + statusHtml };
        },
        eventDidMount: function(info) {
            if (info.event.extendedProps.type === 'weekly_availability') {
                info.el.style.opacity = '0.8';
                info.el.style.border = '1px dashed #2196F3';
            }
        }
    });
    calendar.render();

    // View switchers
    document.getElementById('dayView').addEventListener('click', function() {
        calendar.changeView('timeGridDay');
        this.classList.add('active');
        document.getElementById('weekView').classList.remove('active');
        document.getElementById('monthView').classList.remove('active');
    });

    document.getElementById('weekView').addEventListener('click', function() {
        calendar.changeView('timeGridWeek');
        this.classList.add('active');
        document.getElementById('dayView').classList.remove('active');
        document.getElementById('monthView').classList.remove('active');
    });

    document.getElementById('monthView').addEventListener('click', function() {
        calendar.changeView('dayGridMonth');
        this.classList.add('active');
        document.getElementById('dayView').classList.remove('active');
        document.getElementById('weekView').classList.remove('active');
    });

    // Refresh calendar when modal is closed
    document.getElementById('addScheduleModal').addEventListener('hidden.bs.modal', function() {
        calendar.refetchEvents();
    });
});



// Edit Button Click Handler
document.querySelectorAll('.edit-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        const modal = new bootstrap.Modal(document.getElementById('editScheduleModal'));
        
        // Fill modal with data
        document.getElementById('edit_schedule_id').value = this.dataset.id;
        document.getElementById('edit_date').value = this.dataset.date;
        document.getElementById('edit_start_time').value = this.dataset.start;
        document.getElementById('edit_end_time').value = this.dataset.end;
        document.getElementById('edit_status').value = this.dataset.status;
        document.getElementById('edit_notes').value = this.dataset.notes;
        
        modal.show();
    });
});



// When edit modal is shown, populate it with data
document.getElementById('editScheduleModal').addEventListener('show.bs.modal', function(event) {
    const button = event.relatedTarget; // Button that triggered the modal
    const modal = this;
    
    // Extract data from button attributes
    modal.querySelector('#edit_schedule_id').value = button.getAttribute('data-id');
    modal.querySelector('#edit_date').value = button.getAttribute('data-date');
    modal.querySelector('#edit_start_time').value = button.getAttribute('data-start');
    modal.querySelector('#edit_end_time').value = button.getAttribute('data-end');
    modal.querySelector('#edit_status').value = button.getAttribute('data-status');
    modal.querySelector('#edit_notes').value = button.getAttribute('data-notes');
});

// Add loading state to delete buttons
</script>


</body>

</html>