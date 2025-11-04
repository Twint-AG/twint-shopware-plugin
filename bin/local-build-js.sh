#!/usr/bin/env bash

set -euo pipefail  # Exit on error, undefined variables, and pipe failures

# Color codes for better output
readonly RED='\033[0;31m'
readonly GREEN='\033[0;32m'
readonly YELLOW='\033[1;33m'
readonly NC='\033[0m' # No Color

# Get the base directory
readonly BASE_DIR=$(cd "$(dirname "$0")" && pwd)
readonly INFRA_DIR="$BASE_DIR/../infra/ci"
readonly PROJECT_DIR="$BASE_DIR/.."

# Logging functions
log_info() {
    echo -e "${GREEN}[INFO]${NC} $1"
}

log_warn() {
    echo -e "${YELLOW}[WARN]${NC} $1"
}

log_error() {
    echo -e "${RED}[ERROR]${NC} $1" >&2
}

# Cleanup function
cleanup() {
    local exit_code=$?
    if [ $exit_code -ne 0 ]; then
        log_error "Script failed with exit code $exit_code"
    fi
    exit $exit_code
}

trap cleanup EXIT

# Check if docker is available
if ! command -v docker &> /dev/null; then
    log_error "Docker is not installed or not in PATH"
    exit 1
fi

# Start Docker containers
start_containers() {
    log_info "Starting Docker containers..."
    cd "$INFRA_DIR"
    
    if ! docker compose down; then
        log_error "Failed to stop existing containers"
        return 1
    fi
    
    if ! docker compose up -d; then
        log_error "Failed to start containers"
        return 1
    fi
    
    cd "$PROJECT_DIR"
    log_info "Docker containers started successfully"
}

# Build for a specific Shopware version
build_version() {
    local version=$1
    local container=$2
    local is_first_build=$3
    
    log_info "Building for Shopware ${version}"
    
    if ! docker exec "$container" /builds/twint-ag/twint-shopware-plugin/bin/build-js.sh > /dev/null 2>&1; then
        log_error "Build failed for Shopware ${version}"
        return 1
    fi
    
    # Show changes
    echo ""
    git diff --name-status src/Resources/
    echo ""
    
    # Add files based on whether this is the first build
    if [ "$is_first_build" = true ]; then
        log_info "Adding all changes (including deletions) as baseline"
        git add src/Resources
    else
        log_info "Adding changes (ignoring deletions from other versions)"
        git add src/Resources --ignore-removal
    fi
    
    log_info "Build completed for Shopware ${version}"
}

# Main execution
main() {
    log_info "Starting multi-version Shopware build process"
    
    # Start containers
    if ! start_containers; then
        exit 1
    fi
    
    # Wait for containers to be fully ready
    log_info "Waiting for containers to be ready..."
    sleep 3

    log_info "Deleting old build files ..."
    rm -rf src/Resources/public/static/*
    rm -rf src/Resources/app/storefront/dist/*
    
    # Build for each version
    build_version "6.5" "ci65" true    
    build_version "6.6" "ci66" false
    build_version "6.7" "ci67" false
    
    log_info "All builds completed successfully!"
    
    # Show final summary
    echo ""
    log_info "Summary of all changes:"
    git status src/Resources/ --short
}

# Run main function
main