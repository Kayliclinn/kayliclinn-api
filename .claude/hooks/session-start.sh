#!/bin/bash
set -euo pipefail

# Ne s'exécute que dans Claude Code sur le web (environnement distant)
if [ "${CLAUDE_CODE_REMOTE:-}" != "true" ]; then
  exit 0
fi

cd "${CLAUDE_PROJECT_DIR:-.}"

# Installe les dépendances (idempotent, profite du cache du conteneur)
npm install
