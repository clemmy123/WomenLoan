{{-- Report PDF letterhead: plain emblem, ministry heading on clean background --}}
@php
    $reportTitle = $reportTitle ?? '';

    $pdfDataUri = static function (string $relativePath): ?string {
        $path = public_path($relativePath);
        if (! is_file($path)) {
            return null;
        }

        return 'data:image/png;base64,'.base64_encode((string) file_get_contents($path));
    };

    $nemboSrc = $pdfDataUri('images/nembo2.png');
@endphp

<table width="100%" cellpadding="0" cellspacing="0" style="margin:0 0 14px 0; border-collapse:collapse;">
    <tr>
        <td align="center" style="padding:16px 18px; background-color:#ffffff; text-align:center; border-bottom:2px solid #1e3a8a;">
            @if($nemboSrc)
                <img src="{{ $nemboSrc }}" width="84" height="84" alt="" style="display:block; width:84px; height:84px; margin:0 auto 10px auto;">
            @endif
            <div style="font-size:16px; font-weight:bold; color:#0f766e; line-height:1.35; margin:0 0 4px 0;">
                {{ __('reports.pdf_ministry') }}
            </div>
            <div style="font-size:11px; font-weight:bold; color:#1e3a8a; margin:0 0 3px 0;">
                {{ __('reports.pdf_fund') }}
            </div>
            @if($reportTitle !== '')
                <div style="font-size:12px; font-weight:bold; color:#1e3a8a; margin:0;">
                    {{ $reportTitle }}
                </div>
            @endif
        </td>
    </tr>
</table>
