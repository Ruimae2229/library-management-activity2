<?php
require_once "config.php";

if (!isset($_SESSION["user"])) {
    header("Location: login.php");
    exit();
}

$isAdmin = ($_SESSION["user"]["role"] === "admin");
$userName = htmlspecialchars($_SESSION["user"]["name"]);
$userEmail = htmlspecialchars($_SESSION["user"]["email"]);
$userRole = htmlspecialchars($_SESSION["user"]["role"]);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Library Dashboard</title>
    <link rel="stylesheet" href="assets/style.css">
</head>

<body class="dashboard-page">

<div class="dashboard-container">

    <header class="dashboard-header">
        <div>
            <h1>Online Library</h1>
            <p>Library Management System</p>
        </div>

        <a href="logout.php" class="logout-button">Logout</a>
    </header>

    <main class="dashboard-main">

        <section class="welcome-section">
            <p class="section-label">DASHBOARD</p>
            <h2>Welcome, <?= $userName ?>!</h2>
            <p>Manage your library activities in one place.</p>
        </section>

        <section class="account-section">
            <h3>Account Information</h3>

            <div class="account-grid">

                <div class="account-card">
                    <span class="account-label">Full Name</span>
                    <strong><?= $userName ?></strong>
                </div>

                <div class="account-card">
                    <span class="account-label">Email Address</span>
                    <strong><?= $userEmail ?></strong>
                </div>

                <div class="account-card">
                    <span class="account-label">Account Role</span>
                    <strong class="role-text"><?= ucfirst($userRole) ?></strong>
                </div>

            </div>
        </section>

        <section class="management-section">
            <h3>Management</h3>
            <p class="management-description">
                Choose a section to continue.
            </p>

            <div class="management-grid">

                <a href="books.php" class="management-card">
                    <h4>Books Management</h4>
                    <p>View and manage library book records.</p>
                    <span class="card-link">Open Books</span>
                </a>

                <?php if ($isAdmin): ?>
                    <a href="users.php" class="management-card">
                        <h4>User Management</h4>
                        <p>Manage registered library accounts.</p>
                        <span class="card-link">Open Users</span>
                    </a>
                <?php endif; ?>

            </div>
        </section>

    </main>

    <footer class="dashboard-footer">
        <p>Online Library Management System</p>
    </footer>

</div>

</body>
</html>