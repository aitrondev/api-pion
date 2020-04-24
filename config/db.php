<?php

/**
 * Database Configuration
 *
 */
$config['db'] = [
    'driver' => 'mysql',
    'charset'   => 'utf8',
    'collation' => 'utf8_unicode_ci',
    'prefix'    => '',
];

// setting online
if ($_SERVER['HTTP_HOST']!="localhost" && $_SERVER['HTTP_HOST']!="localhost:12345") {
    $config['db']['host'] = 'localhost';
    $config['db']['database'] = 'pion'; //matel
    $config['db']['username'] = 'root';
    $config['db']['password'] = 'jatimcom';
// setting local
} else {
    $config['db']['host'] = 'localhost';
    $config['db']['database'] = 'eorder';
    $config['db']['username'] = 'root';
    $config['db']['password'] = '';
}
