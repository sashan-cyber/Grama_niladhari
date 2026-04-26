<?php
require_once 'config.php';

// ── Fetch resident ──────────────────────────────────────────
$id = intval($_GET['id'] ?? 0);
if ($id <= 0) { header('Location: index.php'); exit; }

$row = $conn->query("SELECT * FROM residents WHERE id=$id")->fetch_assoc();
if (!$row) { header('Location: index.php'); exit; }

$errors  = [];
$success = false;

$form = [
  'full_name'  => $row['full_name'],
  'dob'        => $row['dob'],
  'nic'        => $row['nic'],
  'address'    => $row['address'],
  'phone'      => $row['phone'],
  'email'      => $row['email'],
  'occupation' => $row['occupation'] ?? '',
  'gender'     => $row['gender'],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  foreach ($form as $key => $_) {
    $form[$key] = trim($_POST[$key] ?? '');
  }

  // ── Validation ─────────────────────────────────────────
  if ($form['full_name'] === '')
      $errors['full_name'] = 'Full name is required.';
  elseif (strlen($form['full_name']) > 100)
      $errors['full_name'] = 'Full name must be ≤ 100 characters.';

  if ($form['dob'] === '')
      $errors['dob'] = 'Date of birth is required.';
  elseif (strtotime($form['dob']) >= strtotime('today'))
      $errors['dob'] = 'Date of birth must be in the past.';

  if ($form['nic'] === '')
      $errors['nic'] = 'NIC number is required.';
  elseif (!preg_match('/^[0-9]{9}[VvXx]$|^[0-9]{12}$/', $form['nic']))
      $errors['nic'] = 'NIC must be 9 digits + V/X or 12 digits.';

  if ($form['address'] === '')
      $errors['address'] = 'Address is required.';

  if ($form['phone'] === '')
      $errors['phone'] = 'Phone number is required.';
  elseif (!preg_match('/^[0-9+\-\s]{7,15}$/', $form['phone']))
      $errors['phone'] = 'Enter a valid phone number.';

  if ($form['email'] === '')
      $errors['email'] = 'Email address is required.';
  elseif (!filter_var($form['email'], FILTER_VALIDATE_EMAIL))
      $errors['email'] = 'Enter a valid email address.';

  if (!in_array($form['gender'], ['Male','Female','Other']))
      $errors['gender'] = 'Please select a gender.';

  // NIC uniqueness (exclude self)
  if (empty($errors['nic'])) {
    $chk = $conn->prepare("SELECT id FROM residents WHERE nic=? AND id!=?");
    $chk->bind_param('si', $form['nic'], $id);
    $chk->execute();
    if ($chk->get_result()->num_rows > 0)
        $errors['nic'] = 'This NIC number is already registered to another resident.';
    $chk->close();
  }

  if (empty($errors)) {
    $stmt = $conn->prepare(
      "UPDATE residents SET full_name=?,dob=?,nic=?,address=?,phone=?,email=?,occupation=?,gender=?
       WHERE id=?"
    );
    $stmt->bind_param('ssssssssi',
      $form['full_name'], $form['dob'],    $form['nic'],
      $form['address'],   $form['phone'],  $form['email'],
      $form['occupation'],$form['gender'], $id
    );
    if ($stmt->execute()) {
      $success = true;
      $row = $form; // update display row
    } else {
      $errors['db'] = 'Database error: ' . $conn->error;
    }
    $stmt->close();
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Edit Resident – Grama Niladhari</title>
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
  --gold:       #c9a84c;
  --danger:     #c0392b;
  --danger-pale:#fdf0ef;
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

.container{max-width:820px;margin:0 auto;padding:2.5rem 1.5rem;}

.page-heading{display:flex;align-items:center;gap:.75rem;margin-bottom:2rem;animation:fadeUp .4s ease;}
.page-heading h1{font-family:'Playfair Display',serif;font-size:1.6rem;color:var(--sage-dark);}
.breadcrumb{font-size:.82rem;color:var(--ink-muted);margin-top:.2rem;}
.breadcrumb a{color:var(--sage);text-decoration:none;}
.breadcrumb a:hover{text-decoration:underline;}

/* Identity strip */
.identity-strip{
  background:#fff;border:1px solid var(--border);border-radius:var(--radius);
  padding:1.25rem 1.75rem;margin-bottom:1.5rem;
  display:flex;align-items:center;gap:1.25rem;
  box-shadow:var(--shadow);animation:fadeUp .4s .05s ease both;
}
.id-avatar{
  width:52px;height:52px;background:var(--sage-pale);border:2px solid var(--sage-light);
  border-radius:50%;display:flex;align-items:center;justify-content:center;
  font-size:1.5rem;flex-shrink:0;
}
.id-name{font-family:'Playfair Display',serif;font-size:1.1rem;font-weight:600;color:var(--ink);}
.id-meta{font-size:.82rem;color:var(--ink-muted);margin-top:.2rem;}
.id-badge{
  margin-left:auto;background:var(--gold-light,#fdf6e3);color:#8a6d00;
  border:1px solid #e8c96e;border-radius:20px;padding:.25rem .9rem;font-size:.78rem;font-weight:600;
}

.alert{padding:1rem 1.25rem;border-radius:var(--radius-sm);margin-bottom:1.5rem;display:flex;align-items:flex-start;gap:.6rem;font-size:.9rem;animation:fadeUp .4s ease;}
.alert-success{background:var(--sage-pale);color:var(--sage-dark);border:1.5px solid var(--sage-light);}
.alert-error{background:var(--danger-pale);color:var(--danger);border:1.5px solid #f0b8b4;}

.card{background:#fff;border:1px solid var(--border);border-radius:var(--radius);box-shadow:var(--shadow);overflow:hidden;animation:fadeUp .5s .1s ease both;}
.card-header{background:var(--sage-pale);border-bottom:1.5px solid var(--sage-light);padding:1.25rem 2rem;display:flex;align-items:center;gap:.75rem;}
.card-header h2{font-family:'Playfair Display',serif;font-size:1.1rem;color:var(--sage-dark);}
.card-body{padding:2rem;}

.form-grid{display:grid;grid-template-columns:1fr 1fr;gap:1.25rem;}
.form-full{grid-column:1/-1;}

.form-group label{display:block;font-size:.82rem;font-weight:500;color:var(--ink-soft);margin-bottom:.4rem;text-transform:uppercase;letter-spacing:.05em;}
.form-group label .req{color:var(--danger);margin-left:.2rem;}
.form-control{width:100%;border:1.5px solid var(--border);border-radius:var(--radius-sm);padding:.72rem 1rem;font-family:'DM Sans',sans-serif;font-size:.9rem;color:var(--ink);background:var(--cream);outline:none;transition:border-color .2s,box-shadow .2s;}
.form-control:focus{border-color:var(--sage);box-shadow:0 0 0 3px rgba(107,143,113,.15);background:#fff;}
.form-control.is-error{border-color:var(--danger);}
textarea.form-control{resize:vertical;min-height:80px;}
.error-msg{font-size:.78rem;color:var(--danger);margin-top:.3rem;}

.radio-group{display:flex;gap:.75rem;flex-wrap:wrap;margin-top:.2rem;}
.radio-option{display:flex;align-items:center;gap:.4rem;padding:.5rem 1rem;border:1.5px solid var(--border);border-radius:20px;cursor:pointer;font-size:.88rem;transition:all .15s;}
.radio-option:has(input:checked){border-color:var(--sage);background:var(--sage-pale);color:var(--sage-dark);font-weight:500;}
.radio-option input{accent-color:var(--sage);}

.section-divider{grid-column:1/-1;border:none;border-top:1px dashed var(--border);margin:.5rem 0;}

.form-footer{display:flex;justify-content:flex-end;gap:.75rem;align-items:center;padding:1.5rem 2rem;border-top:1px solid var(--border);background:var(--sage-pale);}
.btn{display:inline-flex;align-items:center;gap:.4rem;padding:.7rem 1.5rem;border-radius:var(--radius-sm);border:none;cursor:pointer;font-family:'DM Sans',sans-serif;font-size:.9rem;font-weight:500;text-decoration:none;transition:transform .15s,box-shadow .15s,background .2s;}
.btn:active{transform:scale(.97);}
.btn-primary{background:var(--sage);color:#fff;box-shadow:0 2px 12px rgba(107,143,113,.35);}
.btn-primary:hover{background:var(--sage-dark);}
.btn-outline{background:transparent;color:var(--ink-soft);border:1.5px solid var(--border);}
.btn-outline:hover{background:var(--cream);}
.btn-danger{background:var(--danger);color:#fff;}
.btn-danger:hover{background:#a93226;}

footer{text-align:center;padding:2rem;color:var(--ink-muted);font-size:.8rem;border-top:1px solid var(--border);margin-top:3rem;}

@keyframes fadeUp{from{opacity:0;transform:translateY(18px);}to{opacity:1;transform:translateY(0);}}
@media(max-width:640px){.form-grid{grid-template-columns:1fr;}.header{padding:0 1rem;}}
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

  <div class="page-heading">
    <div>
      <h1>✏️ Modify Resident Details</h1>
      <div class="breadcrumb"><a href="index.php">Home</a> / Edit Resident</div>
    </div>
  </div>

  <!-- Identity strip -->
  <div class="identity-strip">
    <div class="id-avatar"><?= $form['gender']==='Female' ? '👩' : ($form['gender']==='Male'?'👨':'🧑') ?></div>
    <div>
      <div class="id-name"><?= htmlspecialchars($form['full_name']) ?></div>
      <div class="id-meta">NIC: <?= htmlspecialchars($form['nic']) ?> &nbsp;·&nbsp; ID #<?= $id ?></div>
    </div>
    <div class="id-badge">Editing Record</div>
  </div>

  <?php if ($success): ?>
    <div class="alert alert-success">✅ <strong>Record updated successfully!</strong> <a href="index.php" style="margin-left:.5rem;color:var(--sage-dark);text-decoration:underline;">Back to search</a></div>
  <?php endif; ?>

  <?php if (!empty($errors['db'])): ?>
    <div class="alert alert-error">⚠️ <?= htmlspecialchars($errors['db']) ?></div>
  <?php endif; ?>

  <div class="card">
    <div class="card-header">
      <span>📋</span>
      <h2>Update Resident Information</h2>
    </div>
    <form method="POST" novalidate>
      <div class="card-body">
        <div class="form-grid">

          <div class="form-group">
            <label>Full Name <span class="req">*</span></label>
            <input type="text" name="full_name" class="form-control <?= isset($errors['full_name'])?'is-error':'' ?>"
                   value="<?= htmlspecialchars($form['full_name']) ?>" maxlength="100">
            <?php if(isset($errors['full_name'])): ?><div class="error-msg">⚠ <?= $errors['full_name'] ?></div><?php endif; ?>
          </div>

          <div class="form-group">
            <label>Date of Birth <span class="req">*</span></label>
            <input type="date" name="dob" class="form-control <?= isset($errors['dob'])?'is-error':'' ?>"
                   value="<?= htmlspecialchars($form['dob']) ?>" max="<?= date('Y-m-d', strtotime('-1 day')) ?>">
            <?php if(isset($errors['dob'])): ?><div class="error-msg">⚠ <?= $errors['dob'] ?></div><?php endif; ?>
          </div>

          <div class="form-group">
            <label>NIC Number <span class="req">*</span></label>
            <input type="text" name="nic" class="form-control <?= isset($errors['nic'])?'is-error':'' ?>"
                   value="<?= htmlspecialchars($form['nic']) ?>" maxlength="12">
            <?php if(isset($errors['nic'])): ?><div class="error-msg">⚠ <?= $errors['nic'] ?></div><?php endif; ?>
          </div>

          <div class="form-group">
            <label>Phone Number <span class="req">*</span></label>
            <input type="tel" name="phone" class="form-control <?= isset($errors['phone'])?'is-error':'' ?>"
                   value="<?= htmlspecialchars($form['phone']) ?>" maxlength="15">
            <?php if(isset($errors['phone'])): ?><div class="error-msg">⚠ <?= $errors['phone'] ?></div><?php endif; ?>
          </div>

          <div class="form-group">
            <label>Email Address <span class="req">*</span></label>
            <input type="email" name="email" class="form-control <?= isset($errors['email'])?'is-error':'' ?>"
                   value="<?= htmlspecialchars($form['email']) ?>" maxlength="100">
            <?php if(isset($errors['email'])): ?><div class="error-msg">⚠ <?= $errors['email'] ?></div><?php endif; ?>
          </div>

          <div class="form-group">
            <label>Occupation <em style="font-style:normal;opacity:.6;">(optional)</em></label>
            <input type="text" name="occupation" class="form-control"
                   value="<?= htmlspecialchars($form['occupation']) ?>" maxlength="50">
          </div>

          <div class="form-group form-full">
            <label>Address <span class="req">*</span></label>
            <textarea name="address" class="form-control <?= isset($errors['address'])?'is-error':'' ?>"><?= htmlspecialchars($form['address']) ?></textarea>
            <?php if(isset($errors['address'])): ?><div class="error-msg">⚠ <?= $errors['address'] ?></div><?php endif; ?>
          </div>

          <hr class="section-divider">

          <div class="form-group form-full">
            <label>Gender <span class="req">*</span></label>
            <div class="radio-group">
              <?php foreach(['Male','Female','Other'] as $g): ?>
              <label class="radio-option">
                <input type="radio" name="gender" value="<?= $g ?>" <?= $form['gender']===$g?'checked':'' ?>>
                <?= $g==='Male'?'👨 Male':($g==='Female'?'👩 Female':'🧑 Other') ?>
              </label>
              <?php endforeach; ?>
            </div>
            <?php if(isset($errors['gender'])): ?><div class="error-msg" style="margin-top:.4rem;">⚠ <?= $errors['gender'] ?></div><?php endif; ?>
          </div>

        </div>
      </div>
      <div class="form-footer">
        <a href="delete.php?id=<?= $id ?>&name=<?= urlencode($form['full_name']) ?>" class="btn btn-danger" style="margin-right:auto;">🗑️ Delete Record</a>
        <a href="index.php" class="btn btn-outline">Cancel</a>
        <button type="submit" class="btn btn-primary">💾 Save Changes</button>
      </div>
    </form>
  </div>

</main>
<footer>© <?= date('Y') ?> Grama Niladhari Office · Resident Registry System</footer>
</body>
</html>
