<?php
session_start();
require_once 'config.php';
require_once 'helpers.php';
initNotifications($pdo);
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Karyawan') { header("Location: login.php"); exit; }

$user_id = $_SESSION['user_id'];
$name = $_SESSION['name'];
$role = $_SESSION['role'];
$initial = substr($name, 0, 1);
$pageTitle = 'My Shift';

// 1. Shift Aktif Hari Ini
$stmt = $pdo->prepare("SELECT * FROM shifts WHERE user_id = ? AND work_date = CURDATE()");
$stmt->execute([$user_id]);
$shift_today = $stmt->fetch();

// 2. Histori Shift Selesai
$hist = $pdo->prepare("SELECT * FROM shifts WHERE user_id = ? AND status = 'Completed' ORDER BY work_date DESC LIMIT 3");
$hist->execute([$user_id]);
$history = $hist->fetchAll();

// 3. Jadwal Minggu Ini (Senin - Minggu)
$week_shifts = [];
$days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
foreach($days as $d) {
    $date = date('Y-m-d', strtotime($d . ' this week'));
    $st = $pdo->prepare("SELECT * FROM shifts WHERE user_id = ? AND work_date = ?");
    $st->execute([$user_id, $date]);
    $week_shifts[$d] = $st->fetch();
}

// Simulasi Update Clock In (Jika di klik)
if(isset($_POST['clock_in']) && $shift_today) {
    $pdo->prepare("UPDATE shifts SET status = 'Clocked In' WHERE id = ?")->execute([$shift_today['id']]);
    header("Location: my_shift.php"); exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8"/><meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>My Shift | JoyOps</title>
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
                <span class="text-xs font-medium text-slate-500 uppercase tracking-widest mt-1">Staff Portal</span>
            </div>
            <button onclick="toggleMobileMenu()" class="p-1.5 rounded-lg hover:bg-slate-200/50"><span class="material-symbols-outlined">close</span></button>
        </div>
        <nav class="flex-1 flex flex-col gap-1 text-sm">
            <a href="employee_dashboard.php" class="flex items-center gap-3 px-3 py-2.5 text-slate-600 hover:bg-slate-200/50 transition-all rounded-lg"><span class="material-symbols-outlined">dashboard</span><span>Dashboard</span></a>
            <a href="machines.php" class="flex items-center gap-3 px-3 py-2.5 text-slate-600 hover:bg-slate-200/50 transition-all rounded-lg"><span class="material-symbols-outlined">local_laundry_service</span><span>Machines</span></a>
            <a href="my_shift.php" class="flex items-center gap-3 px-3 py-2.5 bg-white text-[#00666e] font-bold shadow-sm rounded-lg transition-all"><span class="material-symbols-outlined">event_note</span><span>My Shift</span></a>
            <a href="services.php" class="flex items-center gap-3 px-3 py-2.5 text-slate-600 hover:bg-slate-200/50 transition-all rounded-lg"><span class="material-symbols-outlined">payments</span><span>Services</span></a>
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
        <span class="text-xs font-medium text-slate-500 uppercase tracking-widest mt-1">Staff Portal</span>
    </div>
    <nav class="flex-1 flex flex-col gap-1 text-sm">
        <a href="employee_dashboard.php" class="flex items-center gap-3 px-3 py-2 text-slate-600 hover:bg-slate-200/50 transition-all rounded-lg"><span class="material-symbols-outlined">dashboard</span><span>Dashboard</span></a>
        <a href="machines.php" class="flex items-center gap-3 px-3 py-2 text-slate-600 hover:bg-slate-200/50 transition-all rounded-lg"><span class="material-symbols-outlined">local_laundry_service</span><span>Machines</span></a>
        <a href="my_shift.php" class="flex items-center gap-3 px-3 py-2 bg-white text-[#00666e] font-bold shadow-sm rounded-lg transition-all"><span class="material-symbols-outlined">event_note</span><span>My Shift</span></a>
        <a href="services.php" class="flex items-center gap-3 px-3 py-2 text-slate-600 hover:bg-slate-200/50 transition-all rounded-lg"><span class="material-symbols-outlined">payments</span><span>Services</span></a>
    </nav>
    <div class="mt-auto border-t border-slate-200/50 pt-4 flex flex-col gap-1 text-sm">
        <a href="settings.php" class="flex items-center gap-3 px-3 py-2 text-slate-600 hover:bg-slate-200/50 transition-all rounded-lg"><span class="material-symbols-outlined">settings</span><span>Settings</span></a>
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
                <div class="text-right hidden sm:block"><p class="text-sm font-bold text-slate-800"><?= htmlspecialchars($name) ?></p><p class="text-xs text-slate-500">Staff</p></div>
                <div class="w-10 h-10 rounded-full bg-[#00666e] text-white flex items-center justify-center font-bold text-lg shadow-sm"><?= $initial ?></div>
            </div>
        </div>
    </header>

    <div class="p-4 sm:p-8 space-y-6 sm:space-y-8 w-full mx-auto max-w-7xl flex-1">
        <!-- Today's Shift Card -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 sm:gap-8">
            <div class="lg:col-span-2 bg-white rounded-xl p-6 sm:p-8 shadow-sm border border-gray-100 relative overflow-hidden group">
                <span class="material-symbols-outlined absolute -right-4 -bottom-4 text-primary/5 text-[80px] sm:text-9xl transform -rotate-12 pointer-events-none">dry_cleaning</span>
                <div class="relative z-10 flex flex-col h-full justify-between">
                    <div>
                        <span class="px-3 py-1 <?= $shift_today ? 'bg-[#fdd34d] text-[#5c4900]' : 'bg-gray-100 text-gray-500' ?> text-[10px] font-bold rounded-full uppercase tracking-wider mb-4 inline-block">Status Hari Ini</span>
                        <?php if($shift_today): ?>
                            <h3 class="text-2xl sm:text-4xl font-headline font-extrabold text-gray-800 mb-2">Shift Aktif</h3>
                            <p class="text-gray-500 text-base sm:text-lg">Stasiun: <?= htmlspecialchars($shift_today['station']) ?></p>
                        <?php else: ?>
                            <h3 class="text-2xl sm:text-4xl font-headline font-extrabold text-gray-400 mb-2">Day Off</h3>
                            <p class="text-gray-500 text-base sm:text-lg">Anda tidak memiliki jadwal hari ini. Selamat beristirahat.</p>
                        <?php endif; ?>
                    </div>
                    
                    <?php if($shift_today): ?>
                    <div class="mt-8 sm:mt-12 flex flex-col sm:flex-row sm:items-end justify-between gap-4">
                        <div class="space-y-1">
                            <p class="text-gray-500 text-sm font-medium">Jam Kerja</p>
                            <div class="flex items-center gap-3">
                                <span class="text-2xl sm:text-3xl font-headline font-bold text-primary"><?= substr($shift_today['start_time'],0,5) ?></span>
                                <span class="text-gray-300">—</span>
                                <span class="text-2xl sm:text-3xl font-headline font-bold text-primary"><?= substr($shift_today['end_time'],0,5) ?></span>
                            </div>
                        </div>
                        
                        <?php if($shift_today['status'] !== 'Completed'): ?>
                        <form method="POST">
                            <input type="hidden" name="clock_in" value="1">
                            <button type="submit" class="<?= $shift_today['status'] == 'Clocked In' ? 'bg-green-100 text-green-700' : 'bg-secondary text-[#004e61] hover:scale-105' ?> px-6 py-3 rounded-full flex items-center gap-2 font-bold shadow-sm transition-all w-full sm:w-auto justify-center">
                                <span class="material-symbols-outlined">timer</span> <?= $shift_today['status'] == 'Clocked In' ? 'Sedang Bekerja' : 'Mulai Bekerja (Clock In)' ?>
                            </button>
                        </form>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="bg-primary rounded-xl p-6 text-white flex flex-col justify-between relative overflow-hidden shadow-lg shadow-primary/20">
                <div class="absolute top-0 right-0 p-2 opacity-20"><span class="material-symbols-outlined text-6xl">inventory_2</span></div>
                <div>
                    <p class="font-bold text-xs uppercase tracking-wider mb-2 opacity-80">Assigned Station</p>
                    <h4 class="text-xl sm:text-2xl font-headline font-bold"><?= $shift_today ? htmlspecialchars($shift_today['station']) : 'N/A' ?></h4>
                </div>
                <div class="mt-8 bg-white/10 p-4 rounded-lg backdrop-blur-sm border border-white/20">
                    <p class="text-xs font-medium">Harap update status mesin di menu <i>Machines</i> jika ada pergantian pelanggan.</p>
                </div>
            </div>
        </div>

        <!-- Weekly Schedule -->
        <h3 class="text-xl sm:text-2xl font-headline font-bold text-gray-800">Weekly Schedule</h3>
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 xl:grid-cols-7 gap-3 sm:gap-4">
            <?php foreach($days as $d): 
                $shift = $week_shifts[$d];
                $is_today = ($d === date('l'));
            ?>
            <div class="p-4 sm:p-6 rounded-xl transition-shadow <?= $shift ? ($is_today ? 'bg-[#6ce9f6]/20 border-primary/30 shadow-md border' : 'bg-white shadow-sm border border-gray-100') : 'bg-gray-50 border border-dashed border-gray-200 opacity-60 flex flex-col items-center text-center justify-center' ?>">
                <?php if($shift): ?>
                    <p class="text-gray-500 font-bold text-xs sm:text-sm mb-3 sm:mb-4"><?= substr($d,0,3) ?>, <?= date('d M', strtotime($shift['work_date'])) ?> <?= $is_today ? '<span class="text-primary">(Hari Ini)</span>' : '' ?></p>
                    <div class="flex items-center gap-2 sm:gap-3 mb-4 sm:mb-6">
                        <div class="w-8 h-8 sm:w-10 sm:h-10 rounded-full bg-secondary flex items-center justify-center text-[#004e61]"><span class="material-symbols-outlined text-sm sm:text-base">wb_sunny</span></div>
                        <div>
                            <p class="text-gray-800 font-bold text-sm"><?= htmlspecialchars($shift['station']) ?></p>
                            <p class="text-gray-500 text-xs"><?= substr($shift['start_time'],0,5) ?> - <?= substr($shift['end_time'],0,5) ?></p>
                        </div>
                    </div>
                    <div class="pt-3 sm:pt-4 border-t border-gray-100 flex items-center justify-between text-xs font-medium text-gray-500">
                        <span>Status</span>
                        <?php
                            $sc = 'bg-gray-100 text-gray-800';
                            if($shift['status'] == 'Clocked In') $sc = 'bg-[#e2f6ff] text-primary';
                            if($shift['status'] == 'Completed') $sc = 'bg-green-100 text-green-700';
                        ?>
                        <span class="<?= $sc ?> px-2 py-1 rounded-full font-bold uppercase text-[10px]"><?= $shift['status'] ?></span>
                    </div>
                <?php else: ?>
                    <span class="material-symbols-outlined text-gray-300 text-2xl sm:text-3xl mb-2">event_busy</span>
                    <p class="text-gray-500 font-bold text-xs sm:text-sm"><?= substr($d,0,3) ?>, <?= date('d M', strtotime($d . ' this week')) ?></p>
                    <p class="text-xs font-medium uppercase mt-1">Day Off</p>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Recently Completed -->
        <section class="bg-white rounded-xl p-6 sm:p-8 shadow-sm border border-gray-100">
            <div class="flex items-center gap-3 mb-6 sm:mb-8">
                <span class="material-symbols-outlined text-primary">history</span>
                <h3 class="text-lg sm:text-xl font-headline font-bold text-gray-800">Recently Completed</h3>
            </div>
            <div class="space-y-6 relative before:absolute before:left-[1.2rem] before:top-4 before:bottom-4 before:w-px before:bg-gray-200">
                <?php if($history): foreach($history as $h): ?>
                <div class="flex gap-4 sm:gap-6 relative">
                    <div class="w-10 h-10 rounded-full bg-white flex items-center justify-center z-10 border border-gray-200 shrink-0">
                        <span class="material-symbols-outlined text-green-500 text-lg">check_circle</span>
                    </div>
                    <div class="flex-1">
                        <div class="flex flex-col sm:flex-row sm:justify-between sm:items-start gap-1">
                            <h4 class="font-bold text-gray-800"><?= date('D, M d Y', strtotime($h['work_date'])) ?></h4>
                            <p class="text-xs font-bold text-primary"><?= substr($h['start_time'],0,5) ?> - <?= substr($h['end_time'],0,5) ?></p>
                        </div>
                        <p class="text-sm text-gray-500">Stasiun: <?= htmlspecialchars($h['station']) ?></p>
                    </div>
                </div>
                <?php endforeach; else: ?>
                    <p class="pl-14 text-sm text-gray-500">Belum ada histori shift yang diselesaikan.</p>
                <?php endif; ?>
            </div>
        </section>
    </div>

    <footer class="mt-auto py-6 border-t border-gray-200 text-center text-slate-400 text-xs font-medium w-full">© 2024 JoyOps Operations</footer>
</main>
<script>
function toggleMobileMenu(){document.getElementById('mobileSidebar').classList.toggle('hidden');}
var NR='<?= $role ?>';function toggleNotifs(){var p=document.getElementById('notifPanel');p.classList.toggle('hidden');if(!p.classList.contains('hidden'))loadNotifs();}function loadNotifs(){fetch('notifications_api.php?action=fetch&role='+NR).then(function(r){return r.json()}).then(function(d){var l=document.getElementById('notifList'),b=document.getElementById('notifBadge');if(d.unread>0){b.classList.remove('hidden');b.textContent=d.unread>9?'9+':d.unread;}else{b.classList.add('hidden');}if(!d.notifications||!d.notifications.length){l.innerHTML='<div class="p-8 text-center text-gray-400 text-sm">Tidak ada notifikasi</div>';return;}l.innerHTML=d.notifications.map(function(n){var ic={info:'info',success:'check_circle',warning:'warning',error:'error'};var cl={info:'text-[#00666e] bg-[#e2f6ff]',success:'text-green-600 bg-green-50',warning:'text-[#705900] bg-[#fdd34d]/20',error:'text-[#b31b25] bg-red-50'};return'<div class="px-5 py-3 border-b border-gray-50 flex gap-3 '+(n.is_read?'opacity-50':'')+' hover:bg-gray-50 transition-colors cursor-default"><div class="w-9 h-9 rounded-full '+(cl[n.type]||cl.info)+' flex items-center justify-center shrink-0"><span class="material-symbols-outlined text-lg">'+(ic[n.type]||'info')+'</span></div><div class="flex-1 min-w-0"><p class="text-sm font-bold text-gray-800 truncate">'+n.title+'</p><p class="text-xs text-gray-500 mt-0.5">'+n.message+'</p><p class="text-[10px] text-gray-400 mt-1">'+n.time_ago+'</p></div></div>';}).join('');}).catch(function(){});}function markAllRead(){fetch('notifications_api.php?action=mark_read&role='+NR).then(function(){loadNotifs();});}
document.addEventListener('click',function(e){var c=document.getElementById('notifContainer');if(c&&!c.contains(e.target)){var p=document.getElementById('notifPanel');if(p)p.classList.add('hidden');}});document.addEventListener('DOMContentLoaded',function(){fetch('notifications_api.php?action=count&role='+NR).then(function(r){return r.json();}).then(function(d){var b=document.getElementById('notifBadge');if(d.count>0){b.classList.remove('hidden');b.textContent=d.count>9?'9+':d.count;}}).catch(function(){});});
</script>
</body></html>