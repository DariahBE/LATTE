pipeline {
    agent any

    stages {
        stage('Clone repository') {
            steps {
                checkout scm
            }
        }
        stage('Pull') {
            steps {
                sh 'docker-compose pull'
            }
        }
        stage('Build') {
            steps {
                sh 'docker-compose build --parallel'
            }
        }
        stage('Teardown') {
            steps {
                sh 'docker-compose down --volumes --remove-orphans'
            }
        }
        stage('Static Analysis') {
            steps {
                sh 'docker-compose run client vendor/bin/php-cs-fixer fix --dry-run'
                sh 'docker-compose run client vendor/bin/psalm --show-info=true'
            }
        }
        stage('Test') {
            steps {
                sh 'docker-compose run client php vendor/bin/phpunit'
            }
        }
        stage ('Coverage') {
            steps {
                sh 'docker-compose run client bash -c "\
                    cc-test-reporter before-build && \
                    XDEBUG_MODE=coverage vendor/bin/phpunit --config phpunit.coverage.xml.dist -d memory_limit=1024M && \
                    cp out/phpunit/clover.xml clover.xml && \
                    cc-test-reporter after-build --id ba53635a16f172c606d292e52962b8d05aa53bd8f5407ead59356048829d51cc --exit-code 0"'
            }
        }
    }

    post {
        always {
            sh 'docker-compose down --volumes'
        }
    }
}
