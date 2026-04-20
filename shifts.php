<?php
session_start();
require_once 'config.php';
require_once 'helpers.php';
initNotifications($pdo);

// Pastikan hanya admin yang bisa mengakses
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') { 
    header("Location: login.php"); 
    exit; 
}

$user_name = $_SESSION['name'];
$role = $_SESSION['role'];
$initial = substr($_SESSION['name'], 0, 1);
$pageTitle = 'Shift Management';

// --- CRUD Operations ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    // ADD Shift
    if ($action === 'add_shift') {
        $user_id = $_POST['user_id'];
        $day_name = $_POST['work_day'];
        $start = $_POST['start_time'];
        $end = $_POST['end_time'];
        $station = $_POST['station'];
        $work_date = date('Y-m-d', strtotime($day_name . ' this week'));
        
        $stmt = $pdo->prepare("INSERT INTO shifts (user_id, work_day, work_date, start_time, end_time, station) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$user_id, $day_name, $work_date, $start, $end, $station]);
        addNotification($pdo, 'Shift Ditambahkan', 'Shift baru hari ' . $day_name . ' (' . $start . ' - ' . $end . ') di stasiun ' . $station . '.', 'success', 'All');
        header("Location: shifts.php"); exit;
    }
    
    // UPDATE Shift
    if ($action === 'update_shift') {
        $day_name = $_POST['work_day'];
        $work_date = date('Y-m-d', strtotime($day_name . ' this week'));
        
        $stmt = $pdo->prepare("UPDATE shifts SET user_id = ?, work_day = ?, work_date = ?, start_time = ?, end_time = ?, station = ? WHERE id = ?");
        $stmt->execute([$_POST['user_id'], $day_name, $work_date, $_POST['start_time'], $_POST['end_time'], $_POST['station'], $_POST['shift_id']]);
        addNotification($pdo, 'Shift Diperbarui', 'Jadwal shift #' . $_POST['shift_id'] . ' telah diperbarui.', 'info', 'All');
        header("Location: shifts.php"); exit;
    }
    
    // DELETE Shift
    if ($action === 'delete_shift') {
        $stmt = $pdo->prepare("DELETE FROM shifts WHERE id = ?");
        $stmt->execute([$_POST['shift_id']]);
        addNotification($pdo, 'Shift Dihapus', 'Jadwal shift #' . $_POST['shift_id'] . ' telah dihapus.', 'warning', 'All');
        header("Location: shifts.php"); exit;
    }
}

$employees = $pdo->query("SELECT id, name FROM users WHERE role = 'Karyawan'")->fetchAll();
$shifts = $pdo->query("SELECT s.*, u.name as employee_name FROM shifts s JOIN users u ON s.user_id = u.id ORDER BY s.work_date ASC, s.start_time ASC")->fetchAll();

$days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
$today_name = date('l');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8"/><meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title><?= $pageTitle ?> | JoyOps</title>
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
            <a href="shifts.php" class="flex items-center gap-3 px-3 py-2.5 bg-white text-[#00666e] font-bold shadow-sm rounded-lg transition-all"><span class="material-symbols-outlined">event_note</span><span>Shifts</span></a>
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
        <a href="admin_dashboard.php" class="flex items-center gap-3 px-3 py-2 text-slate-600 hover:bg-slate-200/50 transition-all rounded-lg"><span class="material-symbols-outlined">dashboard</span><span>Dashboard</span></a>
        <a href="machines.php" class="flex items-center gap-3 px-3 py-2 text-slate-600 hover:bg-slate-200/50 transition-all rounded-lg"><span class="material-symbols-outlined">local_laundry_service</span><span>Machines</span></a>
        <a href="shifts.php" class="flex items-center gap-3 px-3 py-2 bg-white text-[#00666e] font-bold shadow-sm rounded-lg transition-all"><span class="material-symbols-outlined">event_note</span><span>Shifts</span></a>
        <a href="services.php" class="flex items-center gap-3 px-3 py-2 text-slate-600 hover:bg-slate-200/50 transition-all rounded-lg"><span class="material-symbols-outlined">payments</span><span>Services</span></a>
        <a href="finance.php" class="flex items-center gap-3 px-3 py-2 text-slate-600 hover:bg-slate-200/50 transition-all rounded-lg"><span class="material-symbols-outlined">leaderboard</span><span>Finance</span></a>
        <a href="employees.php" class="flex items-center gap-3 px-3 py-2 text-slate-600 hover:bg-slate-200/50 transition-all rounded-lg"><span class="material-symbols-outlined">group_add</span><span>Recruitment</span></a>
    </nav>
    <div class="mt-auto border-t border-slate-200/50 pt-4 flex flex-col gap-1 text-sm">
        <a href="settings.php" class="flex items-center gap-3 px-3 py-2 text-slate-600 hover:bg-slate-200/50 transition-all rounded-lg"><span class="material-symbols-outlined">settings</span><span>Settings</span></a>
        <a href="logout.php" class="flex items-center gap-3 px-3 py-2 text-error hover:bg-red-50 transition-all rounded-lg font-semibold mt-1"><span class="material-symbols-outlined">logout</span><span>Logout</span></a>
    </div>
</aside>

<!-- Edit Shift Modal -->
<div id="editShiftModal" class="fixed inset-0 z-[70] hidden">
    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" onclick="closeEditShift()"></div>
    <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[90%] max-w-lg bg-white rounded-2xl p-6 sm:p-8 shadow-2xl fade-in">
        <h3 class="text-xl font-bold font-headline mb-6 flex items-center gap-2"><span class="material-symbols-outlined text-primary">edit_calendar</span> Edit Shift</h3>
        <form method="POST" class="space-y-4">
            <input type="hidden" name="action" value="update_shift">
            <input type="hidden" name="shift_id" id="edit_shift_id">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="space-y-2 sm:col-span-2">
                    <label class="text-xs font-bold uppercase tracking-wider text-gray-500">Karyawan</label>
                    <select name="user_id" id="edit_shift_user" required class="w-full bg-gray-50 border-none rounded-lg px-4 py-3 focus:ring-2 focus:ring-primary text-sm font-bold">
                        <?php foreach($employees as $e): ?>
                            <option value="<?= $e['id'] ?>"><?= htmlspecialchars($e['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="space-y-2">
                    <label class="text-xs font-bold uppercase tracking-wider text-gray-500">Hari</label>
                    <select name="work_day" id="edit_shift_day" required class="w-full bg-gray-50 border-none rounded-lg px-4 py-3 focus:ring-2 focus:ring-primary text-sm font-bold">
                        <option value="Monday">Senin</option>
                        <option value="Tuesday">Selasa</option>
                        <option value="Wednesday">Rabu</option>
                        <option value="Thursday">Kamis</option>
                        <option value="Friday">Jumat</option>
                        <option value="Saturday">Sabtu</option>
                        <option value="Sunday">Minggu</option>
                    </select>
                </div>
                <div class="space-y-2">
                    <label class="text-xs font-bold uppercase tracking-wider text-gray-500">Station</label>
                    <input type="text" name="station" id="edit_shift_station" required class="w-full bg-gray-50 border-none rounded-lg px-4 py-3 focus:ring-2 focus:ring-primary text-sm">
                </div>
                <div class="space-y-2">
                    <label class="text-xs font-bold uppercase tracking-wider text-gray-500">Jam Mulai</label>
                    <input type="time" name="start_time" id="edit_shift_start" required class="w-full bg-gray-50 border-none rounded-lg px-4 py-3 focus:ring-2 focus:ring-primary text-sm font-bold">
                </div>
                <div class="space-y-2">
                    <label class="text-xs font-bold uppercase tracking-wider text-gray-500">Jam Selesai</label>
                    <input type="time" name="end_time" id="edit_shift_end" required class="w-full bg-gray-50 border-none rounded-lg px-4 py-3 focus:ring-2 focus:ring-primary text-sm font-bold">
                </div>
            </div>
            <div class="flex gap-3 pt-2">
                <button type="button" onclick="closeEditShift()" class="flex-1 py-3 rounded-lg border border-gray-200 font-bold text-gray-500 hover:bg-gray-50 transition-all">Batal</button>
                <button type="submit" class="flex-1 py-3 action-gradient text-white font-bold rounded-lg shadow-md hover:opacity-90 transition-all">Simpan</button>
            </div>
        </form>
    </div>
</div>

<main class="md:ml-64 flex-1 flex flex-col min-h-screen w-full">
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
                <div class="text-right hidden sm:block"><p class="text-sm font-bold text-slate-800"><?= htmlspecialchars($user_name) ?></p><p class="text-xs text-slate-500"><?= $role ?></p></div>
                <div class="w-10 h-10 rounded-full bg-[#00666e] text-white flex items-center justify-center font-bold text-lg shadow-sm"><?= $initial ?></div>
            </div>
        </div>
    </header>

    <div class="p-4 sm:p-8 space-y-6 sm:space-y-8 w-full mx-auto max-w-7xl flex-1">
        
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-end gap-4">
            <div>
                <h2 class="text-2xl sm:text-4xl font-extrabold font-headline tracking-tight">Jadwal Staf</h2>
                <p class="text-gray-500 mt-1 sm:mt-2 font-medium">Buat dan pantau jadwal operasional harian karyawan.</p>
            </div>
            <button onclick="document.getElementById('addShiftForm').classList.toggle('hidden')" class="action-gradient text-white font-bold px-6 py-3 rounded-full shadow-lg flex items-center gap-2 hover:scale-105 transition-transform active:scale-95 w-full sm:w-auto justify-center">
                <span class="material-symbols-outlined">add</span> Add Shift
            </button>
        </div>

        <!-- Add Shift Form -->
        <div id="addShiftForm" class="hidden bg-white p-4 sm:p-6 rounded-xl shadow-sm border border-gray-100">
            <form method="POST" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-6 gap-4">
                <input type="hidden" name="action" value="add_shift">
                <select name="user_id" required class="bg-gray-50 border-none rounded-lg focus:ring-[#00666e] sm:col-span-2 text-sm font-bold text-gray-600">
                    <option value="">-- Pilih Karyawan --</option>
                    <?php foreach($employees as $e): ?>
                        <option value="<?= $e['id'] ?>"><?= htmlspecialchars($e['name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <select name="work_day" required class="bg-gray-50 border-none rounded-lg focus:ring-[#00666e] text-sm font-bold text-gray-600">
                    <option value="Monday">Senin</option>
                    <option value="Tuesday">Selasa</option>
                    <option value="Wednesday">Rabu</option>
                    <option value="Thursday">Kamis</option>
                    <option value="Friday">Jumat</option>
                    <option value="Saturday">Sabtu</option>
                    <option value="Sunday">Minggu</option>
                </select>
                <input type="time" name="start_time" required class="bg-gray-50 border-none rounded-lg text-sm focus:ring-[#00666e] font-bold text-gray-600">
                <input type="time" name="end_time" required class="bg-gray-50 border-none rounded-lg text-sm focus:ring-[#00666e] font-bold text-gray-600">
                <input type="text" name="station" placeholder="Station (e.g. Wash A)" required class="bg-gray-50 border-none rounded-lg text-sm focus:ring-[#00666e]">
                <button type="submit" class="bg-[#00666e] text-white font-bold py-2 rounded-lg sm:col-span-2 md:col-span-6 hover:opacity-90 transition-opacity">Simpan Shift</button>
            </form>
        </div>

        <!-- Weekly Overview -->
        <section class="overflow-x-auto">
            <div class="grid grid-cols-7 gap-2 sm:gap-4 min-w-[500px]">
                <div class="col-span-7 flex items-center justify-between mb-2">
                    <h3 class="font-headline text-lg sm:text-xl font-bold text-[#00666e]">Weekly Overview (This Week)</h3>
                </div>
                
                <?php foreach($days as $d): 
                    $date_val = date('d', strtotime($d . ' this week'));
                    $is_today = ($d === $today_name);
                    
                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM shifts WHERE work_day = ? AND work_date >= ?");
                    $stmt->execute([$d, date('Y-m-d', strtotime('monday this week'))]);
                    $count = $stmt->fetchColumn();
                ?>
                <div class="flex flex-col gap-2 sm:gap-3 <?= $count == 0 && !$is_today ? 'opacity-60' : '' ?>">
                    <div class="text-center pb-2 <?= $is_today ? 'border-b-2 border-[#00666e]' : 'border-b border-gray-200' ?>">
                        <span class="block text-xs font-bold uppercase tracking-tighter <?= $is_today ? 'text-[#00666e]' : 'text-gray-500' ?>"><?= substr($d,0,3) ?></span>
                        <span class="text-base sm:text-lg font-bold <?= $is_today ? 'text-[#00666e]' : 'text-gray-800' ?>"><?= $date_val ?></span>
                    </div>
                    <?php if($count > 0): ?>
                        <div class="<?= $is_today ? 'bg-[#00666e] text-white shadow-md' : 'bg-white border border-gray-100 shadow-sm' ?> p-2 sm:p-3 rounded-xl text-center">
                            <p class="text-[10px] font-bold uppercase <?= $is_today ? 'text-[#6ce9f6]' : 'text-gray-400' ?>">Scheduled</p>
                            <p class="text-xs font-black mt-1"><?= $count ?> Staff</p>
                        </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </section>

        <!-- Shift Table -->
        <section class="bg-white rounded-2xl sm:rounded-3xl p-4 sm:p-6 shadow-sm border border-gray-100">
            <h3 class="font-headline text-lg sm:text-xl font-bold text-gray-800 mb-4 sm:mb-6">Daily Floor Assignments</h3>
            <div class="overflow-x-auto">
                <table class="w-full text-left min-w-[650px]">
                    <thead>
                        <tr class="text-gray-400 text-xs font-bold uppercase tracking-wider border-b border-gray-100">
                            <th class="pb-4 px-3 sm:px-4">Employee</th>
                            <th class="pb-4 px-3 sm:px-4">Station / Machine</th>
                            <th class="pb-4 px-3 sm:px-4">Shift Period</th>
                            <th class="pb-4 px-3 sm:px-4 text-center">Status</th>
                            <th class="pb-4 px-3 sm:px-4 text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        <?php if(empty($shifts)): ?>
                            <tr><td colspan="5" class="py-6 text-center text-gray-400 font-medium">Belum ada jadwal shift yang terdaftar.</td></tr>
                        <?php else: ?>
                            <?php foreach($shifts as $s): ?>
                            <tr class="hover:bg-gray-50 transition-colors group">
                                <td class="py-4 sm:py-5 px-3 sm:px-4 font-bold text-gray-800">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-full bg-[#00666e] text-white flex items-center justify-center text-xs shadow-sm shrink-0"><?= substr($s['employee_name'],0,1) ?></div>
                                        <span class="truncate"><?= htmlspecialchars($s['employee_name']) ?></span>
                                    </div>
                                </td>
                                <td class="py-4 sm:py-5 px-3 sm:px-4">
                                    <div class="flex items-center gap-2">
                                        <span class="material-symbols-outlined text-[#00666e] text-lg">local_laundry_service</span>
                                        <span class="font-medium text-gray-600"><?= htmlspecialchars($s['station']) ?></span>
                                    </div>
                                </td>
                                <td class="py-4 sm:py-5 px-3 sm:px-4 font-black text-[#00666e]">
                                    <?= substr($s['start_time'],0,5) ?> to <?= substr($s['end_time'],0,5) ?> 
                                    <span class="block text-xs text-gray-400 font-bold mt-0.5"><?= date('D, d M Y', strtotime($s['work_date'])) ?></span>
                                </td>
                                <td class="py-4 sm:py-5 px-3 sm:px-4 text-center">
                                    <?php 
                                        $status_color = 'bg-gray-100 text-gray-600';
                                        if($s['status'] == 'Clocked In') $status_color = 'bg-[#e2f6ff] text-[#00666e]';
                                        if($s['status'] == 'Completed') $status_color = 'bg-green-100 text-green-700';
                                    ?>
                                    <span class="<?= $status_color ?> px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-wider"><?= $s['status'] ?></span>
                                </td>
                                <td class="py-4 sm:py-5 px-3 sm:px-4 text-center">
                                    <div class="flex items-center justify-center gap-1">
                                        <button onclick="openEditShift(<?= $s['id'] ?>, <?= $s['user_id'] ?>, '<?= $s['work_day'] ?>', '<?= substr($s['start_time'],0,5) ?>', '<?= substr($s['end_time'],0,5) ?>', '<?= htmlspecialchars($s['station'], ENT_QUOTES) ?>')" class="p-1.5 text-gray-400 hover:text-primary hover:bg-primary/10 rounded-lg transition-colors">
                                            <span class="material-symbols-outlined text-lg">edit</span>
                                        </button>
                                        <form method="POST" class="inline" onsubmit="return confirm('Hapus shift ini?')">
                                            <input type="hidden" name="action" value="delete_shift">
                                            <input type="hidden" name="shift_id" value="<?= $s['id'] ?>">
                                            <button type="submit" class="p-1.5 text-gray-400 hover:text-error hover:bg-red-50 rounded-lg transition-colors">
                                                <span class="material-symbols-outlined text-lg">delete</span>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </div>

    <footer class="mt-auto py-6 border-t border-gray-200 text-center text-slate-400 text-xs font-medium w-full">
        © 2024 JoyOps Operations
    </footer>
</main>
<script>
function toggleMobileMenu(){document.getElementById('mobileSidebar').classList.toggle('hidden');}
function openEditShift(id, userId, day, start, end, station) {
    document.getElementById('edit_shift_id').value = id;
    document.getElementById('edit_shift_user').value = userId;
    document.getElementById('edit_shift_day').value = day;
    document.getElementById('edit_shift_start').value = start;
    document.getElementById('edit_shift_end').value = end;
    document.getElementById('edit_shift_station').value = station;
    document.getElementById('editShiftModal').classList.remove('hidden');
}
function closeEditShift() { document.getElementById('editShiftModal').classList.add('hidden'); }
var NR='<?= $role ?>';function toggleNotifs(){var p=document.getElementById('notifPanel');p.classList.toggle('hidden');if(!p.classList.contains('hidden'))loadNotifs();}function loadNotifs(){fetch('notifications_api.php?action=fetch&role='+NR).then(function(r){return r.json()}).then(function(d){var l=document.getElementById('notifList'),b=document.getElementById('notifBadge');if(d.unread>0){b.classList.remove('hidden');b.textContent=d.unread>9?'9+':d.unread;}else{b.classList.add('hidden');}if(!d.notifications||!d.notifications.length){l.innerHTML='<div class="p-8 text-center text-gray-400 text-sm">Tidak ada notifikasi</div>';return;}l.innerHTML=d.notifications.map(function(n){var ic={info:'info',success:'check_circle',warning:'warning',error:'error'};var cl={info:'text-[#00666e] bg-[#e2f6ff]',success:'text-green-600 bg-green-50',warning:'text-[#705900] bg-[#fdd34d]/20',error:'text-[#b31b25] bg-red-50'};return'<div class="px-5 py-3 border-b border-gray-50 flex gap-3 '+(n.is_read?'opacity-50':'')+' hover:bg-gray-50 transition-colors cursor-default"><div class="w-9 h-9 rounded-full '+(cl[n.type]||cl.info)+' flex items-center justify-center shrink-0"><span class="material-symbols-outlined text-lg">'+(ic[n.type]||'info')+'</span></div><div class="flex-1 min-w-0"><p class="text-sm font-bold text-gray-800 truncate">'+n.title+'</p><p class="text-xs text-gray-500 mt-0.5">'+n.message+'</p><p class="text-[10px] text-gray-400 mt-1">'+n.time_ago+'</p></div></div>';}).join('');}).catch(function(){});}function markAllRead(){fetch('notifications_api.php?action=mark_read&role='+NR).then(function(){loadNotifs();});}
document.addEventListener('click',function(e){var c=document.getElementById('notifContainer');if(c&&!c.contains(e.target)){var p=document.getElementById('notifPanel');if(p)p.classList.add('hidden');}});document.addEventListener('DOMContentLoaded',function(){fetch('notifications_api.php?action=count&role='+NR).then(function(r){return r.json();}).then(function(d){var b=document.getElementById('notifBadge');if(d.count>0){b.classList.remove('hidden');b.textContent=d.count>9?'9+':d.count;}}).catch(function(){});});
</script>
</body></html>