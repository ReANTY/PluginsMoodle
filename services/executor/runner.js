/**
 * Runner script executed inside Docker container
 *
 * This script is the entrypoint for code execution inside the isolated container.
 * It reads the student code, executes it, and returns results.
 *
 * @copyright 2025 AICode Team
 * @license GPL-3.0
 */

// Note: This is a minimal runner. The actual execution happens via `node code.js`
// in the Docker container. This file is for reference and testing purposes.

// In production, the Docker container directly runs: node /workspace/code.js
// The security model relies on Docker isolation with:
// - No network access (--network none)
// - Limited memory (--memory=128m)
// - Limited CPU (--cpus=0.5)
// - Read-only filesystem (--read-only)
// - No new privileges (--security-opt=no-new-privileges)

console.log("AICode Docker Runner - Ready");
