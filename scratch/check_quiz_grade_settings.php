<?php
define('CLI_SCRIPT', true);
require_once(__DIR__ . '/../config.php');

global $DB;

$gis = $DB->get_records_sql("SELECT gi.id, gi.itemname, gi.itemtype, gi.itemmodule, gi.iteminstance, gi.grademax, gi.grademin, gi.gradepass 
                               FROM {grade_items} gi 
                              WHERE gi.courseid = 22 AND gi.itemmodule = 'quiz'");
foreach ($gis as $gi) {
    echo "Quiz '{$gi->itemname}' (instance {$gi->iteminstance}): grademax = {$gi->grademax}, gradepass = {$gi->gradepass}\n";
}

// Cek juga grade_grades untuk user 3
$ggs = $DB->get_records_sql("SELECT gg.id, gi.itemname, gg.rawgrade, gg.finalgrade 
                               FROM {grade_grades} gg 
                               JOIN {grade_items} gi ON gi.id = gg.itemid 
                              WHERE gg.userid = 3 AND gi.courseid = 22");
echo "\nGrades for user 3:\n";
foreach ($ggs as $gg) {
    echo "Item '{$gg->itemname}': rawgrade = {$gg->rawgrade}, finalgrade = {$gg->finalgrade}\n";
}
