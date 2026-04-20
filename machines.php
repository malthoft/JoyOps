<?php
session_start();
require_once 'config.php';
require_once 'helpers.php';
initNotifications($pdo);
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }

$pageTitle = 'Machines';
$role = $_SESSION['role'];
$initial = substr($_SESSION['name'], 0, 1);
$msg = ''; $msg_type = '';

// --- CRUD Operations (Admin Only) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $role === 'Admin') {
    
    // ADD Machine
    if (isset($_POST['action']) && $_POST['action'] === 'add') {
        $stmt = $pdo->prepare("INSERT INTO machines (machine_code, machine_type) VALUES (?, ?)");
        $stmt->execute([$_POST['m_code'], $_POST['m_type']]);
        addNotification($pdo, 'Mesin Ditambahkan', 'Mesin ' . $_POST['m_code'] . ' (' . $_POST['m_type'] . ') berhasil ditambahkan.', 'success');
        header("Location: machines.php"); exit;
    }
    
    // EDIT Machine
    if (isset($_POST['action']) && $_POST['action'] === 'edit') {
        $stmt = $pdo->prepare("UPDATE machines SET machine_code = ?, machine_type = ? WHERE id = ?");
        $stmt->execute([$_POST['m_code'], $_POST['m_type'], $_POST['m_id']]);
        addNotification($pdo, 'Mesin Diperbarui', 'Data mesin ' . $_POST['m_code'] . ' telah diperbarui.', 'info');
        header("Location: machines.php"); exit;
    }
    
    // DELETE Machine
    if (isset($_POST['action']) && $_POST['action'] === 'delete') {
        $stmt = $pdo->prepare("DELETE FROM machines WHERE id = ?");
        $stmt->execute([$_POST['m_id']]);
        addNotification($pdo, 'Mesin Dihapus', 'Sebuah mesin telah dihapus dari sistem.', 'warning');
        header("Location: machines.php"); exit;
    }
}

// UPDATE Status (All roles)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update') {
    $id = $_POST['m_id'];
    $status = $_POST['status'];
    $stmt = $pdo->prepare("UPDATE machines SET status = ?, last_used = NOW() WHERE id = ?");
    $stmt->execute([$status, $id]);

    if ($status === 'Digunakan' && !empty($_POST['service_id']) && !empty($_POST['qty'])) {
        $srv = $pdo->prepare("SELECT price, service_name FROM services WHERE id = ?");
        $srv->execute([$_POST['service_id']]);
        $s = $srv->fetch();
        if ($s) {
            $total = $s['price'] * $_POST['qty'];
            $desc = "Mesin " . $_POST['m_code'] . " - " . $s['service_name'] . " (" . $_POST['qty'] . ")";
            $trx = $pdo->prepare("INSERT INTO transactions (transaction_date, transaction_type, amount, description) VALUES (CURDATE(), 'Pemasukan', ?, ?)");
            $trx->execute([$total, $desc]);
        }
    }
    addNotification($pdo, 'Status Mesin', 'Status mesin ' . $_POST['m_code'] . ' diubah ke ' . $_POST['status'] . '.', 'info', 'All');
    header("Location: machines.php"); exit;
}

$machines = $pdo->query("SELECT * FROM machines")->fetchAll();
$services = $pdo->query("SELECT * FROM services")->fetchAll();

// Status counts for summary
$status_counts = ['Tersedia' => 0, 'Digunakan' => 0, 'Maintenance' => 0];
foreach ($machines as $m) {
    if (isset($status_counts[$m['status']])) $status_counts[$m['status']]++;
}
$total_machines = count($machines);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8"/><meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Machines | JoyOps</title>
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
                <a href="machines.php" class="flex items-center gap-3 px-3 py-2.5 bg-white text-[#00666e] font-bold shadow-sm rounded-lg transition-all"><span class="material-symbols-outlined">local_laundry_service</span><span>Machines</span></a>
                <a href="shifts.php" class="flex items-center gap-3 px-3 py-2.5 text-slate-600 hover:bg-slate-200/50 transition-all rounded-lg"><span class="material-symbols-outlined">event_note</span><span>Shifts</span></a>
                <a href="services.php" class="flex items-center gap-3 px-3 py-2.5 text-slate-600 hover:bg-slate-200/50 transition-all rounded-lg"><span class="material-symbols-outlined">payments</span><span>Services</span></a>
                <a href="finance.php" class="flex items-center gap-3 px-3 py-2.5 text-slate-600 hover:bg-slate-200/50 transition-all rounded-lg"><span class="material-symbols-outlined">leaderboard</span><span>Finance</span></a>
                <a href="employees.php" class="flex items-center gap-3 px-3 py-2.5 text-slate-600 hover:bg-slate-200/50 transition-all rounded-lg"><span class="material-symbols-outlined">group_add</span><span>Recruitment</span></a>
            <?php else: ?>
                <a href="employee_dashboard.php" class="flex items-center gap-3 px-3 py-2.5 text-slate-600 hover:bg-slate-200/50 transition-all rounded-lg"><span class="material-symbols-outlined">dashboard</span><span>Dashboard</span></a>
                <a href="machines.php" class="flex items-center gap-3 px-3 py-2.5 bg-white text-[#00666e] font-bold shadow-sm rounded-lg transition-all"><span class="material-symbols-outlined">local_laundry_service</span><span>Machines</span></a>
                <a href="my_shift.php" class="flex items-center gap-3 px-3 py-2.5 text-slate-600 hover:bg-slate-200/50 transition-all rounded-lg"><span class="material-symbols-outlined">event_note</span><span>My Shift</span></a>
                <a href="services.php" class="flex items-center gap-3 px-3 py-2.5 text-slate-600 hover:bg-slate-200/50 transition-all rounded-lg"><span class="material-symbols-outlined">payments</span><span>Services</span></a>
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
            <a href="machines.php" class="flex items-center gap-3 px-3 py-2 bg-white text-[#00666e] font-bold shadow-sm rounded-lg transition-all"><span class="material-symbols-outlined">local_laundry_service</span><span>Machines</span></a>
            <a href="shifts.php" class="flex items-center gap-3 px-3 py-2 text-slate-600 hover:bg-slate-200/50 transition-all rounded-lg"><span class="material-symbols-outlined">event_note</span><span>Shifts</span></a>
            <a href="services.php" class="flex items-center gap-3 px-3 py-2 text-slate-600 hover:bg-slate-200/50 transition-all rounded-lg"><span class="material-symbols-outlined">payments</span><span>Services</span></a>
            <a href="finance.php" class="flex items-center gap-3 px-3 py-2 text-slate-600 hover:bg-slate-200/50 transition-all rounded-lg"><span class="material-symbols-outlined">leaderboard</span><span>Finance</span></a>
            <a href="employees.php" class="flex items-center gap-3 px-3 py-2 text-slate-600 hover:bg-slate-200/50 transition-all rounded-lg"><span class="material-symbols-outlined">group_add</span><span>Recruitment</span></a>
        <?php else: ?>
            <a href="employee_dashboard.php" class="flex items-center gap-3 px-3 py-2 text-slate-600 hover:bg-slate-200/50 transition-all rounded-lg"><span class="material-symbols-outlined">dashboard</span><span>Dashboard</span></a>
            <a href="machines.php" class="flex items-center gap-3 px-3 py-2 bg-white text-[#00666e] font-bold shadow-sm rounded-lg transition-all"><span class="material-symbols-outlined">local_laundry_service</span><span>Machines</span></a>
            <a href="my_shift.php" class="flex items-center gap-3 px-3 py-2 text-slate-600 hover:bg-slate-200/50 transition-all rounded-lg"><span class="material-symbols-outlined">event_note</span><span>My Shift</span></a>
            <a href="services.php" class="flex items-center gap-3 px-3 py-2 text-slate-600 hover:bg-slate-200/50 transition-all rounded-lg"><span class="material-symbols-outlined">payments</span><span>Services</span></a>
        <?php endif; ?>
    </nav>
    <div class="mt-auto border-t border-slate-200/50 pt-4 flex flex-col gap-1 text-sm">
        <a href="settings.php" class="flex items-center gap-3 px-3 py-2 text-slate-600 hover:bg-slate-200/50 transition-all rounded-lg"><span class="material-symbols-outlined">settings</span><span>Settings</span></a>
        <a href="logout.php" class="flex items-center gap-3 px-3 py-2 text-error hover:bg-red-50 transition-all rounded-lg font-semibold mt-1"><span class="material-symbols-outlined">logout</span><span>Logout</span></a>
    </div>
</aside>

<!-- Edit Modal -->
<div id="editModal" class="fixed inset-0 z-[70] hidden">
    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" onclick="closeEditModal()"></div>
    <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[90%] max-w-md bg-white rounded-2xl p-6 sm:p-8 shadow-2xl fade-in">
        <h3 class="text-xl font-bold font-headline mb-6 flex items-center gap-2"><span class="material-symbols-outlined text-primary">edit</span> Edit Machine</h3>
        <form method="POST" class="space-y-4">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="m_id" id="edit_m_id">
            <div class="space-y-2">
                <label class="text-xs font-bold uppercase tracking-wider text-gray-500">Kode Mesin</label>
                <input type="text" name="m_code" id="edit_m_code" required class="w-full bg-gray-50 border-none rounded-lg px-4 py-3 focus:ring-2 focus:ring-primary">
            </div>
            <div class="space-y-2">
                <label class="text-xs font-bold uppercase tracking-wider text-gray-500">Tipe Mesin</label>
                <input type="text" name="m_type" id="edit_m_type" required class="w-full bg-gray-50 border-none rounded-lg px-4 py-3 focus:ring-2 focus:ring-primary">
            </div>
            <div class="flex gap-3 pt-2">
                <button type="button" onclick="closeEditModal()" class="flex-1 py-3 rounded-lg border border-gray-200 font-bold text-gray-500 hover:bg-gray-50 transition-all">Batal</button>
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
        <!-- Status Summary Cards -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 sm:gap-4">
            <button onclick="filterMachines('all')" class="filter-btn active-filter bg-white p-4 rounded-xl shadow-sm border border-gray-100 text-center hover:shadow-md transition-all cursor-pointer" data-filter="all">
                <p class="text-2xl sm:text-3xl font-black text-gray-800"><?= $total_machines ?></p>
                <p class="text-xs font-bold text-gray-400 uppercase mt-1">Total</p>
            </button>
            <button onclick="filterMachines('Tersedia')" class="filter-btn bg-white p-4 rounded-xl shadow-sm border border-gray-100 text-center hover:shadow-md transition-all cursor-pointer" data-filter="Tersedia">
                <p class="text-2xl sm:text-3xl font-black text-primary"><?= $status_counts['Tersedia'] ?></p>
                <p class="text-xs font-bold text-gray-400 uppercase mt-1">Tersedia</p>
            </button>
            <button onclick="filterMachines('Digunakan')" class="filter-btn bg-white p-4 rounded-xl shadow-sm border border-gray-100 text-center hover:shadow-md transition-all cursor-pointer" data-filter="Digunakan">
                <p class="text-2xl sm:text-3xl font-black text-[#705900]"><?= $status_counts['Digunakan'] ?></p>
                <p class="text-xs font-bold text-gray-400 uppercase mt-1">Digunakan</p>
            </button>
            <button onclick="filterMachines('Maintenance')" class="filter-btn bg-white p-4 rounded-xl shadow-sm border border-gray-100 text-center hover:shadow-md transition-all cursor-pointer" data-filter="Maintenance">
                <p class="text-2xl sm:text-3xl font-black text-error"><?= $status_counts['Maintenance'] ?></p>
                <p class="text-xs font-bold text-gray-400 uppercase mt-1">Maintenance</p>
            </button>
        </div>

        <!-- Search + Add -->
        <div class="flex flex-col sm:flex-row gap-4 items-stretch sm:items-center">
            <div class="relative flex-1">
                <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-gray-400">search</span>
                <input type="text" id="searchMachine" oninput="searchMachines(this.value)" placeholder="Cari kode atau tipe mesin..." class="w-full pl-10 pr-4 py-3 bg-white border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary focus:border-transparent text-sm">
            </div>
            <?php if($role === 'Admin'): ?>
            <button onclick="document.getElementById('addForm').classList.toggle('hidden')" class="action-gradient text-white px-6 py-3 rounded-xl font-bold flex items-center gap-2 shadow-lg hover:scale-105 transition-transform justify-center shrink-0">
                <span class="material-symbols-outlined">add</span> Add Machine
            </button>
            <?php endif; ?>
        </div>

        <?php if($role === 'Admin'): ?>
        <div id="addForm" class="hidden bg-white p-4 sm:p-6 rounded-xl shadow-sm border border-gray-100">
            <form method="POST" class="flex flex-col sm:flex-row gap-4">
                <input type="hidden" name="action" value="add">
                <input type="text" name="m_code" placeholder="Kode Mesin (WM-01)" required class="bg-gray-50 border-none rounded-lg focus:ring-primary flex-1 text-sm">
                <input type="text" name="m_type" placeholder="Tipe (Washer 15kg)" required class="bg-gray-50 border-none rounded-lg focus:ring-primary flex-1 text-sm">
                <button type="submit" class="action-gradient text-white px-6 py-2 rounded-lg font-bold shadow-md hover:scale-105 transition-transform">Add Machine</button>
            </form>
        </div>
        <?php endif; ?>

        <!-- Machine Grid -->
        <div id="machineGrid" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4 sm:gap-6">
            <?php foreach($machines as $m): 
                $bg = $m['status'] == 'Tersedia' ? 'bg-[#e2f6ff] text-primary' : ($m['status'] == 'Digunakan' ? 'bg-[#fdd34d] text-[#5c4900]' : 'bg-red-100 text-error');
            ?>
            <article class="machine-card bg-white rounded-xl p-5 sm:p-6 shadow-sm border border-gray-100 flex flex-col group hover:shadow-md transition-shadow relative overflow-hidden" data-status="<?= $m['status'] ?>" data-code="<?= strtolower($m['machine_code']) ?>" data-type="<?= strtolower($m['machine_type']) ?>">
                <span class="material-symbols-outlined absolute -right-4 -bottom-4 text-[100px] sm:text-[120px] text-gray-50 pointer-events-none group-hover:scale-110 transition-transform">local_laundry_service</span>
                <div class="flex justify-between items-start mb-4 sm:mb-6 relative z-10">
                    <div>
                        <span class="text-xs font-bold text-gray-400 tracking-widest uppercase">ID: <?= $m['machine_code'] ?></span>
                        <h3 class="text-lg font-headline font-bold text-gray-800"><?= $m['machine_type'] ?></h3>
                    </div>
                    <span class="px-2 sm:px-3 py-1 rounded-full text-[10px] font-black uppercase <?= $bg ?>"><?= $m['status'] ?></span>
                </div>
                
                <?php if($role === 'Admin'): ?>
                <div class="flex gap-2 mb-3 relative z-10">
                    <button onclick="openEditModal(<?= $m['id'] ?>, '<?= htmlspecialchars($m['machine_code'], ENT_QUOTES) ?>', '<?= htmlspecialchars($m['machine_type'], ENT_QUOTES) ?>')" class="flex-1 py-2 bg-gray-50 text-gray-600 rounded-lg text-xs font-bold hover:bg-gray-100 transition-colors flex items-center justify-center gap-1">
                        <span class="material-symbols-outlined text-sm">edit</span> Edit
                    </button>
                    <form method="POST" class="flex-shrink-0" onsubmit="return confirm('Hapus mesin ini?')">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="m_id" value="<?= $m['id'] ?>">
                        <button type="submit" class="py-2 px-3 bg-red-50 text-error rounded-lg text-xs font-bold hover:bg-red-100 transition-colors flex items-center gap-1">
                            <span class="material-symbols-outlined text-sm">delete</span>
                        </button>
                    </form>
                </div>
                <?php endif; ?>

                <form method="POST" class="mt-auto border-t border-gray-100 pt-3 sm:pt-4 space-y-3 relative z-10">
                    <input type="hidden" name="action" value="update"><input type="hidden" name="m_id" value="<?= $m['id'] ?>"><input type="hidden" name="m_code" value="<?= $m['machine_code'] ?>">
                    <select name="status" class="w-full bg-gray-50 border-none rounded-lg text-sm font-bold focus:ring-primary" onchange="toggleSrv(this, <?= $m['id'] ?>)">
                        <option value="Tersedia" <?= $m['status'] == 'Tersedia' ? 'selected' : '' ?>>Tersedia</option>
                        <option value="Digunakan" <?= $m['status'] == 'Digunakan' ? 'selected' : '' ?>>Digunakan (Proses)</option>
                        <option value="Maintenance" <?= $m['status'] == 'Maintenance' ? 'selected' : '' ?>>Maintenance</option>
                    </select>

                    <div id="srv_<?= $m['id'] ?>" class="hidden bg-[#e2f6ff] p-3 rounded-lg space-y-2">
                        <p class="text-[10px] font-bold text-primary uppercase">Input Keuangan</p>
                        <select name="service_id" class="w-full bg-white border-none text-xs rounded-md">
                            <option value="">-- Pilih Layanan --</option>
                            <?php foreach($services as $s): ?><option value="<?= $s['id'] ?>"><?= $s['service_name'] ?> (Rp <?= number_format($s['price'],0) ?>)</option><?php endforeach; ?>
                        </select>
                        <input type="number" step="0.1" name="qty" placeholder="Qty / Kg" class="w-full bg-white border-none text-xs rounded-md">
                    </div>
                    <button type="submit" class="w-full py-2 bg-secondary text-[#004e61] font-bold rounded-lg text-sm hover:opacity-80 transition-all">Update Status</button>
                </form>
            </article>
            <?php endforeach; ?>
        </div>
    </div>

    <footer class="mt-auto py-6 border-t border-gray-200 text-center text-slate-400 text-xs font-medium w-full">© 2024 JoyOps Operations</footer>
</main>
<script>
function toggleMobileMenu(){document.getElementById('mobileSidebar').classList.toggle('hidden');}
function toggleSrv(sel, id) {
    let div = document.getElementById('srv_' + id);
    if(sel.value === 'Digunakan') div.classList.remove('hidden'); else div.classList.add('hidden');
}
function openEditModal(id, code, type) {
    document.getElementById('edit_m_id').value = id;
    document.getElementById('edit_m_code').value = code;
    document.getElementById('edit_m_type').value = type;
    document.getElementById('editModal').classList.remove('hidden');
}
function closeEditModal() { document.getElementById('editModal').classList.add('hidden'); }

function filterMachines(status) {
    document.querySelectorAll('.filter-btn').forEach(b => {
        b.classList.remove('ring-2','ring-primary','bg-primary/5');
        if(b.dataset.filter === status) b.classList.add('ring-2','ring-primary','bg-primary/5');
    });
    document.querySelectorAll('.machine-card').forEach(card => {
        if(status === 'all' || card.dataset.status === status) {
            card.style.display = '';
        } else {
            card.style.display = 'none';
        }
    });
}

function searchMachines(query) {
    query = query.toLowerCase();
    document.querySelectorAll('.machine-card').forEach(card => {
        const code = card.dataset.code;
        const type = card.dataset.type;
        if(code.includes(query) || type.includes(query)) {
            card.style.display = '';
        } else {
            card.style.display = 'none';
        }
    });
}
var NR='<?= $role ?>';function toggleNotifs(){var p=document.getElementById('notifPanel');p.classList.toggle('hidden');if(!p.classList.contains('hidden'))loadNotifs();}function loadNotifs(){fetch('notifications_api.php?action=fetch&role='+NR).then(function(r){return r.json()}).then(function(d){var l=document.getElementById('notifList'),b=document.getElementById('notifBadge');if(d.unread>0){b.classList.remove('hidden');b.textContent=d.unread>9?'9+':d.unread;}else{b.classList.add('hidden');}if(!d.notifications||!d.notifications.length){l.innerHTML='<div class="p-8 text-center text-gray-400 text-sm">Tidak ada notifikasi</div>';return;}l.innerHTML=d.notifications.map(function(n){var ic={info:'info',success:'check_circle',warning:'warning',error:'error'};var cl={info:'text-[#00666e] bg-[#e2f6ff]',success:'text-green-600 bg-green-50',warning:'text-[#705900] bg-[#fdd34d]/20',error:'text-[#b31b25] bg-red-50'};return'<div class="px-5 py-3 border-b border-gray-50 flex gap-3 '+(n.is_read?'opacity-50':'')+' hover:bg-gray-50 transition-colors cursor-default"><div class="w-9 h-9 rounded-full '+(cl[n.type]||cl.info)+' flex items-center justify-center shrink-0"><span class="material-symbols-outlined text-lg">'+(ic[n.type]||'info')+'</span></div><div class="flex-1 min-w-0"><p class="text-sm font-bold text-gray-800 truncate">'+n.title+'</p><p class="text-xs text-gray-500 mt-0.5">'+n.message+'</p><p class="text-[10px] text-gray-400 mt-1">'+n.time_ago+'</p></div></div>';}).join('');}).catch(function(){});}function markAllRead(){fetch('notifications_api.php?action=mark_read&role='+NR).then(function(){loadNotifs();});}
document.addEventListener('click',function(e){var c=document.getElementById('notifContainer');if(c&&!c.contains(e.target)){var p=document.getElementById('notifPanel');if(p)p.classList.add('hidden');}});document.addEventListener('DOMContentLoaded',function(){fetch('notifications_api.php?action=count&role='+NR).then(function(r){return r.json();}).then(function(d){var b=document.getElementById('notifBadge');if(d.count>0){b.classList.remove('hidden');b.textContent=d.count>9?'9+':d.count;}}).catch(function(){});});
</script>
</body></html>