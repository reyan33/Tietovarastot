<?php

session_start();
require "yhteys.php";

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "admin") {
    header("Location: login.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["delete_admin"])) {

    $adminId = (int)$_POST["admin_id"];

    if ($adminId === (int)$_SESSION["user_id"]) {
        $error = "Et voi poistaa omaa käyttäjätiliäsi.";
    }

    else {
    $deleteSql = "DELETE FROM bank_users
                  WHERE id = ? AND role = 'admin'";

    $deleteStmt = $conn->prepare($deleteSql);
    $deleteStmt->bind_param("i", $adminId);
    $deleteStmt->execute();
    $deleteStmt->close();

    header("Location: admins_list.php?deleted=1");
    exit;
    }

}

$limit = 5;

$page = isset($_GET["page"]) ? (int)$_GET["page"] : 1;

if ($page < 1) {
    $page = 1;
}

$offset = ($page - 1) * $limit;

$countSql = "SELECT COUNT(*) AS total
             FROM bank_users
             WHERE role = 'admin'";

$countResult = $conn->query($countSql);
$countRow = $countResult->fetch_assoc();

$totalAdmins = $countRow["total"];
$totalPages = ceil($totalAdmins / $limit);

$sql = "SELECT id, first_name, last_name, username
        FROM bank_users
        WHERE role = 'admin'
        ORDER BY id DESC
        LIMIT ? OFFSET ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $limit, $offset);
$stmt->execute();

$result = $stmt->get_result();

?>

<!DOCTYPE html>
<html lang="fi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Järjestelmänvalvojat</title>
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

        <h1>Järjestelmänvalvojat 🐾</h1>

        <p>
            Tarkastele ja hallitse järjestelmänvalvojia.
        </p>

        <?php if (isset($_GET["deleted"]) && $_GET["deleted"] === "1"): ?>

            <div class="message message-success">
                Järjestelmänvalvoja poistettiin onnistuneesti.
            </div>

        <?php endif; ?>

        <?php if (isset($error)): ?>

            <div class="message message-error">
                <?php echo htmlspecialchars($error); ?>
            </div>

        <?php endif; ?>

        <div class="table-container">

            <table>
                <tr>
                    <th>ID</th>
                    <th>Etunimi</th>
                    <th>Sukunimi</th>
                    <th>Käyttäjätunnus</th>
                    <th>Toiminnot</th>
                </tr>

                <?php while ($admin = $result->fetch_assoc()): ?>

                    <tr>
                        <td>
                            <?php echo $admin["id"]; ?>
                        </td>

                        <td>
                            <?php echo htmlspecialchars($admin["first_name"]); ?>
                        </td>

                        <td>
                            <?php echo htmlspecialchars($admin["last_name"]); ?>
                        </td>

                        <td>
                            <?php echo htmlspecialchars($admin["username"]); ?>
                        </td>

                        <td>
                            <?php if ($admin["id"] == $_SESSION["user_id"]): ?>
                                <span>Oma tili</span>

                            <?php else: ?>
                                <form method="POST">
                                    <input
                                        type="hidden"
                                        name="admin_id"
                                        value="<?php echo $admin["id"]; ?>"
                                    >
                                    <button
                                        type="submit"
                                        name="delete_admin"
                                        class="btn btn-danger"
                                    >
                                        Poista
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
                        href="admins_list.php?page=<?php echo $i; ?>"
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