<?php
// Backend: Database connection (Ensure to include your own credentials)
include '../config/config.php';


// Function to get recent documents from the database
function getDocuments($conn) {
    $sql = "SELECT r.documentID AS document_id, r.description, r.location, r.submissionDate AS date_reported, 
                   r.statusID, s.statusName
            FROM document r
            JOIN Status s ON r.statusID = s.statusID
            ORDER BY r.submissionDate DESC";
    
    $result = $conn->query($sql);
    $documents = [];
    
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $documents[] = $row;
        }
    }
    
    return $documents;
}
?>
