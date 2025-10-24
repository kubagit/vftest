<?php

declare(strict_types=1);

namespace App;

use Nette\Bootstrap\Bootstrapper;
use Nette\Bootstrap\Configurator;

final class Bootstrap
{
    public static function boot(): Configurator
    {
        $configurator = new Configurator();
        $configurator->setTempDirectory(__DIR__ . '/../temp');
        $configurator->addStaticParameters([
            'appDir' => __DIR__,
            'wwwDir' => __DIR__ . '/../www',
        ]);

        date_default_timezone_set('Europe/Prague');

        $configurator->addConfig(__DIR__ . '/config/common.neon');
        $localConfig = __DIR__ . '/config/local.neon';
        if (file_exists($localConfig)) {
            $configurator->addConfig($localConfig);
        }

        return $configurator;
    }
}
