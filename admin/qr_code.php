
<?php

// Protect the admin page
require_once __DIR__ . "/auth.php";


require_once __DIR__ . "/../config/database.php";

$settingsStatement = $pdo->query(
    "SELECT shop_name
     FROM business_settings
     WHERE id = 1"
);

$shopName = $settingsStatement->fetchColumn() ?: "Qahwa";


// Temporary local URL
// Replace this after deploying Qahwa online
$menuUrl = "https://qahwa-menu.infinityfree.io/";

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Qahwa | Menu QR Code</title>

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
    <link rel="stylesheet"
          href="../assets/css/style.css?v=3">

    <!-- QR Code Library -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>

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
                Menu QR Code
            </h1>

            <p class="dashboard-subtitle">
                Share Qahwa's digital menu
            </p>
        </div>

    </div>

    <!-- QR Card -->
    <div class="qr-card">

        <h2 class="qr-brand">
    <?php echo htmlspecialchars($shopName); ?>
</h2>

        <p class="qr-subtitle">
            Scan to explore our menu
        </p>

        <!-- Generated QR Code -->
        <div id="qrcode" class="qr-image"></div>

        <!-- Menu URL -->
        <p class="qr-link">
            <?php echo htmlspecialchars($menuUrl); ?>
        </p>

        <!-- Buttons -->
        <div class="qr-actions">

            <button
                type="button"
                id="downloadQR"
                class="qr-download-button"
            >
                <i class="bi bi-download"></i>
                Download
            </button>

            <button
                type="button"
                id="printQR"
                class="qr-print-button"
            >
                <i class="bi bi-printer"></i>
                Print
            </button>

        </div>

    </div>


</main>

<script>

// PHP passes the menu URL to JavaScript safely
const menuUrl = <?php echo json_encode(
    $menuUrl,
    JSON_HEX_TAG | JSON_HEX_AMP |
    JSON_HEX_APOS | JSON_HEX_QUOT
); ?>;

const qrContainer = document.getElementById("qrcode");

// Generate the QR code
if (typeof QRCode !== "undefined") {

    new QRCode(qrContainer, {
        text: menuUrl,
        width: 220,
        height: 220,
        colorDark: "#49382F",
        colorLight: "#FFFFFF",
        correctLevel: QRCode.CorrectLevel.H
    });

} else {
    qrContainer.textContent =
        "Unable to load QR code generator.";
}

// Download QR Code
document.getElementById("downloadQR")
    .addEventListener("click", function () {

        const canvas = qrContainer.querySelector("canvas");
        const qrImage = qrContainer.querySelector("img");

        let imageUrl = null;

        if (canvas) {
            imageUrl = canvas.toDataURL("image/png");
        } else if (qrImage) {
            imageUrl = qrImage.src;
        }

        if (!imageUrl) {
            alert("QR code is not ready yet.");
            return;
        }

        const link = document.createElement("a");
        link.href = imageUrl;
        link.download = "qahwa-menu-qr.png";
        document.body.appendChild(link);
        link.click();
        link.remove();
    });

// Print QR Code
document.getElementById("printQR")
    .addEventListener("click", function () {

        const qrImage = qrContainer.querySelector("img");

        if (!qrImage || !qrImage.getAttribute("src")) {
            alert("QR code is not ready yet.");
            return;
        }

        window.print();
    });

</script>

</body>
</html>
