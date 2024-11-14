<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);



session_start();
// $host = 'mi-linux.wlv.ac.uk'; // Database host
// $username = '2237880'; // Database username
// $password = 'Qwerty1234@@'; // Database password
// $database = 'db2237880'; // Database name



$host = 'localhost'; // Database host
$username = 'root'; // Database username
$password = ''; // Database password
$database = 'Moviesdb'; // Database name



// $server = "localhost";
// $username = "db2237880";
// $password = "qwerty12@@";
// $database = "shop";



$conn = new mysqli($host, $username, $password, $database);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
