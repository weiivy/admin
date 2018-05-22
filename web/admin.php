<?php

// composer class autoload
$loader = require __DIR__ . '/../vendor/autoload.php';

// create app
$app = new \Rain\Application();

// config
require __DIR__ . '/../app/config/app.php';

// run application
$app->run();
