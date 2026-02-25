# AICode Executor Service

Code execution service for the AICode Moodle plugin. Executes student JavaScript code in a secure, isolated environment.

## Features

- **Two execution modes:**
  - **Production**: Docker container isolation (full security)
  - **Development**: vm2 isolation (lightweight, for local dev)
- Configurable timeouts and resource limits
- Input validation and blocked pattern detection
- Automatic cleanup of temporary files

## Installation

```bash
cd services/executor
npm install
```

## Running the Service

### Development Mode (vm2)

For local development without Docker:

```bash
npm run dev
# or
EXECUTOR_MODE=dev npm start
```

Windows (PowerShell/CMD):

```bash
npm run dev:win
```

### Production Mode (Docker)

First, build the Docker worker image:

```bash
docker build -t aicode-worker:latest .
```

Then start the service:

```bash
npm start
# or
EXECUTOR_MODE=prod npm start
```

## Configuration

Environment variables:

- `PORT`: Service port (default: 3001)
- `EXECUTOR_MODE`: Execution mode - `dev` or `prod` (default: prod)
- `EXECUTION_TIMEOUT`: Timeout in milliseconds (default: 2000)

## API

### POST /run

Execute code.

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

### GET /health

Health check endpoint.

**Response:**

```json
{
  "status": "ok",
  "mode": "prod",
  "uptime": 12345
}
```

## Testing

Run the test suite:

```bash
# Start the service first
npm run dev

# In another terminal
npm test
```

## Security

### Production Mode (Docker)

Docker containers are executed with strict security constraints:

- `--network none`: No network access
- `--memory=128m`: Limited to 128MB RAM
- `--cpus=0.5`: Limited to 50% of one CPU core
- `--security-opt=no-new-privileges`: Prevents privilege escalation
- `--read-only`: Filesystem is read-only
- `--rm`: Containers are ephemeral and automatically removed

### Code Validation

All code is validated before execution to block:

- `child_process`, `spawn`, `exec`
- Filesystem write operations
- Network operations (`fetch`, `require('http')`, etc.)
- Process exit calls

## Docker Worker Image

The worker image is minimal and runs as a non-root user. Build it with:

```bash
docker build -t aicode-worker:latest .
```

Test the worker:

```bash
echo 'console.log("Test");' > /tmp/test.js
docker run --rm --network none --memory=128m --cpus=0.5 \
  --mount type=bind,source=/tmp/test.js,target=/workspace/code.js,readonly \
  aicode-worker:latest node /workspace/code.js
```

## Troubleshooting

### Docker not found

Ensure Docker is installed and running:

```bash
docker --version
docker ps
```

### Permission denied

Make sure your user has permission to run Docker:

```bash
sudo usermod -aG docker $USER
# Log out and back in
```

### Timeout errors

Increase the timeout:

```bash
EXECUTION_TIMEOUT=5000 npm start
```

## License

GPL-3.0 - See LICENSE file
