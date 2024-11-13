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


// Handle adding a new movie
if (isset($_POST['add_movie']) && isset($_FILES['movie_image'])) {
    $name = $conn->real_escape_string($_POST['movie_name']);
    $synopsis = $conn->real_escape_string($_POST['movie_synopsis']);
    $duration = $conn->real_escape_string($_POST['movie_duration']);
    $image = $_FILES['movie_image']['name'];
    $target_dir = "images/";
    $target_file = $target_dir . basename($image);
    $tmp_file = $_FILES['movie_image']['tmp_name'];

    // Check if image file is an actual image or fake image
    $check = getimagesize($tmp_file);
    if ($check !== false) {
        echo "File is an image - " . $check["mime"] . ".";
        // Check if file already exists
        if (!file_exists($target_file)) {
            if (move_uploaded_file($tmp_file, $target_file)) {
                echo "The file " . htmlspecialchars(basename($image)) . " has been uploaded.";
                $sql = "INSERT INTO movies (name, synopsis, duration, image) VALUES ('$name', '$synopsis', '$duration', '$image')";
                if ($conn->query($sql) === TRUE) {
                    echo "New record created successfully";
                } else {
                    echo "Error: " . $sql . "<br>" . $conn->error;
                }
            } else {
                echo "Sorry, there was an error uploading your file.";
            }
        } else {
            echo "Sorry, file already exists.";
        }
    } else {
        echo "File is not an image.";
    }
}

//Edit Movie
if (isset($_GET['edit'])) {
    $id = $conn->real_escape_string($_GET['edit']);
    $result = $conn->query("SELECT * FROM movies WHERE id='$id'");
    if ($result->num_rows > 0) {
        $movie = $result->fetch_assoc();
    } else {
        echo "<script>alert('Movie not found');</script>";
        $movie = null;
    }
}


//Update Movie
if (isset($_POST['update_movie'])) {
    $id = $conn->real_escape_string($_POST['movie_id']);
    $name = $conn->real_escape_string($_POST['movie_name']);
    $synopsis = $conn->real_escape_string($_POST['movie_synopsis']);
    $duration = $conn->real_escape_string($_POST['movie_duration']);
    
    $movieToUpdate = $conn->query("SELECT * FROM movies WHERE id='$id'")->fetch_assoc();
    $oldImage = $movieToUpdate['image'];
    
    if ($_FILES['movie_image']['error'] == UPLOAD_ERR_OK) {
        $image = $_FILES['movie_image']['name'];
        $target_file = "images/" . basename($image);
        if (move_uploaded_file($_FILES['movie_image']['tmp_name'], $target_file)) {
            if ($oldImage && file_exists("images/$oldImage")) {
                unlink("images/$oldImage"); // Delete the old image
            }
        } else {
            $image = $oldImage; // If no new file, keep old file name
        }
    } else {
        $image = $oldImage; // If no new file, keep old file name
    }

    $sql = "UPDATE movies SET name='$name', synopsis='$synopsis', duration='$duration', image='$image' WHERE id='$id'";
    if ($conn->query($sql)) {
        echo "<script>alert('Movie updated successfully');</script>";
    } else {
        echo "<script>alert('Error updating movie: " . $conn->error . "');</script>";
    }
}




//Add to Cart

// Handling the addition to cart
if (isset($_GET['add_to_cart'])) {
    $movie_id = $conn->real_escape_string($_GET['add_to_cart']);
    // Check if cart already exists
    if (isset($_COOKIE['cart'])) {
        $cart = json_decode($_COOKIE['cart'], true);
    } else {
        $cart = [];
    }
    // Add to cart
    if (!in_array($movie_id, $cart)) {
        $cart[] = $movie_id;
        setcookie('cart', json_encode($cart), time() + 86400); // Expires in 1 day
        echo "<script>alert('Movie added to cart');</script>";
    }
}


//Search
// Fetch movies based on the search query
if (isset($_GET['search'])) {
    $search = $conn->real_escape_string($_GET['search']);
    $movies = $conn->query("SELECT * FROM movies WHERE name LIKE '%$search%' OR synopsis LIKE '%$search%'");
} else {
    $movies = $conn->query("SELECT * FROM movies");
}

//Ajax Search

if (isset($_GET['ajax_search'])) {
    $search = $conn->real_escape_string($_GET['ajax_search']);
    $result = $conn->query("SELECT * FROM movies WHERE name LIKE '%$search%' OR synopsis LIKE '%$search%' LIMIT 10");
    $search_results = [];
    while ($row = $result->fetch_assoc()) {
        $search_results[] = $row['name'];  // You can adjust the details you want to send back
    }
    echo json_encode($search_results);
    exit;  // Important to stop further script execution
}
