<?php
// Shared layout partials for the authenticated app shell.
function render_header($active = '') {
    $u = $_SESSION ?? [];
    $name = $u['full_name'] ?? 'User';
    $role = $u['role'] ?? 'employee';
    $init = strtoupper(substr($name,0,1));
    $nav = [
        'dashboard'  => ['dashboard.php','Dashboard'],
        'directory'  => ['directory.php','Staff Directory'],
        'payroll'    => ['payroll.php','My Payroll'],
        'activities' => ['activities.php','Activity Log'],
        'profile'    => ['profile.php','My Profile'],
    ];
    echo '<!doctype html><html lang="en"><head><meta charset="utf-8">';
    echo '<meta name="viewport" content="width=device-width,initial-scale=1">';
    echo '<title>'.APP_NAME.'</title>';
    echo '<link rel="stylesheet" href="assets/css/style.css"></head><body>';
    echo '<div class="lab-banner">⚠ TRAINING LAB — Senarath Bank is fictional · deliberately vulnerable · internal use only</div>';
    echo '<div class="topbar"><div class="brand"><div class="dot">S</div><div>Senarath Bank<small>HR Management Portal</small></div></div><nav>';
    foreach ($nav as $key=>$item) {
        $cls = $key===$active ? ' style="background:rgba(255,255,255,.12)"' : '';
        echo '<a href="'.$item[0].'"'.$cls.'>'.$item[1].'</a>';
    }
    echo '<a class="admin" href="admin/index.php">⚙ Admin</a>';
    echo '<span class="user-chip"><span class="av">'.$init.'</span>'.htmlspecialchars($name)
        .' <a href="logout.php" style="color:#ffd;margin-left:6px">Logout</a></span>';
    echo '</nav></div><div class="container">';
}
function render_footer() {
    echo '</div><div class="footer">Senarath Bank HR Portal · Lab build · '
        . date('Y') . ' · For authorized security training only.</div></body></html>';
}
