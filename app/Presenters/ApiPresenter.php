<?php

declare(strict_types=1);

namespace App\Presenters;

use App\Model\GameService;
use App\Model\GameStateFormatter;
use App\Model\PlayerService;
use App\Model\QuestionService;
use Nette\Application\Responses\JsonResponse;
use RuntimeException;

final class ApiPresenter extends BasePresenter
{
    public function __construct(
        private readonly GameService $gameService,
        private readonly PlayerService $playerService,
        private readonly GameStateFormatter $formatter,
        private readonly QuestionService $questionService,
    ) {
    }

    public function actionGameState(string $code): void
    {
        $game = $this->gameService->getGameByCode($code);
        if ($game === null) {
            $this->sendError('Hra nebyla nalezena.', 404);
        }

        $this->sendPayload($this->formatter->buildHostState($game));
    }

    public function actionStartGame(string $code): void
    {
        $this->requirePost();
        try {
            $game = $this->gameService->startGame($code);
            $this->sendPayload(['status' => 'ok', 'game' => $this->formatter->buildHostState($game)]);
        } catch (RuntimeException $e) {
            $this->sendError($e->getMessage());
        }
    }

    public function actionNextQuestion(string $code): void
    {
        $this->requirePost();
        try {
            $game = $this->gameService->startNextQuestion($code);
            $this->sendPayload(['status' => 'ok', 'game' => $this->formatter->buildHostState($game)]);
        } catch (RuntimeException $e) {
            $this->sendError($e->getMessage());
        }
    }

    public function actionRevealAnswer(string $code): void
    {
        $this->requirePost();
        try {
            $this->gameService->revealAnswer($code);
            $game = $this->gameService->getGameByCode($code);
            $this->sendPayload(['status' => 'ok', 'game' => $game !== null ? $this->formatter->buildHostState($game) : null]);
        } catch (RuntimeException $e) {
            $this->sendError($e->getMessage());
        }
    }

    public function actionFinishGame(string $code): void
    {
        $this->requirePost();
        try {
            $game = $this->gameService->finishGame($code);
            $this->sendPayload(['status' => 'ok', 'game' => $this->formatter->buildHostState($game)]);
        } catch (RuntimeException $e) {
            $this->sendError($e->getMessage());
        }
    }

    public function actionRegisterPlayer(string $code): void
    {
        $this->requirePost();
        $game = $this->gameService->getGameByCode($code);
        if ($game === null) {
            $this->sendError('Hra nebyla nalezena.', 404);
        }
        if ($game['status'] !== 'lobby') {
            $this->sendError('Registrace již není povolena.', 409);
        }

        $name = trim((string) $this->getHttpRequest()->getPost('name'));
        if ($name === '') {
            $this->sendError('Zadejte prosím jméno.');
        }

        try {
            $player = $this->playerService->registerPlayer((int) $game['id'], $name);
            $this->sendPayload([
                'status' => 'ok',
                'player' => [
                    'token' => $player['token'],
                    'name' => $player['name'],
                ],
            ]);
        } catch (RuntimeException $e) {
            $this->sendError($e->getMessage());
        }
    }

    public function actionPlayerState(string $code, string $token): void
    {
        $game = $this->gameService->getGameByCode($code);
        if ($game === null) {
            $this->sendError('Hra nebyla nalezena.', 404);
        }
        $player = $this->playerService->findByToken($token);
        if ($player === null) {
            $this->sendError('Hráč nebyl nalezen.', 404);
        }
        if (!$player['is_active']) {
            $this->sendError('Hráč je odhlášen.', 410);
        }

        $this->sendPayload($this->formatter->buildPlayerState($game, $player));
    }

    public function actionSubmitAnswer(string $code, string $token): void
    {
        $this->requirePost();
        $game = $this->gameService->getGameByCode($code);
        if ($game === null) {
            $this->sendError('Hra nebyla nalezena.', 404);
        }
        $player = $this->playerService->findByToken($token);
        if ($player === null) {
            $this->sendError('Hráč nebyl nalezen.', 404);
        }
        if (!$player['is_active']) {
            $this->sendError('Hráč je odhlášen.', 410);
        }

        $playerState = $this->formatter->buildPlayerState($game, $player);
        $question = $playerState['question'];
        if ($question === null) {
            $this->sendError('Žádná aktivní otázka.', 409);
        }

        $selectedIndex = $this->getHttpRequest()->getPost('option');
        if ($selectedIndex === null || !is_numeric($selectedIndex)) {
            $this->sendError('Neplatná odpověď.');
        }

        $index = (int) $selectedIndex;
        if ($index < 0 || $index >= count($question['options'])) {
            $this->sendError('Odpověď je mimo rozsah.');
        }

        $questionRow = $this->questionService->getActiveQuestion((int) $game['id']);
        if ($questionRow === null) {
            $this->sendError('Otázku se nepodařilo načíst.', 409);
        }

        $this->playerService->recordResponse((int) $questionRow['id'], (int) $player['id'], $index);
        $this->sendPayload(['status' => 'ok']);
    }

    public function actionLeaveGame(string $code, string $token): void
    {
        $this->requirePost();
        $this->playerService->leaveGame($token);
        $this->sendPayload(['status' => 'ok']);
    }

    private function requirePost(): void
    {
        if ($this->getHttpRequest()->getMethod() !== 'POST') {
            $this->sendError('Očekáváme POST požadavek.', 405);
        }
    }

    private function sendError(string $message, int $code = 400): void
    {
        $this->sendPayload(['status' => 'error', 'message' => $message], $code);
    }

    private function sendPayload(array $data, int $code = 200): void
    {
        $response = new JsonResponse($data);
        $response->setCode($code);
        $this->sendResponse($response);
    }
}
