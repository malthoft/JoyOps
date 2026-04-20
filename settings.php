<?php
session_start();
require_once 'config.php';
require_once 'helpers.php';
initNotifications($pdo);

// Validasi akses
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];
$initial = substr($_SESSION['name'], 0, 1);
$pageTitle = 'Settings';
$msg = ''; $msg_type = '';

// Ambil data user saat ini
$stmt = $pdo->prepare("SELECT name, username FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$current_user = $stmt->fetch();

// --- Proses Form Update ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // 1. Update Profil Dasar
    if (isset($_POST['action']) && $_POST['action'] === 'update_profile') {
        $name = trim($_POST['name']);
        $username = trim($_POST['username']);
        
        try {
            $update = $pdo->prepare("UPDATE users SET name = ?, username = ? WHERE id = ?");
            $update->execute([$name, $username, $user_id]);
            $_SESSION['name'] = $name; // Update nama di session
            $current_user['name'] = $name;
            $current_user['username'] = $username;
            $msg = "Profil berhasil diperbarui."; $msg_type = "success";
            addNotification($pdo, 'Profil Diperbarui', $name . ' memperbarui data profil.', 'info');
        } catch (\PDOException $e) {
            $msg = "Gagal memperbarui profil. Username mungkin sudah terpakai."; $msg_type = "error";
        }
    }

    // 2. Update Password
    if (isset($_POST['action']) && $_POST['action'] === 'update_password') {
        $new_pass = $_POST['new_password'];
        $conf_pass = $_POST['confirm_password'];
        
        if ($new_pass === $conf_pass && strlen($new_pass) >= 6) {
            $hashed = password_hash($new_pass, PASSWORD_DEFAULT);
            $update_pw = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            $update_pw->execute([$hashed, $user_id]);
            $msg = "Password berhasil diperbarui."; $msg_type = "success";
            addNotification($pdo, 'Password Diperbarui', $_SESSION['name'] . ' mengubah password.', 'info');
        } else {
            $msg = "Password tidak cocok atau terlalu pendek (min 6 karakter)."; $msg_type = "error";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8"/><meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Settings | JoyOps</title>
<script src="https://cdn.tailwindcss.com?plugins=forms"></script>
<link href="https://fonts.googleapis.com/css2?family=Manrope:wght@600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
<script> tailwind.config = { theme: { extend: { colors: { primary: "#00666e", secondary: "#92dcf8", "error": "#b31b25", surface: "#f5f7f8", "on-surface": "#2c2f30" }, fontFamily: { headline: ["Manrope"], body: ["Inter"] } } } } </script>
<style>
.material-symbols-outlined { font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24; }
.action-gradient { background: linear-gradient(135deg, #00666e 0%, #6ce9f6 100%); }
@keyframes slideIn { from { transform: translateX(-100%); } to { transform: translateX(0); } }
.slide-in { animation: slideIn 0.3s ease-out; }
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
                <span class="text-xs font-medium text-slate-500 uppercase tracking-widest mt-1"><?= $role ?> Portal</span>
            </div>
            <button onclick="toggleMobileMenu()" class="p-1.5 rounded-lg hover:bg-slate-200/50"><span class="material-symbols-outlined">close</span></button>
        </div>
        <nav class="flex-1 flex flex-col gap-1 text-sm">
            <?php if($role === 'Admin'): ?>
                <a href="admin_dashboard.php" class="flex items-center gap-3 px-3 py-2.5 text-slate-600 hover:bg-slate-200/50 transition-all rounded-lg"><span class="material-symbols-outlined">dashboard</span><span>Dashboard</span></a>
                <a href="machines.php" class="flex items-center gap-3 px-3 py-2.5 text-slate-600 hover:bg-slate-200/50 transition-all rounded-lg"><span class="material-symbols-outlined">local_laundry_service</span><span>Machines</span></a>
                <a href="shifts.php" class="flex items-center gap-3 px-3 py-2.5 text-slate-600 hover:bg-slate-200/50 transition-all rounded-lg"><span class="material-symbols-outlined">event_note</span><span>Shifts</span></a>
                <a href="services.php" class="flex items-center gap-3 px-3 py-2.5 text-slate-600 hover:bg-slate-200/50 transition-all rounded-lg"><span class="material-symbols-outlined">payments</span><span>Services</span></a>
                <a href="finance.php" class="flex items-center gap-3 px-3 py-2.5 text-slate-600 hover:bg-slate-200/50 transition-all rounded-lg"><span class="material-symbols-outlined">leaderboard</span><span>Finance</span></a>
                <a href="employees.php" class="flex items-center gap-3 px-3 py-2.5 text-slate-600 hover:bg-slate-200/50 transition-all rounded-lg"><span class="material-symbols-outlined">group_add</span><span>Recruitment</span></a>
            <?php else: ?>
                <a href="employee_dashboard.php" class="flex items-center gap-3 px-3 py-2.5 text-slate-600 hover:bg-slate-200/50 transition-all rounded-lg"><span class="material-symbols-outlined">dashboard</span><span>Dashboard</span></a>
                <a href="machines.php" class="flex items-center gap-3 px-3 py-2.5 text-slate-600 hover:bg-slate-200/50 transition-all rounded-lg"><span class="material-symbols-outlined">local_laundry_service</span><span>Machines</span></a>
                <a href="my_shift.php" class="flex items-center gap-3 px-3 py-2.5 text-slate-600 hover:bg-slate-200/50 transition-all rounded-lg"><span class="material-symbols-outlined">calendar_month</span><span>My Shift</span></a>
                <a href="services.php" class="flex items-center gap-3 px-3 py-2.5 text-slate-600 hover:bg-slate-200/50 transition-all rounded-lg"><span class="material-symbols-outlined">payments</span><span>Services</span></a>
            <?php endif; ?>
        </nav>
        <div class="mt-auto border-t border-slate-200/50 pt-4 flex flex-col gap-1 text-sm">
            <a href="settings.php" class="flex items-center gap-3 px-3 py-2.5 bg-white text-[#00666e] font-bold shadow-sm rounded-lg transition-all"><span class="material-symbols-outlined">settings</span><span>Settings</span></a>
            <a href="logout.php" class="flex items-center gap-3 px-3 py-2.5 text-error hover:bg-red-50 transition-all rounded-lg font-semibold mt-1"><span class="material-symbols-outlined">logout</span><span>Logout</span></a>
        </div>
    </aside>
</div>

<!-- Desktop Sidebar -->
<aside class="hidden md:flex flex-col h-screen w-64 fixed left-0 top-0 bg-[#eef1f2] border-r border-gray-200 z-50 p-4 gap-2">
    <div class="mb-8 px-2 flex flex-col">
        <h1 class="text-2xl font-black text-[#00666e] leading-none font-headline">JoyOps</h1>
        <span class="text-xs font-medium text-slate-500 uppercase tracking-widest mt-1"><?= $role ?> Portal</span>
    </div>
    <nav class="flex-1 flex flex-col gap-1 text-sm">
        <?php if($role === 'Admin'): ?>
            <a href="admin_dashboard.php" class="flex items-center gap-3 px-3 py-2 text-slate-600 hover:bg-slate-200/50 transition-all rounded-lg"><span class="material-symbols-outlined">dashboard</span><span>Dashboard</span></a>
            <a href="machines.php" class="flex items-center gap-3 px-3 py-2 text-slate-600 hover:bg-slate-200/50 transition-all rounded-lg"><span class="material-symbols-outlined">local_laundry_service</span><span>Machines</span></a>
            <a href="shifts.php" class="flex items-center gap-3 px-3 py-2 text-slate-600 hover:bg-slate-200/50 transition-all rounded-lg"><span class="material-symbols-outlined">event_note</span><span>Shifts</span></a>
            <a href="services.php" class="flex items-center gap-3 px-3 py-2 text-slate-600 hover:bg-slate-200/50 transition-all rounded-lg"><span class="material-symbols-outlined">payments</span><span>Services</span></a>
            <a href="finance.php" class="flex items-center gap-3 px-3 py-2 text-slate-600 hover:bg-slate-200/50 transition-all rounded-lg"><span class="material-symbols-outlined">leaderboard</span><span>Finance</span></a>
            <a href="employees.php" class="flex items-center gap-3 px-3 py-2 text-slate-600 hover:bg-slate-200/50 transition-all rounded-lg"><span class="material-symbols-outlined">group_add</span><span>Recruitment</span></a>
        <?php else: ?>
            <a href="employee_dashboard.php" class="flex items-center gap-3 px-3 py-2 text-slate-600 hover:bg-slate-200/50 transition-all rounded-lg"><span class="material-symbols-outlined">dashboard</span><span>Dashboard</span></a>
            <a href="machines.php" class="flex items-center gap-3 px-3 py-2 text-slate-600 hover:bg-slate-200/50 transition-all rounded-lg"><span class="material-symbols-outlined">local_laundry_service</span><span>Machines</span></a>
            <a href="my_shift.php" class="flex items-center gap-3 px-3 py-2 text-slate-600 hover:bg-slate-200/50 transition-all rounded-lg"><span class="material-symbols-outlined">calendar_month</span><span>My Shift</span></a>
            <a href="services.php" class="flex items-center gap-3 px-3 py-2 text-slate-600 hover:bg-slate-200/50 transition-all rounded-lg"><span class="material-symbols-outlined">payments</span><span>Services</span></a>
        <?php endif; ?>
    </nav>
    <div class="mt-auto border-t border-slate-200/50 pt-4 flex flex-col gap-1 text-sm">
        <a href="settings.php" class="flex items-center gap-3 px-3 py-2 bg-white text-[#00666e] font-bold shadow-sm rounded-lg transition-all"><span class="material-symbols-outlined">settings</span><span>Settings</span></a>
        <a href="logout.php" class="flex items-center gap-3 px-3 py-2 text-error hover:bg-red-50 transition-all rounded-lg font-semibold mt-1"><span class="material-symbols-outlined">logout</span><span>Logout</span></a>
    </div>
</aside>

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

    <div class="p-4 sm:p-8 space-y-6 sm:space-y-8 w-full mx-auto max-w-7xl flex-1">
        <?php if($msg): ?>
            <div class="p-4 rounded-lg font-bold text-sm <?= $msg_type === 'success' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' ?>">
                <?= htmlspecialchars($msg) ?>
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 sm:gap-8">
            <section class="lg:col-span-8 space-y-6 sm:space-y-8">
                <!-- Profile Information -->
                <div class="bg-white rounded-xl p-6 sm:p-8 shadow-sm border border-gray-100 relative overflow-hidden">
                    <div class="absolute -right-4 -top-4 opacity-5 pointer-events-none"><span class="material-symbols-outlined text-[120px]">person</span></div>
                    <h3 class="text-xl font-bold font-headline mb-6 flex items-center gap-2"><span class="material-symbols-outlined text-primary">account_circle</span> Profile Information</h3>
                    
                    <form method="POST" class="flex flex-col md:flex-row items-start gap-6 sm:gap-8 relative z-10">
                        <input type="hidden" name="action" value="update_profile">
                        <div class="relative mx-auto md:mx-0">
                            <div class="w-24 h-24 sm:w-32 sm:h-32 rounded-full bg-primary text-white flex items-center justify-center font-bold text-4xl sm:text-5xl shadow-lg ring-4 ring-[#6ce9f6]">
                                <?= substr($current_user['name'], 0, 1) ?>
                            </div>
                        </div>
                        <div class="flex-1 grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-6 w-full">
                            <div class="space-y-2">
                                <label class="text-xs font-bold uppercase tracking-wider text-gray-500 px-1">Nama Lengkap</label>
                                <input name="name" required class="w-full bg-gray-50 border-none rounded-lg px-4 py-3 focus:ring-2 focus:ring-primary transition-all" type="text" value="<?= htmlspecialchars($current_user['name']) ?>"/>
                            </div>
                            <div class="space-y-2">
                                <label class="text-xs font-bold uppercase tracking-wider text-gray-500 px-1">Username Login</label>
                                <input name="username" required class="w-full bg-gray-50 border-none rounded-lg px-4 py-3 focus:ring-2 focus:ring-primary transition-all" type="text" value="<?= htmlspecialchars($current_user['username']) ?>"/>
                            </div>
                            <div class="space-y-2 md:col-span-2">
                                <label class="text-xs font-bold uppercase tracking-wider text-gray-500 px-1">Role / Hak Akses</label>
                                <input class="w-full bg-gray-200 border-none rounded-lg px-4 py-3 text-gray-500 cursor-not-allowed" type="text" value="<?= htmlspecialchars($role) ?>" disabled/>
                            </div>
                            <div class="md:col-span-2 flex justify-end">
                                <button type="submit" class="px-8 py-3 bg-primary text-white font-bold rounded-full shadow hover:opacity-90 transition-all w-full sm:w-auto">Simpan Profil</button>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Display Settings -->
                <div class="bg-white rounded-xl p-6 sm:p-8 shadow-sm border border-gray-100">
                    <h3 class="text-xl font-bold font-headline mb-6 flex items-center gap-2"><span class="material-symbols-outlined text-primary">palette</span> Display Settings</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 sm:gap-8">
                        <div class="space-y-4">
                            <label class="text-xs font-bold uppercase tracking-wider text-gray-500 px-1">Language</label>
                            <select disabled class="w-full appearance-none bg-gray-50 border-none rounded-lg px-4 py-3 opacity-50 cursor-not-allowed">
                                <option>Bahasa Indonesia (ID)</option>
                            </select>
                        </div>
                        <div class="space-y-4">
                            <label class="text-xs font-bold uppercase tracking-wider text-gray-500 px-1">Interface Theme</label>
                            <div class="flex gap-4">
                                <button class="flex-1 py-3 rounded-lg border-2 border-primary bg-[#6ce9f6]/10 flex items-center justify-center gap-2 font-bold text-primary"><span class="material-symbols-outlined">light_mode</span> Light</button>
                                <button disabled class="flex-1 py-3 rounded-lg border-2 border-transparent bg-gray-50 flex items-center justify-center gap-2 font-medium text-gray-400 opacity-50 cursor-not-allowed"><span class="material-symbols-outlined">dark_mode</span> Dark</button>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section class="lg:col-span-4 space-y-6 sm:space-y-8">
                <!-- Change Password -->
                <div class="bg-white rounded-xl p-6 sm:p-8 shadow-sm border border-gray-100">
                    <h3 class="text-xl font-bold font-headline mb-6 flex items-center gap-2"><span class="material-symbols-outlined text-primary">security</span> Ganti Password</h3>
                    <form method="POST" class="space-y-6">
                        <input type="hidden" name="action" value="update_password">
                        <div class="space-y-2">
                            <label class="text-xs font-bold uppercase tracking-wider text-gray-500 px-1">Password Baru</label>
                            <input name="new_password" required class="w-full bg-gray-50 border-none rounded-lg px-4 py-3 focus:ring-2 focus:ring-primary transition-all" placeholder="••••••••" type="password"/>
                        </div>
                        <div class="space-y-2">
                            <label class="text-xs font-bold uppercase tracking-wider text-gray-500 px-1">Konfirmasi Password Baru</label>
                            <input name="confirm_password" required class="w-full bg-gray-50 border-none rounded-lg px-4 py-3 focus:ring-2 focus:ring-primary transition-all" placeholder="••••••••" type="password"/>
                        </div>
                        <div class="pt-2">
                            <div class="p-4 rounded-lg bg-[#fdd34d]/20 border border-[#fdd34d]/30 mb-6">
                                <p class="text-xs text-[#5c4900] font-medium flex items-start gap-2"><span class="material-symbols-outlined text-sm mt-0.5">info</span> Password minimal 6 karakter. Harap simpan dengan baik.</p>
                            </div>
                            <button type="submit" class="w-full py-4 action-gradient text-white font-bold rounded-full shadow-lg hover:shadow-xl active:scale-95 transition-all">Update Password</button>
                        </div>
                    </form>
                </div>
                
                <!-- User Info Card -->
                <div class="bg-gray-50 rounded-xl p-6 text-center border border-gray-100">
                    <p class="text-xs text-gray-500 font-medium">User ID: U-<?= str_pad($user_id, 4, '0', STR_PAD_LEFT) ?></p>
                    <p class="text-[10px] text-gray-400 mt-1 uppercase tracking-[2px]">Akses saat ini: <?= htmlspecialchars($role) ?></p>
                </div>
            </section>
        </div>
    </div>

    <footer class="mt-auto py-6 border-t border-gray-200 text-center text-slate-400 text-xs font-medium w-full">
        © 2024 JoyOps Operations
    </footer>
</main>
<script>
function toggleMobileMenu(){document.getElementById('mobileSidebar').classList.toggle('hidden');}
var NR='<?= $role ?>';function toggleNotifs(){var p=document.getElementById('notifPanel');p.classList.toggle('hidden');if(!p.classList.contains('hidden'))loadNotifs();}function loadNotifs(){fetch('notifications_api.php?action=fetch&role='+NR).then(function(r){return r.json()}).then(function(d){var l=document.getElementById('notifList'),b=document.getElementById('notifBadge');if(d.unread>0){b.classList.remove('hidden');b.textContent=d.unread>9?'9+':d.unread;}else{b.classList.add('hidden');}if(!d.notifications||!d.notifications.length){l.innerHTML='<div class="p-8 text-center text-gray-400 text-sm">Tidak ada notifikasi</div>';return;}l.innerHTML=d.notifications.map(function(n){var ic={info:'info',success:'check_circle',warning:'warning',error:'error'};var cl={info:'text-[#00666e] bg-[#e2f6ff]',success:'text-green-600 bg-green-50',warning:'text-[#705900] bg-[#fdd34d]/20',error:'text-[#b31b25] bg-red-50'};return'<div class="px-5 py-3 border-b border-gray-50 flex gap-3 '+(n.is_read?'opacity-50':'')+' hover:bg-gray-50 transition-colors cursor-default"><div class="w-9 h-9 rounded-full '+(cl[n.type]||cl.info)+' flex items-center justify-center shrink-0"><span class="material-symbols-outlined text-lg">'+(ic[n.type]||'info')+'</span></div><div class="flex-1 min-w-0"><p class="text-sm font-bold text-gray-800 truncate">'+n.title+'</p><p class="text-xs text-gray-500 mt-0.5">'+n.message+'</p><p class="text-[10px] text-gray-400 mt-1">'+n.time_ago+'</p></div></div>';}).join('');}).catch(function(){});}function markAllRead(){fetch('notifications_api.php?action=mark_read&role='+NR).then(function(){loadNotifs();});}
document.addEventListener('click',function(e){var c=document.getElementById('notifContainer');if(c&&!c.contains(e.target)){var p=document.getElementById('notifPanel');if(p)p.classList.add('hidden');}});document.addEventListener('DOMContentLoaded',function(){fetch('notifications_api.php?action=count&role='+NR).then(function(r){return r.json();}).then(function(d){var b=document.getElementById('notifBadge');if(d.count>0){b.classList.remove('hidden');b.textContent=d.count>9?'9+':d.count;}}).catch(function(){});});
</script>
</body></html>