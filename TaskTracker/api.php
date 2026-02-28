<?php
require_once 'config.php';
session_start();
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['user_id'];
$action = $_GET['action'] ?? '';

// Read JSON data from JavaScript Fetch
$input = json_decode(file_get_contents('php://input'), true);

try {
    if ($action === 'fetch') {
        $stmt = $pdo->prepare("SELECT * FROM tasks WHERE user_id = ? ORDER BY id DESC");
        $stmt->execute([$user_id]);
        echo json_encode($stmt->fetchAll());
    } 
    elseif ($action === 'add' && !empty($input['title'])) {
        $stmt = $pdo->prepare("INSERT INTO tasks (user_id, title, priority) VALUES (?, ?, ?)");
        $stmt->execute([$user_id, $input['title'], $input['priority']]);
        echo json_encode(['status' => 'success', 'id' => $pdo->lastInsertId()]);
    } 
    elseif ($action === 'toggle') {
        $stmt = $pdo->prepare("UPDATE tasks SET completed = NOT completed WHERE id = ? AND user_id = ?");
        $stmt->execute([$input['id'], $user_id]);
        echo json_encode(['status' => 'success']);
    } 
    elseif ($action === 'delete') {
        $stmt = $pdo->prepare("DELETE FROM tasks WHERE id = ? AND user_id = ?");
        $stmt->execute([$input['id'], $user_id]);
        echo json_encode(['status' => 'success']);
    }
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>