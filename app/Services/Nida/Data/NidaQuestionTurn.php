<?php

namespace App\Services\Nida\Data;

final readonly class NidaQuestionTurn
{
    public function __construct(
        public string $sessionId,
        public string $nin,
        public string $rqCode,
        public string $question,
        public int $correctCount,
        public int $requiredCorrect = 2,
        public ?int $previousAnswerCode = null,
        public string $statusCode = '120',
    ) {}

    /**
     * @return array{
     *     session_id: string,
     *     nin: string,
     *     rq_code: string,
     *     question: string,
     *     correct_count: int,
     *     required_correct: int,
     *     previous_answer_code: int|null,
     *     status_code: string,
     *     completed: false
     * }
     */
    public function toArray(): array
    {
        return [
            'session_id' => $this->sessionId,
            'nin' => $this->nin,
            'rq_code' => $this->rqCode,
            'question' => $this->question,
            'correct_count' => $this->correctCount,
            'required_correct' => $this->requiredCorrect,
            'previous_answer_code' => $this->previousAnswerCode,
            'status_code' => $this->statusCode,
            'completed' => false,
        ];
    }
}
