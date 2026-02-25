# Dockerfile for AICode Executor Worker
# 
# This image is used to execute student code in isolation.
# It provides a minimal Node.js environment with no network access.

FROM node:18-alpine

# Create non-root user
RUN addgroup -g 1001 -S aicode && \
    adduser -u 1001 -S aicode -G aicode

# Create workspace directory
RUN mkdir -p /workspace && \
    chown aicode:aicode /workspace

# Switch to non-root user
USER aicode

# Set working directory
WORKDIR /workspace

# Default command (will be overridden)
CMD ["node", "--version"]

# Security:
# - Run as non-root user
# - No package installation (minimal attack surface)
# - Container is used with --network=none, --read-only, --memory, --cpus limits
# - Ephemeral containers (--rm flag) ensure no persistence

