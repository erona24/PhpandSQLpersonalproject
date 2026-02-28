<?php
require_once 'config.php';
session_start();

// Simple Login Simulation (For this setup, we auto-login to User ID 1)
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1; 
    $_SESSION['user_name'] = 'Admin User';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>TaskMate Pro | Website</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root { --p-purple: #6A5AE0; --bg: #f4f7fe; }
        body { font-family: 'Plus Jakarta Sans', sans-serif; background: var(--bg); }
        .header { background: linear-gradient(135deg, #6A5AE0, #9B84E5); padding: 60px 0 140px; color: white; }
        .stats-card { background: rgba(255,255,255,0.1); backdrop-filter: blur(10px); border-radius: 20px; padding: 20px; border: 1px solid rgba(255,255,255,0.2); }
        .main-container { max-width: 800px; margin: -80px auto 50px; background: white; border-radius: 30px; padding: 40px; box-shadow: 0 20px 40px rgba(0,0,0,0.05); }
        .task-row { display: flex; align-items: center; justify-content: space-between; padding: 15px; border: 1px solid #eee; border-radius: 15px; margin-bottom: 10px; transition: 0.2s; }
        .task-row:hover { border-color: var(--p-purple); transform: translateX(5px); }
        .completed { text-decoration: line-through; opacity: 0.5; }
        .priority-dot { width: 10px; height: 10px; border-radius: 50%; display: inline-block; margin-right: 10px; }
        .high { background: #ff7675; } .medium { background: #fdcb6e; } .low { background: #55efc4; }
    </style>
</head>
<body>

<div class="header text-center">
    <h1>TaskMate Dashboard</h1>
    <p>Logged in as <b><?php echo $_SESSION['user_name']; ?></b></p>
    <div class="container d-flex justify-content-center gap-3 mt-4">
        <div class="stats-card"> <h2 id="totalCount">0</h2> <small>TOTAL</small> </div>
        <div class="stats-card"> <h2 id="pendingCount">0</h2> <small>PENDING</small> </div>
    </div>
</div>

<div class="main-container">
    <div class="input-group mb-4 p-2 bg-light rounded-4">
        <input type="text" id="taskTitle" class="form-control border-0 bg-transparent" placeholder="Enter new task...">
        <select id="taskPriority" class="form-select border-0 bg-transparent fw-bold" style="max-width: 120px;">
            <option value="high">High</option>
            <option value="medium" selected>Medium</option>
            <option value="low">Low</option>
        </select>
        <button class="btn btn-primary rounded-3 px-4" onclick="addTask()">Add</button>
    </div>

    <div id="taskList">
        <!-- Tasks will load here automatically -->
    </div>
</div>

<script>
async function loadTasks() {
    const res = await fetch('api.php?action=fetch');
    const tasks = await res.json();
    
    const list = document.getElementById('taskList');
    list.innerHTML = '';
    
    document.getElementById('totalCount').innerText = tasks.length;
    document.getElementById('pendingCount').innerText = tasks.filter(t => t.completed == 0).length;

    tasks.forEach(t => {
        const div = document.createElement('div');
        div.className = 'task-row';
        div.innerHTML = `
            <div>
                <span class="priority-dot ${t.priority}"></span>
                <span class="fw-bold ${t.completed == 1 ? 'completed' : ''}">${t.title}</span>
            </div>
            <div>
                <button class="btn btn-sm btn-light text-success" onclick="toggleTask(${t.id})"><i class="bi bi-check-circle-fill"></i></button>
                <button class="btn btn-sm btn-light text-danger" onclick="deleteTask(${t.id})"><i class="bi bi-trash-fill"></i></button>
            </div>
        `;
        list.appendChild(div);
    });
}

async function addTask() {
    const title = document.getElementById('taskTitle').value;
    const priority = document.getElementById('taskPriority').value;
    if (!title) return;

    await fetch('api.php?action=add', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ title, priority })
    });

    document.getElementById('taskTitle').value = '';
    loadTasks();
}

async function toggleTask(id) {
    await fetch('api.php?action=toggle', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id })
    });
    loadTasks();
}

async function deleteTask(id) {
    if (!confirm('Delete this task?')) return;
    await fetch('api.php?action=delete', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id })
    });
    loadTasks();
}

// Initial load
loadTasks();
</script>

</body>
</html>