<?php
// Force error display for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once 'includes/config.php';
require_once 'includes/auth.php';

// Check if logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$limits = checkUserLimits($_SESSION['user_id']);
$channel_url = TELEGRAM_CHANNEL_URL;
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GhostHades · Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { margin:0; padding:0; box-sizing:border-box; font-family:'Inter',system-ui,sans-serif; }
        body { background:#0a0a14; color:#e8e8f0; min-height:100vh; display:flex; }
        .sidebar { width:280px; background:rgba(255,255,255,0.02); backdrop-filter:blur(20px); border-right:1px solid rgba(255,255,255,0.04); padding:2rem 1.5rem; display:flex; flex-direction:column; height:100vh; position:sticky; top:0; overflow-y:auto; flex-shrink:0; }
        .sidebar-logo { font-size:1.5rem; font-weight:700; background:linear-gradient(135deg,#a78bfa,#ec4899); -webkit-background-clip:text; -webkit-text-fill-color:transparent; margin-bottom:2rem; letter-spacing:-0.02em; }
        .sidebar-logo small { font-size:0.6rem; opacity:0.4; display:block; -webkit-text-fill-color:#6b6b8a; }
        .sidebar-nav { flex:1; }
        .sidebar-nav a { display:flex; align-items:center; gap:0.8rem; padding:0.8rem 1rem; border-radius:1rem; color:#8b8ba3; text-decoration:none; transition:all 0.25s; font-size:0.9rem; margin-bottom:0.3rem; cursor:pointer; }
        .sidebar-nav a:hover, .sidebar-nav a.active { background:rgba(167,139,250,0.08); color:#fff; }
        .sidebar-nav a .icon { font-size:1.2rem; width:24px; text-align:center; }
        .sidebar-nav .divider { border-top:1px solid rgba(255,255,255,0.04); margin:1rem 0; }
        .sidebar-user { padding-top:1rem; border-top:1px solid rgba(255,255,255,0.04); font-size:0.8rem; color:#6b6b8a; }
        .sidebar-user strong { color:#e8e8f0; display:block; margin-bottom:0.2rem; }
        .sidebar-user .limit-badge { display:inline-block; background:rgba(167,139,250,0.12); padding:0.15rem 0.8rem; border-radius:2rem; font-size:0.7rem; color:#a78bfa; margin-top:0.3rem; }
        .main { flex:1; padding:2rem 2.5rem; overflow-y:auto; max-height:100vh; }
        .top-bar { display:flex; justify-content:space-between; align-items:center; margin-bottom:2rem; flex-wrap:wrap; gap:1rem; }
        .top-bar h1 { font-size:1.8rem; font-weight:600; letter-spacing:-0.02em; }
        .top-bar .badge { background:rgba(167,139,250,0.15); padding:0.4rem 1rem; border-radius:2rem; font-size:0.75rem; color:#a78bfa; }
        .stats-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(180px,1fr)); gap:1rem; margin-bottom:2rem; }
        .stat-card { background:rgba(255,255,255,0.02); border:1px solid rgba(255,255,255,0.04); border-radius:1.5rem; padding:1.2rem 1.5rem; }
        .stat-card .label { font-size:0.7rem; text-transform:uppercase; letter-spacing:0.05em; color:#6b6b8a; }
        .stat-card .value { font-size:2rem; font-weight:700; margin-top:0.2rem; }
        .stat-card .sub { font-size:0.75rem; color:#6b6b8a; margin-top:0.2rem; }
        .stat-card .value.unlimited { color:#22c55e; font-size:1.2rem; }
        .panel { background:rgba(255,255,255,0.02); border:1px solid rgba(255,255,255,0.04); border-radius:2rem; padding:2rem; margin-bottom:2rem; }
        .panel h2 { font-size:1.1rem; font-weight:500; margin-bottom:1.5rem; letter-spacing:-0.01em; }
        .row { display:flex; gap:1rem; flex-wrap:wrap; }
        .row > * { flex:1; min-width:180px; }
        input, select, textarea { background:rgba(255,255,255,0.03); border:1px solid rgba(255,255,255,0.06); border-radius:1.2rem; padding:0.8rem 1.2rem; color:#fff; width:100%; outline:none; transition:all 0.25s; }
        input:focus, select:focus, textarea:focus { border-color:#a78bfa; box-shadow:0 0 0 4px rgba(167,139,250,0.08); background:rgba(255,255,255,0.05); }
        input::placeholder, textarea::placeholder { color:#4a4a6a; }
        select option { background:#0a0a14; color:#fff; }
        textarea { resize:vertical; min-height:80px; font-family:'Monaco',monospace; font-size:0.8rem; }
        .btn-primary { background:linear-gradient(135deg,#a78bfa,#7c3aed); border:none; border-radius:1.2rem; padding:0.8rem 1.8rem; color:#fff; font-weight:600; cursor:pointer; transition:all 0.3s; }
        .btn-primary:hover { transform:scale(1.02); box-shadow:0 8px 30px rgba(167,139,250,0.25); }
        .btn-secondary { background:rgba(255,255,255,0.05); border:1px solid rgba(255,255,255,0.06); border-radius:1.2rem; padding:0.8rem 1.8rem; color:#c8c8e0; font-weight:500; cursor:pointer; transition:all 0.3s; }
        .btn-secondary:hover { background:rgba(255,255,255,0.08); }
        .btn-success { background:linear-gradient(135deg,#22c55e,#16a34a); border:none; border-radius:1.2rem; padding:0.8rem 1.8rem; color:#fff; font-weight:600; cursor:pointer; transition:all 0.3s; }
        .btn-success:hover { transform:scale(1.02); box-shadow:0 8px 30px rgba(34,197,94,0.25); }
        .btn-gold { background:linear-gradient(135deg,#f59e0b,#d97706); border:none; border-radius:1.2rem; padding:0.8rem 1.8rem; color:#fff; font-weight:600; cursor:pointer; transition:all 0.3s; }
        .btn-gold:hover { transform:scale(1.02); box-shadow:0 8px 30px rgba(245,158,11,0.25); }
        .btn-telegram { background:linear-gradient(135deg,#0088cc,#004f7a); border:none; border-radius:1.2rem; padding:0.8rem 1.8rem; color:#fff; font-weight:600; cursor:pointer; transition:all 0.3s; text-decoration:none; display:inline-block; }
        .btn-telegram:hover { transform:scale(1.02); box-shadow:0 8px 30px rgba(0,136,204,0.25); }
        .btn-danger { background:linear-gradient(135deg,#ef4444,#dc2626); border:none; border-radius:1.2rem; padding:0.8rem 1.8rem; color:#fff; font-weight:600; cursor:pointer; transition:all 0.3s; }
        .file-upload { position:relative; display:inline-block; }
        .file-upload input[type="file"] { position:absolute; left:0; top:0; opacity:0; width:100%; height:100%; cursor:pointer; }
        .license-box { background:rgba(245,158,11,0.04); border:1px solid rgba(245,158,11,0.1); border-radius:1.5rem; padding:1.5rem; margin-top:1rem; }
        #results-container { background:rgba(0,0,0,0.3); border-radius:1.2rem; padding:1.2rem; max-height:400px; overflow-y:auto; margin-top:1rem; font-family:'Monaco','Menlo',monospace; font-size:0.8rem; white-space:pre-wrap; word-break:break-all; }
        .saved-panel { background:rgba(255,255,255,0.02); border:1px solid rgba(255,255,255,0.04); border-radius:2rem; padding:1.5rem; }
        .saved-item { display:flex; justify-content:space-between; align-items:center; padding:0.7rem 1rem; border-bottom:1px solid rgba(255,255,255,0.03); }
        .saved-item:last-child { border-bottom:none; }
        .saved-item .info { font-size:0.85rem; }
        .saved-item .info .name { color:#e8e8f0; }
        .saved-item .info .meta { color:#6b6b8a; font-size:0.7rem; }
        .saved-item .actions { display:flex; gap:0.5rem; }
        .saved-item .actions button { background:rgba(255,255,255,0.05); border:none; border-radius:0.8rem; padding:0.3rem 0.8rem; color:#c8c8e0; cursor:pointer; font-size:0.7rem; transition:all 0.2s; }
        .saved-item .actions button:hover { background:rgba(167,139,250,0.15); color:#a78bfa; }
        .telegram-box { background:rgba(34,197,94,0.04); border:1px solid rgba(34,197,94,0.1); border-radius:1.5rem; padding:1.2rem 1.5rem; margin-top:1rem; display:flex; align-items:center; gap:1rem; flex-wrap:wrap; }
        .telegram-box .status { display:flex; align-items:center; gap:0.5rem; font-size:0.85rem; }
        .telegram-box .status .dot { width:8px; height:8px; border-radius:50%; display:inline-block; }
        .telegram-box .status .dot.on { background:#22c55e; }
        .telegram-box .status .dot.off { background:#6b6b8a; }
        .domain-tags { display:flex; flex-wrap:wrap; gap:0.4rem; margin-top:0.8rem; }
        .domain-tag { background:rgba(167,139,250,0.08); border:1px solid rgba(167,139,250,0.1); border-radius:1rem; padding:0.2rem 0.8rem; font-size:0.7rem; color:#a78bfa; cursor:pointer; transition:all 0.2s; }
        .domain-tag:hover { background:rgba(167,139,250,0.15); }
        .domain-tag .count { color:#6b6b8a; margin-left:0.3rem; }
        .process-log { background:rgba(0,0,0,0.4); border-radius:1.2rem; padding:1rem; max-height:250px; overflow-y:auto; font-family:'Monaco','Menlo',monospace; font-size:0.7rem; color:#8b8ba3; margin-top:1rem; }
        .process-log .log-entry { padding:0.2rem 0; border-bottom:1px solid rgba(255,255,255,0.02); }
        .process-log .log-entry .time { color:#4a4a6a; margin-right:0.8rem; }
        .process-log .log-entry .keyword-highlight { color:#f59e0b; }
        .process-log .log-entry .success { color:#22c55e; }
        .process-log .log-entry .error { color:#ef4444; }
        .process-log .log-entry .info { color:#a78bfa; }
        .batch-status { display:inline-block; padding:0.2rem 0.8rem; border-radius:2rem; font-size:0.65rem; font-weight:600; }
        .batch-status.queued { background:rgba(107,107,138,0.2); color:#8b8ba3; }
        .batch-status.processing { background:rgba(167,139,250,0.2); color:#a78bfa; animation:pulse 1.5s infinite; }
        .batch-status.completed { background:rgba(34,197,94,0.2); color:#22c55e; }
        .batch-status.failed { background:rgba(239,68,68,0.2); color:#ef4444; }
        .hidden { display:none; }
        .toast { position:fixed; bottom:2rem; right:2rem; background:rgba(0,0,0,0.9); backdrop-filter:blur(20px); border:1px solid rgba(255,255,255,0.06); border-radius:1.2rem; padding:1rem 1.5rem; color:#e8e8f0; font-size:0.9rem; z-index:9999; animation:slideUp 0.3s ease; max-width:400px; }
        .toast.success { border-color:rgba(34,197,94,0.3); }
        .toast.error { border-color:rgba(239,68,68,0.3); }
        .telegram-channel-btn { display:flex; align-items:center; gap:0.5rem; }
        .profile-row { display:flex; gap:1rem; align-items:center; flex-wrap:wrap; background:rgba(255,255,255,0.02); border-radius:1.5rem; padding:1rem 1.5rem; margin-bottom:1.5rem; }
        @keyframes slideUp { from { opacity:0; transform:translateY(20px); } to { opacity:1; transform:translateY(0); } }
        @keyframes pulse { 0%,100%{opacity:1;} 50%{opacity:0.5;} }
        @media (max-width:768px) { .sidebar { display:none; } .main { padding:1rem; } }
        ::-webkit-scrollbar { width:4px; } ::-webkit-scrollbar-track { background:transparent; } ::-webkit-scrollbar-thumb { background:#3b3b5a; border-radius:10px; }
    </style>
</head>
<body>
<nav class="sidebar">
    <div class="sidebar-logo">⧫ GhostHades <small>lead extractor</small></div>
    <div class="sidebar-nav">
        <a href="#" class="active" onclick="showTab('extract')"><span class="icon">⚡</span> Extract</a>
        <a href="#" onclick="showTab('batch')"><span class="icon">📋</span> Batch</a>
        <a href="#" onclick="showTab('saved')"><span class="icon">📁</span> Saved</a>
        <a href="#" onclick="showTab('telegram')"><span class="icon">✈️</span> Telegram</a>
        <a href="#" onclick="showTab('license')"><span class="icon">🎫</span> License</a>
        <a href="#" onclick="showTab('profile')"><span class="icon">👤</span> Profile</a>
        <?php if ($_SESSION['role'] === 'admin'): ?>
        <a href="admin.php"><span class="icon">🔧</span> Admin</a>
        <?php endif; ?>
        <div class="divider"></div>
        <a href="logout.php"><span class="icon">🚪</span> Logout</a>
    </div>
    <div class="sidebar-user">
        <strong><?= htmlspecialchars($_SESSION['username']) ?></strong>
        <span>Remaining: <span id="sidebarRemaining"><?= number_format($limits['total_remaining']) ?></span></span>
        <span class="limit-badge">Daily: <?= $limits['daily_used'] ?> / <?= $limits['daily_limit'] >= 99999 ? '∞' : number_format($limits['daily_limit']) ?></span>
    </div>
</nav>

<div class="main">
    <div class="top-bar">
        <h1>⚡ extractor</h1>
        <div style="display:flex;gap:0.8rem;align-items:center;flex-wrap:wrap;">
            <?php if (!empty($channel_url)): ?>
            <a href="<?= htmlspecialchars($channel_url) ?>" target="_blank" class="btn-telegram telegram-channel-btn">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21.5 4.5L2.5 12.5L8.5 15.5L12.5 21.5L17.5 14.5L21.5 4.5Z"/><path d="M8.5 15.5L12.5 21.5L17.5 14.5"/></svg>
                Channel
            </a>
            <?php endif; ?>
            <span class="badge">🎫 <?= number_format($limits['total_remaining']) ?> remaining</span>
        </div>
    </div>

    <div class="profile-row">
        <span style="color:#8b8ba3;">👤 <strong style="color:#e8e8f0;"><?= htmlspecialchars($_SESSION['username']) ?></strong></span>
        <span style="color:#6b6b8a;font-size:0.8rem;">Role: <?= ucfirst($_SESSION['role']) ?></span>
        <button class="btn-secondary" onclick="showTab('profile')" style="padding:0.3rem 1rem;font-size:0.8rem;">Change Password</button>
    </div>

    <div class="stats-grid">
        <div class="stat-card"><div class="label">Total Extracted</div><div class="value" id="totalEmails">0</div></div>
        <div class="stat-card"><div class="label">Today</div><div class="value" id="todayExtracts">0</div></div>
        <div class="stat-card"><div class="label">Saved Results</div><div class="value" id="savedCount">0</div></div>
        <div class="stat-card">
            <div class="label">Remaining Limit</div>
            <div class="value <?= $limits['total_remaining'] > 99998 ? 'unlimited' : '' ?>">
                <?= $limits['total_remaining'] > 99998 ? '♾️ Unlimited' : number_format($limits['total_remaining']) ?>
            </div>
            <div class="sub">+<?= number_format($limits['remaining_limit']) ?> license</div>
        </div>
    </div>

    <!-- Tab: Extract -->
    <div id="tab-extract">
        <div class="panel">
            <h2>🔍 single extraction</h2>
            <div class="row">
                <input type="text" id="keyword" placeholder="keyword (e.g. @gmail.com site:linkedin.com)">
                <input type="number" id="targetCount" placeholder="emails to extract" value="100" min="1" max="<?= $limits['total_remaining'] > 99998 ? 9999 : $limits['total_remaining'] ?>">
                <button class="btn-primary" id="extractBtn">→ extract</button>
            </div>
            <div style="display:flex;gap:0.8rem;margin-top:1rem;flex-wrap:wrap;">
                <button class="btn-secondary" id="exportTxt">⬇ download .txt</button>
                <button class="btn-success" id="sendTelegramBtn">✈️ send to telegram</button>
                <button class="btn-secondary" id="saveResultBtn">💾 save</button>
                <span id="extractStatus" style="color:#6b6b8a;font-size:0.85rem;align-self:center;"></span>
            </div>
            <div id="results-container">ready, gng. drop a keyword.</div>
            <div class="domain-tags" id="domainTags"></div>
        </div>
    </div>

    <!-- Tab: Batch -->
    <div id="tab-batch" class="hidden">
        <div class="panel">
            <h2>📋 batch extraction</h2>
            <p style="color:#6b6b8a;font-size:0.85rem;margin-bottom:1rem;">Enter keywords (one per line) or upload a .txt file.</p>
            <div class="row">
                <textarea id="batchKeywords" placeholder="keyword1&#10;keyword2&#10;keyword3" style="flex:2;min-height:120px;"></textarea>
                <div style="display:flex;flex-direction:column;gap:0.5rem;min-width:180px;">
                    <input type="number" id="batchTarget" placeholder="per keyword" value="50" min="1">
                    <div class="file-upload">
                        <button class="btn-secondary" style="width:100%;">📤 upload .txt</button>
                        <input type="file" id="fileUpload" accept=".txt">
                    </div>
                    <button class="btn-primary" id="batchExtractBtn">🚀 start batch</button>
                </div>
            </div>
            <div style="margin-top:1rem;">
                <span id="batchStatus" style="color:#6b6b8a;font-size:0.85rem;"></span>
                <div class="process-log" id="processLog"></div>
            </div>
        </div>
        <div class="panel">
            <h2>📋 recent batches</h2>
            <div id="batchHistory"></div>
        </div>
    </div>

    <!-- Tab: Saved -->
    <div id="tab-saved" class="hidden">
        <div class="saved-panel">
            <h2 style="font-size:1.1rem;font-weight:500;margin-bottom:1.5rem;">📁 saved results</h2>
            <div id="savedList"></div>
        </div>
    </div>

    <!-- Tab: Telegram -->
    <div id="tab-telegram" class="hidden">
        <div class="panel">
            <h2>✈️ telegram integration</h2>
            <p style="color:#6b6b8a;font-size:0.85rem;margin-bottom:1rem;">Connect your bot to receive extraction results automatically.</p>
            <div class="row">
                <input type="text" id="telegramBotToken" placeholder="bot token">
                <input type="text" id="telegramChatId" placeholder="chat ID">
                <button class="btn-success" id="connectTelegramBtn">🔗 connect</button>
            </div>
            <div class="telegram-box" id="telegramStatusBox">
                <div class="status"><span class="dot off" id="telegramDot"></span> <span id="telegramStatusText">not connected</span></div>
            </div>
            <div style="margin-top:1rem;">
                <button class="btn-secondary" id="testTelegramBtn">📨 test</button>
            </div>
        </div>
    </div>

    <!-- Tab: License -->
    <div id="tab-license" class="hidden">
        <div class="panel">
            <h2>🎫 redeem license</h2>
            <p style="color:#6b6b8a;font-size:0.85rem;margin-bottom:1rem;">Enter a license code to add extraction limit.</p>
            <div class="row">
                <input type="text" id="licenseCode" placeholder="GH-A1B2C3D4E5F6" style="flex:2;">
                <button class="btn-gold" id="redeemLicenseBtn">🎫 redeem</button>
            </div>
            <div id="licenseResult" style="margin-top:0.8rem;color:#6b6b8a;font-size:0.85rem;"></div>
            <div class="license-box">
                <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:0.5rem;">
                    <span style="font-size:0.85rem;color:#e8e8f0;">💳 license balance</span>
                    <span style="font-size:1.2rem;font-weight:600;color:#f59e0b;">+<?= number_format($limits['remaining_limit']) ?></span>
                </div>
            </div>
        </div>
    </div>

    <!-- Tab: Profile -->
    <div id="tab-profile" class="hidden">
        <div class="panel">
            <h2>👤 change password</h2>
            <p style="color:#6b6b8a;font-size:0.85rem;margin-bottom:1rem;">Update your account password.</p>
            <div class="row">
                <input type="password" id="currentPassword" placeholder="current password">
                <input type="password" id="newPassword" placeholder="new password (min 6 chars)">
                <input type="password" id="confirmPassword" placeholder="confirm new password">
                <button class="btn-primary" id="changePasswordBtn">🔒 update password</button>
            </div>
            <div id="passwordResult" style="margin-top:0.8rem;color:#6b6b8a;font-size:0.85rem;"></div>
        </div>
    </div>
</div>

<script>
const API_URL = '/api/extract.php';
const BATCH_API = '/api/batch.php';
const SAVED_API = '/api/saved.php';
const TELEGRAM_API = '/api/telegram.php';
const LICENSE_API = '/api/licenses.php';
const USER_API = '/api/users.php';
let currentEmails = [];
let currentDomainStats = {};
let currentExtractionId = null;
let batchPollInterval = null;

function showToast(message, type = 'success') {
    const existing = document.querySelector('.toast');
    if (existing) existing.remove();
    const toast = document.createElement('div');
    toast.className = 'toast ' + type;
    toast.innerText = message;
    document.body.appendChild(toast);
    setTimeout(() => toast.remove(), 5000);
}

function showTab(tab) {
    document.querySelectorAll('.sidebar-nav a').forEach(a => a.classList.remove('active'));
    document.querySelectorAll('[id^="tab-"]').forEach(el => el.classList.add('hidden'));
    document.getElementById('tab-' + tab).classList.remove('hidden');
    document.querySelector(`.sidebar-nav a[onclick*="${tab}"]`)?.classList.add('active');
    if (tab === 'batch') loadBatchHistory();
}

// --- Change Password ---
document.getElementById('changePasswordBtn').addEventListener('click', async () => {
    const current = document.getElementById('currentPassword').value;
    const newPass = document.getElementById('newPassword').value;
    const confirm = document.getElementById('confirmPassword').value;
    const result = document.getElementById('passwordResult');
    
    if (!current || !newPass || !confirm) {
        result.innerHTML = '<span style="color:#ef4444;">❌ Fill all fields.</span>';
        return;
    }
    if (newPass.length < 6) {
        result.innerHTML = '<span style="color:#ef4444;">❌ Password must be at least 6 characters.</span>';
        return;
    }
    if (newPass !== confirm) {
        result.innerHTML = '<span style="color:#ef4444;">❌ Passwords do not match.</span>';
        return;
    }
    
    const res = await fetch(USER_API, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'change_password', current, new_password: newPass })
    });
    const data = await res.json();
    if (data.status === 'ok') {
        result.innerHTML = '<span style="color:#22c55e;">✅ Password updated successfully!</span>';
        document.getElementById('currentPassword').value = '';
        document.getElementById('newPassword').value = '';
        document.getElementById('confirmPassword').value = '';
        showToast('Password updated!');
    } else {
        result.innerHTML = '<span style="color:#ef4444;">❌ ' + (data.error || 'Failed to update password.') + '</span>';
    }
});

// --- Saved ---
async function loadSaved() {
    const res = await fetch(SAVED_API + '?action=list');
    const data = await res.json();
    const list = document.getElementById('savedList');
    if (data.length === 0) {
        list.innerHTML = '<div style="color:#6b6b8a;padding:1rem;text-align:center;">no saved results yet.</div>';
        return;
    }
    list.innerHTML = data.map(item => `
        <div class="saved-item">
            <div class="info">
                <div class="name">${item.filename}</div>
                <div class="meta">${item.email_count} emails · ${item.created_at}</div>
            </div>
            <div class="actions">
                <button onclick="downloadSaved(${item.id})">⬇</button>
                <button onclick="sendSavedTelegram(${item.id})">✈️</button>
                <button onclick="deleteSaved(${item.id})" style="color:#ef4444;">✕</button>
            </div>
        </div>
    `).join('');
    document.getElementById('savedCount').innerText = data.length;
}

async function downloadSaved(id) {
    const res = await fetch(SAVED_API + '?action=download&id=' + id);
    const blob = await res.blob();
    const a = document.createElement('a');
    a.href = URL.createObjectURL(blob);
    a.download = 'emails.txt';
    a.click();
}

async function sendSavedTelegram(id) {
    const res = await fetch(SAVED_API + '?action=send_telegram&id=' + id);
    const data = await res.json();
    showToast(data.message || 'sent', data.status === 'ok' ? 'success' : 'error');
}

async function deleteSaved(id) {
    if (!confirm('delete?')) return;
    await fetch(SAVED_API + '?action=delete&id=' + id, { method: 'POST' });
    loadSaved();
}

// --- Extract ---
document.getElementById('extractBtn').addEventListener('click', async () => {
    const keyword = document.getElementById('keyword').value;
    const targetCount = parseInt(document.getElementById('targetCount').value) || 100;
    const status = document.getElementById('extractStatus');
    
    if (!keyword) { showToast('need a keyword.', 'error'); return; }
    
    const remaining = parseInt(document.getElementById('sidebarRemaining').innerText.replace(/,/g, ''));
    if (remaining < targetCount && remaining < 99999) {
        showToast('insufficient limit. redeem a license.', 'error');
        return;
    }
    
    status.innerText = '⏳ scraping...';
    document.getElementById('results-container').innerText = '🔍 searching...';
    
    try {
        const res = await fetch(API_URL, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ keyword, target_count: targetCount })
        });
        const data = await res.json();
        
        if (data.emails) {
            currentEmails = data.emails;
            currentDomainStats = data.domain_stats || {};
            currentExtractionId = data.extraction_id;
            
            document.getElementById('results-container').innerText = data.emails.join('\n');
            document.getElementById('totalEmails').innerText = data.total;
            status.innerText = `✅ ${data.total} emails`;
            
            if (data.remaining !== undefined) {
                document.getElementById('sidebarRemaining').innerText = data.remaining.toLocaleString();
            }
            
            const tagsContainer = document.getElementById('domainTags');
            tagsContainer.innerHTML = '';
            if (data.domain_stats) {
                Object.entries(data.domain_stats).forEach(([domain, count]) => {
                    const tag = document.createElement('span');
                    tag.className = 'domain-tag';
                    tag.innerText = `${domain} (${count})`;
                    tag.onclick = () => filterByDomain(domain);
                    tagsContainer.appendChild(tag);
                });
            }
            
            await fetch(SAVED_API, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'auto_save', extraction_id: data.extraction_id })
            });
            loadSaved();
            loadStats();
        } else {
            document.getElementById('results-container').innerText = '❌ no emails found';
            status.innerText = '❌ failed';
        }
    } catch(e) {
        document.getElementById('results-container').innerText = 'error: ' + e.message;
        status.innerText = '❌ error';
    }
});

function filterByDomain(domain) {
    const filtered = currentEmails.filter(e => e.includes('@' + domain));
    document.getElementById('results-container').innerText = filtered.join('\n') || 'no emails from this domain';
}

document.getElementById('exportTxt').addEventListener('click', () => {
    if (currentEmails.length === 0) return showToast('extract first', 'error');
    const blob = new Blob([currentEmails.join('\n')], { type: 'text/plain' });
    const a = document.createElement('a');
    a.href = URL.createObjectURL(blob);
    a.download = 'emails_' + Date.now() + '.txt';
    a.click();
});

document.getElementById('saveResultBtn').addEventListener('click', async () => {
    if (currentEmails.length === 0) return showToast('extract first', 'error');
    const res = await fetch(SAVED_API, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'save', extraction_id: currentExtractionId })
    });
    const data = await res.json();
    if (data.status === 'ok') { showToast('saved'); loadSaved(); }
});

document.getElementById('sendTelegramBtn').addEventListener('click', async () => {
    if (currentEmails.length === 0) return showToast('extract first', 'error');
    const res = await fetch(TELEGRAM_API, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'send_results', emails: currentEmails })
    });
    const data = await res.json();
    showToast(data.message || 'sent', data.status === 'ok' ? 'success' : 'error');
});

// --- Batch ---
document.getElementById('fileUpload').addEventListener('change', function(e) {
    const file = this.files[0];
    if (!file) return;
    const reader = new FileReader();
    reader.onload = function(ev) {
        document.getElementById('batchKeywords').value = ev.target.result;
        showToast('loaded ' + file.name);
    };
    reader.readAsText(file);
    this.value = '';
});

document.getElementById('batchExtractBtn').addEventListener('click', async () => {
    const keywordsText = document.getElementById('batchKeywords').value;
    const targetPerKeyword = parseInt(document.getElementById('batchTarget').value) || 50;
    const status = document.getElementById('batchStatus');
    const log = document.getElementById('processLog');
    
    const keywords = keywordsText.split('\n').map(k => k.trim()).filter(k => k.length > 0);
    if (keywords.length === 0) { showToast('enter at least one keyword', 'error'); return; }
    
    const totalNeeded = keywords.length * targetPerKeyword;
    const remaining = parseInt(document.getElementById('sidebarRemaining').innerText.replace(/,/g, ''));
    if (remaining < totalNeeded && remaining < 99999) {
        showToast(`insufficient limit. need ${totalNeeded}, have ${remaining}`, 'error');
        return;
    }
    
    status.innerText = '⏳ starting batch...';
    log.innerHTML = '<div class="log-entry" style="color:#a78bfa;">→ Starting batch with ' + keywords.length + ' keywords...</div>';
    
    try {
        const res = await fetch(BATCH_API, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'start', keywords: keywords, target_per_keyword: targetPerKeyword })
        });
        const data = await res.json();
        
        if (data.batch_id) {
            status.innerText = '🔄 processing...';
            showToast('batch started: ' + data.batch_id);
            loadBatchHistory();
            if (batchPollInterval) clearInterval(batchPollInterval);
            batchPollInterval = setInterval(() => pollBatch(data.batch_id), 3000);
        } else {
            status.innerText = '❌ failed: ' + (data.error || 'unknown');
        }
    } catch(e) {
        status.innerText = '❌ error: ' + e.message;
    }
});

async function pollBatch(batchId) {
    const res = await fetch(BATCH_API + '?action=status&batch_id=' + batchId);
    const data = await res.json();
    const log = document.getElementById('processLog');
    const status = document.getElementById('batchStatus');
    
    if (data.status === 'processing') {
        status.innerText = `🔄 ${data.processed_keywords}/${data.total_keywords} keywords processed · ${data.total_emails} emails`;
    } else if (data.status === 'completed') {
        status.innerText = `✅ completed · ${data.total_emails} emails from ${data.total_keywords} keywords`;
        if (batchPollInterval) clearInterval(batchPollInterval);
        loadBatchHistory();
        loadStats();
        showToast('batch completed! ' + data.total_emails + ' emails');
    } else if (data.status === 'failed') {
        status.innerText = '❌ failed';
        if (batchPollInterval) clearInterval(batchPollInterval);
    }
    
    if (data.logs) {
        log.innerHTML = data.logs.map(entry => `
            <div class="log-entry">
                <span class="time">${entry.time || ''}</span>
                <span class="${entry.type}">${entry.message}</span>
            </div>
        `).join('');
        log.scrollTop = log.scrollHeight;
    }
}

async function loadBatchHistory() {
    const res = await fetch(BATCH_API + '?action=history');
    const data = await res.json();
    const container = document.getElementById('batchHistory');
    if (data.length === 0) {
        container.innerHTML = '<div style="color:#6b6b8a;padding:0.5rem 0;">no batches yet.</div>';
        return;
    }
    container.innerHTML = data.map(b => `
        <div style="display:flex;justify-content:space-between;align-items:center;padding:0.5rem 0;border-bottom:1px solid rgba(255,255,255,0.03);font-size:0.85rem;">
            <span>#${b.id} · ${b.total_keywords} keywords · ${b.total_emails} emails</span>
            <span class="batch-status ${b.status}">${b.status}</span>
            <span style="color:#6b6b8a;font-size:0.65rem;">${b.created_at}</span>
        </div>
    `).join('');
}

// --- License ---
document.getElementById('redeemLicenseBtn').addEventListener('click', async () => {
    const code = document.getElementById('licenseCode').value.trim();
    if (!code) { showToast('enter a license code', 'error'); return; }
    
    const res = await fetch(LICENSE_API, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'redeem', code })
    });
    const data = await res.json();
    
    if (data.success) {
        showToast(data.message, 'success');
        document.getElementById('licenseCode').value = '';
        loadStats();
    } else {
        showToast(data.message, 'error');
    }
});

// --- Telegram ---
document.getElementById('connectTelegramBtn').addEventListener('click', async () => {
    const token = document.getElementById('telegramBotToken').value;
    const chatId = document.getElementById('telegramChatId').value;
    if (!token || !chatId) return showToast('fill both fields', 'error');
    
    const res = await fetch(TELEGRAM_API, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'connect', bot_token: token, chat_id: chatId })
    });
    const data = await res.json();
    if (data.status === 'ok') {
        document.getElementById('telegramDot').className = 'dot on';
        document.getElementById('telegramStatusText').innerText = '✅ connected!';
        showToast('✅ bot connected!');
    } else {
        showToast('❌ failed: ' + (data.error || 'unknown'), 'error');
    }
});

document.getElementById('testTelegramBtn').addEventListener('click', async () => {
    const res = await fetch(TELEGRAM_API, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'test' })
    });
    const data = await res.json();
    showToast(data.message || 'test sent', data.status === 'ok' ? 'success' : 'error');
});

async function loadTelegramStatus() {
    const res = await fetch(TELEGRAM_API + '?action=status');
    const data = await res.json();
    if (data.connected) {
        document.getElementById('telegramDot').className = 'dot on';
        document.getElementById('telegramStatusText').innerText = '✅ connected';
    }
}

async function loadStats() {
    const res = await fetch('/api/stats.php');
    const stats = await res.json();
    if (stats) {
        document.getElementById('totalEmails').innerText = stats.total || 0;
        document.getElementById('todayExtracts').innerText = stats.today || 0;
        document.getElementById('savedCount').innerText = stats.saved || 0;
        if (stats.remaining !== undefined) {
            document.getElementById('sidebarRemaining').innerText = stats.remaining.toLocaleString();
        }
    }
}

loadSaved();
loadStats();
loadTelegramStatus();
loadBatchHistory();
setInterval(loadSaved, 30000);
setInterval(loadStats, 30000);
setInterval(loadBatchHistory, 60000);
</script>
</body>
</html>
