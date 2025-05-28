<?php
require 'config.php'; // Include the database connection

// Fetch the nominations from the database
$sql = "SELECT candidate_name, photo FROM nominations";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UCES - Nominees</title>
    <style>
        body {
            background-color: #000;
            color: #fff;
            font-family: Arial, sans-serif;
            text-align: center;
            margin: 0;
            overflow: hidden;
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
            width: 100%;
        }
        nav {
            display: flex;
        }
        nav ul {
            list-style-type: none;
            margin: 0;
            padding: 0;
            display: flex;
            flex-direction: row-reverse; /* Aligns the nav items to the right */
        }
        nav ul li {
            margin: 0 10px;
        }
        nav ul li a {
            color: #fff;
            text-decoration: none;
        }
        nav ul li a:hover {
            text-decoration: underline;
        }
        .header-title-container {
            display: flex;
            justify-content: center;
            flex-grow: 1;
        }
        .header-title {
            font-size: 24px;
            font-weight: bold;
            text-align: center;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.5);
        }
        main {
            display: flex;
            flex: 1;
            justify-content: center;
            align-items: center;
            padding: 20px;
            width: 100%;
            box-sizing: border-box;
        }
        .content {
            background-color: #333;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(255, 255, 255, 0.1);
            max-width: 1000px;
            text-align: center;
            width: 80%; /* Adjusted to 80% */
            overflow-y: auto; /* Add vertical scroll */
            max-height: 70vh; /* Limit the height to fit within the view */
        }
        .container {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
        }
        .candidate {
            margin: 20px;
            text-align: center;
        }
        .candidate img {
            max-width: 300px;
            max-height: 300px;
            border-radius: 10px;
        }
        .candidate-name {
            margin-top: 10px;
            font-size: 18px;
            color: #fff;
        }
        footer {
            background-color: #111;
            color: #fff;
            text-align: center;
            padding: 10px;
            border-top: 2px solid #333;
            width: 100%;
            position: relative;
        }

        /* Custom scrollbar styles */
        .content::-webkit-scrollbar {
            width: 12px;
        }
        .content::-webkit-scrollbar-track {
            background: #444;
            border-radius: 10px;
        }
        .content::-webkit-scrollbar-thumb {
            background-color: #888;
            border-radius: 10px;
            border: 3px solid #444;
        }
        .content::-webkit-scrollbar-thumb:hover {
            background-color: #aaa;
        }
    </style>
</head>
<body>
    <header>
        <div class="header-title-container">
            <h1 class="header-title">University Council Election System</h1>
        </div>
        <nav>
            <ul>
                <li><a href="index.html">Home</a></li>
                <li><a href="about.html">About</a></li>
                <li><a href="login.php">Register/Login</a></li>
                <li><a href="results.html">Results</a></li>
                <li><a href="faqs.html">FAQs</a></li>
            </ul>
        </nav>
    </header>
    <main>
        <div class="content">
            <h2>Nominees</h2>
            <div class="container">
                <?php
                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        echo "<div class='candidate'>";
                        echo "<img src='data:image/jpeg;base64," . base64_encode($row['photo']) . "' alt='Candidate Photo'>";
                        echo "<div class='candidate-name'>" . htmlspecialchars($row['candidate_name']) . "</div>";
                        echo "</div>";
                    }
                } else {
                    echo "<p>No nominations found.</p>";
                }
                $conn->close();
                ?>
            </div>
        </div>
    </main>
    <footer>
        <p>© 2024 University Council Election System</p>
    </footer>
</body>
</html>
