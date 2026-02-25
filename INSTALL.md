# AICode Installation Guide

Complete installation guide for the AICode Moodle plugin with microservices.

## Table of Contents

1. [System Requirements](#system-requirements)
2. [Pre-Installation](#pre-installation)
3. [Install Microservices](#install-microservices)
4. [Install Moodle Plugin](#install-moodle-plugin)
5. [Configuration](#configuration)
6. [Verification](#verification)
7. [Production Deployment](#production-deployment)
8. [Troubleshooting](#troubleshooting)

---

## System Requirements

### Minimum Requirements

- **Operating System**: Linux (Ubuntu 20.04+, CentOS 8+) or macOS
- **Moodle**: Version 5.0+ (Build 20250502 or later)
- **PHP**: 8.1 or later
- **Node.js**: 18.0 or later
- **Docker**: 20.10 or later (for production executor)
- **Memory**: 2GB RAM minimum (4GB+ recommended)
- **Disk Space**: 1GB free space

### External Services

- **Google Gemini API**: Free API key required (get at https://makersuite.google.com/app/apikey)
- **Internet Connection**: Required for Gemini API calls

---

## Pre-Installation

### 1. Install Node.js

**Ubuntu/Debian:**

```bash
curl -fsSL https://deb.nodesource.com/setup_18.x | sudo -E bash -
sudo apt-get install -y nodejs
node --version  # Should show v18.x or higher
```

**CentOS/RHEL:**

```bash
curl -fsSL https://rpm.nodesource.com/setup_18.x | sudo bash -
sudo yum install -y nodejs
```

**macOS:**

```bash
brew install node@18
node --version
```

### 2. Install Docker

**Ubuntu:**

```bash
sudo apt-get update
sudo apt-get install -y docker.io docker-compose
sudo systemctl start docker
sudo systemctl enable docker
sudo usermod -aG docker $USER
# Log out and back in for group changes
```

**CentOS:**

```bash
sudo yum install -y docker docker-compose
sudo systemctl start docker
sudo systemctl enable docker
sudo usermod -aG docker $USER
```

**macOS:**

```bash
# Download Docker Desktop from https://www.docker.com/products/docker-desktop
# Or via Homebrew:
brew install --cask docker
```

Verify Docker:

```bash
docker --version
docker ps
```

### 3. Get Gemini API Key

1. Visit https://makersuite.google.com/app/apikey
2. Sign in with Google account
3. Click "Create API Key"
4. Copy the key (you'll need it later)

---

## Install Microservices

### 1. Extract Files

```bash
# If you have a ZIP file
unzip aicode-plugin.zip
cd aicode-plugin

# Or clone from repository
git clone https://github.com/your-org/aicode.git
cd aicode
```

### 2. Configure Environment

```bash
# Copy environment template
cp .env.example .env

# Edit environment file
nano .env
```

Add your configuration:

```bash
GEMINI_API_KEY=your-gemini-api-key-here
GEMINI_API_URL=https://generativelanguage.googleapis.com/v1beta/models/gemini-pro:generateContent
CONFIDENCE_THRESHOLD=0.6
EXECUTOR_MODE=prod
EXECUTION_TIMEOUT=2000
```

Save and close (Ctrl+X, Y, Enter).

### 3. Install Dependencies

```bash
# Option A: Using Makefile (recommended)
make install

# Option B: Manual installation
cd services/executor && npm install && cd ../..
cd services/analyzer && npm install && cd ../..
```

### 4. Build Docker Images

```bash
# Option A: Using Makefile
make build

# Option B: Manual build
docker-compose build
cd services/executor
docker build -t aicode-worker:latest .
cd ../..
```

### 5. Start Services

```bash
# Option A: Using Makefile
make start

# Option B: Docker Compose
docker-compose up -d

# View logs
docker-compose logs -f
```

### 6. Verify Services

```bash
# Check executor
curl http://127.0.0.1:3001/health
# Expected: {"status":"ok","mode":"prod","uptime":...}

# Check analyzer
curl http://127.0.0.1:3002/health
# Expected: {"status":"ok","gemini_configured":true,...}
```

If both return `status: "ok"`, microservices are ready! ✅

---

## Install Moodle Plugin

### 1. Copy Plugin Files

```bash
# Copy to Moodle mod directory
sudo cp -r mod/mod_aicode /path/to/moodle/mod/aicode

# Set correct permissions
sudo chown -R www-data:www-data /path/to/moodle/mod/aicode
sudo chmod -R 755 /path/to/moodle/mod/aicode
```

Replace `/path/to/moodle` with your actual Moodle installation path (commonly `/var/www/html/moodle` or `/opt/moodle`).

### 2. Install via Moodle Admin

1. Log in to Moodle as administrator
2. Navigate to: **Site administration**
3. Moodle will detect the new plugin and show a notification
4. Click **Upgrade Moodle database now**
5. Review the installation details
6. Click **Continue**
7. Wait for tables to be created

### 3. Alternative: ZIP Upload

If you can't access the server filesystem:

1. Create a ZIP of the plugin:

   ```bash
   cd mod
   zip -r aicode.zip mod_aicode/
   ```

2. In Moodle:
   - Go to: **Site administration → Plugins → Install plugins**
   - Upload `aicode.zip`
   - Click **Install plugin from ZIP file**
   - Follow prompts

---

## Configuration

### 1. Configure Plugin Settings

Navigate to:
**Site administration → Plugins → Activity modules → AICode**

Set the following:

| Setting              | Value                   | Description                 |
| -------------------- | ----------------------- | --------------------------- |
| Executor URL         | `http://127.0.0.1:3001` | URL of executor service     |
| Analyzer URL         | `http://127.0.0.1:3002` | URL of analyzer service     |
| Gemini API Key       | `your-api-key`          | Your Google Gemini key      |
| Confidence Threshold | `0.6`                   | Min AI confidence (0.0-1.0) |
| Cache TTL            | `3600`                  | Cache duration in seconds   |
| Max Calls per Day    | `50`                    | Rate limit per student      |
| Execution Timeout    | `2`                     | Code timeout in seconds     |

Click **Save changes**.

### 2. Set Capabilities

Navigate to:
**Site administration → Users → Permissions → Define roles**

Ensure roles have appropriate capabilities:

- **Students**: `mod/aicode:view`, `mod/aicode:submit`
- **Teachers**: All of above + `mod/aicode:viewattempts`, `mod/aicode:overridefeedback`
- **Managers**: All of above + `mod/aicode:addinstance`

---

## Verification

### 1. Test Microservices

```bash
# Test executor with simple code
curl -X POST http://127.0.0.1:3001/run \
  -H "Content-Type: application/json" \
  -d '{
    "code": "console.log(\"Hello, World!\");",
    "language": "javascript",
    "testcase": []
  }'

# Expected response:
# {"stdout":"Hello, World!\n","stderr":"","exitCode":0,...}
```

```bash
# Test analyzer
curl -X POST http://127.0.0.1:3002/analyze \
  -H "Content-Type: application/json" \
  -d '{
    "codeSnippet": "console.log(x);",
    "language": "javascript",
    "stderr": "ReferenceError: x is not defined",
    "studentLevel": "beginner"
  }'

# Expected: JSON with diagnosis, hints, etc.
```

### 2. Create Test Activity

1. In Moodle, go to any course
2. **Turn editing on**
3. Click **Add an activity or resource**
4. Select **AICode — AI Programming Lab**
5. Fill in:
   - Name: "Test Problem"
   - Description: "Print Hello"
   - Starter Code: `console.log("Hello");`
6. **Save and display**

### 3. Test as Student

1. Open the activity
2. Verify Monaco editor loads
3. Click **Run**
4. Check output appears

If all steps work, installation is complete! 🎉

---

## Production Deployment

### 1. Use Process Manager

Instead of Docker Compose, use a process manager for production:

**Using systemd:**

Create `/etc/systemd/system/aicode-executor.service`:

```ini
[Unit]
Description=AICode Executor Service
After=network.target

[Service]
Type=simple
User=www-data
WorkingDirectory=/opt/aicode/services/executor
Environment="EXECUTOR_MODE=prod"
Environment="PORT=3001"
ExecStart=/usr/bin/node server.js
Restart=on-failure

[Install]
WantedBy=multi-user.target
```

Create `/etc/systemd/system/aicode-analyzer.service`:

```ini
[Unit]
Description=AICode Analyzer Service
After=network.target

[Service]
Type=simple
User=www-data
WorkingDirectory=/opt/aicode/services/analyzer
Environment="PORT=3002"
Environment="GEMINI_API_KEY=your-key-here"
ExecStart=/usr/bin/node server.js
Restart=on-failure

[Install]
WantedBy=multi-user.target
```

Enable and start:

```bash
sudo systemctl daemon-reload
sudo systemctl enable aicode-executor aicode-analyzer
sudo systemctl start aicode-executor aicode-analyzer
sudo systemctl status aicode-executor aicode-analyzer
```

### 2. Use Nginx Reverse Proxy

For HTTPS and external access:

```nginx
# /etc/nginx/sites-available/aicode

upstream executor {
    server 127.0.0.1:3001;
}

upstream analyzer {
    server 127.0.0.1:3002;
}

server {
    listen 443 ssl http2;
    server_name executor.yourschool.edu;

    ssl_certificate /path/to/cert.pem;
    ssl_certificate_key /path/to/key.pem;

    location / {
        proxy_pass http://executor;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
    }
}

server {
    listen 443 ssl http2;
    server_name analyzer.yourschool.edu;

    ssl_certificate /path/to/cert.pem;
    ssl_certificate_key /path/to/key.pem;

    location / {
        proxy_pass http://analyzer;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
    }
}
```

Update Moodle plugin settings with:

- Executor URL: `https://executor.yourschool.edu`
- Analyzer URL: `https://analyzer.yourschool.edu`

### 3. Monitoring & Logging

**Log files:**

- Executor: `/var/log/aicode-executor.log`
- Analyzer: `/var/log/aicode-analyzer.log`

**Monitor with:**

```bash
journalctl -u aicode-executor -f
journalctl -u aicode-analyzer -f
```

**Health checks:**

```bash
# Add to cron for monitoring
*/5 * * * * curl -f http://127.0.0.1:3001/health || systemctl restart aicode-executor
*/5 * * * * curl -f http://127.0.0.1:3002/health || systemctl restart aicode-analyzer
```

---

## Troubleshooting

### Services won't start

**Check Docker:**

```bash
docker ps
docker logs aicode-executor
docker logs aicode-analyzer
```

**Check ports:**

```bash
sudo netstat -tuln | grep 300[12]
sudo lsof -i :3001
sudo lsof -i :3002
```

**Kill conflicting processes:**

```bash
sudo kill $(sudo lsof -t -i:3001)
sudo kill $(sudo lsof -t -i:3002)
```

### "GEMINI_API_KEY not configured"

1. Check `.env` file exists and has the key
2. Restart analyzer service
3. Verify key at https://console.cloud.google.com/

### Moodle can't connect to services

1. Test from Moodle server:

   ```bash
   curl http://127.0.0.1:3001/health
   ```

2. If fails, check firewall:

   ```bash
   sudo ufw allow 3001
   sudo ufw allow 3002
   ```

3. Check SELinux (CentOS/RHEL):
   ```bash
   sudo setenforce 0  # Temporary
   # Or permanently allow
   sudo setsebool -P httpd_can_network_connect 1
   ```

### Monaco editor not loading

1. Clear Moodle cache:
   **Site administration → Development → Purge all caches**

2. Check browser console for errors

3. Try serving Monaco locally instead of CDN (edit `amd/src/editor.js`)

### Docker worker fails

1. Rebuild worker image:

   ```bash
   cd services/executor
   docker build -t aicode-worker:latest --no-cache .
   ```

2. Test manually:
   ```bash
   echo 'console.log("test");' > /tmp/test.js
   docker run --rm --network none \
     --mount type=bind,source=/tmp/test.js,target=/workspace/code.js,readonly \
     aicode-worker:latest node /workspace/code.js
   ```

---

## Next Steps

- Read the main [README.md](README.md) for usage instructions
- Create sample problems from `mod/mod_aicode/examples/`
- Configure user roles and permissions
- Set up backups for the database
- Monitor API usage and costs

## Support

Need help? Check:

- GitHub Issues: https://github.com/your-org/aicode/issues
- Moodle Forums: https://moodle.org/plugins/mod_aicode
- Documentation: https://your-docs-site.com

---

Installation guide version 1.0.0
