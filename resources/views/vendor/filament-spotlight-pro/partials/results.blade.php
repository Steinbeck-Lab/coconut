@php
    use pxlrbt\FilamentSpotlightPro\SpotlightResults\SpotlightResult;
@endphp
<ul
    class="spotlight-results max-h-80 transform-gpu scroll-py-10 scroll-pb-2 space-y-4 overflow-y-auto p-4 pb-2"
    id="options"
    role="listbox"
    wire:key="spotlight-results"
>
    @php
        $i = 0;
    @endphp

    @foreach($this->groupedResults as $name => $group)
        <li class="spotlight-results__group" wire:key="spotlight-group-{{ $name }}">
            @if (filled($name))
                <h2 class="spotlight-results__group-title text-xs font-semibold text-gray-900 dark:text-gray-100">
                    {{ $name }}
                </h2>
            @endif

            <ul class="spotlight-results__list -mx-4 mt-2 px-2">
                @php
                    /** @var SpotlightResult $result */
                @endphp

                @foreach($group as $result)
                    @php
                        $action = $result?->action ?? null;
                        $url = $result?->url ?? null;
                    @endphp
                    <li
                        class="spotlight-results__item rounded"
                        :class="{'spotlight-results__item--active bg-black/5 dark:bg-white/5': activeItem === {{ $i }}}"
                        wire:key="spotlight-item-{{ $name }}-{{$result->label}}"
                        x-data="@Js($result->toAlpineData())"
                        x-on:mouseenter="handleMouseEnter({{ $i }})"
                        x-on:click="select"
                    >
                        <button
                            id="option-{{ $i }}"
                            class="spotlight-results__button w-full"
                            role="option"
                            tabindex="-1"
                            wire:loading.attr="disabled"
                            @if ($handler = $action?->getLivewireClickHandler())
                                wire:click.stop="{{ $handler }}"
                            @elseif (filled($url))
                                x-on:click.stop="window.location = '{{ $url }}'"
                            @elseif ($handler = $action?->getAlpineClickHandler())
                                x-on:click.stop="{{ $handler }}"
                            @endif
                        >
                            {{ $result }}
                        </button>
                    </li>

                    @php
                        $i++;
                    @endphp
                @endforeach
            </ul>
        </li>
    @endforeach
</ul>
