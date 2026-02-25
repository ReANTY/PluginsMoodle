/**
 * Test harness for executor service
 *
 * @copyright 2025 AICode Team
 */

const http = require("http");

const SERVICE_URL = "http://127.0.0.1:3001";

/**
 * Make HTTP POST request
 */
function post(path, data) {
  return new Promise((resolve, reject) => {
    const payload = JSON.stringify(data);
    const options = {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
        "Content-Length": Buffer.byteLength(payload),
      },
    };

    const req = http.request(SERVICE_URL + path, options, (res) => {
      let body = "";
      res.on("data", (chunk) => (body += chunk));
      res.on("end", () => {
        try {
          resolve(JSON.parse(body));
        } catch (e) {
          resolve(body);
        }
      });
    });

    req.on("error", reject);
    req.write(payload);
    req.end();
  });
}

/**
 * Run tests
 */
async function runTests() {
  console.log("Testing AICode Executor Service\n");

  // Test 1: Simple console.log
  console.log("Test 1: Simple console.log");
  const test1 = await post("/run", {
    code: 'console.log("Hello, World!");',
    language: "javascript",
    testcase: [],
  });
  console.log("Result:", test1.stdout);
  console.log("Exit code:", test1.exitCode);
  console.log();

  // Test 2: Syntax error
  console.log("Test 2: Syntax error");
  const test2 = await post("/run", {
    code: 'console.log("Hello"',
    language: "javascript",
    testcase: [],
  });
  console.log("Stderr:", test2.stderr);
  console.log("Exit code:", test2.exitCode);
  console.log();

  // Test 3: Runtime error
  console.log("Test 3: Runtime error");
  const test3 = await post("/run", {
    code: "console.log(undefinedVariable);",
    language: "javascript",
    testcase: [],
  });
  console.log("Stderr:", test3.stderr);
  console.log("Exit code:", test3.exitCode);
  console.log();

  // Test 4: Blocked pattern
  console.log("Test 4: Blocked pattern (should fail validation)");
  try {
    const test4 = await post("/run", {
      code: 'const fs = require("fs"); fs.writeFileSync("test.txt", "bad");',
      language: "javascript",
      testcase: [],
    });
    console.log("Error:", test4.error);
  } catch (e) {
    console.log("Error:", e.message);
  }
  console.log();

  // Test 5: Simple function
  console.log("Test 5: Simple function");
  const test5 = await post("/run", {
    code: `
function factorial(n) {
    if (n <= 1) return 1;
    return n * factorial(n - 1);
}
console.log(factorial(5));
        `,
    language: "javascript",
    testcase: [],
  });
  console.log("Result:", test5.stdout);
  console.log("Exit code:", test5.exitCode);
  console.log();

  console.log("All tests completed!");
}

runTests().catch(console.error);
