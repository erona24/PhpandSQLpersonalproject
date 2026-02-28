<?php
require_once 'config.php';
session_start();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'];
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    if ($action === 'register') {
        // Check if username exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->fetch()) {
            $error = "Username already taken!";
        } else {
            // Hash password and save
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
            $stmt->execute([$username, $hashed]);
            $_SESSION['user_id'] = $pdo->lastInsertId();
            $_SESSION['user_name'] = $username;
            header("Location: index.php");
            exit;
        }
    } 
    
    if ($action === 'login') {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['username'];
            header("Location: index.php");
            exit;
        } else {
            $error = "Invalid username or password.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>TaskMate | Sign In</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; background: linear-gradient(135deg, #6A5AE0, #9B84E5); min-height: 100vh; display: flex; align-items: center; justify-content: center; }
        .auth-card { background: white; padding: 40px; border-radius: 30px; box-shadow: 0 20px 60px rgba(0,0,0,0.2); width: 100%; max-width: 400px; }
        .btn-primary { background: #6A5AE0; border: none; padding: 12px; border-radius: 12px; font-weight: 700; }
        .toggle-link { color: #6A5AE0; cursor: pointer; font-weight: 600; text-decoration: none; }
    </style>
</head>
<body>

<div class="auth-card">
    <h2 class="text-center fw-800 mb-4" id="formTitle">Welcome Back</h2>
    
    <?php if($error): ?>
        <div class="alert alert-danger p-2 small"><?php echo $error; ?></div>
    <?php endif; ?>

    <form method="POST">
        <input type="hidden" name="action" id="formAction" value="login">
        <div class="mb-3">
            <label class="form-label small fw-bold">Username</label>
            <input type="text" name="username" class="form-control rounded-3" required>
        </div>
        <div class="mb-4">
            <label class="form-label small fw-bold">Password</label>
            <input type="password" name="password" class="form-control rounded-3" required>
        </div>
        <button type="submit" class="btn btn-primary w-100 mb-3" id="submitBtn">Sign In</button>
    </form>
    
    <p class="text-center small">
        <span id="toggleText">Don't have an account?</span> 
        <a class="toggle-link" onclick="toggleAuth()">Create One</a>
    </p>
</div>

<script>
function toggleAuth() {
    const title = document.getElementById('formTitle');
    const action = document.getElementById('formAction');
    const btn = document.getElementById('submitBtn');
    const text = document.getElementById('toggleText');
    const link = document.querySelector('.toggle-link');

    if (action.value === 'login') {
        title.innerText = "Join TaskMate";
        action.value = "register";
        btn.innerText = "Register Account";
        text.innerText = "Already have an account?";
        link.innerText = "Sign In";
    } else {
        title.innerText = "Welcome Back";
        action.value = "login";
        btn.innerText = "Sign In";
        text.innerText = "Don't have an account?";
        link.innerText = "Create One";
    }
}
</script>
</body>
</html>