<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/partials.php';
require_login();

$uid = (int)$_SESSION['user_id'];
$me  = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM users WHERE id = $uid"));

// Latest payslip
$pay = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT * FROM payroll WHERE user_id = $uid ORDER BY pay_month DESC LIMIT 1"));

// Counts
$act_count   = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(*) c FROM activities WHERE user_id = $uid"))['c'];
$leave_count = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(*) c FROM leave_requests WHERE user_id = $uid AND status='pending'"))['c'];
$staff_total = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(*) c FROM users"))['c'];

// Recent activity
$acts = mysqli_query($conn,
    "SELECT * FROM activities WHERE user_id = $uid ORDER BY created_at DESC LIMIT 6");

render_header('dashboard');
?>
<div class="page-title">
  <h2>Welcome back, <?= htmlspecialchars(explode(' ', $me['full_name'])[0]) ?> 👋</h2>
  <span class="crumb"><?= htmlspecialchars($me['department']) ?> · <?= htmlspecialchars($me['position']) ?></span>
</div>

<div class="cards">
  <div class="stat"><div class="k">Net Pay (<?= htmlspecialchars($pay['pay_month'] ?? '—') ?>)</div>
    <div class="v">LKR <?= $pay ? number_format($pay['net_salary']) : '—' ?></div></div>
  <div class="stat"><div class="k">Pending Leave</div><div class="v"><?= $leave_count ?></div></div>
  <div class="stat"><div class="k">My Activities</div><div class="v"><?= $act_count ?></div></div>
  <div class="stat"><div class="k">Staff On Portal</div><div class="v"><?= $staff_total ?></div></div>
</div>

<div class="panel">
  <h3>My Profile Summary</h3>
  <div class="body">
    <div class="kv">
      <div class="key">Employee ID</div><div>SB-<?= str_pad($me['id'],4,'0',STR_PAD_LEFT) ?></div>
      <div class="key">Full Name</div><div><?= htmlspecialchars($me['full_name']) ?></div>
      <div class="key">Email</div><div><?= htmlspecialchars($me['email']) ?></div>
      <div class="key">Phone</div><div><?= htmlspecialchars($me['phone']) ?></div>
      <div class="key">Role</div><div><span class="badge <?= $me['role'] ?>"><?= $me['role'] ?></span></div>
      <div class="key">Joined</div><div><?= htmlspecialchars($me['date_joined']) ?></div>
    </div>
    <p style="margin-top:14px"><a href="profile.php?id=<?= $me['id'] ?>">View full profile →</a></p>
  </div>
</div>

<div class="panel">
  <h3>Recent Activity</h3>
  <div class="body" style="padding:0">
    <table>
      <tr><th>Activity</th><th>IP</th><th>When</th></tr>
      <?php while ($a = mysqli_fetch_assoc($acts)): ?>
        <tr><td><?= htmlspecialchars($a['activity']) ?></td>
            <td class="muted"><?= htmlspecialchars($a['ip_address']) ?></td>
            <td class="muted"><?= htmlspecialchars($a['created_at']) ?></td></tr>
      <?php endwhile; ?>
    </table>
  </div>
</div>
<?php render_footer(); ?>
