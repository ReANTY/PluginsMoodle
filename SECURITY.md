# Security Policy

## Overview

AICode takes security seriously. This document outlines our security model, known limitations, and how to report vulnerabilities.

## Security Model

### Code Execution Isolation

#### Production Mode (Docker)

Student code runs in isolated Docker containers with:

- **No network access**: `--network none` flag prevents all network operations
- **Memory limits**: `--memory=128m` caps memory usage
- **CPU throttling**: `--cpus=0.5` limits CPU consumption
- **Read-only filesystem**: `--read-only` prevents file writes
- **No privilege escalation**: `--security-opt=no-new-privileges`
- **Ephemeral containers**: `--rm` ensures containers are destroyed immediately
- **Non-root user**: Runs as uid 1001 (aicode user)

#### Development Mode (vm2)

- Runs in Node.js VM2 sandbox
- JavaScript eval disabled
- WebAssembly disabled
- Timeout enforcement
- Less secure than Docker - **use only for local development**

### Input Validation

All code submissions are validated before execution:

1. **Length checks**: Max 50KB code size
2. **Pattern blocking**: Regex filters for dangerous operations:
   - `child_process`, `spawn`, `exec`
   - Filesystem operations (`fs.write`)
   - Network modules (`require('http')`, `fetch`)
   - Process manipulation
3. **Timeout enforcement**: 2-second default (configurable)
4. **Output truncation**: Max 10KB output

### API Security

#### Executor Service (port 3001)

- Listens on `127.0.0.1` only (localhost)
- No authentication (relies on Moodle session validation)
- Rate limiting recommended via reverse proxy

#### Analyzer Service (port 3002)

- Express rate limiter: 100 requests per 15 minutes per IP
- Listens on `127.0.0.1` only
- Response caching to reduce API abuse

### Privacy & Data Protection

#### PII Anonymization

Before sending to Gemini API, the analyzer:

- Strips email addresses
- Removes URLs
- Removes names in comments (heuristic)
- Limits snippet length to 5KB

**What is NOT sent to Gemini:**

- Student names
- Student IDs
- User emails
- IP addresses
- Session tokens

**What IS sent to Gemini:**

- Anonymized code snippet
- Anonymized error messages
- Programming language
- Generic student level (beginner/intermediate/advanced)

#### Data Storage

Moodle database stores:

- **Code hashes** (SHA-256), not full code
- Execution results (stdout/stderr, truncated)
- Hint usage counts
- AI feedback (cached)

**Anonymized mode**: When `allow_training` is enabled:

- `userid` field is NULL
- `is_anonymous` flag set to 1
- Used only for opt-in training data

### Moodle Integration Security

- **Capability checks**: All API calls validate user permissions
- **Session validation**: `confirm_sesskey()` on all write operations
- **SQL injection protection**: Uses Moodle DML with parameterized queries
- **XSS protection**: Output escaped via Moodle renderers
- **CSRF protection**: Session keys required for all state changes

## Known Limitations

### 1. Resource Exhaustion

**Issue**: Malicious code could attempt CPU/memory exhaustion.

**Mitigation**:

- Docker resource limits (128MB RAM, 0.5 CPU)
- 2-second timeout
- Container auto-removal

**Residual risk**: Low. Containers are ephemeral and limits are enforced by kernel.

### 2. Side-Channel Attacks

**Issue**: Timing attacks could infer information about test cases.

**Mitigation**:

- Execution time included in response
- Test cases not visible to students (by default)

**Residual risk**: Low. Minimal sensitive information exposed via timing.

### 3. Gemini API Abuse

**Issue**: Students could spam analyzer service to exhaust API quota.

**Mitigation**:

- Rate limiting: 50 calls per student per day (configurable)
- Response caching (1-hour TTL)
- Request deduplication via hash

**Residual risk**: Medium. Determined attacker could still cause API costs.

### 4. Docker-in-Docker Risks

**Issue**: Executor service requires Docker socket access.

**Mitigation**:

- Use Docker API with minimal permissions
- Consider using Docker API via TCP with TLS
- Alternative: Use vm2 for development

**Residual risk**: Medium in shared hosting. Recommend dedicated VM for production.

## Best Practices

### For System Administrators

1. **Run services on isolated server**: Don't run on same host as Moodle web server if possible.
2. **Use firewall**: Block external access to ports 3001 and 3002.
3. **Monitor resource usage**: Set up alerts for unusual CPU/memory/disk usage.
4. **Rotate API keys**: Change Gemini API key periodically.
5. **Review logs**: Check service logs for suspicious patterns.
6. **Keep updated**: Apply security updates promptly.

### For Educators

1. **Review starter code**: Ensure starter templates don't contain unsafe patterns.
2. **Monitor submissions**: Check for repeated attempts to bypass validation.
3. **Set reasonable limits**: Adjust max calls per day based on course needs.
4. **Educate students**: Explain what data is sent to external APIs.

### For Developers

1. **Never skip validation**: Always validate input on both client and server.
2. **Use parameterized queries**: Never build SQL with string concatenation.
3. **Escape output**: Use Moodle's rendering functions.
4. **Check capabilities**: Validate user permissions before any action.
5. **Audit dependencies**: Regularly check for vulnerable npm packages.

## Reporting Vulnerabilities

**Please DO NOT open public GitHub issues for security vulnerabilities.**

Instead:

1. **Email**: security@your-org.com
2. **PGP Key**: Available at https://your-org.com/pgp.asc
3. **Include**:
   - Description of vulnerability
   - Steps to reproduce
   - Potential impact
   - Suggested fix (optional)

We will:

- Acknowledge receipt within 24 hours
- Provide initial assessment within 72 hours
- Work on a fix with priority based on severity
- Credit you in the security advisory (unless you prefer anonymity)

## Security Updates

Subscribe to security announcements:

- GitHub Security Advisories: https://github.com/your-org/aicode/security/advisories
- Mailing list: security-announce@your-org.com
- RSS feed: https://your-org.com/security/feed.xml

## Compliance

### GDPR

AICode complies with GDPR requirements:

- ✅ Data minimization (stores hashes, not full code)
- ✅ Purpose limitation (data used only for education)
- ✅ Storage limitation (configurable cache TTL)
- ✅ Data portability (export via Moodle privacy API)
- ✅ Right to erasure (delete via Moodle privacy API)
- ✅ Privacy by design (anonymization before external API calls)

### FERPA (US)

AICode can be configured to comply with FERPA:

- Enable `allow_training=0` (default) to prevent data sharing
- PII is never sent to external services
- Student identifiers remain within Moodle

### Other Regulations

Consult your legal team for:

- COPPA (if serving children under 13)
- State-specific education privacy laws
- Institutional data governance policies

## Audit Log

Security-relevant events logged:

- Code execution attempts (success/failure)
- AI analysis requests (with rate limit tracking)
- Capability violations (logged by Moodle)
- Service health checks

Access logs via:

```bash
# Executor
docker-compose logs executor | grep ERROR

# Analyzer
docker-compose logs analyzer | grep ERROR

# Moodle
tail -f /var/log/apache2/error.log | grep aicode
```

## Penetration Testing

We welcome responsible security testing. Please:

1. Test only on your own installations
2. Don't attempt to access other users' data
3. Don't cause denial of service
4. Report findings via responsible disclosure

## License

This security policy is licensed under CC-BY-4.0.

---

Last updated: 2025-01-15
