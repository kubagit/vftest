<?php

declare(strict_types=1);

namespace App\Model;

use DateTimeImmutable;
use Nette\Database\Explorer;
use Nette\Utils\Json;

final class GameStateFormatter
{
    public function __construct(private readonly Explorer $db)
    {
    }

    public function buildHostState(array $game): array
    {
        $players = $this->db->table('players')
            ->where('game_id', $game['id'])
            ->order('score DESC, joined_at ASC')
            ->fetchAll();

        $question = $this->db->table('questions')
            ->where('game_id', $game['id'])
            ->order('sequence DESC')
            ->limit(1)
            ->fetch();

        $questionData = null;
        if ($question !== null) {
            $questionData = [
                'sequence' => (int) $question['sequence'],
                'text' => $question['question'],
                'options' => Json::decode((string) $question['options'], Json::FORCE_ARRAY),
                'correctIndex' => (int) $question['correct_index'],
                'startedAt' => $this->formatDate($question['started_at']),
                'revealAt' => $this->formatDate($question['reveal_at']),
                'revealedAt' => $this->formatDate($question['revealed_at']),
                'sourceUrl' => $question['source_url'],
            ];
        }

        $responses = [];
        if ($question !== null) {
            foreach ($this->db->table('responses')->where('question_id', $question['id']) as $response) {
                $responses[(int) $response['player_id']] = [
                    'selectedIndex' => (int) $response['selected_index'],
                    'isCorrect' => (bool) $response['is_correct'],
                    'answeredAt' => $this->formatDate($response['answered_at']),
                ];
            }
        }

        $playerList = [];
        foreach ($players as $player) {
            $playerList[] = [
                'name' => $player['name'],
                'score' => (int) $player['score'],
                'token' => $player['token'],
                'isActive' => (bool) $player['is_active'],
                'answered' => $responses[$player['id']] ?? null,
            ];
        }

        return [
            'code' => $game['code'],
            'status' => $game['status'],
            'question' => $questionData,
            'players' => $playerList,
            'questionTotal' => (int) $game['question_total'],
            'questionCurrent' => (int) $game['question_current'],
            'countdownSeconds' => (int) $game['countdown_seconds'],
            'questionStartedAt' => $this->formatDate($game['question_started_at']),
            'finishedAt' => $this->formatDate($game['finished_at'] ?? null),
        ];
    }

    public function buildPlayerState(array $game, array $player): array
    {
        $question = $this->db->table('questions')
            ->where('game_id', $game['id'])
            ->order('sequence DESC')
            ->limit(1)
            ->fetch();

        $response = null;
        if ($question !== null) {
            $responseRow = $this->db->table('responses')->where([
                'question_id' => $question['id'],
                'player_id' => $player['id'],
            ])->fetch();
            if ($responseRow !== null) {
                $response = [
                    'selectedIndex' => (int) $responseRow['selected_index'],
                    'isCorrect' => (bool) $responseRow['is_correct'],
                    'answeredAt' => $this->formatDate($responseRow['answered_at']),
                ];
            }
        }

        $questionData = null;
        if ($question !== null) {
            $questionData = [
                'sequence' => (int) $question['sequence'],
                'text' => $question['question'],
                'options' => Json::decode((string) $question['options'], Json::FORCE_ARRAY),
                'correctIndex' => (int) $question['correct_index'],
                'startedAt' => $this->formatDate($question['started_at']),
                'revealAt' => $this->formatDate($question['reveal_at']),
                'revealedAt' => $this->formatDate($question['revealed_at']),
                'sourceUrl' => $question['source_url'],
            ];
        }

        return [
            'player' => [
                'name' => $player['name'],
                'score' => (int) $player['score'],
                'isActive' => (bool) $player['is_active'],
            ],
            'status' => $game['status'],
            'question' => $questionData,
            'response' => $response,
            'questionTotal' => (int) $game['question_total'],
            'questionCurrent' => (int) $game['question_current'],
            'countdownSeconds' => (int) $game['countdown_seconds'],
            'questionStartedAt' => $this->formatDate($game['question_started_at']),
        ];
    }

    private function formatDate($value): ?string
    {
        if ($value === null) {
            return null;
        }
        if ($value instanceof DateTimeImmutable) {
            return $value->format(DateTimeImmutable::ATOM);
        }
        if ($value instanceof \DateTimeInterface) {
            return $value->format(DateTimeImmutable::ATOM);
        }
        if (is_string($value)) {
            return (new DateTimeImmutable($value))->format(DateTimeImmutable::ATOM);
        }

        return null;
    }
}
