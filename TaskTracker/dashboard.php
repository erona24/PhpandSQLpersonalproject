<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Require DB connection
require 'config.php';

// Make sure user is logged in
if (!isset($_SESSION['user']) || !isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// Handle adding a task
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['task'])) {
    $task = trim($_POST['task']);
    if ($task) {
        $stmt = $conn->prepare("INSERT INTO tasks (user_id, title, description, due_date, status) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$_SESSION['user_id'], $task, '', NULL, 'pending']);
    }
}

// Handle deleting a task
if (isset($_GET['delete'])) {
    $task_id = $_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM tasks WHERE id=? AND user_id=?");
    $stmt->execute([$task_id, $_SESSION['user_id']]);
}

// Handle marking a task as finished
if (isset($_GET['done'])) {
    $task_id = $_GET['done'];
    $stmt = $conn->prepare("UPDATE tasks SET status='done' WHERE id=? AND user_id=?");
    $stmt->execute([$task_id, $_SESSION['user_id']]);
}

// Fetch all tasks for this user
$stmt = $conn->prepare("SELECT * FROM tasks WHERE user_id=? ORDER BY id DESC");
$stmt->execute([$_SESSION['user_id']]);
$tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>TaskMate Dashboard</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
<style>
body {
    margin: 0;
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, "Noto Sans", sans-serif, "Apple Color Emoji", "Segoe UI Emoji", "Segoe UI Symbol", "Noto Color Emoji";
    font-size: 0.88rem;
    font-weight: 400;
    line-height: 1.5;
    color: #495057;
    text-align: left;

    background-color: #25273e;
}

i {
    font-style: italic;
}

.container{
    margin-top:100px;
}

.card {
    box-shadow: 0 0.46875rem 2.1875rem rgba(4, 9, 20, 0.03), 0 0.9375rem 1.40625rem rgba(4, 9, 20, 0.03), 0 0.25rem 0.53125rem rgba(4, 9, 20, 0.05), 0 0.125rem 0.1875rem rgba(4, 9, 20, 0.03);
    border-width: 0;
    transition: all .2s;
}

.card-header:first-child {
    border-radius: calc(0.25rem - 1px) calc(0.25rem - 1px) 0 0;
}

.card-header {
    display: flex;
    align-items: center;
    border-bottom-width: 1px;
    padding-top: 0;
    padding-bottom: 0;
    padding-right: 0.625rem;
    height: 3.5rem;
    background-color: #fff;
}
.widget-subheading{
    color: #858a8e;
    font-size: 10px;
}
.card-header.card-header-tab .card-header-title {
    display: flex;
    align-items: center;
    white-space: nowrap;
}

.card-header .header-icon {
    font-size: 1.65rem;
    margin-right: 0.625rem;
}

.card-header.card-header-tab .card-header-title {
    display: flex;
    align-items: center;
    white-space: nowrap;
}

.btn-actions-pane-right {
    margin-left: auto;
    white-space: nowrap;
}

.text-capitalize {
    text-transform: capitalize !important;
}

.scroll-area-sm {
    height: 288px;
    overflow-x: hidden;
}

.list-group-item {
    position: relative;
    display: block;
    padding: 0.75rem 1.25rem;
    margin-bottom: -1px;
    background-color: #fff;
    border: 1px solid rgba(0, 0, 0, 0.125);
}

.list-group {
    display: flex;
    flex-direction: column;
    padding-left: 0;
    margin-bottom: 0;
}

.todo-indicator {
    position: absolute;
    width: 4px;
    height: 60%;
    border-radius: 0.3rem;
    left: 0.625rem;
    top: 20%;
    opacity: .6;
    transition: opacity .2s;
}

.bg-warning {
    background-color: #f7b924 !important;
}

.widget-content {
    padding: 1rem;
    flex-direction: row;
    align-items: center;
}

.widget-content .widget-content-wrapper {
    display: flex;
    flex: 1;
    position: relative;
    align-items: center;
}

.widget-content .widget-content-right.widget-content-actions {
    visibility: hidden;
    opacity: 0;
    transition: opacity .2s;
}

.widget-content .widget-content-right {
    margin-left: auto;
}

.btn:not(:disabled):not(.disabled) {
    cursor: pointer;
}

.btn {
    position: relative;
    transition: color 0.15s, background-color 0.15s, border-color 0.15s, box-shadow 0.15s;
}

.btn-outline-success {
    color: #3ac47d;
    border-color: #3ac47d;
}

.btn-outline-success:hover {
    color: #fff;
    background-color: #3ac47d;
    border-color: #3ac47d;
}

.btn-outline-success:hover {
    color: #fff;
    background-color: #3ac47d;
    border-color: #3ac47d;
}
.btn-primary {
    color: #fff;
    background-color: #3f6ad8;
    border-color: #3f6ad8;
}
.btn { 
    position: relative;
    transition: color 0.15s, background-color 0.15s, border-color 0.15s, box-shadow 0.15s;
    outline: none !important;
}

.card-footer{
    background-color: #fff;
}
<?php include 'style.css'; ?>
</style>
</head>
<body>

<div class="container mt-5">
    <h1 class="text-white mb-3">Welcome to <span style="color: #dc3545;">TaskMate</span> <?php echo $_SESSION['user']; ?>!</h1>
    <a href="logout.php" class="btn btn-danger mb-4">Logout</a>

    <div class="row d-flex justify-content-center">
        <div class="col-md-8">
            <div class="card-hover-shadow-2x mb-3 card">
                <div class="card-header-tab card-header">
                    <div class="card-header-title font-size-lg text-capitalize font-weight-normal">
                        <i class="fa fa-tasks"></i>&nbsp;Task Lists
                    </div>
                </div>
                <div class="scroll-area-sm" style="max-height:400px; overflow-y:auto;">
                    <ul class="list-group list-group-flush">
                        <?php if(empty($tasks)) : ?>
                            <li class="list-group-item text-center">No tasks yet!</li>
                        <?php else: ?>
                            <?php foreach($tasks as $t): ?>
                                <li class="list-group-item">
                                    <div class="todo-indicator <?php echo $t['status'] === 'done' ? 'bg-success' : 'bg-warning'; ?>"></div>
                                    <div class="widget-content p-0">
                                        <div class="widget-content-wrapper">
                                            <div class="widget-content-left mr-2">
                                                <div class="custom-checkbox custom-control">
                                                    <input class="custom-control-input" id="task<?php echo $t['id']; ?>" type="checkbox" <?php echo $t['status']==='done' ? 'checked' : ''; ?> disabled>
                                                    <label class="custom-control-label" for="task<?php echo $t['id']; ?>">&nbsp;</label>
                                                </div>
                                            </div>
                                            <div class="widget-content-left flex2">
                                                <div class="widget-heading"><?php echo htmlspecialchars($t['title']); ?></div>
                                            </div>
                                            <div class="widget-content-right">
                                                <?php if($t['status']!=='done'): ?>
                                                <a href="?done=<?php echo $t['id']; ?>" class="btn btn-outline-success btn-sm"><i class="fa fa-check"></i></a>
                                                <?php endif; ?>
                                                <a href="?delete=<?php echo $t['id']; ?>" class="btn btn-outline-danger btn-sm"><i class="fa fa-trash"></i></a>
                                            </div>
                                        </div>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </ul>
                </div>
                <div class="d-block text-right card-footer">
                    <form method="POST" class="d-flex">
                        <input type="text" name="task" class="form-control me-2" placeholder="Add new task" required>
                        <button type="submit" class="btn btn-primary">Add Task</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>
