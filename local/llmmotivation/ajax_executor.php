<?php
// This file is part of Moodle - http://moodle.org/

define('AJAX_SCRIPT', true);

require_once(__DIR__ . '/../../config.php');

// Security check - only site administrators can manage the executor service
require_login();
require_sesskey();
require_capability('moodle/site:config', context_system::instance());

header('Content-Type: application/json; charset=utf-8');

$action = optional_param('action', 'status', PARAM_ALPHA);

/**
 * Ping executor service health endpoint.
 *
 * @param string $url
 * @return array
 */
function alai_ping_executor($url = 'http://127.0.0.1:3001/health') {
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 2,
        CURLOPT_CONNECTTIMEOUT => 1,
        CURLOPT_SSL_VERIFYPEER => false,
    ]);

    $response = curl_exec($ch);
    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlerr = curl_error($ch);
    curl_close($ch);

    if ($httpcode >= 200 && $httpcode < 300 && !empty($response)) {
        $json = json_decode($response, true);
        if (is_array($json) && !empty($json['status'])) {
            return [
                'online' => true,
                'httpcode' => $httpcode,
                'mode' => isset($json['mode']) ? $json['mode'] : 'unknown',
                'uptime' => isset($json['uptime']) ? round($json['uptime']) : 0,
                'data' => $json,
            ];
        }
    }

    return [
        'online' => false,
        'httpcode' => $httpcode,
        'error' => $curlerr,
    ];
}

$executordir = realpath($CFG->dirroot . '/services/executor');
$serverfile = $executordir ? $executordir . DIRECTORY_SEPARATOR . 'server.js' : '';

if ($action === 'status') {
    $ping = alai_ping_executor();
    echo json_encode([
        'success' => true,
        'online' => $ping['online'],
        'mode' => isset($ping['mode']) ? $ping['mode'] : '',
        'uptime' => isset($ping['uptime']) ? $ping['uptime'] : 0,
        'os' => PHP_OS_FAMILY,
        'directory' => $executordir,
        'exists' => file_exists($serverfile),
    ]);
    exit;
}

if ($action === 'start') {
    $ping = alai_ping_executor();
    if ($ping['online']) {
        echo json_encode([
            'success' => true,
            'already_running' => true,
            'online' => true,
            'mode' => $ping['mode'],
            'uptime' => $ping['uptime'],
            'message' => 'Service sudah berjalan aktif.',
        ]);
        exit;
    }

    if (!file_exists($serverfile)) {
        echo json_encode([
            'success' => false,
            'message' => 'File server.js tidak ditemukan di ' . $serverfile,
        ]);
        exit;
    }

    $iswindows = (PHP_OS_FAMILY === 'Windows' || DIRECTORY_SEPARATOR === '\\');

    if ($iswindows) {
        // Windows non-blocking execution
        $cmd = 'start /B cmd /c "cd /d ' . escapeshellarg($executordir) . ' && set EXECUTOR_MODE=dev&& node server.js"';
        pclose(popen($cmd, "r"));
    } else {
        // Linux / Azure VM background execution
        $cmd = 'cd ' . escapeshellarg($executordir) . ' && export EXECUTOR_MODE=dev && node server.js > /dev/null 2>&1 &';
        exec($cmd);
    }

    // Give node.js 1 second to bind to port
    usleep(1200000);

    $recheck = alai_ping_executor();

    echo json_encode([
        'success' => true,
        'online' => $recheck['online'],
        'mode' => isset($recheck['mode']) ? $recheck['mode'] : 'dev',
        'uptime' => isset($recheck['uptime']) ? $recheck['uptime'] : 0,
        'os' => PHP_OS_FAMILY,
        'message' => $recheck['online'] ? 'Service Node.js berhasil dijalankan!' : 'Perintah start telah dikirimkan. Silakan tunggu beberapa detik dan periksa kembali statusnya.',
    ]);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Aksi tidak dikenali.']);
exit;
