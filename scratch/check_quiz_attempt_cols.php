<?php
define('CLI_SCRIPT', true);
require_once(__DIR__ . '/../config.php');

global $DB;
$cols = $DB->get_columns('quiz_attempts');
echo implode(', ', array_keys($cols));
