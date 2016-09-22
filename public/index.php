<?php
require __DIR__ . '/../database/initialize.php';
require __DIR__ . '/../Jacwright/RestServer/RestServer.php';
require __DIR__ . '/class.OWDDBController.php';
//redirect_to('test.php');
$server = new \Jacwright\RestServer\RestServer('debug');
$server->addClass('OWDDBController');
$server->handle();
