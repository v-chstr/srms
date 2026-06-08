{{--
    Shared inner content for archive/show.
    Included by both @auth and @else branches of show.blade.php.
    Variables: $paper, $citations
--}}

@auth
{{-- Back link for authenticated users (renders on gray page bg) --}}
<a href="{{ route('archive.index') }}" class="inline-flex items-center gap-1 text-xs font-medium text-gray-500 hover:text-gray-900 mb-4 transition-colors">
    <x-ui.icon name="arrow-left" class="h-3.5 w-3.5" />
    Back to Archive
</a>
@endauth

@guest
{{-- Hero: paper title, authors and course on dark green (guests only) --}}
<div class="relative overflow-hidden bg-primary-900">
    <div class="pointer-events-none absolute inset-0 opacity-[0.04]"
         style="background-image: radial-gradient(circle, white 1px, transparent 1px); background-size: 28px 28px;"></div>
    <div class="relative mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 pb-8 pt-4">
        {{-- Back link inside hero --}}
        <a href="{{ route('archive.index') }}" class="inline-flex items-center gap-1 text-xs font-medium text-primary-300 hover:text-white mb-4 transition-colors">
            <x-ui.icon name="arrow-left" class="h-3.5 w-3.5" />
            Back to Archive
        </a>
        <div class="flex flex-col sm:flex-row sm:items-start sm:gap-6">
            {{-- Compact cover band --}}
            <div class="shrink-0 w-20 sm:w-24">
                <div class="relative h-28 sm:h-32 rounded-md overflow-hidden border border-primary-700">
                    <x-archive.paper-cover :paper="$paper" logoSize="w-8" />
                </div>
            </div>
            {{-- Title block --}}
            <div class="mt-4 sm:mt-0 flex-1 min-w-0">
                @if($paper->course)
                    <span class="inline-block mb-2.5 rounded-md bg-primary-800 px-2.5 py-1 text-xs font-medium text-accent-300">{{ $paper->course->displayCode() }}</span>
                @endif
                <h1 class="text-2xl font-bold text-white leading-snug break-words mb-3">{{ $paper->title }}</h1>
                @if($paper->authors->isNotEmpty())
                    <p class="text-sm text-primary-200">
                        {{ $paper->authors->map(fn ($a) => trim($a->first_name . ' ' . $a->last_name))->implode(', ') }}
                    </p>
                @endif
                <div class="mt-3 flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-primary-300">
                    <span>{{ $paper->published_year ?? $paper->created_at->year }}</span>
                    @if($paper->adviser)
                        <span>Advised by {{ $paper->adviser->first_name }} {{ $paper->adviser->last_name }}</span>
                    @elseif($paper->adviser_name)
                        <span>Advised by {{ $paper->adviser_name }}</span>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endguest

{{-- Main content area --}}
<div class="{{ auth()->check() ? '' : 'mx-auto max-w-7xl px-4 sm:px-6 lg:px-8' }} py-6">
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-5">

        {{-- Left sidebar: compact cover + download + quick metadata --}}
        <aside class="lg:col-span-3 space-y-4">

            {{-- Cover + actions --}}
            <div class="rounded-md border border-gray-200 bg-white overflow-hidden">
                {{-- Compact cover band (auth only — guests saw it in the hero) --}}
                @auth
                <div class="relative h-36 bg-gray-100 border-b border-gray-200">
                    <x-archive.paper-cover :paper="$paper" logoSize="w-10" />
                </div>
                @endauth
                <div class="p-3 flex flex-col gap-2">
                    @if(strtolower(pathinfo($paper->original_filename ?? $paper->file_path ?? '', PATHINFO_EXTENSION)) === 'pdf')
                        <a href="{{ route('archive.preview', $paper->id) }}" target="_blank"
                           class="flex w-full items-center justify-center gap-1.5 rounded-md border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                            <x-ui.icon name="eye" class="h-4 w-4" />
                            Preview
                        </a>
                    @endif
                    <a href="{{ route('archive.download', $paper->id) }}"
                       class="flex w-full items-center justify-center gap-1.5 rounded-md bg-primary-600 px-3 py-2 text-sm font-medium text-white hover:bg-primary-700">
                        <x-ui.icon name="arrow-down-tray" class="h-4 w-4" />
                        Download
                    </a>
                </div>
            </div>

            {{-- Quick metadata --}}
            <dl class="rounded-md border border-gray-200 bg-white divide-y divide-gray-100">
                <div class="px-3 py-2.5">
                    <dt class="text-xs font-medium text-gray-500">Program</dt>
                    <dd class="mt-0.5 text-sm text-gray-900">{{ $paper->course->name ?? 'N/A' }}</dd>
                </div>
                <div class="px-3 py-2.5">
                    <dt class="text-xs font-medium text-gray-500">Year</dt>
                    <dd class="mt-0.5 text-sm text-gray-900 tabular-nums">{{ $paper->published_year ?? $paper->created_at->year }}</dd>
                </div>
                <div class="px-3 py-2.5">
                    <dt class="text-xs font-medium text-gray-500">Adviser</dt>
                    <dd class="mt-0.5 text-sm text-gray-900">
                        @if($paper->adviser)
                            {{ $paper->adviser->first_name }} {{ $paper->adviser->last_name }}
                        @elseif($paper->adviser_name)
                            {{ $paper->adviser_name }}
                        @else
                            Not Indicated
                        @endif
                    </dd>
                </div>
                <div class="px-3 py-2.5">
                    <dt class="text-xs font-medium text-gray-500">Submitted</dt>
                    <dd class="mt-0.5 text-sm text-gray-900">{{ $paper->created_at->format('M j, Y') }}</dd>
                </div>
            </dl>
        </aside>

        {{-- Right main: title (auth only), abstract, keywords, citations --}}
        <div class="lg:col-span-9 space-y-4">

            @auth
            {{-- Title block for authenticated users (no hero) --}}
            <header class="rounded-md border border-gray-200 bg-white p-4">
                @if($paper->course)
                    <span class="inline-block mb-2 rounded-md bg-primary-50 px-2.5 py-1 text-xs font-medium text-primary-700">{{ $paper->course->displayCode() }}</span>
                @endif
                <h1 class="text-xl font-semibold text-gray-900 leading-snug break-words">{{ $paper->title }}</h1>
                @if($paper->authors->isNotEmpty())
                    <p class="mt-2 text-sm text-gray-600">
                        {{ $paper->authors->map(fn ($a) => trim($a->first_name . ' ' . $a->last_name))->implode(', ') }}
                    </p>
                @endif
            </header>
            @endauth

            {{-- Abstract --}}
            <section class="rounded-md border border-gray-200 bg-white p-4">
                <h2 class="text-xs font-semibold text-gray-500 mb-3">Abstract</h2>
                @if($paper->abstract)
                    <p class="text-sm text-gray-700 leading-relaxed whitespace-pre-line">{{ $paper->abstract }}</p>
                @else
                    <p class="text-sm text-gray-400 italic">No abstract provided.</p>
                @endif

                @if($paper->keywords)
                    <div class="mt-4 pt-4 border-t border-gray-100">
                        <h3 class="text-xs font-semibold text-gray-500 mb-2">Keywords</h3>
                        <div class="flex flex-wrap gap-1.5">
                            @foreach($paper->keywords as $kw)
                                <span class="inline-block rounded-md bg-gray-100 px-2.5 py-1 text-xs text-gray-600">
                                    {{ \Illuminate\Support\Str::of($kw)->replace(['-', '_'], ' ')->title() }}
                                </span>
                            @endforeach
                        </div>
                    </div>
                @endif
            </section>

            {{-- Citation generator --}}
            <section class="rounded-md border border-gray-200 bg-white p-4" x-data="{ format: 'apa', copied: false }">
                <div class="flex items-center justify-between mb-3">
                    <h2 class="text-xs font-semibold text-gray-500">Cite this paper</h2>
                    <div class="flex gap-1">
                        @foreach(['apa' => 'APA', 'mla' => 'MLA', 'chicago' => 'Chicago'] as $key => $label)
                            <button type="button" @click="format = '{{ $key }}'"
                                    :class="format === '{{ $key }}' ? 'bg-primary-50 text-primary-700 border-primary-200' : 'bg-white text-gray-500 border-gray-200 hover:bg-gray-50'"
                                    class="px-2.5 py-1 text-xs font-medium rounded-md border transition-colors">
                                {{ $label }}
                            </button>
                        @endforeach
                    </div>
                </div>

                <div class="bg-gray-50 border border-gray-100 rounded-md p-3 text-sm text-gray-700 leading-relaxed break-words">
                    <p x-show="format === 'apa'" x-cloak>{{ $citations['apa'] }}</p>
                    <p x-show="format === 'mla'" x-cloak>{{ $citations['mla'] }}</p>
                    <p x-show="format === 'chicago'" x-cloak>{{ $citations['chicago'] }}</p>
                </div>

                <button type="button"
                    @click="
                        const text = format === 'apa' ? @js($citations['apa']) : format === 'mla' ? @js($citations['mla']) : @js($citations['chicago']);
                        navigator.clipboard.writeText(text);
                        copied = true;
                        setTimeout(() => copied = false, 2000);
                    "
                    class="mt-3 inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-gray-600 bg-white border border-gray-200 rounded-md hover:bg-gray-50">
                    <x-ui.icon name="clipboard" class="h-3.5 w-3.5" />
                    <span x-text="copied ? 'Copied' : 'Copy citation'"></span>
                </button>
            </section>

        </div>
    </div>
</div>
