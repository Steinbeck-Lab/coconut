<div>
    <div class="mt-32 min-h-screen py-16 isolate">
        <div class="relative isolate -z-10">
            <svg class="absolute inset-x-0 top-0 -z-10 h-[64rem] w-full stroke-gray-200 [mask-image:radial-gradient(32rem_32rem_at_center,white,transparent)]"
                aria-hidden="true">
                <defs>
                    <pattern id="1f932ae7-37de-4c0a-a8b0-a6e3b4d44b84" width="200" height="200" x="50%" y="-1"
                        patternUnits="userSpaceOnUse">
                        <path d="M.5 200V.5H200" fill="none" />
                    </pattern>
                </defs>
                <svg x="50%" y="-1" class="overflow-visible fill-gray-50">
                    <path d="M-200 0h201v201h-201Z M600 0h201v201h-201Z M-400 600h201v201h-201Z M200 800h201v201h-201Z"
                        stroke-width="0" />
                </svg>
                <rect width="100%" height="100%" stroke-width="0"
                    fill="url(#1f932ae7-37de-4c0a-a8b0-a6e3b4d44b84)" />
            </svg>
            <div class="absolute left-1/2 right-0 top-0 -z-10 -ml-24 transform-gpu overflow-hidden blur-3xl lg:ml-24 xl:ml-48"
                aria-hidden="true">
                <div class="aspect-[801/1036] w-[50.0625rem] bg-gradient-to-tr from-[#ff80b5] to-[#9089fc] opacity-30"
                    style="
                  clip-path: polygon(
                    63.1% 29.5%,
                    100% 17.1%,
                    76.6% 3%,
                    48.4% 0%,
                    44.6% 4.7%,
                    54.5% 25.3%,
                    59.8% 49%,
                    55.2% 57.8%,
                    44.4% 57.2%,
                    27.8% 47.9%,
                    35.1% 81.5%,
                    0% 97.7%,
                    39.2% 100%,
                    35.2% 81.4%,
                    97.2% 52.8%,
                    63.1% 29.5%
                  );
                ">
                </div>
            </div>
            <div class="overflow-hidden">
                <div class="mx-auto max-w-6xl pb-32 px-8">
                    <div class="mx-auto max-w-2xl gap-x-14 lg:mx-0 lg:flex lg:max-w-none lg:items-center">
                        <div class="w-full max-w-xl lg:shrink-0 xl:max-w-2xl">
                            <h1 class="text-3xl font-bold tracking-tight text-gray-900 sm:text-5xl">
                                COCONUT Analytics: Natural Product Chemical Space Exploration
                            </h1>
                            <p class="relative mt-6 text-lg leading-7 text-gray-600 sm:max-w-md lg:max-w-none">
                                Natural product chemistry is intrinsically complex, with molecules exhibiting diverse structural features and bioactivities. This analytics dashboard provides comprehensive insights into the COCONUT (COlleCtion of Open NatUral producTs) database, enabling researchers to explore chemical space distributions, structural patterns, and property relationships across various natural product families.
                            </p>
                        </div>
                        <div class="mt-14 flex justify-end gap-8 sm:-mt-44 sm:justify-start sm:pl-20 lg:mt-0 lg:pl-0">
                            <div
                                class="ml-auto w-44 flex-none space-y-8 pt-32 sm:ml-0 sm:pt-80 lg:order-last lg:pt-36 xl:order-none xl:pt-80">
                                <div class="relative">
                                    <img src="img/collections/1.png" alt="About us image"
                                        class="aspect-[2/3] w-full rounded-xl bg-gray-900/5 object-cover shadow-lg" />
                                    <div
                                        class="pointer-events-none absolute inset-0 rounded-xl ring-1 ring-inset ring-gray-900/10">
                                    </div>
                                </div>
                            </div>
                            <div class="mr-auto w-44 flex-none space-y-8 sm:mr-0 sm:pt-52 lg:pt-36">
                                <div class="relative">
                                    <img src="img/collections/2.png" alt="About us image"
                                        class="aspect-[2/3] w-full rounded-xl bg-gray-900/5 object-cover shadow-lg" />
                                    <div
                                        class="pointer-events-none absolute inset-0 rounded-xl ring-1 ring-inset ring-gray-900/10">
                                    </div>
                                </div>

                            </div>
                            <div class="w-44 flex-none space-y-8 pt-32 sm:pt-0">
                                <div class="relative">
                                    <img src="img/collections/4.png" alt="About us image"
                                        class="aspect-[2/3] w-full rounded-xl bg-gray-900/5 object-cover shadow-lg" />
                                    <div
                                        class="pointer-events-none absolute inset-0 rounded-xl ring-1 ring-inset ring-gray-900/10">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="mx-auto max-w-6xl pb-32 px-8 w-full">
            <livewire:density-plot />
        </div>
        <div class="mx-auto max-w-6xl pb-32 px-8 w-full">
            <livewire:annotation-score-plot />
        </div>
        <div class="mx-auto max-w-6xl pb-32 px-8 w-full">
            <div class=" grid grid-cols-1 md:grid-cols-2 gap-2 p-2">
                @foreach ($properties_json_data as $name => $property)
                    @if ($name != 'np_likeness')
                        @livewire('properties-plot', [
                        'property' => $property,
                        'name' => $name
                        ])
                    @endif
                @endforeach
            </div>
        </div>
        <div class="mx-auto max-w-6xl pb-32 px-8 w-full">
            @foreach ($bubble_frequency_json_data as $chartName => $chartData)
            @livewire('bubble-frequency-plot', [
                'chartName' => $chartName,
                'chartData' => $chartData,
                ])
            @endforeach
        </div>
        <div class="mx-auto max-w-6xl pb-32 px-8 w-full">
            @livewire('collection-overlap')
        </div>
    </div>
</div>