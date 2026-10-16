
<?php

require_once __DIR__ . "/auth.php";
require_once __DIR__ . "/../config/database.php";

// CSRF protection
if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

// Retrieve categories for the dropdown
$categoryStatement = $pdo->query(
    "SELECT id, name
     FROM categories
     ORDER BY display_order ASC, id ASC"
);

$categories = $categoryStatement->fetchAll(PDO::FETCH_ASSOC);
$validCategoryIds = array_map(
    "intval",
    array_column($categories, "id")
);

$error = "";
$name = "";
$description = "";
$price = "";
$categoryId = "";
$isAvailable = 1;

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // Verify CSRF token
    $token = $_POST["csrf_token"] ?? "";

    if (!is_string($token) ||
        !hash_equals($_SESSION["csrf_token"], $token)) {
        http_response_code(403);
        exit("Invalid form submission.");
    }

    // Read form values
    $name = trim($_POST["name"] ?? "");
    $description = trim($_POST["description"] ?? "");
    $price = trim($_POST["price"] ?? "");
    $categoryId = filter_input(
        INPUT_POST,
        "category_id",
        FILTER_VALIDATE_INT
    );

    $isAvailable = isset($_POST["is_available"]) ? 1 : 0;

    // Validate fields
    if ($name === "" || mb_strlen($name, "UTF-8") > 150) {
        $error = "Enter a product name of 1 to 150 characters.";

    } elseif (
        $categoryId === false ||
        $categoryId === null ||
        !in_array($categoryId, $validCategoryIds, true)
    ) {
        $error = "Please select a valid category.";

    } elseif (
        !preg_match('/^\d{1,12}$/D', $price)
    ) {
        $error = "Enter a valid whole-number price in LBP.";

    } else {

        $imagePath = null;
        $uploadedFilePath = null;

        try {

            // Optional image upload
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

                // Validate MIME type
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

                if (getimagesize($file["tmp_name"]) === false) {
                    throw new RuntimeException("Invalid image file.");
                }

                // Generate a unique filename
                $filename = bin2hex(random_bytes(16)) .
                    "." . $allowedTypes[$mime];

                $uploadDirectory =
                    __DIR__ . "/../uploads/products/";

                if (!is_dir($uploadDirectory)) {
                    throw new RuntimeException(
                        "Product uploads folder is missing."
                    );
                }

                $destination = $uploadDirectory . $filename;

                if (!move_uploaded_file(
                    $file["tmp_name"],
                    $destination
                )) {
                    throw new RuntimeException(
                        "Unable to save the image."
                    );
                }

                $uploadedFilePath = $destination;
                $imagePath = "uploads/products/" . $filename;
            }

            // Insert product into database
            $statement = $pdo->prepare(
                "INSERT INTO products
                (category_id, name, description,
                 price, image, is_available)
                 VALUES (?, ?, ?, ?, ?, ?)"
            );

            $statement->execute([
                $categoryId,
                $name,
                $description === "" ? null : $description,
                $price,
                $imagePath,
                $isAvailable
            ]);

            header("Location: products.php");
            exit;

        } catch (RuntimeException $e) {
            $error = $e->getMessage();

        } catch (PDOException $e) {
            error_log($e->getMessage());
            $error = "Unable to create the product.";
        }

        // Clean up if database insertion failed
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

    <title>Qahwa | Add Product</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
        rel="stylesheet"
    >

    <link rel="stylesheet" href="../assets/css/style.css?v=2">
</head>

<body>

<main class="container py-4 admin-dashboard">

    <div class="d-flex align-items-start gap-3 mb-4">

        <a href="products.php" class="admin-back">
            <i class="bi bi-arrow-left"></i>
        </a>

        <div>
            <h1 class="dashboard-title">Add Product</h1>
            <p class="dashboard-subtitle">
                Create a new menu item
            </p>
        </div>

    </div>

    <?php if ($error !== ""): ?>

        <div class="alert alert-danger">
            <?php echo htmlspecialchars($error); ?>
        </div>

    <?php endif; ?>

    <form
        method="post"
        enctype="multipart/form-data"
        class="category-edit-form"
    >

        <input
            type="hidden"
            name="csrf_token"
            value="<?php echo htmlspecialchars($_SESSION["csrf_token"]); ?>"
        >

        <!-- Product Name -->
        <div class="mb-4">
            <label for="name" class="form-label">
                Product Name *
            </label>

            <input
                type="text"
                id="name"
                name="name"
                class="form-control"
                maxlength="150"
                value="<?php echo htmlspecialchars($name); ?>"
                required
            >
        </div>

        <!-- Category -->
        <div class="mb-4">

            <label for="category_id" class="form-label">
                Category *
            </label>

            <select
                id="category_id"
                name="category_id"
                class="form-control"
                required
            >

                <option value="">Select category</option>

                <?php foreach ($categories as $category): ?>

                    <option
                        value="<?php echo (int) $category["id"]; ?>"
                        <?php echo $categoryId == $category["id"] ? "selected" : ""; ?>
                    >
                        <?php echo htmlspecialchars($category["name"]); ?>
                    </option>

                <?php endforeach; ?>

            </select>
        </div>

        <!-- Description -->
        <div class="mb-4">

            <label for="description" class="form-label">
                Description (optional)
            </label>

            <textarea
                id="description"
                name="description"
                class="form-control"
                rows="3"
                placeholder="Describe the product..."
            ><?php echo htmlspecialchars($description); ?></textarea>

        </div>

        <!-- Price -->
        <div class="mb-4">

            <label for="price" class="form-label">
                Price (LBP) *
            </label>

            <input
                type="number"
                id="price"
                name="price"
                class="form-control"
                min="0"
                max="999999999999"
                step="1"
                value="<?php echo htmlspecialchars($price); ?>"
                required
            >

        </div>

        <!-- Product Image -->
        <div class="mb-4">

            <label for="image" class="form-label">
                Product Image (optional)
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
            </small>

        </div>

        <!-- Availability -->
        <div class="mb-4">

            <div class="form-check form-switch">

                <input
                    class="form-check-input"
                    type="checkbox"
                    role="switch"
                    id="is_available"
                    name="is_available"
                    value="1"
                    <?php echo $isAvailable ? "checked" : ""; ?>
                >

                <label
                    class="form-check-label"
                    for="is_available"
                >
                    Available on customer menu
                </label>

            </div>

        </div>

        <button
            type="submit"
            class="admin-save-button"
        >
            Add Product
        </button>

    </form>

</main>

</body>
</html>
