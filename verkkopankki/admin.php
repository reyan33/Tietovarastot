<?php

session_start();
require "yhteys.php";

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "admin") {
    header("Location: login.php");
    exit;
}

?>

<!DOCTYPE html>
<html lang="fi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ylläpito - Verkkopankki</title>
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

    <main class="container admin-dashboard">

        <div class="dashboard-heading">

            <h1>
                Tervetuloa,
                <?php echo htmlspecialchars($_SESSION["first_name"]); ?>! 🐾
            </h1>

            <p>Olet kirjautunut järjestelmänvalvojana.</p>

        </div>

        <h2>Toiminnot</h2>

        <div class="admin-actions">

            <a href="create_customer.php" class="admin-action-card">

                <div class="admin-action-icon">👤</div>

                <div>
                    <h3>Luo uusi asiakas</h3>
                    <p>Luo asiakkaalle käyttäjä ja oletuspankkitili.</p>
                </div>

            </a>

            <a href="customers_list.php" class="admin-action-card">

                <div class="admin-action-icon">👥</div>

                <div>
                    <h3>Asiakkaat</h3>
                    <p>Tarkastele asiakkaiden tietoja ja pankkitilejä.</p>
                </div>

            </a>

            <a href="account_requests.php" class="admin-action-card">

                <div class="admin-action-icon">🏦</div>

                <div>
                    <h3>Pankkitilipyynnöt</h3>
                    <p>Hyväksy pankkitilien luomis- ja poistopyyntöjä.</p>
                </div>

            </a>

            <a href="create_admin_user.php" class="admin-action-card">

                <div class="admin-action-icon">➕</div>

                <div>
                    <h3>Luo järjestelmänvalvoja</h3>
                    <p>Luo uusi järjestelmänvalvojan käyttäjätili.</p>
                </div>

            </a>

            <a href="admins_list.php" class="admin-action-card">

                <div class="admin-action-icon">⚙️</div>

                <div>
                    <h3>Järjestelmänvalvojat</h3>
                    <p>Tarkastele ja hallitse järjestelmänvalvojia.</p>
                </div>

            </a>

        </div>

    </main>

</body>

</html>