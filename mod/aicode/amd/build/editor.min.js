// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Textarea editor integration for AICode module
 *
 * @module     mod_aicode/editor
 * @copyright  2025 AICode Team
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(["jquery", "core/ajax", "core/notification", "core/modal_factory", "core/modal_events"],
function ($, Ajax, Notification, ModalFactory, ModalEvents) {
  let editor = null;
  let config = {};
  let cachedFeedback = null;
  let lastAnalysisInput = null;
  let lastRunWasSuccess = false;
  let aiFeedbackPromise = null;
  let aiFeedbackRequestId = 0;
  let hintRequested = false;
  let previewFrame = null;
  let fallbackEditor = null;
  let lineNumberPane = null;
  let lineNumbersReady = false;
  let highlightPane = null;
  let highlightCode = null;
  let highlightReady = false;
  let handlersReady = false;
  let historyDrawer = null;
  let historyBackdrop = null;
  let historyList = null;
  let historyItems = [];
  let expandedHistoryIndex = null;

  // Problem panel state
  let problemStats = { error: 0, warning: 0, info: 0 };
  let previewLineOffset = 71;

  // VS Code–style problem icons (inline SVG)
  const PROB_ICON_ERROR = (
    '<svg width="14" height="14" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">' +
    '<circle cx="8" cy="8" r="7.5" fill="#f14c4c"/>' +
    '<line x1="5.5" y1="5.5" x2="10.5" y2="10.5" stroke="white" stroke-width="1.6" stroke-linecap="round"/>' +
    '<line x1="10.5" y1="5.5" x2="5.5" y2="10.5" stroke="white" stroke-width="1.6" stroke-linecap="round"/>' +
    '</svg>'
  );
  const PROB_ICON_WARN = (
    '<svg width="14" height="14" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">' +
    '<path d="M8 2L14.5 13.5H1.5L8 2Z" fill="#cca700"/>' +
    '<line x1="8" y1="7" x2="8" y2="10.5" stroke="white" stroke-width="1.6" stroke-linecap="round"/>' +
    '<circle cx="8" cy="12.2" r="0.85" fill="white"/>' +
    '</svg>'
  );
  const PROB_ICON_INFO = (
    '<svg width="14" height="14" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">' +
    '<circle cx="8" cy="8" r="7.5" fill="#3794ff"/>' +
    '<line x1="8" y1="7.5" x2="8" y2="11.5" stroke="white" stroke-width="1.6" stroke-linecap="round"/>' +
    '<circle cx="8" cy="5.5" r="0.9" fill="white"/>' +
    '</svg>'
  );
  const PROB_ICON_LOG = (
    '<svg width="14" height="14" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">' +
    '<circle cx="8" cy="8" r="7.5" fill="#6c757d"/>' +
    '<path d="M5.5 8h5M8.5 5.5l3 2.5-3 2.5" stroke="white" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/>' +
    '</svg>'
  );

  /**
   * Initialize editor and panels
   */
  const initEditor = function () {
    previewFrame = document.getElementById("aicode-preview-iframe");
    attachPreviewListener();
    initFallbackEditor();
    initHistoryPanel();
    setupEventHandlers();
  };

  /**
   * Resolve course-module id from the current page URL (?id=N)
   * @return {number|null}
   */
  const getCmIdFromUrl = function () {
    try {
      const params = new URLSearchParams(window.location.search);
      const id = parseInt(params.get("id"), 10);
      return Number.isInteger(id) && id > 0 ? id : null;
    } catch (e) {
      return null;
    }
  };

  /**
   * Resolve problem id from config or DOM
   * @return {number|null}
   */
  const getProblemId = function () {
    const configured = Number.parseInt(config.problemId, 10);
    if (Number.isInteger(configured) && configured > 0) {
      return configured;
    }

    const fallback = getFallbackProblemId();
    if (Number.isInteger(fallback) && fallback > 0) {
      config.problemId = fallback;
      return fallback;
    }

    const dataValue = getProblemIdFromData();
    if (Number.isInteger(dataValue) && dataValue > 0) {
      config.problemId = dataValue;
      return dataValue;
    }

    return null;
  };

  /**
   * Fallback for missing problem id in AMD config
   * @return {number|null}
   */
  const getFallbackProblemId = function () {
    const input = document.getElementById("aicode-problemid");
    if (!input) {
      return null;
    }
    const value = Number.parseInt(input.value, 10);
    return Number.isInteger(value) ? value : null;
  };

  /**
   * Fallback for missing problem id from data container
   * @return {number|null}
   */
  const getProblemIdFromData = function () {
    const data = document.getElementById("aicode-data");
    if (!data || !data.dataset || !data.dataset.problemid) {
      return null;
    }
    const value = Number.parseInt(data.dataset.problemid, 10);
    return Number.isInteger(value) ? value : null;
  };

  /**
   * Get activity mode from config or DOM
   * @return {string} "training" or "exam"
   */
  const getModeFromData = function () {
    const data = document.getElementById("aicode-data");
    if (!data || !data.dataset || !data.dataset.mode) {
      return "training";
    }
    return data.dataset.mode || "training";
  };

  /**
   * Resolve current activity mode
   * @return {string}
   */
  const getMode = function () {
    if (config.mode) {
      return config.mode;
    }
    const mode = getModeFromData();
    config.mode = mode;
    return mode;
  };

  /**
   * Whether current user is a teacher (can view attempts)
   * @return {boolean}
   */
  const isTeacher = function () {
    if (typeof config.isTeacher === "boolean") {
      return config.isTeacher;
    }
    const data = document.getElementById("aicode-data");
    if (data && data.dataset && typeof data.dataset.isteacher !== "undefined") {
      config.isTeacher = data.dataset.isteacher === "1";
      return config.isTeacher;
    }
    config.isTeacher = false;
    return false;
  };

  /**
   * Whether we are in exam mode for a student (no AI feedback)
   * @return {boolean}
   */
  const isExamModeForStudent = function () {
    return getMode() === "exam" && !isTeacher();
  };

  /**
   * Fallback for missing language in AMD config
   * @return {string}
   */
  const getFallbackLanguage = function () {
    const input = document.getElementById("aicode-language");
    if (!input) {
      return "javascript";
    }
    return input.value || "javascript";
  };

  /**
   * Fallback for missing language from data container
   * @return {string}
   */
  const getLanguageFromData = function () {
    const data = document.getElementById("aicode-data");
    if (!data || !data.dataset || !data.dataset.language) {
      return "javascript";
    }
    return data.dataset.language || "javascript";
  };

  /**
   * Resolve language from config or DOM
   * @return {string}
   */
  const getLanguage = function () {
    if (config.language) {
      return config.language;
    }
    const fallback = getFallbackLanguage() || getLanguageFromData();
    config.language = fallback;
    return fallback;
  };

  /**
   * Normalize starter code to a safe string
   * @param {*} raw
   * @return {string}
   */
  const normalizeStarterCode = function (raw) {
    if (raw === undefined || raw === null) {
      return "";
    }
    if (typeof raw === "string") {
      const trimmed = raw.trim();
      if (!trimmed) {
        return "";
      }
      const lowered = trimmed.toLowerCase();
      if (lowered === "undefined" || lowered === "null") {
        return "";
      }
      return raw;
    }
    const stringified = String(raw);
    const normalized = stringified.trim().toLowerCase();
    if (!normalized || normalized === "undefined" || normalized === "null") {
      return "";
    }
    return stringified;
  };

  /**
   * Resolve starter code safely
   * @return {string}
   */
  const getStarterCode = function () {
    let raw = config.starterCode;
    if (raw === undefined || raw === null || (typeof raw === "string" && !raw.trim())) {
      const rawEl = document.getElementById("aicode-starter-code-raw");
      if (rawEl && rawEl.value !== undefined) {
        raw = rawEl.value;
      } else {
        const data = document.getElementById("aicode-data");
        if (data && data.dataset && data.dataset.startercode) {
          raw = data.dataset.startercode;
        }
      }
    }
    return normalizeStarterCode(raw);
  };

  /**
   * Get HTML template from config or DOM
   * @return {string}
   */
  const getHtmlTemplate = function () {
    if (config.htmlTemplate) {
      return config.htmlTemplate;
    }
    const raw = document.getElementById("aicode-html-template-raw");
    if (raw && raw.value !== undefined) {
      config.htmlTemplate = raw.value;
      return raw.value;
    }
    const data = document.getElementById("aicode-data");
    if (data && data.dataset && data.dataset.htmltemplate) {
      config.htmlTemplate = data.dataset.htmltemplate;
      return data.dataset.htmltemplate;
    }
    return "";
  };

  /**
   * Get CSS template from config or DOM
   * @return {string}
   */
  const getCssTemplate = function () {
    if (config.cssTemplate) {
      return config.cssTemplate;
    }
    const raw = document.getElementById("aicode-css-template-raw");
    if (raw && raw.value !== undefined) {
      config.cssTemplate = raw.value;
      return raw.value;
    }
    const data = document.getElementById("aicode-data");
    if (data && data.dataset && data.dataset.csstemplate) {
      config.cssTemplate = data.dataset.csstemplate;
      return data.dataset.csstemplate;
    }
    return "";
  };

  /**
   * Resolve sesskey from config or DOM/global
   * @return {string|null}
   */
  const getSesskey = function () {
    if (config.sesskey) {
      return config.sesskey;
    }
    if (window.M && window.M.cfg && window.M.cfg.sesskey) {
      config.sesskey = window.M.cfg.sesskey;
      return config.sesskey;
    }
    const input = document.getElementById("aicode-sesskey");
    if (input && input.value) {
      config.sesskey = input.value;
      return config.sesskey;
    }
    const data = document.getElementById("aicode-data");
    if (data && data.dataset && data.dataset.sesskey) {
      config.sesskey = data.dataset.sesskey;
      return config.sesskey;
    }
    const field = document.querySelector('input[name="sesskey"]');
    if (field && field.value) {
      config.sesskey = field.value;
      return config.sesskey;
    }
    return "";
  };

  /**
   * Setup button event handlers
   */
  const setupEventHandlers = function () {
    if (handlersReady) {
      return;
    }
    handlersReady = true;
    $("#aicode-run-btn").on("click", handleRun);
    $("#aicode-history-btn").on("click", toggleHistoryPanel);
    $("#aicode-history-close").on("click", hideHistoryPanel);
    $("#aicode-history-backdrop").on("click", hideHistoryPanel);
    $("#aicode-hint-btn").on("click", handleHint);
    $("#aicode-reset-btn").on("click", handleReset);
    $("#aicode-submit-btn").on("click", handleSendToTeacher);
    $(document).on("click", ".aicode-history-item", handleHistoryItemClick);
    $(document).on("click", ".aicode-problem-item[data-line]", handleProblemItemClick);

  };
  /**
   * Apply UI changes based on mode and role
   */
  const applyModeSettings = function () {
    if (isExamModeForStudent()) {
      // Hide hint button entirely in exam mode for students.
      $("#aicode-hint-btn").hide();
    }
  };

  /**
   * Attach listener for preview iframe console/error messages
   */
  const attachPreviewListener = function () {
    window.addEventListener("message", function (event) {
      if (!previewFrame || event.source !== previewFrame.contentWindow) {
        return;
      }

      const data = event.data || {};
      if (data.source !== "aicode-preview") {
        return;
      }

      if (data.type === "console") {
        const level = data.payload && data.payload.level ? data.payload.level : "log";
        const args = data.payload && data.payload.args ? data.payload.args : [];
        const message = args.join(" ");
        if (level === "warn" || level === "error") {
          addProblem(level === "warn" ? "warning" : "error", message, null, null);
        } else {
          addOutput(level, message);
        }
      }

      if (data.type === "error") {
        const message = data.payload && data.payload.message ? data.payload.message : "Kesalahan";
        const stack = data.payload && data.payload.stack ? data.payload.stack : "";
        const loc = parseStackLocation(stack || message, true);
        addProblem("error", message, loc ? loc.line : null, loc ? loc.col : null);
      }
    });
  };

  /**
   * Handle Run button click
   */
  const handleRun = function () {
    const code = getEditorValue();
    const problemId = getProblemId();
    const language = getLanguage();
    const sesskey = getSesskey();
    const examModeForStudent = isExamModeForStudent();

    if (!problemId) {
      Notification.alert("ID soal tidak ditemukan", "Silakan muat ulang halaman lalu coba lagi.");
      return;
    }

    // In exam mode for students, Run is only for local preview/error checking.
    // Submission is handled by the dedicated Submit button.
    if (examModeForStudent) {
      if (!code || !String(code).trim()) {
        Notification.alert("Kode kosong", "Silakan tulis kode terlebih dahulu sebelum menjalankan.");
        return;
      }
    }
    // Clear previous output
    resetPanels();
    $("#aicode-feedback").html("");
    resetAIFeedbackState();
    cachedFeedback = null;
    lastAnalysisInput = null;
    lastRunWasSuccess = false;

    // Pre-filter for obvious errors
    if (preFilterCode(code)) {
      return; // Already handled locally
    }

    addHistoryEntry(code);

    // Render preview
    runPreview(getHtmlTemplate(), getCssTemplate(), code);

    // Send to backend
    const promise = Ajax.call([
      {
        methodname: "mod_aicode_run_code",
        args: {
          problemid: problemId,
          code: code,
          language: language,
          sesskey: sesskey || "",
        },
      },
    ])[0];

    promise
      .then(function (response) {
        let result = null;
        try {
          result = response && typeof response.result === "string" ? JSON.parse(response.result) : null;
        } catch (e) {
          result = null;
        }

        if (!result) {
          addProblem("error", "Eksekusi gagal: tidak ada hasil dari layanan eksekutor.", null, null);
          return true;
        }

        // Security block from server-side security checker — do NOT trigger AI analysis.
        if (result.security_blocked === true) {
          var riskLabels = { critical: "Kritis", high: "Tinggi", medium: "Sedang", low: "Rendah" };
          var riskLabel  = riskLabels[result.risk_level] || "Terdeteksi";
          if (Array.isArray(result.violations) && result.violations.length) {
            result.violations.forEach(function (v) {
              var sev = (v.risk === "critical" || v.risk === "high") ? "error" : "warning";
              addProblem(sev, "[Keamanan] " + escapeHtml(v.message), v.line > 0 ? v.line : null, null);
            });
          } else {
            addProblem("error", "[Keamanan] Kode diblokir karena mengandung pola berbahaya.", null, null);
          }
          $("#aicode-feedback").html(
            '<div class="alert alert-danger">' +
            "<strong>🔒 Kode Diblokir — Risiko Keamanan " + escapeHtml(riskLabel) + "</strong>" +
            "<p>Kode kamu mengandung operasi yang tidak diizinkan dan <strong>tidak dieksekusi</strong>. " +
            "Lihat panel <strong>MASALAH</strong> di bawah untuk detail setiap pelanggaran.</p>" +
            "<p class=\"mb-0\"><small>Hapus pola berbahaya lalu coba jalankan lagi.</small></p>" +
            "</div>"
          );
          return true;
        }

        // Display executor output only on failure (avoid noisy test logs).
        if (result.stderr || result.exitCode !== 0) {
          if (result.stdout) {
            const outText = String(result.stdout).trim();
            const outFirst = outText.split("\n")[0];
            const outLoc = parseStackLocation(outText, false);
            addProblem("error", outFirst, outLoc ? outLoc.line : null, outLoc ? outLoc.col : null);
          }
          if (result.stderr) {
            const errText = String(result.stderr).trim();
            const errFirst = errText.split("\n")[0];
            const errLoc = parseStackLocation(errText, false);
            addProblem("error", errFirst, errLoc ? errLoc.line : null, errLoc ? errLoc.col : null);
          }
        }

        // Save latest analysis payload from the most recent run.
        const payload = {
          code: code,
          stderr: result.stderr || "",
          trace: result.trace || "",
        };
        lastAnalysisInput = payload;

        if (result.stderr || result.exitCode !== 0) {
          // Code has errors - start AI feedback analysis
          cachedFeedback = null;
          lastRunWasSuccess = false;
          if (!examModeForStudent) {
            startAIFeedbackAnalysis(payload);
          }
        } else if (!examModeForStudent) {
          // Code ran successfully without errors
          lastRunWasSuccess = true;
          $("#aicode-feedback").html('<div class="alert alert-success">✓ Kode berhasil dijalankan!</div>');
        }

        return true;
      })
      .catch(Notification.exception);
  };

  /**
   * Clear console and error panels
   */
  const resetPanels = function () {
    clearProblems();
    clearOutput();
  };

  /**
   * Append a console.log/info line to the Output panel
   * @param {string} level  "log" | "info"
   * @param {string} message
   */
  const addOutput = function (level, message) {
    if (!message) {
      return;
    }
    const panel = document.getElementById("aicode-output-panel");
    const list  = document.getElementById("aicode-output-list");
    const countEl = document.getElementById("aicode-output-count");
    if (!panel || !list) {
      return;
    }
    panel.style.display = "block";
    if (countEl) {
      const n = (parseInt(countEl.dataset.count || "0", 10) || 0) + 1;
      countEl.dataset.count = String(n);
      countEl.textContent = n + " baris";
    }
    const el = document.createElement("div");
    el.className = "aicode-output-line" + (level === "info" ? " aicode-output-info" : "");
    el.textContent = message;
    list.appendChild(el);
    list.scrollTop = list.scrollHeight;
  };

  /**
   * Clear the Output panel and hide it
   */
  const clearOutput = function () {
    const panel   = document.getElementById("aicode-output-panel");
    const list    = document.getElementById("aicode-output-list");
    const countEl = document.getElementById("aicode-output-count");
    if (panel) {
      panel.style.display = "none";
    }
    if (list) {
      list.innerHTML = "";
    }
    if (countEl) {
      countEl.dataset.count = "0";
      countEl.textContent = "";
    }
  };

  /**
   * Append a console-level message as a problem item
   * @param {string} line  format: "[level] message"
   */
  const appendConsole = function (line) {
    if (!line) {
      return;
    }
    const m = String(line).match(/^\[(\w+)\]\s*([\s\S]*)/);
    if (m) {
      const level = m[1].toLowerCase();
      const msg = m[2];
      const type = level === "warn" ? "warning"
        : level === "error" ? "error"
        : level === "info"  ? "info"
        : "log";
      addProblem(type, msg, null, null);
    } else {
      addProblem("log", line, null, null);
    }
  };

  /**
   * Append an error message, parsing type and location from the text
   * @param {string} line
   */
  const appendError = function (line) {
    if (!line) {
      return;
    }
    const text = String(line);

    // Executor output: "[executor] ..."
    if (text.startsWith("[executor] ")) {
      const body = text.slice("[executor] ".length).trim();
      const firstLine = body.split("\n")[0];
      const loc = parseStackLocation(body, false);
      addProblem("error", firstLine, loc ? loc.line : null, loc ? loc.col : null);
      return;
    }

    // Console-level prefix: "[warn] ...", "[log] ...", etc.
    const cm = text.match(/^\[(\w+)\]\s*([\s\S]*)/);
    if (cm) {
      appendConsole(text);
      return;
    }

    // Runtime error possibly with stack trace
    const parts = text.split("\n");
    const msg = parts[0];
    const stack = parts.slice(1).join("\n");
    const loc = parseStackLocation(stack || text, true);
    addProblem("error", msg, loc ? loc.line : null, loc ? loc.col : null);
  };

  /**
   * Escape closing script tags to prevent breaking out of <script>
   * @param {string} code
   * @return {string}
   */
  const escapeClosingScriptTags = function (code) {
    return String(code ?? "").replace(/<\/script/gi, "<\\/script");
  };

  /**
   * Best-effort loop protector for common patterns
   * @param {string} js
   * @return {string}
   */
  const addBasicLoopProtector = function (js) {
    const src = String(js ?? "");
    return src
      .replace(/for\s*\([^)]*\)\s*\{/g, (m) => `${m}\n__loopProtect();`)
      .replace(/while\s*\([^)]*\)\s*\{/g, (m) => `${m}\n__loopProtect();`)
      .replace(/do\s*\{/g, (m) => `${m}\n__loopProtect();`);
  };

  /**
   * Build iframe srcdoc for preview
   * @param {string} html
   * @param {string} css
   * @param {string} js
   * @return {string}
   */
  const buildSrcDoc = function (html, css, js) {
    const safeJs = escapeClosingScriptTags(addBasicLoopProtector(js));

    return `<!doctype html>
<html>
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <style>${css ?? ""}</style>
  </head>
  <body>
    ${html ?? ""}
    <script>
      (function () {
        function post(type, payload) {
          try {
            parent.postMessage({ source: "aicode-preview", type: type, payload: payload }, "*");
          } catch (e) {}
        }

        var __getById = document.getElementById.bind(document);
        document.getElementById = function (id) {
          var el = __getById(id);
          if (!el && id) {
            el = document.createElement("div");
            el.id = id;
            el.style.display = "none";
            document.body.appendChild(el);
            post("console", { level: "warn", args: ["Element #" + id + " not found. Placeholder created."] });
          }
          return el;
        };
        var __querySelector = document.querySelector.bind(document);
        document.querySelector = function (selector) {
          var el = __querySelector(selector);
          if (!el && selector && selector.charAt(0) === "#") {
            var id = selector.slice(1);
            el = document.createElement("div");
            el.id = id;
            el.style.display = "none";
            document.body.appendChild(el);
            post("console", { level: "warn", args: ["Element " + selector + " not found. Placeholder created."] });
          }
          return el;
        };

        ["log", "info", "warn", "error"].forEach(function (level) {
          var original = console[level];
          console[level] = function () {
            try {
              post("console", { level: level, args: Array.prototype.slice.call(arguments).map(String) });
            } catch (e) {}
            if (original) {
              original.apply(console, arguments);
            }
          };
        });

        window.addEventListener("error", function (e) {
          post("error", { message: e.message || "Kesalahan", stack: e.error && e.error.stack ? e.error.stack : "" });
        });
        window.addEventListener("unhandledrejection", function (e) {
          var reason = e && e.reason ? e.reason : "Unhandled promise rejection";
          post("error", { message: reason.message ? reason.message : String(reason), stack: reason.stack ? reason.stack : "" });
        });

        var __lpStart = Date.now();
        window.__loopProtect = function () {
          if (Date.now() - __lpStart > 500) {
            throw new Error("Loop protector: kemungkinan infinite loop (stop setelah 500ms).");
          }
        };

        try {
          ${safeJs}
        } catch (err) {
          post("error", { message: err && err.message ? err.message : String(err), stack: err && err.stack ? err.stack : "" });
        }
      })();
    </script>
  </body>
</html>`;
  };

  /**
   * Run preview in sandboxed iframe
   * @param {string} html
   * @param {string} css
   * @param {string} js
   */
  const runPreview = function (html, css, js) {
    if (!previewFrame) {
      return;
    }
    // Show preview panel only when an HTML template is provided.
    // For console-only exercises (no HTML template) keep it hidden; the iframe
    // still runs in the background so console.log messages reach the Output panel.
    const previewPanel = document.querySelector(".aicode-preview-panel");
    if (previewPanel) {
      previewPanel.style.display = (html && html.trim()) ? "block" : "none";
    }
    // Compute how many boilerplate lines precede user code inside the srcdoc template,
    // so stack-trace line numbers can be mapped back to user code lines.
    // Fixed boilerplate = 71 lines (with single-line CSS and HTML).
    // Each extra CSS/HTML line adds 1 to the offset.
    const cssLineCount  = String(css  || "").split("\n").length;
    const htmlLineCount = String(html || "").split("\n").length;
    previewLineOffset = 69 + cssLineCount + htmlLineCount;
    previewFrame.srcdoc = buildSrcDoc(html, css, js);
  };

  /**
   * Initialize history panel and load existing entries
   */
  const initHistoryPanel = function () {
    historyDrawer = document.getElementById("aicode-history-drawer");
    historyBackdrop = document.getElementById("aicode-history-backdrop");
    historyList = document.getElementById("aicode-history-list");
    historyItems = loadHistory();
    renderHistory();
    loadHistoryFromDB();
  };

  /**
   * Load run history from DB and merge with sessionStorage
   */
  const loadHistoryFromDB = function () {
    const problemId = getProblemId();
    if (!problemId) {
      return;
    }
    Ajax.call([
      {
        methodname: "mod_aicode_get_run_history",
        args: {
          problemid: problemId,
          sesskey: getSesskey() || "",
        },
      },
    ])[0]
      .then(function (response) {
        var dbHistory;
        try {
          dbHistory = JSON.parse(response.history);
        } catch (e) {
          return true;
        }
        if (!Array.isArray(dbHistory) || !dbHistory.length) {
          return true;
        }

        // Extract code strings from DB history (oldest-first, already sorted).
        var dbCodes = dbHistory
          .map(function (h) { return String(h.code || "").trim(); })
          .filter(Boolean);

        // Merge: DB history is authoritative; append any session-only items not yet in DB.
        var merged = dbCodes.slice();
        historyItems.forEach(function (item) {
          var trimmed = String(item || "").trim();
          if (trimmed && merged.indexOf(trimmed) === -1) {
            merged.push(trimmed);
          }
        });

        merged = merged.slice(-20);
        historyItems = merged;
        saveHistory(historyItems);
        renderHistory();
        return true;
      })
      .catch(function () {
        // Silently ignore — sessionStorage history still works.
      });
  };

  /**
   * Get storage key for history – scoped to the current exercise.
   * Uses problemId as primary key, cmId (course-module id) as secondary,
   * and the URL ?id= parameter as a final fallback so that history is
   * never accidentally shared across different activities.
   * @return {string}
   */
  const getHistoryKey = function () {
    const pid = Number.parseInt(config.problemId, 10);
    const cid = Number.parseInt(config.cmId, 10);
    if (Number.isInteger(pid) && pid > 0) {
      return `aicode_history_p${pid}`;
    }
    if (Number.isInteger(cid) && cid > 0) {
      return `aicode_history_cm${cid}`;
    }
    const urlCmId = getCmIdFromUrl();
    if (urlCmId) {
      return `aicode_history_cm${urlCmId}`;
    }
    return "aicode_history_unknown";
  };

  /**
   * Load history from sessionStorage
   * @return {Array}
   */
  const loadHistory = function () {
    try {
      const raw = sessionStorage.getItem(getHistoryKey());
      const parsed = raw ? JSON.parse(raw) : [];
      return Array.isArray(parsed) ? parsed : [];
    } catch (e) {
      return [];
    }
  };

  /**
   * Save history to sessionStorage
   * @param {Array} items
   */
  const saveHistory = function (items) {
    try {
      sessionStorage.setItem(getHistoryKey(), JSON.stringify(items));
    } catch (e) {
      // Ignore storage errors for session-only history.
    }
  };

  /**
   * Add a history entry for the current run
   * @param {string} code
   */
  const addHistoryEntry = function (code) {
    const trimmed = String(code || "").trim();
    if (!trimmed) {
      return;
    }

    const last = historyItems.length ? historyItems[historyItems.length - 1] : "";
    if (last === trimmed) {
      return;
    }

    historyItems.push(trimmed);
    if (historyItems.length > 20) {
      historyItems = historyItems.slice(-20);
    }
    saveHistory(historyItems);
    renderHistory();
  };

  /**
   * Render history list
   */
  const renderHistory = function () {
    if (!historyList) {
      return;
    }
    historyList.innerHTML = "";

    if (expandedHistoryIndex !== null) {
      if (expandedHistoryIndex < 0 || expandedHistoryIndex >= historyItems.length) {
        expandedHistoryIndex = null;
      }
    }

    if (!historyItems.length) {
      const empty = document.createElement("div");
      empty.className = "aicode-history-empty";
      empty.textContent = "Belum ada riwayat.";
      historyList.appendChild(empty);
      return;
    }

    historyItems.forEach(function (item, index) {
      const entry = document.createElement("div");
      entry.className = "aicode-history-item";
      if (index === expandedHistoryIndex) {
        entry.classList.add("is-expanded");
      }
      entry.dataset.index = String(index);
      const isExpanded = index === expandedHistoryIndex;
      const content = isExpanded ? String(item || "") : buildHistoryPreview(item);
      const safeContent = escapeHtml(content || "(kosong)");
      const number = index + 1;
      entry.innerHTML =
        `<div class="aicode-history-meta">` +
        `<span class="aicode-history-index">${number}</span>` +
        `<span class="aicode-history-label">Riwayat ${number}</span>` +
        `</div>` +
        `<pre class="aicode-history-code">${safeContent}</pre>`;
      historyList.appendChild(entry);
    });
  };

  /**
   * Build preview text for a history item
   * @param {string} code
   * @return {string}
   */
  const buildHistoryPreview = function (code) {
    const firstLine = String(code).split("\n")[0] || "";
    const snippet = firstLine.length > 90 ? firstLine.slice(0, 90) + "…" : firstLine;
    return snippet || "(kosong)";
  };

  /**
   * Toggle history panel visibility
   */
  const toggleHistoryPanel = function () {
    if (!historyDrawer) {
      return;
    }
    if (historyDrawer.classList.contains("is-open")) {
      hideHistoryPanel();
    } else {
      showHistoryPanel();
    }
  };

  /**
   * Hide history panel
   */
  const hideHistoryPanel = function () {
    if (!historyDrawer) {
      return;
    }
    historyDrawer.classList.remove("is-open");
    if (historyBackdrop) {
      historyBackdrop.classList.remove("is-visible");
    }
  };

  /**
   * Show history panel
   */
  const showHistoryPanel = function () {
    if (!historyDrawer) {
      return;
    }
    historyDrawer.classList.add("is-open");
    if (historyBackdrop) {
      historyBackdrop.classList.add("is-visible");
    }
  };

  /**
   * Handle history item click
   * @param {Event} e
   */
  const handleHistoryItemClick = function (e) {
    const target = e.target && e.target.closest ? e.target.closest(".aicode-history-item") : null;
    const index = target && target.dataset ? Number(target.dataset.index) : NaN;
    if (!Number.isInteger(index) || !historyItems[index]) {
      return;
    }
    if (expandedHistoryIndex !== index) {
      expandedHistoryIndex = index;
      renderHistory();
      return;
    }
    setEditorValue(historyItems[index]);
    // Show confirmation popup when history code is applied to editor.
    Notification.alert("Riwayat dimuat", "Kode dari riwayat berhasil ditampilkan di editor.");
  };

  /**
   * Pre-filter code for obvious errors
   * @param {string} code
   * @return {boolean} true if handled locally
   */
  const preFilterCode = function (code) {
    // Check for syntax errors using a simple heuristic
    const suspiciousPatterns = [
      /child_process/,
      /spawn/,
      /execSync/,
      /fs\.writeFileSync/,
      /process\.exit/,
      /require\(['"]net['"]\)/,
      /fetch\(['"]http/,
    ];

    for (let pattern of suspiciousPatterns) {
      if (pattern.test(code)) {
        addProblem("error", "Pola kode mencurigakan atau tidak diizinkan terdeteksi.", null, null);
        $("#aicode-feedback").html(
          '<div class="alert alert-danger">⚠ Kode kamu mengandung operasi yang berpotensi tidak aman.</div>'
        );
        return true;
      }
    }

    return false;
  };

  /**
   * Build Recommended Materials HTML section
   * @param {Array} materials
   * @return {string}
   */
  const buildRecommendedMaterialsHtml = function (materials) {
    if (!Array.isArray(materials) || !materials.length) {
      return "";
    }

    let html = '<div class="mt-2"><strong>Materi Rekomendasi:</strong><ul>';
    materials.forEach(function (material) {
      if (!material || !material.title) {
        return;
      }
      const title = escapeHtml(String(material.title));
      const url = material.url ? String(material.url) : "";
      const reason = material.reason ? escapeHtml(String(material.reason)) : "";

      if (url) {
        const safeUrl = escapeHtml(url);
        html += `<li><a href="${safeUrl}" target="_blank" rel="noopener noreferrer">${title}</a>`;
      } else {
        html += `<li>${title}`;
      }

      if (reason) {
        html += ` - ${reason}`;
      }

      html += "</li>";
    });
    html += "</ul></div>";
    return html;
  };

  /**
   * Record hint usage
   * @param {number} problemId
   * @param {string} sesskey
   */
  const recordHintUsage = function (problemId, sesskey) {
    Ajax.call([
      {
        methodname: "mod_aicode_record_hint",
        args: {
          problemid: problemId,
          sesskey: sesskey || "",
        },
      },
    ])[0].catch(function () {
      return false;
    });
  };

  /**
   * Build standardized AI failure payload for UI
   * @param {string} reason
   * @param {string} code
   * @return {object}
   */
  const buildUnavailableFeedback = function (reason, code) {
    const cleanReason = String(reason || "").trim() || "Feedback AI belum tersedia saat ini.";
    return {
      status: "error",
      error: {
        code: String(code || "feedback_unavailable"),
        message: "Feedback AI gagal diberikan saat ini.",
        reason: cleanReason,
        action: "Silakan klik tombol Bantuan lagi beberapa saat lagi.",
      },
      explainability: cleanReason,
    };
  };

  /**
   * Parse provider or ajax error into readable reason
   * @param {*} error
   * @return {string}
   */
  const extractFeedbackFailureReason = function (error) {
    if (!error) {
      return "Permintaan analisis AI gagal diproses.";
    }
    if (typeof error === "string") {
      return error;
    }
    if (error.message) {
      return String(error.message);
    }
    if (error.error && error.error.message) {
      return String(error.error.message);
    }
    if (error.error && error.error.error) {
      return String(error.error.error);
    }
    return "Permintaan analisis AI gagal diproses.";
  };

  /**
   * Parse feedback response without creating diagnosis fallback
   * @param {object} response
   * @return {object}
   */
  const parseAIFeedbackResponse = function (response) {
    let feedback = null;
    try {
      feedback = JSON.parse(response.feedback);
    } catch (e) {
      return buildUnavailableFeedback("Format respons AI tidak valid.", "invalid_json");
    }

    if (!feedback || typeof feedback !== "object") {
      return buildUnavailableFeedback("Respons AI kosong atau tidak dapat dibaca.", "empty_response");
    }

    if (feedback.status === "error" || feedback.error) {
      const reason = feedback.error && feedback.error.reason ? feedback.error.reason : feedback.explainability;
      return buildUnavailableFeedback(reason, feedback.error && feedback.error.code ? feedback.error.code : "ai_error");
    }

    if (!feedback.diagnosis) {
      return buildUnavailableFeedback("Respons AI tidak memuat diagnosis yang diperlukan.", "missing_diagnosis");
    }

    feedback.status = "success";
    return feedback;
  };

  /**
   * Toggle loading state on Hint button
   * @param {boolean} isLoading
   */
  const setHintButtonLoading = function (isLoading) {
    const $hintBtn = $("#aicode-hint-btn");
    if (!$hintBtn.length) {
      return;
    }
    const defaultLabel = $hintBtn.data("default-label") || $hintBtn.text() || "Bantuan";
    $hintBtn.data("default-label", defaultLabel);
    if (isLoading) {
      $hintBtn.prop("disabled", true);
      $hintBtn.text("AI sedang menganalisis...");
    } else {
      $hintBtn.prop("disabled", false);
      $hintBtn.text($hintBtn.data("default-label"));
    }
  };

  /**
   * Show loading notice while waiting for AI feedback
   */
  const showHintLoadingState = function () {
    const html =
      '<div class="alert alert-info aicode-hint-loading">' +
      "<strong>AI masih menganalisis error terbaru kamu.</strong>" +
      '<p class="mb-0">Mohon tunggu sebentar. Umpan balik akan muncul otomatis setelah siap.</p>' +
      "</div>";
    $("#aicode-feedback").html(html);
  };

  /**
   * Render AI feedback in a single panel
   * @param {object} feedback
   * @param {number} problemId
   * @param {string} sesskey
   */
  const renderHintFeedback = function (feedback, problemId, sesskey) {
    if (!feedback) {
      return;
    }
    if (feedback.status === "error" || feedback.error) {
      displayFeedbackUnavailable(feedback);
      hintRequested = false;
      return;
    }
    if (!feedback.diagnosis) {
      displayFeedbackUnavailable(buildUnavailableFeedback("Respons AI tidak memuat diagnosis.", "missing_diagnosis"));
      hintRequested = false;
      return;
    }
    displayFeedback(feedback);
    if (feedback.location && feedback.location.line) {
      highlightError(feedback.location);
    }
    recordHintUsage(problemId, sesskey);
    hintRequested = false;
  };

  /**
   * Reset async AI feedback state for new run/reset
   */
  const resetAIFeedbackState = function () {
    aiFeedbackRequestId += 1;
    aiFeedbackPromise = null;
    hintRequested = false;
    lastRunWasSuccess = false;
    setHintButtonLoading(false);
  };

  /**
   * Start AI feedback analysis in background
   * @param {object} payload
   * @return {Promise|null}
   */
  const startAIFeedbackAnalysis = function (payload) {
    const problemId = getProblemId();
    if (!problemId) {
      return null;
    }
    const normalizedPayload = {
      code: payload && payload.code ? payload.code : getEditorValue(),
      stderr: payload && payload.stderr ? payload.stderr : "",
      trace: payload && payload.trace ? payload.trace : "",
    };
    const sesskey = getSesskey();
    const requestId = ++aiFeedbackRequestId;
    const promise = Ajax.call([
      {
        methodname: "mod_aicode_analyze_code",
        args: {
          problemid: problemId,
          code: normalizedPayload.code,
          stderr: normalizedPayload.stderr,
          trace: normalizedPayload.trace,
          sesskey: sesskey || "",
        },
      },
    ])[0];

    aiFeedbackPromise = promise;
    const cleanupAIFeedbackRequest = function () {
      if (requestId !== aiFeedbackRequestId) {
        return;
      }
      aiFeedbackPromise = null;
      setHintButtonLoading(false);
      if (hintRequested && cachedFeedback) {
        renderHintFeedback(cachedFeedback, problemId, sesskey);
      }
    };
    promise
      .then(
        function (response) {
          if (requestId !== aiFeedbackRequestId) {
            return null;
          }
          const feedback = parseAIFeedbackResponse(response);
          cachedFeedback = feedback;
          return feedback;
        },
        function (error) {
          if (requestId !== aiFeedbackRequestId) {
            return null;
          }
          const reason = extractFeedbackFailureReason(error);
          const unavailable = buildUnavailableFeedback(
            reason + " Silakan klik tombol Bantuan lagi beberapa saat lagi.",
            "ajax_request_failed"
          );
          cachedFeedback = unavailable;
          return unavailable;
        }
      )
      .then(
        function () {
          cleanupAIFeedbackRequest();
        },
        function () {
          cleanupAIFeedbackRequest();
        }
      );
    return promise;
  };

  /**
   * Translate diagnosis category label for display
   * @param {string} category
   * @return {string}
   */
  const formatDiagnosisCategory = function (category) {
    const labels = {
      syntax: "Sintaks",
      runtime: "Runtime",
      logic: "Logika",
      style: "Gaya kode",
      security: "Keamanan",
      performance: "Performa",
    };
    const key = String(category || "runtime").toLowerCase();
    return labels[key] || category || "Runtime";
  };

  /**
   * Build HTML for AI performance metrics panel (collapsible)
   * @param {object} perf - The _performance object from feedback
   * @return {string}
   */
  const buildPerformancePanelHtml = function (perf) {
    if (!perf || typeof perf !== "object") {
      return "";
    }

    const perfId = "aicode-perf-" + Date.now();
    const latency = perf.latency_ms ? (perf.latency_ms / 1000).toFixed(2) + "s" : "—";
    const confidence = typeof perf.confidence === "number" ? Math.round(perf.confidence * 100) + "%" : "—";
    const confValue = typeof perf.confidence === "number" ? perf.confidence : 0;
    const confColor = confValue >= 0.7 ? "#198754" : confValue >= 0.4 ? "#ffc107" : "#dc3545";
    const fromCache = perf.from_cache ? "Ya ✓" : "Tidak";
    const cacheColor = perf.from_cache ? "#198754" : "#6c757d";
    const provider = escapeHtml(String(perf.provider || "—"));
    const model = escapeHtml(String(perf.model || "—"));
    const temperature = typeof perf.temperature === "number" ? perf.temperature.toFixed(1) : "—";
    const promptTokens = perf.prompt_tokens ? perf.prompt_tokens.toLocaleString() : "—";
    const responseTokens = perf.response_tokens ? perf.response_tokens.toLocaleString() : "—";
    const totalTokens = perf.total_tokens ? perf.total_tokens.toLocaleString() : "—";
    const timestamp = perf.timestamp
      ? new Date(perf.timestamp * 1000).toLocaleString("id-ID", { timeZone: "Asia/Jakarta" }) + " WIB"
      : "—";

    let html = '<details class="aicode-perf-details mt-2" style="border-top:1px solid rgba(0,0,0,.1);padding-top:8px;">';
    html += '<summary style="cursor:pointer;font-size:0.82em;color:#555;user-select:none;">';
    html += '<svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 16 16" fill="currentColor" '
        + 'style="vertical-align:-2px;margin-right:4px;opacity:.7;">'
        + '<path d="M8 4.754a3.246 3.246 0 1 0 0 6.492 3.246 3.246 0 0 0 0-6.492zM5.754 8a2.246 2.246 0 1 1 4.492 0 '
        + '2.246 2.246 0 0 1-4.492 0z"/>'
        + '<path d="M9.796 1.343c-.527-1.79-3.065-1.79-3.592 0l-.094.319a.873.873 0 0 1-1.255.52l-.292-.16c-1.64-.892-3.433'
        + '.902-1.793 1.793l.16.292a.873.873 0 0 1-.52 1.255l-.319.094c-1.79.527-1.79 3.065 0 3.592l.319.094a.873.873 0 0 1'
        + ' .52 1.255l-.16.292c-.892 1.64.901 3.434 1.793 1.793l.292-.16a.873.873 0 0 1 1.255.52l.094.319c.527 1.79 3.065 '
        + '1.79 3.592 0l.094-.319a.873.873 0 0 1 1.255-.52l.292.16c1.64.893 3.434-.902 1.793-1.793l-.16-.292a.873.873 0 0 1'
        + ' .52-1.255l.319-.094c1.79-.527 1.79-3.065 0-3.592l-.319-.094a.873.873 0 0 1-.52-1.255l.16-.292c.893-1.64-.902-3.433'
        + '-1.793-1.793l-.292.16a.873.873 0 0 1-1.255-.52l-.094-.319zm-2.633.283c.246-.835 1.428-.835 1.674 0l.094.319a1.873'
        + ' 1.873 0 0 0 2.693 1.115l.291-.16c.764-.415 1.6.42 1.184 1.185l-.159.292a1.873 1.873 0 0 0 1.116 2.692l.318.094c'
        + '.835.246.835 1.428 0 1.674l-.319.094a1.873 1.873 0 0 0-1.115 2.693l.16.291c.415.764-.42 1.6-1.185 1.184l-.291-.159'
        + 'a1.873 1.873 0 0 0-2.693 1.116l-.094.318c-.246.835-1.428.835-1.674 0l-.094-.319a1.873 1.873 0 0 0-2.692-1.115l-'
        + '.292.16c-.764.415-1.6-.42-1.184-1.185l.159-.291A1.873 1.873 0 0 0 1.945 8.93l-.319-.094c-.835-.246-.835-1.428 0-1'
        + '.674l.319-.094A1.873 1.873 0 0 0 3.06 4.377l-.16-.292c-.415-.764.42-1.6 1.185-1.184l.292.159a1.873 1.873 0 0 0 2'
        + '.692-1.115l.094-.319z"/></svg>';
    html += "Performa AI";
    html += "</summary>";
    html += '<div style="margin-top:8px;font-size:0.8em;">';
    html += '<table style="width:100%;border-collapse:collapse;">';

    const rows = [
      ["Provider", '<span style="font-weight:600;">' + provider + "</span>"],
      ["Model", '<code style="font-size:0.92em;background:#e9ecef;padding:1px 5px;border-radius:3px;">' + model + "</code>"],
      ["Latensi", perf.from_cache
        ? '<span style="color:' + cacheColor + ';font-weight:600;">Dari Cache</span>'
        : '<span style="font-weight:600;">' + escapeHtml(latency) + "</span>"],
      ["Kepercayaan", '<span style="color:' + confColor + ';font-weight:600;">' + escapeHtml(confidence) + "</span>"],
      ["Token Prompt", escapeHtml(promptTokens)],
      ["Token Respons", escapeHtml(responseTokens)],
      ["Total Token", escapeHtml(totalTokens)],
      ["Temperature", escapeHtml(temperature)],
      ["Dari Cache", '<span style="color:' + cacheColor + ';font-weight:600;">' + escapeHtml(fromCache) + "</span>"],
      ["Waktu", escapeHtml(timestamp)],
    ];

    rows.forEach(function (row) {
      html += '<tr style="border-bottom:1px solid rgba(0,0,0,.06);">'
        + '<td style="padding:3px 6px 3px 0;color:#666;white-space:nowrap;width:130px;">' + row[0] + "</td>"
        + '<td style="padding:3px 0;">' + row[1] + "</td></tr>";
    });

    html += "</table></div></details>";
    return html;
  };

  /**
   * Display AI feedback
   * @param {object} feedback
   */
  const displayFeedback = function (feedback) {
    const diagnosis = feedback && feedback.diagnosis ? feedback.diagnosis : {};
    let html = '<div class="alert alert-info">';
    html += `<h5>Diagnosis AI: ${escapeHtml(formatDiagnosisCategory(diagnosis.category))}</h5>`;
    html += `<p><strong>${escapeHtml(String(diagnosis.message_short || "Terjadi kesalahan."))}</strong></p>`;
    html += `<p>${escapeHtml(String(diagnosis.message_long || ""))}</p>`;

    if (feedback && Array.isArray(feedback.hints) && feedback.hints.length) {
      html += '<div class="mt-2"><strong>Petunjuk:</strong><ul class="mb-1">';
      feedback.hints.forEach(function (hint) {
        const hintText = typeof hint === "string" ? hint : (hint && hint.hint ? hint.hint : "");
        if (hintText) {
          html += `<li>${escapeHtml(hintText)}</li>`;
        }
      });
      html += "</ul></div>";
    }

    if (feedback && feedback.suggested_fix && feedback.suggested_fix.explanation) {
      html += '<div class="mt-2"><strong>Saran Perbaikan:</strong><br>';
      html += escapeHtml(String(feedback.suggested_fix.explanation));
      if (feedback.suggested_fix.code_patch) {
        html += '<pre class="mt-1" style="background:#f0f0f0;padding:5px;">';
        html += escapeHtml(String(feedback.suggested_fix.code_patch));
        html += "</pre>";
      }
      html += "</div>";
    }

    html += buildRecommendedMaterialsHtml(feedback ? feedback.recommended_materials : []);

    if (feedback && feedback.explainability) {
      html += `<p class="mt-2 mb-0"><small>${escapeHtml(String(feedback.explainability))}</small></p>`;
    }

    // AI Performance panel (collapsible).
    if (feedback && feedback._performance) {
      html += buildPerformancePanelHtml(feedback._performance);
    }

    html += "</div>";
    $("#aicode-feedback").html(html);
  };

  /**
   * Display AI feedback failure
   * @param {object} feedback
   */
  const displayFeedbackUnavailable = function (feedback) {
    const error = feedback && feedback.error ? feedback.error : {};
    const message = String(error.message || "Feedback AI gagal diberikan saat ini.");
    const reason = String(error.reason || feedback.explainability || "Penyebab tidak tersedia.");
    const action = String(error.action || "Silakan klik tombol Bantuan lagi beberapa saat lagi.");

    let html = '<div class="alert alert-warning">';
    html += "<h5>Feedback AI Belum Tersedia</h5>";
    html += `<p><strong>${escapeHtml(message)}</strong></p>`;
    html += `<p class="mb-1">Alasan: ${escapeHtml(reason)}</p>`;
    html += `<p class="mb-0">${escapeHtml(action)}</p>`;
    html += "</div>";
    $("#aicode-feedback").html(html);
  };

  /**
   * Highlight error location in editor
   * @param {object} location
   */
  const highlightError = function (location) {
    if (!location || !location.line) {
      return;
    }
    highlightEditorLine(location.line);
  };

  /**
   * Determine if a char can start an identifier
   * @param {string} ch
   * @return {boolean}
   */
  const isIdentifierStart = function (ch) {
    return /[A-Za-z_$]/.test(ch);
  };

  /**
   * Determine if a char can be part of an identifier
   * @param {string} ch
   * @return {boolean}
   */
  const isIdentifierPart = function (ch) {
    return /[A-Za-z0-9_$]/.test(ch);
  };

  /**
   * Highlight JavaScript source for the preview layer
   * @param {string} code
   * @return {string}
   */
  const highlightJavascript = function (code) {
    const keywords = new Set([
      "break",
      "case",
      "catch",
      "class",
      "const",
      "continue",
      "debugger",
      "default",
      "delete",
      "do",
      "else",
      "export",
      "extends",
      "finally",
      "for",
      "function",
      "if",
      "import",
      "in",
      "instanceof",
      "let",
      "new",
      "return",
      "super",
      "switch",
      "this",
      "throw",
      "try",
      "typeof",
      "var",
      "void",
      "while",
      "with",
      "yield",
      "await",
    ]);
    const booleans = new Set(["true", "false"]);
    const nullish = new Set(["null", "undefined", "NaN", "Infinity"]);
    const builtins = new Set([
      "Array",
      "Boolean",
      "Date",
      "JSON",
      "Math",
      "Number",
      "Object",
      "Promise",
      "RegExp",
      "String",
      "Map",
      "Set",
      "WeakMap",
      "WeakSet",
      "console",
      "document",
      "window",
      "setTimeout",
      "setInterval",
      "clearTimeout",
      "clearInterval",
      "parseInt",
      "parseFloat",
      "isNaN",
      "isFinite",
    ]);

    const wrap = function (type, text) {
      return `<span class="tok tok-${type}">${escapeHtml(text)}</span>`;
    };

    let out = "";
    let i = 0;
    const len = code.length;

    while (i < len) {
      const ch = code[i];
      const next = code[i + 1];

      if (ch === "/" && next === "/") {
        let end = code.indexOf("\n", i);
        if (end === -1) {
          end = len;
        }
        out += wrap("comment", code.slice(i, end));
        i = end;
        continue;
      }

      if (ch === "/" && next === "*") {
        let end = code.indexOf("*/", i + 2);
        if (end === -1) {
          end = len - 2;
        }
        end += 2;
        out += wrap("comment", code.slice(i, end));
        i = end;
        continue;
      }

      if (ch === "'" || ch === '"') {
        const quote = ch;
        let j = i + 1;
        let escaped = false;
        while (j < len) {
          const curr = code[j];
          if (escaped) {
            escaped = false;
            j += 1;
            continue;
          }
          if (curr === "\\") {
            escaped = true;
            j += 1;
            continue;
          }
          if (curr === quote) {
            j += 1;
            break;
          }
          j += 1;
        }
        out += wrap("string", code.slice(i, j));
        i = j;
        continue;
      }

      if (ch === "`") {
        let j = i + 1;
        let escaped = false;
        while (j < len) {
          const curr = code[j];
          if (escaped) {
            escaped = false;
            j += 1;
            continue;
          }
          if (curr === "\\") {
            escaped = true;
            j += 1;
            continue;
          }
          if (curr === "`") {
            j += 1;
            break;
          }
          j += 1;
        }
        out += wrap("string", code.slice(i, j));
        i = j;
        continue;
      }

      if (/[0-9]/.test(ch)) {
        let j = i;
        if (ch === "0" && /[xXbBoO]/.test(code[j + 1] || "")) {
          j += 2;
          while (/[0-9a-fA-F_]/.test(code[j] || "")) {
            j += 1;
          }
        } else {
          while (/[0-9_]/.test(code[j] || "")) {
            j += 1;
          }
          if (code[j] === ".") {
            j += 1;
            while (/[0-9_]/.test(code[j] || "")) {
              j += 1;
            }
          }
          if (/[eE]/.test(code[j] || "")) {
            j += 1;
            if (/[+-]/.test(code[j] || "")) {
              j += 1;
            }
            while (/[0-9_]/.test(code[j] || "")) {
              j += 1;
            }
          }
        }
        out += wrap("number", code.slice(i, j));
        i = j;
        continue;
      }

      if (isIdentifierStart(ch)) {
        let j = i + 1;
        while (j < len && isIdentifierPart(code[j])) {
          j += 1;
        }
        const word = code.slice(i, j);
        let type = "";
        if (keywords.has(word)) {
          type = "keyword";
        } else if (booleans.has(word)) {
          type = "boolean";
        } else if (nullish.has(word)) {
          type = "null";
        } else if (builtins.has(word)) {
          type = "builtin";
        } else {
          let k = j;
          while (k < len && /\s/.test(code[k])) {
            k += 1;
          }
          if (code[k] === "(") {
            type = "function";
          }
        }

        if (type) {
          out += wrap(type, word);
        } else {
          out += escapeHtml(word);
        }
        i = j;
        continue;
      }

      out += escapeHtml(ch);
      i += 1;
    }

    return out;
  };

  /**
   * Sync highlight scroll with textarea
   */
  const syncHighlightScroll = function () {
    if (!fallbackEditor || !highlightPane) {
      return;
    }
    highlightPane.scrollTop = fallbackEditor.scrollTop;
    highlightPane.scrollLeft = fallbackEditor.scrollLeft;
  };

  /**
   * Update highlight layer
   */
  const updateHighlight = function () {
    if (!fallbackEditor || !highlightCode) {
      return;
    }
    highlightCode.innerHTML = highlightJavascript(fallbackEditor.value || "");
    syncHighlightScroll();
  };

  /**
   * Initialize highlight layer
   */
  const initHighlight = function () {
    if (highlightReady) {
      return;
    }
    highlightPane = document.getElementById("aicode-highlight");
    highlightCode = document.getElementById("aicode-highlight-code");
    if (!highlightPane || !highlightCode || !fallbackEditor) {
      return;
    }
    highlightReady = true;
    updateHighlight();
    fallbackEditor.addEventListener("input", updateHighlight);
    fallbackEditor.addEventListener("scroll", syncHighlightScroll);
  };

  /**
   * Sync line numbers with textarea scroll
   */
  const syncLineNumbersScroll = function () {
    if (!fallbackEditor || !lineNumberPane) {
      return;
    }
    lineNumberPane.scrollTop = fallbackEditor.scrollTop;
  };

  /**
   * Update line numbers for the textarea
   */
  const updateLineNumbers = function () {
    if (!fallbackEditor || !lineNumberPane) {
      return;
    }
    const value = fallbackEditor.value || "";
    const lineCount = value.split("\n").length || 1;
    let numbers = "";
    for (let i = 1; i <= lineCount; i++) {
      numbers += i + "\n";
    }
    lineNumberPane.textContent = numbers;
    syncLineNumbersScroll();
  };

  /**
   * Initialize line numbers for the textarea
   */
  const initLineNumbers = function () {
    if (lineNumbersReady) {
      return;
    }
    lineNumberPane = document.getElementById("aicode-line-numbers");
    if (!lineNumberPane || !fallbackEditor) {
      return;
    }
    lineNumbersReady = true;
    updateLineNumbers();
    fallbackEditor.addEventListener("input", updateLineNumbers);
    fallbackEditor.addEventListener("scroll", syncLineNumbersScroll);
    fallbackEditor.addEventListener("input", function () {
      $("#aicode-active-line").hide();
      $(".aicode-problem-item").removeClass("is-active");
    });
  };

  /**
   * Initialize fallback textarea editor
   */
  const initFallbackEditor = function () {
    fallbackEditor = document.getElementById("aicode-fallback-editor");
    if (!fallbackEditor) {
      return;
    }
    if (!fallbackEditor.value) {
      fallbackEditor.value = getStarterCode();
    }
    fallbackEditor.style.display = "block";
    initLineNumbers();
    initHighlight();
    editor = {
      getValue: function () {
        return fallbackEditor.value;
      },
      setValue: function (value) {
        const safeValue = value === undefined || value === null ? "" : String(value);
        fallbackEditor.value = safeValue;
        updateLineNumbers();
        updateHighlight();
      },
    };
  };

  /**
   * Get editor value
   * @return {string}
   */
  const getEditorValue = function () {
    if (editor && typeof editor.getValue === "function") {
      return editor.getValue();
    }
    return "";
  };

  /**
   * Set editor value
   * @param {string} value
   */
  const setEditorValue = function (value) {
    if (editor && typeof editor.setValue === "function") {
      editor.setValue(value);
    }
  };

  /**
   * Clear editor error highlights
   */
  const clearEditorMarkers = function () {
    $("#aicode-active-line").hide();
    $(".aicode-problem-item").removeClass("is-active");
  };

  /**
   * Handle Hint button click
   */
  const handleHint = function () {
    const problemId = getProblemId();
    const sesskey = getSesskey();

    if (!problemId) {
      Notification.alert("ID soal tidak ditemukan", "Silakan muat ulang halaman lalu coba lagi.");
      return;
    }

    if (!lastAnalysisInput && !cachedFeedback && !aiFeedbackPromise) {
      Notification.alert("Petunjuk belum tersedia", "Jalankan kode terlebih dahulu agar AI dapat menganalisis.");
      return;
    }

    if (cachedFeedback && cachedFeedback.diagnosis) {
      renderHintFeedback(cachedFeedback, problemId, sesskey);
      return;
    }

    if (!lastAnalysisInput) {
      Notification.alert("Petunjuk belum tersedia", "Jalankan kode terlebih dahulu agar AI dapat menganalisis.");
      return;
    }

    // Check if code ran successfully without errors (fallback via lastAnalysisInput)
    const hasError = lastAnalysisInput.stderr && lastAnalysisInput.stderr.trim() !== "";
    const hasTrace = lastAnalysisInput.trace && lastAnalysisInput.trace.trim() !== "";

    if (!hasError && !hasTrace) {
      // Code ran without errors. Check if student actually wrote something vs starter code.
      const currentCode = String(lastAnalysisInput.code || getEditorValue() || "").trim();
      const starterCode = String(getStarterCode() || "").trim();

      if (!currentCode || (starterCode && currentCode === starterCode)) {
        // Student hasn't modified the starter code — prompt them to write their answer.
        Notification.alert(
          "Tulis jawaban kamu dulu",
          "Kamu belum mengubah kode dari template awal. Tuliskan jawaban kamu terlebih dahulu, " +
          "lalu klik Jalankan sebelum meminta bantuan AI."
        );
        return;
      }

      // Student wrote custom code that runs without errors.
      // Show neutral popup — no need to call AI for this case.
      Notification.alert(
        "\u2139\ufe0f Kode berjalan tanpa error",
        "Kode kamu berhasil dijalankan tanpa pesan error. " +
        "Namun pastikan output yang dihasilkan sudah sesuai dengan perintah soal sebelum mengumpulkan jawaban."
      );
      return;
    }

    hintRequested = true;
    showHintLoadingState();
    setHintButtonLoading(true);

    if (!aiFeedbackPromise) {
      const payload = {
        code: lastAnalysisInput.code || getEditorValue(),
        stderr: lastAnalysisInput.stderr || "",
        trace: lastAnalysisInput.trace || "",
      };
      startAIFeedbackAnalysis(payload);
    }
  };

  /**
   * Handle Reset button click
   */
  const handleReset = function () {
    if (confirm("Yakin ingin mengembalikan kode ke template awal?")) {
      setEditorValue(getStarterCode());
      resetPanels();
      $("#aicode-feedback").html("");
      resetAIFeedbackState();
      cachedFeedback = null;
      lastAnalysisInput = null;
      clearEditorMarkers();
      if (previewFrame) {
        previewFrame.srcdoc = "";
        const previewPanel = document.querySelector(".aicode-preview-panel");
        if (previewPanel) {
          const hasHtmlTpl = getHtmlTemplate() && getHtmlTemplate().trim();
          previewPanel.style.display = hasHtmlTpl ? "block" : "none";
        }
      }
    }
  };

  /**
   * Lock the editor after a successful exam submission
   * @param {string} code  The submitted code to display
   */
  const lockEditorAfterSubmit = function (code) {
    if (code !== undefined && code !== null && String(code).trim()) {
      setEditorValue(String(code));
    }

    if (fallbackEditor) {
      fallbackEditor.setAttribute("readonly", "readonly");
      fallbackEditor.style.cursor = "default";
      fallbackEditor.style.color = "#495057";
      fallbackEditor.style.caretColor = "transparent";
    }

    $("#aicode-submit-btn")
      .prop("disabled", true)
      .text("Sudah Dikumpulkan ✓")
      .removeClass("btn-info")
      .addClass("btn-success");
    $("#aicode-reset-btn").hide();
    $("#aicode-hint-btn").hide();

    if (!document.getElementById("aicode-submitted-banner")) {
      const banner = document.createElement("div");
      banner.id = "aicode-submitted-banner";
      banner.style.cssText =
        "margin-top:8px;padding:10px 14px;background:#d1e7dd;border:1px solid #a3cfbb;" +
        "border-radius:0.5rem;font-size:0.875rem;color:#0a3622;";
      banner.innerHTML =
        "<strong>✓ Jawaban kamu sudah dikumpulkan.</strong> " +
        "Editor sekarang bersifat <em>hanya baca</em> dan tidak dapat diubah lagi.";
      const controls = document.querySelector(".aicode-controls");
      if (controls && controls.parentNode) {
        controls.parentNode.insertBefore(banner, controls.nextSibling);
      }
    }
  };

  /**
   * Get submitted code from DOM hidden textarea
   * @return {string}
   */
  const getSubmittedCodeFromDom = function () {
    const el = document.getElementById("aicode-submitted-code-raw");
    return el ? (el.value || "") : "";
  };

  /**
   * Check if student has already submitted (from config or DOM)
   * @return {boolean}
   */
  const getHasSubmitted = function () {
    if (config.hasSubmitted === true) {
      return true;
    }
    const data = document.getElementById("aicode-data");
    if (data && data.dataset && data.dataset.hassubmitted === "1") {
      return true;
    }
    return false;
  };

  /**
   * Handle Send to Teacher button click
   */
  const handleSendToTeacher = function () {
    const code = getEditorValue();
    const problemId = getProblemId();
    const sesskey = getSesskey();

    if (!problemId) {
      Notification.alert("ID soal tidak ditemukan", "Silakan muat ulang halaman lalu coba lagi.");
      return;
    }

    const examMode = isExamModeForStudent();

    if (examMode && getHasSubmitted()) {
      Notification.alert(
        "Sudah Dikumpulkan",
        "Kamu hanya dapat mengumpulkan jawaban satu kali. Jawaban kamu sudah tercatat."
      );
      return;
    }

    if (examMode) {
      ModalFactory.create({
        type: ModalFactory.types.SAVE_CANCEL,
        title: "Konfirmasi Pengumpulan",
        body:
          "<p>Apakah kamu sudah yakin dengan jawaban kamu?</p>" +
          "<p class='mb-0'><strong>⚠ Setelah dikumpulkan, kode tidak dapat diubah atau dikirim ulang.</strong></p>",
      })
        .then(function (modal) {
          modal.setSaveButtonText("Ya, Kumpulkan");
          modal.show();
          modal.getRoot().on(ModalEvents.save, function () {
            doSubmit(code, problemId, sesskey, examMode);
          });
          modal.getRoot().on(ModalEvents.hidden, function () {
            modal.destroy();
          });
          return modal;
        })
        .catch(Notification.exception);
      return;
    }

    doSubmit(code, problemId, sesskey, examMode);
  };

  /**
   * Execute the actual AJAX submit call
   * @param {string} code
   * @param {number} problemId
   * @param {string} sesskey
   * @param {boolean} examMode
   */
  const doSubmit = function (code, problemId, sesskey, examMode) {
    const outputList = document.getElementById("aicode-output-list");
    const consoleOutput = outputList
      ? Array.from(outputList.querySelectorAll(".aicode-output-line"))
          .map(function (el) { return el.textContent || ""; })
          .join("\n")
      : "";

    Ajax.call([
      {
        methodname: "mod_aicode_send_to_teacher",
        args: {
          problemid: problemId,
          code: code,
          console_output: consoleOutput,
          sesskey: sesskey || "",
        },
      },
    ])[0]
      .then(function (response) {
        if (examMode) {
          if (response && response.already_submitted) {
            config.hasSubmitted = true;
            lockEditorAfterSubmit(code);
            Notification.alert(
              "Sudah Dikumpulkan",
              "Kamu hanya dapat mengumpulkan jawaban satu kali. Jawaban kamu sudah tercatat."
            );
            return true;
          }
          config.hasSubmitted = true;
          lockEditorAfterSubmit(code);
          Notification.alert(
            "Berhasil Dikumpulkan",
            "Jawaban kamu berhasil dikumpulkan. Kamu tidak dapat mengubah atau mengirim ulang jawaban."
          );
        } else {
          Notification.alert("Berhasil", "Kode kamu telah dikirim ke guru untuk ditinjau.");
        }
        return true;
      })
      .catch(Notification.exception);
  };

  /**
   * Escape HTML
   * @param {string} text
   * @return {string}
   */
  const escapeHtml = function (text) {
    const map = {
      "&": "&amp;",
      "<": "&lt;",
      ">": "&gt;",
      '"': "&quot;",
      "'": "&#039;",
    };
    return text.replace(/[&<>"']/g, function (m) {
      return map[m];
    });
  };

  /**
   * Update the count badges in the Problems panel header
   */
  const updateProblemBadges = function () {
    const $err  = $("#aicode-badge-error");
    const $warn = $("#aicode-badge-warn");
    const $info = $("#aicode-badge-info");
    const total = problemStats.error + problemStats.warning + problemStats.info;

    if (problemStats.error > 0) {
      $err.html(PROB_ICON_ERROR + " " + problemStats.error).show();
    } else {
      $err.hide();
    }
    if (problemStats.warning > 0) {
      $warn.html(PROB_ICON_WARN + " " + problemStats.warning).show();
    } else {
      $warn.hide();
    }
    if (problemStats.info > 0) {
      $info.html(PROB_ICON_LOG + " " + problemStats.info).show();
    } else {
      $info.hide();
    }

    const $fc = $("#aicode-problems-file-count");
    if (total > 0) {
      $fc.text(total + " masalah");
    } else {
      $fc.text("");
    }
  };

  /**
   * Clear all problem items and reset counts
   */
  const clearProblems = function () {
    problemStats = { error: 0, warning: 0, info: 0 };
    $("#aicode-errors").empty();
    $("#aicode-problems-group").hide();
    $("#aicode-problems-empty").addClass("is-visible");
    updateProblemBadges();
  };

  /**
   * Highlight a specific line in the fallback editor and scroll to it
   * @param {number} lineNumber  1-based user code line
   */
  const highlightEditorLine = function (lineNumber) {
    if (!fallbackEditor || !lineNumber || lineNumber < 1) {
      return;
    }

    const value = fallbackEditor.value || "";
    const lines = value.split("\n");
    const clampedLine = Math.min(lineNumber, lines.length);

    // Build char offset for the target line
    let charOffset = 0;
    for (let i = 0; i < clampedLine - 1; i++) {
      charOffset += lines[i].length + 1; // +1 for \n
    }
    const lineEnd = charOffset + (lines[clampedLine - 1] || "").length;

    // Select the entire line in the textarea
    fallbackEditor.focus();
    fallbackEditor.setSelectionRange(charOffset, lineEnd);

    // Get computed line height and padding
    const style = window.getComputedStyle(fallbackEditor);
    const lineH = parseFloat(style.lineHeight) || 20;
    const padTop = parseFloat(style.paddingTop) || 10;

    // Position the active-line highlight overlay
    const $hl = $("#aicode-active-line");
    if ($hl.length) {
      const topPx = padTop + (clampedLine - 1) * lineH;
      $hl.css({ top: topPx + "px", height: lineH + "px" }).show();
    }

    // Scroll editor to vertically center the target line
    const scrollTarget = Math.max(
      0,
      padTop + (clampedLine - 1) * lineH - fallbackEditor.clientHeight / 2 + lineH / 2
    );
    fallbackEditor.scrollTop = scrollTarget;
    syncHighlightScroll();
  };

  /**
   * Handle click on a problem item that has a line reference
   * @param {Event} e
   */
  const handleProblemItemClick = function (e) {
    const $item = $(e.currentTarget);
    const lineAttr = $item.attr("data-line");
    const lineNum = lineAttr ? parseInt(lineAttr, 10) : NaN;
    if (isNaN(lineNum) || lineNum < 1) {
      return;
    }
    // Mark this item as active, deactivate others
    $(".aicode-problem-item").removeClass("is-active");
    $item.addClass("is-active");
    highlightEditorLine(lineNum);
  };

  /**
   * Parse stack trace for line/column info
   * @param {string} stack
   * @param {boolean} isPreview - true if from srcdoc iframe
   * @return {{line: number, col: number}|null}
   */
  const parseStackLocation = function (stack, isPreview) {
    if (!stack) {
      return null;
    }
    if (isPreview) {
      const m = stack.match(/srcdoc:(\d+):(\d+)/);
      if (m) {
        const rawLine = parseInt(m[1], 10);
        const col = parseInt(m[2], 10);
        const userLine = Math.max(1, rawLine - previewLineOffset);
        return { line: userLine, col: col };
      }
    } else {
      const m = stack.match(/student_code\.js:(\d+):(\d+)/);
      if (m) {
        return { line: parseInt(m[1], 10), col: parseInt(m[2], 10) };
      }
    }
    return null;
  };

  /**
   * Add a single problem item to the Problems panel
   * @param {string} type  'error' | 'warning' | 'info' | 'log'
   * @param {string} message
   * @param {number|null} line
   * @param {number|null} col
   */
  const addProblem = function (type, message, line, col) {
    if (!message) {
      return;
    }

    $("#aicode-problems-empty").removeClass("is-visible");
    $("#aicode-problems-group").show();

    if (type === "error") {
      problemStats.error++;
    } else if (type === "warning") {
      problemStats.warning++;
    } else {
      problemStats.info++;
    }
    updateProblemBadges();

    const icon = type === "error"   ? PROB_ICON_ERROR
      : type === "warning" ? PROB_ICON_WARN
      : type === "info"    ? PROB_ICON_INFO
      : PROB_ICON_LOG;

    const locationHtml = (line !== null && line !== undefined && line > 0)
      ? '<span class="aicode-problem-location">[' + line + ', ' + (col || 1) + ']</span>'
      : "";

    const $item = $('<div class="aicode-problem-item aicode-problem-' + type + '"></div>');
    if (line !== null && line !== undefined && line > 0) {
      $item.attr("data-line", line);
    }
    $item.html(
      '<span class="aicode-problem-icon">' + icon + "</span>" +
      '<span class="aicode-problem-message">' + escapeHtml(String(message)) + "</span>" +
      locationHtml
    );
    $("#aicode-errors").append($item);
  };

  return {
    /**
     * Initialize the module
     * @param {object} cfg Configuration object
     */
    init: function (cfg) {
      config = cfg || {};
      config.problemId = config.problemId || config.problemid || getFallbackProblemId();
      config.cmId = config.cmId || config.cmid || getCmIdFromUrl();
      config.language = config.language || getFallbackLanguage();
      config.sesskey = config.sesskey || getSesskey();
      config.htmlTemplate = config.htmlTemplate || getHtmlTemplate();
      config.cssTemplate = config.cssTemplate || getCssTemplate();
      config.mode = config.mode || getModeFromData();
      if (typeof config.isTeacher === "undefined") {
        config.isTeacher = isTeacher();
      }
      if (typeof config.hasSubmitted === "undefined") {
        config.hasSubmitted = false;
      }
      $(document).ready(function () {
        initEditor();
        applyModeSettings();
        if (isExamModeForStudent() && getHasSubmitted()) {
          const submittedCode = getSubmittedCodeFromDom();
          lockEditorAfterSubmit(submittedCode);
        }
      });
    },
  };
});
