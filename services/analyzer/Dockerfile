# Dockerfile for AICode Analyzer Service

FROM node:18-alpine

# Create app directory
WORKDIR /app

# Copy package files
COPY package*.json ./

# Install dependencies
RUN npm ci --only=production

# Copy source code
COPY server.js ./

# Create non-root user
RUN addgroup -g 1001 -S aicode && \
    adduser -u 1001 -S aicode -G aicode && \
    chown -R aicode:aicode /app

# Switch to non-root user
USER aicode

# Expose port
EXPOSE 3002

# Health check
HEALTHCHECK --interval=30s --timeout=3s --start-period=5s --retries=3 \
  CMD node -e "require('http').get('http://localhost:3002/health', (r) => {process.exit(r.statusCode === 200 ? 0 : 1);})"

# Start service
CMD ["node", "server.js"]

