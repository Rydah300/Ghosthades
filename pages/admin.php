<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Admin · GhostHades</title>
    <style>
        * { margin:0; padding:0; box-sizing:border-box; font-family:'Inter',system-ui,sans-serif; }
        body { background:#0a0a14; color:#e8e8f0; padding:2rem; min-height:100vh; }
        .admin { max-width:1400px; margin:0 auto; }
        .header { display:flex; justify-content:space-between; align-items:center; margin-bottom:2rem; flex-wrap:wrap; gap:1rem; }
        .header h1 { font-size:1.8rem; font-weight:600; }
        .back { color:#a78bfa; text-decoration:none; font-size:0.85rem; }
        .card { background:rgba(255,255,255,0.02); border:1px solid rgba(255,255,255,0.04); border-radius:2rem; padding:2rem; margin-bottom:2rem; }
        .card h2 { font-size:1rem; font-weight:500; margin-bottom:1.2rem; color:#a78bfa; }
        table { width:100%; border-collapse:collapse; }
        th, td { padding:0.8rem 1rem; text-align:left; border-bottom:1px solid rgba(255,255,255,0.04); }
        th { color:#6b6b8a; font-weight:500; font-size:0.7rem; text-transform:uppercase; letter-spacing:0.05em; }
        input, select { background:rgba(255,255,255,0.03); border:1px solid rgba(255,255,255,0.06); border-radius:1rem; padding:0.6rem 1rem; color:#fff; outline:none; }
        input:focus { border-color:#a78bfa; }
        .btn { background:rgba(255,255,255,0.05); border:none; border-radius:1rem; padding:0.5rem 1.2rem; color:#fff; cursor:pointer; transition:all 0.2s; }
        .btn:hover { background:rgba(167,139,250,0.15); }
        .btn-primary { background:linear-gradient(135deg,#a78bfa,#7c3aed); }
        .btn-primary:hover { box-shadow:0 4px 20px rgba(167,139,250,0.25); }
        .btn-gold { background:linear-gradient(135deg,#f59e0b,#d97706); }
        .btn-gold:hover { box-shadow:0 4px 20px rgba(245,158,11,0.25); }
        .btn-danger { background:linear-gradient(135deg,#ef4444,#dc2626); }
        .btn-danger:hover { box-shadow:0 4px 20px rgba(239,68,68,0.25); }
        .btn-success { background:linear-gradient(135deg,#22c55e,#16a34a); }
        .btn-success:hover { box-shadow:0 4px 20px rgba(34,197,94,0.25); }
        .btn-purple { background:linear-gradient(135deg,#8b5cf6,#6d28d9); }
        .btn-purple:hover { box-shadow:0 4px 20px rgba(139,92,246,0.25); }
        .row { display:flex; gap:1rem; flex-wrap:wrap; align-items:center; }
        .row > * { flex:1; min-width:150px; }
        .logs { max-height:300px; overflow-y:auto; font-family:'Monaco',monospace; font-size:0.75rem; color:#6b6b8a; }
        .code-block { background:#0a0a14; border:1px solid rgba(255,255,255,0.04); border-radius:1rem; padding:1rem; font-family:'Monaco',monospace; font-size:0.75rem; color:#a78bfa; word-break:break-all; max-height:300px; overflow-y:auto; }
        .toast { position:fixed; bottom:2rem; right:2rem; background:rgba(0,0,0,0.9); backdrop-filter:blur(20px); border:1px solid rgba(255,255,255,0.06); border-radius:1.2rem; padding:1rem 1.5rem; color:#e8e8f0; font-size:0.9rem; z-index:9999; animation:slideUp 0.3s ease; }
        .toast.success { border-color:rgba(34,197,94,0.3); }
        .toast.error { border-color:rgba(239,68,68,0.3); }
        @keyframes slideUp { from { opacity:0; transform:translateY(20px); } to { opacity:1; transform:translateY(0); } }
        .generated-user-item { background:rgba(255,255,255,0.02); border:1px solid rgba(255,255,255,0.04); border-radius:0.8rem; padding:0.5rem 0.8rem; font-size:0.75rem; margin-bottom:0.3rem; }
        .generated-user-item .user { color:#a78bfa; font-weight:500; }
        .generated-user-item .pass { color:#f59e0b; font-family:'Monaco',monospace; }
    </style>
</head>
<body>
<div class="admin">
    <div class="header">
        <h1>🔧 admin panel</h1>
        <a href="/dashboard" class="back">← back to dashboard</a>
    </div>
    
    <!-- User Management -->
    <div class="card">
        <h2>👥 manage users</h2>
        <div class="row" style="margin-bottom:1.5rem;">
            <input type="text" id="newUsername" placeholder="username (or leave empty for random)">
            <input type="password" id="newPassword" placeholder="password (or leave empty for random)">
            <select id="newRole"><option value="user">user</option><option value="admin">admin</option></select>
            <input type="number" id="newLimit" placeholder="daily limit" value="500">
            <input type="number" id="newRemaining" placeholder="initial limit" value="0">
            <button class="btn btn-primary" id="addUserBtn">+ add user</button>
            <button class="btn btn-purple" id="generateRandomUserBtn">🎲 random single</button>
            <button class="btn btn-success" id="generateBulkUsersBtn">🎲 bulk users</button>
        </div>
        
        <div id="bulkUserControls" style="display:none;margin-top:0.5rem;padding:1rem;background:rgba(255,255,255,0.02);border-radius:1rem;">
            <div class="row">
                <input type="number" id="bulkUserCount" placeholder="number of users" value="5" min="1" max="100">
                <input type="number" id="bulkUserDailyLimit" placeholder="daily limit" value="500">
                <input type="number" id="bulkUserRemaining" placeholder="initial limit" value="0">
                <button class="btn btn-success" id="executeBulkUsersBtn">✅ generate</button>
                <button class="btn btn-secondary" id="cancelBulkUsersBtn">✕ cancel</button>
            </div>
        </div>
        
        <div id="generatedUsersContainer" style="display:none;margin-top:1rem;">
            <div style="display:flex;gap:0.8rem;flex-wrap:wrap;margin-bottom:0.5rem;">
                <button class="btn btn-secondary" id="copyUsersBtn">📋 copy all</button>
                <button class="btn btn-secondary" id="downloadUsersBtn">⬇ download .txt</button>
                <button class="btn btn-secondary" id="clearUsersBtn">🗑️ clear</button>
            </div>
            <div id="generatedUsersList" class="code-block"></div>
        </div>
        
        <table style="margin-top:1.5rem;">
            <thead><tr><th>ID</th><th>Username</th><th>Role</th><th>Daily</th><th>Remaining</th><th>Telegram</th><th>Action</th></tr></thead>
            <tbody id="userTableBody"></tbody>
        </table>
    </div>
    
    <!-- License Management -->
    <div class="card">
        <h2>🎫 generate license</h2>
        <div class="row" style="margin-bottom:1.5rem;">
            <input type="number" id="licenseLimit" placeholder="limit amount" value="100">
            <select id="licenseUser">
                <option value="">— any user —</option>
            </select>
            <input type="number" id="licenseExpiry" placeholder="expiry days (optional)">
            <button class="btn btn-gold" id="generateLicenseBtn">+ generate single</button>
            <button class="btn btn-success" id="generateBulkBtn">+ generate bulk (10)</button>
        </div>
        <div style="display:flex;gap:1rem;flex-wrap:wrap;margin-bottom:1rem;">
            <button class="btn btn-secondary" id="copyLicensesBtn">📋 copy all</button>
            <button class="btn btn-secondary" id="downloadLicensesBtn">⬇ download .txt</button>
        </div>
        <div id="generatedLicenses" class="code-block" style="display:none;"></div>
        
        <h2 style="margin-top:2rem;">📋 all licenses</h2>
        <div id="licenseListContainer" style="max-height:400px;overflow-y:auto;"></div>
    </div>
    
    <!-- Logs -->
    <div class="card">
        <h2>📋 system logs</h2>
        <div class="logs" id="logContainer">loading...</div>
    </div>
</div>

<script>
let generatedUsers = [];
let generatedLicenses = [];

function showToast(message, type = 'success') {
    const existing = document.querySelector('.toast');
    if (existing) existing.remove();
    const toast = document.createElement('div');
    toast.className = 'toast ' + type;
    toast.innerText = message;
    document.body.appendChild(toast);
    setTimeout(() => toast.remove(), 5000);
}

async function loadUsers() {
    try {
        const res = await fetch('/api/users?action=list');
        const users = await res.json();
        const tbody = document.getElementById('userTableBody');
        tbody.innerHTML = users.map(u => `
            <tr>
                <td>${u.id}</td>
                <td>${u.username}</td>
                <td><span style="color:${u.role === 'admin' ? '#a78bfa' : '#6b6b8a'}">${u.role}</span></td>
                <td>${u.daily_limit >= 99999 ? '∞' : u.daily_limit}</td>
                <td>${u.remaining_limit >= 99999 ? '∞' : u.remaining_limit}</td>
                <td>${u.telegram_connected ? '✅' : '❌'}</td>
                <td>
                    ${u.role !== 'admin' ? `<button class="btn btn-danger" onclick="deleteUser(${u.id})">✕</button>` : '—'}
                </td>
            </tr>
        `).join('');
        
        const select = document.getElementById('licenseUser');
        select.innerHTML = '<option value="">— any user —</option>';
        users.forEach(u => {
            const opt = document.createElement('option');
            opt.value = u.id;
            opt.innerText = u.username + (u.role === 'admin' ? ' (admin)' : '');
            select.appendChild(opt);
        });
    } catch(e) {
        showToast('Failed to load users', 'error');
    }
}

document.getElementById('addUserBtn').addEventListener('click', async () => {
    let username = document.getElementById('newUsername').value.trim();
    let password = document.getElementById('newPassword').value.trim();
    const role = document.getElementById('newRole').value;
    const daily_limit = document.getElementById('newLimit').value || 500;
    const remaining_limit = document.getElementById('newRemaining').value || 0;
    
    if (!username) username = generateRandomUsername();
    if (!password) password = generateRandomPassword();
    
    if (!username || !password) return showToast('fill all fields', 'error');
    
    try {
        const res = await fetch('/api/users', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'add', username, password, role, daily_limit, remaining_limit })
        });
        const data = await res.json();
        if (data.status === 'ok') {
            showToast('user added: ' + username);
            loadUsers();
            document.getElementById('newUsername').value = '';
            document.getElementById('newPassword').value = '';
        } else {
            showToast('error: ' + (data.error || 'unknown'), 'error');
        }
    } catch(e) {
        showToast('Failed to add user', 'error');
    }
});

document.getElementById('generateRandomUserBtn').addEventListener('click', async () => {
    const username = generateRandomUsername();
    const password = generateRandomPassword();
    const daily_limit = document.getElementById('newLimit').value || 500;
    const remaining_limit = document.getElementById('newRemaining').value || 0;
    
    try {
        const res = await fetch('/api/users', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'add', username, password, role: 'user', daily_limit, remaining_limit })
        });
        const data = await res.json();
        if (data.status === 'ok') {
            showToast('✅ generated: ' + username);
            loadUsers();
            generatedUsers.push({ username, password });
            showGeneratedUsers();
        } else {
            showToast('error: ' + (data.error || 'unknown'), 'error');
        }
    } catch(e) {
        showToast('Failed to generate user', 'error');
    }
});

document.getElementById('generateBulkUsersBtn').addEventListener('click', () => {
    const controls = document.getElementById('bulkUserControls');
    controls.style.display = controls.style.display === 'none' ? 'block' : 'none';
});

document.getElementById('cancelBulkUsersBtn').addEventListener('click', () => {
    document.getElementById('bulkUserControls').style.display = 'none';
});

document.getElementById('executeBulkUsersBtn').addEventListener('click', async () => {
    const count = parseInt(document.getElementById('bulkUserCount').value) || 5;
    const daily_limit = parseInt(document.getElementById('bulkUserDailyLimit').value) || 500;
    const remaining_limit = parseInt(document.getElementById('bulkUserRemaining').value) || 0;
    
    if (count < 1 || count > 100) {
        showToast('count must be between 1 and 100', 'error');
        return;
    }
    
    try {
        const res = await fetch('/api/users', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'generate_bulk', count, daily_limit, remaining_limit })
        });
        const data = await res.json();
        if (data.status === 'ok') {
            generatedUsers = generatedUsers.concat(data.users);
            showGeneratedUsers();
            loadUsers();
            showToast('✅ generated ' + data.users.length + ' users');
            document.getElementById('bulkUserControls').style.display = 'none';
        } else {
            showToast('error: ' + (data.error || 'unknown'), 'error');
        }
    } catch(e) {
        showToast('Failed to generate users', 'error');
    }
});

function showGeneratedUsers() {
    const container = document.getElementById('generatedUsersContainer');
    const list = document.getElementById('generatedUsersList');
    if (generatedUsers.length === 0) {
        container.style.display = 'none';
        return;
    }
    container.style.display = 'block';
    list.innerHTML = generatedUsers.map((u, i) => `
        <div class="generated-user-item">
            <span class="user">${i+1}. ${u.username}</span>
            <span class="pass">${u.password}</span>
        </div>
    `).join('');
}

document.getElementById('copyUsersBtn').addEventListener('click', () => {
    if (generatedUsers.length === 0) return showToast('no users to copy', 'error');
    const text = generatedUsers.map(u => `${u.username}:${u.password}`).join('\n');
    navigator.clipboard.writeText(text).then(() => {
        showToast('copied ' + generatedUsers.length + ' users');
    }).catch(() => {
        const textarea = document.createElement('textarea');
        textarea.value = text;
        document.body.appendChild(textarea);
        textarea.select();
        document.execCommand('copy');
        textarea.remove();
        showToast('copied ' + generatedUsers.length + ' users');
    });
});

document.getElementById('downloadUsersBtn').addEventListener('click', () => {
    if (generatedUsers.length === 0) return showToast('no users to download', 'error');
    const text = generatedUsers.map(u => `${u.username}:${u.password}`).join('\n');
    const blob = new Blob([text], { type: 'text/plain' });
    const a = document.createElement('a');
    a.href = URL.createObjectURL(blob);
    a.download = 'users_' + Date.now() + '.txt';
    a.click();
    showToast('downloaded ' + generatedUsers.length + ' users');
});

document.getElementById('clearUsersBtn').addEventListener('click', () => {
    generatedUsers = [];
    showGeneratedUsers();
    showToast('cleared');
});

async function deleteUser(id) {
    if (!confirm('remove this user?')) return;
    try {
        await fetch('/api/users', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'delete', id })
        });
        showToast('user deleted');
        loadUsers();
    } catch(e) {
        showToast('Failed to delete user', 'error');
    }
}

// License
document.getElementById('generateLicenseBtn').addEventListener('click', async () => {
    const limit = document.getElementById('licenseLimit').value || 100;
    const user_id = document.getElementById('licenseUser').value || null;
    const expiry = document.getElementById('licenseExpiry').value || null;
    
    try {
        const res = await fetch('/api/licenses', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'generate', limit_amount: limit, user_id, expiry_days: expiry })
        });
        const data = await res.json();
        if (data.status === 'ok') {
            generatedLicenses = [data.code];
            showGeneratedLicenses();
            loadLicenses();
            showToast('license generated: ' + data.code, 'success');
        }
    } catch(e) {
        showToast('Failed to generate license', 'error');
    }
});

document.getElementById('generateBulkBtn').addEventListener('click', async () => {
    const limit = document.getElementById('licenseLimit').value || 100;
    const user_id = document.getElementById('licenseUser').value || null;
    const expiry = document.getElementById('licenseExpiry').value || null;
    const count = 10;
    
    try {
        const res = await fetch('/api/licenses', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'generate', limit_amount: limit, user_id, expiry_days: expiry, count })
        });
        const data = await res.json();
        if (data.status === 'ok') {
            generatedLicenses = data.codes;
            showGeneratedLicenses();
            loadLicenses();
            showToast('generated ' + data.count + ' licenses', 'success');
        }
    } catch(e) {
        showToast('Failed to generate licenses', 'error');
    }
});

function showGeneratedLicenses() {
    const container = document.getElementById('generatedLicenses');
    if (generatedLicenses.length === 0) {
        container.style.display = 'none';
        return;
    }
    container.style.display = 'block';
    container.innerHTML = generatedLicenses.map((c, i) => 
        `<div>${i+1}. ${c}</div>`
    ).join('');
}

document.getElementById('copyLicensesBtn').addEventListener('click', () => {
    if (generatedLicenses.length === 0) return showToast('no licenses to copy', 'error');
    const text = generatedLicenses.join('\n');
    navigator.clipboard.writeText(text).then(() => {
        showToast('copied ' + generatedLicenses.length + ' licenses');
    }).catch(() => {
        const textarea = document.createElement('textarea');
        textarea.value = text;
        document.body.appendChild(textarea);
        textarea.select();
        document.execCommand('copy');
        textarea.remove();
        showToast('copied ' + generatedLicenses.length + ' licenses');
    });
});

document.getElementById('downloadLicensesBtn').addEventListener('click', () => {
    if (generatedLicenses.length === 0) return showToast('no licenses to download', 'error');
    const blob = new Blob([generatedLicenses.join('\n')], { type: 'text/plain' });
    const a = document.createElement('a');
    a.href = URL.createObjectURL(blob);
    a.download = 'licenses_' + Date.now() + '.txt';
    a.click();
    showToast('downloaded ' + generatedLicenses.length + ' licenses');
});

async function loadLicenses() {
    try {
        const res = await fetch('/api/licenses?action=list');
        const licenses = await res.json();
        const container = document.getElementById('licenseListContainer');
        if (licenses.length === 0) {
            container.innerHTML = '<div style="color:#6b6b8a;padding:1rem;text-align:center;">no licenses generated yet.</div>';
            return;
        }
        container.innerHTML = licenses.map(l => `
            <div style="display:flex;justify-content:space-between;align-items:center;padding:0.5rem 0;border-bottom:1px solid rgba(255,255,255,0.03);font-size:0.8rem;">
                <span style="font-family:'Monaco',monospace;color:#f59e0b;">${l.code}</span>
                <span>${l.limit_amount} limit</span>
                <span style="color:${l.used ? '#22c55e' : '#6b6b8a'}">${l.used ? '✅ used by ' + (l.redeemed_by || 'unknown') : 'available'}</span>
                <span style="color:#6b6b8a;font-size:0.65rem;">${l.created_at}</span>
            </div>
        `).join('');
    } catch(e) {
        // Silent fail
    }
}

async function loadLogs() {
    try {
        const res = await fetch('/api/users?action=logs');
        const logs = await res.json();
        document.getElementById('logContainer').innerHTML = logs.map(l => 
            `[${l.created_at}] ${l.username || 'system'}: ${l.action}`
        ).join('\n') || 'no logs yet.';
    } catch(e) {
        // Silent fail
    }
}

function generateRandomUsername() {
    const adjectives = ['neon', 'shadow', 'cyber', 'ghost', 'dark', 'void', 'phantom', 'cipher', 'static', 'pulse', 'frost', 'ember', 'shade', 'echo', 'trace'];
    const nouns = ['wolf', 'hawk', 'viper', 'raven', 'tiger', 'fox', 'lynx', 'drake', 'owl', 'crow', 'lion', 'bear', 'eagle', 'fury', 'storm'];
    return adjectives[Math.floor(Math.random() * adjectives.length)] + '_' + nouns[Math.floor(Math.random() * nouns.length)] + '_' + Math.floor(100 + Math.random() * 900);
}

function generateRandomPassword() {
    const chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()_+-=';
    let pass = '';
    for (let i = 0; i < 12; i++) {
        pass += chars[Math.floor(Math.random() * chars.length)];
    }
    return pass;
}

loadUsers();
loadLicenses();
loadLogs();
setInterval(loadLogs, 30000);
setInterval(loadLicenses, 60000);
</script>
</body>
</html>
