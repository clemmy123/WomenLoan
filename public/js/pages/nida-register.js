/**
 * NIDA RQVerification wizard (register + shared fetch helpers).
 */
function nidaCsrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.content
        || document.querySelector('input[name="_token"]')?.value
        || '';
}

function nidaPhotoSrc(identity) {
    if (! identity?.photo_base64) {
        return null;
    }

    const raw = identity.photo_base64;
    if (raw.startsWith('data:')) {
        return raw;
    }

    const isSvg = raw.startsWith('PHN2Zy') || raw.startsWith('PD94');
    return isSvg
        ? `data:image/svg+xml;base64,${raw}`
        : `data:image/jpeg;base64,${raw}`;
}

function createNidaVerificationState(config = {}) {
    return {
        step: 'nin',
        nin: config.oldNin || '',
        loading: false,
        error: '',
        sessionId: '',
        rqCode: '',
        question: '',
        correctCount: 0,
        requiredCorrect: 2,
        answer: '',
        identity: null,
        routes: {
            start: config.startUrl,
            answer: config.answerUrl,
        },
        labels: config.labels || {},
        afterVerifiedStep: config.afterVerifiedStep || 'preview',

        nidaUrl(value) {
            return typeof value === 'string' && value !== '' && value !== 'undefined'
                ? value
                : '';
        },

        photoSrc() {
            return nidaPhotoSrc(this.identity);
        },

        async startNin() {
            this.error = '';
            this.loading = true;
            this.syncUi?.();

            try {
                const startUrl = this.nidaUrl(this.routes.start);
                if (! startUrl) {
                    this.error = 'Verification is not available. Refresh the page and try again.';
                    return;
                }

                const res = await fetch(startUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        Accept: 'application/json',
                        'X-CSRF-TOKEN': nidaCsrfToken(),
                    },
                    body: JSON.stringify({ nin: this.nin }),
                });

                const payload = await res.json();

                if (! res.ok) {
                    this.error = payload.message
                        || payload.errors?.nin?.[0]
                        || 'Verification failed';
                    return;
                }

                this.applyTurn(payload.data);
                this.step = 'question';
                this.answer = '';
            } catch {
                this.error = 'Network error. Please try again.';
            } finally {
                this.loading = false;
                this.syncUi?.();
            }
        },

        async submitAnswer() {
            this.error = '';
            this.loading = true;
            this.syncUi?.();

            try {
                const answerUrl = this.nidaUrl(this.routes.answer);
                if (! answerUrl) {
                    this.error = 'Verification is not available. Refresh the page and try again.';
                    return;
                }

                const res = await fetch(answerUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        Accept: 'application/json',
                        'X-CSRF-TOKEN': nidaCsrfToken(),
                    },
                    body: JSON.stringify({
                        nin: this.nin,
                        session_id: this.sessionId,
                        rq_code: this.rqCode,
                        answer: this.answer,
                    }),
                });

                const payload = await res.json();

                if (! res.ok) {
                    this.error = payload.message || 'Verification failed';
                    return;
                }

                const data = payload.data;

                if (data.completed) {
                    this.identity = data;
                    this.step = this.afterVerifiedStep;
                    this.answer = '';
                    this.onVerified?.(data);
                    return;
                }

                this.applyTurn(data);
                this.answer = '';

                if (data.previous_answer_code === 124) {
                    this.error = this.labels.wrongAnswer || 'Incorrect answer. Try again.';
                }
            } catch {
                this.error = 'Network error. Please try again.';
            } finally {
                this.loading = false;
                this.syncUi?.();
            }
        },

        applyTurn(data) {
            this.sessionId = data.session_id;
            this.rqCode = data.rq_code;
            this.question = data.question;
            this.correctCount = data.correct_count;
            this.requiredCorrect = data.required_correct;
            this.nin = data.nin || this.nin;
        },

        continueToAccount() {
            this.step = 'account';
            this.syncUi?.();
        },
    };
}

function initNidaRegisterWizard(root) {
    if (! root || root.dataset.bound === 'true') {
        return;
    }

    let config = {};
    try {
        config = JSON.parse(root.dataset.nidaConfig || '{}');
    } catch {
        config = {};
    }

    const labels = {
        loading: root.dataset.labelLoading || 'Loading…',
        continueNin: root.dataset.labelContinueNin || 'Continue',
        submitAnswer: root.dataset.labelSubmitAnswer || 'Submit',
        wrongAnswer: config.labels?.wrongAnswer || '',
    };

    const state = createNidaVerificationState({
        ...config,
        afterVerifiedStep: 'preview',
    });

    const ninInput = root.querySelector('[data-nida-nin]');
    const answerInput = root.querySelector('[data-nida-answer]');
    const errorEl = root.querySelector('[data-nida-error]');
    const btnStart = root.querySelector('[data-nida-start]');
    const btnAnswer = root.querySelector('[data-nida-submit-answer]');
    const btnContinue = root.querySelector('[data-nida-continue-account]');
    const btnStartLabel = btnStart?.querySelector('[data-nida-btn-label]');
    const btnAnswerLabel = btnAnswer?.querySelector('[data-nida-btn-label]');
    const panels = root.querySelectorAll('[data-nida-panel]');
    const identityCard = root.querySelector('[data-nida-identity]');
    const photoImg = root.querySelector('[data-nida-photo]');

    const identityFields = {
        first_name: root.querySelector('[data-nida-field="first_name"]'),
        middle_name: root.querySelector('[data-nida-field="middle_name"]'),
        last_name: root.querySelector('[data-nida-field="last_name"]'),
        sex: root.querySelector('[data-nida-field="sex"]'),
        dob: root.querySelector('[data-nida-field="dob"]'),
        age: root.querySelector('[data-nida-field="age"]'),
        nin: root.querySelector('[data-nida-field="nin"]'),
        nationality: root.querySelector('[data-nida-field="nationality"]'),
    };

    const hiddenFields = {
        nin: root.querySelector('[data-nida-hidden="nin"]'),
        first_name: root.querySelector('[data-nida-hidden="first_name"]'),
        middle_name: root.querySelector('[data-nida-hidden="middle_name"]'),
        last_name: root.querySelector('[data-nida-hidden="last_name"]'),
    };

    const rqCodeEl = root.querySelector('[data-nida-rq-code]');
    const questionEl = root.querySelector('[data-nida-question]');

    const syncPanels = () => {
        panels.forEach((panel) => {
            const name = panel.dataset.nidaPanel;
            const visible = state.step === name;
            panel.hidden = ! visible;
        });

        root.dataset.step = state.step;
        root.setAttribute('data-ready', '1');
        document.body.classList.toggle('is-preview-step', state.step === 'preview');
    };

    const syncIdentity = () => {
        const id = state.identity;
        if (! id) {
            identityCard?.setAttribute('hidden', '');
            return;
        }

        identityCard?.removeAttribute('hidden');

        Object.entries(identityFields).forEach(([key, el]) => {
            if (! el) {
                return;
            }

            const val = id[key];
            el.textContent = val === null || val === undefined || val === ''
                ? (key === 'middle_name' ? '—' : '')
                : String(val);
        });

        const src = state.photoSrc();
        if (photoImg) {
            if (src) {
                photoImg.src = src;
                photoImg.removeAttribute('hidden');
            } else {
                photoImg.removeAttribute('src');
                photoImg.setAttribute('hidden', '');
            }
        }

        if (hiddenFields.nin) {
            hiddenFields.nin.value = id.nin || '';
        }
        if (hiddenFields.first_name) {
            hiddenFields.first_name.value = id.first_name || '';
        }
        if (hiddenFields.middle_name) {
            hiddenFields.middle_name.value = id.middle_name || '';
        }
        if (hiddenFields.last_name) {
            hiddenFields.last_name.value = id.last_name || '';
        }
    };

    state.syncUi = () => {
        if (errorEl) {
            if (state.error) {
                errorEl.textContent = state.error;
                errorEl.removeAttribute('hidden');
            } else {
                errorEl.textContent = '';
                errorEl.setAttribute('hidden', '');
            }
        }

        if (ninInput && ninInput.value !== state.nin) {
            ninInput.value = state.nin;
        }

        if (answerInput && answerInput.value !== state.answer) {
            answerInput.value = state.answer;
        }

        if (rqCodeEl) {
            rqCodeEl.textContent = state.rqCode || '';
        }

        if (questionEl) {
            questionEl.textContent = state.question || '';
        }

        const ninDigits = String(state.nin || '').replace(/\D/g, '');
        if (btnStart) {
            btnStart.disabled = state.loading || ninDigits.length < 20;
        }

        if (btnAnswer) {
            btnAnswer.disabled = state.loading || ! String(state.answer || '').trim();
        }

        if (btnStartLabel) {
            btnStartLabel.textContent = state.loading ? labels.loading : labels.continueNin;
        }

        if (btnAnswerLabel) {
            btnAnswerLabel.textContent = state.loading ? labels.loading : labels.submitAnswer;
        }

        syncIdentity();
        syncPanels();
    };

    ninInput?.addEventListener('input', () => {
        state.nin = ninInput.value;
        state.syncUi();
    });

    answerInput?.addEventListener('input', () => {
        state.answer = answerInput.value;
        state.syncUi();
    });

    ninInput?.addEventListener('keydown', (event) => {
        if (event.key === 'Enter') {
            event.preventDefault();
            state.startNin();
        }
    });

    answerInput?.addEventListener('keydown', (event) => {
        if (event.key === 'Enter') {
            event.preventDefault();
            state.submitAnswer();
        }
    });

    btnStart?.addEventListener('click', () => state.startNin());
    btnAnswer?.addEventListener('click', () => state.submitAnswer());
    btnContinue?.addEventListener('click', () => state.continueToAccount());

    root.dataset.bound = 'true';
    state.syncUi();
}

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-nida-register-wizard]').forEach(initNidaRegisterWizard);
});

window.nidaPhotoSrc = nidaPhotoSrc;
window.createNidaVerificationState = createNidaVerificationState;
