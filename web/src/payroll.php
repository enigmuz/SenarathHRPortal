<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/partials.php';
require_login();

/* INTENTIONALLY VULNERABLE (LAB): IDOR — any user can read anyone's payroll
 * by changing user_id, and the param is also injectable.
 *     payroll.php?user_id=1 */
$uid = $_GET['user_id'] ?? $_SESSION['user_id'];

$owner = mysqli_fetch_assoc(mysqli_query($conn, "SELECT full_name, department FROM users WHERE id = $uid"));
$rows  = mysqli_query($conn, "SELECT * FROM payroll WHERE user_id = $uid ORDER BY pay_month DESC");

render_header('payroll');

if ($rows === false) {
    echo '<div class="panel"><div class="body"><strong style="color:#b02a37">SQL error:</strong> '
       . '<code class="inl">' . htmlspecialchars(mysqli_error($conn)) . '</code></div></div>';
    render_footer(); exit;
}
?>
<div class="page-title"><h2>Payroll</h2>
  <span class="crumb"><?= htmlspecialchars($owner['full_name'] ?? 'Unknown') ?></span></div>

<div class="panel">
  <h3>Payslip History</h3>
  <div class="body" style="padding:0">
    <table>
      <tr><th>Month</th><th class="right">Basic</th><th class="right">Allowances</th>
          <th class="right">Deductions</th><th class="right">EPF</th><th class="right">ETF</th>
          <th class="right">Net Salary</th><th>Pay Date</th></tr>
      <?php while ($p = mysqli_fetch_assoc($rows)): ?>
        <tr>
          <td><strong><?= htmlspecialchars($p['pay_month']) ?></strong></td>
          <td class="right"><?= number_format($p['basic']) ?></td>
          <td class="right"><?= number_format($p['allowances']) ?></td>
          <td class="right"><?= number_format($p['deductions']) ?></td>
          <td class="right"><?= number_format($p['epf']) ?></td>
          <td class="right"><?= number_format($p['etf']) ?></td>
          <td class="right"><strong>LKR <?= number_format($p['net_salary']) ?></strong></td>
          <td class="muted"><?= htmlspecialchars($p['pay_date']) ?></td>
        </tr>
      <?php endwhile; ?>
    </table>
  </div>
</div>
<?php render_footer(); ?>
