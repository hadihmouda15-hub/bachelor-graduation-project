<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <style>
        /* Notification specific styles */
        .notification-container {
            display: flex;
            gap: 20px;
            margin-top: 20px;
        }
        
        .notification-section {
            flex: 1;
            background-color: white;
            border-radius: 5px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 20px;
        }
        
        .notification-section h4 {
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
            color: var(--dark-color);
        }
        
        .notification-item {
            padding: 15px;
            border-bottom: 1px solid #f5f5f5;
            transition: all 0.3s;
        }
        
        .notification-item:hover {
            background-color: #f9f9f9;
        }
        
        .notification-item.unread {
            background-color: #f0f7ff;
        }
        
        .notification-title {
            font-weight: 600;
            margin-bottom: 5px;
            display: flex;
            justify-content: space-between;
        }
        
        .notification-meta {
            font-size: 0.8rem;
            color: var(--gray-color);
            margin-bottom: 5px;
        }
        
        .notification-message {
            font-size: 0.9rem;
            color: #555;
        }
        
        .notification-type {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 0.7rem;
            font-weight: 600;
            margin-left: 5px;
        }
        
        .type-info {
            background-color: #d1ecf1;
            color: #0c5460;
        }
        
        .type-warning {
            background-color: #fff3cd;
            color: #856404;
        }
        
        .type-urgent {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        .type-update {
            background-color: #d4edda;
            color: #155724;
        }
        
        /* Form styles */
        #notificationForm {
            max-width: 600px;
            margin: 0 auto;
        }
        
        /* Tabs for notification sections */
        .notification-tabs {
            display: flex;
            margin-bottom: 20px;
        }
        
        .notification-tab {
            padding: 10px 20px;
            cursor: pointer;
            background-color: #f5f5f5;
            border: 1px solid #ddd;
            border-bottom: none;
            margin-right: 5px;
            border-radius: 5px 5px 0 0;
        }
        
        .notification-tab.active {
            background-color: white;
            border-bottom: 1px solid white;
            margin-bottom: -1px;
        }
    </style>
</head>

<body>
    <?php
    require_once 'db_connection.php';
    session_start();

    // Check if user is logged in and has staff role
    // if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'staff') {
    //     header("Location: login.php");
    //     exit();
    // }

    $staff_id = 21; // Staff ID
    $admin_id = 20; // Admin ID

    // Handle sending notifications
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_notification'])) {
        $title = $_POST['title'];
        $message = $_POST['message'];
        $type = $_POST['type'];
        
        $stmt = $conn->prepare("INSERT INTO notification (SenderID, SenderType, RecipientID, RecipientType, Title, Message, Type) 
                              VALUES (?, 'Staff', ?, 'Admin', ?, ?, ?)");
        $stmt->bind_param("iisss", $staff_id, $admin_id, $title, $message, $type);
        
        if ($stmt->execute()) {
            $_SESSION['message'] = "Notification sent to admin successfully!";
            $_SESSION['message_type'] = "success";
        } else {
            $_SESSION['message'] = "Error sending notification: " . $conn->error;
            $_SESSION['message_type'] = "error";
        }
        
        $stmt->close();
        header("Location: ".$_SERVER['PHP_SELF']);
        exit();
    }

    // Fetch notifications sent by this staff to admin
    $sent_query = "SELECT n.*, u.FullName as RecipientName 
                   FROM notification n
                   JOIN user u ON n.RecipientID = u.UserID
                   WHERE n.SenderID = ? AND n.RecipientID = ?
                   ORDER BY n.Date DESC";
    $sent_stmt = $conn->prepare($sent_query);
    $sent_stmt->bind_param("ii", $staff_id, $admin_id);
    $sent_stmt->execute();
    $sent_result = $sent_stmt->get_result();

    // Fetch notifications received by this staff from admin
    $received_query = "SELECT n.*, u.FullName as SenderName 
                       FROM notification n
                       JOIN user u ON n.SenderID = u.UserID
                       WHERE n.SenderID = ? AND n.RecipientID = ?
                       ORDER BY n.Date DESC";
    $received_stmt = $conn->prepare($received_query);
    $received_stmt->bind_param("ii", $admin_id, $staff_id);
    $received_stmt->execute();
    $received_result = $received_stmt->get_result();
    ?>
    
    <div class="container">
        <?php include "sidebar.php" ?>
        <div class="main-content">            
            <?php if (isset($_SESSION['message'])): ?>
                <div class="alert alert-<?php echo $_SESSION['message_type']; ?>">
                    <?php 
                    echo $_SESSION['message']; 
                    unset($_SESSION['message']);
                    unset($_SESSION['message_type']);
                    ?>
                </div>
            <?php endif; ?>
            
            <div class="tab-content active" id="notifications">
                <div class="card">
                    <div class="card-header">
                        <h3>Send Notification to Admin</h3>
                    </div>
                    <div class="card-body">
                        <form method="POST" id="notificationForm">
                            <input type="hidden" name="send_notification" value="1">
                            <div class="form-group">
                                <label for="title">Title</label>
                                <input type="text" id="title" name="title" class="form-control" placeholder="Notification title" required>
                            </div>
                            <div class="form-group">
                                <label for="message">Message</label>
                                <textarea id="message" name="message" class="form-control" placeholder="Enter your message here" required rows="4"></textarea>
                            </div>
                            <div class="form-group">
                                <label for="type">Type</label>
                                <select id="type" name="type" class="form-control" required>
                                    <option value="info">Information</option>
                                    <option value="warning">Warning</option>
                                    <option value="urgent">Urgent</option>
                                    <option value="update">System Update</option>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-primary">Send Notification</button>
                        </form>
                    </div>
                </div>
                
                <div class="notification-tabs">
                    <div class="notification-tab active" data-tab="sent">Sent to Admin</div>
                    <div class="notification-tab" data-tab="received">Received from Admin</div>
                </div>
                
                <div class="notification-container">
                    <!-- Sent Notifications Section -->
                    <div class="notification-section" id="sent-notifications">
                        <h4>Notifications Sent to Admin</h4>
                        <?php if ($sent_result->num_rows > 0): ?>
                            <?php while ($notification = $sent_result->fetch_assoc()): ?>
                                <div class="notification-item">
                                    <div class="notification-title">
                                        <?php echo htmlspecialchars($notification['Title']); ?>
                                        <span class="notification-type type-<?php echo htmlspecialchars($notification['Type']); ?>">
                                            <?php echo ucfirst(htmlspecialchars($notification['Type'])); ?>
                                        </span>
                                    </div>
                                    <div class="notification-meta">
                                        To: <?php echo htmlspecialchars($notification['RecipientName']); ?> | 
                                        <?php echo date('M j, Y g:i A', strtotime($notification['Date'])); ?>
                                    </div>
                                    <div class="notification-message">
                                        <?php echo htmlspecialchars($notification['Message']); ?>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <p>No notifications sent to admin yet.</p>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Received Notifications Section -->
                    <div class="notification-section" id="received-notifications" style="display: none;">
                        <h4>Notifications Received from Admin</h4>
                        <?php if ($received_result->num_rows > 0): ?>
                            <?php while ($notification = $received_result->fetch_assoc()): ?>
                                <div class="notification-item <?php echo $notification['Status'] === 'Unread' ? 'unread' : ''; ?>">
                                    <div class="notification-title">
                                        <?php echo htmlspecialchars($notification['Title']); ?>
                                        <span class="notification-type type-<?php echo htmlspecialchars($notification['Type']); ?>">
                                            <?php echo ucfirst(htmlspecialchars($notification['Type'])); ?>
                                        </span>
                                    </div>
                                    <div class="notification-meta">
                                        From: <?php echo htmlspecialchars($notification['SenderName']); ?> | 
                                        <?php echo date('M j, Y g:i A', strtotime($notification['Date'])); ?>
                                    </div>
                                    <div class="notification-message">
                                        <?php echo htmlspecialchars($notification['Message']); ?>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <p>No notifications received from admin.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>


    <!-- Add this just before the closing </body> tag -->
<div id="logoutModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Confirm Logout</h3>
            <span class="close">&times;</span>
        </div>
        <div class="modal-body">
            <p>Are you sure you want to log out?</p>
        </div>
        <div class="modal-footer">
            <button class="btn btn-light" id="cancelLogout">Cancel</button>
            <button class="btn btn-danger" id="confirmLogout">Log Out</button>
        </div>
    </div>
</div>


    <script>
        // Tab functionality
        document.querySelectorAll('.notification-tab').forEach(tab => {
            tab.addEventListener('click', function() {
                // Update active tab
                document.querySelectorAll('.notification-tab').forEach(t => t.classList.remove('active'));
                this.classList.add('active');
                
                // Show corresponding content
                const tabName = this.getAttribute('data-tab');
                document.getElementById('sent-notifications').style.display = 'none';
                document.getElementById('received-notifications').style.display = 'none';
                
                if (tabName === 'sent') {
                    document.getElementById('sent-notifications').style.display = 'block';
                } else {
                    document.getElementById('received-notifications').style.display = 'block';
                }
            });
        });
        
        // Mark notifications as read when received tab is clicked
        document.querySelector('.notification-tab[data-tab="received"]').addEventListener('click', function() {
            fetch('mark_notifications_read.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'staff_id=<?php echo $staff_id; ?>'
            });
        });


            // Get modal elements
    const logoutModal = document.getElementById('logoutModal');
    const logoutBtn = document.querySelector('.logout-btn');
    const closeBtn = document.querySelector('.close');
    const cancelBtn = document.getElementById('cancelLogout');
    const confirmBtn = document.getElementById('confirmLogout');

    // Open modal when logout button is clicked
    logoutBtn.addEventListener('click', function(e) {
        e.preventDefault();
        logoutModal.style.display = 'flex';
    });

    // Close modal when X is clicked
    closeBtn.addEventListener('click', function() {
        logoutModal.style.display = 'none';
    });

    // Close modal when Cancel is clicked
    cancelBtn.addEventListener('click', function() {
        logoutModal.style.display = 'none';
    });

    // Redirect to logout page when confirmed
confirmBtn.addEventListener('click', function() {
    fetch('../includes/logout.php', {
        method: 'POST',
        credentials: 'same-origin'
    })
    .then(response => {
        if (response.ok) {
            window.location.href = '../homepage/mainpage.php';
        } 
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred during logout.');
    });
});// Redirect to logout page when confirmed
    confirmBtn.addEventListener('click', function() {
        window.location.href = '../homepage/mainpage.php';
    });



    // Close modal when clicking outside
    window.addEventListener('click', function(e) {
        if (e.target === logoutModal) {
            logoutModal.style.display = 'none';
        }
    });
    </script>
</body>

</html>