<?php
unset($CFG);
global $CFG;
$CFG = new stdClass();

/**
 * Read environment variable with fallback default.
 */
function env_or_default(string $key, $default = '') {
    $value = getenv($key);
    return ($value === false || $value === '') ? $default : $value;
}

$railwaydomain = env_or_default('RAILWAY_PUBLIC_DOMAIN', '');
$autowwwroot = $railwaydomain ? 'https://' . $railwaydomain : 'http://localhost';

$CFG->dbtype    = 'mysqli';
$CFG->dblibrary = 'native';
$CFG->dbhost    = env_or_default('MYSQLHOST', 'localhost');
$CFG->dbname    = env_or_default('MYSQLDATABASE', 'railway');
$CFG->dbuser    = env_or_default('MYSQLUSER', 'root');
$CFG->dbpass    = env_or_default('MYSQLPASSWORD', '');
$CFG->prefix    = 'mdl_';
$CFG->dboptions = array(
    'dbpersist' => 0,
    'dbport' => env_or_default('MYSQLPORT', '3306'),
    'dbsocket' => '',
    'dbcollation' => 'utf8mb4_unicode_ci',
);

$CFG->wwwroot   = $autowwwroot;
$CFG->dataroot  = '/app/moodledata';
$CFG->admin     = 'admin';
$CFG->directorypermissions = 0777;

// Railway runs behind proxy and TLS terminates at edge.
$CFG->reverseproxy = true;
$CFG->sslproxy = true;

require_once(__DIR__ . '/lib/setup.php');
