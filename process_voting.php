<?php
session_start();
require 'config.php';

// Check if the user is logged in
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit;
}

$username = $_SESSION['username'];

// Fetch the user ID
$user_id_sql = "SELECT id FROM users WHERE username = ?";
$stmt = $conn->prepare($user_id_sql);
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$user_id = $user['id'];
$stmt->close();

// Check if the user has already voted
$voted_sql = "SELECT * FROM votes WHERE user_id = ?";
$stmt = $conn->prepare($voted_sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$voted_result = $stmt->get_result();
if ($voted_result->num_rows > 0) {
    // User has already voted
    header("Location: welcome.php");
    exit;
}
$stmt->close();

// Process the votes
foreach ($_POST as $position => $candidate_id) {
    $vote_sql = "INSERT INTO votes (user_id, candidate_id, position) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($vote_sql);
    $stmt->bind_param("iis", $user_id, $candidate_id, $position);
    $stmt->execute();
    $stmt->close();

    // Update the election results
    $update_results_sql = "UPDATE election_results SET votes = votes + 1 WHERE candidate_name = (SELECT candidate_name FROM nominations WHERE id = ?) AND position = ?";
    $stmt = $conn->prepare($update_results_sql);
    $stmt->bind_param("is", $candidate_id, $position);
    $stmt->execute();
    $stmt->close();
}

$conn->close();
header("Location: welcome.php");
exit;
?>
