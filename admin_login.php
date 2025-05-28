<?php
session_start();

// Check if admin is already logged in
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header("Location: admin_dashboard.php");
    exit;
}

require 'config.php'; // Include the database connection

// Initialize variables
$usernameError = $passwordError = "";
$username = $password = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Basic validation
    if (empty($username)) {
        $usernameError = "Please enter your username.";
    }

    if (empty($password)) {
        $passwordError = "Please enter your password.";
    }

    // If there are no errors, proceed to check credentials
    if (empty($usernameError) && empty($passwordError)) {
        $sql = "SELECT id, username, password FROM admins WHERE username = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows == 1) {
            // Check if the password matches
            $row = $result->fetch_assoc();
            if (password_verify($password, $row['password'])) {
                // Password is correct, create a session
                $_SESSION['admin_logged_in'] = true;
                $_SESSION['admin_username'] = $row['username'];
                // Redirect to admin dashboard
                header("Location: admin_dashboard.php");
                exit();
            } else {
                $passwordError = "Invalid password.";
            }
        } else {
            $usernameError = "No admin found with that username.";
        }
    }

    $stmt->close();
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login</title>
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
        main {
            display: flex;
            flex: 1;
            justify-content: center;
            align-items: center;
        }
        .content {
            background-color: #333;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(255, 255, 255, 0.1);
            max-width: 500px;
            text-align: center;
            width: 100%;
        }
        .content img {
            width: 200px;
            margin-bottom: 20px;
            border-radius: 10px;
            background-color: #fff;
            padding: 10px;
        }
        .content h1 {
            font-size: 1.8em;
            margin-bottom: 15px;
            color: white;
        }
        .textbox {
            width: 100%;
            padding: 10px;
            margin: 10px 0;
            border-radius: 5px;
            border: 1px solid #555;
            box-sizing: border-box;
            background-color: #222;
            color: white;
        }
        .error {
            color: red;
            font-size: 12px;
            margin-top: 5px;
        }
        .button {
            width: 100%;
            padding: 15px;
            background-color: #666;
            border: none;
            border-radius: 5px;
            color: white;
            font-size: 16px;
            cursor: pointer;
            margin-top: 10px;
            transition: background-color 0.3s ease;
        }
        .button:hover {
            background-color: #888;
        }
        footer {
            background-color: #111;
            color: #fff;
            text-align: center;
            padding: 10px;
            border-top: 2px solid #333;
            width: 100%;
            position: absolute;
            bottom: 0;
        }
    </style>
</head>
<body>
    <main>
        <div class="content">
            <img src="logo.png" alt="University Logo">
            <h1>Admin Login</h1>
            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST">
                <input type="text" placeholder="Username" id="username" name="username" class="textbox" value="<?php echo htmlspecialchars($username); ?>">
                <span class="error"><?php echo $usernameError; ?></span>
                <input type="password" placeholder="Password" id="password" name="password" class="textbox" value="<?php echo htmlspecialchars($password); ?>">
                <span class="error"><?php echo $passwordError; ?></span>
                <button type="submit" class="button">Login</button>
            </form>
        </div>
    </main>
    <footer>
        <p>© 2024 University Council Election System</p>
    </footer>
</body>
</html>
