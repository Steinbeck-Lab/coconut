<div class="max-w-4xl lg:max-w-7xl mx-auto">
    @if($molecules)
    <div class="px-4 py-8">
        <div class="mx-6 border-gray-200 py-5 sm:flex sm:items-center sm:justify-between">
            <h2 class="text-2xl pb-0 font-bold tracking-tight text-white sm:text-2xl">
                <span class="-mb-1 block text-primary-dark bg-clip-text">Recent entries</span>
            </h2>
            <div class="mt-3 sm:ml-4 sm:mt-0">
                <a href="/search?sort=latest" type="button"
                    class="inline-flex items-center rounded-md bg-secondary-dark px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-secondary-light focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-secondary-light">
                    View all
                </a>
            </div>
        </div>
        <div>
            <div class="px-6">
                <div class="mx-auto grid mt-6 gap-5 lg:max-w-none md:grid-cols-3 lg:grid-cols-5 grid-cols-1">
                    @foreach ($molecules as $molecule)
                        <livewire:molecule-card :key="$molecule->identifier" :molecule="$molecule" />
                    @endforeach
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
