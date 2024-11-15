<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);



session_start();
$host = 'localhost'; // Database host
$username = 'root'; // Database username
$password = ''; // Database password
$database = 'Moviesdb'; // Database name



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







//Delete Function
if (isset($_GET['delete'])) {
    $id = $conn->real_escape_string($_GET['delete']);

    // First, fetch the image filename from the database
    $result = $conn->query("SELECT image FROM movies WHERE id='$id'");
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $imageFile = $row['image'];

        // Delete the movie entry from the database
        if ($conn->query("DELETE FROM movies WHERE id='$id'")) {
            // If the database delete is successful, attempt to delete the image file
            $filePath = "images/" . $imageFile;
            if (file_exists($filePath)) {
                if (!unlink($filePath)) {
                    // Log error or inform the user if the file deletion failed
                    echo "<script>alert('Error deleting the image file.');</script>";
                }
            }
        } else {
            echo "<script>alert('Error deleting record from database.');</script>";
        }
    } else {
        echo "<script>alert('Movie not found.');</script>";
    }
}


// Fetch all movies
$movies = $conn->query("SELECT * FROM movies");

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>My movies</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous">
</head>
<body>
<div class="container">
    <nav class="navbar navbar-expand-lg bg-light">
        <div class="container-fluid">
            <a class="navbar-brand" href="index.php">My Movies</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarSupportedContent">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">Home</a>
                    </li>
                    <?php if (!isset($_SESSION['user_id'])): ?>
                    <li class="nav-item">
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#loginModal">Login</button>
                    </li>
                    <li class="nav-item">
                        <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#registerModal">Sign up</button>
                    </li>
                    <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link" href="?logout">Logout</a>
                    </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>


    
    <!-- Login Modal -->
    <div class="modal fade" id="loginModal" tabindex="-1" aria-labelledby="loginModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-5" id="loginModalLabel">Login</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form action="index.php" method="post">
                        <div class="mb-3">
                            <label for="InputEmail" class="form-label">Email address</label>
                            <input type="email" class="form-control" name="InputEmail" aria-describedby="emailHelp" required>
                            <div id="emailHelp" class="form-text">We'll never share your email with anyone else.</div>
                        </div>
                        <div class="mb-3">
                            <label for="InputPassword" class="form-label">Password</label>
                            <input type="password" class="form-control" name="InputPassword" required>
                        </div>
                        <button type="submit" name="login" class="btn btn-primary">Login</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <!-- Register Modal -->
    <div class="modal fade" id="registerModal" tabindex="-1" aria-labelledby="registerModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-5" id="registerModalLabel">Register</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form action="index.php" method="post">
                        <div class="mb-3">
                            <label for="InputSignupEmail" class="form-label">Email address</label>
                            <input type="email" class="form-control" name="InputSignupEmail" aria-describedby="emailHelp" required>
                            <div id="emailHelp" class="form-text">We'll never share your email with anyone else.</div>
                        </div>
                        <div class="mb-3">
                            <label for="InputSignupPassword" class="form-label">Password</label>
                            <input type="password" class="form-control" name="InputSignupPassword" required>
                        </div>
                        <button type="submit" name="register" class="btn btn-primary">Register</button>
                    </form>
                </div>
            </div>
        </div>
    </div>


    
    <!-- Edit Movie Form -->
    <?php if (isset($movie)): ?>
    <div class="modal fade show" id="editMovieModal" style="display: block;" aria-modal="true" role="dialog">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Movie</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" id="closeModalButton"></button>

                </div>
                <div class="modal-body">
                    <form action="index.php" method="post" enctype="multipart/form-data">
                        <input type="hidden" name="movie_id" value="<?= htmlspecialchars($movie['id']); ?>">
                        <div class="mb-3">
                            <label for="movie_name" class="form-label">Movie Name</label>
                            <input type="text" class="form-control" name="movie_name" value="<?= htmlspecialchars($movie['name']); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="movie_synopsis" class="form-label">Synopsis</label>
                            <textarea class="form-control" name="movie_synopsis" required><?= htmlspecialchars($movie['synopsis']); ?></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="movie_duration" class="form-label">Duration (in minutes)</label>
                            <input type="number" class="form-control" name="movie_duration" value="<?= $movie['duration']; ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="movie_image" class="form-label">Movie Image</label>
                            <input type="file" class="form-control" name="movie_image">
                            Current: <img src="images/<?= htmlspecialchars($movie['image']); ?>" style="width: 100px;">
                        </div>
                        <button type="submit" name="update_movie" class="btn btn-primary">Update Movie</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <div class="modal-backdrop fade show"></div>
    <?php endif; ?>


