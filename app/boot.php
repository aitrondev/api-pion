<?php
/**
 * Class RouteDumper untuk Docs
 */


class RouteDumper extends \Slim\Router {
    public static function getAllRoutes($container) {
        return $container->router->routes;
    }
}

/**
 * Load Config & Bootstraping the API
 * 
 */
session_start();
$app = new \Slim\App($config);
//$app->add(new \Slim\Csrf\Guard);

spl_autoload_register(function ($classname) {
	$classfile = str_replace('\\', '/', $classname);
    require ("classes/" . $classfile . ".php");
});

