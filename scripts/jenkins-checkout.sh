#!/usr/bin/env bash
# Clone application source into Jenkins WORKSPACE (used by job1/job2/job3)
set -euo pipefail

cd "${WORKSPACE:?}"

REPO_SLUG="${GITHUB_REPO:-gibsongelera/hrms}"
BRANCH="${GIT_BRANCH:-main}"

if [[ -f Dockerfile && -f docker-compose.yml ]]; then
  echo "Workspace already has source"
  exit 0
fi

echo "Preparing workspace..."
find . -mindepth 1 -maxdepth 1 -exec rm -rf {} + 2>/dev/null || true

if [[ -n "${GITHUB_TOKEN:-}" ]]; then
  GIT_URL="https://x-access-token:${GITHUB_TOKEN}@github.com/${REPO_SLUG}.git"
else
  GIT_URL="https://github.com/${REPO_SLUG}.git"
fi

echo "Cloning ${REPO_SLUG} (branch ${BRANCH})..."
if ! git clone --depth 1 --branch "${BRANCH}" "${GIT_URL}" .; then
  echo "ERROR: git clone failed for ${REPO_SLUG}"
  echo "Check GITHUB_REPO in .env (use gibsongelera/hrms or mucx-tech/hrms-main)"
  exit 1
fi

if [[ ! -f Dockerfile ]]; then
  echo "ERROR: Dockerfile missing after clone"
  ls -la
  exit 1
fi

echo "Checkout OK"
