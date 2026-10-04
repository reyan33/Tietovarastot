<?php

require "yhteys.php";

$first_name = "Reyan";
$last_name = "Ymer";
$username = "admin";
$password = "YOUR_PASSWORD";

$hashed_password = password_hash($password, PASSWORD_DEFAULT);

$sql = "INSERT INTO bank_users (first_name, last_name, username, password, role)
        VALUES (?, ?, ?, ?, 'admin')";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ssss", $first_name, $last_name, $username, $hashed_password);

if ($stmt->execute()) {
    echo "Admin luotu onnistuneesti!";
} else {
    echo "Virhe: " . $stmt->error;
}

$stmt->close();
$conn->close();

?>