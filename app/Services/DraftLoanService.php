<?php

namespace App\Services;

use App\Models\DraftLoan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DraftLoanService
{
    /** @var list<string> */
    public const DOCUMENT_FIELDS = [
        'business_proposal_document',
        'business_registration_attachment',
        'proof_address_attachment',
        'application_letter',
        'bank_statement',
        'group_constitution',
        'group_muhtasari',
        'group_certificate',
        'guarantor_letter',
    ];

    public function save(int $userId, string $trackId, Request $request, array $except = ['_token', 'form_action']): DraftLoan
    {
        $except = array_merge($except, self::DOCUMENT_FIELDS);

        $incoming = collect($request->except($except))
            ->map(fn ($value) => is_scalar($value) || $value === null ? $value : null)
            ->filter(fn ($value) => $value !== null && $value !== '')
            ->all();

        $existing = DraftLoan::query()
            ->where('track_id', $trackId)
            ->where('user_id', $userId)
            ->first();

        $formData = array_merge($existing?->form_data ?? [], $incoming);

        foreach (self::DOCUMENT_FIELDS as $field) {
            if ($request->hasFile($field)) {
                $formData[$field] = $request->file($field)->store("draft-documents/{$trackId}", 'public');
            }
        }

        $formData['step'] = max(1, min(6, (int) (
            $request->input('step')
            ?? $formData['step']
            ?? $existing?->form_data['step']
            ?? 1
        )));

        if (empty($formData['loan_type'])) {
            $applicant = auth()->user()?->applicant()->withoutGlobalScope(\App\Models\Scopes\ApplicantAccess::class)->first();

            if ($applicant?->preferred_loan_type) {
                $formData['loan_type'] = $applicant->preferred_loan_type;
            }
        }

        return DraftLoan::updateOrCreate(
            ['track_id' => $trackId, 'user_id' => $userId],
            ['form_data' => $formData]
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function findFormData(string $trackId, int $userId): array
    {
        return DraftLoan::query()
            ->where('track_id', $trackId)
            ->where('user_id', $userId)
            ->value('form_data') ?? [];
    }

    /**
     * @return array<string, string>
     */
    public function storedDocuments(string $trackId, int $userId): array
    {
        $formData = $this->findFormData($trackId, $userId);
        $documents = [];

        foreach (self::DOCUMENT_FIELDS as $field) {
            $path = $formData[$field] ?? null;

            if (is_string($path) && $path !== '') {
                $documents[$field] = basename($path);
            }
        }

        return $documents;
    }

    public function deleteByTrackId(string $trackId): void
    {
        $draft = DraftLoan::query()->where('track_id', $trackId)->first();

        if ($draft) {
            $this->deleteStoredDocuments($draft->form_data ?? []);
        }

        DraftLoan::where('track_id', $trackId)->delete();
    }

  /**
   * @param  array<string, mixed>  $formData
   */
    public function deleteStoredDocuments(array $formData): void
    {
        foreach (self::DOCUMENT_FIELDS as $field) {
            $path = $formData[$field] ?? null;

            if (is_string($path) && str_starts_with($path, 'draft-documents/')) {
                Storage::disk('public')->delete($path);
            }
        }
    }
}
