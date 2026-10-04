<?php

session_start();
require "yhteys.php";

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "admin") {
    header("Location: login.php");
    exit;
}

$limit = 5;

$page = isset($_GET["page"]) ? (int)$_GET["page"] : 1;

if ($page < 1) {
    $page = 1;
}

$offset = ($page - 1) * $limit;


// Count all customers
$countSql = "SELECT COUNT(*) AS total
             FROM bank_users
             WHERE role = 'customer'
             AND is_active = 1";

$countResult = $conn->query($countSql);
$countRow = $countResult->fetch_assoc();

$totalCustomers = $countRow["total"];

$totalPages = ceil($totalCustomers / $limit);


// Get customers for the current page
$sql = "SELECT id, first_name, last_name, username
        FROM bank_users
        WHERE role = 'customer'
        AND is_active = 1
        ORDER BY id DESC
        LIMIT ? OFFSET ?";

$stmt = $conn->prepare($sql);

$stmt->bind_param(
    "ii",
    $limit,
    $offset
);

$stmt->execute();

$result = $stmt->get_result();

?>

<!DOCTYPE html>
<html lang="fi">

<head>
    <meta charset="UTF-8">
    <title>Asiakaslista</title>
    <link rel="stylesheet" href="style.css?v=9">
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

        <h1>Asiakkaat 🐾</h1>

        <p>
            Tarkastele asiakkaiden tietoja ja pankkitilejä.
        </p>

        <div class="table-container">

            <table>

                <tr>
                    <th>ID</th>
                    <th>Etunimi</th>
                    <th>Sukunimi</th>
                    <th>Käyttäjätunnus</th>
                    <th>Toiminnot</th>
                </tr>

                <?php while ($customer = $result->fetch_assoc()): ?>

                    <tr>

                        <td>
                            <?php echo $customer["id"]; ?>
                        </td>

                        <td>
                            <?php
                            echo htmlspecialchars(
                                $customer["first_name"]
                            );
                            ?>
                        </td>

                        <td>
                            <?php
                            echo htmlspecialchars(
                                $customer["last_name"]
                            );
                            ?>
                        </td>

                        <td>
                            <?php
                            echo htmlspecialchars(
                                $customer["username"]
                            );
                            ?>
                        </td>

                        <td>

                            <a
                                href="customer_details.php?id=<?php
                                echo $customer["id"];
                                ?>"
                            >
                                Näytä tiedot
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
                        href="customers_list.php?page=<?php echo $i; ?>"
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