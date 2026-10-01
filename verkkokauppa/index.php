<!DOCTYPE html>
<html lang="fi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verkkokauppa - Tuotteet</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>

<div class="container">

    <?php
    require "yhteys.php";

    $sql = "SELECT * FROM products";
    $result = $conn->query($sql);
    ?>

    <h1>Verkkokauppa</h1>

    <nav>
        <a href="index.php">Tuotteet</a>
        <a href="asiakkaat.php">Asiakkaat</a>
    </nav>

    <hr>

    <h2>Tuotteet</h2>

    <ul>
        <?php
        while ($row = $result->fetch_assoc()) {
            echo "<li>" . $row["id"] . " - "
                . $row["name"] . " - "
                . $row["price"] . " €</li>";
        }
        ?>
    </ul>

</div>

</body>
</html>