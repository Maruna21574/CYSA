<x-mail::message>
# {{ __('Nová správa z kontaktného formulára') }}

**{{ __('Meno') }}:** {{ $data['name'] }}
**{{ __('E-mail') }}:** {{ $data['email'] }}
@if ($data['school'])
**{{ __('Škola') }}:** {{ $data['school'] }}
@endif
**{{ __('Téma') }}:** {{ $data['subject'] }}

<x-mail::panel>
{{ $data['message'] }}
</x-mail::panel>

{{ __('Na správu odpovedzte priamo – odpoveď pôjde odosielateľovi.') }}
</x-mail::message>
