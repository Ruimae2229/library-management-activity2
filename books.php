
<?php
require_once "config.php";

// LOGIN REQUIRED
if (!isset($_SESSION["user"])) {
    header("Location: login.php");
    exit();
}

// CHECK ROLE
$isAdmin = ($_SESSION["user"]["role"] === "admin");

$error = "";
$message = "";

// CSRF TOKEN
if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, "UTF-8");
}

// HANDLE FORM SUBMISSIONS
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // MEMBERS CANNOT MODIFY BOOKS
    if (!$isAdmin) {
        http_response_code(403);
        exit("Access denied. Admin only.");
    }

    // VERIFY CSRF TOKEN
    if (
        !isset($_POST["csrf_token"]) ||
        !hash_equals($_SESSION["csrf_token"], $_POST["csrf_token"])
    ) {
        $error = "Invalid request. Please try again.";
    } else {

        $action = $_POST["action"] ?? "";

        // ADD BOOK
        if ($action === "add") {

            $title = trim($_POST["title"] ?? "");
            $author = trim($_POST["author"] ?? "");
            $isbn = trim($_POST["isbn"] ?? "");
            $category = trim($_POST["category"] ?? "");

            $quantity = filter_var(
                $_POST["quantity"] ?? null,
                FILTER_VALIDATE_INT
            );

            if (
                $title === "" ||
                $author === "" ||
                $quantity === false ||
                $quantity < 1
            ) {
                $error = "Please enter valid book information.";

            } else {

                try {
                    $stmt = $conn->prepare(
                        "INSERT INTO books
                        (title, author, isbn, category, quantity, available)
                        VALUES (?, ?, ?, ?, ?, ?)"
                    );

                    $stmt->execute([
                        $title,
                        $author,
                        $isbn !== "" ? $isbn : null,
                        $category !== "" ? $category : null,
                        $quantity,
                        $quantity
                    ]);

                    header("Location: books.php?success=added");
                    exit();

                } catch (PDOException $e) {
                    $error = "Could not add book. Check if the ISBN already exists.";
                }
            }
        }

        // UPDATE BOOK
        elseif ($action === "update") {

            $id = filter_var(
                $_POST["id"] ?? null,
                FILTER_VALIDATE_INT
            );

            $title = trim($_POST["title"] ?? "");
            $author = trim($_POST["author"] ?? "");
            $isbn = trim($_POST["isbn"] ?? "");
            $category = trim($_POST["category"] ?? "");

            $quantity = filter_var(
                $_POST["quantity"] ?? null,
                FILTER_VALIDATE_INT
            );

            if (
                !$id ||
                $title === "" ||
                $author === "" ||
                $quantity === false ||
                $quantity < 1
            ) {
                $error = "Please enter valid book information.";

            } else {

                try {
                    // Count currently borrowed copies
                    $stmt = $conn->prepare(
                        "SELECT COUNT(*)
                         FROM borrow_records
                         WHERE book_id = ?
                         AND status = 'borrowed'"
                    );

                    $stmt->execute([$id]);
                    $borrowed = (int)$stmt->fetchColumn();

                    if ($quantity < $borrowed) {

                        $error = "Quantity cannot be less than the number of currently borrowed copies.";

                    } else {

                        $available = $quantity - $borrowed;

                        $stmt = $conn->prepare(
                            "UPDATE books
                             SET title = ?,
                                 author = ?,
                                 isbn = ?,
                                 category = ?,
                                 quantity = ?,
                                 available = ?
                             WHERE id = ?"
                        );

                        $stmt->execute([
                            $title,
                            $author,
                            $isbn !== "" ? $isbn : null,
                            $category !== "" ? $category : null,
                            $quantity,
                            $available,
                            $id
                        ]);

                        header("Location: books.php?success=updated");
                        exit();
                    }

                } catch (PDOException $e) {
                    $error = "Could not update book. Check if the ISBN already exists.";
                }
            }
        }

        // DELETE BOOK
        elseif ($action === "delete") {

            $id = filter_var(
                $_POST["id"] ?? null,
                FILTER_VALIDATE_INT
            );

            if (!$id) {

                $error = "Invalid book ID.";

            } else {

                $stmt = $conn->prepare(
                    "SELECT COUNT(*)
                     FROM borrow_records
                     WHERE book_id = ?"
                );

                $stmt->execute([$id]);
                $history = (int)$stmt->fetchColumn();

                if ($history > 0) {

                    $error = "This book has borrowing history. It cannot be deleted.";

                } else {

                    $stmt = $conn->prepare(
                        "DELETE FROM books WHERE id = ?"
                    );

                    $stmt->execute([$id]);

                    header("Location: books.php?success=deleted");
                    exit();
                }
            }
        }
    }
}

// SUCCESS MESSAGES
if (isset($_GET["success"])) {

    if ($_GET["success"] === "added") {
        $message = "Book added successfully.";
    }

    elseif ($_GET["success"] === "updated") {
        $message = "Book updated successfully.";
    }

    elseif ($_GET["success"] === "deleted") {
        $message = "Book deleted successfully.";
    }
}

// GET BOOK FOR EDITING
$editBook = null;

if ($isAdmin && isset($_GET["edit"])) {

    $editId = filter_var(
        $_GET["edit"],
        FILTER_VALIDATE_INT
    );

    if ($editId) {

        $stmt = $conn->prepare(
            "SELECT * FROM books WHERE id = ?"
        );

        $stmt->execute([$editId]);
        $editBook = $stmt->fetch(PDO::FETCH_ASSOC);
    }
}

// GET ALL BOOKS
$stmt = $conn->query(
    "SELECT * FROM books ORDER BY id DESC"
);

$books = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Books Management</title>

    <link rel="stylesheet" href="assets/style.css">

    <style>

        body {
            background: #f4f1eb;
            font-family: Arial, sans-serif;
            color: #333;
        }

        .container {
            width: 92%;
            max-width: 1100px;
            margin: 40px auto;
        }

        .panel {
            background: white;
            padding: 25px;
            margin-bottom: 25px;
            border-radius: 12px;
            box-shadow: 0 5px 18px rgba(0,0,0,0.07);
        }

        h1, h2 {
            color: #315c43;
        }

        input {
            width: 100%;
            padding: 11px;
            margin: 7px 0 15px;
            border: 1px solid #ccc;
            border-radius: 6px;
            box-sizing: border-box;
        }

        label {
            font-weight: bold;
        }

        button, .button {
            display: inline-block;
            padding: 10px 16px;
            background: #315c43;
            color: white;
            border: none;
            border-radius: 6px;
            text-decoration: none;
            cursor: pointer;
        }

        button:hover, .button:hover {
            background: #203d2c;
        }

        .delete-button {
            background: #a83232;
        }

        .delete-button:hover {
            background: #7d2020;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
            background: white;
        }

        th, td {
            padding: 12px;
            border-bottom: 1px solid #ddd;
            text-align: left;
        }

        th {
            background: #315c43;
            color: white;
        }

        .success {
            background: #e2f3e5;
            color: #245b31;
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 15px;
        }

        .error {
            background: #f8e1e1;
            color: #8a2525;
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 15px;
        }

        .actions {
            display: flex;
            gap: 7px;
            align-items: center;
        }

        .actions form {
            margin: 0;
        }

        .back-link {
            display: inline-block;
            margin-bottom: 20px;
            color: #315c43;
            text-decoration: none;
            font-weight: bold;
        }

        .role-info {
            color: #555;
            margin-bottom: 15px;
        }

        @media (max-width: 700px) {

            .panel {
                overflow-x: auto;
            }

            table {
                min-width: 700px;
            }
        }

    </style>

</head>

<body>

<div class="container">

    <a class="back-link" href="dashboard.php">
        Back to Dashboard
    </a>

    <h1>Books Management</h1>

    <?php if ($isAdmin): ?>

        <p>Manage the library book records.</p>

    <?php else: ?>

        <p class="role-info">
            Welcome, Member! You can view the available library books.
        </p>

    <?php endif; ?>


    <?php if ($message): ?>

        <div class="success">
            <?= e($message) ?>
        </div>

    <?php endif; ?>


    <?php if ($error): ?>

        <div class="error">
            <?= e($error) ?>
        </div>

    <?php endif; ?>


    <?php if ($isAdmin): ?>

        <div class="panel">

            <h2>
                <?= $editBook ? "Edit Book" : "Add New Book" ?>
            </h2>

            <form method="POST" action="books.php">

                <input type="hidden"
                       name="csrf_token"
                       value="<?= e($_SESSION["csrf_token"]) ?>">

                <input type="hidden"
                       name="action"
                       value="<?= $editBook ? "update" : "add" ?>">

                <?php if ($editBook): ?>

                    <input type="hidden"
                           name="id"
                           value="<?= e($editBook["id"]) ?>">

                <?php endif; ?>


                <label>Book Title</label>

                <input type="text"
                       name="title"
                       required
                       maxlength="200"
                       value="<?= e($editBook["title"] ?? "") ?>">


                <label>Author</label>

                <input type="text"
                       name="author"
                       required
                       maxlength="150"
                       value="<?= e($editBook["author"] ?? "") ?>">


                <label>ISBN</label>

                <input type="text"
                       name="isbn"
                       maxlength="50"
                       value="<?= e($editBook["isbn"] ?? "") ?>">


                <label>Category</label>

                <input type="text"
                       name="category"
                       maxlength="100"
                       value="<?= e($editBook["category"] ?? "") ?>">


                <label>Total Quantity</label>

                <input type="number"
                       name="quantity"
                       min="1"
                       required
                       value="<?= e($editBook["quantity"] ?? 1) ?>">


                <button type="submit">
                    <?= $editBook ? "Save Changes" : "Add Book" ?>
                </button>


                <?php if ($editBook): ?>

                    <a class="button" href="books.php">
                        Cancel
                    </a>

                <?php endif; ?>

            </form>

        </div>

    <?php endif; ?>


    <div class="panel">

        <h2>Book List</h2>

        <table>

            <thead>

                <tr>
                    <th>ID</th>
                    <th>Title</th>
                    <th>Author</th>
                    <th>ISBN</th>
                    <th>Category</th>
                    <th>Quantity</th>
                    <th>Available</th>

                    <?php if ($isAdmin): ?>
                        <th>Actions</th>
                    <?php endif; ?>

                </tr>

            </thead>


            <tbody>

                <?php if (count($books) > 0): ?>

                    <?php foreach ($books as $book): ?>

                        <tr>

                            <td><?= e($book["id"]) ?></td>

                            <td><?= e($book["title"]) ?></td>

                            <td><?= e($book["author"]) ?></td>

                            <td><?= e($book["isbn"] ?? "") ?></td>

                            <td><?= e($book["category"] ?? "") ?></td>

                            <td><?= e($book["quantity"]) ?></td>

                            <td><?= e($book["available"]) ?></td>


                            <?php if ($isAdmin): ?>

                                <td>

                                    <div class="actions">

                                        <a class="button"
                                           href="books.php?edit=<?= e($book["id"]) ?>">
                                            Edit
                                        </a>


                                        <form method="POST"
                                              action="books.php"
                                              onsubmit="return confirm('Are you sure you want to delete this book?');">

                                            <input type="hidden"
                                                   name="csrf_token"
                                                   value="<?= e($_SESSION["csrf_token"]) ?>">

                                            <input type="hidden"
                                                   name="action"
                                                   value="delete">

                                            <input type="hidden"
                                                   name="id"
                                                   value="<?= e($book["id"]) ?>">

                                            <button type="submit"
                                                    class="delete-button">
                                                Delete
                                            </button>

                                        </form>

                                    </div>

                                </td>

                            <?php endif; ?>

                        </tr>

                    <?php endforeach; ?>

                <?php else: ?>

                    <tr>
                        <td colspan="<?= $isAdmin ? 8 : 7 ?>">
                            No books found.
                        </td>
                    </tr>

                <?php endif; ?>

            </tbody>

        </table>

    </div>

</div>

</body>
</html>
