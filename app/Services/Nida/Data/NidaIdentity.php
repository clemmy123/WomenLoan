<?php

namespace App\Services\Nida\Data;

use App\Support\AgeCalculator;
use Carbon\Carbon;
use Carbon\CarbonInterface;

final readonly class NidaIdentity
{
    public function __construct(
        public string $nin,
        public string $firstName,
        public ?string $middleName,
        public string $lastName,
        public string $sex,
        public CarbonInterface $dateOfBirth,
        public string $nationality,
        public ?string $photoBase64 = null,
        public ?string $otherName = null,
    ) {}

    public function age(?CarbonInterface $asOf = null): ?int
    {
        return AgeCalculator::years($this->dateOfBirth, $asOf);
    }

    public function fullName(): string
    {
        return trim(implode(' ', array_filter([
            $this->firstName,
            $this->middleName,
            $this->lastName,
        ])));
    }

    /**
     * @return array{
     *     nin: string,
     *     first_name: string,
     *     middle_name: string|null,
     *     last_name: string,
     *     full_name: string,
     *     sex: string,
     *     dob: string,
     *     age: int|null,
     *     nationality: string,
     *     photo_base64: string|null,
     *     other_name: string|null,
     *     completed: true
     * }
     */
    public function toArray(): array
    {
        return [
            'nin' => $this->nin,
            'first_name' => $this->firstName,
            'middle_name' => $this->middleName,
            'last_name' => $this->lastName,
            'full_name' => $this->fullName(),
            'sex' => $this->sex,
            'dob' => $this->dateOfBirth->format('Y-m-d'),
            'age' => $this->age(),
            'nationality' => $this->nationality,
            'photo_base64' => $this->photoBase64,
            'other_name' => $this->otherName,
            'completed' => true,
        ];
    }

    /**
     * @return array{
     *     nin: string,
     *     first_name: string,
     *     middle_name: string|null,
     *     last_name: string,
     *     sex: string,
     *     dob: string,
     *     nationality: string,
     *     nida_verified: bool,
     *     nida_verified_at: string
     * }
     */
    public function toApplicantAttributes(): array
    {
        return [
            'nin' => $this->nin,
            'first_name' => $this->firstName,
            'middle_name' => $this->middleName,
            'last_name' => $this->lastName,
            'sex' => $this->sex,
            'dob' => $this->dateOfBirth->format('Y-m-d'),
            'nationality' => $this->nationality,
            'nida_verified' => true,
            'nida_verified_at' => Carbon::now()->toDateTimeString(),
        ];
    }
}
