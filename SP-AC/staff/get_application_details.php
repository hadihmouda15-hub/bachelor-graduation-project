<?php
require_once 'db_connection.php';

if (isset($_GET['id'])) {
    $appId = $_GET['id'];

    $query = "SELECT * FROM nurseapplication WHERE NAID = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $appId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();

        echo "<div class='cert-details-container'>";

        // Header
        echo '<div class="cert-header" style="display: flex; justify-content: space-between;">';
        echo "<h3>Nurse Application Details</h3>";
        echo '<span class="close" style="color: white;" onclick="closeModal(\'viewApplicationModal\')">&times;</span>';
        echo "</div>";

        // Two-column content
        echo "<div class='cert-content'>";

        // Left column
        echo "<div class='cert-column'>";
        echo "<div class='detail-group'><label>Full Name:</label><p class='detail-value'>{$row['FullName']}</p></div>";
        echo "<div class='detail-group'><label>Email:</label><p class='detail-value'>{$row['Email']}</p></div>";
        echo "<div class='detail-group'><label>Phone:</label><p class='detail-value'>{$row['PhoneNumber']}</p></div>";
        echo "<div class='detail-group'><label>Gender:</label><p class='detail-value'>{$row['Gender']}</p></div>";
        echo "<div class='detail-group'><label>Date of Birth:</label><p class='detail-value'>{$row['DateOfBirth']}</p></div>";
        echo "<div class='detail-group'><label>Language:</label><p class='detail-value'>{$row['Language']}</p></div>";
        echo "</div>";

        // Right column
        echo "<div class='cert-column'>";
        echo "<div class='detail-group'><label>Specialization:</label><p class='detail-value'>{$row['Specialization']}</p></div>";
        echo "<div class='detail-group'><label>Syndicate Number:</label><p class='detail-value'>{$row['SyndicateNumber']}</p></div>";
        echo "<div class='detail-group'><label>Status:</label><p class='detail-value'><span class='status " . strtolower($row['Status']) . "'>{$row['Status']}</span></p></div>";

        if (!empty($row['Comments'])) {
            echo "<div class='detail-group'><label>Comments:</label><p class='detail-value'>{$row['Comments']}</p></div>";
        }

        if (!empty($row['RejectedReason'])) {
            echo "<div class='detail-group'><label>Rejected Reason:</label><p class='detail-value'>{$row['RejectedReason']}</p></div>";
        }

        echo "</div>"; // End right column

        // third column
        echo "<div class='cert-column'>";
        // Document (Image)
        // if (!empty($row['Picture'])) {
        //     echo "<div class='document-preview'>";
        //     echo "<label>Profile Picture:</label>";
        //     echo "<div class='image-container'>";
        //     echo "<img src='{$row['Picture']}' alt='Profile Picture'>";
        //     echo "<a href='{$row['Picture']}' target='_blank' class='view-link'>View Full Size</a>";
        //     echo "</div>";
        //     echo "</div>";
        // }

        if (!empty($row['Picture'])) {
            echo '<div class="detail-section">';
            echo '<h4>Profile Picture</h4>';
            $imagePath = '../homepage/uploads/images/' . basename($row['Picture']);
            echo '<div class="document-preview">';
            echo '<img src="' . htmlspecialchars($imagePath) . '" alt="Profile Picture">';
            echo '<div class="document-actions">';
            echo '<a href="' . htmlspecialchars($imagePath) . '" target="_blank" class="view-full-btn">View Full Size</a>';
            echo '</div>';
            echo '</div>';
            echo '</div>'; // end detail-section
        }


        // CV Link
        if (!empty($row['URL_CV'])) {
            $cvPath = "../homepage/uploads/cvs/" . basename($row['URL_CV']);
            echo "<div class='detail-group'>";
            echo "<a href='$cvPath' target='_blank' class='view-cv-btn'>View CV</a>";
            echo "</div>";
        }



        echo "</div>"; // End third column

        echo "</div>"; // End main content


        // Action Buttons
        if ($row['Status'] === 'Pending') {
            echo "<div class='action-buttons'>";
            echo "<form method='post' action='nurse_applications.php' onsubmit='return confirmAction(\"approve\")'>";
            echo "<input type='hidden' name='naid' value='{$row['NAID']}'>";
            echo "<input type='hidden' name='action' value='approve'>";
            echo "<button type='submit' class='btn approve-btn'>Approve</button>";
            echo "</form>";

            echo "<form method='post' action='nurse_applications.php' onsubmit='return confirmAction(\"reject\")'>";
            echo "<input type='hidden' name='naid' value='{$row['NAID']}'>";
            echo "<input type='hidden' name='action' value='reject'>";
            echo "<button type='submit' class='btn reject-btn'>Reject</button>";
            echo "</form>";
            echo "</div>";
        }



        echo '<div style="display: flex; justify-content: flex-end; width: 100%;">
        <button class="btn btn-light" style="margin: 10px;" onclick="closeModal(\'viewApplicationModal\')">Close</button>
      </div>';



        echo "</div>"; // End container
    } else {
        echo "<div class='alert'>Application not found.</div>";
    }

    $stmt->close();
} else {
    echo "<div class='alert'>Invalid request.</div>";
}
