<?php
session_start();
require 'config.php';

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$username = $_SESSION['username'];
$user_id = $_SESSION['user_id'];

// Fetch the nominations from the database
$sql = "SELECT id, candidate_name, position, photo FROM nominations";
$result = $conn->query($sql);

$candidates = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $candidates[$row['position']][] = $row;
    }
}

// Check if the user has already voted
$voted_sql = "SELECT * FROM votes WHERE user_id = ?";
$stmt = $conn->prepare($voted_sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$voted_result = $stmt->get_result();
$has_voted = $voted_result->num_rows > 0;

$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UCES - Welcome</title>
    <style>
        body {
            background-color: #000;
            color: #fff;
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }
        header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px;
            background-color: #111;
            border-bottom: 2px solid #333;
        }
        header h1 {
            margin: 0;
            font-size: 24px;
        }
        nav ul {
            list-style: none;
            padding: 0;
            margin: 0;
            display: flex;
        }
        nav ul li {
            margin-left: 20px;
        }
        nav ul li a {
            color: #fff;
            text-decoration: none;
        }
        nav ul li a:hover {
            text-decoration: underline;
        }
        main {
            padding: 20px;
            flex: 1;
        }
        .content {
            background-color: #111;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(255, 255, 255, 0.1);
            max-width: 800px;
            margin: auto;
            text-align: center;
        }
        h2 {
            margin-top: 0;
            font-size: 24px;
            color: #fff;
        }
        .container {
            margin-bottom: 20px;
            text-align: left;
        }
        .container label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #fff;
        }
        .candidates {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 20px;
        }
        .candidate {
            background-color: #222;
            border: 1px solid #333;
            border-radius: 5px;
            padding: 10px;
            text-align: center;
            width: 150px;
        }
        .candidate img {
            max-width: 100%;
            border-radius: 5px;
        }
        .candidate button {
            background-color: #007bff;
            border: none;
            color: white;
            padding: 10px;
            border-radius: 5px;
            cursor: pointer;
            width: 100%;
        }
        .candidate button:hover {
            background-color: #0056b3;
        }
        .button {
            padding: 15px;
            background-color: #007bff;
            border: none;
            border-radius: 5px;
            color: white;
            font-size: 16px;
            cursor: pointer;
            margin-top: 10px;
        }
        .button:hover {
            background-color: #0056b3;
        }
        footer {
            background-color: #111;
            color: #fff;
            text-align: center;
            padding: 10px;
            border-top: 2px solid #333;
        }
    </style>
    <script>
        function confirmVote() {
            return confirm('Are you sure you want to submit your vote?');
        }

        function selectCandidate(position, candidateId) {
            document.getElementById(position).value = candidateId;
            alert('You have selected candidate ID ' + candidateId + ' for ' + position);
        }
    </script>
</head>
<body>
    <header>
        <h1>University Council Election System</h1>
        <nav>
            <ul>
                <li><a href="nomination.php">Nomination</a></li>
                <li><a href="results.html">Results</a></li>
                <li><a href="logout.php">Logout</a></li>
            </ul>
        </nav>
    </header>
    <main>
        <div class="content">
            <h2>Welcome, <?= htmlspecialchars($username); ?>!</h2>
            <?php if ($has_voted): ?>
                <p>You have already voted. Thank you for your participation!</p>
            <?php else: ?>
                <p>Please select one candidate for each position:</p>
                <form action="process_voting.php" method="POST" onsubmit="return confirmVote();">
                    <?php foreach ($candidates as $position => $candidateList): ?>
                        <div class="container">
                            <label><?= ucfirst(str_replace('_', ' ', $position)); ?>:</label>
                            <div class="candidates">
                                <?php foreach ($candidateList as $candidate): ?>
                                    <div class="candidate">
                                        <img src="data:image/jpeg;base64,<?= base64_encode($candidate['photo']); ?>" alt="<?= htmlspecialchars($candidate['candidate_name']); ?>">
                                        <p><?= htmlspecialchars($candidate['candidate_name']); ?></p>
                                        <button type="button" onclick="selectCandidate('<?= $position; ?>', <?= $candidate['id']; ?>)"><?= htmlspecialchars($candidate['candidate_name']); ?></button>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <input type="hidden" id="<?= $position; ?>" name="<?= $position; ?>">
                        </div>
                    <?php endforeach; ?>
                    <button type="submit" class="button">Submit Vote</button>
                </form>
            <?php endif; ?>
        </div>
    </main>
    <footer>
        <p>© 2024 University Council Election System</p>
    </footer>
</body>
</html>
