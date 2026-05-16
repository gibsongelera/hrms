pipeline {
    agent any

    options {
        timestamps()
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
                    if (isUnix()) {
                        sh '''
                            docker compose -p $COMPOSE_PROJECT_NAME up -d
                            sleep 25
                            docker compose -p $COMPOSE_PROJECT_NAME ps
                            curl -fsS http://localhost:8080/ || (docker compose -p $COMPOSE_PROJECT_NAME logs --tail=100 web && exit 1)
                        '''
                    } else {
                        bat '''
                            docker compose -p %COMPOSE_PROJECT_NAME% up -d
                            ping 127.0.0.1 -n 25 > nul
                            docker compose -p %COMPOSE_PROJECT_NAME% ps
                            curl -fsS http://localhost:8080/ || ( docker compose -p %COMPOSE_PROJECT_NAME% logs --tail=100 web & exit /b 1 )
                        '''
                    }
                }
            }
            post {
                always {
                    script {
                        if (isUnix()) {
                            sh 'docker compose -p $COMPOSE_PROJECT_NAME down -v || true'
                        } else {
                            bat 'docker compose -p %COMPOSE_PROJECT_NAME% down -v || ver > nul'
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
