<?php

namespace VfTest\Lib;

use Symfony\Component\Yaml\Yaml;

class Config {

    /**
     * @var array
     */
    private $configValues;

    /**
     * @constructor
     * @param string $configFileName YAML config file name including path
     * @return void
     */
    public function __construct($configFileName) {
        $configValues = Yaml::parse(file_get_contents($configFileName));
        $this->setConfigValues($configValues);
    }

    /**
     * Sets config values
     * @param array $configValues Array with config values
     * @return void
     */
    public function setConfigValues($configValues) {
        $this->configValues = $configValues;
    }

    /**
     * Gets config value
     * @param string $configKey Config key
     * @return mixed
     */
    public function getConfigValue($configKey) {
        return $this->configValues[$configKey];
    }

}
