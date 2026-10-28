<?php
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../logger.php';
require_once __DIR__ . '/admin_partials.php';
require_login();   // no role check — broken access control (LAB)

$id  = $_GET['id'] ?? 0;
$msg = '';

/* ---------------------------------------------------------------------------
 *  INTENTIONALLY VULNERABLE (LAB): SQLi in UPDATE + privilege escalation.
 *  Every field (including 'role') is attacker-controllable and written back
 *  with raw string concatenation. Setting role=admin on any account is a
 *  one-click privilege escalation; the raw fields are also SQL-injectable.
 * ------------------------------------------------------------------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full  = $_POST['full_name'];
    $uname = $_POST['username'];
    $pw    = $_POST['password'];
    $dept  = $_POST['department'];
    $pos   = $_POST['position'];
    $role  = $_POST['role'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $pid   = $_POST['id'];

    // capture prior role to detect privilege escalation
    $prev = mysqli_fetch_assoc(mysqli_query($conn, "SELECT role FROM users WHERE id = $pid"));
    $old_role = $prev['role'] ?? null;

    $sql = "UPDATE users SET
              full_name='$full', username='$uname', password='$pw',
              department='$dept', position='$pos', role='$role',
              email='$email', phone='$phone'
            WHERE id=$pid";
    $ok = mysqli_query($conn, $sql);
    if ($ok) {
        $msg = 'Account updated.';
    } else {
        $msg = 'SQL error: ' . mysqli_error($conn);
    }

    $privesc = ($old_role !== null && $role !== $old_role && $role === 'admin');
    $unauthorized = (($_SESSION['role'] ?? '') !== 'admin');
    sec_log([
        'event_category'          => $ok ? 'admin' : 'error',
        'event_action'            => $privesc ? 'privilege_change' : 'account_update',
        'component'               => 'admin/edit_user',
        'severity'                => ($privesc || !$ok || $unauthorized) ? 'high' : 'warning',
        'outcome'                 => $ok ? 'success' : 'error',
        'target_user_id'          => (string)$pid,
        'old_role'                => $old_role,
        'new_role'                => $role,
        'privesc_suspected'       => $privesc,     // account raised to admin
        'broken_access_suspected' => $unauthorized,
        'sqli_suspected'          => looks_like_sqli((string)$role) || looks_like_sqli((string)$pid),
        'message'                 => $ok ? null : mysqli_error($conn),
    ]);
    $id = $pid;
}

$u = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM users WHERE id = $id LIMIT 1"));

render_admin_header('users');

if (!$u) {
    echo '<div class="panel"><div class="body muted">No such staff account.</div></div>';
    render_admin_footer(); exit;
}
?>
<div class="page-title"><h2>Edit Staff Account</h2><span class="crumb">ID <?= htmlspecialchars($u['id']) ?></span></div>

<?php if ($msg): ?><div class="panel"><div class="body"><strong><?= htmlspecialchars($msg) ?></strong></div></div><?php endif; ?>

<div class="panel">
  <h3><?= htmlspecialchars($u['full_name']) ?></h3>
  <div class="body">
    <form method="post">
      <input type="hidden" name="id" value="<?= htmlspecialchars($u['id']) ?>">
      <div class="kv" style="grid-template-columns:160px 1fr;row-gap:14px">
        <div class="key">Full Name</div><div><input name="full_name" value="<?= htmlspecialchars($u['full_name']) ?>" style="width:100%;padding:9px;border:1.5px solid var(--line);border-radius:8px"></div>
        <div class="key">Username</div><div><input name="username" value="<?= htmlspecialchars($u['username']) ?>" style="width:100%;padding:9px;border:1.5px solid var(--line);border-radius:8px"></div>
        <div class="key">Password</div><div><input name="password" value="<?= htmlspecialchars($u['password']) ?>" style="width:100%;padding:9px;border:1.5px solid var(--line);border-radius:8px"></div>
        <div class="key">Department</div><div><input name="department" value="<?= htmlspecialchars($u['department']) ?>" style="width:100%;padding:9px;border:1.5px solid var(--line);border-radius:8px"></div>
        <div class="key">Position</div><div><input name="position" value="<?= htmlspecialchars($u['position']) ?>" style="width:100%;padding:9px;border:1.5px solid var(--line);border-radius:8px"></div>
        <div class="key">Role</div><div>
          <select name="role" style="width:100%;padding:9px;border:1.5px solid var(--line);border-radius:8px">
            <?php foreach (['employee','manager','admin'] as $r): ?>
              <option value="<?= $r ?>" <?= $u['role']===$r?'selected':'' ?>><?= $r ?></option>
            <?php endforeach; ?>
          </select></div>
        <div class="key">Email</div><div><input name="email" value="<?= htmlspecialchars($u['email']) ?>" style="width:100%;padding:9px;border:1.5px solid var(--line);border-radius:8px"></div>
        <div class="key">Phone</div><div><input name="phone" value="<?= htmlspecialchars($u['phone']) ?>" style="width:100%;padding:9px;border:1.5px solid var(--line);border-radius:8px"></div>
      </div>
      <div style="margin-top:16px"><button class="btn-sm" type="submit">Save Changes</button>
        <a class="btn-sm" href="users.php" style="background:#64748b">Back</a></div>
    </form>
  </div>
</div>
<?php render_admin_footer(); ?>
