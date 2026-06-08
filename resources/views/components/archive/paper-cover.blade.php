{{--
    Archive paper cover — coloured placeholder with SITE logo.
    Used on the paper card (small) and the show page left panel (larger).

    Props:
      - paper:    ResearchPaper model with 'course' loaded
      - logoSize: Tailwind width class for the logo (default: w-10)
--}}
@props(['paper', 'logoSize' => 'w-10'])

@php
    $code = $paper->course->code ?? '';
    $bg = match($code) {
        'IT'   => 'bg-emerald-800',   // dark forest green
        'CpE'  => 'bg-blue-900',      // dark navy
        'CE'   => 'bg-lime-600',      // bright lime-green (distinct from emerald)
        'ENSE' => 'bg-teal-600',      // medium teal
        'BLIS' => 'bg-rose-800',      // warm burgundy (clearly different from navy)
        default => 'bg-gray-600',
    };
@endphp

<div class="absolute inset-0 flex flex-col items-center justify-center {{ $bg }}">
    <img src="{{ asset('images/spup-site.png') }}"
         alt="SITE"
         class="{{ $logoSize }} object-contain opacity-70" />
</div>
