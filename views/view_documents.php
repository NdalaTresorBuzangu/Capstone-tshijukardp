<?php
// Include the configuration file to set up the database connection
include __DIR__ . '/../config/config.php';

// Function to fetch all submitted image paths from the document table
function fetchSubmittedImages() {
    global $conn;

    // Update the SQL query to fetch the documentID and imagePath from the document table
    $sql = "SELECT document.documentID, document.imagePath, user.userName 
            FROM Document AS document
            JOIN User AS user ON document.userID = user.userID
            WHERE document.imagePath IS NOT NULL AND document.imagePath != '' 
            ORDER BY document.submissionDate DESC";

    // Execute the query and fetch the results
    $result = $conn->query($sql);
    $images = [];

    // If results are found, store them in an array
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $images[] = $row;
        }
    }

    // Return the array of images
    return $images;
}

// Fetch all submitted images
$images = fetchSubmittedImages();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Submitted Images</title>
    <style>
        img {
            max-width: 80%;
            height: auto;
        }
        .image-container {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
        }
        .image-container div {
            margin: 10px;
            text-align: center;
        }
    </style>
</head>
<body>
    <h1>Submitted Images</h1>
    
    <?php if (!empty($images)): ?>
        <div class="image-container">
            <?php foreach ($images as $image): ?>
                <div class="container">
                    <h3>Document ID: <?php echo htmlspecialchars($image['documentID']); ?></h3>
                    <p>Submitted by: <?php echo htmlspecialchars($image['userName']); ?></p>
                    <!-- imagePath is stored as uploads/images/... ; use view_document for consistent display -->
                    <img src="view_document.php?documentID=<?php echo urlencode($image['documentID']); ?>" alt="Submitted Image">
                </div>
            <?php endforeach; ?>
        </div>
        <!-- Print button -->
        <button onclick="printDocument(this)">Print Document</button>
    <?php else: ?>
        <p>No images have been submitted yet.</p>
    <?php endif; ?>
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        function printDocument(button) {
            // Clone the closest .container and remove unwanted elements
            var documentContainer = $(button).closest('.container').clone();   
            documentContainer.find(".register").remove();  // If there is any register section to remove
            documentContainer.find('table').css('border-collapse', 'collapse').find('td, th').css('border', '1px solid #ddd');
            documentContainer.find(".register").remove();

            // Apply styling for the print window
            documentContainer.find('div').css({
                'width': '100%',
                'margin': '0',
                'padding': '10px',
                'display': 'flex',
                'flex-direction': 'column',
                'align-items': 'center',
                'text-align': 'center'
            });

            // Specific styles for images
            documentContainer.find('img').css({
                'max-width': '80%',
                'height': 'auto'
            });

            // Opening the print window and writing the cloned HTML to it
            var printWindow = window.open('', '_blank');
            printWindow.document.open();
            printWindow.document.write(documentContainer.html());
            printWindow.document.close();
            printWindow.print();
        }
    </script>
</body>
</html>
