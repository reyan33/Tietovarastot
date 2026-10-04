<?php

session_start();
require "yhteys.php";

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "customer") {
    header("Location: login.php");
    exit;
}

// Check that the customer is still active
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

// Check account ID
if (!isset($_GET["id"])) {
    die("Tilin ID puuttuu.");
}

$account_id = (int) $_GET["id"];

if ($account_id < 1) {
    die("Virheellinen tilin ID.");
}

$user_id = $_SESSION["user_id"];

$sql = "SELECT id, iban, balance
        FROM bank_accounts
        WHERE id = ?
        AND user_id = ?
        AND is_active = 1";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $account_id, $user_id);
$stmt->execute();

$result = $stmt->get_result();
$account = $result->fetch_assoc();

$stmt->close();

if (!$account) {
    die("Pankkitiliä ei löytynyt.");
}

$message = "";
$error = "";

if (isset($_GET["success"]) && $_GET["success"] == 1) {
    $message = "Rahansiirto onnistui!";
}

// TRANSFER
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $from_account_id = (int) $_POST["from_account_id"];
    $to_iban = trim($_POST["to_iban"]);
    $amount = (float) $_POST["amount"];


    // Check sending account
    $checkSql = "SELECT id, iban, balance
                 FROM bank_accounts
                 WHERE id = ?
                 AND user_id = ?
                 AND is_active = 1";

    $checkStmt = $conn->prepare($checkSql);
    $checkStmt->bind_param("ii", $from_account_id, $user_id);
    $checkStmt->execute();

    $checkResult = $checkStmt->get_result();
    $fromAccount = $checkResult->fetch_assoc();

    $checkStmt->close();

    if (!$fromAccount) {
        $error = "Lähettävää tiliä ei löytynyt.";
    }

    // Find destination account
    $toAccount = null;

    if ($error === "") {

        $toSql = "SELECT id, iban, balance
                  FROM bank_accounts
                  WHERE iban = ?
                  AND is_active = 1";

        $toStmt = $conn->prepare($toSql);
        $toStmt->bind_param("s", $to_iban);
        $toStmt->execute();

        $toResult = $toStmt->get_result();
        $toAccount = $toResult->fetch_assoc();

        $toStmt->close();

        if (!$toAccount) {
            $error = "Kohdetiliä ei löytynyt.";
        }
    }

    // Check transfer information
    if ($error === "") {

        if ($amount <= 0) {

            $error = "Summan täytyy olla suurempi kuin 0.";

        } elseif ($fromAccount["id"] == $toAccount["id"]) {

            $error = "Et voi siirtää rahaa samalle tilille.";

        } elseif ($fromAccount["balance"] < $amount) {

            $error = "Tilillä ei ole tarpeeksi rahaa.";
        }
    }

    // Make transfer
    if ($error === "") {

        try {

            $conn->begin_transaction();

            // Remove money from sending account
            $subtractSql = "UPDATE bank_accounts
                            SET balance = balance - ?
                            WHERE id = ?";

            $subtractStmt = $conn->prepare($subtractSql);

            $subtractStmt->bind_param(
                "di",
                $amount,
                $fromAccount["id"]
            );

            $subtractStmt->execute();
            $subtractStmt->close();

            // Add money to destination account
            $addSql = "UPDATE bank_accounts
                       SET balance = balance + ?
                       WHERE id = ?";

            $addStmt = $conn->prepare($addSql);

            $addStmt->bind_param(
                "di",
                $amount,
                $toAccount["id"]
            );

            $addStmt->execute();
            $addStmt->close();

            // Save transaction
            $transactionSql = "INSERT INTO bank_transactions
                               (from_account_id, to_account_id, amount)
                               VALUES (?, ?, ?)";

            $transactionStmt = $conn->prepare($transactionSql);

            $transactionStmt->bind_param(
                "iid",
                $fromAccount["id"],
                $toAccount["id"],
                $amount
            );

            $transactionStmt->execute();
            $transactionStmt->close();

            $conn->commit();

            header(
                "Location: customer_account_details.php?id="
                . $account_id
                . "&success=1"
            );
            exit;

        } catch (Throwable $e) {

            $conn->rollback();

            $error = "Rahansiirto epäonnistui.";
        }
    }
}


// Get all active accounts for transfer dropdown
$accountsSql = "SELECT id, iban, balance
                FROM bank_accounts
                WHERE user_id = ?
                AND is_active = 1
                ORDER BY id ASC";

$accountsStmt = $conn->prepare($accountsSql);
$accountsStmt->bind_param("i", $user_id);
$accountsStmt->execute();

$accounts = $accountsStmt->get_result();

// Refresh the viewed account after a transfer attempt
$refreshSql = "SELECT id, iban, balance
               FROM bank_accounts
               WHERE id = ?
               AND user_id = ?
               AND is_active = 1";

$refreshStmt = $conn->prepare($refreshSql);
$refreshStmt->bind_param("ii", $account_id, $user_id);
$refreshStmt->execute();

$refreshResult = $refreshStmt->get_result();
$account = $refreshResult->fetch_assoc();

$refreshStmt->close();

$limit = 5;

$page = isset($_GET["page"]) ? (int) $_GET["page"] : 1;

if ($page < 1) {
    $page = 1;
}

$offset = ($page - 1) * $limit;

// Count transactions
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


// Get transactions
$transactionSql = "SELECT id,
                          from_account_id,
                          to_account_id,
                          amount,
                          created_at
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

    <h1>Tilin tiedot 🐾</h1>

    <div class="account-card">

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


    <div class="account-content">

        <!-- TRANSACTIONS -->

        <section class="transactions-section">

            <h2>Tilitapahtumat</h2>

            <?php if ($totalTransactions == 0): ?>

                <p>Ei tilitapahtumia.</p>

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

                                    if ($transaction["from_account_id"] == $account_id) {

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
                            href="customer_account_details.php?id=<?php echo $account_id; ?>&page=<?php echo $i; ?>"
                            <?php if ($i == $page): ?>
                                class="active"
                            <?php endif; ?>
                        >
                            <?php echo $i; ?>
                        </a>

                    <?php endfor; ?>

                </div>

            <?php endif; ?>

        </section>


        <!-- TRANSFER -->

        <section class="transfer-section">

            <h2>Siirrä rahaa</h2>

            <div class="form-card">

                <form method="POST">

                    <div class="form-group">

                        <label>
                            Lähettävä tili
                        </label>

                        <select
                            name="from_account_id"
                            required
                        >

                            <?php while ($transferAccount = $accounts->fetch_assoc()): ?>

                                <option
                                    value="<?php
                                    echo $transferAccount["id"];
                                    ?>"
                                    <?php
                                    if (
                                        $transferAccount["id"]
                                        == $account_id
                                    ) {
                                        echo "selected";
                                    }
                                    ?>
                                >

                                    <?php

                                    echo htmlspecialchars(
                                        $transferAccount["iban"]
                                    )
                                    . " - "
                                    . number_format(
                                        $transferAccount["balance"],
                                        2,
                                        ",",
                                        " "
                                    )
                                    . " €";

                                    ?>

                                </option>

                            <?php endwhile; ?>

                        </select>

                    </div>

                    <div class="form-group">

                        <label>
                            Kohdetilin IBAN
                        </label>

                        <input
                            type="text"
                            name="to_iban"
                            required
                        >

                    </div>

                    <div class="form-group">
                        <label>
                            Summa (€)
                        </label>

                        <input
                            type="number"
                            name="amount"
                            min="0.01"
                            step="0.01"
                            required
                        >
                    </div>

                    <button
                        type="submit"
                        class="btn"
                    >
                        Siirrä rahaa
                    </button>
                </form>
            </div>
        </section>
    </div>

</main>

</body>
</html>