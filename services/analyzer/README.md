# AICode Analyzer Service

AI-powered code analysis service for the AICode Moodle plugin. Uses Google Gemini to analyze student code errors and provide structured educational feedback.

## Features

- **AI-Powered Analysis**: Uses Google Gemini API with carefully crafted prompts
- **Structured Feedback**: Returns JSON with diagnosis, hints, fixes, and learning materials
- **Anonymization**: Strips PII from code before sending to Gemini
- **Caching**: Caches responses to reduce API calls and costs
- **Rate Limiting**: Protects against abuse with request rate limits
- **Fallback**: Rule-based feedback when AI is unavailable or low confidence

## Installation

```bash
cd services/analyzer
npm install
```

## Configuration

Set these environment variables:

```bash
export GEMINI_API_KEY="your-gemini-api-key-here"
export GEMINI_MODEL="gemini-1.5-flash-latest"
export GEMINI_API_URL="https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash-latest:generateContent"
export CONFIDENCE_THRESHOLD="0.6"
export PORT="3002"
```

### Getting a Gemini API Key

1. Visit https://makersuite.google.com/app/apikey
2. Sign in with your Google account
3. Click "Create API Key"
4. Copy the key and set it as `GEMINI_API_KEY`

## Running the Service

```bash
npm start
```

Or with custom configuration:

```bash
GEMINI_API_KEY=your-key GEMINI_MODEL=gemini-1.5-flash-latest PORT=3002 npm start
```

## API

### POST /analyze

Analyze code and provide feedback.

**Request:**

```json
{
  "codeSnippet": "function sum(arr) { ... }",
  "language": "javascript",
  "stderr": "TypeError: Cannot read property...",
  "failingTests": [],
  "trace": "",
  "studentLevel": "beginner"
}
```

**Response:**

```json
{
  "diagnosis": {
    "category": "off-by-one",
    "confidence": 0.95,
    "message_short": "Loop condition uses <= instead of <",
    "message_long": "The for loop is iterating one time too many..."
  },
  "location": {
    "line": 3,
    "column": 20,
    "snippet": "i <= arr.length"
  },
  "hints": [
    { "level": 1, "hint": "Check how many times your loop runs..." },
    { "level": 2, "hint": "Array indices start at 0..." },
    { "level": 3, "hint": "Change the loop condition from <= to <" }
  ],
  "suggested_fix": {
    "explanation": "Change the loop condition...",
    "code_patch": "for (let i = 0; i < arr.length; i++) {"
  },
  "recommended_materials": [
    {
      "topic": "loop-bounds",
      "title": "Understanding Array Loops",
      "type": "video",
      "moodle_resource_id": 0,
      "score": 0.92
    }
  ],
  "explainability": "Error message indicates undefined access..."
}
```

### GET /health

Health check endpoint.

**Response:**

```json
{
  "status": "ok",
  "gemini_configured": true,
  "cache_keys": 42,
  "uptime": 12345
}
```

## Testing

```bash
# Start the service first
GEMINI_API_KEY=your-key npm start

# In another terminal
npm test
```

## Prompt Engineering

The service uses a carefully crafted system prompt with few-shot examples to ensure Gemini returns valid JSON. The prompt:

1. **Sets the role**: "You are an expert programming tutor..."
2. **Specifies output format**: Exact JSON schema with all fields
3. **Provides examples**: Two complete examples showing input → output
4. **Emphasizes constraints**: "respond with ONLY valid JSON"

### Few-Shot Examples

The service includes two complete examples:

1. **Off-by-one error**: Loop condition mistake with array access
2. **Syntax error**: Missing parenthesis in function call

These examples teach Gemini the expected format and level of detail.

## Privacy & Anonymization

Before sending code to Gemini, the service:

- Removes email addresses
- Removes URLs
- Removes potential names in comments
- Limits input length to 5KB

No student identifiers are ever sent to Gemini.

## Caching Strategy

Responses are cached based on a hash of:

- Anonymized code snippet
- Anonymized error message

Cache TTL: 1 hour (configurable via NodeCache)

This reduces:

- API costs
- Response latency
- Rate limit issues

## Rate Limiting

Default limits:

- 100 requests per 15 minutes per IP
- Configurable via `express-rate-limit`

## Fallback Behavior

The service falls back to rule-based responses when:

1. Gemini API is unavailable
2. API key is missing
3. Response parsing fails
4. Confidence score < threshold (default 0.6)

Fallback responses provide basic categorization based on error type:

- SyntaxError → syntax category
- ReferenceError → runtime category
- TypeError → runtime category

## Docker Deployment

Build the image:

```bash
docker build -t aicode-analyzer:latest .
```

Run the container:

```bash
docker run -d \
  -p 3002:3002 \
  -e GEMINI_API_KEY=your-key \
  --name aicode-analyzer \
  aicode-analyzer:latest
```

## Troubleshooting

### "GEMINI_API_KEY not configured"

Set the environment variable:

```bash
export GEMINI_API_KEY="your-key-here"
```

### "No response from Gemini"

Check:

1. API key is valid
2. You have quota remaining
3. Network connectivity to googleapis.com

### "Failed to parse Gemini response"

The service will automatically fall back to rule-based responses. This can happen if:

- Gemini returns non-JSON (rare with good prompts)
- Response is malformed
- Temperature too high (use 0.0)

### Low confidence scores

Adjust the threshold:

```bash
CONFIDENCE_THRESHOLD=0.5 npm start
```

## Production Considerations

1. **API Costs**: Gemini charges per token. Monitor usage and set quotas.
2. **Rate Limits**: Adjust based on your student population.
3. **Caching**: Increase TTL for more cache hits.
4. **Monitoring**: Log all API calls and failures.
5. **Backup**: Always have rule-based fallback enabled.

## License

GPL-3.0 - See LICENSE file
