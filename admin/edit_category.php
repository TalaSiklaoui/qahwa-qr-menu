
<?php

require_once __DIR__ . "/auth.php";
require_once __DIR__ . "/../config/database.php";
require_once __DIR__ . "/image_cleanup.php";

// Read the category ID from the URL
$id = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);

if (!$id || $id < 1) {
    http_response_code(400);
    exit("Invalid category ID.");
}

// Retrieve the category from MySQL
$statement = $pdo->prepare(
    "SELECT id, name, image, display_order
     FROM categories
     WHERE id = ?"
);

$statement->execute([$id]);

$category = $statement->fetch(PDO::FETCH_ASSOC);

if (!$category) {
    http_response_code(404);
    exit("Category not found.");
}

$oldImage = $category["image"];

// Create a security token for the editing form
if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

$error = "";

// Check if the form was submitted
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // Verify the CSRF token
    $submittedToken = $_POST["csrf_token"] ?? "";

    if (!hash_equals($_SESSION["csrf_token"], $submittedToken)) {
        http_response_code(403);
        exit("Invalid form submission.");
    }

    // Get the submitted values
    $name = trim($_POST["name"] ?? "");

    $displayOrder = filter_input(
        INPUT_POST,
        "display_order",
        FILTER_VALIDATE_INT
    );

    // Validate the category name
    if ($name === "") {
        $error = "Category name cannot be empty.";

    } elseif (mb_strlen($name, "UTF-8") > 100) {
        $error = "Category name is too long.";

    } elseif ($displayOrder === false ||
              $displayOrder === null ||
              $displayOrder < 0) {

        $error = "Please enter a valid display order.";

    } else {

        
// Keep the current image unless a new one is uploaded
$imagePath = null;
$uploadedFilePath = null;

try {

    // Check whether the admin selected a new image
    if (
        isset($_FILES["image"]) &&
        $_FILES["image"]["error"] !== UPLOAD_ERR_NO_FILE
    ) {

        $file = $_FILES["image"];

        if (is_array($file["error"]) ||
            $file["error"] !== UPLOAD_ERR_OK) {
            throw new RuntimeException("Image upload failed.");
        }

        // Maximum image size: 2 MB
        if ($file["size"] > 2 * 1024 * 1024) {
            throw new RuntimeException(
                "Image must be smaller than 2 MB."
            );
        }

        // Check the actual file's MIME type
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

        // Create a unique filename
        $extension = $allowedTypes[$mime];

        $filename = bin2hex(random_bytes(16)) .
                    "." . $extension;

        // Image destination
        $uploadDirectory =
            __DIR__ . "/../uploads/categories/";

        $destination = $uploadDirectory . $filename;

        // Save the uploaded file
        if (!move_uploaded_file(
            $file["tmp_name"],
            $destination
        )) {
            throw new RuntimeException(
                "Unable to save the image."
            );
        }

        $uploadedFilePath = $destination;
        $imagePath = "uploads/categories/" . $filename;
    }

    // Update the database
    if ($imagePath !== null) {

        $updateStatement = $pdo->prepare(
            "UPDATE categories
             SET name = ?,
                 display_order = ?,
                 image = ?
             WHERE id = ?"
        );

        $updateStatement->execute([
            $name,
            $displayOrder,
            $imagePath,
            $id
        ]);

    } else {

        $updateStatement = $pdo->prepare(
            "UPDATE categories
             SET name = ?,
                 display_order = ?
             WHERE id = ?"
        );

        $updateStatement->execute([
            $name,
            $displayOrder,
            $id
        ]);
    }

    
// Remove the previous image only when a new one was saved.
if ($imagePath !== null && $oldImage !== $imagePath) {
    try {
        deleteUnusedCategoryImage($pdo, $oldImage);
    } catch (Throwable $e) {
        // The database update succeeded, so do not undo it.
        // Log cleanup problems for later maintenance.
        error_log("Category image cleanup: " . $e->getMessage());
    }
}


    header("Location: categories.php");
    exit;

} catch (RuntimeException | PDOException $e) {

    // Clean up a new file if the database update fails
    if ($uploadedFilePath !== null &&
        is_file($uploadedFilePath)) {
        unlink($uploadedFilePath);
    }

    error_log($e->getMessage());
    $error = "Unable to save the category or image.";
}

    }

    // Keep submitted values in the form if validation fails
    $category["name"] = $name;

    $category["display_order"] =
        $_POST["display_order"] ?? "";
}


?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Qahwa | Edit Category</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <link rel="stylesheet" href="../assets/css/style.css">
</head>

<body>

<main class="container py-4 admin-dashboard">

    <!-- Page Header -->
    <div class="d-flex align-items-center gap-3 mb-4">

        <a href="categories.php"
           class="admin-back"
           aria-label="Back to categories">
            &#8592;
        </a>

        <div>
            <h1 class="dashboard-title">Edit Category</h1>
            <p class="dashboard-subtitle">
                Update category information
            </p>
        </div>

    </div>

    <?php if ($error !== ""): ?>
        <div class="alert alert-danger">
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <!-- Edit Form -->
    
<form method="post"
      enctype="multipart/form-data"
      class="category-edit-form">


        <input type="hidden"
               name="csrf_token"
               value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">

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
                value="<?php echo htmlspecialchars($category['name']); ?>"
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
                value="<?php echo (int) $category['display_order']; ?>"
                required
            >

        </div>

        
<!-- Category Image -->
<div class="mb-4">

    <label for="image" class="form-label">
        Category Image
    </label>

    <?php if (!empty($category["image"])): ?>

        <div class="mb-3">
            <img
                src="../<?php echo htmlspecialchars($category["image"]); ?>"
                alt="Current category image"
                class="admin-image-preview"
            >
        </div>

    <?php endif; ?>

    <input
        type="file"
        id="image"
        name="image"
        class="form-control"
        accept="image/jpeg,image/png,image/webp"
    >

    <small class="text-muted d-block mt-2">
        JPG, PNG or WebP. Maximum 2 MB.
        Leave empty to keep the current image.
    </small>

</div>


        

        <button type="submit" class="admin-save-button">
            Save Changes
        </button>

    </form>

</main>

</body>
</html>

