(function () {
    const body = document.body;
    const toggle = document.querySelector('[data-sidebar-toggle]');
    const closeTargets = document.querySelectorAll('[data-sidebar-close]');

    if (toggle) {
        toggle.addEventListener('click', () => {
            body.classList.toggle('sidebar-pinned');
        });
    }

    closeTargets.forEach((target) => {
        target.addEventListener('click', () => body.classList.remove('sidebar-pinned'));
    });

    document.querySelectorAll('.shellfix-sidebar .nav-link').forEach((link) => {
        link.addEventListener('click', () => {
            if (window.matchMedia('(max-width: 991.98px)').matches) {
                body.classList.remove('sidebar-pinned');
            }
        });
    });

    const scenarioPanel = document.querySelector('[data-scenarios-panel]');
    if (scenarioPanel && scenarioPanel.dataset.loadUrl) {
        scenarioPanel.classList.add('is-loading');
        fetch(scenarioPanel.dataset.loadUrl, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
            .then((response) => response.text())
            .then((html) => {
                const doc = new DOMParser().parseFromString(html, 'text/html');
                const updatedPanel = doc.querySelector('[data-scenarios-panel]');
                if (updatedPanel) {
                    scenarioPanel.innerHTML = updatedPanel.innerHTML;
                }
            })
            .catch(() => {})
            .finally(() => scenarioPanel.classList.remove('is-loading'));
    }

    const answerForm = document.querySelector('[data-answer-form]');
    if (answerForm) {
        answerForm.addEventListener('submit', (event) => {
            event.preventDefault();

            const submitButton = answerForm.querySelector('[type="submit"]');
            const resultTarget = document.querySelector('[data-answer-result]');
            const formData = new FormData(answerForm);

            if (submitButton) {
                submitButton.disabled = true;
                submitButton.dataset.originalText = submitButton.textContent;
                submitButton.textContent = 'Checking...';
            }

            fetch(answerForm.action, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'text/html'
                }
            })
                .then((response) => response.text())
                .then((html) => {
                    const doc = new DOMParser().parseFromString(html, 'text/html');
                    const freshResult = doc.querySelector('[data-answer-result]');
                    const freshErrors = doc.querySelector('[data-answer-errors]');

                    if (freshResult && resultTarget) {
                        resultTarget.innerHTML = freshResult.innerHTML;
                        resultTarget.classList.add('answer-flash');
                        setTimeout(() => resultTarget.classList.remove('answer-flash'), 700);
                    } else if (freshErrors && resultTarget) {
                        resultTarget.innerHTML = freshErrors.innerHTML;
                    } else {
                        window.location.reload();
                    }
                })
                .catch(() => answerForm.submit())
                .finally(() => {
                    if (submitButton) {
                        submitButton.disabled = false;
                        submitButton.textContent = submitButton.dataset.originalText || 'Submit';
                    }
                });
        });
    }
})();
