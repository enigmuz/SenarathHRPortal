<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/partials.php';
require_once __DIR__ . '/logger.php';
require_login();

$q = $_GET['q'] ?? '';
$rows = null;
$sql  = '';

if (isset($_GET['q'])) {
    /* -----------------------------------------------------------------------
     *  INTENTIONALLY VULNERABLE SEARCH (LAB)
     *  The search term is concatenated into the query. Because a fixed set of
     *  columns is selected and echoed into the table, this is directly
     *  UNION-injectable, e.g.:
     *     q =  ' UNION SELECT 1,username,password,role,email,phone FROM users -- ␣
     *     q =  ' UNION SELECT 1,@@version,database(),user(),4,5 -- ␣
     *  (6 columns returned below.)
     * --------------------------------------------------------------------- */
    $sql = "SELECT id, full_name, department, position, email, phone
            FROM users
            WHERE full_name LIKE '%$q%' OR department LIKE '%$q%'
            ORDER BY full_name";
    $rows = mysqli_query($conn, $sql);
    log_activity($conn, (int)$_SESSION['user_id'], 'Viewed staff directory');
    $susp = looks_like_sqli($q);
    sec_log([
        'event_category' => $rows === false ? 'error' : 'data_access',
        'event_action'   => $rows === false ? 'sql_error' : 'staff_search',
        'component'      => 'directory',
        'severity'       => ($susp || $rows === false) ? 'high' : 'info',
        'outcome'        => $rows === false ? 'error' : 'success',
        'query'          => $q,                                   // captures the SQLi payload
        'result_count'   => ($rows && $rows !== false) ? mysqli_num_rows($rows) : 0,
        'sqli_suspected' => $susp,
        'message'        => $rows === false ? mysqli_error($conn) : null,
    ]);
}

render_header('directory');
?>
<div class="page-title"><h2>Staff Directory</h2><span class="crumb">Search colleagues across Senarath Bank</span></div>

<form method="get" class="search-row">
  <input type="text" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="Search by name or department…">
  <button type="submit">Search</button>
</form>

<?php if ($rows === false): ?>
  <div class="panel"><div class="body"><strong style="color:#b02a37">SQL error:</strong>
    <code class="inl"><?= htmlspecialchars(mysqli_error($conn)) ?></code></div></div>
<?php elseif ($rows !== null): ?>
  <div class="panel">
    <h3>Results (<?= mysqli_num_rows($rows) ?>)</h3>
    <div class="body" style="padding:0">
      <table>
        <tr><th>ID</th><th>Name</th><th>Department</th><th>Position</th><th>Email</th><th>Phone</th></tr>
        <?php while ($r = mysqli_fetch_row($rows)): ?>
          <tr>
            <td><?= htmlspecialchars($r[0]) ?></td>
            <td><a href="profile.php?id=<?= urlencode($r[0]) ?>"><?= htmlspecialchars($r[1]) ?></a></td>
            <td><?= htmlspecialchars($r[2]) ?></td>
            <td><?= htmlspecialchars($r[3]) ?></td>
            <td class="muted"><?= htmlspecialchars($r[4]) ?></td>
            <td class="muted"><?= htmlspecialchars($r[5]) ?></td>
          </tr>
        <?php endwhile; ?>
      </table>
    </div>
  </div>
<?php else: ?>
  <div class="panel"><div class="body muted">Enter a search term to list staff.</div></div>
<?php endif; ?>
<?php render_footer(); ?>
