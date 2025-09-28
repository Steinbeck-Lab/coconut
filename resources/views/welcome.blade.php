<x-guest-layout>
    @section("title", "Welcome")
    <div class="@if(config('app.env') !== 'production') mt-32 @else mt-20 @endif">
        <livewire:welcome>
    </div>
</x-guest-layout>
