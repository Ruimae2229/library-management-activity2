<?php
require_once "config.php";

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $email = trim($_POST["email"]);
    $password = $_POST["password"];

    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);

    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user["password"])) {

        if ($user["status"] === "active") {

            $_SESSION["user"] = [
                "id" => $user["id"],
                "name" => $user["full_name"],
                "email" => $user["email"],
                "role" => $user["role"]
            ];

            header("Location: dashboard.php");
            exit();

        } else {
            $error = "Your account is disabled.";
        }

    } else {
        $error = "Invalid email or password.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Library Login</title>
    <link rel="stylesheet" href="assets/style.css">
</head>

<body>

<div class="auth-container">

    <div class="visual-panel login-image">
        <div class="image-overlay">
            <h1>Welcome to Online Library</h1>
            <p>Discover knowledge. Explore stories. Build your future.</p>
        </div>
    </div>

    <div class="form-panel">

        <div class="form-content">

            <h1>Library Login</h1>
            <p class="subtitle">Sign in to your account</p>

            <?php if (!empty($error)): ?>
                <div class="error-message">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <form method="POST">

                <label>Email Address</label>
                <input
                    type="email"
                    name="email"
                    placeholder="Enter your email"
                    required
                >

                <label>Password</label>
                <input
                    type="password"
                    name="password"
                    placeholder="Enter your password"
                    required
                >

                <button type="submit">Login</button>

            </form>

            <p class="switch-link">
                Don't have an account?
                <a href="register.php">Register here</a>
            </p>

        </div>

    </div>

</div>

</body>
</html>