@extends('core::layouts.master')

@section('title', __('Edit Menu'))
@section('page-title', $menu->name)

@section('breadcrumb')
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <a href="{{ route('ecommerce.index') }}">Website</a>
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <a href="{{ route('ecommerce.menus.index') }}">Menus</a>
    <span class="sep"><i class="fa-solid fa-chevron-right"></i></span>
    <span>{{ $menu->name }}</span>
@endsection

@section('page-actions')
    <a href="{{ route('ecommerce.menus.index') }}" class="bp-btn bp-btn-primary"><i
            class="fa-solid fa-arrow-left me-1"></i>Back</a>
    @bpCan('ecommerce.edit')
        <form action="{{ route('ecommerce.menus.reset', $menu) }}" method="POST" class="d-inline"
            onsubmit="return confirm('Reset this menu to its default items? Current items will be replaced.');">
            @csrf
            <button type="submit" class="bp-btn bp-btn-warning"><i class="fa-solid fa-rotate-left me-1"></i>Reset to
                default</button>
        </form>
    @endbpCan
    @bpCan('ecommerce.delete')
        <form action="{{ route('ecommerce.menus.clear', $menu) }}" method="POST" class="d-inline"
            onsubmit="return confirm('Remove ALL items from this menu? The storefront will fall back to its built-in default until you add items.');">
            @csrf
            <button type="submit" class="bp-btn bp-btn-danger"><i class="fa-solid fa-trash me-1"></i>Clear menu</button>
        </form>
    @endbpCan
@endsection

@section('content')
    <div class="row g-4">
        {{-- Left: Add item --}}
        <div class="col-lg-4">
            <div class="bp-card">
                <div class="bp-card-header">
                    <h5 class="bp-card-title"><i class="fa-solid fa-plus me-2"></i>Add Item</h5>
                </div>
                <div class="bp-card-body">
                    @bpCan('ecommerce.edit')
                        <form action="{{ route('ecommerce.menus.items.store', $menu) }}" method="POST" id="addItemForm"
                            class="row g-3">
                            @csrf
                            <div class="col-12">
                                <label class="bp-form-label">Type *</label>
                                <select class="bp-form-select w-100" name="type" id="addItemType" required>
                                    <option value="route">Page / Route</option>
                                    <option value="category">Category</option>
                                    <option value="page">Custom Page</option>
                                    <option value="url">Custom URL</option>
                                    <option value="widget">Widget (cart, wishlist…)</option>
                                    <option value="categories_dropdown">Categories Dropdown</option>
                                    <option value="heading">Heading (no link)</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="bp-form-label">Label *</label>
                                <input class="bp-form-control" name="label" required>
                            </div>

                            {{-- type-specific value fields --}}
                            <div class="col-12 add-val" data-for="route">
                                <label class="bp-form-label">Route</label>
                                <select class="bp-form-select w-100" name="value_route">
                                    @foreach ($routes as $name => $opt)
                                        <option value="{{ $name }}">{{ $opt['label'] }} — {{ $opt['path'] }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12 add-val d-none" data-for="category">
                                <label class="bp-form-label">Category</label>
                                <select class="bp-form-select w-100" name="value_category">
                                    @foreach ($categories as $c)
                                        <option value="{{ $c->id }}">{{ $c->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12 add-val d-none" data-for="page">
                                <label class="bp-form-label">Page</label>
                                <select class="bp-form-select w-100" name="value_page">
                                    @foreach ($pages as $p)
                                        <option value="{{ $p->id }}">{{ $p->title }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12 add-val d-none" data-for="url">
                                <label class="bp-form-label">URL</label>
                                <input class="bp-form-control" name="value_url" placeholder="https://… or /path">
                            </div>
                            <div class="col-12 add-val d-none" data-for="widget">
                                <label class="bp-form-label">Widget</label>
                                <select class="bp-form-select w-100" name="value_widget">
                                    @foreach ($widgets as $w)
                                        <option value="{{ $w }}">{{ ucfirst($w) }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12 add-val d-none" data-for="categories_dropdown">
                                <label class="bp-form-label">Categories to show</label>
                                <input class="bp-form-control" type="number" min="1" name="value_limit" value="10">
                            </div>

                            <div class="col-6">
                                <label class="bp-form-label">Visibility</label>
                                <select class="bp-form-select w-100" name="visibility">
                                    <option value="all">Everyone</option>
                                    <option value="guest">Guests only</option>
                                    <option value="auth">Logged-in only</option>
                                </select>
                            </div>
                            <div class="col-6">
                                <label class="bp-form-label">Icon</label>
                                <input class="bp-form-control" name="icon" placeholder="fas fa-home">
                            </div>

                            {{-- resolved value + settings injected on submit --}}
                            <input type="hidden" name="value" id="addItemValue">
                            <input type="hidden" name="settings[limit]" id="addItemLimit" disabled>

                            <div class="col-12">
                                <button type="submit" class="bp-btn bp-btn-primary w-100 justify-content-center"><i
                                        class="fa-solid fa-plus me-1"></i>Add to menu</button>
                            </div>
                        </form>
                    @endbpCan
                    <p class="fs-12 text-muted mt-3 mb-0"><i class="fa-solid fa-circle-info me-1"></i>Drag items by the
                        handle to reorder or nest them. Order saves automatically.</p>
                </div>
            </div>
        </div>

        {{-- Right: Menu structure --}}
        <div class="col-lg-8">
            <div class="bp-card">
                <div class="bp-card-header">
                    <h5 class="bp-card-title"><i class="fa-solid fa-list me-2"></i>Menu Structure</h5>
                    <span class="fs-12 text-muted">Drag to reorder &amp; nest</span>
                </div>
                <div class="bp-card-body">
                    @if ($menu->rootItems->isEmpty())
                        <div class="text-center text-muted py-5">
                            <i class="fa-solid fa-bars fa-2x mb-2 d-block"></i>
                            This menu is empty — add items on the left, or
                            @bpCan('ecommerce.edit')
                                <form action="{{ route('ecommerce.menus.reset', $menu) }}" method="POST" class="d-inline">
                                    @csrf<button class="bp-btn bp-btn-sm bp-btn-link p-0">load the defaults</button></form>
                            @endbpCan.
                        </div>
                    @else
                        @php
                            // Flatten the tree depth-first; the builder shows a single
                            // list and encodes nesting via each row's data-depth.
                            $flat = [];
                            $flatten = function ($items, $depth) use (&$flatten, &$flat) {
                                foreach ($items as $it) {
                                    $flat[] = [$it, $depth];
                                    if ($it->recursiveChildren->isNotEmpty()) {
                                        $flatten($it->recursiveChildren, $depth + 1);
                                    }
                                }
                            };
                            $flatten($menu->rootItems, 0);
                        @endphp
                        <ol class="menu-tree" data-menu-id="{{ $menu->id }}"
                            data-reorder-url="{{ route('ecommerce.menus.reorder', $menu) }}">
                            @foreach ($flat as [$node, $depth])
                                @include('ecommerce::menus._node', ['node' => $node, 'depth' => $depth])
                            @endforeach
                        </ol>
                        <p class="fs-12 text-muted mt-2 mb-0"><i class="fa-solid fa-arrows-left-right me-1"></i>Drag
                            <strong>right</strong> to make an item a sub-menu, <strong>left</strong> to move it back out.
                        </p>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection

@push('styles')
    <link rel="stylesheet" href="{{ asset('vendor/fontawesome-iconpicker/fontawesome-iconpicker.min.css') }}">
    <style>
        .menu-tree {
            list-style: none;
            margin: 0;
            padding: 0;
        }

        .menu-node {
            margin-bottom: 6px;
            transition: margin-left .12s ease;
        }

        .menu-node-row {
            display: flex;
            align-items: center;
            gap: 8px;
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 8px 12px;
        }

        /* depth indicator: a coloured rail on the left of nested rows */
        .menu-node[data-depth="1"] .menu-node-row,
        .menu-node[data-depth="2"] .menu-node-row,
        .menu-node[data-depth="3"] .menu-node-row {
            border-left: 3px solid var(--bp-primary, #1B4F72);
        }

        .menu-node.is-inactive .menu-node-row {
            opacity: .6;
        }

        .menu-node-handle {
            cursor: grab;
            color: #98a2b3;
        }

        .menu-node-label {
            font-weight: 600;
        }

        .menu-node-actions {
            margin-left: auto;
            display: flex;
            gap: 4px;
        }

        .menu-node-edit {
            background: #f8fafc;
            border: 1px solid #e5e7eb;
            border-top: 0;
            border-radius: 0 0 8px 8px;
            padding: 12px;
            margin-top: -2px;
        }

        .sortable-ghost {
            opacity: .35;
        }

        .sortable-ghost .menu-node-row {
            border-style: dashed;
        }

        [data-theme="dark"] .menu-node-row {
            background: var(--bp-surface-dark, #23272f);
            border-color: var(--bp-border-dark, #3a3f4b);
        }

        [data-theme="dark"] .menu-node-edit {
            background: var(--bp-bg-dark, #1a1d23);
            border-color: var(--bp-border-dark, #3a3f4b);
        }
    </style>
@endpush

@push('scripts')
    <script src="{{ asset('vendor/sortablejs/Sortable.min.js') }}"></script>
    <script src="{{ asset('vendor/fontawesome-iconpicker/fontawesome-iconpicker.min.js') }}"></script>
    <script>
        'use strict';
        $(function() {
            // ── FontAwesome icon picker on every icon field (add form + inline edits) ──
            if ($.fn.iconpicker) {
                $('input[name="icon"]').iconpicker({
                    placement: 'bottomLeft',
                    hideOnSelect: true,
                    showFooter: false,
                    animation: false
                });
            }

            // ── Add-item form: surface the right value field per type, then copy it
            //    into the hidden `value` (and toggle the settings[limit] field). ──
            var $type = $('#addItemType');

            function syncAddFields() {
                var t = $type.val();
                $('.add-val').addClass('d-none');
                $('.add-val[data-for="' + t + '"]').removeClass('d-none');
                $('#addItemLimit').prop('disabled', t !== 'categories_dropdown');
            }
            $type.on('change', syncAddFields);
            syncAddFields();

            $('#addItemForm').on('submit', function() {
                var t = $type.val();
                var val = '';
                if (t === 'route') {
                    val = $('select[name="value_route"]').val();
                } else if (t === 'category') {
                    val = $('select[name="value_category"]').val();
                } else if (t === 'page') {
                    val = $('select[name="value_page"]').val();
                } else if (t === 'url') {
                    val = $('input[name="value_url"]').val();
                } else if (t === 'widget') {
                    val = $('select[name="value_widget"]').val();
                }
                $('#addItemValue').val(val);
                if (t === 'categories_dropdown') {
                    $('#addItemLimit').prop('disabled', false).val($('input[name="value_limit"]').val() ||
                        10);
                }
            });

            // ── Inline edit toggle ──
            $(document).on('click', '.menu-node-edit-btn', function() {
                $(this).closest('.menu-node').children('.menu-node-edit').toggleClass('d-none');
            });

            // ── Show/hide (active) toggle ──
            $(document).on('click', '.menu-toggle', function() {
                var $btn = $(this);
                $.post($btn.data('url')).done(function(res) {
                    var $node = $btn.closest('.menu-node');
                    $node.toggleClass('is-inactive', !res.is_active);
                    $btn.find('i').toggleClass('fa-eye', res.is_active).toggleClass('fa-eye-slash',
                        !res.is_active);
                });
            });

            // ── Flat list + horizontal-indent nesting (WordPress-style) ──
            // One list; nesting is encoded per row via data-depth. Drag right to
            // indent (becomes a child of the row above), drag left to outdent. The
            // dragged item's subtree follows it. A row can be at most one level
            // deeper than the row above it, so dropping at the top is always root.
            var $tree = $('.menu-tree');
            if ($tree.length) {
                var reorderUrl = $tree.data('reorder-url');
                var INDENT = 28; // px of horizontal drag per nesting level
                var dragStartX = 0,
                    dragLastX = 0,
                    movingGroup = [];

                function depthOf(li) {
                    return parseInt(li.getAttribute('data-depth'), 10) || 0;
                }

                function applyIndent() {
                    $tree.children('.menu-node').each(function() {
                        this.style.marginLeft = (depthOf(this) * INDENT) + 'px';
                    });
                }
                // Keep every row at most one level deeper than the row above it.
                function normalizeDepths() {
                    var prevDepth = -1;
                    $tree.children('.menu-node').each(function() {
                        var d = Math.min(depthOf(this), prevDepth + 1);
                        if (d < 0) d = 0;
                        this.setAttribute('data-depth', d);
                        prevDepth = d;
                    });
                }

                function persist() {
                    var stack = [],
                        roots = [];
                    $tree.children('.menu-node').each(function() {
                        var d = depthOf(this),
                            node = {
                                id: $(this).data('id'),
                                children: []
                            };
                        if (d === 0 || !stack[d - 1]) {
                            roots.push(node);
                        } else {
                            stack[d - 1].children.push(node);
                        }
                        stack[d] = node;
                        stack.length = d + 1;
                    });
                    $.ajax({
                        url: reorderUrl,
                        method: 'POST',
                        data: {
                            tree: roots
                        }
                    });
                }
                // Following rows that are deeper than `li` = its subtree.
                function subtreeOf(li) {
                    var group = [],
                        d = depthOf(li),
                        n = li.nextElementSibling;
                    while (n && depthOf(n) > d) {
                        group.push(n);
                        n = n.nextElementSibling;
                    }
                    return group;
                }
                // Change a row's depth in place, carrying its subtree, then save.
                function setDepth(li, newDepth) {
                    var oldDepth = depthOf(li);
                    if (newDepth < 0) newDepth = 0;
                    if (newDepth !== oldDepth) {
                        var group = subtreeOf(li),
                            delta = newDepth - oldDepth; // capture BEFORE changing
                        li.setAttribute('data-depth', newDepth);
                        group.forEach(function(c) {
                            c.setAttribute('data-depth', Math.max(0, depthOf(c) + delta));
                        });
                    }
                    normalizeDepths();
                    applyIndent();
                    persist();
                }

                applyIndent();

                // Track pointer X for the whole drag. forceFallback makes Sortable use
                // mouse/touch events, so this fires even for a pure horizontal drag
                // that doesn't reorder anything (which is how you outdent in place).
                $(document).on('mousedown.menudrag touchstart.menudrag', '.menu-node-handle', function(e) {
                    dragStartX = dragLastX = e.type === 'touchstart' ? e.originalEvent.touches[0].clientX :
                        e.clientX;
                });

                function trackMove(e) {
                    dragLastX = (e.touches && e.touches[0]) ? e.touches[0].clientX : e.clientX;
                }

                new Sortable($tree[0], {
                    handle: '.menu-node-handle',
                    draggable: '.menu-node',
                    animation: 150,
                    forceFallback: true,
                    fallbackTolerance: 3,
                    onStart: function(evt) {
                        movingGroup = subtreeOf(evt.item);
                        document.addEventListener('mousemove', trackMove);
                        document.addEventListener('touchmove', trackMove, {
                            passive: true
                        });
                    },
                    onEnd: function(evt) {
                        document.removeEventListener('mousemove', trackMove);
                        document.removeEventListener('touchmove', trackMove);
                        var li = evt.item,
                            oldDepth = depthOf(li);
                        var prev = li.previousElementSibling;
                        var maxDepth = prev ? depthOf(prev) + 1 : 0;
                        var newDepth = Math.min(Math.max(oldDepth + Math.round((dragLastX -
                            dragStartX) / INDENT), 0), maxDepth);
                        var delta = newDepth - oldDepth;
                        li.setAttribute('data-depth', newDepth);
                        var anchor = li;
                        movingGroup.forEach(function(child) {
                            anchor.after(child);
                            child.setAttribute('data-depth', Math.max(0, depthOf(child) +
                                delta));
                            anchor = child;
                        });
                        normalizeDepths();
                        applyIndent();
                        persist();
                    }
                });

                // Explicit indent / outdent — guaranteed "in place" control.
                $(document).on('click', '.menu-outdent', function() {
                    var li = $(this).closest('.menu-node')[0];
                    setDepth(li, depthOf(li) - 1);
                });
                $(document).on('click', '.menu-indent', function() {
                    var li = $(this).closest('.menu-node')[0];
                    var prev = li.previousElementSibling;
                    var maxDepth = prev ? depthOf(prev) + 1 : 0;
                    setDepth(li, Math.min(depthOf(li) + 1, maxDepth));
                });
            }
        });
    </script>
@endpush
