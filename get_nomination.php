<?php
require 'config.php'; // Include the database connection

$id = $_GET['id'];

$sql = "SELECT id, candidate_name, position, manifesto FROM nominations WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 1) {
    echo json_encode($result->fetch_assoc());
} else {
    echo json_encode([]);
}

$stmt->close();
$conn->close();
?>
