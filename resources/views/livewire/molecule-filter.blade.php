<div x-data="{
    isOpen: true,
    type: 'substructure',
    smiles: '',
    currentSmiles: '',
    draggedFile: null,
    loadSmilesIntoEditor() {
        try {
            window.editor.setSmiles(this.currentSmiles);
        } catch(e) {
            console.error('Invalid SMILES:', e);
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
        <div class="w-full bg-white max-w-7xl mx-auto">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Left Column: Editor and SMILES Input -->
                <div class="my-4">
                    <div id="structureSearchEditor" class="border border-gray-200 rounded-lg mb-3" style="height: 400px; width: 100%"></div>
                    
                    <!-- SMILES input field -->
                    <div class="mb-3">
                        <label for="smiles-string" class="block text-sm font-medium text-gray-700">SMILES String</label>
                        <div class="mt-1 flex rounded-lg overflow-hidden border border-gray-200">
                            <input type="text" 
                                   id="smiles-string" 
                                   x-model="currentSmiles" 
                                   class="block w-full border-0 px-3 py-2 focus:ring-0 sm:text-sm" 
                                   placeholder="Enter SMILES string to load into the Editor">
                            <button 
                                type="button"
                                @click.stop="loadSmilesIntoEditor()"
                                class="inline-flex items-center border-l border-gray-200 bg-gray-50 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 focus:outline-none">
                                Load
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Right Column: File Upload, Search Options -->
                <div>
                    <div class="py-3">
                        <!-- File Upload Area -->
                        <div x-on:dragover.prevent="draggedFile = null"
                            x-on:drop.prevent="draggedFile = $event.dataTransfer.files[0]; handleFileUpload($event)"
                            style="border: 2px dashed #d1d5db; border-radius: 0.5rem;"
                            class="p-6 text-center mb-3 hover:border-gray-400 transition-colors cursor-pointer">
                            <input type="file" accept=".mol,.sdf" class="hidden" id="fileUpload"
                                x-on:change="handleFileUpload($event)" />
                            <label for="fileUpload" class="cursor-pointer">
                                <p class="text-gray-600">Drag and drop .mol or .sdf files here</p>
                                <p class="text-sm text-gray-500 mt-2">Or click to select a file</p>
                            </label>
                        </div>

                        <!-- Paste from Clipboard -->
                        <button @click="fetchClipboardText" type="button"
                            class="w-full text-center rounded-lg shadow-sm p-4 text-sm font-medium text-gray-700 bg-white border border-gray-200 hover:bg-gray-50 hover:border-gray-300 focus:outline-none transition-colors">
                            Paste from Clipboard
                        </button>
                    </div>

                    <!-- Search Type Selection -->
                    <fieldset class="mt-1">
                        <legend class="text-sm font-medium text-gray-700 mb-2">Search type</legend>
                        <div class="mt-3 flex gap-2">
                            <button type="button" @click="type = 'exact'"
                                x-bind:style="type === 'exact' ? 'background-color: #111827; color: white; border-color: #111827;' : ''"
                                class="flex-1 cursor-pointer rounded-lg border border-gray-200 bg-white text-gray-700 p-4 text-sm font-medium transition-colors hover:bg-gray-50">
                                Exact
                            </button>
                            <button type="button" @click="type = 'substructure'"
                                x-bind:style="type === 'substructure' ? 'background-color: #111827; color: white; border-color: #111827;' : ''"
                                class="flex-1 cursor-pointer rounded-lg border border-gray-200 bg-white text-gray-700 p-4 text-sm font-medium transition-colors hover:bg-gray-50">
                                Substructure
                            </button>
                            <button type="button" @click="type = 'similarity'"
                                x-bind:style="type === 'similarity' ? 'background-color: #111827; color: white; border-color: #111827;' : ''"
                                class="flex-1 cursor-pointer rounded-lg border border-gray-200 bg-white text-gray-700 p-4 text-sm font-medium transition-colors hover:bg-gray-50">
                                Similarity
                            </button>
                        </div>
                    </fieldset>

                    <!-- Search Button -->
                    <div class="mt-5 sm:mt-4">
                        <button
                            @click="const smiles = window.getEditorSmiles(); window.location.href = `${window.location.pathname}?tableFilters[structure][type]=${type}&tableFilters[structure][smiles]=${encodeURIComponent(smiles)}`"
                            type="button"
                            class="w-full cursor-pointer rounded-lg p-4 text-sm font-medium text-white focus:outline-none transition-colors mt-2"
                            style="background-color: #111827;"
                            onmouseover="this.style.backgroundColor='#1f2937'"
                            onmouseout="this.style.backgroundColor='#111827'">
                            Search
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
