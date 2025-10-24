<?php

declare(strict_types=1);

namespace App\Model;

use App\Services\WikipediaQuestionProvider;
use DateTimeImmutable;
use Nette\Database\Explorer;
use Nette\Utils\Json;
use RuntimeException;

final class QuestionService
{
    public function __construct(
        private readonly Explorer $db,
        private readonly WikipediaQuestionProvider $questionProvider,
    ) {
    }

    public function startQuestion(int $gameId, int $sequence, int $countdownSeconds): array
    {
        $question = $this->questionProvider->fetchQuestion();
        $now = new DateTimeImmutable();

        $row = $this->db->table('questions')->insert([
            'game_id' => $gameId,
            'sequence' => $sequence,
            'question' => $question['question'],
            'options' => Json::encode($question['options']),
            'correct_index' => $question['correctIndex'],
            'source_url' => $question['source'],
            'started_at' => $now,
            'reveal_at' => $now->modify(sprintf('+%d seconds', $countdownSeconds)),
        ]);

        if ($row === null) {
            throw new RuntimeException('Nepodařilo se uložit otázku.');
        }

        return $row->toArray();
    }

    public function getActiveQuestion(int $gameId): ?array
    {
        $row = $this->db->table('questions')
            ->where('game_id', $gameId)
            ->order('sequence DESC')
            ->limit(1)
            ->fetch();

        return $row?->toArray();
    }

    public function markRevealed(int $questionId): void
    {
        $this->db->table('questions')->where('id', $questionId)->update([
            'revealed_at' => new DateTimeImmutable(),
        ]);
    }
}
