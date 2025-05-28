<?php
require 'config.php'; // Include the database connection

// Initialize variables to hold form data and errors
$candidateNameError = $positionError = $manifestoError = $photoError = "";
$candidateName = $position = $manifesto = $photo = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $candidateName = $_POST['candidate_name'];
    $position = $_POST['position'];
    $manifesto = $_POST['manifesto'];

    // Basic validation
    if (empty($candidateName)) {
        $candidateNameError = "Please enter the candidate's name.";
    }

    if (empty($position)) {
        $positionError = "Please select a position.";
    }

    if (empty($manifesto)) {
        $manifestoError = "Please enter the manifesto.";
    }

    // Check if a photo was uploaded
    if ($_FILES['photo']['error'] == UPLOAD_ERR_OK) {
        $photo = file_get_contents($_FILES['photo']['tmp_name']);
    } else {
        $photoError = "Please upload a photo.";
    }

    // If there are no errors, proceed to insert the nomination
    if (empty($candidateNameError) && empty($positionError) && empty($manifestoError) && empty($photoError)) {
        // Prepare and bind
        $stmt = $conn->prepare("INSERT INTO nominations (candidate_name, position, manifesto, photo) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("sssb", $candidateName, $position, $manifesto, $photo);

        if ($stmt->execute()) {
            // Redirect to a success page or display a success message
            header("Location: nomination_success.php");
            exit();
        } else {
            echo "Error: " . $stmt->error;
        }

        $stmt->close();
    }

    $conn->close();
}
?>
