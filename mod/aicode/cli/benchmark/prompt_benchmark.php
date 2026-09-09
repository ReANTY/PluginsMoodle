<?php
// This file is part of Moodle - http://moodle.org/
//
// Prompt performance benchmark for mod_aicode thesis research (Bab 4).
// Runs experiments A (parameter tuning), B (prompting technique), C (ablation study).
//
// Usage:
//   php prompt_benchmark.php --apikey=YOUR_GEMINI_API_KEY [options]
//
// Options:
//   --apikey=KEY        Gemini API key (REQUIRED)
//   --model=MODEL       Model name (default: gemini-2.5-flash)
//   --experiment=X      Run specific experiment: A, B, C, or all (default: all)
//   --config=X          Run specific config only, e.g. A3 or B1 (optional)
//   --runs=N            Repetitions per scenario (default: 3)
//   --delay=MS          Delay between API calls in ms (default: 1500)
//   --output=DIR        Output directory (default: ./results)
//   --resume            Skip rows already present in results CSV
//
// @package    mod_aicode
// @copyright  2025 AICode Team
// @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later

define('CLI_SCRIPT', true);

// Parse CLI arguments.
$opts = [];
foreach ($argv as $arg) {
    if (preg_match('/^--([a-z_]+)=(.+)$/i', $arg, $m)) {
        $opts[$m[1]] = $m[2];
    } elseif (preg_match('/^--([a-z_]+)$/i', $arg, $m)) {
        $opts[$m[1]] = true;
    }
}

$apikey      = $opts['apikey']     ?? '';
$model       = $opts['model']      ?? 'gemini-2.5-flash';
$experiment  = strtoupper($opts['experiment'] ?? 'all');
$singlecfg   = $opts['config']     ?? '';
$runs        = max(1, (int)($opts['runs'] ?? 3));
$delayms     = max(0, (int)($opts['delay'] ?? 1500));
$outputdir   = $opts['output']     ?? __DIR__ . '/results';
$resume      = isset($opts['resume']);

if ($apikey === '') {
    fwrite(STDERR, "ERROR: --apikey is required.\n\n");
    fwrite(STDERR, "Usage: php prompt_benchmark.php --apikey=YOUR_GEMINI_API_KEY [--experiment=A|B|C|all] [--runs=3]\n");
    exit(1);
}

// Load dependencies.
require_once __DIR__ . '/prompt_variants.php';

$testsuitepath = __DIR__ . '/test_suite.json';
if (!file_exists($testsuitepath)) {
    fwrite(STDERR, "ERROR: test_suite.json not found at {$testsuitepath}\n");
    exit(1);
}

$scenarios = json_decode(file_get_contents($testsuitepath), true);
if (!is_array($scenarios) || empty($scenarios)) {
    fwrite(STDERR, "ERROR: test_suite.json is empty or invalid.\n");
    exit(1);
}

$allconfigs = get_experiment_configs();

// Filter configs by experiment / single config.
$configs = [];
foreach ($allconfigs as $cfgid => $cfg) {
    if ($singlecfg !== '' && strtoupper($singlecfg) !== strtoupper($cfgid)) {
        continue;
    }
    if ($experiment !== 'ALL') {
        $expprefix = 'EKS-' . $experiment;
        if ($cfg['experiment'] !== $expprefix) {
            continue;
        }
    }
    $configs[$cfgid] = $cfg;
}

if (empty($configs)) {
    fwrite(STDERR, "ERROR: No matching configs found for experiment={$experiment}, config={$singlecfg}\n");
    exit(1);
}

// Prepare output directory.
if (!is_dir($outputdir)) {
    mkdir($outputdir, 0755, true);
}

$csvpath = $outputdir . '/results.csv';
$csvheaders = [
    'experiment', 'config_id', 'config_label', 'temperature', 'top_p',
    'scenario_id', 'scenario_category', 'run_number',
    'http_code', 'latency_ms', 'raw_response_length',
    'json_valid', 'schema_valid', 'confidence',
    'returned_category', 'expected_category', 'category_match',
    'returned_line', 'expected_line', 'line_match',
    'message_short', 'message_long_excerpt',
    'hints_count', 'has_suggested_fix', 'has_materials',
    'prompt_token_estimate', 'error_code',
    'timestamp',
];

// Load existing results for resume mode.
$existingkeys = [];
if ($resume && file_exists($csvpath)) {
    $fh = fopen($csvpath, 'r');
    fgetcsv($fh); // skip header
    while (($row = fgetcsv($fh)) !== false) {
        if (count($row) >= 8) {
            $key = "{$row[1]}|{$row[5]}|{$row[7]}"; // config_id|scenario_id|run_number
            $existingkeys[$key] = true;
        }
    }
    fclose($fh);
    echo "[RESUME] Found " . count($existingkeys) . " existing results.\n";
}

// Open CSV for writing (append if resuming, new otherwise).
$writeheader = !$resume || !file_exists($csvpath);
$csvfh = fopen($csvpath, $resume ? 'a' : 'w');
if ($writeheader) {
    fputcsv($csvfh, $csvheaders);
}

// =========================================================================
// Main benchmark loop
// =========================================================================
$totalcalls = count($configs) * count($scenarios) * $runs;
$skipped = 0;
$completed = 0;
$errors = 0;

echo "=============================================================\n";
echo " mod_aicode Prompt Benchmark — Skripsi Bab 4\n";
echo "=============================================================\n";
echo " Model       : {$model}\n";
echo " Experiment  : {$experiment}\n";
echo " Configs     : " . count($configs) . " (" . implode(', ', array_keys($configs)) . ")\n";
echo " Scenarios   : " . count($scenarios) . "\n";
echo " Runs/each   : {$runs}\n";
echo " Total calls : {$totalcalls}\n";
echo " Output      : {$csvpath}\n";
echo " Delay       : {$delayms}ms\n";
echo "=============================================================\n\n";

foreach ($configs as $cfgid => $cfg) {
    echo "--- CONFIG: {$cfgid} ({$cfg['label']}) ---\n";

    foreach ($scenarios as $scenario) {
        $sid = $scenario['id'];

        for ($run = 1; $run <= $runs; $run++) {
            // Resume check.
            $resumekey = "{$cfgid}|{$sid}|{$run}";
            if ($resume && isset($existingkeys[$resumekey])) {
                $skipped++;
                continue;
            }

            $completed++;
            echo "  [{$completed}/{$totalcalls}] {$cfgid} × {$sid} × run#{$run} ... ";

            // Build prompt.
            $prompt = build_prompt(
                $cfg['prompt_key'],
                $scenario['code'],
                $scenario['stderr'],
                $scenario['trace']
            );

            // Estimate prompt tokens (~1 token per 4 chars for mixed ID/EN text).
            $prompttokens = (int)ceil(strlen($prompt) / 4);

            // Call Gemini API.
            $result = call_gemini_api($apikey, $model, $prompt, $cfg['temperature'], $cfg['top_p']);

            // Evaluate response.
            $eval = evaluate_response($result, $scenario);

            // Write CSV row.
            $row = [
                $cfg['experiment'],
                $cfgid,
                $cfg['label'],
                $cfg['temperature'],
                $cfg['top_p'],
                $sid,
                $scenario['category'],
                $run,
                $result['http_code'],
                $result['latency_ms'],
                strlen($result['raw_response'] ?? ''),
                $eval['json_valid'] ? 1 : 0,
                $eval['schema_valid'] ? 1 : 0,
                $eval['confidence'] ?? '',
                $eval['returned_category'] ?? '',
                $scenario['expected_category'],
                $eval['category_match'] ? 1 : 0,
                $eval['returned_line'] ?? '',
                $scenario['expected_line'],
                $eval['line_match'] ? 1 : 0,
                mb_substr($eval['message_short'] ?? '', 0, 120),
                mb_substr($eval['message_long'] ?? '', 0, 200),
                $eval['hints_count'] ?? 0,
                $eval['has_suggested_fix'] ? 1 : 0,
                $eval['has_materials'] ? 1 : 0,
                $prompttokens,
                $eval['error_code'] ?? '',
                date('Y-m-d H:i:s'),
            ];
            fputcsv($csvfh, $row);
            fflush($csvfh);

            // Status.
            $status = $eval['schema_valid'] ? 'PASS' : 'FAIL';
            if (!$eval['json_valid']) {
                $status = 'JSON_ERR';
                $errors++;
            } elseif (!$eval['schema_valid']) {
                $errors++;
            }

            $catmatch = $eval['category_match'] ? 'cat=OK' : 'cat=MISS';
            echo "{$status} | {$catmatch} | conf={$eval['confidence']} | {$result['latency_ms']}ms\n";

            // Rate-limit delay.
            if ($delayms > 0) {
                usleep($delayms * 1000);
            }
        }
    }
    echo "\n";
}

fclose($csvfh);

// =========================================================================
// Generate summary report
// =========================================================================
echo "Generating summary report...\n";
generate_summary($csvpath, $outputdir);

echo "\n=============================================================\n";
echo " BENCHMARK COMPLETE\n";
echo " Completed : {$completed}\n";
echo " Skipped   : {$skipped} (resume)\n";
echo " Errors    : {$errors}\n";
echo " Results   : {$csvpath}\n";
echo " Summary   : {$outputdir}/results_summary.md\n";
echo "=============================================================\n";


// =========================================================================
// Helper functions
// =========================================================================

/**
 * Call Gemini API (via OpenRouter) and return result with timing.
 */
function call_gemini_api(string $apikey, string $model, string $prompt, float $temp, float $topp): array {
    $url = "https://openrouter.ai/api/v1/chat/completions";

    // OpenRouter requires a provider prefix. Jika model belum ada prefix, otomatis tambahkan 'google/'.
    $ormodel = strpos($model, '/') === false ? "google/{$model}" : $model;

    $body = json_encode([
        'model' => $ormodel,
        'messages' => [
            ['role' => 'user', 'content' => $prompt]
        ],
        'temperature' => $temp,
        'top_p' => $topp,
        'max_tokens' => 2048,
    ]);

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $body,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $apikey,
            'HTTP-Referer: http://localhost', // OpenRouter standard
            'X-Title: AICode Benchmark'       // OpenRouter standard
        ],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 60,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);

    $start = microtime(true);
    $raw = curl_exec($ch);
    $latency = (int)round((microtime(true) - $start) * 1000);
    $httpcode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlerr = curl_error($ch);
    curl_close($ch);

    if ($curlerr !== '') {
        return [
            'http_code' => 0,
            'latency_ms' => $latency,
            'raw_response' => '',
            'generated_text' => '',
            'error' => $curlerr,
        ];
    }

    $decoded = json_decode($raw, true);
    // OpenRouter menstandarkan output menggunakan format OpenAI (choices[0].message.content)
    $text = trim($decoded['choices'][0]['message']['content'] ?? '');

    return [
        'http_code' => $httpcode,
        'latency_ms' => $latency,
        'raw_response' => $raw,
        'generated_text' => $text,
        'error' => '',
    ];
}

/**
 * Extract JSON object from model output (mirrors analyze_code.php logic).
 */
function extract_json(string $text): ?array {
    if ($text === '') return null;

    // Try direct parse.
    $decoded = json_decode($text, true);
    if (is_array($decoded)) return $decoded;

    // Try extracting from markdown code block.
    if (preg_match('/```(?:json)?\s*(\{.*\})\s*```/is', $text, $m)) {
        $decoded = json_decode($m[1], true);
        if (is_array($decoded)) return $decoded;
    }

    // Try finding first JSON object via brace-depth.
    $start = strpos($text, '{');
    if ($start === false) return null;

    $depth = 0;
    $instring = false;
    $escaped = false;
    $len = strlen($text);
    for ($i = $start; $i < $len; $i++) {
        $c = $text[$i];
        if ($instring) {
            if ($escaped) { $escaped = false; }
            elseif ($c === '\\') { $escaped = true; }
            elseif ($c === '"') { $instring = false; }
            continue;
        }
        if ($c === '"') { $instring = true; continue; }
        if ($c === '{') { $depth++; }
        elseif ($c === '}') {
            $depth--;
            if ($depth === 0) {
                $decoded = json_decode(substr($text, $start, $i - $start + 1), true);
                if (is_array($decoded)) return $decoded;
                return null;
            }
        }
    }
    return null;
}

/**
 * Evaluate a single API response against expected scenario data.
 */
function evaluate_response(array $result, array $scenario): array {
    $eval = [
        'json_valid' => false,
        'schema_valid' => false,
        'confidence' => null,
        'returned_category' => null,
        'category_match' => false,
        'returned_line' => null,
        'line_match' => false,
        'message_short' => null,
        'message_long' => null,
        'hints_count' => 0,
        'has_suggested_fix' => false,
        'has_materials' => false,
        'error_code' => $result['error'] ?? '',
    ];

    if ($result['http_code'] !== 200 || $result['generated_text'] === '') {
        $eval['error_code'] = 'http_' . $result['http_code'];
        return $eval;
    }

    $feedback = extract_json($result['generated_text']);
    if (!is_array($feedback)) {
        $eval['error_code'] = 'json_parse_failed';
        return $eval;
    }

    $eval['json_valid'] = true;

    // Schema validation.
    $diagnosis = $feedback['diagnosis'] ?? null;
    if (!is_array($diagnosis)) {
        $eval['error_code'] = 'missing_diagnosis';
        return $eval;
    }

    $short = trim($diagnosis['message_short'] ?? '');
    $long  = trim($diagnosis['message_long'] ?? '');
    $conf  = $diagnosis['confidence'] ?? -1;

    if ($short === '' || $long === '' || $conf < 0 || $conf > 1) {
        $eval['error_code'] = 'schema_incomplete';
        return $eval;
    }

    $eval['schema_valid'] = true;
    $eval['confidence'] = round((float)$conf, 3);
    $eval['message_short'] = $short;
    $eval['message_long'] = $long;

    // Category.
    $cat = strtolower(trim($diagnosis['category'] ?? ''));
    $eval['returned_category'] = $cat;
    $expectedcat = strtolower(trim($scenario['expected_category']));
    $eval['category_match'] = ($cat === $expectedcat);

    // Location.
    $line = (int)($feedback['location']['line'] ?? 0);
    $eval['returned_line'] = $line;
    $expectedline = (int)($scenario['expected_line'] ?? 0);
    // Line match: exact or within ±1.
    $eval['line_match'] = ($expectedline === 0) ? true : (abs($line - $expectedline) <= 1);

    // Hints.
    $hints = $feedback['hints'] ?? [];
    $eval['hints_count'] = is_array($hints) ? count($hints) : 0;

    // Suggested fix.
    $fix = $feedback['suggested_fix'] ?? null;
    $eval['has_suggested_fix'] = (is_array($fix) && !empty($fix['explanation']));

    // Materials.
    $mats = $feedback['recommended_materials'] ?? [];
    $eval['has_materials'] = (is_array($mats) && count($mats) > 0);

    $eval['error_code'] = '';
    return $eval;
}

/**
 * Generate a summary markdown report from the CSV results.
 */
function generate_summary(string $csvpath, string $outputdir): void {
    if (!file_exists($csvpath)) return;

    $rows = [];
    $fh = fopen($csvpath, 'r');
    $headers = fgetcsv($fh);
    while (($row = fgetcsv($fh)) !== false) {
        if (count($row) === count($headers)) {
            $rows[] = array_combine($headers, $row);
        }
    }
    fclose($fh);

    if (empty($rows)) return;

    // Group by experiment → config.
    $byexp = [];
    foreach ($rows as $r) {
        $exp = $r['experiment'];
        $cfg = $r['config_id'];
        $byexp[$exp][$cfg][] = $r;
    }

    $md = "# Hasil Pengujian Performa Prompt — Ringkasan\n\n";
    $md .= "Dihasilkan: " . date('Y-m-d H:i:s') . "\n\n";

    foreach ($byexp as $exp => $configs) {
        $md .= "## {$exp}\n\n";
        $md .= "| Config | Label | PSR (%) | Avg Confidence | Category Match (%) | Line Match (%) | Avg Latency (ms) | Median Latency | P95 Latency |\n";
        $md .= "|--------|-------|---------|----------------|-------------------|----------------|-----------------|----------------|-------------|\n";

        foreach ($configs as $cfgid => $cfgrows) {
            $total = count($cfgrows);
            $jsonvalid = array_sum(array_column($cfgrows, 'json_valid'));
            $schemavalid = array_sum(array_column($cfgrows, 'schema_valid'));
            $catmatches = array_sum(array_column($cfgrows, 'category_match'));
            $linematches = array_sum(array_column($cfgrows, 'line_match'));

            $psr = $total > 0 ? round(($schemavalid / $total) * 100, 1) : 0;
            $catpct = $total > 0 ? round(($catmatches / $total) * 100, 1) : 0;
            $linepct = $total > 0 ? round(($linematches / $total) * 100, 1) : 0;

            // Confidence avg (only valid responses).
            $confs = array_filter(array_column($cfgrows, 'confidence'), fn($v) => $v !== '' && $v !== null);
            $avgconf = count($confs) > 0 ? round(array_sum($confs) / count($confs), 3) : 0;

            // Latency stats.
            $latencies = array_map('intval', array_column($cfgrows, 'latency_ms'));
            sort($latencies);
            $avglat = count($latencies) > 0 ? (int)round(array_sum($latencies) / count($latencies)) : 0;
            $medlat = $latencies[(int)floor(count($latencies) / 2)] ?? 0;
            $p95idx = (int)floor(count($latencies) * 0.95);
            $p95lat = $latencies[min($p95idx, count($latencies) - 1)] ?? 0;

            $label = $cfgrows[0]['config_label'] ?? '';
            $md .= "| {$cfgid} | {$label} | {$psr} | {$avgconf} | {$catpct} | {$linepct} | {$avglat} | {$medlat} | {$p95lat} |\n";
        }
        $md .= "\n";

        // Per-scenario breakdown.
        $md .= "### Detail per Skenario — {$exp}\n\n";
        $md .= "| Config | TS-01 | TS-02 | TS-03 | TS-04 | TS-05 | TS-06 | TS-07 | TS-08 | TS-09 | TS-10 |\n";
        $md .= "|--------|-------|-------|-------|-------|-------|-------|-------|-------|-------|-------|\n";

        foreach ($configs as $cfgid => $cfgrows) {
            $byscenario = [];
            foreach ($cfgrows as $r) {
                $byscenario[$r['scenario_id']][] = $r;
            }
            $cells = [$cfgid];
            foreach (['TS-01','TS-02','TS-03','TS-04','TS-05','TS-06','TS-07','TS-08','TS-09','TS-10'] as $sid) {
                $srows = $byscenario[$sid] ?? [];
                $pass = 0;
                $total = count($srows);
                foreach ($srows as $sr) {
                    if ($sr['schema_valid'] && $sr['category_match']) $pass++;
                }
                $cells[] = $total > 0 ? "{$pass}/{$total}" : '-';
            }
            $md .= '| ' . implode(' | ', $cells) . " |\n";
        }
        $md .= "\n---\n\n";
    }

    // Overall stats.
    $totalrows = count($rows);
    $totaljson = array_sum(array_column($rows, 'json_valid'));
    $totalschema = array_sum(array_column($rows, 'schema_valid'));
    $totalcat = array_sum(array_column($rows, 'category_match'));

    $md .= "## Statistik Keseluruhan\n\n";
    $md .= "| Metrik | Nilai |\n";
    $md .= "|--------|-------|\n";
    $md .= "| Total API Calls | {$totalrows} |\n";
    $md .= "| JSON Valid (%) | " . round(($totaljson / max(1, $totalrows)) * 100, 1) . " |\n";
    $md .= "| Schema Valid / PSR (%) | " . round(($totalschema / max(1, $totalrows)) * 100, 1) . " |\n";
    $md .= "| Category Match (%) | " . round(($totalcat / max(1, $totalrows)) * 100, 1) . " |\n";

    file_put_contents($outputdir . '/results_summary.md', $md);
}
