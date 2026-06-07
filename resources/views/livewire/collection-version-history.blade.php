<div class="mt-8 border-t border-gray-200 pt-8">
    <h2 class="text-xl font-semibold text-gray-900">Version history</h2>
    @if ($baseDoi)
        <p class="mt-2 text-sm text-gray-600">
            Latest DOI:
            <a href="https://doi.org/{{ $baseDoi }}" class="text-blue-600 underline" target="_blank" rel="noopener">{{ $baseDoi }}</a>
        </p>
    @endif

    <div class="mt-4 overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-3 py-2 text-left font-medium text-gray-700">Version</th>
                    <th class="px-3 py-2 text-left font-medium text-gray-700">Status</th>
                    <th class="px-3 py-2 text-left font-medium text-gray-700">DOI</th>
                    <th class="px-3 py-2 text-left font-medium text-gray-700">Released</th>
                    <th class="px-3 py-2 text-left font-medium text-gray-700">Entries</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach ($versions as $version)
                    <tr class="@if($selected && $selected->id === $version->id) bg-blue-50 @endif">
                        <td class="px-3 py-2">
                            <button type="button" wire:click="selectVersion({{ $version->version }})" class="text-blue-600 hover:underline">
                                v{{ $version->version }}
                            </button>
                        </td>
                        <td class="px-3 py-2">
                            @if ($version->is_latest)
                                <span class="rounded bg-green-100 px-2 py-0.5 text-green-800">Current</span>
                            @else
                                <span class="rounded bg-gray-100 px-2 py-0.5 text-gray-700">Superseded</span>
                            @endif
                        </td>
                        <td class="px-3 py-2">
                            @if ($version->doi)
                                <a href="https://doi.org/{{ $version->doi }}" class="text-blue-600 underline" target="_blank" rel="noopener">{{ $version->doi }}</a>
                            @else
                                —
                            @endif
                        </td>
                        <td class="px-3 py-2">{{ $version->release_date?->format('Y-m-d') ?? $version->superseded_at?->format('Y-m-d') ?? '—' }}</td>
                        <td class="px-3 py-2">{{ $version->is_latest ? $version->total_entries : ($version->archived_entries_count ?? $version->total_entries) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    @if ($selected && ! $selected->is_latest)
        <p class="mt-4 rounded-md bg-amber-50 px-4 py-3 text-sm text-amber-900">
            You are viewing metadata for <strong>v{{ $selected->version }}</strong>.
            <a href="?" class="font-semibold underline">Jump to current version</a>
        </p>
    @endif

    @if ($selected)
        <div class="mt-4 rounded-lg border border-gray-200 bg-gray-50 p-4 text-sm text-gray-700">
            <p class="font-medium text-gray-900">{{ $selected->title }} (v{{ $selected->version }})</p>
            <p class="mt-2">{{ $selected->description }}</p>
            @if ($selected->url)
                <p class="mt-2"><a href="{{ $selected->url }}" class="text-blue-600 underline" target="_blank" rel="noopener">Source URL</a></p>
            @endif
        </div>
    @endif
</div>
