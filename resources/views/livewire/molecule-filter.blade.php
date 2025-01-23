<div x-data="{
    isOpen: true,
    type: 'substructure',
    smiles: '',
    currentSmiles: '', // Added for SMILES input
    draggedFile: null,
    loadSmilesIntoEditor() {
        try {
            window.editor.setSmiles(this.currentSmiles);
        } catch(e) {
            console.error('Invalid SMILES:', e);
            // Revert to last valid SMILES if invalid
            this.currentSmiles = window.editor.getSmiles();
            alert('Invalid SMILES string');
        }
    },
    fetchClipboardText() {
        navigator.clipboard.readText().then(text => {
            window.editor.setSmiles(text);
        }).catch(err => {
            console.error('Failed to read clipboard contents: ', err);
        });
    },
    handleFileUpload(event) {
        const file = event?.target?.files?.[0] || this.draggedFile;
        if (!file) return;

        const allowedExtensions = ['.mol', '.sdf'];
        const fileExtension = '.' + file.name.split('.').pop().toLowerCase();

        if (!allowedExtensions.includes(fileExtension)) {
            alert('Only .mol and .sdf files are allowed');
            return;
        }

        const reader = new FileReader();
        reader.onload = (e) => {
            const fileContent = e.target.result;
            try {
                window.editor.setMolFile(fileContent);
            } catch (err) {
                console.error('Error loading molecule:', err);
                alert('Failed to load molecule file');
            }
        };
        reader.readAsText(file);
    }
}">
    <div x-init="setTimeout(() => {
        if (typeof window.OCL === 'undefined') {
            import('https://unpkg.com/openchemlib/full.js').then(module => {
                window.OCL = module;
                initializeEditor();
            }).catch(err => {
                console.error('Failed to load OCL:', err);
            });
        } else {
            initializeEditor();
        }
    
        function initializeEditor() {
            window.editor = OCL.StructureEditor.createSVGEditor('structureSearchEditor', 1);
            if (smiles) {
                window.editor.setSmiles(smiles);
            }
            window.getEditorSmiles = () => window.editor.getSmiles();
        }
    }, 100);">
        <div class="w-full bg-white rounded-lg shadow-md max-w-7xl mx-auto">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <div class="border rounded-md mb-3">
                        <div id="structureSearchEditor" style="height: 400px; width: 100%"></div>
                    </div>
                    <!-- Added SMILES input field -->
                    <div class="mb-3">
                        <label for="smiles-string" class="block text-sm font-medium text-gray-700">SMILES String</label>
                        <div class="mt-1 flex rounded-md shadow-sm">
                            <input type="text" 
                                   id="smiles-string" 
                                   x-model="currentSmiles" 
                                   class="block w-full rounded-l-md border-r-0 border-gray-300 focus:border-blue-500 focus:ring-blue-500 sm:text-sm" 
                                   placeholder="Enter SMILES string to load into the Editor">
                                                            <button 
                                type="button"
                                @click.stop="loadSmilesIntoEditor()"
                                class="relative -ml-px inline-flex items-center space-x-2 rounded-r-md border border-gray-300 bg-gray-50 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
                                Load
                            </button>
                        </div>
                    </div>
                </div>
                <div>
                    <div class="pb-3">
                        <div x-on:dragover.prevent="draggedFile = null"
                            x-on:drop.prevent="draggedFile = $event.dataTransfer.files[0]; handleFileUpload($event)"
                            class="border border-dashed border-gray-300 p-6 text-center mb-3 rounded-md">
                            <input type="file" accept=".mol,.sdf" class="hidden" id="fileUpload"
                                x-on:change="handleFileUpload($event)" />
                            <label for="fileUpload" class="cursor-pointer">
                                <p class="text-gray-600">Drag and drop .mol or .sdf files here</p>
                                <p class="text-sm text-gray-500 mt-2">Or click to select a file</p>
                            </label>
                        </div>
                        <div @click="fetchClipboardText"
                            class="hover:cursor-pointer w-full text-center rounded-md shadow-sm px-4 py-2 bg-white-600 text-base font-medium hover:bg-white-700 focus:outline-none sm:w-auto sm:text-sm border mb-4">
                            Paste from Clipboard
                        </div>
                    </div>
                    <fieldset class="mt-1">
                        <legend class="contents text-base font-medium text-gray-900">
                            Select search type
                        </legend>
                        <div class="mt-4 space-y-4">
                            <div class="flex items-center">
                                <label for="search-type-exact"
                                    class="block cursor-pointer text-sm font-medium text-gray-700">
                                    <input id="search-type-exact" name="search-type" x-model="type" value="exact"
                                        type="radio"
                                        class="mr-3 h-4 w-4 border-gray-300 text-secondary-dark focus:ring-secondary-dark" />
                                    Exact match
                                </label>
                            </div>
                            <div class="flex items-center">
                                <label for="search-type-sub"
                                    class="block cursor-pointer text-sm font-medium text-gray-700">
                                    <input id="search-type-sub" name="search-type" x-model="type" value="substructure"
                                        type="radio"
                                        class="mr-3 h-4 w-4 border-gray-300 text-secondary-dark focus:ring-secondary-dark" />
                                    Substructure Search
                                </label>
                            </div>
                            <div class="flex items-center">
                                <label for="search-type-similar"
                                    class="block cursor-pointer text-sm font-medium text-gray-700">
                                    <input id="search-type-similar" name="search-type" x-model="type" value="similarity"
                                        type="radio"
                                        class="mr-3 h-4 w-4 border-gray-300 text-secondary-dark focus:ring-secondary-dark" />
                                    Similarity Search (tanimoto_threshold=0.5)
                                </label>
                            </div>
                        </div>
                    </fieldset>
                    <div class="mt-5 sm:mt-4 sm:flex sm:flex-row-reverse">
                        <button
                            @click="const smiles = window.getEditorSmiles(); window.location.href = `${window.location.pathname}?tableFilters[structure][type]=${type}&tableFilters[structure][smiles]=${encodeURIComponent(smiles)}`"
                            type="button"
                            class="hover:cursor-pointer w-full text-center rounded-md shadow-sm px-4 py-2 bg-white-600 text-base font-medium hover:bg-white-700 focus:outline-none sm:w-auto sm:text-sm border my-4">
                            Search
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>