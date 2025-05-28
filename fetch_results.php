<?php
require 'config.php'; // Include the database connection

$sql = "SELECT 
            n.candidate_name, 
            n.position, 
            n.photo, 
            COUNT(v.id) as votes 
        FROM 
            votes v 
        JOIN 
            nominations n 
        ON 
            v.candidate_id = n.id 
        GROUP BY 
            n.candidate_name, n.position 
        ORDER BY 
            n.position";
$result = $conn->query($sql);

$results = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $row['photo'] = base64_encode($row['photo']); // Encode photo for display
        $results[] = $row;
    }
}

header('Content-Type: application/json');
echo json_encode($results);

$conn->close();
?>
