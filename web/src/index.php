<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/logger.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    /* -----------------------------------------------------------------------
     *  INTENTIONALLY VULNERABLE AUTH (LAB)
     *  User input is concatenated straight into the SQL string with no
     *  escaping, binding, or hashing. This allows authentication bypass via
     *  SQL injection, e.g.:
     *        username:  admin' --␣          (note trailing space after --)
     *        username:  ' OR '1'='1' --␣
     *  Passwords are also compared in PLAINTEXT.
     * --------------------------------------------------------------------- */
    $sql = "SELECT * FROM users
            WHERE username = '$username' AND password = '$password'
            LIMIT 1";

    $res = mysqli_query($conn, $sql);
    $susp = looks_like_sqli($username) || looks_like_sqli($password);

    if ($res === false) {
        // Verbose SQL errors on purpose (enables error-based SQLi).
        $error = 'SQL error: ' . mysqli_error($conn);
        sec_log([
            'event_category' => 'error',
            'event_action'   => 'sql_error',
            'component'      => 'login',
            'severity'       => 'high',
            'outcome'        => 'error',
            'attempt_user'   => $username,
            'sqli_suspected' => $susp,
            'message'        => mysqli_error($conn),
        ]);
    } elseif (mysqli_num_rows($res) > 0) {
        $row = mysqli_fetch_assoc($res);
        $_SESSION['user_id']   = $row['id'];
        $_SESSION['username']  = $row['username'];
        $_SESSION['full_name'] = $row['full_name'];
        $_SESSION['role']      = $row['role'];
        log_activity($conn, (int)$row['id'], 'Logged in');
        sec_log([
            'event_category'       => 'authentication',
            'event_action'         => 'login_success',
            'severity'             => $susp ? 'high' : 'info',
            'outcome'              => 'success',
            'actor'                => $row['username'],
            'actor_uid'            => (int)$row['id'],
            'actor_role'           => $row['role'],
            'attempt_user'         => $username,
            'auth_bypass_suspected'=> $susp,   // success + injection chars => likely SQLi bypass
        ]);
        header('Location: dashboard.php');
        exit;
    } else {
        $error = 'Invalid username or password.';
        sec_log([
            'event_category' => 'authentication',
            'event_action'   => 'login_failure',
            'severity'       => $susp ? 'high' : 'warning',
            'outcome'        => 'failure',
            'attempt_user'   => $username,
            'sqli_suspected' => $susp,
        ]);
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title><?= APP_NAME ?></title>
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
  <div class="login-wrap">
    <div class="login-card">
      <div class="login-head">
        <div class="brand-badge">S</div>
        <h1>Senarath Bank</h1>
        <p>HR Management Portal · Staff Sign-in</p>
      </div>
      <div class="login-body">
        <?php if ($error): ?>
          <div class="alert"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <form method="post" autocomplete="off">
          <div class="field">
            <label for="username">Staff Username / Email</label>
            <input type="text" id="username" name="username" placeholder="e.g. nperera" required>
          </div>
          <div class="field">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" placeholder="••••••••" required>
          </div>
          <button class="btn" type="submit">Sign In</button>
        </form>
        <div class="login-foot">Forgot password? Contact HR Helpdesk · ext. 2200</div>
        <div class="lab-ribbon">⚠ Training lab · fictional data · authorized security testing only</div>
      </div>
    </div>
  </div>
</body>
</html>
