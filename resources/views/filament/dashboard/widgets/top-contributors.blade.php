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
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4">
                @foreach(array_slice($contributors, 0, 10) as $contributor)
                <div class="relative rounded-2xl bg-white dark:bg-gray-800 border border-dashed border-gray-200 dark:border-gray-700 hover:border-gray-300 dark:hover:border-gray-600 hover:shadow-md transition-all duration-200 ease-in-out">
                    <div class="p-4">
                        <div class="flex flex-col items-center text-center space-y-3">
                            <!-- Avatar with status indicator -->
                            <div class="relative">
                                <div class="h-16 w-16 rounded-full bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center ring-2 ring-blue-50 dark:ring-blue-900/50">
                                    <img class="h-16 w-16 rounded-full object-cover" 
                                         src="{{ $contributor['avatar_url'] }}" 
                                         alt="{{ $contributor['user']->name }}"
                                         loading="lazy">
                                </div>
                                <div class="absolute -bottom-0.5 -right-0.5 h-5 w-5 rounded-full bg-green-500 ring-2 ring-white dark:ring-gray-800 flex items-center justify-center">
                                    <svg class="h-3 w-3 text-white" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                    </svg>
                                </div>
                            </div>
                            
                            <!-- User Name -->
                            <div class="w-full">
                                <h4 class="text-sm font-semibold text-gray-900 dark:text-white leading-tight line-clamp-2 min-h-[2.5rem]">
                                    {{ $contributor['user']->name }}
                                </h4>
                            </div>
                            
                            <!-- Contribution count badge -->
                            <div class="w-full">
                                <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg px-3 py-2">
                                    <div class="text-xl font-bold text-blue-600 dark:text-blue-400">
                                        {{ number_format($contributor['contribution_count']) }}
                                    </div>
                                    <div class="text-xs text-blue-500 dark:text-blue-400 font-medium">
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