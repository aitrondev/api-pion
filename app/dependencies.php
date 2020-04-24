<?php

/**
 * Custom error handler
 * 
 */
$container = $app->getContainer();
/*$container['errorHandler'] = function ($c) {
    return function ($request, $response, $exception) use ($c) {
        return $c['response']
            ->withStatus(500)
            ->withJson( array( 'status' => false, 'message' => $exception) );
    };
};*/

$container['notAllowedHandler'] = function ($c) {
    return function ($request, $response, $methods) use ($c) {
        return $c['response']
            ->withStatus(405)
            ->withJson( array( 'status' => false, 'message' => '405 Not Allowed') );
    };
};

$container['notFoundHandler'] = function ($c) {
    return function ($request, $response) use ($c) {
        return $c['response']
            ->withStatus(404)
            ->withJson( array( 'status' => false, 'message' => '404 Not Found') );
    };
};

/**
 * Dependency Injection
 * tambah disini jika menambah 3rd party plugin lagi
 * 
 */
// monolog
$container['settings']['logger'] = function($c) {
    $logger = new \Monolog\Logger('my_logger');
    $file_handler = new \Monolog\Handler\StreamHandler("../logs/app.log");
    $logger->pushHandler($file_handler);
    return $logger;
};

// eloquest laravel
$capsule = new \Illuminate\Database\Capsule\Manager;
$capsule->addConnection($container['db']);
$capsule->setAsGlobal();
$capsule->bootEloquent();

/*timezone*/
$container['timezone'];

/*csrf*/
$container['csrf'] = function ($c) {
    return new \Slim\Csrf\Guard;
};

// templating - php view
$container['renderer'] = function ($container) {
    return new \Slim\Views\PhpRenderer('../app/views/');
};


/**
 * disabled for now in case we need this on the future :)
 */
/*// templating - twig
$container['view'] = function ($container) {
    $view = new \Slim\Views\Twig('../app/views/', [
        'cache' => '../app/cache'
    ]);
    $view->addExtension(new \Slim\Views\TwigExtension(
        $container['router'],
        $container['request']->getUri()
    ));

    return $view;
};
*/