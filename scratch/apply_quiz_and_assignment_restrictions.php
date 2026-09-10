<?php
define('CLI_SCRIPT', true);
require_once(__DIR__ . '/../config.php');
require_once($CFG->dirroot . '/course/lib.php');

global $DB;

// Map of sections to previous section's required CM IDs in Course 22:
// Section 2 requires Minggu 1: Weekly Quiz (969) & Weekly Assignment (970)
// Section 3 requires Minggu 2: Weekly Quiz (983) & Weekly Assignment (984)
// Section 4 requires Minggu 3: Weekly Quiz (997) & Weekly Assignment (998)
// Section 5 requires Minggu 4: Weekly Quiz (1011) & Weekly Assignment (1012)
// Section 6 requires Minggu 5: Weekly Quiz (1025) & Weekly Assignment (1026)
// Section 7 requires Minggu 6: Weekly Quiz (1039) & Weekly Assignment (1040)
// Section 8 requires Minggu 7: Weekly Quiz (1053) & Weekly Assignment (1054)

$prerequisites = [
    2 => [969, 970],
    3 => [983, 984],
    4 => [997, 998],
    5 => [1011, 1012],
    6 => [1025, 1026],
    7 => [1039, 1040],
    8 => [1053, 1054],
];

foreach ($prerequisites as $secnum => $cmids) {
    $sec = $DB->get_record('course_sections', ['course' => 22, 'section' => $secnum]);
    if (!$sec) continue;

    $conditions = [];
    $showc = [];
    foreach ($cmids as $cmid) {
        $conditions[] = ['type' => 'completion', 'cm' => (int)$cmid, 'e' => 1];
        $showc[] = false; // Hidden entirely until completed
    }

    $tree = [
        'op' => '&',
        'c' => $conditions,
        'showc' => $showc,
    ];

    $json = json_encode($tree);
    $DB->set_field('course_sections', 'availability', $json, ['id' => $sec->id]);
    echo "Section {$secnum} availability updated: {$json}\n";
}

rebuild_course_cache(22, true);
echo "Course cache rebuilt successfully.\n";
