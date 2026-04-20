<?php
session_start();
require_once 'config.php';
require_once 'helpers.php';
initNotifications($pdo);

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') { header("Location: login.php"); exit; }

$pageTitle = 'Finance';
$role = $_SESSION['role'];
$initial = substr($_SESSION['name'], 0, 1);

// --- CRUD Operations ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'add';
    
    if ($action === 'add') {
        $stmt = $pdo->prepare("INSERT INTO transactions (transaction_date, transaction_type, amount, description) VALUES (?, ?, ?, ?)");
        $stmt->execute([$_POST['date'], $_POST['type'], $_POST['amount'], $_POST['desc']]);
        addNotification($pdo, 'Transaksi Baru', $_POST['type'] . ' sebesar Rp ' . number_format($_POST['amount'],0,',','.') . ' — ' . $_POST['desc'], $_POST['type'] == 'Pemasukan' ? 'success' : 'warning', 'Admin');
        header("Location: finance.php"); exit;
    }
    
    if ($action === 'update') {
        $stmt = $pdo->prepare("UPDATE transactions SET transaction_date = ?, transaction_type = ?, amount = ?, description = ? WHERE id = ?");
        $stmt->execute([$_POST['date'], $_POST['type'], $_POST['amount'], $_POST['desc'], $_POST['trx_id']]);
        addNotification($pdo, 'Transaksi Diperbarui', 'Transaksi #' . $_POST['trx_id'] . ' telah diperbarui.', 'info', 'Admin');
        header("Location: finance.php"); exit;
    }
    
    if ($action === 'delete') {
        $stmt = $pdo->prepare("DELETE FROM transactions WHERE id = ?");
        $stmt->execute([$_POST['trx_id']]);
        addNotification($pdo, 'Transaksi Dihapus', 'Transaksi #' . $_POST['trx_id'] . ' telah dihapus.', 'warning', 'Admin');
        header("Location: finance.php"); exit;
    }
}

$rev = $pdo->query("SELECT SUM(amount) FROM transactions WHERE transaction_type='Pemasukan'")->fetchColumn() ?: 0;
$exp = $pdo->query("SELECT SUM(amount) FROM transactions WHERE transaction_type='Pengeluaran'")->fetchColumn() ?: 0;
$net = $rev - $exp;
$trx = $pdo->query("SELECT * FROM transactions ORDER BY transaction_date DESC, id DESC LIMIT 30")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8"/><meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Finance | JoyOps</title>
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
            <a href="finance.php" class="flex items-center gap-3 px-3 py-2.5 bg-white text-[#00666e] font-bold shadow-sm rounded-lg transition-all"><span class="material-symbols-outlined">leaderboard</span><span>Finance</span></a>
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
        <a href="shifts.php" class="flex items-center gap-3 px-3 py-2 text-slate-600 hover:bg-slate-200/50 transition-all rounded-lg"><span class="material-symbols-outlined">event_note</span><span>Shifts</span></a>
        <a href="services.php" class="flex items-center gap-3 px-3 py-2 text-slate-600 hover:bg-slate-200/50 transition-all rounded-lg"><span class="material-symbols-outlined">payments</span><span>Services</span></a>
        <a href="finance.php" class="flex items-center gap-3 px-3 py-2 bg-white text-[#00666e] font-bold shadow-sm rounded-lg transition-all"><span class="material-symbols-outlined">leaderboard</span><span>Finance</span></a>
        <a href="employees.php" class="flex items-center gap-3 px-3 py-2 text-slate-600 hover:bg-slate-200/50 transition-all rounded-lg"><span class="material-symbols-outlined">group_add</span><span>Recruitment</span></a>
    </nav>
    <div class="mt-auto border-t border-slate-200/50 pt-4 flex flex-col gap-1 text-sm">
        <a href="settings.php" class="flex items-center gap-3 px-3 py-2 text-slate-600 hover:bg-slate-200/50 transition-all rounded-lg"><span class="material-symbols-outlined">settings</span><span>Settings</span></a>
        <a href="logout.php" class="flex items-center gap-3 px-3 py-2 text-error hover:bg-red-50 transition-all rounded-lg font-semibold mt-1"><span class="material-symbols-outlined">logout</span><span>Logout</span></a>
    </div>
</aside>

<!-- Edit Transaction Modal -->
<div id="editTrxModal" class="fixed inset-0 z-[70] hidden">
    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" onclick="closeEditTrx()"></div>
    <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[90%] max-w-lg bg-white rounded-2xl p-6 sm:p-8 shadow-2xl fade-in">
        <h3 class="text-xl font-bold font-headline mb-6 flex items-center gap-2"><span class="material-symbols-outlined text-primary">edit</span> Edit Transaksi</h3>
        <form method="POST" class="space-y-4">
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="trx_id" id="edit_trx_id">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="space-y-2">
                    <label class="text-xs font-bold uppercase tracking-wider text-gray-500">Tanggal</label>
                    <input type="date" name="date" id="edit_trx_date" required class="w-full bg-gray-50 border-none rounded-lg px-4 py-3 focus:ring-2 focus:ring-primary">
                </div>
                <div class="space-y-2">
                    <label class="text-xs font-bold uppercase tracking-wider text-gray-500">Tipe</label>
                    <select name="type" id="edit_trx_type" required class="w-full bg-gray-50 border-none rounded-lg px-4 py-3 focus:ring-2 focus:ring-primary">
                        <option value="Pemasukan">Pemasukan (+)</option>
                        <option value="Pengeluaran">Pengeluaran (-)</option>
                    </select>
                </div>
            </div>
            <div class="space-y-2">
                <label class="text-xs font-bold uppercase tracking-wider text-gray-500">Nominal (Rp)</label>
                <input type="number" name="amount" id="edit_trx_amount" required class="w-full bg-gray-50 border-none rounded-lg px-4 py-3 focus:ring-2 focus:ring-primary">
            </div>
            <div class="space-y-2">
                <label class="text-xs font-bold uppercase tracking-wider text-gray-500">Deskripsi</label>
                <input type="text" name="desc" id="edit_trx_desc" required class="w-full bg-gray-50 border-none rounded-lg px-4 py-3 focus:ring-2 focus:ring-primary">
            </div>
            <div class="flex gap-3 pt-2">
                <button type="button" onclick="closeEditTrx()" class="flex-1 py-3 rounded-lg border border-gray-200 font-bold text-gray-500 hover:bg-gray-50 transition-all">Batal</button>
                <button type="submit" class="flex-1 py-3 action-gradient text-white font-bold rounded-lg shadow-md hover:opacity-90 transition-all">Update</button>
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
        <!-- Header + Actions -->
        <section class="flex flex-col gap-4">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-end gap-4">
                <div>
                    <h2 class="text-2xl sm:text-4xl font-extrabold font-headline tracking-tight mb-1">Finance Overview</h2>
                    <p class="text-gray-500">Track operations revenue and expenses.</p>
                </div>
                <div class="flex flex-wrap gap-2 w-full sm:w-auto">
                    <button onclick="document.getElementById('trxForm').classList.toggle('hidden')" class="action-gradient text-white px-4 py-2.5 rounded-xl font-bold flex items-center gap-2 shadow-lg hover:scale-105 transition-transform flex-1 sm:flex-initial justify-center text-sm">
                        <span class="material-symbols-outlined text-lg">add</span> Transaksi
                    </button>
                    <a href="export_finance.php?format=csv" class="bg-white border border-gray-200 text-gray-700 px-4 py-2.5 rounded-xl font-bold flex items-center gap-2 hover:bg-gray-50 transition-colors flex-1 sm:flex-initial justify-center text-sm shadow-sm">
                        <span class="material-symbols-outlined text-lg text-green-600">table_view</span> Excel
                    </a>
                    <a href="export_finance.php?format=pdf" target="_blank" class="bg-white border border-gray-200 text-gray-700 px-4 py-2.5 rounded-xl font-bold flex items-center gap-2 hover:bg-gray-50 transition-colors flex-1 sm:flex-initial justify-center text-sm shadow-sm">
                        <span class="material-symbols-outlined text-lg text-red-500">picture_as_pdf</span> PDF
                    </a>
                </div>
            </div>
        </section>

        <!-- Add Transaction Form -->
        <div id="trxForm" class="hidden bg-white p-4 sm:p-6 rounded-xl shadow-sm border border-gray-100">
            <h4 class="font-headline font-bold text-sm mb-4 flex items-center gap-2"><span class="material-symbols-outlined text-primary text-lg">receipt_long</span> Tambah Transaksi Baru</h4>
            <form method="POST" class="space-y-4">
                <input type="hidden" name="action" value="add">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <input type="date" name="date" required value="<?= date('Y-m-d') ?>" class="bg-gray-50 border-none rounded-lg focus:ring-primary text-sm">
                    <select name="type" required class="bg-gray-50 border-none rounded-lg focus:ring-primary text-sm">
                        <option value="Pemasukan">Pemasukan (+)</option><option value="Pengeluaran">Pengeluaran (-)</option>
                    </select>
                    <input type="number" name="amount" placeholder="Nominal (Rp)" required class="bg-gray-50 border-none rounded-lg focus:ring-primary text-sm">
                    <input type="text" name="desc" placeholder="Deskripsi transaksi" required class="bg-gray-50 border-none rounded-lg focus:ring-primary text-sm">
                </div>
                <button type="submit" class="w-full bg-primary text-white font-bold py-3 rounded-lg hover:opacity-90 transition-opacity">Simpan Transaksi</button>
            </form>
        </div>

        <!-- Summary Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="bg-white p-5 sm:p-8 rounded-xl shadow-sm border border-gray-100 relative overflow-hidden">
                <span class="material-symbols-outlined absolute -right-4 -bottom-4 text-7xl sm:text-8xl text-primary/5">payments</span>
                <p class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-1">Total Revenue</p>
                <h3 class="text-xl sm:text-3xl font-extrabold font-headline text-primary">Rp <?= number_format($rev, 0, ',', '.') ?></h3>
            </div>
            <div class="bg-white p-5 sm:p-8 rounded-xl shadow-sm border border-gray-100 relative overflow-hidden">
                <span class="material-symbols-outlined absolute -right-4 -bottom-4 text-7xl sm:text-8xl text-error/5">account_balance_wallet</span>
                <p class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-1">Total Expenses</p>
                <h3 class="text-xl sm:text-3xl font-extrabold font-headline text-gray-800">Rp <?= number_format($exp, 0, ',', '.') ?></h3>
            </div>
            <div class="bg-white p-5 sm:p-8 rounded-xl shadow-sm border border-gray-100 relative overflow-hidden">
                <span class="material-symbols-outlined absolute -right-4 -bottom-4 text-7xl sm:text-8xl <?= $net >= 0 ? 'text-green-500/5' : 'text-error/5' ?>">trending_up</span>
                <p class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-1">Net Profit</p>
                <h3 class="text-xl sm:text-3xl font-extrabold font-headline <?= $net >= 0 ? 'text-green-600' : 'text-error' ?>">
                    <?= $net >= 0 ? '+' : '-' ?>Rp <?= number_format(abs($net), 0, ',', '.') ?>
                </h3>
            </div>
        </div>

        <!-- MOBILE: Transaction Cards -->
        <section class="sm:hidden space-y-3">
            <div class="flex justify-between items-center">
                <h4 class="font-headline font-bold text-lg">Recent Transactions</h4>
                <span class="text-xs text-gray-400 font-medium"><?= count($trx) ?> items</span>
            </div>
            <?php if(empty($trx)): ?>
                <div class="bg-white p-8 rounded-xl text-center text-gray-400 shadow-sm border border-gray-100">
                    <span class="material-symbols-outlined text-4xl text-gray-300 mb-2">receipt_long</span>
                    <p class="font-medium">Belum ada transaksi</p>
                </div>
            <?php else: ?>
                <?php foreach($trx as $t): ?>
                <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-100">
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex items-center gap-3 flex-1 min-w-0">
                            <div class="w-10 h-10 rounded-full <?= $t['transaction_type'] == 'Pemasukan' ? 'bg-green-50 text-green-600' : 'bg-red-50 text-red-500' ?> flex items-center justify-center shrink-0">
                                <span class="material-symbols-outlined text-xl"><?= $t['transaction_type'] == 'Pemasukan' ? 'arrow_downward' : 'arrow_upward' ?></span>
                            </div>
                            <div class="min-w-0">
                                <p class="font-bold text-gray-800 text-sm truncate"><?= htmlspecialchars($t['description']) ?></p>
                                <p class="text-xs text-gray-400"><?= date('d M Y', strtotime($t['transaction_date'])) ?></p>
                            </div>
                        </div>
                        <div class="text-right shrink-0">
                            <p class="font-bold text-sm <?= $t['transaction_type'] == 'Pemasukan' ? 'text-green-600' : 'text-gray-800' ?>">
                                <?= $t['transaction_type'] == 'Pemasukan' ? '+' : '-' ?>Rp <?= number_format($t['amount'], 0, ',', '.') ?>
                            </p>
                        </div>
                    </div>
                    <div class="flex gap-2 mt-3 pt-3 border-t border-gray-50">
                        <button onclick="openEditTrx(<?= $t['id'] ?>, '<?= $t['transaction_date'] ?>', '<?= $t['transaction_type'] ?>', <?= $t['amount'] ?>, '<?= htmlspecialchars($t['description'], ENT_QUOTES) ?>')" class="flex-1 py-2 bg-gray-50 text-gray-600 rounded-lg text-xs font-bold hover:bg-gray-100 transition-colors flex items-center justify-center gap-1">
                            <span class="material-symbols-outlined text-sm">edit</span> Edit
                        </button>
                        <form method="POST" class="flex-shrink-0" onsubmit="return confirm('Hapus transaksi ini?')">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="trx_id" value="<?= $t['id'] ?>">
                            <button type="submit" class="py-2 px-3 bg-red-50 text-error rounded-lg text-xs font-bold hover:bg-red-100 transition-colors flex items-center gap-1">
                                <span class="material-symbols-outlined text-sm">delete</span>
                            </button>
                        </form>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </section>

        <!-- DESKTOP: Transaction Table -->
        <section class="hidden sm:block bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-6 py-5 border-b border-gray-100 flex items-center justify-between">
                <h4 class="font-headline font-bold text-lg">Recent Transactions</h4>
                <span class="text-xs text-gray-400 font-medium"><?= count($trx) ?> items</span>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-4 text-xs font-bold text-gray-400 uppercase">Date</th>
                            <th class="px-6 py-4 text-xs font-bold text-gray-400 uppercase">Type</th>
                            <th class="px-6 py-4 text-xs font-bold text-gray-400 uppercase">Description</th>
                            <th class="px-6 py-4 text-xs font-bold text-gray-400 uppercase text-right">Amount</th>
                            <th class="px-6 py-4 text-xs font-bold text-gray-400 uppercase text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        <?php foreach($trx as $t): ?>
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-4 text-sm font-medium"><?= date('M d, Y', strtotime($t['transaction_date'])) ?></td>
                            <td class="px-6 py-4">
                                <?= $t['transaction_type'] == 'Pemasukan' ? '<span class="text-green-600 font-bold bg-green-100 px-3 py-1 rounded-full text-[10px] uppercase">Income</span>' : '<span class="text-red-600 font-bold bg-red-100 px-3 py-1 rounded-full text-[10px] uppercase">Expense</span>' ?>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600"><?= htmlspecialchars($t['description']) ?></td>
                            <td class="px-6 py-4 text-sm font-bold text-right <?= $t['transaction_type'] == 'Pemasukan' ? 'text-green-600' : 'text-gray-800' ?>">
                                <?= $t['transaction_type'] == 'Pemasukan' ? '+' : '-' ?>Rp <?= number_format($t['amount'], 0, ',', '.') ?>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <div class="flex items-center justify-center gap-1">
                                    <button onclick="openEditTrx(<?= $t['id'] ?>, '<?= $t['transaction_date'] ?>', '<?= $t['transaction_type'] ?>', <?= $t['amount'] ?>, '<?= htmlspecialchars($t['description'], ENT_QUOTES) ?>')" class="p-1.5 text-gray-400 hover:text-primary hover:bg-primary/10 rounded-lg transition-colors">
                                        <span class="material-symbols-outlined text-lg">edit</span>
                                    </button>
                                    <form method="POST" class="inline" onsubmit="return confirm('Hapus transaksi ini?')">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="trx_id" value="<?= $t['id'] ?>">
                                        <button type="submit" class="p-1.5 text-gray-400 hover:text-error hover:bg-red-50 rounded-lg transition-colors">
                                            <span class="material-symbols-outlined text-lg">delete</span>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
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
function openEditTrx(id, date, type, amount, desc) {
    document.getElementById('edit_trx_id').value = id;
    document.getElementById('edit_trx_date').value = date;
    document.getElementById('edit_trx_type').value = type;
    document.getElementById('edit_trx_amount').value = amount;
    document.getElementById('edit_trx_desc').value = desc;
    document.getElementById('editTrxModal').classList.remove('hidden');
}
function closeEditTrx() { document.getElementById('editTrxModal').classList.add('hidden'); }
// --- Notification System ---
var NR='<?= $role ?>';
function toggleNotifs(){var p=document.getElementById('notifPanel');p.classList.toggle('hidden');if(!p.classList.contains('hidden'))loadNotifs();}
function loadNotifs(){fetch('notifications_api.php?action=fetch&role='+NR).then(function(r){return r.json()}).then(function(d){var l=document.getElementById('notifList'),b=document.getElementById('notifBadge');if(d.unread>0){b.classList.remove('hidden');b.textContent=d.unread>9?'9+':d.unread;}else{b.classList.add('hidden');}if(!d.notifications||!d.notifications.length){l.innerHTML='<div class="p-8 text-center text-gray-400 text-sm">Tidak ada notifikasi</div>';return;}l.innerHTML=d.notifications.map(function(n){var ic={info:'info',success:'check_circle',warning:'warning',error:'error'};var cl={info:'text-[#00666e] bg-[#e2f6ff]',success:'text-green-600 bg-green-50',warning:'text-[#705900] bg-[#fdd34d]/20',error:'text-[#b31b25] bg-red-50'};return '<div class="px-5 py-3 border-b border-gray-50 flex gap-3 '+(n.is_read?'opacity-50':'')+' hover:bg-gray-50 transition-colors cursor-default"><div class="w-9 h-9 rounded-full '+(cl[n.type]||cl.info)+' flex items-center justify-center shrink-0"><span class="material-symbols-outlined text-lg">'+(ic[n.type]||'info')+'</span></div><div class="flex-1 min-w-0"><p class="text-sm font-bold text-gray-800 truncate">'+n.title+'</p><p class="text-xs text-gray-500 mt-0.5">'+n.message+'</p><p class="text-[10px] text-gray-400 mt-1">'+n.time_ago+'</p></div></div>';}).join('');}).catch(function(){});}
function markAllRead(){fetch('notifications_api.php?action=mark_read&role='+NR).then(function(){loadNotifs();});}
document.addEventListener('click',function(e){var c=document.getElementById('notifContainer');if(c&&!c.contains(e.target)){var p=document.getElementById('notifPanel');if(p)p.classList.add('hidden');}});
document.addEventListener('DOMContentLoaded',function(){fetch('notifications_api.php?action=count&role='+NR).then(function(r){return r.json();}).then(function(d){var b=document.getElementById('notifBadge');if(d.count>0){b.classList.remove('hidden');b.textContent=d.count>9?'9+':d.count;}}).catch(function(){});});
</script>
</body></html>