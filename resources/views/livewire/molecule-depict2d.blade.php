<div x-data="{ toolkit: @entangle('toolkit'), options: @entangle('options') }">
    <fieldset x-show="options" class="pl-4 w-full">
        <div class="mt-4 grid grid-cols-6 gap-y-6 gap-x-1 sm:grid-cols-6 sm:gap-x-4 items-center">
            <label @click="toolkit= 'cdk'"
                class="relative flex cursor-pointer rounded-lg bg-white p-3 focus:outline-none col-span-2">
                <input type="radio" name="project-type" value="Newsletter" class="sr-only">
                <span class="flex flex-1">
                    <span class="flex flex-col">
                        <span class="block text-sm font-bold text-indigo-900">CDK</span>
                    </span>
                </span> 
                <svg :class="{ 'text-indigo-400': toolkit == 'cdk', 'text-gray-400': toolkit == 'rdkit' }"
                    class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path fill-rule="evenodd"
                        d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z"
                        clip-rule="evenodd" />
                </svg>
                <span class="pointer-events-none absolute -inset-px rounded-lg border-2" aria-hidden="true"></span>
            </label>
            <label @click="toolkit= 'rdkit'"
                class="relative flex cursor-pointer rounded-lg bg-white p-3 focus:outline-none col-span-2">
                <input type="radio" name="project-type" value="Existing Customers" class="sr-only">
                <span class="flex flex-1">
                    <span class="flex flex-col">
                        <span class="block text-sm font-bold text-gray-900">RDKit&nbsp;</span>
                    </span>
                </span>
                <svg :class="{ 'text-indigo-400': toolkit == 'rdkit', 'text-gray-400': toolkit == 'cdk' }"
                    class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path fill-rule="evenodd"
                        d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z"
                        clip-rule="evenodd" />
                </svg>
                <span class="pointer-events-none absolute -inset-px rounded-lg border-2" aria-hidden="true"></span>
            </label>
            <!-- Download Button -->
            <span x-show="options" class="relative flex justify-center cursor-pointer" x-on:click="$wire.downloadMolFile(toolkit)" :title="`Download SD file from RDKit`" >
                    2D 
                    <svg class="ml-1" width="25px" height="25px" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path d="M12.5535 16.5061C12.4114 16.6615 12.2106 16.75 12 16.75C11.7894 16.75 11.5886 16.6615 11.4465 16.5061L7.44648 12.1311C7.16698 11.8254 7.18822 11.351 7.49392 11.0715C7.79963 10.792 8.27402 10.8132 8.55352 11.1189L11.25 14.0682V3C11.25 2.58579 11.5858 2.25 12 2.25C12.4142 2.25 12.75 2.58579 12.75 3V14.0682L15.4465 11.1189C15.726 10.8132 16.2004 10.792 16.5061 11.0715C16.8118 11.351 16.833 11.8254 16.5535 12.1311L12.5535 16.5061Z" fill="#1C274C" />
                        <path d="M3.75 15C3.75 14.5858 3.41422 14.25 3 14.25C2.58579 14.25 2.25 14.5858 2.25 15V15.0549C2.24998 16.4225 2.24996 17.5248 2.36652 18.3918C2.48754 19.2919 2.74643 20.0497 3.34835 20.6516C3.95027 21.2536 4.70814 21.5125 5.60825 21.6335C6.47522 21.75 7.57754 21.75 8.94513 21.75H15.0549C16.4225 21.75 17.5248 21.75 18.3918 21.6335C19.2919 21.5125 20.0497 21.2536 20.6517 20.6516C21.2536 20.0497 21.5125 19.2919 21.6335 18.3918C21.75 17.5248 21.75 16.4225 21.75 15.0549V15C21.75 14.5858 21.4142 14.25 21 14.25C20.5858 14.25 20.25 14.5858 20.25 15C20.25 16.4354 20.2484 17.4365 20.1469 18.1919C20.0482 18.9257 19.8678 19.3142 19.591 19.591C19.3142 19.8678 18.9257 20.0482 18.1919 20.1469C17.4365 20.2484 16.4354 20.25 15 20.25H9C7.56459 20.25 6.56347 20.2484 5.80812 20.1469C5.07435 20.0482 4.68577 19.8678 4.40901 19.591C4.13225 19.3142 3.9518 18.9257 3.85315 18.1919C3.75159 17.4365 3.75 16.4354 3.75 15Z" fill="#1C274C" />
                    </svg>
            </span>
            <!-- Preview Button -->
            <span x-show="options" class="relative flex cursor-pointer " @click="window.open(`{{ $this->preview }}&toolkit=${toolkit}&CIP=true&unicolor=false`, '_blank')">
                <svg width="20px" height="20px" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" >
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M13.5 6H5.25A2.25 2.25 0 0 0 3 8.25v10.5A2.25 2.25 0 0 0 5.25 21h10.5A2.25 2.25 0 0 0 18 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25">
                    </path>
                </svg>
            </span>
        </div>

    </fieldset>
    <img alt="{{ $this->name }}" class="mx-auto w-100"
        :src="'{{ $this->source }}&toolkit=' + toolkit + '&CIP=true&unicolor=false'" alt="smiles" />


</div>