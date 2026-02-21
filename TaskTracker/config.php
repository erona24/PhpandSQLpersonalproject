<?php
$server = "localhost";
$user   = "root";
$pass   = "";
$dbname = "task_tracker";

try {
    $conn = new PDO("mysql:host=$server;dbname=$dbname;charset=utf8mb4", $user, $pass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?>