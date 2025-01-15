<?php

namespace App\Filament\Traits;

use Str;

trait MutatesCollectionFormData
{
    protected static function mutateFormData(array $data): array
    {
        $data['slug'] = Str::slug($data['title']);

        return $data;
    }
}
