<?php
function selectBestNurses($conn, $service_id, $patient_gender, $patient_age_type, $patient_lat, $patient_lon) {
    // Step 1: Fetch nurses who offer the service
    $sql = "SELECT n.NurseID, u.FullName, na.Gender, u.DateOfBirth, na.Specialization, na.Language, a.Latitude, a.Longitude, a.City, a.Street, n.image_path , ns.Price, AVG(r.Rating) AS AvgRating
            FROM nurse n
            JOIN user u ON n.UserID = u.UserID
            JOIN nurseapplication na ON n.NAID = na.NAID
            JOIN address a ON u.AddressID = a.AddressID
            JOIN nurseservices ns ON n.NurseID = ns.NurseID
            LEFT JOIN rating r ON n.NurseID = r.NurseID
            WHERE ns.ServiceID = '$service_id' AND n.Availability = 1";
    if ($patient_gender !== 'No Preference') {
        $sql .= " AND na.Gender = '$patient_gender'";
    }
    $sql .= " GROUP BY n.NurseID";
    $result = $conn->query($sql);
    $nurses = [];
    while ($row = $result->fetch_assoc()) {
        $nurses[] = $row;
    }

    // Step 2: Calculate scores
    $scored_nurses = [];
    foreach ($nurses as $nurse) {
        $score = 0;

        // Gender match
        if ($patient_gender === 'No Preference' || $nurse['Gender'] === $patient_gender) {
            $score += 10;
        }

        // AgeType match
        $dob = new DateTime($nurse['DateOfBirth']);
        $now = new DateTime();
        $age = $now->diff($dob)->y;
        $nurse_age_type = ($age <= 40) ? 'Adult' : 'Mature';
        if ($patient_age_type === 'No Preference' || $nurse_age_type === $patient_age_type) {
            $score += 10;
        }

        // Distance calculation using Haversine formula
        $nurse_lat = $nurse['Latitude'];
        $nurse_lon = $nurse['Longitude'];
        $distance = 6371 * acos(
            cos(deg2rad($patient_lat)) * cos(deg2rad($nurse_lat)) * 
            cos(deg2rad($nurse_lon) - deg2rad($patient_lon)) + 
            sin(deg2rad($patient_lat)) * sin(deg2rad($nurse_lat))
        );
        if ($distance <= 5) {
            $score += 10;
        } elseif ($distance <= 10) {
            $score += 5;
        }

        // Rating
        $score += $nurse['AvgRating'] ? $nurse['AvgRating'] : 0;

        $scored_nurses[] = [
            'NurseID' => $nurse['NurseID'],
            'FullName' => $nurse['FullName'],
            'Gender' => $nurse['Gender'],
            'AgeType' => $nurse_age_type,
            'Distance' => $distance,
            'Price' => $nurse['Price'],
            'AvgRating' => $nurse['AvgRating'],
            'image_path' => $nurse['image_path'],
            'Specialization' => $nurse['Specialization'],
            'Language' => $nurse['Language'],
            'City' => $nurse['City'],
            'Street' => $nurse['Street'],
            'Score' => $score
        ];
    }

    // Step 3: Sort by score
    usort($scored_nurses, function($a, $b) {
        return $b['Score'] <=> $a['Score'];
    });

    // Step 4: Return all matching nurses
    return $scored_nurses;
}
?>