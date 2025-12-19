<?php
require_once 'db_connection.php';

if (isset($_GET['id'])) {
    $certification_id = intval($_GET['id']);
    
    $query = "SELECT c.*, u.FullName, u.Email, u.PhoneNumber 
              FROM certification c
              JOIN nurse n ON c.NurseID = n.NurseID
              JOIN user u ON n.UserID = u.UserID
              WHERE c.CertificationID = ?";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $certification_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        
        // Determine status badge class
        $statusClass = strtolower($row['Status']) . '-badge';
        
        echo '<div class="certification-header">';
        echo '<h3>Certification Application Details</h3>';
        echo '<span class="status-badge ' . $statusClass . '">' . htmlspecialchars($row['Status']) . '</span>';
        echo '</div>';
        
        echo '<div class="certification-details-grid">';
        
        // Left column - Application Details
        echo '<div class="details-column">';
        echo '<div class="detail-section">';
        echo '<h4>Application Information</h4>';
        echo '<div class="detail-row">';
        echo '<span class="detail-label">Nurse Name:</span>';
        echo '<span class="detail-value">' . htmlspecialchars($row['FullName']) . '</span>';
        echo '</div>';
        echo '<div class="detail-row">';
        echo '<span class="detail-label">Certification Type:</span>';
        echo '<span class="detail-value">' . htmlspecialchars($row['Name']) . '</span>';
        echo '</div>';
        echo '<div class="detail-row">';
        echo '<span class="detail-label">Submitted On:</span>';
        echo '<span class="detail-value">' . date('Y-m-d H:i', strtotime($row['CreatedAt'])) . '</span>';
        echo '</div>';
        echo '</div>'; // end detail-section
        
        // Contact Information
        echo '<div class="detail-section">';
        echo '<h4>Contact Information</h4>';
        echo '<div class="detail-row">';
        echo '<span class="detail-label">Email:</span>';
        echo '<span class="detail-value">' . htmlspecialchars($row['Email']) . '</span>';
        echo '</div>';
        echo '<div class="detail-row">';
        echo '<span class="detail-label">Phone:</span>';
        echo '<span class="detail-value">' . htmlspecialchars($row['PhoneNumber']) . '</span>';
        echo '</div>';
        echo '</div>'; // end detail-section
        echo '</div>'; // end details-column
        
        // Right column - Document and Comments
        echo '<div class="details-column">';
        
        if (!empty($row['Image'])) {
            echo '<div class="detail-section">';
            echo '<h4>Certification Document</h4>';
            $imagePath = '../nurse/uploads/certifications/' . basename($row['Image']);
            echo '<div class="document-preview">';
            echo '<img src="' . htmlspecialchars($imagePath) . '" alt="Certification Document">';
            echo '<div class="document-actions">';
            echo '<a href="' . htmlspecialchars($imagePath) . '" target="_blank" class="view-full-btn">View Full Size</a>';
            echo '</div>';
            echo '</div>';
            echo '</div>'; // end detail-section
        }
        
        if (!empty($row['Comment'])) {
            echo '<div class="detail-section">';
            echo '<h4>Administrator Comments</h4>';
            echo '<div class="comments-box">' . htmlspecialchars($row['Comment']) . '</div>';
            echo '</div>'; // end detail-section
        }
        echo '</div>'; // end details-column
        
        echo '</div>'; // end certification-details-grid
        
    } else {
        echo '<div class="no-data-found">';
        echo '<i class="fas fa-exclamation-circle"></i>';
        echo '<p>Certification details not found.</p>';
        echo '</div>';
    }
    
    $stmt->close();
} else {
    echo '<div class="invalid-request">';
    echo '<i class="fas fa-times-circle"></i>';
    echo '<p>Invalid request.</p>';
    echo '</div>';
}
?>