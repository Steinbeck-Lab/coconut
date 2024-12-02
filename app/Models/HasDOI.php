<?php

namespace App\Models;

trait HasDOI
{
    public function generateDOI($doiService)
    {
        $doi_host = env('DOI_HOST', null);
        if (! is_null($doi_host)) {
            $identifier = $this->getIdentifier($this, 'identifier');
            if ($this->doi == null) {
                $url = 'https://coconut.naturalproducts.net/collections/'.$identifier;
                $attributes = $this->getMetadata();
                $attributes['url'] = $url;
                $doiResponse = $doiService->createDOI($identifier, $attributes);
                $this->doi = $doiResponse['data']['id'];
                $this->datacite_schema = $doiResponse;
                $this->save();
            }

        }

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
        $publicationYear = explode('-', $this->created_at)[0];
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
