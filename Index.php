<?php
//Rojina
include 'dbMovie.php';

include 'Auth.php';


// Handle adding a new movie Function
if (isset($_POST['add_movie']) && isset($_FILES['movie_image'])) {
    $name = $conn->real_escape_string($_POST['movie_name']);
    $synopsis = $conn->real_escape_string($_POST['movie_synopsis']);
    $genre = $conn->real_escape_string($_POST['movie_genre']);
    $duration = $conn->real_escape_string($_POST['movie_duration']);
    $image = $_FILES['movie_image']['name'];
    $target_dir = "images/";
    $target_file = $target_dir . basename($image);
    $tmp_file = $_FILES['movie_image']['tmp_name'];

    // Check if image file is an actual image or fake image Function
    $check = getimagesize($tmp_file);
    if ($check !== false) {
        echo "File is an image - " . $check["mime"] . ".";
        // Check if file already exists in target file
        if (!file_exists($target_file)) {
            if (move_uploaded_file($tmp_file, $target_file)) {
                echo "The file " . htmlspecialchars(basename($image)) . " has been uploaded.";

                $sql = "INSERT INTO movies (name, synopsis, duration, image, genre) VALUES ('$name', '$synopsis', '$duration', '$image', '$genre')";

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
       
        header("Location: index.php");  // Redirect to home or another appropriate page
        exit;  // Stop script execution after redirect to login

    
}


//Edit Movie FUnctions
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

//Update Movie Function
if (isset($_POST['update_movie'])) {
    $id = $conn->real_escape_string($_POST['movie_id']);
    $name = $conn->real_escape_string($_POST['movie_name']);
    $synopsis = $conn->real_escape_string($_POST['movie_synopsis']);
    $duration = $conn->real_escape_string($_POST['movie_duration']);
    $genre = $conn->real_escape_string($_POST['movie_genre']);
    
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

    $sql = "UPDATE movies SET name='$name', synopsis='$synopsis', duration='$duration', image='$image', genre='$genre' WHERE id='$id'";


    if ($conn->query($sql)) {
        echo "<script>alert('Movie updated successfully');</script>";
        header("Location: index.php");  // Redirect to home or another appropriate page
        exit;  // Stop script execution after redirect
    } else {
        echo "<script>alert('Error Updating Movie: " . $conn->error . "');</script>";
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
            echo "<script>alert('Error deleting record from Movie database.');</script>";
        }
    } else {
        echo "<script>alert('Movie not found Search Again.');</script>";
    }

    header("Location: index.php");  
    exit;
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
        header("Location: index.php");  
        exit;
    }

    // header("Location: index.php");  
    // exit;
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



// Fetch all movies
// $movies = $conn->query("SELECT * FROM movies");
//HTML starts Here
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>My movies</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous">

    <style>
        .btn-link {
    color: inherit; /* Inherits text color from the button */
    text-decoration: none; /* Removes underline */
    display: inline-block; /* Ensures the link covers the button area if needed */
    width: 100%; /* Makes the link fill the button */
    height: 100%;
    line-height: inherit; /* Adjusts line height to match the button */
}

.btn-link:hover, .btn-link:focus {
    text-decoration: none; /* Ensures no underline on hover/focus */
    color: inherit; /* Optional: ensures the color doesn't change on hover/focus */
}

.search-dropdown ul {   /*search dropdown */
    background: white;
    border: 1px solid #ccc;
    list-style: none;
    padding-left: 0;
    position: absolute;
    width: -100px;
}
.search-dropdown li {
    padding: 5px 10px;
    cursor: pointer;
}
.search-dropdown li:hover {
    background-color: #afc2e0;
}

.synopsis {
    max-width: 300px; /* Set a max-width to control column width */
    word-wrap: break-word; /* Allows the text to wrap to the next line */
}



    </style>

    <!-- style ends here -->
</head>
<body>
<div class="container">
    <nav class="navbar navbar-expand-lg bg-light">
        <div class="container-fluid">
            <a class="navbar-brand" href="index.php">MovieFlix</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarSupportedContent">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item">
                       

                        <li class="nav-item">
                        <button type="button" class="btn btn-success">
    <a href="index.php" class="btn-link">Home</a>
</button>
                    </li>
                        
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

    <!-- Edit Movie Form starts here-->
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
    <label for="movie_genre" class="form-label">Genre</label>
    <select class="form-control" name="movie_genre" required>
        <option value="">Select a Genre</option>
        <option value="Action" <?= $movie['genre'] == 'Action' ? 'selected' : '' ?>>Action</option>
        <option value="Comedy" <?= $movie['genre'] == 'Comedy' ? 'selected' : '' ?>>Comedy</option>
        <option value="Drama" <?= $movie['genre'] == 'Drama' ? 'selected' : '' ?>>Drama</option>
        <option value="Fantasy" <?= $movie['genre'] == 'Fantasy' ? 'selected' : '' ?>>Fantasy</option>
        <option value="Horror" <?= $movie['genre'] == 'Horror' ? 'selected' : '' ?>>Horror</option>
        <option value="Romance" <?= $movie['genre'] == 'Romance' ? 'selected' : '' ?>>Romance</option>
        <option value="Thriller" <?= $movie['genre'] == 'Thriller' ? 'selected' : '' ?>>Thriller</option>
    </select>
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
<!-- MOvie Drop down ends here -->
<!-- GitHub Commited -->

    <!-- Movies Listing and CRUD -->
    <div class="card m-1">
    <div class="card-header d-flex justify-content-between align-items-center">
    <span>List of Films</span>
    <?php if (isset($_SESSION['user_id'])): ?>
    <div>
        <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addMovieModal">Add Movie</button>
        <button type="button" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#cartModal">View Cart</button>
    </div>


    <?php endif; ?>
    <div>
    <form class="d-inline-flex ms-2" action="index.php" method="get">
        <input class="form-control me-2" type="search" placeholder="Search movies" name="search" id="searchInput" aria-label="Search" required>
        <button class="btn btn-primary" type="submit">Search</button>
    </form>
    <div class="search-dropdown">
    <ul id="searchResults"></ul>
</div>
</div>



<!-- Body Card Start here -->
</div> 
        <div class="card-body">
            <table class="table table-striped table-responsive table-sm">
                <thead>
                    <tr>
                        <th>Image</th>
                        <th>Movie Name</th>
                        <th>Synopsis</th>
                        <th>Genre</th>

                        <th>Duration</th>
                        <?php if (isset($_SESSION['user_id'])): ?>
                        <th>Actions</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($movie = $movies->fetch_assoc()): ?>
                    <tr>
                        <td><img style="width: 200px; height: auto;" src="images/<?= htmlspecialchars($movie['image']); ?>" alt="<?= htmlspecialchars($movie['name']); ?>"></td>
                        <td><?= htmlspecialchars($movie['name']); ?></td>
                        <td><div class="synopsis"><?= htmlspecialchars($movie['synopsis']); ?></div></td>

                        <td><?= htmlspecialchars($movie['genre']); ?></td>

                        <td><?= htmlspecialchars($movie['duration']); ?> min</td>
                        <?php if (isset($_SESSION['user_id'])): ?>
                        <td>
                            <a href="?add_to_cart=<?= $movie['id']; ?>" class="btn btn-success">Add to Cart</a>
                            <a href="?edit=<?= $movie['id']; ?>" class="btn btn-primary">Edit</a>
                            <a href="?delete=<?= $movie['id']; ?>" class="btn btn-danger" onclick="return confirm('Are you sure?')">Delete</a>

                        </td>
                        <?php endif; ?>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
<!-- body card end here -->
      <!-- Cart Modal -->
      <div class="modal fade" id="cartModal" tabindex="-1" aria-labelledby="cartModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="cartModalLabel">Your Cart</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <?php
                    if (isset($_COOKIE['cart'])) {
                        $cart = json_decode($_COOKIE['cart'], true);
                        foreach ($cart as $id) {
                            $result = $conn->query("SELECT name FROM movies WHERE id='$id'");
                            if ($movie = $result->fetch_assoc()) {
                                echo "<p>" . htmlspecialchars($movie['name']) . "</p>";
                            }
                        }
                    }
                    ?>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" onclick="purchase()">Purchase</button>
                </div>
            </div>
        </div>
    </div>


    

    <!-- Add Movie Modal -->
    <div class="modal fade" id="addMovieModal" tabindex="-1" aria-labelledby="addMovieModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-5" id="addMovieModalLabel">Add Movie</h1>
                    <button type of="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form action="index.php" method="post" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label for="movie_name" class="form-label">Movie Name</label>
                            <input type="text" class="form-control" name="movie_name" required>
                        </div>
                        <div class="mb-3">
                            <label for="movie_synopsis" class="form-label">Synopsis</label>
                            <textarea class="form-control" name="movie_synopsis" required></textarea>
                        </div>
                        

                        <div class="mb-3">
    <label for="movie_genre" class="form-label">Genre</label>
    <select class="form-control" name="movie_genre" required>
        <option value="">Select a Genre</option>
        <option value="Action">Action</option>
        <option value="Comedy">Comedy</option>
        <option value="Drama">Drama</option>
        <option value="Fantasy">Fantasy</option>
        <option value="Horror">Horror</option>
        <option value="Romance">Romance</option>
        <option value="Thriller">Thriller</option>
    </select>
</div>


                        
                        <div class="mb-3">
                            <label for="movie_duration" class="form-label">Duration</label>
                            <input type="number" class="form-control" name="movie_duration" required>
                        </div>
                        <div class="mb-3">
                            <label for="movie_image" class="form-label">Movie Image</label>
                            <input type="file" class="form-control" name="movie_image" required>
                        </div>
                        <button type="submit" name="add_movie" class="btn btn-success">Add Movie</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/js/bootstrap.bundle.min.js" integrity="sha384-HwwvtgBNo3bZJJLYd8oVXjrBZt8cqVSpeBNS5n7C8IVInixGAoxmnlMuBnhbgrkm" crossorigin="anonymous"></script>


<script>
    //This is for Close Button Popup
document.getElementById('closeModalButton').addEventListener('click', function() {
    var modal = document.getElementById('editMovieModal');
    modal.style.display = 'none'; // Hide the modal
    var backdrop = document.querySelector('.modal-backdrop'); // Remove the backdrop
    if (backdrop) {
        backdrop.style.display = 'none';
    }
});

//Cart COOKIE
function purchase() {
    alert('Purchase successful!');
    document.cookie.split(';').forEach(cookie => {
        const eqPos = cookie.indexOf('=');
        const name = 'cart'
        document.cookie = name + '=;expires=Thu, 01 Jan 1970 00:00:00 GMT';
    });
}
</script>

<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>


<!-- AAAAJJJJAAAAAAXXXXXXX -->
<script>
//AJAX CODE
//ajax code starts here
$(document).ready(function() {
    $('#searchInput').on('keyup', function() {
        var query = $(this).val();
        if (query.length > 1) {
            $.ajax({
                url: 'index.php',
                method: 'GET',
                data: {ajax_search: query},
                success: function(data) {
                    let results = JSON.parse(data);
                    $('#searchResults').empty();  // Clear previous results
                    $.each(results, function(index, value) {
                        // Create list items as links
                        $('#searchResults').append($('<li>').text(value).on('click', function() {
                            $('#searchInput').val($(this).text());  // Fill the search box with the clicked value
                            $('#searchResults').empty();  // Optionally clear the list
                        }));
                    });
                }
            });
        } else {
            $('#searchResults').empty();  // Clear results if less than 2 characters
        }
    });
});

</script>
</body>
</html>


