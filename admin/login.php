
<?php

session_start();

require_once __DIR__ . "/../config/database.php";

if (isset($_SESSION["admin_id"])) {
    header("Location: dashboard.php");
    exit;
}

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $username = trim($_POST["username"] ?? "");
    $password = $_POST["password"] ?? "";

    if ($username === "" || $password === "") {

        $error = "Please enter your username and password.";

    } else {

        $statement = $pdo->prepare(
            "SELECT id, username, password_hash
             FROM admins
             WHERE username = ?"
        );

        $statement->execute([$username]);

        $admin = $statement->fetch(PDO::FETCH_ASSOC);

        if (
            $admin &&
            password_verify($password, $admin["password_hash"])
        ) {

            session_regenerate_id(true);

            $_SESSION["admin_id"] = $admin["id"];
            $_SESSION["admin_username"] = $admin["username"];

            header("Location: dashboard.php");
            exit;

        } else {

            $error = "Invalid username or password.";

        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Qahwa | Admin Login</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <link rel="stylesheet" href="../assets/css/style.css">
</head>

<body>

    <main class="container py-5">

        <div class="mx-auto" style="max-width: 400px;">

            
<div class="text-center mb-4">
    <h1 class="fw-bold mb-2">Qahwa Admin</h1>
    <p>Sign in to manage the coffee shop menu.</p>
</div>


<?php if ($error !== ""): ?>

    <div class="alert alert-danger" role="alert">
        <?php echo htmlspecialchars($error); ?>
    </div>

<?php endif; ?>


            <form method="post">

                <div class="mb-3">
                    <label for="username" class="form-label">
                        Username
                    </label>

                    <input
                        type="text"
                        name="username"
                        id="username"
                        class="form-control"
                        required
                    >
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label">
                        Password
                    </label>

                    <input
                        type="password"
                        name="password"
                        id="password"
                        class="form-control"
                        required
                    >
                </div>

                <button type="submit" class="btn btn-dark w-100">
                    Sign In
                </button>

            </form>

        </div>

    </main>

</body>
</html>
