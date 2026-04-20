<?php
session_start();
require_once 'config.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') { header("Location: login.php"); exit; }

$format = $_GET['format'] ?? 'csv';

$trx = $pdo->query("SELECT * FROM transactions ORDER BY transaction_date DESC, id DESC")->fetchAll();
$rev = $pdo->query("SELECT SUM(amount) FROM transactions WHERE transaction_type='Pemasukan'")->fetchColumn() ?: 0;
$exp = $pdo->query("SELECT SUM(amount) FROM transactions WHERE transaction_type='Pengeluaran'")->fetchColumn() ?: 0;
$net = $rev - $exp;

// --- CSV EXPORT ---
if ($format === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=joyops_finance_' . date('Y-m-d') . '.csv');
    
    $out = fopen('php://output', 'w');
    fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF)); // BOM for Excel UTF-8
    
    fputcsv($out, ['Tanggal', 'Tipe', 'Deskripsi', 'Jumlah (Rp)']);
    foreach ($trx as $t) {
        fputcsv($out, [
            $t['transaction_date'],
            $t['transaction_type'],
            $t['description'],
            ($t['transaction_type'] == 'Pemasukan' ? '' : '-') . $t['amount']
        ]);
    }
    fputcsv($out, []);
    fputcsv($out, ['', '', 'Total Pemasukan', $rev]);
    fputcsv($out, ['', '', 'Total Pengeluaran', $exp]);
    fputcsv($out, ['', '', 'Net Profit', $net]);
    
    fclose($out);
    exit;
}

// --- PDF / PRINT VIEW ---
if ($format === 'pdf') {
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>JoyOps Finance Report — <?= date('d M Y') ?></title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: 'Inter', sans-serif; padding: 40px; color: #2c2f30; background: #fff; font-size: 13px; }
    .header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 32px; padding-bottom: 20px; border-bottom: 3px solid #00666e; }
    .header h1 { font-size: 26px; color: #00666e; font-weight: 800; }
    .header .sub { color: #888; font-size: 11px; margin-top: 4px; }
    .brand { text-align: right; }
    .brand .name { font-size: 20px; font-weight: 800; color: #00666e; }
    .brand .tagline { font-size: 10px; color: #999; text-transform: uppercase; letter-spacing: 2px; }
    .summary { display: flex; gap: 16px; margin-bottom: 32px; }
    .card { flex: 1; padding: 18px; border: 1px solid #e5e7eb; border-radius: 12px; }
    .card .label { font-size: 10px; text-transform: uppercase; color: #999; font-weight: 700; letter-spacing: 1px; }
    .card .value { font-size: 20px; font-weight: 800; margin-top: 6px; }
    .card.rev .value { color: #00666e; }
    .card.exp .value { color: #333; }
    .card.net .value { color: <?= $net >= 0 ? '#16a34a' : '#b31b25' ?>; }
    table { width: 100%; border-collapse: collapse; margin-top: 8px; }
    th { background: #f5f7f8; text-align: left; padding: 10px 14px; font-size: 10px; text-transform: uppercase; letter-spacing: 1px; color: #888; font-weight: 700; border-bottom: 2px solid #e5e7eb; }
    td { padding: 10px 14px; border-bottom: 1px solid #f3f3f3; }
    tr:nth-child(even) { background: #fafbfc; }
    .badge { display: inline-block; padding: 3px 12px; border-radius: 50px; font-size: 9px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px; }
    .badge-in { background: #dcfce7; color: #16a34a; }
    .badge-out { background: #fee2e2; color: #dc2626; }
    .income { color: #16a34a; font-weight: 700; }
    .expense { color: #333; font-weight: 700; }
    .footer { margin-top: 40px; padding-top: 16px; border-top: 1px solid #e5e7eb; text-align: center; color: #bbb; font-size: 10px; }
    .toolbar { text-align: center; margin-bottom: 24px; padding: 16px; background: #f5f7f8; border-radius: 12px; }
    .toolbar button { padding: 12px 32px; background: #00666e; color: white; border: none; border-radius: 8px; font-weight: 700; cursor: pointer; font-size: 14px; margin: 0 6px; }
    .toolbar button:hover { opacity: 0.9; }
    .toolbar a { margin-left: 16px; color: #00666e; font-weight: 600; text-decoration: none; font-size: 13px; }
    @media print {
        body { padding: 16px; }
        .toolbar { display: none !important; }
        .header { border-bottom-width: 2px; }
    }
    @media (max-width: 640px) {
        body { padding: 16px; }
        .summary { flex-direction: column; }
        .header { flex-direction: column; gap: 8px; }
        .brand { text-align: left; }
    }
</style>
</head>
<body>
    <div class="toolbar">
        <button onclick="window.print()">🖨️ Print / Save as PDF</button>
        <a href="finance.php">← Kembali ke Finance</a>
    </div>
    
    <div class="header">
        <div>
            <h1>Laporan Keuangan</h1>
            <p class="sub">Dibuat pada <?= date('l, d F Y — H:i') ?> WIB</p>
        </div>
        <div class="brand">
            <div class="name">JoyOps</div>
            <div class="tagline">Operations Management</div>
        </div>
    </div>
    
    <div class="summary">
        <div class="card rev">
            <div class="label">Total Pemasukan</div>
            <div class="value">Rp <?= number_format($rev, 0, ',', '.') ?></div>
        </div>
        <div class="card exp">
            <div class="label">Total Pengeluaran</div>
            <div class="value">Rp <?= number_format($exp, 0, ',', '.') ?></div>
        </div>
        <div class="card net">
            <div class="label">Net Profit</div>
            <div class="value"><?= $net >= 0 ? '+' : '-' ?>Rp <?= number_format(abs($net), 0, ',', '.') ?></div>
        </div>
    </div>
    
    <table>
        <thead>
            <tr>
                <th>Tanggal</th>
                <th>Tipe</th>
                <th>Deskripsi</th>
                <th style="text-align:right">Jumlah</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($trx as $t): ?>
            <tr>
                <td><?= date('d M Y', strtotime($t['transaction_date'])) ?></td>
                <td><span class="badge <?= $t['transaction_type'] == 'Pemasukan' ? 'badge-in' : 'badge-out' ?>"><?= $t['transaction_type'] == 'Pemasukan' ? 'Pemasukan' : 'Pengeluaran' ?></span></td>
                <td><?= htmlspecialchars($t['description']) ?></td>
                <td style="text-align:right" class="<?= $t['transaction_type'] == 'Pemasukan' ? 'income' : 'expense' ?>">
                    <?= $t['transaction_type'] == 'Pemasukan' ? '+' : '-' ?>Rp <?= number_format($t['amount'], 0, ',', '.') ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    
    <div class="footer">
        <p>© <?= date('Y') ?> JoyOps Operations — Laporan Keuangan Rahasia</p>
        <p>Total <?= count($trx) ?> transaksi tercatat</p>
    </div>
</body>
</html>
<?php exit; } ?>
