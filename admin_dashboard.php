<?php
session_start();
require_once 'config.php';
require_once 'helpers.php';
initNotifications($pdo);
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') { header("Location: login.php"); exit; }

$pageTitle = 'Dashboard';
$role = $_SESSION['role'];
$initial = substr($_SESSION['name'], 0, 1);

$rev_today = $pdo->query("SELECT SUM(amount) FROM transactions WHERE transaction_type='Pemasukan' AND DATE(transaction_date) = CURDATE()")->fetchColumn() ?: 0;
$total_trx = $pdo->query("SELECT COUNT(*) FROM transactions WHERE DATE(transaction_date) = CURDATE()")->fetchColumn() ?: 0;
$active_staff = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'Karyawan'")->fetchColumn() ?: 0;
$machines = $pdo->query("SELECT status, COUNT(*) as c FROM machines GROUP BY status")->fetchAll(PDO::FETCH_KEY_PAIR);
$avail = $machines['Tersedia'] ?? 0;
$in_use = $machines['Digunakan'] ?? 0;
$maint = $machines['Maintenance'] ?? 0;
$total_m = $avail + $in_use + $maint;

function calcWidth($val, $tot) { return $tot > 0 ? ($val / $tot) * 100 : 0; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8"/><meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Admin Dashboard | JoyOps</title>
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
                <span class="text-xs font-medium text-slate-500 uppercase tracking-widest mt-1">Admin Portal</span>
            </div>
            <button onclick="toggleMobileMenu()" class="p-1.5 rounded-lg hover:bg-slate-200/50"><span class="material-symbols-outlined">close</span></button>
        </div>
        <nav class="flex-1 flex flex-col gap-1 text-sm">
            <a href="admin_dashboard.php" class="flex items-center gap-3 px-3 py-2.5 bg-white text-[#00666e] font-bold shadow-sm rounded-lg transition-all"><span class="material-symbols-outlined">dashboard</span><span>Dashboard</span></a>
            <a href="machines.php" class="flex items-center gap-3 px-3 py-2.5 text-slate-600 hover:bg-slate-200/50 transition-all rounded-lg"><span class="material-symbols-outlined">local_laundry_service</span><span>Machines</span></a>
            <a href="shifts.php" class="flex items-center gap-3 px-3 py-2.5 text-slate-600 hover:bg-slate-200/50 transition-all rounded-lg"><span class="material-symbols-outlined">event_note</span><span>Shifts</span></a>
            <a href="services.php" class="flex items-center gap-3 px-3 py-2.5 text-slate-600 hover:bg-slate-200/50 transition-all rounded-lg"><span class="material-symbols-outlined">payments</span><span>Services</span></a>
            <a href="finance.php" class="flex items-center gap-3 px-3 py-2.5 text-slate-600 hover:bg-slate-200/50 transition-all rounded-lg"><span class="material-symbols-outlined">leaderboard</span><span>Finance</span></a>
            <a href="employees.php" class="flex items-center gap-3 px-3 py-2.5 text-slate-600 hover:bg-slate-200/50 transition-all rounded-lg"><span class="material-symbols-outlined">group_add</span><span>Recruitment</span></a>
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
        <a href="admin_dashboard.php" class="flex items-center gap-3 px-3 py-2 bg-white text-[#00666e] font-bold shadow-sm rounded-lg transition-all"><span class="material-symbols-outlined">dashboard</span><span>Dashboard</span></a>
        <a href="machines.php" class="flex items-center gap-3 px-3 py-2 text-slate-600 hover:bg-slate-200/50 transition-all rounded-lg"><span class="material-symbols-outlined">local_laundry_service</span><span>Machines</span></a>
        <a href="shifts.php" class="flex items-center gap-3 px-3 py-2 text-slate-600 hover:bg-slate-200/50 transition-all rounded-lg"><span class="material-symbols-outlined">event_note</span><span>Shifts</span></a>
        <a href="services.php" class="flex items-center gap-3 px-3 py-2 text-slate-600 hover:bg-slate-200/50 transition-all rounded-lg"><span class="material-symbols-outlined">payments</span><span>Services</span></a>
        <a href="finance.php" class="flex items-center gap-3 px-3 py-2 text-slate-600 hover:bg-slate-200/50 transition-all rounded-lg"><span class="material-symbols-outlined">leaderboard</span><span>Finance</span></a>
        <a href="employees.php" class="flex items-center gap-3 px-3 py-2 text-slate-600 hover:bg-slate-200/50 transition-all rounded-lg"><span class="material-symbols-outlined">group_add</span><span>Recruitment</span></a>
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
                <div class="text-right hidden sm:block"><p class="text-sm font-bold text-slate-800"><?= htmlspecialchars($_SESSION['name']) ?></p><p class="text-xs text-slate-500"><?= $role ?></p></div>
                <div class="w-10 h-10 rounded-full bg-[#00666e] text-white flex items-center justify-center font-bold text-lg shadow-sm"><?= $initial ?></div>
            </div>
        </div>
    </header>

    <div class="p-4 sm:p-8 space-y-6 sm:space-y-8 w-full mx-auto max-w-7xl flex-1">
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4 sm:gap-6">
            <div class="sm:col-span-2 bg-white p-5 sm:p-6 rounded-xl shadow-sm border border-gray-100 flex flex-col justify-between">
                <div class="flex justify-between items-start mb-4">
                    <h3 class="text-sm font-semibold text-gray-500 font-headline uppercase tracking-wider">Machine Status Summary</h3>
                    <span class="material-symbols-outlined text-primary">local_laundry_service</span>
                </div>
                <div class="flex items-center justify-around py-4">
                    <div class="text-center w-1/3 px-2">
                        <div class="text-2xl sm:text-3xl font-bold text-primary"><?= $avail ?></div><p class="text-xs font-medium text-gray-500">Available</p>
                        <div class="h-1.5 w-full bg-primary/20 rounded-full mt-2 overflow-hidden"><div class="bg-primary h-full" style="width: <?= calcWidth($avail, $total_m) ?>%"></div></div>
                    </div>
                    <div class="text-center w-1/3 px-2">
                        <div class="text-2xl sm:text-3xl font-bold text-[#705900]"><?= $in_use ?></div><p class="text-xs font-medium text-gray-500">In Use</p>
                        <div class="h-1.5 w-full bg-[#fdd34d]/50 rounded-full mt-2 overflow-hidden"><div class="bg-[#fdd34d] h-full" style="width: <?= calcWidth($in_use, $total_m) ?>%"></div></div>
                    </div>
                    <div class="text-center w-1/3 px-2">
                        <div class="text-2xl sm:text-3xl font-bold text-error"><?= $maint ?></div><p class="text-xs font-medium text-gray-500">Maintenance</p>
                        <div class="h-1.5 w-full bg-error/20 rounded-full mt-2 overflow-hidden"><div class="bg-error h-full" style="width: <?= calcWidth($maint, $total_m) ?>%"></div></div>
                    </div>
                </div>
            </div>

            <div class="bg-white p-5 sm:p-6 rounded-xl shadow-sm border border-gray-100 flex flex-col">
                <h3 class="text-sm font-semibold text-gray-500 font-headline uppercase tracking-wider mb-2">Today's Revenue</h3>
                <div class="flex items-baseline gap-2 mt-auto">
                    <span class="text-2xl sm:text-4xl font-black text-gray-800">Rp <?= number_format($rev_today, 0, ',', '.') ?></span>
                </div>
                <div class="mt-4 flex items-end gap-1 h-12">
                    <div class="bg-primary/10 w-full h-[20%] rounded-t-sm"></div><div class="bg-primary/20 w-full h-[40%] rounded-t-sm"></div><div class="bg-primary/30 w-full h-[35%] rounded-t-sm"></div><div class="bg-primary/40 w-full h-[60%] rounded-t-sm"></div><div class="bg-primary/50 w-full h-[55%] rounded-t-sm"></div><div class="bg-primary/70 w-full h-[85%] rounded-t-sm"></div><div class="bg-primary w-full h-[100%] rounded-t-sm"></div>
                </div>
            </div>

            <div class="flex flex-row sm:flex-col gap-4">
                <div class="flex-1 bg-white p-4 sm:p-5 rounded-xl shadow-sm border border-gray-100 flex items-center justify-between">
                    <div><p class="text-xs font-semibold text-gray-500 font-headline">Transactions</p><p class="text-xl sm:text-2xl font-bold"><?= $total_trx ?></p></div>
                    <div class="bg-secondary w-10 h-10 rounded-full flex items-center justify-center"><span class="material-symbols-outlined text-[#004e61]">receipt_long</span></div>
                </div>
                <div class="flex-1 bg-white p-4 sm:p-5 rounded-xl shadow-sm border border-gray-100 flex items-center justify-between">
                    <div><p class="text-xs font-semibold text-gray-500 font-headline">Active Staff</p><p class="text-xl sm:text-2xl font-bold"><?= $active_staff ?></p></div>
                    <div class="bg-[#fdd34d] w-10 h-10 rounded-full flex items-center justify-center"><span class="material-symbols-outlined text-[#5c4900]">badge</span></div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 sm:gap-8">
            <div class="lg:col-span-1 space-y-4">
                <h3 class="text-lg font-bold font-headline">Quick Links</h3>
                <div class="grid grid-cols-1 gap-3">
                    <a href="machines.php" class="group flex items-center justify-between p-4 bg-white border border-gray-100 rounded-xl transition-all hover:border-primary/30">
                        <div class="flex items-center gap-3"><div class="bg-primary/10 text-primary p-2 rounded-lg group-hover:scale-110 transition-transform"><span class="material-symbols-outlined">add_circle</span></div><span class="font-semibold text-gray-800">Add Machine</span></div>
                        <span class="material-symbols-outlined text-gray-400">chevron_right</span>
                    </a>
                    <a href="shifts.php" class="group flex items-center justify-between p-4 bg-white border border-gray-100 rounded-xl transition-all hover:border-primary/30">
                        <div class="flex items-center gap-3"><div class="bg-primary/10 text-primary p-2 rounded-lg group-hover:scale-110 transition-transform"><span class="material-symbols-outlined">person_add</span></div><span class="font-semibold text-gray-800">Register Shift</span></div>
                        <span class="material-symbols-outlined text-gray-400">chevron_right</span>
                    </a>
                    <a href="finance.php" class="group flex items-center justify-between p-4 bg-white border border-gray-100 rounded-xl transition-all hover:border-primary/30">
                        <div class="flex items-center gap-3"><div class="bg-primary/10 text-primary p-2 rounded-lg group-hover:scale-110 transition-transform"><span class="material-symbols-outlined">point_of_sale</span></div><span class="font-semibold text-gray-800">Record Transaction</span></div>
                        <span class="material-symbols-outlined text-gray-400">chevron_right</span>
                    </a>
                </div>
            </div>

            <div class="lg:col-span-2 bg-white p-6 sm:p-8 rounded-xl shadow-sm border border-gray-100 flex flex-col justify-center">
                <div class="flex items-start gap-4 p-4 sm:p-6 bg-[#fdd34d]/30 border border-[#fdd34d]/50 rounded-xl">
                    <span class="material-symbols-outlined text-[#705900] text-3xl sm:text-4xl">lightbulb</span>
                    <div>
                        <p class="text-lg font-bold text-[#5c4900] font-headline">Operations Tip</p>
                        <p class="text-sm text-[#5c4900] mt-2">Selalu pastikan status mesin diperbarui oleh karyawan melalui Staff Portal. Laporan pendapatan harian yang akurat bergantung pada pembaruan status mesin secara <i>real-time</i>.</p>
                    </div>
                </div>
            </div>
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