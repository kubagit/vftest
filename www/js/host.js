(function () {
    const section = document.querySelector('.panel.host');
    if (!section) {
        return;
    }

    const gameCode = section.dataset.gameCode;
    const basePath = window.APP_BASE_PATH || '';
    const stateUrl = `${basePath}/api/game/${gameCode}/state`;
    const registerUrl = `${basePath}/api/game/${gameCode}/players/register`;
    const startUrl = `${basePath}/api/game/${gameCode}/start`;
    const nextUrl = `${basePath}/api/game/${gameCode}/next`;
    const revealUrl = `${basePath}/api/game/${gameCode}/reveal`;
    const finishUrl = `${basePath}/api/game/${gameCode}/finish`;

    const registerForm = section.querySelector('.register-form');
    const playerList = section.querySelector('.player-list');
    const scoreboard = section.querySelector('.scoreboard tbody');
    const questionText = section.querySelector('.question-text');
    const optionsList = section.querySelector('.options');
    const questionMeta = section.querySelector('.question-meta');
    const timerValue = section.querySelector('.timer-value');

    let lastState = null;
    let pollTimer = null;

    const actions = {
        start: startUrl,
        next: nextUrl,
        reveal: revealUrl,
        finish: finishUrl,
    };

    registerForm.addEventListener('submit', async (event) => {
        event.preventDefault();
        const formData = new FormData(registerForm);
        const name = formData.get('name');
        if (!name) {
            return;
        }
        try {
            const response = await fetch(registerUrl, {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body: formData,
            });
            const data = await response.json();
            if (data.status !== 'ok') {
                alert(data.message || 'Registrace selhala.');
                return;
            }
            registerForm.reset();
            await refreshState();
        } catch (error) {
            console.error(error);
            alert('Registrace hráče se nezdařila.');
        }
    });

    section.querySelectorAll('[data-action]').forEach((button) => {
        button.addEventListener('click', async () => {
            const action = button.dataset.action;
            const url = actions[action];
            if (!url) {
                return;
            }
            try {
                const response = await fetch(url, {
                    method: 'POST',
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                });
                const data = await response.json();
                if (data.status === 'error') {
                    alert(data.message || 'Operace se nezdařila.');
                }
                await refreshState();
            } catch (error) {
                console.error(error);
                alert('Operace se nezdařila.');
            }
        });
    });

    async function refreshState() {
        try {
            const response = await fetch(stateUrl, { cache: 'no-store' });
            if (!response.ok) {
                throw new Error('Nepodařilo se načíst stav hry.');
            }
            const state = await response.json();
            updateView(state);
            lastState = state;
        } catch (error) {
            console.error(error);
        }
    }

    function updateView(state) {
        updatePlayers(state);
        updateScoreboard(state);
        updateQuestion(state);
        updateButtons(state);
    }

    function updatePlayers(state) {
        playerList.innerHTML = '';
        state.players.forEach((player) => {
            const li = document.createElement('li');
            li.innerHTML = `
                <div>
                    <strong>${escapeHtml(player.name)}</strong><br>
                    <small>Skóre: ${player.score}</small><br>
                    <small>${player.isActive ? 'Připojeno' : 'Odhlášen'}</small>
                </div>
                <div class="qr"></div>
            `;
            const qrContainer = li.querySelector('.qr');
            if (player.isActive) {
                const joinUrl = `${window.location.origin}${basePath}/ovladac/${player.token}`;
                const img = document.createElement('img');
                img.alt = `QR kód pro ${player.name}`;
                img.src = `https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=${encodeURIComponent(joinUrl)}`;
                const link = document.createElement('a');
                link.href = joinUrl;
                link.textContent = 'Otevřít ovladač';
                link.target = '_blank';
                qrContainer.appendChild(img);
                qrContainer.appendChild(document.createElement('br'));
                qrContainer.appendChild(link);
            } else {
                qrContainer.textContent = 'Token zneplatněn';
            }
            playerList.appendChild(li);
        });
    }

    function updateScoreboard(state) {
        scoreboard.innerHTML = '';
        state.players.forEach((player) => {
            const tr = document.createElement('tr');
            const answer = player.answered;
            let answerCell = '';
            if (answer) {
                const icon = answer.isCorrect ? '✅' : '❌';
                answerCell = `Varianta ${answer.selectedIndex + 1} ${icon}`;
            }
            tr.innerHTML = `
                <td>${escapeHtml(player.name)}</td>
                <td>${player.score}</td>
                <td>${answerCell}</td>
            `;
            scoreboard.appendChild(tr);
        });
    }

    function updateQuestion(state) {
        const question = state.question;
        if (!question) {
            questionText.textContent = 'Zatím žádná otázka.';
            optionsList.innerHTML = '';
            questionMeta.textContent = '';
            timerValue.textContent = '--';
            timerValue.classList.remove('active');
            return;
        }

        questionText.textContent = question.text;
        optionsList.innerHTML = '';
        question.options.forEach((option, index) => {
            const li = document.createElement('li');
            li.textContent = `${index + 1}. ${option}`;
            if (question.revealedAt) {
                if (index === question.correctIndex) {
                    li.classList.add('correct');
                }
            }
            optionsList.appendChild(li);
        });
        const sequenceInfo = `Otázka ${question.sequence} / ${state.questionTotal}`;
        const sourceInfo = question.sourceUrl ? `Zdroj: <a href="${question.sourceUrl}" target="_blank" rel="noopener">Wikipedie</a>` : '';
        questionMeta.innerHTML = sourceInfo ? `${sequenceInfo}<br>${sourceInfo}` : sequenceInfo;

        updateTimer(state);
    }

    function updateTimer(state) {
        if (!state.questionStartedAt) {
            timerValue.textContent = '--';
            timerValue.classList.remove('active');
            return;
        }
        const start = new Date(state.questionStartedAt).getTime();
        const now = Date.now();
        const diff = Math.floor((now - start) / 1000);
        const remaining = Math.max(state.countdownSeconds - diff, 0);
        timerValue.textContent = `${remaining}s`;
        if (remaining > 0 && state.status === 'question') {
            timerValue.classList.add('active');
        } else {
            timerValue.classList.remove('active');
        }
    }

    function escapeHtml(str) {
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function updateButtons(state) {
        const startBtn = section.querySelector('[data-action="start"]');
        const revealBtn = section.querySelector('[data-action="reveal"]');
        const nextBtn = section.querySelector('[data-action="next"]');
        const finishBtn = section.querySelector('[data-action="finish"]');

        startBtn.disabled = state.status !== 'lobby';
        revealBtn.disabled = state.status !== 'question';
        nextBtn.disabled = !(state.status === 'reveal' && state.questionCurrent < state.questionTotal);
        finishBtn.disabled = state.status === 'finished';
    }

    async function startPolling() {
        await refreshState();
        pollTimer = setInterval(async () => {
            await refreshState();
            if (lastState) {
                updateTimer(lastState);
            }
        }, 1000);
    }

    startPolling();
})();
