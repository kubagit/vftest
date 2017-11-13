<?php

require __DIR__ . '/../vendor/autoload.php';

use Symfony\Component\Console\Application;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

use VfTest\Command\FindCodeCommand;


$container = new ContainerBuilder();
$container->register('config', 'VfTest\Lib\Config')
        ->addArgument(__DIR__ . '/../config/vftest.yml');
$container->register('location', 'VfTest\Lib\LocationClient')
        ->addArgument(new Reference('config'));
$container->register('finder', 'VfTest\Lib\CodeFinder')
        ->addArgument(new Reference('location'));


$application = new Application();
$application->add(new FindCodeCommand($container));
$application->setDefaultCommand('findCode', TRUE);
$application->run();
