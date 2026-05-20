@echo off
setlocal
cd /d "%~dp0.."

echo === HRMS Jenkins Setup ===
docker-compose -f docker-compose.jenkins.yml up -d

echo Waiting for Jenkins...
set /A N=0
:WAIT
set /A N+=1
if %N% geq 60 goto TIMEOUT
curl.exe -fsS http://localhost:9090/login >nul 2>&1
if %ERRORLEVEL% equ 0 goto READY
timeout /t 5 /nobreak >nul
goto WAIT

:TIMEOUT
echo Jenkins did not start in time
exit /b 1

:READY
powershell -ExecutionPolicy Bypass -File "%~dp0jenkins-deploy-jobs.ps1"
echo.
echo Jenkins: http://localhost:9090
echo Jobs: job1 build, job2 test, job3 deployment
echo Run: scripts\ci-cd-auto.bat
exit /b 0
