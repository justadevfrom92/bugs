#!/usr/bin/env bash
# Serve the site locally on Linux: ./serve.sh [port]
# Then open http://localhost:8000 (website) — the Admin button is top right.
cd "$(dirname "$0")" || exit 1
PORT="${1:-8000}"
echo "Serving on http://localhost:$PORT  (Ctrl+C to stop)"
exec python3 -m http.server "$PORT"
