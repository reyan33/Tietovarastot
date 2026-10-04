<?php

$host = "localhost";
$database = "25p_6937";
$username = "25p_6937";
$password = "B@jDNkpPQ)_IzgOn";

$conn = new mysqli($host, $username, $password, $database);

if ($conn->connect_error) {
    die("Tietokantayhteys epäonnistui.");
}

?>