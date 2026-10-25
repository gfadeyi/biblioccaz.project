function initCaptchaPuzzle(racine) {
    const grid = document.getElementById('captchaGrid');
    const triggerBtn = document.getElementById('captchaTriggerBtn');
    const tokenInput = document.getElementById('captchaToken');
    const message = document.getElementById('captchaMessage');
    const modalEl = document.getElementById('captchaModal');

    let ordre = [];
    let selectedTile = null;
    let isVerified = false;

    function afficherMessage(texte, classe) {
        message.textContent = texte;
        message.className = 'mt-3 small fw-bold mb-0 ' + classe;
    }

    function initPuzzle() {
        isVerified = false;
        selectedTile = null;
        grid.innerHTML = '';
        message.textContent = '';
        tokenInput.value = '';

        fetch(racine + 'get_captcha_image.php', { cache: 'no-store' })
            .then(response => response.json())
            .then(data => {
                ordre = data.pieces || [];
                renderGrid();
            })
            .catch(() => afficherMessage('Impossible de charger le puzzle, réessayez.', 'text-danger'));
    }

    function renderGrid() {
        grid.innerHTML = '';
        ordre.forEach((id, position) => {
            const box = document.createElement('div');
            box.className = 'puzzle-tile';
            box.dataset.position = position;
            box.style.backgroundImage = 'url(' + racine + 'captcha_piece.php?id=' + encodeURIComponent(id) + ')';
            box.style.backgroundSize = '100% 100%';

            box.addEventListener('click', function () {
                if (isVerified) return;

                if (!selectedTile) {
                    selectedTile = box;
                    box.classList.add('selected');
                } else if (selectedTile === box) {
                    selectedTile.classList.remove('selected');
                    selectedTile = null;
                } else {
                    const p1 = parseInt(selectedTile.dataset.position);
                    const p2 = parseInt(box.dataset.position);
                    [ordre[p1], ordre[p2]] = [ordre[p2], ordre[p1]];
                    selectedTile = null;
                    renderGrid();
                    verifier();
                }
            });

            grid.appendChild(box);
        });
    }

    function verifier() {
        const body = new URLSearchParams({ ordre: ordre.join(',') });
        fetch(racine + 'captcha_verifier.php', { method: 'POST', body: body })
            .then(response => response.json())
            .then(data => {
                if (data.ok) {
                    reussite();
                } else if (data.expire) {
                    afficherMessage("Trop d'essais, nouveau puzzle.", 'text-danger');
                    setTimeout(initPuzzle, 800);
                }
            });
    }

    function reussite() {
        isVerified = true;
        tokenInput.value = 'ok';

        triggerBtn.innerHTML = '✅ Vérification réussie';
        triggerBtn.className = 'btn btn-success w-100';
        afficherMessage('✅ Parfait ! Image reconstituée.', 'text-success');

        setTimeout(() => {
            const modal = window.bootstrap && bootstrap.Modal.getInstance(modalEl);
            if (modal) modal.hide();
        }, 600);
    }

    tokenInput.form.addEventListener('submit', function (e) {
        if (!tokenInput.value) {
            e.preventDefault();
            alert("Veuillez d'abord réussir le puzzle de sécurité.");
            if (window.bootstrap) bootstrap.Modal.getOrCreateInstance(modalEl).show();
        }
    });

    modalEl.addEventListener('shown.bs.modal', initPuzzle);
}
