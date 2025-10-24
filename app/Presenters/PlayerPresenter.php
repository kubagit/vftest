<?php

declare(strict_types=1);

namespace App\Presenters;

use App\Model\GameService;
use App\Model\PlayerService;

final class PlayerPresenter extends BasePresenter
{
    public function __construct(
        private readonly GameService $gameService,
        private readonly PlayerService $playerService,
    ) {
    }

    public function renderController(string $token): void
    {
        $player = $this->playerService->findByToken($token);
        if ($player === null) {
            $this->redirect('exit');
        }
        if (!$player['is_active']) {
            $this->redirect('exit');
        }

        $game = $this->gameService->getGameById((int) $player['game_id']);
        if ($game === null) {
            $this->redirect('exit');
        }

        $this->template->token = $token;
        $this->template->gameCode = $game['code'];
        $this->template->player = $player;
    }

    public function renderExit(): void
    {
    }
}
