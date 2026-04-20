<?php
session_start();
require_once 'config.php';

$error = ''; $success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $username = trim($_POST['username']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    try {
        $stmt = $pdo->prepare("INSERT INTO users (name, username, password, role) VALUES (?, ?, ?, 'Admin')");
        $stmt->execute([$name, $username, $password]);
        $success = "Akun Admin Berhasil Dibuat! Silakan Login.";
    } catch (Exception $e) { $error = "Username sudah digunakan!"; }
}
?>
<!DOCTYPE html>
<html class="light" lang="en">
<head>
<meta charset="utf-8"/><meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<title>Register | JoyOps</title>
<script src="https://cdn.tailwindcss.com?plugins=forms"></script>
<link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
<script> tailwind.config = { theme: { extend: { colors: { primary: "#00666e", secondary: "#92dcf8", surface: "#f5f7f8", "on-surface": "#2c2f30" }, fontFamily: { headline: ["Manrope"], body: ["Inter"] } } } } </script>
<style>.material-symbols-outlined { font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24; } .primary-gradient { background: linear-gradient(135deg, #00666e 0%, #6ce9f6 100%); }</style>
</head>
<body class="bg-surface font-body text-on-surface min-h-screen flex flex-col items-center justify-center">
<main class="relative z-10 w-full max-w-md px-6">
    <div class="bg-white/90 shadow-xl rounded-xl p-10 flex flex-col items-center">
        <div class="mb-8 text-center">
            <h1 class="font-headline text-3xl font-extrabold text-primary">Setup Admin</h1>
        </div>
        <?php if($error): ?><div class="w-full bg-red-100 text-red-600 p-3 rounded-lg text-sm font-bold mb-4 text-center"><?= $error ?></div><?php endif; ?>
        <?php if($success): ?><div class="w-full bg-green-100 text-green-700 p-3 rounded-lg text-sm font-bold mb-4 text-center"><?= $success ?></div><?php endif; ?>
        <form method="POST" class="w-full space-y-4">
            <input name="name" required class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-lg focus:ring-primary" placeholder="Full Name"/>
            <input name="username" required class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-lg focus:ring-primary" placeholder="Username"/>
            <input name="password" required type="password" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-lg focus:ring-primary" placeholder="Password"/>
            <button type="submit" class="w-full py-4 rounded-full primary-gradient text-white font-bold">Register Admin</button>
        </form>
        <a href="login.php" class="mt-6 text-sm font-bold text-primary">Ke Halaman Login</a>
    </div>
</main>
</body></html>