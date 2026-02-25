/**
 * AICode Analyzer Service
 *
 * Provides AI-powered code analysis using Google Gemini API.
 * Analyzes student code errors and provides structured feedback.
 *
 * @copyright 2025 AICode Team
 * @license GPL-3.0
 */

const express = require("express");
const bodyParser = require("body-parser");
const crypto = require("crypto");
const NodeCache = require("node-cache");
const rateLimit = require("express-rate-limit");
const https = require("https");

const app = express();
app.use(bodyParser.json({ limit: "1mb" }));

// Configuration
const PORT = process.env.PORT || 3002;
const GEMINI_API_KEY = process.env.GEMINI_API_KEY || "";
const GEMINI_MODEL = process.env.GEMINI_MODEL || "gemini-1.5-flash-latest";
const GEMINI_API_URL = process.env.GEMINI_API_URL || `https://generativelanguage.googleapis.com/v1beta/models/${GEMINI_MODEL}:generateContent`;
const CONFIDENCE_THRESHOLD = parseFloat(process.env.CONFIDENCE_THRESHOLD || "0.6");

// Cache for AI responses (TTL: 1 hour)
const cache = new NodeCache({ stdTTL: 3600, checkperiod: 600 });

// Rate limiting: 100 requests per 15 minutes per IP
const limiter = rateLimit({
  windowMs: 15 * 60 * 1000,
  max: 100,
  message: { error: "Too many requests, please try again later." },
});

app.use("/analyze", limiter);

/**
 * System prompt for Gemini
 * This prompt instructs Gemini to act as a programming tutor and return structured JSON feedback.
 */
const SYSTEM_PROMPT = `You are an expert programming tutor helping students learn JavaScript. 
Your task is to analyze student code that has errors and provide educational feedback.

CRITICAL: You MUST respond with ONLY valid JSON matching this exact schema:

{
  "diagnosis": {
    "category": "<one of: syntax|runtime|logical|off-by-one|api-misuse|style|performance>",
    "confidence": <float 0.0-1.0>,
    "message_short": "<brief one-line explanation>",
    "message_long": "<detailed explanation in 2-3 sentences>"
  },
  "location": {
    "line": <line number or 0 if unknown>,
    "column": <column number or 0 if unknown>,
    "snippet": "<problematic code snippet>"
  },
  "hints": [
    {"level": 1, "hint": "<gentle nudge without giving away the answer>"},
    {"level": 2, "hint": "<clearer hint pointing to the issue>"},
    {"level": 3, "hint": "<explicit guidance with pseudocode or minimal fix>"}
  ],
  "suggested_fix": {
    "explanation": "<what needs to change and why>",
    "code_patch": "<minimal code snippet showing the fix (max 5 lines)>"
  },
  "recommended_materials": [
    {"topic": "<topic-slug>", "title": "<Material Title>", "type": "<video|doc|quiz>", "moodle_resource_id": 0, "score": <0.0-1.0>}
  ],
  "explainability": "<brief reasoning for your diagnosis>"
}

Do NOT include any markdown, explanation text, or anything outside the JSON object.
Respond with ONLY the JSON object.`;

/**
 * Few-shot examples for Gemini
 */
const FEW_SHOT_EXAMPLES = [
  {
    // Example 1: Off-by-one error
    user: `Code:
function sumArray(arr) {
    let sum = 0;
    for (let i = 0; i <= arr.length; i++) {
        sum += arr[i];
    }
    return sum;
}
console.log(sumArray([1, 2, 3]));

Error: TypeError: Cannot read property of undefined`,

    assistant: `{
  "diagnosis": {
    "category": "off-by-one",
    "confidence": 0.95,
    "message_short": "Loop condition uses <= instead of < causing array index out of bounds",
    "message_long": "The for loop is iterating one time too many. Array indices go from 0 to length-1, but your condition i <= arr.length tries to access arr[arr.length] which doesn't exist. This is a classic off-by-one error."
  },
  "location": {
    "line": 3,
    "column": 20,
    "snippet": "i <= arr.length"
  },
  "hints": [
    {"level": 1, "hint": "Check how many times your loop runs compared to the array size."},
    {"level": 2, "hint": "Array indices start at 0. If an array has length 3, valid indices are 0, 1, 2. What happens when i equals 3?"},
    {"level": 3, "hint": "Change the loop condition from 'i <= arr.length' to 'i < arr.length'. This ensures i only goes from 0 to length-1."}
  ],
  "suggested_fix": {
    "explanation": "Change the loop condition from <= to < so the loop stops before accessing an invalid index",
    "code_patch": "for (let i = 0; i < arr.length; i++) {"
  },
  "recommended_materials": [
    {"topic": "loop-bounds", "title": "Understanding Array Loops and Boundaries", "type": "video", "moodle_resource_id": 0, "score": 0.92},
    {"topic": "off-by-one", "title": "Common Off-By-One Errors", "type": "doc", "moodle_resource_id": 0, "score": 0.88}
  ],
  "explainability": "Error message indicates undefined access. Code inspection shows loop condition allowing i to equal arr.length, which is out of bounds."
}`,
  },
  {
    // Example 2: Syntax error
    user: `Code:
function greet(name) {
    console.log("Hello, " + name;
}
greet("Alice");

Error: SyntaxError: missing ) after argument list`,

    assistant: `{
  "diagnosis": {
    "category": "syntax",
    "confidence": 0.98,
    "message_short": "Missing closing parenthesis in console.log statement",
    "message_long": "JavaScript requires all opening parentheses to have matching closing parentheses. The console.log call has an opening ( but is missing the closing ) before the semicolon. This prevents the code from being parsed."
  },
  "location": {
    "line": 2,
    "column": 31,
    "snippet": "console.log(\\"Hello, \\" + name;"
  },
  "hints": [
    {"level": 1, "hint": "Count your parentheses. Every ( needs a matching )."},
    {"level": 2, "hint": "Look at the console.log line. Where does the function call end?"},
    {"level": 3, "hint": "Add a closing ) after name and before the semicolon: console.log(\\"Hello, \\" + name);"}
  ],
  "suggested_fix": {
    "explanation": "Add the missing closing parenthesis to properly close the console.log function call",
    "code_patch": "console.log(\\"Hello, \\" + name);"
  },
  "recommended_materials": [
    {"topic": "syntax-basics", "title": "JavaScript Syntax Fundamentals", "type": "doc", "moodle_resource_id": 0, "score": 0.85},
    {"topic": "parentheses", "title": "Matching Brackets and Parentheses", "type": "quiz", "moodle_resource_id": 0, "score": 0.80}
  ],
  "explainability": "Parser error explicitly states 'missing ) after argument list'. Visual inspection confirms missing closing parenthesis in console.log call."
}`,
  },
];

/**
 * Sanitize and anonymize input
 */
function anonymizeInput(text) {
  if (!text) return "";

  // Remove email addresses
  text = text.replace(/[\w\.-]+@[\w\.-]+\.\w+/g, "[EMAIL]");

  // Remove URLs
  text = text.replace(/https?:\/\/[^\s]+/g, "[URL]");

  // Remove potential names in comments (basic heuristic)
  text = text.replace(/\/\/.*?(John|Jane|Student\s+\w+)/gi, "// [NAME]");

  // Limit length
  return text.substring(0, 5000);
}

/**
 * Call Gemini API
 */
async function callGemini(prompt) {
  return new Promise((resolve, reject) => {
    if (!GEMINI_API_KEY) {
      return reject(new Error("GEMINI_API_KEY not configured"));
    }

    const url = `${GEMINI_API_URL}?key=${GEMINI_API_KEY}`;
    const payload = JSON.stringify({
      contents: [
        {
          parts: [
            {
              text: prompt,
            },
          ],
        },
      ],
      generationConfig: {
        temperature: 0.0,
        maxOutputTokens: 2048,
        topP: 1,
        topK: 1,
      },
    });

    const urlObj = new URL(url);
    const options = {
      hostname: urlObj.hostname,
      path: urlObj.pathname + urlObj.search,
      method: "POST",
      headers: {
        "Content-Type": "application/json",
        "Content-Length": Buffer.byteLength(payload),
      },
    };

    const req = https.request(options, (res) => {
      let body = "";
      res.on("data", (chunk) => (body += chunk));
      res.on("end", () => {
        try {
          const response = JSON.parse(body);

          if (response.error) {
            return reject(new Error(response.error.message || "Gemini API error"));
          }

          if (!response.candidates || !response.candidates[0]) {
            return reject(new Error("No response from Gemini"));
          }

          const text = response.candidates[0].content.parts[0].text;
          resolve(text);
        } catch (e) {
          reject(new Error(`Failed to parse Gemini response: ${e.message}`));
        }
      });
    });

    req.on("error", reject);
    req.write(payload);
    req.end();
  });
}

/**
 * Parse and validate Gemini JSON response
 */
function parseGeminiResponse(text) {
  // Try to extract JSON from markdown code blocks if present
  const jsonMatch = text.match(/```json\s*(\{[\s\S]*?\})\s*```/) || text.match(/(\{[\s\S]*?\})/);

  if (!jsonMatch) {
    throw new Error("No JSON found in response");
  }

  const parsed = JSON.parse(jsonMatch[1]);

  // Validate required fields
  if (!parsed.diagnosis || !parsed.diagnosis.category || !parsed.diagnosis.confidence) {
    throw new Error("Invalid response structure: missing diagnosis fields");
  }

  // Validate confidence is a number between 0 and 1
  parsed.diagnosis.confidence = parseFloat(parsed.diagnosis.confidence);
  if (isNaN(parsed.diagnosis.confidence) || parsed.diagnosis.confidence < 0 || parsed.diagnosis.confidence > 1) {
    parsed.diagnosis.confidence = 0.5;
  }

  // Ensure required arrays exist
  parsed.hints = parsed.hints || [];
  parsed.recommended_materials = parsed.recommended_materials || [];

  return parsed;
}

/**
 * Get fallback rule-based response
 */
function getFallbackResponse(stderr, code) {
  let category = "runtime";
  let messageShort = "An error occurred during execution.";
  let messageLong = "Please review the error message and check your code for common mistakes.";

  if (stderr.includes("SyntaxError")) {
    category = "syntax";
    messageShort = "There is a syntax error in your code.";
    messageLong = "JavaScript cannot parse your code. Check for missing or extra brackets, parentheses, or semicolons.";
  } else if (stderr.includes("ReferenceError")) {
    category = "runtime";
    messageShort = "You are using a variable that has not been defined.";
    messageLong = "Make sure all variables are declared before you use them. Check for typos in variable names.";
  } else if (stderr.includes("TypeError")) {
    category = "runtime";
    messageShort = "You are performing an invalid operation on a value.";
    messageLong = "Check that you are using the correct methods and operations for the data type you have.";
  }

  return {
    diagnosis: {
      category,
      confidence: 0.5,
      message_short: messageShort,
      message_long: messageLong,
    },
    location: { line: 0, column: 0, snippet: "" },
    hints: [
      { level: 1, hint: "Read the error message carefully for clues." },
      { level: 2, hint: "Check the line mentioned in the error." },
      { level: 3, hint: "Use console.log statements to debug your code step by step." },
    ],
    suggested_fix: null,
    recommended_materials: [],
    explainability: "Rule-based fallback due to low AI confidence or unavailability",
  };
}

/**
 * POST /analyze - Analyze code and provide feedback
 */
app.post("/analyze", async (req, res) => {
  try {
    const { codeSnippet, language, stderr, failingTests, trace, studentLevel } = req.body;

    // Validate input
    if (!codeSnippet || typeof codeSnippet !== "string") {
      return res.status(400).json({ error: "Invalid code snippet" });
    }

    if (language !== "javascript") {
      return res.status(400).json({ error: "Only JavaScript is currently supported" });
    }

    // Anonymize inputs
    const anonymizedCode = anonymizeInput(codeSnippet);
    const anonymizedStderr = anonymizeInput(stderr || "");

    // Check cache
    const cacheKey = crypto
      .createHash("sha256")
      .update(anonymizedCode + anonymizedStderr)
      .digest("hex");

    const cached = cache.get(cacheKey);
    if (cached) {
      console.log("Cache hit for", cacheKey.substring(0, 8));
      return res.json(cached);
    }

    // Construct prompt with few-shot examples
    let prompt = SYSTEM_PROMPT + "\n\n";
    prompt += "Here are some examples:\n\n";

    FEW_SHOT_EXAMPLES.forEach((example, i) => {
      prompt += `Example ${i + 1}:\n`;
      prompt += `User: ${example.user}\n\n`;
      prompt += `Assistant: ${example.assistant}\n\n`;
    });

    prompt += `Now analyze this code:\n\nCode:\n${anonymizedCode}\n\n`;
    if (anonymizedStderr) {
      prompt += `Error: ${anonymizedStderr}\n\n`;
    }
    prompt += "Remember: respond with ONLY valid JSON, no other text.\n\nAssistant:";

    // Call Gemini
    console.log("Calling Gemini API...");
    const geminiResponse = await callGemini(prompt);

    // Parse response
    let feedback;
    try {
      feedback = parseGeminiResponse(geminiResponse);
    } catch (parseError) {
      console.error("Failed to parse Gemini response:", parseError.message);
      feedback = getFallbackResponse(anonymizedStderr, anonymizedCode);
    }

    // Check confidence threshold
    if (feedback.diagnosis.confidence < CONFIDENCE_THRESHOLD) {
      console.log(`Low confidence (${feedback.diagnosis.confidence}), using fallback`);
      feedback = getFallbackResponse(anonymizedStderr, anonymizedCode);
    }

    // Cache the result
    cache.set(cacheKey, feedback);

    res.json(feedback);
  } catch (error) {
    console.error("Analysis error:", error);

    // Return fallback response on error
    const fallback = getFallbackResponse(req.body.stderr || "", req.body.codeSnippet || "");
    res.json(fallback);
  }
});

/**
 * GET /health - Health check
 */
app.get("/health", (req, res) => {
  res.json({
    status: "ok",
    gemini_configured: !!GEMINI_API_KEY,
    cache_keys: cache.keys().length,
    uptime: process.uptime(),
  });
});

// Start server
app.listen(PORT, "127.0.0.1", () => {
  console.log(`AICode Analyzer Service running on http://127.0.0.1:${PORT}`);
  console.log(`Gemini API configured: ${!!GEMINI_API_KEY}`);
  console.log(`Confidence threshold: ${CONFIDENCE_THRESHOLD}`);
});

// Graceful shutdown
process.on("SIGTERM", () => {
  console.log("SIGTERM received, shutting down gracefully");
  cache.flushAll();
  process.exit(0);
});
