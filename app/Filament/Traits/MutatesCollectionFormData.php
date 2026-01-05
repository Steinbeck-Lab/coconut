<?php

namespace App\Filament\Traits;

use Illuminate\Support\Str;

trait MutatesCollectionFormData
{
    public static function mutateFormData(array $data): array
    {
        $data['slug'] = Str::slug($data['title']);

        return $data;
    }
}
