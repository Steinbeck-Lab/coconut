<div class="space-y-2" style="overflow: visible;">
    @forelse($getTableData($getRecord()->name) as $row)
        <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm" style="padding: 14px 24px;">
            <table style="width: 100%; border-collapse: collapse;">
                <tr>
                    <td style="vertical-align: top; padding-right: 20px;">
                        <!-- Organism Name (Scientific name style) -->
                        <h3 class="text-lg font-bold italic text-gray-900 dark:text-gray-100" style="margin: 0 0 6px 0;">
                            {{ $row['name'] }}
                        </h3>
                        
                        <!-- Molecule Count -->
                        <p class="text-sm text-gray-500 dark:text-gray-400" style="margin: 0 0 4px 0;">
                            Molecules - {{ number_format($row['molecule_count']) }}
                        </p>
                        
                        <!-- IRI Link -->
                        @if($row['iri'])
                            <a href="{{ $row['iri'] }}" 
                               target="_blank" 
                               class="text-sm text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 hover:underline"
                               style="word-break: break-all;">
                                IRI - {{ $row['iri'] }}
                            </a>
                        @endif
                    </td>
                    <td style="vertical-align: middle; width: 100px; text-align: right;">
                        <a href="{{ config('app.url').'/dashboard/organisms/'.$row['id'].'/edit' }}" 
                           target="_blank"
                           style="display: inline-block !important; visibility: visible !important; opacity: 1 !important; padding: 10px 24px; white-space: nowrap; background-color: #111827; color: white; font-size: 14px; font-weight: 500; border-radius: 8px; text-decoration: none;">
                            Edit
                        </a>
                    </td>
                </tr>
            </table>
        </div>
    @empty
        <div class="text-center py-6 px-4 rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50">
            <div class="mx-auto w-12 h-12 rounded-full bg-gray-100 dark:bg-gray-700 flex items-center justify-center mb-3">
                <svg class="h-6 w-6 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"></path>
                </svg>
            </div>
            <p class="text-sm font-medium text-gray-600 dark:text-gray-300">No similar organisms found</p>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">No organisms with matching genus in the database</p>
        </div>
    @endforelse
</div>
