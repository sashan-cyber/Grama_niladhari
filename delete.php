<?php
require_once 'config.php';

$id   = intval($_GET['id'] ?? 0);
$name = htmlspecialchars($_GET['name'] ?? 'this resident');

if ($id <= 0) { header('Location: index.php'); exit; }

// Fetch full record for display
$row = $conn->query("SELECT * FROM residents WHERE id=$id")->fetch_assoc();
if (!$row) { header('Location: index.php'); exit; }

$deleted = false;
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_delete'])) {
  $stmt = $conn->prepare("DELETE FROM residents WHERE id=?");
  $stmt->bind_param('i', $id);
  if ($stmt->execute()) {
    header('Location: index.php?deleted=1');
    exit;
  } else {
    $error = 'Database error: ' . $conn->error;
  }
  $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Delete Resident – Grama Niladhari</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=DM+Sans:ital,wght@0,300;0,400;0,500;1,300&display=swap" rel="stylesheet">
<style>
:root {
  --sage:       #6b8f71;
  --sage-light: #a8c5ac;
  --sage-pale:  #eef4ef;
  --sage-dark:  #4a6b50;
  --cream:      #faf8f3;
  --ink:        #2c2c2c;
  --ink-soft:   #5a5a5a;
  --ink-muted:  #8a8a8a;
  --danger:     #c0392b;
  --danger-pale:#fdf0ef;
  --danger-mid: #e74c3c;
  --border:     #ddd8cc;
  --shadow:     0 2px 20px rgba(107,143,113,.12);
  --radius:     14px;
  --radius-sm:  8px;
}
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0;}
body{font-family:'DM Sans',sans-serif;background:var(--cream);color:var(--ink);min-height:100vh;}

.header{background:linear-gradient(135deg,var(--sage-dark) 0%,var(--sage) 60%,var(--sage-light) 100%);padding:0 2.5rem;display:flex;align-items:center;justify-content:space-between;box-shadow:0 2px 24px rgba(74,107,80,.35);position:sticky;top:0;z-index:100;min-height:72px;}
.header-brand{display:flex;align-items:center;gap:1rem;}
.header-emblem{width:46px;height:46px;background:rgba(255,255,255,.18);border:2px solid rgba(255,255,255,.4);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:1.4rem;}
.header-title{font-family:'Playfair Display',serif;color:#fff;font-size:1.25rem;font-weight:700;}
.header-title span{font-size:.8rem;font-weight:300;opacity:.85;display:block;font-family:'DM Sans',sans-serif;}
.header-nav a{color:rgba(255,255,255,.9);text-decoration:none;font-size:.875rem;font-weight:500;padding:.5rem 1.1rem;border-radius:20px;transition:background .2s;border:1.5px solid transparent;}
.header-nav a:hover{background:rgba(255,255,255,.18);border-color:rgba(255,255,255,.3);}

.container{max-width:620px;margin:0 auto;padding:3rem 1.5rem;}

/* Warning card */
.warn-card{
  background:#fff;border:1px solid var(--border);border-radius:var(--radius);
  box-shadow:var(--shadow);overflow:hidden;
  animation:fadeUp .5s ease;
}

.warn-header{
  background:var(--danger-pale);border-bottom:2px solid #f0b8b4;
  padding:2rem;text-align:center;
}
.warn-icon{
  width:68px;height:68px;background:#fff;border:3px solid #f0b8b4;
  border-radius:50%;display:flex;align-items:center;justify-content:center;
  font-size:2rem;margin:0 auto 1rem;
  box-shadow:0 4px 16px rgba(192,57,43,.15);
}
.warn-header h1{font-family:'Playfair Display',serif;color:var(--danger);font-size:1.5rem;margin-bottom:.4rem;}
.warn-header p{color:#9b4b42;font-size:.9rem;}

/* Resident card */
.resident-card{
  margin:1.75rem;
  background:var(--sage-pale);border:1px solid var(--sage-light);
  border-radius:var(--radius-sm);padding:1.25rem 1.5rem;
}
.resident-card h3{font-family:'Playfair Display',serif;font-size:1.1rem;color:var(--sage-dark);margin-bottom:1rem;padding-bottom:.6rem;border-bottom:1px dashed var(--sage-light);}

.detail-row{display:flex;gap:.5rem;margin-bottom:.55rem;font-size:.875rem;}
.detail-label{color:var(--ink-muted);min-width:110px;flex-shrink:0;}
.detail-value{color:var(--ink);font-weight:500;}

/* Warning note */
.warn-note{
  margin:0 1.75rem 1.75rem;
  background:#fff8f7;border:1.5px solid #f0b8b4;border-radius:var(--radius-sm);
  padding:1rem 1.25rem;
  display:flex;gap:.75rem;align-items:flex-start;
  font-size:.875rem;color:#7a3030;
}
.warn-note-icon{font-size:1.2rem;flex-shrink:0;margin-top:.05rem;}

/* Actions */
.warn-actions{
  display:flex;justify-content:flex-end;gap:.75rem;
  padding:1.25rem 1.75rem;border-top:1px solid var(--border);background:#fafafa;
}

.btn{display:inline-flex;align-items:center;gap:.4rem;padding:.75rem 1.75rem;border-radius:var(--radius-sm);border:none;cursor:pointer;font-family:'DM Sans',sans-serif;font-size:.9rem;font-weight:500;text-decoration:none;transition:transform .15s,box-shadow .15s,background .2s;}
.btn:active{transform:scale(.97);}
.btn-cancel{background:transparent;color:var(--ink-soft);border:1.5px solid var(--border);}
.btn-cancel:hover{background:var(--cream);}
.btn-danger{background:var(--danger);color:#fff;box-shadow:0 2px 12px rgba(192,57,43,.3);}
.btn-danger:hover{background:#a93226;box-shadow:0 4px 18px rgba(192,57,43,.4);}

.alert-error{padding:1rem 1.25rem;border-radius:var(--radius-sm);margin-bottom:1.5rem;background:var(--danger-pale);color:var(--danger);border:1.5px solid #f0b8b4;font-size:.9rem;}

footer{text-align:center;padding:2rem;color:var(--ink-muted);font-size:.8rem;border-top:1px solid var(--border);margin-top:3rem;}

@keyframes fadeUp{from{opacity:0;transform:translateY(18px);}to{opacity:1;transform:translateY(0);}}
@media(max-width:480px){.warn-actions{flex-direction:column;}.btn{justify-content:center;}.header{padding:0 1rem;}}
</style>
</head>
<body>

<header class="header">
  <div class="header-brand">
    <div class="header-emblem">🏛️</div>
    <div class="header-title">Grama Niladhari Office<span>Resident Registry System</span></div>
  </div>
  <nav class="header-nav">
    <a href="index.php">← Back to Search</a>
  </nav>
</header>

<main class="container">

  <?php if ($error): ?>
    <div class="alert-error">⚠️ <?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <div class="warn-card">
    <div class="warn-header">
      <div class="warn-icon">🗑️</div>
      <h1>Delete Resident Record</h1>
      <p>Please review the information below before confirming.</p>
    </div>

    <!-- Resident details preview -->
    <div class="resident-card">
      <h3>📄 Resident Details</h3>
      <div class="detail-row">
        <span class="detail-label">Full Name</span>
        <span class="detail-value"><?= htmlspecialchars($row['full_name']) ?></span>
      </div>
      <div class="detail-row">
        <span class="detail-label">NIC Number</span>
        <span class="detail-value" style="font-family:monospace;"><?= htmlspecialchars($row['nic']) ?></span>
      </div>
      <div class="detail-row">
        <span class="detail-label">Date of Birth</span>
        <span class="detail-value"><?= date('d F Y', strtotime($row['dob'])) ?></span>
      </div>
      <div class="detail-row">
        <span class="detail-label">Gender</span>
        <span class="detail-value"><?= htmlspecialchars($row['gender']) ?></span>
      </div>
      <div class="detail-row">
        <span class="detail-label">Phone</span>
        <span class="detail-value"><?= htmlspecialchars($row['phone']) ?></span>
      </div>
      <div class="detail-row">
        <span class="detail-label">Email</span>
        <span class="detail-value"><?= htmlspecialchars($row['email']) ?></span>
      </div>
      <div class="detail-row">
        <span class="detail-label">Occupation</span>
        <span class="detail-value"><?= htmlspecialchars($row['occupation'] ?: '—') ?></span>
      </div>
      <div class="detail-row">
        <span class="detail-label">Address</span>
        <span class="detail-value"><?= htmlspecialchars($row['address']) ?></span>
      </div>
      <div class="detail-row">
        <span class="detail-label">Registered</span>
        <span class="detail-value"><?= date('d F Y, H:i', strtotime($row['registered_date'])) ?></span>
      </div>
    </div>

    <div class="warn-note">
      <div class="warn-note-icon">⚠️</div>
      <div>
        <strong>This action cannot be undone.</strong> Deleting this record will permanently remove
        <strong><?= htmlspecialchars($row['full_name']) ?></strong>'s information from the resident database.
      </div>
    </div>

    <form method="POST">
      <div class="warn-actions">
        <a href="index.php" class="btn btn-cancel">✕ Cancel</a>
        <a href="edit.php?id=<?= $id ?>" class="btn btn-cancel" style="border-color:var(--sage-light);color:var(--sage-dark);">✏️ Edit Instead</a>
        <button type="submit" name="confirm_delete" class="btn btn-danger">🗑️ Yes, Delete Record</button>
      </div>
    </form>

  </div>
</main>

<footer>© <?= date('Y') ?> Grama Niladhari Office · Resident Registry System</footer>
</body>
</html>
