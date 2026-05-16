pipeline {
    agent any

    options {
        buildDiscarder(logRotator(numToKeepStr: '10'))
        timeout(time: 30, unit: 'MINUTES')
    }

    environment {
        IMAGE_NAME   = 'hrms-app'
        IMAGE_TAG    = "${env.BUILD_NUMBER}"
        REGISTRY     = ''                       // e.g. 'docker.io/yourname'  (leave blank to skip push)
        REGISTRY_CRED = 'dockerhub-credentials' // Jenkins credentials ID (only used if REGISTRY is set)
        COMPOSE_PROJECT_NAME = 'hrms'
    }

    stages {
        stage('Checkout') {
            steps {
                checkout scm
            }
        }

        stage('Lint PHP') {
            steps {
                script {
                    if (isUnix()) {
                        sh '''
                            docker run --rm -v "$PWD":/app -w /app php:8.2-cli \
                                sh -c "find . -name '*.php' -not -path './vendor/*' -print0 | xargs -0 -n1 php -l"
                        '''
                    } else {
                        bat 'docker run --rm -v "%CD%":/app -w /app php:8.2-cli sh -c "find . -name *.php -not -path ./vendor/* -print0 | xargs -0 -n1 php -l"'
                    }
                }
            }
        }

        stage('Build Image') {
            steps {
                script {
                    def fullName = "${env.IMAGE_NAME}:${env.IMAGE_TAG}"
                    if (isUnix()) {
                        sh "docker build -t ${fullName} -t ${env.IMAGE_NAME}:latest ."
                    } else {
                        bat "docker build -t ${fullName} -t ${env.IMAGE_NAME}:latest ."
                    }
                }
            }
        }

        stage('Smoke Test (compose up)') {
            steps {
                script {
                    // We test the app from INSIDE the web container (docker exec)
                    // because Jenkins runs in its own container and can't reach the
                    // host's published ports via "localhost".
                    if (isUnix()) {
                        sh '''
                            set -e
                            # Only start "web" (which pulls in "db" via depends_on).
                            # Skip phpmyadmin in CI — it's not needed for smoke testing
                            # and its dependency timeout sometimes fires before MySQL is healthy.
                            docker compose -p ${COMPOSE_PROJECT_NAME}_ci \
                                -f docker-compose.yml -f docker-compose.ci.yml up -d web

                            echo "Waiting up to 120s for hrms_web_ci to respond..."
                            success=0
                            for i in $(seq 1 60); do
                                if docker exec hrms_web_ci curl -fsS -o /dev/null -w "%{http_code}" http://localhost/ 2>/dev/null | grep -qE "^(200|302)$"; then
                                    echo "App is responding (attempt $i)."
                                    success=1
                                    break
                                fi
                                sleep 2
                            done

                            docker compose -p ${COMPOSE_PROJECT_NAME}_ci \
                                -f docker-compose.yml -f docker-compose.ci.yml ps

                            if [ "$success" -ne 1 ]; then
                                echo "App never responded; printing logs:"
                                docker compose -p ${COMPOSE_PROJECT_NAME}_ci \
                                    -f docker-compose.yml -f docker-compose.ci.yml logs --tail=200
                                exit 1
                            fi
                        '''
                    } else {
                        bat '''
                            docker compose -p %COMPOSE_PROJECT_NAME%_ci ^
                                -f docker-compose.yml -f docker-compose.ci.yml up -d web
                            ping 127.0.0.1 -n 60 > nul
                            docker compose -p %COMPOSE_PROJECT_NAME%_ci ^
                                -f docker-compose.yml -f docker-compose.ci.yml ps
                            docker exec hrms_web_ci curl -fsS -o NUL http://localhost/ || ^
                                ( docker compose -p %COMPOSE_PROJECT_NAME%_ci ^
                                    -f docker-compose.yml -f docker-compose.ci.yml ^
                                    logs --tail=200 & exit /b 1 )
                        '''
                    }
                }
            }
            post {
                always {
                    script {
                        if (isUnix()) {
                            sh '''
                                docker compose -p ${COMPOSE_PROJECT_NAME}_ci \
                                    -f docker-compose.yml -f docker-compose.ci.yml down -v || true
                            '''
                        } else {
                            bat '''
                                docker compose -p %COMPOSE_PROJECT_NAME%_ci ^
                                    -f docker-compose.yml -f docker-compose.ci.yml down -v || ver > nul
                            '''
                        }
                    }
                }
            }
        }

        stage('Push Image') {
            when { expression { return env.REGISTRY?.trim() } }
            steps {
                script {
                    def remote = "${env.REGISTRY}/${env.IMAGE_NAME}"
                    withCredentials([usernamePassword(credentialsId: env.REGISTRY_CRED,
                                                     usernameVariable: 'REG_USER',
                                                     passwordVariable: 'REG_PASS')]) {
                        if (isUnix()) {
                            sh """
                                echo "$REG_PASS" | docker login ${env.REGISTRY} -u "$REG_USER" --password-stdin
                                docker tag ${env.IMAGE_NAME}:${env.IMAGE_TAG} ${remote}:${env.IMAGE_TAG}
                                docker tag ${env.IMAGE_NAME}:latest ${remote}:latest
                                docker push ${remote}:${env.IMAGE_TAG}
                                docker push ${remote}:latest
                            """
                        } else {
                            bat """
                                echo %REG_PASS% | docker login ${env.REGISTRY} -u %REG_USER% --password-stdin
                                docker tag ${env.IMAGE_NAME}:${env.IMAGE_TAG} ${remote}:${env.IMAGE_TAG}
                                docker tag ${env.IMAGE_NAME}:latest ${remote}:latest
                                docker push ${remote}:${env.IMAGE_TAG}
                                docker push ${remote}:latest
                            """
                        }
                    }
                }
            }
        }
    }

    post {
        success { echo "Build #${env.BUILD_NUMBER} succeeded. Image: ${env.IMAGE_NAME}:${env.IMAGE_TAG}" }
        failure { echo "Build #${env.BUILD_NUMBER} FAILED." }
        always  { echo "Pipeline finished." }
    }
}
