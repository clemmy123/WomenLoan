<?php

namespace App\Services\Nida;

use App\Contracts\NidaClientInterface;
use App\Exceptions\NidaException;
use App\Services\Nida\Data\NidaIdentity;
use App\Services\Nida\Data\NidaQuestionTurn;
use App\Support\IdentityNormalizer;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * Local RQVerification stub (demographic questions) until live CIG credentials exist.
 *
 * Demo answers (case-insensitive):
 * - RQ001 mother maiden  → Asha
 * - RQ002 birth region   → Dodoma
 */
class FakeNidaClient implements NidaClientInterface
{
    private const CACHE_PREFIX = 'nida:fake:rq:';

    private const MAX_ATTEMPTS = 5;

    /** @var list<array{code: string, text: string, answer: string}> */
    private const QUESTIONS = [
        [
            'code' => 'RQ001',
            'text' => 'What is your mother\'s maiden name?',
            'answer' => 'asha',
        ],
        [
            'code' => 'RQ002',
            'text' => 'In which region were you born?',
            'answer' => 'dodoma',
        ],
    ];

    public function startVerification(string $nin): NidaQuestionTurn
    {
        $nin = IdentityNormalizer::normalizeNin($nin);

        if (! preg_match('/^\d{20}$/', $nin)) {
            throw NidaException::ninNotFound();
        }

        $sessionId = (string) Str::uuid();
        $question = self::QUESTIONS[0];
        $ttl = (int) config('services.nida.challenge_ttl', 300);

        Cache::put(self::CACHE_PREFIX.$sessionId, [
            'nin' => $nin,
            'index' => 0,
            'correct' => 0,
            'attempts' => 0,
            'current_code' => $question['code'],
        ], $ttl);

        return new NidaQuestionTurn(
            sessionId: $sessionId,
            nin: $nin,
            rqCode: $question['code'],
            question: $question['text'],
            correctCount: 0,
            requiredCorrect: 2,
            previousAnswerCode: null,
            statusCode: '120',
        );
    }

    public function answerQuestion(string $nin, string $sessionId, string $rqCode, string $answer): NidaQuestionTurn|NidaIdentity
    {
        $nin = IdentityNormalizer::normalizeNin($nin);
        $state = Cache::get(self::CACHE_PREFIX.$sessionId);

        if (! is_array($state) || ($state['nin'] ?? null) !== $nin) {
            throw NidaException::sessionExpired();
        }

        if (($state['current_code'] ?? null) !== $rqCode) {
            throw NidaException::challengeFailed();
        }

        $index = (int) ($state['index'] ?? 0);
        $question = self::QUESTIONS[$index] ?? null;

        if ($question === null) {
            throw NidaException::sessionExpired();
        }

        $state['attempts'] = (int) ($state['attempts'] ?? 0) + 1;

        if ($state['attempts'] > self::MAX_ATTEMPTS) {
            Cache::forget(self::CACHE_PREFIX.$sessionId);
            throw NidaException::attemptsExceeded();
        }

        $given = strtolower(trim($answer));
        $correct = $given === $question['answer'];

        if (! $correct) {
            Cache::put(self::CACHE_PREFIX.$sessionId, $state, (int) config('services.nida.challenge_ttl', 300));

            return new NidaQuestionTurn(
                sessionId: $sessionId,
                nin: $nin,
                rqCode: $question['code'],
                question: $question['text'],
                correctCount: (int) $state['correct'],
                requiredCorrect: 2,
                previousAnswerCode: 124,
                statusCode: '124',
            );
        }

        $state['correct'] = (int) $state['correct'] + 1;
        $state['index'] = $index + 1;

        if ($state['correct'] >= 2) {
            Cache::forget(self::CACHE_PREFIX.$sessionId);

            return $this->demoIdentity($nin);
        }

        $next = self::QUESTIONS[$state['index']];
        $state['current_code'] = $next['code'];
        Cache::put(self::CACHE_PREFIX.$sessionId, $state, (int) config('services.nida.challenge_ttl', 300));

        return new NidaQuestionTurn(
            sessionId: $sessionId,
            nin: $nin,
            rqCode: $next['code'],
            question: $next['text'],
            correctCount: (int) $state['correct'],
            requiredCorrect: 2,
            previousAnswerCode: 123,
            statusCode: '123',
        );
    }

    /** @var list<string> */
    private const FIRST_NAMES = [
        'Asha', 'Rehema', 'Fatuma', 'Zainabu', 'Halima', 'Mariamu', 'Saida', 'Neema',
        'Grace', 'Joyce', 'Amina', 'Zuhura', 'Mwanaidi', 'Lightness', 'Happy', 'Faith',
        'Pendo', 'Furaha', 'Tunu', 'Baraka', 'Subira', 'Tatu', 'Mwajuma', 'Hadija',
    ];

    /** @var list<string> */
    private const MIDDLE_NAMES = [
        'Juma', 'Hassan', 'Ally', 'Omari', 'Bakari', 'Said', 'Rashid', 'Hamisi',
        'Mohamed', 'Iddi', 'Ramadhani', 'Selemani', 'Abdallah', 'Mussa', 'Shaaban', 'Kassim',
    ];

    /** @var list<string> */
    private const LAST_NAMES = [
        'Mwangi', 'Kimaro', 'Msangi', 'Mwakasege', 'Ngowi', 'Mushi', 'Swai', 'Mosha',
        'Lyimo', 'Mrema', 'Kyando', 'Mbwambo', 'Shirima', 'Mtenga', 'Komba', 'Ndunguru',
        'Mollel', 'Laizer', 'Sulle', 'Meena', 'Chacha', 'Maganga', 'Lugoe', 'Mkude',
    ];

    private function demoIdentity(string $nin): NidaIdentity
    {
        $year = (int) substr($nin, 0, 4);
        $month = max(1, min(12, (int) substr($nin, 4, 2) ?: 1));
        $day = max(1, min(28, (int) substr($nin, 6, 2) ?: 1));
        $dob = Carbon::createSafe($year, $month, $day) ?? Carbon::parse('1990-01-15');
        $names = $this->demoNamesForNin($nin);

        return new NidaIdentity(
            nin: $nin,
            firstName: $names['first_name'],
            middleName: $names['middle_name'],
            lastName: $names['last_name'],
            sex: 'Female',
            dateOfBirth: $dob->startOfDay(),
            nationality: 'Tanzanian',
            photoBase64: $this->demoPortraitBase64(),
            otherName: null,
        );
    }

    /**
     * Pick a stable female name trio from the NIN (same NIN → same names; different NIN → different mix).
     *
     * @return array{first_name: string, middle_name: string, last_name: string}
     */
    private function demoNamesForNin(string $nin): array
    {
        $seed = (int) sprintf('%u', crc32($nin));

        return [
            'first_name' => self::FIRST_NAMES[$seed % count(self::FIRST_NAMES)],
            'middle_name' => self::MIDDLE_NAMES[intdiv($seed, 7) % count(self::MIDDLE_NAMES)],
            'last_name' => self::LAST_NAMES[intdiv($seed, 13) % count(self::LAST_NAMES)],
        ];
    }

    /**
     * SVG portrait placeholder (Base64) — live CIG returns JPEG Base64 instead.
     */
    private function demoPortraitBase64(): string
    {
        $svg = <<<'SVG'
<svg xmlns="http://www.w3.org/2000/svg" width="160" height="200" viewBox="0 0 160 200">
  <rect width="160" height="200" fill="#eef2ff"/>
  <circle cx="80" cy="72" r="36" fill="#4f46e5"/>
  <ellipse cx="80" cy="168" rx="58" ry="48" fill="#4f46e5"/>
</svg>
SVG;

        return base64_encode($svg);
    }
}
