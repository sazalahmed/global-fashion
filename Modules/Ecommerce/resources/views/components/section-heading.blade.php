{{--
    Renders a section heading with the first occurrence of $highlight wrapped in
    a <span> (the storefront's emphasized-word style). Both inputs are escaped
    first, then the span is injected — so admin-entered text is XSS-safe.
    Outputs inline HTML only (no wrapper), so each partial keeps its own h2/h3.
--}}
@props(['heading' => '', 'highlight' => ''])
@php
    $h  = (string) ($heading ?? '');
    $hl = trim((string) ($highlight ?? ''));
    $safe = e($h);
    if ($hl !== '' && \Illuminate\Support\Str::contains($h, $hl)) {
        $safe = \Illuminate\Support\Str::replaceFirst(e($hl), '<span>' . e($hl) . '</span>', $safe);
    }
@endphp
{!! $safe !!}
