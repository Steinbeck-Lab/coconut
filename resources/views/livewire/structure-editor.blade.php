<div x-data="{
    isOpen: false,
    mode: @entangle('mode'),
    searchType: 'exact',
    smiles: @entangle('smiles'),
    currentSmiles: '',  // New state for current SMILES
    editorOutput: '',   // New state for editor output
    searchSource: 'editor', // 'smiles' or 'editor'
    type: @entangle('type'),
    draggedFile: null,
    recentSearches: JSON.parse(localStorage.getItem('recentSearches') || '[]'),
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
    },
    performSearch() {
        let smiles = '';
        if (this.searchSource === 'smiles') {
            smiles = this.currentSmiles;
        } else {
            smiles = this.editorOutput;
        }
        const query = { type: this.type, q: smiles };

        this.recentSearches.push(query);
        this.recentSearches = Array.from(
            new Map(this.recentSearches.map(item => [item.q, item])).values()
        );
        localStorage.setItem('recentSearches', JSON.stringify(this.recentSearches));

        window.location.href = `/search?type=${this.type}&q=${encodeURIComponent(smiles)}`;
    },
    deleteSearch(index) {
        this.recentSearches.splice(index, 1);
        localStorage.setItem('recentSearches', JSON.stringify(this.recentSearches));
    },
    loadSearch(search) {
        this.type = search.type;
        window.editor.setSmiles(search.q);
        this.editorOutput = window.getEditorSmiles();
    },
    loadSmilesIntoEditor() {
        try {
            window.editor.setSmiles(this.currentSmiles);
            // Update the editorOutput and also force polling to pick up the change
            if (window.getEditorSmiles) {
                this.editorOutput = window.getEditorSmiles();
            }
        } catch(e) {
            console.error('Invalid SMILES:', e);
            // Revert to last valid SMILES if invalid
            this.currentSmiles = window.editor.getSmiles();
            if (window.getEditorSmiles) {
                this.editorOutput = window.getEditorSmiles();
            }
            alert('Invalid SMILES string');
        }
    },
}">
    <div x-init="
        $watch('isOpen', value => {
            if (value) {
                setTimeout(() => {
                    window.editor = OCL.StructureEditor.createSVGEditor('structureSearchEditor', 1);
                    if (smiles) {
                        window.editor.setSmiles(smiles);
                    }
                    window.getEditorSmiles = () => window.editor.getSmiles();
                    
                    // Always poll for changes every 300ms while modal is open (robust for all editors)
                    if ($data._editorPoll) clearInterval($data._editorPoll);
                    let lastSmiles = window.getEditorSmiles();
                    $data._editorPoll = setInterval(() => {
                        if (!$data.isOpen) { clearInterval($data._editorPoll); return; }
                        const current = window.getEditorSmiles();
                        if (current !== lastSmiles) {
                            $data.editorOutput = current;
                            lastSmiles = current;
                        }
                    }, 300);
                }, 100);
            } else {
                if ($data._editorPoll) clearInterval($data._editorPoll);
            }
        });
    ">
        <div x-show="isOpen" x-cloak class="fixed z-20 inset-0 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-center justify-center min-h-screen text-center px-8">
                <div class="fixed inset-0 bg-gray-900/50 backdrop-blur-sm transition-opacity" aria-hidden="true"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                <div class="z-20 inline-block align-bottom bg-white rounded-xl px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:w-full sm:p-6">
                    <div class="sm:flex sm:items-start">
                        <div class="mt-3 text-center sm:mt-0 sm:text-left w-full">
                            <h3 class="text-lg leading-6 font-semibold text-gray-900" id="modal-title">
                                Structure Editor
                            </h3>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                                <div class="my-4">
                                    <div id="structureSearchEditor" class="border border-gray-200 rounded-lg mb-3" style="height: 400px; width: 100%">
                                    </div>
                                    <!-- SMILES input field -->
                                    <div class="mb-3">
                                        <label for="smiles-string" class="block text-sm font-medium text-gray-700">SMILES String</label>
                                        <div class="mt-1 flex rounded-md">
                                            <input type="text" 
                                                   id="smiles-string" 
                                                   x-model="currentSmiles" 
                                                   class="block w-full rounded-l-md border border-r-0 border-gray-200 focus:border-gray-300 focus:ring-0 sm:text-sm" 
                                                   placeholder="Enter SMILES string to load into the Editor">
                                            <button 
                                                @click="loadSmilesIntoEditor()"
                                                class="inline-flex items-center rounded-r-md border border-gray-200 bg-gray-50 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 focus:outline-none">
                                                Load
                                            </button>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label for="editor-output" class="block text-sm font-medium text-gray-700">Editor Output</label>
                                        <div class="mt-1">
                                            <input type="text" 
                                                   id="editor-output" 
                                                   x-model="editorOutput" 
                                                   readonly
                                                   class="block w-full rounded-md border border-gray-200 focus:border-gray-300 focus:ring-0 sm:text-sm bg-gray-50" 
                                                   placeholder="Editor Output">
                                        </div>
                                    </div>
                                    <div class="py-3">
                                        <div x-on:dragover.prevent="draggedFile = null"
                                            x-on:drop.prevent="draggedFile = $event.dataTransfer.files[0]; handleFileUpload($event)"
                                            class="border-2 border-dashed border-gray-200 rounded-lg p-6 text-center mb-3 hover:border-gray-300 transition-colors">
                                            <input type="file" accept=".mol,.sdf" class="hidden" id="fileUpload"
                                                x-on:change="handleFileUpload($event)" />
                                            <label for="fileUpload" class="cursor-pointer">
                                                <p class="text-gray-600">Drag and drop .mol or .sdf files here</p>
                                                <p class="text-sm text-gray-500 mt-2">Or click to select a file</p>
                                            </label>
                                        </div>
                                        <button @click="fetchClipboardText"
                                            class="w-full text-center rounded-lg shadow-sm px-4 py-2.5 text-sm font-medium text-gray-700 bg-white border border-gray-200 hover:bg-gray-50 hover:border-gray-300 focus:outline-none transition-colors">
                                            Paste from Clipboard
                                        </button>
                                    </div>
                                </div>
                                <div>
                                    <fieldset class="mt-1">
                                        <legend class="text-base font-medium text-gray-900 mb-2">
                                            Select search type
                                        </legend>
                                        <div class="mt-4 space-y-3">
                                            <div class="flex items-center">
                                                <label for="search-type-exact"
                                                    :class="type === 'exact' ? 'bg-secondary-50 border-secondary-500 ring-2 ring-secondary-500' : 'bg-white border-gray-300 hover:border-gray-400'"
                                                    class="flex-1 cursor-pointer rounded-lg border-2 p-4 transition-all duration-200">
                                                    <div class="flex items-center justify-between">
                                                        <div class="flex items-center">
                                                            <input id="search-type-exact" name="search-type" x-model="type"
                                                                value="exact" type="radio"
                                                                class="h-4 w-4 border-gray-300 text-secondary-dark focus:ring-secondary-dark" />
                                                            <span class="ml-3 font-semibold text-gray-900">Exact match</span>
                                                        </div>
                                                        <span x-show="type === 'exact'" class="text-secondary-600">
                                                            <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                                            </svg>
                                                        </span>
                                                    </div>
                                                    <p class="ml-7 mt-1 text-sm text-gray-500">Find molecules with identical structure</p>
                                                </label>
                                            </div>
                                            <div class="flex items-center">
                                                <label for="search-type-sub"
                                                    :class="type === 'substructure' ? 'bg-secondary-50 border-secondary-500 ring-secondary-500' : 'bg-white border-gray-300 hover:border-gray-400'"
                                                    class="flex-1 cursor-pointer rounded-lg border-2 p-4 transition-all duration-200">
                                                    <div class="flex items-center justify-between">
                                                        <div class="flex items-center">
                                                            <input id="search-type-sub" name="search-type" x-model="type"
                                                                value="substructure" type="radio"
                                                                class="h-4 w-4 border-gray-300 text-secondary-dark focus:ring-secondary-dark" />
                                                            <span class="ml-3 font-semibold text-gray-900">Substructure Search</span>
                                                        </div>
                                                        <span x-show="type === 'substructure'" class="text-secondary-600">
                                                            <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                                            </svg>
                                                        </span>
                                                    </div>
                                                    <p class="ml-7 mt-1 text-sm text-gray-500">Find molecules containing this structure</p>
                                                </label>
                                            </div>
                                            <div class="flex items-center">
                                                <label for="search-type-similar"
                                                    :class="type === 'similarity' ? 'bg-secondary-50 border-secondary-500 ring-2 ring-secondary-500' : 'bg-white border-gray-300 hover:border-gray-400'"
                                                    class="flex-1 cursor-pointer rounded-lg border-2 p-4 transition-all duration-200">
                                                    <div class="flex items-center justify-between">
                                                        <div class="flex items-center">
                                                            <input id="search-type-similar" name="search-type" x-model="type"
                                                                value="similarity" type="radio"
                                                                class="h-4 w-4 border-gray-300 text-secondary-dark focus:ring-secondary-dark" />
                                                            <span class="ml-3 font-semibold text-gray-900">Similarity Search</span>
                                                        </div>
                                                        <span x-show="type === 'similarity'" class="text-secondary-600">
                                                            <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                                            </svg>
                                                        </span>
                                                    </div>
                                                    <p class="ml-7 mt-1 text-sm text-gray-500">Find similar molecules (Tanimoto ≥ 0.5)</p>
                                                </label>
                                            </div>
                                        </div>
                                    </fieldset>
                                    <div class="mt-6" x-show="recentSearches.length > 0">
                                        <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wide">Previous searches</h3>
                                        <div class="mt-3 grid grid-cols-2 gap-4 max-h-[480px] overflow-y-auto pr-1">
                                            <template x-for="(search, index) in recentSearches" :key="index">
                                                <div class="border border-gray-200 rounded-md bg-white overflow-hidden">
                                                    <div class="cursor-pointer" @click="loadSearch(search)">
                                                        <div class="h-32 flex items-center justify-center bg-white p-2">
                                                            <img :src="`https://api.cheminf.studio/latest/depict/2D?smiles=${search.q}&height=300&width=300&CIP=true&toolkit=cdk`"
                                                                alt="Molecule Structure"
                                                                class="object-contain h-full w-full">
                                                        </div>
                                                        <div class="border-t border-gray-200 px-3 py-2">
                                                            <div class="flex items-center justify-between">
                                                                <span x-text="search.type"
                                                                    class="text-xs font-medium text-gray-500 uppercase tracking-wide">
                                                                </span>
                                                                <button @click.stop="deleteSearch(index)"
                                                                    class="text-gray-400 hover:text-red-500 transition-colors p-1 -mr-1">
                                                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
                                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                                                                    </svg>
                                                                </button>
                                                            </div>
                                                            <p x-text="search.q"
                                                                class="mt-1 text-sm text-gray-900 truncate font-mono">
                                                            </p>
                                                        </div>
                                                    </div>
                                                </div>
                                            </template>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="flex justify-end gap-3 border-t border-gray-200 pt-4 mt-6">
                        <button @click="isOpen = false" type="button"
                            class="cursor-pointer inline-flex justify-center rounded-md border border-gray-300 bg-danger-dark px-4 py-2 text-sm font-medium text-white hover:bg-danger-light focus:outline-none transition-colors">
                            Close
                        </button>
                        <button @click="performSearch()" type="button"
                            class="cursor-pointer inline-flex justify-center rounded-md bg-secondary-dark px-4 py-2 text-sm font-medium text-white hover:bg-secondary-light focus:outline-none transition-colors">
                            Search
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @if ($mode && $mode == 'inline')
        <button type="button" @click="isOpen = true"
            class="rounded-lg text-gray-900 bg-white mr-3 py-3 px-2 text-gray-500 hover:bg-gray-50 hover:text-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors">
            <svg class="w-6 h-6 mx-2 mx-auto" viewBox="0 0 78 78" fill="none" xmlns="http://www.w3.org/2000/svg">
                <g clip-path="url(#clip0_1_2)">
                    <path d="M70.1638 11.819L66.3621 23.4827" stroke="black" stroke-width="1.13386"
                        stroke-linecap="round" stroke-linejoin="round" />
                    <path d="M68.0431 32.4052L74.8966 41.819" stroke="black" stroke-width="1.13386"
                        stroke-linecap="round" stroke-linejoin="round" />
                    <path d="M68.0431 51.2586L74.8966 41.819" stroke="black" stroke-width="1.13386"
                        stroke-linecap="round" stroke-linejoin="round" />
                    <path d="M65.8448 49.6293L71.5086 41.819" stroke="black" stroke-width="1.13386"
                        stroke-linecap="round" stroke-linejoin="round" />
                    <path d="M61.0603 54.3621L48.6983 50.3793" stroke="black" stroke-width="1.13386"
                        stroke-linecap="round" stroke-linejoin="round" />
                    <path d="M48.6983 50.3793V33.2845" stroke="black" stroke-width="1.13386" stroke-linecap="round"
                        stroke-linejoin="round" />
                    <path d="M45.9828 48.8017V34.8621" stroke="black" stroke-width="1.13386" stroke-linecap="round"
                        stroke-linejoin="round" />
                    <path d="M61.0603 29.3017L48.6983 33.2845" stroke="black" stroke-width="1.13386"
                        stroke-linecap="round" stroke-linejoin="round" />
                    <path d="M48.6983 33.2845L33.9052 24.75" stroke="black" stroke-width="1.13386"
                        stroke-linecap="round" stroke-linejoin="round" />
                    <path d="M32.5345 25.5259V12.4397" stroke="black" stroke-width="1.13386" stroke-linecap="round"
                        stroke-linejoin="round" />
                    <path d="M35.2759 25.5259V12.4397" stroke="black" stroke-width="1.13386" stroke-linecap="round"
                        stroke-linejoin="round" />
                    <path d="M33.9052 24.75L22.7845 31.1638" stroke="black" stroke-width="1.13386"
                        stroke-linecap="round" stroke-linejoin="round" />
                    <path d="M19.1121 37.9397V50.3793" stroke="black" stroke-width="1.13386" stroke-linecap="round"
                        stroke-linejoin="round" />
                    <path d="M20.4828 51.1552L9.10345 57.7241" stroke="black" stroke-width="1.13386"
                        stroke-linecap="round" stroke-linejoin="round" />
                    <path d="M19.1121 48.8017L7.73276 55.3707" stroke="black" stroke-width="1.13386"
                        stroke-linecap="round" stroke-linejoin="round" />
                    <path d="M19.1121 50.3793L30.2069 56.7672" stroke="black" stroke-width="1.13386"
                        stroke-linecap="round" stroke-linejoin="round" />
                    <path d="M48.6983 50.3793L37.6034 56.7672" stroke="black" stroke-width="1.13386"
                        stroke-linecap="round" stroke-linejoin="round" />
                    <path d="M33.9052 63.569V75.9828" stroke="black" stroke-width="1.13386" stroke-linecap="round"
                        stroke-linejoin="round" />
                    <path d="M15.4138 31.1638L4.31897 24.75" stroke="black" stroke-width="1.13386"
                        stroke-linecap="round" stroke-linejoin="round" />
                    <path
                        d="M62.5345 24.9569H63.6466L66.3879 30.1552V24.9569H67.2155V31.1638H66.0776L63.3362 25.9914V31.1638H62.5345V24.9569Z"
                        fill="black" />
                    <path
                        d="M62.5345 52.5H63.6466L66.3879 57.6983V52.5H67.2155V58.7069H66.0776L63.3362 53.5345V58.7069H62.5345V52.5Z"
                        fill="black" />
                    <path
                        d="M33.9052 5.12069C33.2845 5.12069 32.7931 5.34482 32.431 5.7931C32.069 6.24138 31.8879 6.86207 31.8879 7.65517C31.8879 8.43103 32.069 9.05172 32.431 9.51724C32.7931 9.96551 33.2845 10.1897 33.9052 10.1897C34.5086 10.1897 34.9914 9.96551 35.3535 9.51724C35.7155 9.06896 35.8966 8.44827 35.8966 7.65517C35.8966 6.87931 35.7155 6.25862 35.3535 5.7931C34.9914 5.34482 34.5086 5.12069 33.9052 5.12069ZM33.9052 4.44827C34.7672 4.44827 35.4569 4.74138 35.9741 5.32758C36.4914 5.91379 36.75 6.69827 36.75 7.68103C36.75 8.66379 36.4914 9.44827 35.9741 10.0345C35.4569 10.6207 34.7672 10.9138 33.9052 10.9138C33.0259 10.9138 32.3276 10.6207 31.8103 10.0345C31.2931 9.44827 31.0345 8.66379 31.0345 7.68103C31.0345 6.69827 31.2931 5.91379 31.8103 5.32758C32.3276 4.74138 33.0259 4.44827 33.9052 4.44827Z"
                        fill="black" />
                    <path
                        d="M16.7586 30.181H17.8707L20.6121 35.3793V30.181H21.4397V36.3879H20.3017L17.5603 31.2155V36.3879H16.7586V30.181Z"
                        fill="black" />
                    <path
                        d="M4.31897 56.3793C3.69828 56.3793 3.2069 56.6034 2.84483 57.0517C2.48276 57.5 2.30172 58.1207 2.30172 58.9138C2.30172 59.6897 2.48276 60.3103 2.84483 60.7759C3.2069 61.2241 3.69828 61.4483 4.31897 61.4483C4.92241 61.4483 5.40517 61.2241 5.76724 60.7759C6.12931 60.3276 6.31035 59.7069 6.31035 58.9138C6.31035 58.1379 6.12931 57.5172 5.76724 57.0517C5.40517 56.6034 4.92241 56.3793 4.31897 56.3793ZM4.31897 55.681C5.18103 55.681 5.87069 55.9741 6.38793 56.5603C6.90517 57.1465 7.16379 57.931 7.16379 58.9138C7.16379 59.8965 6.90517 60.681 6.38793 61.2672C5.87069 61.8534 5.18103 62.1465 4.31897 62.1465C3.43966 62.1465 2.74138 61.8534 2.22414 61.2672C1.7069 60.681 1.44828 59.8965 1.44828 58.9138C1.44828 57.931 1.7069 57.1465 2.22414 56.5603C2.74138 55.9741 3.43966 55.681 4.31897 55.681Z"
                        fill="black" />
                    <path
                        d="M31.5517 55.8103H32.6638L35.4052 61.0086V55.8103H36.2328V62.0172H35.0948L32.3535 56.8448V62.0172H31.5517V55.8103Z"
                        fill="black" />
                    <path fill-rule="evenodd" clip-rule="evenodd"
                        d="M61.194 0.892153L70.1146 9.91002C70.6816 10.4859 71 11.2659 71 12.0792C71 12.8924 70.6816 13.6724 70.1146 14.2483L65.1786 19.2692L51.9689 5.89107L56.932 0.892153C57.502 0.320612 58.2717 0 59.0739 0C59.876 0 60.6457 0.320612 61.2157 0.892153H61.194ZM43.5211 41.1808L32.7957 44.5391C25.2666 46.1907 25.3319 47.9249 26.729 40.7348L30.3385 27.8301L49.4682 8.42906L62.678 21.8072L43.532 41.1973L43.5211 41.1808ZM32.5891 30.1149L41.2869 38.9235L34.2199 41.1257C28.7023 42.8489 28.7295 43.9995 30.2298 38.6207L32.5891 30.1424V30.1149Z"
                        fill="black" />
                </g>
                <defs>
                    <clipPath id="clip0_1_2">
                        <rect width="77.5862" height="78" fill="white" />
                    </clipPath>
                </defs>
            </svg>
        </button>
    @else
        <button type="button" @click="isOpen = true"
            class="cursor-pointer border border-gray-200 bg-white justify-center items-center text-center rounded-md text-gray-900 mr-1 py-3 px-4 hover:bg-gray-50 hover:border-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-all shadow-sm">
            <svg class="w-12 h-12 mx-auto" viewBox="0 0 78 78" fill="none" xmlns="http://www.w3.org/2000/svg">
                <g clip-path="url(#clip0_1_2)">
                    <path d="M70.1638 11.819L66.3621 23.4827" stroke="black" stroke-width="1.13386"
                        stroke-linecap="round" stroke-linejoin="round" />
                    <path d="M68.0431 32.4052L74.8966 41.819" stroke="black" stroke-width="1.13386"
                        stroke-linecap="round" stroke-linejoin="round" />
                    <path d="M68.0431 51.2586L74.8966 41.819" stroke="black" stroke-width="1.13386"
                        stroke-linecap="round" stroke-linejoin="round" />
                    <path d="M65.8448 49.6293L71.5086 41.819" stroke="black" stroke-width="1.13386"
                        stroke-linecap="round" stroke-linejoin="round" />
                    <path d="M61.0603 54.3621L48.6983 50.3793" stroke="black" stroke-width="1.13386"
                        stroke-linecap="round" stroke-linejoin="round" />
                    <path d="M48.6983 50.3793V33.2845" stroke="black" stroke-width="1.13386" stroke-linecap="round"
                        stroke-linejoin="round" />
                    <path d="M45.9828 48.8017V34.8621" stroke="black" stroke-width="1.13386" stroke-linecap="round"
                        stroke-linejoin="round" />
                    <path d="M61.0603 29.3017L48.6983 33.2845" stroke="black" stroke-width="1.13386"
                        stroke-linecap="round" stroke-linejoin="round" />
                    <path d="M48.6983 33.2845L33.9052 24.75" stroke="black" stroke-width="1.13386"
                        stroke-linecap="round" stroke-linejoin="round" />
                    <path d="M32.5345 25.5259V12.4397" stroke="black" stroke-width="1.13386" stroke-linecap="round"
                        stroke-linejoin="round" />
                    <path d="M35.2759 25.5259V12.4397" stroke="black" stroke-width="1.13386" stroke-linecap="round"
                        stroke-linejoin="round" />
                    <path d="M33.9052 24.75L22.7845 31.1638" stroke="black" stroke-width="1.13386"
                        stroke-linecap="round" stroke-linejoin="round" />
                    <path d="M19.1121 37.9397V50.3793" stroke="black" stroke-width="1.13386" stroke-linecap="round"
                        stroke-linejoin="round" />
                    <path d="M20.4828 51.1552L9.10345 57.7241" stroke="black" stroke-width="1.13386"
                        stroke-linecap="round" stroke-linejoin="round" />
                    <path d="M19.1121 48.8017L7.73276 55.3707" stroke="black" stroke-width="1.13386"
                        stroke-linecap="round" stroke-linejoin="round" />
                    <path d="M19.1121 50.3793L30.2069 56.7672" stroke="black" stroke-width="1.13386"
                        stroke-linecap="round" stroke-linejoin="round" />
                    <path d="M48.6983 50.3793L37.6034 56.7672" stroke="black" stroke-width="1.13386"
                        stroke-linecap="round" stroke-linejoin="round" />
                    <path d="M33.9052 63.569V75.9828" stroke="black" stroke-width="1.13386" stroke-linecap="round"
                        stroke-linejoin="round" />
                    <path d="M15.4138 31.1638L4.31897 24.75" stroke="black" stroke-width="1.13386"
                        stroke-linecap="round" stroke-linejoin="round" />
                    <path
                        d="M62.5345 24.9569H63.6466L66.3879 30.1552V24.9569H67.2155V31.1638H66.0776L63.3362 25.9914V31.1638H62.5345V24.9569Z"
                        fill="black" />
                    <path
                        d="M62.5345 52.5H63.6466L66.3879 57.6983V52.5H67.2155V58.7069H66.0776L63.3362 53.5345V58.7069H62.5345V52.5Z"
                        fill="black" />
                    <path
                        d="M33.9052 5.12069C33.2845 5.12069 32.7931 5.34482 32.431 5.7931C32.069 6.24138 31.8879 6.86207 31.8879 7.65517C31.8879 8.43103 32.069 9.05172 32.431 9.51724C32.7931 9.96551 33.2845 10.1897 33.9052 10.1897C34.5086 10.1897 34.9914 9.96551 35.3535 9.51724C35.7155 9.06896 35.8966 8.44827 35.8966 7.65517C35.8966 6.87931 35.7155 6.25862 35.3535 5.7931C34.9914 5.34482 34.5086 5.12069 33.9052 5.12069ZM33.9052 4.44827C34.7672 4.44827 35.4569 4.74138 35.9741 5.32758C36.4914 5.91379 36.75 6.69827 36.75 7.68103C36.75 8.66379 36.4914 9.44827 35.9741 10.0345C35.4569 10.6207 34.7672 10.9138 33.9052 10.9138C33.0259 10.9138 32.3276 10.6207 31.8103 10.0345C31.2931 9.44827 31.0345 8.66379 31.0345 7.68103C31.0345 6.69827 31.2931 5.91379 31.8103 5.32758C32.3276 4.74138 33.0259 4.44827 33.9052 4.44827Z"
                        fill="black" />
                    <path
                        d="M16.7586 30.181H17.8707L20.6121 35.3793V30.181H21.4397V36.3879H20.3017L17.5603 31.2155V36.3879H16.7586V30.181Z"
                        fill="black" />
                    <path
                        d="M4.31897 56.3793C3.69828 56.3793 3.2069 56.6034 2.84483 57.0517C2.48276 57.5 2.30172 58.1207 2.30172 58.9138C2.30172 59.6897 2.48276 60.3103 2.84483 60.7759C3.2069 61.2241 3.69828 61.4483 4.31897 61.4483C4.92241 61.4483 5.40517 61.2241 5.76724 60.7759C6.12931 60.3276 6.31035 59.7069 6.31035 58.9138C6.31035 58.1379 6.12931 57.5172 5.76724 57.0517C5.40517 56.6034 4.92241 56.3793 4.31897 56.3793ZM4.31897 55.681C5.18103 55.681 5.87069 55.9741 6.38793 56.5603C6.90517 57.1465 7.16379 57.931 7.16379 58.9138C7.16379 59.8965 6.90517 60.681 6.38793 61.2672C5.87069 61.8534 5.18103 62.1465 4.31897 62.1465C3.43966 62.1465 2.74138 61.8534 2.22414 61.2672C1.7069 60.681 1.44828 59.8965 1.44828 58.9138C1.44828 57.931 1.7069 57.1465 2.22414 56.5603C2.74138 55.9741 3.43966 55.681 4.31897 55.681Z"
                        fill="black" />
                    <path
                        d="M31.5517 55.8103H32.6638L35.4052 61.0086V55.8103H36.2328V62.0172H35.0948L32.3535 56.8448V62.0172H31.5517V55.8103Z"
                        fill="black" />
                    <path fill-rule="evenodd" clip-rule="evenodd"
                        d="M61.194 0.892153L70.1146 9.91002C70.6816 10.4859 71 11.2659 71 12.0792C71 12.8924 70.6816 13.6724 70.1146 14.2483L65.1786 19.2692L51.9689 5.89107L56.932 0.892153C57.502 0.320612 58.2717 0 59.0739 0C59.876 0 60.6457 0.320612 61.2157 0.892153H61.194ZM43.5211 41.1808L32.7957 44.5391C25.2666 46.1907 25.3319 47.9249 26.729 40.7348L30.3385 27.8301L49.4682 8.42906L62.678 21.8072L43.532 41.1973L43.5211 41.1808ZM32.5891 30.1149L41.2869 38.9235L34.2199 41.1257C28.7023 42.8489 28.7295 43.9995 30.2298 38.6207L32.5891 30.1424V30.1149Z"
                        fill="black" />
                </g>
                <defs>
                    <clipPath id="clip0_1_2">
                        <rect width="77.5862" height="78" fill="white" />
                    </clipPath>
                </defs>
            </svg>
            <small class="text-base font-semibold leading-7">Draw Structure</small>
        </button>
    @endif
</div>
