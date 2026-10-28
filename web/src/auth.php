<?php
/* ---------------------------------------------------------------------------
 * Session helpers (deliberately weak — LAB)
 *   - No session regeneration, no CSRF, role trusted from session blindly.
 * ------------------------------------------------------------------------- */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function current_user() {
    return isset($_SESSION['user_id']) ? $_SESSION : null;
}

// Require only that *someone* is logged in. (No role check here — the admin
// area reuses this, which is the broken-access-control bug.)
function require_login() {
    if (!isset($_SESSION['user_id'])) {
        header('Location: index.php');
        exit;
    }
}

function log_activity($conn, $user_id, $text) {
    $ip  = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    // Unparameterised, but inputs here are app-controlled.
    $text = mysqli_real_escape_string($conn, $text);
    @mysqli_query($conn,
        "INSERT INTO activities (user_id, activity, ip_address, created_at)
         VALUES ($user_id, '$text', '$ip', NOW())");
}
