
<?php
require_once "config.php";

/* =====================================
   ADMIN ACCESS
===================================== */

if (!isset($_SESSION["user"])) {
    header("Location: login.php");
    exit;
}

if ($_SESSION["user"]["role"] !== "admin") {
    die("Access denied. Admin only.");
}

/* =====================================
   HELPER FUNCTION
===================================== */

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, "UTF-8");
}

/* =====================================
   CSRF TOKEN
===================================== */

if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

$message = "";
$messageType = "";
$editUser = null;

/* =====================================
   FORM PROCESSING
===================================== */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    if (
        !isset($_POST["csrf_token"]) ||
        !hash_equals($_SESSION["csrf_token"], $_POST["csrf_token"])
    ) {
        die("Invalid request. Please refresh the page.");
    }

    $action = $_POST["action"] ?? "";

    /* =====================================
       ADD USER
    ===================================== */

    if ($action === "add") {

        $fullName = trim($_POST["full_name"] ?? "");
        $email = trim($_POST["email"] ?? "");
        $password = $_POST["password"] ?? "";
        $role = $_POST["role"] ?? "member";
        $status = $_POST["status"] ?? "active";

        if (
            $fullName === "" ||
            $email === "" ||
            $password === ""
        ) {
            $message = "Please complete all required fields.";
            $messageType = "error";

        } elseif (strlen($fullName) > 150) {
            $message = "Full name must not exceed 150 characters.";
            $messageType = "error";

        } elseif (
            !filter_var($email, FILTER_VALIDATE_EMAIL) ||
            strlen($email) > 255
        ) {
            $message = "Please enter a valid email address.";
            $messageType = "error";

        } elseif (strlen($password) < 8) {
            $message = "Password must contain at least 8 characters.";
            $messageType = "error";

        } elseif (!in_array($role, ["admin", "member"], true)) {
            $message = "Invalid user role.";
            $messageType = "error";

        } elseif (!in_array($status, ["active", "disabled"], true)) {
            $message = "Invalid account status.";
            $messageType = "error";

        } else {

            $checkEmail = $conn->prepare(
                "SELECT id FROM users WHERE email = ?"
            );
            $checkEmail->execute([$email]);

            if ($checkEmail->fetch()) {
                $message = "Email address is already registered.";
                $messageType = "error";

            } else {

                $hashedPassword = password_hash(
                    $password,
                    PASSWORD_DEFAULT
                );

                $insert = $conn->prepare(
                    "INSERT INTO users
                    (full_name, email, password, role, status)
                    VALUES (?, ?, ?, ?, ?)"
                );

                $insert->execute([
                    $fullName,
                    $email,
                    $hashedPassword,
                    $role,
                    $status
                ]);

                $message = "User successfully added.";
                $messageType = "success";
            }
        }
    }

    /* =====================================
       UPDATE USER
    ===================================== */

    elseif ($action === "update") {

        $id = filter_var(
            $_POST["id"] ?? null,
            FILTER_VALIDATE_INT
        );

        $fullName = trim($_POST["full_name"] ?? "");
        $email = trim($_POST["email"] ?? "");
        $password = $_POST["password"] ?? "";
        $role = $_POST["role"] ?? "";
        $status = $_POST["status"] ?? "";

        if (
            !$id ||
            $fullName === "" ||
            $email === ""
        ) {
            $message = "Please complete all required fields.";
            $messageType = "error";

        } elseif (strlen($fullName) > 150) {
            $message = "Full name must not exceed 150 characters.";
            $messageType = "error";

        } elseif (
            !filter_var($email, FILTER_VALIDATE_EMAIL) ||
            strlen($email) > 255
        ) {
            $message = "Please enter a valid email address.";
            $messageType = "error";

        } elseif (!in_array($role, ["admin", "member"], true)) {
            $message = "Invalid user role.";
            $messageType = "error";

        } elseif (!in_array($status, ["active", "disabled"], true)) {
            $message = "Invalid account status.";
            $messageType = "error";

        } elseif ($password !== "" && strlen($password) < 8) {
            $message = "New password must contain at least 8 characters.";
            $messageType = "error";

        } else {

            $findUser = $conn->prepare(
                "SELECT * FROM users WHERE id = ?"
            );
            $findUser->execute([$id]);
            $existingUser = $findUser->fetch(PDO::FETCH_ASSOC);

            if (!$existingUser) {
                $message = "User not found.";
                $messageType = "error";

            } else {

                $checkEmail = $conn->prepare(
                    "SELECT id FROM users
                     WHERE email = ? AND id != ?"
                );
                $checkEmail->execute([$email, $id]);

                if ($checkEmail->fetch()) {
                    $message = "Email address is already used by another user.";
                    $messageType = "error";

                } else {

                    $isCurrentUser =
                        ((int)$id === (int)$_SESSION["user"]["id"]);

                    if ($isCurrentUser) {

                        if (
                            $role !== $existingUser["role"] ||
                            $status !== $existingUser["status"]
                        ) {
                            $message = "You cannot change your own role or account status.";
                            $messageType = "error";
                        }
                    }

                    if ($message === "") {

                        if (
                            $existingUser["role"] === "admin" &&
                            $existingUser["status"] === "active" &&
                            ($role !== "admin" || $status !== "active")
                        ) {

                            $adminCount = $conn->query(
                                "SELECT COUNT(*) FROM users
                                 WHERE role = 'admin'
                                 AND status = 'active'"
                            )->fetchColumn();

                            if ((int)$adminCount <= 1) {
                                $message = "You cannot disable or remove the last active admin.";
                                $messageType = "error";
                            }
                        }
                    }

                    if ($message === "") {

                        if ($password !== "") {

                            $hashedPassword = password_hash(
                                $password,
                                PASSWORD_DEFAULT
                            );

                            $update = $conn->prepare(
                                "UPDATE users
                                 SET full_name = ?,
                                     email = ?,
                                     password = ?,
                                     role = ?,
                                     status = ?
                                 WHERE id = ?"
                            );

                            $update->execute([
                                $fullName,
                                $email,
                                $hashedPassword,
                                $role,
                                $status,
                                $id
                            ]);

                        } else {

                            $update = $conn->prepare(
                                "UPDATE users
                                 SET full_name = ?,
                                     email = ?,
                                     role = ?,
                                     status = ?
                                 WHERE id = ?"
                            );

                            $update->execute([
                                $fullName,
                                $email,
                                $role,
                                $status,
                                $id
                            ]);
                        }

                        $message = "User successfully updated.";
                        $messageType = "success";

                        if ($isCurrentUser) {
                            $_SESSION["user"]["name"] = $fullName;
                            $_SESSION["user"]["email"] = $email;
                        }
                    }
                }
            }
        }
    }

    /* =====================================
       DELETE USER
    ===================================== */

    elseif ($action === "delete") {

        $id = filter_var(
            $_POST["id"] ?? null,
            FILTER_VALIDATE_INT
        );

        if (!$id) {
            $message = "Invalid user ID.";
            $messageType = "error";

        } elseif ((int)$id === (int)$_SESSION["user"]["id"]) {

            $message = "You cannot delete your own account.";
            $messageType = "error";

        } else {

            $findUser = $conn->prepare(
                "SELECT * FROM users WHERE id = ?"
            );
            $findUser->execute([$id]);
            $userToDelete = $findUser->fetch(PDO::FETCH_ASSOC);

            if (!$userToDelete) {
                $message = "User not found.";
                $messageType = "error";

            } else {

                if (
                    $userToDelete["role"] === "admin" &&
                    $userToDelete["status"] === "active"
                ) {

                    $adminCount = $conn->query(
                        "SELECT COUNT(*) FROM users
                         WHERE role = 'admin'
                         AND status = 'active'"
                    )->fetchColumn();

                    if ((int)$adminCount <= 1) {
                        $message = "You cannot delete the last active admin.";
                        $messageType = "error";
                    }
                }

                if ($message === "") {

                    $checkHistory = $conn->prepare(
                        "SELECT COUNT(*) FROM borrow_records
                         WHERE user_id = ?"
                    );
                    $checkHistory->execute([$id]);

                    $historyCount = $checkHistory->fetchColumn();

                    if ((int)$historyCount > 0) {

                        $message = "User has borrowing history. Disable the account instead.";
                        $messageType = "error";

                    } else {

                        $delete = $conn->prepare(
                            "DELETE FROM users WHERE id = ?"
                        );
                        $delete->execute([$id]);

                        $message = "User successfully deleted.";
                        $messageType = "success";
                    }
                }
            }
        }
    }
}

/* =====================================
   GET USER FOR EDITING
===================================== */

if (isset($_GET["edit"])) {

    $editId = filter_var(
        $_GET["edit"],
        FILTER_VALIDATE_INT
    );

    if ($editId) {

        $editQuery = $conn->prepare(
            "SELECT id, full_name, email, role, status
             FROM users
             WHERE id = ?"
        );

        $editQuery->execute([$editId]);
        $editUser = $editQuery->fetch(PDO::FETCH_ASSOC);
    }
}

/* =====================================
   GET ALL USERS
===================================== */

$usersQuery = $conn->query(
    "SELECT id, full_name, email, role, status, created_at
     FROM users
     ORDER BY id DESC"
);

$users = $usersQuery->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>User Management | Online Library</title>

    <link rel="stylesheet" href="assets/style.css">
    <link rel="stylesheet" href="assets/users.css">
</head>

<body class="users-page">

<div class="users-container">

    <!-- PAGE HEADER -->

    <header class="users-header">

        <div>
            <h1>User Management</h1>
            <p>Manage registered library users and their roles.</p>
        </div>

        <a href="dashboard.php" class="users-back-button">
            Back to Dashboard
        </a>

    </header>


    <!-- ALERT MESSAGE -->

    <?php if ($message !== ""): ?>

        <div class="users-alert <?php
            echo $messageType === "success"
                ? "users-success"
                : "users-error";
        ?>">
            <?php echo e($message); ?>
        </div>

    <?php endif; ?>


    <!-- ADD / EDIT USER CARD -->

    <section class="users-panel">

        <div class="users-panel-heading">

            <h2>
                <?php echo $editUser ? "Edit User" : "Add New User"; ?>
            </h2>

            <p>
                <?php echo $editUser
                    ? "Update the selected library account."
                    : "Create a new library account."; ?>
            </p>

        </div>


        <form method="POST" action="users.php">

            <input
                type="hidden"
                name="csrf_token"
                value="<?php echo e($_SESSION["csrf_token"]); ?>"
            >

            <input
                type="hidden"
                name="action"
                value="<?php echo $editUser ? "update" : "add"; ?>"
            >

            <?php if ($editUser): ?>

                <input
                    type="hidden"
                    name="id"
                    value="<?php echo e($editUser["id"]); ?>"
                >

            <?php endif; ?>


            <div class="users-form-grid">

                <!-- FULL NAME -->

                <div class="users-field">

                    <label for="full_name">Full Name</label>

                    <input
                        type="text"
                        id="full_name"
                        name="full_name"
                        maxlength="150"
                        placeholder="Enter full name"
                        value="<?php
                            echo $editUser
                                ? e($editUser["full_name"])
                                : "";
                        ?>"
                        required
                    >

                </div>


                <!-- EMAIL -->

                <div class="users-field">

                    <label for="email">Email Address</label>

                    <input
                        type="email"
                        id="email"
                        name="email"
                        maxlength="255"
                        placeholder="Enter email address"
                        value="<?php
                            echo $editUser
                                ? e($editUser["email"])
                                : "";
                        ?>"
                        required
                    >

                </div>


                <!-- PASSWORD -->

                <div class="users-field">

                    <label for="password">
                        Password
                        <?php if ($editUser): ?>
                            <small>(Leave blank to keep current password)</small>
                        <?php endif; ?>
                    </label>

                    <input
                        type="password"
                        id="password"
                        name="password"
                        placeholder="<?php
                            echo $editUser
                                ? "Enter new password if changing"
                                : "Enter password";
                        ?>"
                        <?php echo $editUser ? "" : "required"; ?>
                        minlength="8"
                    >

                </div>


                <!-- ROLE -->

                <div class="users-field">

                    <label for="role">User Role</label>

                    <select id="role" name="role" required>

                        <option
                            value="member"
                            <?php
                                echo (
                                    $editUser &&
                                    $editUser["role"] === "member"
                                ) ? "selected" : "";
                            ?>
                        >
                            Member
                        </option>

                        <option
                            value="admin"
                            <?php
                                echo (
                                    $editUser &&
                                    $editUser["role"] === "admin"
                                ) ? "selected" : "";
                            ?>
                        >
                            Admin
                        </option>

                    </select>

                </div>


                <!-- STATUS -->

                <div class="users-field">

                    <label for="status">Account Status</label>

                    <select id="status" name="status" required>

                        <option
                            value="active"
                            <?php
                                echo (
                                    !$editUser ||
                                    $editUser["status"] === "active"
                                ) ? "selected" : "";
                            ?>
                        >
                            Active
                        </option>

                        <option
                            value="disabled"
                            <?php
                                echo (
                                    $editUser &&
                                    $editUser["status"] === "disabled"
                                ) ? "selected" : "";
                            ?>
                        >
                            Disabled
                        </option>

                    </select>

                </div>

            </div>


            <!-- FORM BUTTONS -->

            <div class="users-form-actions">

                <button type="submit">
                    <?php echo $editUser ? "Update User" : "Add User"; ?>
                </button>

                <?php if ($editUser): ?>

                    <a href="users.php" class="users-edit-button">
                        Cancel
                    </a>

                <?php endif; ?>

            </div>

        </form>

    </section>


    <!-- REGISTERED USERS CARD -->

    <section class="users-panel registered-panel">

        <div class="registered-heading">

            <div class="users-panel-heading">

                <h2>Registered Users</h2>

                <p>
                    View and manage all registered library accounts.
                </p>

            </div>

            <span class="users-count">
                <?php echo count($users); ?> Users
            </span>

        </div>


        <!-- USERS TABLE -->

        <div class="users-table-wrap">

            <table class="users-table">

                <thead>

                    <tr>
                        <th>ID</th>
                        <th>Full Name</th>
                        <th>Email Address</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>

                </thead>


                <tbody>

                    <?php if (count($users) > 0): ?>

                        <?php foreach ($users as $user): ?>

                            <tr>

                                <td>
                                    <?php echo e($user["id"]); ?>
                                </td>

                                <td>
                                    <?php echo e($user["full_name"]); ?>
                                </td>

                                <td>
                                    <?php echo e($user["email"]); ?>
                                </td>

                                <td>

                                    <span class="role-badge <?php
                                        echo $user["role"] === "admin"
                                            ? "admin-role"
                                            : "member-role";
                                    ?>">
                                        <?php echo ucfirst(e($user["role"])); ?>
                                    </span>

                                </td>

                                <td>

                                    <span class="status-badge <?php
                                        echo $user["status"] === "active"
                                            ? "active-status"
                                            : "disabled-status";
                                    ?>">
                                        <?php echo ucfirst(e($user["status"])); ?>
                                    </span>

                                </td>

                                <td>

                                    <div class="users-actions">

                                        <a
                                            href="users.php?edit=<?php echo e($user["id"]); ?>"
                                            class="users-edit-button"
                                        >
                                            Edit
                                        </a>


                                        <?php if (
                                            (int)$user["id"] !==
                                            (int)$_SESSION["user"]["id"]
                                        ): ?>

                                            <form
                                                method="POST"
                                                action="users.php"
                                                class="users-delete-form"
                                                onsubmit="return confirm('Are you sure you want to delete this user?');"
                                            >

                                                <input
                                                    type="hidden"
                                                    name="csrf_token"
                                                    value="<?php echo e($_SESSION["csrf_token"]); ?>"
                                                >

                                                <input
                                                    type="hidden"
                                                    name="action"
                                                    value="delete"
                                                >

                                                <input
                                                    type="hidden"
                                                    name="id"
                                                    value="<?php echo e($user["id"]); ?>"
                                                >

                                                <button
                                                    type="submit"
                                                    class="users-delete-button"
                                                >
                                                    Delete
                                                </button>

                                            </form>

                                        <?php endif; ?>

                                    </div>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                    <?php else: ?>

                        <tr>
                            <td colspan="6" class="users-empty">
                                No registered users found.
                            </td>
                        </tr>

                    <?php endif; ?>

                </tbody>

            </table>

        </div>

    </section>


    <!-- FOOTER -->

    <footer class="users-footer">
        Online Library Management System
    </footer>

</div>

</body>
</html>
