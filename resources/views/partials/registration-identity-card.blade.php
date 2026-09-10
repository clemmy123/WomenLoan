@php
    use App\Support\IdentityNormalizer;
    use Illuminate\Support\Facades\Storage;

    $photoPath = $applicant->photo_path ?? $user->nida_photo_path ?? null;
    $photoUrl = filled($photoPath) && Storage::disk('public')->exists($photoPath)
        ? \App\Support\SecureFileUrl::forPath($photoPath)
        : null;
    $dobValue = $applicant->dob
        ? \Illuminate\Support\Carbon::parse($applicant->dob)->format('Y-m-d')
        : (optional($user->dob)->format('Y-m-d') ?? '');
    $ageYears = $dobValue ? \App\Support\AgeCalculator::years(\Carbon\Carbon::parse($dobValue)) : null;
    $showNidaVerified = $lockNidaFields ?? false;
@endphp

<div class="nida-identity-card">
    <div class="nida-identity-header">
        <div class="nida-identity-photo-wrap">
            @if($photoUrl)
                <img src="{{ $photoUrl }}" alt="" class="nida-identity-photo" width="112" height="140">
            @else
                <div class="nida-identity-photo" aria-hidden="true"></div>
            @endif
        </div>
        @if($showNidaVerified)
            <span class="nida-verified-pill">{{ __('nida.verified_badge') }}</span>
        @endif
        <p class="nida-identity-fullname">{{ trim(implode(' ', array_filter([$applicant->first_name, $applicant->middle_name, $applicant->last_name]))) }}</p>
    </div>
    <dl class="nida-identity-grid">
        @if(filled($applicant->nin))
            <div class="nida-identity-field nida-identity-field--full">
                <dt>{{ __('applicants.nin') }}</dt>
                <dd class="nida-mono">{{ IdentityNormalizer::formatNin($applicant->nin) }}</dd>
            </div>
        @endif
        <div class="nida-identity-field">
            <dt>{{ __('applicants.first_name') }}</dt>
            <dd>{{ $applicant->first_name ?: __('common.na') }}</dd>
        </div>
        <div class="nida-identity-field">
            <dt>{{ __('applicants.middle_name') }}</dt>
            <dd>{{ $applicant->middle_name ?: __('common.na') }}</dd>
        </div>
        <div class="nida-identity-field">
            <dt>{{ __('applicants.last_name') }}</dt>
            <dd>{{ $applicant->last_name ?: __('common.na') }}</dd>
        </div>
        @if($dobValue)
            <div class="nida-identity-field">
                <dt>{{ __('applicants.dob') }}</dt>
                <dd>{{ $dobValue }}</dd>
            </div>
            <div class="nida-identity-field">
                <dt>{{ __('applicants.age') }}</dt>
                <dd>{{ $ageYears ?? __('common.na') }}</dd>
            </div>
        @endif
        <div class="nida-identity-field">
            <dt>{{ __('applicants.sex') }}</dt>
            <dd>
                @if($applicant->sex)
                    {{ $applicant->sex }}
                @elseif($user->sex)
                    {{ $user->sex }}
                @else
                    {{ __('applicants.female') }}
                @endif
            </dd>
        </div>
        <div class="nida-identity-field">
            <dt>{{ __('applicants.nationality') }}</dt>
            <dd>
                @if($applicant->nationality)
                    {{ $applicant->nationality }}
                @elseif($user->nationality)
                    {{ $user->nationality }}
                @else
                    Tanzanian
                @endif
            </dd>
        </div>
        <div class="nida-identity-field">
            <dt>{{ __('applicants.phone_number') }}</dt>
            <dd>{{ $applicant->phone ? IdentityNormalizer::formatPhone($applicant->phone) : __('common.na') }}</dd>
        </div>
        <div class="nida-identity-field">
            <dt>{{ __('applicants.email') }}</dt>
            <dd>{{ $applicant->email ?: __('common.na') }}</dd>
        </div>
    </dl>
</div>
