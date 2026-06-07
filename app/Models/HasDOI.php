<?php

namespace App\Models;

trait HasDOI
{
    public function generateDOI($doiService)
    {
        $doi_host = config('doi.datacite.host');
        if (is_null($doi_host)) {
            return;
        }

        if ($this->doi !== null) {
            return;
        }

        if ($this instanceof Collection && $this->identifier) {
            $this->generateVersionedCollectionDois($doiService);

            return;
        }

        $identifier = $this->getIdentifier($this, 'identifier');
        $url = $this->collectionLandingUrl($identifier);
        $attributes = $this->getMetadata();
        $attributes['url'] = $url;
        $doiResponse = $doiService->createDOI($identifier, $attributes);
        $this->doi = $doiResponse['data']['id'];
        $this->datacite_schema = $doiResponse;
        $this->save();
    }

    public function generateVersionedCollectionDois($doiService): void
    {
        if (! ($this instanceof Collection)) {
            return;
        }

        $doi_host = config('doi.datacite.host');
        if (is_null($doi_host)) {
            return;
        }

        $identifier = (string) $this->identifier;
        $versionSuffix = $this->versionDoiSuffix();
        $baseSuffix = $this->baseDoiSuffix();
        $root = $this->lineageRoot();

        if ($this->doi === null) {
            $versionUrl = $this->collectionLandingUrl($identifier, $this->version);
            $attributes = $this->getMetadata();
            $attributes['url'] = $versionUrl;
            if ($root->doi_base) {
                $attributes['relatedIdentifiers'] = array_merge($attributes['relatedIdentifiers'] ?? [], [
                    [
                        'relatedIdentifier' => $root->doi_base,
                        'relatedIdentifierType' => 'DOI',
                        'relationType' => 'IsVersionOf',
                    ],
                ]);
            }

            $doiResponse = $doiService->createDoiWithSuffix($versionSuffix, $attributes);
            $this->doi = $doiResponse['data']['id'];
            $this->doi_suffix = $versionSuffix;
            $this->datacite_schema = $doiResponse;
            $this->save();
        }

        if ($root->doi_base === null) {
            $baseUrl = $this->collectionLandingUrl($identifier);
            $baseAttributes = $this->getMetadata();
            $baseAttributes['url'] = $baseUrl;
            $baseAttributes['titles'] = [
                ['title' => $this->title.' (latest)'],
            ];
            $baseResponse = $doiService->createDoiWithSuffix($baseSuffix, $baseAttributes);
            $root->doi_base = $baseResponse['data']['id'];
            $root->save();
        } else {
            $this->updateBaseDoiLanding($doiService, $root);
        }
    }

    public function updateBaseDoiLanding($doiService, ?Collection $root = null): void
    {
        if (! ($this instanceof Collection)) {
            return;
        }

        $root = $root ?? $this->lineageRoot();
        if (! $root->doi_base) {
            return;
        }

        $identifier = (string) $this->identifier;
        $baseUrl = $this->collectionLandingUrl($identifier);
        $related = [
            [
                'relatedIdentifier' => $this->doi,
                'relatedIdentifierType' => 'DOI',
                'relationType' => 'HasVersion',
            ],
        ];

        $doiService->updateDOI($root->doi_base, [
            'url' => $baseUrl,
            'relatedIdentifiers' => $related,
        ]);
    }

    protected function collectionLandingUrl(string $identifier, ?int $version = null): string
    {
        $base = rtrim(config('app.url', 'https://coconut.naturalproducts.net'), '/');
        $url = $base.'/collections/'.$identifier;
        if ($version !== null && $version > 1) {
            $url .= '?version='.$version;
        }

        return $url;
    }

    public function getIdentifier($model, $key)
    {
        return $model->getAttributes()[$key];
    }

    public function getMetadata()
    {
        $title = $this->title;
        $creators = [
            ['name' => 'COCONUT',
                'nameType' => 'Organizational', ],
        ];
        $description = [
            'description' => $this->description,
            'descriptionType' => 'Other',
        ];
        $relatedIdentifiers = [
            [
                'relatedIdentifier' => $this->url,
                'relatedIdentifierType' => 'URL',
                'relationType' => 'References',
            ],
        ];
        $dates = [
            [
                'date' => $this->created_at,
                'dateType' => 'Available',
            ],
            [
                'date' => $this->created_at,
                'dateType' => 'Submitted',
            ],
            [
                'date' => $this->updated_at,
                'dateType' => 'Updated',
            ],
        ];

        $rights = [
            [
                'rights' => 'Creative Commons Attribution 4.0 International',
                'rightsUri' => 'https://creativecommons.org/licenses/by/4.0/legalcode',
                'rightsIdentifier' => 'CC-BY-4.0',
                'rightsIdentifierScheme' => 'SPDX',
                'schemeUri' => 'https://spdx.org/licenses/',
            ],
        ];
        $publicationYear = explode('-', (string) $this->created_at)[0];
        $subjects = [
            ['subject' => 'Natural Product',
                'subjectScheme' => 'NCI Thesaurus OBO Edition',
                'schemeURI' => 'http://purl.obolibrary.org/obo/ncit/releases/2022-08-19/ncit.owl',
                'valueURI' => 'http://purl.obolibrary.org/obo/NCIT_C66892',
                'classificationCode' => 'NCIT:C66892',
            ],
        ];
        $attributes = [
            'creators' => $creators,
            'titles' => [
                [
                    'title' => $title,
                ],
            ],
            'dates' => $dates,
            'language' => 'en',
            'rightsList' => $rights,
            'descriptions' => [$description],
            'relatedIdentifiers' => $relatedIdentifiers,
            'resourceType' => 'Collection',
            'resourceTypeGeneral' => 'Collection',
            'publicationYear' => $publicationYear,
            'subjects' => $subjects,
            'types' => [
                'ris' => 'DATA',
                'bibtex' => 'misc',
                'schemaOrg' => 'Collection',
                'resourceType' => 'Collection',
                'resourceTypeGeneral' => 'Collection',
            ],
            'isActive' => true,
            'event' => 'publish',
            'state' => 'findable',
            'schemaVersion' => 'http://datacite.org/schema/kernel-4',

        ];

        return $attributes;
    }
}
