<?php

session_start();
require "yhteys.php";

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "customer") {
    header("Location: login.php");
    exit;
}

$checkUserSql = "SELECT id
                 FROM bank_users
                 WHERE id = ?
                 AND role = 'customer'
                 AND is_active = 1";

$checkUserStmt = $conn->prepare($checkUserSql);
$checkUserStmt->bind_param("i", $_SESSION["user_id"]);
$checkUserStmt->execute();

$checkUserResult = $checkUserStmt->get_result();

if ($checkUserResult->num_rows === 0) {
    $checkUserStmt->close();

    session_unset();
    session_destroy();

    header("Location: login.php");
    exit;
}

$checkUserStmt->close();

$message = "";
$error = "";

$user_id = $_SESSION["user_id"];

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $checkSql = "SELECT id
                 FROM bank_account_requests
                 WHERE user_id = ?
                 AND request_type = 'create'
                 AND status = 'pending'";

    $checkStmt = $conn->prepare($checkSql);
    $checkStmt->bind_param("i", $user_id);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();

    if ($checkResult->num_rows > 0) {

        $error = "Sinulla on jo odottava pankkitilipyyntö.";

    } else {

        $sql = "INSERT INTO bank_account_requests
                (user_id, request_type, status)
                VALUES (?, 'create', 'pending')";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stmt->close();

        $message = "Uuden pankkitilin pyyntö lähetetty.";
    }

    $checkStmt->close();
}

?>

<!DOCTYPE html>
<html lang="fi">

<head>
    <meta charset="UTF-8">
    <title>Uusi pankkitili</title>
    <link rel="stylesheet" href="style.css?v=20">
</head>

<body>

    <nav class="navbar">
        <div class="navbar-content">

            <a href="customer.php" class="logo">
                <img src="cat-logo.png" alt="Kissa">
                <span>Verkkopankki</span>
            </a>

            <div class="nav-links">
                <a href="customer.php">Etusivu</a>

                <a href="request_account.php">
                    Uusi pankkitili
                </a>

                <a href="request_account_delete.php">
                    Poista pankkitili
                </a>

                <a href="logout.php" class="logout-link">
                    Kirjaudu ulos
                </a>
            </div>

        </div>
    </nav>


    <main class="container">

        <h1>Pyydä uusi pankkitili 🐾</h1>

        <?php if ($message !== ""): ?>

            <div class="message message-success">
                <?php echo htmlspecialchars($message); ?>
            </div>

        <?php endif; ?>


        <?php if ($error !== ""): ?>

            <div class="message message-error">
                <?php echo htmlspecialchars($error); ?>
            </div>

        <?php endif; ?>


        <div class="form-card">

            <h2>Uusi pankkitili</h2>

            <p>
                Voit lähettää pyynnön uuden pankkitilin luomisesta.
                Järjestelmänvalvoja hyväksyy pyynnön.
            </p>

            <form method="POST">

                <button type="submit" class="btn">
                    Pyydä uusi pankkitili
                </button>

            </form>

        </div>

    </main>

</body>

</html>