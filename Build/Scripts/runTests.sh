#!/usr/bin/env bash

#
# TYPO3 extension backend_roles test runner based on docker and
# docker-compose.
# 
# This is adopted from the TYPO3 core's runTests.sh script
#

# Function to write a .env file in Build/testing-docker/local
# This is read by docker-compose and vars defined here are
# used in Build/testing-docker/local/docker-compose.yml
setUpDockerComposeDotEnv() {
    # Delete possibly existing local .env file if exists
    [ -e .env ] && rm .env
    # Set up a new .env file for docker-compose
    {
        echo "COMPOSE_PROJECT_NAME=local"
        # To prevent access rights of files created by the testing, the docker image later
        # runs with the same user that is currently executing the script. docker-compose can't
        # use $UID directly itself since it is a shell variable and not an env variable, so
        # we have to set it explicitly here.
        echo "HOST_UID=$(id -u)"
        # Your local user
        echo "ROOT_DIR=${ROOT_DIR}"
        echo "TEST_FILE=${TEST_FILE}"
        echo "PHP_XDEBUG_ON=${PHP_XDEBUG_ON}"
        echo "PHP_XDEBUG_PORT=${PHP_XDEBUG_PORT}"
        echo "DOCKER_PHP_IMAGE=${DOCKER_PHP_IMAGE}"
        echo "EXTRA_TEST_OPTIONS=${EXTRA_TEST_OPTIONS}"
        echo "CGLCHECK_DRY_RUN=${CGLCHECK_DRY_RUN}"
        echo "SCRIPT_VERBOSE=${SCRIPT_VERBOSE}"
        echo "PHP_VERSION=${PHP_VERSION}"
        echo "OUTPUT_TO_DEV_NULL=${OUTPUT_TO_DEV_NULL}"
    } > .env
}

cleanCacheFiles() {
    echo -n "Clean caches ... " ; rm -rf \
        ../../../.Build/.cache
    echo "done"
}

purgeAllBuildFiles() {
    echo -n "Clean build files ... " ; rm -rf \
        ../../../.Build \
        ../../../Build/testing-docker/local/.env \
        ../../../composer.lock \
        ../../../Documentation-GENERATED-temp
    echo "done"
}

# Load help text into $HELP
read -r -d '' HELP <<EOF
TYPO3 extension backend_roles test runner.
Execute acceptance, unit, functional and other test suites in a docker based
test environment. Handles execution of single test files, sending xdebug
information to a local IDE and more.

Usage: $0 [options] [file]

No arguments: Run all unit tests with PHP 8.2

Options:
    -s <...>
        Specifies which test suite to run
            - cgl: Test and fix all php files
            - clean: Clean up cache and testing related files and folders
            - composerInstall: "composer install"
            - composerUpdate: "composer update"
            - composerValidate: "composer validate"
            - lintPhp: PHP linting
            - phpstan: phpstan tests
            - phpstanGenerateBaseline: regenerate phpstan baseline, handy after phpstan updates
            - rebirth: Remove the .Build folder and the composer.lock file. Note: re-run "$0 -s composerInstall" after this
            - t3docmake: Render the documentation locally
            - unit (default): PHP unit tests

    -p <8.1|8.2>
        Specifies the PHP minor version to be used
            - 8.1: use PHP 8.1
            - 8.2 (default): use PHP 8.2

    -e "<phpunit options>"
        Only with -s unit
        Additional options to send to phpunit (unit & functional tests) or codeception (acceptance
        tests). For phpunit, options starting with "--" must be added after options starting with "-".
        Example -e "-v --filter canRetrieveValueWithGP" to enable verbose output AND filter tests
        named "canRetrieveValueWithGP"

    -x
        Only with -s unit
        Send information to host instance for test or system under test break points. This is especially
        useful if a local PhpStorm instance is listening on default xdebug port 9003. A different port
        can be selected with -y

    -y <port>
        Send xdebug information to a different port than default 9003 if an IDE like PhpStorm
        is not listening on default port.

    -n
        Only with -s cgl
        Activate dry-run in CGL check that does not actively change files and only prints broken ones.

    -u
        Update existing typo3/core-testing-*:latest docker images and remove dangling local docker volumes.
        Maintenance call to docker pull latest versions of the main php images. The images are updated once
        in a while and only the latest ones are supported by core testing. Use this if weird test errors occur.
        Also removes obsolete image versions of typo3/core-testing-*.

    -v
        Enable verbose script output. Shows variables and docker commands.

    -h
        Show this help.

Examples:
    # Run all unit tests using PHP 7.4
    ./Build/Scripts/runTests.sh
    ./Build/Scripts/runTests.sh -s unit

    # Run all units tests and enable xdebug (have a PhpStorm listening on port 9003!)
    ./Build/Scripts/runTests.sh -x

    # Run unit tests with PHP 8.0 and have xdebug enabled
    ./Build/Scripts/runTests.sh -x -p 8.0
EOF

# Test if docker-compose exists, else exit out with error
if ! type "docker-compose" > /dev/null; then
    echo "This script relies on docker and docker-compose. Please install" >&2
    exit 1
fi

# Go to the directory this script is located, so everything else is relative
# to this dir, no matter from where this script is called.
THIS_SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" >/dev/null && pwd)"
cd "$THIS_SCRIPT_DIR" || exit 1

# Go to directory that contains the local docker-compose.yml file
cd ../testing-docker/local || exit 1

# Set core root path by checking whether realpath exists
if ! command -v realpath &> /dev/null; then
    echo "Consider installing realpath for properly resolving symlinks" >&2
    ROOT_DIR="${PWD}/../../../"
else
    ROOT_DIR=$(realpath "${PWD}/../../../")
fi

# Option defaults
TEST_SUITE="unit"
PHP_VERSION="8.2"
PHP_XDEBUG_ON=0
PHP_XDEBUG_PORT=9003
EXTRA_TEST_OPTIONS=""
SCRIPT_VERBOSE=0
CGLCHECK_DRY_RUN=""
OUTPUT_TO_DEV_NULL=" >/dev/null"

# Option parsing
# Reset in case getopts has been used previously in the shell
OPTIND=1
# Array for invalid options
INVALID_OPTIONS=();
# Simple option parsing based on getopts (! not getopt)
while getopts ":s:p:e:xy:nhuv" OPT; do
    case ${OPT} in
        s)
            TEST_SUITE=${OPTARG}
            ;;
        p)
            PHP_VERSION=${OPTARG}
            if ! [[ ${PHP_VERSION} =~ ^(8.1|8.2)$ ]]; then
                INVALID_OPTIONS+=("${OPTARG}")
            fi
            ;;
        e)
            EXTRA_TEST_OPTIONS=${OPTARG}
            ;;
        x)
            PHP_XDEBUG_ON=1
            ;;
        y)
            PHP_XDEBUG_PORT=${OPTARG}
            ;;
        n)
            CGLCHECK_DRY_RUN="-n"
            ;;
        h)
            echo "${HELP}"
            exit 0
            ;;
        u)
            TEST_SUITE=update
            ;;
        v)
            SCRIPT_VERBOSE=1
            ;;
        \?)
            INVALID_OPTIONS+=("${OPTARG}")
            ;;
        :)
            INVALID_OPTIONS+=("${OPTARG}")
            ;;
    esac
done

# Exit on invalid options
if [ ${#INVALID_OPTIONS[@]} -ne 0 ]; then
    echo "Invalid option(s):" >&2
    for I in "${INVALID_OPTIONS[@]}"; do
        echo "-"${I} >&2
    done
    echo >&2
    echo "call \"Build/Scripts/runTests.sh -h\" to display help and valid options"
    exit 1
fi

# Move "8.2" to "php82", the latter is the docker container name
DOCKER_PHP_IMAGE=$(echo "php${PHP_VERSION}" | sed -e 's/\.//')

# Set $1 to first mass argument, this is the optional test file or test directory to execute
shift $((OPTIND - 1))
TEST_FILE=${1}

if [ ${SCRIPT_VERBOSE} -eq 1 ]; then
    set -x
    OUTPUT_TO_DEV_NULL=""
fi

# Suite execution
case ${TEST_SUITE} in
    cgl)
        # Active dry-run for cgl needs not "-n" but specific options
        if [ -n "${CGLCHECK_DRY_RUN}" ]; then
            CGLCHECK_DRY_RUN="--dry-run --diff"
        fi
        setUpDockerComposeDotEnv
        docker-compose run --rm cgl
        SUITE_EXIT_CODE=$?
        docker-compose down
        ;;
    clean)
        cleanCacheFiles
        ;;
    composerInstall)
        setUpDockerComposeDotEnv
        docker-compose run --rm composer_install
        SUITE_EXIT_CODE=$?
        docker-compose down
        ;;
    composerUpdate)
        setUpDockerComposeDotEnv
        docker-compose run --rm composer_update
        SUITE_EXIT_CODE=$?
        docker-compose down
        ;;
    composerValidate)
        setUpDockerComposeDotEnv
        docker-compose run --rm composer_validate
        SUITE_EXIT_CODE=$?
        docker-compose down
        ;;
    lintPhp)
        setUpDockerComposeDotEnv
        docker-compose run --rm lint_php
        SUITE_EXIT_CODE=$?
        docker-compose down
        ;;
    phpstan)
        setUpDockerComposeDotEnv
        docker-compose run --rm phpstan
        SUITE_EXIT_CODE=$?
        docker-compose down
        ;;
    phpstanGenerateBaseline)
        setUpDockerComposeDotEnv
        docker-compose run --rm phpstan_generate_baseline
        SUITE_EXIT_CODE=$?
        docker-compose down
        ;;
    rebirth)
        cleanCacheFiles
        purgeAllBuildFiles
        ;;
    t3docmake)
        setUpDockerComposeDotEnv
        docker-compose run --rm t3docmake
        SUITE_EXIT_CODE=$?
        docker-compose down
        ;;
    unit)
        setUpDockerComposeDotEnv
        docker-compose run --rm unit
        SUITE_EXIT_CODE=$?
        docker-compose down
        ;;
    update)
        # prune unused, dangling local volumes
        echo "> prune unused, dangling local volumes"
        docker volume ls -q -f driver=local -f dangling=true | awk '$0 ~ /^[0-9a-f]{64}$/ { print }' | xargs -I {} docker volume rm {}
        echo ""
        # pull typo3/core-testing-*:latest versions of those ones that exist locally
        echo "> pull typo3/core-testing-*:latest versions of those ones that exist locally"
        docker images typo3/core-testing-*:latest --format "{{.Repository}}:latest" | xargs -I {} docker pull {}
        echo ""
        # remove "dangling" typo3/core-testing-* images (those tagged as <none>)
        echo "> remove \"dangling\" typo3/core-testing-* images (those tagged as <none>)"
        docker images typo3/core-testing-* --filter "dangling=true" --format "{{.ID}}" | xargs -I {} docker rmi {}
        echo ""
        ;;
    *)
        echo "Invalid -s option argument ${TEST_SUITE}" >&2
        echo >&2
        echo "${HELP}" >&2
        exit 1
esac

# Print summary
if [ ${SCRIPT_VERBOSE} -eq 1 ]; then
    # Turn off verbose mode for the script summary
    set +x
fi
echo "" >&2
echo "###########################################################################" >&2
echo "Result of ${TEST_SUITE}" >&2
echo "Environment: local" >&2
echo "PHP: ${PHP_VERSION}" >&2

if [[ ${SUITE_EXIT_CODE} -eq 0 ]]; then
    echo "SUCCESS" >&2
else
    echo "FAILURE" >&2
fi
echo "###########################################################################" >&2
echo "" >&2

# Exit with code of test suite - This script return non-zero if the executed test failed.
exit $SUITE_EXIT_CODE
