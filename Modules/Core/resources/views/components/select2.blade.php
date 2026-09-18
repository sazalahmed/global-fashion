{{--
  Searchable <select> powered by the global Select2 initializer (targets the
  `.select2-search` class — see layouts/master.blade.php). Renders a leading
  blank/placeholder option, then either a supplied slot of <option>s (full
  control over labels) or a simple :options map.

  Usage — slot (custom option labels, e.g. "Name (phone)"):
    <x-core::select2 name="customer_id" placeholder="Select Customer" required>
      @foreach($customers as $c)
        <option value="{{ $c->id }}" @selected(old('customer_id') == $c->id)>{{ $c->name }} ({{ $c->phone }})</option>
      @endforeach
    </x-core::select2>

  Usage — options map:
    <x-core::select2 name="status" :options="['active' => 'Active', 'inactive' => 'Inactive']" :selected="old('status')" />

  Props:
    name        — select name (required)
    id          — element id (defaults to name)
    placeholder — blank-option text; also drives the Select2 placeholder
    options     — [value => label] map (ignored when a slot is provided)
    selected    — pre-selected value (options-map mode)
    required    — adds the required attribute
    blank       — render the leading blank/placeholder option (default true)

  Any extra attributes (data-*, class, etc.) pass through to the <select>.
--}}
@props([
    'name',
    'id' => null,
    'placeholder' => 'Select...',
    'options' => [],
    'selected' => null,
    'required' => false,
    'blank' => true,
])

<select {{ $attributes->merge(['class' => 'bp-form-select w-100 select2-search']) }}
        name="{{ $name }}" id="{{ $id ?? $name }}" @required($required)>
    @if($blank)
        <option value="">{{ $placeholder }}</option>
    @endif
    @if(trim($slot) !== '')
        {{ $slot }}
    @else
        @foreach($options as $value => $label)
            <option value="{{ $value }}" @selected((string) $selected === (string) $value)>{{ $label }}</option>
        @endforeach
    @endif
</select>
