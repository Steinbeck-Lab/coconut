<div>
    <div class="mt-24">
        <div class="mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8">
            <h1 class="text-3xl font-bold tracking-tight text-gray-900">Workspace sale</h1>
            <p class="mt-4 max-w-xl text-sm text-gray-700">Our thoughtfully designed workspace objects are crafted in
                limited runs. Improve your productivity and organization with these sale items before we run out.</p>
        </div>
    </div>
    <div class="mx-auto max-w-2xl px-4 py-8 sm:px-6 sm:py-8 lg:max-w-7xl lg:px-8">
        <div class="grid grid-cols-1 gap-y-4 sm:grid-cols-2 sm:gap-x-6 sm:gap-y-10 lg:grid-cols-4 lg:gap-x-8">
            @foreach ($molecules as $molecule)
                <div class="rounded-lg hover:shadow-lg shadow border">
                    <livewire:molecule-card :molecule="json_encode($molecule)" />
                </div>
            @endforeach
        </div>
    </div>
</div>
