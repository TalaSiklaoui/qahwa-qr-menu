

<?php

require_once __DIR__ . "/auth.php";
require_once __DIR__ . "/../config/database.php";
require_once __DIR__ . "/image_cleanup.php";

if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

$error = "";

// Handle delete requests
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $token = $_POST["csrf_token"] ?? "";

    if (!is_string($token) ||
        !hash_equals($_SESSION["csrf_token"], $token)) {
        http_response_code(403);
        exit("Invalid form submission.");
    }

    $id = filter_input(
        INPUT_POST,
        "category_id",
        FILTER_VALIDATE_INT
    );

    if (!$id || $id < 1) {
        $error = "Invalid category.";

    } else {

        // Retrieve the category before deleting it.
        $statement = $pdo->prepare(
            "SELECT image FROM categories WHERE id = ?"
        );

        $statement->execute([$id]);
        $category = $statement->fetch(PDO::FETCH_ASSOC);

        if (!$category) {
            $error = "Category not found.";

        } else {

            try {

                $deleteStatement = $pdo->prepare(
                    "DELETE FROM categories WHERE id = ?"
                );

                $deleteStatement->execute([$id]);

                // Clean up the old image only after deletion.
                try {
                    deleteUnusedCategoryImage(
                        $pdo,
                        $category["image"]
                    );
                } catch (Throwable $e) {
                    error_log($e->getMessage());
                }

                header("Location: categories.php");
                exit;

            } catch (PDOException $e) {

                if ($e->getCode() === "23000") {
                    $error = "This category contains products. Move or delete those products first.";
                } else {
                    error_log($e->getMessage());
                    $error = "Unable to delete the category.";
                }
            }
        }
    }
}

// Retrieve all categories
$statement = $pdo->query(
    "SELECT id, name, image, display_order
     FROM categories
     ORDER BY display_order ASC, id ASC"
);

$categories = $statement->fetchAll(PDO::FETCH_ASSOC);

?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Qahwa | Manage Categories</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
        rel="stylesheet"
    >

    <link rel="stylesheet" href="../assets/css/style.css">
</head>

<body>

<main class="container py-4 admin-dashboard">

    <!-- Page Header -->
    <div class="category-page-header">
    <div class="category-page-heading">
        <a href="dashboard.php" class="back-link" aria-label="Back">
            <i class="bi bi-arrow-left"></i>
        </a>

        <div class="category-page-title-text">
            <h1>Manage Categories</h1>
            <p>Organize Qahwa's menu categories</p>
        </div>
    </div>

    <a href="add_category.php"
       class="add-category-button-square"
       aria-label="Add category"
       title="Add category">
        <i class="bi bi-plus-lg"></i>
    </a>
</div>


<?php if ($error !== ""): ?>
    <div class="alert alert-danger" role="alert">
        <?php echo htmlspecialchars($error); ?>
    </div>
<?php endif; ?>



    <!-- Category List -->
    <div class="category-admin-list">

        <?php foreach ($categories as $category): ?>

            <div class="category-admin-row">

                <div class="category-admin-image">

                    <?php if (!empty($category["image"])): ?>

                        <img
                            src="../<?php echo htmlspecialchars($category["image"]); ?>"
                            alt=""
                        >

                    <?php else: ?>

                        <i class="bi bi-cup-hot"></i>

                    <?php endif; ?>

                </div>

                <div class="category-admin-info">

                    <h2>
                        <?php echo htmlspecialchars($category["name"]); ?>
                    </h2>

                    <p>
                        Display order:
                        <?php echo (int) $category["display_order"]; ?>
                    </p>

                </div>

                

<div class="category-admin-actions">

    <!-- Category ID -->
    <span class="category-admin-number">
        #<?php echo (int) $category["id"]; ?>
    </span>

    <!-- Edit Button -->
    <a
        href="edit_category.php?id=<?php echo (int) $category["id"]; ?>"
        class="category-edit-button"
        aria-label="Edit <?php echo htmlspecialchars($category["name"]); ?>"
    >
        <i class="bi bi-pencil-square"></i>
    </a>

    <!-- Delete Button -->
    <form
        method="post"
        action="categories.php"
        class="category-delete-form"
    >

        <input
            type="hidden"
            name="csrf_token"
            value="<?php echo htmlspecialchars($_SESSION["csrf_token"]); ?>"
        >

        <input
            type="hidden"
            name="category_id"
            value="<?php echo (int) $category["id"]; ?>"
        >

        <button
            type="submit"
            class="category-delete-button"
            aria-label="Delete <?php echo htmlspecialchars($category["name"]); ?>"
            onclick="return confirm('Delete this category? This cannot be undone.');"
        >
            <i class="bi bi-trash"></i>
        </button>

    </form>

</div>



            </div>

        <?php endforeach; ?>

    </div>

</main>

</body>
</html>
