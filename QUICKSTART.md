# 🚀 HRMS Jenkins CI/CD - Quick Start Guide

## One-Line Start (Choose Your Platform)

### Linux/Mac:
```bash
chmod +x scripts/complete-setup.sh && ./scripts/complete-setup.sh
```

### Windows (PowerShell):
```powershell
powershell -ExecutionPolicy Bypass -File scripts\complete-setup.ps1
```

---

## What You Get

```
✅ Jenkins running on http://localhost:9090
✅ 3 CI/CD jobs (build, test, deploy)
✅ Docker containers for app + database
✅ GitHub webhook integration
✅ Automated backups & migrations
✅ Complete documentation
```

---

## Important Credentials

| Item | Value |
|------|-------|
| Jenkins URL | http://localhost:9090 |
| Jenkins User | admin |
| **Jenkins API Token** | **11c5d0e78cf477527c1cf9361c23c2c42c** |
| DB User | hrms_user |
| DB Password | hrms_pass |
| DB Host | localhost:3307 |

---

## 3-Step Manual Setup

### Step 1: Start Services
```bash
docker-compose -f docker-compose.jenkins.yml up -d
docker-compose -f docker-compose.yml up -d
```

### Step 2: Initialize Jenkins
```bash
# Linux/Mac
./scripts/jenkins-setup.sh

# Windows
.\scripts\jenkins-setup.bat
```

### Step 3: Configure GitHub
1. Go to http://localhost:9090
2. Add credentials with your GitHub token
3. Set webhook in GitHub repo to `http://your-ip:9090/github-webhook/`

---

## Common Commands

### Trigger Builds
```bash
# Build
curl -X POST http://localhost:9090/job/hrms-build/build \
  -u admin:11c5d0e78cf477527c1cf9361c23c2c42c

# Test
curl -X POST http://localhost:9090/job/hrms-test/build \
  -u admin:11c5d0e78cf477527c1cf9361c23c2c42c

# Deploy (staging)
curl -X POST http://localhost:9090/job/hrms-deploy/buildWithParameters \
  -u admin:11c5d0e78cf477527c1cf9361c23c2c42c \
  -d "ENVIRONMENT=staging&IMAGE_TAG=latest&RUN_MIGRATIONS=true"
```

### View Logs
```bash
docker-compose -f docker-compose.jenkins.yml logs -f jenkins
docker-compose -f docker-compose.yml logs -f web
```

### Database Operations
```bash
# Backup
docker exec hrms_db mysqldump -u hrms_user -p"hrms_pass" hrms_db > backup.sql

# Restore
docker exec hrms_db mysql -u hrms_user -p"hrms_pass" hrms_db < backup.sql
```

---

## Job Details

### 🔨 Job 1: Build
- Checkout code
- Lint PHP
- Build Docker image
- Tag with build number

### ✓ Job 2: Test
- Database connectivity
- PHP validation
- Endpoint testing
- Health checks

### 🚀 Job 3: Deploy
- Database backup
- Stop containers
- Start new containers
- Run migrations
- Health verification

---

## Accessing Services

| Service | URL | Login |
|---------|-----|-------|
| Jenkins | http://localhost:9090 | admin / token |
| App | http://localhost:8080 | See app |
| PHPMyAdmin | http://localhost:8081 | hrms_user / hrms_pass |

---

## GitHub Auto-Push

```bash
export GITHUB_TOKEN="your-token"
export GITHUB_REPO="username/repo"

# Linux/Mac
./scripts/github-auto-push.sh "Deployment successful" "main"

# Windows
.\scripts\github-auto-push.bat "Deployment successful" "main"
```

---

## Troubleshooting

### Jenkins Won't Start
```bash
docker-compose -f docker-compose.jenkins.yml logs jenkins
docker-compose -f docker-compose.jenkins.yml restart jenkins
```

### Port in Use
```bash
# Find process
lsof -i :9090

# Kill it
kill -9 <PID>
```

### Build Fails
1. Check Jenkins logs
2. Verify GitHub credentials
3. Check Docker is running
4. View build console in Jenkins UI

---

## Files Structure

```
├── Jenkinsfile                          # CI/CD pipeline
├── docker-compose.jenkins.yml           # Jenkins setup
├── docker-compose.yml                   # App setup (modified)
├── .env.example                         # Environment template
├── scripts/
│   ├── complete-setup.sh               # Automated setup
│   ├── complete-setup.ps1              # Windows setup
│   ├── jenkins-setup.sh                # Jenkins init
│   ├── jenkins-setup.bat               # Windows init
│   ├── github-auto-push.sh             # GitHub push
│   └── github-auto-push.bat            # Windows push
├── jenkins/
│   ├── job-build-config.xml            # Build job
│   ├── job-test-config.xml             # Test job
│   └── job-deploy-config.xml           # Deploy job
├── JENKINS_SETUP_GUIDE.md              # Full guide
├── QUICK_REFERENCE.md                  # Commands ref
└── JENKINS_CI_CD_SETUP.md              # This file
```

---

## Pipeline Workflow

```
Developer pushes to GitHub
         ↓
GitHub webhook triggers build
         ↓
Job 1: Build (PHP lint + Docker build)
         ↓
Job 2: Test (Database + endpoints)
         ↓
Job 3: Deploy (Backup + migrate + start)
         ↓
Application online
         ↓
Auto-push release notes to GitHub
```

---

## Environment Variables

Create `.env` file with:
```bash
MYSQL_ROOT_PASSWORD=rootpass
MYSQL_DATABASE=hrms_db
MYSQL_USER=hrms_user
MYSQL_PASSWORD=hrms_pass

JENKINS_JAVA_OPTIONS=-Xmx2048m
GITHUB_TOKEN=11c5d0e78cf477527c1cf9361c23c2c42c
GITHUB_REPO=your-username/hrms-main
```

---

## Documentation

| File | Purpose |
|------|---------|
| `JENKINS_SETUP_GUIDE.md` | Comprehensive setup guide (300+ lines) |
| `QUICK_REFERENCE.md` | Quick command reference |
| `JENKINS_CI_CD_SETUP.md` | Architecture and details |
| `README.md` | Project overview (if present) |

---

## Next Steps After Setup

1. ✅ Run automated setup script
2. 🔐 Change Jenkins admin password
3. 🔑 Add GitHub credentials
4. 🔗 Configure GitHub webhook
5. ▶️ Trigger first build
6. 📊 Monitor build progress
7. 🎯 Test deployment process
8. 📝 Review documentation

---

## Support

**For detailed information**, see:
- Full setup guide: `JENKINS_SETUP_GUIDE.md`
- Quick reference: `QUICK_REFERENCE.md`
- Architecture details: `JENKINS_CI_CD_SETUP.md`

**External Resources:**
- Jenkins: https://www.jenkins.io/doc/
- Docker: https://docs.docker.com/
- GitHub Actions Alternative: https://github.com/features/actions

---

## Key Highlights

🎯 **Fully Automated** - One command to set up everything
🔄 **CI/CD Ready** - 3 jobs for build, test, deploy
🐳 **Docker Native** - Containerized app and database
🔗 **GitHub Integrated** - Webhook triggering and auto-push
💾 **Backup Enabled** - Automatic database backups
📚 **Well Documented** - 3 comprehensive guides
🔐 **Secure** - Credentials management included

---

## Version Info

- **Version**: 1.0
- **Created**: 2024-12-20
- **Jenkins**: Latest LTS (jdk17)
- **Docker**: 20.10+
- **Status**: ✅ Production Ready

---

**Ready to deploy? Run:**
```bash
./scripts/complete-setup.sh
```

Then open http://localhost:9090 in your browser! 🚀

---

**Questions? Check the documentation files or run the setup script for interactive guidance.**
