<?php
session_start();
require 'config.php';

if (isset($_POST['new_status'])) {
    $stmt = $conn->prepare("UPDATE tasks SET status=? WHERE id=? AND user_id=?");
    $stmt->execute([$_POST['new_status'], $_POST['task_id'], $_SESSION['user_id']]);
    header("Location: index.php"); exit();
}

if (isset($_POST['add_task'])) {
    $stmt = $conn->prepare("INSERT INTO tasks (user_id, title, priority, status, created_at) VALUES (?,?,?,?,NOW())");
    $stmt->execute([$_SESSION['user_id'], trim($_POST['title']), $_POST['priority'], $_POST['status'] ?? 'pending']);
    header("Location: index.php"); exit();
}

if (isset($_POST['update_task'])) {
    $stmt = $conn->prepare("UPDATE tasks SET title=?, priority=?, status=? WHERE id=? AND user_id=?");
    $stmt->execute([trim($_POST['title']), $_POST['priority'], $_POST['status'], $_POST['task_id'], $_SESSION['user_id']]);
    header("Location: index.php"); exit();
}

if (isset($_POST['delete_task'])) {
    $stmt = $conn->prepare("DELETE FROM tasks WHERE id=? AND user_id=?");
    $stmt->execute([$_POST['task_id'], $_SESSION['user_id']]);
    header("Location: index.php"); exit();
}

$tasks = $inProgressTasks = $pendingTasks = $doneTasks = [];
if (isset($_SESSION['user_id'])) {
    $stmt = $conn->prepare("SELECT * FROM tasks WHERE user_id=? ORDER BY id DESC");
    $stmt->execute([$_SESSION['user_id']]);
    $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($tasks as $t) {
        if ($t['status'] === 'in_progress') $inProgressTasks[] = $t;
        elseif ($t['status'] === 'pending') $pendingTasks[] = $t;
        elseif ($t['status'] === 'done') $doneTasks[] = $t;
    }
}

function renderTasks($tasksArray) {
    if (empty($tasksArray)) {
        echo '<div class="text-center py-12 text-slate-600 bg-slate-900/30 rounded-3xl border-2 border-dashed border-slate-800/50">No tasks found.</div>';
        return;
    }
    foreach($tasksArray as $row):
        $p = strtolower($row['priority']);
        $cls = $p==='high'?'border-rose-500/50':($p==='medium'?'border-amber-500/50':'border-emerald-500/50');
?>
<div class="bg-slate-900/40 backdrop-blur-md border border-slate-800 flex flex-col md:flex-row md:items-center justify-between p-5 border-l-4 rounded-2xl mb-3 transition-all hover:bg-slate-800/40 <?php echo $cls; ?>">
    <div class="flex-1">
        <div class="flex items-center gap-2 mb-1">
            <span class="text-[9px] font-black uppercase tracking-widest text-slate-500"><?php echo $p; ?></span>
            <?php if($row['status']==='in_progress'): ?><span class="h-1.5 w-1.5 rounded-full bg-indigo-500 animate-pulse"></span><?php endif; ?>
        </div>
        <h3 class="text-base font-bold text-slate-200 <?php echo $row['status']==='done'?'line-through opacity-25':''; ?>"><?php echo htmlspecialchars($row['title']); ?></h3>
    </div>
    <div class="flex items-center gap-2 mt-3 md:mt-0">
        <form method="POST" class="inline">
            <input type="hidden" name="task_id" value="<?php echo $row['id']; ?>">
            <input type="hidden" name="new_status" value="<?php echo $row['status']==='done'?'pending':'done'; ?>">
            <button type="submit" class="bg-indigo-500/10 hover:bg-indigo-500 text-indigo-400 hover:text-white text-[10px] font-bold px-3 py-1.5 rounded-lg border border-indigo-500/20 transition-all uppercase tracking-tighter"><?php echo $row['status']==='done'?'Reopen':'Done'; ?></button>
        </form>
        <button onclick="showEditForm(<?php echo $row['id']; ?>,'<?php echo htmlspecialchars($row['title'],ENT_QUOTES); ?>','<?php echo $row['priority']; ?>','<?php echo $row['status']; ?>')" class="bg-slate-800 hover:bg-slate-700 text-slate-400 text-[10px] font-bold px-3 py-1.5 rounded-lg transition uppercase tracking-tighter">Edit</button>
        <form method="POST" class="inline" onsubmit="return confirm('Delete?');">
            <input type="hidden" name="task_id" value="<?php echo $row['id']; ?>">
            <button type="submit" name="delete_task" class="text-slate-600 hover:text-rose-500 transition px-1"><i class="bi bi-trash3 text-sm"></i></button>
        </form>
    </div>
</div>
<?php endforeach; } ?>
<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="UTF-8">
    <title>TaskFlow</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap');
        body { font-family: 'Inter', sans-serif; background: #020617; color: #f1f5f9; }
        .active-tab { background: #6366f1 !important; color: white !important; }
        input, select { background: #0f172a !important; border: 1px solid #1e293b !important; color: white !important; }
        input:focus { border-color: #6366f1 !important; outline: none; }
    </style>
</head>
<body class="p-4 md:p-10 flex justify-center min-h-screen">
<div class="w-full max-w-4xl">
    <?php if(isset($_SESSION['user_id'])): ?>
    <div class="flex justify-between items-center mb-10">
        <div>
            <h1 class="text-2xl font-black tracking-tight text-white">TASK<span class="text-indigo-500">FLOW</span></h1>
            <p class="text-slate-500 text-[10px] font-bold uppercase tracking-widest mt-1">Productivity Dashboard</p>
        </div>
        <div class="flex items-center gap-4">
            <div class="text-right hidden sm:block">
                <div class="text-xs font-bold text-slate-300">Total: <?php echo count($tasks); ?></div>
                <div class="text-[9px] font-bold text-indigo-500 uppercase tracking-widest">Active Tasks</div>
            </div>
            <a href="logout.php" class="bg-slate-900 border border-slate-800 hover:border-rose-500/50 hover:text-rose-500 p-2 rounded-xl transition text-slate-500"><i class="bi bi-power"></i></a>
        </div>
    </div>

    <div class="bg-slate-900/40 border border-slate-800 p-6 rounded-3xl mb-8">
        <form method="POST" class="grid grid-cols-1 md:grid-cols-12 gap-4 items-end">
            <div class="md:col-span-6">
                <label class="block text-[9px] font-black text-slate-500 uppercase tracking-widest mb-1.5 ml-1">Task Description</label>
                <input type="text" name="title" placeholder="What needs to be done?" class="w-full px-4 py-3 rounded-xl text-sm" required>
            </div>
            <div class="md:col-span-3">
                <label class="block text-[9px] font-black text-slate-500 uppercase tracking-widest mb-1.5 ml-1">Priority</label>
                <select name="priority" class="w-full px-4 py-3 rounded-xl text-sm appearance-none cursor-pointer">
                    <option value="low">Low</option>
                    <option value="medium" selected>Medium</option>
                    <option value="high">High</option>
                </select>
            </div>
            <div class="md:col-span-3">
                <button type="submit" name="add_task" class="w-full bg-gradient-to-r from-indigo-600 to-indigo-500 hover:to-indigo-400 text-white font-black py-3 rounded-xl shadow-lg shadow-indigo-500/10 transition-all transform active:scale-[0.98] text-[11px] uppercase tracking-widest">
                    Add Task
                </button>
            </div>
        </form>
    </div>

    <div class="flex flex-col md:flex-row gap-6">
        <div class="w-full md:w-40 flex flex-row md:flex-col gap-1">
            <button onclick="showTab('all')" id="tab-all" class="tab-btn flex-1 text-left px-4 py-2.5 rounded-lg text-[10px] font-bold transition-all active-tab uppercase tracking-widest">All</button>
            <button onclick="showTab('pending')" id="tab-pending" class="tab-btn flex-1 text-left px-4 py-2.5 rounded-lg text-[10px] font-bold transition-all text-slate-500 hover:bg-slate-900 uppercase tracking-widest">Pending</button>
            <button onclick="showTab('done')" id="tab-done" class="tab-btn flex-1 text-left px-4 py-2.5 rounded-lg text-[10px] font-bold transition-all text-slate-500 hover:bg-slate-900 uppercase tracking-widest">Done</button>
        </div>
        <div class="flex-1">
            <div id="section-all" class="task-tab-content"><?php renderTasks($tasks); ?></div>
            <div id="section-pending" class="task-tab-content hidden"><?php renderTasks(array_merge($pendingTasks, $inProgressTasks)); ?></div>
            <div id="section-done" class="task-tab-content hidden"><?php renderTasks($doneTasks); ?></div>
        </div>
    </div>
    <?php else: ?>
    <div class="max-w-sm mx-auto mt-20 p-8 bg-slate-900/50 border border-slate-800 rounded-[2rem] text-center">
        <div class="w-12 h-12 bg-indigo-600 rounded-xl mx-auto mb-6 flex items-center justify-center shadow-lg shadow-indigo-500/20"><i class="bi bi-lock-fill text-white"></i></div>
        <h2 class="text-xl font-bold text-white mb-6">Protected Area</h2>
        <form method="POST" class="space-y-3">
            <input type="email" name="login_email" placeholder="Email Address" class="w-full px-4 py-3.5 rounded-xl text-sm" required>
            <input type="password" name="login_password" placeholder="Password" class="w-full px-4 py-3.5 rounded-xl text-sm" required>
            <button type="submit" class="w-full py-3.5 bg-indigo-600 hover:bg-indigo-500 text-white font-black rounded-xl transition-all uppercase tracking-widest text-[10px]">Sign In</button>
        </form>
    </div>
    <?php endif; ?>
</div>

<div id="editModal" class="hidden fixed inset-0 bg-black/80 backdrop-blur-sm z-50 flex items-center justify-center p-4">
    <div class="bg-slate-900 border border-slate-800 rounded-3xl w-full max-w-sm overflow-hidden">
        <div class="p-6 border-b border-slate-800 flex justify-between items-center">
            <h2 class="text-sm font-black uppercase tracking-widest text-white">Edit Task</h2>
            <button onclick="closeEditForm()" class="text-slate-500 hover:text-white"><i class="bi bi-x-lg"></i></button>
        </div>
        <form method="POST" class="p-6 space-y-4">
            <input type="hidden" name="task_id" id="edit_task_id">
            <div>
                <label class="block text-[9px] font-black text-slate-500 uppercase tracking-widest mb-1.5 ml-1">Title</label>
                <input type="text" name="title" id="edit_title" class="w-full px-4 py-3 rounded-xl text-sm" required>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-[9px] font-black text-slate-500 uppercase tracking-widest mb-1.5 ml-1">Priority</label>
                    <select name="priority" id="edit_priority" class="w-full px-4 py-3 rounded-xl text-sm">
                        <option value="low">Low</option>
                        <option value="medium">Medium</option>
                        <option value="high">High</option>
                    </select>
                </div>
                <div>
                    <label class="block text-[9px] font-black text-slate-500 uppercase tracking-widest mb-1.5 ml-1">Status</label>
                    <select name="status" id="edit_status" class="w-full px-4 py-3 rounded-xl text-sm">
                        <option value="pending">Pending</option>
                        <option value="in_progress">In Progress</option>
                        <option value="done">Done</option>
                    </select>
                </div>
            </div>
            <div class="flex gap-2 pt-2">
                <button type="button" onclick="closeEditForm()" class="flex-1 py-3 bg-slate-800 text-slate-400 font-bold rounded-xl text-[10px] uppercase tracking-widest">Cancel</button>
                <button type="submit" name="update_task" class="flex-1 py-3 bg-indigo-600 text-white font-bold rounded-xl shadow-lg shadow-indigo-500/20 text-[10px] uppercase tracking-widest">Save</button>
            </div>
        </form>
    </div>
</div>

<script>
function showTab(t){
    document.querySelectorAll('.task-tab-content').forEach(e=>e.classList.add('hidden'));
    document.querySelectorAll('.tab-btn').forEach(b=>{b.classList.remove('active-tab');b.classList.add('text-slate-500');});
    document.getElementById('section-'+t).classList.remove('hidden');
    let b=document.getElementById('tab-'+t);b.classList.add('active-tab');b.classList.remove('text-slate-500');
}
function showEditForm(i,t,p,s){
    document.getElementById('edit_task_id').value=i;
    document.getElementById('edit_title').value=t;
    document.getElementById('edit_priority').value=p;
    document.getElementById('edit_status').value=s;
    document.getElementById('editModal').classList.remove('hidden');
}
function closeEditForm(){document.getElementById('editModal').classList.add('hidden');}
</script>
</body>
</html>