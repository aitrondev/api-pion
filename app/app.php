<?php
/**
 * @AppName: BIBEX API
 * @Version: 1.0
 * @CreateDate: September 2016
 * @Author: Wisnu Hafid
 * @Docs: http://api.domain.com/docs
 * @Description: build with Slim Framework, Laravel Eloquent
 *
 */
  header("Access-Control-Allow-Origin: *");
require '../vendor/autoload.php';
require '../config/global.php';
require '../config/db.php';

include 'boot.php';
include 'dependencies.php';
include 'router.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);
$app->run();
