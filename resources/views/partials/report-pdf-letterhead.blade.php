{{-- Report PDF letterhead: emblem, ministry heading, gradient banner --}}
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
    $gradientSrc = $pdfDataUri('images/pdf-header-gradient.png');
@endphp

<table width="100%" cellpadding="0" cellspacing="0" style="margin:0 0 14px 0; border-collapse:collapse;">
    <tr>
        <td style="padding:16px 18px; background-color:#4f46e5; @if($gradientSrc) background-image:url('{{ $gradientSrc }}'); background-repeat:no-repeat; background-position:left center; @endif">
            <table width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;">
                <tr>
                    @if($nemboSrc)
                        <td width="96" valign="middle" style="padding:0 14px 0 0;">
                            <img src="{{ $nemboSrc }}" width="84" height="84" alt="" style="display:block; width:84px; height:84px;">
                        </td>
                    @endif
                    <td valign="middle" style="padding:0;">
                        <div style="font-size:16px; font-weight:bold; color:#ffffff; line-height:1.35; margin:0 0 4px 0;">
                            {{ __('reports.pdf_ministry') }}
                        </div>
                        <div style="font-size:11px; font-weight:bold; color:#e0e7ff; margin:0 0 3px 0;">
                            {{ __('reports.pdf_fund') }}
                        </div>
                        @if($reportTitle !== '')
                            <div style="font-size:12px; font-weight:bold; color:#ffffff; margin:0;">
                                {{ $reportTitle }}
                            </div>
                        @endif
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
