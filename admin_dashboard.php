<?php
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: admin_login.php");
    exit;
}

require 'config.php'; // Include the database connection

// Set a larger timeout and memory limit
ini_set('max_execution_time', 3000); // 3000 seconds = 50 minutes
ini_set('memory_limit', '1024M'); // 1GB

// Function to check if a student ID exists in the university system
function checkStudentId($studentId) {
    $url = "https://student.alquds.edu/assets/image/profile/$studentId.jpg";
    $headers = @get_headers($url, 1);
    return (strpos($headers[0], "200") !== false);
}

// Handle form submission to add or update candidates and users
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['candidate_name'])) {
        $candidateName = $_POST['candidate_name'];
        $position = $_POST['position'];
        $manifesto = $_POST['manifesto'];

        // Check if a photo was uploaded
        if ($_FILES['photo']['error'] == UPLOAD_ERR_OK) {
            $photo = file_get_contents($_FILES['photo']['tmp_name']);
        } else {
            $photo = null;
        }

        if (isset($_POST['candidate_id']) && !empty($_POST['candidate_id'])) {
            // Update existing candidate
            $id = $_POST['candidate_id'];
            if ($photo) {
                $sql = "UPDATE nominations SET candidate_name = ?, position = ?, manifesto = ?, photo = ? WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ssssi", $candidateName, $position, $manifesto, $photo, $id);
            } else {
                $sql = "UPDATE nominations SET candidate_name = ?, position = ?, manifesto = ? WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("sssi", $candidateName, $position, $manifesto, $id);
            }
        } else {
            // Insert new candidate
            $sql = "INSERT INTO nominations (candidate_name, position, manifesto, photo) VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssss", $candidateName, $position, $manifesto, $photo);
        }

        $stmt->execute();
        $stmt->close();
        header("Location: admin_dashboard.php");
        exit;
    } elseif (isset($_POST['username'])) {
        $username = $_POST['username'];
        $password = $_POST['password'];

        if (!checkStudentId($username)) {
            echo "<script>alert('Invalid student ID.');</script>";
        } else {
            // Check if the username already exists
            $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $stmt->store_result();
            if ($stmt->num_rows > 0 && empty($_POST['user_id'])) {
                echo "<script>alert('Username already exists.');</script>";
                $stmt->close();
            } else {
                if (isset($_POST['user_id']) && !empty($_POST['user_id'])) {
                    // Update existing user
                    $id = $_POST['user_id'];
                    if (!empty($password)) {
                        // Hash the password
                        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                        $sql = "UPDATE users SET username = ?, password = ? WHERE id = ?";
                        $stmt = $conn->prepare($sql);
                        $stmt->bind_param("ssi", $username, $hashedPassword, $id);
                    } else {
                        $sql = "UPDATE users SET username = ? WHERE id = ?";
                        $stmt = $conn->prepare($sql);
                        $stmt->bind_param("si", $username, $id);
                    }
                } else {
                    // Insert new user
                    // Hash the password
                    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                    $sql = "INSERT INTO users (username, password) VALUES (?, ?)";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("ss", $username, $hashedPassword);
                }

                $stmt->execute();
                $stmt->close();
                header("Location: admin_dashboard.php");
                exit;
            }
        }
    } elseif (isset($_POST['delete_user_id'])) {
        // Delete user and their votes
        $delete_user_id = $_POST['delete_user_id'];

        // Delete the votes associated with the user
        $sql = "DELETE FROM votes WHERE user_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $delete_user_id);
        $stmt->execute();
        $stmt->close();

        // Delete the user
        $sql = "DELETE FROM users WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $delete_user_id);
        $stmt->execute();
        $stmt->close();

        header("Location: admin_dashboard.php");
        exit;
    } elseif (isset($_POST['logout'])) {
        // Logout and redirect to admin login
        session_destroy();
        header("Location: admin_login.php");
        exit;
    }
}

// Fetch the nominations and users from the database
$nominations_sql = "SELECT id, candidate_name, position, manifesto FROM nominations";
$nominations_result = $conn->query($nominations_sql);

$users_result = null;
if (isset($_POST['search_username'])) {
    $search_username = $_POST['search_username'];
    $users_sql = "SELECT id, username FROM users WHERE username LIKE '%" . $conn->real_escape_string($search_username) . "%'";
    $users_result = $conn->query($users_sql);
}

// Fetch the election results
$results_sql = "SELECT n.candidate_name, n.position, COUNT(v.id) AS votes
                FROM votes v
                JOIN nominations n ON v.candidate_id = n.id
                GROUP BY n.candidate_name, n.position
                ORDER BY n.position, votes DESC";
$results_result = $conn->query($results_sql);

// Prepare data for the chart
$chart_data = [];
if ($results_result->num_rows > 0) {
    while ($row = $results_result->fetch_assoc()) {
        $chart_data[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <style>
        body {
            background-color: #000;
            color: #fff;
            font-family: Arial, sans-serif;
            text-align: center;
            margin: 0;
        }
        header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px;
            background-color: #111;
            border-bottom: 2px solid #333;
        }
        nav ul {
            list-style-type: none;
            margin: 0;
            padding: 0;
            display: flex;
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
        main {
            padding: 20px;
            width: 100%;
            max-width: 1200px;
            margin: auto;
            box-sizing: border-box;
        }
        .card {
            background-color: #1e1e1e;
            color: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            margin-bottom: 20px;
            padding: 20px;
        }
        .card h3 {
            margin-top: 0;
        }
        .card form, .card table {
            width: 100%;
            box-sizing: border-box;
        }
        .card input, .card select, .card textarea {
            width: calc(100% - 22px); /* Adjusted width to fit within padding */
            padding: 10px;
            margin-bottom: 10px;
            border: 1px solid #555;
            border-radius: 4px;
            background-color: #333;
            color: #fff;
            box-sizing: border-box; /* Ensures padding and border are included in the total width */
        }
        .card button {
            width: 100%;
            padding: 15px;
            background-color: #555;
            border: none;
            border-radius: 5px;
            color: white;
            font-size: 16px;
            cursor: pointer;
        }
        .card button:hover {
            background-color: #777;
        }
        .card .action-buttons button {
            width: auto;
            padding: 5px 10px;
            margin: 0 5px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        th, td {
            padding: 10px;
            border: 1px solid #555;
            text-align: left;
        }
        th {
            background-color: #555;
            color: white;
        }
        tr:nth-child(even) {
            background-color: #333;
        }
        .chart-container {
            width: 100%;
            max-width: 800px;
            margin: 20px auto;
            background-color: #1e1e1e;
            padding: 10px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }
    </style>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <header>
        <h1>Welcome To Admin Dashboard</h1>
        <form method="POST" style="display: inline;">
            <button type="submit" name="logout" style="background-color: #f44336; border: none; padding: 10px 20px; border-radius: 5px; color: white; font-size: 16px; cursor: pointer;">Logout</button>
        </form>
    </header>
    <main>
        <div class="chart-container">
            <canvas id="resultsChart"></canvas>
        </div>

        <div class="card">
            <h3>Add or Update Candidate</h3>
            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="candidate_id" id="candidate_id">
                <label for="candidate_name">Candidate Name:</label>
                <input type="text" id="candidate_name" name="candidate_name" required>
                
                <label for="position">Position:</label>
                <select id="position" name="position">
                    <option value="president">President</option>
                </select>
                
                <label for="manifesto">Manifesto:</label>
                <textarea id="manifesto" name="manifesto" required></textarea>
                
                <label for="photo">Photo:</label>
                <input type="file" id="photo" name="photo">
                
                <button type="submit" class="button">Submit</button>
            </form>
        </div>

        <div class="card">
            <h3>Current Nominations</h3>
            <table>
                <thead>
                    <tr>
                        <th>Candidate Name</th>
                        <th>Position</th>
                        <th>Manifesto</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if ($nominations_result->num_rows > 0) {
                        while ($row = $nominations_result->fetch_assoc()) {
                            echo "<tr>";
                            echo "<td>" . htmlspecialchars($row['candidate_name']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['position']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['manifesto']) . "</td>";
                            echo "<td class='action-buttons'>
                                <button onclick='editCandidate(" . $row['id'] . ")'>Edit</button>
                                <form action='delete_nomination.php' method='POST' style='display:inline;' onsubmit='return confirm(\"Are you sure you want to delete this nomination?\");'>
                                    <input type='hidden' name='id' value='" . $row['id'] . "'>
                                    <button type='submit'>Delete</button>
                                </form>
                              </td>";
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='4'>No nominations found.</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>

        <div class="card">
            <h3>Add or Update User</h3>
            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST">
                <input type="hidden" name="user_id" id="user_id">
                <label for="username">Username:</label>
                <input type="text" id="username" name="username" required>
                
                <label for="password">Password (leave blank to keep current password):</label>
                <input type="password" id="password" name="password">
                
                <button type="submit" class="button">Submit</button>
            </form>
        </div>

        <div class="card">
            <h3>Search User</h3>
            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST">
                <label for="search_username">Search by Username:</label>
                <input type="text" id="search_username" name="search_username">
                <button type="submit" class="button">Search</button>
            </form>
        </div>

        <?php if ($users_result && $users_result->num_rows > 0): ?>
        <div class="card">
            <h3>Search Results</h3>
            <table>
                <thead>
                    <tr>
                        <th>Username</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $users_result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['username']); ?></td>
                        <td class='action-buttons'>
                            <button onclick='editUser(<?php echo $row["id"]; ?>)'>Edit</button>
                            <form action='admin_dashboard.php' method='POST' style='display:inline;' onsubmit='return confirm("Are you sure you want to delete this user?");'>
                                <input type='hidden' name='delete_user_id' value='<?php echo $row["id"]; ?>'>
                                <button type='submit'>Delete</button>
                            </form>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        <?php elseif ($users_result): ?>
        <div class="card">
            <p>No users found with the username "<?php echo htmlspecialchars($search_username); ?>".</p>
        </div>
        <?php endif; ?>
    </main>
    <script>
        function editCandidate(id) {
            fetch('get_nomination.php?id=' + id)
                .then(response => response.json())
                .then(data => {
                    document.getElementById('candidate_id').value = data.id;
                    document.getElementById('candidate_name').value = data.candidate_name;
                    document.getElementById('position').value = data.position;
                    document.getElementById('manifesto').value = data.manifesto;
                });
        }

        function editUser(id) {
            fetch('get_user.php?id=' + id)
                .then(response => response.json())
                .then(data => {
                    document.getElementById('user_id').value = data.id;
                    document.getElementById('username').value = data.username;
                });
        }

        document.addEventListener('DOMContentLoaded', function() {
            const ctx = document.getElementById('resultsChart').getContext('2d');
            const chartData = <?php echo json_encode($chart_data); ?>;
            const labels = chartData.map(item => item.candidate_name);
            const votes = chartData.map(item => item.votes);

            const resultsChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Votes',
                        data: votes,
                        backgroundColor: 'rgba(75, 192, 192, 0.2)',
                        borderColor: 'rgba(75, 192, 192, 1)',
                        borderWidth: 1
                    }]
                },
                options: {
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
        });
    </script>
</body>
</html>
