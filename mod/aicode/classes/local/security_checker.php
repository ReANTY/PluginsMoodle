<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Server-side static security analyser for student code.
 *
 * Performs pattern-based analysis per language to detect dangerous operations
 * (OS commands, filesystem writes, network access, code-injection tricks, etc.)
 * before code is forwarded to the executor service.
 *
 * @package    mod_aicode
 * @copyright  2025 AICode Team
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_aicode\local;

defined('MOODLE_INTERNAL') || die();

/**
 * Static code security analyser.
 *
 * Usage:
 *   $result = security_checker::analyze($code, 'javascript', 'high');
 *   if ($result['blocked']) { ... }
 */
class security_checker {

    /** No violations detected. */
    const RISK_NONE     = 'none';
    /** Minor issues (e.g. innerHTML assignment). Warn only. */
    const RISK_LOW      = 'low';
    /** Suspicious patterns (e.g. globals(), excessive nesting). Log only by default. */
    const RISK_MEDIUM   = 'medium';
    /** Serious violations (eval, file-system access). Block by default. */
    const RISK_HIGH     = 'high';
    /** Critical violations (OS commands, process spawning). Always block. */
    const RISK_CRITICAL = 'critical';

    /**
     * Analyse student code for security violations.
     *
     * Returns:
     *   - safe       bool    True when no violations were found.
     *   - risk_level string  Aggregate risk (none|low|medium|high|critical).
     *   - violations array   Each item: [rule, line, match, message, risk].
     *   - blocked    bool    True when aggregate risk >= $blocklevel.
     *
     * @param string $code       Raw student source code.
     * @param string $language   Language tag (javascript, python, java, cpp, …).
     * @param string $blocklevel Minimum risk level that triggers a block (default 'high').
     * @return array
     */
    public static function analyze(string $code, string $language, string $blocklevel = 'high'): array {
        $violations = [];
        $lines      = explode("\n", $code);
        $rules      = self::get_rules($language);

        foreach ($rules as $rule) {
            foreach ($lines as $index => $line) {
                if (preg_match($rule['pattern'], $line, $matches) === 1) {
                    // One violation per rule per line is enough.
                    $duplicate = false;
                    foreach ($violations as $existing) {
                        if ($existing['rule'] === $rule['id'] && $existing['line'] === $index + 1) {
                            $duplicate = true;
                            break;
                        }
                    }
                    if (!$duplicate) {
                        $violations[] = [
                            'rule'    => $rule['id'],
                            'line'    => $index + 1,
                            'match'   => trim($matches[0]),
                            'message' => $rule['message'],
                            'risk'    => $rule['risk'],
                        ];
                    }
                }
            }
        }

        $nestingviolation = self::check_nesting_depth($code, $language);
        if ($nestingviolation !== null) {
            $violations[] = $nestingviolation;
        }

        $risklevel = self::aggregate_risk($violations);
        $blocked   = self::should_block($risklevel, $blocklevel);

        return [
            'safe'       => empty($violations),
            'risk_level' => $risklevel,
            'violations' => $violations,
            'blocked'    => $blocked,
        ];
    }

    /**
     * Determine whether a given aggregate risk level should trigger a block.
     *
     * @param string $risklevel  Result risk level from analyze().
     * @param string $blocklevel Configured minimum level to block.
     * @return bool
     */
    public static function should_block(string $risklevel, string $blocklevel = 'high'): bool {
        $order = [
            self::RISK_NONE     => 0,
            self::RISK_LOW      => 1,
            self::RISK_MEDIUM   => 2,
            self::RISK_HIGH     => 3,
            self::RISK_CRITICAL => 4,
        ];
        $riskscore  = $order[$risklevel]  ?? 0;
        $blockscore = $order[$blocklevel] ?? 3; // default to 'high' score
        return $riskscore > 0 && $riskscore >= $blockscore;
    }

    // -------------------------------------------------------------------------
    // Rule definitions
    // -------------------------------------------------------------------------

    /**
     * Return the security rules appropriate for the given programming language.
     *
     * Each rule is an associative array with keys:
     *   id      - unique rule identifier
     *   pattern - PCRE regex matched against a single source line
     *   message - human-readable Indonesian explanation
     *   risk    - one of the RISK_* constants
     *
     * @param string $language
     * @return array
     */
    private static function get_rules(string $language): array {
        switch (strtolower(trim($language))) {
            case 'javascript':
            case 'js':
                return self::rules_javascript();
            case 'python':
            case 'python3':
            case 'py':
                return self::rules_python();
            case 'java':
                return self::rules_java();
            case 'c':
            case 'cpp':
            case 'c++':
                return self::rules_cpp();
            default:
                return [];
        }
    }

    /**
     * Security rules for JavaScript / Node.js.
     *
     * @return array
     */
    private static function rules_javascript(): array {
        return [
            [
                'id'      => 'js_eval',
                'pattern' => '/\beval\s*\(/',
                'message' => 'eval() dapat mengeksekusi kode JavaScript arbitrer — pola berbahaya.',
                'risk'    => self::RISK_HIGH,
            ],
            [
                'id'      => 'js_function_constructor',
                'pattern' => '/\bnew\s+Function\s*\(/',
                'message' => 'new Function() berperilaku seperti eval() dan dapat mengeksekusi kode arbitrer.',
                'risk'    => self::RISK_HIGH,
            ],
            [
                'id'      => 'js_proto_pollution',
                'pattern' => '/__proto__\s*[=\[]/',
                'message' => 'Modifikasi __proto__ dapat menyebabkan prototype pollution.',
                'risk'    => self::RISK_HIGH,
            ],
            [
                'id'      => 'js_constructor_prototype',
                'pattern' => '/\.constructor\s*\[/',
                'message' => 'Manipulasi constructor melalui bracket notation terdeteksi.',
                'risk'    => self::RISK_HIGH,
            ],
            [
                'id'      => 'js_process_access',
                'pattern' => '/\bprocess\s*\.\s*(env|exit|kill|binding|dlopen|mainModule)\b/',
                'message' => 'Akses ke objek process Node.js (env/exit/kill) dapat membahayakan server.',
                'risk'    => self::RISK_CRITICAL,
            ],
            [
                'id'      => 'js_require_child_process',
                'pattern' => '/require\s*\(\s*[\'"]child_process[\'"]/',
                'message' => 'Import child_process memungkinkan eksekusi perintah sistem operasi.',
                'risk'    => self::RISK_CRITICAL,
            ],
            [
                'id'      => 'js_exec_sync',
                'pattern' => '/\bexecSync\s*\(/',
                'message' => 'execSync() menjalankan perintah shell secara sinkron.',
                'risk'    => self::RISK_CRITICAL,
            ],
            [
                'id'      => 'js_spawn_sync',
                'pattern' => '/\bspawnSync\s*\(/',
                'message' => 'spawnSync() meluncurkan proses baru secara sinkron.',
                'risk'    => self::RISK_CRITICAL,
            ],
            [
                'id'      => 'js_require_fs',
                'pattern' => '/require\s*\(\s*[\'"]fs[\'"]/',
                'message' => 'Akses modul filesystem (fs) tidak diizinkan dalam lingkungan ini.',
                'risk'    => self::RISK_HIGH,
            ],
            [
                'id'      => 'js_require_os',
                'pattern' => '/require\s*\(\s*[\'"]os[\'"]/',
                'message' => 'Akses modul os tidak diizinkan.',
                'risk'    => self::RISK_HIGH,
            ],
            [
                'id'      => 'js_require_net',
                'pattern' => '/require\s*\(\s*[\'"]net[\'"]/',
                'message' => 'Akses modul net (raw socket) tidak diizinkan.',
                'risk'    => self::RISK_HIGH,
            ],
            [
                'id'      => 'js_require_http',
                'pattern' => '/require\s*\(\s*[\'"]https?[\'"]/',
                'message' => 'Modul http/https untuk permintaan jaringan tidak diizinkan.',
                'risk'    => self::RISK_HIGH,
            ],
            [
                'id'      => 'js_fetch_external',
                'pattern' => '/\bfetch\s*\(\s*[\'"]https?:\/\/(?!localhost|127\.0\.0\.1)/',
                'message' => 'Permintaan jaringan ke URL eksternal tidak diizinkan.',
                'risk'    => self::RISK_HIGH,
            ],
            [
                'id'      => 'js_xmlhttp_external',
                'pattern' => '/\.open\s*\(\s*[\'"](?:GET|POST|PUT|DELETE|PATCH)[\'"],\s*[\'"]https?:\/\/(?!localhost|127\.0\.0\.1)/',
                'message' => 'XMLHttpRequest ke server eksternal tidak diizinkan.',
                'risk'    => self::RISK_HIGH,
            ],
            [
                'id'      => 'js_websocket_external',
                'pattern' => '/\bnew\s+WebSocket\s*\(\s*[\'"]wss?:\/\/(?!localhost|127\.0\.0\.1)/',
                'message' => 'Koneksi WebSocket ke server eksternal tidak diizinkan.',
                'risk'    => self::RISK_HIGH,
            ],
            [
                'id'      => 'js_atob_eval',
                'pattern' => '/\batob\b[^;]*\beval\b|\beval\b[^;]*\batob\b/',
                'message' => 'Kombinasi atob() + eval() adalah pola obfuskasi umum — potensi eksekusi kode tersembunyi.',
                'risk'    => self::RISK_CRITICAL,
            ],
            [
                'id'      => 'js_fromcharcode_obfuscation',
                'pattern' => '/String\.fromCharCode\s*\((?:[^)]+,\s*){5,}/',
                'message' => 'Kemungkinan obfuskasi kode menggunakan String.fromCharCode berulang.',
                'risk'    => self::RISK_MEDIUM,
            ],
            [
                'id'      => 'js_innerhtml_assign',
                'pattern' => '/\.innerHTML\s*=(?!=)/',
                'message' => 'Penugasan innerHTML langsung dapat memicu XSS jika konten berasal dari input tidak tepercaya.',
                'risk'    => self::RISK_LOW,
            ],
            [
                'id'      => 'js_document_write',
                'pattern' => '/document\s*\.\s*write\s*\(/',
                'message' => 'document.write() dapat merusak DOM dan memicu XSS.',
                'risk'    => self::RISK_LOW,
            ],
        ];
    }

    /**
     * Security rules for Python 3.
     *
     * @return array
     */
    private static function rules_python(): array {
        return [
            [
                'id'      => 'py_eval',
                'pattern' => '/\beval\s*\(/',
                'message' => 'eval() dapat mengeksekusi kode Python arbitrer.',
                'risk'    => self::RISK_HIGH,
            ],
            [
                'id'      => 'py_exec',
                'pattern' => '/\bexec\s*\(/',
                'message' => 'exec() dapat mengeksekusi kode Python arbitrer.',
                'risk'    => self::RISK_HIGH,
            ],
            [
                'id'      => 'py_import_os',
                'pattern' => '/^\s*import\s+os\b/',
                'message' => 'Modul os memberikan akses langsung ke sistem operasi.',
                'risk'    => self::RISK_HIGH,
            ],
            [
                'id'      => 'py_from_os',
                'pattern' => '/^\s*from\s+os\b/',
                'message' => 'Import dari modul os tidak diizinkan.',
                'risk'    => self::RISK_HIGH,
            ],
            [
                'id'      => 'py_import_subprocess',
                'pattern' => '/^\s*import\s+subprocess\b/',
                'message' => 'subprocess memungkinkan eksekusi perintah sistem operasi.',
                'risk'    => self::RISK_CRITICAL,
            ],
            [
                'id'      => 'py_from_subprocess',
                'pattern' => '/^\s*from\s+subprocess\b/',
                'message' => 'Import dari subprocess tidak diizinkan.',
                'risk'    => self::RISK_CRITICAL,
            ],
            [
                'id'      => 'py_dunder_import',
                'pattern' => '/__import__\s*\(/',
                'message' => '__import__() dapat mem-bypass pembatasan import normal.',
                'risk'    => self::RISK_CRITICAL,
            ],
            [
                'id'      => 'py_import_pickle',
                'pattern' => '/^\s*import\s+pickle\b/',
                'message' => 'pickle dapat mengeksekusi kode arbitrer saat deserialisasi.',
                'risk'    => self::RISK_CRITICAL,
            ],
            [
                'id'      => 'py_import_ctypes',
                'pattern' => '/^\s*import\s+ctypes\b/',
                'message' => 'ctypes memungkinkan akses memori tingkat rendah.',
                'risk'    => self::RISK_CRITICAL,
            ],
            [
                'id'      => 'py_import_sys',
                'pattern' => '/^\s*import\s+sys\b/',
                'message' => 'Modul sys memberikan akses ke interpreter dan lingkungan Python.',
                'risk'    => self::RISK_MEDIUM,
            ],
            [
                'id'      => 'py_open_write',
                'pattern' => '/\bopen\s*\([^)]+,\s*[\'"][wa+][\'"]/',
                'message' => 'Membuka file dengan mode tulis (w/a) tidak diizinkan.',
                'risk'    => self::RISK_HIGH,
            ],
            [
                'id'      => 'py_import_socket',
                'pattern' => '/^\s*import\s+socket\b/',
                'message' => 'Akses jaringan via socket tidak diizinkan.',
                'risk'    => self::RISK_HIGH,
            ],
            [
                'id'      => 'py_import_urllib',
                'pattern' => '/^\s*(?:import\s+urllib|from\s+urllib)\b/',
                'message' => 'Akses jaringan via urllib tidak diizinkan.',
                'risk'    => self::RISK_HIGH,
            ],
            [
                'id'      => 'py_import_requests',
                'pattern' => '/^\s*import\s+requests\b/',
                'message' => 'Akses jaringan via requests tidak diizinkan.',
                'risk'    => self::RISK_HIGH,
            ],
            [
                'id'      => 'py_globals_locals',
                'pattern' => '/\b(?:globals|locals)\s*\(\s*\)/',
                'message' => 'globals()/locals() dapat memperlihatkan variabel lingkungan Python.',
                'risk'    => self::RISK_MEDIUM,
            ],
            [
                'id'      => 'py_getattr_dunder',
                'pattern' => '/getattr\s*\([^,]+,\s*[\'"]__/',
                'message' => 'Akses atribut dunder via getattr() bisa mem-bypass pembatasan kelas.',
                'risk'    => self::RISK_MEDIUM,
            ],
        ];
    }

    /**
     * Security rules for Java.
     *
     * @return array
     */
    private static function rules_java(): array {
        return [
            [
                'id'      => 'java_runtime_exec',
                'pattern' => '/Runtime\s*\.\s*getRuntime\s*\(\s*\)\s*\.\s*exec\s*\(/',
                'message' => 'Runtime.exec() dapat menjalankan perintah sistem operasi.',
                'risk'    => self::RISK_CRITICAL,
            ],
            [
                'id'      => 'java_processbuilder',
                'pattern' => '/\bnew\s+ProcessBuilder\s*\(/',
                'message' => 'ProcessBuilder dapat meluncurkan proses eksternal.',
                'risk'    => self::RISK_CRITICAL,
            ],
            [
                'id'      => 'java_system_exit',
                'pattern' => '/\bSystem\s*\.\s*exit\s*\(/',
                'message' => 'System.exit() dapat menghentikan seluruh JVM.',
                'risk'    => self::RISK_HIGH,
            ],
            [
                'id'      => 'java_reflection_declared',
                'pattern' => '/\.getDeclaredMethod\s*\(|\.getDeclaredField\s*\(/',
                'message' => 'getDeclared*() via refleksi dapat mem-bypass akses private.',
                'risk'    => self::RISK_MEDIUM,
            ],
            [
                'id'      => 'java_class_forname',
                'pattern' => '/\bClass\s*\.\s*forName\s*\(/',
                'message' => 'Class.forName() via refleksi dapat memuat kelas sewenang-wenang.',
                'risk'    => self::RISK_MEDIUM,
            ],
            [
                'id'      => 'java_file_write',
                'pattern' => '/\bnew\s+FileWriter\s*\(|\bnew\s+FileOutputStream\s*\(/',
                'message' => 'Penulisan ke filesystem tidak diizinkan.',
                'risk'    => self::RISK_HIGH,
            ],
            [
                'id'      => 'java_network_socket',
                'pattern' => '/\bnew\s+Socket\s*\(|\bServerSocket\b|\bHttpURLConnection\b/',
                'message' => 'Akses jaringan (socket/HTTP) tidak diizinkan.',
                'risk'    => self::RISK_HIGH,
            ],
        ];
    }

    /**
     * Security rules for C / C++.
     *
     * @return array
     */
    private static function rules_cpp(): array {
        return [
            [
                'id'      => 'cpp_system',
                'pattern' => '/\bsystem\s*\(/',
                'message' => 'system() dapat menjalankan perintah shell sewenang-wenang.',
                'risk'    => self::RISK_CRITICAL,
            ],
            [
                'id'      => 'cpp_popen',
                'pattern' => '/\bpopen\s*\(/',
                'message' => 'popen() dapat menjalankan perintah shell dan membaca outputnya.',
                'risk'    => self::RISK_CRITICAL,
            ],
            [
                'id'      => 'cpp_exec_family',
                'pattern' => '/\bexecvp?\s*\(|\bexecle\s*\(|\bexecve\s*\(|\bexeclp\s*\(|\bexecl\s*\(/',
                'message' => 'Fungsi exec*() menggantikan proses saat ini dengan proses baru.',
                'risk'    => self::RISK_CRITICAL,
            ],
            [
                'id'      => 'cpp_fork',
                'pattern' => '/\bfork\s*\(\s*\)/',
                'message' => 'fork() dapat membuat proses anak baru.',
                'risk'    => self::RISK_HIGH,
            ],
            [
                'id'      => 'cpp_fopen_write',
                'pattern' => '/\bfopen\s*\([^,]+,\s*[\'"][wa+][\'"]/',
                'message' => 'fopen() dengan mode tulis tidak diizinkan.',
                'risk'    => self::RISK_HIGH,
            ],
            [
                'id'      => 'cpp_ofstream',
                'pattern' => '/\bofstream\b/',
                'message' => 'ofstream (file output stream) tidak diizinkan.',
                'risk'    => self::RISK_HIGH,
            ],
            [
                'id'      => 'cpp_network_socket',
                'pattern' => '/\bsocket\s*\(\s*AF_INET|\bconnect\s*\([^,]+,\s*\(struct\s+sockaddr/',
                'message' => 'Pembuatan socket jaringan tidak diizinkan.',
                'risk'    => self::RISK_HIGH,
            ],
        ];
    }

    // -------------------------------------------------------------------------
    // Structural checks
    // -------------------------------------------------------------------------

    /**
     * Detect excessive block-nesting depth in C-style languages.
     *
     * Deep nesting (> 8 levels) may indicate obfuscated infinite loops
     * or other resource-exhaustion patterns.
     *
     * @param string $code
     * @param string $language
     * @return array|null Violation array or null when not applicable / not exceeded.
     */
    private static function check_nesting_depth(string $code, string $language): ?array {
        $cstyled = ['javascript', 'js', 'java', 'c', 'cpp', 'c++'];
        if (!in_array(strtolower(trim($language)), $cstyled, true)) {
            return null;
        }

        $maxdepth     = 0;
        $currentdepth = 0;
        $maxline      = 1;

        foreach (explode("\n", $code) as $lineno => $line) {
            // Strip single-line comments.
            $stripped = (string) preg_replace('/\/\/.*$/', '', $line);
            // Strip basic string literals to avoid counting braces inside them.
            $stripped = (string) preg_replace('/([\'"`])(?:(?!\1)[^\\\\]|\\\\.)*\1/', '""', $stripped);

            $currentdepth += substr_count($stripped, '{') - substr_count($stripped, '}');

            if ($currentdepth > $maxdepth) {
                $maxdepth = $currentdepth;
                $maxline  = $lineno + 1;
            }
        }

        if ($maxdepth > 8) {
            return [
                'rule'    => 'excessive_nesting',
                'line'    => $maxline,
                'match'   => "nesting depth: {$maxdepth}",
                'message' => "Blok kode terlalu dalam ({$maxdepth} level). Kemungkinan loop bersarang yang berlebihan.",
                'risk'    => self::RISK_MEDIUM,
            ];
        }

        return null;
    }

    // -------------------------------------------------------------------------
    // Risk aggregation
    // -------------------------------------------------------------------------

    /**
     * Compute the highest risk level across all violations.
     *
     * @param array $violations
     * @return string One of the RISK_* constants.
     */
    private static function aggregate_risk(array $violations): string {
        if (empty($violations)) {
            return self::RISK_NONE;
        }

        $order = [
            self::RISK_LOW      => 1,
            self::RISK_MEDIUM   => 2,
            self::RISK_HIGH     => 3,
            self::RISK_CRITICAL => 4,
        ];

        $maxscore = 0;
        $maxrisk  = self::RISK_LOW;

        foreach ($violations as $v) {
            $score = $order[$v['risk']] ?? 0;
            if ($score > $maxscore) {
                $maxscore = $score;
                $maxrisk  = $v['risk'];
            }
        }

        return $maxrisk;
    }
}
