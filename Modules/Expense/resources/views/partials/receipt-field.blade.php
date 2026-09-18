{{-- Receipt / attachment picker.
     Pass $existingPath (stored relative path) when editing, and
     $showPreview => true to render the inline image / PDF preview. Creating a
     new expense does not preview: there is nothing stored yet to confirm, and
     the panel pushed the form's own fields off-screen. The list previews the
     saved attachment instead. --}}
@php
    $existingPath = $existingPath ?? null;
    $showPreview = $showPreview ?? false;
    $existingUrl = $existingPath ? \App\Helpers\Upload::url($existingPath) : null;
    $existingIsPdf = $existingPath && \Illuminate\Support\Str::endsWith(strtolower($existingPath), '.pdf');
@endphp

<label class="bp-form-label">Receipt / Attachment</label>
<input type="file" class="bp-form-control" name="receipt" id="receiptInput" accept="image/*,.pdf">

@if ($showPreview)
<div class="bp-receipt-preview {{ $existingUrl ? '' : 'd-none' }}" id="receiptPreview">
    <img alt="Receipt preview" class="bp-receipt-preview-img {{ $existingUrl && ! $existingIsPdf ? '' : 'd-none' }}"
        id="receiptPreviewImg" @if ($existingUrl && ! $existingIsPdf) src="{{ $existingUrl }}" @endif>
    <iframe class="bp-receipt-preview-pdf {{ $existingIsPdf ? '' : 'd-none' }}" id="receiptPreviewPdf"
        title="Receipt PDF preview" @if ($existingIsPdf) src="{{ $existingUrl }}" @endif></iframe>
    <div class="bp-receipt-preview-meta">
        <span class="bp-receipt-preview-name">
            <i class="fa-solid fa-paperclip me-1"></i><span id="receiptPreviewName">{{ $existingPath ? basename($existingPath) : '' }}</span>
        </span>
        <a href="{{ $existingUrl ?? '#' }}" target="_blank" rel="noopener"
            class="bp-btn bp-btn-sm bp-btn-outline {{ $existingUrl ? '' : 'd-none' }}" id="receiptPreviewOpen">
            <i class="fa-solid fa-eye me-1"></i>View
        </a>
    </div>
</div>

@push('scripts')
    <script>
        'use strict';
        $(function() {
            var $img = $('#receiptPreviewImg'),
                $pdf = $('#receiptPreviewPdf'),
                $name = $('#receiptPreviewName'),
                $open = $('#receiptPreviewOpen'),
                $wrap = $('#receiptPreview');
            var initial = {
                img: $img.attr('src') || '',
                pdf: $pdf.attr('src') || '',
                name: $name.text().trim(),
                href: $open.attr('href') || '#'
            };
            var objectUrl = null;

            $('#receiptInput').on('change', function() {
                if (objectUrl) {
                    URL.revokeObjectURL(objectUrl);
                    objectUrl = null;
                }

                var file = this.files && this.files[0];
                if (!file) {
                    // Selection cleared — restore the stored attachment, if any.
                    if (initial.img) $img.attr('src', initial.img).removeClass('d-none');
                    else $img.removeAttr('src').addClass('d-none');
                    if (initial.pdf) $pdf.attr('src', initial.pdf).removeClass('d-none');
                    else $pdf.attr('src', 'about:blank').addClass('d-none');
                    $name.text(initial.name);
                    $open.attr('href', initial.href).toggleClass('d-none', initial.href === '#');
                    $wrap.toggleClass('d-none', !initial.img && !initial.pdf);
                    return;
                }

                objectUrl = URL.createObjectURL(file);
                var isPdf = file.type === 'application/pdf' || /\.pdf$/i.test(file.name);
                var isImage = file.type.indexOf('image/') === 0;

                if (isPdf) {
                    $pdf.attr('src', objectUrl).removeClass('d-none');
                    $img.removeAttr('src').addClass('d-none');
                } else if (isImage) {
                    $img.attr('src', objectUrl).removeClass('d-none');
                    $pdf.attr('src', 'about:blank').addClass('d-none');
                } else {
                    $img.removeAttr('src').addClass('d-none');
                    $pdf.attr('src', 'about:blank').addClass('d-none');
                }
                $name.text(file.name);
                $open.attr('href', objectUrl).removeClass('d-none');
                $wrap.removeClass('d-none');
            });
        });
    </script>
@endpush
@endif
