<?php
session_start();
require 'config.php'; // Include the database connection

// Initialize variables to hold form data and errors
$usernameError = $passwordError = $repeatPasswordError = "";
$username = $password = $repeatPassword = "";

function checkStudentId($studentId) {
    $url = "https://student.alquds.edu/assets/image/profile/$studentId.jpg";
    $headers = @get_headers($url, 1);
    return (strpos($headers[0], "200") !== false);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $repeatPassword = $_POST['repeat_password'];

    // Basic validation
    if (empty($username)) {
        $usernameError = "Please enter your student ID.";
    } else if (!checkStudentId($username)) {
        $usernameError = "Invalid student ID.";
    }

    if (empty($password)) {
        $passwordError = "Please enter your password.";
    }

    if ($password !== $repeatPassword) {
        $repeatPasswordError = "Passwords do not match.";
    }

    // Check if the username already exists
    if (empty($usernameError)) {
        $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $usernameError = "Student ID already exists.";
        }
        $stmt->close();
    }

    // If there are no errors, proceed to register the user
    if (empty($usernameError) && empty($passwordError) && empty($repeatPasswordError)) {
        // Hash the password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        // Prepare and bind
        $stmt = $conn->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
        $stmt->bind_param("ss", $username, $hashedPassword);

        if ($stmt->execute()) {
            $_SESSION['username'] = $username;
            $_SESSION['logged_in'] = true;
            // Redirect to a welcome page or dashboard
            header("Location: login.php");
            exit();
        } else {
            echo "Error: " . $stmt->error;
        }

        $stmt->close();
    }

    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UCES - Register</title>
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
        .header-title-container {
            flex-grow: 1;
            display: flex;
            justify-content: center;
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
        .register-link {
            display: block;
            margin-top: 10px;
            color: #4CAF50;
            text-decoration: none;
            font-size: 14px;
        }
        .register-link:hover {
            text-decoration: underline;
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
    <header>
        <div class="header-title-container">
            <h1 class="header-title">University Council Election System</h1>
        </div>
        <nav>
            <ul>
                <li><a href="index.html">Home</a></li>
                <li><a href="about.html">About</a></li>
                <li><a href="nomination.php">Nomination</a></li>
                <li><a href="results.html">Results</a></li>
                <li><a href="faqs.html">FAQs</a></li>
            </ul>
        </nav>
    </header>
    <main>
        <div class="content">
            <img src="logo.png" alt="University Logo">
            <h1>Register</h1>
            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST">
                <input type="text" placeholder="Student ID" id="username" name="username" class="textbox" value="<?php echo htmlspecialchars($username); ?>">
                <span class="error"><?php echo $usernameError; ?></span>
                <input type="password" placeholder="Password" id="password" name="password" class="textbox" value="<?php echo htmlspecialchars($password); ?>">
                <span class="error"><?php echo $passwordError; ?></span>
                <input type="password" placeholder="Repeat Password" id="repeat_password" name="repeat_password" class="textbox" value="<?php echo htmlspecialchars($repeatPassword); ?>">
                <span class="error"><?php echo $repeatPasswordError; ?></span>
                <button type="submit" class="button">Register</button>
            </form>
            <a href="login.php" class="register-link">Already have an account? Login</a>
        </div>
    </main>
    <footer>
        <p>© 2024 University Council Election System</p>
    </footer>
</body>
</html>
