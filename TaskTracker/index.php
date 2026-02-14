<?php
session_start();
require 'config.php';

$message = '';

// ------------------------
// Registration Handling
// ------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['first_name'])) {
        // Sign Up form
        $firstName = trim($_POST['first_name']);
        $lastName  = trim($_POST['last_name']);
        $email     = trim($_POST['email']);
        $password  = $_POST['password'];
        $confirm   = $_POST['confirm_password'];

        if ($firstName && $lastName && $email && $password && $confirm) {
            if ($password === $confirm) {
                // Hash password
                $hashed = password_hash($password, PASSWORD_DEFAULT);

                // Insert into DB
                $stmt = $conn->prepare("INSERT INTO users (first_name,last_name,email,password) VALUES (?, ?, ?, ?)");
                if ($stmt->execute([$firstName, $lastName, $email, $hashed])) {
                    $_SESSION['success'] = "Registered successfully!";
                    header("Location: index.php");
                    exit();
                } else {
                    $message = "Registration failed!";
                }
            } else {
                $message = "Passwords do not match!";
            }
        } else {
            $message = "Please fill all fields!";
        }
    }

    // ------------------------
    // Login Handling
    // ------------------------
    if (isset($_POST['login_email']) && !isset($_POST['first_name'])) {
        $email = trim($_POST['login_email']);
        $password = $_POST['login_password'];

        $stmt = $conn->prepare("SELECT * FROM users WHERE email=?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['success'] = "Logged in successfully!";
            $_SESSION['user'] = $user['username'];
            $_SESSION['user_id'] = $user['id'];

            header("Location: dashboard.php");
            exit();
        } else {
            $message = "Invalid credentials!";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>TaskMate - Login / Register</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
<link rel="stylesheet" href="style.css">
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body>

<section id="login_page_3">
    <div class="container">
        <div class="login">            
            <div class="logo mb-3">
                <h2 style="color:#dc3545;">TaskMate</h2>
            </div>
            <div class="title">
                <h1>Welcome to TaskMate</h1>
                <p>Enter your credentials to access your account</p>
            </div>

            <!-- Display Messages -->
            <?php
            if (isset($_SESSION['success'])) {
                echo "<div class='alert alert-success'>".$_SESSION['success']."</div>";
                unset($_SESSION['success']);
            }
            if (!empty($message)) {
                echo "<div class='alert alert-danger'>$message</div>";
            }
            ?>

            <!-- Sign In Form -->
            <form class="sign_in" action="" method="POST" style="display: block;">
                <div class="email">
                    <i class="bi bi-envelope"></i>
                    <input type="email" placeholder="Enter Your Email" class="form-control" name="login_email" required>
                </div>
                <div class="pass">
                    <i class="bi bi-lock"></i>
                    <input type="password" placeholder="Enter Your password" class="form-control" name="login_password" required>
                </div>
                <div class="d-flex justify-content-between mb-3">
                    <div class="form-check text-start">
                        <input type="checkbox" class="form-check-input" id="exampleCheck1">
                        <label class="form-check-label" for="exampleCheck1">Remember me</label>
                    </div>
                    <div class="reset">
                        <p><a href="#">Reset password</a></p>
                    </div>  
                </div>
                <button type="submit" class="btn btn-danger">Sign In</button>                    
                <p class="sign_up_btn">Don't have an account? <a href="#">Sign Up</a></p>
            </form>
            
            <!-- Sign Up Form -->
            <form class="sign_up" action="" method="POST" style="display: none;">
                <div class="user">
                    <i class="bi bi-person"></i>
                    <input type="text" placeholder="First Name" class="form-control" name="first_name" required>
                </div>
                <div class="user">
                    <i class="bi bi-person"></i>
                    <input type="text" placeholder="Last Name" class="form-control" name="last_name" required>
                </div>
                <div class="email">
                    <i class="bi bi-envelope"></i>
                    <input type="email" placeholder="Enter Your Email" class="form-control" name="email" required>
                </div>
                <div class="pass">
                    <i class="bi bi-lock"></i>
                    <input type="password" placeholder="Create password" class="form-control" name="password" required>
                </div>
                <div class="pass">
                    <i class="bi bi-shield-lock"></i>
                    <input type="password" placeholder="Confirm password" class="form-control" name="confirm_password" required>
                </div>
                <div class="form-check text-start mb-3">
                    <input type="checkbox" class="form-check-input" id="exampleCheck2" required>
                    <label class="form-check-label" for="exampleCheck2">I agree with <a href="#">Privacy and Policy</a></label>
                </div>
                <button type="submit" class="btn btn-danger">Sign Up</button>                    
                <p class="sign_in_btn">Already have an account? <a href="#">Sign In</a></p>                    
            </form>
        </div>
    </div>
</section>

<script src="main.js"></script>
</body>
</html>
