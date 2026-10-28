<?php
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/admin_partials.php';

/* ---------------------------------------------------------------------------
 *  INTENTIONALLY VULNERABLE (LAB): BROKEN ACCESS CONTROL
 *  This console only calls require_login() — it never checks that the session
 *  role is 'admin'. So ANY authenticated user (including one who logged in via
 *  SQL-injection bypass) can reach the admin console. The commented line below
 *  is the check that *should* be here.
 *
 *  // if (($_SESSION['role'] ?? '') !== 'admin') { http_response_code(403); exit('Forbidden'); }
 * ------------------------------------------------------------------------- */
require_login();

$total   = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) c FROM users"))['c'];
$admins  = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) c FROM users WHERE role='admin'"))['c'];
$mgrs    = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) c FROM users WHERE role='manager'"))['c'];
$payroll = mysqli_fetch_assoc(mysqli_query($conn,"SELECT SUM(net_salary) s FROM payroll WHERE pay_month=(SELECT MAX(pay_month) FROM payroll)"))['s'];
$pendlv  = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) c FROM leave_requests WHERE status='pending'"))['c'];

$recent = mysqli_query($conn,
  "SELECT a.activity,a.ip_address,a.created_at,u.full_name
     FROM activities a JOIN users u ON u.id=a.user_id
    ORDER BY a.created_at DESC LIMIT 10");

render_admin_header('index');
?>
<div class="page-title"><h2>Admin Overview</h2><span class="crumb">HR operations console</span></div>

<div class="cards">
  <div class="stat"><div class="k">Total Staff</div><div class="v"><?= $total ?></div></div>
  <div class="stat"><div class="k">Administrators</div><div class="v"><?= $admins ?></div></div>
  <div class="stat"><div class="k">Managers</div><div class="v"><?= $mgrs ?></div></div>
  <div class="stat"><div class="k">Latest Payroll Run</div><div class="v">LKR <?= number_format((float)$payroll) ?></div></div>
  <div class="stat"><div class="k">Pending Leave</div><div class="v"><?= $pendlv ?></div></div>
</div>

<div class="panel">
  <h3>Quick Actions</h3>
  <div class="body">
    <a class="btn-sm" href="users.php">Manage Staff Accounts</a>
  </div>
</div>

<div class="panel">
  <h3>Portal-wide Recent Activity</h3>
  <div class="body" style="padding:0">
    <table>
      <tr><th>Staff</th><th>Activity</th><th>IP</th><th>When</th></tr>
      <?php while ($r = mysqli_fetch_assoc($recent)): ?>
        <tr><td><?= htmlspecialchars($r['full_name']) ?></td>
            <td><?= htmlspecialchars($r['activity']) ?></td>
            <td class="muted"><?= htmlspecialchars($r['ip_address']) ?></td>
            <td class="muted"><?= htmlspecialchars($r['created_at']) ?></td></tr>
      <?php endwhile; ?>
    </table>
  </div>
</div>
<?php render_admin_footer(); ?>
