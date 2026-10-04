<?php

session_start();

session_unset();
session_destroy();

?>

<!DOCTYPE html>
<html lang="fi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kirjauduttu ulos</title>
    <link rel="stylesheet" href="style.css?v=20">
</head>

<body class="logout-page">

    <div class="logout-card">

        <img
            src="logout-decoration.png?v=3"
            alt=""
            class="logout-decoration"
        >

        <div class="logout-content">

            <img
                src="cat-logout.png"
                alt="Kissa"
                class="logout-cat"
            >

            <h1>Olet kirjautunut ulos</h1>

            <p>Kiitos, että käytit verkkopankkia.</p>

            <a href="login.php" class="logout-login-button">
                🐾 Kirjaudu uudelleen
            </a>
        </div>
    </div>

</body>
</html>