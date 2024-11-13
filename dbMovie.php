<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);



session_start();
$host = 'localhost'; // Database host
$username = 'db2237880'; // Database username
$password = 'qwerty12@@'; // Database password
$database = 'db2237880'; // Database name


// $server = "localhost";
// $username = "db2237880";
// $password = "qwerty12@@";
// $database = "shop";



$conn = new mysqli($host, $username, $password, $database);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
