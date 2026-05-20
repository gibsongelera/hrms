@echo off
REM GitHub Auto-Push Script for HRMS CI/CD (Windows)
REM Usage: github-auto-push.bat "commit message" "branch"

setlocal enabledelayedexpansion

set COMMIT_MESSAGE=%~1
if "!COMMIT_MESSAGE!"=="" set COMMIT_MESSAGE=Automated build %BUILD_NUMBER%

set BRANCH=%~2
if "!BRANCH!"=="" set BRANCH=main

if "!GITHUB_REPO!"=="" set GITHUB_REPO=mucx-tech/hrms-main

if "!GITHUB_TOKEN!"=="" (
    echo Error: GITHUB_TOKEN environment variable not set
    exit /b 1
)

echo.
echo === GitHub Auto-Push Script ===
echo Repository: !GITHUB_REPO!
echo Branch: !BRANCH!
echo Message: !COMMIT_MESSAGE!
echo.

git --version >nul 2>&1
if %ERRORLEVEL% neq 0 (
    echo Error: Git is not installed or not in PATH
    exit /b 1
)

git config user.name >nul 2>&1
if %ERRORLEVEL% neq 0 (
    git config user.name "Jenkins CI"
    git config user.email "jenkins@hrms.local"
)

git diff-index --quiet HEAD -- >nul 2>&1
if %ERRORLEVEL% equ 0 (
    echo No changes to commit
    exit /b 0
)

git add -A
git commit -m "!COMMIT_MESSAGE!"
if %ERRORLEVEL% neq 0 (
    echo Nothing to commit
    exit /b 0
)

echo Pushing to GitHub...
set HTTPS_URL=https://!GITHUB_TOKEN!@github.com/!GITHUB_REPO!.git

git push -u !HTTPS_URL! !BRANCH!
if %ERRORLEVEL% neq 0 (
    echo Error: Failed to push to GitHub
    exit /b 1
)

echo.
echo === GitHub Auto-Push Completed Successfully ===
echo.

endlocal
exit /b 0
