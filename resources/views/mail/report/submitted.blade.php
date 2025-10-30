@component('mail::message')
Hello {{ $user->name }}!

@if ($mail_to == 'owner')
@if ($event->report->report_category == 'REVOKE')
Thank you for your revocation request and for contributing to the curation process. It is pending review with our Curators. You will receive further updates via email.
@elseif ($event->report->report_category == 'UPDATE')
Thank you for your update request and for contributing to the curation process. It is pending review with our Curators. You will receive further updates via email.
@else
Thank you for your submission and for contributing to the curation process. It is pending review with our Curators. You will receive further updates via email.
@endif
@else
A {{ $readableCategory }} request has been submitted. Please review and take necessary actions.
@endif

## Compound Details
@if ($event->report->molecules && $event->report->molecules->count() > 0)
@foreach ($event->report->molecules as $molecule)
**COCONUT ID:** [{{ $molecule->identifier }}]({{ config('app.url') }}/compound/{{ $molecule->identifier }})  
**Compound Name:** {{ $molecule->name ?? 'N/A' }}
@endforeach
@else
_No compound information available_
@endif

## Report Information
**Title:** {{ $event->report->title }}  
@if ($event->report->evidence)
**Evidence/Comment:** {{ $event->report->evidence }}  
@endif
@if ($event->report->doi)
**DOI:** [{{ $event->report->doi }}](https://doi.org/{{ $event->report->doi }})  
@endif
@if ($event->report->comment)
**Comment:** {{ $event->report->comment }}  
@endif
**Status:** {{ ucwords(strtolower(str_replace('_', ' ', $event->report->status))) }}

@if ($event->report->report_category == 'UPDATE' && $event->report->suggested_changes)
@php
$suggestedChanges = $event->report->suggested_changes;
$changes = isset($suggestedChanges['overall_changes']) ? $suggestedChanges['overall_changes'] : null;
$hasAnyChanges = false;
@endphp

@if ($changes)
## Suggested Changes

@if (isset($changes['synonym_changes']))
@php
$deleteList = !empty($changes['synonym_changes']['delete']) ? $changes['synonym_changes']['delete'] : [];
$addList = [];
if (isset($changes['synonym_changes']['changes'])) {
    foreach ($changes['synonym_changes']['changes'] as $old => $new) {
        if ($new && trim($new) !== '') {
            $addList[] = $new;
        }
    }
}
@endphp
@if (count($deleteList) > 0 || count($addList) > 0)
@php $hasAnyChanges = true; @endphp
**Synonym Changes:**

@if (count($deleteList) > 0)
_Remove:_ {{ implode(', ', $deleteList) }}
@endif
@if (count($addList) > 0)
_Add:_ {{ implode(', ', $addList) }}
@endif

@endif
@endif

@if (isset($changes['name_change']) && ($changes['name_change']['old'] || $changes['name_change']['new']))
@php $hasAnyChanges = true; @endphp
**Name Change:**  
_From:_ {{ $changes['name_change']['old'] ?? 'N/A' }}  
_To:_ {{ $changes['name_change']['new'] ?? 'N/A' }}

@endif

@if (isset($changes['cas_changes']))
@php
$deleteList = !empty($changes['cas_changes']['delete']) ? $changes['cas_changes']['delete'] : [];
$addList = [];
if (isset($changes['cas_changes']['changes'])) {
    foreach ($changes['cas_changes']['changes'] as $old => $new) {
        if ($new && trim($new) !== '') {
            $addList[] = $new;
        }
    }
}
@endphp
@if (count($deleteList) > 0 || count($addList) > 0)
@php $hasAnyChanges = true; @endphp
**CAS Number Changes:**

@if (count($deleteList) > 0)
_Remove:_ {{ implode(', ', $deleteList) }}
@endif
@if (count($addList) > 0)
_Add:_ {{ implode(', ', $addList) }}
@endif

@endif
@endif

@if (isset($changes['geo_location_changes']))
@php
$deleteList = !empty($changes['geo_location_changes']['delete']) ? $changes['geo_location_changes']['delete'] : [];
$addList = [];
if (isset($changes['geo_location_changes']['changes'])) {
    foreach ($changes['geo_location_changes']['changes'] as $old => $new) {
        if ($new && trim($new) !== '') {
            $parts = explode('|', $new);
            foreach ($parts as $part) {
                if (trim($part)) {
                    $addList[] = trim($part);
                }
            }
        }
    }
}
@endphp
@if (count($deleteList) > 0 || count($addList) > 0)
@php $hasAnyChanges = true; @endphp
**Geographic Location Changes:**

@if (count($deleteList) > 0)
_Remove:_ {{ implode(', ', $deleteList) }}
@endif
@if (count($addList) > 0)
_Add:_ {{ implode(', ', $addList) }}
@endif

@endif
@endif

@if (isset($changes['organism_changes']))
@php
$deleteList = !empty($changes['organism_changes']['delete']) ? $changes['organism_changes']['delete'] : [];
$addList = !empty($changes['organism_changes']['add']) ? $changes['organism_changes']['add'] : [];
@endphp
@if (count($deleteList) > 0 || count($addList) > 0)
@php $hasAnyChanges = true; @endphp
**Organism Changes:**

@if (count($deleteList) > 0)
_Remove:_ {{ implode(', ', $deleteList) }}
@endif
@if (count($addList) > 0)
_Add:_ {{ implode(', ', $addList) }}
@endif

@endif
@endif

@if (isset($changes['citation_changes']))
@php
$deleteList = !empty($changes['citation_changes']['delete']) ? $changes['citation_changes']['delete'] : [];
$addList = !empty($changes['citation_changes']['add']) ? $changes['citation_changes']['add'] : [];
@endphp
@if (count($deleteList) > 0 || count($addList) > 0)
@php $hasAnyChanges = true; @endphp
**Citation Changes:**

@if (count($deleteList) > 0)
_Remove:_ {{ implode(', ', $deleteList) }}
@endif
@if (count($addList) > 0)
_Add:_ {{ implode(', ', $addList) }}
@endif

@endif
@endif

@if (!$hasAnyChanges)
_No specific changes detected. Please review the report for details._
@endif

@endif
@endif

@if ($event->report->report_category == 'SUBMISSION' && $event->report->suggested_changes && isset($event->report->suggested_changes['new_molecule_data']))
@php
$newMolecule = $event->report->suggested_changes['new_molecule_data'];
@endphp

## Submission Details

@if (isset($newMolecule['name']) && $newMolecule['name'])
**Compound Name:** {{ $newMolecule['name'] }}
@endif

@if (isset($newMolecule['canonical_smiles']) && $newMolecule['canonical_smiles'])
**Canonical SMILES:** {{ $newMolecule['canonical_smiles'] }}
@endif

@if (isset($newMolecule['reference_id']) && $newMolecule['reference_id'])
**Reference ID:** {{ $newMolecule['reference_id'] }}
@endif

@if (isset($newMolecule['link']) && $newMolecule['link'])
**Link:** {{ $newMolecule['link'] }}
@endif

@if (isset($newMolecule['structural_comments']) && $newMolecule['structural_comments'])
**Structural Comments:** {{ $newMolecule['structural_comments'] }}
@endif

@if (isset($newMolecule['mol_filename']) && $newMolecule['mol_filename'])
**MOL File:** {{ $newMolecule['mol_filename'] }}
@endif

@if (isset($newMolecule['references']) && !empty($newMolecule['references']))
**References:** {{ implode(', ', $newMolecule['references']) }}
@endif

@endif

@if ($mail_to == 'curator')
@component('mail::button', ['url' => $url])
Review Report
@endcomponent
@else
@component('mail::button', ['url' => $url])
View Report
@endcomponent
@endif

Thanks,<br>
{{ config('app.name') }}
@endcomponent
