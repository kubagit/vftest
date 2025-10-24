<?php

declare(strict_types=1);

namespace App\Model;

use DateTimeImmutable;
use Nette\Database\Explorer;
use Nette\Database\Table\Selection;
use Nette\Utils\Random;
use RuntimeException;

final class PlayerService
{
    public function __construct(private readonly Explorer $db)
    {
    }

    public function registerPlayer(int $gameId, string $name): array
    {
        $trimmed = trim($name);
        if ($trimmed === '') {
            throw new RuntimeException('Jméno hráče nesmí být prázdné.');
        }

        $token = Random::generate(32);
        $row = $this->db->table('players')->insert([
            'game_id' => $gameId,
            'name' => $trimmed,
            'token' => $token,
            'score' => 0,
            'is_active' => true,
            'joined_at' => new DateTimeImmutable(),
        ]);

        if ($row === null) {
            throw new RuntimeException('Registrace hráče selhala.');
        }

        return $row->toArray();
    }

    public function getActivePlayers(int $gameId): Selection
    {
        return $this->db->table('players')
            ->where('game_id', $gameId)
            ->where('is_active', true)
            ->order('score DESC, joined_at ASC');
    }

    public function findByToken(string $token): ?array
    {
        $row = $this->db->table('players')->where('token', $token)->fetch();
        return $row?->toArray();
    }

    public function recordResponse(int $questionId, int $playerId, int $selectedIndex): void
    {
        $existing = $this->db->table('responses')->where([
            'question_id' => $questionId,
            'player_id' => $playerId,
        ])->fetch();

        if ($existing !== null) {
            $this->db->table('responses')->where('id', $existing['id'])->update([
                'selected_index' => $selectedIndex,
                'answered_at' => new DateTimeImmutable(),
            ]);
            return;
        }

        $this->db->table('responses')->insert([
            'question_id' => $questionId,
            'player_id' => $playerId,
            'selected_index' => $selectedIndex,
            'is_correct' => false,
            'answered_at' => new DateTimeImmutable(),
        ]);
    }

    public function leaveGame(string $token): void
    {
        $player = $this->findByToken($token);
        if ($player === null) {
            return;
        }

        $this->db->table('players')->where('id', $player['id'])->update([
            'is_active' => false,
            'left_at' => new DateTimeImmutable(),
        ]);
    }
}
