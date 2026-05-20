# Full CI/CD: push to GitHub, then trigger Jenkins job1 -> job2 -> job3
# Usage:
#   powershell -ExecutionPolicy Bypass -File scripts/ci-cd-auto.ps1
#   powershell -ExecutionPolicy Bypass -File scripts/ci-cd-auto.ps1 -UpdateJobs

param(
    [string]$Message = "CI/CD automated push $(Get-Date -Format 'yyyy-MM-dd HH:mm')",
    [string]$Branch = "main",
    [switch]$SkipGitHub,
    [switch]$SkipJenkins,
    [switch]$DeployOnly,
    [switch]$UpdateJobs
)

$ErrorActionPreference = "Stop"
$Root = Split-Path -Parent (Split-Path -Parent $MyInvocation.MyCommand.Path)
Set-Location $Root

function Load-DotEnv {
    param([string]$Path)
    if (-not (Test-Path $Path)) { return }
    Get-Content $Path | ForEach-Object {
        if ($_ -match '^\s*#' -or $_ -notmatch '=') { return }
        $k, $v = $_ -split '=', 2
        $k = $k.Trim()
        $v = $v.Trim().Trim('"').Trim("'")
        if ($k) { Set-Item -Path "env:$k" -Value $v }
    }
}

Load-DotEnv (Join-Path $Root ".env")

$JenkinsUrl   = if ($env:JENKINS_URL) { $env:JENKINS_URL } else { "http://localhost:9090" }
$JenkinsUser  = if ($env:JENKINS_USER) { $env:JENKINS_USER } else { "gib" }
$JenkinsToken = $env:JENKINS_API_TOKEN
$GithubRepo   = if ($env:GITHUB_REPO) { $env:GITHUB_REPO } else { "mucx-tech/hrms-main" }
$GithubToken  = $env:GITHUB_TOKEN

function Get-JenkinsHeaders {
    $pair = "${JenkinsUser}:${JenkinsToken}"
    $b64 = [Convert]::ToBase64String([Text.Encoding]::ASCII.GetBytes($pair))
    return @{ Authorization = "Basic $b64" }
}

function Wait-JenkinsReady {
    param([int]$TimeoutSec = 300)
    $deadline = (Get-Date).AddSeconds($TimeoutSec)
    while ((Get-Date) -lt $deadline) {
        try {
            $null = Invoke-WebRequest -Uri "$JenkinsUrl/api/json" -UseBasicParsing -TimeoutSec 10
            return $true
        } catch {
            Start-Sleep -Seconds 3
        }
    }
    return $false
}

function Invoke-JenkinsBuild {
    param([string]$JobName)

    Write-Host "Triggering Jenkins: $JobName ..."
    $headers = Get-JenkinsHeaders
    $session = New-Object Microsoft.PowerShell.Commands.WebRequestSession

    $crumbResp = Invoke-RestMethod `
        -Uri "$JenkinsUrl/crumbIssuer/api/json" `
        -Headers $headers `
        -WebSession $session `
        -Method Get `
        -TimeoutSec 30
    $headers[$crumbResp.crumbRequestField] = $crumbResp.crumb

    $uri = "$JenkinsUrl/job/$JobName/build"
    $resp = Invoke-WebRequest `
        -Uri $uri `
        -Method Post `
        -Headers $headers `
        -WebSession $session `
        -UseBasicParsing `
        -TimeoutSec 60

    if ($resp.StatusCode -ge 200 -and $resp.StatusCode -lt 400) {
        Write-Host "  Queued $JobName"
        return $true
    }
    return $false
}

function Wait-JenkinsJob {
    param(
        [string]$JobName,
        [int]$TimeoutSec = 1800
    )

    $headers = Get-JenkinsHeaders
    $deadline = (Get-Date).AddSeconds($TimeoutSec)
    $uri = "$JenkinsUrl/job/$JobName/lastBuild/api/json"

    while ((Get-Date) -lt $deadline) {
        try {
            $info = Invoke-RestMethod -Uri $uri -Headers $headers -TimeoutSec 60
            if (-not $info.building) {
                if ($info.result -eq "SUCCESS") {
                    Write-Host "  $JobName SUCCESS (#$($info.number))"
                    return $true
                }
                if ($null -eq $info.result) {
                    Write-Host "  $JobName still starting..."
                } else {
                    Write-Host "  $JobName finished: $($info.result) (#$($info.number))"
                    return $false
                }
            } else {
                Write-Host "  $JobName building (#$($info.number))..."
            }
        } catch {
            Write-Host "  Jenkins busy or restarting, retrying..."
            if (-not (Wait-JenkinsReady -TimeoutSec 120)) {
                throw "Jenkins not reachable while waiting for $JobName"
            }
        }
        Start-Sleep -Seconds 10
    }
    throw "Timeout waiting for $JobName"
}

Write-Host ""
Write-Host "=== HRMS CI/CD Auto (GitHub + Jenkins) ==="
Write-Host ""

if ($UpdateJobs -and -not $SkipJenkins) {
    & (Join-Path $Root "scripts\jenkins-deploy-jobs.ps1") -Restart
}

if (-not $SkipJenkins) {
    if (-not (Wait-JenkinsReady)) {
        throw "Jenkins is not running at $JenkinsUrl"
    }
}

if (-not $SkipGitHub -and -not $DeployOnly) {
    if (-not $GithubToken) {
        Write-Warning "GITHUB_TOKEN not set in .env - skipping GitHub push"
    } else {
        Write-Host "Pushing to GitHub ($GithubRepo @ $Branch)..."
        $env:GITHUB_REPO = $GithubRepo
        $env:GITHUB_TOKEN = $GithubToken
        & (Join-Path $Root "scripts\github-auto-push.bat") $Message $Branch
        if ($LASTEXITCODE -ne 0) { throw "GitHub push failed" }
    }
}

if (-not $SkipJenkins) {
    if (-not $JenkinsToken) {
        Write-Warning "JENKINS_API_TOKEN not set. Add your token to .env (Jenkins user: $JenkinsUser)"
        exit 1
    }

    # job1 triggers job2, job2 triggers job3 (see jenkins/jobs/*/config.xml)
    if (-not (Invoke-JenkinsBuild -JobName "job1")) {
        throw "Failed to queue job1"
    }

    foreach ($job in @("job1", "job2", "job3")) {
        if (-not (Wait-JenkinsJob -JobName $job)) {
            throw "$job failed - see $JenkinsUrl/job/$job"
        }
    }
}

Write-Host ""
Write-Host "=== CI/CD complete ==="
Write-Host "Jenkins: $JenkinsUrl"
Write-Host "App:     http://localhost:8080"
