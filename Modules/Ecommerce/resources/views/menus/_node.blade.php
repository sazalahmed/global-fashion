<li class="menu-node {{ $node->is_active ? '' : 'is-inactive' }}" data-id="{{ $node->id }}"
    data-depth="{{ $depth ?? 0 }}">
    <div class="menu-node-row">
        <span class="menu-node-handle" title="Drag to reorder / nest"><i class="fa-solid fa-grip-vertical"></i></span>
        <span class="menu-node-label">{{ $node->label }}</span>
        <span class="bp-badge bp-badge-secondary fs-11">{{ $node->type }}</span>
        @if ($node->visibility !== 'all')
            <span class="bp-badge bp-badge-info fs-11">{{ $node->visibility }}</span>
        @endif
        @unless ($node->is_active)
            <span class="bp-badge bp-badge-warning fs-11">hidden</span>
        @endunless
        <span class="menu-node-actions">
            @bpCan('ecommerce.edit')
                <button type="button" class="bp-btn bp-btn-sm bp-btn-outline menu-outdent"
                    title="Move out to main level"><i class="fa-solid fa-angle-left"></i></button>
                <button type="button" class="bp-btn bp-btn-sm bp-btn-outline menu-indent" title="Make a sub-menu"><i
                        class="fa-solid fa-angle-right"></i></button>
                <button type="button" class="bp-btn bp-btn-sm bp-btn-success menu-node-edit-btn" title="Edit"><i
                        class="fa-solid fa-pen"></i></button>
                <button type="button" class="bp-btn bp-btn-sm bp-btn-warning menu-toggle"
                    data-url="{{ route('ecommerce.menus.items.toggle', $node) }}" title="Show / hide">
                    <i class="fa-solid {{ $node->is_active ? 'fa-eye' : 'fa-eye-slash' }}"></i>
                </button>
            @endbpCan
            @bpCan('ecommerce.delete')
                <form action="{{ route('ecommerce.menus.items.destroy', $node) }}" method="POST" class="d-inline"
                    onsubmit="return confirm('Delete this item and all of its sub-items?');">
                    @csrf @method('DELETE')
                    <button type="submit" class="bp-btn bp-btn-sm bp-btn-danger" title="Delete"><i
                            class="fa-solid fa-trash"></i></button>
                </form>
            @endbpCan
        </span>
    </div>

    {{-- Inline edit form --}}
    @bpCan('ecommerce.edit')
        <div class="menu-node-edit d-none">
            <form action="{{ route('ecommerce.menus.items.update', $node) }}" method="POST" class="row g-2">
                @csrf @method('PUT')
                <input type="hidden" name="type" value="{{ $node->type }}">

                <div class="col-md-4">
                    <label class="bp-form-label">Label *</label>
                    <input class="bp-form-control" name="label" value="{{ $node->label }}" required>
                </div>

                @switch($node->type)
                    @case('route')
                        <div class="col-md-4">
                            <label class="bp-form-label">Route</label>
                            <select class="bp-form-select w-100" name="value">
                                @foreach ($routes as $name => $opt)
                                    <option value="{{ $name }}" {{ $node->value === $name ? 'selected' : '' }}>
                                        {{ $opt['label'] }} — {{ $opt['path'] }}</option>
                                @endforeach
                            </select>
                        </div>
                    @break

                    @case('category')
                        <div class="col-md-4">
                            <label class="bp-form-label">Category</label>
                            <select class="bp-form-select w-100" name="value">
                                @foreach ($categories as $c)
                                    <option value="{{ $c->id }}"
                                        {{ (string) $node->value === (string) $c->id ? 'selected' : '' }}>{{ $c->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    @break

                    @case('url')
                        <div class="col-md-4">
                            <label class="bp-form-label">URL</label>
                            <input class="bp-form-control" name="value" value="{{ $node->value }}"
                                placeholder="https://… or /path">
                        </div>
                    @break

                    @case('widget')
                        <div class="col-md-4">
                            <label class="bp-form-label">Widget</label>
                            <select class="bp-form-select w-100" name="value">
                                @foreach ($widgets as $w)
                                    <option value="{{ $w }}" {{ $node->value === $w ? 'selected' : '' }}>
                                        {{ ucfirst($w) }}</option>
                                @endforeach
                            </select>
                        </div>
                    @break

                    @case('categories_dropdown')
                        <div class="col-md-4">
                            <label class="bp-form-label">Categories to show</label>
                            <input class="bp-form-control" type="number" min="1" name="settings[limit]"
                                value="{{ $node->settings['limit'] ?? 10 }}">
                        </div>
                    @break
                @endswitch

                <div class="col-md-2">
                    <label class="bp-form-label">Visibility</label>
                    <select class="bp-form-select w-100" name="visibility">
                        <option value="all" {{ $node->visibility === 'all' ? 'selected' : '' }}>Everyone</option>
                        <option value="guest" {{ $node->visibility === 'guest' ? 'selected' : '' }}>Guests only</option>
                        <option value="auth" {{ $node->visibility === 'auth' ? 'selected' : '' }}>Logged-in only</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="bp-form-label">Open in</label>
                    <select class="bp-form-select w-100" name="target">
                        <option value="_self" {{ $node->target === '_self' ? 'selected' : '' }}>Same tab</option>
                        <option value="_blank" {{ $node->target === '_blank' ? 'selected' : '' }}>New tab</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="bp-form-label">Icon</label>
                    <input class="bp-form-control" name="icon" value="{{ $node->icon }}" placeholder="fas fa-home">
                </div>
                <div class="col-md-2">
                    <label class="bp-form-label">CSS class</label>
                    <input class="bp-form-control" name="css_class" value="{{ $node->css_class }}">
                </div>

                <div class="col-12 text-end">
                    <button type="submit" class="bp-btn bp-btn-sm bp-btn-success"><i
                            class="fa-solid fa-save me-1"></i>Save</button>
                </div>
            </form>
        </div>
    @endbpCan
</li>
