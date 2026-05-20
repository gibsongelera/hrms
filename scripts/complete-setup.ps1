# HRMS Complete CI/CD Setup Script (Windows PowerShell)
# Run this with: powershell -ExecutionPolicy Bypass -File complete-setup.ps1

$JENKINS_PORT = 9090
$APP_PORT = 8080
$PHPMYADMIN_PORT = 8081

# Color codes
function Write-Success {
    Write-Host $args -ForegroundColor Green
}

function Write-Warning {
    Write-Host $args -ForegroundColor Yellow
}

function Write-Error {
    Write-Host $args -ForegroundColor Red
}

function Write-Info {
    Write-Host $args -ForegroundColor Blue
}

# Header
Write-Info ""
Write-Info "╔════════════════════════════════════════════════════════════╗"
Write-Info "║   HRMS Complete CI/CD Setup Script (Windows)               ║"
Write-Info "║   Automated Jenkins + Docker + GitHub Integration         ║"
Write-Info "╚════════════════════════════════════════════════════════════╝"

# Step 1: Check Prerequisites
Write-Warning ""
Write-Warning "Step 1: Checking prerequisites..."
Write-Warning "=================================="

$tools = @(
    @{ Name = "Docker"; Command = "docker" },
    @{ Name = "Docker Compose"; Command = "docker-compose" },
    @{ Name = "Git"; Command = "git" },
    @{ Name = "cURL"; Command = "curl" }
)

$missing = 0
foreach ($tool in $tools) {
    $exists = Get-Command $tool.Command -ErrorAction SilentlyContinue
    if ($exists) {
        Write-Success "✓ $($tool.Name) is installed"
    } else {
        Write-Error "✗ $($tool.Name) is not installed"
        $missing++
    }
}

if ($missing -gt 0) {
    Write-Error ""
    Write-Error "✗ Please install missing tools and try again"
    exit 1
}

Write-Success "✓ All prerequisites are met"

# Step 2: Setup Environment File
Write-Warning ""
Write-Warning "Step 2: Setting up environment configuration..."
Write-Warning "=================================="

if (-not (Test-Path ".env")) {
    Copy-Item ".env.example" ".env"
    Write-Success "✓ Created .env file from template"
    Write-Warning "  Edit .env with your GitHub token and other settings"
} else {
    Write-Warning "⚠ .env file already exists"
}

# Step 3: Create Directories
Write-Warning ""
Write-Warning "Step 3: Creating required directories..."
Write-Warning "=================================="

$directories = @("scripts", "jenkins", "backups", "logs")
foreach ($dir in $directories) {
    if (-not (Test-Path $dir)) {
        New-Item -ItemType Directory -Path $dir | Out-Null
    }
}

Write-Success "✓ Directories created"

# Step 4: Check Port Availability
Write-Warning ""
Write-Warning "Step 4: Checking port availability..."
Write-Warning "=================================="

$ports = @($JENKINS_PORT, $APP_PORT, $PHPMYADMIN_PORT, 3307, 50000)
$ports_ok = $true

foreach ($port in $ports) {
    $connection = Get-NetTCPConnection -LocalPort $port -ErrorAction SilentlyContinue
    if ($connection) {
        Write-Warning "⚠ Port $port is already in use"
        $ports_ok = $false
    } else {
        Write-Success "✓ Port $port is available"
    }
}

if (-not $ports_ok) {
    Write-Warning "Some ports are in use. You may need to change port mappings."
}

# Step 5: Start Docker Services
Write-Warning ""
Write-Warning "Step 5: Starting Docker services..."
Write-Warning "=================================="

Write-Info "Starting Jenkins..."
docker-compose -f docker-compose.jenkins.yml up -d

Write-Info "Starting Application..."
docker-compose -f docker-compose.yml up -d

Write-Success "✓ Docker services started"

# Step 6: Wait for Services
Write-Warning ""
Write-Warning "Step 6: Waiting for services to be ready..."
Write-Warning "=================================="

Write-Info "Waiting for Jenkins (http://localhost:$JENKINS_PORT)..."
$jenkins_ready = $false
for ($i = 1; $i -le 60; $i++) {
    try {
        $response = Invoke-WebRequest -Uri "http://localhost:$JENKINS_PORT/login" -UseBasicParsing -ErrorAction SilentlyContinue
        if ($response.StatusCode -eq 200) {
            Write-Success "✓ Jenkins is ready"
            $jenkins_ready = $true
            break
        }
    } catch {
        # Continue waiting
    }
    
    if ($i -eq 60) {
        Write-Error "✗ Jenkins failed to start"
        exit 1
    }
    
    Write-Host -NoNewline "."
    Start-Sleep -Seconds 2
}

Write-Info ""
Write-Info "Waiting for Application (http://localhost:$APP_PORT)..."
for ($i = 1; $i -le 30; $i++) {
    try {
        $response = Invoke-WebRequest -Uri "http://localhost:$APP_PORT" -UseBasicParsing -ErrorAction SilentlyContinue
        if ($response.StatusCode -le 404) {
            Write-Success "✓ Application is ready"
            break
        }
    } catch {
        # Continue waiting
    }
    
    if ($i -eq 30) {
        Write-Warning "⚠ Application may take longer to initialize"
        break
    }
    
    Write-Host -NoNewline "."
    Start-Sleep -Seconds 2
}

Write-Info ""

# Display Summary
Write-Success ""
Write-Success "╔════════════════════════════════════════════════════════════╗"
Write-Success "║           Setup Completed Successfully!                    ║"
Write-Success "╚════════════════════════════════════════════════════════════╝"

Write-Info ""
Write-Info "Access Information:"
Write-Info "  Jenkins:         http://localhost:$JENKINS_PORT"
Write-Info "  Application:     http://localhost:$APP_PORT"
Write-Info "  PHPMyAdmin:      http://localhost:$PHPMYADMIN_PORT"
Write-Info "  Database:        localhost:3307"

Write-Info ""
Write-Info "Credentials:"
Write-Info "  Jenkins User:    (see JENKINS_USER in .env, default: gib)"
Write-Info "  Jenkins Token:   (set JENKINS_API_TOKEN in .env)"
Write-Info "  DB User:         hrms_user"
Write-Info "  DB Password:     hrms_pass"

Write-Info ""
Write-Info "Next Steps:"
Write-Info "  1. Edit .env file with your GitHub token"
Write-Info "  2. Access Jenkins and complete initial setup"
Write-Info "  3. Configure GitHub webhooks"
Write-Info "  4. Open Jenkins in browser"

Write-Info ""
Write-Info "Useful Commands:"
Write-Info "  View logs:       docker-compose logs -f jenkins"
Write-Info "  Stop services:   docker-compose down"
Write-Info "  Check status:    docker-compose ps"

Write-Info ""
Write-Info "Documentation:"
Write-Info "  Full Guide:      JENKINS_SETUP_GUIDE.md"
Write-Info "  Quick Ref:       QUICK_REFERENCE.md"

Write-Info ""
Write-Success "Setup complete!"
Write-Info ""

# Offer to open Jenkins in browser
$open_jenkins = Read-Host "Open Jenkins in browser? (y/n)"
if ($open_jenkins.ToLower() -eq "y") {
    Start-Process "http://localhost:$JENKINS_PORT"
}
