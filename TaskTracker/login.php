<?php

if ($login_success) {
    $_SESSION['success'] = "Logged in successfully!";
    header("Location: dashboard.php");
    exit();
}


?>