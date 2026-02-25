# AICode Moodle Plugin

Moodle 5.0+ activity module for interactive programming education with AI-powered feedback.

## Installation

### Method 1: Copy Files

1. Copy this directory to your Moodle installation:

   ```bash
   cp -r mod/mod_aicode /path/to/moodle/mod/aicode
   ```

2. Navigate to Moodle admin panel:

   ```
   http://your-moodle/admin
   ```

3. Follow the installation prompts to create database tables.

### Method 2: ZIP Upload

1. Create a ZIP file of this directory
2. In Moodle, go to: **Site administration → Plugins → Install plugins**
3. Upload the ZIP file
4. Follow the installation prompts

## Configuration

After installation, configure the plugin at:
**Site administration → Plugins → Activity modules → AICode**

Required settings:

- **Executor URL**: URL of the executor microservice (default: `http://127.0.0.1:3001`)

Optional settings:

- **Confidence Threshold**: Minimum AI confidence score (default: 0.6)
- **Cache TTL**: Cache duration in seconds (default: 3600)
- **Max Calls per Day**: Rate limit per student (default: 50)
- **Execution Timeout**: Code execution timeout in seconds (default: 2)

AI feedback is provided by Moodle AI providers. Configure Ollama at:
**Site administration -> AI -> AI providers**

## Service Setup

This plugin requires:

1. **Executor Service** (port 3001): Runs student code in isolation
2. **Moodle AI provider** for text generation (recommended: `aiprovider_ollama`)

See the main `README.md` in the repository root for setup instructions.

## File Structure

```
mod/mod_aicode/
├── version.php           # Plugin version and dependencies
├── lib.php               # Core plugin functions
├── mod_form.php          # Activity instance settings form
├── view.php              # Main view script
├── renderer.php          # Output renderer
├── settings.php          # Admin settings
├── db/
│   ├── install.xml       # Database schema
│   ├── access.php        # Capability definitions
│   └── services.php      # Web service definitions
├── classes/
│   ├── event/            # Event definitions
│   ├── external/         # Web service API classes
│   ├── output/           # Renderable classes
│   └── privacy/          # Privacy API provider
├── lang/en/
│   └── aicode.php        # English language strings
├── amd/src/
│   └── editor.js         # Monaco editor integration
├── pix/
│   ├── icon.svg          # Plugin icon
│   └── monologo.svg      # Monochrome logo
└── README.md             # This file
```

## Usage for Teachers

### Creating a Problem

1. Turn editing on in a course
2. Add an activity or resource → AICode — AI Programming Lab
3. Fill in:
   - **Name**: Problem title
   - **Description**: Problem statement and requirements
   - **Language**: JavaScript (currently only supported language)
   - **Test Cases**: JSON array of test inputs/outputs
   - **Starter Code**: Template code for students
4. Save

### Test Cases Format

Test cases should be a JSON array:

```json
[
  { "input": "[1, 2, 3]", "expected": "6" },
  { "input": "[10, 20, 30]", "expected": "60" }
]
```

### Viewing Student Attempts

1. Go to the activity
2. Click **View attempts** (requires `mod/aicode:viewattempts` capability)
3. See all student submissions with:
   - Code hash
   - Execution results
   - Hints used
   - AI feedback received

## Usage for Students

1. Open the AICode activity
2. Read the problem description
3. Write code in the Monaco editor
4. Click **Run** to execute your code
5. View output and errors
6. If there are errors, get AI feedback with:
   - **Diagnosis**: What type of error
   - **Location**: Where in your code
   - **Suggested Fix**: How to fix it
7. Request hints (3 levels):
   - **Level 1**: Gentle nudge
   - **Level 2**: Direct pointer
   - **Level 3**: Explicit solution
8. Click **Send to Teacher** to request help

## Capabilities

- `mod/aicode:addinstance`: Add a new AICode activity (teachers, managers)
- `mod/aicode:view`: View AICode activity (all users)
- `mod/aicode:submit`: Submit code for execution (students)
- `mod/aicode:viewattempts`: View all student attempts (teachers)
- `mod/aicode:overridefeedback`: Override AI feedback (teachers, managers)

## Privacy

This plugin complies with Moodle's privacy API (GDPR):

- Stores student code hashes (not full code)
- Records hint usage for grading
- Can anonymize attempts for training (opt-in)
- Provides data export and deletion

### Data Sent to External Services

**Moodle AI provider** (for example Ollama) receives:

- Anonymized code snippets (PII stripped)
- Error messages (anonymized)
- No student identifiers

Students and teachers should be aware that anonymized code is sent to the configured Moodle AI provider for analysis.

## Uninstallation

1. Go to: **Site administration → Plugins → Activity modules → AICode**
2. Click **Uninstall**
3. Confirm deletion

This will:

- Remove all AICode activities
- Delete all student attempts
- Drop database tables
- Remove plugin files (manual step if files remain)

## Upgrading

1. Replace plugin files:
   ```bash
   cp -r mod/mod_aicode /path/to/moodle/mod/aicode
   ```
2. Visit: **Site administration → Notifications**
3. Follow upgrade prompts

Always backup your database before upgrading!

## Troubleshooting

### "Service unavailable" errors

- Verify executor service is running
- Verify Moodle AI provider is configured and enabled
- Ensure services are accessible from Moodle server

### Monaco editor not loading

- Check browser console for errors
- Verify CDN access (or serve Monaco locally)
- Clear Moodle caches: **Site administration → Development → Purge all caches**

### AI feedback not working

- Verify Moodle AI provider is configured in Site administration -> AI -> AI providers
- Ensure Ollama server is reachable and model is available (if using Ollama)
- Ensure rate limits not exceeded
- Try lowering confidence threshold

## Support

For issues, questions, or contributions, please visit:

- Repository: https://github.com/your-org/aicode
- Moodle plugins: https://moodle.org/plugins/mod_aicode

## License

GPL-3.0 or later
