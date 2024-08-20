<?php

namespace App\Services\DOI;

interface DOIService
{
    public function getDOIs();

    public function createDOI($identifier, $attributes = []);

    public function getDOI($doi);

    public function updateDOI($doi);

    public function deleteDOI($doi);

    public function getDOIActivity($doi);
}
