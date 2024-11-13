<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);



session_start();
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

// Handle login
if (isset($_POST['login'])) {
    $email = $conn->real_escape_string($_POST['InputEmail']);
    $password = $conn->real_escape_string($_POST['InputPassword']);
    $result = $conn->query("SELECT * FROM users WHERE email='$email'");
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_email'] = $user['email'];
        } else {
            echo "<script>alert('Invalid credentials');</script>";
        }
    } else {
        echo "<script>alert('No user found');</script>";
    }
}

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    unset($_SESSION['user_id']);
    header('Location: index.php');
    exit;
}

// Handle registration
if (isset($_POST['register'])) {
    $email = $conn->real_escape_string($_POST['InputSignupEmail']);
    $password = password_hash($conn->real_escape_string($_POST['InputSignupPassword']), PASSWORD_DEFAULT);
    $conn->query("INSERT INTO users (email, password) VALUES ('$email', '$password')");
}