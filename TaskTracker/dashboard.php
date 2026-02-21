<?php
session_start();
require 'config.php';

if(!isset($_SESSION['user_id'])){
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];


// ADD TASK
if(isset($_POST['add_task'])){
    $title = $_POST['title'];
    $desc  = $_POST['description'];
    $date  = $_POST['due_date'];

    $stmt = $conn->prepare("
        INSERT INTO tasks(user_id,title,description,due_date)
        VALUES(?,?,?,?)
    ");
    $stmt->execute([$user_id,$title,$desc,$date]);
}


// DELETE TASK
if(isset($_GET['delete'])){
    $stmt = $conn->prepare("DELETE FROM tasks WHERE id=? AND user_id=?");
    $stmt->execute([$_GET['delete'],$user_id]);
}


// FETCH TASKS
$stmt = $conn->prepare("SELECT * FROM tasks WHERE user_id=? ORDER BY id DESC");
$stmt->execute([$user_id]);
$tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html>
<head>
<title>Dashboard</title>
<link rel="stylesheet" href="style.css">
</head>

<body>

<h2>Welcome <?php echo $_SESSION['user']; ?> 👋</h2>

<a href="logout.php">Logout</a>

<hr>

<h3>Add Task</h3>

<form method="POST">
<input type="text" name="title" placeholder="Task title" required>
<br><br>

<textarea name="description" placeholder="Description"></textarea>
<br><br>

<input type="date" name="due_date">
<br><br>

<button name="add_task">Add Task</button>
</form>

<hr>

<h3>Your Tasks</h3>

<?php foreach($tasks as $task): ?>

<div style="border:1px solid #ccc; padding:10px; margin-bottom:10px;">
<b><?php echo $task['title']; ?></b><br>
<?php echo $task['description']; ?><br>
Due: <?php echo $task['due_date']; ?><br>

<a href="?delete=<?php echo $task['id']; ?>">Delete</a>
</div>

<?php endforeach; ?>

</body>
</html>