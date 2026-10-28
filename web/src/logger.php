<?php
/* ---------------------------------------------------------------------------
 * Structured security-event logging for SIEM ingestion (CrowdStrike NG-SIEM /
 * Falcon LogScale compatible).
 *
 *  - One JSON object per line (NDJSON) -> /var/log/senarath/app.log
 *  - Also mirrored to php://stderr so the Docker json-file driver captures it
 *    (gives the collector a second, container-log ingestion path).
 *  - Flat, consistent field names so a json parser extracts everything.
 *
 * NOTE: this is blue-team instrumentation only. It records events; it does NOT
 * block or sanitise anything, so the app stays fully vulnerable for the lab.
 * ------------------------------------------------------------------------- */

define('SENARATH_LOG_DIR',  getenv('APP_LOG_DIR') ?: '/var/log/senarath');
define('SENARATH_LOG_FILE', SENARATH_LOG_DIR . '/app.log');

function sec_log(array $event): void {
    $base = [
        'timestamp'    => gmdate('Y-m-d\TH:i:s\Z'),          // UTC ISO-8601
        'app'          => 'senarath-hr',
        'env'          => 'lab',
        'host'         => gethostname(),
        'src_ip'       => $_SERVER['REMOTE_ADDR']     ?? null,
        'x_forwarded_for' => $_SERVER['HTTP_X_FORWARDED_FOR'] ?? null,
        'user_agent'   => $_SERVER['HTTP_USER_AGENT'] ?? null,
        'http_method'  => $_SERVER['REQUEST_METHOD']  ?? null,
        'http_path'    => $_SERVER['REQUEST_URI']     ?? null,
        // who is doing it (from the session), if anyone
        'session_user' => $_SESSION['username'] ?? null,
        'session_uid'  => isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null,
        'session_role' => $_SESSION['role'] ?? null,
    ];
    // event-specific fields override/extend the base
    $rec  = array_merge($base, $event);
    $line = json_encode($rec, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n";

    if (!is_dir(SENARATH_LOG_DIR)) { @mkdir(SENARATH_LOG_DIR, 0777, true); }
    @file_put_contents(SENARATH_LOG_FILE, $line, FILE_APPEND | LOCK_EX); // primary (clean NDJSON)
    @file_put_contents('php://stderr', $line);                           // fallback (docker logs)
}

/* Cheap indicator used to TAG suspicious input in the logs so the SOC can
 * pivot on it. Metadata only — never changes app behaviour. */
function looks_like_sqli($s): bool {
    if (!is_string($s) || $s === '') return false;
    return (bool)preg_match(
        '/(\bunion\b|\bselect\b|--|#|\/\*|\bor\b\s+[\'"]?\d+[\'"]?\s*=\s*[\'"]?\d+|[\'"]\s*or\s|\bsleep\s*\(|\bbenchmark\s*\(|\binformation_schema\b|\bload_file\b|0x[0-9a-f]{2,})/i',
        $s
    );
}
