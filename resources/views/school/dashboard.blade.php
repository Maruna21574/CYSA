@php use App\Support\Format; use App\Support\Terms; @endphp
<x-layouts::app :title="Terms::of('Prehľad školy', school: $school)">
    <x-page-header :title="$school->name" :description="Terms::of('Prehľad školy za posledných 30 dní.', school: $school)">
        <x-slot:actions>
            <x-link-button variant="secondary" :href="route('school.students.import')">{{ Terms::of('Import študentov', school: $school) }}</x-link-button>
            <x-link-button :href="route('school.classrooms.create')">{{ Terms::of('Nová trieda', school: $school) }}</x-link-button>
        </x-slot:actions>
    </x-page-header>

    <div class="mb-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <x-stat :label="Terms::of('Učitelia', school: $school)" :value="$teachers"
            :hint="$admins > 0 ? Terms::choice('{1} + :count administrátor školy|[2,*] + :count administrátori školy', $admins, ['count' => $admins], $school) : null"
            icon="users" :href="route('users.index', ['role' => 'teacher'])" />
        <x-stat :label="Terms::of('Študenti', school: $school)" :value="$students" :hint="__(':n aktívnych za 30 dní', ['n' => $activeStudents])" icon="users" :href="route('users.index', ['role' => 'student'])" />
        <x-stat :label="__('Kurzy')" :value="$courses" :hint="__(':n publikovaných', ['n' => $publishedCourses])" icon="book" :href="route('teacher.courses.index')" />
        <x-stat :label="__('Priemerná úspešnosť')" :value="Format::percent($summary['average'])" :hint="__(':n testov za 30 dní', ['n' => $summary['attempts']])" icon="chart" />
    </div>

    <div class="grid gap-6 lg:grid-cols-2">
        <x-card :title="Terms::of('Triedy', school: $school)">
            @forelse ($classrooms as $classroom)
                <a href="{{ route('school.classrooms.show', $classroom) }}" class="flex justify-between border-b border-slate-100 py-2 text-sm last:border-0 hover:text-brand-700">
                    <span class="font-medium">{{ $classroom->name }} <span class="font-normal text-slate-500">({{ $classroom->school_year }})</span></span>
                    <span class="text-slate-500">{{ Terms::choice('{1} :count študent|[2,4] :count študenti|[0,*] :count študentov', $classroom->students_count, ['count' => $classroom->students_count], $school) }}</span>
                </a>
            @empty
                <p class="text-sm text-slate-500">{{ Terms::of('Zatiaľ žiadne triedy.', school: $school) }}</p>
            @endforelse
        </x-card>

        <x-card :title="Terms::of('Úspešnosť školy podľa tém', school: $school)">
            @forelse ($topics as $topic)
                <div class="mb-2">
                    <p class="text-sm text-slate-800">{{ $topic->topic }}</p>
                    <x-meter :value="$topic->success" />
                </div>
            @empty
                <p class="text-sm text-slate-500">{{ __('Zatiaľ bez výsledkov.') }}</p>
            @endforelse
        </x-card>
    </div>
</x-layouts::app>
