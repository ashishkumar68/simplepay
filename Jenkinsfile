pipeline {
    agent {
        dockerfile {
            dir 'docker/jenkins'
            args '--network=simplepay-net --ip=172.18.0.6'
        }
    }
    triggers {
        githubPush()
    }
    stages {
        stage('build') {
            steps {
                emailext (
                    subject: "STARTED: Job '${env.JOB_NAME} [${env.BUILD_NUMBER}]'",
                    body: """<p>STARTED: Job '${env.JOB_NAME} [${env.BUILD_NUMBER}]':</p><p>Check console output at <a href='${env.BUILD_URL}'>${env.JOB_NAME} [${env.BUILD_NUMBER}]</a></p>""",
                    recipientProviders: [[$class: 'DevelopersRecipientProvider']]
                )
                withCredentials([string(credentialsId: 'simple_pay_ashish_token', variable: 'TOKEN')]) {
                    sh "curl -XPOST -H 'Authorization: token $TOKEN' https://api.github.com/repos/ashishkumar68/simplepay/statuses/\$(git rev-parse HEAD) -d '{\"state\":\"pending\",\"target_url\":\"${BUILD_URL}\",\"description\": \"The build is pending\"}'"
                }
                sh 'rm -rf var/cache/* var/log/*'
                sh 'git clean -df && git reset --hard'
                withCredentials([string(credentialsId: 'mysql_test_db_pass', variable: 'DB_PASS')]) {
                    sh 'echo "DATABASE_URL=mysql://root:$DB_PASS@172.18.0.2:3306/simplepay" >> .env.test'
                    sh 'mysql -h 172.18.0.2 -u root -p$DB_PASS -e "create database simplepay;"'
                }
                sh 'echo "TEST_HOST=http://172.18.0.6" >> .env.test'
                sh 'composer install --optimize-autoloader'
                sh 'composer dump-env test'
                sh 'chmod -R 777 var/cache var/log'
            }
        }
        stage('Prepare Web Server') {
            steps {
                sh "sed -i -e 's/project_dir/${WORKSPACE.replace('/', '\\/')}\\/public/g' /etc/apache2/sites-available/000-default.conf"
                sh 'a2enmod rewrite'
                sh 'service apache2 start'
            }
        }
        stage('test') {
            steps {
                sh 'APP_ENV=test php bin/console doctrine:migrations:migrate'
                sh 'APP_ENV=test php -d memory_limit=-1 vendor/bin/phpspec run --format=junit > phpspec.junit.xml'
                sh 'APP_ENV=test php -d memory_limit=-1 vendor/bin/phpunit --exclude-group unit --log-junit phpunit.junit.xml'
                sh 'APP_ENV=test php -d memory_limit=-1 vendor/bin/behat --strict --stop-on-failure --format progress --out std --format junit --out behat.junit.xml'
            }
        }
        stage('SonarQube Analysis') {
            steps {
                // un-comment the below script if you are using sonarqube developer or above edition to see
                // branch wise code quality analysis.
                // sh "echo 'sonar.branch.name=$BRANCH_NAME' >> sonar-project.properties"
                script {
                    def scannerHome = tool 'SonarScanner';
                    withSonarQubeEnv('Sonar CCQ Server') {
                        sh "${scannerHome}/bin/sonar-scanner"
                    }
                }
            }
        }
    }
    post {
        always {
            sh 'echo Github Webhook check 8'
            withCredentials([string(credentialsId: 'mysql_test_db_pass', variable: 'DB_PASS')]) {
                sh 'mysql -h 172.18.0.2 -u root -p$DB_PASS -e "drop database simplepay;"'
            }
        }
        success {
            withCredentials([string(credentialsId: 'simple_pay_ashish_token', variable: 'TOKEN')]) {
                sh "curl -XPOST -H 'Authorization: token $TOKEN' https://api.github.com/repos/ashishkumar68/simplepay/statuses/\$(git rev-parse HEAD) -d '{\"state\":\"success\",\"target_url\":\"${BUILD_URL}\",\"description\": \"The build succeeded\"}'"
            }
            emailext (
                subject: "SUCCESSFUL: Job '${env.JOB_NAME} [${env.BUILD_NUMBER}]'",
                body: """<p>SUCCESSFUL: Job '${env.JOB_NAME} [${env.BUILD_NUMBER}]':</p><p>Check console output at <a href='${env.BUILD_URL}'>${env.JOB_NAME} [${env.BUILD_NUMBER}]</a></p>""",
                recipientProviders: [[$class: 'DevelopersRecipientProvider']]
            )
        }
        failure {
            withCredentials([string(credentialsId: 'simple_pay_ashish_token', variable: 'TOKEN')]) {
                sh "curl -XPOST -H 'Authorization: token $TOKEN' https://api.github.com/repos/ashishkumar68/simplepay/statuses/\$(git rev-parse HEAD) -d '{\"state\":\"failure\",\"target_url\":\"${BUILD_URL}\",\"description\": \"The build failed\"}'"
            }
            emailext (
                subject: "FAILED: Job '${env.JOB_NAME} [${env.BUILD_NUMBER}]'",
                body: """<p>FAILED: Job '${env.JOB_NAME} [${env.BUILD_NUMBER}]':</p><p>Check console output at <a href='${env.BUILD_URL}'>${env.JOB_NAME} [${env.BUILD_NUMBER}]</a></p>""",
                recipientProviders: [[$class: 'DevelopersRecipientProvider']]
            )
        }
    }
}
