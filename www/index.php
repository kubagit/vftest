<?php

declare(strict_types=1);

use App\Bootstrap;
use Nette\Application\Application;

require __DIR__ . '/../vendor/autoload.php';

$bootstrap = Bootstrap::boot();
$container = $bootstrap->createContainer();

/** @var Application $application */
$application = $container->getByType(Application::class);
$application->run();
