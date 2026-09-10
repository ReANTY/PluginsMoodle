<?php
define('CLI_SCRIPT', true);
require_once(__DIR__ . '/../config.php');

global $DB;

$cm969 = $DB->get_record('course_modules', ['id' => 969]);
$cm970 = $DB->get_record('course_modules', ['id' => 970]);

echo "cm 969 (Quiz Minggu 1):\n";
print_r($cm969);

echo "\ncm 970 (Weekly Assignment 1):\n";
print_r($cm970);
