<?php

namespace App\Services\DOI;

interface DOIService
{
    public function getDOIs();

    public function createDOI($identifier, $attributes = []);

    public function createDoiWithSuffix(string $suffix, array $metadata = []): array;

    public function getDOI($doi);

    public function updateDOI($doi, $metadata = []);

    public function deleteDOI($doi);

    public function getDOIActivity($doi);
}
