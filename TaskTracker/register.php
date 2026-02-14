<?php

if ($register_success) {
    $_SESSION['success'] = "Registered successfully!";
    header("Location: login.php");
    exit();
}

?>