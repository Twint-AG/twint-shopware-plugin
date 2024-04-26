@Library('nfq-library') _

def versions =  [
    "infra/demo65": "Twint-Gitlab/twint-ag%252Ftwint-infrastructure%252Finfrastructure/version-65", 
    "infra/demo66": "Twint-Gitlab/twint-ag%252Ftwint-infrastructure%252Finfrastructure/version-66"
]

pipeline {
    agent any 
    options {
        ansiColor('xterm')
    }

    parameters {
        string (name: "gitBranch", defaultValue: "develop", description: "Branch to build")
        string (name: "git_sha", defaultValue: "HEAD", description: "sha to build")
        string (name: "changed_files", defaultValue: "", description: "Changed files")
    }

    triggers {
        GenericTrigger(
        genericVariables: [
            [key: 'gitBranch', value: '$.ref'],
            [key: 'git_sha', value: '$.after'],
            [key: 'changed_files', value: '$.commits[*].["modified","added","removed"][*]']
        ],

        causeString: 'Triggered on $gitBranch',

        token: "twint-trigger",

        printContributedVariables: true,
        printPostContent: true,

        silentResponse: false,
        )
    }

    environment {
        SOURCE_REPO_URL = "git@git.nfq.asia:twint-ag/twint-infrastructure/infrastructure.git"
        SOURCE_REPO_BRANCH = "check"
        GIT_CREDENTIAL_ID = "D13-GITLAB-SSH-KEY"
        INFRA_DIR = "infra"
        COMMIT_MESSAGE = "Auto-commit: deploy shopware application"
    }

    stages {
        stage("Checkout infra repository") {
            steps {
                script {
                    gitCheckout( 
                        gitUrl = SOURCE_REPO_URL,  
                        gitBranch = SOURCE_REPO_BRANCH,
                        targetDir = INFRA_DIR,
                        credentialId = GIT_CREDENTIAL_ID
                    )
                }
            }
        }

        stage('Check changed files') {
            steps {
                script {
                    dir(INFRA_DIR) {
                        versions.each { key, value ->
                            println("Changed_files list: ${changed_files}")
                            if (changed_files.contains(key)) {
                                 build job: value, wait: true
                            }
                        }
                    }
                }
                    
            }
        }
        
    } // End stages

    post {
        always {
            cleanWs()
        }
    } 

} // End pipeline  
