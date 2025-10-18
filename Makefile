SHELL := /bin/bash

COMPOSE := docker compose
PHP_SERVICE := php
WATCH_CMD := npm run dev -- --host
WATCH_GREP := vite dev
WATCH_LOG := var/log/vite.log

.PHONY: up down watch watch-stop ensure-containers ensure-log-dir

up: ensure-containers watch
	@echo "Docker services are running and the Vite dev server is active."

down: watch-stop
	@echo "Stopping Docker services..."
	$(COMPOSE) down

watch: ensure-log-dir
	@if pgrep -f "$(WATCH_GREP)" >/dev/null; then \
		echo "Vite dev server already running."; \
	else \
		echo "Starting Vite dev server..."; \
		nohup $(WATCH_CMD) >> $(WATCH_LOG) 2>&1 & \
		echo "Vite dev server logs: $(WATCH_LOG)"; \
	fi

watch-stop:
	@if pkill -f "$(WATCH_GREP)" >/dev/null 2>&1; then \
		echo "Stopped Vite dev server."; \
	else \
		echo "Vite dev server not running; nothing to stop."; \
	fi

ensure-containers:
	@echo "Starting Docker services..."
	$(COMPOSE) up -d

ensure-log-dir:
	mkdir -p var/log
