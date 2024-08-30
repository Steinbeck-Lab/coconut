<div x-data="{ on: true}" x-effect="on && $wire.getHistory()" class="mt-5">
    <button type="button" x-on:click="on = !on" class="text-base font-semibold text-text-dark hover:text-slate-600">
        <span>History</span>
        <!-- <span x-show="on">Hide History</span> -->
    </button>

    <div x-show="on" class="bg-white px-4 pb-7 mt-2 shadow sm:rounded-lg sm:px-6 border">
        <div class="lg:col-start-3">
            <!-- Activity feed -->
            <ul role="list" class="mt-6 space-y-6">
                @foreach (array_reverse($audit_data) as $audit)
                <li class="relative flex gap-x-1">
                    <div class="absolute -bottom-6 left-0 top-0 flex w-6 justify-center">
                        <div class="w-px bg-gray-200"></div>
                    </div>
                    <div class="relative flex h-6 w-6 flex-none items-center justify-center bg-white">
                        <div class="h-1.5 w-1.5 rounded-full bg-gray-100 ring-1 ring-gray-300"></div>
                    </div>
                    <p>
                    <div class="flex-auto">
                        <p class="flex-col py-0.5 text-s leading-5 text-gray-500"><p class="font-medium text-gray-900">{{$audit['event']}} {{$audit['affected_column']}}</p> <p class="text-xs">{{$audit['user_name']}}</p></p>
                    </div>
                    </p>
                    <time datetime="2023-01-23T10:32" class="flex-none py-0.5 text-xs leading-5 text-gray-500">{{$audit['created_at']}}</time>
                </li>
                @endforeach
            </ul>
        </div>
    </div>
</div>