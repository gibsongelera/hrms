#!/bin/bash

###############################################################################
# HRMS Complete CI/CD Setup Script (Linux/Mac)
# This script automates the complete setup process
###############################################################################

set -e

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

# Configuration
JENKINS_PORT=9090
APP_PORT=8080
PHPMYADMIN_PORT=8081

echo -e "${BLUE}╔════════════════════════════════════════════════════════════╗${NC}"
echo -e "${BLUE}║   HRMS Complete CI/CD Setup Script                         ║${NC}"
echo -e "${BLUE}║   Automated Jenkins + Docker + GitHub Integration         ║${NC}"
echo -e "${BLUE}╚════════════════════════════════════════════════════════════╝${NC}"

# Function to check prerequisites
check_prerequisites() {
    echo ""
    echo -e "${YELLOW}Step 1: Checking prerequisites...${NC}"
    echo "=================================="
    
    local missing_tools=0
    
    # Check Docker
    if ! command -v docker &> /dev/null; then
        echo -e "${RED}✗ Docker is not installed${NC}"
        missing_tools=$((missing_tools + 1))
    else
        echo -e "${GREEN}✓ Docker is installed$(docker --version)${NC}"
    fi
    
    # Check Docker Compose
    if ! command -v docker-compose &> /dev/null; then
        echo -e "${RED}✗ Docker Compose is not installed${NC}"
        missing_tools=$((missing_tools + 1))
    else
        echo -e "${GREEN}✓ Docker Compose is installed$(docker-compose --version)${NC}"
    fi
    
    # Check Git
    if ! command -v git &> /dev/null; then
        echo -e "${RED}✗ Git is not installed${NC}"
        missing_tools=$((missing_tools + 1))
    else
        echo -e "${GREEN}✓ Git is installed $(git --version)${NC}"
    fi
    
    # Check cURL
    if ! command -v curl &> /dev/null; then
        echo -e "${RED}✗ cURL is not installed${NC}"
        missing_tools=$((missing_tools + 1))
    else
        echo -e "${GREEN}✓ cURL is installed${NC}"
    fi
    
    if [ $missing_tools -gt 0 ]; then
        echo ""
        echo -e "${RED}✗ Please install missing tools and try again${NC}"
        exit 1
    fi
    
    echo -e "${GREEN}✓ All prerequisites are met${NC}"
}

# Function to setup environment file
setup_env_file() {
    echo ""
    echo -e "${YELLOW}Step 2: Setting up environment configuration...${NC}"
    echo "=================================="
    
    if [ ! -f ".env" ]; then
        cp .env.example .env
        echo -e "${GREEN}✓ Created .env file from template${NC}"
        echo -e "${YELLOW}  Edit .env with your GitHub token and other settings${NC}"
    else
        echo -e "${YELLOW}⚠ .env file already exists${NC}"
    fi
}

# Function to create directories
create_directories() {
    echo ""
    echo -e "${YELLOW}Step 3: Creating required directories...${NC}"
    echo "=================================="
    
    mkdir -p scripts
    mkdir -p jenkins
    mkdir -p backups
    mkdir -p logs
    
    echo -e "${GREEN}✓ Directories created${NC}"
}

# Function to make scripts executable
make_scripts_executable() {
    echo ""
    echo -e "${YELLOW}Step 4: Making scripts executable...${NC}"
    echo "=================================="
    
    chmod +x scripts/*.sh 2>/dev/null || true
    chmod +x scripts/*.bat 2>/dev/null || true
    
    echo -e "${GREEN}✓ Scripts are now executable${NC}"
}

# Function to check port availability
check_ports() {
    echo ""
    echo -e "${YELLOW}Step 5: Checking port availability...${NC}"
    echo "=================================="
    
    local ports_ok=true
    
    for port in $JENKINS_PORT $APP_PORT $PHPMYADMIN_PORT 3307 50000; do
        if lsof -Pi :$port -sTCP:LISTEN -t >/dev/null 2>&1; then
            echo -e "${YELLOW}⚠ Port $port is already in use${NC}"
            ports_ok=false
        else
            echo -e "${GREEN}✓ Port $port is available${NC}"
        fi
    done
    
    if [ "$ports_ok" = false ]; then
        echo -e "${YELLOW}Some ports are in use. You may need to change port mappings.${NC}"
    fi
}

# Function to start Docker services
start_services() {
    echo ""
    echo -e "${YELLOW}Step 6: Starting Docker services...${NC}"
    echo "=================================="
    
    echo "Starting Jenkins..."
    docker-compose -f docker-compose.jenkins.yml up -d
    
    echo "Starting Application..."
    docker-compose -f docker-compose.yml up -d
    
    echo -e "${GREEN}✓ Docker services started${NC}"
}

# Function to wait for services
wait_for_services() {
    echo ""
    echo -e "${YELLOW}Step 7: Waiting for services to be ready...${NC}"
    echo "=================================="
    
    # Wait for Jenkins
    echo "Waiting for Jenkins..."
    for i in {1..60}; do
        if curl -f -s http://localhost:$JENKINS_PORT/login >/dev/null 2>&1; then
            echo -e "${GREEN}✓ Jenkins is ready${NC}"
            break
        fi
        if [ $i -eq 60 ]; then
            echo -e "${RED}✗ Jenkins failed to start${NC}"
            exit 1
        fi
        echo -n "."
        sleep 2
    done
    
    # Wait for Application
    echo ""
    echo "Waiting for Application..."
    for i in {1..30}; do
        if curl -f -s http://localhost:$APP_PORT >/dev/null 2>&1; then
            echo -e "${GREEN}✓ Application is ready${NC}"
            break
        fi
        if [ $i -eq 30 ]; then
            echo -e "${YELLOW}⚠ Application may take longer to initialize${NC}"
        fi
        echo -n "."
        sleep 2
    done
    
    echo ""
}

# Function to configure Jenkins
configure_jenkins() {
    echo ""
    echo -e "${YELLOW}Step 8: Configuring Jenkins...${NC}"
    echo "=================================="
    
    echo "Installing Jenkins plugins..."
    # Note: Plugins install asynchronously, this just initiates
    ./scripts/jenkins-setup.sh
    
    echo -e "${GREEN}✓ Jenkins configuration initiated${NC}"
}

# Function to display summary
display_summary() {
    echo ""
    echo -e "${GREEN}╔════════════════════════════════════════════════════════════╗${NC}"
    echo -e "${GREEN}║           Setup Completed Successfully!                    ║${NC}"
    echo -e "${GREEN}╚════════════════════════════════════════════════════════════╝${NC}"
    
    echo ""
    echo -e "${BLUE}Access Information:${NC}"
    echo "  Jenkins:         http://localhost:$JENKINS_PORT"
    echo "  Application:     http://localhost:$APP_PORT"
    echo "  PHPMyAdmin:      http://localhost:$PHPMYADMIN_PORT"
    echo "  Database:        localhost:3307"
    
    echo ""
    echo -e "${BLUE}Credentials:${NC}"
    echo "  Jenkins User:    admin"
    echo "  Jenkins Token:   11c5d0e78cf477527c1cf9361c23c2c42c"
    echo "  DB User:         hrms_user"
    echo "  DB Password:     hrms_pass"
    
    echo ""
    echo -e "${BLUE}Next Steps:${NC}"
    echo "  1. Edit .env file with your GitHub token"
    echo "  2. Access Jenkins and complete initial setup"
    echo "  3. Configure GitHub webhooks"
    echo "  4. Run first build: curl -X POST http://localhost:9090/job/hrms-build/build"
    
    echo ""
    echo -e "${BLUE}Useful Commands:${NC}"
    echo "  View logs:       docker-compose logs -f jenkins"
    echo "  Stop services:   docker-compose down"
    echo "  Check status:    docker-compose ps"
    
    echo ""
    echo -e "${BLUE}Documentation:${NC}"
    echo "  Full Guide:      JENKINS_SETUP_GUIDE.md"
    echo "  Quick Ref:       QUICK_REFERENCE.md"
    
    echo ""
}

# Main execution
main() {
    check_prerequisites
    setup_env_file
    create_directories
    make_scripts_executable
    check_ports
    start_services
    wait_for_services
    configure_jenkins
    display_summary
}

# Run main function
main

echo -e "${GREEN}Setup complete!${NC}"
