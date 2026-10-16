
<?php

// Check if the administrator is logged in
require_once __DIR__ . "/auth.php";

// Connect to MySQL
require_once __DIR__ . "/../config/database.php";


// Retrieve business branding
$settingsStatement = $pdo->query(
    "SELECT shop_name, tagline, logo_path
     FROM business_settings
     WHERE id = 1"
);

$business = $settingsStatement->fetch(PDO::FETCH_ASSOC);

if (!$business) {
    exit("Business settings not found.");
}


// Get the logged-in administrator's username
$username = $_SESSION["admin_username"];

// Count the categories
$categoryStatement = $pdo->query(
    "SELECT COUNT(*) FROM categories"
);

$totalCategories = $categoryStatement->fetchColumn();

// Count the products
$productStatement = $pdo->query(
    "SELECT COUNT(*) FROM products"
);

$totalProducts = $productStatement->fetchColumn();

?>



<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Qahwa | Admin Dashboard</title>

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

    <!-- Custom stylesheet -->
    <link rel="stylesheet" href="../assets/css/style.css?v=5">
</head>

<body>

    <main class="container py-4 admin-dashboard">

        <!-- Dashboard Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">

            <div>
                
<div class="admin-brand">
    
<img
    src="../<?php echo htmlspecialchars(
        $business["logo_path"],
        ENT_QUOTES,
        "UTF-8"
    ); ?>"
    alt="Coffee shop logo"
    class="admin-brand-logo"
>


    <div>
        
<h1 class="dashboard-title">
    <?php echo htmlspecialchars($business["shop_name"]); ?> Admin
</h1>

        <p class="dashboard-subtitle">
            Coffee Shop Management
        </p>
    </div>
</div>

            </div>

            
<a href="logout.php"
   class="logout-icon"
   aria-label="Log out"
   title="Log out">
    <i class="bi bi-box-arrow-right"></i>
</a>


        </div>

        <!-- Statistics -->
        <div class="row g-3 mb-4">

            <div class="col-6">
                <div class="dashboard-stat">

                    <i class="bi bi-grid"></i>

                    <h2>
                        <?php echo (int) $totalCategories; ?>
                    </h2>

                    <p>Categories</p>

                </div>
            </div>

            <div class="col-6">
                <div class="dashboard-stat">

                    <i class="bi bi-cup-hot"></i>

                    <h2>
                        <?php echo (int) $totalProducts; ?>
                    </h2>

                    <p>Products</p>

                </div>
            </div>

        </div>

        <!-- Management Section -->
        <h2 class="dashboard-section-title mb-3">
            Menu Management
        </h2>

        <div class="dashboard-links">

            <a href="categories.php" class="dashboard-link">
                <i class="bi bi-grid"></i>

                <div>
                    <h3>Manage Categories</h3>
                    <p>Add, edit and organize categories</p>
                </div>

                <i class="bi bi-chevron-right"></i>
            </a>

            <a href="products.php" class="dashboard-link">
                <i class="bi bi-cup-hot"></i>

                <div>
                    <h3>Manage Products</h3>
                    <p>Update products, prices and images</p>
                </div>

                <i class="bi bi-chevron-right"></i>
            </a>

            <a href="qr_code.php" class="dashboard-link">
                <i class="bi bi-qr-code"></i>

                <div>
                    <h3>Menu QR Code</h3>
                    <p>View and print your QR code</p>
                </div>

                <i class="bi bi-chevron-right"></i>
            </a>

            
<!-- Business Settings -->
<a href="business_settings.php" class="dashboard-link">

    <i class="bi bi-gear"></i>

    <div>
        <h3>Business Settings</h3>
        <p>Update coffee shop name, tagline and logo</p>
    </div>

    <i class="bi bi-chevron-right"></i>

</a>


        </div>

        
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

</body>
</html>

