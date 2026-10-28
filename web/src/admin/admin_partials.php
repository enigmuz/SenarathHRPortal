<?php
// Admin shell layout (paths are ../ relative because these live in /admin/)
function render_admin_header($active = '') {
    $name = $_SESSION['full_name'] ?? 'User';
    $role = $_SESSION['role'] ?? 'employee';
    $init = strtoupper(substr($name,0,1));
    echo '<!doctype html><html lang="en"><head><meta charset="utf-8">';
    echo '<meta name="viewport" content="width=device-width,initial-scale=1">';
    echo '<title>Admin · '.APP_NAME.'</title>';
    echo '<link rel="stylesheet" href="../assets/css/style.css"></head><body>';
    echo '<div class="lab-banner">⚠ TRAINING LAB — ADMIN AREA · deliberately vulnerable · internal use only</div>';
    echo '<div class="topbar"><div class="brand"><div class="dot">S</div><div>Senarath Bank<small>HR Admin Console</small></div></div><nav>';
    $nav = ['index'=>['index.php','Overview'], 'users'=>['users.php','Manage Staff']];
    foreach ($nav as $k=>$it) {
        $cls = $k===$active ? ' style="background:rgba(255,255,255,.12)"' : '';
        echo '<a href="'.$it[0].'"'.$cls.'>'.$it[1].'</a>';
    }
    echo '<a href="../dashboard.php">← Back to Portal</a>';
    echo '<span class="user-chip"><span class="av">'.$init.'</span>'.htmlspecialchars($name)
        .' <span class="badge '.$role.'" style="margin-left:4px">'.$role.'</span>'
        .' <a href="../logout.php" style="color:#ffd;margin-left:6px">Logout</a></span>';
    echo '</nav></div><div class="container">';
}
function render_admin_footer() {
    echo '</div><div class="footer">Senarath Bank HR Admin · Lab build · for authorized training only.</div></body></html>';
}
