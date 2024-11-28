<div class="mt-4" x-data="{ src: '' }">
    <div class="w-full max-w-xl">
        <div class="flex items-center justify-between" x-data="{ on: false }">
            <span class="flex flex-grow flex-col">
                <span class="text-sm font-medium leading-6 text-gray-900" id="availability-label">3D Structure </span>
                <span class="text-sm text-gray-500 pr-5" id="availability-description"><span class="text-sm text-gray-500">(by RDKit)</span> Interactive JSmol molecular viewer
                </span>
            </span>
            <span class="relative flex justify-center cursor-pointer mr-4" x-on:click="$wire.downloadSDFFile()" :title="`Download SDF file from RDKit`" >
                    3D 
                    <svg class="ml-1" width="25px" height="25px" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path d="M12.5535 16.5061C12.4114 16.6615 12.2106 16.75 12 16.75C11.7894 16.75 11.5886 16.6615 11.4465 16.5061L7.44648 12.1311C7.16698 11.8254 7.18822 11.351 7.49392 11.0715C7.79963 10.792 8.27402 10.8132 8.55352 11.1189L11.25 14.0682V3C11.25 2.58579 11.5858 2.25 12 2.25C12.4142 2.25 12.75 2.58579 12.75 3V14.0682L15.4465 11.1189C15.726 10.8132 16.2004 10.792 16.5061 11.0715C16.8118 11.351 16.833 11.8254 16.5535 12.1311L12.5535 16.5061Z" fill="#1C274C" />
                        <path d="M3.75 15C3.75 14.5858 3.41422 14.25 3 14.25C2.58579 14.25 2.25 14.5858 2.25 15V15.0549C2.24998 16.4225 2.24996 17.5248 2.36652 18.3918C2.48754 19.2919 2.74643 20.0497 3.34835 20.6516C3.95027 21.2536 4.70814 21.5125 5.60825 21.6335C6.47522 21.75 7.57754 21.75 8.94513 21.75H15.0549C16.4225 21.75 17.5248 21.75 18.3918 21.6335C19.2919 21.5125 20.0497 21.2536 20.6517 20.6516C21.2536 20.0497 21.5125 19.2919 21.6335 18.3918C21.75 17.5248 21.75 16.4225 21.75 15.0549V15C21.75 14.5858 21.4142 14.25 21 14.25C20.5858 14.25 20.25 14.5858 20.25 15C20.25 16.4354 20.2484 17.4365 20.1469 18.1919C20.0482 18.9257 19.8678 19.3142 19.591 19.591C19.3142 19.8678 18.9257 20.0482 18.1919 20.1469C17.4365 20.2484 16.4354 20.25 15 20.25H9C7.56459 20.25 6.56347 20.2484 5.80812 20.1469C5.07435 20.0482 4.68577 19.8678 4.40901 19.591C4.13225 19.3142 3.9518 18.9257 3.85315 18.1919C3.75159 17.4365 3.75 16.4354 3.75 15Z" fill="#1C274C" />
                    </svg>
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
