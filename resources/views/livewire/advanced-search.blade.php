<div x-data="{
    showModal: false,
    isLoading: @entangle('isLoading'),
    schema: @entangle('schema'),
    searchParams: @entangle('searchParams'),
    queryString: '',
    init() {
        // Parse URL and prefill searchParams
        const url = new URL(window.location.href);
        const type = url.searchParams.get('type');
        const query = url.searchParams.get('q');

        if (type === 'filters' && query) {
            this.queryString = decodeURIComponent(query);

            // Parse the query string to populate searchParams
            const parts = this.queryString.split(' ');
            parts.forEach(part => {
                const [key, value] = part.split(':');
                const config = this.schema[key];

                if (config) {
                    if (config.type === 'range' && value.includes('..')) {
                        const [min, max] = value.split('..').map(Number);
                        this.searchParams[key] = { min, max };
                    } else if (config.type === 'select' && value.includes('|')) {
                        this.searchParams[key] = value.split('|');
                    } else if (config.type === 'boolean') {
                        this.searchParams[key] = value === 'true';
                    } else {
                        this.searchParams[key] = value.replace(/\+/g, ' ');
                    }
                }
            });
        }
    },
    updateQueryString() {
        const parts = [];
        for (const key in this.searchParams) {
            const value = this.searchParams[key];
            const config = this.schema[key];
            if (config) {
                if (config.type === 'range' && value?.min !== undefined && value?.max !== undefined) {
                    if (value.min !== config?.range?.min || value.max !== config?.range?.max) {
                        parts.push(`${key}:${value.min}..${value.max}`);
                    }
                } else if (config.type === 'select' && Array.isArray(value)) {
                    if (value.length > 0) {
                        parts.push(`${key}:${value.join('|')}`);
                    }
                } else if (config.type === 'boolean') {
                    if (value !== 'undefined') {
                        parts.push(`${key}=${value}`);
                    }
                } else if (value && value !== config.default) {
                    parts.push(`${key}:${value}`);
                }
            }
        }
        this.queryString = parts.join(' ');
    },
    toggleBooleanParam(key, value) {
        this.searchParams[key] = value;
        this.updateQueryString();
        $wire.call('updateSearchParam', key, this.searchParams[key]);
    },
    performSearch() {
        this.updateQueryString();
        const url = new URL(window.location.href);

        if (this.queryString) {
            // Update the query parameter with the `type=filters&q=` pattern
            url.searchParams.set('type', 'filters');
            url.searchParams.set('q', this.queryString);
        } else {
            // Remove the query parameters if no filters are set
            url.searchParams.delete('type');
            url.searchParams.delete('q');
        }

        // Close the modal
        this.showModal = false;

        // Reload the page with the updated URL
        window.location.href = url.toString();
    }
}" x-init="init()" class="relative">
    <button @click="showModal = true"
        class="border border-1 border-gray-400 text-gray-600 px-4 py-2 rounded-md font-medium focus:outline-none">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"
            class="size-6 inline">
            <path stroke-linecap="round" stroke-linejoin="round"
                d="M10.5 6h9.75M10.5 6a1.5 1.5 0 1 1-3 0m3 0a1.5 1.5 0 1 0-3 0M3.75 6H7.5m3 12h9.75m-9.75 0a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m-3.75 0H7.5m9-6h3.75m-3.75 0a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m-9.75 0h9.75" />
        </svg>&emsp;Advanced Search
    </button>

    <div x-show="showModal" class="fixed inset-0 flex items-center justify-center bg-gray-800 bg-opacity-75"
        x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" style="display: none;">
        <div class="bg-white p-8 rounded-lg shadow-lg max-w-5xl w-full">
            <h2 class="text-lg font-semibold mb-4">Advanced Search</h2>
            <p class="text-gray-600 mb-4">
                Customize your search options here.
            </p>

            <textarea x-model="queryString" readonly
                class="w-full border rounded px-2 py-1 mb-4 bg-gray-100 text-gray-700 h-20 resize-none"></textarea>

            <div x-show="isLoading" class="text-center">
                <p class="text-gray-600">Loading...</p>
            </div>

            <div x-show="!isLoading" class="overflow-y-auto h-[600px] py-4 px-2">
                <div class="grid grid-cols-2 gap-8">
                    <template x-for="(config, key) in schema" :key="key">
                        <div x-show="config.unique_values?.length > 0 || config.type !== 'select'" class="mb-4 px-2">
                            <div x-show="config.type === 'range'">
                                <label x-text="config.label"
                                    class="block text-sm font-medium text-gray-700 mb-2 capitalize"></label>
                                <div class="flex space-x-4">
                                    <input type="number" placeholder="Min" x-model="searchParams[key].min"
                                        :min="config?.range?.min" :max="searchParams[key]?.max - 1"
                                        @input="updateQueryString(); $wire.call('updateSearchParam', key, searchParams[key])"
                                        class="border rounded px-2 py-1 w-full" />
                                    <input type="number" placeholder="Max" x-model="searchParams[key].max"
                                        :min="searchParams[key]?.min + 1" :max="config?.range?.max"
                                        @input="updateQueryString(); $wire.call('updateSearchParam', key, searchParams[key])"
                                        class="border rounded px-2 py-1 w-full" />
                                </div>
                            </div>
                            <div x-show="config.type === 'select'">
                                <label x-text="config.label"
                                    class="block text-sm font-medium text-gray-700 mb-2 capitalize"></label>
                                <select x-model="searchParams[key]" multiple
                                    @change="updateQueryString(); $wire.call('updateSearchParam', key, searchParams[key])"
                                    class="w-full border rounded px-2 py-1">
                                    <template x-for="value in config.unique_values" :key="value">
                                        <option x-text="value" :value="value"></option>
                                    </template>
                                </select>
                            </div>
                            <div x-show="config.type === 'boolean'">
                                <label x-text="config.label"
                                    class="block text-sm font-medium text-gray-700 mb-2 capitalize"></label>
                                <div class="flex space-x-2">
                                    <template x-for="value in config.values" :key="value">
                                        <button x-text="value ? 'Yes' : 'No'" @click="toggleBooleanParam(key, value)"
                                            :class="{
                                                'bg-blue-500 text-white': searchParams[key] === value,
                                                'bg-gray-200': searchParams[key] !== value
                                            }"
                                            class="px-3 py-1 rounded"></button>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            <div class="flex justify-end mt-4">
                <button @click="showModal = false"
                    class="px-4 py-2 bg-red-500 text-white rounded hover:bg-red-600 mr-2">
                    Cancel
                </button>
                <button @click="performSearch()" :disabled="isLoading"
                    class="px-4 py-2 bg-green-500 text-white rounded hover:bg-green-600 disabled:opacity-50">
                    <span x-show="!isLoading">Search</span>
                    <span x-show="isLoading">Searching...</span>
                </button>
            </div>
        </div>
    </div>
</div>
