/**
 * Shared NIDA RQVerification fetch helpers + Alpine wizards.
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

function nidaVerificationMethods(config = {}) {
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

        photoSrc() {
            return nidaPhotoSrc(this.identity);
        },

        async startNin() {
            this.error = '';
            this.loading = true;

            try {
                const res = await fetch(this.routes.start, {
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
            }
        },

        async submitAnswer() {
            this.error = '';
            this.loading = true;

            try {
                const res = await fetch(this.routes.answer, {
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
                    this.step = this.afterVerifiedStep || 'preview';
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

        resetNida() {
            this.step = 'nin';
            this.sessionId = '';
            this.rqCode = '';
            this.question = '';
            this.correctCount = 0;
            this.answer = '';
            this.identity = null;
            this.error = '';
        },
    };
}

window.nidaRegisterWizard = function nidaRegisterWizard(config = {}) {
    return {
        ...nidaVerificationMethods(config),
        afterVerifiedStep: 'preview',

        continueToAccount() {
            this.step = 'account';
        },
    };
};

window.nidaApplicantWizard = function nidaApplicantWizard(config = {}) {
    return {
        ...nidaVerificationMethods(config),
        afterVerifiedStep: 'form',

        continueToForm() {
            this.step = 'form';
        },
    };
};
