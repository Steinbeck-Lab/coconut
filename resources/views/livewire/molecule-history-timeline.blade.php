<div x-data="{ on: false}" x-effect="on && $wire.getHistory()" class="mt-5">
    <button type="button" x-on:click="on = !on" class="text-base font-semibold text-text-dark hover:text-slate-600" wire:transition="fade">
        <span x-show="!on">View complete history → </span>
        <span x-show="on">View complete history ↓ </span>
        <!-- <span x-show="on">Hide History</span> -->
    </button>

    <div x-show="on" class="bg-white px-4 pb-7 mt-2 shadow sm:rounded-lg sm:px-6 border">
        <div class="lg:col-start-3">
            <!-- Activity feed -->
            <ul role="list" class="mt-6 space-y-3">
                @foreach ($audit_data as $audit)
                <li class="relative flex gap-x-1">
                    <div class="absolute -bottom-6 left-0 top-0 flex w-6 justify-center">
                        <div class="w-px bg-gray-200"></div>
                    </div>
                    <div class="relative flex h-6 w-6 flex-none items-center justify-center bg-white">
                        <div class="h-1.5 w-1.5 rounded-full bg-gray-100 ring-1 ring-gray-300"></div>
                    </div>
                    <p>
                    <div class="flex-auto">
                        <p class="flex-col py-0.5 text-s leading-5 text-gray-500">
                        <p class="text-xs  text-gray-900"><span>{{$audit['user_name']}} </span> <span  class="font-bold border px-4 bg-white isolate inline-flex rounded-md shadow-sm mb-2">{{$audit['event']}}</span></p>
                        @foreach ($audit['affected_columns'] as $column_name => $column_values)

                        <div class="flex justify-between gap-x-4">
                            <div class="py-0.5 text-xs leading-5 text-gray-500">
                                <span class="font-medium text-sm text-gray-900">{{ Str::of($column_name)->camel()->replace('_', ' ')->replaceMatches('/[A-Z]/', ' $0')->title() }}</span>
                                <div class="tooltip max-w">
                                    <svg xmlns="http://www.w3.org/2000/svg" x="0px" y="0px" width="10" height="10" viewBox="0 0 24 24">
                                        <path d="M 12 2 C 6.4889971 2 2 6.4889971 2 12 C 2 17.511003 6.4889971 22 12 22 C 17.511003 22 22 17.511003 22 12 C 22 6.4889971 17.511003 2 12 2 z M 12 4 C 16.430123 4 20 7.5698774 20 12 C 20 16.430123 16.430123 20 12 20 C 7.5698774 20 4 16.430123 4 12 C 4 7.5698774 7.5698774 4 12 4 z M 11 7 L 11 9 L 13 9 L 13 7 L 11 7 z M 11 11 L 11 17 L 13 17 L 13 11 L 11 11 z"></path>
                                    </svg>
                                    <span class="tooltiptext ">
                                        @switch(explode('.',$column_name)[0])
                                        @case('comment')
                                        {{$column_values['new_value']}}
                                        @break
                                        @case('active')
                                        @if ($column_values['new_value'])
                                        Activated
                                        @else
                                        Deactivated
                                        @endif
                                        @break
                                        @case('created')
                                        Initial creation of the compound on COCONUT
                                        @break
                                        @case('organisms')
                                        @case('sampleLocations')
                                            <span class="font-bold">Detached from:</span> <br /> {{$column_values['old_value']?:'N/A'}} <br />
                                            <span class="font-bold">Attached to:</span> <br /> {{$column_values['new_value']?:'N/A'}} <br />
                                        @break
                                        @default
                                        Old Value: <br /> {{$column_values['old_value']??'N/A'}} <br />
                                        New Value: <br /> {{$column_values['new_value']??'N/A'}}
                                        @endswitch
                                    </span>
                                </div>
                            </div>
                        </div>


                        @endforeach
                        </p>
                    </div>
                    </p>
                    <time datetime="2023-01-23T10:32" class="flex-none py-0.5 text-xs leading-5 text-gray-500">{{$audit['created_at']}}</time>
                </li>
                @endforeach
            </ul>
        </div>
    </div>
</div>