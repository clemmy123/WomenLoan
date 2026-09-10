<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function show(): View|RedirectResponse
    {
        $user = auth()->user();

        if ($user->applicant) {
            return redirect()->route('applicants.show', $user->applicant);
        }

        $applicant = app(\App\Services\ApplicantService::class)->draftFromUser($user);
        $lockNidaFields = (bool) config('services.nida.enabled') && filled($applicant->nin);

        return view('profile.show', compact('user', 'applicant', 'lockNidaFields'));
    }
}
