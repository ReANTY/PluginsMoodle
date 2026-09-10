<?php
define('CLI_SCRIPT', true);
require_once(__DIR__ . '/../config.php');
require_once($CFG->libdir . '/completionlib.php');
require_once($CFG->dirroot . '/mod/quiz/locallib.php');

global $DB;

$cm969 = $DB->get_record('course_modules', ['id' => 969]);
echo "cm 969 completionpassgrade before: {$cm969->completionpassgrade}\n";

// Cek custom completion rules di customdata
echo "customdata: {$cm969->customdata}\n";
