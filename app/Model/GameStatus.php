<?php

declare(strict_types=1);

namespace App\Model;

final class GameStatus
{
    public const LOBBY = 'lobby';
    public const QUESTION = 'question';
    public const REVEAL = 'reveal';
    public const FINISHED = 'finished';
}
