<div x-data="{
    isOpen: false,
    mode: @entangle('mode'),
    searchType: 'exact',
    smiles: @entangle('smiles'),
    type: @entangle('type'),
    draggedFile: null,
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
}" >
    <div x-init="$watch('isOpen', value => {
        if (value) {
            setTimeout(() => {
                window.editor = OCL.StructureEditor.createSVGEditor('structureSearchEditor', 1);
                if (smiles) {
                    window.editor.setSmiles(smiles);
                }
                window.getEditorSmiles = () => window.editor.getSmiles();
            }, 100);
        }
    });">
        <div x-show="isOpen" x-cloak class="fixed z-20 inset-0 overflow-y-auto" aria-labelledby="modal-title" role="dialog"
            aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>

                <!-- This element is to trick the browser into centering the modal contents. -->
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                <div
                    class="z-20 inline-block align-bottom bg-white rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full sm:p-6">
                    <div class="sm:flex sm:items-start">
                        <div class="mt-3 text-center sm:mt-0 sm:text-left w-full">
                            <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                                Structure Editor
                            </h3>
                            <div class="py-3">
                                <div 
                                x-on:dragover.prevent="draggedFile = null" 
                                x-on:drop.prevent="draggedFile = $event.dataTransfer.files[0]; handleFileUpload($event)"
                                class="border-2 border-dashed border-gray-300 p-6 text-center mb-3"
                            >
                                <input 
                                    type="file" 
                                    accept=".mol,.sdf" 
                                    class="hidden" 
                                    id="fileUpload" 
                                    x-on:change="handleFileUpload($event)"
                                />
                                <label for="fileUpload" class="cursor-pointer">
                                    <p class="text-gray-600">Drag and drop .mol or .sdf files here</p>
                                    <p class="text-sm text-gray-500 mt-2">Or click to select a file</p>
                                </label>
                            </div>
                                <div id="structureSearchEditor" class="border mb-3" style="height: 400px; width: 100%">
                                </div>
                                <div 
                                    @click="fetchClipboardText"
                                    class="hover:cursor-pointer w-full text-center rounded-md shadow-sm px-4 py-2 bg-white-600 text-base font-medium hover:bg-white-700 focus:outline-none sm:w-auto sm:text-sm border"
                                >
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
                                            <input id="search-type-exact" name="search-type" x-model="type"
                                                value="exact" type="radio"
                                                class="mr-3 h-4 w-4 border-gray-300 text-secondary-dark focus:ring-secondary-dark" />
                                            Exact match
                                        </label>
                                    </div>
                                    <div class="flex items-center">
                                        <label for="search-type-sub"
                                            class="block cursor-pointer text-sm font-medium text-gray-700">
                                            <input id="search-type-sub" name="search-type" x-model="type"
                                                value="substructure" type="radio"
                                                class="mr-3 h-4 w-4 border-gray-300 text-secondary-dark focus:ring-secondary-dark" />
                                            Substructure Search
                                        </label>
                                    </div>
                                    <div class="flex items-center">
                                        <label for="search-type-similar"
                                            class="block cursor-pointer text-sm font-medium text-gray-700">
                                            <input id="search-type-similar" name="search-type" x-model="type"
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
                            @click="const smiles = window.getEditorSmiles(); window.location.href = `/search?type=${type}&q=${encodeURIComponent(smiles)}`"
                            type="button"
                            class="mt-3 w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                            Search
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @if ($mode && $mode == 'inline')
        <button type="button" @click="isOpen = true"
            class="rounded-md text-gray-900 bg-white mr-3 py-3 px-2 text-gray-400 hover:bg-gray-100 hover:text-gray-700 focus:outline-none focus:ring-2 focus:ring-secondary-dark focus:ring-offset-2">
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
            class="border bg-gray-50 justify-center items-center text-center rounded-md text-gray-900 mr-1 py-3 px-4 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-secondary-dark focus:ring-offset-2">
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
