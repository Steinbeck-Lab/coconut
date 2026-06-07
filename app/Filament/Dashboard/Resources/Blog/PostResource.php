<?php

namespace App\Filament\Dashboard\Resources\Blog;

use App\Filament\Dashboard\Resources\Blog\Concerns\AuthorizesBlogManagement;
use Stephenjude\FilamentBlog\Resources\PostResource as BasePostResource;

class PostResource extends BasePostResource
{
    use AuthorizesBlogManagement;
}
