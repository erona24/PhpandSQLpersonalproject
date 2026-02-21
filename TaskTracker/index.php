<?php
session_start();
require 'config.php';
$message = '';
$total_tasks = 0;

// Handle task status update
if (isset($_POST['new_status'])) {
    $taskId = $_POST['task_id'];
    $newStatus = $_POST['new_status'];
    $stmt = $conn->prepare("UPDATE tasks SET status=? WHERE id=? AND user_id=?");
    $stmt->execute([$newStatus, $taskId, $_SESSION['user_id']]);
    header("Location: index.php"); exit();
}

// Handle Add Task
if (isset($_POST['add_task'])) {
    $title = trim($_POST['title']);
    $due = $_POST['due_date'];
    $priority = $_POST['priority'];
    $status = $_POST['status'] ?? 'pending';
    if ($title && $due && $priority) {
        $stmt = $conn->prepare("INSERT INTO tasks (user_id, title, due_date, priority, status, created_at) VALUES (?,?,?,?,?,NOW())");
        $stmt->execute([$_SESSION['user_id'], $title, $due, $priority, $status]);
        header("Location: index.php"); exit();
    }
}

// Handle Edit Task
if (isset($_POST['update_task'])) {
    $id = $_POST['task_id'];
    $title = trim($_POST['title']);
    $due = $_POST['due_date'];
    $priority = $_POST['priority'];
    $status = $_POST['status'];
    $stmt = $conn->prepare("UPDATE tasks SET title=?, due_date=?, priority=?, status=? WHERE id=? AND user_id=?");
    $stmt->execute([$title, $due, $priority, $status, $id, $_SESSION['user_id']]);
    header("Location: index.php"); exit();
}

// Handle Delete Task
if (isset($_POST['delete_task'])) {
    $id = $_POST['task_id'];
    $stmt = $conn->prepare("DELETE FROM tasks WHERE id=? AND user_id=?");
    $stmt->execute([$id, $_SESSION['user_id']]);
    header("Location: index.php"); exit();
}

// Fetch tasks
$tasks = $inProgressTasks = $pendingTasks = $doneTasks = [];
if (isset($_SESSION['user_id'])) {
    $stmt = $conn->prepare("SELECT * FROM tasks WHERE user_id=? ORDER BY due_date ASC");
    $stmt->execute([$_SESSION['user_id']]);
    $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($tasks as $t) {
        switch ($t['status']) {
            case 'in_progress': $inProgressTasks[] = $t; break;
            case 'pending': $pendingTasks[] = $t; break;
            case 'done': $doneTasks[] = $t; break;
        }
    }
    $total_tasks = count($tasks);
}

// Render tasks function
function renderTasks($tasksArray) {
    if (empty($tasksArray)) {
        echo '<div class="text-center py-12 text-slate-400 bg-slate-50 rounded-3xl border-2 border-dashed border-slate-200">No tasks in this category.</div>';
        return;
    }
    foreach($tasksArray as $row):
        $priority = strtolower($row['priority'] ?? 'low');
        $priorityStyles = [
            'high' => 'border-red-500 bg-red-50 text-red-700',
            'medium' => 'border-orange-400 bg-orange-50 text-orange-700',
            'low' => 'border-green-500 bg-green-50 text-green-700'
        ];
        $currentStyle = $priorityStyles[$priority] ?? $priorityStyles['low'];
        $isDone = ($row['status'] === 'done');
?>
<div class="flex flex-col md:flex-row md:items-center justify-between p-5 border-l-8 rounded-2xl shadow-sm mb-4 transition-all hover:shadow-md hover:-translate-y-0.5 <?php echo $currentStyle; ?>">
    <div class="flex-1">
        <h3 class="text-lg font-bold <?php echo $isDone ? 'line-through opacity-40' : ''; ?>">
            <?php echo htmlspecialchars($row['title']); ?>
        </h3>
        <div class="text-[11px] opacity-70 font-bold uppercase tracking-wider mt-1">
            <i class="bi bi-calendar3 mr-1"></i> Due: <?php echo date('M d, Y', strtotime($row['due_date'])); ?> 
            <span class="mx-2">|</span>
            <i class="bi bi-clock-history mr-1"></i> Priority: <?php echo ucfirst($priority); ?>
        </div>
    </div>
    <div class="flex items-center gap-2 mt-4 md:mt-0">
        <form method="POST" class="inline">
            <input type="hidden" name="task_id" value="<?php echo $row['id']; ?>">
            <input type="hidden" name="new_status" value="<?php echo $isDone ? 'pending' : 'done'; ?>">
            <button type="submit" class="bg-emerald-600 hover:bg-emerald-700 text-white text-[10px] font-black px-4 py-2.5 rounded-xl transition shadow-sm">
                <?php echo $isDone ? 'UNDO' : 'COMPLETE'; ?>
            </button>
        </form>
        <button onclick="showEditForm(<?php echo $row['id']; ?>,'<?php echo htmlspecialchars($row['title']); ?>','<?php echo $row['due_date']; ?>','<?php echo $row['priority']; ?>','<?php echo $row['status']; ?>')" 
                class="bg-amber-400 hover:bg-amber-500 text-amber-900 text-[10px] font-black px-4 py-2.5 rounded-xl transition shadow-sm">
            EDIT
        </button>
        <form method="POST" class="inline" onsubmit="return confirm('Permanently delete this task?');">
            <input type="hidden" name="task_id" value="<?php echo $row['id']; ?>">
            <button type="submit" name="delete_task" class="bg-red-500 hover:bg-red-600 text-white text-[10px] font-black px-4 py-2.5 rounded-xl transition shadow-sm">
                DELETE
            </button>
        </form>
        <div class="ml-4 text-[10px] font-black uppercase tracking-widest opacity-60"><?php echo $priority; ?></div>
    </div>
</div>
<?php endforeach; } ?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CloudTask Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap');
        body { font-family: 'Plus Jakarta Sans', sans-serif; background: linear-gradient(135deg, #6366f1 0%, #a855f7 50%, #ec4899 100%); background-attachment: fixed; min-height: 100vh; margin: 0; padding: 20px; }
        .main-card { background: white; border-radius: 2.5rem; box-shadow: 0 30px 60px -12px rgba(0, 0, 0, 0.3); overflow: hidden; }
        .header-gradient { background: linear-gradient(to bottom right, #4f46e5, #9333ea); }
        input, select { border: 1.5px solid #e2e8f0 !important; transition: all 0.3s; }
        input:focus, select:focus { border-color: #a855f7 !important; box-shadow: 0 0 0 4px rgba(168, 85, 247, 0.1) !important; outline: none; }
        .active-tab { background-color: #f1f5f9; color: #475569 !important; box-shadow: inset 0 2px 4px rgba(0,0,0,0.05); }
    </style>
</head>
<body class="p-4 md:p-12 flex justify-center">

<div class="w-full max-w-4xl main-card">
    <?php if(isset($_SESSION['user_id'])): ?>
    
    <!-- HEADER SECTION -->
    <div class="header-gradient p-10 text-white">
        <div class="flex justify-between items-start mb-12">
            <div>
                <h1 class="text-4xl font-extrabold tracking-tight">My Todo List</h1>
                <p class="opacity-80 font-medium text-sm mt-1">Welcome back, <?php echo $_SESSION['username'] ?? 'User'; ?>!</p>
            </div>
            <div class="flex gap-2">
                <button class="bg-white/20 hover:bg-white/30 px-5 py-2.5 rounded-2xl text-xs font-bold backdrop-blur-lg transition">Profile</button>
                <a href="logout.php" class="bg-slate-100 hover:bg-white text-slate-900 px-5 py-2.5 rounded-2xl text-xs font-black shadow-xl transition">Logout</a>
            </div>
        </div>

        <!-- STATS GRID -->
        <div class="grid grid-cols-3 gap-6 text-center">
            <div class="bg-white/10 py-5 rounded-3xl backdrop-blur-md border border-white/10">
                <div class="text-4xl font-black mb-1"><?php echo $total_tasks; ?></div>
                <div class="text-[10px] uppercase font-black tracking-[0.2em] opacity-70">Total Tasks</div>
            </div>
            <div class="bg-white/10 py-5 rounded-3xl backdrop-blur-md border border-white/10">
                <div class="text-4xl font-black mb-1"><?php echo (count($pendingTasks) + count($inProgressTasks)); ?></div>
                <div class="text-[10px] uppercase font-black tracking-[0.2em] opacity-70">Pending</div>
            </div>
            <div class="bg-white/10 py-5 rounded-3xl backdrop-blur-md border border-white/10">
                <div class="text-4xl font-black mb-1"><?php echo count($doneTasks); ?></div>
                <div class="text-[10px] uppercase font-black tracking-[0.2em] opacity-70">Completed</div>
            </div>
        </div>
    </div>

    <!-- ADD TASK FORM -->
    <div class="p-10 border-b border-slate-100 bg-slate-50/40">
        <form method="POST" class="grid grid-cols-1 md:grid-cols-12 gap-5 items-end">
            <div class="md:col-span-5">
                <label class="block text-[11px] font-black text-slate-400 uppercase tracking-widest mb-3 ml-1">Task Title</label>
                <input type="text" name="title" placeholder="What needs to be done?" class="w-full px-5 py-4 rounded-2xl text-slate-900 shadow-sm" required>
            </div>
            <div class="md:col-span-3">
                <label class="block text-[11px] font-black text-slate-400 uppercase tracking-widest mb-3 ml-1">Priority</label>
                <select name="priority" class="w-full px-5 py-4 rounded-2xl text-slate-900 shadow-sm bg-white cursor-pointer">
                    <option value="low">Low</option>
                    <option value="medium" selected>Medium</option>
                    <option value="high">High</option>
                </select>
            </div>
            <div class="md:col-span-2">
                <label class="block text-[11px] font-black text-slate-400 uppercase tracking-widest mb-3 ml-1">Due Date</label>
                <input type="date" name="due_date" class="w-full px-5 py-4 rounded-2xl text-slate-900 shadow-sm" required>
            </div>
            <div class="md:col-span-2">
                <button type="submit" name="add_task" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-black py-4.5 rounded-2xl shadow-xl transition transform active:scale-95">
                    ADD TASK
                </button>
            </div>
        </form>
    </div>

    <!-- TASK LIST SECTION -->
    <div class="p-10">
        <div class="flex flex-col md:flex-row justify-between items-center mb-10 gap-4">
            <h2 class="text-2xl font-black text-slate-800 tracking-tight">Your Tasks</h2>
            <div class="flex gap-2 p-1.5 bg-slate-100/50 rounded-2xl border border-slate-200/50">
                <button onclick="showTab('all')" id="tab-all" class="tab-btn px-6 py-2.5 rounded-xl text-xs font-black transition-all active-tab">ALL</button>
                <button onclick="showTab('pending')" id="tab-pending" class="tab-btn px-6 py-2.5 rounded-xl text-xs font-black transition-all text-slate-400">PENDING</button>
                <button onclick="showTab('done')" id="tab-done" class="tab-btn px-6 py-2.5 rounded-xl text-xs font-black transition-all text-slate-400">DONE</button>
            </div>
        </div>

        <div id="section-all" class="task-tab-content">
            <?php renderTasks($tasks); ?>
        </div>
        <div id="section-pending" class="task-tab-content hidden">
            <?php renderTasks(array_merge($pendingTasks, $inProgressTasks)); ?>
        </div>
        <div id="section-done" class="task-tab-content hidden">
            <?php renderTasks($doneTasks); ?>
        </div>
    </div>

    <?php else: ?>
    <!-- LOGIN FORM UI (Original functionality) -->
    <div class="max-w-md mx-auto p-12 text-center">
        <div class="w-20 h-20 bg-indigo-600 rounded-3xl mx-auto mb-8 flex items-center justify-center shadow-2xl">
            <i class="bi bi-layers-half text-white text-4xl"></i>
        </div>
        <h2 class="text-3xl font-black text-slate-800 mb-2">CloudTask</h2>
        <p class="text-slate-500 mb-10 font-medium">Please sign in to your account</p>
        <?php if($message): ?><p class="mb-6 py-3 px-4 bg-red-50 text-red-600 rounded-xl text-sm font-bold border border-red-100"><?php echo htmlspecialchars($message); ?></p><?php endif; ?>
        <form method="POST" class="space-y-4">
            <input type="email" name="login_email" placeholder="Email Address" class="w-full px-5 py-4 rounded-2xl bg-slate-50 border border-slate-200" required>
            <input type="password" name="login_password" placeholder="Password" class="w-full px-5 py-4 rounded-2xl bg-slate-50 border border-slate-200" required>
            <button type="submit" class="w-full py-4 bg-indigo-600 hover:bg-indigo-700 text-white font-black rounded-2xl shadow-xl transition-all">SIGN IN</button>
        </form>
        <p class="mt-8 text-[10px] font-black text-slate-400 uppercase tracking-widest">Authentication Required</p>
    </div>
    <?php endif; ?>
</div>

<!-- EDIT MODAL -->
<div id="editModal" class="hidden fixed inset-0 bg-slate-900/80 backdrop-blur-md z-50 flex items-center justify-center p-6">
    <div class="bg-white rounded-[2.5rem] shadow-2xl w-full max-w-lg overflow-hidden transform transition-all">
        <div class="p-8 border-b border-slate-100 flex justify-between items-center bg-slate-50/50">
            <h2 class="text-2xl font-black text-slate-800 tracking-tight">Edit Task</h2>
            <button onclick="closeEditForm()" class="w-10 h-10 flex items-center justify-center rounded-xl hover:bg-slate-200 text-slate-400 transition">
                <i class="bi bi-x-lg text-xl"></i>
            </button>
        </div>
        <form method="POST" class="p-8 space-y-6">
            <input type="hidden" name="task_id" id="edit_task_id">
            <div>
                <label class="block text-[11px] font-black text-slate-400 uppercase tracking-widest mb-3">Task Title</label>
                <input type="text" name="title" id="edit_title" class="w-full px-5 py-4 rounded-2xl border" required>
            </div>
            <div class="grid grid-cols-2 gap-5">
                <div>
                    <label class="block text-[11px] font-black text-slate-400 uppercase tracking-widest mb-3">Priority</label>
                    <select name="priority" id="edit_priority" class="w-full px-5 py-4 rounded-2xl border bg-white">
                        <option value="low">Low</option>
                        <option value="medium">Medium</option>
                        <option value="high">High</option>
                    </select>
                </div>
                <div>
                    <label class="block text-[11px] font-black text-slate-400 uppercase tracking-widest mb-3">Status</label>
                    <select name="status" id="edit_status" class="w-full px-5 py-4 rounded-2xl border bg-white">
                        <option value="pending">Pending</option>
                        <option value="in_progress">In Progress</option>
                        <option value="done">Done</option>
                    </select>
                </div>
            </div>
            <div>
                <label class="block text-[11px] font-black text-slate-400 uppercase tracking-widest mb-3">Due Date</label>
                <input type="date" name="due_date" id="edit_due_date" class="w-full px-5 py-4 rounded-2xl border" required>
            </div>
            <div class="flex gap-4 pt-4">
                <button type="button" onclick="closeEditForm()" class="flex-1 py-4.5 bg-slate-100 text-slate-600 font-black rounded-2xl transition">CANCEL</button>
                <button type="submit" name="update_task" class="flex-1 py-4.5 bg-indigo-600 text-white font-black rounded-2xl shadow-xl transition">SAVE CHANGES</button>
            </div>
        </form>
    </div>
</div>

<script>
function showTab(tabName) {
    document.querySelectorAll('.task-tab-content').forEach(el => el.classList.add('hidden'));
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.classList.remove('active-tab');
        btn.classList.add('text-slate-400');
    });
    document.getElementById('section-' + tabName).classList.remove('hidden');
    const activeBtn = document.getElementById('tab-' + tabName);
    activeBtn.classList.add('active-tab');
    activeBtn.classList.remove('text-slate-400');
}
function showEditForm(id, title, due, priority, status) {
    document.getElementById('edit_task_id').value = id;
    document.getElementById('edit_title').value = title;
    document.getElementById('edit_due_date').value = due;
    document.getElementById('edit_priority').value = priority;
    document.getElementById('edit_status').value = status;
    document.getElementById('editModal').classList.remove('hidden');
}
function closeEditForm() {
    document.getElementById('editModal').classList.add('hidden');
}
</script>
</body>
</html>