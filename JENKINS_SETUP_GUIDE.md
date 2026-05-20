# HRMS Jenkins CI/CD Pipeline Setup Guide

## Overview

This guide provides comprehensive instructions for setting up a complete CI/CD pipeline for the HRMS application using Jenkins, Docker, and GitHub integration.

### Architecture

```
GitHub (Source Code)
    ↓ (Webhook Trigger)
Jenkins Master (Port 9090)
    ├── Job 1: Build (Compile, Lint, Docker Image)
    ├── Job 2: Test (Database, Integration Tests)
    └── Job 3: Deploy (Staging/Production)
         ↓
Docker Containers (Application & Database)
```

## Prerequisites

- **Docker**: Version 20.10 or higher
- **Docker Compose**: Version 1.29 or higher
- **Git**: For version control
- **cURL**: For API calls
- **GitHub Account**: For repository and webhooks
- **Jenkins API Token**: `11c5d0e78cf477527c1cf9361c23c2c42c`

## Quick Start

### 1. Start Jenkins

**Linux/Mac:**
```bash
docker-compose -f docker-compose.jenkins.yml up -d
```

**Windows:**
```batch
docker-compose -f docker-compose.jenkins.yml up -d
```

Jenkins will be available at `http://localhost:9090`

### 2. Run Setup Script

**Linux/Mac:**
```bash
chmod +x scripts/jenkins-setup.sh
./scripts/jenkins-setup.sh
```

**Windows (PowerShell):**
```powershell
.\scripts\jenkins-setup.bat
```

The script will:
- Install required Jenkins plugins
- Create the three CI/CD jobs (build, test, deploy)
- Configure GitHub integration

## Detailed Job Configuration

### Job 1: hrms-build (Build Job)

**Purpose**: Compiles code, runs linting, and builds Docker image

**Triggers**:
- GitHub webhook push
- Manual trigger
- Scheduled polling

**Stages**:
1. **Checkout** - Clone repository from GitHub
2. **Lint PHP** - Validate PHP syntax
3. **Build Image** - Build Docker image with build number tag
4. **Upload to GitHub** - Create release notes

**Parameters**:
- `GIT_BRANCH`: Git branch to build (default: `main`)
- `PUSH_TO_REGISTRY`: Push to Docker registry (optional)

**Output**:
- Docker image tagged as `hrms-app:${BUILD_NUMBER}`
- Latest image also tagged as `hrms-app:latest`

### Job 2: hrms-test (Test Job)

**Purpose**: Runs automated tests on the built application

**Stages**:
1. **Start Services** - Start database and application containers
2. **Database Tests** - Validate database connectivity
3. **PHP Validation** - Verify PHP files
4. **Endpoint Tests** - Test application endpoints
5. **Health Checks** - Verify service health

**Parameters**:
- `BUILD_NUMBER_DEPENDENCY`: Reference to build job number

**Output**:
- Test results
- Health check reports

### Job 3: hrms-deploy (Deployment Job)

**Purpose**: Deploys application to staging or production

**Stages**:
1. **Pre-deployment Backup** - Backup current database
2. **Stop Services** - Gracefully stop current containers
3. **Pull Images** - Update base images
4. **Start Services** - Start new containers with new image
5. **Health Checks** - Verify all services are healthy
6. **Database Migrations** - Run migrations if enabled
7. **Verification** - Confirm deployment success

**Parameters**:
- `ENVIRONMENT`: Target environment (`staging` or `production`)
- `IMAGE_TAG`: Docker image tag to deploy
- `RUN_MIGRATIONS`: Execute database migrations

**Output**:
- Database backup file
- Deployment logs
- Email notification

## GitHub Integration

### 1. Create GitHub Token

1. Go to GitHub Settings → Developer settings → Personal access tokens
2. Click "Generate new token"
3. Select scopes:
   - `repo` (full control of private repositories)
   - `admin:repo_hook` (write access to hooks)
4. Generate and copy the token

### 2. Configure Jenkins Credentials

1. Open Jenkins at `http://localhost:9090`
2. Navigate to **Credentials** → **System** → **Global credentials**
3. Click **Add Credentials**
4. Type: **Username with password**
   - Username: Your GitHub username
   - Password: Your GitHub token
   - ID: `github-credentials`

### 3. Configure GitHub Webhook

1. Go to your GitHub repository → **Settings** → **Webhooks**
2. Click **Add webhook**
3. Payload URL: `http://your-jenkins-domain:9090/github-webhook/`
4. Content type: `application/json`
5. Select **Just the push event**
6. Click **Add webhook**

## Automatic GitHub Push

Scripts are provided to automatically commit and push code to GitHub after builds:

### Linux/Mac:

```bash
export GITHUB_TOKEN="your-github-token"
export GITHUB_REPO="your-username/hrms-main"

./scripts/github-auto-push.sh "Deployment successful" "main"
```

### Windows:

```batch
set GITHUB_TOKEN=your-github-token
set GITHUB_REPO=your-username/hrms-main

.\scripts\github-auto-push.bat "Deployment successful" "main"
```

## Manual Job Execution

### Trigger Build Job

**Using cURL:**
```bash
curl -X POST http://localhost:9090/job/hrms-build/build \
  -u admin:11c5d0e78cf477527c1cf9361c23c2c42c
```

**Using Jenkins UI:**
1. Navigate to `http://localhost:9090/job/hrms-build`
2. Click **Build Now**

### Trigger Test Job

```bash
curl -X POST http://localhost:9090/job/hrms-test/build \
  -u admin:11c5d0e78cf477527c1cf9361c23c2c42c
```

### Trigger Deployment

```bash
curl -X POST http://localhost:9090/job/hrms-deploy/buildWithParameters \
  -u admin:11c5d0e78cf477527c1cf9361c23c2c42c \
  -d "ENVIRONMENT=staging&IMAGE_TAG=latest&RUN_MIGRATIONS=true"
```

## Environment Variables

Key environment variables for Jenkins:

```bash
# Jenkins Configuration
JENKINS_OPTS="-Xmx2048m -Djenkins.install.runSetupWizard=false"

# GitHub Configuration
GITHUB_TOKEN=11c5d0e78cf477527c1cf9361c23c2c42c
GITHUB_REPO=your-username/hrms-main

# Docker Configuration
DOCKER_HOST=unix:///var/run/docker.sock
IMAGE_NAME=hrms-app
REGISTRY=docker.io/your-username

# Application Configuration
DB_HOST=hrms_db
DB_NAME=hrms_db
DB_USER=hrms_user
DB_PASS=hrms_pass
```

## Accessing Services

After deployment, the following services are available:

| Service | URL | Port | Credentials |
|---------|-----|------|-------------|
| Jenkins | http://localhost:9090 | 9090 | admin / token |
| Application | http://localhost:8080 | 8080 | See app |
| PHPMyAdmin | http://localhost:8081 | 8081 | hrms_user / hrms_pass |
| Database | localhost:3307 | 3307 | hrms_user / hrms_pass |

## Docker Management

### View Running Containers

```bash
docker-compose -f docker-compose.jenkins.yml ps
docker-compose -f docker-compose.yml ps
```

### View Logs

```bash
# Jenkins logs
docker-compose -f docker-compose.jenkins.yml logs -f jenkins

# Application logs
docker-compose -f docker-compose.yml logs -f web

# Database logs
docker-compose -f docker-compose.yml logs -f db
```

### Stop Services

```bash
# Stop Jenkins
docker-compose -f docker-compose.jenkins.yml down

# Stop Application
docker-compose -f docker-compose.yml down
```

## Database Backup

Automatic backups are created before deployments in `./backups/` directory:

```bash
# List backups
ls -la backups/

# Restore from backup
docker exec hrms_db mysql -u hrms_user -p"hrms_pass" hrms_db < backups/backup_file.sql
```

## Troubleshooting

### Jenkins Won't Start

```bash
# Check Docker logs
docker-compose -f docker-compose.jenkins.yml logs jenkins

# Verify ports are free
netstat -an | grep 9090

# Restart Jenkins
docker-compose -f docker-compose.jenkins.yml restart jenkins
```

### Build Fails

1. Check build logs in Jenkins UI
2. Verify GitHub connection: `curl -u admin:TOKEN http://localhost:9090/api/json`
3. Verify Docker is running: `docker ps`

### Database Connection Issues

```bash
# Check database container
docker exec hrms_db mysqladmin ping -u hrms_user -p"hrms_pass"

# Verify network connectivity
docker network ls
```

### Webhook Not Triggering

1. Verify webhook in GitHub settings
2. Check GitHub webhook delivery logs
3. Verify firewall allows traffic to port 9090
4. Test manually: `curl -X POST http://localhost:9090/github-webhook/`

## Pipeline Workflow Example

```
1. Developer pushes code to GitHub
   ↓
2. GitHub webhook triggers Jenkins build
   ↓
3. Job 1: hrms-build
   - Clone repository
   - Lint PHP code
   - Build Docker image
   - Tag with build number
   ↓
4. Job 2: hrms-test (Automatic)
   - Start test environment
   - Run database tests
   - Run integration tests
   - Verify endpoints
   ↓
5. Job 3: hrms-deploy (Manual approval)
   - Create database backup
   - Stop current containers
   - Start new containers
   - Run migrations
   - Health checks
   ↓
6. Application online at http://localhost:8080
   ↓
7. Automatic GitHub update with release notes
```

## Performance Optimization

### Resource Allocation

Adjust Docker resources in `docker-compose.jenkins.yml`:

```yaml
deploy:
  resources:
    limits:
      cpus: '2'      # Max CPU cores
      memory: 2G     # Max memory
```

### Build Timeout

Modify in Jenkinsfile:

```groovy
options {
    timeout(time: 30, unit: 'MINUTES')
}
```

## Security Best Practices

1. **Change Default Password**: After first login, change Jenkins admin password
2. **Use HTTPS**: Configure reverse proxy for HTTPS
3. **Limit API Token Access**: Restrict token usage to specific IPs
4. **Secure Credentials**: Use Jenkins Credentials plugin for secrets
5. **Regular Backups**: Schedule automatic Jenkins home backups
6. **Update Plugins**: Regularly update Jenkins and plugins

## Maintenance

### Daily Tasks

- Monitor build logs for errors
- Check webhook delivery in GitHub

### Weekly Tasks

- Review and archive old builds
- Check disk space usage

### Monthly Tasks

- Update Jenkins plugins
- Update Docker base images
- Review security settings
- Test disaster recovery procedures

## Support

For issues or questions:

1. Check Jenkins logs: `docker logs hrms_jenkins`
2. Review job build logs in Jenkins UI
3. Consult Docker documentation
4. Check GitHub Actions for alternative CI/CD

## Additional Resources

- [Jenkins Documentation](https://www.jenkins.io/doc/)
- [Docker Documentation](https://docs.docker.com/)
- [GitHub Actions](https://github.com/features/actions)
- [CI/CD Best Practices](https://www.atlassian.com/continuous-delivery/ci-cd)

---

**Version**: 1.0  
**Last Updated**: 2024-12-20  
**Maintained By**: HRMS Team
