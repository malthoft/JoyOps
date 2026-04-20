<?php
session_start();
require_once 'config.php';
require_once 'helpers.php';
initNotifications($pdo);
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') { header("Location: login.php"); exit; }

$pageTitle = 'Recruitment';
$role = $_SESSION['role'];
$initial = substr($_SESSION['name'], 0, 1);
$msg = ''; $msg_type = '';

// --- CRUD Operations ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'add';
    
    // ADD Employee
    if ($action === 'add') {
        try {
            $stmt = $pdo->prepare("INSERT INTO users (name, username, password, role) VALUES (?, ?, ?, 'Karyawan')");
            $stmt->execute([$_POST['name'], $_POST['username'], password_hash($_POST['password'], PASSWORD_DEFAULT)]);
            $msg = "Karyawan berhasil ditambahkan."; $msg_type = "success";
            addNotification($pdo, 'Karyawan Baru', $_POST['name'] . ' telah didaftarkan sebagai karyawan baru.', 'success');
        } catch (\PDOException $e) {
            $msg = "Gagal menambahkan. Username mungkin sudah terpakai."; $msg_type = "error";
        }
    }
    
    // UPDATE Employee
    if ($action === 'update') {
        try {
            $stmt = $pdo->prepare("UPDATE users SET name = ?, username = ? WHERE id = ? AND role = 'Karyawan'");
            $stmt->execute([$_POST['name'], $_POST['username'], $_POST['emp_id']]);
            $msg = "Data karyawan berhasil diperbarui."; $msg_type = "success";
            addNotification($pdo, 'Data Karyawan Diperbarui', 'Data karyawan ' . $_POST['name'] . ' telah diperbarui.', 'info');
        } catch (\PDOException $e) {
            $msg = "Gagal memperbarui. Username mungkin sudah terpakai."; $msg_type = "error";
        }
    }
    
    // RESET PASSWORD
    if ($action === 'reset_password') {
        $hashed = password_hash('123456', PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ? AND role = 'Karyawan'");
        $stmt->execute([$hashed, $_POST['emp_id']]);
        $msg = "Password berhasil direset ke '123456'."; $msg_type = "success";
        addNotification($pdo, 'Password Direset', 'Password seorang karyawan telah direset.', 'warning');
    }
    
    // DELETE Employee
    if ($action === 'delete') {
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ? AND role = 'Karyawan'");
        $stmt->execute([$_POST['emp_id']]);
        $msg = "Karyawan berhasil dihapus."; $msg_type = "success";
        addNotification($pdo, 'Karyawan Dihapus', 'Seorang karyawan telah dihapus dari sistem.', 'warning');
    }
}

$emps = $pdo->query("SELECT * FROM users WHERE role = 'Karyawan'")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8"/><meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Employees | JoyOps</title>
<script src="https://cdn.tailwindcss.com?plugins=forms"></script>
<link href="https://fonts.googleapis.com/css2?family=Manrope:wght@600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
<script> tailwind.config = { theme: { extend: { colors: { primary: "#00666e", secondary: "#92dcf8", "error": "#b31b25", surface: "#f5f7f8", "on-surface": "#2c2f30" }, fontFamily: { headline: ["Manrope"], body: ["Inter"] } } } } </script>
<style>
.material-symbols-outlined { font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24; }
.action-gradient { background: linear-gradient(135deg, #00666e 0%, #6ce9f6 100%); }
@keyframes slideIn { from { transform: translateX(-100%); } to { transform: translateX(0); } }
.slide-in { animation: slideIn 0.3s ease-out; }
@keyframes fadeIn { from { opacity:0; transform:scale(0.95); } to { opacity:1; transform:scale(1); } }
.fade-in { animation: fadeIn 0.2s ease-out; }
</style>
</head>
<body class="bg-surface font-body text-on-surface flex min-h-screen">

<!-- Mobile Sidebar Overlay -->
<div id="mobileSidebar" class="fixed inset-0 z-[60] hidden md:hidden">
    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" onclick="toggleMobileMenu()"></div>
    <aside class="absolute left-0 top-0 h-full w-72 bg-[#eef1f2] p-4 flex flex-col gap-2 shadow-2xl slide-in">
        <div class="flex justify-between items-center mb-8 px-2">
            <div class="flex flex-col">
                <h1 class="text-2xl font-black text-[#00666e] leading-none font-headline">JoyOps</h1>
                <span class="text-xs font-medium text-slate-500 uppercase tracking-widest mt-1">Admin Portal</span>
            </div>
            <button onclick="toggleMobileMenu()" class="p-1.5 rounded-lg hover:bg-slate-200/50"><span class="material-symbols-outlined">close</span></button>
        </div>
        <nav class="flex-1 flex flex-col gap-1 text-sm">
            <a href="admin_dashboard.php" class="flex items-center gap-3 px-3 py-2.5 text-slate-600 hover:bg-slate-200/50 transition-all rounded-lg"><span class="material-symbols-outlined">dashboard</span><span>Dashboard</span></a>
            <a href="machines.php" class="flex items-center gap-3 px-3 py-2.5 text-slate-600 hover:bg-slate-200/50 transition-all rounded-lg"><span class="material-symbols-outlined">local_laundry_service</span><span>Machines</span></a>
            <a href="shifts.php" class="flex items-center gap-3 px-3 py-2.5 text-slate-600 hover:bg-slate-200/50 transition-all rounded-lg"><span class="material-symbols-outlined">event_note</span><span>Shifts</span></a>
            <a href="services.php" class="flex items-center gap-3 px-3 py-2.5 text-slate-600 hover:bg-slate-200/50 transition-all rounded-lg"><span class="material-symbols-outlined">payments</span><span>Services</span></a>
            <a href="finance.php" class="flex items-center gap-3 px-3 py-2.5 text-slate-600 hover:bg-slate-200/50 transition-all rounded-lg"><span class="material-symbols-outlined">leaderboard</span><span>Finance</span></a>
            <a href="employees.php" class="flex items-center gap-3 px-3 py-2.5 bg-white text-[#00666e] font-bold shadow-sm rounded-lg transition-all"><span class="material-symbols-outlined">group_add</span><span>Recruitment</span></a>
        </nav>
        <div class="mt-auto border-t border-slate-200/50 pt-4 flex flex-col gap-1 text-sm">
            <a href="settings.php" class="flex items-center gap-3 px-3 py-2.5 text-slate-600 hover:bg-slate-200/50 transition-all rounded-lg"><span class="material-symbols-outlined">settings</span><span>Settings</span></a>
            <a href="logout.php" class="flex items-center gap-3 px-3 py-2.5 text-error hover:bg-red-50 transition-all rounded-lg font-semibold mt-1"><span class="material-symbols-outlined">logout</span><span>Logout</span></a>
        </div>
    </aside>
</div>

<!-- Desktop Sidebar -->
<aside class="hidden md:flex flex-col h-screen w-64 fixed left-0 top-0 bg-[#eef1f2] border-r border-gray-200 z-50 p-4 gap-2">
    <div class="mb-8 px-2 flex flex-col">
        <h1 class="text-2xl font-black text-[#00666e] leading-none font-headline">JoyOps</h1>
        <span class="text-xs font-medium text-slate-500 uppercase tracking-widest mt-1">Admin Portal</span>
    </div>
    <nav class="flex-1 flex flex-col gap-1 text-sm">
        <a href="admin_dashboard.php" class="flex items-center gap-3 px-3 py-2 text-slate-600 hover:bg-slate-200/50 transition-all rounded-lg"><span class="material-symbols-outlined">dashboard</span><span>Dashboard</span></a>
        <a href="machines.php" class="flex items-center gap-3 px-3 py-2 text-slate-600 hover:bg-slate-200/50 transition-all rounded-lg"><span class="material-symbols-outlined">local_laundry_service</span><span>Machines</span></a>
        <a href="shifts.php" class="flex items-center gap-3 px-3 py-2 text-slate-600 hover:bg-slate-200/50 transition-all rounded-lg"><span class="material-symbols-outlined">event_note</span><span>Shifts</span></a>
        <a href="services.php" class="flex items-center gap-3 px-3 py-2 text-slate-600 hover:bg-slate-200/50 transition-all rounded-lg"><span class="material-symbols-outlined">payments</span><span>Services</span></a>
        <a href="finance.php" class="flex items-center gap-3 px-3 py-2 text-slate-600 hover:bg-slate-200/50 transition-all rounded-lg"><span class="material-symbols-outlined">leaderboard</span><span>Finance</span></a>
        <a href="employees.php" class="flex items-center gap-3 px-3 py-2 bg-white text-[#00666e] font-bold shadow-sm rounded-lg transition-all"><span class="material-symbols-outlined">group_add</span><span>Recruitment</span></a>
    </nav>
    <div class="mt-auto border-t border-slate-200/50 pt-4 flex flex-col gap-1 text-sm">
        <a href="settings.php" class="flex items-center gap-3 px-3 py-2 text-slate-600 hover:bg-slate-200/50 transition-all rounded-lg"><span class="material-symbols-outlined">settings</span><span>Settings</span></a>
        <a href="logout.php" class="flex items-center gap-3 px-3 py-2 text-error hover:bg-red-50 transition-all rounded-lg font-semibold mt-1"><span class="material-symbols-outlined">logout</span><span>Logout</span></a>
    </div>
</aside>

<!-- Edit Employee Modal -->
<div id="editEmpModal" class="fixed inset-0 z-[70] hidden">
    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" onclick="closeEditEmp()"></div>
    <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[90%] max-w-md bg-white rounded-2xl p-6 sm:p-8 shadow-2xl fade-in">
        <h3 class="text-xl font-bold font-headline mb-6 flex items-center gap-2"><span class="material-symbols-outlined text-primary">edit</span> Edit Karyawan</h3>
        <form method="POST" class="space-y-4">
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="emp_id" id="edit_emp_id">
            <div class="space-y-2">
                <label class="text-xs font-bold uppercase tracking-wider text-gray-500">Nama Lengkap</label>
                <input type="text" name="name" id="edit_emp_name" required class="w-full bg-gray-50 border-none rounded-lg px-4 py-3 focus:ring-2 focus:ring-primary">
            </div>
            <div class="space-y-2">
                <label class="text-xs font-bold uppercase tracking-wider text-gray-500">Username</label>
                <input type="text" name="username" id="edit_emp_username" required class="w-full bg-gray-50 border-none rounded-lg px-4 py-3 focus:ring-2 focus:ring-primary">
            </div>
            <div class="flex gap-3 pt-2">
                <button type="button" onclick="closeEditEmp()" class="flex-1 py-3 rounded-lg border border-gray-200 font-bold text-gray-500 hover:bg-gray-50 transition-all">Batal</button>
                <button type="submit" class="flex-1 py-3 action-gradient text-white font-bold rounded-lg shadow-md hover:opacity-90 transition-all">Simpan</button>
            </div>
        </form>
    </div>
</div>

<main class="md:ml-64 flex-1 flex flex-col min-h-screen">
    <header class="bg-[#f5f7f8]/80 backdrop-blur-xl sticky top-0 z-40 border-b border-gray-200 flex justify-between items-center px-4 sm:px-8 py-4 w-full">
        <div class="flex items-center gap-2">
            <button onclick="toggleMobileMenu()" class="md:hidden p-1.5 rounded-lg hover:bg-gray-100"><span class="material-symbols-outlined text-[#00666e]">menu</span></button>
            <h2 class="text-xl font-bold text-[#00666e] font-headline tracking-tight"><?= $pageTitle ?></h2>
        </div>
        <div class="flex items-center gap-4 sm:gap-6">
            <div class="relative" id="notifContainer">
                <button onclick="toggleNotifs()" class="relative p-1 rounded-lg hover:bg-gray-100 transition-colors">
                    <span class="material-symbols-outlined text-slate-500 hover:text-[#00666e]">notifications</span>
                    <span id="notifBadge" class="hidden absolute -top-1 -right-1 w-5 h-5 bg-[#b31b25] text-white text-[10px] font-bold rounded-full flex items-center justify-center">0</span>
                </button>
                <div id="notifPanel" class="hidden absolute right-0 top-full mt-2 w-[85vw] sm:w-96 bg-white rounded-2xl shadow-2xl border border-gray-100 z-[80] overflow-hidden">
                    <div class="px-5 py-4 border-b border-gray-100 flex justify-between items-center bg-gray-50/50">
                        <h4 class="font-headline font-bold text-gray-800 text-sm">Notifications</h4>
                        <button onclick="markAllRead()" class="text-xs font-bold text-primary hover:underline">Mark all read</button>
                    </div>
                    <div id="notifList" class="max-h-80 overflow-y-auto">
                        <div class="p-8 text-center text-gray-400 text-sm">Loading...</div>
                    </div>
                </div>
            </div>
            <div class="flex items-center gap-3">
                <div class="text-right hidden sm:block"><p class="text-sm font-bold text-slate-800"><?= htmlspecialchars($_SESSION['name']) ?></p><p class="text-xs text-slate-500"><?= $role ?></p></div>
                <div class="w-10 h-10 rounded-full bg-[#00666e] text-white flex items-center justify-center font-bold text-lg shadow-sm"><?= $initial ?></div>
            </div>
        </div>
    </header>

    <div class="p-4 sm:p-8 max-w-7xl w-full mx-auto space-y-6 sm:space-y-8 flex-1">
        <?php if($msg): ?>
            <div class="p-4 rounded-lg font-bold text-sm <?= $msg_type === 'success' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' ?>">
                <?= htmlspecialchars($msg) ?>
            </div>
        <?php endif; ?>

        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-end gap-4">
            <div><h2 class="text-2xl sm:text-4xl font-extrabold font-headline">Staff Directory</h2><p class="text-gray-500 mt-1">Create and manage employee accounts.</p></div>
            <div class="bg-white px-4 py-2 rounded-lg border border-gray-100 shadow-sm">
                <p class="text-xs font-bold text-gray-400 uppercase">Total Staff</p>
                <p class="text-2xl font-black text-primary"><?= count($emps) ?></p>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 sm:gap-8">
            <!-- Register Form -->
            <div class="bg-white rounded-xl p-5 sm:p-6 shadow-sm border border-gray-100 h-fit">
                <h3 class="font-bold mb-4 font-headline flex items-center gap-2"><span class="material-symbols-outlined text-primary text-lg">person_add</span> Register New Staff</h3>
                <form method="POST" class="space-y-4">
                    <input type="hidden" name="action" value="add">
                    <input type="text" name="name" placeholder="Full Name" required class="w-full bg-gray-50 border-none rounded-lg focus:ring-primary text-sm">
                    <input type="text" name="username" placeholder="Username (for login)" required class="w-full bg-gray-50 border-none rounded-lg focus:ring-primary text-sm">
                    <input type="password" name="password" placeholder="Password" required class="w-full bg-gray-50 border-none rounded-lg focus:ring-primary text-sm">
                    <button type="submit" class="w-full action-gradient text-white py-3 rounded-lg font-bold shadow-md hover:opacity-90 transition-all">Create Account</button>
                </form>
            </div>
            
            <!-- Employee List -->
            <div class="lg:col-span-2 space-y-4">
                <?php if(empty($emps)): ?>
                    <div class="bg-white p-8 rounded-xl shadow-sm border border-gray-100 text-center">
                        <span class="material-symbols-outlined text-gray-300 text-5xl mb-3">group</span>
                        <p class="text-gray-400 font-medium">Belum ada karyawan terdaftar.</p>
                    </div>
                <?php else: ?>
                    <?php foreach($emps as $e): ?>
                    <div class="bg-white p-4 sm:p-5 rounded-xl shadow-sm border border-gray-100 flex flex-col sm:flex-row items-start sm:items-center gap-4 group hover:shadow-md transition-shadow">
                        <div class="flex items-center gap-4 flex-1 min-w-0">
                            <div class="w-12 h-12 rounded-full bg-primary text-white flex items-center justify-center font-bold text-xl shrink-0"><?= substr($e['name'],0,1) ?></div>
                            <div class="min-w-0">
                                <h4 class="font-bold text-gray-800 text-lg truncate"><?= htmlspecialchars($e['name']) ?></h4>
                                <p class="text-gray-500 text-xs">@<?= htmlspecialchars($e['username']) ?></p>
                            </div>
                        </div>
                        <div class="flex gap-2 w-full sm:w-auto">
                            <button onclick="openEditEmp(<?= $e['id'] ?>, '<?= htmlspecialchars($e['name'], ENT_QUOTES) ?>', '<?= htmlspecialchars($e['username'], ENT_QUOTES) ?>')" class="flex-1 sm:flex-initial px-3 py-2 bg-gray-50 text-gray-600 rounded-lg text-xs font-bold hover:bg-gray-100 transition-colors flex items-center justify-center gap-1">
                                <span class="material-symbols-outlined text-sm">edit</span> Edit
                            </button>
                            <form method="POST" onsubmit="return confirm('Reset password karyawan ini ke 123456?')" class="flex-1 sm:flex-initial">
                                <input type="hidden" name="action" value="reset_password">
                                <input type="hidden" name="emp_id" value="<?= $e['id'] ?>">
                                <button type="submit" class="w-full px-3 py-2 bg-[#fdd34d]/20 text-[#705900] rounded-lg text-xs font-bold hover:bg-[#fdd34d]/40 transition-colors flex items-center justify-center gap-1">
                                    <span class="material-symbols-outlined text-sm">lock_reset</span> Reset PW
                                </button>
                            </form>
                            <form method="POST" onsubmit="return confirm('Hapus karyawan ini? Semua data shift terkait juga akan dihapus.')" class="flex-shrink-0">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="emp_id" value="<?= $e['id'] ?>">
                                <button type="submit" class="px-3 py-2 bg-red-50 text-error rounded-lg text-xs font-bold hover:bg-red-100 transition-colors flex items-center gap-1">
                                    <span class="material-symbols-outlined text-sm">delete</span>
                                </button>
                            </form>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <footer class="mt-auto py-6 border-t border-gray-200 text-center text-slate-400 text-xs font-medium w-full">
        © 2024 JoyOps Operations
    </footer>
</main>
<script>
function toggleMobileMenu(){document.getElementById('mobileSidebar').classList.toggle('hidden');}
function openEditEmp(id, name, username) {
    document.getElementById('edit_emp_id').value = id;
    document.getElementById('edit_emp_name').value = name;
    document.getElementById('edit_emp_username').value = username;
    document.getElementById('editEmpModal').classList.remove('hidden');
}
function closeEditEmp() { document.getElementById('editEmpModal').classList.add('hidden'); }
var NR='<?= $role ?>';function toggleNotifs(){var p=document.getElementById('notifPanel');p.classList.toggle('hidden');if(!p.classList.contains('hidden'))loadNotifs();}function loadNotifs(){fetch('notifications_api.php?action=fetch&role='+NR).then(function(r){return r.json()}).then(function(d){var l=document.getElementById('notifList'),b=document.getElementById('notifBadge');if(d.unread>0){b.classList.remove('hidden');b.textContent=d.unread>9?'9+':d.unread;}else{b.classList.add('hidden');}if(!d.notifications||!d.notifications.length){l.innerHTML='<div class="p-8 text-center text-gray-400 text-sm">Tidak ada notifikasi</div>';return;}l.innerHTML=d.notifications.map(function(n){var ic={info:'info',success:'check_circle',warning:'warning',error:'error'};var cl={info:'text-[#00666e] bg-[#e2f6ff]',success:'text-green-600 bg-green-50',warning:'text-[#705900] bg-[#fdd34d]/20',error:'text-[#b31b25] bg-red-50'};return'<div class="px-5 py-3 border-b border-gray-50 flex gap-3 '+(n.is_read?'opacity-50':'')+' hover:bg-gray-50 transition-colors cursor-default"><div class="w-9 h-9 rounded-full '+(cl[n.type]||cl.info)+' flex items-center justify-center shrink-0"><span class="material-symbols-outlined text-lg">'+(ic[n.type]||'info')+'</span></div><div class="flex-1 min-w-0"><p class="text-sm font-bold text-gray-800 truncate">'+n.title+'</p><p class="text-xs text-gray-500 mt-0.5">'+n.message+'</p><p class="text-[10px] text-gray-400 mt-1">'+n.time_ago+'</p></div></div>';}).join('');}).catch(function(){});}function markAllRead(){fetch('notifications_api.php?action=mark_read&role='+NR).then(function(){loadNotifs();});}
document.addEventListener('click',function(e){var c=document.getElementById('notifContainer');if(c&&!c.contains(e.target)){var p=document.getElementById('notifPanel');if(p)p.classList.add('hidden');}});document.addEventListener('DOMContentLoaded',function(){fetch('notifications_api.php?action=count&role='+NR).then(function(r){return r.json();}).then(function(d){var b=document.getElementById('notifBadge');if(d.count>0){b.classList.remove('hidden');b.textContent=d.count>9?'9+':d.count;}}).catch(function(){});});
</script>
</body></html>