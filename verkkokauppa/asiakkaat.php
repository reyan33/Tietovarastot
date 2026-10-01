<!DOCTYPE html>
<html lang="fi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verkkokauppa - Asiakkaat</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>

<div class="container">

    <?php
    require "yhteys.php";

    $sql = "SELECT * FROM customers";
    $result = $conn->query($sql);
    ?>

    <h1>Verkkokauppa</h1>

    <nav>
        <a href="index.php">Tuotteet</a>
        <a href="asiakkaat.php">Asiakkaat</a>
    </nav>

    <hr>

    <h2>Asiakkaat</h2>

    <ul>
        <?php
        while ($row = $result->fetch_assoc()) {
            echo "<li>" . $row["id"] . " - "
                . $row["first_name"] . " "
                . $row["last_name"] . " - "
                . $row["address"] . "</li>";
        }
        ?>
    </ul>

</div>

</body>
</html>