<?php

namespace App\Services\Nida;

use App\Contracts\NidaClientInterface;
use App\Exceptions\NidaException;
use App\Services\Nida\Data\NidaIdentity;
use App\Services\Nida\Data\NidaQuestionTurn;

/**
 * Live CIG client placeholder — wire AES/RSA/SOAP after workshop credentials.
 */
class HttpNidaClient implements NidaClientInterface
{
    public function startVerification(string $nin): NidaQuestionTurn
    {
        throw NidaException::notConfigured();
    }

    public function answerQuestion(string $nin, string $sessionId, string $rqCode, string $answer): NidaQuestionTurn|NidaIdentity
    {
        throw NidaException::notConfigured();
    }
}
