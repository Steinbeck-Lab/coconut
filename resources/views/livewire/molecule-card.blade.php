<div class="bg-white rounded-lg hover:shadow-xl shadow border">
    @if ($molecule && $molecule->identifier)
        <a href="{{ route('compound', $molecule->identifier) }}" wire:navigate>
            <div class="group flex flex-col overflow-hidden">
                <div class="aspect-h-3 aspect-w-3 sm:aspect-none group-hover:opacity-75 h-56">
                    <livewire:molecule-depict2d :name="$molecule->name" :smiles="$molecule->canonical_smiles">
                </div>
                <div class="flex flex-1 border-t flex-col p-4 pb-2">
                    <div class="flex items-center">
                        @for ($i = 0; $i < $molecule->annotation_level; $i++)
                            <span class="text-amber-300">★</span>
                        @endfor
                        @for ($i = $molecule->annotation_level; $i < 5; $i++)
                            ☆
                        @endfor
                    </div>
                    <div class="flex flex-1 flex-col justify-end">
                        <p class="text-sm font-medium text-gray-500">{{ $molecule->identifier }}</p>
                    </div>
                    <div>
                        <h3 class="mt-1 text-base font-bold text-gray-900 capitalize text-clip overflow-hidden truncate ..." title="{{ $molecule->name }}">
                        {!! convert_italics_notation( $molecule->name ? $molecule->name : $molecule->iupac_name) !!}
                        </h3>
                    </div>
                </div>
            </div>
            <div class="flex justify-around p-4 py-2 border-t mt-1">
                <div class="flex-1 flex justify-center items-center text-xs" title="Organism Count">
                    <svg fill="currentColor" class="w-4 h-4 inline mr-1" version="1.1" id="Layer_1"
                        xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"
                        viewBox="0 0 512 512" xml:space="preserve">
                        <g>
                            <g>
                                <circle cx="162.914" cy="197.818" r="11.636" />
                            </g>
                        </g>
                        <g>
                            <g>
                                <circle cx="221.095" cy="221.091" r="11.636" />
                            </g>
                        </g>
                        <g>
                            <g>
                                <circle cx="209.459" cy="139.636" r="11.636" />
                            </g>
                        </g>
                        <g>
                            <g>
                                <path d="M453.823,290.909c-6.435,0-11.636,5.213-11.636,11.636c0,25.67-20.876,46.545-46.545,46.545
   c-44.905,0-81.455,36.538-81.455,81.455c0,32.081-26.1,58.182-58.182,58.182c-32.081,0-58.182-26.1-58.182-58.182v-11.636
   c0-6.423-5.201-11.636-11.636-11.636c-44.905,0-81.455-36.538-81.455-81.455V139.636c0-44.916,36.55-81.455,81.455-81.455
   s81.455,36.538,81.455,81.455v186.182c0,28.998-15.604,56.017-40.727,70.54c-5.574,3.212-7.471,10.333-4.259,15.895l17.455,30.231
   c3.212,5.562,10.345,7.459,15.895,4.259c5.574-3.212,7.482-10.333,4.259-15.895l-11.951-20.713
   c8.518-6.295,15.825-13.905,22.004-22.307l20.911,12.067c1.827,1.059,3.828,1.559,5.807,1.559c4.026,0,7.936-2.083,10.089-5.818
   c3.212-5.574,1.303-12.684-4.259-15.895l-20.911-12.067c4.585-10.473,7.494-21.679,8.448-33.292l27.462-9.158
   c6.086-2.036,9.391-8.623,7.354-14.72c-2.036-6.086-8.553-9.414-14.72-7.354l-19.584,6.516v-30.394h23.273
   c6.435,0,11.636-5.213,11.636-11.636S320.621,256,314.186,256h-23.273v-49.792l26.95-8.983c6.086-2.036,9.391-8.622,7.354-14.72
   c-2.036-6.097-8.553-9.414-14.72-7.354l-19.584,6.516v-30.394h23.273c6.435,0,11.636-5.213,11.636-11.636S320.621,128,314.186,128
   h-23.959c-1.187-10.659-3.991-20.841-8.134-30.301l20.771-11.985c5.562-3.212,7.471-10.321,4.259-15.895
   c-3.223-5.574-10.345-7.482-15.895-4.259l-20.852,12.044c-6.237-8.448-13.696-15.895-22.144-22.144l12.044-20.852
   c3.212-5.574,1.303-12.684-4.259-15.895c-5.562-3.212-12.684-1.303-15.895,4.259l-11.985,20.771
   c-9.472-4.166-19.654-6.959-30.313-8.145V11.636C197.823,5.213,192.621,0,186.186,0S174.55,5.213,174.55,11.636v23.959
   c-11.497,1.28-22.388,4.48-32.465,9.181l-23.156-24.064c-4.445-4.631-11.811-4.771-16.442-0.314
   c-4.631,4.457-4.771,11.823-0.314,16.454l19.607,20.375c-7.482,5.865-14.115,12.719-19.77,20.364L81.156,65.559
   c-5.562-3.223-12.684-1.315-15.907,4.259c-3.223,5.574-1.303,12.684,4.271,15.895l20.771,11.985
   c-4.154,9.46-6.959,19.642-8.145,30.301H58.186c-6.435,0-11.636,5.213-11.636,11.636s5.201,11.636,11.636,11.636h23.273v38.156
   l-26.95,8.983c-6.086,2.036-9.391,8.623-7.354,14.72c1.617,4.876,6.156,7.959,11.031,7.959c1.21,0,2.455-0.198,3.677-0.605
   l19.596-6.516V256H58.186c-6.435,0-11.636,5.213-11.636,11.636s5.201,11.636,11.636,11.636h23.273v30.394l-19.596-6.528
   c-6.144-2.06-12.684,1.268-14.72,7.354c-2.036,6.097,1.257,12.684,7.354,14.72l27.357,9.123c0.954,11.799,3.91,23.005,8.46,33.385
   l-20.806,12.009c-5.562,3.223-7.471,10.333-4.259,15.907c2.164,3.735,6.063,5.818,10.089,5.818c1.978,0,3.98-0.5,5.807-1.559
   l20.783-11.997c6.505,8.809,14.394,16.5,23.284,22.9l-13.766,33.036c-2.479,5.935,0.326,12.742,6.26,15.22
   c1.466,0.617,2.979,0.908,4.48,0.908c4.561,0,8.879-2.7,10.74-7.168l12.695-30.452c9.076,3.828,18.769,6.447,28.928,7.575v0.628
   c0,44.916,36.55,81.455,81.455,81.455s81.455-36.538,81.455-81.455c0-32.081,26.1-58.182,58.182-58.182
   c38.505,0,69.818-31.313,69.818-69.818C465.459,296.122,460.258,290.909,453.823,290.909z" />
                            </g>
                        </g>
                        <g>
                            <g>
                                <path d="M174.55,256c-25.67,0-46.545,20.876-46.545,46.545s20.876,46.545,46.545,46.545c25.67,0,46.545-20.876,46.545-46.545
   S200.22,256,174.55,256z M174.55,325.818c-12.835,0-23.273-10.438-23.273-23.273s10.438-23.273,23.273-23.273
   s23.273,10.438,23.273,23.273S187.385,325.818,174.55,325.818z" />
                            </g>
                        </g>
                    </svg>
                    <span>{{ $molecule->organism_count }}</span>
                </div>
                <div class="flex-1 flex justify-center items-center text-xs" title="Collection Count">
                    <svg class="w-4 h-5 inline mr-1" viewBox="0 0 16 16" xmlns="http://www.w3.org/2000/svg"
                        fill="currentColor">
                        <path
                            d="M2.5 3.5a.5.5 0 0 1 0-1h11a.5.5 0 0 1 0 1h-11zm2-2a.5.5 0 0 1 0-1h7a.5.5 0 0 1 0 1h-7zM0 13a1.5 1.5 0 0 0 1.5 1.5h13A1.5 1.5 0 0 0 16 13V6a1.5 1.5 0 0 0-1.5-1.5h-13A1.5 1.5 0 0 0 0 6v7zm1.5.5A.5.5 0 0 1 1 13V6a.5.5 0 0 1 .5-.5h13a.5.5 0 0 1 .5.5v7a.5.5 0 0 1-.5.5h-13z" />
                    </svg>
                    <span>{{ $molecule->collection_count }}</span>
                </div>
                <div class="flex-1 flex justify-center items-center text-xs" title="Geo Count">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                        stroke="currentColor" class="w-4 h-4 inline mr-1">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z" />
                    </svg>
                    <span>{{ $molecule->geo_count }}</span>
                </div>
                <div class="flex-1 flex justify-center items-center text-xs" title="Citation Count">
                    <svg class="w-auto h-3 inline mr-1" viewBox="0 0 16 16" xmlns="http://www.w3.org/2000/svg"
                        fill="currentColor">
                        <path fill-rule="evenodd"
                            d="M6 1h6v7a.5.5 0 0 1-.757.429L9 7.083 6.757 8.43A.5.5 0 0 1 6 8V1z" />
                        <path
                            d="M3 0h10a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2v-1h1v1a1 1 0 0 0 1 1h10a1 1 0 0 0 1-1V2a1 1 0 0 0-1-1H3a1 1 0 0 0-1 1v1H1V2a2 2 0 0 1 2-2z" />
                        <path
                            d="M1 5v-.5a.5.5 0 0 1 1 0V5h.5a.5.5 0 0 1 0 1h-2a.5.5 0 0 1 0-1H1zm0 3v-.5a.5.5 0 0 1 1 0V8h.5a.5.5 0 0 1 0 1h-2a.5.5 0 0 1 0-1H1zm0 3v-.5a.5.5 0 0 1 1 0v.5h.5a.5.5 0 0 1 0 1h-2a.5.5 0 0 1 0-1H1z" />
                    </svg>
                    <span>{{ $molecule->citation_count }}</span>
                </div>
            </div>
        </a>
    @endif
</div>
