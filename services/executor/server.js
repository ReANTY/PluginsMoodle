/**
 * AICode Executor Service
 *
 * Executes student JavaScript code in a secure sandbox.
 * Supports two modes:
 * - Production: Docker container isolation (full security)
 * - Development: vm2 isolation (lightweight, for local dev)
 *
 * @copyright 2025 AICode Team
 * @license GPL-3.0
 */

const express = require("express");
const bodyParser = require("body-parser");
const { exec } = require("child_process");
const { promisify } = require("util");
const fs = require("fs").promises;
const path = require("path");
const crypto = require("crypto");

const execAsync = promisify(exec);

const app = express();
app.use(bodyParser.json({ limit: "1mb" }));

// Configuration
const PORT = process.env.PORT || 3001;
const EXECUTOR_MODE = process.env.EXECUTOR_MODE || "prod"; // 'dev' or 'prod'
const EXECUTION_TIMEOUT = parseInt(process.env.EXECUTION_TIMEOUT || "2000"); // ms
const MAX_OUTPUT_SIZE = 10000; // chars
const DOCKER_IMAGE = "aicode-worker:latest";

// Suspicious patterns that should be blocked
const BLOCKED_PATTERNS = [/child_process/i, /spawn/i, /exec(?:Sync)?/i, /fs\.write/i, /process\.exit/i, /require\(['"]net['"]\)/i, /require\(['"]http['"]\)/i, /require\(['"]https['"]\)/i, /fetch\(['"]http/i];

/**
 * Validate and sanitize code
 */
function validateCode(code) {
  if (!code || typeof code !== "string") {
    throw new Error("Invalid code input");
  }

  if (code.length > 50000) {
    throw new Error("Code too long (max 50KB)");
  }

  // Check for blocked patterns
  for (const pattern of BLOCKED_PATTERNS) {
    if (pattern.test(code)) {
      throw new Error("Code contains disallowed operations");
    }
  }

  return true;
}

/**
 * Build DOM shim for Node environments without browser APIs
 * @return {string}
 */
function getDomShim() {
  return `
(function () {
  function makeElement() {
    return {
      textContent: "",
      innerText: "",
      innerHTML: "",
      value: "",
      style: {},
      appendChild: function () {},
      setAttribute: function () {},
      addEventListener: function () {},
      removeEventListener: function () {},
      classList: { add: function () {}, remove: function () {}, toggle: function () {}, contains: function () { return false; } },
    };
  }

  const document = {
    getElementById: function () { return makeElement(); },
    querySelector: function () { return makeElement(); },
    createElement: function () { return makeElement(); },
    body: makeElement(),
  };
  const window = { document: document };

  if (typeof globalThis !== "undefined") {
    globalThis.document = document;
    globalThis.window = window;
  }
})();
`;
}

/**
 * Execute code using vm2 (development mode)
 */
async function executeWithVM2(code, testcases) {
  const { VM } = require("vm2");

  const vm = new VM({
    timeout: EXECUTION_TIMEOUT,
    sandbox: {
      console: {
        log: (...args) => {
          output.push(args.join(" "));
        },
      },
    },
    eval: false,
    wasm: false,
  });

  const output = [];
  let stderr = "";
  let exitCode = 0;

  try {
    // Add DOM shim to avoid "document is not defined" errors in Node.
    const wrappedCode = `${getDomShim()}\n${code}`;
    vm.run(wrappedCode);

    // Run test cases if provided
    if (testcases && testcases.length > 0) {
      for (const testcase of testcases) {
        try {
          const result = vm.run(testcase.input);
          output.push(`Test: ${testcase.input} => ${result}`);
        } catch (e) {
          output.push(`Test failed: ${e.message}`);
        }
      }
    }
  } catch (error) {
    stderr = error.message;
    exitCode = 1;
  }

  return {
    stdout: output.join("\n").substring(0, MAX_OUTPUT_SIZE),
    stderr: stderr.substring(0, MAX_OUTPUT_SIZE),
    exitCode,
    trace: "",
    lintWarnings: [],
  };
}

/**
 * Execute code using Docker (production mode)
 */
async function executeWithDocker(code, testcases) {
  // Create temporary file with unique name
  const jobId = crypto.randomBytes(16).toString("hex");
  const tmpDir = "/tmp/aicode-jobs";
  const jobDir = path.join(tmpDir, jobId);
  const codeFile = path.join(jobDir, "code.js");

  try {
    // Ensure job directory exists
    await fs.mkdir(jobDir, { recursive: true });

    // Write code to file with DOM shim to avoid browser API errors.
    const wrappedCode = `${getDomShim()}\n${code}`;
    await fs.writeFile(codeFile, wrappedCode, "utf8");

    // Prepare Docker command with security options
    const dockerCmd = [
      "docker run",
      "--rm",
      "--network none",
      "--memory=128m",
      "--cpus=0.5",
      "--security-opt=no-new-privileges",
      "--read-only",
      `--mount type=bind,source=${jobDir},target=/workspace,readonly`,
      `--name aicode-job-${jobId}`,
      DOCKER_IMAGE,
      "node /workspace/code.js",
    ].join(" ");

    // Execute with timeout
    const { stdout, stderr } = await execAsync(dockerCmd, {
      timeout: EXECUTION_TIMEOUT,
      maxBuffer: MAX_OUTPUT_SIZE,
    });

    return {
      stdout: stdout.substring(0, MAX_OUTPUT_SIZE),
      stderr: stderr.substring(0, MAX_OUTPUT_SIZE),
      exitCode: 0,
      trace: "",
      lintWarnings: [],
    };
  } catch (error) {
    // Handle execution errors
    let stderr = error.stderr || error.message;
    let exitCode = error.code || 1;

    if (error.killed) {
      stderr = "Execution timeout exceeded";
      exitCode = 124;
    }

    return {
      stdout: (error.stdout || "").substring(0, MAX_OUTPUT_SIZE),
      stderr: stderr.substring(0, MAX_OUTPUT_SIZE),
      exitCode,
      trace: "",
      lintWarnings: [],
    };
  } finally {
    // Clean up temporary files
    try {
      await fs.rm(jobDir, { recursive: true, force: true });
    } catch (e) {
      console.error("Failed to clean up job directory:", e);
    }
  }
}

/**
 * POST /run - Execute code
 */
app.post("/run", async (req, res) => {
  const startTime = Date.now();

  try {
    const { code, language, testcase } = req.body;

    // Validate language
    if (language !== "javascript") {
      return res.status(400).json({ error: "Only JavaScript is currently supported" });
    }

    // Validate code
    validateCode(code);

    // Execute based on mode
    let result;
    if (EXECUTOR_MODE === "dev") {
      console.log("[DEV MODE] Executing with vm2");
      result = await executeWithVM2(code, testcase);
    } else {
      console.log("[PROD MODE] Executing with Docker");
      result = await executeWithDocker(code, testcase);
    }

    // Add execution time
    result.executionTime = Date.now() - startTime;

    res.json(result);
  } catch (error) {
    console.error("Execution error:", error);
    res.status(500).json({
      error: error.message,
      stdout: "",
      stderr: error.message,
      exitCode: 1,
    });
  }
});

/**
 * GET /health - Health check
 */
app.get("/", (req, res) => {
  res.json({
    status: "ok",
    message: "AICode Executor Service is running. Use /health for details.",
  });
});

app.get("/health", (req, res) => {
  res.json({
    status: "ok",
    mode: EXECUTOR_MODE,
    uptime: process.uptime(),
  });
});

// Start server
app.listen(PORT, "127.0.0.1", () => {
  console.log(`AICode Executor Service running on http://127.0.0.1:${PORT}`);
  console.log(`Mode: ${EXECUTOR_MODE}`);
  console.log(`Timeout: ${EXECUTION_TIMEOUT}ms`);
});

// Graceful shutdown
process.on("SIGTERM", () => {
  console.log("SIGTERM received, shutting down gracefully");
  process.exit(0);
});
