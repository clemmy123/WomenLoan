<?php

namespace App\Services;

use App\Models\LoanPayment;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\Writer\PngWriter;
use Illuminate\Support\Carbon;

class ReceiptQrCodeService
{
    public function payload(LoanPayment $payment, array $tx, string $receiptNumber): string
    {
        $loan = $payment->relationLoaded('loan') && $payment->loan
            ? $payment->loan
            : $payment->loan()->withoutGlobalScopes()->first();

        $applicant = $loan
            ? ($loan->relationLoaded('applicant') && $loan->applicant
                ? $loan->applicant
                : $loan->applicant()->withoutGlobalScopes()->first())
            : null;

        $paymentDate = Carbon::parse($tx['date'] ?? now())->format('Y-m-d h:i:s A');
        $amount = number_format((float) ($tx['amount'] ?? 0), 2, '.', ',');

        // Spaced phone avoids phone-camera "Call" mode that hides other QR text.
        $lines = [
            'WDF LOAN PAYMENT RECEIPT',
            'Payment DateTime = '.$paymentDate,
            'Receipt No = '.$receiptNumber,
            'Loan Track = '.($loan?->loan_track_id ?: 'N/A'),
            'Payer Name = '.($applicant?->full_name ?: 'N/A'),
            'Payer Phone = '.$this->formatPhoneForQr($applicant?->phone),
            'Amount TZS = '.$amount,
            'Payment Ref = '.($tx['reference'] ?? 'N/A'),
            'Pay Method = '.($tx['method'] ?? 'N/A'),
            'WDF Account = '.config('wdf.repayment_account.account_number'),
            'Account Name = '.config('wdf.repayment_account.account_name'),
        ];

        return implode("\n", $lines);
    }

    public function dataUri(LoanPayment $payment, array $tx, string $receiptNumber): string
    {
        $this->ensureQrLibrariesAutoloaded();

        $result = (new Builder(
            writer: new PngWriter,
            writerOptions: [],
            validateResult: false,
            data: $this->payload($payment, $tx, $receiptNumber),
            encoding: new Encoding('UTF-8'),
            errorCorrectionLevel: ErrorCorrectionLevel::Medium,
            size: 280,
            margin: 10,
        ))->build();

        return $result->getDataUri();
    }

    protected function formatPhoneForQr(?string $phone): string
    {
        $digits = preg_replace('/\D+/', '', (string) $phone);

        if ($digits === '') {
            return 'N/A';
        }

        return trim(chunk_split($digits, 3, ' '));
    }

    protected function ensureQrLibrariesAutoloaded(): void
    {
        if (class_exists(Builder::class)) {
            return;
        }

        spl_autoload_register(static function (string $class): void {
            $prefixes = [
                'Endroid\\QrCode\\' => base_path('vendor/endroid/qr-code/src/'),
                'BaconQrCode\\' => base_path('vendor/bacon/bacon-qr-code/src/'),
                'DASPRiD\\Enum\\' => base_path('vendor/dasprid/enum/src/'),
            ];

            foreach ($prefixes as $prefix => $baseDir) {
                if (! str_starts_with($class, $prefix)) {
                    continue;
                }

                $relative = str_replace('\\', '/', substr($class, strlen($prefix))).'.php';
                $path = $baseDir.$relative;

                if (is_file($path)) {
                    require_once $path;
                }
            }
        });
    }
}
