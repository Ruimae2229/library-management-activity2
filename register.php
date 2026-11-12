
<?php
require_once "config.php";

$error = "";

if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $full_name = trim($_POST["full_name"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";
    $confirm_password = $_POST["confirm_password"] ?? "";

    if (
        !isset($_POST["csrf_token"]) ||
        !hash_equals($_SESSION["csrf_token"], $_POST["csrf_token"])
    ) {

        $error = "Invalid request. Please refresh the page.";

    } elseif ($full_name === "" || strlen($full_name) > 150) {

        $error = "Please enter a valid full name.";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $error = "Please enter a valid email address.";

    } elseif (strlen($email) > 255) {

        $error = "Email address is too long.";

    } elseif ($password !== $confirm_password) {

        $error = "Passwords do not match.";

    } elseif (strlen($password) < 8) {

        $error = "Password must be at least 8 characters.";

    } else {

        try {

            $check = $conn->prepare(
                "SELECT id FROM users WHERE email = ?"
            );

            $check->execute([$email]);

            if ($check->fetch()) {

                $error = "Email is already registered.";

            } else {

                $hashed_password = password_hash(
                    $password,
                    PASSWORD_DEFAULT
                );

                // New registrations are ALWAYS members
                $stmt = $conn->prepare(
                    "INSERT INTO users
                    (full_name, email, password, role, status)
                    VALUES (?, ?, ?, 'member', 'active')"
                );

                $stmt->execute([
                    $full_name,
                    $email,
                    $hashed_password
                ]);

                header("Location: login.php?registered=1");
                exit();
            }

        } catch (PDOException $e) {

            $error = "Registration failed. Please try again.";

        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Library Registration</title>

    <link rel="stylesheet" href="assets/style.css">

</head>

<body>

<div class="auth-container">

    <div class="visual-panel register-image">

        <div class="image-overlay">

            <h1>Join Our Online Library</h1>

            <p>
                Start your journey into a world of knowledge and imagination.
            </p>

        </div>

    </div>


    <div class="form-panel">

        <div class="form-content">

            <h1>Create Account</h1>

            <p class="subtitle">
                Register to become a library member
            </p>


            <?php if (!empty($error)): ?>

                <div class="error-message">
                    <?= htmlspecialchars($error, ENT_QUOTES, "UTF-8") ?>
                </div>

            <?php endif; ?>


            <form method="POST" action="register.php">

                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= htmlspecialchars($_SESSION["csrf_token"], ENT_QUOTES, "UTF-8") ?>"
                >


                <label>Full Name</label>

                <input
                    type="text"
                    name="full_name"
                    placeholder="Enter your full name"
                    maxlength="150"
                    required
                    value="<?= htmlspecialchars($full_name ?? "", ENT_QUOTES, "UTF-8") ?>"
                >


                <label>Email Address</label>

                <input
                    type="email"
                    name="email"
                    placeholder="Enter your email"
                    maxlength="255"
                    required
                    value="<?= htmlspecialchars($email ?? "", ENT_QUOTES, "UTF-8") ?>"
                >


                <label>Password</label>

                <input
                    type="password"
                    name="password"
                    placeholder="At least 8 characters"
                    minlength="8"
                    required
                >


                <label>Confirm Password</label>

                <input
                    type="password"
                    name="confirm_password"
                    placeholder="Confirm your password"
                    minlength="8"
                    required
                >


                <button type="submit">
                    Create Account
                </button>

            </form>


            <p class="switch-link">

                Already have an account?

                <a href="login.php">
                    Login here
                </a>

            </p>

        </div>

    </div>

</div>

</body>
</html>
