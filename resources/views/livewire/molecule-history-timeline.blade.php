<div x-data="{ on: false}" x-effect="on && $wire.getHistory()">
    <div class="w-full max-w-xl">
        <div class="flex items-center justify-between">
            <span class="flex flex-grow flex-col">
                <span class="text-m font-medium leading-6 text-gray-900" id="availability-label">Show History</span>
            </span>
            <button
                :class="{ 'bg-gray-600': on==false, 'bg-green-600': on!=false }" type="button"
                class="bg-gray-200 relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-indigo-600 focus:ring-offset-2"
                role="switch" aria-checked="false" x-ref="switch" x-state:on="Enabled" x-state:off="Not Enabled"
                :class="{ 'bg-indigo-600': on, 'bg-gray-200': !(on) }" aria-labelledby="availability-label"
                aria-describedby="availability-description" :aria-checked="on.toString()" @click="on = !on">
                <span :class="{ 'translate-x-0': on==false, 'translate-x-5': on!=false }" aria-hidden="true"
                    class="translate-x-0 pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"
                    x-state:on="Enabled" x-state:off="Not Enabled"
                    :class="{ 'translate-x-5': on, 'translate-x-0': !(on) }"></span>
            </button>
        </div>

    </div>


    <div x-show="on">
        <div class="lg:col-start-3">
            <!-- Activity feed -->
            <h2 class="text-sm font-semibold leading-6 text-gray-900">Activity</h2>
            <ul role="list" class="mt-6 space-y-6">
                @foreach ($audit_data as $audit)
                <!-- <li class="relative flex gap-x-4">
                    <div class="absolute -bottom-6 left-0 top-0 flex w-6 justify-center">
                        <div class="w-px bg-gray-200"></div>
                    </div>
                    <div class="relative flex h-6 w-6 flex-none items-center justify-center bg-white">
                        <div class="h-1.5 w-1.5 rounded-full bg-gray-100 ring-1 ring-gray-300"></div>
                    </div>
                    <p class="flex-auto py-0.5 text-xs leading-5 text-gray-500"><span class="font-medium text-gray-900">{{$audit['user_name']}}</span> {{$audit['event']}} {{$audit['affected_column']}}</p>
                    <time datetime="2023-01-23T10:32" class="flex-none py-0.5 text-xs leading-5 text-gray-500">7d ago</time>
                </li> -->
                <li class="relative flex gap-x-4">
                    <div class="absolute -bottom-6 left-0 top-0 flex w-6 justify-center">
                        <div class="w-px bg-gray-200"></div>
                    </div>
                    <img src="https://images.unsplash.com/photo-1550525811-e5869dd03032?ixlib=rb-1.2.1&ixid=eyJhcHBfaWQiOjEyMDd9&auto=format&fit=facearea&facepad=2&w=256&h=256&q=80" alt="" class="relative mt-3 h-6 w-6 flex-none rounded-full bg-gray-50">
                    <div class="flex-auto rounded-md p-3 ring-1 ring-inset ring-gray-200">
                        <div class="flex justify-between gap-x-4">
                            <div class="py-0.5 text-xs leading-5 text-gray-500"><span class="font-medium text-gray-900">{{$audit['user_name']}}</span> {{$audit['event']}} {{$audit['affected_column']}}</div>
                            <time datetime="2023-01-23T15:56" class="flex-none py-0.5 text-xs leading-5 text-gray-500">{{$audit['created_at']}}</time>
                        </div>
                        <p class="text-sm leading-6 text-gray-500">@if ($audit['old_value'])
                        {{$audit['old_value']}} ->
                        @endif
                        @if ($audit['new_value'])
                            {{$audit['new_value']}}
                        @endif
                     </p>
                    </div>
                </li>
                @endforeach




                <!-- <li class="relative flex gap-x-4">
                    <div class="absolute left-0 top-0 flex h-6 w-6 justify-center">
                        <div class="w-px bg-gray-200"></div>
                    </div>
                    <div class="relative flex h-6 w-6 flex-none items-center justify-center bg-white">
                        <svg class="h-6 w-6 text-indigo-600" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M2.25 12c0-5.385 4.365-9.75 9.75-9.75s9.75 4.365 9.75 9.75-4.365 9.75-9.75 9.75S2.25 17.385 2.25 12zm13.36-1.814a.75.75 0 10-1.22-.872l-3.236 4.53L9.53 12.22a.75.75 0 00-1.06 1.06l2.25 2.25a.75.75 0 001.14-.094l3.75-5.25z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <p class="flex-auto py-0.5 text-xs leading-5 text-gray-500"><span class="font-medium text-gray-900">Alex Curren</span> paid the invoice.</p>
                    <time datetime="2023-01-24T09:20" class="flex-none py-0.5 text-xs leading-5 text-gray-500">1d ago</time>
                </li> -->
            </ul>

        </div>
    </div>
</div>