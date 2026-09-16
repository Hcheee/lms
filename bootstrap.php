<?php

require_once __DIR__ . '/vendor/autoload.php';

$config = require __DIR__ . '/config.php';

$connection = new \App\Database\Connection($config);

$repository = new \App\Repository\CourseRepository(
    $connection->getPdo()
);

$courseService = new \App\Service\CourseService(
    $repository
);