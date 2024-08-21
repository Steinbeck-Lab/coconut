<div class="mt-4" x-data="{ src: '' }">
    <div class="w-full max-w-xl">
        <div class="flex items-center justify-between" x-data="{ on: false }">
            <span class="flex flex-grow flex-col">
                <span class="text-sm font-medium leading-6 text-gray-900" id="availability-label">3D Structure</span>
                <span class="text-sm text-gray-500" id="availability-description">Interactive JSMol molecular viewer
                </span>
            </span>
            <button @click="src = '{{ $this->source }}'"
                :class="{ 'bg-gray-600': src=='', 'bg-green-600': src!='' }" type="button"
                class="bg-gray-200 relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-indigo-600 focus:ring-offset-2"
                role="switch" aria-checked="false" x-ref="switch" x-state:on="Enabled" x-state:off="Not Enabled"
                :class="{ 'bg-indigo-600': on, 'bg-gray-200': !(on) }" aria-labelledby="availability-label"
                aria-describedby="availability-description" :aria-checked="on.toString()" @click="on = !on">
                <span :class="{ 'translate-x-0': src=='', 'translate-x-5': src!='' }" aria-hidden="true"
                    class="translate-x-0 pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"
                    x-state:on="Enabled" x-state:off="Not Enabled"
                    :class="{ 'translate-x-5': on, 'translate-x-0': !(on) }"></span>
            </button>
        </div>
    </div>
    <div class="border aspect-h-2 aspect-w-3 overflow-hidden rounded-lg mb-2 mt-2" x-show="src">
        <iframe x-bind:src="src" width="{{ $this->width }}" height="{{ $this->height }}"
            frameborder="0"></iframe>
    </div>
</div>
