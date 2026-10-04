<?php

session_start();
require "yhteys.php";

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "admin") {
    header("Location: login.php");
    exit;
}

if (!isset($_GET["id"])) {
    die("Tilin ID puuttuu.");
}

$account_id = (int) $_GET["id"];

if ($account_id < 1) {
    die("Virheellinen tilin ID.");
}

$sql = "SELECT id, user_id, iban, balance
        FROM bank_accounts
        WHERE id = ?
        AND is_active = 1";

$stmt = $conn->prepare($sql);

$stmt->bind_param("i", $account_id);

$stmt->execute();

$result = $stmt->get_result();

$account = $result->fetch_assoc();

$stmt->close();

if (!$account) {
    die("Pankkitiliä ei löytynyt.");
}

// Transaction pagination
$limit = 5;

$page = isset($_GET["page"]) ? (int) $_GET["page"] : 1;

if ($page < 1) {
    $page = 1;
}

$offset = ($page - 1) * $limit;

// Count all transactions
$countSql = "SELECT COUNT(*) AS total
             FROM bank_transactions
             WHERE from_account_id = ?
             OR to_account_id = ?";

$countStmt = $conn->prepare($countSql);

$countStmt->bind_param(
    "ii",
    $account_id,
    $account_id
);

$countStmt->execute();

$countResult = $countStmt->get_result();
$countRow = $countResult->fetch_assoc();

$totalTransactions = $countRow["total"];

$totalPages = ceil($totalTransactions / $limit);

$countStmt->close();

// Get transactions for this account
$transactionSql = "SELECT id, from_account_id, to_account_id, amount, created_at
                   FROM bank_transactions
                   WHERE from_account_id = ?
                   OR to_account_id = ?
                   ORDER BY created_at DESC
                   LIMIT ? OFFSET ?";

$transactionStmt = $conn->prepare($transactionSql);

$transactionStmt->bind_param(
    "iiii",
    $account_id,
    $account_id,
    $limit,
    $offset
);

$transactionStmt->execute();

$transactions = $transactionStmt->get_result();
?>

<!DOCTYPE html>
<html lang="fi">

<head>
    <meta charset="UTF-8">
    <title>Tilin tiedot</title>
    <link rel="stylesheet" href="style.css?v=20">
</head>

<body>

    <nav class="navbar">
        <div class="navbar-content">

            <a href="admin.php" class="logo">
                <img src="cat-logo.png" alt="Kissa">
                <span>Verkkopankki</span>
            </a>

            <div class="nav-links">

                <a href="admin.php">Etusivu</a>

                <a href="customers_list.php">
                    Asiakkaat
                </a>

                <a href="account_requests.php">
                    Pankkitilipyynnöt
                </a>

                <a href="admins_list.php">
                    Järjestelmänvalvojat
                </a>

                <a href="logout.php" class="logout-link">
                    Kirjaudu ulos
                </a>

            </div>

        </div>
    </nav>

    <main class="container">

        <h1>Tilin tiedot 🐾</h1>

        <div class="account-card">

            <p>
                <strong>Tilin ID:</strong>
                <?php echo $account["id"]; ?>
            </p>

            <p>
                <strong>IBAN:</strong>
                <?php echo htmlspecialchars($account["iban"]); ?>
            </p>

            <p class="account-balance">
                <?php
                echo number_format(
                    $account["balance"],
                    2,
                    ",",
                    " "
                );
                ?> €
            </p>

        </div>

        <h2>Tilitapahtumat</h2>

        <?php if ($totalTransactions == 0): ?>

            <div class="card">
                <p>Ei tilitapahtumia.</p>
            </div>

        <?php else: ?>

            <div class="table-container">

                <table>

                    <tr>
                        <th>ID</th>
                        <th>Tyyppi</th>
                        <th>Summa</th>
                        <th>Päivämäärä</th>
                    </tr>

                    <?php while ($transaction = $transactions->fetch_assoc()): ?>

                        <tr>
                            <td>
                                <?php echo $transaction["id"]; ?>
                            </td>

                            <td>

                                <?php

                                if (
                                    $transaction["from_account_id"]
                                    == $account_id
                                ) {
                                    echo "Lähetetty";
                                } else {
                                    echo "Vastaanotettu";
                                }

                                ?>

                            </td>

                            <td class="<?php
                                echo $transaction["from_account_id"] == $account_id
                                    ? "amount-sent"
                                    : "amount-received";
                            ?>">

                                <?php

                                if (
                                    $transaction["from_account_id"]
                                    == $account_id
                                ) {

                                    echo "-"
                                        . number_format(
                                            $transaction["amount"],
                                            2,
                                            ",",
                                            " "
                                        )
                                        . " €";

                                } else {

                                    echo "+"
                                        . number_format(
                                            $transaction["amount"],
                                            2,
                                            ",",
                                            " "
                                        )
                                        . " €";
                                }

                                ?>

                            </td>

                            <td>
                                <?php
                                echo htmlspecialchars(
                                    $transaction["created_at"]
                                );
                                ?>
                            </td>

                        </tr>

                    <?php endwhile; ?>

                </table>

            </div>

        <?php endif; ?>

        <?php if ($totalPages > 1): ?>

            <div class="pagination">

                <?php for ($i = 1; $i <= $totalPages; $i++): ?>

                    <a
                        href="account_details.php?id=<?php echo $account_id; ?>&page=<?php echo $i; ?>"
                        <?php if ($i == $page): ?>
                            class="active"
                        <?php endif; ?>
                    >
                        <?php echo $i; ?>
                    </a>

                <?php endfor; ?>

            </div>

        <?php endif; ?>
    </main>

</body>

</html>