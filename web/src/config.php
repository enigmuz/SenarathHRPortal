<?php
/* ---------------------------------------------------------------------------
 * Senarath Bank HR Portal — DB configuration (LAB)
 * Credentials are intentionally weak and pulled from the docker-compose env.
 * ------------------------------------------------------------------------- */
$DB_HOST = getenv('DB_HOST') ?: 'db';
$DB_USER = getenv('DB_USER') ?: 'webapp';
$DB_PASS = getenv('DB_PASS') ?: 'webapp123';
$DB_NAME = getenv('DB_NAME') ?: 'senarath_hr';

// App constants
define('APP_NAME', 'Senarath Bank HR Management Portal');
