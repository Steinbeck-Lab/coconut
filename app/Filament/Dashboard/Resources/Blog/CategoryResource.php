<?php

namespace App\Filament\Dashboard\Resources\Blog;

use App\Filament\Dashboard\Resources\Blog\Concerns\AuthorizesBlogManagement;
use Stephenjude\FilamentBlog\Resources\CategoryResource as BaseCategoryResource;

class CategoryResource extends BaseCategoryResource
{
    use AuthorizesBlogManagement;
}
