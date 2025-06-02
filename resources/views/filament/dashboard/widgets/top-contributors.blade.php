@php
    $contributors = $this->getTopContributors();
@endphp

<x-filament-widgets::widget class="w-full">
    <x-filament::section>
        <x-slot name="heading">
            <div class="flex items-center space-x-3">
                <div class="flex-shrink-0">
                    <div class="w-8 h-8 rounded-lg bg-primary-50 dark:bg-primary-900/20 flex items-center justify-center">
                        <svg class="h-5 w-5 text-primary-600 dark:text-primary-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />
                        </svg>
                    </div>
                </div>
                <div>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Top Contributors</h3>
                </div>
            </div>
        </x-slot>

        @if(count($contributors) > 0)
            <div class="flex gap-6 overflow-x-auto pb-4 px-2">
                @foreach($contributors as $contributor)
                <div class="relative overflow-hidden rounded-xl bg-white shadow-lg border border-t ring-1 ring-gray-200/50 dark:bg-gray-800 dark:ring-gray-700/50 hover:shadow-xl hover:ring-gray-300/60 dark:hover:ring-gray-600/60 hover:scale-105 transition-all duration-300 ease-in-out flex-shrink-0 w-36 border-0">
                    <div class="p-5">
                        <div class="flex flex-col items-center text-center space-y-4">
                            <!-- Avatar with enhanced styling -->
                            <div class="relative">
                                <img class="h-14 w-14 rounded-full ring-3 ring-gray-100 dark:ring-gray-700 object-cover shadow-md" 
                                     src="{{ $contributor['avatar_url'] }}" 
                                     alt="{{ $contributor['user']->name }}"
                                     loading="lazy">
                                <div class="absolute -bottom-1 -right-1 h-5 w-5 rounded-full bg-green-500 ring-2 ring-white dark:ring-gray-800 flex items-center justify-center">
                                    <svg class="h-3 w-3 text-white" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                    </svg>
                                </div>
                            </div>
                            
                            <!-- User Info with improved typography -->
                            <div class="space-y-1">
                                <h4 class="text-sm font-bold text-gray-900 dark:text-white leading-tight line-clamp-2 min-h-[2.5rem] flex items-center">
                                    {{ $contributor['user']->name }}
                                </h4>
                            </div>
                            
                            <!-- Enhanced contribution badge -->
                            <div class="w-full">
                                <div class="bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 rounded-lg px-3 py-2 border border-blue-200/60 dark:border-blue-700/60">
                                    <div class="text-lg font-bold text-blue-700 dark:text-blue-300">
                                        {{ number_format($contributor['contribution_count']) }}
                                    </div>
                                    <div class="text-xs text-blue-600 dark:text-blue-400 font-medium">
                                        contributions
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        @else
            <div class="text-center py-8">
                <div class="flex justify-center mb-4">
                    <div class="w-12 h-12 rounded-lg bg-gray-100 dark:bg-gray-800 flex items-center justify-center">
                        <svg class="h-6 w-6 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />
                        </svg>
                    </div>
                </div>
                <h3 class="text-sm font-medium text-gray-900 dark:text-white mb-1">No contributors found</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400">No user contributions have been recorded yet.</p>
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget> 