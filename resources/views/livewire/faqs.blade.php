<div>
    <div class="my-10">
        <h1 class="font-medium text-2xl max-w-3xl mx-auto pt-10 pb-4">Frequently asked questions</h1>
        <div class="bg-white max-w-3xl mx-auto">
            <ul class="shadow-box">
                @foreach ($faqs as $question => $answer)
                    <li class="relative border-b border-gray-200" x-data="{ selected: null }">
                        <button type="button" class="w-full px-6 py-6 text-left"
                            @click="selected !== {{ $loop->index }} ? selected = {{ $loop->index }} : selected = null">
                            <div class="flex items-center justify-between">
                                <span class="font-medium">
                                    {{ $question }} </span>
                            </div>
                        </button>
                        <div class="relative overflow-hidden transition-all max-h-0 duration-700"
                            x-ref="container{{ $loop->index }}"
                            x-bind:style="selected == {{ $loop->index }} ? 'max-height: ' + $refs.container{{ $loop->index }}
                                .scrollHeight + 'px' : ''">
                            <div class="p-6 -mt-6">
                                {{ $answer }}
                            </div>
                        </div>
                    </li>
                @endforeach
            </ul>
        </div>
    </div>
</div>
