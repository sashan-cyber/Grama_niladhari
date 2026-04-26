<?php
require_once 'config.php';

$results    = [];
$searchTerm = '';
$searchType = 'full_name';
$searched   = false;

// ── Handle search ────────────────────────────────────────────
if (isset($_GET['search']) && trim($_GET['search']) !== '') {
    $searched   = true;
    $searchTerm = trim($_GET['search']);
    $searchType = $_GET['search_type'] ?? 'full_name';

    $allowed = ['full_name', 'address', 'nic'];
    if (!in_array($searchType, $allowed)) $searchType = 'full_name';

    if ($searchType === 'nic') {
        $stmt = $conn->prepare("SELECT * FROM residents WHERE nic = ? ORDER BY registered_date DESC");
        $stmt->bind_param('s', $searchTerm);
    } else {
        $like = '%' . $searchTerm . '%';
        $stmt = $conn->prepare("SELECT * FROM residents WHERE $searchType LIKE ? ORDER BY registered_date DESC");
        $stmt->bind_param('s', $like);
    }

    $stmt->execute();
    $results = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

// ── Handle delete (from this page) ──────────────────────────
$deleteMsg = '';
if (isset($_GET['deleted']) && $_GET['deleted'] === '1') {
    $deleteMsg = 'success';
}

// ── Stats ────────────────────────────────────────────────────
$totalRes   = $conn->query("SELECT COUNT(*) AS c FROM residents")->fetch_assoc()['c'];
$maleCount  = $conn->query("SELECT COUNT(*) AS c FROM residents WHERE gender='Male'")->fetch_assoc()['c'];
$femaleCount= $conn->query("SELECT COUNT(*) AS c FROM residents WHERE gender='Female'")->fetch_assoc()['c'];
$todayCount = $conn->query("SELECT COUNT(*) AS c FROM residents WHERE DATE(registered_date)=CURDATE()")->fetch_assoc()['c'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Grama Niladhari – Resident Registry</title>
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
  --warm-white: #f5f2ea;
  --ink:        #2c2c2c;
  --ink-soft:   #5a5a5a;
  --ink-muted:  #8a8a8a;
  --gold:       #c9a84c;
  --gold-light: #f0dea0;
  --danger:     #c0392b;
  --danger-pale:#fdf0ef;
  --border:     #ddd8cc;
  --shadow:     0 2px 20px rgba(107,143,113,.12);
  --shadow-lg:  0 8px 40px rgba(107,143,113,.18);
  --radius:     14px;
  --radius-sm:  8px;
}

*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

body {
  font-family: 'DM Sans', sans-serif;
  background: var(--cream);
  color: var(--ink);
  min-height: 100vh;
}

/* ── Header ── */
.header {
  background: linear-gradient(135deg, var(--sage-dark) 0%, var(--sage) 60%, var(--sage-light) 100%);
  padding: 0 2.5rem;
  display: flex;
  align-items: center;
  justify-content: space-between;
  box-shadow: 0 2px 24px rgba(74,107,80,.35);
  position: sticky; top: 0; z-index: 100;
  min-height: 72px;
}

.header-brand {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.header-emblem {
  width: 46px; height: 46px;
  background: rgba(255,255,255,.18);
  border: 2px solid rgba(255,255,255,.4);
  border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
  font-size: 1.4rem;
}

.header-title {
  font-family: 'Playfair Display', serif;
  color: #fff;
  font-size: 1.25rem;
  font-weight: 700;
  letter-spacing: .02em;
  line-height: 1.2;
}
.header-title span { font-size: .8rem; font-weight: 300; opacity: .85; display: block; font-family: 'DM Sans', sans-serif; }

.header-nav a {
  color: rgba(255,255,255,.9);
  text-decoration: none;
  font-size: .875rem;
  font-weight: 500;
  padding: .5rem 1.1rem;
  border-radius: 20px;
  transition: background .2s;
  border: 1.5px solid transparent;
}
.header-nav a:hover, .header-nav a.active {
  background: rgba(255,255,255,.18);
  border-color: rgba(255,255,255,.3);
}
.header-nav a.btn-add {
  background: var(--gold);
  color: var(--ink);
  border-color: var(--gold);
  font-weight: 600;
}
.header-nav a.btn-add:hover { background: #b8952e; }

/* ── Layout ── */
.container { max-width: 1160px; margin: 0 auto; padding: 2.5rem 1.5rem; }

/* ── Stats row ── */
.stats-row {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 1rem;
  margin-bottom: 2rem;
}
.stat-card {
  background: #fff;
  border: 1px solid var(--border);
  border-radius: var(--radius);
  padding: 1.25rem 1.5rem;
  display: flex; align-items: center; gap: 1rem;
  box-shadow: var(--shadow);
  animation: fadeUp .5s ease both;
}
.stat-card:nth-child(2) { animation-delay: .07s; }
.stat-card:nth-child(3) { animation-delay: .14s; }
.stat-card:nth-child(4) { animation-delay: .21s; }

.stat-icon {
  width: 46px; height: 46px;
  border-radius: 12px;
  display: flex; align-items: center; justify-content: center;
  font-size: 1.3rem;
  flex-shrink: 0;
}
.stat-icon.green  { background: var(--sage-pale); }
.stat-icon.blue   { background: #eaf0fb; }
.stat-icon.pink   { background: #fdeef4; }
.stat-icon.gold   { background: #fdf6e3; }

.stat-num {
  font-family: 'Playfair Display', serif;
  font-size: 1.8rem;
  font-weight: 700;
  color: var(--ink);
  line-height: 1;
}
.stat-label { font-size: .78rem; color: var(--ink-muted); margin-top: .2rem; }

/* ── Search card ── */
.search-card {
  background: #fff;
  border: 1px solid var(--border);
  border-radius: var(--radius);
  padding: 2rem;
  box-shadow: var(--shadow);
  margin-bottom: 2rem;
  animation: fadeUp .5s .25s ease both;
}

.search-card h2 {
  font-family: 'Playfair Display', serif;
  font-size: 1.2rem;
  color: var(--sage-dark);
  margin-bottom: 1.25rem;
  display: flex; align-items: center; gap: .5rem;
}

.search-row {
  display: flex;
  gap: .75rem;
  flex-wrap: wrap;
}

.search-select, .search-input {
  border: 1.5px solid var(--border);
  border-radius: var(--radius-sm);
  padding: .7rem 1rem;
  font-family: 'DM Sans', sans-serif;
  font-size: .9rem;
  color: var(--ink);
  background: var(--cream);
  outline: none;
  transition: border-color .2s, box-shadow .2s;
}
.search-select:focus, .search-input:focus {
  border-color: var(--sage);
  box-shadow: 0 0 0 3px rgba(107,143,113,.15);
}
.search-select { min-width: 180px; cursor: pointer; }
.search-input  { flex: 1; min-width: 220px; }

.btn {
  display: inline-flex; align-items: center; gap: .4rem;
  padding: .7rem 1.5rem;
  border-radius: var(--radius-sm);
  border: none; cursor: pointer;
  font-family: 'DM Sans', sans-serif;
  font-size: .9rem; font-weight: 500;
  text-decoration: none;
  transition: transform .15s, box-shadow .15s, background .2s;
}
.btn:active { transform: scale(.97); }

.btn-primary {
  background: var(--sage);
  color: #fff;
  box-shadow: 0 2px 12px rgba(107,143,113,.35);
}
.btn-primary:hover { background: var(--sage-dark); box-shadow: 0 4px 18px rgba(107,143,113,.45); }

.btn-gold {
  background: var(--gold);
  color: var(--ink);
  font-weight: 600;
}
.btn-gold:hover { background: #b8952e; }

.btn-danger {
  background: var(--danger);
  color: #fff;
  font-size: .82rem;
  padding: .45rem 1rem;
}
.btn-danger:hover { background: #a93226; }

.btn-edit {
  background: var(--sage-pale);
  color: var(--sage-dark);
  border: 1.5px solid var(--sage-light);
  font-size: .82rem;
  padding: .45rem 1rem;
}
.btn-edit:hover { background: var(--sage-light); }

/* ── Toast ── */
.toast {
  padding: .9rem 1.4rem;
  border-radius: var(--radius-sm);
  margin-bottom: 1.5rem;
  display: flex; align-items: center; gap: .6rem;
  font-size: .9rem; font-weight: 500;
  animation: fadeUp .4s ease;
}
.toast.success { background: var(--sage-pale); color: var(--sage-dark); border: 1.5px solid var(--sage-light); }

/* ── Results ── */
.results-header {
  display: flex; align-items: center; justify-content: space-between;
  margin-bottom: 1rem;
}
.results-title {
  font-family: 'Playfair Display', serif;
  font-size: 1.1rem;
  color: var(--ink);
}
.badge {
  background: var(--sage-pale);
  color: var(--sage-dark);
  border: 1px solid var(--sage-light);
  border-radius: 20px;
  padding: .2rem .8rem;
  font-size: .78rem;
  font-weight: 600;
}

.table-wrap {
  background: #fff;
  border: 1px solid var(--border);
  border-radius: var(--radius);
  overflow: hidden;
  box-shadow: var(--shadow);
  animation: fadeUp .4s .1s ease both;
}

table {
  width: 100%;
  border-collapse: collapse;
}

thead th {
  background: var(--sage-pale);
  padding: .85rem 1rem;
  text-align: left;
  font-size: .78rem;
  text-transform: uppercase;
  letter-spacing: .08em;
  color: var(--sage-dark);
  font-weight: 600;
  white-space: nowrap;
  border-bottom: 1.5px solid var(--sage-light);
}

tbody tr {
  border-bottom: 1px solid var(--border);
  transition: background .15s;
}
tbody tr:last-child { border-bottom: none; }
tbody tr:hover { background: #fafdf9; }

tbody td {
  padding: .85rem 1rem;
  font-size: .875rem;
  color: var(--ink-soft);
  vertical-align: middle;
}

.td-name { color: var(--ink); font-weight: 500; white-space: nowrap; }
.td-nic  { font-family: monospace; font-size: .82rem; color: var(--sage-dark); }

.gender-badge {
  display: inline-block;
  padding: .2rem .7rem;
  border-radius: 20px;
  font-size: .75rem;
  font-weight: 600;
}
.gender-male   { background: #eaf0fb; color: #2a5faa; }
.gender-female { background: #fdeef4; color: #a03070; }
.gender-other  { background: #f0f0f0; color: #555; }

.actions-cell { display: flex; gap: .4rem; }

/* ── Empty state ── */
.empty-state {
  text-align: center;
  padding: 4rem 2rem;
  color: var(--ink-muted);
}
.empty-state .icon { font-size: 3rem; margin-bottom: 1rem; opacity: .4; }
.empty-state h3 { font-family: 'Playfair Display', serif; font-size: 1.3rem; color: var(--ink-soft); margin-bottom: .5rem; }
.empty-state p { font-size: .9rem; }

/* ── No search yet ── */
.welcome-state {
  background: #fff;
  border: 1px solid var(--border);
  border-radius: var(--radius);
  padding: 3rem 2rem;
  text-align: center;
  box-shadow: var(--shadow);
  animation: fadeUp .5s .3s ease both;
}
.welcome-state .icon { font-size: 2.5rem; margin-bottom: 1rem; }
.welcome-state h3 { font-family: 'Playfair Display', serif; font-size: 1.3rem; color: var(--sage-dark); margin-bottom: .5rem; }
.welcome-state p { color: var(--ink-muted); font-size: .9rem; }

/* ── Footer ── */
footer {
  text-align: center;
  padding: 2rem;
  color: var(--ink-muted);
  font-size: .8rem;
  border-top: 1px solid var(--border);
  margin-top: 3rem;
}

/* ── Animations ── */
@keyframes fadeUp {
  from { opacity: 0; transform: translateY(18px); }
  to   { opacity: 1; transform: translateY(0); }
}

@media (max-width: 768px) {
  .stats-row { grid-template-columns: repeat(2, 1fr); }
  .header { padding: 0 1rem; }
  .header-nav a span { display: none; }
  table { font-size: .8rem; }
  thead th, tbody td { padding: .65rem .6rem; }
}
</style>
</head>
<body>

<!-- ── Header ── -->
<header class="header">
  <div class="header-brand">
    <div class="header-emblem">🏛️</div>
    <div class="header-title">
      Grama Niladhari Office
      <span>Resident Registry System</span>
    </div>
  </div>
  <nav class="header-nav" style="display:flex;gap:.5rem;align-items:center;">
    <a href="index.php" class="active">🔍 <span>Search</span></a>
    <a href="add.php" class="btn-add">+ Add Resident</a>
  </nav>
</header>

<!-- ── Main ── -->
<main class="container">

  <?php if ($deleteMsg === 'success'): ?>
    <div class="toast success">✅ Resident record deleted successfully.</div>
  <?php endif; ?>

  <!-- Stats -->
  <div class="stats-row">
    <div class="stat-card">
      <div class="stat-icon green">👥</div>
      <div>
        <div class="stat-num"><?= $totalRes ?></div>
        <div class="stat-label">Total Residents</div>
      </div>
    </div>
    <div class="stat-card">
      <div class="stat-icon blue">👨</div>
      <div>
        <div class="stat-num"><?= $maleCount ?></div>
        <div class="stat-label">Male</div>
      </div>
    </div>
    <div class="stat-card">
      <div class="stat-icon pink">👩</div>
      <div>
        <div class="stat-num"><?= $femaleCount ?></div>
        <div class="stat-label">Female</div>
      </div>
    </div>
    <div class="stat-card">
      <div class="stat-icon gold">📅</div>
      <div>
        <div class="stat-num"><?= $todayCount ?></div>
        <div class="stat-label">Registered Today</div>
      </div>
    </div>
  </div>

  <!-- Search -->
  <div class="search-card">
    <h2>🔍 Search Residents</h2>
    <form method="GET" action="index.php">
      <div class="search-row">
        <select name="search_type" class="search-select">
          <option value="full_name" <?= ($searchType==='full_name'?'selected':'') ?>>Full Name</option>
          <option value="address"   <?= ($searchType==='address'  ?'selected':'') ?>>Address</option>
          <option value="nic"       <?= ($searchType==='nic'      ?'selected':'') ?>>NIC Number</option>
        </select>
        <input type="text" name="search" class="search-input"
               placeholder="Type to search residents…"
               value="<?= htmlspecialchars($searchTerm) ?>">
        <button type="submit" class="btn btn-primary">Search</button>
        <?php if ($searched): ?>
          <a href="index.php" class="btn btn-edit">Clear</a>
        <?php endif; ?>
      </div>
    </form>
  </div>

  <!-- Results -->
  <?php if ($searched): ?>
    <div class="results-header">
      <div class="results-title">Search Results</div>
      <span class="badge"><?= count($results) ?> record(s) found</span>
    </div>

    <?php if (count($results) > 0): ?>
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>#</th>
            <th>Full Name</th>
            <th>NIC</th>
            <th>DOB</th>
            <th>Gender</th>
            <th>Phone</th>
            <th>Occupation</th>
            <th>Address</th>
            <th>Registered</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($results as $i => $r): ?>
          <tr>
            <td><?= $i + 1 ?></td>
            <td class="td-name"><?= htmlspecialchars($r['full_name']) ?></td>
            <td class="td-nic"><?= htmlspecialchars($r['nic']) ?></td>
            <td><?= date('d M Y', strtotime($r['dob'])) ?></td>
            <td>
              <span class="gender-badge gender-<?= strtolower($r['gender']) ?>">
                <?= $r['gender'] ?>
              </span>
            </td>
            <td><?= htmlspecialchars($r['phone']) ?></td>
            <td><?= htmlspecialchars($r['occupation'] ?? '—') ?></td>
            <td style="max-width:180px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"
                title="<?= htmlspecialchars($r['address']) ?>">
              <?= htmlspecialchars($r['address']) ?>
            </td>
            <td><?= date('d M Y', strtotime($r['registered_date'])) ?></td>
            <td>
              <div class="actions-cell">
                <a href="edit.php?id=<?= $r['id'] ?>" class="btn btn-edit">✏️ Edit</a>
                <a href="delete.php?id=<?= $r['id'] ?>&name=<?= urlencode($r['full_name']) ?>"
                   class="btn btn-danger">🗑️ Delete</a>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php else: ?>
    <div class="table-wrap">
      <div class="empty-state">
        <div class="icon">🔍</div>
        <h3>No residents found</h3>
        <p>Try a different search term or search type.</p>
      </div>
    </div>
    <?php endif; ?>

  <?php else: ?>
  <div class="welcome-state">
    <div class="icon">🏘️</div>
    <h3>Welcome to the Resident Registry</h3>
    <p>Use the search bar above to find residents by name, address, or NIC number.</p>
  </div>
  <?php endif; ?>

</main>

<footer>
  © <?= date('Y') ?> Grama Niladhari Office &nbsp;·&nbsp; Resident Registry System &nbsp;·&nbsp; All rights reserved.
</footer>
</body>
</html>
