<?php

session_start();
require "yhteys.php";

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "admin") {
    header("Location: login.php");
    exit;
}

$message = "";
$error = "";

function generateFinnishIban()
{
    $accountNumber = "";

    for ($i = 0; $i < 14; $i++) {
        $accountNumber .= random_int(0, 9);
    }

    $checkString = $accountNumber . "151800";

    $remainder = 0;

    for ($i = 0; $i < strlen($checkString); $i++) {
        $remainder = ($remainder * 10 + (int)$checkString[$i]) % 97;
    }

    $checkDigits = 98 - $remainder;
    $checkDigits = str_pad($checkDigits, 2, "0", STR_PAD_LEFT);

    return "FI" . $checkDigits . $accountNumber;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $request_id = isset($_POST["request_id"])
        ? (int) $_POST["request_id"]
        : 0;

    $request_type = isset($_POST["request_type"])
        ? $_POST["request_type"]
        : "";        

    if ($request_id < 1) {

        $error = "Virheellinen pyyntö.";

    } elseif ($request_type !== "create" && $request_type !== "delete") {

        $error = "Virheellinen pyynnön tyyppi.";

    } else {

        try {

            $conn->begin_transaction();

            $requestSql = "SELECT id, user_id, account_id, request_type, status
                           FROM bank_account_requests
                           WHERE id = ?
                           AND request_type = ?
                           AND status = 'pending'
                           FOR UPDATE";

            $requestStmt = $conn->prepare($requestSql);
            $requestStmt->bind_param("is", $request_id, $request_type);
            $requestStmt->execute();

            $requestResult = $requestStmt->get_result();
            $request = $requestResult->fetch_assoc();

            $requestStmt->close();


            if (!$request) {
                throw new Exception("Pyyntöä ei löytynyt tai se on jo käsitelty.");
            }


            $user_id = (int) $request["user_id"];

            if ($request_type === "create") {
              do {
              

                  $iban = generateFinnishIban();

                  $ibanSql = "SELECT id
                              FROM bank_accounts
                              WHERE iban = ?";

                  $ibanStmt = $conn->prepare($ibanSql);
                  $ibanStmt->bind_param("s", $iban);
                  $ibanStmt->execute();

                  $ibanResult = $ibanStmt->get_result();
                  $ibanExists = $ibanResult->num_rows > 0;

                  $ibanStmt->close();

              } while ($ibanExists);

            // Create the new bank account with a balance of 0
            $accountSql = "INSERT INTO bank_accounts
                           (user_id, iban, balance)
                           VALUES (?, ?, 0.00)";

            $accountStmt = $conn->prepare($accountSql);
            $accountStmt->bind_param("is", $user_id, $iban);
            $accountStmt->execute();

            $new_account_id = $conn->insert_id;

            $accountStmt->close();

            $updateSql = "UPDATE bank_account_requests
                          SET status = 'approved',
                              account_id = ?
                          WHERE id = ?";

            $updateStmt = $conn->prepare($updateSql);
            $updateStmt->bind_param(
                "ii",
                $new_account_id,
                $request_id
            );

            $updateStmt->execute();
            $updateStmt->close();

            }

            if ($request_type === "delete") {

                $account_id = (int) $request["account_id"];

                $accountSql = "SELECT id, user_id, balance
                              FROM bank_accounts
                              WHERE id = ? 
                              AND user_id = ?
                              AND is_active = 1
                              FOR UPDATE";

                $accountStmt = $conn->prepare($accountSql);
                $accountStmt->bind_param("ii", $account_id, $user_id);
                $accountStmt->execute();

                $accountResult = $accountStmt->get_result();
                $account = $accountResult->fetch_assoc();

                $accountStmt->close();

                if (!$account) {
                    throw new Exception("Pankkitiliä ei löytynyt.");
                }

                if ((float) $account["balance"] != 0) {
                    throw new Exception("Pankkitilin saldo ei ole 0.");
                } 
                
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
                
                if ($accountCount > 1) {

                    $deleteSql = "UPDATE bank_accounts
                                SET is_active = 0
                                WHERE id = ? AND user_id = ?";

                    $deleteStmt = $conn->prepare($deleteSql);
                    $deleteStmt->bind_param("ii", $account_id, $user_id);
                    $deleteStmt->execute();
                    $deleteStmt->close();

                }

                if ($accountCount === 1) {

                    $deactivateSql = "UPDATE bank_accounts
                                    SET is_active = 0
                                    WHERE id = ? AND user_id = ?";

                    $deactivateStmt = $conn->prepare($deactivateSql);
                    $deactivateStmt->bind_param("ii", $account_id, $user_id);
                    $deactivateStmt->execute();
                    $deactivateStmt->close();

                    $deactivateUserSql = "UPDATE bank_users
                                        SET is_active = 0
                                        WHERE id = ? AND role = 'customer'";

                    $deactivateUserStmt = $conn->prepare($deactivateUserSql);
                    $deactivateUserStmt->bind_param("i", $user_id);
                    $deactivateUserStmt->execute();
                    $deactivateUserStmt->close();

                } 
                
                $updateRequestSql = "UPDATE bank_account_requests
                                    SET status = 'approved'
                                    WHERE id = ?";

                $updateRequestStmt = $conn->prepare($updateRequestSql);
                $updateRequestStmt->bind_param("i", $request_id);
                $updateRequestStmt->execute();
                $updateRequestStmt->close();

                }
                 

            $conn->commit();

            // Refreshing the page won't accidentally approve/create another account.
            if ($request_type === "create") {
                header("Location: account_requests.php?created=1");
            } else {
                header("Location: account_requests.php?deleted=1");
            }

            exit;


        } catch (Throwable $e) {

            $conn->rollback();

            $error = "Pyynnön hyväksyminen epäonnistui.";
        }
    }
}

if (isset($_GET["created"]) && $_GET["created"] == 1) {
    $message = "Pankkitilipyyntö hyväksyttiin ja uusi pankkitili luotiin.";
}

if (isset($_GET["deleted"]) && $_GET["deleted"] == 1) {
    $message = "Pankkitilin poistopyyntö hyväksyttiin ja pankkitili poistettiin.";
}

$limit = 5;

$page = isset($_GET["page"])
    ? (int) $_GET["page"]
    : 1;

if ($page < 1) {
    $page = 1;
}

$offset = ($page - 1) * $limit;

// Count pending requests
$countSql = "SELECT COUNT(*) AS total
             FROM bank_account_requests
             WHERE status = 'pending'
             OR (request_type = 'create' AND status = 'approved')";

$countResult = $conn->query($countSql);
$countRow = $countResult->fetch_assoc();

$totalRequests = $countRow["total"];

$totalPages = ceil($totalRequests / $limit);

// Get pending requests and customer information
$sql = "SELECT
            r.id,
            r.user_id,
            r.account_id,
            r.request_type,
            r.status,
            r.created_at,
            u.first_name,
            u.last_name,
            u.username
        FROM bank_account_requests r
        JOIN bank_users u
            ON r.user_id = u.id
        WHERE r.status = 'pending'
            OR (r.request_type = 'create' AND r.status = 'approved')
        ORDER BY r.created_at DESC
        LIMIT ? OFFSET ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $limit, $offset);
$stmt->execute();

$requests = $stmt->get_result();

?>

<!DOCTYPE html>
<html lang="fi">

<head>
    <meta charset="UTF-8">
    <title>Pankkitilipyynnöt</title>
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

                <a href="admin.php">
                    Etusivu
                </a>

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

        <h1>Pankkitilipyynnöt 🐾</h1>

        <p>
            Tarkastele ja hyväksy asiakkaiden pankkitilipyynnöt.
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

        <?php if ($totalRequests == 0): ?>

            <div class="card">
                <p>Ei odottavia pyyntöjä.</p>
            </div>

        <?php else: ?>

            <div class="table-container">

                <table>

                    <tr>
                        <th>ID</th>
                        <th>Asiakas</th>
                        <th>Käyttäjätunnus</th>
                        <th>Pyynnön tyyppi</th>
                        <th>Päivämäärä</th>
                        <th>Toiminnot</th>
                    </tr>


                    <?php while ($request = $requests->fetch_assoc()): ?>

                        <tr>

                            <td>
                                <?php echo $request["id"]; ?>
                            </td>


                            <td>
                                <?php
                                echo htmlspecialchars(
                                    $request["first_name"]
                                    . " "
                                    . $request["last_name"]
                                );
                                ?>
                            </td>


                            <td>
                                <?php
                                echo htmlspecialchars(
                                    $request["username"]
                                );
                                ?>
                            </td>


                            <td>

                                <?php if ($request["request_type"] === "create"): ?>

                                    Uusi pankkitili

                                <?php else: ?>

                                    Pankkitilin poisto

                                <?php endif; ?>

                            </td>


                            <td>
                                <?php
                                echo htmlspecialchars(
                                    $request["created_at"]
                                );
                                ?>
                            </td>


                            <td>

                                <?php
                                if (
                                    $request["request_type"] === "create"
                                    && $request["status"] === "pending"
                                ):
                                ?>

                                    <form method="POST">

                                        <input
                                            type="hidden"
                                            name="request_id"
                                            value="<?php echo $request["id"]; ?>"
                                        >

                                        <input
                                            type="hidden"
                                            name="request_type"
                                            value="create"
                                        >

                                        <button
                                            type="submit"
                                            class="btn"
                                        >
                                            Hyväksy
                                        </button>

                                    </form>


                                <?php
                                elseif (
                                    $request["request_type"] === "create"
                                    && $request["status"] === "approved"
                                ):
                                ?>

                                    <span class="amount-received">
                                        Hyväksytty
                                    </span>


                                <?php
                                elseif (
                                    $request["request_type"] === "delete"
                                    && $request["status"] === "pending"
                                ):
                                ?>

                                    <form method="POST">

                                        <input
                                            type="hidden"
                                            name="request_id"
                                            value="<?php echo $request["id"]; ?>"
                                        >

                                        <input
                                            type="hidden"
                                            name="request_type"
                                            value="delete"
                                        >

                                        <button
                                            type="submit"
                                            class="btn btn-danger"
                                        >
                                            Hyväksy
                                        </button>

                                    </form>

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
                            href="account_requests.php?page=<?php echo $i; ?>"
                            <?php if ($i == $page): ?>
                                class="active"
                            <?php endif; ?>
                        >
                            <?php echo $i; ?>
                        </a>

                    <?php endfor; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>

    </main>

</body>

</html>