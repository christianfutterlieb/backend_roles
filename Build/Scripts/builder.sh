#!/usr/bin/env sh

#
# ------------------------------------------------------------------------------
#
# This is the extension builder and test run manager for the "backend_roles"
# extension.
#
#
# Exit codes
# ----------
#
# Note: a command that calls an external appllication will return the app's
#       exit code.
#       This script however uses the exit codes from following table (see
#       also sysexits.h):
#
#   0   EX_OK             OK
#   64  EX_USAGE          Invalid user input
#   70  EX_SOFTWARE       Internal error: no exit code from command 
#   71  EX_OSERR          No docker installation found
#   74  EX_IOERR          System problem: cannot determine current directory or
#                         change to the project root directory
#
# ------------------------------------------------------------------------------
#
# @author christian@futterlieb.ch
#


# ------------------------------------------------------------------------------
# Part 1: Initializations, Functions, definitions
# ------------------------------------------------------------------------------
EX_OK=0
EX_USAGE=64
EX_SOFTWARE=70
EX_OSERR=71
EX_IOERR=74

scriptsDir=""
rootDir=""
outputVerbosity=0
isDryRun=0
isCiEnvironment=0
randomSuffix=$(printf "%s" $RANDOM)
projectPrefix="t3ext-backend_roles"
dockerLabelName="org.typo3.extensions.backend_roles"
dockerLabelValue="${projectPrefix}-${randomSuffix}"
dockerLabel="${dockerLabelName}=${dockerLabelValue}"
#dockerNetworkName="${projectPrefix}-${randomSuffix}"
phpVersion="8.1"
composerPreferLowest=0
composerBinDir=".Build/bin"
enableCoverage=0

msg_ok() {
    printf "\033[1;32m%s\033[0m\n" "${1}"
}
msg_err() {
    printf "\033[1;31m%s\033[0m\n" "${1}" >&2
}

resetCaches() {
    echo "Removing files:"

    printf "  .Build/.cache ..."
    rm -rf "${rootDir}/.Build/.cache"
    echo " OK"

    echo "Done"
}

resetProject() {
    echo "Removing files:"

    printf "  ./.Build ..."
    rm -rf "${rootDir}/.Build"
    echo " OK"

    printf "  ./var ..."
    rm -rf "${rootDir}/var"
    echo " OK"
    
    printf "  ./composer.lock ..."
    rm -rf "${rootDir}/composer.lock"
    echo " OK"

    printf "  ./Documentation-GENERATED-temp ..."
    rm -rf "${rootDir}/Documentation-GENERATED-temp"
    echo " OK"

    echo "Done"
}

loadDockerArgumentsForComposer() {
    # COMPOSER_HOME
    local composerHome="${rootDir}/.Build/composer"

    # COMPOSER_CACHE_DIR
    local composerCacheDir="${composerHome}/cache"

    local dockerArgumentsForComposer="-e COMPOSER_HOME=${composerHome} -e COMPOSER_CACHE_DIR=${composerCacheDir} -e COMPOSER_AUTH=${composerAuth}"
    printf '%s\n' "${dockerArgumentsForComposer}"
}

createCommandList() {
    printf "Avaliable commands:

    commands                 Show available commands
    composer:install         Run composer install (install dependencies)
    composer:normalize       Normalize composer.json file. Use -n to run only a test.
    composer:validate        Run composer validate
    documentation:render     Render the documentation
    help                     Show help text
    reset                    Reset the project to a state like when it has been freshly cloned
    reset:cache              Reset the build caches
    lint:php                 Run php lint
    test:qa:cgl              Run php-cs-fixer. Use -n to run only the check without changing the code
    test:qa:phpstan          Run phpstan (static code analysis)
    test:qa:phpstan:baseline Generate phpstan baseline (not available in CI environment)
    test:qa:rector           Run rector. Use -n to run only the check without changing the code
    test:unit                Run unit tests
"
}

createHelpText() {
    printf "EXT:backend_roles extension builder

Usage: ${0} [options] command [command-arguments..]

%s

Options:
    -c
        Enable code coverage report. This option has only effect for following commands:
          - test:unit

    -l
        Run composer update with --prefer-lowest. This option has only effect for following commands:
          - composer:install

    -n
        Dry-run the operation. This option has only effect for following commands:
          - composer:install
#          - composer:normalize
          - test:qa:cgl
          - test:qa:rector

    -p <8.1|8.2|8.3|8.4>
        Specifies the PHP minor version to be used:
          - 8.1 (default): use PHP 8.1
          - 8.2: use PHP 8.2
          - 8.3: use PHP 8.3
          - 8.4: use PHP 8.4
    -v
        Show more information in output (increase verbosity)
" "$(createCommandList)"
}

# Test if docker exists, else exit with error
if ! type "docker" >/dev/null 2>&1; then
    msg_err "This script relies on docker. Please install"
    exit $EX_OSERR
fi

# Fix the directory where the script is run in to the main project directory
scriptsDir=$(CDPATH= cd "$(dirname "$0")" && pwd)
cd "$scriptsDir" || exit $EX_IOERR
cd ../../ || exit $EX_IOERR
rootDir="${PWD}"

# Decide whether we're the CI environment or not
if [ "${CI}" = "true" ]; then
    isCiEnvironment=1
fi

# ------------------------------------------------------------------------------
# Part 2: Option parsing
# ------------------------------------------------------------------------------
while getopts ":clnp:v" OPT; do
    case ${OPT} in
        c)
            enableCoverage=1
            ;;
        l)
            composerPreferLowest=1
            ;;
        n)
            isDryRun=1
            ;;
        p)
            phpVersion=${OPTARG}
            if [ ${phpVersion} != "8.1" ] && [ ${phpVersion} != "8.2" ] && [ ${phpVersion} != "8.3" ] && [ ${phpVersion} != "8.4" ]; then
                msg_err "Invalid PHP version"
                exit $EX_USAGE
            fi
            ;;
        v)
            outputVerbosity=1
            ;;
        \?)
            msg_err "Illegal option -${OPTARG}"
            exit $EX_USAGE
            ;;
        :)
            msg_err "No arg for -${OPTARG} option"
            exit $EX_USAGE
            ;;
    esac
done


# Input option shifting:
# 1. Skip the options parsed by getopts
shift $(expr $OPTIND - 1)
# 2. Store the first remaining argument as $command
command=${1}
if [ -z $command ]; then
    msg_err "No command given. Try 'help' to get some help."
    exit $EX_USAGE
fi
# 3. Shift again to have only the remaining stuff in $@ and move $1 after the command argument
shift 1

# ------------------------------------------------------------------------------
# Part 3: Check/setup system
# ------------------------------------------------------------------------------

# ------------------------------------------------------------------------------
# Part 4: Setup docker
# ------------------------------------------------------------------------------
# Initial arguments
dockerBaseArguments="--pull always"
if [ ! $isCiEnvironment -eq 1 ]; then
    dockerBaseArguments="${dockerBaseArguments} -it --init"
fi

# Remove containers when they exit, add the label
dockerBaseArguments="${dockerBaseArguments} --rm --label '${dockerLabel}'"

# Setup docker container name
dockerContainerName="${projectPrefix}-$(echo ${command} | sed 's/:/_/g')-${randomSuffix}"
dockerBaseArguments="${dockerBaseArguments} --name ${dockerContainerName}"

# Run docker jobs as current user to prevent permission issues.
if [ $(uname) != "Darwin" ]; then
    dockerBaseArguments="${dockerBaseArguments} --user $(id -u)"
fi

# Setup filesystem mounts
dockerArguments="${dockerBaseArguments} -v ${rootDir}:${rootDir} -w ${rootDir}"

# Setup docker networking
#docker network create ${dockerNetworkName} --label ${dockerLabel} > /dev/null
#dockerArguments="${dockerArguments} --network ${dockerNetworkName}"

# Setup image/version
dockerImagePhp="ghcr.io/typo3/core-testing-$(echo "php${phpVersion}" | sed -e 's/\.//'):latest"
dockerImageDocmake="ghcr.io/t3docs/render-documentation:latest"

# ------------------------------------------------------------------------------
# Part 5: Run the command
# ------------------------------------------------------------------------------

# Default exit code (meaning: no exit code from command)
commandExitCode=$EX_SOFTWARE
showSummary=1
usedDockerImage="none"

# Temporary: disable xdebug
dockerArguments="${dockerArguments} -e XDEBUG_MODE=off"

case ${command} in
    commands)
        createCommandList
        commandExitCode=$EX_OK
        showSummary=0
        ;;
    composer:install)
        dockerArguments="${dockerArguments} $(loadDockerArgumentsForComposer)"

        composerOptions=""
        if [ ${composerPreferLowest} -eq 1 ]; then
            composerOptions="${composerOptions} --prefer-lowest"
        fi

        if [ ${isDryRun} -eq 1 ]; then
            composerOptions="${composerOptions} --dry-run"
        fi

        if [ $isCiEnvironment -eq 1 ]; then
            composerOptions="${composerOptions} --no-progress"
        fi

        docker run ${dockerArguments} ${dockerImagePhp} composer update ${composerOptions}
        commandExitCode=$?

        usedDockerImage=${dockerImagePhp}
        ;;
    composer:normalize)
        dockerArguments="${dockerArguments} $(loadDockerArgumentsForComposer)"
        commandInDocker="composer normalize --no-check-lock --diff"
        if [ ${isDryRun} -eq 1 ]; then
            commandInDocker="${commandInDocker} --dry-run"
        fi
        docker run ${dockerArguments} ${dockerImagePhp} ${commandInDocker}
        commandExitCode=$?
        usedDockerImage=${dockerImagePhp}
        ;;
    composer:validate)
        dockerArguments="${dockerArguments} $(loadDockerArgumentsForComposer)"
        docker run ${dockerArguments} ${dockerImagePhp} composer validate --no-check-lock
        commandExitCode=$?
        usedDockerImage=${dockerImagePhp}
        showSummary=0
        ;;
    documentation:render)
        # Use docker base arguments only (no volumes or working-dir), and add the appropriate volumes
        dockerArguments="${dockerBaseArguments} -v ${rootDir}:/PROJECT:ro -v ${rootDir}/Documentation-GENERATED-temp:/RESULT"
        if [ ! -d ${rootDir}/Documentation-GENERATED-temp ]; then
            mkdir -p ${rootDir}/Documentation-GENERATED-temp
        fi
        docker run ${dockerArguments} ${dockerImageDocmake} makehtml
        commandExitCode=$?
        usedDockerImage=${dockerImageDocmake}
        ;;
    help)
        createHelpText
        commandExitCode=$EX_OK
        showSummary=0
        ;;
    lint:php)
        commandInDocker="php -v | grep '^PHP'; find . -name \\*.php ! -path ./.Build/\\* -print0 | xargs -0 -n1 -P4 php -dxdebug.mode=off -l"
        if [ ${outputVerbosity} -lt 1 ]; then
            commandInDocker="${commandInDocker} >/dev/null"
        fi
        docker run ${dockerArguments} ${dockerImagePhp} /bin/sh -c "${commandInDocker}"
        commandExitCode=$?
        usedDockerImage=${dockerImagePhp}
        ;;
    reset)
        resetProject
        commandExitCode=$EX_OK
        showSummary=0
        ;;
    reset:cache)
        resetCaches
        commandExitCode=$EX_OK
        showSummary=0
        ;;
    test:qa:cgl)
        phpCsFixerArguments="fix --verbose --diff --config=Build/php-cs-fixer/config.php"

        if [ ${isDryRun} -eq 1 ]; then
            phpCsFixerArguments="${phpCsFixerArguments} --dry-run"
        fi

        # Differences for CI environment: caching, progressbar
        if [ $isCiEnvironment -eq 1 ]; then
            phpCsFixerArguments="${phpCsFixerArguments} --using-cache=no --show-progress=none"
        else
            phpCsFixerArguments="${phpCsFixerArguments} --cache-file=.Build/php-cs-fixer.cache --show-progress=dots"
        fi

        docker run ${dockerArguments} ${dockerImagePhp} php ${rootDir}/${composerBinDir}/php-cs-fixer ${phpCsFixerArguments}
        commandExitCode=$?
        usedDockerImage=${dockerImagePhp}
        ;;
    test:qa:phpstan)
        phpstanArguments="analyse -c Build/phpstan/phpstan.neon"
        if [ $isCiEnvironment -eq 1 ]; then
            phpstanArguments="${phpstanArguments} --no-progress"
        fi
        docker run ${dockerArguments} ${dockerImagePhp} php ${rootDir}/${composerBinDir}/phpstan ${phpstanArguments} ${@}
        commandExitCode=$?
        usedDockerImage=${dockerImagePhp}
        ;;
    test:qa:phpstan:baseline)
        if [ $isCiEnvironment -eq 1 ]; then
            msg_err "Error: phpstan beseline cannot be generated in CI environment"
            commandExitCode=$EX_USAGE
        else
            phpstanArguments="analyse -c Build/phpstan/phpstan.neon --generate-baseline=Build/phpstan/phpstan-baseline.neon"
            docker run ${dockerArguments} ${dockerImagePhp} php ${rootDir}/${composerBinDir}/phpstan ${phpstanArguments}
            commandExitCode=$?
            usedDockerImage=${dockerImagePhp}
        fi
        ;;
    test:qa:rector)
        rectorArguments="process --config Build/rector/rector.php"
        if [ $isCiEnvironment -eq 1 ]; then
            rectorArguments="${rectorArguments} --no-progress-bar"
        fi
        if [ ${isDryRun} -eq 1 ]; then
            rectorArguments="${rectorArguments} --dry-run"
        fi

        docker run ${dockerArguments} ${dockerImagePhp} php ${rootDir}/${composerBinDir}/rector ${rectorArguments} ${@}
        commandExitCode=$?
        usedDockerImage=${dockerImagePhp}
        ;;
    test:unit)
        dockerArguments="${dockerArguments} -e TYPO3_PATH_ROOT=${rootDir}/.Build/public"
        phpunitArguments="-c Build/phpunit/UnitTests.xml"

        # En-/disable code coverage report
        if [ ${enableCoverage} -eq 1 ]; then
            dockerArguments="${dockerArguments} -e XDEBUG_MODE=coverage"
        else
            phpunitArguments="${phpunitArguments} --no-coverage"
        fi

        docker run ${dockerArguments} ${dockerImagePhp} php ${rootDir}/${composerBinDir}/phpunit ${phpunitArguments} ${@}
        commandExitCode=$?
        usedDockerImage=${dockerImagePhp}
        ;;
    *)
        msg_err "Unknown command ${command}. Try 'help' to get some help."
        # Note: do no exit directly here, we need to call function shutdownDockerContainersAndNetwork first
        commandExitCode=$EX_USAGE
        showSummary=0
        ;;
esac


# ------------------------------------------------------------------------------
# Part 6: Finish (clenup, summarize operations)
# ------------------------------------------------------------------------------

# Print summary
summaryTitle="Result of ${command}"
if [ ${outputVerbosity} -gt 0 ]; then
    showSummary=1
    summaryTitle="${summaryTitle} (verbose mode level ${outputVerbosity})"
fi

if [ $showSummary -eq 1 ]; then
    echo "" >&2
    echo "##########################################" >&2
    echo "${summaryTitle}" >&2
    echo "------------------------------------------" >&2
    if [ $isCiEnvironment -eq 1 ]; then
        echo "Environment:   CI" >&2
    else
        echo "Environment:   local" >&2
    fi

    echo "PHP version:   $phpVersion" >&2
    echo "Image used:    $usedDockerImage" >&2
    echo "------------------------------------------" >&2

    if [ $commandExitCode -eq $EX_OK ]; then
        msg_ok "SUCCESS" >&2
    else
        msg_err "FAILURE" >&2
    fi
    echo "##########################################" >&2
    echo "" >&2
fi

# Exit with code of the executed command
exit $commandExitCode
