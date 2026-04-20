<?php
/**
 * JoyOps Helper Functions
 * Notification system & utilities
 */

function addNotification($pdo, $title, $message, $type = 'info', $for_role = 'All') {
    try {
        $stmt = $pdo->prepare("INSERT INTO notifications (title, message, type, for_role) VALUES (?, ?, ?, ?)");
        $stmt->execute([$title, $message, $type, $for_role]);
    } catch (\PDOException $e) { /* silently fail if table doesn't exist yet */ }
}

function initNotifications($pdo) {
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS notifications (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(100) NOT NULL,
            message TEXT NOT NULL,
            type ENUM('info', 'success', 'warning', 'error') DEFAULT 'info',
            for_role ENUM('Admin', 'Karyawan', 'All') DEFAULT 'All',
            is_read TINYINT(1) DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");
        // Seed welcome notifications on first run
        $count = $pdo->query("SELECT COUNT(*) FROM notifications")->fetchColumn();
        if ($count == 0) {
            addNotification($pdo, 'Selamat Datang!', 'Sistem notifikasi JoyOps telah aktif. Anda akan menerima update aktivitas di sini.', 'info', 'All');
            addNotification($pdo, 'Tip Operasional', 'Pastikan selalu memperbarui status mesin setelah digunakan pelanggan.', 'info', 'Karyawan');
            addNotification($pdo, 'Fitur Baru', 'Export data keuangan ke PDF dan Excel kini tersedia di halaman Finance.', 'success', 'Admin');
        }
    } catch (\PDOException $e) { /* silently fail */ }
}
?>
