<?php

session_start();
require "yhteys.php";

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "admin") {
    header("Location: login.php");
    exit;
}

$message = "";
$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $first_name = isset($_POST["first_name"])
        ? trim($_POST["first_name"])
        : "";

    $last_name = isset($_POST["last_name"])
        ? trim($_POST["last_name"])
        : "";

    $username = isset($_POST["username"])
        ? trim($_POST["username"])
        : "";

    $password = isset($_POST["password"])
        ? $_POST["password"]
        : "";

if (
    $first_name === "" &&
    $last_name === "" &&
    $username === "" &&
    $password === ""
) {
    $error = "Täytä kaikki kentät.";
} elseif ($first_name === "") {
    $error = "Etunimi on pakollinen.";
} elseif ($last_name === "") {
    $error = "Sukunimi on pakollinen.";
} elseif ($username === "") {
    $error = "Käyttäjätunnus on pakollinen.";
} elseif ($password === "") {
    $error = "Salasana on pakollinen.";
}

if ($error === "") {

    $checkSql = "SELECT id
                 FROM bank_users
                 WHERE username = ?";

    $checkStmt = $conn->prepare($checkSql);
    $checkStmt->bind_param("s", $username);
    $checkStmt->execute();

    $checkResult = $checkStmt->get_result();

    if ($checkResult->num_rows > 0) {
        $error = "Käyttäjätunnus on jo käytössä.";
    }

    $checkStmt->close();
}

if ($error === "") {

    $passwordHash = password_hash($password, PASSWORD_DEFAULT);

    $insertSql = "INSERT INTO bank_users
                  (first_name, last_name, username, password, role)
                  VALUES (?, ?, ?, ?, 'admin')";

    $insertStmt = $conn->prepare($insertSql);
    $insertStmt->bind_param(
        "ssss",
        $first_name,
        $last_name,
        $username,
        $passwordHash
    );

    $insertStmt->execute();
    $insertStmt->close();

    $message = "Järjestelmänvalvoja luotiin onnistuneesti.";
}

}

?>

<!DOCTYPE html>
<html lang="fi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Luo järjestelmänvalvoja</title>
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
                <a href="customers_list.php">Asiakkaat</a>
                <a href="account_requests.php">Pankkitilipyynnöt</a>
                <a href="admins_list.php">Järjestelmänvalvojat</a>
                <a href="logout.php" class="logout-link">Kirjaudu ulos</a>
            </div>

        </div>
    </nav>

    <main class="container">

        <h1>Luo järjestelmänvalvoja 🐾</h1>

        <p>
            Luo uusi järjestelmänvalvojan käyttäjätili.
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

            <h2>Järjestelmänvalvojan tiedot</h2>

            <form method="POST">

                <div class="form-group">
                    <label for="first_name">
                        Etunimi
                    </label>

                    <input
                        type="text"
                        id="first_name"
                        name="first_name"
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
                    >
                </div>

                <button type="submit" class="btn">
                    Luo järjestelmänvalvoja
                </button>
            </form>
        </div>
    </main>

</body>

</html>