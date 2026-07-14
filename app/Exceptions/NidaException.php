<?php

namespace App\Exceptions;

use RuntimeException;

class NidaException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly ?string $statusCode = null,
    ) {
        parent::__construct($message);
    }

    public static function disabled(): self
    {
        return new self(__('nida.disabled'), 'disabled');
    }

    public static function notConfigured(): self
    {
        return new self(__('nida.not_configured'), 'not_configured');
    }

    public static function ninNotFound(): self
    {
        return new self(__('nida.nin_not_found'), '102');
    }

    public static function challengeFailed(): self
    {
        return new self(__('nida.challenge_failed'), '124');
    }

    public static function attemptsExceeded(): self
    {
        return new self(__('nida.attempts_exceeded'), '122');
    }

    public static function sexNotEligible(string $sex): self
    {
        return new self(__('nida.sex_not_eligible', ['sex' => $sex]), 'sex');
    }

    public static function sessionExpired(): self
    {
        return new self(__('nida.session_expired'), 'session');
    }
}
