<?php
session_start();
require_once 'config.php';
require_once 'helpers.php';
initNotifications($pdo);
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }

$pageTitle = 'Services';
$role = $_SESSION['role'];
$initial = substr($_SESSION['name'], 0, 1);
$msg = ''; $msg_type = '';

// Fungsi Tambah Service (Hanya Admin)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add' && $role === 'Admin') {
    $icon = !empty($_POST['icon']) ? $_POST['icon'] : 'local_laundry_service';
    $stmt = $pdo->prepare("INSERT INTO services (service_name, price, icon) VALUES (?, ?, ?)");
    $stmt->execute([$_POST['s_name'], $_POST['s_price'], $icon]);
    addNotification($pdo, 'Layanan Ditambahkan', 'Layanan ' . $_POST['s_name'] . ' (Rp ' . number_format($_POST['s_price'],0,',','.') . ') berhasil ditambahkan.', 'success');
    header("Location: services.php"); exit;
}

// Fungsi Edit Service (Hanya Admin)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update' && $role === 'Admin') {
    $icon = !empty($_POST['icon']) ? $_POST['icon'] : 'local_laundry_service';
    $stmt = $pdo->prepare("UPDATE services SET service_name = ?, price = ?, icon = ? WHERE id = ?");
    $stmt->execute([$_POST['s_name'], $_POST['s_price'], $icon, $_POST['srv_id']]);
    addNotification($pdo, 'Layanan Diperbarui', 'Layanan ' . $_POST['s_name'] . ' telah diperbarui.', 'info');
    header("Location: services.php"); exit;
}

// Fungsi Hapus Service (Hanya Admin)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete' && $role === 'Admin') {
    $stmt = $pdo->prepare("DELETE FROM services WHERE id = ?");
    $stmt->execute([$_POST['srv_id']]);
    addNotification($pdo, 'Layanan Dihapus', 'Sebuah layanan telah dihapus dari sistem.', 'warning');
    header("Location: services.php"); exit;
}

$services = $pdo->query("SELECT * FROM services ORDER BY id DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8"/><meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Services & Pricing | JoyOps</title>
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
                <span class="text-xs font-medium text-slate-500 uppercase tracking-widest mt-1"><?= $role ?> Portal</span>
            </div>
            <button onclick="toggleMobileMenu()" class="p-1.5 rounded-lg hover:bg-slate-200/50"><span class="material-symbols-outlined">close</span></button>
        </div>
        <nav class="flex-1 flex flex-col gap-1 text-sm">
            <?php if($role === 'Admin'): ?>
                <a href="admin_dashboard.php" class="flex items-center gap-3 px-3 py-2.5 text-slate-600 hover:bg-slate-200/50 transition-all rounded-lg"><span class="material-symbols-outlined">dashboard</span><span>Dashboard</span></a>
                <a href="machines.php" class="flex items-center gap-3 px-3 py-2.5 text-slate-600 hover:bg-slate-200/50 transition-all rounded-lg"><span class="material-symbols-outlined">local_laundry_service</span><span>Machines</span></a>
                <a href="shifts.php" class="flex items-center gap-3 px-3 py-2.5 text-slate-600 hover:bg-slate-200/50 transition-all rounded-lg"><span class="material-symbols-outlined">event_note</span><span>Shifts</span></a>
                <a href="services.php" class="flex items-center gap-3 px-3 py-2.5 bg-white text-[#00666e] font-bold shadow-sm rounded-lg transition-all"><span class="material-symbols-outlined">payments</span><span>Services</span></a>
                <a href="finance.php" class="flex items-center gap-3 px-3 py-2.5 text-slate-600 hover:bg-slate-200/50 transition-all rounded-lg"><span class="material-symbols-outlined">leaderboard</span><span>Finance</span></a>
                <a href="employees.php" class="flex items-center gap-3 px-3 py-2.5 text-slate-600 hover:bg-slate-200/50 transition-all rounded-lg"><span class="material-symbols-outlined">group_add</span><span>Recruitment</span></a>
            <?php else: ?>
                <a href="employee_dashboard.php" class="flex items-center gap-3 px-3 py-2.5 text-slate-600 hover:bg-slate-200/50 transition-all rounded-lg"><span class="material-symbols-outlined">dashboard</span><span>Dashboard</span></a>
                <a href="machines.php" class="flex items-center gap-3 px-3 py-2.5 text-slate-600 hover:bg-slate-200/50 transition-all rounded-lg"><span class="material-symbols-outlined">local_laundry_service</span><span>Machines</span></a>
                <a href="my_shift.php" class="flex items-center gap-3 px-3 py-2.5 text-slate-600 hover:bg-slate-200/50 transition-all rounded-lg"><span class="material-symbols-outlined">event_note</span><span>My Shift</span></a>
                <a href="services.php" class="flex items-center gap-3 px-3 py-2.5 bg-white text-[#00666e] font-bold shadow-sm rounded-lg transition-all"><span class="material-symbols-outlined">payments</span><span>Services</span></a>
            <?php endif; ?>
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
        <span class="text-xs font-medium text-slate-500 uppercase tracking-widest mt-1"><?= $role ?> Portal</span>
    </div>
    <nav class="flex-1 flex flex-col gap-1 text-sm">
        <?php if($role === 'Admin'): ?>
            <a href="admin_dashboard.php" class="flex items-center gap-3 px-3 py-2 text-slate-600 hover:bg-slate-200/50 transition-all rounded-lg"><span class="material-symbols-outlined">dashboard</span><span>Dashboard</span></a>
            <a href="machines.php" class="flex items-center gap-3 px-3 py-2 text-slate-600 hover:bg-slate-200/50 transition-all rounded-lg"><span class="material-symbols-outlined">local_laundry_service</span><span>Machines</span></a>
            <a href="shifts.php" class="flex items-center gap-3 px-3 py-2 text-slate-600 hover:bg-slate-200/50 transition-all rounded-lg"><span class="material-symbols-outlined">event_note</span><span>Shifts</span></a>
            <a href="services.php" class="flex items-center gap-3 px-3 py-2 bg-white text-[#00666e] font-bold shadow-sm rounded-lg transition-all"><span class="material-symbols-outlined">payments</span><span>Services</span></a>
            <a href="finance.php" class="flex items-center gap-3 px-3 py-2 text-slate-600 hover:bg-slate-200/50 transition-all rounded-lg"><span class="material-symbols-outlined">leaderboard</span><span>Finance</span></a>
            <a href="employees.php" class="flex items-center gap-3 px-3 py-2 text-slate-600 hover:bg-slate-200/50 transition-all rounded-lg"><span class="material-symbols-outlined">group_add</span><span>Recruitment</span></a>
        <?php else: ?>
            <a href="employee_dashboard.php" class="flex items-center gap-3 px-3 py-2 text-slate-600 hover:bg-slate-200/50 transition-all rounded-lg"><span class="material-symbols-outlined">dashboard</span><span>Dashboard</span></a>
            <a href="machines.php" class="flex items-center gap-3 px-3 py-2 text-slate-600 hover:bg-slate-200/50 transition-all rounded-lg"><span class="material-symbols-outlined">local_laundry_service</span><span>Machines</span></a>
            <a href="my_shift.php" class="flex items-center gap-3 px-3 py-2 text-slate-600 hover:bg-slate-200/50 transition-all rounded-lg"><span class="material-symbols-outlined">event_note</span><span>My Shift</span></a>
            <a href="services.php" class="flex items-center gap-3 px-3 py-2 bg-white text-[#00666e] font-bold shadow-sm rounded-lg transition-all"><span class="material-symbols-outlined">payments</span><span>Services</span></a>
        <?php endif; ?>
    </nav>
    <div class="mt-auto border-t border-slate-200/50 pt-4 flex flex-col gap-1 text-sm">
        <a href="settings.php" class="flex items-center gap-3 px-3 py-2 text-slate-600 hover:bg-slate-200/50 transition-all rounded-lg"><span class="material-symbols-outlined">settings</span><span>Settings</span></a>
        <a href="logout.php" class="flex items-center gap-3 px-3 py-2 text-error hover:bg-red-50 transition-all rounded-lg font-semibold mt-1"><span class="material-symbols-outlined">logout</span><span>Logout</span></a>
    </div>
</aside>

<!-- Edit Service Modal -->
<div id="editSrvModal" class="fixed inset-0 z-[70] hidden">
    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" onclick="closeEditSrv()"></div>
    <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[90%] max-w-md bg-white rounded-2xl p-6 sm:p-8 shadow-2xl fade-in">
        <h3 class="text-xl font-bold font-headline mb-6 flex items-center gap-2"><span class="material-symbols-outlined text-primary">edit</span> Edit Layanan</h3>
        <form method="POST" class="space-y-4">
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="srv_id" id="edit_srv_id">
            <div class="space-y-2">
                <label class="text-xs font-bold uppercase tracking-wider text-gray-500">Nama Layanan</label>
                <input type="text" name="s_name" id="edit_srv_name" required class="w-full bg-gray-50 border-none rounded-lg px-4 py-3 focus:ring-2 focus:ring-primary">
            </div>
            <div class="space-y-2">
                <label class="text-xs font-bold uppercase tracking-wider text-gray-500">Harga / Unit (Rp)</label>
                <input type="number" name="s_price" id="edit_srv_price" required class="w-full bg-gray-50 border-none rounded-lg px-4 py-3 focus:ring-2 focus:ring-primary">
            </div>
            <div class="space-y-2">
                <label class="text-xs font-bold uppercase tracking-wider text-gray-500">Icon (Material Symbol)</label>
                <input type="text" name="icon" id="edit_srv_icon" class="w-full bg-gray-50 border-none rounded-lg px-4 py-3 focus:ring-2 focus:ring-primary" placeholder="e.g. local_laundry_service">
            </div>
            <div class="flex gap-3 pt-2">
                <button type="button" onclick="closeEditSrv()" class="flex-1 py-3 rounded-lg border border-gray-200 font-bold text-gray-500 hover:bg-gray-50 transition-all">Batal</button>
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
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-end gap-4">
            <div><h2 class="text-2xl sm:text-4xl font-extrabold font-headline tracking-tight">Catalogue</h2><p class="text-gray-500 mt-1">Layanan, harga, dan manajemen operasional pencucian.</p></div>
            <?php if($role === 'Admin'): ?>
            <button onclick="document.getElementById('addForm').classList.toggle('hidden')" class="action-gradient text-white px-6 py-3 rounded-full font-bold flex items-center gap-2 shadow-lg hover:scale-105 transition-transform w-full sm:w-auto justify-center">
                <span class="material-symbols-outlined">add_circle</span> Add Service
            </button>
            <?php endif; ?>
        </div>

        <?php if($role === 'Admin'): ?>
        <div id="addForm" class="hidden bg-white p-4 sm:p-6 rounded-xl shadow-sm border border-gray-100">
            <form method="POST" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-5 gap-4">
                <input type="hidden" name="action" value="add">
                <input type="text" name="s_name" placeholder="Nama Layanan (ex: Cuci Karpet)" required class="bg-gray-50 border-none rounded-lg focus:ring-primary sm:col-span-2 text-sm">
                <input type="number" name="s_price" placeholder="Harga / Unit (Rp)" required class="bg-gray-50 border-none rounded-lg focus:ring-primary text-sm">
                <input type="text" name="icon" placeholder="Icon (e.g. laundry)" required class="bg-gray-50 border-none rounded-lg focus:ring-primary text-sm">
                <button type="submit" class="bg-primary text-white font-bold rounded-lg text-sm hover:opacity-90">Simpan</button>
            </form>
        </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4 sm:gap-6">
            <?php foreach($services as $srv): ?>
            <div class="group bg-white p-5 sm:p-6 rounded-xl shadow-sm border border-gray-100 relative overflow-hidden flex flex-col hover:-translate-y-1 transition-all">
                <div class="absolute -right-4 -top-4 text-primary/5 group-hover:text-primary/10 transition-colors"><span class="material-symbols-outlined text-[100px] sm:text-[120px]"><?= htmlspecialchars($srv['icon']) ?></span></div>
                <div class="relative z-10">
                    <div class="w-12 h-12 rounded-xl bg-secondary flex items-center justify-center text-[#004e61] mb-4 sm:mb-6"><span class="material-symbols-outlined"><?= htmlspecialchars($srv['icon']) ?></span></div>
                    <h3 class="font-headline text-lg sm:text-xl font-bold text-gray-800 mb-4 sm:mb-6"><?= htmlspecialchars($srv['service_name']) ?></h3>
                </div>
                <div class="relative z-10 pt-4 border-t border-gray-100 flex items-center justify-between mt-auto">
                    <span class="text-xl sm:text-2xl font-black text-primary">Rp <?= number_format($srv['price'],0,',','.') ?></span>
                    <?php if($role === 'Admin'): ?>
                    <div class="flex items-center gap-1">
                        <button onclick="openEditSrv(<?= $srv['id'] ?>, '<?= htmlspecialchars($srv['service_name'], ENT_QUOTES) ?>', <?= $srv['price'] ?>, '<?= htmlspecialchars($srv['icon'], ENT_QUOTES) ?>')" class="p-2 text-gray-400 hover:text-primary hover:bg-primary/10 rounded-full transition-colors">
                            <span class="material-symbols-outlined text-xl">edit</span>
                        </button>
                        <form method="POST" class="inline" onsubmit="return confirm('Hapus layanan ini?')">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="srv_id" value="<?= $srv['id'] ?>">
                            <button type="submit" class="p-2 text-gray-400 hover:text-error hover:bg-red-50 rounded-full transition-colors"><span class="material-symbols-outlined text-xl">delete</span></button>
                        </form>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <footer class="mt-auto py-6 border-t border-gray-200 text-center text-slate-400 text-xs font-medium w-full">© 2024 JoyOps Operations</footer>
</main>
<script>
function toggleMobileMenu(){document.getElementById('mobileSidebar').classList.toggle('hidden');}
function openEditSrv(id, name, price, icon) {
    document.getElementById('edit_srv_id').value = id;
    document.getElementById('edit_srv_name').value = name;
    document.getElementById('edit_srv_price').value = price;
    document.getElementById('edit_srv_icon').value = icon;
    document.getElementById('editSrvModal').classList.remove('hidden');
}
function closeEditSrv() { document.getElementById('editSrvModal').classList.add('hidden'); }
var NR='<?= $role ?>';function toggleNotifs(){var p=document.getElementById('notifPanel');p.classList.toggle('hidden');if(!p.classList.contains('hidden'))loadNotifs();}function loadNotifs(){fetch('notifications_api.php?action=fetch&role='+NR).then(function(r){return r.json()}).then(function(d){var l=document.getElementById('notifList'),b=document.getElementById('notifBadge');if(d.unread>0){b.classList.remove('hidden');b.textContent=d.unread>9?'9+':d.unread;}else{b.classList.add('hidden');}if(!d.notifications||!d.notifications.length){l.innerHTML='<div class="p-8 text-center text-gray-400 text-sm">Tidak ada notifikasi</div>';return;}l.innerHTML=d.notifications.map(function(n){var ic={info:'info',success:'check_circle',warning:'warning',error:'error'};var cl={info:'text-[#00666e] bg-[#e2f6ff]',success:'text-green-600 bg-green-50',warning:'text-[#705900] bg-[#fdd34d]/20',error:'text-[#b31b25] bg-red-50'};return'<div class="px-5 py-3 border-b border-gray-50 flex gap-3 '+(n.is_read?'opacity-50':'')+' hover:bg-gray-50 transition-colors cursor-default"><div class="w-9 h-9 rounded-full '+(cl[n.type]||cl.info)+' flex items-center justify-center shrink-0"><span class="material-symbols-outlined text-lg">'+(ic[n.type]||'info')+'</span></div><div class="flex-1 min-w-0"><p class="text-sm font-bold text-gray-800 truncate">'+n.title+'</p><p class="text-xs text-gray-500 mt-0.5">'+n.message+'</p><p class="text-[10px] text-gray-400 mt-1">'+n.time_ago+'</p></div></div>';}).join('');}).catch(function(){});}function markAllRead(){fetch('notifications_api.php?action=mark_read&role='+NR).then(function(){loadNotifs();});}
document.addEventListener('click',function(e){var c=document.getElementById('notifContainer');if(c&&!c.contains(e.target)){var p=document.getElementById('notifPanel');if(p)p.classList.add('hidden');}});document.addEventListener('DOMContentLoaded',function(){fetch('notifications_api.php?action=count&role='+NR).then(function(r){return r.json();}).then(function(d){var b=document.getElementById('notifBadge');if(d.count>0){b.classList.remove('hidden');b.textContent=d.count>9?'9+':d.count;}}).catch(function(){});});
</script>
</body></html>