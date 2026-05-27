<?php
$servername = "localhost";
$username = "root";
$password = "cics";
$database = "db_serke_resortsystem_acv";  

$conn = @new mysqli($servername, $username, $password, $database);

if ($conn->connect_error) {
    $password = "";
    $conn = new mysqli($servername, $username, $password, $database);
}
?>