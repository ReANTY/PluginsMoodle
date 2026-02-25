# AICode System Architecture

This document provides a detailed architectural overview of the AICode plugin.

## High-Level Architecture

```
┌─────────────────────────────────────────────────────────────────────────┐
│                           User Layer                                     │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐                 │
│  │   Student    │  │   Teacher    │  │    Admin     │                 │
│  │   Browser    │  │   Browser    │  │   Browser    │                 │
│  └──────┬───────┘  └──────┬───────┘  └──────┬───────┘                 │
│         │                  │                  │                          │
│         └──────────────────┼──────────────────┘                          │
│                            │ HTTPS                                       │
└────────────────────────────┼─────────────────────────────────────────────┘
                             ▼
┌─────────────────────────────────────────────────────────────────────────┐
│                        Moodle Server                                     │
│  ┌────────────────────────────────────────────────────────────────┐    │
│  │  AICode Plugin (mod_aicode)                                     │    │
│  │  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐         │    │
│  │  │  view.php    │  │ renderer.php │  │ settings.php │         │    │
│  │  └──────────────┘  └──────────────┘  └──────────────┘         │    │
│  │  ┌──────────────────────────────────────────────────┐          │    │
│  │  │  Web Services (classes/external/)                │          │    │
│  │  │  • _code                                      │          │    │
│  │  │  • analyze_code                                  │          │    │
│  │  │  • record_hint                                   │          │    │
│  │  │  • send_to_teacher                               │          │    │
│  │  └──────────────────────────────────────────────────┘          │    │
│  │  ┌──────────────────────────────────────────────────┐          │    │
│  │  │  Frontend (amd/src/editor.js)                    │          │    │
│  │  │  • Monaco Editor Integration                      │          │    │
│  │  │  • Run/Hint/Reset Buttons                        │          │    │
│  │  │  • Output Display                                │          │    │
│  │  └──────────────────────────────────────────────────┘          │    │
│  └────────────────────────────────────────────────────────────────┘    │
│  ┌────────────────────────────────────────────────────────────────┐    │
│  │  Moodle Core                                                    │    │
│  │  • Authentication                                               │    │
│  │  • Session Management                                           │    │
│  │  • Capability Checking                                          │    │
│  │  • Database Abstraction (DML)                                   │    │
│  └────────────────────────────────────────────────────────────────┘    │
│  ┌────────────────────────────────────────────────────────────────┐    │
│  │  Database (MySQL/PostgreSQL)                                    │    │
│  │  • mdl_aicode_problems                                          │    │
│  │  • mdl_aicode_attempts                                          │    │
│  │  • mdl_aicode_materials                                         │    │
│  │  • mdl_aicode_cache                                             │    │
│  │  • mdl_aicode_teacher_overrides                                 │    │
│  └────────────────────────────────────────────────────────────────┘    │
└─────────────────────────────────────────────────────────────────────────┘
                             │                │
                   HTTP      │                │ HTTP
                  (3001)     │                │ (3002)
                             ▼                ▼
┌─────────────────────────────────────────────────────────────────────────┐
│                       Microservices Layer                                │
│  ┌─────────────────────────┐    ┌─────────────────────────┐            │
│  │  Executor Service       │    │  Analyzer Service       │            │
│  │  (port 3001)            │    │  (port 3002)            │            │
│  │  ┌──────────────────┐   │    │  ┌──────────────────┐   │            │
│  │  │  server.js       │   │    │  │  server.js       │   │            │
│  │  │  • Validation    │   │    │  │  • Anonymization │   │            │
│  │  │  • Mode Switch   │   │    │  │  • Rate Limiting │   │            │
│  │  │  • Timeout       │   │    │  │  • Caching       │   │            │
│  │  └──────────────────┘   │    │  └──────────────────┘   │            │
│  │         │                │    │         │                │            │
│  │    ┌────┴─────┐          │    │         │                │            │
│  │    │          │          │    │         │                │            │
│  │  ┌─▼──┐    ┌─▼──┐       │    │         │                │            │
│  │  │vm2 │    │Dock│       │    │         │                │            │
│  │  │(dev│    │ er │       │    │         │                │            │
│  │  │)   │    │(prd│       │    │         │                │            │
│  │  └────┘    └─┬──┘       │    │         │                │            │
│  └──────────────┼───────────┘    └─────────┼────────────────┘            │
│                 │                           │                             │
└─────────────────┼───────────────────────────┼─────────────────────────────┘
                  │                           │
                  ▼                           ▼ HTTPS
           ┌──────────────┐          ┌──────────────────┐
           │    Docker    │          │  Google Gemini   │
           │   Worker     │          │      API         │
           │  Container   │          └──────────────────┘
           │  (isolated)  │
           └──────────────┘
```

## Data Flow Diagrams

### Flow 1: Code Execution

```
┌─────────┐     1. Write Code      ┌─────────┐
│ Student │ ───────────────────────>│ Monaco  │
│ Browser │                         │ Editor  │
└─────────┘                         └────┬────┘
                                         │ 2. Click Run
                                         ▼
                               ┌──────────────────┐
                               │  editor.js       │
                               │  (AMD module)    │
                               └────┬─────────────┘
                                    │ 3. AJAX Call
                                    ▼
                        ┌────────────────────────┐
                        │ mod_aicode_run_code    │
                        │ (Web Service)          │
                        └────┬───────────────────┘
                             │ 4. HTTP POST
                             ▼
                   ┌──────────────────────┐
                   │ Executor Service     │
                   │ (port 3001)          │
                   ├──────────────────────┤
                   │ • Validate input     │
                   │ • Check patterns     │
                   │ • Choose mode        │
                   └──┬───────────────────┘
                      │
         ┌────────────┴────────────┐
         │                         │
    Dev Mode                  Prod Mode
         │                         │
         ▼                         ▼
    ┌────────┐            ┌──────────────┐
    │  vm2   │            │ Docker Worker│
    │ Sandbox│            │  Container   │
    └────┬───┘            └──────┬───────┘
         │                       │
         └───────┬───────────────┘
                 │ 5. Return Result
                 ▼
         {stdout, stderr, exitCode}
                 │
                 │ 6. Store Attempt
                 ▼
       ┌────────────────────┐
       │ mdl_aicode_attempts│
       └────────────────────┘
                 │
                 │ 7. Return to Frontend
                 ▼
           ┌──────────┐
           │ Display  │
           │ Output   │
           └──────────┘
```

### Flow 2: AI Analysis

```
┌─────────┐    Execution Failed    ┌──────────┐
│ Executor│ ───────────────────────>│ Frontend │
│ Returns │        (stderr)         │ (editor) │
└─────────┘                         └────┬─────┘
                                         │ Auto-trigger AI
                                         ▼
                             ┌─────────────────────┐
                             │ mod_aicode_analyze  │
                             │ (Web Service)       │
                             └────┬────────────────┘
                                  │ HTTP POST
                                  ▼
                       ┌─────────────────────────┐
                       │ Analyzer Service        │
                       │ (port 3002)             │
                       ├─────────────────────────┤
                       │ 1. Anonymize code       │
                       │    • Strip emails       │
                       │    • Remove URLs        │
                       │    • Clean comments     │
                       └────┬────────────────────┘
                            │
                       ┌────▼───────────┐
                       │ Check Cache    │
                       │ (payload hash) │
                       └────┬───────────┘
                            │
                ┌───────────┴───────────┐
                │                       │
             Cache Hit              Cache Miss
                │                       │
                │                       ▼
                │            ┌─────────────────────┐
                │            │ 2. Build Prompt     │
                │            │    • System prompt  │
                │            │    • Few-shot       │
                │            │    • User code      │
                │            └────┬────────────────┘
                │                 │ HTTPS
                │                 ▼
                │         ┌──────────────────┐
                │         │  Google Gemini   │
                │         │      API         │
                │         └────┬─────────────┘
                │              │
                │              ▼
                │         ┌─────────────────┐
                │         │ 3. Parse JSON   │
                │         │    • Validate   │
                │         │    • Check conf │
                │         └────┬────────────┘
                │              │
                └──────────────┤
                               │ 4. Cache Result
                               ▼
                    ┌────────────────────┐
                    │ mdl_aicode_cache   │
                    └────┬───────────────┘
                         │ 5. Return Feedback
                         ▼
                 {diagnosis, hints, fix, materials}
                         │
                         ▼
                   ┌──────────┐
                   │ Display  │
                   │ Feedback │
                   └──────────┘
```

### Flow 3: Hint Request

```
┌─────────┐   Click Hint Button    ┌──────────┐
│ Student │ ───────────────────────>│ Frontend │
└─────────┘        (Level 1-3)      └────┬─────┘
                                         │
                                         ▼
                              ┌────────────────────┐
                              │ Check Cached       │
                              │ Feedback           │
                              └────┬───────────────┘
                                   │
                          ┌────────┴────────┐
                          │                 │
                    Exists              Not Exists
                          │                 │
                          ▼                 ▼
                   ┌─────────────┐   ┌─────────────┐
                   │ Display Hint│   │ Show Alert  │
                   │ for Level   │   │ "Run first" │
                   └──────┬──────┘   └─────────────┘
                          │
                          │ Record Usage
                          ▼
                   ┌─────────────────────┐
                   │ mod_aicode_record_  │
                   │ hint (Web Service)  │
                   └──────┬──────────────┘
                          │
                          ▼
                 ┌─────────────────────┐
                 │ mdl_aicode_attempts │
                 │ (update hints_json) │
                 └─────────────────────┘
```

## Component Details

### Moodle Plugin Components

```
mod_aicode/
│
├─ PHP Layer (Backend)
│  ├─ lib.php ────────────────────> Core CRUD functions
│  ├─ mod_form.php ───────────────> Activity settings form
│  ├─ view.php ───────────────────> Main view controller
│  ├─ renderer.php ───────────────> HTML output
│  └─ classes/
│     ├─ external/ ──────────────> Web service API
│     ├─ event/ ─────────────────> Event logging
│     └─ privacy/ ───────────────> GDPR compliance
│
├─ JavaScript Layer (Frontend)
│  └─ amd/src/editor.js
│     ├─ initMonaco() ──────────> Load & configure editor
│     ├─ handleRun() ───────────> Execute code
│     ├─ getAIFeedback() ───────> Request analysis
│     ├─ handleHint() ──────────> Display hints
│     └─ highlightError() ──────> Mark errors in code
│
├─ Database Layer
│  └─ db/install.xml
│     ├─ aicode_problems ──────> Problem definitions
│     ├─ aicode_attempts ──────> Student submissions
│     ├─ aicode_materials ─────> Learning resources
│     ├─ aicode_cache ─────────> AI response cache
│     └─ aicode_teacher_overrides ──> Manual feedback
│
└─ Language Layer
   └─ lang/en/aicode.php ────────> Strings & translations
```

### Executor Service Architecture

```
Executor Service (Node.js)
│
├─ HTTP Server (Express)
│  ├─ POST /run ──────────────> Execute code endpoint
│  └─ GET /health ────────────> Health check
│
├─ Validation Layer
│  ├─ validateCode() ─────────> Check length, patterns
│  └─ BLOCKED_PATTERNS[] ─────> Dangerous operation regex
│
├─ Execution Modes
│  ├─ vm2 Mode (Development)
│  │  ├─ VM instance ────────> Lightweight sandbox
│  │  ├─ Timeout ────────────> 2s default
│  │  └─ Sandbox scope ──────> Limited globals
│  │
│  └─ Docker Mode (Production)
│     ├─ Create temp file ──> Write code to /tmp
│     ├─ Docker run ────────> Isolated container
│     │  └─ Security flags
│     │     ├─ --network none
│     │     ├─ --memory 128m
│     │     ├─ --cpus 0.5
│     │     ├─ --read-only
│     │     └─ --security-opt no-new-privileges
│     └─ Cleanup ───────────> Remove temp files
│
└─ Response Formatter
   └─ {stdout, stderr, exitCode, trace, lintWarnings, executionTime}
```

### Analyzer Service Architecture

```
Analyzer Service (Node.js)
│
├─ HTTP Server (Express)
│  ├─ POST /analyze ──────────> Analysis endpoint
│  │  └─ Rate Limiter ────────> 100 req/15min per IP
│  └─ GET /health ────────────> Health check
│
├─ Pre-Processing Layer
│  ├─ anonymizeInput() ───────> Strip PII
│  │  ├─ Remove emails ──────> Regex replace
│  │  ├─ Remove URLs ────────> Regex replace
│  │  └─ Remove names ───────> Comment parsing
│  └─ createHash() ───────────> Payload hash for cache
│
├─ Cache Layer (node-cache)
│  ├─ Check cache ────────────> O(1) lookup
│  ├─ TTL: 1 hour ────────────> Configurable
│  └─ Store result ───────────> After Gemini call
│
├─ Gemini Integration
│  ├─ SYSTEM_PROMPT ──────────> Role definition + schema
│  ├─ FEW_SHOT_EXAMPLES ──────> 2 complete examples
│  ├─ callGemini() ───────────> HTTPS POST
│  │  ├─ Temperature: 0.0 ───> Deterministic
│  │  ├─ MaxTokens: 2048 ────> Response limit
│  │  └─ Model: gemini-pro ──> Latest model
│  └─ parseGeminiResponse() ──> Validate JSON
│
├─ Validation Layer
│  ├─ Check confidence ────────> >= threshold (0.6)
│  ├─ Validate schema ─────────> Required fields
│  └─ getFallbackResponse() ──> Rule-based backup
│
└─ Response Formatter
   └─ {diagnosis, location, hints, suggested_fix,
       recommended_materials, explainability}
```

## Security Layers

```
┌─────────────────────────────────────────────────────────┐
│ Layer 1: Network Isolation                              │
│ • Services on 127.0.0.1 only                            │
│ • Docker containers with --network none                 │
│ • No external access to execution environment           │
└─────────────────────────────────────────────────────────┘
                         ▼
┌─────────────────────────────────────────────────────────┐
│ Layer 2: Input Validation                               │
│ • Pattern blocking (regex)                              │
│ • Length limits (50KB code, 10KB output)                │
│ • Type checking (language, format)                      │
└─────────────────────────────────────────────────────────┘
                         ▼
┌─────────────────────────────────────────────────────────┐
│ Layer 3: Resource Limits                                │
│ • Memory: 128MB per container                           │
│ • CPU: 0.5 cores                                        │
│ • Timeout: 2 seconds                                    │
│ • Ephemeral containers (auto-cleanup)                   │
└─────────────────────────────────────────────────────────┘
                         ▼
┌─────────────────────────────────────────────────────────┐
│ Layer 4: Filesystem Protection                          │
│ • Read-only filesystem in Docker                        │
│ • Temporary files in isolated namespace                 │
│ • Non-root user (uid 1001)                              │
└─────────────────────────────────────────────────────────┘
                         ▼
┌─────────────────────────────────────────────────────────┐
│ Layer 5: Privilege Isolation                            │
│ • --security-opt no-new-privileges                      │
│ • No sudo/setuid binaries                               │
│ • Minimal container image (Alpine)                      │
└─────────────────────────────────────────────────────────┘
                         ▼
┌─────────────────────────────────────────────────────────┐
│ Layer 6: Data Privacy                                   │
│ • PII anonymization before external API                 │
│ • Code hash storage (not full code)                     │
│ • Opt-in for training data                              │
│ • GDPR-compliant privacy provider                       │
└─────────────────────────────────────────────────────────┘
```

## Scaling Considerations

### Horizontal Scaling

```
                     Load Balancer
                          │
          ┌───────────────┼───────────────┐
          │               │               │
          ▼               ▼               ▼
    Executor 1      Executor 2      Executor 3
    (port 3001)     (port 3001)     (port 3001)
          │               │               │
          └───────────────┴───────────────┘
                          │
                   Docker Swarm / K8s
                (Shared Worker Pool)

                     Load Balancer
                          │
          ┌───────────────┼───────────────┐
          │               │               │
          ▼               ▼               ▼
    Analyzer 1      Analyzer 2      Analyzer 3
    (port 3002)     (port 3002)     (port 3002)
          │               │               │
          └───────────────┴───────────────┘
                          │
                   Shared Cache (Redis)
```

### Performance Optimization

1. **Caching Strategy**

   - Response cache: 1-hour TTL
   - Request deduplication: Hash-based
   - Expected cache hit rate: 70%+

2. **Rate Limiting**

   - Per-student: 50 calls/day
   - Per-IP: 100 calls/15min
   - Configurable via settings

3. **Resource Management**
   - Docker worker pool
   - Connection pooling
   - Async/await throughout

## Deployment Topologies

### Topology 1: Single Server (Development)

```
┌─────────────────────────────────────┐
│  Server (localhost)                 │
│  ┌───────────────┐                  │
│  │  Moodle       │                  │
│  │  (Apache/PHP) │                  │
│  └───────────────┘                  │
│  ┌───────────────┐                  │
│  │  Executor     │                  │
│  │  (Node:3001)  │                  │
│  └───────────────┘                  │
│  ┌───────────────┐                  │
│  │  Analyzer     │                  │
│  │  (Node:3002)  │                  │
│  └───────────────┘                  │
│  ┌───────────────┐                  │
│  │  MySQL/PG     │                  │
│  └───────────────┘                  │
└─────────────────────────────────────┘
```

### Topology 2: Multi-Server (Production)

```
┌─────────────┐         ┌──────────────┐
│  Web Server │         │ App Server   │
│  (Nginx)    │────────>│  (Moodle)    │
│  SSL/TLS    │         │  PHP-FPM     │
└─────────────┘         └──────┬───────┘
                               │
                ┌──────────────┼──────────────┐
                │              │              │
         ┌──────▼─────┐ ┌─────▼─────┐ ┌─────▼─────┐
         │  Executor  │ │ Analyzer  │ │ Database  │
         │  Server    │ │  Server   │ │  Server   │
         │  (Docker)  │ │  (Node)   │ │ (MySQL)   │
         └────────────┘ └───────────┘ └───────────┘
```

### Topology 3: Cloud (Kubernetes)

```
                   ┌─────────────────┐
                   │  Ingress (HTTPS)│
                   └────────┬────────┘
                            │
              ┌─────────────┼─────────────┐
              │             │             │
        ┌─────▼─────┐ ┌────▼────┐ ┌─────▼─────┐
        │  Moodle   │ │Executor │ │ Analyzer  │
        │   Pods    │ │  Pods   │ │   Pods    │
        │  (x3)     │ │  (x5)   │ │   (x3)    │
        └───────────┘ └─────────┘ └───────────┘
              │             │             │
              └─────────────┼─────────────┘
                            │
                    ┌───────▼────────┐
                    │  Persistent    │
                    │  Volume        │
                    │  (Database)    │
                    └────────────────┘
```

---

For more details, see:

- [README.md](README.md) - Overview
- [INSTALL.md](INSTALL.md) - Installation
- [SECURITY.md](SECURITY.md) - Security model
- [services/\*/README.md](services/) - Service-specific docs
