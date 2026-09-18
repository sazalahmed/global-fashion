{{-- Emits a single BizPOS.track() call for a server-rendered event.
     Params: $event (string FB-style name), $ga (array GA4 params),
             $fb (array ['data'=>[...]]), $eventId (optional string). --}}
@php
    $ga = $ga ?? [];
    $fb = $fb ?? [];
    $eventId = $eventId ?? null;
@endphp
<script>
'use strict';
document.addEventListener('DOMContentLoaded', function () {
    if (!window.BizPOS || typeof window.BizPOS.track !== 'function') { return; }
    window.BizPOS.track(
        @json($event),
        @json($ga),
        {
            fbData: @json($fb['data'] ?? []),
            @if($eventId) eventID: @json($eventId) @endif
        }
    );
});
</script>
