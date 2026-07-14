<?php

namespace App\Services\Nida;

use App\Contracts\NidaClientInterface;
use App\Exceptions\NidaException;
use App\Rules\TanzanianNin;
use App\Services\Nida\Data\NidaIdentity;
use App\Services\Nida\Data\NidaQuestionTurn;
use App\Support\IdentityNormalizer;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class NidaService
{
    private const VERIFIED_PREFIX = 'nida:verified:';

    public function __construct(private NidaClientInterface $client) {}

    public function enabled(): bool
    {
        return (bool) config('services.nida.enabled', false);
    }

    public function startVerification(string $nin): NidaQuestionTurn
    {
        $this->assertEnabled();

        return $this->client->startVerification($this->validatedNin($nin));
    }

    public function answerQuestion(string $nin, string $sessionId, string $rqCode, string $answer): NidaQuestionTurn|NidaIdentity
    {
        $this->assertEnabled();

        $result = $this->client->answerQuestion(
            $this->validatedNin($nin),
            $sessionId,
            $rqCode,
            $answer,
        );

        if ($result instanceof NidaIdentity) {
            if (strcasecmp($result->sex, 'Female') !== 0) {
                throw NidaException::sexNotEligible($result->sex);
            }

            Cache::put(
                self::VERIFIED_PREFIX.$result->nin,
                $result,
                (int) config('services.nida.verified_ttl', 600),
            );
        }

        return $result;
    }

    public function pullVerified(string $nin): ?NidaIdentity
    {
        $cached = Cache::get(self::VERIFIED_PREFIX.IdentityNormalizer::normalizeNin($nin));

        return $cached instanceof NidaIdentity ? $cached : null;
    }

    public function forgetVerified(string $nin): void
    {
        Cache::forget(self::VERIFIED_PREFIX.IdentityNormalizer::normalizeNin($nin));
    }

    private function assertEnabled(): void
    {
        if (! $this->enabled()) {
            throw NidaException::disabled();
        }
    }

    private function validatedNin(string $nin): string
    {
        $nin = IdentityNormalizer::normalizeNin($nin);

        $validator = Validator::make(
            ['nin' => $nin],
            ['nin' => ['required', 'string', new TanzanianNin]],
        );

        if ($validator->fails()) {
            throw ValidationException::withMessages($validator->errors()->toArray());
        }

        return $nin;
    }
}
