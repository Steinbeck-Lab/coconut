@php
    use Illuminate\Contracts\Support\Htmlable;
    use Filament\Support\Contracts\ScalableIcon;
    use Filament\Support\Enums\IconSize;
@endphp

<div class="spotlight-result w-full flex items-center  min-h-10 p-2 gap-3 text-left">
    @if (filled($icon) || filled($image))
        <div class="spotlight-result__icon flex-none opacity-80">
            @if ($image)
                <img src="{{ $image }}" @class(['spotlight-result__icon-image size-6', 'rounded-full' => $imageRounded]) alt="">
            @else
                @if ($icon instanceof Htmlable)
                    {!! $icon !!}
                @elseif($icon instanceof ScalableIcon)
                    @svg($icon->getIconForSize(IconSize::Medium), 'spotlight-result__icon-image size-6 text-current')
                @else
                    @svg($icon, 'spotlight-result__icon-image size-6 text-current')
                @endif
            @endif
        </div>
    @endif

    <div class="spotlight-result__label flex-1 truncate">
        {{ $label }}
    </div>

    @if (filled($aliases))
        <x-filament::badge size="sm" color="gray">
            {{ $aliases[0] }}
        </x-filament::badge>
    @endif

    @if ($badge)
        <x-filament::badge class="ml-auto" size="sm" :color="$badgeColor">
            {{ $badge }}
        </x-filament::badge>
    @endif
</div>
