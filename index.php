
<?php

// Connect to the database
require_once __DIR__ . "/config/database.php";


// Retrieve coffee shop branding
$settingsStatement = $pdo->query(
    "SELECT shop_name, tagline, logo_path
     FROM business_settings
     WHERE id = 1"
);

$business = $settingsStatement->fetch(PDO::FETCH_ASSOC);

if (!$business) {
    exit("Business settings not found.");
}


// Get categories in their display order
$categoryStatement = $pdo->query(
    "SELECT * FROM categories ORDER BY display_order ASC, id ASC"
);

$categories = $categoryStatement->fetchAll(PDO::FETCH_ASSOC);

// Get all products
$productStatement = $pdo->query(
    "SELECT *
     FROM products
     WHERE is_available = 1
     ORDER BY id ASC"
);


$products = $productStatement->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Qahwa | Coffee Menu</title>

    <!-- Bootstrap CSS -->
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
    <link rel="stylesheet" href="assets/css/style.css?v=4">

</head>

<body>

    
<!-- Qahwa Header -->
<header class="qahwa-header">

    <div class="container">

        <div class="qahwa-brand">

            <!-- Coffee Beans Logo -->
            
<img
    src="<?php echo htmlspecialchars(
        $business["logo_path"],
        ENT_QUOTES,
        "UTF-8"
    ); ?>"
    alt="Coffee shop logo"
    class="qahwa-logo"
>


            <!-- Coffee Shop Name and Tagline -->
            <div class="qahwa-brand-text">

                
<h1>
    <?php echo htmlspecialchars($business["shop_name"]); ?>
</h1>

<p>
    <?php echo htmlspecialchars($business["tagline"]); ?>
</p>

            </div>

        </div>

    </div>

</header>



    <!-- Main Menu -->
    <main class="container py-4">

        <h2 class="menu-title mb-4">Our Menu</h2>


        <!-- Horizontal Category Navigation -->
        <div class="category-slider-wrapper mb-4">

            <!-- Left Arrow -->
            <button
                class="category-arrow category-arrow-left"
                type="button"
                id="scrollLeftBtn"
                aria-label="Scroll categories left"
            >
                <i class="bi bi-caret-left-fill"></i>
            </button>


            <nav
                class="category-navigation"
                aria-label="Menu categories"
            >

                <div class="category-list" id="categoryList">

                    <?php foreach ($categories as $category): ?>

                        <a
                            href="#category-<?php echo (int) $category['id']; ?>"
                            class="category-item"
                        >

                            <!-- Category Image -->
                            <div class="category-image">

                                <?php if (!empty($category["image"])): ?>

                                    <img
                                        src="<?php echo htmlspecialchars($category["image"]); ?>"
                                        alt=""
                                        loading="lazy"
                                    >

                                <?php else: ?>

                                    <i class="bi bi-cup-hot"></i>

                                <?php endif; ?>

                            </div>


                            <!-- Category Name -->
                            <span class="category-name">
                                <?php echo htmlspecialchars($category["name"]); ?>
                            </span>

                        </a>

                    <?php endforeach; ?>

                </div>

            </nav>


            <!-- Right Arrow -->
            <button
                class="category-arrow category-arrow-right"
                type="button"
                id="scrollRightBtn"
                aria-label="Scroll categories right"
            >
                <i class="bi bi-caret-right-fill"></i>
            </button>

        </div>


        <!-- Product Categories -->
        <?php foreach ($categories as $category): ?>

            <section
                id="category-<?php echo (int) $category['id']; ?>"
                class="menu-category mb-5"
            >

                <!-- Category Title -->
                <h3 class="category-title">
                    <?php echo htmlspecialchars($category["name"]); ?>
                </h3>


                <!-- Products -->
                <?php foreach ($products as $product): ?>

                    <?php if ($product["category_id"] == $category["id"]): ?>

                        <div class="product-row">

                            <!-- Product Image -->
                            <div class="product-thumb">

                                <?php if (!empty($product["image"])): ?>

                                    <img
                                        src="<?php echo htmlspecialchars($product["image"]); ?>"
                                        alt="<?php echo htmlspecialchars($product["name"]); ?>"
                                        class="product-image"
                                        loading="lazy"
                                    >

                                <?php else: ?>

                                    <div class="product-placeholder"></div>

                                <?php endif; ?>

                            </div>


                            <!-- Product Name and Description -->
                            <div class="product-content">

                                <h4>
                                    <?php echo htmlspecialchars($product["name"]); ?>
                                </h4>

                                <?php if (!empty($product["description"])): ?>

                                    <p class="product-description">
                                        <?php echo htmlspecialchars($product["description"]); ?>
                                    </p>

                                <?php endif; ?>

                            </div>

                            <!-- Product Price -->
                            <div class="product-price">
                                <?php echo number_format($product["price"], 0); ?> LBP
                            </div>

                        </div>

                    <?php endif; ?>

                <?php endforeach; ?>

            </section>

        <?php endforeach; ?>

        


<!-- Footer -->
<footer class="qahwa-footer">
    <p>
        &copy; <?php echo date("Y"); ?>
        <?php echo htmlspecialchars($business["shop_name"]); ?>.
        All rights reserved.
    </p>

    <p>Developed by <span>Tala Siklaoui</span></p>
</footer>




    </main>


    <!-- JavaScript -->
    <script src="assets/js/menu.js"></script>

</body>
</html>
