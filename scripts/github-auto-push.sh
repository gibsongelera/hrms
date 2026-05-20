#!/bin/bash

#############################################################################
# GitHub Auto-Push Script for HRMS CI/CD
# This script automatically commits and pushes code to GitHub after builds
# Usage: ./github-auto-push.sh "[commit message]" "[branch]"
#############################################################################

set -e

COMMIT_MESSAGE="${1:-Automated build ${BUILD_NUMBER}}"
BRANCH="${2:-main}"
GITHUB_REPO="${GITHUB_REPO:-mucx-tech/hrms-main}"
if [[ -z "${GITHUB_TOKEN:-}" ]]; then
  echo "Error: set GITHUB_TOKEN in .env"
  exit 1
fi

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo -e "${YELLOW}=== GitHub Auto-Push Script ===${NC}"
echo "Repository: $GITHUB_REPO"
echo "Branch: $BRANCH"
echo "Message: $COMMIT_MESSAGE"

# Check if git is installed
if ! command -v git &> /dev/null; then
    echo -e "${RED}Error: Git is not installed${NC}"
    exit 1
fi

# Configure git user if not already configured
if ! git config user.name &> /dev/null; then
    git config user.name "Jenkins CI"
    git config user.email "jenkins@hrms.local"
fi

# Check if there are changes to commit
if git diff-index --quiet HEAD --; then
    echo -e "${YELLOW}No changes to commit${NC}"
    exit 0
fi

# Add all changes
git add -A

# Create commit
git commit -m "$COMMIT_MESSAGE" || echo "Nothing to commit"

# Push to GitHub
echo -e "${YELLOW}Pushing to GitHub...${NC}"

HTTPS_URL="https://${GITHUB_TOKEN}@github.com/${GITHUB_REPO}.git"

if git push -u "$HTTPS_URL" "$BRANCH"; then
    echo -e "${GREEN}✓ Successfully pushed to GitHub${NC}"
else
    echo -e "${RED}✗ Failed to push to GitHub${NC}"
    exit 1
fi

echo -e "${GREEN}=== GitHub Auto-Push Completed ===${NC}"
