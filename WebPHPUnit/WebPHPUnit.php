<?php
namespace WebPHPUnit;
require __DIR__ . '/Lib/nx.php';

class WebPHPUnit
{
    protected static $config = array();

    const PACKAGE = 'WebPHPUnit';
    
    const VERSION = '0.0.1';

    const BASEPATH = __DIR__;
    
    public static function getClassMap()
    {
        return array(
            '\\controller\\Home' => WPU_BATH . str_replace('\\', DIRECTORY_SEPARATOR, '\\controller\\Home')
        );
    }

    public static function autoload($className)
    {
        if( strpos($className, '\\') === FALSE || strrpos($className, self::PACKAGE) === FALSE) return FALSE;

        $className = ltrim(ltrim($className, self::PACKAGE), '\\');
        $path      = implode(DIRECTORY_SEPARATOR, explode('\\', $className)) . '.php';
        $path      = self::BASEPATH . DIRECTORY_SEPARATOR . $path;
        if(file_exists($path) && is_readable($path)) include($path);
        return class_exists($className, false) || interface_exists($className, false);
    }
    
    public static function initConfig($config)
    {
        self::$config = $config;
        foreach(self::$config['bootstraps'] as $file) { require $file; }

        //hacked for phpunit.phar;
        $GLOBALS['_SERVER']['SCRIPT_NAME'] = '-';
        ob_start();
        require self::$config['phpunit_phar_path'];
        ob_end_clean();
    }
    
    public static function getConfig($name = null)
    {
        if($name === null) return self::$config;
        return isset(self::$config[$name]) ? self::$config[$name] : FALSE;
    }

    public static function loadStaticFile($fileName)
    {
        return @file_get_contents( self::BASEPATH . DIRECTORY_SEPARATOR . 'Static' .  DIRECTORY_SEPARATOR . $fileName);
    }

    public static function run()
    {
        spl_autoload_register(array(__CLASS__, 'autoload'));

        $request    = new \nx\core\Request();
        $dispatcher = new \nx\core\Dispatcher();
        $dispatcher->handle($request, self::getRoutes());
    }
    
    public static function getRoutes()
    {
        $selfClassName = __CLASS__;

        return array(
            array(array('get', 'post'), '/', function($request) {
                $controller = new \WebPHPUnit\Controller\Home();
                return $controller->call('index', $request);
            }),

            array('get', '/archives', function($request) {
                $controller = new \WebPHPUnit\Controller\Archives();
                return $controller->call('index', $request);
            }),

            array('get', '/file-list', function($request) {
                $controller = new \WebPHPUnit\Controller\FileList();
                return $controller->call('index', $request);
            }),

            array('get', '/help', function($request) {
                $controller = new \WebPHPUnit\Controller\Home();
                return $controller->call('help', $request);
            }),

            array('get', '/css/.*\.css', function($request) use($selfClassName) {
                return array(
                    'status' => 200,
                    'headers' => array('Content-Type: text/css; charset=utf-8'),
                    'body'   => $selfClassName::loadStaticFile(str_replace(array('/', '\\'), '', pathinfo($request->url, PATHINFO_BASENAME)))
                );
            }),

            array('get', '/js/.*\.js', function($request) use($selfClassName) {
                return array(
                    'status' => 200,
                    'headers' => array('Content-Type: text/javascript; charset=utf-8'),
                    'body'   => $selfClassName::loadStaticFile(str_replace(array('/', '\\'), '', pathinfo($request->url, PATHINFO_BASENAME)))
                );
            }),

            array('get', '/img/.*\.png', function($request) use($selfClassName) {
                return array(
                    'status' => 200,
                    'headers' => array('Content-Type: image/png; charset=utf-8'),
                    'body'   => $selfClassName::loadStaticFile(str_replace(array('/', '\\'), '', pathinfo($request->url, PATHINFO_BASENAME)))
                );
            }),

            array('get', '/img/.*\.gif', function($request) use($selfClassName) {
                return array(
                    'status'  => 200,
                    'headers' => array('Content-Type: image/gif; charset=utf-8'),
                    'body'    => $selfClassName::loadStaticFile(str_replace(array('/', '\\'), '', pathinfo($request->url, PATHINFO_BASENAME)))
                );
            }),

            // 404
            array('get', '*', function($request) {
                return array(
                    'status' => 404,
                    'body'   => '<h1>Not Found</h1>'
                );
            }),


        );
    }
}

