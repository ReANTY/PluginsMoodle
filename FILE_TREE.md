# AICode Complete File Tree

This document provides an overview of all files in the AICode plugin distribution.

## Complete Directory Structure

```
aicode/
│
├── README.md                              # Main documentation
├── INSTALL.md                             # Installation guide
├── LICENSE                                # GPL-3.0 license
├── SECURITY.md                            # Security policy and best practices
├── CONTRIBUTING.md                        # Contribution guidelines
├── CHANGELOG.md                           # Version history
├── FILE_TREE.md                           # This file
├── .gitignore                             # Git ignore rules
├── .dockerignore                          # Docker ignore rules
├── Makefile                               # Build automation
├── docker-compose.yml                     # Production Docker configuration
├── docker-compose.override.yml.example    # Development Docker override
│
├── mod/mod_aicode/                        # MOODLE PLUGIN
│   ├── README.md                          # Plugin-specific documentation
│   ├── version.php                        # Plugin version and metadata
│   ├── lib.php                            # Core plugin functions
│   ├── mod_form.php                       # Activity instance form
│   ├── view.php                           # Main view script
│   ├── renderer.php                       # Output renderer
│   ├── settings.php                       # Admin settings page
│   │
│   ├── amd/                               # JavaScript modules (AMD)
│   │   └── src/
│   │       └── editor.js                  # Monaco editor integration
│   │
│   ├── classes/                           # PHP classes
│   │   ├── event/
│   │   │   └── course_module_viewed.php   # View event
│   │   ├── external/
│   │   │   ├── run_code.php               # Web service: execute code
│   │   │   ├── analyze_code.php           # Web service: AI analysis
│   │   │   ├── record_hint.php            # Web service: record hint usage
│   │   │   └── send_to_teacher.php        # Web service: send to teacher
│   │   └── privacy/
│   │       └── provider.php               # Privacy API provider (GDPR)
│   │
│   ├── db/                                # Database definitions
│   │   ├── install.xml                    # Table schema (5 tables)
│   │   ├── access.php                     # Capability definitions
│   │   └── services.php                   # Web service definitions
│   │
│   ├── examples/                          # Sample problems
│   │   └── sample_problems.json           # 3 example problems (import ready)
│   │
│   ├── lang/                              # Language strings
│   │   └── en/
│   │       └── aicode.php                 # English strings
│   │
│   └── pix/                               # Graphics
│       ├── icon.svg                       # Plugin icon (64x64)
│       └── monologo.svg                   # Monochrome logo (32x32)
│
├── services/                              # MICROSERVICES
│   │
│   ├── executor/                          # Code execution service (port 3001)
│   │   ├── README.md                      # Executor documentation
│   │   ├── package.json                   # Node.js dependencies
│   │   ├── server.js                      # HTTP API server
│   │   ├── runner.js                      # Docker worker entrypoint
│   │   ├── Dockerfile                     # Worker image definition
│   │   └── test.js                        # Test harness
│   │
│   └── analyzer/                          # AI analysis service (port 3002)
│       ├── README.md                      # Analyzer documentation
│       ├── package.json                   # Node.js dependencies
│       ├── server.js                      # HTTP API + Gemini integration
│       ├── Dockerfile                     # Service image definition
│       └── test.js                        # Test harness
│
└── [runtime directories]                  # Created at runtime
    ├── node_modules/                      # Node.js packages (gitignored)
    ├── /tmp/aicode-jobs/                  # Temporary execution files
    └── moodledata/                        # Moodle data (if using local instance)
```

## File Count Summary

- **Moodle Plugin**: 22 files

  - PHP: 13 files
  - JavaScript: 1 file
  - XML/Database: 3 files
  - Language: 1 file
  - Graphics: 2 files
  - Documentation: 2 files

- **Microservices**: 12 files

  - Executor: 6 files
  - Analyzer: 6 files

- **Documentation**: 6 files
- **Configuration**: 6 files

**Total**: ~46 source files (excluding node_modules, build artifacts)

## Key Files Quick Reference

### Must Configure

| File                             | Purpose               | Required Action                                |
| -------------------------------- | --------------------- | ---------------------------------------------- |
| `.env`                           | Environment variables | Copy from `.env.example`, add `GEMINI_API_KEY` |
| `services/executor/package.json` | Executor dependencies | Run `npm install`                              |
| `services/analyzer/package.json` | Analyzer dependencies | Run `npm install`                              |

### Installation Entry Points

| File                          | Use When                     |
| ----------------------------- | ---------------------------- |
| `README.md`                   | Overview and quick start     |
| `INSTALL.md`                  | Step-by-step installation    |
| `mod/mod_aicode/README.md`    | Moodle-specific installation |
| `services/executor/README.md` | Executor service setup       |
| `services/analyzer/README.md` | Analyzer service setup       |

### Runtime Entry Points

| File                          | Purpose              | Port     |
| ----------------------------- | -------------------- | -------- |
| `services/executor/server.js` | Execute student code | 3001     |
| `services/analyzer/server.js` | Analyze code with AI | 3002     |
| `mod/mod_aicode/view.php`     | Moodle activity view | (Moodle) |

### Development Files

| File                                  | Purpose                   |
| ------------------------------------- | ------------------------- |
| `Makefile`                            | Build automation commands |
| `docker-compose.yml`                  | Production services       |
| `docker-compose.override.yml.example` | Dev mode configuration    |
| `services/*/test.js`                  | Unit tests                |

### Documentation Files

| File              | Purpose                   |
| ----------------- | ------------------------- |
| `README.md`       | Main documentation        |
| `INSTALL.md`      | Installation instructions |
| `SECURITY.md`     | Security policy           |
| `CONTRIBUTING.md` | Contribution guidelines   |
| `CHANGELOG.md`    | Version history           |
| `LICENSE`         | GPL-3.0 license           |

## Database Tables (Created by Moodle Installer)

Created from `mod/mod_aicode/db/install.xml`:

1. **mdl_aicode_problems** - Problem definitions
2. **mdl_aicode_attempts** - Student submissions
3. **mdl_aicode_materials** - Learning resources
4. **mdl_aicode_cache** - AI response cache
5. **mdl_aicode_teacher_overrides** - Teacher corrections

## APIs Exposed

### Executor Service (port 3001)

- `POST /run` - Execute code
- `GET /health` - Health check

### Analyzer Service (port 3002)

- `POST /analyze` - Analyze code with AI
- `GET /health` - Health check

### Moodle Web Services

Defined in `mod/mod_aicode/db/services.php`:

- `mod_aicode_run_code` - Execute code (calls executor)
- `mod_aicode_analyze_code` - Analyze code (calls analyzer)
- `mod_aicode_record_hint` - Record hint usage
- `mod_aicode_send_to_teacher` - Send code to teacher

## Build Artifacts (Not in Distribution)

These are generated at runtime:

```
services/executor/node_modules/
services/analyzer/node_modules/
mod/mod_aicode/amd/build/*.js     # Minified AMD modules (if built)
.env                               # Environment config (copy from .env.example)
```

## Security-Sensitive Files

⚠️ **Never commit these:**

- `.env` - Contains API keys
- `config.php` - Moodle database credentials
- `moodledata/` - User data
- Private keys or certificates

✅ **Already gitignored** via `.gitignore`

## Deployment Checklist

### Development

- [ ] Copy `.env.example` to `.env`
- [ ] Add `GEMINI_API_KEY` to `.env`
- [ ] Run `make install` (install dependencies)
- [ ] Run `make dev` (start in development mode)
- [ ] Copy `mod/mod_aicode` to Moodle
- [ ] Install via Moodle admin panel

### Production

- [ ] All development steps above
- [ ] Build Docker images: `make build`
- [ ] Start services: `make start` (or systemd)
- [ ] Configure reverse proxy (nginx/Apache)
- [ ] Set up SSL certificates
- [ ] Configure firewall (block 3001, 3002 externally)
- [ ] Set up monitoring and logging
- [ ] Configure backups

## Support Files

### Testing

- `services/executor/test.js` - Executor unit tests
- `services/analyzer/test.js` - Analyzer unit tests
- `mod/mod_aicode/examples/sample_problems.json` - Example problems

### Automation

- `Makefile` - Build commands
- `.github/workflows/` - CI/CD (if added)

### Graphics

- `mod/mod_aicode/pix/icon.svg` - 64x64 plugin icon
- `mod/mod_aicode/pix/monologo.svg` - 32x32 monochrome logo

## File Size Estimates

- **Moodle plugin**: ~150 KB (excluding node_modules)
- **Executor service**: ~50 KB source + ~15 MB node_modules
- **Analyzer service**: ~60 KB source + ~20 MB node_modules
- **Documentation**: ~100 KB
- **Docker images**: ~200 MB (Node.js Alpine base)

**Total distribution**: ~35 MB (with node_modules)
**Total with Docker images**: ~235 MB

## Modification Guide

### Adding a New Language (e.g., Python)

Files to modify:

1. `mod/mod_aicode/lang/en/aicode.php` - Add language string
2. `services/executor/server.js` - Add Python execution logic
3. `services/analyzer/server.js` - Update system prompt for Python
4. `mod/mod_aicode/amd/src/editor.js` - Add Monaco Python mode
5. `mod/mod_aicode/mod_form.php` - Add to language dropdown

### Adding a New Web Service

Files to create/modify:

1. Create `mod/mod_aicode/classes/external/your_service.php`
2. Add to `mod/mod_aicode/db/services.php`
3. Update `mod/mod_aicode/amd/src/editor.js` to call new service
4. Add capability to `mod/mod_aicode/db/access.php` (if needed)
5. Add language strings to `mod/mod_aicode/lang/en/aicode.php`

### Customizing AI Feedback

Files to modify:

1. `services/analyzer/server.js` - Update `SYSTEM_PROMPT`
2. `services/analyzer/server.js` - Modify `FEW_SHOT_EXAMPLES`
3. `services/analyzer/server.js` - Adjust `parseGeminiResponse()` schema
4. `mod/mod_aicode/amd/src/editor.js` - Update UI to show new fields

## Version Control

### Main Branch Protection

Protect these files from direct modification:

- `version.php` - Version number (bump via release process)
- `db/install.xml` - Schema (use upgrade.php for changes)
- `db/services.php` - API contracts (maintain backward compatibility)

### Branching Strategy

- `main` - Stable releases
- `develop` - Integration branch
- `feature/*` - New features
- `fix/*` - Bug fixes
- `release/*` - Release preparation

## Troubleshooting File-Specific Issues

| Error                    | File to Check                       |
| ------------------------ | ----------------------------------- |
| "Service unavailable"    | `docker-compose.yml`, `.env`        |
| "Table not found"        | `mod/mod_aicode/db/install.xml`     |
| "Permission denied"      | `mod/mod_aicode/db/access.php`      |
| "String not found"       | `mod/mod_aicode/lang/en/aicode.php` |
| "JavaScript error"       | `mod/mod_aicode/amd/src/editor.js`  |
| "API key invalid"        | `.env`, check `GEMINI_API_KEY`      |
| "Docker image not found" | Run `make build`                    |

## License Information

All files are licensed under **GPL-3.0-or-later** (see `LICENSE` file).

### Third-Party Dependencies

- **Monaco Editor**: MIT License (Microsoft)
- **Express.js**: MIT License (OpenJS Foundation)
- **vm2**: MIT License (Patrik Simek)
- **Google Gemini API**: Google Terms of Service

See individual `package.json` files for complete dependency lists.

---

Generated: 2025-01-15
Version: 1.0.0
