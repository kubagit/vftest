<?php

declare(strict_types=1);

namespace App\Presenters;

use App\Model\GameService;
use App\Model\GameStateFormatter;
use Nette\Application\UI\Form;
use RuntimeException;

final class HostPresenter extends BasePresenter
{
    public function __construct(
        private readonly GameService $gameService,
        private readonly GameStateFormatter $formatter,
    ) {
    }

    protected function createComponentNewGameForm(): Form
    {
        $form = new Form();
        $form->addInteger('questionTotal', 'Počet otázek:')
            ->setDefaultValue(10)
            ->setRequired()
            ->addRule(Form::RANGE, 'Počet otázek musí být mezi 1 a 50.', [1, 50]);
        $form->addInteger('countdown', 'Čas na odpověď (s):')
            ->setDefaultValue(30)
            ->setRequired()
            ->addRule(Form::RANGE, 'Čas na odpověď musí být mezi 5 a 120 sekundami.', [5, 120]);
        $form->addSubmit('start', 'Vytvořit novou hru');
        $form->onSuccess[] = function (Form $form, array $values): void {
            $this->handleCreateGame($form, $values);
        };
        return $form;
    }

    private function handleCreateGame(Form $form, array $values): void
    {
        try {
            $game = $this->gameService->createGame((int) $values['questionTotal'], (int) $values['countdown']);
            $this->redirect('game', ['code' => $game['code']]);
        } catch (RuntimeException $e) {
            $form->addError($e->getMessage());
        }
    }

    public function renderGame(string $code): void
    {
        $game = $this->gameService->getGameByCode($code);
        if ($game === null) {
            $this->error('Hra nebyla nalezena.');
        }

        $this->template->game = $game;
        $this->template->state = $this->formatter->buildHostState($game);
        $this->template->code = $code;
    }
}
