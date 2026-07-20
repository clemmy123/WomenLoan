{{-- Shared dark-blue gradient styles for report PDF data table headers --}}
@php
    $pdfThPadding = $pdfThPadding ?? '8px 6px';
    $pdfThFontSize = $pdfThFontSize ?? '10px';

    $reportPdfHeaderGradientSvg = '<svg xmlns="http://www.w3.org/2000/svg" width="1200" height="40">'
        .'<defs><linearGradient id="thGrad" x1="0%" y1="0%" x2="100%" y2="0%">'
        .'<stop offset="0%" stop-color="#0f766e"/>'
        .'<stop offset="45%" stop-color="#1e3a8a"/>'
        .'<stop offset="100%" stop-color="#0ea5e9"/>'
        .'</linearGradient></defs>'
        .'<rect width="1200" height="40" fill="url(#thGrad)"/></svg>';

    $reportPdfHeaderGradientSrc = 'data:image/svg+xml;base64,'.base64_encode($reportPdfHeaderGradientSvg);
@endphp
        table.data th {
            background-color: #1e3a8a;
            background-image: url('{{ $reportPdfHeaderGradientSrc }}');
            background-repeat: no-repeat;
            background-size: cover;
            background-position: center;
            color: #ffffff;
            font-weight: bold;
            padding: {{ $pdfThPadding }};
            text-align: left;
            font-size: {{ $pdfThFontSize }};
        }
