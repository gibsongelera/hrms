# Deploy job1, job2, job3 into the running Jenkins container
# Usage: powershell -ExecutionPolicy Bypass -File scripts/jenkins-deploy-jobs.ps1

$ErrorActionPreference = "Stop"
$Root = Split-Path -Parent (Split-Path -Parent $MyInvocation.MyCommand.Path)
Set-Location $Root

$Container = "hrms_jenkins"
$Jobs = @("job1", "job2", "job3")

if (-not (docker ps --format "{{.Names}}" | Select-String -Pattern "^${Container}$")) {
    Write-Host "Starting Jenkins..."
    docker compose -f docker-compose.jenkins.yml up -d
    Start-Sleep -Seconds 15
}

foreach ($job in $Jobs) {
    $src = Join-Path $Root "jenkins\jobs\$job\config.xml"
    if (-not (Test-Path $src)) {
        throw "Missing $src"
    }
    docker exec -u root $Container mkdir -p "/var/jenkins_home/jobs/$job" | Out-Null
    docker cp $src "${Container}:/var/jenkins_home/jobs/$job/config.xml"
    docker exec -u root $Container chown -R jenkins:jenkins "/var/jenkins_home/jobs/$job"
    Write-Host "Deployed $job"
}

Write-Host "Reloading Jenkins..."
docker restart $Container | Out-Null
$ready = $false
for ($i = 1; $i -le 40; $i++) {
    try {
        $r = Invoke-WebRequest -Uri "http://localhost:9090/login" -UseBasicParsing -TimeoutSec 3
        if ($r.StatusCode -eq 200) { $ready = $true; break }
    } catch {}
    Start-Sleep -Seconds 3
}
if (-not $ready) { Write-Warning "Jenkins may still be starting" }

Write-Host ""
Write-Host "Jenkins jobs ready at http://localhost:9090/"
Write-Host "  job1 - build"
Write-Host "  job2 - test"
Write-Host "  job3 - deployment"
