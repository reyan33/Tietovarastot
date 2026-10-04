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

$user_id = $_SESSION["user_id"];

$message = "";
$error = "";

$countSql = "SELECT COUNT(*) AS total
             FROM bank_accounts
             WHERE user_id = ?
             AND is_active = 1";

$countStmt = $conn->prepare($countSql);
$countStmt->bind_param("i", $user_id);
$countStmt->execute();

$countResult = $countStmt->get_result();
$countRow = $countResult->fetch_assoc();

$accountCount = (int) $countRow["total"];

$countStmt->close();

$limit = 5;

$page = isset($_GET["page"]) ? (int)$_GET["page"] : 1;

if ($page < 1) {
    $page = 1;
}

$offset = ($page - 1) * $limit;

$totalPages = ceil($accountCount / $limit);

if (isset($_GET["success"]) && $_GET["success"] == 1) {
    $message = "Pankkitilin poistopyyntö lähetetty.";
}

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

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $account_id = isset($_POST["account_id"])
        ? (int) $_POST["account_id"]
        : 0;

    $confirm1 = isset($_POST["confirm1"]) ? $_POST["confirm1"] : "";
    $confirm2 = isset($_POST["confirm2"]) ? $_POST["confirm2"] : "";        

    if ($account_id < 1) {
        $error = "Virheellinen pankkitili.";
    }

    if ($error === "" && $accountCount === 1) {

        if ($confirm1 !== "yes" || $confirm2 !== "yes") {
            $error = "Viimeisen pankkitilin poistaminen vaatii kaksi vahvistusta.";
        }

    }    

    if ($error === "") {

        $checkSql = "SELECT id, iban, balance
                    FROM bank_accounts
                    WHERE id = ? 
                    AND user_id = ?
                    AND is_active = 1";

        $checkStmt = $conn->prepare($checkSql);
        $checkStmt->bind_param("ii", $account_id, $user_id);
        $checkStmt->execute();

        $checkResult = $checkStmt->get_result();
        $account = $checkResult->fetch_assoc();

        $checkStmt->close();

        if (!$account) {
            $error = "Pankkitiliä ei löytynyt.";
        } elseif ((float) $account["balance"] != 0) {
            $error = "Pankkitilin saldo täytyy olla 0 € ennen poistopyyntöä.";
        }
    }  
    
    if ($error === "") {

        $requestSql = "SELECT id
                      FROM bank_account_requests
                      WHERE account_id = ?
                      AND request_type = 'delete'
                      AND status = 'pending'";

        $requestStmt = $conn->prepare($requestSql);
        $requestStmt->bind_param("i", $account_id);
        $requestStmt->execute();

        $requestResult = $requestStmt->get_result();

        if ($requestResult->num_rows > 0) {
            $error = "Tällä pankkitilillä on jo odottava poistopyyntö.";
        }

        $requestStmt->close();
    }  
    
    if ($error === "") {

        $insertSql = "INSERT INTO bank_account_requests
                      (user_id, account_id, request_type, status)
                      VALUES (?, ?, 'delete', 'pending')";

        $insertStmt = $conn->prepare($insertSql);
        $insertStmt->bind_param("ii", $user_id, $account_id);
        $insertStmt->execute();
        $insertStmt->close();

        header("Location: request_account_delete.php?success=1");
        exit;
    }   
}

?>

<!DOCTYPE html>
<html lang="fi">

<head>
    <meta charset="UTF-8">
    <title>Pankkitilin poistopyyntö</title>
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

        <h1>Pyydä pankkitilin poistamista 🐾</h1>

        <p>
            Pankkitilin voi poistaa vain, jos tilin saldo on 0 €.
        </p>


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


        <div class="table-container">

            <table>

                <tr>
                    <th>IBAN</th>
                    <th>Saldo</th>
                    <th>Toiminnot</th>
                </tr>


                <?php while ($bankAccount = $accounts->fetch_assoc()): ?>

                    <tr>

                        <td>
                            <?php echo htmlspecialchars($bankAccount["iban"]); ?>
                        </td>


                        <td>
                            <?php
                            echo number_format(
                                $bankAccount["balance"],
                                2,
                                ",",
                                " "
                            );
                            ?> €
                        </td>


                        <td>

                            <?php if ((float) $bankAccount["balance"] == 0): ?>

                                <form method="POST">

                                    <input
                                        type="hidden"
                                        name="account_id"
                                        value="<?php echo $bankAccount["id"]; ?>"
                                    >


                                    <?php if ($accountCount === 1): ?>

                                        <div class="delete-warning">

                                            <strong>Huomio!</strong>

                                            <p>
                                                Tämä on viimeinen pankkitilisi.
                                                Tilin poistaminen poistaa myös
                                                käyttäjätilisi.
                                            </p>


                                            <label class="checkbox-label">

                                                <input
                                                    type="checkbox"
                                                    name="confirm1"
                                                    value="yes"
                                                    required
                                                >

                                                Olen varma, että haluan
                                                poistaa pankkitilin.

                                            </label>


                                            <label class="checkbox-label">

                                                <input
                                                    type="checkbox"
                                                    name="confirm2"
                                                    value="yes"
                                                    required
                                                >

                                                Vahvistan vielä kerran, että
                                                haluan poistaa pankkitilin.

                                            </label>

                                        </div>

                                    <?php endif; ?>


                                    <button
                                        type="submit"
                                        class="btn btn-danger"
                                    >
                                        Pyydä poistamista
                                    </button>

                                </form>


                            <?php else: ?>

                                <span class="balance-warning">
                                    Saldo ei ole 0 €
                                </span>

                            <?php endif; ?>

                        </td>

                    </tr>

                <?php endwhile; ?>

            </table>

        </div>


        <?php if ($totalPages > 1): ?>

            <div class="pagination">

                <?php for ($i = 1; $i <= $totalPages; $i++): ?>

                    <a
                        href="request_account_delete.php?page=<?php echo $i; ?>"
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