<?php

declare(strict_types=1);

namespace App\Router;

use Nette\Application\Routers\Route;
use Nette\Application\Routers\RouteList;

final class RouterFactory
{
    public static function create(): RouteList
    {
        $router = new RouteList();
        $router->addRoute('api/game/<code>/state', 'Api:gameState');
        $router->addRoute('api/game/<code>/start', 'Api:startGame');
        $router->addRoute('api/game/<code>/next', 'Api:nextQuestion');
        $router->addRoute('api/game/<code>/reveal', 'Api:revealAnswer');
        $router->addRoute('api/game/<code>/finish', 'Api:finishGame');
        $router->addRoute('api/game/<code>/players/register', 'Api:registerPlayer');
        $router->addRoute('api/game/<code>/players/<token>/state', 'Api:playerState');
        $router->addRoute('api/game/<code>/players/<token>/answer', 'Api:submitAnswer');
        $router->addRoute('api/game/<code>/players/<token>/leave', 'Api:leaveGame');
        $router->addRoute('ovladac/<token>', 'Player:controller');
        $router->addRoute('odhlaseno', 'Player:exit');
        $router->addRoute('hry/<code>', 'Host:game');
        $router->addRoute('<presenter>/<action>', 'Host:default');
        return $router;
    }
}
