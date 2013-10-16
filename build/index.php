<?php
$config = array(

    /* Required */

    // The directory where PEAR is located
    'phpunit_phar_path'      => __DIR__ . '/phpunit.phar',
    
    
    'cache_directory' => __DIR__ . '/cache',

    // The directories where the tests reside
    'test_directories' => array(
        __DIR__ .  "/test"
    ),


    /* Optional */

    // Whether or not to create snapshots of the test results
    'create_snapshots' => false,

    // The directory where the test results will be stored
    'snapshot_directory' => __DIR__ .  "/snap",

    // Whether or not to sandbox PHP errors
    'sandbox_errors' => true,

    // Which errors to sandbox
    //
    // (note that E_ERROR, E_PARSE, E_CORE_ERROR, E_CORE_WARNING,
    // E_COMPILE_ERROR, E_COMPILE_WARNING, and most of E_STRICT cannot
    // be sandboxed)
    //
    // see the following for more information:
    // http://us3.php.net/manual/en/errorfunc.constants.php
    // http://us3.php.net/manual/en/function.error-reporting.php
    // http://us3.php.net/set_error_handler
    'error_reporting' => E_ALL | E_STRICT,

    // Whether or not to ignore hidden folders
    // (i.e., folders with a '.' prefix)
    'ignore_hidden_folders' => true,

    // The PHPUnit XML configuration files to use
    // (leave empty to disable)
    //
    // In order for VPU to function correctly, the configuration files must
    // contain a JSON listener (see the README for more information)
    'xml_configuration_files' => array(),
    //'xml_configuration_files' => array(
    //    "{$root}/app/config/phpunit.xml"
    //),

    // Paths to any necessary bootstraps
    'bootstraps' => array(
        __DIR__ . '/config/bootstrap.php'
    )
);
require(__DIR__ . '/WebPHPUnit.phar');
//require(__DIR__ . '/WebPHPUnit/WebPHPUnit.php');
\WebPHPUnit\WebPHPUnit::initConfig($config);
\WebPHPUnit\WebPHPUnit::run();

