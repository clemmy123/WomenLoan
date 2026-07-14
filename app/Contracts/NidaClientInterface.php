<?php

namespace App\Contracts;

use App\Services\Nida\Data\NidaIdentity;
use App\Services\Nida\Data\NidaQuestionTurn;

interface NidaClientInterface
{
    /**
     * Step 1 — send NIN only; receive first demographic question (RQVerification).
     */
    public function startVerification(string $nin): NidaQuestionTurn;

    /**
     * Step 2+ — submit answer for current RQCode; next question or verified identity.
     */
    public function answerQuestion(string $nin, string $sessionId, string $rqCode, string $answer): NidaQuestionTurn|NidaIdentity;
}
