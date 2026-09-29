<x-layouts::app :title="__('Certifikát kurzu')">
    @include('teacher.courses._header', ['active' => 'certificate'])

    <div class="grid gap-6 lg:grid-cols-5">
        <form method="POST" action="{{ route('teacher.courses.certificate.update', $course) }}" class="flex flex-col gap-4 lg:col-span-3">
            @csrf
            @method('PUT')

            <x-card class="flex flex-col gap-4">
                <x-form.checkbox name="certificate_enabled" :checked="$course->certificate_enabled" :label="__('Kurz vydáva certifikát')"
                    :hint="__('Certifikát sa študentovi vydá automaticky hneď po splnení podmienok.')" />
                <x-form.input name="certificate_min_percentage" type="number" min="0" max="100" :label="__('Minimálna priemerná úspešnosť v testoch (%)')" :value="$course->certificate_min_percentage" class="max-w-40" />
            </x-card>

            <x-card :title="__('Povinné kapitoly')">
                <p class="mb-3 text-xs text-slate-500">{{ __('Ak nevyberiete žiadnu, musia byť dokončené všetky zverejnené kapitoly.') }}</p>
                @forelse ($chapters as $chapter)
                    <label class="flex items-center gap-2 py-1 text-sm"><input type="checkbox" name="chapters[]" value="{{ $chapter->id }}" @checked(in_array($chapter->id, old('chapters', $selectedChapters))) class="size-4 rounded text-brand-600">{{ $chapter->title }}</label>
                @empty
                    <p class="text-sm text-slate-500">{{ __('Kurz nemá zverejnené kapitoly.') }}</p>
                @endforelse
                @error('chapters.*') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </x-card>

            <x-card :title="__('Povinné testy')">
                <p class="mb-3 text-xs text-slate-500">{{ __('Ak nevyberiete žiadny, musia byť úspešne absolvované všetky publikované testy a výstupné testy (precvičovacie kvízy a vstupný test sa nepočítajú).') }}</p>
                @forelse ($quizzes as $quiz)
                    <label class="flex items-center gap-2 py-1 text-sm"><input type="checkbox" name="quizzes[]" value="{{ $quiz->id }}" @checked(in_array($quiz->id, old('quizzes', $selectedQuizzes))) class="size-4 rounded text-brand-600">{{ $quiz->title }} <span class="text-xs text-slate-500">({{ $quiz->purpose->label() }})</span></label>
                @empty
                    <p class="text-sm text-slate-500">{{ __('Kurz nemá publikované testy.') }}</p>
                @endforelse
                @error('quizzes.*') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </x-card>

            <div><x-button>{{ __('Uložiť podmienky') }}</x-button></div>
        </form>

        <x-card :title="__('Vydané certifikáty (:n)', ['n' => $certificates->count()])" class="h-fit lg:col-span-2">
            @forelse ($certificates as $certificate)
                <div class="border-b border-slate-100 py-3 text-sm last:border-0">
                    <div class="flex items-center justify-between gap-2">
                        <span class="font-medium text-slate-900">{{ $certificate->holder_name }}</span>
                        @if ($certificate->isValid())
                            <a href="{{ route('certificates.download', $certificate) }}" class="text-brand-700 hover:underline">PDF</a>
                        @else
                            <x-badge color="red">{{ __('Zrušený') }}</x-badge>
                        @endif
                    </div>
                    <span class="font-mono text-xs text-slate-500">{{ $certificate->code }} · {{ $certificate->issued_at->translatedFormat('j. n. Y') }}</span>
                    @if ($certificate->isValid())
                        <form method="POST" action="{{ route('teacher.certificates.revoke', $certificate) }}" class="mt-2 flex gap-2"
                              x-data @submit="if (! confirm(@js(__('Naozaj zrušiť tento certifikát?')))) $event.preventDefault()">
                            @csrf
                            <label for="reason-{{ $certificate->id }}" class="sr-only">{{ __('Dôvod zrušenia') }}</label>
                            <input id="reason-{{ $certificate->id }}" name="reason" required maxlength="255" placeholder="{{ __('Dôvod zrušenia') }}" class="block w-full rounded-lg border border-slate-300 px-2 py-1 text-xs">
                            <x-button variant="danger" class="px-2 py-1 text-xs">{{ __('Zrušiť') }}</x-button>
                        </form>
                    @endif
                </div>
            @empty
                <p class="text-sm text-slate-500">{{ __('Zatiaľ nikto nezískal certifikát.') }}</p>
            @endforelse
        </x-card>
    </div>
</x-layouts::app>
