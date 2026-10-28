<?php
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../logger.php';
require_once __DIR__ . '/admin_partials.php';
require_login();   // no role check — broken access control (LAB)

$msg = '';
$unauthorized = (($_SESSION['role'] ?? '') !== 'admin');

// Delete (injectable id, no CSRF, no role check) — LAB
if (isset($_GET['delete'])) {
    $did = $_GET['delete'];
    $ok  = mysqli_query($conn, "DELETE FROM users WHERE id = $did");
    $msg = "Deleted staff id $did.";
    sec_log([
        'event_category'          => 'admin',
        'event_action'            => 'account_delete',
        'component'               => 'admin/users',
        'severity'                => 'high',
        'outcome'                 => $ok ? 'success' : 'error',
        'target_user_id'          => (string)$did,
        'broken_access_suspected' => $unauthorized,
        'sqli_suspected'          => looks_like_sqli((string)$did),
        'message'                 => $ok ? null : mysqli_error($conn),
    ]);
}

/* Full dump including PLAINTEXT passwords — exactly what an attacker would
 * want to exfiltrate. Present on purpose for the lab. */
$rows = mysqli_query($conn, "SELECT * FROM users ORDER BY id");
sec_log([
    'event_category'          => 'admin',
    'event_action'            => 'staff_dump',      // viewing all accounts incl. plaintext creds
    'component'               => 'admin/users',
    'severity'                => 'high',
    'outcome'                 => 'success',
    'record_count'            => $rows ? mysqli_num_rows($rows) : 0,
    'broken_access_suspected' => $unauthorized,
]);

render_admin_header('users');
?>
<div class="page-title"><h2>Manage Staff Accounts</h2><span class="crumb"><?= mysqli_num_rows($rows) ?> accounts</span></div>

<?php if ($msg): ?><div class="panel"><div class="body"><strong><?= htmlspecialchars($msg) ?></strong></div></div><?php endif; ?>

<div class="panel">
  <h3>All Staff (credentials visible — lab)</h3>
  <div class="body" style="padding:0;overflow-x:auto">
    <table>
      <tr><th>ID</th><th>Username</th><th>Password</th><th>Full Name</th><th>Dept</th>
          <th>Role</th><th>NIC</th><th>Bank A/C</th><th></th></tr>
      <?php while ($u = mysqli_fetch_assoc($rows)): ?>
        <tr>
          <td><?= htmlspecialchars($u['id']) ?></td>
          <td><?= htmlspecialchars($u['username']) ?></td>
          <td><code class="inl"><?= htmlspecialchars($u['password']) ?></code></td>
          <td><?= htmlspecialchars($u['full_name']) ?></td>
          <td><?= htmlspecialchars($u['department']) ?></td>
          <td><span class="badge <?= htmlspecialchars($u['role']) ?>"><?= htmlspecialchars($u['role']) ?></span></td>
          <td class="muted"><?= htmlspecialchars($u['nic']) ?></td>
          <td class="muted"><?= htmlspecialchars($u['bank_account']) ?></td>
          <td class="right">
            <a class="btn-sm" href="edit_user.php?id=<?= $u['id'] ?>">Edit</a>
            <a class="btn-sm danger" href="users.php?delete=<?= $u['id'] ?>"
               onclick="return confirm('Delete this staff account?')">Del</a>
          </td>
        </tr>
      <?php endwhile; ?>
    </table>
  </div>
</div>
<?php render_admin_footer(); ?>
