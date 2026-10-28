<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/partials.php';
require_login();

/* ---------------------------------------------------------------------------
 *  INTENTIONALLY VULNERABLE (LAB): IDOR + SQLi
 *   - 'id' is taken straight from the query string and concatenated into SQL.
 *   - No check that the requested id belongs to the logged-in user, so any
 *     authenticated user can read ANY employee's full record (IDOR):
 *         profile.php?id=1        (the admin)
 *         profile.php?id=5
 *   - Also injectable:
 *         profile.php?id=0 UNION SELECT ... -- ␣
 * ------------------------------------------------------------------------- */
$id = $_GET['id'] ?? $_SESSION['user_id'];

$sql = "SELECT * FROM users WHERE id = $id LIMIT 1";
$res = mysqli_query($conn, $sql);

render_header('profile');

if ($res === false) {
    echo '<div class="panel"><div class="body"><strong style="color:#b02a37">SQL error:</strong> '
       . '<code class="inl">' . htmlspecialchars(mysqli_error($conn)) . '</code></div></div>';
    render_footer(); exit;
}
$u = mysqli_fetch_assoc($res);
if (!$u) {
    echo '<div class="panel"><div class="body muted">No such employee.</div></div>';
    render_footer(); exit;
}
$init = strtoupper(substr($u['full_name'],0,1));
?>
<div class="page-title"><h2>Employee Profile</h2>
  <span class="crumb">Record #<?= htmlspecialchars($u['id']) ?></span></div>

<div class="panel">
  <div class="body">
    <div class="profile-head">
      <div class="big-av"><?= $init ?></div>
      <div>
        <div style="font-size:20px;font-weight:800;color:var(--navy)"><?= htmlspecialchars($u['full_name']) ?></div>
        <div class="muted"><?= htmlspecialchars($u['position']) ?> · <?= htmlspecialchars($u['department']) ?></div>
        <div style="margin-top:6px"><span class="badge <?= htmlspecialchars($u['role']) ?>"><?= htmlspecialchars($u['role']) ?></span>
          <span class="badge active"><?= htmlspecialchars($u['status']) ?></span></div>
      </div>
    </div>
  </div>
</div>

<div class="panel">
  <h3>Personal & HR Details</h3>
  <div class="body">
    <div class="kv">
      <div class="key">Employee ID</div><div>SB-<?= str_pad($u['id'],4,'0',STR_PAD_LEFT) ?></div>
      <div class="key">Username</div><div><?= htmlspecialchars($u['username']) ?></div>
      <div class="key">Date of Birth</div><div><?= htmlspecialchars($u['dob']) ?></div>
      <div class="key">Gender</div><div><?= htmlspecialchars($u['gender']) ?></div>
      <div class="key">NIC</div><div><?= htmlspecialchars($u['nic']) ?></div>
      <div class="key">Email</div><div><?= htmlspecialchars($u['email']) ?></div>
      <div class="key">Phone</div><div><?= htmlspecialchars($u['phone']) ?></div>
      <div class="key">Address</div><div><?= htmlspecialchars($u['address']) ?></div>
      <div class="key">Bank Account</div><div><?= htmlspecialchars($u['bank_account']) ?></div>
      <div class="key">Date Joined</div><div><?= htmlspecialchars($u['date_joined']) ?></div>
      <div class="key">Emergency Contact</div><div><?= htmlspecialchars($u['emergency_contact']) ?></div>
    </div>
    <p style="margin-top:16px">
      <a href="payroll.php?user_id=<?= $u['id'] ?>">View payroll →</a> &nbsp;·&nbsp;
      <a href="activities.php?user_id=<?= $u['id'] ?>">View activity log →</a>
    </p>
  </div>
</div>
<?php render_footer(); ?>
