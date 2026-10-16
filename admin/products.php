
<?php

// Only logged-in administrators can access this page
require_once __DIR__ . "/auth.php";

// Connect to MySQL
require_once __DIR__ . "/../config/database.php";

if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}


// Handle product deletion
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $token = $_POST["csrf_token"] ?? "";

    if (
        !is_string($token) ||
        !hash_equals($_SESSION["csrf_token"], $token)
    ) {
        http_response_code(403);
        exit("Invalid form submission.");
    }

    $productId = filter_input(
        INPUT_POST,
        "delete_product_id",
        FILTER_VALIDATE_INT
    );

    if (!$productId || $productId < 1) {
        http_response_code(400);
        exit("Invalid product ID.");
    }

    // Retrieve the existing image path
    $statement = $pdo->prepare(
        "SELECT image FROM products WHERE id = ?"
    );
    $statement->execute([$productId]);

    $product = $statement->fetch(PDO::FETCH_ASSOC);

    if (!$product) {
        http_response_code(404);
        exit("Product not found.");
    }

    // Delete the database record
    $deleteStatement = $pdo->prepare(
        "DELETE FROM products WHERE id = ?"
    );
    $deleteStatement->execute([$productId]);

    // Remove its image only when safely identifiable
    // and no other product references it.
    $oldImage = $product["image"];

    if (
        is_string($oldImage) &&
        preg_match(
            '~^uploads/products/[a-f0-9]{32}\.(jpg|png|webp)$~',
            $oldImage
        )
    ) {
        try {
            $check = $pdo->prepare(
                "SELECT COUNT(*) FROM products WHERE image = ?"
            );
            $check->execute([$oldImage]);

            if ((int) $check->fetchColumn() === 0) {
                $filePath = __DIR__ . "/../" . $oldImage;

                if (is_file($filePath)) {
                    unlink($filePath);
                }
            }
        } catch (Throwable $cleanupError) {
            error_log($cleanupError->getMessage());
        }
    }

    header("Location: products.php");
    exit;
}


// Retrieve products with their category names
$statement = $pdo->query(
    "SELECT
        p.id,
        p.name,
        p.description,
        p.price,
        p.image,
        p.is_available,
        c.name AS category_name
     FROM products p
     INNER JOIN categories c
        ON p.category_id = c.id
     ORDER BY c.display_order ASC, p.id ASC"
);

$products = $statement->fetchAll(PDO::FETCH_ASSOC);

?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Qahwa | Manage Products</title>

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

    <!-- Qahwa CSS -->
    <link rel="stylesheet" href="../assets/css/style.css?v=2">
</head>

<body>

<main class="container py-4 admin-dashboard">

    <!-- Header -->
    <div class="category-page-header">

        <div class="category-page-heading">

            <a href="dashboard.php"
               class="back-link"
               aria-label="Back to dashboard">
                <i class="bi bi-arrow-left"></i>
            </a>

            <div class="category-page-title-text">
                <h1 class="dashboard-title">
                    Manage Products
                </h1>

                <p class="dashboard-subtitle">
                    Manage Qahwa's menu items
                </p>
            </div>

        </div>

        
<a href="add_product.php"
   class="add-category-button-square"
   aria-label="Add product"
   title="Add product">
    <i class="bi bi-plus-lg"></i>
</a>


    </div>

    
<!-- Search Products -->
<div class="product-search-box">
    <i class="bi bi-search"></i>

    <input
        type="search"
        id="productSearch"
        placeholder="Search products..."
        aria-label="Search products"
    >
</div>

<p id="noProductsFound" class="product-no-results" hidden>
    No products found.
</p>


    <!-- Product List -->
    <div class="product-admin-list">

        <?php $lastCategory = null; ?>

        <?php foreach ($products as $product): ?>

            <!-- Display category heading when it changes -->
            <?php if ($lastCategory !== $product["category_name"]): ?>

                <h2 class="product-admin-category" data-category-heading>
                    <?php echo htmlspecialchars($product["category_name"]); ?>
                </h2>

                <?php $lastCategory = $product["category_name"]; ?>

            <?php endif; ?>

            <div
    class="product-admin-row"
    data-product-search="<?php echo htmlspecialchars(
        mb_strtolower(
            $product["name"] . " " . ($product["description"] ?? ""),
            "UTF-8"
        ),
        ENT_QUOTES,
        "UTF-8"
    ); ?>"
>

                <!-- Product Image -->
                <div class="product-admin-image">

                    <?php if (!empty($product["image"])): ?>

                        <img
                            src="../<?php echo htmlspecialchars($product["image"]); ?>"
                            alt="<?php echo htmlspecialchars($product["name"]); ?>"
                            loading="lazy"
                        >

                    <?php else: ?>

                        <i class="bi bi-cup-hot"></i>

                    <?php endif; ?>

                </div>

                <!-- Product Information -->
                <div class="product-admin-info">

<h3>
    <?php echo htmlspecialchars($product["name"]); ?>
</h3>

<?php if (!empty($product["description"])): ?>
    <p class="product-admin-description">
        <?php echo htmlspecialchars($product["description"]); ?>
    </p>
<?php endif; ?>


                    <p class="product-admin-price">
                        <?php echo number_format($product["price"], 0); ?> LBP
                    </p>

                    <?php if ($product["is_available"]): ?>

                        <span class="product-status available">
                            Available
                        </span>

                    <?php else: ?>

                        <span class="product-status unavailable">
                            Unavailable
                        </span>

                    <?php endif; ?>

                </div>

                
<!-- Edit Product -->
<a
    href="edit_product.php?id=<?php echo (int) $product["id"]; ?>"
    class="category-edit-button"
    aria-label="Edit <?php echo htmlspecialchars($product["name"]); ?>"
    title="Edit product"
>
    <i class="bi bi-pencil-square"></i>
</a>


<!-- Delete Product -->
<form
    method="post"
    action="products.php"
    class="category-delete-form"
    onsubmit="return confirm('Are you sure you want to delete this product?');"
>
    <input
        type="hidden"
        name="csrf_token"
        value="<?php echo htmlspecialchars($_SESSION["csrf_token"]); ?>"
    >

    <input
        type="hidden"
        name="delete_product_id"
        value="<?php echo (int) $product["id"]; ?>"
    >

    <button
        type="submit"
        class="category-delete-button"
        aria-label="Delete <?php echo htmlspecialchars($product["name"]); ?>"
        title="Delete product"
    >
        <i class="bi bi-trash"></i>
    </button>
</form>



            </div>

        <?php endforeach; ?>

    </div>

</main>


<script>
const searchInput = document.getElementById("productSearch");
const productList = document.querySelector(".product-admin-list");
const noResults = document.getElementById("noProductsFound");

searchInput.addEventListener("input", function () {
    const searchTerm = this.value.trim().toLocaleLowerCase();
    const elements = productList.children;

    let currentHeading = null;
    let categoryHasMatches = false;
    let totalMatches = 0;

    function updateHeading() {
        if (currentHeading) {
            currentHeading.hidden = !categoryHasMatches;
        }
    }

    for (const element of elements) {
        if (element.matches("[data-category-heading]")) {
            updateHeading();

            currentHeading = element;
            categoryHasMatches = false;
        } else if (element.classList.contains("product-admin-row")) {
            const productText = element.dataset.productSearch || "";
            const matches = productText.includes(searchTerm);

            element.hidden = !matches;

            if (matches) {
                categoryHasMatches = true;
                totalMatches++;
            }
        }
    }

    updateHeading();
    noResults.hidden = totalMatches > 0;
});
</script>


</body>
</html>
