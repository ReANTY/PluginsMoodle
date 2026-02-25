# Changelog

All notable changes to AICode will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Planned

- Python language support
- Java language support
- Visual debugger integration
- Collaborative coding features
- Plagiarism detection
- Custom AI model fine-tuning

## [1.0.0] - 2025-01-15

### Added

#### Moodle Plugin

- Initial release of AICode activity module for Moodle 5.0+
- Monaco editor integration with syntax highlighting and IntelliSense
- Real-time code execution with test case validation
- Multi-level hint system (progressive disclosure)
- Teacher override functionality for AI feedback
- Privacy API compliance (GDPR-ready)
- Support for anonymized attempts (opt-in training data)
- Capability-based permissions system
- Course activity grading integration
- Web service API for AJAX calls

#### Executor Service

- Secure code execution microservice (port 3001)
- Production mode: Docker container isolation
  - Network disabled (`--network none`)
  - Resource limits (128MB RAM, 0.5 CPU)
  - Read-only filesystem
  - Non-root user execution
  - Security options (`no-new-privileges`)
- Development mode: vm2 sandbox (lightweight alternative)
- Input validation and pattern blocking
- Configurable execution timeout (default 2s)
- Output truncation (max 10KB)
- Health check endpoint
- Comprehensive error handling

#### Analyzer Service

- AI-powered code analysis microservice (port 3002)
- Google Gemini API integration
- Structured JSON feedback schema:
  - Error diagnosis with confidence scores
  - Line/column location identification
  - Three-level progressive hints
  - Suggested fixes with code patches
  - Recommended learning materials
  - Explainability for transparency
- System prompt with few-shot examples
- PII anonymization before external API calls
- Response caching (1-hour TTL)
- Rate limiting (100 req/15min per IP)
- Fallback to rule-based responses
- Configurable confidence threshold
- Health check endpoint

#### Database Schema

- `mdl_aicode_problems`: Programming problem definitions
- `mdl_aicode_attempts`: Student submission tracking
- `mdl_aicode_materials`: Learning resource recommendations
- `mdl_aicode_cache`: AI response caching
- `mdl_aicode_teacher_overrides`: Teacher feedback corrections

#### Docker & Infrastructure

- Docker Compose configuration for services
- Executor worker Dockerfile (minimal Node.js Alpine)
- Analyzer service Dockerfile
- Development override configuration
- Environment variable template
- Makefile for common tasks
- Health checks for both services

#### Documentation

- Comprehensive README with quick start guide
- Detailed INSTALL.md with step-by-step instructions
- Plugin-specific README for Moodle directory
- Service-specific READMEs (executor, analyzer)
- SECURITY.md with threat model and best practices
- CONTRIBUTING.md with development guidelines
- Example problems (syntax, runtime, logic errors)
- API documentation for both services
- Architecture diagrams and file structure

#### Sample Problems

- Syntax error example (missing parenthesis)
- Runtime error example (undefined variable)
- Logic error example (off-by-one indexing)
- JSON format for importing problems

#### Developer Tools

- Unit test harnesses for both services
- Integration test examples
- ESLint configuration
- Git ignore rules
- Docker ignore rules
- License file (GPL-3.0)

### Security

- Docker isolation with strict security options
- Input validation against dangerous patterns
- PII anonymization before external API calls
- Rate limiting on analyzer service
- Session key validation on all write operations
- Capability checks on all Moodle operations
- Parameterized database queries (SQL injection protection)
- Output escaping (XSS protection)

### Performance

- Response caching reduces API calls by ~70%
- Request deduplication via payload hashing
- Lightweight development mode (vm2) for local testing
- Configurable resource limits prevent exhaustion
- Ephemeral Docker containers (auto-cleanup)

## Version Numbering

This project uses semantic versioning:

- **MAJOR**: Incompatible API changes or Moodle version requirements
- **MINOR**: New features (backward-compatible)
- **PATCH**: Bug fixes (backward-compatible)

## Upgrade Notes

### From Development to 1.0.0

This is the initial release. No upgrade path needed.

### Database Changes

- Initial schema creates 5 tables
- No migrations needed for 1.0.0

## Deprecation Notices

None for 1.0.0 (initial release).

## Contributors

### Core Team

- Project Lead: [Name]
- Backend Developer: [Name]
- Frontend Developer: [Name]
- Documentation: [Name]

### Community Contributors

Thank you to all contributors! See CONTRIBUTORS.md for the full list.

## Support

- Report bugs: https://github.com/your-org/aicode/issues
- Security issues: security@your-org.com
- Moodle forums: https://moodle.org/plugins/mod_aicode

---

[Unreleased]: https://github.com/your-org/aicode/compare/v1.0.0...HEAD
[1.0.0]: https://github.com/your-org/aicode/releases/tag/v1.0.0
