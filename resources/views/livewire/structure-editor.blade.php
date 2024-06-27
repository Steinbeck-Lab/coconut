<div x-data="{ isOpen: @entangle('isOpen').live, smiles: @entangle('smiles').live, searchType: 'exact' }" x-init="$watch('isOpen', value => {
    if (value) {
        setTimeout(() => {
            const editor = OCL.StructureEditor.createSVGEditor('structureSearchEditor', 1);
            if (smiles) {
            console.log(smiles)
                editor.setSmiles(smiles);
            }
            window.getEditorSmiles = () => editor.getSmiles();
        }, 100);
    }
});">
    <div x-show="isOpen" x-cloak class="fixed z-10 inset-0 overflow-y-auto" aria-labelledby="modal-title" role="dialog"
        aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>

            <!-- This element is to trick the browser into centering the modal contents. -->
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            <div
                class="inline-block align-bottom bg-white rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full sm:p-6">
                <div class="sm:flex sm:items-start">
                    <div class="mt-3 text-center sm:mt-0 sm:text-left w-full">
                        <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                            Structure Editor
                        </h3>
                        <div class="py-3">
                            <div id="structureSearchEditor" class="border" style="height: 400px; width: 100%"></div>
                        </div>
                        <fieldset class="mt-1">
                            <legend class="contents text-base font-medium text-gray-900">
                                Select search type
                            </legend>
                            <div class="mt-4 space-y-4">
                                <div class="flex items-center">
                                    <label for="search-type-exact"
                                        class="block cursor-pointer text-sm font-medium text-gray-700">
                                        <input id="search-type-exact" name="search-type" x-model="searchType"
                                            value="exact" type="radio"
                                            class="mr-3 h-4 w-4 border-gray-300 text-secondary-dark focus:ring-secondary-dark" />
                                        Exact match
                                    </label>
                                </div>
                                <div class="flex items-center">
                                    <label for="search-type-sub"
                                        class="block cursor-pointer text-sm font-medium text-gray-700">
                                        <input id="search-type-sub" name="search-type" x-model="searchType"
                                            value="substructure" type="radio"
                                            class="mr-3 h-4 w-4 border-gray-300 text-secondary-dark focus:ring-secondary-dark" />
                                        Substructure Search
                                    </label>
                                </div>
                                <div class="flex items-center">
                                    <label for="search-type-similar"
                                        class="block cursor-pointer text-sm font-medium text-gray-700">
                                        <input id="search-type-similar" name="search-type" x-model="searchType"
                                            value="similarity" type="radio"
                                            class="mr-3 h-4 w-4 border-gray-300 text-secondary-dark focus:ring-secondary-dark" />
                                        Similarity Search (tanimoto_threshold=0.5)
                                    </label>
                                </div>
                            </div>
                        </fieldset>
                    </div>
                </div>
                <div class="mt-5 sm:mt-4 sm:flex sm:flex-row-reverse">
                    <button @click="isOpen = false" type="button"
                        class="mt-3 w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                        Close
                    </button>
                    <button
                        @click="Livewire.dispatch('updateSmiles', { smiles: window.getEditorSmiles(), searchType: searchType }); isOpen = false"
                        type="button"
                        class="mt-3 w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                        Search
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
