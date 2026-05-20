# HRMS CI/CD Pipeline - Complete Setup Documentation

## Executive Summary

You now have a complete, production-ready CI/CD pipeline for the HRMS application featuring:

- **Jenkins** running on port 9090 with three automated jobs
- **Docker** containerization for application and database
- **GitHub** integration with webhooks
- **Automated** build, test, and deployment processes
- **Database** backups and migrations
- **Scripts** for automated GitHub pushes

## What Was Configured

### 1. Jenkins Pipeline (3 Jobs)

#### Job 1: hrms-build
- **Trigger**: GitHub webhook or manual
- **Tasks**:
  - Checkout code from GitHub
  - Lint PHP code
  - Build Docker image
  - Tag with build number
  - Create release notes
- **Output**: Docker image `hrms-app:${BUILD_NUMBER}`

#### Job 2: hrms-test
- **Trigger**: After build completion
- **Tasks**:
  - Start database container
  - Run connectivity tests
  - Validate database
  - Test PHP endpoints
  - Health checks
- **Output**: Test results and health status

#### Job 3: hrms-deploy
- **Trigger**: Manual approval
- **Tasks**:
  - Create database backup
  - Stop current containers
  - Start new containers
  - Run migrations
  - Verify deployment
  - Send notifications
- **Output**: Deployment logs, backup file

### 2. Docker Configuration

**docker-compose.jenkins.yml**
- Jenkins container (port 9090)
- Jenkins agent for distributed builds
- Named volume for persistence
- Health checks

**docker-compose.yml** (Enhanced)
- Web application
- MySQL database
- PHPMyAdmin
- Persistent volumes
- Health checks

### 3. Automation Scripts

| Script | Purpose | Platform |
|--------|---------|----------|
| `scripts/github-auto-push.sh` | Auto-commit and push to GitHub | Linux/Mac |
| `scripts/github-auto-push.bat` | Auto-commit and push to GitHub | Windows |
| `scripts/jenkins-setup.sh` | Initialize Jenkins with jobs | Linux/Mac |
| `scripts/jenkins-setup.bat` | Initialize Jenkins with jobs | Windows |
| `scripts/complete-setup.sh` | Full automated setup | Linux/Mac |
| `scripts/complete-setup.ps1` | Full automated setup | Windows |

### 4. Configuration Files

| File | Purpose |
|------|---------|
| `Jenkinsfile` | Pipeline definition (build, test, deploy stages) |
| `docker-compose.jenkins.yml` | Jenkins Docker composition |
| `.env.example` | Environment variables template |
| `jenkins/job-build-config.xml` | Build job configuration |
| `jenkins/job-test-config.xml` | Test job configuration |
| `jenkins/job-deploy-config.xml` | Deploy job configuration |

### 5. Documentation

| Document | Content |
|----------|---------|
| `JENKINS_SETUP_GUIDE.md` | Comprehensive setup and usage guide |
| `QUICK_REFERENCE.md` | Quick command reference |
| `JENKINS_CI_CD_SETUP.md` | This file |

## Getting Started

### Option A: Automated Setup (Recommended)

**Linux/Mac:**
```bash
chmod +x scripts/complete-setup.sh
./scripts/complete-setup.sh
```

**Windows (PowerShell):**
```powershell
Set-ExecutionPolicy -ExecutionPolicy Bypass -Scope Process
.\scripts\complete-setup.ps1
```

### Option B: Manual Setup

1. **Start Services:**
   ```bash
   docker-compose -f docker-compose.jenkins.yml up -d
   docker-compose -f docker-compose.yml up -d
   ```

2. **Initialize Jenkins:**
   ```bash
   # Linux/Mac
   ./scripts/jenkins-setup.sh
   
   # Windows
   .\scripts\jenkins-setup.bat
   ```

3. **Access Jenkins:**
   - URL: http://localhost:9090
   - Username: admin
   - Token: 11c5d0e78cf477527c1cf9361c23c2c42c

## Key Endpoints

| Service | URL | Credentials |
|---------|-----|-------------|
| Jenkins | http://localhost:9090 | admin / token |
| Application | http://localhost:8080 | See app login |
| PHPMyAdmin | http://localhost:8081 | hrms_user / hrms_pass |
| MySQL | localhost:3307 | hrms_user / hrms_pass |

## Important Information

### Jenkins API Token
```
11c5d0e78cf477527c1cf9361c23c2c42c
```

### Database Credentials
- **Host**: hrms_db (localhost:3307)
- **User**: hrms_user
- **Password**: hrms_pass
- **Database**: hrms_db

### GitHub Integration

You need to:
1. Update `.env` with your GitHub token
2. Create GitHub webhook in repository settings
3. Webhook URL: `http://your-jenkins-domain:9090/github-webhook/`

## Build Triggers

### Automatic Triggers
- GitHub push to configured branch
- Polling (every 15 minutes)
- Scheduled builds

### Manual Triggers
```bash
# Build
curl -X POST http://localhost:9090/job/hrms-build/build \
  -u admin:11c5d0e78cf477527c1cf9361c23c2c42c

# Test
curl -X POST http://localhost:9090/job/hrms-test/build \
  -u admin:11c5d0e78cf477527c1cf9361c23c2c42c

# Deploy
curl -X POST http://localhost:9090/job/hrms-deploy/buildWithParameters \
  -u admin:11c5d0e78cf477527c1cf9361c23c2c42c \
  -d "ENVIRONMENT=staging&IMAGE_TAG=latest&RUN_MIGRATIONS=true"
```

## Common Tasks

### View Build Logs
```bash
curl -s -u admin:11c5d0e78cf477527c1cf9361c23c2c42c \
  http://localhost:9090/job/hrms-build/lastBuild/consoleText
```

### Create Database Backup
```bash
docker exec hrms_db mysqldump -u hrms_user -p"hrms_pass" hrms_db > backup.sql
```

### Restore Database
```bash
docker exec hrms_db mysql -u hrms_user -p"hrms_pass" hrms_db < backup.sql
```

### View Running Containers
```bash
docker-compose -f docker-compose.jenkins.yml ps
docker-compose -f docker-compose.yml ps
```

### Stop All Services
```bash
docker-compose -f docker-compose.jenkins.yml down
docker-compose -f docker-compose.yml down
```

## Files Created/Modified

### New Files
- `Jenkinsfile` - Enhanced with full pipeline stages
- `docker-compose.jenkins.yml` - Jenkins configuration
- `scripts/github-auto-push.sh` - GitHub push automation (Linux/Mac)
- `scripts/github-auto-push.bat` - GitHub push automation (Windows)
- `scripts/jenkins-setup.sh` - Jenkins initialization (Linux/Mac)
- `scripts/jenkins-setup.bat` - Jenkins initialization (Windows)
- `scripts/complete-setup.sh` - Full setup (Linux/Mac)
- `scripts/complete-setup.ps1` - Full setup (Windows)
- `jenkins/job-build-config.xml` - Build job configuration
- `jenkins/job-test-config.xml` - Test job configuration
- `jenkins/job-deploy-config.xml` - Deploy job configuration
- `JENKINS_SETUP_GUIDE.md` - Comprehensive guide
- `QUICK_REFERENCE.md` - Quick reference
- `.env.example` - Environment template

## Architecture Diagram

```
┌─────────────────────────────────────────────────────────────┐
│                        GitHub Repository                     │
│                   (Source Code + Webhooks)                   │
└────────────────────────────┬────────────────────────────────┘
                             │
                             ▼ (Webhook Trigger)
┌─────────────────────────────────────────────────────────────┐
│                     Jenkins (Port 9090)                      │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐       │
│  │ Build Job    │──▶ Test Job    │──▶ Deploy Job   │       │
│  │ - Checkout   │  │ - Database  │  │ - Backup     │       │
│  │ - Lint PHP   │  │ - Tests     │  │ - Deploy     │       │
│  │ - Build IMG  │  │ - Health    │  │ - Migrate    │       │
│  └──────────────┘  └──────────────┘  └──────────────┘       │
└────────────────────────────┬────────────────────────────────┘
                             │
                             ▼
┌─────────────────────────────────────────────────────────────┐
│                   Docker Containers                          │
│  ┌──────────────┐              ┌──────────────┐             │
│  │ HRMS App     │──────────────▶│ MySQL DB     │             │
│  │ (Port 8080)  │              │ (Port 3307)  │             │
│  └──────────────┘              └──────────────┘             │
│  ┌──────────────┐                                           │
│  │ PHPMyAdmin   │                                           │
│  │ (Port 8081)  │                                           │
│  └──────────────┘                                           │
└─────────────────────────────────────────────────────────────┘
```

## Security Notes

1. **Change Default Credentials**: Update Jenkins admin password after first login
2. **Secure API Token**: Keep `11c5d0e78cf477527c1cf9361c23c2c42c` private
3. **Use HTTPS**: Configure reverse proxy for production
4. **Backup Credentials**: Store GitHub token securely
5. **Regular Updates**: Update Jenkins plugins and Docker images

## Performance Tips

1. **Increase Memory**: Edit `docker-compose.jenkins.yml` resource limits
2. **Enable Caching**: Docker layer caching for faster builds
3. **Parallel Jobs**: Configure Jenkins for concurrent builds
4. **Database Indexing**: Ensure proper database indexes

## Troubleshooting

### Jenkins Won't Start
```bash
docker-compose -f docker-compose.jenkins.yml logs jenkins
docker-compose -f docker-compose.jenkins.yml restart jenkins
```

### Port Already in Use
```bash
# Find what's using the port
lsof -i :9090

# Kill the process
kill -9 <PID>
```

### Build Fails
1. Check Jenkins logs: `docker-compose logs jenkins`
2. View build details in Jenkins UI
3. Verify GitHub credentials
4. Check Docker connectivity

## Next Steps

1. ✅ Setup complete - Jenkins is running
2. Access Jenkins at http://localhost:9090
3. Configure GitHub credentials
4. Set up webhook in GitHub
5. Run first build
6. Monitor logs
7. Test deployment process

## Support & Documentation

- **Full Guide**: See `JENKINS_SETUP_GUIDE.md`
- **Quick Ref**: See `QUICK_REFERENCE.md`
- **Jenkins Docs**: https://www.jenkins.io/doc/
- **Docker Docs**: https://docs.docker.com/

## Summary

You now have:
- ✅ Jenkins CI/CD pipeline running on port 9090
- ✅ 3 automated jobs (build, test, deploy)
- ✅ Docker containerization
- ✅ GitHub integration ready
- ✅ Automated scripts for deployment
- ✅ Complete documentation
- ✅ Database backup/restore capabilities

**API Token**: 11c5d0e78cf477527c1cf9361c23c2c42c

Happy deploying! 🚀

---

**Version**: 1.0  
**Created**: 2024-12-20  
**Status**: Production Ready
