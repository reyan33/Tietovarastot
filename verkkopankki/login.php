<?php

session_start();
require "yhteys.php";

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $username = $_POST["username"];
    $password = $_POST["password"];

    $sql = "SELECT * FROM bank_users
            WHERE username = ?
            AND is_active = 1";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();

    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if ($user && password_verify($password, $user["password"])) {

        $_SESSION["user_id"] = $user["id"];
        $_SESSION["username"] = $user["username"];
        $_SESSION["first_name"] = $user["first_name"];
        $_SESSION["role"] = $user["role"];

        if ($user["role"] === "admin") {
            header("Location: admin.php");
            exit;
        } else {
            header("Location: customer.php");
            exit;
        }

    } else {
        $error = "Väärä käyttäjätunnus tai salasana.";
    }

    $stmt->close();
}

?>

<!DOCTYPE html>
<html lang="fi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kirjaudu - Verkkopankki</title>
    <link rel="stylesheet" href="style.css?v=21">
</head>

<body class="login-page">

    <div class="login-container">

        <div class="login-brand">
            <div class="login-logo">
                <img src="cat.png" alt="Kissa">
            </div>
            <h1>Verkkopankki</h1>
            <p>Turvallinen pankkiasiointi, aina mukanasi.</p>
        </div>

        <div class="login-card">

            <h2>Kirjaudu sisään 🐾</h2>
            <p class="login-text">Tervetuloa takaisin!</p>

            <?php if ($error !== ""): ?>
                <div class="message message-error">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <form method="POST">

                <div class="form-group">
                    <label for="username">Käyttäjätunnus</label>
                    <input
                        type="text"
                        id="username"
                        name="username"
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="password">Salasana</label>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        required
                    >
                </div>

                <button type="submit" class="btn login-btn">
                    Kirjaudu
                </button>

            </form>

        </div>

    </div>

</body>

</html>