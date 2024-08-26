<x-guest-layout>
    @section('title', $molecule['identifier'])
    @section('meta')
        <meta name="description"
        content="Molecule details for {{ $molecule->name ? $molecule->name : $molecule->iupac_name }}">
        <meta name="keywords" content="{{ implode(',', $molecule->synonyms ?? []) }}">
        <meta name="author" content="COCONUT">
        <meta property="og:title" content="{{ $molecule['identifier'] }} - COCONUT: COlleCtion of Open Natural prodUcTs">
        <meta property="og:description"
            content="Molecule details for {{ $molecule->name ? $molecule->name : $molecule->iupac_name }}">
        <meta property="og:type" content="website">
        <meta property="og:url" content="{{ url()->current() }}">
        <meta property="og:image"
            content="{{ env('CM_API') . 'depict/2D?smiles=' . urlencode($molecule->canonical_smiles) . '&height=630&width=1200&toolkit=cdk' ?? asset('img/coconut-og-image.png') }}">
        <meta property="og:image:width" content="1200">
        <meta property="og:image:height" content="630">
        <meta property="og:site_name" content="{{ config('app.name', 'COCONUT') }}">

        <meta name="twitter:card" content="summary_large_image">
        <meta name="twitter:title" content="{{ $molecule['identifier'] }} - COCONUT: COlleCtion of Open Natural prodUcTs">
        <meta name="twitter:description"
            content="Molecule details for {{ $molecule->name ? $molecule->name : $molecule->iupac_name }}">
        <meta name="twitter:image" content="{{ env('CM_API') . 'depict/2D?smiles=' . urlencode($molecule->canonical_smiles) . '&height=630&width=1200&toolkit=cdk' ?? asset('img/coconut-og-image.png') }}">
        <meta name="twitter:site" content="@coconutdatabase">
        <meta name="twitter:creator" content="@coconutdatabase">
    @overwrite
    @section('schema')
        @if (isset($molecule['schema']))
            {!! $molecule['schema'] !!}
        @else
            {!! $molecule->getSchema() !!}
        @endif
    @overwrite
    <div>
        <livewire:molecule-details :molecule="$molecule">
    </div>
</x-guest-layout>
