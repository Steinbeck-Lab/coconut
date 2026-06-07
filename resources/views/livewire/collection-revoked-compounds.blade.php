@if ($count > 0)
    <div class="mt-8 border-t border-gray-200 pt-8">
        <button type="button" wire:click="toggle" class="flex w-full items-center justify-between text-left">
            <h2 class="text-xl font-semibold text-gray-900">Revoked compounds ({{ $count }})</h2>
            <span class="text-sm text-gray-500">{{ $expanded ? 'Hide' : 'Show' }}</span>
        </button>
        <p class="mt-2 text-sm text-gray-600">
            Compounds removed during a collection version update. They are no longer active for this source in the current release.
        </p>

        @if ($expanded)
            <ul class="mt-4 space-y-4">
                @foreach ($revocations as $revocation)
                    <li class="rounded-lg border border-gray-200 p-4 text-sm">
                        <div class="flex flex-wrap gap-4">
                            @if ($revocation->standardized_canonical_smiles)
                                <img
                                    src="{{ config('services.cheminf.api_url') }}depict/2D?smiles={{ urlencode($revocation->standardized_canonical_smiles) }}&height=120&width=120"
                                    alt="Structure"
                                    class="h-28 w-28 rounded border border-gray-100"
                                />
                            @endif
                            <div>
                                <p><span class="font-medium">Reference:</span> {{ $revocation->reference_id ?? '—' }}</p>
                                <p><span class="font-medium">Dropped in:</span> v{{ $revocation->fromCollection?->version }} → newer version</p>
                                <p><span class="font-medium">Revoked:</span> {{ $revocation->revoked_at?->format('Y-m-d') }}</p>
                                @if ($revocation->molecule?->identifier)
                                    <p class="mt-1">
                                        <a href="{{ url('/molecules/'.$revocation->molecule->identifier) }}" class="text-blue-600 underline">View molecule</a>
                                    </p>
                                @endif
                            </div>
                        </div>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
@endif
