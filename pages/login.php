<?php
// If already logged in, go to dashboard
if (isset($_SESSION['user_id'])) {
    header('Location: /dashboard');
    exit;
}

$loginError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$_POST['username']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user && password_verify($_POST['password'], $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['username'] = $user['username'];
            header('Location: /dashboard');
            exit;
        } else {
            $loginError = 'Invalid credentials.';
        }
    } catch(Exception $e) {
        $loginError = 'Login error. Please try again.';
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GhostHades · Login</title>
    <style>
        * { margin:0; padding:0; box-sizing:border-box; font-family:'Inter',system-ui,-apple-system,sans-serif; }
        body { min-height:100vh; background:radial-gradient(ellipse at 50% 0%, #0a0a1a 0%, #000000 100%); display:flex; align-items:center; justify-content:center; padding:1.5rem; overflow:hidden; }
        .bg-glow { position:fixed; top:-50%; left:-50%; width:200%; height:200%; background:radial-gradient(circle at 30% 40%, rgba(167,139,250,0.08) 0%, transparent 60%), radial-gradient(circle at 70% 60%, rgba(236,72,153,0.06) 0%, transparent 50%); z-index:0; pointer-events:none; }
        .glass { position:relative; z-index:1; background:rgba(255,255,255,0.02); backdrop-filter:blur(40px); border:1px solid rgba(255,255,255,0.05); border-radius:3rem; padding:3.5rem 3rem; width:100%; max-width:440px; box-shadow:0 60px 120px rgba(0,0,0,0.8), inset 0 1px 0 rgba(255,255,255,0.05); }
        .logo { font-size:2.5rem; font-weight:700; letter-spacing:-0.04em; background:linear-gradient(135deg,#a78bfa,#ec4899,#f59e0b); -webkit-background-clip:text; -webkit-text-fill-color:transparent; text-align:center; margin-bottom:0.3rem; }
        .logo-sub { color:#6b6b8a; text-align:center; font-size:0.75rem; letter-spacing:0.15em; text-transform:uppercase; margin-bottom:2rem; }
        label { color:#c8c8e0; font-size:0.75rem; font-weight:500; letter-spacing:0.05em; display:block; margin-bottom:0.4rem; text-transform:uppercase; }
        input { width:100%; background:rgba(255,255,255,0.03); border:1px solid rgba(255,255,255,0.06); border-radius:1.4rem; padding:0.9rem 1.4rem; color:#fff; font-size:0.95rem; transition:all 0.3s; margin-bottom:1.2rem; outline:none; }
        input:focus { border-color:#a78bfa; box-shadow:0 0 0 4px rgba(167,139,250,0.12); background:rgba(255,255,255,0.05); }
        input::placeholder { color:#4a4a6a; }
        .btn { width:100%; background:linear-gradient(135deg,#a78bfa,#7c3aed); border:none; border-radius:1.4rem; padding:0.9rem; font-weight:600; font-size:1rem; color:#fff; cursor:pointer; transition:all 0.3s; }
        .btn:hover { transform:scale(1.01); box-shadow:0 8px 40px rgba(167,139,250,0.3); }
        .error { background:rgba(239,68,68,0.12); border:1px solid rgba(239,68,68,0.2); border-radius:1.2rem; padding:0.7rem 1.2rem; color:#fca5a5; font-size:0.85rem; margin-top:1rem; display:<?= empty($loginError) ? 'none' : 'block'; ?>; }
        .footer { margin-top:1.5rem; text-align:center; color:#4a4a6a; font-size:0.7rem; letter-spacing:0.04em; }
        .status-dot { display:inline-block; width:6px; height:6px; background:#22c55e; border-radius:50%; margin-right:6px; animation:pulse 2s infinite; }
        @keyframes pulse { 0%,100%{opacity:1;} 50%{opacity:0.5;} }
    </style>
</head>
<body>
<div class="bg-glow"></div>
<div class="glass">
    <div class="logo">⧫ GhostHades</div>
    <div class="logo-sub"><span class="status-dot"></span> lead extractor · v4</div>
    <form method="POST" action="/" id="loginForm">
        <label>username</label>
        <input type="text" name="username" placeholder="enter username" required>
        <label>password</label>
        <input type="password" name="password" placeholder="••••••••" required>
        <button class="btn" type="submit">→ unlock gateway</button>
        <?php if (!empty($loginError)): ?>
        <div class="error">⚠ <?= htmlspecialchars($loginError) ?></div>
        <?php endif; ?>
    </form>
    <div class="footer">encrypted · 6767</div>
</div>
</body>
</html>
