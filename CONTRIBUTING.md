# Contributing to AICode

Thank you for considering contributing to AICode! This document provides guidelines and instructions for contributing.

## Code of Conduct

### Our Pledge

We pledge to make participation in our project a harassment-free experience for everyone, regardless of age, body size, disability, ethnicity, gender identity and expression, level of experience, education, socio-economic status, nationality, personal appearance, race, religion, or sexual identity and orientation.

### Our Standards

**Positive behaviors:**

- Using welcoming and inclusive language
- Being respectful of differing viewpoints
- Gracefully accepting constructive criticism
- Focusing on what is best for the community
- Showing empathy towards other community members

**Unacceptable behaviors:**

- Trolling, insulting/derogatory comments, and personal or political attacks
- Public or private harassment
- Publishing others' private information without permission
- Other conduct which could reasonably be considered inappropriate

## How to Contribute

### Reporting Bugs

Before submitting a bug report:

1. **Check existing issues**: Search for similar issues first
2. **Test on latest version**: Verify the bug exists in the latest release
3. **Isolate the problem**: Create a minimal reproduction case

Submit a bug report including:

- **Summary**: Brief description of the issue
- **Steps to reproduce**: Detailed steps to trigger the bug
- **Expected behavior**: What should happen
- **Actual behavior**: What actually happens
- **Environment**:
  - Moodle version
  - PHP version
  - Node.js version
  - Docker version (if relevant)
  - Browser (for frontend issues)
- **Logs**: Relevant error messages or logs
- **Screenshots**: If applicable

Use the bug report template: `.github/ISSUE_TEMPLATE/bug_report.md`

### Suggesting Enhancements

Enhancement suggestions are welcome! Include:

- **Use case**: Why is this feature needed?
- **Proposed solution**: How should it work?
- **Alternatives considered**: Other approaches you've thought of
- **Additional context**: Examples, mockups, etc.

Use the feature request template: `.github/ISSUE_TEMPLATE/feature_request.md`

### Pull Requests

#### Before You Start

1. **Open an issue first**: Discuss major changes before implementing
2. **Check existing PRs**: Someone might already be working on it
3. **Fork the repository**: Don't commit directly to main

#### Development Setup

```bash
# Fork and clone
git clone https://github.com/YOUR-USERNAME/aicode.git
cd aicode

# Install dependencies
make install

# Create a branch
git checkout -b feature/your-feature-name

# Start services in dev mode
make dev
```

#### Coding Standards

**PHP (Moodle plugin):**

- Follow [Moodle Coding Style](https://moodledev.io/general/development/policies/codingstyle)
- Use PHPDoc comments
- Run Moodle code checker:
  ```bash
  php /path/to/moodle/local/codechecker/run.php mod/mod_aicode
  ```

**JavaScript (Frontend):**

- Follow Moodle's JavaScript guidelines
- Use ES6+ features where supported
- Add JSDoc comments for functions
- Run ESLint:
  ```bash
  npm run lint
  ```

**JavaScript (Node.js services):**

- Follow [Airbnb JavaScript Style Guide](https://github.com/airbnb/javascript)
- Use async/await over callbacks
- Add JSDoc for public functions
- Run ESLint:
  ```bash
  cd services/executor && npm run lint
  cd services/analyzer && npm run lint
  ```

**General:**

- Use meaningful variable names
- Keep functions small and focused
- Add comments for complex logic
- Write tests for new features

#### Testing

**Before submitting a PR:**

1. **Test executor service:**

   ```bash
   cd services/executor
   npm test
   ```

2. **Test analyzer service:**

   ```bash
   cd services/analyzer
   GEMINI_API_KEY=your-key npm test
   ```

3. **Test Moodle integration:**

   - Install plugin in a test Moodle instance
   - Create a sample activity
   - Test as student and teacher
   - Check browser console for errors

4. **Test both execution modes:**
   - Production (Docker)
   - Development (vm2)

#### Commit Messages

Use conventional commits format:

```
type(scope): short description

Longer description if needed.

Fixes #123
```

**Types:**

- `feat`: New feature
- `fix`: Bug fix
- `docs`: Documentation only
- `style`: Code style (formatting, missing semicolons, etc.)
- `refactor`: Code change that neither fixes a bug nor adds a feature
- `perf`: Performance improvement
- `test`: Adding or updating tests
- `chore`: Maintenance tasks

**Examples:**

```
feat(analyzer): add support for Python language

Implements Python code analysis with similar structure to JavaScript.
Includes error categorization and hint generation.

Closes #45

fix(executor): prevent timeout when code has infinite loop

Added stricter timeout enforcement at VM level.

Fixes #78

docs(readme): update installation instructions for macOS

Added Homebrew installation method and troubleshooting tips.
```

#### Pull Request Process

1. **Update documentation**: If you change functionality, update README/docs
2. **Add tests**: New features should include tests
3. **Update CHANGELOG**: Add your changes to CHANGELOG.md (Unreleased section)
4. **Run all tests**: Ensure nothing breaks
5. **Update version**: Bump version if applicable (discuss with maintainers)
6. **Submit PR**: Use the PR template

**PR Checklist:**

- [ ] Code follows style guidelines
- [ ] Self-review completed
- [ ] Comments added for complex code
- [ ] Documentation updated
- [ ] Tests pass
- [ ] No new warnings
- [ ] CHANGELOG.md updated

**PR Review Process:**

1. Maintainers will review within 1 week
2. Address feedback in new commits (don't force-push during review)
3. Once approved, maintainer will merge
4. PR will be included in next release

## Project Structure

```
aicode/
├── mod/mod_aicode/          # Moodle plugin
│   ├── amd/src/             # JavaScript (AMD modules)
│   ├── classes/             # PHP classes
│   ├── db/                  # Database definitions
│   ├── lang/en/             # Language strings
│   └── pix/                 # Icons/images
├── services/
│   ├── executor/            # Code execution service
│   └── analyzer/            # AI analysis service
├── docs/                    # Documentation
└── tests/                   # Integration tests
```

## Development Workflow

### Feature Development

```bash
# Start from main
git checkout main
git pull origin main

# Create feature branch
git checkout -b feature/my-feature

# Make changes
# ... edit files ...

# Test locally
make test

# Commit changes
git add .
git commit -m "feat(scope): description"

# Push to your fork
git push origin feature/my-feature

# Open PR on GitHub
```

### Bug Fix

```bash
# Create bugfix branch
git checkout -b fix/issue-123

# Fix the bug
# ... edit files ...

# Test the fix
make test

# Commit
git commit -m "fix(scope): description

Fixes #123"

# Push and create PR
git push origin fix/issue-123
```

## Testing Guidelines

### Unit Tests

Write unit tests for:

- Utility functions
- Data validation
- Business logic

**Example (Node.js):**

```javascript
describe("anonymizeCode", () => {
  it("should remove email addresses", () => {
    const input = "contact me at student@example.com";
    const output = anonymizeCode(input);
    expect(output).not.toContain("student@example.com");
    expect(output).toContain("[EMAIL]");
  });
});
```

### Integration Tests

Test interactions between components:

- Moodle ↔ Executor
- Moodle ↔ Analyzer
- Executor ↔ Docker

### Manual Testing

Use the test problems in `mod/mod_aicode/examples/` to verify:

- Syntax errors are caught
- Runtime errors get AI feedback
- Logic errors are analyzed correctly
- Hints are helpful
- UI is responsive

## Documentation

### Code Documentation

**PHP:**

```php
/**
 * Execute student code in a secure sandbox.
 *
 * @param string $code The code to execute
 * @param string $language Programming language
 * @return array Execution result with stdout, stderr, exitCode
 * @throws moodle_exception If validation fails
 */
function execute_code($code, $language) {
    // ...
}
```

**JavaScript:**

```javascript
/**
 * Analyze code and provide AI feedback.
 *
 * @param {string} code - The code to analyze
 * @param {string} stderr - Error output
 * @returns {Promise<Object>} AI feedback with diagnosis and hints
 */
async function analyzeCode(code, stderr) {
  // ...
}
```

### User Documentation

Update README.md sections:

- Features list
- Installation steps
- Configuration options
- API documentation
- Troubleshooting

### Changelog

Add entries to CHANGELOG.md under `[Unreleased]`:

```markdown
## [Unreleased]

### Added

- Python language support (#45)
- Visual debugger integration (#67)

### Changed

- Improved AI prompt for better feedback quality (#89)

### Fixed

- Executor timeout not being enforced (#78)
- Monaco editor not loading on Safari (#92)
```

## Getting Help

- **Discord**: https://discord.gg/aicode (real-time chat)
- **Moodle Forums**: https://moodle.org/plugins/mod_aicode
- **GitHub Discussions**: https://github.com/your-org/aicode/discussions
- **Email**: dev@your-org.com

## Recognition

Contributors will be:

- Listed in CONTRIBUTORS.md
- Mentioned in release notes
- Credited in significant feature announcements

Thank you for contributing to AICode! 🎉

---

Questions about contributing? Open a discussion: https://github.com/your-org/aicode/discussions
