<div x-data="{ toolkit: @entangle('toolkit'), options:  @entangle('options') }">
    <fieldset x-show="options" class="px-4 w-full">
        <div class="mt-4 grid grid-cols-3 gap-y-6 gap-x-1 sm:grid-cols-3 sm:gap-x-4">
            <label @click="toolkit= 'cdk'" aria-label="Newsletter"
                aria-description="Last message sent an hour ago to 621 users"
                class="relative flex cursor-pointer rounded-lg bg-white p-3 focus:outline-none">
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
            <label @click="toolkit= 'rdkit'" aria-label="Existing Customers"
                aria-description="Last message sent 2 weeks ago to 1200 users"
                class="relative flex cursor-pointer rounded-lg bg-white p-3 focus:outline-none">
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
            <label aria-label="Existing Customers"
                aria-description="Last message sent 2 weeks ago to 1200 users"
                class="relative flex cursor-pointer rounded-lg bg-white p-3 focus:outline-none">
                <a :href="'{{ $this->preview }}&toolkit=' + toolkit + '&CIP=true&unicolor=false'" target="_blank"><svg xmlns="http://www.w3.org/2000/svg"
                    fill="none" viewBox="0 0 24 24"
                    stroke-width="1.5" stroke="currentColor"
                    class="size-5 inline">
                    <path stroke-linecap="round"
                        stroke-linejoin="round"
                        d="M13.5 6H5.25A2.25 2.25 0 0 0 3 8.25v10.5A2.25 2.25 0 0 0 5.25 21h10.5A2.25 2.25 0 0 0 18 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25">
                    </path>
                </svg></a>
            </label>
        </div>
    </fieldset>
    <img alt="{{ $this->name }}" class="mx-auto w-100" :src="'{{ $this->source }}&toolkit=' + toolkit + '&CIP=true&unicolor=false'"
        alt="smiles" />
</div>
