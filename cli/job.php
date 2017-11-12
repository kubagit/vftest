<?php

require __DIR__.'/../vendor/autoload.php';

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

(new Application('vftest', '1.0.0'))
  ->register('findCode')
      ->addArgument('cities', InputArgument::IS_ARRAY, 'The city name')
      ->setCode(function(InputInterface $input, OutputInterface $output) {
          print_r($input->getArguments('cities'));
      })
  ->getApplication()
  ->setDefaultCommand('findCode', TRUE)
  ->run();