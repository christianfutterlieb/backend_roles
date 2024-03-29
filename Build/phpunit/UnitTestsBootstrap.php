<?php
/*
 * This file is initially copied from typo3/testing-framework
 *
 * The TYPO3 project - inspiring people to share!
 */

/**
 * Boilerplate for a unit test phpunit boostrap file.
 *
 * This file is loosely maintained within TYPO3 testing-framework, extensions
 * are encouraged to not use it directly, but to copy it to an own place,
 * usually in parallel to a UnitTests.xml file.
 *
 * This file is defined in UnitTests.xml and called by phpunit
 * before instantiating the test suites.
 *
 * The recommended way to execute the suite is "runTests.sh". See the
 * according script within TYPO3 core's Build/Scripts directory and
 * adapt to extensions needs.
 */
(static function () {
    $testbase = new \TYPO3\TestingFramework\Core\Testbase();

    // These if's are for core testing (package typo3/cms) only. cms-composer-installer does
    // not create the autoload-include.php file that sets these env vars and sets composer
    // mode to true. testing-framework can not be used without composer anyway, so it is safe
    // to do this here. This way it does not matter if 'bin/phpunit' or 'vendor/phpunit/phpunit/phpunit'
    // is called to run the tests since the 'relative to entry script' path calculation within
    // SystemEnvironmentBuilder is not used. However, the binary must be called from the document
    // root since getWebRoot() uses 'getcwd()'.
    if (!getenv('TYPO3_PATH_ROOT')) {
        putenv('TYPO3_PATH_ROOT=' . rtrim($testbase->getWebRoot(), '/'));
    }
    if (!getenv('TYPO3_PATH_WEB')) {
        putenv('TYPO3_PATH_WEB=' . rtrim($testbase->getWebRoot(), '/'));
    }

    $testbase->defineSitePath();

    // We can use the "typo3/cms-composer-installers" constant "TYPO3_COMPOSER_MODE" to determine composer mode.
    // This should be always true except for TYPO3 mono repository.
    $composerMode = defined('TYPO3_COMPOSER_MODE') && TYPO3_COMPOSER_MODE === true;
    $requestType = \TYPO3\CMS\Core\Core\SystemEnvironmentBuilder::REQUESTTYPE_BE | \TYPO3\CMS\Core\Core\SystemEnvironmentBuilder::REQUESTTYPE_CLI;
    \TYPO3\TestingFramework\Core\SystemEnvironmentBuilder::run(0, $requestType, $composerMode);

    $testbase->createDirectory(\TYPO3\CMS\Core\Core\Environment::getPublicPath() . '/typo3conf/ext');
    $testbase->createDirectory(\TYPO3\CMS\Core\Core\Environment::getPublicPath() . '/typo3temp/assets');
    $testbase->createDirectory(\TYPO3\CMS\Core\Core\Environment::getPublicPath() . '/typo3temp/var/tests');
    $testbase->createDirectory(\TYPO3\CMS\Core\Core\Environment::getPublicPath() . '/typo3temp/var/transient');

    // Retrieve an instance of class loader and inject to core bootstrap
    $classLoader = require $testbase->getPackagesPath() . '/autoload.php';
    \TYPO3\CMS\Core\Core\Bootstrap::initializeClassLoader($classLoader);

    // Initialize default TYPO3_CONF_VARS
    $configurationManager = new \TYPO3\CMS\Core\Configuration\ConfigurationManager();
    $GLOBALS['TYPO3_CONF_VARS'] = $configurationManager->getDefaultConfiguration();

    $cache = new \TYPO3\CMS\Core\Cache\Frontend\PhpFrontend(
        'core',
        new \TYPO3\CMS\Core\Cache\Backend\NullBackend('production', [])
    );
    $packageManager = \TYPO3\CMS\Core\Core\Bootstrap::createPackageManager(
        \TYPO3\CMS\Core\Package\UnitTestPackageManager::class,
        \TYPO3\CMS\Core\Core\Bootstrap::createPackageCache($cache)
    );

    \TYPO3\CMS\Core\Utility\GeneralUtility::setSingletonInstance(\TYPO3\CMS\Core\Package\PackageManager::class, $packageManager);
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::setPackageManager($packageManager);

    $testbase->dumpClassLoadingInformation();

    \TYPO3\CMS\Core\Utility\GeneralUtility::purgeInstances();
})();
