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

// Get logged-in customer's bank accounts
$user_id = $_SESSION["user_id"];

$limit = 5;

$page = isset($_GET["page"]) ? (int)$_GET["page"] : 1;

if ($page < 1) {
    $page = 1;
}

$offset = ($page - 1) * $limit;

$countSql = "SELECT COUNT(*) AS total
             FROM bank_accounts
             WHERE user_id = ?
             AND is_active = 1";

$countStmt = $conn->prepare($countSql);
$countStmt->bind_param("i", $user_id);
$countStmt->execute();

$countResult = $countStmt->get_result();
$countRow = $countResult->fetch_assoc();

$totalAccounts = $countRow["total"];
$totalPages = ceil($totalAccounts / $limit);

$countStmt->close();

$sql = "SELECT id, iban, balance
        FROM bank_accounts
        WHERE user_id = ?
        AND is_active = 1
        ORDER BY id ASC
        LIMIT ? OFFSET ?";

$stmt = $conn->prepare($sql);

$stmt->bind_param("iii", $user_id, $limit, $offset);

$stmt->execute();

$accounts = $stmt->get_result();

?>

<!DOCTYPE html>
<html lang="fi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verkkopankki</title>
    <link rel="stylesheet" href="style.css?v=5">
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
                <a href="request_account.php">Uusi pankkitili</a>
                <a href="request_account_delete.php">Poista pankkitili</a>
                <a href="logout.php" class="logout-link">Kirjaudu ulos</a>
            </div>

        </div>
    </nav>

    <main class="container customer-dashboard">

        <div class="dashboard-heading">
            <h1>
                Tervetuloa, <?php echo htmlspecialchars($_SESSION["first_name"]); ?>! 🐾
            </h1>

            <p>Tässä näet tiliesi saldot ja pankkitilit.</p>
        </div>

        <div class="dashboard-layout">

            <div class="dashboard-main">

                <h2>Omat pankkitilit</h2>

                <div class="accounts-grid">

                    <?php while ($account = $accounts->fetch_assoc()): ?>

                        <div class="dashboard-account-card">

                            <div class="account-icon">
                                🏦
                            </div>

                            <div class="account-info">
                                <h3>Pankkitili</h3>

                                <p class="account-iban">
                                    <?php echo htmlspecialchars($account["iban"]); ?>
                                </p>

                                <p class="dashboard-balance">
                                    <?php echo number_format($account["balance"], 2, ",", " "); ?> €
                                </p>
                            </div>

                            <a
                                class="account-open"
                                href="customer_account_details.php?id=<?php echo $account["id"]; ?>"
                            >
                                Näytä tili ›
                            </a>

                        </div>

                    <?php endwhile; ?>

                </div>

                <?php if ($totalPages > 1): ?>

                    <div class="pagination">

                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>

                            <a
                                href="customer.php?page=<?php echo $i; ?>"
                                <?php if ($i == $page): ?>
                                    class="active"
                                <?php endif; ?>
                            >
                                <?php echo $i; ?>
                            </a>

                        <?php endfor; ?>

                    </div>

                <?php endif; ?>

            </div>

            <aside class="greeting-card">

                <img
                    src="cat-greeting.png"
                    alt="Nukkuvat kissat"
                    class="greeting-cat"
                >

                <h2>
                    Hyvää päivää,
                    <?php echo htmlspecialchars($_SESSION["first_name"]); ?>!
                </h2>

                <p>
                    Taloudellinen hyvinvointi<br>
                    antaa sinulle vapautta tehdä<br>
                    enemmän sitä, mistä pidät.
                </p>

                <div class="greeting-heart">♥</div>

            </aside>

        </div>

    </main>

</body>

</html>