@props([
    'name' => '',
    'options' => [],
    'value' => null,
    'ariaLabel' => null,
    'idPrefix' => 'oval-radio-'.Str::random(6),
    'size' => null,
    'required' => false,
])

@php
    $selected = (string) ($value ?? old($name, ''));
    $label = $ariaLabel ?: Str::headline($name);
@endphp

@once
    <style>
        .oval-radio-group {
            display: flex;
            flex-wrap: wrap;
            gap: .5rem;
        }
        .oval-radio-input {
            position: absolute !important;
            opacity: 0 !important;
            width: 0 !important;
            height: 0 !important;
            pointer-events: none !important;
        }
        .oval-card {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 9999px;
            padding: .5rem 1rem;
            border: 1px solid #ced4da;
            background-color: #fff;
            color: #212529;
            box-shadow: 0 0 0 rgba(0,0,0,0);
            transition: background-color .18s ease, color .18s ease, box-shadow .18s ease, border-color .18s ease, transform .12s ease;
            user-select: none;
            cursor: pointer;
            min-width: 110px;
            text-align: center;
            font-weight: 500;
        }
        .oval-card-sm {
            padding: .35rem .75rem;
            font-size: .875rem;
            min-width: 96px;
        }
        .oval-card:hover {
            background-color: #f8f9fa;
            border-color: #b8c2cc;
        }
        .oval-radio-input:focus + .oval-card {
            outline: 0;
            box-shadow: 0 0 0 .2rem rgba(13,110,253,.25);
            border-color: #0d6efd;
        }
        .oval-radio-input:checked + .oval-card {
            background-color: #0d6efd;
            color: #fff;
            border-color: #0d6efd;
            box-shadow: 0 6px 14px -6px rgba(13,110,253,.45);
        }
        .oval-card:active {
            transform: translateY(1px);
        }
        @media (max-width: 576px) {
            .oval-card {
                flex: 1 1 auto;
                min-width: 46%;
            }
        }
        @media (min-width: 577px) and (max-width: 992px) {
            .oval-card {
                min-width: 140px;
            }
        }
    </style>

    <script>
        document.addEventListener('change', function (e) {
            const input = e.target;
            if (!input.classList.contains('oval-radio-input')) return;
            const group = input.closest('.oval-radio-group');
            if (!group) return;
            const name = input.getAttribute('name');
            const radios = group.querySelectorAll('input[type="radio"][name="'+name+'"]');
            radios.forEach(function (r) {
                const label = r.nextElementSibling;
                if (label && label.getAttribute('role') === 'radio') {
                    label.setAttribute('aria-checked', r.checked ? 'true' : 'false');
                }
            });
        });
    </script>
@endonce

<div class="oval-radio-group" role="radiogroup" aria-label="{{ $label }}">
    @foreach($options as $opt)
        @php
            $optValue = (string) ($opt['value'] ?? $opt);
            $optLabel = (string) ($opt['label'] ?? $optValue);
            $id = $idPrefix.'-'.Str::slug($optLabel === '' ? 'all' : $optLabel, '-');
            $isChecked = $optValue === $selected;
        @endphp
        <input
            class="oval-radio-input"
            type="radio"
            id="{{ $id }}"
            name="{{ $name }}"
            value="{{ $optValue }}"
            @if($isChecked) checked @endif
            @if($required) required @endif
        >
        <label
            class="oval-card{{ $size === 'sm' ? ' oval-card-sm' : '' }}"
            for="{{ $id }}"
            role="radio"
            aria-checked="{{ $isChecked ? 'true' : 'false' }}"
        >
            {{ $optLabel === '' ? 'All' : $optLabel }}
        </label>
    @endforeach
</div>
