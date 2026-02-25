# Makefile for AICode plugin development
# 
# Quick commands for common tasks

.PHONY: help build start stop logs test clean install

help: ## Show this help message
	@echo 'Usage: make [target]'
	@echo ''
	@echo 'Targets:'
	@awk 'BEGIN {FS = ":.*?## "} /^[a-zA-Z_-]+:.*?## / {printf "  %-15s %s\n", $$1, $$2}' $(MAKEFILE_LIST)

install: ## Install dependencies for all services
	@echo "Installing executor dependencies..."
	cd services/executor && npm install
	@echo "Installing analyzer dependencies..."
	cd services/analyzer && npm install
	@echo "Done!"

build: ## Build Docker images
	@echo "Building Docker images..."
	docker-compose build
	@echo "Building executor worker image..."
	cd services/executor && docker build -t aicode-worker:latest .
	@echo "Done!"

start: ## Start all services
	@echo "Starting AICode services..."
	docker-compose up -d
	@echo "Services started!"
	@echo "Executor: http://127.0.0.1:3001"
	@echo "Analyzer: http://127.0.0.1:3002"

stop: ## Stop all services
	@echo "Stopping AICode services..."
	docker-compose down
	@echo "Done!"

logs: ## View logs from all services
	docker-compose logs -f

test: ## Run tests for all services
	@echo "Testing executor..."
	cd services/executor && npm test
	@echo "Testing analyzer..."
	cd services/analyzer && npm test
	@echo "All tests passed!"

clean: ## Clean up containers, images, and temp files
	@echo "Cleaning up..."
	docker-compose down -v
	rm -rf /tmp/aicode-jobs/*
	@echo "Done!"

dev: ## Start services in development mode
	@echo "Starting in development mode..."
	docker-compose -f docker-compose.yml -f docker-compose.override.yml up

