<?php
// admin.php - Single-file admin panel: login, dashboard (stats), key generator, manager
session_start();

// ========== CONFIG - edit these to match your environment ==========
$db_host = 'db_host';
$db_name = 'db_name';
$db_user = 'db_user';
$db_pass = 'db_pass';
$admin_user = 'admin';
// SHA256 of password 'zaemond77#$' (do NOT change unless you want a different password)
$admin_pass_sha256 = '9c9d613f45f6ab991fae73a17cc371d9ecc51bb82d427ab9b034fe6a21751464';

// ==================================================================

// Simple helper to connect to DB
function db(){
    global $db_host,$db_name,$db_user,$db_pass;
    static $pdo = null;
    if($pdo) return $pdo;
    $dsn = "mysql:host={$db_host};dbname={$db_name};charset=utf8mb4";
    try{
        $pdo = new PDO($dsn,$db_user,$db_pass,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
    }catch(Exception $e){
        die("DB connection error: ".htmlspecialchars($e->getMessage()));
    }
    return $pdo;
}

// Ensure keys table exists
function ensure_table(){
    $sql = "CREATE TABLE IF NOT EXISTS `keys` (
      `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
      `key_value` VARCHAR(128) NOT NULL,
      `price` DECIMAL(10,2) DEFAULT 0.00,
      `status` ENUM('active','used','expired','disabled') DEFAULT 'active',
      `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
      `expires_at` DATETIME DEFAULT NULL,
      `meta` TEXT DEFAULT NULL,
      PRIMARY KEY (`id`),
      UNIQUE KEY `uk_key_value` (`key_value`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    db()->exec($sql);
}
ensure_table();

// --------- Authentication (simple) ---------
if (isset($_GET['action']) && $_GET['action']==='logout'){
    session_destroy();
    header('Location: admin.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])){
    $user = $_POST['user'] ?? '';
    $pass = $_POST['pass'] ?? '';
    if ($user === $admin_user && hash('sha256',$pass) === $admin_pass_sha256){
        $_SESSION['is_admin'] = true;
        header('Location: admin.php');
        exit;
    } else {
        $login_error = 'Invalid credentials';
    }
}

// If not logged in, show login form
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true){
    ?>
    <!doctype html>
    <html>
    <head>
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Admin Login</title>
    <style>
    body{font-family:system-ui,Segoe UI,Roboto,Arial;background:#0f1724;color:#e6eef8;display:flex;align-items:center;justify-content:center;height:100vh;margin:0}
    .card{background:linear-gradient(180deg,#0b1220, #07101a);padding:24px;border-radius:14px;box-shadow:0 10px 30px rgba(2,6,23,.6);width:360px}
    input{width:100%;padding:12px;margin:8px 0;border-radius:8px;border:1px solid rgba(255,255,255,.06);background:rgba(255,255,255,.02);color:#fff}
    button{width:100%;padding:12px;border-radius:8px;border:0;background:#0a84ff;color:#fff;font-weight:600}
    .small{font-size:13px;color:#9fb0d8}
    </style>
    </head>
    <body>
    <div class="card">
      <h2 style="margin:0 0 8px 0">Admin Login</h2>
      <p class="small">Login to manage keys — mobile-friendly admin panel</p>
      <?php if(!empty(\$login_error)): ?>
        <div style="background:#ffebeb;color:#7a1b1b;padding:8px;border-radius:6px;margin:8px 0"><?=htmlspecialchars(\$login_error)?></div>
      <?php endif; ?>
      <form method="post">
        <input name="user" placeholder="Username" required>
        <input name="pass" type="password" placeholder="Password" required>
        <button name="login">Sign in</button>
      </form>
      <p class="small" style="margin-top:10px">Username: <strong>admin</strong><br>Password: <strong>zaemond77#\$</strong></p>
    </div>
    </body>
    </html>
    <?php
    exit;
}

// ---------------- AJAX HANDLERS ----------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax'])){
    $pdo = db();
    $ajax = $_POST['ajax'];
    header('Content-Type: application/json');

    if ($ajax === 'generate'){
        $count = min(500, max(1, (int)($_POST['count'] ?? 1)));
        $prefix = trim($_POST['prefix'] ?? '');
        $price = number_format((float)($_POST['price'] ?? 0),2,'.','');
        $expiry_days = (int)($_POST['expiry'] ?? 0);
        $len = min(32, max(6, (int)($_POST['len'] ?? 12)));
        $group = max(0, (int)($_POST['group'] ?? 4));
        $status = in_array($_POST['status'] ?? 'active',['active','used','expired','disabled'])?$_POST['status']:'active';

        $ins = $pdo->prepare("INSERT INTO `keys` (key_value,price,status,expires_at) VALUES (?,?,?,?)");
        $out = [];
        for ($i=0;$i<$count;$i++){
            $raw = bin2hex(random_bytes($len));
            if ($group>0) $raw = implode('-',str_split($raw,$group));
            $key = $prefix ? strtoupper($prefix) . '-' . strtoupper($raw) : strtoupper($raw);
            $expires_at = $expiry_days>0 ? date('Y-m-d H:i:s', strtotime("+{$expiry_days} days")) : null;
            try{
                $ins->execute([$key,$price,$status,$expires_at]);
                $out[] = $key;
            }catch(PDOException $e){
                // skip duplicates/errors
            }
        }
        echo json_encode(['ok'=>true,'keys'=>$out]);
        exit;
    }

    if ($ajax === 'status'){
        $id = (int)($_POST['id'] ?? 0);
        $status = in_array($_POST['status'] ?? 'active',['active','used','expired','disabled'])?$_POST['status']:'active';
        $pdo->prepare("UPDATE `keys` SET status=? WHERE id=?")->execute([$status,$id]);
        echo json_encode(['ok'=>true]); exit;
    }

    if ($ajax === 'delete'){
        $id = (int)($_POST['id'] ?? 0);
        $pdo->prepare("DELETE FROM `keys` WHERE id=?")->execute([$id]);
        echo json_encode(['ok'=>true]); exit;
    }

    if ($ajax === 'export'){ // export CSV of all keys
        $rows = $pdo->query("SELECT id,key_value,price,status,created_at,expires_at FROM `keys` ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
        // return CSV as text in JSON
        $csv = "id,key,price,status,created_at,expires_at
";
        foreach($rows as $r){
            $csv .= implode(',',array_map(function($v){ return '"'.str_replace('"','""',(string)$v).'"'; },$r)) . "
";
        }
        echo json_encode(['ok'=>true,'csv'=>$csv]); exit;
    }

    echo json_encode(['ok'=>false]); exit;
}

// ----------------- Page: Dashboard -----------------
$pdo = db();
// Basic stats
$total = $pdo->query("SELECT COUNT(*) FROM `keys`")->fetchColumn();
$by_status = $pdo->query("SELECT status,COUNT(*) c FROM `keys` GROUP BY status")->fetchAll(PDO::FETCH_KEY_PAIR);
$sum_revenue = $pdo->query("SELECT COALESCE(SUM(price),0) FROM `keys`")->fetchColumn();
$expiring = $pdo->prepare("SELECT COUNT(*) FROM `keys` WHERE expires_at IS NOT NULL AND expires_at BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 7 DAY)");
$expiring->execute();
$exp_count = $expiring->fetchColumn();

$latest = $pdo->query("SELECT id,key_value,price,status,created_at,expires_at FROM `keys` ORDER BY id DESC LIMIT 12")->fetchAll(PDO::FETCH_ASSOC);

// Handle search for management table via GET 's'
$search = trim($_GET['s'] ?? '');
$where = '';
$params = [];
if ($search !== ''){
    $where = "WHERE key_value LIKE ? OR status LIKE ? OR price LIKE ?";
    $params = ["%$search%","%$search%","%$search%"];
}
$stmt = $pdo->prepare("SELECT * FROM `keys` $where ORDER BY id DESC LIMIT 200");
$stmt->execute($params);
$keys = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ----------------- HTML Output -----------------
?>
<!doctype html>
<html>
<head>
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Admin Dashboard</title>
<style>
:root{--bg:#0b1220;--card:#0f1724;--muted:#9fb0d8;--accent:#0a84ff}
*{box-sizing:border-box}
body{font-family:system-ui,Segoe UI,Roboto,Arial,Helvetica;background:linear-gradient(180deg,var(--bg),#07101a);color:#e6eef8;margin:0;padding:12px}
.container{max-width:1100px;margin:0 auto}
.header{display:flex;align-items:center;gap:12px;margin-bottom:12px}
.logo{font-weight:700;font-size:18px}
.top-actions{margin-left:auto;display:flex;gap:8px}
.btn{background:var(--accent);color:#fff;padding:10px 12px;border-radius:10px;border:0;cursor:pointer}
.card{background:linear-gradient(180deg,#08101a,#08121a);padding:14px;border-radius:12px;margin-bottom:12px;box-shadow:0 6px 20px rgba(2,6,23,.6)}
.grid{display:grid;grid-template-columns:repeat(3,1fr);gap:12px}
@media(max-width:900px){.grid{grid-template-columns:repeat(2,1fr)}}
@media(max-width:620px){.grid{grid-template-columns:1fr}}
.stat{padding:12px;border-radius:10px}
.stat h3{margin:0;font-size:20px}
.small{color:var(--muted);font-size:13px}
.tabs{display:flex;gap:8px;flex-wrap:wrap;margin-bottom:12px}
.tab{padding:10px 12px;border-radius:10px;background:rgba(255,255,255,.02);cursor:pointer}
.tab.active{background:var(--accent)}
.search{display:flex;gap:8px;margin-bottom:12px}
.search input{flex:1;padding:10px;border-radius:10px;border:1px solid rgba(255,255,255,.06);background:transparent;color:#fff}
.table{width:100%;border-collapse:collapse}
.table th,.table td{padding:10px;border-bottom:1px solid rgba(255,255,255,.03);text-align:left;font-size:14px}
.code{background:rgba(255,255,255,.03);padding:6px;border-radius:6px;font-family:monospace}
.actions button{margin-right:6px}
.mobile-hide{display:block}
@media(max-width:700px){.mobile-hide{display:none}}

/* generator form */
.form-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:8px}
@media(max-width:800px){.form-grid{grid-template-columns:repeat(2,1fr)}}
@media(max-width:500px){.form-grid{grid-template-columns:1fr}}
input.select,select{width:100%;padding:10px;border-radius:8px;border:1px solid rgba(255,255,255,.04);background:transparent;color:#fff}

.footer{margin-top:20px;color:var(--muted);font-size:13px;text-align:center}
</style>
</head>
<body>
<div class="container">
  <div class="header">
    <div class="logo">ASHURA — Admin</div>
    <div class="small">Manage keys • Dashboard</div>
    <div class="top-actions">
      <a class="btn" href="?action=logout">Logout</a>
      <button class="btn" id="tab_manage">Manage</button>
    </div>
  </div>

  <!-- STATS -->
  <div class="grid">
    <div class="card stat">
      <h3><?=$total?></h3>
      <div class="small">Total keys</div>
    </div>
    <div class="card stat">
      <h3><?=number_format($sum_revenue,2)?></h3>
      <div class="small">Total value (sum of price)</div>
    </div>
    <div class="card stat">
      <h3><?=$exp_count?></h3>
      <div class="small">Expiring within 7 days</div>
    </div>
  </div>

  <div style="height:12px"></div>

  <div class="tabs">
    <div class="tab active" id="tab_home" onclick="show('home')">Home</div>
    <div class="tab" id="tab_generate_btn" onclick="show('generate')">Generate</div>
    <div class="tab" id="tab_manage_btn" onclick="show('manage')">Manage</div>
  </div>

  <!-- HOME -->
  <div id="home">
    <div class="card">
      <h3 style="margin-top:0">Quick Overview</h3>
      <p class="small">Status breakdown</p>
      <div style="display:flex;gap:12px;flex-wrap:wrap;margin-top:8px">
        <div class="card" style="padding:10px;min-width:140px">
          <div class="small">Active</div>
          <div style="font-weight:700;font-size:18px"><?= $by_status['active'] ?? 0 ?></div>
        </div>
        <div class="card" style="padding:10px;min-width:140px">
          <div class="small">Used</div>
          <div style="font-weight:700;font-size:18px"><?= $by_status['used'] ?? 0 ?></div>
        </div>
        <div class="card" style="padding:10px;min-width:140px">
          <div class="small">Disabled</div>
          <div style="font-weight:700;font-size:18px"><?= $by_status['disabled'] ?? 0 ?></div>
        </div>
        <div class="card" style="padding:10px;min-width:140px">
          <div class="small">Expired</div>
          <div style="font-weight:700;font-size:18px"><?= $by_status['expired'] ?? 0 ?></div>
        </div>
      </div>

    </div>

    <div style="height:12px"></div>

    <div class="card">
      <h3 style="margin:0 0 8px 0">Latest Keys</h3>
      <div class="small">Recent generated keys</div>
      <table class="table" style="margin-top:10px">
        <tr><th>ID</th><th>Key</th><th>Status</th><th>Price</th><th>Created</th></tr>
        <?php foreach($latest as $l): ?>
          <tr>
            <td><?=$l['id']?></td>
            <td><span class="code"><?=htmlspecialchars($l['key_value'])?></span></td>
            <td><?=htmlspecialchars($l['status'])?></td>
            <td><?=htmlspecialchars($l['price'])?></td>
            <td><?=htmlspecialchars($l['created_at'])?></td>
          </tr>
        <?php endforeach; ?>
      </table>
    </div>
  </div>

  <!-- GENERATE -->
  <div id="generate" style="display:none">
    <div class="card">
      <h3 style="margin-top:0">Generate Keys</h3>
      <div class="small">Create one or many keys</div>
      <div style="height:10px"></div>
      <div class="form-grid">
        <div>
          <label class="small">Count</label>
          <input id="g_count" type="number" value="5">
        </div>
        <div>
          <label class="small">Prefix (optional)</label>
          <input id="g_prefix" type="text" placeholder="ASHURA">
        </div>
        <div>
          <label class="small">Price</label>
          <input id="g_price" type="text" value="0.00">
        </div>
        <div>
          <label class="small">Status</label>
          <select id="g_status"><option>active</option><option>used</option><option>disabled</option><option>expired</option></select>
        </div>
        <div>
          <label class="small">Expiry days (0 = none)</label>
          <input id="g_exp" type="number" value="0">
        </div>
        <div>
          <label class="small">Random length (bytes)</label>
          <input id="g_len" type="number" value="12">
        </div>
        <div>
          <label class="small">Group size (0=no dashes)</label>
          <input id="g_group" type="number" value="4">
        </div>
      </div>
      <div style="height:12px"></div>
      <div style="display:flex;gap:8px;flex-wrap:wrap">
        <button class="btn" onclick="generateNow()">Generate</button>
        <button class="btn" onclick="downloadCSV()">Export All CSV</button>
      </div>
    </div>
  </div>

  <!-- MANAGE -->
  <div id="manage" style="display:none">
    <div class="card">
      <h3 style="margin-top:0">Manage Keys</h3>
      <div class="small">Search, update status, delete</div>
      <div style="height:8px"></div>
      <div class="search">
        <form style="flex:1" method="get">
          <input name="s" placeholder="Search key, status, price" value="<?=htmlspecialchars($search)?>">
        </form>
        <button class="btn" onclick="location.href='admin.php'">Clear</button>
      </div>

      <table class="table">
        <tr><th>ID</th><th>Key</th><th>Price</th><th>Status</th><th>Expire</th><th class="mobile-hide">Created</th><th>Actions</th></tr>
        <?php foreach($keys as $k): ?>
        <tr>
          <td><?=$k['id']?></td>
          <td><span class="code"><?=htmlspecialchars($k['key_value'])?></span></td>
          <td><?=htmlspecialchars($k['price'])?></td>
          <td><span class="small"><?=htmlspecialchars($k['status'])?></span></td>
          <td><?=htmlspecialchars($k['expires_at'] ?: '-')?></td>
          <td class="mobile-hide"><?=htmlspecialchars($k['created_at'])?></td>
          <td class="actions">
            <button onclick="setStatus(<?=$k['id']?>,'used')" style="background:#0a84ff;color:#fff;padding:6px;border-radius:6px;border:0">Used</button>
            <button onclick="setStatus(<?=$k['id']?>,'active')" style="background:#28a745;color:#fff;padding:6px;border-radius:6px;border:0">Active</button>
            <button onclick="setStatus(<?=$k['id']?>,'disabled')" style="background:#dc3545;color:#fff;padding:6px;border-radius:6px;border:0">Disable</button>
            <button onclick="del(<?=$k['id']?>)" style="background:#000;color:#fff;padding:6px;border-radius:6px;border:0">Delete</button>
          </td>
        </tr>
        <?php endforeach; ?>
      </table>
    </div>
  </div>

  <div class="footer">Built for InfinityFree • Mobile-first admin</div>
</div>

<script>
function show(id){
  document.getElementById('home').style.display='none';
  document.getElementById('generate').style.display='none';
  document.getElementById('manage').style.display='none';
  document.getElementById(id).style.display='block';
  document.querySelectorAll('.tab').forEach(t=>t.classList.remove('active'));
  if(id==='home') document.getElementById('tab_home').classList.add('active');
  if(id==='generate') document.getElementById('tab_generate_btn').classList.add('active');
  if(id==='manage') document.getElementById('tab_manage_btn').classList.add('active');
}

async function generateNow(){
  const data = new FormData();
  data.append('ajax','generate');
  data.append('count',document.getElementById('g_count').value);
  data.append('prefix',document.getElementById('g_prefix').value);
  data.append('price',document.getElementById('g_price').value);
  data.append('expiry',document.getElementById('g_exp').value);
  data.append('len',document.getElementById('g_len').value);
  data.append('group',document.getElementById('g_group').value);
  data.append('status',document.getElementById('g_status').value);
  const res = await fetch('admin.php',{method:'POST',body:data});
  const json = await res.json();
  if(json.ok){
    alert('Generated '+json.keys.length+' keys');
    location.reload();
  }else alert('Error');
}

async function setStatus(id,status){
  const data = new FormData(); data.append('ajax','status'); data.append('id',id); data.append('status',status);
  const res = await fetch('admin.php',{method:'POST',body:data}); const j = await res.json(); if(j.ok) location.reload();
}
async function del(id){ if(!confirm('Delete?')) return; const d=new FormData(); d.append('ajax','delete'); d.append('id',id); const r=await fetch('admin.php',{method:'POST',body:d}); const j=await r.json(); if(j.ok) location.reload(); }

async function downloadCSV(){ const d=new FormData(); d.append('ajax','export'); const r=await fetch('admin.php',{method:'POST',body:d}); const j=await r.json(); if(j.ok){ const blob=new Blob([j.csv],{type:'text/csv'}); const url=URL.createObjectURL(blob); const a=document.createElement('a'); a.href=url; a.download='keys_export.csv'; document.body.appendChild(a); a.click(); a.remove(); URL.revokeObjectURL(url);} }

// default tab
show('home');
</script>

</body>
</html>
