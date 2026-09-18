{{-- Renders an uploaded image as <picture> with a WebP source + original
     fallback. Falls back to a plain <img> when no .webp sibling exists.
     Usage: <x-webp :src="$path" :default="asset('...placeholder.png')"
                    alt="..." class="..." loading="lazy" /> --}}
@props(['src' => null, 'alt' => '', 'default' => null, 'sm' => false])
@php
    // When sm is requested, serve the 90x90 "_sm.webp" thumbnail (falls back to
    // the full image for legacy records). Stored webp files have no separate
    // ".webp" sibling, so the <picture> branch is naturally skipped below.
    $bpUrl = $sm ? upload_url_sm($src, $default) : upload_url($src, $default);
    $bpWebp = null;
    // Resolve the .webp from whatever is actually rendered (src OR the default
    // fallback) by mapping its local path to a public file. The .webp sibling
    // is generated at upload time (Upload::store()) or for theme assets.
    if ($bpUrl) {
        $bpRel = ltrim((string) parse_url($bpUrl, PHP_URL_PATH), '/');
        if ($bpRel && is_file(public_path($bpRel . '.webp'))) {
            $bpWebp = asset($bpRel . '.webp');
        }
    }
@endphp
@if($bpUrl && $bpWebp)
<picture><source srcset="{{ $bpWebp }}" type="image/webp"><img src="{{ $bpUrl }}" alt="{{ $alt }}" {{ $attributes }}></picture>
@elseif($bpUrl)
<img src="{{ $bpUrl }}" alt="{{ $alt }}" {{ $attributes }}>
@endif
