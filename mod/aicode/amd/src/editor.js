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

/* global monaco */

define(["jquery", "core/ajax", "core/notification"], function ($, Ajax, Notification) {
  let editor = null;
  let config = {};
  let cachedFeedback = null;
  let lastAnalysisInput = null;
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

  /**
   * Initialize textarea editor (no external library)
   */
  const initMonaco = function () {
    previewFrame = document.getElementById("aicode-preview-iframe");
    attachPreviewListener();
    initFallbackEditor();
    initHistoryPanel();
    setupEventHandlers();
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
        appendConsole(`[${level}] ${args.join(" ")}`.trim());
      }

      if (data.type === "error") {
        const message = data.payload && data.payload.message ? data.payload.message : "Error";
        const stack = data.payload && data.payload.stack ? data.payload.stack : "";
        appendError([message, stack].filter(Boolean).join("\n"));
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
      Notification.alert("Missing problem id", "Please reload the page and try again.");
      return;
    }

    // In exam mode for students, Run is only for local preview/error checking.
    // Submission is handled by the dedicated Submit button.
    if (examModeForStudent) {
      if (!code || !String(code).trim()) {
        Notification.alert("Empty code", "Please write your code before running.");
        return;
      }
    }
    // Clear previous output
    resetPanels();
    $("#aicode-feedback").html("");
    resetAIFeedbackState();
    cachedFeedback = null;
    lastAnalysisInput = null;

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
          appendError("Execution failed: empty result from executor.");
          return true;
        }

        // Display executor output only on failure (avoid noisy test logs).
        if (result.stderr || result.exitCode !== 0) {
          if (result.stdout) {
            appendError(`[executor] ${result.stdout}`.trim());
          }
          if (result.stderr) {
            appendError(`[executor] ${result.stderr}`.trim());
          }
        }

        // Save latest analysis payload from the most recent failed run.
        if (result.stderr || result.exitCode !== 0) {
          const payload = {
            code: code,
            stderr: result.stderr || "",
            trace: result.trace || "",
          };
          lastAnalysisInput = payload;
          cachedFeedback = null;
          if (!examModeForStudent) {
            startAIFeedbackAnalysis(payload);
          }
        } else if (!examModeForStudent) {
          $("#aicode-feedback").html('<div class="alert alert-success">✓ Code executed successfully!</div>');
        }

        return true;
      })
      .catch(Notification.exception);
  };

  /**
   * Clear console and error panels
   */
  const resetPanels = function () {
    $("#aicode-errors").text("");
  };

  /**
   * Append a line to console output
   * @param {string} line
   */
  const appendConsole = function (line) {
    if (!line) {
      return;
    }
    appendError(line);
  };

  /**
   * Append a line to error output
   * @param {string} line
   */
  const appendError = function (line) {
    if (!line) {
      return;
    }
    const current = $("#aicode-errors").text();
    $("#aicode-errors").text(current ? current + "\n" + line : line);
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
          post("error", { message: e.message || "Error", stack: e.error && e.error.stack ? e.error.stack : "" });
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
  };

  /**
   * Get storage key for history
   * @return {string}
   */
  const getHistoryKey = function () {
    return `aicode_history_${config.problemId || "default"}`;
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
      empty.textContent = "No history yet.";
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
      const safeContent = escapeHtml(content || "(empty)");
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
    return snippet || "(empty)";
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
        $("#aicode-errors").text("Error: Suspicious or disallowed code pattern detected.");
        $("#aicode-feedback").html('<div class="alert alert-danger">⚠ Your code contains potentially unsafe operations.</div>');
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

    let html = '<div class="mt-2"><strong>Recommended Materials:</strong><ul>';
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
   * Record hint usage (single detailed hint mode)
   * @param {number} problemId
   * @param {string} sesskey
   */
  const recordHintUsage = function (problemId, sesskey) {
    Ajax.call([
      {
        methodname: "mod_aicode_record_hint",
        args: {
          problemid: problemId,
          level: 1,
          sesskey: sesskey || "",
        },
      },
    ])[0].catch(function () {
      return false;
    });
  };

  /**
   * Parse feedback response and normalize fallback behavior
   * @param {object} response
   * @param {string} stderr
   * @return {object}
   */
  const parseAIFeedbackResponse = function (response, stderr) {
    let feedback = null;
    try {
      feedback = JSON.parse(response.feedback);
    } catch (e) {
      feedback = null;
    }
    if (!feedback || !feedback.diagnosis) {
      feedback = buildFallbackFeedback(stderr || "");
    }
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
    const defaultLabel = $hintBtn.data("default-label") || $hintBtn.text() || "Hint";
    $hintBtn.data("default-label", defaultLabel);
    if (isLoading) {
      $hintBtn.prop("disabled", true);
      $hintBtn.text("AI is analyzing...");
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
      "<strong>AI is still analyzing your latest error.</strong>" +
      '<p class="mb-0">Please wait a moment. Feedback will appear automatically when ready.</p>' +
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
    if (!feedback || !feedback.diagnosis) {
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
      if (hintRequested && cachedFeedback && cachedFeedback.diagnosis) {
        renderHintFeedback(cachedFeedback, problemId, sesskey);
      }
    };
    promise
      .then(
        function (response) {
          if (requestId !== aiFeedbackRequestId) {
            return null;
          }
          const feedback = parseAIFeedbackResponse(response, normalizedPayload.stderr);
          cachedFeedback = feedback;
          return feedback;
        },
        function () {
          if (requestId !== aiFeedbackRequestId) {
            return null;
          }
          const fallback = buildFallbackFeedback(normalizedPayload.stderr);
          cachedFeedback = fallback;
          return fallback;
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
   * Display AI feedback
   * @param {object} feedback
   */
  const displayFeedback = function (feedback) {
    const diagnosis = feedback && feedback.diagnosis ? feedback.diagnosis : {};
    let html = '<div class="alert alert-info">';
    html += `<h5>AI Diagnosis: ${escapeHtml(String(diagnosis.category || "runtime"))}</h5>`;
    html += `<p><strong>${escapeHtml(String(diagnosis.message_short || "An error occurred."))}</strong></p>`;
    html += `<p>${escapeHtml(String(diagnosis.message_long || ""))}</p>`;

    if (feedback && feedback.suggested_fix && feedback.suggested_fix.explanation) {
      html += '<div class="mt-2"><strong>Suggested Fix:</strong><br>';
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

    html += "</div>";
    $("#aicode-feedback").html(html);
  };

  /**
   * Build fallback AI feedback on the client
   * @param {string} stderr
   * @return {object}
   */
  const buildFallbackFeedback = function (stderr) {
    const message = String(stderr || "").trim();
    const fullMessage = message || "An error occurred during execution.";
    let firstLine = "";
    const lines = fullMessage.split("\n");
    for (let i = 0; i < lines.length; i++) {
      const trimmed = String(lines[i] || "").trim();
      if (!trimmed) {
        continue;
      }
      if (trimmed.indexOf("at ") === 0) {
        continue;
      }
      firstLine = trimmed;
      break;
    }

    let category = "runtime";
    let shortMessage = "A runtime error happened while your code was running.";
    let longMessage =
      "Your code runs, but it fails during execution. Focus on the first error line, " +
      "check variable values, and verify scope and data type at that point.";
    let hints = [
      "Read the first error line first, then inspect the related code block.",
      "Check variable names and values right before the failing line using console.log.",
      "Run your code in small steps so you can isolate where the wrong value appears.",
    ];
    let suggestedFix = {
      explanation: "Review the failing line and confirm every variable is declared and has the expected type before use.",
      code_patch: "console.log('debug value:', value);\n// Verify value exists and has the expected type before using it.",
    };
    let recommendedMaterials = [
      {
        title: "MDN JavaScript guide: Debugging",
        url: "https://developer.mozilla.org/en-US/docs/Learn_web_development/Core/Scripting/Debugging_JavaScript",
        reason: "Step-by-step debugging process for beginners.",
      },
      {
        title: "MDN console.log() reference",
        url: "https://developer.mozilla.org/en-US/docs/Web/API/console/log_static",
        reason: "Shows how to inspect values while your code runs.",
      },
    ];

    if (fullMessage.includes("SyntaxError")) {
      category = "syntax";
      shortMessage = "There is a syntax error in your code.";
      longMessage =
        "JavaScript cannot parse your code structure. This usually means missing or " +
        "extra brackets, commas, quotes, or parentheses.";
      hints = [
        "Check the line before the reported error because syntax issues often start earlier.",
        "Make sure each opening bracket, brace, parenthesis, and quote has a closing pair.",
        "Write shorter statements first, run again, then add complexity gradually.",
      ];
      suggestedFix = {
        explanation: "Fix unmatched symbols and split long expressions into smaller lines so parse errors are easier to detect.",
        code_patch: "if (condition) {\n  doSomething();\n}\n// Ensure brackets and punctuation are balanced.",
      };
      recommendedMaterials = [
        {
          title: "MDN SyntaxError reference",
          url: "https://developer.mozilla.org/en-US/docs/Web/JavaScript/Reference/Global_Objects/SyntaxError",
          reason: "Explains common syntax mistakes and how to fix them.",
        },
        {
          title: "JavaScript statements and declarations",
          url: "https://developer.mozilla.org/en-US/docs/Web/JavaScript/Reference/Statements",
          reason: "Helps you understand correct JavaScript statement structure.",
        },
      ];
    } else if (fullMessage.includes("ReferenceError") || /\bis not defined\b/i.test(fullMessage)) {
      category = "runtime";
      shortMessage = "A variable is used before it is declared or available in scope.";
      longMessage =
        "The runtime cannot find one of the variable names you are using. " +
        "This usually happens because of a typo, missing declaration, or scope mismatch.";
      hints = [
        "Declare variables with const or let before using them.",
        "Use exactly the same variable name everywhere because JavaScript is case-sensitive.",
        "If a variable is declared inside a function or block, it is not available outside that scope.",
      ];
      suggestedFix = {
        explanation:
          "Find the undefined variable in the error message, then declare it " +
          "before use or replace it with the correct existing variable name.",
        code_patch: "const numbers = [1, 2, 3];\nconst total = numbers.reduce((sum, n) => sum + n, 0);\nconsole.log(total);",
      };
      recommendedMaterials = [
        {
          title: "MDN ReferenceError reference",
          url: "https://developer.mozilla.org/en-US/docs/Web/JavaScript/Reference/Global_Objects/ReferenceError",
          reason: "Explains why variables are reported as undefined.",
        },
        {
          title: "MDN let declaration",
          url: "https://developer.mozilla.org/en-US/docs/Web/JavaScript/Reference/Statements/let",
          reason: "Shows correct variable declaration and block scope usage.",
        },
        {
          title: "MDN JavaScript scope glossary",
          url: "https://developer.mozilla.org/en-US/docs/Glossary/Scope",
          reason: "Builds understanding of local and global scope to prevent repeated mistakes.",
        },
      ];
    } else if (fullMessage.includes("TypeError") || /cannot read (property|properties) of/i.test(fullMessage)) {
      category = "runtime";
      shortMessage = "A value is used with the wrong data type.";
      longMessage =
        "Your code tries to call a method or access a property on a value that does not support it, often undefined or null.";
      hints = [
        "Check the actual value before using it: console.log(value).",
        "Guard against undefined or null before reading properties.",
        "Make sure the value type matches the method you want to call.",
      ];
      suggestedFix = {
        explanation: "Validate values before property access to avoid runtime failures.",
        code_patch: "if (user && user.name) {\n  console.log(user.name);\n}\n// Guard null/undefined values before use.",
      };
      recommendedMaterials = [
        {
          title: "MDN TypeError reference",
          url: "https://developer.mozilla.org/en-US/docs/Web/JavaScript/Reference/Global_Objects/TypeError",
          reason: "Explains common type misuse scenarios.",
        },
        {
          title: "MDN Optional chaining",
          url: "https://developer.mozilla.org/en-US/docs/Web/JavaScript/Reference/Operators/Optional_chaining",
          reason: "Shows safer access patterns for nested properties.",
        },
      ];
    }

    if (firstLine) {
      longMessage += " Runtime message: " + firstLine;
    }

    let line = 0;
    let column = 0;
    const match = fullMessage.match(/:(\d+):(\d+)/);
    if (match) {
      line = parseInt(match[1], 10) || 0;
      column = parseInt(match[2], 10) || 0;
    }

    return {
      diagnosis: {
        category: category,
        confidence: 0.5,
        message_short: shortMessage,
        message_long: longMessage,
      },
      location: { line: line, column: column, snippet: "" },
      hints: hints.map(function (hint) {
        return { hint: hint };
      }),
      suggested_fix: suggestedFix,
      recommended_materials: recommendedMaterials,
      explainability: "Client-side fallback because AI response was unavailable or invalid.",
    };
  };

  /**
   * Highlight error location in editor
   * @param {object} location
   */
  const highlightError = function (location) {
    if (!window.monaco || !editor || !editor.getModel) {
      return;
    }
    monaco.editor.setModelMarkers(editor.getModel(), "aicode", [
      {
        startLineNumber: location.line,
        startColumn: location.column || 1,
        endLineNumber: location.line,
        endColumn: (location.column || 1) + 10,
        message: location.snippet || "Error here",
        severity: monaco.MarkerSeverity.Error,
      },
    ]);
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
    const monacoContainer = document.getElementById("aicode-monaco-editor");
    if (monacoContainer) {
      monacoContainer.style.display = "none";
    }
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
   * Get editor value (Monaco or fallback)
   * @return {string}
   */
  const getEditorValue = function () {
    if (editor && typeof editor.getValue === "function") {
      return editor.getValue();
    }
    return "";
  };

  /**
   * Set editor value (Monaco or fallback)
   * @param {string} value
   */
  const setEditorValue = function (value) {
    if (editor && typeof editor.setValue === "function") {
      editor.setValue(value);
    }
  };

  /**
   * Clear Monaco markers if available
   */
  const clearEditorMarkers = function () {
    if (!window.monaco || !editor || !editor.getModel) {
      return;
    }
    monaco.editor.setModelMarkers(editor.getModel(), "aicode", []);
  };

  /**
   * Handle Hint button click
   */
  const handleHint = function () {
    const problemId = getProblemId();
    const sesskey = getSesskey();

    if (!problemId) {
      Notification.alert("Missing problem id", "Please reload the page and try again.");
      return;
    }

    if (!lastAnalysisInput && !cachedFeedback && !aiFeedbackPromise) {
      Notification.alert("No hints available", "Please run your code first so AI can analyze the error.");
      return;
    }

    if (cachedFeedback && cachedFeedback.diagnosis) {
      renderHintFeedback(cachedFeedback, problemId, sesskey);
      return;
    }

    if (!lastAnalysisInput) {
      Notification.alert("No hints available", "Please run your code first so AI can analyze the error.");
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
    if (confirm("Are you sure you want to reset the code to the starter template?")) {
      setEditorValue(getStarterCode());
      resetPanels();
      $("#aicode-feedback").html("");
      resetAIFeedbackState();
      cachedFeedback = null;
      lastAnalysisInput = null;
      clearEditorMarkers();
      if (previewFrame) {
        previewFrame.srcdoc = "";
      }
    }
  };

  /**
   * Handle Send to Teacher button click
   */
  const handleSendToTeacher = function () {
    const code = getEditorValue();
    const problemId = getProblemId();
    const sesskey = getSesskey();

    if (!problemId) {
      Notification.alert("Missing problem id", "Please reload the page and try again.");
      return;
    }

    Ajax.call([
      {
        methodname: "mod_aicode_send_to_teacher",
        args: {
          problemid: problemId,
          code: code,
          sesskey: sesskey || "",
        },
      },
    ])[0]
      .then(function () {
        Notification.alert("Success", "Your code has been sent to the teacher for review.");
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

  return {
    /**
     * Initialize the module
     * @param {object} cfg Configuration object
     */
    init: function (cfg) {
      config = cfg || {};
      config.problemId = config.problemId || config.problemid || getFallbackProblemId();
      config.language = config.language || getFallbackLanguage();
      config.sesskey = config.sesskey || getSesskey();
      config.htmlTemplate = config.htmlTemplate || getHtmlTemplate();
      config.cssTemplate = config.cssTemplate || getCssTemplate();
      config.mode = config.mode || getModeFromData();
      if (typeof config.isTeacher === "undefined") {
        config.isTeacher = isTeacher();
      }
      $(document).ready(function () {
        initMonaco();
        applyModeSettings();
      });
    },
  };
});
