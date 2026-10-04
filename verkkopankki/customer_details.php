<?php

session_start();
require "yhteys.php";

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "admin") {
    header("Location: login.php");
    exit;
}

// Check that customer ID exists in the URL
if (!isset($_GET["id"])) {
    die("Asiakkaan ID puuttuu.");
}

$customer_id = (int) $_GET["id"];

if ($customer_id < 1) {
    die("Virheellinen asiakkaan ID.");
}

// Get customer information
$sql = "SELECT id, first_name, last_name, username
        FROM bank_users
        WHERE id = ? 
        AND role = 'customer'
        AND is_active = 1";

$stmt = $conn->prepare($sql);

$stmt->bind_param("i", $customer_id);

$stmt->execute();

$result = $stmt->get_result();

$customer = $result->fetch_assoc();

$stmt->close();

if (!$customer) {
    die("Asiakasta ei löytynyt.");
}

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
$countStmt->bind_param("i", $customer_id);
$countStmt->execute();

$countResult = $countStmt->get_result();
$countRow = $countResult->fetch_assoc();

$totalAccounts = $countRow["total"];
$totalPages = ceil($totalAccounts / $limit);

$countStmt->close();

$accountSql = "SELECT id, iban, balance
               FROM bank_accounts
               WHERE user_id = ?
               AND is_active = 1
               ORDER BY id ASC
               LIMIT ? OFFSET ?";

$accountStmt = $conn->prepare($accountSql);

$accountStmt->bind_param("iii", $customer_id, $limit, $offset);

$accountStmt->execute();

$accounts = $accountStmt->get_result();
?>

<!DOCTYPE html>
<html lang="fi">

<head>
    <meta charset="UTF-8">
    <title>Asiakkaan tiedot</title>
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

        <h1>Asiakkaan tiedot 🐾</h1>

        <div class="card">

            <p>
                <strong>ID:</strong>
                <?php echo $customer["id"]; ?>
            </p>

            <p>
                <strong>Nimi:</strong>
                <?php
                echo htmlspecialchars(
                    $customer["first_name"]
                    . " "
                    . $customer["last_name"]
                );
                ?>
            </p>

            <p>
                <strong>Käyttäjätunnus:</strong>
                <?php echo htmlspecialchars($customer["username"]); ?>
            </p>

        </div>

        <h2>Pankkitilit</h2>

        <div class="table-container">

            <table>

                <tr>
                    <th>Tilin ID</th>
                    <th>IBAN</th>
                    <th>Saldo</th>
                    <th>Toiminnot</th>
                </tr>

                <?php while ($account = $accounts->fetch_assoc()): ?>

                    <tr>

                        <td>
                            <?php echo $account["id"]; ?>
                        </td>

                        <td>
                            <?php
                            echo htmlspecialchars(
                                $account["iban"]
                            );
                            ?>
                        </td>

                        <td>
                            <?php
                            echo number_format(
                                $account["balance"],
                                2,
                                ",",
                                " "
                            );
                            ?> €
                        </td>

                        <td>

                            <a href="account_details.php?id=<?php echo $account["id"]; ?>">
                                Näytä tili
                            </a>

                        </td>

                    </tr>

                <?php endwhile; ?>

            </table>

        </div>

        <?php if ($totalPages > 1): ?>

            <div class="pagination">

                <?php for ($i = 1; $i <= $totalPages; $i++): ?>

                    <a
                        href="customer_details.php?id=<?php echo $customer_id; ?>&page=<?php echo $i; ?>"
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