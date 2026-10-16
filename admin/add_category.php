
<?php

// Protect the page and connect to the database
require_once __DIR__ . "/auth.php";
require_once __DIR__ . "/../config/database.php";

// Create CSRF token
if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

$error = "";
$name = "";
$displayOrder = "";

// Check if the form was submitted
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // Verify CSRF token
    $token = $_POST["csrf_token"] ?? "";

    if (!is_string($token) ||
        !hash_equals($_SESSION["csrf_token"], $token)) {
        http_response_code(403);
        exit("Invalid form submission.");
    }

    // Get form values
    $name = trim($_POST["name"] ?? "");

    $displayOrder = filter_input(
        INPUT_POST,
        "display_order",
        FILTER_VALIDATE_INT
    );

    // Validate fields
    if ($name === "" || mb_strlen($name, "UTF-8") > 100) {

        $error = "Enter a category name of 1 to 100 characters.";

    } elseif (
        $displayOrder === false ||
        $displayOrder === null ||
        $displayOrder < 0
    ) {

        $error = "Enter a valid display order.";

    } else {

        // No image is selected by default
        $imagePath = null;
        $uploadedFilePath = null;

        try {

            // Check if the administrator uploaded an image
            if (
                isset($_FILES["image"]) &&
                $_FILES["image"]["error"] !== UPLOAD_ERR_NO_FILE
            ) {

                $file = $_FILES["image"];

                if (
                    is_array($file["error"]) ||
                    $file["error"] !== UPLOAD_ERR_OK
                ) {
                    throw new RuntimeException("Image upload failed.");
                }

                // Maximum 2 MB
                if ($file["size"] > 2 * 1024 * 1024) {
                    throw new RuntimeException(
                        "Image must be smaller than 2 MB."
                    );
                }

                if (!is_uploaded_file($file["tmp_name"])) {
                    throw new RuntimeException("Invalid uploaded file.");
                }

                // Check file type
                $finfo = new finfo(FILEINFO_MIME_TYPE);
                $mime = $finfo->file($file["tmp_name"]);

                $allowedTypes = [
                    "image/jpeg" => "jpg",
                    "image/png" => "png",
                    "image/webp" => "webp"
                ];

                if (!isset($allowedTypes[$mime])) {
                    throw new RuntimeException(
                        "Only JPG, PNG and WebP images are allowed."
                    );
                }

                // Verify image content
                if (getimagesize($file["tmp_name"]) === false) {
                    throw new RuntimeException("Invalid image.");
                }

                // Generate a safe, unique filename
                $filename = bin2hex(random_bytes(16)) .
                    "." . $allowedTypes[$mime];

                $uploadDirectory =
                    __DIR__ . "/../uploads/categories/";

                if (!is_dir($uploadDirectory)) {
                    throw new RuntimeException(
                        "Category uploads folder is missing."
                    );
                }

                $destination = $uploadDirectory . $filename;

                // Move image into the uploads folder
                if (!move_uploaded_file(
                    $file["tmp_name"],
                    $destination
                )) {
                    throw new RuntimeException(
                        "Unable to save the image."
                    );
                }

                $uploadedFilePath = $destination;

                // Store the relative path in MySQL
                $imagePath = "uploads/categories/" . $filename;
            }

            // Insert the new category
            $statement = $pdo->prepare(
                "INSERT INTO categories
                 (name, image, display_order)
                 VALUES (?, ?, ?)"
            );

            $statement->execute([
                $name,
                $imagePath,
                $displayOrder
            ]);

            // Return to Manage Categories
            header("Location: categories.php");
            exit;

        } catch (RuntimeException $e) {

            $error = $e->getMessage();

        } catch (PDOException $e) {

            error_log($e->getMessage());
            $error = "Unable to create the category.";
        }

        // Delete the uploaded image if saving failed
        if (
            $uploadedFilePath !== null &&
            is_file($uploadedFilePath)
        ) {
            unlink($uploadedFilePath);
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

    <title>Qahwa | Add Category</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <link rel="stylesheet" href="../assets/css/style.css">
</head>

<body>

<main class="container py-4 admin-dashboard">

    <div class="d-flex align-items-center gap-3 mb-4">

        <a href="categories.php"
           class="admin-back"
           aria-label="Back to categories">
            &larr;
        </a>

        <div>
            <h1 class="dashboard-title">Add Category</h1>
            <p class="dashboard-subtitle">
                Create a new menu category
            </p>
        </div>

    </div>

    <?php if ($error !== ""): ?>
        <div class="alert alert-danger">
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <form method="post"
      enctype="multipart/form-data"
      class="category-edit-form">

        <input
            type="hidden"
            name="csrf_token"
            value="<?php echo htmlspecialchars($_SESSION["csrf_token"]); ?>"
        >

        <div class="mb-3">

            <label for="name" class="form-label">
                Category Name
            </label>

            <input
                type="text"
                id="name"
                name="name"
                class="form-control"
                maxlength="100"
                value="<?php echo htmlspecialchars($name); ?>"
                required
            >

        </div>

        <div class="mb-4">

            <label for="display_order" class="form-label">
                Display Order
            </label>

            <input
                type="number"
                id="display_order"
                name="display_order"
                class="form-control"
                min="0"
                value="<?php echo htmlspecialchars((string) $displayOrder); ?>"
                required
            >

        </div>
         
        
<!-- Category Image -->
<div class="mb-4">

    <label for="image" class="form-label">
        Category Image
    </label>

    <input
        type="file"
        id="image"
        name="image"
        class="form-control"
        accept="image/jpeg,image/png,image/webp"
    >

    <small class="text-muted d-block mt-2">
        JPG, PNG or WebP. Maximum 2 MB.
        Optional.
    </small>

</div>


        <button type="submit" class="admin-save-button">
            Add Category
        </button>

    </form>

</main>

</body>
</html>
