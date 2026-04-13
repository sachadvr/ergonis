FRONTEND_IMAGE = sachadvr/jobplanner-front
BACKEND_IMAGE  = sachadvr/jobplanner

ROOT_DIR := $(dir $(abspath $(lastword $(MAKEFILE_LIST))))

FRONTEND_DIR = $(ROOT_DIR)/jobplanner-frontend
BACKEND_DIR  = $(ROOT_DIR)/jobplanner-api

PLATFORM = linux/amd64

.DEFAULT_GOAL := help

.PHONY: help build build-frontend build-backend push push-frontend push-backend release-frontend release-backend csfix

help:
	@echo "Available commands:"
	@echo "  make build              Build frontend + backend"
	@echo "  make push               Push frontend + backend"
	@echo "  make build-frontend     Build frontend image"
	@echo "  make push-frontend      Push frontend image"
	@echo "  make build-backend      Build backend image"
	@echo "  make push-backend       Push backend image"
	@echo "  make release-frontend   Build + push frontend"
	@echo "  make release-backend    Build + push backend"
	@echo "  make csfix              Run PHP CS Fixer"

build: build-frontend build-backend

push: push-frontend push-backend

build-frontend:
	docker buildx build --platform $(PLATFORM) -t $(FRONTEND_IMAGE) $(FRONTEND_DIR)

push-frontend:
	docker push $(FRONTEND_IMAGE)

build-backend:
	docker buildx build --platform $(PLATFORM) -t $(BACKEND_IMAGE) $(BACKEND_DIR)

push-backend:
	docker push $(BACKEND_IMAGE)

release-frontend:
	docker buildx build --platform $(PLATFORM) -t $(FRONTEND_IMAGE) $(FRONTEND_DIR)
	docker push $(FRONTEND_IMAGE)

release-backend:
	docker buildx build --platform $(PLATFORM) -t $(BACKEND_IMAGE) $(BACKEND_DIR)
	docker push $(BACKEND_IMAGE)

csfix:
	docker run --rm -v $$(pwd):/app cytopia/php-cs-fixer fix --config=/app/jobplanner-api/.php-cs-fixer.php

release: release-frontend release-backend
