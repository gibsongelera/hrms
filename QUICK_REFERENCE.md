# HRMS CI/CD Quick Reference

## Setup Commands

### 1. Start Everything (Jenkins + Application)

**Linux/Mac:**
```bash
# Start Jenkins
docker-compose -f docker-compose.jenkins.yml up -d

# Wait for Jenkins to be ready
sleep 30

# Run setup script
./scripts/jenkins-setup.sh

# Start application (in another terminal)
docker-compose -f docker-compose.yml up -d
```

**Windows (PowerShell):**
```powershell
# Start Jenkins
docker-compose -f docker-compose.jenkins.yml up -d

# Start application
docker-compose -f docker-compose.yml up -d

# Run setup script
.\scripts\jenkins-setup.bat
```

### 2. Quick Links

- Jenkins Dashboard: http://localhost:9090
- Application: http://localhost:8080
- PHPMyAdmin: http://localhost:8081
- Jenkins API: http://localhost:9090/api/json

### 3. API Commands

```bash
# Build Job
curl -X POST http://localhost:9090/job/hrms-build/build \
  -u admin:11c5d0e78cf477527c1cf9361c23c2c42c

# Test Job
curl -X POST http://localhost:9090/job/hrms-test/build \
  -u admin:11c5d0e78cf477527c1cf9361c23c2c42c

# Deploy to Staging
curl -X POST http://localhost:9090/job/hrms-deploy/buildWithParameters \
  -u admin:11c5d0e78cf477527c1cf9361c23c2c42c \
  -d "ENVIRONMENT=staging&IMAGE_TAG=latest&RUN_MIGRATIONS=true"

# Deploy to Production
curl -X POST http://localhost:9090/job/hrms-deploy/buildWithParameters \
  -u admin:11c5d0e78cf477527c1cf9361c23c2c42c \
  -d "ENVIRONMENT=production&IMAGE_TAG=latest&RUN_MIGRATIONS=true"

# Get build status
curl -u admin:11c5d0e78cf477527c1cf9361c23c2c42c \
  http://localhost:9090/job/hrms-build/lastBuild/api/json
```

### 4. Docker Commands

```bash
# View running containers
docker ps | grep hrms

# View logs
docker-compose -f docker-compose.jenkins.yml logs -f jenkins
docker-compose -f docker-compose.yml logs -f web

# Stop everything
docker-compose -f docker-compose.jenkins.yml down
docker-compose -f docker-compose.yml down

# Clean up (remove all containers and volumes)
docker-compose -f docker-compose.jenkins.yml down -v
docker-compose -f docker-compose.yml down -v
```

### 5. GitHub Configuration

```bash
# Set GitHub token in environment
export GITHUB_TOKEN="11c5d0e78cf477527c1cf9361c23c2c42c"
export GITHUB_REPO="your-username/hrms-main"

# Auto-push to GitHub
./scripts/github-auto-push.sh "Automated deployment" "main"
```

### 6. Database Operations

```bash
# Backup database
docker exec hrms_db mysqldump -u hrms_user -p"hrms_pass" hrms_db > backup.sql

# Restore database
docker exec hrms_db mysql -u hrms_user -p"hrms_pass" hrms_db < backup.sql

# Access MySQL shell
docker exec -it hrms_db mysql -u hrms_user -p"hrms_pass" hrms_db

# View database logs
docker-compose -f docker-compose.yml logs -f db
```

### 7. Jenkins Plugin Management

```bash
# CLI commands
java -jar jenkins-cli.jar -s http://localhost:9090 \
  -auth admin:11c5d0e78cf477527c1cf9361c23c2c42c \
  list-plugins

# View installed plugins
curl -s -u admin:11c5d0e78cf477527c1cf9361c23c2c42c \
  http://localhost:9090/pluginManager/api/json | jq '.plugins[] | {name: .name, version: .version}'
```

## Job Management

### Create New Job

```bash
curl -X POST http://localhost:9090/createItem?name=new-job \
  -u admin:11c5d0e78cf477527c1cf9361c23c2c42c \
  -d @job-config.xml \
  -H "Content-Type: application/xml"
```

### Delete Job

```bash
curl -X POST http://localhost:9090/job/job-name/doDelete \
  -u admin:11c5d0e78cf477527c1cf9361c23c2c42c
```

### Disable/Enable Job

```bash
# Disable
curl -X POST http://localhost:9090/job/hrms-build/disable \
  -u admin:11c5d0e78cf477527c1cf9361c23c2c42c

# Enable
curl -X POST http://localhost:9090/job/hrms-build/enable \
  -u admin:11c5d0e78cf477527c1cf9361c23c2c42c
```

## Environment Setup

### Required Environment Variables

```bash
# Jenkins
export JENKINS_HOME=/var/jenkins_home
export JENKINS_URL=http://localhost:9090
export JENKINS_API_TOKEN=11c5d0e78cf477527c1cf9361c23c2c42c

# GitHub
export GITHUB_TOKEN=your-token-here
export GITHUB_REPO=your-username/hrms-main
export GIT_USER=your-github-username
export GIT_EMAIL=your-email@example.com

# Docker
export DOCKER_REGISTRY=docker.io
export DOCKER_USERNAME=your-docker-username
export IMAGE_NAME=hrms-app
export IMAGE_TAG=latest

# Application
export DB_HOST=hrms_db
export DB_NAME=hrms_db
export DB_USER=hrms_user
export DB_PASS=hrms_pass
export MYSQL_ROOT_PASSWORD=rootpass
```

## Troubleshooting

### Jenkins Not Responding

```bash
# Check if container is running
docker ps | grep jenkins

# View logs
docker-compose -f docker-compose.jenkins.yml logs jenkins

# Restart
docker-compose -f docker-compose.jenkins.yml restart jenkins
```

### Build Fails

```bash
# View build logs
curl -s -u admin:11c5d0e78cf477527c1cf9361c23c2c42c \
  http://localhost:9090/job/hrms-build/lastBuild/consoleText

# View recent builds
curl -s -u admin:11c5d0e78cf477527c1cf9361c23c2c42c \
  http://localhost:9090/job/hrms-build/api/json | jq '.builds | .[0:5]'
```

### Database Connection Issues

```bash
# Check database status
docker exec hrms_db mysqladmin ping -u hrms_user -p"hrms_pass"

# Check network connectivity
docker network ls
docker network inspect hrms_hrms_net
```

### GitHub Webhook Not Working

1. Check webhook logs in GitHub repository settings
2. Test manually: `curl -X POST http://localhost:9090/github-webhook/`
3. Verify firewall allows port 9090

## Performance Monitoring

### Jenkins Metrics

```bash
# JVM memory
curl -s -u admin:11c5d0e78cf477527c1cf9361c23c2c42c \
  http://localhost:9090/monitoring/api/json

# Queue size
curl -s -u admin:11c5d0e78cf477527c1cf9361c23c2c42c \
  http://localhost:9090/api/json | jq '.queueLength'

# System info
curl -s -u admin:11c5d0e78cf477527c1cf9361c23c2c42c \
  http://localhost:9090/computer/api/json
```

### Docker Resources

```bash
# Container stats
docker stats hrms_jenkins hrms_web hrms_db

# Disk usage
docker system df

# Clean up unused resources
docker system prune -a
```

## Common Issues

| Issue | Solution |
|-------|----------|
| Port 9090 already in use | Change port in docker-compose.jenkins.yml |
| Docker daemon not running | Start Docker Desktop or Docker service |
| Permission denied errors | Run with sudo or add user to docker group |
| Build times too long | Increase resource limits in docker-compose.yml |
| GitHub webhook not triggering | Check firewall, verify URL, test manually |
| Database connection failed | Wait for db healthcheck, verify credentials |

## Useful Scripts

### Automated Deploy Script

```bash
#!/bin/bash
ENVIRONMENT=${1:-staging}
IMAGE_TAG=${2:-latest}
curl -X POST http://localhost:9090/job/hrms-deploy/buildWithParameters \
  -u admin:11c5d0e78cf477527c1cf9361c23c2c42c \
  -d "ENVIRONMENT=${ENVIRONMENT}&IMAGE_TAG=${IMAGE_TAG}&RUN_MIGRATIONS=true"
```

### Build & Test Script

```bash
#!/bin/bash
echo "Starting build..."
curl -X POST http://localhost:9090/job/hrms-build/build \
  -u admin:11c5d0e78cf477527c1cf9361c23c2c42c

echo "Waiting for build to complete..."
sleep 10

echo "Starting tests..."
curl -X POST http://localhost:9090/job/hrms-test/build \
  -u admin:11c5d0e78cf477527c1cf9361c23c2c42c
```

---

**Quick Reference v1.0** | Updated: 2024-12-20
