<!DOCTYPE html>
<html lang="sk">
<head>
    <meta charset="utf-8">
    <title>{{ __('Certifikát') }} {{ $certificate->code }}</title>
    {{-- Rendered by dompdf: simple CSS only, DejaVu Sans covers Slovak diacritics. --}}
    <style>
        @page { margin: 0; }
        body { margin: 0; font-family: "DejaVu Sans", sans-serif; color: #0f172a; }
        /* A4 landscape = 297 x 210 mm; dompdf needs explicit sizes for absolute boxes. */
        .frame { position: absolute; top: 12mm; left: 12mm; width: 271mm; height: 184mm; border: 3px solid #4f46e5; }
        .inner { position: absolute; top: 16mm; left: 16mm; width: 263mm; height: 176mm; border: 1px solid #c7d2fe; }
        .content { position: absolute; top: 28mm; left: 30mm; width: 237mm; }
        .brand { font-size: 13pt; font-weight: bold; color: #4f46e5; letter-spacing: 2px; }
        h1 { margin: 10mm 0 2mm; font-size: 34pt; letter-spacing: 6px; color: #312e81; text-align: center; }
        .subtitle { text-align: center; font-size: 12pt; color: #475569; }
        .name { margin: 8mm 0 3mm; text-align: center; font-size: 28pt; font-weight: bold; }
        .text { text-align: center; font-size: 12pt; color: #334155; }
        .course { margin: 3mm 0 0; text-align: center; font-size: 18pt; font-weight: bold; color: #312e81; }
        .footer { position: absolute; top: 150mm; left: 30mm; width: 237mm; }
        .footer table { width: 100%; border-collapse: collapse; }
        .footer td { vertical-align: bottom; font-size: 9.5pt; color: #334155; }
        .sign { border-top: 1px solid #94a3b8; padding-top: 2mm; width: 60mm; }
        .code { font-family: "DejaVu Sans Mono", monospace; font-size: 10pt; color: #0f172a; }
        .small { font-size: 8pt; color: #64748b; }
    </style>
</head>
<body>
    <div class="frame"></div>
    <div class="inner"></div>
    <div class="content">
        <div class="brand">{{ config('app.name') }} · {{ __('KYBERNETICKÁ BEZPEČNOSŤ') }}</div>

        <h1>{{ __('CERTIFIKÁT') }}</h1>
        <p class="subtitle">{{ __('o úspešnom absolvovaní kurzu') }}</p>

        <p class="name">{{ $certificate->holder_name }}</p>
        <p class="text">{{ __('úspešne absolvoval(a) kurz') }}</p>
        <p class="course">{{ $certificate->course_title }}</p>
        @if ($certificate->final_percentage !== null)
            <p class="text">{{ __('s celkovou úspešnosťou :p %', ['p' => \App\Support\Format::number($certificate->final_percentage, 1)]) }}</p>
        @endif
    </div>

        <div class="footer">
            <table>
                <tr>
                    <td>
                        <div class="sign">
                            {{ $certificate->teacher_name }}<br>
                            <span class="small">{{ __('učiteľ / autor kurzu') }}</span>
                        </div>
                    </td>
                    <td>
                        <div class="sign">
                            {{ $certificate->school_name }}<br>
                            <span class="small">{{ __('Vydané :date', ['date' => $certificate->issued_at->format('j. n. Y')]) }}</span>
                        </div>
                    </td>
                    <td style="text-align: right;">
                        <img src="{{ $qr }}" alt="" style="width: 24mm; height: 24mm;"><br>
                        <span class="code">{{ $certificate->code }}</span><br>
                        <span class="small">{{ __('Overenie: :url', ['url' => $certificate->verificationUrl()]) }}</span>
                    </td>
                </tr>
            </table>
    </div>
</body>
</html>
