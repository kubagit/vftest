<?php

namespace VfTest\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class FindCodeCommand extends Command {

    private $container;

    public function __construct($container) {
        parent::__construct();
        $this->setContainer($container);
    }

    public function setContainer($container) {
        $this->container = $container;
    }

    protected function configure() {
        $this->setName('findCode')
                ->addArgument('cities', InputArgument::IS_ARRAY, 'Names of the cities')
                ->setDescription('Finds post codes')
                ->setHelp('This command allows you to find UK post codes by city name')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        $givenArguments = $input->getArguments();
        $givenCities = $givenArguments['cities'];

        $codeFinder = $this->container->get('finder');
        try {
            $searchResult = $codeFinder->findPostalCodes($givenCities);
            $output->write($searchResult);
        } catch (Exception $e) {
            $output->write($e->getMessage());
        };
    }

}
