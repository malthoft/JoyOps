<?php
session_start();
require_once 'config.php';

if (isset($_SESSION['user_id'])) {
    header("Location: " . ($_SESSION['role'] === 'Admin' ? 'admin_dashboard.php' : 'employee_dashboard.php'));
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([trim($_POST['username'])]);
    $user = $stmt->fetch();

    if ($user && password_verify($_POST['password'], $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['name'] = $user['name'];
        $_SESSION['role'] = $user['role'];
        header("Location: " . ($user['role'] === 'Admin' ? 'admin_dashboard.php' : 'employee_dashboard.php'));
        exit;
    } else {
        $error = 'Username atau Password salah!';
    }
}
?>
<!DOCTYPE html>
<html class="light" lang="en">
<head>
<meta charset="utf-8"/><meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<title>Login | JoyOps</title>
<script src="https://cdn.tailwindcss.com?plugins=forms"></script>
<link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
<script>
    tailwind.config = { theme: { extend: { colors: { primary: "#00666e", secondary: "#92dcf8", surface: "#f5f7f8", "on-surface": "#2c2f30" }, fontFamily: { headline: ["Manrope"], body: ["Inter"] } } } }
</script>
<style>
    .material-symbols-outlined { font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24; }
    .glass-effect { background: rgba(255, 255, 255, 0.7); backdrop-filter: blur(20px); }
    .primary-gradient { background: linear-gradient(135deg, #00666e 0%, #6ce9f6 100%); }
</style>
</head>
<body class="bg-surface font-body text-on-surface min-h-screen flex flex-col items-center justify-center overflow-hidden">
<div class="fixed inset-0 z-0 overflow-hidden pointer-events-none">
    <div class="absolute -top-[10%] -left-[10%] w-[40%] h-[40%] rounded-full bg-[#6ce9f6]/20 blur-[120px]"></div>
    <div class="absolute -bottom-[10%] -right-[10%] w-[50%] h-[50%] rounded-full bg-secondary/30 blur-[150px]"></div>
</div>
<main class="relative z-10 w-full max-w-md px-6">
    <div class="bg-white glass-effect shadow-[0px_8px_24px_rgba(0,102,110,0.06)] rounded-xl p-10 flex flex-col items-center border border-white">
        <div class="mb-10 text-center">
            <div class="flex items-center justify-center mb-6">
                <div class="w-16 h-16 bg-secondary rounded-xl flex items-center justify-center shadow-sm">
                    <span class="material-symbols-outlined text-primary text-4xl">local_laundry_service</span>
                </div>
            </div>
            <h1 class="font-headline text-3xl font-extrabold tracking-tight">JoyOps</h1>
            <p class="text-gray-500 font-medium mt-1">Joyspin Operations</p>
        </div>

        <?php if($error): ?><div class="w-full bg-red-100 text-red-600 p-3 rounded-lg text-sm font-bold mb-4 text-center"><?= $error ?></div><?php endif; ?>

        <form method="POST" class="w-full space-y-6">
            <div class="space-y-2">
                <label class="font-headline text-sm font-bold ml-1">Username</label>
                <div class="relative">
                    <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-gray-400">person</span>
                    <input name="username" required class="w-full pl-12 pr-4 py-3 bg-gray-50 border-none ring-1 ring-gray-200 rounded-lg focus:ring-primary focus:bg-white transition-all" type="text" placeholder="Enter username"/>
                </div>
            </div>
            <div class="space-y-2">
                <label class="font-headline text-sm font-bold ml-1">Password</label>
                <div class="relative">
                    <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-gray-400">lock</span>
                    <input name="password" required class="w-full pl-12 pr-4 py-3 bg-gray-50 border-none ring-1 ring-gray-200 rounded-lg focus:ring-primary focus:bg-white transition-all" type="password" placeholder="••••••••"/>
                </div>
            </div>
            <button type="submit" class="w-full py-4 rounded-full primary-gradient text-white font-headline font-bold text-lg hover:opacity-90 active:scale-[0.98] transition-all flex justify-center gap-2">
                Login <span class="material-symbols-outlined">arrow_forward</span>
            </button>
        </form>
    </div>
</main>
</body></html>