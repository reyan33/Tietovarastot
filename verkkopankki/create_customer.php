<?php

session_start();
require "yhteys.php";

// Only admins can access this page
if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "admin") {
    header("Location: login.php");
    exit;
}

function generateFinnishIBAN() {

    $accountNumber = "";

    for ($i = 0; $i < 14; $i++) {
        $accountNumber .= random_int(0, 9);
    }

    // FI = 1518 in the IBAN checksum calculation
    $checkString = $accountNumber . "151800";

    $remainder = 0;

    for ($i = 0; $i < strlen($checkString); $i++) {
        $remainder =
            ($remainder * 10 + intval($checkString[$i])) % 97;
    }

    $checkDigits = 98 - $remainder;

    return "FI"
        . str_pad($checkDigits, 2, "0", STR_PAD_LEFT)
        . $accountNumber;
}

$message = "";
$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $first_name = trim($_POST["first_name"]);
    $last_name = trim($_POST["last_name"]);
    $username = trim($_POST["username"]);
    $password = $_POST["password"];
    $balance = $_POST["balance"];

    try {

        // Start database transaction
        $conn->begin_transaction();

        // Hash password
        $hashed_password =
            password_hash($password, PASSWORD_DEFAULT);

        // Create customer
        $sql = "INSERT INTO bank_users
                (first_name, last_name, username, password, role)
                VALUES (?, ?, ?, ?, 'customer')";

        $stmt = $conn->prepare($sql);

        $stmt->bind_param(
            "ssss",
            $first_name,
            $last_name,
            $username,
            $hashed_password
        );

        if (!$stmt->execute()) {
            throw new Exception("Asiakkaan luominen epäonnistui.");
        }

        // Get the new customer's ID
        $user_id = $conn->insert_id;

        $stmt->close();

        // Generate a unique IBAN
        do {

            $iban = generateFinnishIBAN();

            $checkSql =
                "SELECT id FROM bank_accounts WHERE iban = ?";

            $checkStmt = $conn->prepare($checkSql);
            $checkStmt->bind_param("s", $iban);
            $checkStmt->execute();

            $checkResult = $checkStmt->get_result();

            $ibanExists = $checkResult->num_rows > 0;

            $checkStmt->close();

        } while ($ibanExists);


        // Create customer's first bank account
        $accountSql =
            "INSERT INTO bank_accounts
             (user_id, iban, balance)
             VALUES (?, ?, ?)";

        $accountStmt = $conn->prepare($accountSql);

        $accountStmt->bind_param(
            "isd",
            $user_id,
            $iban,
            $balance
        );

        if (!$accountStmt->execute()) {
            throw new Exception("Pankkitilin luominen epäonnistui.");
        }

        $accountStmt->close();

        $conn->commit();

        $message =
            "Asiakas ja pankkitili luotu onnistuneesti!";

    } catch (Throwable $e) {

        // Cancel all database changes
        $conn->rollback();

        $error =
            "Asiakkaan tai pankkitilin luominen epäonnistui.";
    }
}

?>

<!DOCTYPE html>
<html lang="fi">

<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Luo asiakas - Verkkopankki</title>

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

        <h1>Luo uusi asiakas 🐾</h1>

        <p>
            Luo asiakkaalle käyttäjätili ja oletuspankkitili.
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

        <div class="form-card">

            <h2>Asiakkaan tiedot</h2>

            <form method="POST">

                <div class="form-group">

                    <label for="first_name">
                        Etunimi
                    </label>

                    <input
                        type="text"
                        id="first_name"
                        name="first_name"
                        required
                    >

                </div>

                <div class="form-group">

                    <label for="last_name">
                        Sukunimi
                    </label>

                    <input
                        type="text"
                        id="last_name"
                        name="last_name"
                        required
                    >

                </div>

                <div class="form-group">

                    <label for="username">
                        Käyttäjätunnus
                    </label>

                    <input
                        type="text"
                        id="username"
                        name="username"
                        required
                    >

                </div>

                <div class="form-group">

                    <label for="password">
                        Salasana
                    </label>

                    <input
                        type="password"
                        id="password"
                        name="password"
                        required
                    >

                </div>

                <div class="form-group">

                    <label for="balance">
                        Alkusaldo (€)
                    </label>

                    <input
                        type="number"
                        id="balance"
                        name="balance"
                        min="0"
                        step="0.01"
                        value="0.00"
                        required
                    >

                </div>

                <button type="submit" class="btn">
                    Luo asiakas
                </button>


            </form>

        </div>
    </main>

</body>

</html>