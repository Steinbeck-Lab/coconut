<?php

namespace App\Filament\Dashboard\Resources\Blog;

use App\Filament\Dashboard\Resources\Blog\Concerns\AuthorizesBlogManagement;
use Stephenjude\FilamentBlog\Resources\AuthorResource as BaseAuthorResource;

class AuthorResource extends BaseAuthorResource
{
    use AuthorizesBlogManagement;
}
