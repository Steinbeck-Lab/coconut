<div style="position: relative" class="z-10">
    @foreach ($banners as $banner)
        @if ($banner->isVisible())
            @php
                $startColor = $banner->start_color;
                $endColor = $banner->background_type === 'gradient' ? $banner->end_color : $startColor;
            @endphp

            <div x-title="banner-component-{{ $banner->id }}" x-cloak x-show="show{{ $banner->id }}"
                x-data="{
                    show{{ $banner->id }}: true,
                    storageKey: 'kenepa-banners::closed',
                    init() {
                        this.checkIfClosed();
                    },
                    close() {
                        this.show{{ $banner->id }} = false;
                        let storedBanners = JSON.parse(localStorage.getItem(this.storageKey) || '[]');
                        if (!storedBanners.includes('{{ $banner->id }}')) {
                            storedBanners.push('{{ $banner->id }}');
                            localStorage.setItem(this.storageKey, JSON.stringify(storedBanners));
                        }
                    },
                    checkIfClosed() {
                        let storedBanners = JSON.parse(localStorage.getItem(this.storageKey) || '[]');
                        if (storedBanners.includes('{{ $banner->id }}')) {
                            this.show{{ $banner->id }} = false;
                        }
                    },
                }"
                style="z-index:1; background-color: {{ $startColor }}; background-image: linear-gradient(to right, {{ $startColor }}, {{ $endColor }}); color: {{ $banner->text_color ?? '#FFFFFF' }};"
                id="banner-{{ $banner->id }}" @class([
                    'grid grid-cols-12 pl-6 py-2 pr-8',
                    'rounded-lg' =>
                        $banner->render_location !==
                        \Filament\View\PanelsRenderHook::BODY_START,
                ])>
                <div class="col-span-12 flex items-center text-sm w-full">
                    @if ($banner->icon)
                        <x-filament::icon alias="banner::icon" :icon="$banner->icon"
                            style="color: {{ $banner->icon_color ?? '#FFFFFF' }}" class="h-6 w-6 mr-2 text-white" />
                    @endif
                    <div class="flex-grow">
                        {!! $banner->content !!}
                    </div>
                    @if ($banner->can_be_closed_by_user)
                        <x-filament::icon class="cursor-pointer" x-on:click="close" alias="banner::close" icon="heroicon-m-x-circle"
                            style="color: {{ $banner->icon_color ?? '#FFFFFF' }}"
                            class="ml-4 h-6 w-6 text-white cursor-pointer hover:opacity-75" />
                    @endif
                </div>
            </div>
        @endif
    @endforeach
</div>
