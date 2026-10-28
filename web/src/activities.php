<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/partials.php';
require_once __DIR__ . '/logger.php';
require_login();

/* INTENTIONALLY VULNERABLE (LAB): IDOR + injectable user_id */
$uid = $_GET['user_id'] ?? $_SESSION['user_id'];

$owner = mysqli_fetch_assoc(mysqli_query($conn, "SELECT full_name FROM users WHERE id = $uid"));
$acts  = mysqli_query($conn, "SELECT * FROM activities WHERE user_id = $uid ORDER BY created_at DESC");
$leaves= mysqli_query($conn, "SELECT * FROM leave_requests WHERE user_id = $uid ORDER BY from_date DESC");

$idor = isset($_GET['user_id']) && (string)$_GET['user_id'] !== (string)($_SESSION['user_id'] ?? '');
sec_log([
    'event_category' => $acts === false ? 'error' : 'data_access',
    'event_action'   => $acts === false ? 'sql_error' : 'view_activity',
    'component'      => 'activities',
    'severity'       => ($acts === false || $idor || looks_like_sqli((string)$uid)) ? 'high' : 'info',
    'outcome'        => $acts === false ? 'error' : 'success',
    'target_user_id' => (string)$uid,
    'idor_suspected' => $idor,
    'sqli_suspected' => looks_like_sqli((string)$uid),
    'message'        => $acts === false ? mysqli_error($conn) : null,
]);

render_header('activities');

if ($acts === false) {
    echo '<div class="panel"><div class="body"><strong style="color:#b02a37">SQL error:</strong> '
       . '<code class="inl">' . htmlspecialchars(mysqli_error($conn)) . '</code></div></div>';
    render_footer(); exit;
}
?>
<div class="page-title"><h2>Activity & Leave</h2>
  <span class="crumb"><?= htmlspecialchars($owner['full_name'] ?? 'Unknown') ?></span></div>

<div class="panel">
  <h3>Activity Log</h3>
  <div class="body" style="padding:0">
    <table>
      <tr><th>Activity</th><th>IP Address</th><th>Timestamp</th></tr>
      <?php while ($a = mysqli_fetch_assoc($acts)): ?>
        <tr><td><?= htmlspecialchars($a['activity']) ?></td>
            <td class="muted"><?= htmlspecialchars($a['ip_address']) ?></td>
            <td class="muted"><?= htmlspecialchars($a['created_at']) ?></td></tr>
      <?php endwhile; ?>
    </table>
  </div>
</div>

<div class="panel">
  <h3>Leave Requests</h3>
  <div class="body" style="padding:0">
    <table>
      <tr><th>Type</th><th>From</th><th>To</th><th>Reason</th><th>Status</th></tr>
      <?php while ($l = mysqli_fetch_assoc($leaves)): ?>
        <tr><td><?= htmlspecialchars($l['leave_type']) ?></td>
            <td><?= htmlspecialchars($l['from_date']) ?></td>
            <td><?= htmlspecialchars($l['to_date']) ?></td>
            <td class="muted"><?= htmlspecialchars($l['reason']) ?></td>
            <td><span class="badge <?= htmlspecialchars($l['status']) ?>"><?= htmlspecialchars($l['status']) ?></span></td></tr>
      <?php endwhile; ?>
    </table>
  </div>
</div>
<?php render_footer(); ?>
