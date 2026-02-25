# AICode — AI Programming Lab

**A Moodle 5.0+ activity module providing an interactive JavaScript programming lab with Monaco editor, secure code execution, and AI-powered intelligent feedback using Google Gemini.**

![Version](https://img.shields.io/badge/version-1.0.0-blue)
![Moodle](https://img.shields.io/badge/moodle-5.0+-orange)
![License](https://img.shields.io/badge/license-GPL--3.0-green)

## Features

### 🎯 Core Functionality

- **Monaco Editor**: Industry-standard code editor (VS Code's editor) with syntax highlighting and IntelliSense
- **Secure Execution**: Docker-isolated code execution with resource limits and network isolation
- **AI Feedback**: Google Gemini-powered analysis with structured hints, fixes, and learning resources
- **Multi-Level Hints**: Progressive disclosure (Level 1: nudge, Level 2: direct, Level 3: solution)
- **Real-time Testing**: Run code against test cases with immediate feedback

### 🔒 Security & Privacy

- **Docker Isolation**: Production mode uses containers with `--network none`, memory/CPU limits
- **Input Validation**: Blocks dangerous operations (filesystem, network, process spawning)
- **PII Anonymization**: Strips personal information before sending to AI
- **Opt-in Training**: Configurable consent for using anonymized data
- **Privacy Compliance**: GDPR-compliant with Moodle privacy API

### 🛠️ Architecture

- **Moodle Plugin**: Activity module following Moodle coding standards
- **Executor Service**: Node.js microservice (port 3001) with Docker/vm2 modes
- **Analyzer Service**: Node.js microservice (port 3002) with Gemini integration
- **Database**: Four tables for problems, attempts, materials, and cache

## Quick Start

### Prerequisites

- **Moodle 5.0+** (Build: 20250502 or later)
- **Docker** (for production executor mode)
- **Node.js 18+** (for microservices)
- **Google Gemini API Key** (get free at https://makersuite.google.com/app/apikey)

### Installation

#### 1. Install Microservices

```bash
# Clone or extract to your preferred location
cd /path/to/aicode

# Install dependencies
make install
# or manually:
# cd services/executor && npm install
# cd services/analyzer && npm install

# Configure environment
cp .env.example .env
nano .env  # Add your GEMINI_API_KEY

# Build Docker images
make build
# or manually:
# docker-compose build
# cd services/executor && docker build -t aicode-worker:latest .

# Start services
make start
# or: docker-compose up -d

# Verify services are running
curl http://127.0.0.1:3001/health
curl http://127.0.0.1:3002/health
```

#### 2. Install Moodle Plugin

```bash
# Copy plugin to Moodle
cp -r mod/mod_aicode /path/to/moodle/mod/aicode

# Navigate to Moodle admin to complete installation
# Visit: http://your-moodle/admin
# Follow the installation prompts
```

#### 3. Configure Plugin Settings

In Moodle, navigate to:
**Site administration → Plugins → Activity modules → AICode**

Set:

- **Executor URL**: `http://127.0.0.1:3001`
- **Analyzer URL**: `http://127.0.0.1:3002`
- **Gemini API Key**: Your API key from Google
- **Confidence Threshold**: `0.6` (recommended)
- **Cache TTL**: `3600` (1 hour)
- **Max Calls per Day**: `50` per student

### Creating Your First Problem

1. Go to a course and **Turn editing on**
2. Click **Add an activity or resource**
3. Select **AICode — AI Programming Lab**
4. Configure:
   - **Name**: "Sum Array Elements"
   - **Description**: "Write a function that sums all elements in an array"
   - **Language**: JavaScript
   - **Starter Code**:
     ```javascript
     function sumArray(arr) {
       // Your code here
     }
     console.log(sumArray([1, 2, 3, 4, 5]));
     ```
   - **Test Cases** (JSON):
     ```json
     [
       { "input": "[1,2,3]", "expected": "6" },
       { "input": "[10,20,30]", "expected": "60" }
     ]
     ```
5. Save and display

Students can now:

- Write code in the Monaco editor
- Click **Run** to execute
- Get **AI Feedback** on errors
- Request **Hints** at 3 levels
- Send code to teacher for review

## Development Mode

For local development without Docker:

```bash
# Start services in vm2 mode (lightweight)
cp docker-compose.override.yml.example docker-compose.override.yml
docker-compose up

# Or run services directly
cd services/executor
EXECUTOR_MODE=dev npm run dev

cd services/analyzer
GEMINI_API_KEY=your-key npm start
```

## Architecture

```
┌─────────────────────────────────────────────────────────────────┐
│                         Moodle LMS                              │
│  ┌──────────────────────────────────────────────────────────┐   │
│  │ AICode Activity Module (mod_aicode)                      │   │
│  │  - Monaco Editor (AMD/JS)                                │   │
│  │  - Renderer (PHP)                                        │   │
│  │  - External API (Web Services)                           │   │
│  └────────────┬──────────────────────┬──────────────────────┘   │
│               │                      │                          │
└───────────────┼──────────────────────┼──────────────────────────┘
                │                      │
                │ HTTP                 │ HTTP
                ▼                      ▼
    ┌───────────────────┐   ┌────────────────────┐
    │ Executor Service  │   │ Analyzer Service   │
    │  (port 3001)      │   │  (port 3002)       │
    │                   │   │                    │
    │ • vm2 (dev)       │   │ • Gemini API       │
    │ • Docker (prod)   │   │ • Caching          │
    │ • Validation      │   │ • Rate Limiting    │
    └─────────┬─────────┘   └────────────────────┘
              │                       │
              ▼                       ▼
     ┌────────────────┐      ┌──────────────────┐
     │ Docker Worker  │      │ Google Gemini    │
     │ Container      │      │ API              │
     └────────────────┘      └──────────────────┘
```

## Database Schema

### `mdl_aicode_problems`

- Stores programming problems (course activities)
- Fields: course, name, description, language, testcases, startercode, allow_training

### `mdl_aicode_attempts`

- Stores student code submissions
- Fields: problemid, userid, code_hash, is_anonymous, result_json, used_hints_json, ai_feedback_json

### `mdl_aicode_materials`

- Learning resources recommended by AI
- Fields: title, url, type, tags_json, moodle_resource_id

### `mdl_aicode_cache`

- Caches AI responses to reduce API calls
- Fields: payload_hash, ai_response_json, timecreated

### `mdl_aicode_teacher_overrides`

- Teacher corrections to AI feedback
- Fields: attemptid, teacherid, corrected_feedback_json

## API Documentation

### Executor Service (port 3001)

#### `POST /run`

Execute code in a sandbox.

**Request:**

```json
{
  "code": "console.log('Hello');",
  "language": "javascript",
  "testcase": []
}
```

**Response:**

```json
{
  "stdout": "Hello\n",
  "stderr": "",
  "exitCode": 0,
  "trace": "",
  "lintWarnings": [],
  "executionTime": 45
}
```

#### `GET /health`

Health check endpoint.

### Analyzer Service (port 3002)

#### `POST /analyze`

Analyze code errors with AI.

**Request:**

```json
{
  "codeSnippet": "function sum(arr) { ... }",
  "language": "javascript",
  "stderr": "TypeError: ...",
  "studentLevel": "beginner"
}
```

**Response:**

```json
{
  "diagnosis": {
    "category": "off-by-one",
    "confidence": 0.95,
    "message_short": "Loop bounds error",
    "message_long": "Detailed explanation..."
  },
  "location": {"line": 3, "column": 20, "snippet": "..."},
  "hints": [
    {"level": 1, "hint": "..."},
    {"level": 2, "hint": "..."},
    {"level": 3, "hint": "..."}
  ],
  "suggested_fix": {
    "explanation": "...",
    "code_patch": "..."
  },
  "recommended_materials": [...],
  "explainability": "..."
}
```

## Security

### Production Executor (Docker)

Code runs in isolated containers with:

- `--network none`: No network access
- `--memory=128m`: Limited RAM
- `--cpus=0.5`: CPU throttling
- `--security-opt=no-new-privileges`: Prevents escalation
- `--read-only`: Immutable filesystem
- `--rm`: Ephemeral (auto-removed)

### Input Validation

Blocks patterns:

- `child_process`, `spawn`, `exec`
- Filesystem writes
- Network operations (`fetch`, `require('http')`)
- Process manipulation

### Privacy

- PII stripped before sending to Gemini (emails, URLs, names)
- No student identifiers sent to external services
- Configurable opt-in for training data
- GDPR-compliant with Moodle privacy API

## Configuration

### Environment Variables

**Analyzer Service:**

- `GEMINI_API_KEY`: Your Google Gemini API key (required)
- `GEMINI_API_URL`: API endpoint (default: Gemini Pro)
- `CONFIDENCE_THRESHOLD`: Min confidence (0.0-1.0, default: 0.6)
- `PORT`: Service port (default: 3002)

**Executor Service:**

- `EXECUTOR_MODE`: `dev` (vm2) or `prod` (Docker, default)
- `EXECUTION_TIMEOUT`: Timeout in ms (default: 2000)
- `PORT`: Service port (default: 3001)

### Moodle Settings

Configure in: **Site administration → Plugins → Activity modules → AICode**

## Sample Problems

See `mod/mod_aicode/examples/` for:

1. **syntax_error.json**: Missing parenthesis
2. **runtime_error.json**: Undefined variable reference
3. **logic_error.json**: Off-by-one array indexing

Import via: **Course → Import → Sample problems**

## Testing

### Unit Tests

```bash
# Test executor
cd services/executor
npm test

# Test analyzer
cd services/analyzer
GEMINI_API_KEY=your-key npm test
```

### Integration Test

```bash
# Start all services
make start

# Run end-to-end test
curl -X POST http://127.0.0.1:3001/run \
  -H "Content-Type: application/json" \
  -d '{"code":"console.log(2+2);","language":"javascript"}'

curl -X POST http://127.0.0.1:3002/analyze \
  -H "Content-Type: application/json" \
  -d '{"codeSnippet":"console.log(x);","language":"javascript","stderr":"ReferenceError: x is not defined"}'
```

## Troubleshooting

### Services won't start

**Check Docker:**

```bash
docker --version
docker ps
```

**Check ports:**

```bash
netstat -tuln | grep 300[12]
```

### Executor timeout errors

Increase timeout:

```bash
EXECUTION_TIMEOUT=5000 docker-compose up
```

### Analyzer returns fallback responses

1. Verify `GEMINI_API_KEY` is set correctly
2. Check Gemini API quota: https://console.cloud.google.com/
3. Lower `CONFIDENCE_THRESHOLD` (may reduce quality)

### Moodle can't connect to services

1. Ensure services run on `127.0.0.1` (not `0.0.0.0`)
2. Verify firewall allows localhost connections
3. Check Moodle server can reach Docker host
4. For remote setups, use reverse proxy with HTTPS

## Contributing

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## License

GPL-3.0 - See LICENSE file

This plugin is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.

## Credits

- **Monaco Editor**: Microsoft (MIT License)
- **Google Gemini**: Google LLC
- **Node.js vm2**: Patrik Simek (MIT License)
- **Express.js**: OpenJS Foundation (MIT License)

## Support

- **Issues**: https://github.com/your-org/aicode/issues
- **Documentation**: https://your-docs-site.com
- **Moodle Forums**: https://moodle.org/plugins/mod_aicode

## Roadmap

- [ ] Python language support
- [ ] Java language support
- [ ] Visual debugger integration
- [ ] Collaborative coding (real-time)
- [ ] Plagiarism detection
- [ ] Custom AI model fine-tuning
- [ ] Mobile app support

---

Made with ❤️ by the AICode Team | © 2025
