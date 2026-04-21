#!/usr/bin/env bash
# Manual test runner for memory-limit-warning-fix — Task 6
# Covers sub-tasks 6.1, 6.2, 6.3
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
PROJECT_ROOT="$(cd "$SCRIPT_DIR/../../../.." && pwd)"

echo "Running manual tests from: $SCRIPT_DIR"
echo "Project root: $PROJECT_ROOT"
echo ""

php "$SCRIPT_DIR/test-task-6.php"
