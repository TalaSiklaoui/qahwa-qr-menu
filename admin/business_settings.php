
<?php

require_once __DIR__ . "/auth.php";
require_once __DIR__ . "/../config/database.php";

// CSRF protection
if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

$error = "";
$success = "";

// Load current business settings
$statement = $pdo->query(
    "SELECT shop_name, tagline, logo_path
     FROM business_settings
     WHERE id = 1"
);

$settings = $statement->fetch(PDO::FETCH_ASSOC);

if (!$settings) {
    exit("Business settings not found.");
}

$shopName = $settings["shop_name"];
$tagline = $settings["tagline"];
$logoPath = $settings["logo_path"];

// Process submitted form
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $token = $_POST["csrf_token"] ?? "";

    if (
        !is_string($token) ||
        !hash_equals($_SESSION["csrf_token"], $token)
    ) {
        http_response_code(403);
        exit("Invalid form submission.");
    }

    $shopName = trim($_POST["shop_name"] ?? "");
    $tagline = trim($_POST["tagline"] ?? "");

    // Validate shop name and tagline
    if (
        $shopName === "" ||
        mb_strlen($shopName, "UTF-8") > 100
    ) {
        $error = "Enter a shop name of 1 to 100 characters.";

    } elseif (mb_strlen($tagline, "UTF-8") > 255) {
        $error = "Tagline must be 255 characters or fewer.";

    } else {

        $newLogoPath = $logoPath;
        $uploadedFilePath = null;

        try {

            // Upload is optional
            if (
                isset($_FILES["logo"]) &&
                $_FILES["logo"]["error"] !== UPLOAD_ERR_NO_FILE
            ) {

                $file = $_FILES["logo"];

                if (
                    is_array($file["error"]) ||
                    $file["error"] !== UPLOAD_ERR_OK
                ) {
                    throw new RuntimeException(
                        "Logo upload failed."
                    );
                }

                if (
                    $file["size"] <= 0 ||
                    $file["size"] > 2 * 1024 * 1024
                ) {
                    throw new RuntimeException(
                        "Logo must be smaller than 2 MB."
                    );
                }

                if (!is_uploaded_file($file["tmp_name"])) {
                    throw new RuntimeException(
                        "Invalid uploaded file."
                    );
                }

                // Validate actual image type
                $finfo = new finfo(FILEINFO_MIME_TYPE);
                $mime = $finfo->file($file["tmp_name"]);

                $allowedTypes = [
                    "image/png" => "png",
                    "image/jpeg" => "jpg",
                    "image/webp" => "webp"
                ];

                if (!isset($allowedTypes[$mime])) {
                    throw new RuntimeException(
                        "Only PNG, JPG and WebP images are allowed."
                    );
                }

                if (getimagesize($file["tmp_name"]) === false) {
                    throw new RuntimeException(
                        "Invalid image file."
                    );
                }

                // Generate a unique filename
                $filename = bin2hex(random_bytes(16)) .
                    "." . $allowedTypes[$mime];

                $uploadDirectory =
                    __DIR__ . "/../uploads/logo/";

                if (!is_dir($uploadDirectory)) {
                    throw new RuntimeException(
                        "Logo upload folder does not exist."
                    );
                }

                $destination = $uploadDirectory . $filename;

                if (!move_uploaded_file(
                    $file["tmp_name"],
                    $destination
                )) {
                    throw new RuntimeException(
                        "Unable to save the uploaded logo."
                    );
                }

                $uploadedFilePath = $destination;
                $newLogoPath = "uploads/logo/" . $filename;
            }

            // Save all settings
            $update = $pdo->prepare(
                "UPDATE business_settings
                 SET shop_name = ?,
                     tagline = ?,
                     logo_path = ?
                 WHERE id = 1"
            );

            $update->execute([
                $shopName,
                $tagline,
                $newLogoPath
            ]);

            // Record success before optional cleanup
            $oldLogoPath = $logoPath;
            $logoPath = $newLogoPath;
            $uploadedFilePath = null;

            // Delete an old generated logo only
            if (
                $oldLogoPath !== $newLogoPath &&
                preg_match(
                    '~^uploads/logo/[a-f0-9]{32}\.(png|jpg|webp)$~',
                    $oldLogoPath
                )
            ) {
                try {
                    $check = $pdo->prepare(
                        "SELECT COUNT(*)
                         FROM business_settings
                         WHERE logo_path = ?"
                    );

                    $check->execute([$oldLogoPath]);

                    if ((int) $check->fetchColumn() === 0) {
                        $oldFile = __DIR__ . "/../" . $oldLogoPath;

                        if (is_file($oldFile)) {
                            unlink($oldFile);
                        }
                    }
                } catch (Throwable $cleanupError) {
                    error_log($cleanupError->getMessage());
                }
            }

            $success = "Business settings updated successfully.";

        } catch (RuntimeException $e) {
            $error = $e->getMessage();

        } catch (PDOException $e) {
            error_log($e->getMessage());
            $error = "Unable to save business settings.";
        }

        // Remove new upload if database save failed
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

    <title>Business Settings | Qahwa</title>

    <!-- Bootstrap -->
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <!-- Bootstrap Icons -->
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
        rel="stylesheet"
    >

    <!-- Custom CSS -->
    <link rel="stylesheet"
          href="../assets/css/style.css?v=7">

</head>

<body>

<main class="container py-4 admin-dashboard">

    <!-- Header -->
    <div class="d-flex align-items-start gap-3 mb-4">

        <a href="dashboard.php"
           class="admin-back"
           aria-label="Back to dashboard">
            <i class="bi bi-arrow-left"></i>
        </a>

        <div>
            <h1 class="dashboard-title">
                Business Settings
            </h1>

            <p class="dashboard-subtitle">
                Manage your coffee shop's branding
            </p>
        </div>

    </div>

    <!-- Error Message -->
    <?php if ($error !== ""): ?>

        <div class="alert alert-danger">
            <?php echo htmlspecialchars($error); ?>
        </div>

    <?php endif; ?>

    <!-- Success Message -->
    <?php if ($success !== ""): ?>

        <div class="alert alert-success">
            <?php echo htmlspecialchars($success); ?>
        </div>

    <?php endif; ?>

    <!-- Settings Form -->
    <form
        method="post"
        enctype="multipart/form-data"
        class="category-edit-form"
    >

        <input
            type="hidden"
            name="csrf_token"
            value="<?php echo htmlspecialchars(
                $_SESSION["csrf_token"],
                ENT_QUOTES,
                "UTF-8"
            ); ?>"
        >

        <!-- Coffee Shop Name -->
        <div class="mb-4">

            <label for="shop_name" class="form-label">
                Coffee Shop Name *
            </label>

            <input
                type="text"
                id="shop_name"
                name="shop_name"
                class="form-control"
                maxlength="100"
                value="<?php echo htmlspecialchars(
                    $shopName,
                    ENT_QUOTES,
                    "UTF-8"
                ); ?>"
                required
            >

        </div>

        <!-- Tagline -->
        <div class="mb-4">

            <label for="tagline" class="form-label">
                Tagline
            </label>

            <input
                type="text"
                id="tagline"
                name="tagline"
                class="form-control"
                maxlength="255"
                value="<?php echo htmlspecialchars(
                    $tagline,
                    ENT_QUOTES,
                    "UTF-8"
                ); ?>"
            >

            <small class="text-muted d-block mt-2">
                The sentence displayed below
                the coffee shop name.
            </small>

        </div>

        <!-- Current Logo -->
        <div class="mb-4">

            <label class="form-label">
                Current Logo
            </label>

            <div class="mb-3">
                <img
                    src="../<?php echo htmlspecialchars(
                        $logoPath,
                        ENT_QUOTES,
                        "UTF-8"
                    ); ?>"
                    alt="Current coffee shop logo"
                    class="admin-image-preview"
                >
            </div>

            <!-- Upload New Logo -->
            <label for="logo" class="form-label">
                Upload New Logo (optional)
            </label>

            <input
                type="file"
                id="logo"
                name="logo"
                class="form-control"
                accept="image/png,image/jpeg,image/webp"
            >

            <small class="text-muted d-block mt-2">
                PNG, JPG or WebP. Maximum 2 MB.
                Leave empty to keep the current logo.
            </small>

        </div>

        <!-- Save Button -->
        <button
            type="submit"
            class="admin-save-button"
        >
            Save Changes
        </button>

    </form>


</main>

</body>
</html>
