/**
 * Test harness for analyzer service
 *
 * @copyright 2025 AICode Team
 */

const http = require("http");

const SERVICE_URL = "http://127.0.0.1:3002";

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
  console.log("Testing AICode Analyzer Service\n");

  // Test 1: Off-by-one error
  console.log("Test 1: Off-by-one error");
  const test1 = await post("/analyze", {
    codeSnippet: `function sumArray(arr) {
    let sum = 0;
    for (let i = 0; i <= arr.length; i++) {
        sum += arr[i];
    }
    return sum;
}
console.log(sumArray([1, 2, 3]));`,
    language: "javascript",
    stderr: "TypeError: Cannot read property of undefined",
    studentLevel: "beginner",
  });
  console.log("Diagnosis:", test1.diagnosis.category);
  console.log("Confidence:", test1.diagnosis.confidence);
  console.log("Message:", test1.diagnosis.message_short);
  console.log("Hints:", test1.hints.length, "hints provided");
  console.log();

  // Test 2: Syntax error
  console.log("Test 2: Syntax error");
  const test2 = await post("/analyze", {
    codeSnippet: `function greet(name) {
    console.log("Hello, " + name;
}`,
    language: "javascript",
    stderr: "SyntaxError: missing ) after argument list",
    studentLevel: "beginner",
  });
  console.log("Diagnosis:", test2.diagnosis.category);
  console.log("Confidence:", test2.diagnosis.confidence);
  console.log("Message:", test2.diagnosis.message_short);
  console.log();

  // Test 3: Reference error
  console.log("Test 3: Reference error");
  const test3 = await post("/analyze", {
    codeSnippet: `function calculate() {
    console.log(result);
}`,
    language: "javascript",
    stderr: "ReferenceError: result is not defined",
    studentLevel: "beginner",
  });
  console.log("Diagnosis:", test3.diagnosis.category);
  console.log("Confidence:", test3.diagnosis.confidence);
  console.log("Message:", test3.diagnosis.message_short);
  console.log();

  // Test 4: Cache test (should return cached result)
  console.log("Test 4: Cache test (repeating test 1)");
  const test4 = await post("/analyze", {
    codeSnippet: `function sumArray(arr) {
    let sum = 0;
    for (let i = 0; i <= arr.length; i++) {
        sum += arr[i];
    }
    return sum;
}
console.log(sumArray([1, 2, 3]));`,
    language: "javascript",
    stderr: "TypeError: Cannot read property of undefined",
    studentLevel: "beginner",
  });
  console.log("Should be same as Test 1:", test4.diagnosis.message_short);
  console.log();

  console.log("All tests completed!");
}

runTests().catch(console.error);
