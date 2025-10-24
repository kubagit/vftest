(function () {
    const controller = document.querySelector('.panel.controller');
    if (!controller) {
        return;
    }

    const basePath = window.APP_BASE_PATH || '';
    const gameCode = controller.dataset.gameCode;
    const playerToken = controller.dataset.playerToken;
    const stateUrl = `${basePath}/api/game/${gameCode}/players/${playerToken}/state`;
    const answerUrl = `${basePath}/api/game/${gameCode}/players/${playerToken}/answer`;
    const leaveUrl = `${basePath}/api/game/${gameCode}/players/${playerToken}/leave`;

    const statusMessage = controller.querySelector('.status-message');
    const timerValue = controller.querySelector('.timer-value');
    const questionBlock = controller.querySelector('.controller-question');
    const questionText = questionBlock.querySelector('.question-text');
    const optionsContainer = questionBlock.querySelector('.options');
    const leaveButton = controller.querySelector('[data-action="leave"]');

    let currentState = null;
    let pollTimer = null;

    leaveButton.addEventListener('click', async () => {
        if (!confirm('Opravdu se chceš odhlásit z této hry?')) {
            return;
        }
        try {
            await fetch(leaveUrl, { method: 'POST', headers: { 'X-Requested-With': 'XMLHttpRequest' } });
        } catch (error) {
            console.error(error);
        }
        window.location.href = `${basePath}/odhlaseno`;
    });

    async function refreshState() {
        try {
            const response = await fetch(stateUrl, { cache: 'no-store' });
            if (!response.ok) {
                throw new Error('Nepodařilo se načíst stav hráče.');
            }
            const state = await response.json();
            currentState = state;
            updateView(state);
        } catch (error) {
            console.error(error);
        }
    }

    function updateView(state) {
        if (!state.player.isActive) {
            window.location.href = `${basePath}/odhlaseno`;
            return;
        }
        statusMessage.textContent = buildStatusText(state.status, state.questionCurrent, state.questionTotal);
        updateTimer(state);

        if (!state.question) {
            questionBlock.classList.add('hidden');
            optionsContainer.innerHTML = '';
            return;
        }

        questionBlock.classList.remove('hidden');
        questionText.textContent = state.question.text;
        optionsContainer.innerHTML = '';
        state.question.options.forEach((option, index) => {
            const button = document.createElement('button');
            button.className = 'btn';
            button.type = 'button';
            button.textContent = `${index + 1}. ${option}`;
            button.dataset.index = String(index);
            if (state.response && state.response.selectedIndex === index) {
                button.classList.add('selected');
            }
            if (state.status !== 'question' || (state.response && typeof state.response.selectedIndex === 'number')) {
                button.classList.add('disabled');
            }
            button.addEventListener('click', () => submitAnswer(index));
            optionsContainer.appendChild(button);
        });
    }

    function buildStatusText(status, current, total) {
        switch (status) {
            case 'lobby':
                return 'Čekáme na start hry…';
            case 'question':
                return `Otázka ${current} z ${total}`;
            case 'reveal':
                return 'Vyhodnocení otázky';
            case 'finished':
                return 'Hra skončila. Děkujeme!';
            default:
                return 'Spojení navázáno.';
        }
    }

    async function submitAnswer(index) {
        if (!currentState || currentState.status !== 'question') {
            return;
        }
        if (currentState.response && typeof currentState.response.selectedIndex === 'number') {
            return;
        }
        try {
            const formData = new FormData();
            formData.append('option', String(index));
            const response = await fetch(answerUrl, {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
            });
            const data = await response.json();
            if (data.status === 'error') {
                alert(data.message || 'Odpověď se nepodařilo uložit.');
                return;
            }
            await refreshState();
        } catch (error) {
            console.error(error);
            alert('Odpověď se nepodařilo uložit.');
        }
    }

    function updateTimer(state) {
        if (!state.questionStartedAt) {
            timerValue.textContent = '--';
            return;
        }
        const start = new Date(state.questionStartedAt).getTime();
        const now = Date.now();
        const diff = Math.floor((now - start) / 1000);
        const remaining = Math.max(state.countdownSeconds - diff, 0);
        timerValue.textContent = `${remaining}s`;
    }

    async function startPolling() {
        await refreshState();
        pollTimer = setInterval(refreshState, 2000);
    }

    startPolling();
})();
