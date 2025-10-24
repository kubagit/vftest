<?php

declare(strict_types=1);

namespace App\Model;

use DateTimeImmutable;
use Nette\Database\Explorer;
use Nette\Utils\Random;
use RuntimeException;

final class GameService
{
    public function __construct(
        private readonly Explorer $db,
        private readonly QuestionService $questionService,
        private readonly PlayerService $playerService,
    ) {
    }

    public function createGame(int $questionTotal, int $countdownSeconds): array
    {
        if ($questionTotal < 1 || $questionTotal > 50) {
            throw new RuntimeException('Počet otázek musí být mezi 1 a 50.');
        }

        if ($countdownSeconds < 5 || $countdownSeconds > 300) {
            throw new RuntimeException('Časovač musí být mezi 5 a 300 sekundami.');
        }

        $attempts = 0;
        $row = null;
        $now = new DateTimeImmutable();

        while ($attempts < 5 && $row === null) {
            $code = strtoupper(Random::generate(6, 'A-Z0-9'));
            if ($this->db->table('games')->where('code', $code)->count('*') > 0) {
                $attempts++;
                continue;
            }
            $row = $this->db->table('games')->insert([
                'code' => $code,
                'status' => GameStatus::LOBBY,
                'question_total' => $questionTotal,
                'question_current' => 0,
                'countdown_seconds' => $countdownSeconds,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        if ($row === null) {
            throw new RuntimeException('Nepodařilo se založit hru.');
        }

        return $row->toArray();
    }

    public function getGameByCode(string $code): ?array
    {
        $row = $this->db->table('games')->where('code', $code)->fetch();
        return $row?->toArray();
    }

    public function getGameById(int $id): ?array
    {
        $row = $this->db->table('games')->get($id);
        return $row?->toArray();
    }

    public function startGame(string $code): array
    {
        $game = $this->requireGame($code);
        if ($game['status'] !== GameStatus::LOBBY) {
            throw new RuntimeException('Hra již byla zahájena.');
        }
        $players = $this->playerService->getActivePlayers((int) $game['id']);
        if ($players->count('*') === 0) {
            throw new RuntimeException('Do hry se musí připojit alespoň jeden hráč.');
        }
        $now = new DateTimeImmutable();
        $this->db->table('games')->where('id', $game['id'])->update([
            'status' => GameStatus::QUESTION,
            'question_current' => 1,
            'question_started_at' => $now,
            'updated_at' => $now,
        ]);

        $question = $this->questionService->startQuestion((int) $game['id'], 1, (int) $game['countdown_seconds']);

        return array_merge($game, [
            'status' => GameStatus::QUESTION,
            'question_current' => 1,
            'question_started_at' => $now,
            'question' => $question,
        ]);
    }

    public function startNextQuestion(string $code): array
    {
        $game = $this->requireGame($code);
        if ($game['status'] === GameStatus::FINISHED) {
            throw new RuntimeException('Hra již skončila.');
        }
        if ($game['question_current'] >= $game['question_total']) {
            return $this->finishGame($code);
        }

        $nextIndex = (int) $game['question_current'] + 1;
        $now = new DateTimeImmutable();
        $this->db->table('games')->where('id', $game['id'])->update([
            'status' => GameStatus::QUESTION,
            'question_current' => $nextIndex,
            'question_started_at' => $now,
            'updated_at' => $now,
        ]);

        $question = $this->questionService->startQuestion((int) $game['id'], $nextIndex, (int) $game['countdown_seconds']);

        return array_merge($game, [
            'status' => GameStatus::QUESTION,
            'question_current' => $nextIndex,
            'question_started_at' => $now,
            'question' => $question,
        ]);
    }

    public function revealAnswer(string $code): void
    {
        $game = $this->requireGame($code);
        $question = $this->questionService->getActiveQuestion((int) $game['id']);
        if ($question !== null) {
            $this->questionService->markRevealed((int) $question['id']);
            $this->scoreQuestion((int) $question['id']);
        }
        $this->db->table('games')->where('id', $game['id'])->update([
            'status' => GameStatus::REVEAL,
            'updated_at' => new DateTimeImmutable(),
        ]);
    }

    public function finishGame(string $code): array
    {
        $game = $this->requireGame($code);
        $now = new DateTimeImmutable();
        $this->db->table('games')->where('id', $game['id'])->update([
            'status' => GameStatus::FINISHED,
            'finished_at' => $now,
            'updated_at' => $now,
        ]);

        return array_merge($game, [
            'status' => GameStatus::FINISHED,
            'finished_at' => $now,
        ]);
    }

    private function scoreQuestion(int $questionId): void
    {
        $questionRow = $this->db->table('questions')->get($questionId);
        if ($questionRow === null) {
            return;
        }

        foreach ($this->db->table('responses')->where('question_id', $questionId) as $response) {
            if ((int) $response['selected_index'] === (int) $questionRow['correct_index'] && !(bool) $response['is_correct']) {
                $this->db->table('responses')->where('id', $response['id'])->update([
                    'is_correct' => true,
                ]);
                $this->db->table('players')->where('id', $response['player_id'])->update([
                    'score' => new \Nette\Database\SqlLiteral('score + 1'),
                ]);
            }
        }
    }

    private function requireGame(string $code): array
    {
        $game = $this->getGameByCode($code);
        if ($game === null) {
            throw new RuntimeException('Hra nebyla nalezena.');
        }
        return $game;
    }
}
