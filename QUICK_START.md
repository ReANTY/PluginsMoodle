# AICode Quick Start Guide

Get AICode running in 15 minutes! ⚡

## Prerequisites Check

Before starting, verify you have:

- [x] Moodle 5.0+ installed and working
- [x] Node.js 18+ installed (`node --version`)
- [x] Docker installed (`docker --version`)
- [x] Google Gemini API key ([get one here](https://makersuite.google.com/app/apikey))

## 5-Step Installation

### Step 1: Configure Environment (2 min)

```bash
# Navigate to aicode directory
cd /path/to/aicode

# Copy environment template
cp .env.example .env

# Edit and add your Gemini API key
nano .env
```

In the `.env` file, set:

```bash
GEMINI_API_KEY=your-actual-api-key-here
```

Save and exit (Ctrl+X, Y, Enter).

### Step 2: Install Dependencies (3 min)

```bash
# Install all dependencies at once
make install

# Or manually:
# cd services/executor && npm install && cd ../..
# cd services/analyzer && npm install && cd ../..
```

### Step 3: Build & Start Services (5 min)

```bash
# Build Docker images
make build

# Start services
make start

# Check they're running
curl http://127.0.0.1:3001/health  # Executor
curl http://127.0.0.1:3002/health  # Analyzer
```

Both should return `{"status":"ok",...}` ✅

### Step 4: Install Moodle Plugin (3 min)

```bash
# Copy plugin to Moodle
sudo cp -r mod/mod_aicode /path/to/moodle/mod/aicode

# Fix permissions
sudo chown -R www-data:www-data /path/to/moodle/mod/aicode
```

Then in Moodle:

1. Log in as admin
2. Go to **Site administration**
3. Click **Upgrade Moodle database now**
4. Click **Continue**

### Step 5: Configure Plugin (2 min)

In Moodle, navigate to:
**Site administration → Plugins → Activity modules → AICode**

Set:

- **Executor URL**: `http://127.0.0.1:3001`
- **Analyzer URL**: `http://127.0.0.1:3002`
- **Gemini API Key**: Your API key from Step 1

Click **Save changes**.

**Done! 🎉 Your AICode installation is ready.**

---

## Create Your First Problem (5 min)

### Option A: Import Sample Problems

1. Go to any course
2. Click **Import** (under course admin menu)
3. Import `mod/mod_aicode/examples/sample_problems.json`

### Option B: Create Manually

1. In a course, **Turn editing on**
2. Click **Add an activity or resource**
3. Select **AICode — AI Programming Lab**
4. Fill in:

**Name:**

```
Factorial Function
```

**Description:**

```
Write a function that calculates the factorial of a number.
Example: factorial(5) should return 120
```

**Starter Code:**

```javascript
function factorial(n) {
  // Your code here
}

console.log(factorial(5));
```

**Test Cases:**

```json
[
  { "input": "5", "expected": "120" },
  { "input": "3", "expected": "6" }
]
```

5. Click **Save and display**

### Test It!

As a student:

1. Open the activity
2. Write the code:

```javascript
function factorial(n) {
  if (n <= 1) return 1;
  return n * factorial(n - 1);
}
console.log(factorial(5));
```

3. Click **Run**
4. See output: `120` ✅

---

## Common Commands

```bash
# View service logs
make logs

# Stop services
make stop

# Restart services
make stop && make start

# Run tests
make test

# Start in dev mode (vm2, no Docker)
make dev
```

## Test the Services

### Test Executor Directly

```bash
curl -X POST http://127.0.0.1:3001/run \
  -H "Content-Type: application/json" \
  -d '{
    "code": "console.log(2 + 2);",
    "language": "javascript",
    "testcase": []
  }'
```

Expected output:

```json
{
  "stdout": "4\n",
  "stderr": "",
  "exitCode": 0,
  "trace": "",
  "lintWarnings": [],
  "executionTime": 45
}
```

### Test Analyzer Directly

```bash
curl -X POST http://127.0.0.1:3002/analyze \
  -H "Content-Type: application/json" \
  -d '{
    "codeSnippet": "console.log(x);",
    "language": "javascript",
    "stderr": "ReferenceError: x is not defined",
    "studentLevel": "beginner"
  }'
```

Expected: JSON with diagnosis, hints, and suggested fix.

---

## Troubleshooting

### "Service unavailable"

**Check services are running:**

```bash
docker ps | grep aicode
```

**If not running:**

```bash
make start
```

**Check logs:**

```bash
docker-compose logs executor
docker-compose logs analyzer
```

### "GEMINI_API_KEY not configured"

**Verify .env file:**

```bash
cat .env | grep GEMINI_API_KEY
```

**Should show:**

```
GEMINI_API_KEY=your-key-here
```

**If empty, add your key and restart:**

```bash
nano .env  # Add key
make stop && make start
```

### "Port already in use"

**Check what's using the port:**

```bash
sudo lsof -i :3001
sudo lsof -i :3002
```

**Kill the process or change port in .env:**

```bash
# In .env
PORT_EXECUTOR=3011
PORT_ANALYZER=3012
```

### Monaco editor not loading

**Check browser console** (F12 in browser)

**Clear Moodle cache:**

```
Site administration → Development → Purge all caches
```

---

## What's Next?

### For Teachers

- Create more problems
- Explore the **View attempts** feature
- Try **overriding AI feedback** when it's incorrect
- Adjust hint difficulty levels

### For Developers

- Read [CONTRIBUTING.md](CONTRIBUTING.md)
- Try the test problems in development mode
- Customize the system prompt in `services/analyzer/server.js`
- Add support for more languages

### For Admins

- Set up monitoring (see [INSTALL.md](INSTALL.md#production-deployment))
- Configure firewall rules
- Set up SSL/TLS with reverse proxy
- Monitor API usage and costs

---

## Quick Reference Card

| Task           | Command                      |
| -------------- | ---------------------------- |
| Start services | `make start`                 |
| Stop services  | `make stop`                  |
| View logs      | `make logs`                  |
| Run tests      | `make test`                  |
| Rebuild        | `make build`                 |
| Check health   | `curl localhost:3001/health` |

| URL                             | Purpose          |
| ------------------------------- | ---------------- |
| `http://127.0.0.1:3001`         | Executor service |
| `http://127.0.0.1:3002`         | Analyzer service |
| `http://your-moodle/mod/aicode` | Plugin in Moodle |

| File                          | Edit When                 |
| ----------------------------- | ------------------------- |
| `.env`                        | Configure API keys, ports |
| `docker-compose.yml`          | Change service config     |
| `services/analyzer/server.js` | Customize AI prompts      |
| `mod/mod_aicode/settings.php` | Add admin settings        |

---

## Support

- **Issues**: https://github.com/your-org/aicode/issues
- **Docs**: See [README.md](README.md) and [INSTALL.md](INSTALL.md)
- **Security**: See [SECURITY.md](SECURITY.md)

---

**Happy coding! 🚀**
