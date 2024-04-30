<div>
    <div wire:poll>
        @if($failMessage)
            {{ $failMessage }}
        @endif
@if($status == 'PROCESSING')
        <div class="rounded-md border bg-yellow p-4">
            <div class="flex">
                <div class="flex-shrink-0 mr-10">
                    <svg class="inline animate-spin -ml-1 mr-3 h-5 w-5 text-dark" xmlns="http://www.w3.org/2000/svg"
                        fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                            stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor"
                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                        </path>
                    </svg>&emsp;
                </div>
                <div class="pl-10">
                    <h3 class="text-sm font-medium text-yellow-800">JOBS IN PROGRESS</h3>
                    <div class="mt-2 text-sm text-yellow-700">
                        <p>{{ $info }}</p>
                    </div>
                </div>
            </div>
        </div>
@endif
    </div>
</div>
