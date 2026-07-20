{{-- Report PDF filter summary bar: bold white text on dark-blue gradient --}}
@php
    $metaGradientSvg = '<svg xmlns="http://www.w3.org/2000/svg" width="1200" height="56">'
        .'<defs><linearGradient id="metaGrad" x1="0%" y1="0%" x2="100%" y2="0%">'
        .'<stop offset="0%" stop-color="#0f766e"/>'
        .'<stop offset="45%" stop-color="#1e3a8a"/>'
        .'<stop offset="100%" stop-color="#0ea5e9"/>'
        .'</linearGradient></defs>'
        .'<rect width="1200" height="56" fill="url(#metaGrad)"/></svg>';

    $metaGradientSrc = 'data:image/svg+xml;base64,'.base64_encode($metaGradientSvg);
@endphp

<table width="100%" cellpadding="0" cellspacing="0" style="margin:0 0 16px 0; border-collapse:collapse;">
    <tr>
        <td
            align="center"
            style="padding:10px 14px; background-color:#1e3a8a; background-image:url('{{ $metaGradientSrc }}'); background-repeat:no-repeat; background-size:cover; background-position:center; font-family:DejaVu Sans, sans-serif; font-size:10px; font-weight:bold; color:#ffffff; line-height:1.5; text-align:center;"
        >
            {{ $slot }}
        </td>
    </tr>
</table>
